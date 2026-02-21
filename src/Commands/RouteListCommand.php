<?php
namespace Jarryd\RouteNotes\Commands;

use Illuminate\Foundation\Console\RouteListCommand as BaseCommand;
use Jarryd\RouteNotes\Support\DocBlockParser;
use Symfony\Component\Console\Input\InputOption;

class RouteListCommand extends BaseCommand
{
    protected function getOptions()
    {
        return array_merge(parent::getOptions(), [
            ['notes', null, InputOption::VALUE_NONE, 'Only show routes that have notes'],
        ]);
    }

    protected function displayRoutes(array $routes)
    {
        $allRoutes = $this->router->getRoutes();
        $showOnlyWithNotes = $this->option('notes');

        $processedRoutes = collect($routes)->map(function ($route) use ($allRoutes) {
            $method = explode('|', $route['method'])[0];
            $actualRoute = collect($allRoutes->get($method))
                ->first(fn($r) => $r->uri() === $route['uri']);

            if (!$actualRoute) {
                $route['note'] = '';
                return $route;
            }

            $fluentNotes = $actualRoute->defaults['_route_notes'] ?? [];

            $action = $actualRoute->getAction('uses');
            $docNotes = DocBlockParser::getNotes($action);

            $allNotes = array_unique(array_merge($fluentNotes, $docNotes));

            $route['note'] = implode(' | ', $allNotes);

            return $route;
        });

        if ($showOnlyWithNotes) {
            $processedRoutes = $processedRoutes->filter(fn($r) => !empty($r['note']));
        }

        $this->table($this->getHeaders(), $processedRoutes->toArray());
    }

    protected function getHeaders()
    {
        $headers = parent::getHeaders();

        if (!in_array('Note', $headers)) {
            $headers[] = 'Note';
        }

        return $headers;
    }
}
