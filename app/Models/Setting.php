<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Rennokki\QueryCache\Traits\QueryCacheable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * App\Models\Setting
 *
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read mixed $logo_url
 * @property-read Collection|Media[] $media
 * @property-read int|null $media_count
 *
 * @method static Builder|Setting newModelQuery()
 * @method static Builder|Setting newQuery()
 * @method static Builder|Setting query()
 * @method static Builder|Setting whereCreatedAt($value)
 * @method static Builder|Setting whereId($value)
 * @method static Builder|Setting whereKey($value)
 * @method static Builder|Setting whereUpdatedAt($value)
 * @method static Builder|Setting whereValue($value)
 *
 * @mixin Model
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Setting whereCompanyName($value)
 */
class Setting extends Model implements HasMedia
{
    // website social links (setting key => Font Awesome brand icon)
    const SOCIAL_ICONS = ['facebook_url' => 'fa-facebook-f', 'twitter_url' => 'fa-x-twitter', 'instagram_url' => 'fa-instagram', 'linkedIn_url' => 'fa-linkedin-in'];

    use InteractsWithMedia, QueryCacheable;

    protected $cacheFor = 3600; // 1 hour

    public $table = 'settings';

    public const PATH = 'settings';

    public const VIDEO_MEDIA_COLLECTION = 'patient_queue_theme_video';

    const CURRENCIES = [
        'eur' => 'Euro (EUR)',
        'aud' => 'Australia Dollar (AUD)',
        'inr' => 'India Rupee (INR)',
        'usd' => 'USA Dollar (USD)',
        'jpy' => 'Japanese Yen (JPY)',
        'gbp' => 'British Pound (GBP)',
        'cad' => 'Canadian Dollar (CAD)',
    ];

    public $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'id' => 'integer',
        'key' => 'string',
        'value' => 'string',
    ];

    // Choices for the amount format (Settings > General); the label is what the admin sees.
    const THOUSANDS_SEPARATORS = [',' => '1,000', '.' => '1.000', ' ' => '1 000', '' => '1000'];

    const DECIMAL_SEPARATORS = ['.' => '0.50', ',' => '0,50'];

    public static $rules = [
        'app_name' => 'required|string',
        'company_name' => 'required|string',
        'app_logo' => 'nullable|mimes:jpg,jpeg,png',
        'favicon' => 'nullable|mimes:jpg,jpeg,png',
        'hospital_email' => 'required|email:rfc,dns',
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
