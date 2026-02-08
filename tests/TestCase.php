<?php

namespace Jarryd\RouteNotes\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Jarryd\RouteNotes\RouteNotesServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        putenv('COLUMNS=200');
    }

    protected function getPackageProviders($app): array
    {
        return [
            RouteNotesServiceProvider::class,
        ];
    }
}
