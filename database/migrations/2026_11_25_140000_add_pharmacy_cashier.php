<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Pharmacy bills are paid at the cashier, with the insurer's share split off like visits and exams. */
    public function up(): void
    {
        Schema::table('medicine_bills', function (Blueprint $table) {
            $table->unsignedBigInteger('patient_insurance_id')->nullable()->after('patient_id');
            $table->decimal('payer_amount', 15, 2)->default(0)->after('net_amount');
            $table->decimal('patient_amount', 15, 2)->nullable()->after('payer_amount');
        });
        if (! DB::table('settings')->where('key', 'pharmacy_charge_category')->exists()) {
            DB::table('settings')->insert(['key' => 'pharmacy_charge_category', 'value' => '', 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'pharmacy_charge_category')->delete();
        Schema::table('medicine_bills', fn (Blueprint $table) => $table->dropColumn(['patient_insurance_id', 'payer_amount', 'patient_amount']));
    }
};
