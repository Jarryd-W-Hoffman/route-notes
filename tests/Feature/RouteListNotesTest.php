<?php

namespace Jarryd\RouteNotes\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Jarryd\RouteNotes\Tests\Stubs\InvokableController;
use Jarryd\RouteNotes\Tests\Stubs\PhpDocController;
use Jarryd\RouteNotes\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RouteListNotesTest extends TestCase
{
    #[Test]
    public function it_displays_fluent_notes_beneath_the_route()
    {
        Route::get('/fluent', fn () => 'ok')
            ->note('First Note')
            ->note('Second Note');

        $lines = $this->routeListLines();

        $route = $this->lineIndexContaining($lines, 'fluent');

        $this->assertStringContainsString('✎ First Note', $lines[$route + 1]);
        $this->assertStringContainsString('✎ Second Note', $lines[$route + 2]);
    }

    #[Test]
    public function it_displays_array_notes()
    {
        Route::get('/array', fn () => 'ok')
            ->notes(['Array Note 1', 'Array Note 2']);

        $this->artisan('route:list')
            ->expectsOutputToContain('✎ Array Note 1')
            ->expectsOutputToContain('✎ Array Note 2')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_keeps_notes_in_the_order_they_were_added()
    {
        Route::get('/mixed', fn () => 'ok')
            ->note('Fluent Note')
            ->notes(['Array Note 1', 'Array Note 2']);

        $this->assertSame(
            [['Fluent Note', 'Array Note 1', 'Array Note 2']],
            array_column($this->routeListJson(), 'note'),
        );
    }

    #[Test]
    public function it_attaches_notes_to_the_correct_route()
    {
        Route::get('/alpha', fn () => 'ok')->note('Alpha note');
        Route::get('/bravo', fn () => 'ok');
        Route::get('/charlie', fn () => 'ok')->note('Charlie note');

        $lines = $this->routeListLines();

        $this->assertStringContainsString('Alpha note', $lines[$this->lineIndexContaining($lines, 'alpha') + 1]);
        $this->assertStringContainsString('bravo', $lines[$this->lineIndexContaining($lines, 'Alpha note') + 1]);
        $this->assertStringContainsString('Charlie note', $lines[$this->lineIndexContaining($lines, 'charlie') + 1]);
    }

    #[Test]
    public function it_merges_fluent_and_phpdoc_notes_with_fluent_first()
    {
        Route::get('/merged', [PhpDocController::class, 'index'])
            ->note('Fluent Note');

        $this->assertSame(
            [['Fluent Note', 'Admin only', 'Read-only access']],
            array_column($this->routeListJson(), 'note'),
        );
    }

    #[Test]
    public function it_removes_duplicate_notes()
    {
        Route::get('/merged', [PhpDocController::class, 'index'])
            ->note('Admin only');

        $this->assertSame(
            [['Admin only', 'Read-only access']],
            array_column($this->routeListJson(), 'note'),
        );
    }

    #[Test]
    public function it_reads_notes_from_invokable_controllers()
    {
        Route::get('/invokable', InvokableController::class);

        $this->artisan('route:list')
            ->expectsOutputToContain('✎ Invokable note')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_can_filter_routes_using_the_notes_flag()
    {
        Route::get('/noted', fn () => 'ok')->note('Important');
        Route::get('/silent', fn () => 'ok');

        $this->artisan('route:list', ['--notes' => true])
            ->expectsOutputToContain('Important')
            ->doesntExpectOutputToContain('silent')
            ->expectsOutputToContain('Showing [1] routes')
            ->assertExitCode(0);
    }

    #[Test]
    public function the_notes_flag_includes_phpdoc_only_routes()
    {
        Route::get('/doc', [PhpDocController::class, 'index']);
        Route::get('/silent', fn () => 'ok');

        $this->assertSame(['doc'], array_column($this->routeListJson(['--notes' => true]), 'uri'));
    }

    #[Test]
    public function the_notes_flag_reports_when_no_routes_have_notes()
    {
        Route::get('/silent', fn () => 'ok');

        $this->artisan('route:list', ['--notes' => true])
            ->expectsOutputToContain("doesn't have any routes matching the given criteria")
            ->assertExitCode(0);
    }

    #[Test]
    public function the_notes_flag_combines_with_other_filters()
    {
        Route::get('/api/noted', fn () => 'ok')->note('API');
        Route::get('/web/noted', fn () => 'ok')->note('Web');
        Route::get('/api/silent', fn () => 'ok');

        $this->assertSame(
            ['api/noted'],
            array_column($this->routeListJson(['--notes' => true, '--path' => 'api']), 'uri'),
        );
    }

    #[Test]
    public function it_outputs_notes_as_an_array_in_json()
    {
        Route::get('/noted', fn () => 'ok')->notes(['One', 'Two']);
        Route::get('/silent', fn () => 'ok');

        $routes = collect($this->routeListJson())->keyBy('uri');

        $this->assertSame(['One', 'Two'], $routes['noted']['note']);
        $this->assertSame([], $routes['silent']['note']);
    }

    #[Test]
    public function json_output_keeps_the_native_columns()
    {
        Route::get('/noted', fn () => 'ok')->name('noted')->middleware('web')->note('Note');

        $route = $this->routeListJson()[0];

        $this->assertSame(
            ['domain', 'method', 'uri', 'name', 'action', 'middleware', 'path', 'note'],
            array_keys($route),
        );
        $this->assertSame(['web'], $route['middleware']);
    }

    #[Test]
    public function it_keeps_the_native_cli_layout()
    {
        Route::get('/noted', fn () => 'ok')->note('Note');

        $this->artisan('route:list')
            ->expectsOutputToContain('Showing [1] routes')
            ->doesntExpectOutputToContain('+--------')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_shows_notes_after_middleware_in_verbose_mode()
    {
        Route::get('/noted', fn () => 'ok')->middleware('auth')->note('Note');

        $lines = $this->routeListLines(['-v' => true]);
        $route = $this->lineIndexContaining($lines, 'noted');

        $this->assertStringContainsString('⇂ auth', $lines[$route + 1]);
        $this->assertStringContainsString('✎ Note', $lines[$route + 2]);
    }

    #[Test]
    public function notes_are_aligned_with_the_uri()
    {
        Route::get('/noted', fn () => 'ok')->note('Note');

        $lines = $this->routeListLines();
        $route = $this->lineIndexContaining($lines, 'noted');

        $this->assertSame(
            mb_strpos($lines[$route], 'noted'),
            mb_strpos($lines[$route + 1], '✎'),
        );
    }

    #[Test]
    public function it_escapes_console_formatting_in_notes()
    {
        Route::get('/noted', fn () => 'ok')->note('Requires <info>admin</info> role');

        $this->artisan('route:list')
            ->expectsOutputToContain('Requires <info>admin</info> role')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_supports_sorting_and_reversing()
    {
        Route::get('/a', fn () => 'ok')->note('A');
        Route::get('/b', fn () => 'ok')->note('B');

        $this->assertSame(['b', 'a'], array_column($this->routeListJson(['--reverse' => true]), 'uri'));
        $this->assertSame([['B'], ['A']], array_column($this->routeListJson(['--reverse' => true]), 'note'));
    }

    #[Test]
    public function it_is_safe_for_closures()
    {
        Route::get('closure-test', fn () => 'ok');

        $this->artisan('route:list')
            ->expectsOutputToContain('closure-test')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_handles_redirect_and_view_routes()
    {
        Route::redirect('/old', '/new')->note('Moved');
        Route::view('/welcome', 'welcome')->note('Static');

        $this->assertSame(
            [['Moved'], ['Static']],
            array_column($this->routeListJson(), 'note'),
        );
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    protected function routeListJson(array $options = []): array
    {
        Artisan::call('route:list', ['--json' => true] + $options);

        return json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, string>
     */
    protected function routeListLines(array $options = []): array
    {
        Artisan::call('route:list', $options);

        return explode("\n", Artisan::output());
    }

    /**
     * @param  array<int, string>  $lines
     */
    protected function lineIndexContaining(array $lines, string $needle): int
    {
        foreach ($lines as $index => $line) {
            if (str_contains($line, $needle)) {
                return $index;
            }
        }

        $this->fail("No line contains [{$needle}].");
    }
}
