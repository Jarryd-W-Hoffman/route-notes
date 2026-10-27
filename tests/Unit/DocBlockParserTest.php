<?php

namespace Jarryd\RouteNotes\Tests\Unit;

use Jarryd\RouteNotes\Note;
use Jarryd\RouteNotes\Support\DocBlockParser;
use Jarryd\RouteNotes\Tests\Stubs\ChildNotesController;
use Jarryd\RouteNotes\Tests\Stubs\ClassNotesController;
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
            self::texts(DocBlockParser::getNotes(PhpDocController::class.'@index')),
        );
    }

    #[Test]
    public function it_extracts_notes_from_an_array_callable()
    {
        $this->assertSame(
            ['Admin only', 'Read-only access'],
            self::texts(DocBlockParser::getNotes([PhpDocController::class, 'index'])),
        );
    }

    #[Test]
    public function it_extracts_notes_from_invokable_controllers()
    {
        $this->assertSame(['Invokable note'], self::texts(DocBlockParser::getNotes(InvokableController::class)));
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
            'multi line notes' => ['multiLine', ['This note is long enough that it wraps onto a second line and a third.', 'Second note']],
            'text on the following line' => ['textOnNextLine', ['Text on the following line']],
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
        $this->assertSame($expected, self::texts(DocBlockParser::getNotes(EdgeCaseController::class.'@'.$method)));
    }

    #[Test]
    public function it_includes_class_level_notes_before_method_notes()
    {
        $this->assertSame(
            ['Owned by the integrations team', 'Nightly export only'],
            self::texts(DocBlockParser::getNotes(ClassNotesController::class.'@export')),
        );
        $this->assertSame(
            ['Owned by the integrations team'],
            self::texts(DocBlockParser::getNotes(ClassNotesController::class.'@undocumented')),
        );
        $this->assertSame(
            ['Owned by the integrations team', 'Invoked by the scheduler'],
            self::texts(DocBlockParser::getNotes(ClassNotesController::class)),
        );
    }

    #[Test]
    public function inherited_methods_use_the_routed_controllers_class_notes()
    {
        $this->assertSame(
            ['Child controller', 'Nightly export only'],
            self::texts(DocBlockParser::getNotes(ChildNotesController::class.'@export')),
        );
    }

    #[Test]
    public function it_caches_notes_per_action()
    {
        DocBlockParser::flushCache();

        $first = DocBlockParser::getNotes(PhpDocController::class.'@index');

        $cache = (new \ReflectionProperty(DocBlockParser::class, 'cache'))->getValue();

        $this->assertSame($first, DocBlockParser::getNotes(PhpDocController::class.'@index'));
        $this->assertArrayHasKey(PhpDocController::class.'::index', $cache);
        $this->assertArrayHasKey(PhpDocController::class, $cache);
    }

    #[Test]
    public function it_handles_windows_line_endings()
    {
        $this->assertSame(['First', 'Second'], self::texts(DocBlockParser::parse("/**\r\n * @note First\r\n * @note Second\r\n */")));
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
        $this->assertSame([], self::texts(DocBlockParser::getNotes($action)));
    }

    /**
     * @param  array<int, Note>  $notes
     * @return array<int, string>
     */
    protected static function texts(array $notes): array
    {
        return array_map(fn (Note $note) => $note->text, $notes);
    }
}
