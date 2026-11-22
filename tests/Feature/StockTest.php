<?php

namespace Tests\Feature;

use App\Models\Medicine;
use App\Models\MedicineBill;
use App\Models\PurchasedMedicine;
use App\Models\PurchaseMedicine;
use App\Models\SaleMedicine;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Utils\Stock;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Tests\TestCase;

class StockTest extends TestCase
{
    private int $pharmacy;

    private int $ward;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('key');
            $t->text('value')->nullable();
            $t->timestamps();
        });
        Schema::create('stores', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('code')->nullable();
            $t->unsignedInteger('store_type')->nullable();
            $t->boolean('can_sell')->default(false);
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });
        Schema::create('stock_batches', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('store_id');
            $t->string('stockable_type');
            $t->unsignedBigInteger('stockable_id');
            $t->string('batch_no')->nullable();
            $t->date('expiry_date')->nullable();
            $t->decimal('unit_cost', 14, 2)->default(0);
            $t->integer('quantity')->default(0);
            $t->unsignedBigInteger('supplier_id')->nullable();
            $t->timestamps();
        });
        Schema::create('stock_movements', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('store_id');
            $t->unsignedBigInteger('stock_batch_id');
            $t->string('stockable_type');
            $t->unsignedBigInteger('stockable_id');
            $t->integer('quantity');
            $t->decimal('unit_cost', 14, 2)->default(0);
            $t->string('movement_type');
            $t->string('document_type')->nullable();
            $t->unsignedBigInteger('document_id')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('note')->nullable();
            $t->timestamps();
        });
        Schema::create('medicines', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->double('buying_price')->default(0);
            $t->integer('quantity')->default(0);
            $t->integer('available_quantity')->default(0);
            $t->timestamps();
        });
        Schema::create('medicine_bills', function (Blueprint $t) {
            $t->id();
            $t->string('bill_number')->nullable();
            $t->unsignedBigInteger('store_id')->nullable();
            $t->integer('payment_status')->default(0);
            $t->dateTime('dispensed_at')->nullable();
            $t->unsignedBigInteger('dispensed_by')->nullable();
            $t->timestamps();
        });
        Schema::create('sale_medicines', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('medicine_bill_id');
            $t->unsignedBigInteger('medicine_id');
            $t->integer('sale_quantity');
            $t->float('sale_price')->default(0);
            $t->float('tax')->default(0);
            $t->dateTime('expiry_date')->nullable();
            $t->timestamps();
        });
        Schema::create('purchase_medicines', function (Blueprint $t) {
            $t->id();
            $t->string('purchase_no')->nullable();
            $t->unsignedBigInteger('store_id')->nullable();
            $t->unsignedBigInteger('supplier_id')->nullable();
            $t->unsignedBigInteger('purchase_order_id')->nullable();
            $t->float('total')->default(0);
            $t->float('discount')->default(0);
            $t->float('tax')->default(0);
            $t->float('net_amount')->default(0);
            $t->integer('payment_type')->default(0);
            $t->boolean('payment_status')->default(1);
            $t->string('note')->nullable();
            $t->timestamps();
        });
        Schema::create('stock_levels', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('store_id');
            $t->string('stockable_type');
            $t->unsignedBigInteger('stockable_id');
            $t->integer('reorder_level')->default(0);
            $t->integer('reorder_quantity')->default(0);
            $t->timestamps();
        });
        Schema::create('purchase_orders', function (Blueprint $t) {
            $t->id();
            $t->string('number');
            $t->unsignedBigInteger('supplier_id');
            $t->unsignedBigInteger('store_id');
            $t->string('status');
            $t->date('expected_date')->nullable();
            $t->string('note')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            foreach (['closed', 'cancelled'] as $step) {
                $t->unsignedBigInteger($step.'_by')->nullable();
                $t->dateTime($step.'_at')->nullable();
            }
            $t->timestamps();
        });
        Schema::create('purchase_order_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('purchase_order_id');
            $t->string('stockable_type');
            $t->unsignedBigInteger('stockable_id');
            $t->integer('quantity');
            $t->decimal('unit_cost', 14, 2)->default(0);
            $t->integer('received_quantity')->default(0);
            $t->timestamps();
        });
        Schema::create('purchased_medicines', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('purchase_medicines_id');
            $t->unsignedBigInteger('medicine_id');
            $t->dateTime('expiry_date')->nullable();
            $t->string('lot_no');
            $t->float('tax')->default(0);
            $t->integer('quantity');
            $t->float('amount');
            $t->timestamps();
        });
        Schema::create('stock_transfers', function (Blueprint $t) {
            $t->id();
            $t->string('number');
            $t->string('kind');
            $t->string('status');
            $t->unsignedBigInteger('from_store_id')->nullable();
            $t->unsignedBigInteger('to_store_id')->nullable();
            $t->unsignedBigInteger('supplier_id')->nullable();
            $t->string('note')->nullable();
            foreach (['requested', 'approved', 'sent', 'received', 'cancelled'] as $step) {
                $t->unsignedBigInteger($step.'_by')->nullable();
                $t->dateTime($step.'_at')->nullable();
            }
            $t->timestamps();
        });
        Schema::create('stock_transfer_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('stock_transfer_id');
            $t->string('stockable_type');
            $t->unsignedBigInteger('stockable_id');
            $t->integer('quantity_requested');
            $t->integer('quantity_approved')->nullable();
            $t->integer('quantity_sent')->nullable();
            $t->timestamps();
        });
        Schema::create('stock_counts', function (Blueprint $t) {
            $t->id();
            $t->string('number');
            $t->unsignedBigInteger('store_id');
            $t->string('status');
            $t->boolean('blind')->default(false);
            $t->string('note')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            foreach (['submitted', 'approved', 'cancelled'] as $step) {
                $t->unsignedBigInteger($step.'_by')->nullable();
                $t->dateTime($step.'_at')->nullable();
            }
            $t->timestamps();
        });
        Schema::create('stock_count_lines', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('stock_count_id');
            $t->unsignedBigInteger('stock_batch_id')->nullable();
            $t->string('stockable_type');
            $t->unsignedBigInteger('stockable_id');
            $t->string('batch_no')->nullable();
            $t->date('expiry_date')->nullable();
            $t->decimal('unit_cost', 14, 2)->default(0);
            $t->integer('system_quantity')->default(0);
            $t->integer('counted_quantity')->nullable();
            $t->timestamps();
        });
        $this->pharmacy = DB::table('stores')->insertGetId(['name' => 'Pharmacy', 'can_sell' => true, 'sort_order' => 1]);
        $this->ward = DB::table('stores')->insertGetId(['name' => 'Ward', 'sort_order' => 2]);
        DB::table('settings')->insert(['key' => 'pharmacy_default_store', 'value' => (string) $this->pharmacy]);
    }

    private function medicine(string $name = 'Paracetamol'): Medicine
    {
        return Medicine::find(DB::table('medicines')->insertGetId(['name' => $name]));
    }

    private function bill(int $status = MedicineBill::UNPAID): MedicineBill
    {
        return MedicineBill::find(DB::table('medicine_bills')->insertGetId(['bill_number' => 'B1', 'payment_status' => $status]));
    }

    private function batchQty(Medicine $m, string $batchNo): int
    {
        return (int) StockBatch::where('stockable_id', $m->id)->where('batch_no', $batchNo)->value('quantity');
    }

    public function test_receipts_and_fefo_issue_skip_expired_batches_and_cache_the_total(): void
    {
        $m = $this->medicine();
        $doc = $this->bill();
        Stock::receive($m, $this->pharmacy, 10, ['batch_no' => 'LATE', 'expiry_date' => now()->addYear(), 'unit_cost' => 100], $doc);
        Stock::receive($m, $this->pharmacy, 5, ['batch_no' => 'SOON', 'expiry_date' => now()->addMonth(), 'unit_cost' => 100], $doc);
        Stock::receive($m, $this->pharmacy, 4, ['batch_no' => 'OLD', 'expiry_date' => now()->subDay(), 'unit_cost' => 100], $doc);
        Stock::receive($m, $this->pharmacy, 10, ['batch_no' => 'LATE', 'expiry_date' => now()->addYear(), 'unit_cost' => 200], $doc);

        $this->assertSame(20, $this->batchQty($m, 'LATE'), 'same batch and expiry add up');
        $this->assertEquals(150.0, StockBatch::where('batch_no', 'LATE')->value('unit_cost'), 'weighted average cost');
        $this->assertSame(29, (int) $m->fresh()->available_quantity);

        Stock::issue($m, $this->pharmacy, 7, $this->bill());
        $this->assertSame(0, $this->batchQty($m, 'SOON'), 'earliest expiry first');
        $this->assertSame(18, $this->batchQty($m, 'LATE'));
        $this->assertSame(4, $this->batchQty($m, 'OLD'), 'expired batch is not issued');
        $this->assertSame(22, (int) $m->fresh()->available_quantity);
    }

    public function test_not_enough_stock_is_refused_unless_negative_stock_is_allowed(): void
    {
        $m = $this->medicine();
        Stock::receive($m, $this->pharmacy, 3, ['batch_no' => 'A'], $this->bill());
        try {
            Stock::issue($m, $this->pharmacy, 5, $this->bill());
            $this->fail('should refuse');
        } catch (UnprocessableEntityHttpException $e) {
            $this->assertStringContainsString('Paracetamol', $e->getMessage());
        }
        $this->assertSame(3, $this->batchQty($m, 'A'), 'nothing taken');
        $this->assertSame(0, Stock::onHand($m, $this->ward), 'other store untouched');

        \App\Models\Setting::create(['key' => 'stock_allow_negative', 'value' => '1']);
        Stock::issue($m, $this->pharmacy, 5, $this->bill());
        $this->assertSame(-2, $this->batchQty($m, 'A'));
        $this->assertSame(-2, (int) $m->fresh()->available_quantity);
    }

    public function test_a_bill_takes_out_what_it_holds_when_paid_or_dispensed_and_follows_edits(): void
    {
        $para = $this->medicine();
        $amox = $this->medicine('Amoxicillin');
        $stock = $this->bill();
        Stock::receive($para, $this->pharmacy, 20, ['batch_no' => 'P1'], $stock);
        Stock::receive($amox, $this->pharmacy, 20, ['batch_no' => 'A1'], $stock);
        Stock::receive($para, $this->ward, 20, ['batch_no' => 'W1'], $stock);

        $bill = $this->bill();
        SaleMedicine::create(['medicine_bill_id' => $bill->id, 'medicine_id' => $para->id, 'sale_quantity' => 6, 'sale_price' => 1, 'tax' => 0]);
        SaleMedicine::create(['medicine_bill_id' => $bill->id, 'medicine_id' => $amox->id, 'sale_quantity' => 2, 'sale_price' => 1, 'tax' => 0]);
        $bill->syncStock();
        $this->assertSame(20, Stock::onHand($para, $this->pharmacy), 'unpaid and not dispensed: nothing out');
        $this->assertSame($this->pharmacy, (int) $bill->fresh()->store_id, 'default store saved on the bill');

        $bill->update(['payment_status' => MedicineBill::FULLPAID]);
        $bill->syncStock();
        $bill->syncStock();
        $this->assertSame(14, Stock::onHand($para, $this->pharmacy), 'paid: out once, a second sync changes nothing');
        $this->assertSame(18, Stock::onHand($amox, $this->pharmacy));

        SaleMedicine::where('medicine_id', $para->id)->update(['sale_quantity' => 4]);
        SaleMedicine::where('medicine_id', $amox->id)->delete();
        $bill->syncStock();
        $this->assertSame(16, Stock::onHand($para, $this->pharmacy), 'quantity lowered: 2 back');
        $this->assertSame(20, Stock::onHand($amox, $this->pharmacy), 'line removed: all back');

        $bill->update(['store_id' => $this->ward]);
        $bill->syncStock();
        $this->assertSame(20, Stock::onHand($para, $this->pharmacy), 'moved to another store: back in the first');
        $this->assertSame(16, Stock::onHand($para, $this->ward));

        Stock::reverse($bill, Stock::RETURN);
        $this->assertSame(20, Stock::onHand($para, $this->ward), 'deleted bill gives everything back');
        $this->assertSame(0, (int) StockMovement::forDocument($bill)->sum('quantity'));

        $ipd = $this->bill();
        SaleMedicine::create(['medicine_bill_id' => $ipd->id, 'medicine_id' => $amox->id, 'sale_quantity' => 5, 'sale_price' => 1, 'tax' => 0]);
        $ipd->update(['dispensed_at' => now()]);
        $ipd->syncStock();
        $this->assertSame(15, Stock::onHand($amox, $this->pharmacy), 'dispensed without payment (admission bill)');
    }

    public function test_purchase_lines_are_received_and_a_used_purchase_cannot_be_undone(): void
    {
        $m = $this->medicine();
        $purchase = PurchaseMedicine::create(['purchase_no' => 'P-1', 'store_id' => $this->ward]);
        PurchasedMedicine::create(['purchase_medicines_id' => $purchase->id, 'medicine_id' => $m->id, 'lot_no' => 'L7',
            'expiry_date' => '2030-05-31 00:00:00', 'quantity' => 10, 'amount' => 2500, 'tax' => 0]);

        $batch = StockBatch::where('batch_no', 'L7')->first();
        $this->assertSame($this->ward, $batch->store_id);
        $this->assertSame('2030-05-31', $batch->expiry_date);
        $this->assertEquals(250.0, $batch->unit_cost);
        $this->assertSame(10, (int) $m->fresh()->available_quantity);

        $bill = $this->bill();
        Stock::issue($m, $this->ward, 4, $bill);
        try {
            Stock::reverse($purchase);
            $this->fail('should refuse');
        } catch (UnprocessableEntityHttpException $e) {
            $this->assertSame(6, $this->batchQty($m, 'L7'));
        }
        Stock::reverse($bill, Stock::RETURN);
        Stock::reverse($purchase);
        $this->assertSame(0, $this->batchQty($m, 'L7'));
        $this->assertSame(0, (int) $m->fresh()->available_quantity);
    }

    public function test_a_ward_requisition_is_approved_sent_and_received_with_the_same_batches(): void
    {
        $m = $this->medicine();
        $seed = $this->bill();
        Stock::receive($m, $this->pharmacy, 10, ['batch_no' => 'SOON', 'expiry_date' => now()->addMonth(), 'unit_cost' => 120], $seed);
        Stock::receive($m, $this->pharmacy, 50, ['batch_no' => 'LATE', 'expiry_date' => now()->addYear(), 'unit_cost' => 100], $seed);

        $req = \App\Models\StockTransfer::open('requisition', $this->pharmacy, $this->ward, null, [[$m, 30]], 'night stock', 1);
        $this->assertSame('requested', $req->status, 'approval is on by default');
        $this->assertSame('TR-'.now()->year.'-0001', $req->number);
        $line = $req->items()->first();

        try {
            $req->send([], 1);
            $this->fail('cannot send before approval');
        } catch (UnprocessableEntityHttpException $e) {
        }
        $req->approve([$line->id => 25], 1);
        $req->refresh()->send([$line->id => 40], 1);
        $this->assertSame(25, $line->fresh()->quantity_sent, 'never more than approved');
        $this->assertSame(35, Stock::onHand($m, $this->pharmacy));
        $this->assertSame(0, Stock::onHand($m, $this->ward), 'in transit until received');

        $req->refresh()->receive(2);
        $this->assertSame('received', $req->fresh()->status);
        $this->assertSame(25, Stock::onHand($m, $this->ward));
        $ward = StockBatch::where('store_id', $this->ward)->orderBy('expiry_date')->get();
        $this->assertSame(['SOON', 'LATE'], $ward->pluck('batch_no')->all(), 'same batches, earliest expiry sent first');
        $this->assertSame([10, 15], $ward->pluck('quantity')->all());
        $this->assertEquals(120.0, $ward->first()->unit_cost, 'cost travels with the batch');
        $this->assertSame(60, (int) $m->fresh()->available_quantity, 'a transfer does not change the total');

        try {
            $req->fresh()->receive(2);
            $this->fail('cannot receive twice');
        } catch (UnprocessableEntityHttpException $e) {
        }
    }

    public function test_cancelling_a_sent_transfer_puts_the_stock_back_and_supplier_returns_leave_the_stock(): void
    {
        \App\Models\Setting::create(['key' => 'stock_transfer_approval', 'value' => '0']);
        $m = $this->medicine();
        Stock::receive($m, $this->pharmacy, 20, ['batch_no' => 'A'], $this->bill());

        $back = \App\Models\StockTransfer::open('return', $this->pharmacy, $this->ward, null, [[$m, 8]], null, 1);
        $this->assertSame('approved', $back->status, 'no approval needed when the setting is off');
        $back->send([], 1);
        $this->assertSame(12, Stock::onHand($m, $this->pharmacy));
        $back->fresh()->cancel(1);
        $this->assertSame(20, Stock::onHand($m, $this->pharmacy));
        $this->assertSame('cancelled', $back->fresh()->status);

        $toSupplier = \App\Models\StockTransfer::open('supplier_return', $this->pharmacy, $this->ward, 7, [[$m, 5]], 'damaged', 1);
        $this->assertNull($toSupplier->to_store_id);
        $toSupplier->send([], 1);
        $this->assertSame(15, Stock::onHand($m, $this->pharmacy));
        $this->assertSame(15, (int) $m->fresh()->available_quantity);
        $this->assertSame('supplier_return', StockMovement::forDocument($toSupplier)->value('movement_type'));
    }

    public function test_after_a_ward_requisition_the_ward_count_shows_no_difference(): void
    {
        \App\Models\Setting::create(['key' => 'stock_transfer_approval', 'value' => '0']);
        $m = $this->medicine();
        Stock::receive($m, $this->pharmacy, 30, ['batch_no' => 'A', 'expiry_date' => now()->addYear()], $this->bill());
        $req = \App\Models\StockTransfer::open('requisition', $this->pharmacy, $this->ward, null, [[$m, 12]], null, 1);
        $req->send([], 1);
        $req->fresh()->receive(1);

        $count = \App\Models\StockCount::open($this->ward, false, null, 1);
        $line = $count->lines()->first();
        $this->assertSame(12, $line->system_quantity);
        $count->record([$line->id => 12], []);
        $count->submit(1);
        $count->fresh()->approve(1);
        $this->assertSame(0, $line->fresh()->variance());
        $this->assertSame(0, StockMovement::forDocument($count)->count(), 'no difference, no adjustment');
        $this->assertSame(12, Stock::onHand($m, $this->ward));
    }

    public function test_a_count_corrects_losses_keeps_sales_made_meanwhile_and_adds_goods_found(): void
    {
        $m = $this->medicine();
        $other = $this->medicine('Ibuprofen');
        Stock::receive($m, $this->pharmacy, 20, ['batch_no' => 'A', 'unit_cost' => 50], $this->bill());
        $count = \App\Models\StockCount::open($this->pharmacy, true, null, 1);
        $this->assertSame('SC-'.now()->year.'-0001', $count->number);
        $line = $count->lines()->first();

        Stock::issue($m, $this->pharmacy, 3, $this->bill()); // sold while counting: 17 left on the shelf
        try {
            $count->submit(1);
            $this->fail('every line must be counted');
        } catch (UnprocessableEntityHttpException $e) {
        }
        $count->record([$line->id => 15], [[$other, 'X9', '2030-01-31', 4]]); // 2 lost, 4 found
        $count->submit(1);
        $this->assertSame(-5, $line->fresh()->variance(), 'counted 15 against 20 on the sheet');
        $count->fresh()->approve(1);

        $this->assertSame(12, Stock::onHand($m, $this->pharmacy), '17 on the system - 5 = 12: the 3 sold are not counted twice');
        $this->assertSame(4, Stock::onHand($other, $this->pharmacy));
        $this->assertSame('2030-01-31', StockBatch::where('batch_no', 'X9')->value('expiry_date'));
        $this->assertSame(['adjustment'], StockMovement::forDocument($count)->distinct()->pluck('movement_type')->all());

        try {
            $count->fresh()->approve(1);
            $this->fail('cannot approve twice');
        } catch (UnprocessableEntityHttpException $e) {
        }
    }

    public function test_reorder_suggestion(): void
    {
        $this->assertSame(0, \App\Models\StockLevel::suggested(20, 50, 25, 0), 'above the level');
        $this->assertSame(50, \App\Models\StockLevel::suggested(20, 50, 5, 0), 'at least the reorder quantity');
        $this->assertSame(70, \App\Models\StockLevel::suggested(100, 50, 30, 0), 'enough to reach the level');
        $this->assertSame(0, \App\Models\StockLevel::suggested(20, 50, 5, 40), 'already on order');
    }

    public function test_a_purchase_order_is_received_in_deliveries_into_its_store(): void
    {
        $m = $this->medicine();
        $order = \App\Models\PurchaseOrder::open(3, $this->ward, [[$m, 100, 80]], null, null, 1);
        $line = $order->items()->first();
        $this->assertSame(['App\Models\Medicine:'.$m->id => 100], \App\Models\PurchaseOrder::onOrder($this->ward));

        $receipt = $order->receive([[$line, 60, 'L1', '2028-02-29', 85]], 0, false, 'BL-77');
        $this->assertSame('partial', $order->fresh()->status);
        $this->assertSame($order->id, (int) $receipt->purchase_order_id);
        $this->assertSame(0, (int) $receipt->payment_status, 'delivered on credit');
        $this->assertEquals(5100, $receipt->net_amount);
        $this->assertSame(60, Stock::onHand($m, $this->ward));
        $this->assertEquals(85.0, StockBatch::where('batch_no', 'L1')->value('unit_cost'));
        $this->assertSame(['App\Models\Medicine:'.$m->id => 40], \App\Models\PurchaseOrder::onOrder($this->ward));

        $order->fresh()->receive([[$line, 40, 'L2', null, 80]], 1, true, null);
        $this->assertSame('received', $order->fresh()->status);
        $this->assertSame(100, (int) $m->fresh()->available_quantity);
        $this->assertSame([], \App\Models\PurchaseOrder::onOrder($this->ward));

        try {
            $order->fresh()->receive([[$line, 1, 'L3', null, 80]], 0, false, null);
            $this->fail('a received order takes no more deliveries');
        } catch (UnprocessableEntityHttpException $e) {
        }
        $other = \App\Models\PurchaseOrder::open(3, $this->ward, [[$m, 10, 80]], null, null, 1);
        $other->receive([[$other->items()->first(), 4, 'L4', null, 80]], 0, false, null);
        $other->fresh()->close(1);
        $this->assertSame('closed', $other->fresh()->status);
    }
}
