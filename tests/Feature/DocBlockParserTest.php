<?php

namespace Jarryd\RouteNotes\Tests\Unit;

use Illuminate\Support\Facades\Route;
use Jarryd\RouteNotes\Support\DocBlockParser;
use Jarryd\RouteNotes\Tests\Stubs\InvokableController;
use Jarryd\RouteNotes\Tests\Stubs\PhpDocController;
use Jarryd\RouteNotes\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DocBlockParserTest extends TestCase
{
    #[Test]
    public function it_extracts_notes_from_controller_method_phpdoc()
    {
        $notes = DocBlockParser::getNotes(PhpDocController::class.'@index');

        $this->assertCount(2, $notes);
        $this->assertEquals('Admin only', $notes[0]);
        $this->assertEquals('Read-only access', $notes[1]);
    }

    #[Test]
    public function it_extracts_notes_from_invokable_controllers()
    {
        $notes = DocBlockParser::getNotes(InvokableController::class);

        $this->assertContains('Invokable note', $notes);
    }

    #[Test]
    public function it_merges_fluent_and_phpdoc_notes_with_fluent_priority()
    {
        Route::get('/merged', [PhpDocController::class, 'index'])
            ->note('Fluent Note');

        $this->artisan('route:list')
            ->expectsOutputToContain('✎ Fluent Note');
    }

    #[Test]
    public function it_returns_empty_array_if_no_notes_found()
    {
        $notes = DocBlockParser::getNotes('NonExistentController@method');

        $this->assertIsArray($notes);
        $this->assertEmpty($notes);
    }
}
