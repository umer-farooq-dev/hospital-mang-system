<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fund transfers ("Transfert de fonds") from a register's open session to another
     * register's open session, or to a destination set by the admin (safe, bank...).
     */
    public function up(): void
    {
        Schema::create('fund_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_session_id')->constrained('cash_sessions');
            $table->foreignId('from_counter_id')->constrained('cash_counters');
            $table->foreignId('to_session_id')->nullable()->constrained('cash_sessions');
            $table->foreignId('to_counter_id')->nullable()->constrained('cash_counters');
            $table->unsignedInteger('destination')->nullable(); // Master List transfer_destination, when not a register
            $table->unsignedInteger('payment_method');
            $table->decimal('amount', 15, 2);
            $table->foreignId('user_id')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $now = now();
        foreach ([[1, 'Safe', 'messages.fund_transfer.destinations.safe'], [2, 'Bank', 'messages.fund_transfer.destinations.bank']] as $i => [$code, $name, $key]) {
            DB::table('master_list_items')->insertOrIgnore([
                'type' => 'transfer_destination', 'code' => $code, 'name' => $name, 'lang_key' => $key,
                'sort_order' => $i + 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('master_list_items')->where('type', 'transfer_destination')->delete();
        Schema::dropIfExists('fund_transfers');
    }
};
