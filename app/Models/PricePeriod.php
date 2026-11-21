<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A time window with a surcharge (old "Périodes"): weekend, night, holidays. A night that
 * crosses midnight is written with an end time before the start time (20:00 to 07:00).
 *
 * @property string|null $days ISO weekdays 1 (Monday) to 7 (Sunday), comma separated; empty = every day
 */
class PricePeriod extends Model
{
    protected $table = 'price_periods';

    protected $fillable = ['name', 'lang_key', 'days', 'start_time', 'end_time', 'surcharge_percent', 'is_active'];

    protected $casts = [
        'surcharge_percent' => 'float',
        'is_active' => 'boolean',
    ];

    public static function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'days' => 'nullable|array',
            'days.*' => 'integer|between:1,7',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'surcharge_percent' => 'required|numeric|min:0|max:1000',
        ];
    }

    public function getLabelAttribute(): string
    {
        if ($this->lang_key && __($this->lang_key) !== $this->lang_key) {
            return __($this->lang_key);
        }

        return $this->name;
    }

    public function dayList(): array
    {
        return array_values(array_filter(array_map('intval', explode(',', (string) $this->days))));
    }

    public function matches(Carbon $at): bool
    {
        $days = $this->dayList();
        if ($days && ! in_array($at->dayOfWeekIso, $days)) {
            return false;
        }
        if (! $this->start_time || ! $this->end_time) {
            return true;
        }
        $time = $at->format('H:i:s');
        $start = Carbon::parse($this->start_time)->format('H:i:s');
        $end = Carbon::parse($this->end_time)->format('H:i:s');

        return $start <= $end ? ($time >= $start && $time < $end) : ($time >= $start || $time < $end);
    }

    /** Surcharge % at a moment; overlapping periods do not add up, the highest one applies. */
    public static function surchargeAt($at): float
    {
        $at = Carbon::parse($at);

        return (float) self::where('is_active', true)->get()->filter->matches($at)->max('surcharge_percent');
    }
}
