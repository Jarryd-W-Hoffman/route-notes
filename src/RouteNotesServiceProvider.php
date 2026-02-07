<?php

namespace Jarryd\RouteNotes;

use Illuminate\Support\ServiceProvider;
use Jarryd\RouteNotes\Commands\RouteListCommand;
use Illuminate\Support\Facades\Route;

class RouteNotesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        $this->registerRouteMacro();
        $this->overrideRouteListCommand();
    }

    protected function registerRouteMacro()
    {
        Route::macro('note', function (string $note) {
            $this->defaults['_note'] = $note;
            return $this;
        });
    }

    protected function overrideRouteListCommand()
    {
        if ($this->app->runningInConsole()) {
            $this->app->extend('command.route.list', function ($command, $app) {
                return new RouteListCommand($app['router']);
            });
        }
    }
}
