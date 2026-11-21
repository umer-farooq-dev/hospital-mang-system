<?php

namespace Tests\Feature;

use App\Models\CoverageRule;
use App\Models\Insurance;
use App\Models\PatientInsurance;
use App\Utils\PriceSplit;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PriceSplitTest extends TestCase
{
    private Insurance $insurer;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('key');
            $t->string('value')->nullable();
            $t->timestamps();
        });
        Schema::create('insurances', function (Blueprint $t) {
            $t->increments('id');
            $t->string('name');
            $t->string('insurance_code')->default('X');
            $t->decimal('coverage_percent', 5, 2)->nullable();
            $t->double('discount')->nullable();
            $t->boolean('status')->default(1);
            $t->timestamps();
        });
        Schema::create('patient_insurances', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('patient_id');
            $t->unsignedInteger('insurance_id');
            $t->date('valid_from')->nullable();
            $t->date('valid_to')->nullable();
            $t->decimal('coverage_percent', 5, 2)->nullable();
            $t->decimal('annual_limit', 15, 2)->nullable();
            $t->decimal('visit_limit', 15, 2)->nullable();
            $t->unsignedTinyInteger('priority')->default(1);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('coverage_rules', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('insurance_id');
            $t->unsignedBigInteger('doctor_department_id')->nullable();
            $t->unsignedInteger('charge_category_id')->nullable();
            $t->decimal('coverage_percent', 5, 2)->nullable();
            $t->boolean('not_covered')->default(false);
            $t->boolean('needs_approval')->default(false);
            $t->decimal('max_amount', 15, 2)->nullable();
            $t->timestamps();
        });
        Schema::create('doctor_departments', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->decimal('discount_percent', 5, 2)->nullable();
            $t->timestamps();
        });
        Schema::create('opd_patient_departments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('patient_insurance_id')->nullable();
            $t->dateTime('appointment_date');
            $t->decimal('payer_amount', 15, 2)->default(0);
        });
        Schema::create('exam_orders', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('patient_insurance_id')->nullable();
            $t->dateTime('ordered_at');
        });
        Schema::create('exam_order_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('exam_order_id');
            $t->decimal('payer_amount', 15, 2)->default(0);
        });
        Schema::create('users', fn (Blueprint $t) => $t->id());
        (require base_path('database/migrations/2026_11_20_120000_create_audit_logs_table.php'))->up();

        DB::table('settings')->insert(['key' => 'currency_decimals', 'value' => '0']); // FCFA
        DB::table('doctor_departments')->insert([
            ['id' => 1, 'title' => 'Médecine générale', 'discount_percent' => 10],
            ['id' => 2, 'title' => 'Gynécologie', 'discount_percent' => null],
        ]);
        $this->insurer = Insurance::create(['name' => 'Assur 80', 'coverage_percent' => 80, 'status' => 1]);
    }

    private function policy(array $values = []): PatientInsurance
    {
        return PatientInsurance::create($values + ['patient_id' => 1, 'insurance_id' => $this->insurer->id])->load('insurance');
    }

    public function test_client_example_department_discount_then_insurer_80_percent(): void
    {
        $split = PriceSplit::compute(6000, $this->policy(), 1, ['date' => '2026-11-24']);

        $this->assertSame([6000.0, 10.0, 600.0, 5400.0, 4320.0, 1080.0, []],
            [$split['gross'], $split['discount_percent'], $split['discount_amount'], $split['net'], $split['payer_amount'], $split['patient_amount'], $split['warnings']]);
    }

    public function test_discounts_compound_and_self_pay_gets_only_the_department_discount(): void
    {
        $this->insurer->update(['discount' => 10]);
        $this->assertSame(19.0, PriceSplit::compute(10000, $this->policy(), 1)['discount_percent'], 'réduction sur réduction');

        $selfPay = PriceSplit::compute(10000, null, 1);
        $this->assertSame([9000.0, 0.0, 9000.0], [$selfPay['net'], $selfPay['payer_amount'], $selfPay['patient_amount']]);
    }

    public function test_insurer_accepting_only_listed_departments(): void
    {
        CoverageRule::create(['insurance_id' => $this->insurer->id, 'doctor_department_id' => 2, 'coverage_percent' => 90]);
        CoverageRule::create(['insurance_id' => $this->insurer->id, 'not_covered' => true]); // everything else

        $this->assertSame(9000.0, PriceSplit::compute(10000, $this->policy(), 2)['payer_amount']);
        $other = PriceSplit::compute(10000, $this->policy(), 1);
        $this->assertSame([0.0, 9000.0, ['not_covered']], [$other['payer_amount'], $other['patient_amount'], $other['warnings']]);
    }

    public function test_approval_needed_until_the_voucher_number_is_entered(): void
    {
        CoverageRule::create(['insurance_id' => $this->insurer->id, 'needs_approval' => true]);

        $this->assertSame(['approval_needed'], PriceSplit::compute(5000, $this->policy(), 2)['warnings']);
        $this->assertSame(4000.0, PriceSplit::compute(5000, $this->policy(), 2, ['voucher_number' => 'B-31604'])['payer_amount']);
    }

    public function test_limits_move_the_rest_to_the_patient(): void
    {
        $perVisit = PriceSplit::compute(10000, $this->policy(['visit_limit' => 2000]), 2);
        $this->assertSame([2000.0, 8000.0, ['limit_reached']], [$perVisit['payer_amount'], $perVisit['patient_amount'], $perVisit['warnings']]);

        $annual = $this->policy(['annual_limit' => 5000]);
        DB::table('opd_patient_departments')->insert([
            ['patient_insurance_id' => $annual->id, 'appointment_date' => '2026-03-01', 'payer_amount' => 4000],
            ['patient_insurance_id' => $annual->id, 'appointment_date' => '2025-12-30', 'payer_amount' => 4000], // last year
        ]);
        $this->assertSame(1000.0, PriceSplit::compute(10000, $annual, 2, ['date' => '2026-11-24'])['payer_amount']);
    }

    public function test_expired_policy_means_the_patient_pays_everything(): void
    {
        $this->insurer->update(['discount' => 20]);
        $split = PriceSplit::compute(6000, $this->policy(['valid_to' => '2026-10-31']), 2, ['date' => '2026-11-24']);

        $this->assertSame([0.0, 0.0, 6000.0, ['policy_not_valid']], [$split['discount_amount'], $split['payer_amount'], $split['patient_amount'], $split['warnings']]);
    }
}
