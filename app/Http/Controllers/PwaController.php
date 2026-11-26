<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Response;

/**
 * Installable app ("desktop icon"): Chrome/Edge offer "Install" and open the system in its own window
 * with the hospital's name, logo and colour (Settings > General and Front CMS).
 */
class PwaController extends Controller
{
    public function manifest()
    {
        $name = getAppName();

        return response()->json([
            'name' => $name,
            'short_name' => mb_strimwidth($name, 0, 24, '…'),
            'start_url' => route('login'),
            'scope' => url('/').'/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => frontSetting('theme_color_primary', '#009E49'),
            'lang' => app()->getLocale(),
            'icons' => [
                ['src' => route('pwa.icon', 192), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => route('pwa.icon', 512), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json']);
    }

    /** Square PNG of the hospital logo (white background), made once per logo and size. */
    public function icon(int $size)
    {
        $source = $this->logoPath();
        $cache = storage_path('app/pwa/'.md5($source.'|'.@filemtime($source)).'-'.$size.'.png');
        if (! is_file($cache)) {
            @mkdir(dirname($cache), 0775, true);
            $logo = @imagecreatefromstring((string) @file_get_contents($source));
            $canvas = imagecreatetruecolor($size, $size);
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
            if ($logo) {
                $box = (int) round($size * 0.8);
                $scale = min($box / imagesx($logo), $box / imagesy($logo));
                $w = (int) round(imagesx($logo) * $scale);
                $h = (int) round(imagesy($logo) * $scale);
                imagecopyresampled($canvas, $logo, (int) (($size - $w) / 2), (int) (($size - $h) / 2), 0, 0, $w, $h, imagesx($logo), imagesy($logo));
            }
            imagepng($canvas, $cache);
        }

        return response()->file($cache, ['Content-Type' => 'image/png', 'Cache-Control' => 'public, max-age=86400']);
    }

    private function logoPath(): string
    {
        $setting = Setting::where('key', 'app_logo')->first();
        $media = $setting?->getFirstMedia(Setting::PATH);
        if ($media && is_file($media->getPath())) {
            return $media->getPath();
        }
        $value = ltrim((string) parse_url((string) $setting?->value, PHP_URL_PATH), '/');

        return is_file(public_path($value)) ? public_path($value) : public_path('web/img/logo.jpg');
    }
}
