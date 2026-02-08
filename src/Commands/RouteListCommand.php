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
            // 1. Locate the actual Route object
            $method = explode('|', $route['method'])[0];
            $actualRoute = collect($allRoutes->get($method))
                ->first(fn($r) => $r->uri() === $route['uri']);

            if (!$actualRoute) {
                $route['note'] = '';
                return $route;
            }

            // 2. Fetch Fluent Notes (from ->note() or ->notes())
            $fluentNotes = $actualRoute->defaults['_route_notes'] ?? [];

            // 3. Fetch PHPDoc Notes (from @note in Controller)
            // Use 'uses' action to get the Closure or Controller string
            $action = $actualRoute->getAction('uses');
            $docNotes = DocBlockParser::getNotes($action);

            // 4. Merge them (Fluent notes first, as per your README)
            $allNotes = array_unique(array_merge($fluentNotes, $docNotes));

            // Format for the table display
            $route['note'] = implode(' | ', $allNotes);

            return $route;
        });

        // 5. Apply the --notes CLI filter
        if ($showOnlyWithNotes) {
            $processedRoutes = $processedRoutes->filter(fn($r) => !empty($r['note']));
        }

        // Render the table using the parent's table helper
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
