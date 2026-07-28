<?php

namespace Jarryd\RouteNotes\Support;

use Closure;
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
            } elseif (is_array($action)) {
                $reflection = new ReflectionMethod($action[0], $action[1]);
            } elseif (is_string($action) && class_exists($action)) {
                if (! method_exists($action, '__invoke')) {
                    return [];
                }

                $reflection = new ReflectionMethod($action, '__invoke');
            }

            if (! $reflection || ! ($docComment = $reflection->getDocComment())) {
                return [];
            }

            preg_match_all('/@note\s+(.*?)(?=\s*\*\/|\n\s*\*?\s*(?:@|\*\/)|\r?\n|$)/', $docComment, $matches);

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
