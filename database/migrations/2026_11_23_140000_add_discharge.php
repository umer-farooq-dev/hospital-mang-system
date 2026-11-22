<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Discharge: outcome, checklist, summary written from the admin's template, and the blocking rules. */
    public function up(): void
    {
        // Settings hold texts longer than 191 characters (templates, receipt footer).
        Schema::table('settings', function (Blueprint $table) {
            $table->text('value')->nullable()->change();
        });
        Schema::table('ipd_patient_departments', function (Blueprint $table) {
            $table->dateTime('discharge_date')->nullable()->after('discharge');
            $table->unsignedInteger('discharge_outcome')->nullable()->after('discharge_date');
            $table->text('discharge_checks')->nullable()->after('discharge_outcome');
            $table->longText('discharge_summary')->nullable()->after('discharge_checks');
            $table->unsignedBigInteger('discharged_by')->nullable()->after('discharge_summary');
        });

        $now = now();
        $lists = [
            'discharge_check' => [[1, 'Discharge summary given to the patient', 'summary'], [2, 'Treatment at home explained', 'treatment'],
                [3, 'Follow-up appointment given', 'follow_up'], [4, 'Belongings and documents returned', 'belongings'], [5, 'Bill settled', 'bill']],
            'discharge_outcome' => [[1, 'Cured', 'cured'], [2, 'Improved', 'improved'], [3, 'Transferred', 'transferred'],
                [4, 'Left against medical advice', 'against_advice'], [5, 'Deceased', 'deceased']],
        ];
        foreach ($lists as $type => $items) {
            foreach ($items as $i => [$code, $name, $key]) {
                DB::table('master_list_items')->insertOrIgnore([
                    'type' => $type, 'code' => $code, 'name' => $name, 'lang_key' => 'messages.discharge.lists.' . $type . '.' . $key,
                    'sort_order' => $i + 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
        $settings = [
            'discharge_block_unpaid' => '0',
            'discharge_require_checklist' => '0',
            'discharge_summary_template' => "Patient: {patient_name} ({patient_id}), {age}, {gender}\nAdmitted on {admission_date} under {doctor} ({department}), IPD no. {ipd_number}.\nDischarged on {discharge_date} after {stay_days} day(s). Condition at discharge: {outcome}.\n\nDiagnosis:\n{diagnosis}\n\nOperations:\n{operations}\n\nTreatment during the stay:\n\nTreatment at home:\n\nFollow-up:\n",
        ];
        foreach ($settings as $key => $value) {
            if (! DB::table('settings')->where('key', $key)->exists()) {
                DB::table('settings')->insert(['key' => $key, 'value' => $value, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', ['discharge_block_unpaid', 'discharge_require_checklist', 'discharge_summary_template'])->delete();
        DB::table('master_list_items')->whereIn('type', ['discharge_check', 'discharge_outcome'])->delete();
        Schema::table('ipd_patient_departments', fn (Blueprint $table) => $table->dropColumn(['discharge_date', 'discharge_outcome', 'discharge_checks', 'discharge_summary', 'discharged_by']));
    }
};
