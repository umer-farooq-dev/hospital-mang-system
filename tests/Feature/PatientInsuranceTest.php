<?php

namespace Tests\Feature;

use App\Models\Insurance;
use App\Models\MasterListItem;
use App\Models\PatientInsurance;
use App\Repositories\InsuranceRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PatientInsuranceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('master_list_items', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->unsignedInteger('code');
            $table->string('name');
            $table->string('lang_key')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('insurances', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->unsignedInteger('payer_type')->default(1);
            $table->decimal('coverage_percent', 5, 2)->nullable();
            $table->double('discount')->nullable();
            $table->double('service_tax')->default(0);
            $table->double('hospital_rate')->default(0);
            $table->double('total')->default(0);
            $table->string('insurance_no')->nullable();
            $table->string('insurance_code');
            $table->string('billing_option')->default('per_visit');
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
        Schema::create('patient_insurances', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('patient_id');
            $table->unsignedInteger('insurance_id');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->decimal('coverage_percent', 5, 2)->nullable();
            $table->unsignedTinyInteger('priority')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('users', fn (Blueprint $t) => $t->id());
        (require base_path('database/migrations/2026_11_20_120000_create_audit_logs_table.php'))->up();
        foreach ([1 => 'Insurance company', 2 => 'Partner company'] as $code => $name) {
            DB::table('master_list_items')->insert(['type' => 'payer_type', 'code' => $code, 'name' => $name, 'sort_order' => $code, 'is_active' => true]);
        }
        MasterListItem::flushCache();
    }

    private function payer(array $values = []): Insurance
    {
        return Insurance::create($values + ['name' => 'Assur '.uniqid(), 'insurance_code' => 'A', 'payer_type' => 1, 'status' => 1]);
    }

    public function test_payer_rules_accept_a_partner_company_without_insurance_figures(): void
    {
        $company = ['name' => 'SONABEL', 'payer_type' => 2, 'discount' => 10, 'insurance_code' => 'SNB', 'billing_option' => 'monthly'];
        $this->assertTrue(Validator::make($company, Insurance::rules())->passes());

        $this->assertTrue(Validator::make(['payer_type' => 9] + $company, Insurance::rules())->errors()->has('payer_type'), 'payer type must be in the master list');
        $this->assertTrue(Validator::make(['coverage_percent' => 120] + $company, Insurance::rules())->errors()->has('coverage_percent'));
        $this->assertTrue(Validator::make(['billing_option' => 'weekly'] + $company, Insurance::rules())->errors()->has('billing_option'));
    }

    public function test_blank_disease_rows_are_skipped(): void
    {
        $rows = app(InsuranceRepository::class)->prepareInputForDiseaseItem([
            'disease_name' => ['', 'Paludisme'],
            'disease_charge' => [null, '5,000'],
        ]);

        $this->assertSame([1 => ['disease_name' => 'Paludisme', 'disease_charge' => '5,000']], $rows);
    }

    public function test_policy_coverage_falls_back_to_the_payer(): void
    {
        $insurer = $this->payer(['coverage_percent' => 80]);
        $own = PatientInsurance::create(['patient_id' => 1, 'insurance_id' => $insurer->id, 'coverage_percent' => 90]);
        $inherited = PatientInsurance::create(['patient_id' => 1, 'insurance_id' => $insurer->id]);

        $this->assertSame(90.0, $own->effective_coverage);
        $this->assertSame(80.0, $inherited->fresh()->effective_coverage);
    }

    public function test_validity_follows_dates_and_both_switches(): void
    {
        $this->travelTo('2026-06-15');
        $insurer = $this->payer();
        $make = fn (array $v) => PatientInsurance::create($v + ['patient_id' => 1, 'insurance_id' => $insurer->id]);

        $valid = $make(['valid_from' => '2026-01-01', 'valid_to' => '2026-12-31']);
        $openEnded = $make([]);
        $expired = $make(['valid_to' => '2026-06-14']);
        $future = $make(['valid_from' => '2026-07-01']);
        $off = $make(['is_active' => false]);

        $this->assertSame(['valid', 'valid', 'expired', 'not_started', 'inactive'],
            collect([$valid, $openEnded, $expired, $future, $off])->map(fn ($p) => $p->fresh()->validity_status)->all());
        $this->assertEqualsCanonicalizing([$valid->id, $openEnded->id], PatientInsurance::validOn()->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$valid->id, $openEnded->id, $future->id], PatientInsurance::validOn('2026-07-01')->pluck('id')->all());

        $insurer->update(['status' => 0]);
        $this->assertSame([], PatientInsurance::validOn()->pluck('id')->all(), 'a deactivated payer covers nobody');
        $this->assertSame('inactive', $valid->fresh()->validity_status);
    }
}
