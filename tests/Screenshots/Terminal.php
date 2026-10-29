<?php

namespace Jarryd\RouteNotes\Tests\Screenshots;

use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Renders console output in a terminal window and saves it as a PNG with headless Chrome.
 */
class Terminal
{
    /**
     * The terminal theme for the basic ANSI colors.
     *
     * @var array<int, string>
     */
    protected const COLORS = [
        30 => '#3b4048', 31 => '#ef6b73', 32 => '#98c379', 33 => '#e5c07b',
        34 => '#61afef', 35 => '#c678dd', 36 => '#56b6c2', 37 => '#e6e8eb',
        90 => '#6c7280', 91 => '#ff7b84', 92 => '#a8d58a', 93 => '#f0cf8f',
        94 => '#7cc0ff', 95 => '#d494ec', 96 => '#6fcfda', 97 => '#ffffff',
    ];

    /**
     * Convert ANSI colored text to HTML.
     */
    public static function toHtml(string $ansi): string
    {
        $html = '';
        [$color, $background, $bold] = [null, null, false];

        foreach (preg_split('/(\e\[[\d;]*m)/', $ansi, flags: PREG_SPLIT_DELIM_CAPTURE) ?: [] as $part) {
            if (! preg_match('/^\e\[([\d;]*)m$/', $part, $matches)) {
                $text = htmlspecialchars($part, ENT_QUOTES);
                $style = ($color ? "color:{$color};" : '')
                    .($background ? "background:{$background};" : '')
                    .($bold ? 'font-weight:700;' : '');

                $html .= $style === '' ? $text : "<span style=\"{$style}\">{$text}</span>";

                continue;
            }

            $codes = array_map(intval(...), explode(';', $matches[1] ?: '0'));

            while ($codes !== []) {
                $code = array_shift($codes);

                match (true) {
                    $code === 0 => [$color, $background, $bold] = [null, null, false],
                    $code === 1 => $bold = true,
                    $code === 22 => $bold = false,
                    $code === 39 => $color = null,
                    $code === 38 && ($codes[0] ?? null) === 2 => $color = vsprintf('rgb(%d,%d,%d)', array_slice(array_splice($codes, 0, 4), 1)),
                    $code === 49 => $background = null,
                    $code === 48 && ($codes[0] ?? null) === 2 => $background = vsprintf('rgb(%d,%d,%d)', array_slice(array_splice($codes, 0, 4), 1)),
                    isset(self::COLORS[$code]) => $color = self::COLORS[$code],
                    isset(self::COLORS[$code - 10]) => $background = self::COLORS[$code - 10],
                    default => null,
                };
            }
        }

        return $html;
    }

    /**
     * Save a terminal window showing the command and its output.
     */
    public static function screenshot(string $path, string $command, string $output, int $columns = 100): void
    {
        $body = '<span class="prompt">$</span> '.htmlspecialchars($command)."\n".static::toHtml(rtrim($output))."\n";

        static::capture($path, static::window($body, $columns));
    }

    /**
     * The HTML for a terminal window around the given (already escaped) content.
     */
    public static function window(string $content, int $columns, string $title = 'my-app — zsh'): string
    {
        $width = (int) ceil($columns * 8.43) + 44;

        return <<<HTML
            <div class="window" style="width: {$width}px">
                <div class="bar"><span></span><span></span><span></span><div class="title">{$title}</div></div>
                <pre>{$content}</pre>
            </div>
            HTML;
    }

    /**
     * Render the HTML on a transparent page and save it, cropped to its content.
     */
    public static function capture(string $path, string $html, int $width = 1100): void
    {
        $page = sys_get_temp_dir().'/route-notes-screenshot-'.uniqid().'.html';

        file_put_contents($page, static::page($html));

        try {
            // Chrome now and then writes an empty image, so try again before giving up.
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                @unlink($path);

                (new Process([
                    static::chrome(),
                    '--headless=new',
                    '--disable-gpu',
                    '--hide-scrollbars',
                    '--force-device-scale-factor=2',
                    '--default-background-color=00000000',
                    '--virtual-time-budget=10000',
                    "--window-size={$width},2000",
                    "--screenshot={$path}",
                    'file://'.$page,
                ]))->setTimeout(60)->mustRun();

                if (is_file($path) && @getimagesize($path) !== false && filesize($path) > 1024) {
                    break;
                }
            }
        } finally {
            unlink($page);
        }

        static::crop($path);
    }

    protected static function page(string $html): string
    {
        return <<<HTML
            <!doctype html>
            <html>
            <head>
            <meta charset="utf-8">
            <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=block">
            <style>
                * { box-sizing: border-box; }
                body { margin: 0; padding: 32px; background: transparent; display: flex; flex-direction: column; align-items: flex-start; gap: 28px; font-family: -apple-system, 'Segoe UI', Ubuntu, sans-serif; }
                .window { background: #16181d; border-radius: 10px; box-shadow: 0 0 0 1px rgba(255,255,255,.08), 0 18px 40px rgba(0,0,0,.35); overflow: hidden; }
                .bar { position: relative; height: 34px; display: flex; align-items: center; gap: 8px; padding: 0 14px; background: #20232a; }
                .bar > span { width: 12px; height: 12px; border-radius: 50%; background: #ff5f57; }
                .bar > span:nth-child(2) { background: #febc2e; }
                .bar > span:nth-child(3) { background: #28c840; }
                .title { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: #8b929e; font-size: 12px; }
                pre { margin: 0; padding: 14px 22px 18px; color: #e6e8eb; font: 14px/20px 'JetBrains Mono', 'DejaVu Sans Mono', monospace; white-space: pre; }
                .prompt { color: #98c379; font-weight: 700; }
                .document { width: 1000px; background: #ffffff; border-radius: 10px; box-shadow: 0 0 0 1px rgba(0,0,0,.08), 0 18px 40px rgba(0,0,0,.25); overflow: hidden; color: #1f2328; font-size: 14px; line-height: 1.5; }
                .document .bar { background: #f6f8fa; border-bottom: 1px solid #d1d9e0; }
                .document .title { color: #59636e; }
                .markdown { padding: 8px 28px 24px; }
                .markdown h1 { font-size: 26px; font-weight: 600; padding-bottom: 8px; border-bottom: 1px solid #d1d9e0; margin: 16px 0; }
                .markdown table { border-collapse: collapse; width: 100%; }
                .markdown th, .markdown td { border: 1px solid #d1d9e0; padding: 6px 12px; text-align: left; vertical-align: top; }
                .markdown th { font-weight: 600; }
                .markdown tr:nth-child(2n) td { background: #f6f8fa; }
                .markdown code { font: 12px 'JetBrains Mono', 'DejaVu Sans Mono', monospace; background: rgba(129,139,152,.12); padding: 2px 5px; border-radius: 6px; white-space: nowrap; }
            </style>
            </head>
            <body>{$html}</body>
            </html>
            HTML;
    }

    protected static function chrome(): string
    {
        $candidates = array_filter([getenv('CHROME_PATH') ?: null, 'google-chrome', 'google-chrome-stable', 'chromium', 'chromium-browser', '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome']);

        foreach ($candidates as $candidate) {
            if (is_executable($candidate) || (new Process(['which', $candidate]))->run() === 0) {
                return $candidate;
            }
        }

        throw new RuntimeException('Chrome was not found. Set CHROME_PATH to take screenshots.');
    }

    /**
     * Crop the transparent space below and to the right of the content, matching the
     * margin above and to the left of it.
     */
    protected static function crop(string $path): void
    {
        $image = imagecreatefrompng($path) ?: throw new RuntimeException("Unable to read [{$path}].");
        [$width, $height] = [imagesx($image), imagesy($image)];

        [$left, $top, $right, $bottom] = [$width, $height, 0, 0];

        for ($y = 0; $y < $height; $y += 2) {
            for ($x = 0; $x < $width; $x += 2) {
                if ((imagecolorat($image, $x, $y) >> 24 & 0x7F) < 127) {
                    [$left, $top] = [min($left, $x), min($top, $y)];
                    [$right, $bottom] = [max($right, $x), max($bottom, $y)];
                }
            }
        }

        $cropped = imagecrop($image, ['x' => 0, 'y' => 0, 'width' => min($width, $right + $left + 2), 'height' => min($height, $bottom + $top + 2)])
            ?: throw new RuntimeException("Unable to crop [{$path}].");

        unset($image);

        imagesavealpha($cropped, true);
        imagepng($cropped, $path, 9);
    }
}
