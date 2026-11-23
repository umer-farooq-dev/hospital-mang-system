<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Accounting year: open (entries allowed), closed (no new entries), locked (closed for good). */
class FiscalYear extends Model
{
    const STATUSES = ['open', 'closed', 'locked'];

    protected $fillable = ['name', 'starts_on', 'ends_on', 'status'];

    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date'];
}
