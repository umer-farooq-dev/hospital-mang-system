<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Payment methods offered at the counter become a Master List. Cash (1) and Cheque (2)
     * keep the codes already stored in OPD visits, IPD payments and appointments; methods the
     * admin adds (mobile money, transfer, card...) get codes from 100.
     */
    public function up(): void
    {
        $now = now();
        foreach ([[1, 'Cash', 'messages.cash'], [2, 'Cheque', 'messages.cheque']] as $i => [$code, $name, $langKey]) {
            DB::table('master_list_items')->insertOrIgnore([
                'type' => 'payment_method', 'code' => $code, 'name' => $name, 'lang_key' => $langKey,
                'sort_order' => $i + 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('master_list_items')->where('type', 'payment_method')->delete();
    }
};
