<?php

namespace Jarryd\RouteNotes\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Jarryd\RouteNotes\Tests\Stubs\ParameterController;
use Jarryd\RouteNotes\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RuntimeIsolationTest extends TestCase
{
    #[Test]
    public function notes_do_not_leak_into_route_parameters()
    {
        Route::get('/users/{id}', [ParameterController::class, 'show'])
            ->note('First Note')
            ->notes(['Second Note']);

        $this->getJson('/users/5')
            ->assertOk()
            ->assertExactJson([
                'parameters' => ['id' => '5'],
                'extra' => [],
            ]);
    }

    #[Test]
    public function notes_do_not_leak_into_route_defaults()
    {
        $route = Route::get('/defaults', fn () => 'ok')->note('Note');

        $this->assertSame([], $route->defaults);
        $this->assertSame([['text' => 'Note', 'level' => 'info']], $route->getAction('notes'));
    }

    #[Test]
    public function notes_do_not_affect_url_generation()
    {
        Route::get('/users/{id}', [ParameterController::class, 'show'])
            ->name('users.show')
            ->note('Note');

        $this->assertSame('http://localhost/users/5', route('users.show', ['id' => 5]));
    }
}
