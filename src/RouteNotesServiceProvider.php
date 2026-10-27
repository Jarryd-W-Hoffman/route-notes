<?php

namespace Jarryd\RouteNotes;

use Illuminate\Foundation\Console\RouteListCommand as BaseCommand;
use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;
use Jarryd\RouteNotes\Commands\RouteListCommand;
use Jarryd\RouteNotes\Commands\RouteNotesCommand;

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
        if ($this->app->runningInConsole()) {
            $this->commands([RouteNotesCommand::class]);
        }

        // Notes live in the route action rather than the route defaults, as
        // defaults are merged into the route parameters at dispatch time.
        Route::macro('note', function (string $value, NoteLevel|string $level = NoteLevel::Info) {
            $this->action['notes'][] = (new Note($value, $level))->toArray();

            return $this;
        });

        Route::macro('notes', function (array $values, NoteLevel|string $level = NoteLevel::Info) {
            foreach ($values as $value) {
                $this->action['notes'][] = (new Note($value, $level))->toArray();
            }

            return $this;
        });
    }
}
