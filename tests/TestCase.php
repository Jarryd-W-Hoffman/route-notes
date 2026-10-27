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

    protected function defineEnvironment($app): void
    {
        // Laravel 12 serves the local disk through routes, which would otherwise
        // appear in every route:list assertion.
        $app['config']->set('filesystems.disks.local.serve', false);
    }

    protected function getPackageProviders($app): array
    {
        return [
            RouteNotesServiceProvider::class,
        ];
    }
}
