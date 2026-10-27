<?php

namespace Jarryd\RouteNotes\Commands;

use Illuminate\Foundation\Console\RouteListCommand as BaseCommand;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Jarryd\RouteNotes\Support\DocBlockParser;
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

        // Notes are newline separated, matching how the parent stores middleware.
        $information['note'] = implode("\n", $notes);

        return $information;
    }

    /**
     * Get the fluent and PHPDoc notes for the given route, fluent notes first.
     *
     * @return array<int, string>
     */
    protected function getNotes(Route $route): array
    {
        /** @var array<int, string> $fluentNotes */
        $fluentNotes = $route->getAction('notes') ?? [];

        $uses = $route->getAction('uses');
        $docNotes = $uses === null ? [] : DocBlockParser::getNotes($uses);

        return array_values(array_unique(array_merge($fluentNotes, $docNotes)));
    }

    /**
     * @param  Collection<array-key, array<string, mixed>>  $routes
     * @return string
     */
    protected function asJson($routes)
    {
        return parent::asJson($routes->map(function (array $route) {
            $route['note'] = empty($route['note']) ? [] : explode("\n", (string) $route['note']);

            return $route;
        }));
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

        $notes = $routes->values()->map(fn (array $route) => (string) ($route['note'] ?? ''));

        $output = [];
        $index = -1;

        foreach ($lines as $line) {
            $isRoute = str_starts_with($line, '  <fg=white;options=bold>');

            // A route's block ends at the next route, or at the blank line before the route count.
            if ($index >= 0 && ($isRoute || $line === '')) {
                $this->appendNotes($output, $notes->get($index, ''), $maxMethod);
            }

            $index = $isRoute ? $index + 1 : ($line === '' ? -1 : $index);

            $output[] = $line;
        }

        return $output;
    }

    /**
     * @param  array<int, string>  $output
     */
    protected function appendNotes(array &$output, string $notes, int $maxMethod): void
    {
        foreach (array_filter(explode("\n", $notes)) as $note) {
            $output[] = sprintf('<fg=yellow>         %s✎ %s</>', str_repeat(' ', $maxMethod), OutputFormatter::escape($note));
        }
    }
}
