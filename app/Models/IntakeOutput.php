<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/** One intake (intake_type set) or output (output_type set) in millilitres. */
class IntakeOutput extends Model
{
    protected $fillable = ['ipd_patient_department_id', 'recorded_at', 'intake_type', 'output_type', 'volume_ml', 'note', 'recorded_by'];

    protected $casts = ['recorded_at' => 'datetime', 'volume_ml' => 'float'];

    public function getIsIntakeAttribute(): bool
    {
        return $this->intake_type !== null;
    }

    /** Totals per day: date => [intake, output, balance]. */
    public static function dailyBalance(Collection $rows): array
    {
        $days = [];
        foreach ($rows as $row) {
            $day = $row->recorded_at->format('Y-m-d');
            $days[$day] ??= ['intake' => 0.0, 'output' => 0.0, 'balance' => 0.0];
            $days[$day][$row->is_intake ? 'intake' : 'output'] += $row->volume_ml;
            $days[$day]['balance'] = $days[$day]['intake'] - $days[$day]['output'];
        }
        krsort($days);

        return $days;
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
