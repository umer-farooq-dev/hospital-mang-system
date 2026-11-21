<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * How amounts are shown (Settings > General). The defaults keep the current display;
     * a hospital working in FCFA sets 0 decimals, a space between thousands and the symbol after.
     */
    public function up(): void
    {
        $defaults = [
            'currency_decimals' => '2',
            'currency_decimal_separator' => '.',
            'currency_thousands_separator' => ',',
            'currency_symbol_position' => 'before',
        ];
        foreach ($defaults as $key => $value) {
            if (! DB::table('settings')->where('key', $key)->exists()) {
                DB::table('settings')->insert(['key' => $key, 'value' => $value, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'currency_decimals', 'currency_decimal_separator', 'currency_thousands_separator', 'currency_symbol_position',
        ])->delete();
    }
};
