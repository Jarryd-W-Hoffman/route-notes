<?php

namespace Jarryd\RouteNotes\Tests;

use Jarryd\RouteNotes\RouteNotesServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

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
