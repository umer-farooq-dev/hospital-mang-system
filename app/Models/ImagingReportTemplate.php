<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Imaging report template; seeded ones follow the user's language until the admin edits them. */
class ImagingReportTemplate extends Model
{
    protected $fillable = ['name', 'lang_key', 'modality', 'body', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer', 'modality' => 'integer'];

    public function getLabelAttribute(): string
    {
        $key = $this->lang_key ? $this->lang_key . '.name' : null;

        return $key && __($key) !== $key ? __($key) : $this->name;
    }

    public function getTextAttribute(): string
    {
        $key = $this->lang_key ? $this->lang_key . '.body' : null;

        return $this->body === null && $key && __($key) !== $key ? __($key) : (string) $this->body;
    }

    /** Active templates, those of the given modality first. */
    public static function forModality(?int $modality)
    {
        return self::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get()
            ->sortBy(fn ($t) => $modality && $t->modality === $modality ? 0 : 1)->values();
    }
}
