<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** ICD-10 (CIM-10) codes imported by the admin; diagnoses carry a code and a status (hypothesis, confirmed...). */
    public function up(): void
    {
        Schema::create('icd10_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('title', 255);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('title');
        });
        foreach (['ipd_diagnoses', 'opd_diagnoses'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('icd10_code', 10)->nullable()->after('report_type')->index();
                $table->unsignedInteger('diagnosis_status')->nullable()->after('icd10_code');
            });
        }
        $now = now();
        foreach ([[1, 'Hypothesis', 'hypothesis'], [2, 'Confirmed', 'confirmed'], [3, 'Ruled out', 'ruled_out']] as $i => [$code, $name, $key]) {
            DB::table('master_list_items')->insertOrIgnore([
                'type' => 'diagnosis_status', 'code' => $code, 'name' => $name, 'lang_key' => 'messages.icd10.lists.diagnosis_status.' . $key,
                'sort_order' => $i + 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        if (! Module::where('name', 'ICD-10')->exists()) {
            Module::create(['name' => 'ICD-10', 'is_active' => 1, 'route' => 'icd10.index']);
        }
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
        Module::where('name', 'ICD-10')->delete();
        DB::table('master_list_items')->where('type', 'diagnosis_status')->delete();
        foreach (['ipd_diagnoses', 'opd_diagnoses'] as $tableName) {
            Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn(['icd10_code', 'diagnosis_status']));
        }
        Schema::dropIfExists('icd10_codes');
    }
};
