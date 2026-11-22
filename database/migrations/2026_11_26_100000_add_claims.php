<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Claims: the payer's share of visits, exams, pharmacy bills and admissions, invoiced to the insurer. */
    public function up(): void
    {
        Schema::table('ipd_bills', function (Blueprint $table) {
            $table->decimal('payer_amount', 15, 2)->default(0);
            $table->decimal('reduction_amount', 15, 2)->default(0);
        });
        Schema::table('insurances', function (Blueprint $table) {
            $table->text('claim_note')->nullable();
        });
        Schema::create('claim_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number', 60)->unique();
            $table->unsignedInteger('insurance_id')->index();
            $table->string('billing_option', 20);
            $table->date('period_from');
            $table->date('period_to');
            $table->date('issued_on');
            $table->date('due_on')->nullable();
            $table->string('status', 20)->index();
            $table->decimal('total_claimed', 15, 2)->default(0);
            $table->string('note', 500)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();
        });
        Schema::create('claim_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('claim_invoice_id')->index();
            $table->string('source_type', 30);
            $table->unsignedBigInteger('source_id');
            $table->unsignedInteger('patient_id')->nullable();
            $table->unsignedBigInteger('patient_insurance_id')->nullable();
            $table->string('voucher_number', 50)->nullable();
            $table->date('service_date');
            $table->string('description', 255);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('patient_amount', 15, 2)->default(0);
            $table->decimal('payer_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('rejection_reason')->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamps();
            $table->index(['source_type', 'source_id']);
        });
        $now = now();
        foreach (['claim_invoice_format' => '{seq}/{mm}/{yy}', 'claim_invoice_digits' => '4'] as $key => $value) {
            if (! DB::table('settings')->where('key', $key)->exists()) {
                DB::table('settings')->insert(['key' => $key, 'value' => $value, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
        if (! Module::where('name', 'Claims')->exists()) {
            Module::create(['name' => 'Claims', 'is_active' => 1, 'route' => 'claims.index']);
        }
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
        Module::where('name', 'Claims')->delete();
        DB::table('settings')->whereIn('key', ['claim_invoice_format', 'claim_invoice_digits'])->delete();
        Schema::dropIfExists('claim_lines');
        Schema::dropIfExists('claim_invoices');
        Schema::table('insurances', fn (Blueprint $table) => $table->dropColumn('claim_note'));
        Schema::table('ipd_bills', fn (Blueprint $table) => $table->dropColumn(['payer_amount', 'reduction_amount']));
    }
};
