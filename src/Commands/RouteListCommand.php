<?php

namespace Jarryd\RouteNotes\Commands;

use Illuminate\Foundation\Console\RouteListCommand as BaseCommand;
use Illuminate\Routing\Route;
use Jarryd\RouteNotes\Support\DocBlockParser;
use Symfony\Component\Console\Input\InputOption;

class RouteListCommand extends BaseCommand
{
    protected $headers = ['Domain', 'Method', 'URI', 'Name', 'Action', 'Middleware', 'Note'];

    /**
     * @return array<int, array{0:string, 1:string|null, 2:int, 3:string}>
     */
    protected function getOptions(): array
    {
        return array_merge(parent::getOptions(), [
            ['notes', null, InputOption::VALUE_NONE, 'Only show routes that have notes'],
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function getRouteInformation(Route $route)
    {
        $information = parent::getRouteInformation($route);

        // @phpstan-ignore identical.alwaysFalse
        if ($information === null) {
            return null;
        }

        $fluentNotes = $route->defaults['_route_notes'] ?? [];
        $docNotes = DocBlockParser::getNotes($route->getAction('uses'));
        $allNotes = array_unique(array_merge($fluentNotes, $docNotes));

        $information['note'] = implode(' | ', $allNotes);

        return $information;
    }

    /**
     * @param  array<string, mixed>[]  $routes
     */
    protected function displayRoutes(array $routes): void
    {
        $routes = collect($routes);

        if ($this->option('notes')) {
            $routes = $routes->filter(fn ($r) => ! empty($r['note']));
        }

        $this->table($this->getHeaders(), $routes->toArray());
    }
}
