<?php

namespace Jarryd\RouteNotes\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Jarryd\RouteNotes\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RouteCacheTest extends TestCase
{
    #[Test]
    public function notes_survive_route_caching()
    {
        $this->defineCacheRoutes(<<<'PHP'
<?php

use Illuminate\Support\Facades\Route;
use Jarryd\RouteNotes\Tests\Stubs\ParameterController;
use Jarryd\RouteNotes\Tests\Stubs\PhpDocController;

Route::get('/cached/{id}', [ParameterController::class, 'show'])->note('Fluent')->notes(['Array']);
Route::get('/cached-doc', [PhpDocController::class, 'index']);
PHP);

        $this->assertTrue($this->app->routesAreCached());

        Artisan::call('route:list', ['--json' => true, '--path' => 'cached']);

        $notes = collect(json_decode(Artisan::output(), true))->pluck('note', 'uri')->all();

        $this->assertSame([
            'cached-doc' => ['Admin only', 'Read-only access'],
            'cached/{id}' => ['Fluent', 'Array'],
        ], $notes);
    }

    #[Test]
    public function cached_routes_do_not_receive_notes_as_parameters()
    {
        $this->defineCacheRoutes(<<<'PHP'
<?php

use Illuminate\Support\Facades\Route;
use Jarryd\RouteNotes\Tests\Stubs\ParameterController;

Route::get('/cached/{id}', [ParameterController::class, 'show'])->note('Fluent');
PHP);

        $this->getJson('/cached/5')
            ->assertOk()
            ->assertExactJson([
                'parameters' => ['id' => '5'],
                'extra' => [],
            ]);
    }
}
