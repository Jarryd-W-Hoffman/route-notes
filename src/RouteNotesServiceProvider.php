<?php

namespace Jarryd\RouteNotes;

use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;
use Jarryd\RouteNotes\Commands\RouteListCommand;

class RouteNotesServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->extend('command.route.list', function ($command, $app) {
            return new RouteListCommand($app->make($app['router']));
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                RouteListCommand::class,
            ]);
        }

        Route::macro('note', function (string $value) {
            $notes = $this->defaults['_route_notes'] ?? [];
            $notes[] = $value;

            return $this->defaults('_route_notes', $notes);
        });

        Route::macro('notes', function (array $values) {
            $notes = $this->defaults['_route_notes'] ?? [];
            $notes = array_merge($notes, $values);

            return $this->defaults('_route_notes', $notes);
        });
    }
}
