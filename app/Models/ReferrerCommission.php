<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/** Commission rate of one referrer (an item of the "Referrers" master list). */
class ReferrerCommission extends Model
{
    protected $fillable = ['referrer_code', 'percent'];

    protected $casts = ['referrer_code' => 'integer', 'percent' => 'float'];

    /**
     * One row per referrer with visits in the period: visits, amount of the acts, rate, commission, paid and still due.
     *
     * @return array<int, array{code: int, name: string, visits: int, amount: float, percent: float, commission: float, paid: float, due: float}>
     */
    public static function report(Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();
        $rates = self::pluck('percent', 'referrer_code');
        $names = MasterListItem::labels('referrer');
        $visits = OpdPatientDepartment::whereNotNull('referrer')->where('referrer', '>', 0)->whereBetween('appointment_date', [$from, $to])
            ->selectRaw('referrer, COUNT(*) as visits, SUM(standard_charge) as amount')->groupBy('referrer')->get();
        $paid = ReferrerPayout::whereDate('period_from', '>=', $from->toDateString())->whereDate('period_to', '<=', $to->toDateString())
            ->selectRaw('referrer_code, SUM(amount) as paid')->groupBy('referrer_code')->pluck('paid', 'referrer_code');

        return $visits->map(function ($row) use ($rates, $names, $paid) {
            $percent = (float) ($rates[$row->referrer] ?? 0);
            $commission = round((float) $row->amount * $percent / 100, 2);
            $done = round((float) ($paid[$row->referrer] ?? 0), 2);

            return ['code' => (int) $row->referrer, 'name' => $names[$row->referrer] ?? '#'.$row->referrer, 'visits' => (int) $row->visits,
                'amount' => round((float) $row->amount, 2), 'percent' => $percent, 'commission' => $commission, 'paid' => $done, 'due' => round(max(0, $commission - $done), 2)];
        })->sortByDesc('commission')->values()->all();
    }
}
