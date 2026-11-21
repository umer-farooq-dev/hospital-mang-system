<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Client answer (22 Nov 2026): the cashier selects the specific price (employee, weekend...),
     * and "prise en charge" is a reduction from an insurer, the workplace, or the favour given to
     * staff and their next of kin. So the weekend becomes a price list the cashier picks (the
     * automatic weekend period is switched off but kept), and staff become a payer type.
     */
    public function up(): void
    {
        $now = now();
        if (! DB::table('price_lists')->where('lang_key', 'messages.price_list.seed.weekend')->exists()) {
            DB::table('price_lists')->insert(['name' => 'Weekend', 'lang_key' => 'messages.price_list.seed.weekend', 'adjustment_percent' => 50,
                'is_default' => false, 'is_active' => true, 'sort_order' => (int) DB::table('price_lists')->max('sort_order') + 1,
                'created_at' => $now, 'updated_at' => $now]);
        }
        DB::table('price_periods')->where('lang_key', 'messages.price_list.seed.weekend')->update(['is_active' => false, 'updated_at' => $now]);

        DB::table('master_list_items')->insertOrIgnore([
            'type' => 'payer_type', 'code' => 4, 'name' => 'Staff and next of kin', 'lang_key' => 'messages.insurance.payer_types.staff',
            'sort_order' => 4, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('master_list_items')->where('type', 'payer_type')->where('code', 4)->whereNotExists(
            fn ($q) => $q->from('insurances')->where('payer_type', 4))->delete();
        DB::table('price_periods')->where('lang_key', 'messages.price_list.seed.weekend')->update(['is_active' => true]);
        DB::table('price_lists')->where('lang_key', 'messages.price_list.seed.weekend')->delete();
    }
};
