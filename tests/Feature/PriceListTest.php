<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\ItemPrice;
use App\Models\PriceList;
use App\Models\PricePeriod;
use App\Repositories\PriceListRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PriceListTest extends TestCase
{
    private Charge $consultation;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('lang_key')->nullable();
            $table->decimal('adjustment_percent', 6, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('price_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('lang_key')->nullable();
            $table->string('days')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('surcharge_percent', 6, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('item_prices', function (Blueprint $table) {
            $table->id();
            $table->string('priceable_type');
            $table->unsignedBigInteger('priceable_id');
            $table->unsignedBigInteger('price_list_id');
            $table->decimal('price', 15, 2)->nullable();
            $table->decimal('doctor_share_percent', 5, 2)->nullable();
            $table->decimal('assistant_share_percent', 5, 2)->nullable();
            $table->timestamps();
        });
        Schema::create('insurances', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('price_list_id')->nullable();
        });
        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->integer('charge_type')->default(1);
            $table->unsignedBigInteger('charge_category_id')->default(1);
            $table->string('code');
            $table->double('standard_charge');
            $table->timestamps();
        });

        Schema::create('users', fn (Blueprint $t) => $t->id());
        (require base_path('database/migrations/2026_11_20_120000_create_audit_logs_table.php'))->up();

        // The old tariff: insured = cash + 15%, affiliate = half, weekend = +50%.
        DB::table('price_lists')->insert([
            ['name' => 'Cash', 'lang_key' => 'messages.price_list.seed.cash', 'adjustment_percent' => 0, 'is_default' => true, 'sort_order' => 1],
            ['name' => 'Insured', 'lang_key' => 'messages.price_list.seed.insured', 'adjustment_percent' => 15, 'is_default' => false, 'sort_order' => 2],
            ['name' => 'Affiliate', 'lang_key' => null, 'adjustment_percent' => -50, 'is_default' => false, 'sort_order' => 3],
        ]);
        DB::table('price_periods')->insert(['name' => 'Weekend', 'days' => '6,7', 'surcharge_percent' => 50, 'is_active' => true]);
        $this->consultation = Charge::create(['code' => 'Consultation spécialisée', 'standard_charge' => 4000, 'charge_type' => 1, 'charge_category_id' => 1]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function list(string $name): PriceList
    {
        return PriceList::where('name', $name)->firstOrFail();
    }

    public function test_list_percent_reproduces_the_old_tariff(): void
    {
        $tuesday = Carbon::parse('2026-11-24 10:00');
        $saturday = Carbon::parse('2026-11-21 10:00');

        $this->assertSame(4000.0, $this->list('Cash')->priceFor($this->consultation, $tuesday));
        $this->assertSame(4600.0, $this->list('Insured')->priceFor($this->consultation, $tuesday));
        $this->assertSame(2000.0, $this->list('Affiliate')->priceFor($this->consultation, $tuesday));
        $this->assertSame(6000.0, $this->list('Cash')->priceFor($this->consultation, $saturday), 'weekend +50%');
    }

    public function test_an_own_price_wins_over_the_list_percent(): void
    {
        ItemPrice::create(['priceable_type' => 'charge', 'priceable_id' => $this->consultation->id, 'price_list_id' => $this->list('Affiliate')->id, 'price' => 2500]);

        $this->assertSame(2500.0, $this->list('Affiliate')->priceFor($this->consultation, '2026-11-24 10:00'));
    }

    public function test_night_period_crosses_midnight_and_the_highest_surcharge_applies(): void
    {
        PricePeriod::create(['name' => 'Night', 'start_time' => '20:00', 'end_time' => '07:00', 'surcharge_percent' => 25, 'is_active' => true]);

        $this->assertSame(25.0, PricePeriod::surchargeAt('2026-11-24 23:30'));
        $this->assertSame(25.0, PricePeriod::surchargeAt('2026-11-25 06:59'));
        $this->assertSame(0.0, PricePeriod::surchargeAt('2026-11-25 07:00'));
        $this->assertSame(50.0, PricePeriod::surchargeAt('2026-11-21 23:30'), 'Saturday night: weekend 50% beats night 25%');
    }

    public function test_tariff_grid_saves_prices_and_shares_and_keeps_hidden_lists(): void
    {
        $insured = $this->list('Insured');
        $affiliate = $this->list('Affiliate');
        ItemPrice::create(['priceable_type' => 'charge', 'priceable_id' => $this->consultation->id, 'price_list_id' => $affiliate->id, 'price' => 2500]);
        $affiliate->update(['is_active' => false]); // not on the screen any more

        app(PriceListRepository::class)->saveTariff('charge', [
            $this->consultation->id => [$insured->id => '4800', 'doctor' => '50', 'assistant' => ''],
            999 => [$insured->id => '1'], // unknown item: ignored
        ]);

        $this->assertSame(4800.0, $insured->priceFor($this->consultation, '2026-11-24 10:00'));
        $this->assertSame(2500.0, ItemPrice::for($this->consultation, $affiliate->id)->price, 'inactive list keeps its price');
        $this->assertSame(50.0, ItemPrice::doctorShare($this->consultation, $insured), 'falls back to the default list share');
        $this->assertSame(3, ItemPrice::count(), 'affiliate price, insured price, default row with the shares');

        app(PriceListRepository::class)->saveTariff('charge', [$this->consultation->id => [$insured->id => '', 'doctor' => '']]);
        $this->assertNull(ItemPrice::for($this->consultation, $insured->id), 'emptied row removed');
        $this->assertSame(4600.0, $insured->priceFor($this->consultation, '2026-11-24 10:00'));
    }

    public function test_lists_keep_their_translation_unless_renamed_and_one_default_only(): void
    {
        app()->setLocale('fr');
        $cash = $this->list('Cash');
        $insured = $this->list('Insured');
        $rows = [
            $cash->id => ['name' => 'Comptant', 'adjustment_percent' => 0, 'is_active' => 1],
            $insured->id => ['name' => 'Assurés', 'adjustment_percent' => 15, 'is_active' => 1],
        ];
        app(PriceListRepository::class)->saveLists($rows, ['name' => 'Employé', 'adjustment_percent' => -20], $insured->id);

        $this->assertSame('messages.price_list.seed.cash', $cash->fresh()->lang_key, 'same translated text keeps the translation');
        $this->assertSame(['Assurés', null], [$insured->fresh()->name, $insured->fresh()->lang_key]);
        $this->assertSame([$insured->id], PriceList::where('is_default', true)->pluck('id')->all());
        $this->assertSame(-20.0, PriceList::where('name', 'Employé')->first()->adjustment_percent);

        $this->expectException(ValidationException::class);
        app(PriceListRepository::class)->saveLists([$insured->id => ['delete' => 1]], null, null);
    }
}
