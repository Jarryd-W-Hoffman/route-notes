<?php

namespace Jarryd\RouteNotes\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Router;
use Jarryd\RouteNotes\Note;
use Jarryd\RouteNotes\NoteLevel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'route:notes')]
class RouteNotesCommand extends RouteListCommand
{
    public function __construct(Router $router)
    {
        parent::__construct($router);

        $this->setName('route:notes');
        $this->setDescription('Export the routes that have notes as a Markdown report');
        $this->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Write the report to the given file instead of the console');
    }

    protected function onlyRoutesWithNotes(): bool
    {
        return true;
    }

    /**
     * @param  array<int, array<string, mixed>>  $routes
     */
    protected function displayRoutes(array $routes): void
    {
        if ($this->option('json')) {
            parent::displayRoutes($routes);

            return;
        }

        $markdown = $this->toMarkdown($routes);

        if (is_string($path = $this->option('output'))) {
            $files = $this->laravel->make(Filesystem::class);

            $files->ensureDirectoryExists(dirname($path));
            $files->put($path, $markdown);

            $this->components->info(sprintf('Route notes written to [%s].', $path));

            return;
        }

        $this->output->write($markdown, false, OutputInterface::OUTPUT_RAW);
    }

    /**
     * @param  array<int, array<string, mixed>>  $routes
     */
    protected function toMarkdown(array $routes): string
    {
        $lines = [
            '# Route Notes',
            '',
            sprintf('%d %s with notes.', count($routes), count($routes) === 1 ? 'route' : 'routes'),
            '',
            '| Method | URI | Name | Action | Notes |',
            '| --- | --- | --- | --- | --- |',
        ];

        foreach ($routes as $route) {
            /** @var array<int, array{text: string, level: string}> $notes */
            $notes = $route['note'] ?? [];

            $lines[] = sprintf(
                '| %s | %s | %s | %s | %s |',
                $this->escape((string) $route['method']),
                $this->code(($route['domain'] ? $route['domain'].'/' : '').$route['uri']),
                $route['name'] ? $this->code((string) $route['name']) : '',
                $this->code((string) $route['action']),
                implode('<br>', array_map(fn (array $note) => $this->formatNote(Note::fromArray($note)), $notes)),
            );
        }

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    protected function formatNote(Note $note): string
    {
        $text = $this->escape(htmlspecialchars($note->text, ENT_NOQUOTES));

        return $note->level === NoteLevel::Info ? $text : sprintf('**%s:** %s', ucfirst($note->level->value), $text);
    }

    protected function code(string $value): string
    {
        return '`'.$this->escape($value).'`';
    }

    /**
     * Escape a value so it cannot break out of its table cell.
     */
    protected function escape(string $value): string
    {
        return str_replace(['|', "\r", "\n"], ['\|', ' ', ' '], $value);
    }
}
