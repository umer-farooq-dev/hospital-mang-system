<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** One scheduled dose of the IPD prescription and what the nurse did (Given, Held, Refused... from Master Lists). */
class MedicationAdministration extends Model
{
    protected $fillable = ['ipd_patient_department_id', 'ipd_prescription_item_id', 'scheduled_at', 'status', 'administered_at', 'note', 'recorded_by'];

    protected $casts = ['scheduled_at' => 'datetime', 'administered_at' => 'datetime'];

    /** Times of day for n doses: the admin's times (Settings > Inpatient), else spread evenly from 08:00. */
    public static function doseTimes(int $perDay, array $configured): array
    {
        $perDay = max(1, min(24, $perDay));
        $times = array_values(array_filter(array_map('trim', explode(',', (string) ($configured[$perDay] ?? ''))),
            fn ($t) => preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $t)));
        if (count($times) === $perDay) {
            return $times;
        }
        $step = intdiv(24 * 60, $perDay);

        return array_map(fn ($i) => sprintf('%02d:%02d', intdiv((8 * 60 + $i * $step) % 1440, 60), (8 * 60 + $i * $step) % 60), range(0, $perDay - 1));
    }

    /** Dose moments from the start day for the given number of days, the first day from the prescription time on, never after $until. */
    public static function schedule(Carbon $start, int $days, array $times, ?Carbon $until = null): array
    {
        $moments = [];
        for ($d = 0; $d < max(1, $days); $d++) {
            foreach ($times as $time) {
                [$h, $m] = array_map('intval', explode(':', $time));
                $moment = $start->copy()->startOfDay()->addDays($d)->setTime($h, $m);
                if ($h === 0 && $m === 0) {
                    $moment->addDay(); // midnight dose belongs to the end of the day
                }
                if ($moment->lt($start->copy()->startOfMinute()) || ($until && $moment->gt($until))) {
                    continue;
                }
                $moments[] = $moment;
            }
        }

        return $moments;
    }

    /** Creates the missing scheduled doses of an admission from its prescriptions (safe to call again). */
    public static function generateFor(IpdPatientDepartment $ipd): int
    {
        $configured = json_decode((string) Setting::where('key', 'mar_dose_times')->value('value'), true) ?: [];
        $until = $ipd->discharge && $ipd->discharge_date ? Carbon::parse($ipd->discharge_date) : null;
        $rows = [];
        $items = IpdPrescriptionItem::whereHas('ipdPrescription', fn ($q) => $q->where('ipd_patient_department_id', $ipd->id))
            ->with('ipdPrescription')->get();
        foreach ($items as $item) {
            $perDay = (int) round(MasterListItem::quantity('dose_interval', $item->dose_interval) ?? 1);
            $days = (int) ceil(MasterListItem::quantity('dose_duration', $item->day) ?? 1);
            foreach (self::schedule(Carbon::parse($item->ipdPrescription->created_at), $days, self::doseTimes($perDay, $configured), $until) as $moment) {
                $rows[] = ['ipd_patient_department_id' => $ipd->id, 'ipd_prescription_item_id' => $item->id,
                    'scheduled_at' => $moment->format('Y-m-d H:i:s'), 'created_at' => now(), 'updated_at' => now()];
            }
        }

        return $rows ? DB::table('medication_administrations')->insertOrIgnore($rows) : 0;
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(IpdPrescriptionItem::class, 'ipd_prescription_item_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
