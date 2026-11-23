<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** What patients and insurers owed on the first day: collected at the cashier, settled in third-party billing. */
    public function up(): void
    {
        Schema::create('patient_opening_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('patient_id')->index();
            $table->decimal('amount', 15, 2);
            $table->string('label', 255);
            $table->date('as_of');
            $table->unsignedBigInteger('data_import_run_id')->nullable();
            $table->timestamps();
        });
        Schema::table('claim_invoices', function (Blueprint $table) {
            $table->boolean('is_opening')->default(false)->after('status'); // brought from before go-live: already in the opening balances
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_opening_balances');
        Schema::table('claim_invoices', function (Blueprint $table) {
            $table->dropColumn('is_opening');
        });
    }
};
