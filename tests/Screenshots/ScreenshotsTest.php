<?php

namespace Jarryd\RouteNotes\Tests\Screenshots;

use Illuminate\Foundation\Console\RouteListCommand;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Jarryd\RouteNotes\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Output\AnsiColorMode;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Terminal as SymfonyTerminal;

require_once __DIR__.'/DemoApp.php';

/*
 * The screenshots in the README, taken of a demo app. Run this after changing what a
 * screenshot shows, and commit the images it writes to .github/screenshots:
 *
 *     composer screenshots
 *
 * It needs Chrome (set CHROME_PATH if it isn't found), the GD extension, and a network
 * connection for the JetBrains Mono font.
 */
class ScreenshotsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('gd')) {
            $this->markTestSkipped('The GD extension is needed to crop screenshots.');
        }

        // Cropping holds two full-size images in memory at once.
        ini_set('memory_limit', '512M');

        SymfonyTerminal::setColorMode(AnsiColorMode::Ansi24);

        DemoApp::routes();
    }

    protected function tearDown(): void
    {
        RouteListCommand::resolveTerminalWidthUsing(null);

        parent::tearDown();
    }

    #[Test]
    public function route_list()
    {
        $this->shoot('route-list', 'php artisan route:list');
    }

    #[Test]
    public function route_list_with_notes_only()
    {
        $this->shoot('route-list-notes', 'php artisan route:list --notes', ['--notes' => true], columns: 92);
    }

    #[Test]
    public function route_list_filtered_by_note()
    {
        $this->shoot('route-list-deprecated', 'php artisan route:list --note=deprecated', ['--note' => 'deprecated'], columns: 92);
    }

    #[Test]
    public function route_notes_report()
    {
        Artisan::call('route:notes');
        $markdown = Artisan::output();

        $terminal = Terminal::window(
            '<span class="prompt">$</span> php artisan route:notes --output=docs/routes.md'."\n"
                .Terminal::toHtml($this->runCommand('route:notes', ['--output' => sys_get_temp_dir().'/routes.md'], 92)),
            92,
        );

        $terminal = str_replace(e(sys_get_temp_dir().'/routes.md'), 'docs/routes.md', $terminal);

        $document = '<div class="document"><div class="bar"><span></span><span></span><span></span><div class="title">docs/routes.md</div></div>'
            .'<div class="markdown">'.Str::markdown($markdown, ['html_input' => 'allow']).'</div></div>';

        Terminal::capture($this->path('route-notes'), $terminal.$document);

        $this->assertScreenshotWritten('route-notes');
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function shoot(string $name, string $command, array $options = [], int $columns = 112): void
    {
        Terminal::screenshot($this->path($name), $command, $this->runCommand('route:list', $options, $columns), $columns);

        $this->assertScreenshotWritten($name);
    }

    /**
     * Run a command as if in a terminal of the given width, returning its colored output.
     *
     * @param  array<string, mixed>  $options
     */
    protected function runCommand(string $command, array $options, int $columns): string
    {
        RouteListCommand::resolveTerminalWidthUsing(fn () => $columns);

        $output = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, decorated: true);

        Artisan::call($command, $options, $output);

        return $output->fetch();
    }

    protected function path(string $name): string
    {
        if (! is_dir($directory = dirname(__DIR__, 2).'/.github/screenshots')) {
            mkdir($directory, recursive: true);
        }

        return "{$directory}/{$name}.png";
    }

    protected function assertScreenshotWritten(string $name): void
    {
        $this->assertNotFalse(getimagesize($this->path($name)), "The {$name} screenshot wasn't written.");
    }
}
