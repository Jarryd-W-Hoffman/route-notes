<?php

namespace Jarryd\RouteNotes\Tests\Unit;

use Jarryd\RouteNotes\Support\DocBlockParser;
use Jarryd\RouteNotes\Tests\Stubs\EdgeCaseController;
use Jarryd\RouteNotes\Tests\Stubs\InvokableController;
use Jarryd\RouteNotes\Tests\Stubs\PhpDocController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DocBlockParserTest extends TestCase
{
    #[Test]
    public function it_extracts_notes_from_a_class_at_method_string()
    {
        $this->assertSame(
            ['Admin only', 'Read-only access'],
            DocBlockParser::getNotes(PhpDocController::class.'@index'),
        );
    }

    #[Test]
    public function it_extracts_notes_from_an_array_callable()
    {
        $this->assertSame(
            ['Admin only', 'Read-only access'],
            DocBlockParser::getNotes([PhpDocController::class, 'index']),
        );
    }

    #[Test]
    public function it_extracts_notes_from_invokable_controllers()
    {
        $this->assertSame(['Invokable note'], DocBlockParser::getNotes(InvokableController::class));
    }

    /**
     * @return array<string, array{string, array<int, string>}>
     */
    public static function docBlocks(): array
    {
        return [
            'single line docblock' => ['singleLine', ['Single line']],
            'notes among other tags' => ['mixedTags', ['After other tags']],
            'lookalike and empty tags' => ['lookalikes', ['Padded note']],
            'special characters' => ['specialCharacters', ['Contains an @ symbol and a * star']],
            'empty single line docblock' => ['emptySingleLine', []],
            'no docblock' => ['undocumented', []],
            'line comment' => ['lineComment', []],
        ];
    }

    /**
     * @param  array<int, string>  $expected
     */
    #[Test]
    #[DataProvider('docBlocks')]
    public function it_parses_docblock_edge_cases(string $method, array $expected)
    {
        $this->assertSame($expected, DocBlockParser::getNotes(EdgeCaseController::class.'@'.$method));
    }

    #[Test]
    public function it_handles_windows_line_endings()
    {
        $this->assertSame(['First', 'Second'], DocBlockParser::parse("/**\r\n * @note First\r\n * @note Second\r\n */"));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function unresolvableActions(): array
    {
        return [
            'missing class' => ['NonExistentController@method'],
            'missing method' => [PhpDocController::class.'@missing'],
            'non invokable class' => [PhpDocController::class],
            'plain string' => ['not-a-class'],
            'closure' => [fn () => 'ok'],
            'serialized closure string' => ['O:47:"Laravel\SerializableClosure\SerializableClosure":1:{s:12:"serializable";s:3:"a@b";}'],
        ];
    }

    #[Test]
    #[DataProvider('unresolvableActions')]
    public function it_returns_no_notes_for_unresolvable_actions(mixed $action)
    {
        $this->assertSame([], DocBlockParser::getNotes($action));
    }
}
