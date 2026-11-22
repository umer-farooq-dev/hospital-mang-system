<?php

namespace Tests\Feature;

use App\Models\ReferrerCommission;
use App\Models\ReferrerPayout;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReferrerCommissionTest extends TestCase
{
    public function test_commission_per_referrer_with_payments_of_the_period(): void
    {
        Schema::create('master_list_items', function (Blueprint $t) {
            $t->id();
            $t->string('type');
            $t->unsignedInteger('code');
            $t->string('name');
            $t->string('lang_key')->nullable();
            $t->unsignedInteger('quantity')->nullable();
            $t->unsignedInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('opd_patient_departments', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('referrer')->nullable();
            $t->dateTime('appointment_date');
            $t->double('standard_charge');
        });
        foreach (['referrer_commissions' => ['referrer_code', 'percent'], 'referrer_payouts' => []] as $table => $unused) {
            Schema::create($table, function (Blueprint $t) use ($table) {
                $t->id();
                $t->unsignedInteger('referrer_code');
                if ($table === 'referrer_commissions') {
                    $t->decimal('percent', 5, 2)->default(0);
                } else {
                    $t->date('period_from');
                    $t->date('period_to');
                    $t->decimal('amount', 15, 2);
                    $t->date('paid_on');
                    $t->unsignedInteger('payment_method')->nullable();
                    $t->string('reference')->nullable();
                    $t->unsignedBigInteger('created_by')->nullable();
                }
                $t->timestamps();
            });
        }
        DB::table('master_list_items')->insert([['type' => 'referrer', 'code' => 1, 'name' => 'Clinique Wend-Kuni'], ['type' => 'referrer', 'code' => 2, 'name' => 'Dr Ouédraogo']]);
        DB::table('opd_patient_departments')->insert([
            ['referrer' => 1, 'appointment_date' => '2026-11-03 09:00', 'standard_charge' => 6000],
            ['referrer' => 1, 'appointment_date' => '2026-11-04 09:00', 'standard_charge' => 4000],
            ['referrer' => 2, 'appointment_date' => '2026-11-05 09:00', 'standard_charge' => 5000],
            ['referrer' => null, 'appointment_date' => '2026-11-05 10:00', 'standard_charge' => 5000],
            ['referrer' => 1, 'appointment_date' => '2026-12-01 09:00', 'standard_charge' => 9000],
        ]);
        ReferrerCommission::create(['referrer_code' => 1, 'percent' => 10]);
        ReferrerPayout::create(['referrer_code' => 1, 'period_from' => '2026-11-01', 'period_to' => '2026-11-30', 'amount' => 400, 'paid_on' => '2026-12-02']);

        $rows = collect(ReferrerCommission::report(Carbon::parse('2026-11-01'), Carbon::parse('2026-11-30')))->keyBy('code');
        $this->assertSame([2, 10000.0, 1000.0, 400.0, 600.0], [$rows[1]['visits'], $rows[1]['amount'], $rows[1]['commission'], $rows[1]['paid'], $rows[1]['due']]);
        $this->assertSame([0.0, 0.0], [$rows[2]['commission'], $rows[2]['due']], 'no rate set: no commission');
        $this->assertCount(2, $rows, 'not the visit without referrer, not December');
    }
}
