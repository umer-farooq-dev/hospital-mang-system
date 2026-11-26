<?php

namespace App\Utils;

/**
 * The software in the hospital's own colour: the stock admin theme is built on one purple (#6571ff)
 * and a few shades of it. The CSS rules that use them are copied with the hospital's colour
 * (Front CMS > main colour) and its shades, once per colour, into public/brand/.
 */
class Brand
{
    // stock theme colour => how the hospital's colour replaces it
    const PALETTE = [
        '#6571ff' => ['mix', 0],      // primary
        '#5660d9' => ['dark', 0.15],
        '#515acc' => ['dark', 0.20],  // hover
        '#4c55bf' => ['dark', 0.25],
        '#3d4499' => ['dark', 0.40],
        '#e0e3ff' => ['light', 0.88], // light background
        '#cacde8' => ['light', 0.75],
        '#d0d3ee' => ['light', 0.78],
        '#d5d8f3' => ['light', 0.80],
    ];

    const RGB = ['101,113,255' => ['mix', 0], '124,134,255' => ['light', 0.2]];

    const SOURCES = ['assets/css/style.css', 'assets/css/plugins.css', 'assets/css/custom.css'];

    const DARK_SOURCES = ['assets/css/style.dark.css', 'assets/css/plugins.dark.css', 'assets/css/custom.css'];

    const DEFAULT = '#6571ff';

    /** URL of the stylesheet that recolours the software (light or dark mode), or null when the stock colour is kept. */
    public static function stylesheet(bool $dark = false): ?string
    {
        $sources = $dark ? self::DARK_SOURCES : self::SOURCES;
        $color = strtolower((string) frontSetting('theme_color_primary', self::DEFAULT));
        if (! preg_match('/^#[0-9a-f]{6}$/', $color) || $color === self::DEFAULT) {
            return null;
        }
        $name = 'brand/admin-'.($dark ? 'dark-' : '').substr(md5($color.'|'.implode(',', array_map(fn ($f) => $f.@filemtime(public_path($f)), $sources))), 0, 12).'.css';
        if (! is_file(public_path($name))) {
            @mkdir(public_path('brand'), 0775, true);
            file_put_contents(public_path($name), self::build($color, $sources), LOCK_EX);
        }

        return asset($name);
    }

    /** Rules of the stock stylesheets that use the palette, rewritten with the hospital's colour. */
    public static function build(string $color, array $sources = self::SOURCES): string
    {
        $map = [];
        foreach (self::PALETTE as $stock => [$how, $amount]) {
            $map[$stock] = self::hex(self::shade($color, $how, $amount));
        }
        foreach (self::RGB as $stock => [$how, $amount]) {
            $map[$stock] = implode(',', self::shade($color, $how, $amount));
        }
        $pattern = '/'.implode('|', array_map(fn ($c) => preg_quote($c, '/'), array_keys($map))).'/i';

        $out = "/* generated from the stock theme for {$color}; delete the file to rebuild */\n";
        foreach ($sources as $file) {
            $css = preg_replace('/@(charset|import)[^;]*;/i', '', (string) @file_get_contents(public_path($file)));
            // keep media queries around their rules
            preg_match_all('/(@media[^{]+\{(?:[^{}]*\{[^{}]*\})*[^{}]*\})|([^{}@]+\{[^{}]*\})/', $css, $blocks);
            foreach ($blocks[0] as $block) {
                if (preg_match($pattern, $block)) {
                    $out .= preg_replace_callback($pattern, fn ($m) => $map[strtolower($m[0])], $block)."\n";
                }
            }
        }

        return $out;
    }

    /** @return int[] r, g, b */
    private static function shade(string $hex, string $how, float $amount): array
    {
        [$r, $g, $b] = array_map('hexdec', str_split(substr($hex, 1), 2));
        $to = $how === 'dark' ? 0 : 255;

        return array_map(fn ($c) => (int) round($c + ($to - $c) * ($how === 'mix' ? 0 : $amount)), [$r, $g, $b]);
    }

    private static function hex(array $rgb): string
    {
        return '#'.implode('', array_map(fn ($c) => str_pad(dechex(max(0, min(255, $c))), 2, '0', STR_PAD_LEFT), $rgb));
    }
}
