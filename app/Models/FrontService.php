<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class FrontService extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    public $table = 'front_services';

    public const PATH = 'front-services';

    public const IMAGE_PATH = 'front-services-image';

    // one table for the three kinds of website content
    public const SERVICE = 1;

    public const CASE_STUDY = 2;

    public const POST = 3;

    public const TYPES = [self::SERVICE => 'services', self::CASE_STUDY => 'cases', self::POST => 'news'];

    public static $rules = [
        'name' => 'required',
        'short_description' => 'required',
    ];

    public $fillable = [
        'type',
        'name',
        'category',
        'short_description',
        'description',
        'published_at',
        'sort_order',
    ];

    protected $appends = ['icon_url', 'image_url'];

    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'short_description' => 'string',
        'type' => 'integer',
        'sort_order' => 'integer',
        'published_at' => 'date:Y-m-d',
    ];

    public function scopeOfType($query, int $type)
    {
        return $query->where('type', $type)->orderBy('sort_order')->orderByDesc('published_at')->orderByDesc('id');
    }

    public function getImageUrlAttribute()
    {
        return $this->getFirstMediaUrl(self::IMAGE_PATH) ?: null;
    }

    /** Admin text to paragraphs and "- " lines to a checklist, escaped. */
    public function getBodyHtmlAttribute(): string
    {
        $html = '';
        foreach (preg_split("/\R{2,}/", trim((string) ($this->description ?: $this->short_description))) as $block) {
            $lines = preg_split('/\R/', trim($block));
            if (collect($lines)->every(fn ($l) => str_starts_with(ltrim($l), '- '))) {
                $html .= '<ul>'.collect($lines)->map(fn ($l) => '<li><i class="fa-solid fa-badge-check"></i> '.e(trim(substr(ltrim($l), 2))).'</li>')->implode('').'</ul>';
            } else {
                $html .= '<p>'.nl2br(e($block)).'</p>';
            }
        }

        return $html;
    }

    public function getIconUrlAttribute()
    {
        return $this->getFirstMediaUrl(self::PATH) ?: null;
    }
}
