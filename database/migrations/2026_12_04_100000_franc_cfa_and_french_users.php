<?php

use App\Models\CurrencySetting;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

/**
 * Burkina Faso: money in franc CFA (XOF) written "5 000 FCFA", and the staff screens in French.
 * Only InfyHMS defaults are changed; a hospital that already chose its currency, format or language keeps it.
 * Everything stays editable in Settings > General (currency list in Settings > Currencies).
 */
return new class extends Migration
{
    public function up(): void
    {
        CurrencySetting::firstOrCreate(['currency_code' => 'XOF'], ['currency_name' => 'Franc CFA (BCEAO)', 'currency_icon' => 'FCFA']);

        $currency = Setting::where('key', 'current_currency')->first();
        if ($currency && in_array(strtolower((string) $currency->value), ['inr', 'usd'], true)) {
            $currency->update(['value' => 'xof']);
            $format = ['currency_decimals' => ['2', '0'], 'currency_decimal_separator' => ['.', ','],
                'currency_thousands_separator' => [',', ' '], 'currency_symbol_position' => ['before', 'after']];
            foreach ($format as $key => [$infyDefault, $cfa]) {
                $row = Setting::where('key', $key)->first();
                if ($row && $row->value === $infyDefault) {
                    $row->update(['value' => $cfa]);
                }
            }
        }

        // the seeded accounts were in English; French is the system default since 2026_12_03
        if (Setting::where('key', 'default_lang')->value('value') === 'fr') {
            User::where('language', 'en')->update(['language' => 'fr']);
        }
    }

    public function down(): void
    {
    }
};
