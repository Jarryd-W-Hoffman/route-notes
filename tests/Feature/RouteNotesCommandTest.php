<?php

namespace Jarryd\RouteNotes\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Jarryd\RouteNotes\Commands\RouteNotesCommand;
use Jarryd\RouteNotes\Tests\Stubs\PhpDocController;
use Jarryd\RouteNotes\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RouteNotesCommandTest extends TestCase
{
    #[Test]
    public function it_is_registered()
    {
        $this->assertInstanceOf(RouteNotesCommand::class, Artisan::all()['route:notes']);
    }

    #[Test]
    public function it_exports_routes_with_notes_as_markdown()
    {
        Route::get('/api/legacy-sync', [PhpDocController::class, 'index'])
            ->name('sync.handle')
            ->note('Legacy sync for old ERP');
        Route::post('/webhooks/stripe', fn () => 'ok')
            ->note('Production only')
            ->note('Use the v2 webhook', 'deprecated');
        Route::get('/silent', fn () => 'ok');

        Artisan::call('route:notes');

        $this->assertSame(implode(PHP_EOL, [
            '# Route Notes',
            '',
            '2 routes with notes.',
            '',
            '| Method | URI | Name | Action | Notes |',
            '| --- | --- | --- | --- | --- |',
            '| GET\|HEAD | `api/legacy-sync` | `sync.handle` | `Jarryd\RouteNotes\Tests\Stubs\PhpDocController@index` | Legacy sync for old ERP<br>Admin only<br>Read-only access |',
            '| POST | `webhooks/stripe` |  | `Closure` | Production only<br>**Deprecated:** Use the v2 webhook |',
            '',
        ]), Artisan::output());
    }

    #[Test]
    public function it_escapes_values_that_would_break_the_table()
    {
        Route::get('/a', fn () => 'ok')->note('Either <b>this</b> | that');

        Artisan::call('route:notes');

        $this->assertStringContainsString('| Either &lt;b&gt;this&lt;/b&gt; \| that |', Artisan::output());
    }

    #[Test]
    public function it_includes_the_domain_in_the_uri()
    {
        Route::domain('api.example.com')->get('/users', fn () => 'ok')->note('Note');

        Artisan::call('route:notes');

        $this->assertStringContainsString('`api.example.com/users`', Artisan::output());
    }

    #[Test]
    public function it_supports_the_route_list_filters()
    {
        Route::get('/api/a', fn () => 'ok')->note('Legacy');
        Route::get('/web/b', fn () => 'ok')->note('Legacy');
        Route::get('/api/c', fn () => 'ok')->note('Current');

        Artisan::call('route:notes', ['--path' => 'api', '--note' => 'legacy']);

        $output = Artisan::output();

        $this->assertStringContainsString('1 route with notes.', $output);
        $this->assertStringContainsString('`api/a`', $output);
    }

    #[Test]
    public function it_writes_the_report_to_a_file()
    {
        $path = sys_get_temp_dir().'/route-notes-'.uniqid().'/notes.md';

        Route::get('/a', fn () => 'ok')->note('Note');

        try {
            $this->artisan('route:notes', ['--output' => $path])
                ->expectsOutputToContain('Route notes written to')
                ->assertExitCode(0);

            $this->assertStringStartsWith('# Route Notes', File::get($path));
        } finally {
            File::deleteDirectory(dirname($path));
        }
    }

    #[Test]
    public function it_supports_json_output()
    {
        Route::get('/a', fn () => 'ok')->note('Note');
        Route::get('/b', fn () => 'ok');

        Artisan::call('route:notes', ['--json' => true]);

        $this->assertSame(['a'], array_column(json_decode(Artisan::output(), true), 'uri'));
    }

    #[Test]
    public function it_reports_when_no_routes_have_notes()
    {
        Route::get('/silent', fn () => 'ok');

        $this->artisan('route:notes')
            ->expectsOutputToContain("doesn't have any routes matching the given criteria")
            ->assertExitCode(0);
    }

    #[Test]
    public function route_list_is_unaffected()
    {
        Route::get('/a', fn () => 'ok')->note('Note');
        Route::get('/b', fn () => 'ok');

        Artisan::call('route:list', ['--json' => true]);

        $this->assertCount(2, json_decode(Artisan::output(), true));
    }
}
