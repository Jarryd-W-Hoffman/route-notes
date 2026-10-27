<?php

namespace Jarryd\RouteNotes;

use Illuminate\Foundation\Console\RouteListCommand as BaseCommand;
use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;
use Jarryd\RouteNotes\Commands\RouteListCommand;

class RouteNotesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Swap the framework's command for ours wherever it is resolved, rather
        // than registering a second command under the same name.
        $this->app->extend(BaseCommand::class, function ($command, $app) {
            return new RouteListCommand($app['router']);
        });
    }

    public function boot(): void
    {
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
