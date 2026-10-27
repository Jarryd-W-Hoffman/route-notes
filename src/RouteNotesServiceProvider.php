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

        // Notes live in the route action rather than the route defaults, as
        // defaults are merged into the route parameters at dispatch time.
        Route::macro('note', function (string $value) {
            $this->action['notes'][] = $value;

            return $this;
        });

        Route::macro('notes', function (array $values) {
            $this->action['notes'] = array_merge($this->action['notes'] ?? [], array_values($values));

            return $this;
        });
    }
}
