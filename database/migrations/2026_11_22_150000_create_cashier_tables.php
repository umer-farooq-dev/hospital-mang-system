<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The cashier, as in the old program: cash registers ("caisses") with an accounting
     * account per payment method, a register per user, sessions closed with a closing number
     * ("N° Arrêt"), and receipts with one running number.
     */
    public function up(): void
    {
        Schema::create('cash_counters', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->boolean('is_reception')->default(false);
            $table->string('account_cash', 30)->nullable();
            $table->string('account_cheque', 30)->nullable();
            $table->string('account_electronic', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('cash_counter_id')->nullable()->constrained('cash_counters')->nullOnDelete();
        });

        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_counter_id')->constrained('cash_counters');
            $table->foreignId('user_id')->constrained('users');
            $table->dateTime('opened_at');
            $table->decimal('opening_float', 15, 2)->default(0);
            $table->dateTime('closed_at')->nullable();
            $table->unsignedBigInteger('closing_number')->nullable()->unique(); // N° Arrêt
            $table->decimal('expected_cash', 15, 2)->nullable();
            $table->decimal('counted_cash', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['cash_counter_id', 'closed_at']);
        });

        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('receipt_number')->unique();
            $table->foreignId('cash_session_id')->constrained('cash_sessions');
            $table->foreignId('cash_counter_id')->constrained('cash_counters');
            $table->foreignId('user_id')->constrained('users');
            $table->unsignedInteger('patient_id');
            $table->unsignedInteger('payment_method');
            $table->decimal('amount', 15, 2);                 // paid on this receipt
            $table->decimal('amount_received', 15, 2)->nullable();
            $table->decimal('change_given', 15, 2)->default(0);
            $table->string('cheque_number', 50)->nullable();
            $table->string('cheque_drawer', 100)->nullable();
            $table->dateTime('voided_at')->nullable();
            $table->timestamps();
            $table->foreign('patient_id')->references('id')->on('patients');
            $table->index(['patient_id', 'created_at']);
        });

        Schema::create('receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->constrained('receipts')->cascadeOnDelete();
            $table->string('payable_type', 50);
            $table->unsignedBigInteger('payable_id');
            $table->string('description');
            $table->decimal('total', 15, 2);           // before reduction
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('payer_amount', 15, 2)->default(0);
            $table->decimal('amount', 15, 2);          // patient share paid on this receipt
            $table->timestamps();
            $table->index(['payable_type', 'payable_id']);
        });

        $now = now();
        $settings = [
            'receipt_start_number' => '1',
            'receipt_followup_days' => '14',
            'receipt_paper' => 'a5',
            'cash_payment_method' => '1', // the method counted in the drawer at closing (Espèces)
        ];
        foreach ($settings as $key => $value) {
            if (! DB::table('settings')->where('key', $key)->exists()) {
                DB::table('settings')->insert(['key' => $key, 'value' => $value, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        foreach ([['Cash Counters', 'cash-counters.index'], ['Cashier', 'cashier.index']] as [$name, $route]) {
            if (! Module::where('name', $name)->exists()) {
                Module::create(['name' => $name, 'is_active' => 1, 'route' => $route]);
            }
        }
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
        Module::whereIn('name', ['Cash Counters', 'Cashier'])->delete();
        DB::table('settings')->whereIn('key', ['receipt_start_number', 'receipt_followup_days', 'receipt_paper', 'cash_payment_method'])->delete();
        Schema::dropIfExists('receipt_lines');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('cash_sessions');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cash_counter_id');
        });
        Schema::dropIfExists('cash_counters');
    }
};
