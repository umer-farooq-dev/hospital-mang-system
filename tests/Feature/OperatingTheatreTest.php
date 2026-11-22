<?php

namespace Tests\Feature;

use App\Models\IpdOperation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OperatingTheatreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('key');
            $t->text('value')->nullable();
            $t->timestamps();
        });
        Schema::create('price_lists', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('lang_key')->nullable();
            $t->decimal('adjustment_percent', 8, 2)->default(0);
            $t->decimal('k_value', 14, 2)->nullable();
            $t->boolean('is_default')->default(false);
            $t->boolean('is_active')->default(true);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });
        Schema::create('operations', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('operation_category_id')->nullable();
            $t->string('name');
            $t->decimal('k_surgeon', 8, 2)->nullable();
            $t->decimal('k_assistant', 8, 2)->nullable();
            $t->decimal('k_anaesthetist', 8, 2)->nullable();
            $t->timestamps();
        });
        Schema::create('ipd_patient_departments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('patient_id');
            $t->unsignedBigInteger('price_list_id')->nullable();
            $t->unsignedBigInteger('patient_insurance_id')->nullable();
            $t->timestamps();
        });
        Schema::create('ipd_operation', function (Blueprint $t) {
            $t->id();
            $t->string('ref_no')->nullable();
            $t->unsignedBigInteger('operation_category_id')->nullable();
            $t->unsignedBigInteger('operation_id')->nullable();
            $t->unsignedBigInteger('ipd_patient_department_id')->nullable();
            $t->dateTime('operation_date')->nullable();
            $t->unsignedInteger('ot_room')->nullable();
            $t->dateTime('scheduled_start')->nullable();
            $t->dateTime('scheduled_end')->nullable();
            $t->unsignedInteger('status')->nullable();
            $t->unsignedInteger('intervention_type')->nullable();
            $t->unsignedBigInteger('doctor_id')->nullable();
            $t->unsignedBigInteger('assistant_doctor_id')->nullable();
            $t->unsignedBigInteger('anaesthetist_doctor_id')->nullable();
            $t->string('anesthesia_type')->nullable();
            $t->text('preop_checks')->nullable();
            $t->decimal('k_value', 14, 2)->nullable();
            $t->decimal('surgeon_fee', 14, 2)->default(0);
            $t->decimal('assistant_fee', 14, 2)->default(0);
            $t->decimal('anaesthetist_fee', 14, 2)->default(0);
            $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->timestamps();
        });
        (require base_path('database/migrations/2026_11_20_120000_create_audit_logs_table.php'))->up();
        DB::table('settings')->insert(['key' => 'ot_excluded_statuses', 'value' => '5']);
        DB::table('price_lists')->insert([['id' => 1, 'name' => 'Cash', 'is_default' => true, 'k_value' => 1000], ['id' => 2, 'name' => 'Insured', 'is_default' => false, 'k_value' => 1500]]);
        DB::table('operations')->insert(['id' => 1, 'name' => 'Appendectomy', 'k_surgeon' => 60, 'k_assistant' => 15, 'k_anaesthetist' => 20]);
        DB::table('ipd_patient_departments')->insert([['id' => 1, 'patient_id' => 7, 'price_list_id' => null], ['id' => 2, 'patient_id' => 8, 'price_list_id' => 2]]);
    }

    private function operation(array $data): IpdOperation
    {
        return IpdOperation::create($data + ['ref_no' => 'R' . random_int(1000, 9999), 'operation_id' => 1, 'operation_date' => '2026-11-24 08:00', 'doctor_id' => 3]);
    }

    public function test_fees_are_k_coefficients_times_the_value_of_k_of_the_patient_list(): void
    {
        $cash = $this->operation(['ipd_patient_department_id' => 1]);
        $this->assertSame([1000.0, 60000.0, 15000.0, 20000.0, 95000.0], [$cash->k_value, $cash->surgeon_fee, $cash->assistant_fee, $cash->anaesthetist_fee, $cash->total_fee]);

        $insured = $this->operation(['ipd_patient_department_id' => 2]);
        $this->assertSame(142500.0, $insured->total_fee, '95 K x 1 500');

        DB::table('price_lists')->where('id', 1)->update(['k_value' => 2000]);
        $cash->update(['ot_room' => 1]);
        $this->assertSame(95000.0, $cash->fresh()->total_fee, 'a later change of K does not reprice a saved operation');
    }

    public function test_room_and_surgeon_cannot_be_booked_twice(): void
    {
        $booked = $this->operation(['ipd_patient_department_id' => 1, 'ot_room' => 1, 'scheduled_start' => '2026-11-24 08:00', 'scheduled_end' => '2026-11-24 10:00']);

        $sameRoom = ['ot_room' => 1, 'doctor_id' => 9, 'scheduled_start' => '2026-11-24 09:30', 'scheduled_end' => '2026-11-24 11:00'];
        $this->assertSame('room', IpdOperation::clash($sameRoom)[1]);
        $this->assertNull(IpdOperation::clash(['scheduled_start' => '2026-11-24 10:00', 'scheduled_end' => '2026-11-24 11:00'] + $sameRoom), 'back to back is fine');
        $this->assertSame('surgeon', IpdOperation::clash(['ot_room' => 2, 'doctor_id' => 3, 'scheduled_start' => '2026-11-24 09:00', 'scheduled_end' => '2026-11-24 09:45'])[1]);
        $this->assertNull(IpdOperation::clash($sameRoom, $booked->id), 'editing the same operation');

        $booked->update(['status' => 5]); // cancelled frees the room and is not billed
        $this->assertNull(IpdOperation::clash($sameRoom));
        $this->assertFalse($booked->fresh()->isBillable());
    }
}
