<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = ['name', 'contact_person', 'phone', 'email', 'address', 'tax_number', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    /** id => name of the active suppliers, plus the one already chosen. */
    public static function options(?int $current = null): array
    {
        return self::where('is_active', true)->when($current, fn ($q) => $q->orWhere('id', $current))->orderBy('name')->pluck('name', 'id')->all();
    }
}
