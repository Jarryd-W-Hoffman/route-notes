<?php
namespace Jarryd\RouteNotes\Tests\Feature;

use Jarryd\RouteNotes\Tests\TestCase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;

class RouteListNotesTest extends TestCase
{
    #[Test]
    public function it_displays_fluent_notes_in_the_table()
    {
        Route::get('/fluent', fn() => 'ok')
            ->note('First Note')
            ->note('Second Note');

        $this->artisan('route:list')
            ->expectsOutputToContain('First Note | Second Note')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_displays_array_notes_in_the_table()
    {
        Route::get('/array', fn() => 'ok')
            ->notes(['Array Note 1', 'Array Note 2']);

        $this->artisan('route:list')
            ->expectsOutputToContain('Array Note 1 | Array Note 2')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_can_filter_routes_using_the_notes_flag()
    {
        Route::get('/noted', fn() => 'ok')->note('Important');

        Route::get('/silent', fn() => 'ok');

        $this->artisan('route:list', ['--notes' => true])
            ->expectsOutputToContain('Important')
            ->doesntExpectOutputToContain('/silent');
    }

    #[Test]
    public function it_is_safe_for_closures()
    {
        Route::get('closure-test', fn() => 'ok');

        $this->artisan('route:list')
            ->expectsOutputToContain('closure-test')
            ->assertExitCode(0);
    }
}
