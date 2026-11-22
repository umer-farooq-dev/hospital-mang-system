<?php

use App\Models\IpdPatientDepartment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** One admission flow: the package and guardian move from Patient Admissions to IPD Patients. */
    public function up(): void
    {
        Schema::table('ipd_patient_departments', function (Blueprint $table) {
            $table->unsignedBigInteger('package_id')->nullable()->after('voucher_number');
            $table->string('guardian_name', 191)->nullable()->after('package_id');
            $table->unsignedInteger('guardian_relation')->nullable()->after('guardian_name');
            $table->string('guardian_contact', 50)->nullable()->after('guardian_relation');
            $table->string('guardian_address', 500)->nullable()->after('guardian_contact');
        });
        // A pharmacy bill goes on one admission bill only (null = not billed yet).
        Schema::table('medicine_bills', function (Blueprint $table) {
            $table->unsignedBigInteger('ipd_patient_department_id')->nullable()->after('patient_id')->index();
        });

        $now = now();
        $relations = [[1, 'Father', 'father'], [2, 'Mother', 'mother'], [3, 'Spouse', 'spouse'], [4, 'Child', 'child'],
            [5, 'Brother or sister', 'sibling'], [6, 'Other relative', 'other_relative'], [7, 'Friend', 'friend']];
        foreach ($relations as $i => [$code, $name, $key]) {
            DB::table('master_list_items')->insertOrIgnore([
                'type' => 'guardian_relation', 'code' => $code, 'name' => $name, 'lang_key' => 'messages.ipd_patient.relations.' . $key,
                'sort_order' => $i + 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // Old admissions become IPD admissions (insurance -> patient policy; free-text details kept in the notes).
        foreach (DB::table('patient_admissions')->orderBy('id')->get() as $a) {
            $exists = DB::table('ipd_patient_departments')->where('patient_id', $a->patient_id)
                ->whereDate('admission_date', substr($a->admission_date, 0, 10))->exists();
            if (! $a->bed_id || $exists) {
                continue;
            }
            $policyId = null;
            if ($a->insurance_id) {
                $policyId = DB::table('patient_insurances')->where('patient_id', $a->patient_id)->where('insurance_id', $a->insurance_id)->value('id')
                    ?? DB::table('patient_insurances')->insertGetId(['patient_id' => $a->patient_id, 'insurance_id' => $a->insurance_id,
                        'policy_number' => $a->policy_no, 'priority' => 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
            }
            $notes = array_filter([
                'Patient admission ' . $a->patient_admission_id,
                $a->agent_name ? 'Agent: ' . $a->agent_name : null,
                $a->guardian_relation ? 'Guardian relation: ' . $a->guardian_relation : null,
            ]);
            DB::table('ipd_patient_departments')->insert([
                'patient_id' => $a->patient_id, 'ipd_number' => IpdPatientDepartment::generateUniqueIpdNumber(),
                'admission_date' => $a->admission_date, 'doctor_id' => $a->doctor_id, 'bed_id' => $a->bed_id,
                'bed_type_id' => DB::table('beds')->where('id', $a->bed_id)->value('bed_type'),
                'patient_insurance_id' => $policyId, 'package_id' => $a->package_id,
                'guardian_name' => $a->guardian_name, 'guardian_contact' => $a->guardian_contact, 'guardian_address' => $a->guardian_address,
                'notes' => implode("\n", $notes), 'discharge' => $a->discharge_date ? 1 : 0, 'bill_status' => 0,
                'created_at' => $a->created_at ?? $now, 'updated_at' => $now,
            ]);
        }

        // The old screen stays available to the admin through Settings > Modules.
        DB::table('modules')->where('route', 'patient-admissions.index')->update(['is_active' => 0]);
    }

    public function down(): void
    {
        DB::table('modules')->where('route', 'patient-admissions.index')->update(['is_active' => 1]);
        DB::table('master_list_items')->where('type', 'guardian_relation')->delete();
        Schema::table('medicine_bills', fn (Blueprint $table) => $table->dropColumn('ipd_patient_department_id'));
        Schema::table('ipd_patient_departments', fn (Blueprint $table) => $table->dropColumn(['package_id', 'guardian_name', 'guardian_relation', 'guardian_contact', 'guardian_address']));
    }
};
