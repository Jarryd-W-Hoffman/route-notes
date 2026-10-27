<?php

namespace Jarryd\RouteNotes\Support;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class DocBlockParser
{
    /**
     * Notes already read, keyed by class or "class::method".
     *
     * @var array<string, array<int, string>>
     */
    protected static array $cache = [];

    /**
     * Extract notes from a given action's class and method PHPDoc, class notes first.
     *
     * @return array<int, string>
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
     * @return array<int, string>
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
            static::$cache[$class] ??= static::parse((string) (new ReflectionClass($class))->getDocComment()),
            static::parse((string) $reflection->getDocComment()),
        );
    }

    /**
     * Extract the @note tags from a PHPDoc comment.
     *
     * A note continues onto following lines until a blank line, another tag
     * or the end of the comment.
     *
     * @return array<int, string>
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

        return array_values(array_filter($notes, fn (?string $note) => $note !== null && $note !== ''));
    }
}
