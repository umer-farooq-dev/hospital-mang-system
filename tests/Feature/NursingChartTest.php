<?php

namespace Tests\Feature;

use App\Models\IntakeOutput;
use App\Models\MedicationAdministration;
use App\Models\VitalType;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NursingChartTest extends TestCase
{
    public function test_dose_times_follow_the_admin_times_or_spread_from_eight(): void
    {
        $configured = ['2' => '07:00,19:00', '3' => '08:00,bad,20:00'];

        $this->assertSame(['07:00', '19:00'], MedicationAdministration::doseTimes(2, $configured));
        $this->assertSame(['08:00', '16:00', '00:00'], MedicationAdministration::doseTimes(3, $configured), 'invalid times fall back to the spread');
        $this->assertSame(['08:00', '14:00', '20:00', '02:00'], MedicationAdministration::doseTimes(4, []));
        $this->assertSame(['08:00'], MedicationAdministration::doseTimes(0, []), 'at least one dose');
    }

    public function test_schedule_starts_at_the_prescription_and_stops_at_discharge(): void
    {
        $start = Carbon::parse('2026-11-20 10:30');
        $moments = array_map(fn ($m) => $m->format('d H:i'), MedicationAdministration::schedule($start, 2, ['08:00', '20:00']));
        $this->assertSame(['20 20:00', '21 08:00', '21 20:00'], $moments, 'the 08:00 dose before the prescription is skipped');

        $until = Carbon::parse('2026-11-21 12:00');
        $this->assertCount(2, MedicationAdministration::schedule($start, 3, ['08:00', '20:00'], $until));

        $night = array_map(fn ($m) => $m->format('d H:i'), MedicationAdministration::schedule(Carbon::parse('2026-11-20 05:00'), 1, ['06:00', '00:00']));
        $this->assertSame(['20 06:00', '21 00:00'], $night, 'the midnight dose closes the day');
    }

    public function test_vital_out_of_range_and_daily_fluid_balance(): void
    {
        $temperature = new VitalType(['name' => 'Temperature', 'unit' => '°C', 'normal_min' => 36.1, 'normal_max' => 37.5]);
        $this->assertTrue($temperature->isAbnormal(38.4));
        $this->assertFalse($temperature->isAbnormal(37.0));
        $this->assertFalse((new VitalType(['name' => 'Weight']))->isAbnormal(80));

        $rows = collect([
            new IntakeOutput(['recorded_at' => '2026-11-21 08:00', 'intake_type' => 1, 'volume_ml' => 1500]),
            new IntakeOutput(['recorded_at' => '2026-11-21 14:00', 'output_type' => 1, 'volume_ml' => 900]),
            new IntakeOutput(['recorded_at' => '2026-11-22 09:00', 'output_type' => 2, 'volume_ml' => 200]),
        ]);
        $balance = IntakeOutput::dailyBalance($rows);
        $this->assertSame(['2026-11-22', '2026-11-21'], array_keys($balance), 'latest day first');
        $this->assertSame(600.0, $balance['2026-11-21']['balance']);
        $this->assertSame(-200.0, $balance['2026-11-22']['balance']);
    }
}
