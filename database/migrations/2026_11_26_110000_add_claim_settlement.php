<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Settlement of claim invoices: payments, rejected lines and what happens to them, ageing. */
    public function up(): void
    {
        Schema::create('claim_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('claim_invoice_id')->index();
            $table->date('paid_on');
            $table->decimal('amount', 15, 2);
            $table->unsignedInteger('payment_method')->nullable();
            $table->string('reference', 100)->nullable();
            $table->string('note', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
        Schema::table('claim_lines', function (Blueprint $table) {
            $table->date('settled_on')->nullable()->after('status');
        });
        $now = now();
        $reasons = ['not_covered' => 'Act not covered', 'no_voucher' => 'Voucher missing or invalid', 'out_of_period' => 'Policy not valid on that date',
            'ceiling' => 'Ceiling reached', 'duplicate' => 'Already paid', 'other' => 'Other'];
        $i = 0;
        foreach ($reasons as $key => $name) {
            $i++;
            DB::table('master_list_items')->insertOrIgnore(['type' => 'claim_rejection_reason', 'code' => $i, 'name' => $name,
                'lang_key' => 'messages.claim_settlement.lists.claim_rejection_reason.'.$key, 'sort_order' => $i, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }
        if (! DB::table('settings')->where('key', 'claim_ageing_buckets')->exists()) {
            DB::table('settings')->insert(['key' => 'claim_ageing_buckets', 'value' => '30,60,90', 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'claim_ageing_buckets')->delete();
        DB::table('master_list_items')->where('type', 'claim_rejection_reason')->delete();
        Schema::table('claim_lines', fn (Blueprint $table) => $table->dropColumn('settled_on'));
        Schema::dropIfExists('claim_payments');
    }
};
