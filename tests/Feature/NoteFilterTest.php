<?php

namespace Jarryd\RouteNotes\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Jarryd\RouteNotes\Tests\Stubs\PhpDocController;
use Jarryd\RouteNotes\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class NoteFilterTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        Route::get('/erp', fn () => 'ok')->note('Legacy sync for old ERP');
        Route::get('/billing', fn () => 'ok')->note('Uses the legacy billing API', 'warning');
        Route::get('/v1', fn () => 'ok')->note('Use v2 instead', 'deprecated');
        Route::get('/admin', [PhpDocController::class, 'index']);
        Route::get('/silent', fn () => 'ok');
    }

    #[Test]
    public function it_filters_by_note_text_case_insensitively()
    {
        $this->assertSame(['billing', 'erp'], $this->uris(['--note' => 'LEGACY']));
    }

    #[Test]
    public function it_filters_by_level()
    {
        $this->assertSame(['v1'], $this->uris(['--note' => 'deprecated']));
        $this->assertSame(['billing'], $this->uris(['--note' => 'Warning']));
    }

    #[Test]
    public function it_filters_phpdoc_notes()
    {
        $this->assertSame(['admin'], $this->uris(['--note' => 'admin only']));
    }

    #[Test]
    public function it_combines_with_other_filters()
    {
        $this->assertSame(['erp'], $this->uris(['--note' => 'legacy', '--path' => 'erp']));
    }

    #[Test]
    public function it_shows_every_note_on_matching_routes()
    {
        Route::get('/multi', fn () => 'ok')->notes(['First', 'Matches legacy']);

        $this->artisan('route:list', ['--note' => 'legacy'])
            ->expectsOutputToContain('✎ First')
            ->expectsOutputToContain('✎ Matches legacy')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_reports_when_nothing_matches()
    {
        $this->artisan('route:list', ['--note' => 'nothing matches this'])
            ->expectsOutputToContain("doesn't have any routes matching the given criteria")
            ->assertExitCode(0);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, string>
     */
    protected function uris(array $options): array
    {
        Artisan::call('route:list', ['--json' => true] + $options);

        return array_column(json_decode(Artisan::output(), true), 'uri');
    }
}
