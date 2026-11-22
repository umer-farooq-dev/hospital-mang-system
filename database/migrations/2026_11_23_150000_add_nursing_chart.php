<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Nursing chart: vital signs, notes per shift, intake and output, and the medication administration record. */
    public function up(): void
    {
        // A number behind some list items: doses per day for dose intervals, days for dose durations.
        Schema::table('master_list_items', function (Blueprint $table) {
            $table->decimal('quantity', 10, 2)->nullable()->after('name');
        });
        DB::table('master_list_items')->whereIn('type', ['dose_interval', 'dose_duration'])->update(['quantity' => DB::raw('code')]);

        Schema::create('vital_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('lang_key')->nullable();
            $table->string('unit', 30)->nullable();
            $table->decimal('normal_min', 10, 2)->nullable();
            $table->decimal('normal_max', 10, 2)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('vital_readings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ipd_patient_department_id')->index();
            $table->unsignedBigInteger('vital_type_id');
            $table->decimal('value', 10, 2);
            $table->dateTime('measured_at');
            $table->string('note', 500)->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();
        });
        Schema::create('nursing_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ipd_patient_department_id')->index();
            $table->unsignedInteger('shift')->nullable();
            $table->dateTime('noted_at');
            $table->text('note');
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();
        });
        Schema::create('intake_outputs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ipd_patient_department_id')->index();
            $table->dateTime('recorded_at');
            $table->unsignedInteger('intake_type')->nullable();
            $table->unsignedInteger('output_type')->nullable();
            $table->decimal('volume_ml', 10, 2);
            $table->string('note', 500)->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();
        });
        Schema::create('medication_administrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ipd_patient_department_id')->index();
            $table->unsignedBigInteger('ipd_prescription_item_id');
            $table->dateTime('scheduled_at');
            $table->unsignedInteger('status')->nullable();
            $table->dateTime('administered_at')->nullable();
            $table->string('note', 500)->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();
            $table->unique(['ipd_prescription_item_id', 'scheduled_at'], 'mar_item_time_unique');
        });

        $now = now();
        $vitals = [
            ['Temperature', 'temperature', '°C', 36.1, 37.5], ['Pulse', 'pulse', 'bpm', 60, 100],
            ['Systolic blood pressure', 'systolic', 'mmHg', 90, 140], ['Diastolic blood pressure', 'diastolic', 'mmHg', 60, 90],
            ['Respiratory rate', 'respiratory_rate', '/min', 12, 20], ['Oxygen saturation', 'spo2', '%', 95, 100],
            ['Blood glucose', 'glucose', 'g/L', 0.7, 1.1], ['Weight', 'weight', 'kg', null, null], ['Pain score', 'pain', '/10', 0, 3],
        ];
        foreach ($vitals as $i => [$name, $key, $unit, $min, $max]) {
            DB::table('vital_types')->insert(['name' => $name, 'lang_key' => 'messages.nursing.vitals.' . $key, 'unit' => $unit,
                'normal_min' => $min, 'normal_max' => $max, 'sort_order' => $i + 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }
        $lists = [
            'nursing_shift' => [[1, 'Morning', 'morning'], [2, 'Afternoon', 'afternoon'], [3, 'Night', 'night']],
            'intake_type' => [[1, 'Oral', 'oral'], [2, 'IV infusion', 'iv'], [3, 'Blood transfusion', 'blood'], [4, 'Tube feeding', 'tube']],
            'output_type' => [[1, 'Urine', 'urine'], [2, 'Vomit', 'vomit'], [3, 'Drain', 'drain'], [4, 'Stool', 'stool'], [5, 'Gastric tube', 'gastric']],
            'mar_status' => [[1, 'Given', 'given'], [2, 'Held', 'held'], [3, 'Refused', 'refused']],
        ];
        foreach ($lists as $type => $items) {
            foreach ($items as $i => [$code, $name, $key]) {
                DB::table('master_list_items')->insertOrIgnore([
                    'type' => $type, 'code' => $code, 'name' => $name, 'lang_key' => 'messages.nursing.lists.' . $type . '.' . $key,
                    'sort_order' => $i + 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
        if (! DB::table('settings')->where('key', 'mar_dose_times')->exists()) {
            DB::table('settings')->insert(['key' => 'mar_dose_times', 'created_at' => $now, 'updated_at' => $now,
                'value' => json_encode(['1' => '08:00', '2' => '08:00,20:00', '3' => '08:00,14:00,20:00', '4' => '06:00,12:00,18:00,00:00'])]);
        }
        if (! Module::where('name', 'Nursing Chart')->exists()) {
            Module::create(['name' => 'Nursing Chart', 'is_active' => 1, 'route' => 'ipd.nursing.show']);
        }
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
        Module::where('name', 'Nursing Chart')->delete();
        DB::table('settings')->where('key', 'mar_dose_times')->delete();
        DB::table('master_list_items')->whereIn('type', ['nursing_shift', 'intake_type', 'output_type', 'mar_status'])->delete();
        foreach (['medication_administrations', 'intake_outputs', 'nursing_notes', 'vital_readings', 'vital_types'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('master_list_items', fn (Blueprint $table) => $table->dropColumn('quantity'));
    }
};
