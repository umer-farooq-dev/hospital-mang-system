<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** An advance stays unused (null) until an admission bill takes it, so an advance paid before admission is not lost. */
    public function up(): void
    {
        Schema::table('advanced_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('ipd_patient_department_id')->nullable()->after('patient_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('advanced_payments', function (Blueprint $table) {
            $table->dropColumn('ipd_patient_department_id');
        });
    }
};
