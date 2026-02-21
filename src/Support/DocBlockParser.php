<?php

namespace Jarryd\RouteNotes\Support;

use Closure;
use ReflectionMethod;
use ReflectionException;

class DocBlockParser
{
    public static function getNotes($action): array
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

            if (!$reflection || !($docComment = $reflection->getDocComment())) {
                return [];
            }

            preg_match_all('/@note\s+(.*?)(?:\s*\*\/|\r?\n|$)/', $docComment, $matches);

            return collect($matches[1])
                ->map(fn($note) => trim($note, " \t\n\r\0\x0B*"))
                ->filter()
                ->values()
                ->toArray();

        } catch (ReflectionException $e) {
            return [];
        }
    }
}
