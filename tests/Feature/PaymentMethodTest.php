<?php

namespace Tests\Feature;

use App\Models\IpdPayment;
use App\Models\MasterListItem;
use App\Models\PaymentMethod;
use App\Repositories\MasterListRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('master_list_items', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->unsignedInteger('code');
            $table->string('name');
            $table->string('lang_key')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        foreach (['opd_patient_departments' => 'payment_mode', 'ipd_payments' => 'payment_mode', 'appointments' => 'payment_type', 'appointment_transactions' => 'payment_type'] as $table => $column) {
            Schema::create($table, function (Blueprint $t) use ($column) {
                $t->id();
                $t->integer($column)->nullable();
            });
        }
        Schema::create('users', fn (Blueprint $t) => $t->id());
        (require base_path('database/migrations/2026_11_20_120000_create_audit_logs_table.php'))->up();
        (require base_path('database/migrations/2026_11_21_190000_add_payment_methods_list.php'))->up();
        MasterListItem::flushCache();
    }

    public function test_methods_added_by_the_admin_never_take_a_gateway_code(): void
    {
        $item = app(MasterListRepository::class)->store(['type' => 'payment_method', 'name' => 'Orange Money', 'is_active' => 1]);
        $this->assertSame(100, $item->code);

        $item = app(MasterListRepository::class)->store(['type' => 'payment_method', 'name' => 'Moov Money', 'is_active' => 1]);
        $this->assertSame(101, $item->code);
        $this->assertSame(4, $item->sort_order, 'new items go to the end of the list');
    }

    public function test_counter_methods_and_gateways_are_told_apart(): void
    {
        app()->setLocale('fr');
        $this->assertSame([1 => 'Espèces', 2 => 'Chèque', 3 => 'Stripe'], PaymentMethod::options([3 => 'Stripe']));
        $this->assertSame('Razorpay', PaymentMethod::label(4, IpdPayment::PAYMENT_MODES));
        $this->assertSame('Espèces', PaymentMethod::label(1, IpdPayment::PAYMENT_MODES));
        $this->assertTrue(PaymentMethod::isOffline(2));
        $this->assertFalse(PaymentMethod::isOffline(3));
        $this->assertFalse(PaymentMethod::isOffline(null));
    }

    public function test_a_deactivated_method_stays_on_old_records_and_cannot_be_deleted_while_used(): void
    {
        DB::table('master_list_items')->where('type', 'payment_method')->where('code', 2)->update(['is_active' => false]);
        MasterListItem::flushCache();
        $this->assertArrayNotHasKey(2, PaymentMethod::options());
        $this->assertArrayHasKey(2, PaymentMethod::options([], 2));
        $this->assertSame('Cheque', PaymentMethod::label(2));

        DB::table('ipd_payments')->insert(['payment_mode' => 1]);
        $this->expectException(UnprocessableEntityHttpException::class);
        app(MasterListRepository::class)->delete(MasterListItem::where('type', 'payment_method')->where('code', 1)->value('id'));
    }
}
