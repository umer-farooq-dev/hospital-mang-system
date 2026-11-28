<?php

namespace Tests\Feature;

use App\Models\CoverageRule;
use App\Models\ExamOrder;
use App\Models\Insurance;
use App\Models\PatientInsurance;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExamOrderTest extends TestCase
{
    public function test_exams_are_priced_from_the_payer_list_and_split_by_charge_category(): void
    {
        $tables = [
            'settings' => fn ($t) => [$t->string('key'), $t->string('value')->nullable(), $t->timestamps()],
            'price_lists' => fn ($t) => [$t->string('name'), $t->string('lang_key')->nullable(), $t->decimal('adjustment_percent', 6, 2)->default(0),
                $t->boolean('is_default')->default(false), $t->boolean('is_active')->default(true), $t->unsignedInteger('sort_order')->default(0), $t->timestamps()],
            'price_periods' => fn ($t) => [$t->string('name'), $t->string('days')->nullable(), $t->time('start_time')->nullable(), $t->time('end_time')->nullable(),
                $t->decimal('surcharge_percent', 6, 2)->default(0), $t->boolean('is_active')->default(true), $t->timestamps()],
            'item_prices' => fn ($t) => [$t->string('priceable_type'), $t->unsignedBigInteger('priceable_id'), $t->unsignedBigInteger('price_list_id'),
                $t->decimal('price', 15, 2)->nullable(), $t->decimal('doctor_share_percent', 5, 2)->nullable(), $t->decimal('assistant_share_percent', 5, 2)->nullable(), $t->timestamps()],
            'pathology_tests' => fn ($t) => [$t->string('test_name'), $t->unsignedInteger('charge_category_id'), $t->double('standard_charge'), $t->timestamps()],
            'insurances' => fn ($t) => [$t->string('name'), $t->string('insurance_code')->default('X'), $t->decimal('coverage_percent', 5, 2)->nullable(),
                $t->double('discount')->nullable(), $t->unsignedBigInteger('price_list_id')->nullable(), $t->boolean('status')->default(1), $t->timestamps()],
            'patient_insurances' => fn ($t) => [$t->unsignedInteger('patient_id'), $t->unsignedInteger('insurance_id'), $t->date('valid_from')->nullable(), $t->date('valid_to')->nullable(),
                $t->decimal('coverage_percent', 5, 2)->nullable(), $t->decimal('annual_limit', 15, 2)->nullable(), $t->decimal('visit_limit', 15, 2)->nullable(),
                $t->unsignedTinyInteger('priority')->default(1), $t->boolean('is_active')->default(true), $t->timestamps()],
            'coverage_rules' => fn ($t) => [$t->unsignedInteger('insurance_id'), $t->unsignedBigInteger('doctor_department_id')->nullable(), $t->unsignedInteger('charge_category_id')->nullable(),
                $t->decimal('coverage_percent', 5, 2)->nullable(), $t->boolean('not_covered')->default(false), $t->boolean('needs_approval')->default(false),
                $t->decimal('max_amount', 15, 2)->nullable(), $t->timestamps()],
            'doctor_departments' => fn ($t) => [$t->string('title'), $t->decimal('discount_percent', 5, 2)->nullable()],
            'packages' => fn ($t) => [$t->string('name'), $t->double('total_amount'), $t->timestamps()],
            'users' => fn ($t) => [],
        ];
        foreach ($tables as $name => $columns) {
            Schema::create($name, function (Blueprint $t) use ($columns) {
                $t->id();
                $columns($t);
            });
        }
        (require base_path('database/migrations/2026_11_20_120000_create_audit_logs_table.php'))->up();

        DB::table('price_lists')->insert([
            ['id' => 1, 'name' => 'Cash', 'adjustment_percent' => 0, 'is_default' => true],
            ['id' => 2, 'name' => 'Insured', 'adjustment_percent' => 15, 'is_default' => false],
        ]);
        DB::table('pathology_tests')->insert([
            ['id' => 1, 'test_name' => 'NFS', 'charge_category_id' => 1, 'standard_charge' => 3500],
            ['id' => 2, 'test_name' => 'G.E. avec frottis', 'charge_category_id' => 2, 'standard_charge' => 1500],
        ]);
        $insurer = Insurance::create(['name' => 'Assur 80', 'coverage_percent' => 80, 'price_list_id' => 2, 'status' => 1]);
        CoverageRule::create(['insurance_id' => $insurer->id, 'charge_category_id' => 2, 'not_covered' => true]);
        $policy = PatientInsurance::create(['patient_id' => 7, 'insurance_id' => $insurer->id]);

        $lines = ExamOrder::priceTests(['pathology_test:1', 'pathology_test:2', 'pathology_test:99', 'radiology_test:abc'],
            ['patient_id' => 7, 'patient_insurance_id' => $policy->id, 'ordered_at' => '2026-11-24 10:00']);

        $this->assertSame(['NFS', 'G.E. avec frottis'], array_column($lines, 'description'), 'unknown or malformed tests are dropped');
        $this->assertSame([4025.0, 3220.0, 805.0], [$lines[0]['split']['gross'], $lines[0]['split']['payer_amount'], $lines[0]['split']['patient_amount']], 'insured list +15%, insurer 80%');
        $this->assertSame([1500.0, 0.0, 1500.0, ['not_covered']], [$lines[1]['split']['gross'], $lines[1]['split']['payer_amount'], $lines[1]['split']['patient_amount'], $lines[1]['split']['warnings']],
            'not covered: the patient pays the cash price');

        Schema::create('charge_categories', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->integer('charge_type')->default(1);
            $t->timestamps();
        });
        Schema::create('charges', function (Blueprint $t) {
            $t->id();
            $t->integer('charge_type')->default(1);
            $t->unsignedInteger('charge_category_id');
            $t->string('code');
            $t->double('standard_charge');
            $t->timestamps();
        });
        DB::table('charges')->insert(['id' => 1, 'charge_category_id' => 1, 'code' => 'Consultation de gynécologue', 'standard_charge' => 7000]);
        $act = ExamOrder::priceTests(['charge:1'], ['patient_id' => 7, 'patient_insurance_id' => $policy->id]);
        $this->assertSame(['Consultation de gynécologue', 8050.0, 6440.0], [$act[0]['description'], $act[0]['split']['gross'], $act[0]['split']['payer_amount']], 'an act of the tariff is priced like an exam');

        DB::table('packages')->insert(['id' => 1, 'name' => 'Check-up adulte', 'total_amount' => 20000]);
        $checkup = ExamOrder::priceTests(['package:1'], ['patient_id' => 7, 'patient_insurance_id' => $policy->id]);
        $this->assertSame(['Check-up adulte', 23000.0, 18400.0], [$checkup[0]['description'], $checkup[0]['split']['gross'], $checkup[0]['split']['payer_amount']], 'a check-up package is priced and split like an exam');

        $selfPay = ExamOrder::priceTests(['pathology_test:1'], ['patient_id' => 7, 'patient_insurance_id' => $policy->id + 50]);
        $this->assertSame([3500.0, 3500.0], [$selfPay[0]['split']['gross'], $selfPay[0]['split']['patient_amount']], "another patient's policy is ignored: cash price");
    }
}
