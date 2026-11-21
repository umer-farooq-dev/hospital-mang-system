<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Amount display follows Settings > General. The helpers keep their format in a static
 * cache for the request, so each scenario runs in its own process.
 *
 * @runTestsInSeparateProcesses
 *
 * @preserveGlobalState disabled
 */
class MoneyFormatTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('value')->nullable();
            $table->timestamps();
        });
        Schema::create('currency_settings', function (Blueprint $table) {
            $table->id();
            $table->string('currency_name');
            $table->string('currency_code');
            $table->string('currency_icon');
            $table->timestamps();
        });
        Schema::create('users', fn (Blueprint $t) => $t->id());
        (require base_path('database/migrations/2026_11_20_120000_create_audit_logs_table.php'))->up();
        DB::table('currency_settings')->insert([
            ['currency_name' => 'CFA franc', 'currency_code' => 'xof', 'currency_icon' => 'FCFA'],
            ['currency_name' => 'Euro', 'currency_code' => 'eur', 'currency_icon' => '€'],
        ]);
        DB::table('settings')->insert(['key' => 'current_currency', 'value' => 'xof']);
    }

    private function settings(array $values): void
    {
        foreach ($values as $key => $value) {
            DB::table('settings')->insert(['key' => $key, 'value' => $value]);
        }
    }

    public function test_defaults_keep_the_original_display(): void
    {
        (require base_path('database/migrations/2026_11_21_200000_add_currency_format_settings.php'))->up();

        $this->assertSame('1,234,567.89', formatAmount(1234567.891));
        $this->assertSame('FCFA 6,000.00', getCurrencyFormat(6000));
    }

    public function test_fcfa_format_without_decimals_with_a_space_and_the_symbol_after(): void
    {
        $this->settings(['currency_decimals' => '0', 'currency_decimal_separator' => ',', 'currency_thousands_separator' => ' ', 'currency_symbol_position' => 'after']);

        $this->assertSame('6 000', formatAmount(6000));
        $this->assertSame('6 000 FCFA', getCurrencyFormat(6000));
        // XOF has no cents: the amount is not multiplied by 100.
        $this->assertSame('6 000 FCFA', moneyFormat(6000, 'XOF'));
        $this->assertSame('1 500 €', checkNumberFormat(1499.6, 'eur'));
    }

    public function test_each_currency_code_gets_its_own_symbol(): void
    {
        $this->assertSame('FCFA', getSymbols('xof'));
        $this->assertSame('€', getSymbols('eur'));
        $this->assertNull(getSymbols('usd'));
    }

    public function test_no_thousands_separator_is_kept_when_saved_empty(): void
    {
        $this->settings(['currency_decimals' => '2', 'currency_thousands_separator' => null]);

        $this->assertSame('1234567.89', formatAmount(1234567.891));
    }
}
