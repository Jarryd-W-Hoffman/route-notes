<?php

namespace Jarryd\RouteNotes\Commands;

use Illuminate\Foundation\Console\RouteListCommand as BaseCommand;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Jarryd\RouteNotes\Note;
use Jarryd\RouteNotes\NoteLevel;
use Jarryd\RouteNotes\Support\NoteReader;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputOption;

class RouteListCommand extends BaseCommand
{
    /**
     * @var string[]
     */
    protected $headers = ['Domain', 'Method', 'URI', 'Name', 'Action', 'Middleware', 'Path', 'Note'];

    public function __construct(Router $router)
    {
        parent::__construct($router);

        // Added here rather than via getOptions(), which newer framework versions
        // ignore in favour of the parent command's $signature.
        $this->addOption('notes', null, InputOption::VALUE_NONE, 'Only show routes that have notes');
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function getRouteInformation(Route $route)
    {
        /** @var array<string, mixed>|null $information */
        $information = parent::getRouteInformation($route);

        if ($information === null) {
            return null;
        }

        $notes = $this->getNotes($route);

        if ($this->option('notes') && $notes === []) {
            return null;
        }

        // Stored as plain arrays so sorting and JSON output work like the other columns.
        $information['note'] = array_map(fn (Note $note) => $note->toArray(), $notes);

        return $information;
    }

    /**
     * Get the fluent and PHPDoc notes for the given route, fluent notes first.
     *
     * @return array<int, Note>
     */
    protected function getNotes(Route $route): array
    {
        /** @var array<int, string|array{text: string, level?: string}> $fluentNotes */
        $fluentNotes = $route->getAction('notes') ?? [];

        $notes = array_merge(
            array_map(Note::fromArray(...), $fluentNotes),
            NoteReader::getNotes($route->getAction('uses')),
        );

        $unique = [];

        foreach ($notes as $note) {
            $unique[$note->level->value.':'.$note->text] ??= $note;
        }

        return array_values($unique);
    }

    /**
     * Render the parent CLI output, then add each route's notes beneath it.
     *
     * @param  Collection<array-key, array<string, mixed>>  $routes
     * @return array<int, string>
     */
    protected function forCli($routes)
    {
        $lines = parent::forCli($routes);

        $maxMethod = mb_strlen((string) $routes
            ->map(fn (array $route) => $route['method'] === 'GET|HEAD|POST|PUT|PATCH|DELETE|OPTIONS' ? 'ANY' : $route['method'])
            ->max());

        $notes = $routes->values()->map(fn (array $route) => $route['note'] ?? []);

        $output = [];
        $index = -1;

        foreach ($lines as $line) {
            $isRoute = str_starts_with($line, '  <fg=white;options=bold>');

            // A route's block ends at the next route, or at the blank line before the route count.
            if ($index >= 0 && ($isRoute || $line === '')) {
                $this->appendNotes($output, $notes->get($index, []), $maxMethod);
            }

            $index = $isRoute ? $index + 1 : ($line === '' ? -1 : $index);

            $output[] = $line;
        }

        return $output;
    }

    /**
     * @param  array<int, string>  $output
     * @param  array<int, array{text: string, level: string}>  $notes
     */
    protected function appendNotes(array &$output, array $notes, int $maxMethod): void
    {
        foreach (array_map(Note::fromArray(...), $notes) as $note) {
            $output[] = sprintf(
                '<fg=%s>         %s✎ %s%s</>',
                $note->level->color(),
                str_repeat(' ', $maxMethod),
                $note->level === NoteLevel::Info ? '' : "[{$note->level->value}] ",
                OutputFormatter::escape($note->text),
            );
        }
    }
}
