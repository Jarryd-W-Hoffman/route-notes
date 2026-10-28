<?php

namespace Jarryd\RouteNotes\Tests\Feature;

use Illuminate\Foundation\Console\RouteListCommand as BaseCommand;
use Illuminate\Support\Facades\Artisan;
use Jarryd\RouteNotes\Commands\RouteListCommand;
use Jarryd\RouteNotes\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ServiceProviderTest extends TestCase
{
    #[Test]
    public function it_replaces_the_framework_route_list_command()
    {
        $this->assertInstanceOf(RouteListCommand::class, Artisan::all()['route:list']);
    }

    #[Test]
    public function resolving_the_framework_command_returns_ours()
    {
        $this->assertInstanceOf(RouteListCommand::class, $this->app->make(BaseCommand::class));
    }

    #[Test]
    public function it_registers_the_notes_option()
    {
        $this->assertTrue(Artisan::all()['route:list']->getDefinition()->hasOption('notes'));
    }
}
