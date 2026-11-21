<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * IPD payments and advance payments also issue a receipt on the cashier's open session.
     * The admin decides whether these payments are refused without an open session.
     */
    public function up(): void
    {
        if (! DB::table('settings')->where('key', 'cashier_session_required')->exists()) {
            DB::table('settings')->insert(['key' => 'cashier_session_required', 'value' => '0', 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'cashier_session_required')->delete();
    }
};
