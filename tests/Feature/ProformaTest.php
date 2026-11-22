<?php

namespace Tests\Feature;

use App\Models\Invoice;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Tests\TestCase;

class ProformaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('key');
            $t->text('value')->nullable();
            $t->timestamps();
        });
        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->string('invoice_id');
            $t->string('type')->default('invoice');
            $t->unsignedInteger('patient_id');
            $t->date('invoice_date');
            $t->date('valid_until')->nullable();
            $t->unsignedInteger('insurance_id')->nullable();
            $t->unsignedBigInteger('price_list_id')->nullable();
            $t->unsignedInteger('converted_invoice_id')->nullable();
            $t->double('amount')->default(0);
            $t->double('discount')->default(0);
            $t->boolean('status')->default(0);
            $t->string('currency_symbol')->nullable();
            $t->timestamps();
        });
        Schema::create('invoice_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('account_id');
            $t->unsignedInteger('invoice_id');
            $t->string('description')->nullable();
            $t->integer('quantity');
            $t->double('price');
            $t->double('total');
            $t->timestamps();
        });
        \App\Models\Setting::create(['key' => 'proforma_validity_days', 'value' => '15']);
    }

    public function test_validity_defaults_from_the_setting(): void
    {
        $this->assertSame('2026-12-16', Invoice::validUntil(null, '2026-12-01'));
        $this->assertSame('2026-12-31', Invoice::validUntil('2026-12-31', '2026-12-01'));
    }

    public function test_a_valid_quote_becomes_an_invoice_once_and_an_expired_one_does_not(): void
    {
        $quote = Invoice::create(['patient_id' => 1, 'invoice_id' => 'Q1', 'type' => 'proforma', 'invoice_date' => now()->toDateString(),
            'valid_until' => now()->addDays(5)->toDateString(), 'amount' => 150000, 'discount' => 0, 'status' => 1, 'insurance_id' => 3]);
        DB::table('invoice_items')->insert([['account_id' => 1, 'invoice_id' => $quote->id, 'description' => 'Césarienne', 'quantity' => 1, 'price' => 120000, 'total' => 120000],
            ['account_id' => 1, 'invoice_id' => $quote->id, 'description' => 'Chambre', 'quantity' => 3, 'price' => 10000, 'total' => 30000]]);

        $invoice = $quote->convertToInvoice();
        $this->assertSame('invoice', $invoice->type);
        $this->assertNotSame('Q1', $invoice->invoice_id);
        $this->assertSame([120000.0, 30000.0], $invoice->invoiceItems()->pluck('total')->map(fn ($v) => (float) $v)->all());
        $this->assertSame([150000.0, 3], [(float) $invoice->amount, (int) $invoice->insurance_id]);
        $this->assertSame($invoice->id, (int) $quote->fresh()->converted_invoice_id);

        try {
            $quote->fresh()->convertToInvoice();
            $this->fail('converted once');
        } catch (UnprocessableEntityHttpException $e) {
        }

        $old = Invoice::create(['patient_id' => 1, 'invoice_id' => 'Q2', 'type' => 'proforma', 'invoice_date' => now()->subMonth()->toDateString(),
            'valid_until' => now()->subDay()->toDateString(), 'amount' => 1000, 'discount' => 0, 'status' => 1]);
        $this->assertTrue($old->isExpired());
        $this->expectException(UnprocessableEntityHttpException::class);
        $old->convertToInvoice();
    }
}
