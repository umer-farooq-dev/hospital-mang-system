<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Post-operative follow-up note on an operation. */
class OperationFollowup extends Model
{
    protected $fillable = ['ipd_operation_id', 'noted_at', 'note', 'created_by'];

    protected $casts = ['noted_at' => 'datetime'];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
