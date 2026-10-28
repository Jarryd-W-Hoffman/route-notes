<?php

namespace Jarryd\RouteNotes\Support;

use Closure;
use Jarryd\RouteNotes\Note;
use Jarryd\RouteNotes\NoteLevel;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class NoteReader
{
    /**
     * Notes already read, keyed by class or "class::method".
     *
     * @var array<string, array<int, Note>>
     */
    protected static array $cache = [];

    /**
     * Read the notes for an action from its controller class and method, class
     * notes first. Each reads PHPDoc @note tags, then #[Note] attributes.
     *
     * @return array<int, Note>
     */
    public static function getNotes(mixed $action): array
    {
        if ($action instanceof Closure) {
            return [];
        }

        if (is_string($action) && str_contains($action, '@')) {
            [$class, $method] = explode('@', $action);
        } elseif (is_array($action) && count($action) === 2 && is_string($action[0]) && is_string($action[1])) {
            [$class, $method] = $action;
        } elseif (is_string($action) && class_exists($action)) {
            [$class, $method] = [$action, '__invoke'];
        } else {
            return [];
        }

        return static::$cache["{$class}::{$method}"] ??= static::readNotes($class, $method);
    }

    /**
     * Forget all cached notes.
     */
    public static function flushCache(): void
    {
        static::$cache = [];
    }

    /**
     * @return array<int, Note>
     */
    protected static function readNotes(string $class, string $method): array
    {
        if (! class_exists($class)) {
            return [];
        }

        try {
            $reflection = new ReflectionMethod($class, $method);
        } catch (ReflectionException) {
            return [];
        }

        // Class notes come from the routed controller, not a parent declaring the method.
        return array_merge(
            static::$cache[$class] ??= static::fromReflection(new ReflectionClass($class)),
            static::fromReflection($reflection),
        );
    }

    /**
     * @param  ReflectionClass<object>|ReflectionMethod  $reflection
     * @return array<int, Note>
     */
    protected static function fromReflection(ReflectionClass|ReflectionMethod $reflection): array
    {
        return array_merge(
            static::parse((string) $reflection->getDocComment()),
            array_map(fn (ReflectionAttribute $attribute) => $attribute->newInstance(), $reflection->getAttributes(Note::class)),
        );
    }

    /**
     * Extract the @note tags from a PHPDoc comment.
     *
     * A note continues onto following lines until a blank line, another tag
     * or the end of the comment. A leading "[level]" sets the note's level.
     *
     * @return array<int, Note>
     */
    public static function parse(string $docComment): array
    {
        $notes = [];
        $current = null;

        foreach (preg_split('/\R/', $docComment) ?: [] as $line) {
            $line = trim(preg_replace(['/^\s*(\/\*\*|\*(?!\/))?/', '/\*\/\s*$/'], '', $line) ?? '');

            if (preg_match('/^@note(?:\s+(.*))?$/', $line, $matches)) {
                $notes[] = $current;
                $current = $matches[1] ?? '';
            } elseif ($line === '' || str_starts_with($line, '@')) {
                $notes[] = $current;
                $current = null;
            } elseif ($current !== null) {
                $current = ltrim("{$current} {$line}");
            }
        }

        $notes[] = $current;

        return array_values(array_map(
            fn (string $note) => static::toNote($note),
            array_filter($notes, fn (?string $note) => $note !== null && $note !== ''),
        ));
    }

    protected static function toNote(string $text): Note
    {
        if (preg_match('/^\[(\w+)\]\s+(.+)$/', $text, $matches) && $level = NoteLevel::tryFrom(strtolower($matches[1]))) {
            return new Note($matches[2], $level);
        }

        return new Note($text);
    }
}
