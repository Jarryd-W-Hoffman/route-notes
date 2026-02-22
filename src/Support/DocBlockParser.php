<?php

namespace Jarryd\RouteNotes\Support;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class DocBlockParser
{
    /**
     * Extract notes from a given action's PHPDoc.
     *
     * @return array<int, string>
     */
    public static function getNotes(object|string|callable $action): array
    {
        if ($action instanceof Closure) {
            return [];
        }

        try {
            $reflection = null;

            if (is_string($action) && str_contains($action, '@')) {
                [$class, $method] = explode('@', $action);
                $reflection = new ReflectionMethod($class, $method);
            } elseif (is_string($action) && class_exists($action)) {
                $reflection = method_exists($action, '__invoke')
                    ? new ReflectionMethod($action, '__invoke')
                    : new ReflectionClass($action);
            }

            if (! $reflection || ! ($docComment = $reflection->getDocComment())) {
                return [];
            }

            preg_match_all('/@note\s+(.*?)(?:\s*\*\/|\r?\n|$)/', $docComment, $matches);

            return collect($matches[1])
                ->map(fn ($note) => trim($note, " \t\n\r\0\x0B*"))
                ->filter()
                ->values()
                ->toArray();

        } catch (ReflectionException $e) {
            return [];
        }
    }
}
