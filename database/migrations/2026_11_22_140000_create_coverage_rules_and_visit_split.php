<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The split engine: what each payer covers per department or charge category, the
     * department discount, and the payer/patient shares stored on every OPD visit.
     */
    public function up(): void
    {
        Schema::table('doctor_departments', function (Blueprint $table) {
            $table->decimal('discount_percent', 5, 2)->nullable()->after('description');
        });

        Schema::create('coverage_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('insurance_id');
            $table->unsignedBigInteger('doctor_department_id')->nullable(); // empty = every other department
            $table->unsignedInteger('charge_category_id')->nullable();       // empty = any category
            $table->decimal('coverage_percent', 5, 2)->nullable();           // empty = the policy's or payer's %
            $table->boolean('not_covered')->default(false);
            $table->boolean('needs_approval')->default(false);
            $table->decimal('max_amount', 15, 2)->nullable();
            $table->timestamps();

            $table->foreign('insurance_id')->references('id')->on('insurances')->cascadeOnDelete();
            $table->foreign('doctor_department_id')->references('id')->on('doctor_departments')->cascadeOnDelete();
            $table->foreign('charge_category_id')->references('id')->on('charge_categories')->cascadeOnDelete();
        });

        Schema::table('opd_patient_departments', function (Blueprint $table) {
            $table->foreignId('price_list_id')->nullable()->after('standard_charge')->constrained('price_lists')->nullOnDelete();
            $table->foreignId('patient_insurance_id')->nullable()->after('price_list_id')->constrained('patient_insurances')->nullOnDelete();
            $table->string('voucher_number', 50)->nullable()->after('patient_insurance_id');
            $table->decimal('discount_percent', 5, 2)->default(0)->after('voucher_number');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('discount_percent');
            $table->decimal('payer_amount', 15, 2)->default(0)->after('discount_amount');
            $table->decimal('patient_amount', 15, 2)->nullable()->after('payer_amount'); // null on visits made before the split
        });
    }

    public function down(): void
    {
        Schema::table('opd_patient_departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('price_list_id');
            $table->dropConstrainedForeignId('patient_insurance_id');
            $table->dropColumn(['voucher_number', 'discount_percent', 'discount_amount', 'payer_amount', 'patient_amount']);
        });
        Schema::dropIfExists('coverage_rules');
        Schema::table('doctor_departments', function (Blueprint $table) {
            $table->dropColumn('discount_percent');
        });
    }
};
