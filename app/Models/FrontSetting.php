<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * App\Models\FrontSetting
 *
 * @property int $id
 * @property string $key
 * @property string $value
 * @property string $type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder|FrontSetting newModelQuery()
 * @method static Builder|FrontSetting newQuery()
 * @method static Builder|FrontSetting query()
 * @method static Builder|FrontSetting whereCreatedAt($value)
 * @method static Builder|FrontSetting whereId($value)
 * @method static Builder|FrontSetting whereKey($value)
 * @method static Builder|FrontSetting whereType($value)
 * @method static Builder|FrontSetting whereUpdatedAt($value)
 * @method static Builder|FrontSetting whereValue($value)
 *
 * @mixin \Eloquent
 *
 * @property-read mixed $logo_url
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection|Media[] $media
 * @property-read int|null $media_count
 */
class FrontSetting extends Model implements HasMedia
{
    use InteractsWithMedia;

    public $table = 'front_settings';

    public const PATH = 'front-settings';

    public const HOME_IMAGE_PATH = 'homepage-image';

    const ABOUT_US = 1;

    const HOME_PAGE = 2;

    const APPOINTMENT = 3;

    // home page blocks in their default order; the admin can hide or reorder them
    const HOME_SECTIONS = [
        ['key' => 'about', 'on' => 1], ['key' => 'services', 'on' => 1], ['key' => 'video', 'on' => 1],
        ['key' => 'process', 'on' => 1], ['key' => 'appointment', 'on' => 1], ['key' => 'doctors', 'on' => 1],
        ['key' => 'cases', 'on' => 1], ['key' => 'testimonials', 'on' => 1], ['key' => 'blog', 'on' => 1],
    ];

    // every uploadable website picture (key => media collection)
    const IMAGE_KEYS = [
        'about_us_image' => self::PATH, 'home_page_image' => self::HOME_IMAGE_PATH,
        'home_page_certified_doctor_image' => self::HOME_IMAGE_PATH, 'home_page_video_image' => self::HOME_IMAGE_PATH,
        'home_page_step_1_image' => self::HOME_IMAGE_PATH, 'home_page_step_2_image' => self::HOME_IMAGE_PATH,
        'home_page_step_3_image' => self::HOME_IMAGE_PATH, 'home_page_step_4_image' => self::HOME_IMAGE_PATH,
        'page_banner_image' => self::HOME_IMAGE_PATH,
    ];

    // uploaded background video of the home page video block
    const VIDEO_KEY = 'home_page_video_file';

    const VIDEO_PATH = 'homepage-video';

    const COLOR_KEYS = ['theme_color_primary', 'theme_color_secondary', 'theme_color_accent', 'theme_color_dark', 'theme_color_title'];

    /** Enabled home sections in the admin's order. */
    public static function homeSections(array $settings): array
    {
        $saved = json_decode($settings['home_sections'] ?? '', true) ?: self::HOME_SECTIONS;
        $known = array_column(self::HOME_SECTIONS, 'key');
        $saved = array_values(array_filter($saved, fn ($s) => in_array($s['key'] ?? null, $known, true)));
        foreach (array_diff($known, array_column($saved, 'key')) as $missing) {
            $saved[] = ['key' => $missing, 'on' => 1];
        }

        return $saved;
    }

    const STATUS_ARR = [
        self::ABOUT_US => 'About Us',
    ];

    public static $rules = [
        'about_us_title' => 'required',
        'about_us_mission' => 'required',
        'about_us_image' => 'nullable',
    ];

    public $fillable = [
        'key',
        'value',
        'type',
    ];

    protected $casts = [
        'id' => 'integer',
        'key' => 'string',
        'value' => 'string',
        'type' => 'string',
    ];

    public function getLogoUrlAttribute()
    {
        $media = $this->media->first();
        if (! empty($media)) {
            return $media->getFullUrl();
        }

        return $this->value;
    }
}
