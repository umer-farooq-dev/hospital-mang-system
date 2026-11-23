<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    const KINDS = ['cash', 'bank', 'sales', 'purchases', 'insurance', 'general'];

    protected $fillable = ['code', 'name', 'kind', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
