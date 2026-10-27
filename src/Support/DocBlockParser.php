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
    public static function getNotes(mixed $action): array
    {
        if ($action instanceof Closure) {
            return [];
        }

        try {
            $reflection = null;

            if (is_string($action) && str_contains($action, '@')) {
                [$class, $method] = explode('@', $action);
                $reflection = new ReflectionMethod($class, $method);
            } elseif (is_array($action) && count($action) === 2) {
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

            return static::parse($docComment);
        } catch (ReflectionException $e) {
            return [];
        }
    }

    /**
     * Extract the @note tags from a PHPDoc comment.
     *
     * @return array<int, string>
     */
    public static function parse(string $docComment): array
    {
        // Only match tags at the start of a docblock line, so prose mentioning
        // "@note" and lookalike tags such as "@noteworthy" are ignored.
        preg_match_all('/^[ \t]*(?:\/\*\*|\*)?[ \t]*@note[ \t]+(?!\*\/)(.+?)[ \t]*(?:\*\/)?[ \t]*\r?$/m', $docComment, $matches);

        return $matches[1];
    }
}
