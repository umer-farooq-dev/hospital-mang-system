<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exam requests from reception (lab and radiology), priced from the price list and
     * split between payer and patient, then paid at the cashier like a consultation.
     * Results and the lab worklist come with Phase 4.
     */
    public function up(): void
    {
        Schema::create('exam_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('patient_id');
            $table->unsignedBigInteger('doctor_id')->nullable();          // prescriber
            $table->foreignId('patient_insurance_id')->nullable()->constrained('patient_insurances')->nullOnDelete();
            $table->foreignId('price_list_id')->nullable()->constrained('price_lists')->nullOnDelete();
            $table->string('voucher_number', 50)->nullable();
            $table->dateTime('ordered_at');
            $table->foreignId('user_id')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('doctor_id')->references('id')->on('doctors')->nullOnDelete();
        });

        Schema::create('exam_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_order_id')->constrained('exam_orders')->cascadeOnDelete();
            $table->string('testable_type', 30);   // pathology_test | radiology_test
            $table->unsignedBigInteger('testable_id');
            $table->string('description');
            $table->decimal('total', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('payer_amount', 15, 2)->default(0);
            $table->decimal('patient_amount', 15, 2);
            $table->timestamps();
        });

        if (! Module::where('name', 'Exam Requests')->exists()) {
            Module::create(['name' => 'Exam Requests', 'is_active' => 1, 'route' => 'exam-orders.index']);
        }
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
        Module::where('name', 'Exam Requests')->delete();
        Schema::dropIfExists('exam_order_items');
        Schema::dropIfExists('exam_orders');
    }
};
