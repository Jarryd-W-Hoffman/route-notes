<?php

namespace Jarryd\RouteNotes\Tests\Stubs;

class ParameterController
{
    public function show(string $id, mixed ...$extra): array
    {
        return [
            'parameters' => request()->route()?->parameters(),
            'extra' => $extra,
        ];
    }
}
