<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A balanced accounting entry. Never edited or deleted: a mistake is undone by a reversal entry. */
class JournalEntry extends Model
{
    protected $fillable = ['journal_id', 'fiscal_year_id', 'number', 'entry_date', 'reference', 'label', 'source_type', 'source_id', 'source_event',
        'reversal_of', 'exported_at', 'created_by'];

    protected $casts = ['entry_date' => 'date', 'exported_at' => 'datetime'];

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reversedBy()
    {
        return self::where('reversal_of', $this->id)->first();
    }

    public function total(): float
    {
        return round($this->lines->sum('debit'), 2);
    }
}
