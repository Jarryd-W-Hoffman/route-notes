<?php

namespace Jarryd\RouteNotes;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Route;
use Jarryd\RouteNotes\Commands\RouteListCommand;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;

class RouteNotesServiceProvider extends ServiceProvider
{
    // public function register(): void
    // {
    //     $this->registerRouteMacro();

    //     // Override route:list command in the container
    //     if ($this->app->runningInConsole()) {
    //         $this->app->extend('command.route.list', fn($command, $app) => new RouteListCommand($app['router']));
    //     }
    // }

    public function register()
{
    $this->app->extend('command.route.list', function ($command, $app) {
        return new RouteListCommand($app->make($app['router']));
    });
}

    public function boot(): void
    {
        // nothing else needed here

if ($this->app->runningInConsole()) {
        $this->commands([
            RouteListCommand::class,
        ]);
    }

        // 1. Define the macro
        // Route::macro('note', function (string $text) {
        //     return $this->defaults('_route_note', $text);
        // });
Route::macro('note', function (string $value) {
    /** @var Route $this */
    $notes = $this->defaults['_route_notes'] ?? [];
    $notes[] = $value;

    // Pass key and value as separate arguments
    return $this->defaults('_route_notes', $notes);
});

Route::macro('notes', function (array $values) {
    /** @var Route $this */
    $notes = $this->defaults['_route_notes'] ?? [];
    $notes = array_merge($notes, $values);

    return $this->defaults('_route_notes', $notes);
});

        // 2. Usage in web.php:
        // Route::get('/test', fn() => 'hello')->note('This is for internal testing');

    }

    // protected function registerRouteMacro(): void
    // {
    //     if (! Route::hasMacro('note')) {
    //         Route::macro('note', function (string $note) {
    //             $this->defaults['_notes'] ??= [];
    //             $this->defaults['_notes'][] = $note;
    //             return $this;
    //         });

    //         Route::macro('notes', function (array $notes) {
    //             foreach ($notes as $note) {
    //                 $this->note($note);
    //             }
    //             return $this;
    //         });
    //     }
    // }
}
