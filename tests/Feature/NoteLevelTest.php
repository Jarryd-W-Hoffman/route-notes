<?php

namespace Jarryd\RouteNotes\Tests\Feature;

use Illuminate\Foundation\Console\RouteListCommand;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Jarryd\RouteNotes\NoteLevel;
use Jarryd\RouteNotes\Tests\Stubs\LevelController;
use Jarryd\RouteNotes\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class NoteLevelTest extends TestCase
{
    #[Test]
    public function notes_default_to_the_info_level()
    {
        $route = Route::get('/a', fn () => 'ok')->note('Plain');

        $this->assertSame([['text' => 'Plain', 'level' => 'info']], $route->getAction('notes'));
    }

    #[Test]
    public function fluent_notes_accept_a_level_name_or_enum()
    {
        $route = Route::get('/a', fn () => 'ok')
            ->note('Old', 'deprecated')
            ->note('Careful', NoteLevel::Danger)
            ->notes(['One', 'Two'], 'Warning');

        $this->assertSame([
            ['text' => 'Old', 'level' => 'deprecated'],
            ['text' => 'Careful', 'level' => 'danger'],
            ['text' => 'One', 'level' => 'warning'],
            ['text' => 'Two', 'level' => 'warning'],
        ], $route->getAction('notes'));
    }

    #[Test]
    public function an_unknown_level_throws()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid note level [urgent]. Expected one of: info, warning, deprecated, danger.');

        Route::get('/a', fn () => 'ok')->note('Typo', 'urgent');
    }

    #[Test]
    public function phpdoc_notes_can_set_a_level()
    {
        Route::get('/levels', [LevelController::class, 'index']);

        Artisan::call('route:list', ['--json' => true]);

        $this->assertSame([
            ['text' => 'Use the v2 endpoint', 'level' => 'deprecated'],
            ['text' => 'Deletes customer data', 'level' => 'danger'],
            ['text' => '[unknown] Kept as plain text', 'level' => 'info'],
            ['text' => 'Plain note', 'level' => 'info'],
        ], json_decode(Artisan::output(), true)[0]['note']);
    }

    #[Test]
    public function the_cli_labels_and_colors_non_info_notes()
    {
        Route::get('/a', fn () => 'ok')
            ->note('Plain')
            ->note('Old', 'deprecated')
            ->note('Careful', 'danger');

        $command = $this->app->make(RouteListCommand::class);
        $method = new \ReflectionMethod($command, 'appendNotes');
        $output = [];
        $method->invokeArgs($command, [&$output, Route::getRoutes()->getRoutes()[0]->getAction('notes'), 8]);

        $this->assertSame([
            '<fg=cyan>                 ✎ Plain</>',
            '<fg=magenta>                 ✎ [deprecated] Old</>',
            '<fg=red>                 ✎ [danger] Careful</>',
        ], $output);
    }

    #[Test]
    public function the_same_text_at_different_levels_is_kept()
    {
        Route::get('/a', fn () => 'ok')->note('Same')->note('Same', 'warning')->note('Same');

        Artisan::call('route:list', ['--json' => true]);

        $this->assertSame([
            ['text' => 'Same', 'level' => 'info'],
            ['text' => 'Same', 'level' => 'warning'],
        ], json_decode(Artisan::output(), true)[0]['note']);
    }
}
