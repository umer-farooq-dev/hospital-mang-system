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
     * Price lists as in the old tariff: every act has a cash price and other prices
     * (insured, affiliate...), plus weekend/night surcharges and the doctor's share.
     * A list without an explicit price applies its % to the base price of the act.
     */
    public function up(): void
    {
        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('lang_key')->nullable();
            $table->decimal('adjustment_percent', 6, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('price_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('lang_key')->nullable();
            $table->string('days', 20)->nullable(); // ISO days 1-7, e.g. "6,7"; empty = every day
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('surcharge_percent', 6, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('item_prices', function (Blueprint $table) {
            $table->id();
            $table->string('priceable_type', 50);
            $table->unsignedBigInteger('priceable_id');
            $table->foreignId('price_list_id')->constrained('price_lists')->cascadeOnDelete();
            $table->decimal('price', 15, 2)->nullable();
            $table->decimal('doctor_share_percent', 5, 2)->nullable();
            $table->decimal('assistant_share_percent', 5, 2)->nullable();
            $table->timestamps();
            $table->unique(['priceable_type', 'priceable_id', 'price_list_id'], 'item_prices_item_list_unique');
        });

        Schema::table('insurances', function (Blueprint $table) {
            $table->foreignId('price_list_id')->nullable()->after('coverage_percent')->constrained('price_lists')->nullOnDelete();
            $table->string('tax_id', 50)->nullable()->after('credit_days');          // IFU
            $table->string('trade_register', 50)->nullable()->after('tax_id');       // RCCM
            $table->string('tax_regime', 100)->nullable()->after('trade_register');
            $table->string('tax_division', 100)->nullable()->after('tax_regime');
            $table->string('account_code', 30)->nullable()->after('tax_division');
        });

        // Starting values seen in the old tariff; the admin confirms or changes them.
        $now = now();
        $lists = [
            ['Cash', 'messages.price_list.seed.cash', 0, true],
            ['Insured', 'messages.price_list.seed.insured', 15, false],
            ['Affiliate', 'messages.price_list.seed.affiliate', -50, false],
        ];
        foreach ($lists as $i => [$name, $langKey, $percent, $default]) {
            DB::table('price_lists')->insert(['name' => $name, 'lang_key' => $langKey, 'adjustment_percent' => $percent,
                'is_default' => $default, 'is_active' => true, 'sort_order' => $i + 1, 'created_at' => $now, 'updated_at' => $now]);
        }
        DB::table('price_periods')->insert(['name' => 'Weekend', 'lang_key' => 'messages.price_list.seed.weekend', 'days' => '6,7',
            'surcharge_percent' => 50, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);

        // Existing payers use the insured prices, like the old program unless "cash prices" was ticked.
        DB::table('insurances')->update(['price_list_id' => DB::table('price_lists')->where('name', 'Insured')->value('id')]);

        if (! Module::where('name', 'Price Lists')->exists()) {
            Module::create(['name' => 'Price Lists', 'is_active' => 1, 'route' => 'price-lists.index']);
        }
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
        Module::where('name', 'Price Lists')->delete();
        Schema::table('insurances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('price_list_id');
            $table->dropColumn(['tax_id', 'trade_register', 'tax_regime', 'tax_division', 'account_code']);
        });
        Schema::dropIfExists('item_prices');
        Schema::dropIfExists('price_periods');
        Schema::dropIfExists('price_lists');
    }
};
