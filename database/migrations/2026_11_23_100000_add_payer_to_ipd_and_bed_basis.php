<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 3, IPD bill: the payer and price list of the admission, exam requests made during
     * the stay belong to its bill, and the bed is charged per day, night or hour (setting).
     */
    public function up(): void
    {
        Schema::table('ipd_patient_departments', function (Blueprint $table) {
            $table->foreignId('patient_insurance_id')->nullable()->after('bed_id')->constrained('patient_insurances')->nullOnDelete();
            $table->foreignId('price_list_id')->nullable()->after('patient_insurance_id')->constrained('price_lists')->nullOnDelete();
            $table->string('voucher_number', 50)->nullable()->after('price_list_id');
        });
        Schema::table('exam_orders', function (Blueprint $table) {
            $table->unsignedInteger('ipd_patient_department_id')->nullable()->after('patient_id');
            $table->index('ipd_patient_department_id');
        });
        if (! DB::table('settings')->where('key', 'bed_charge_basis')->exists()) {
            DB::table('settings')->insert(['key' => 'bed_charge_basis', 'value' => 'day', 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'bed_charge_basis')->delete();
        Schema::table('exam_orders', function (Blueprint $table) {
            $table->dropIndex(['ipd_patient_department_id']);
            $table->dropColumn('ipd_patient_department_id');
        });
        Schema::table('ipd_patient_departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('patient_insurance_id');
            $table->dropConstrainedForeignId('price_list_id');
            $table->dropColumn('voucher_number');
        });
    }
};
