<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One import of a file at go-live: what was created, what already existed, the lines that failed. */
class DataImportRun extends Model
{
    protected $fillable = ['kind', 'file_name', 'user_id', 'options', 'created', 'existing', 'failed', 'errors', 'note'];

    protected $casts = ['options' => 'array', 'errors' => 'array', 'created' => 'integer', 'existing' => 'integer', 'failed' => 'integer'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
