<?php

namespace Tests\Feature;

use App\Models\ClaimInvoice;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ClaimTest extends TestCase
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
        Schema::create('insurances', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('billing_option')->default('monthly');
            $t->unsignedSmallInteger('credit_days')->nullable();
            $t->timestamps();
        });
        Schema::create('patient_insurances', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('patient_id');
            $t->unsignedInteger('insurance_id');
            $t->timestamps();
        });
        Schema::create('opd_patient_departments', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('patient_id');
            $t->unsignedBigInteger('doctor_id')->nullable();
            $t->unsignedBigInteger('patient_insurance_id')->nullable();
            $t->string('voucher_number')->nullable();
            $t->dateTime('appointment_date');
            $t->double('standard_charge');
            $t->decimal('payer_amount', 15, 2)->default(0);
            $t->decimal('patient_amount', 15, 2)->nullable();
        });
        Schema::create('exam_orders', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('patient_id');
            $t->unsignedBigInteger('ipd_patient_department_id')->nullable();
            $t->unsignedBigInteger('patient_insurance_id')->nullable();
            $t->string('voucher_number')->nullable();
            $t->dateTime('ordered_at');
            $t->timestamps();
        });
        Schema::create('exam_order_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('exam_order_id');
            $t->string('description');
            $t->decimal('total', 15, 2);
            $t->decimal('payer_amount', 15, 2)->default(0);
            $t->decimal('patient_amount', 15, 2);
            $t->timestamps();
        });
        Schema::create('medicine_bills', function (Blueprint $t) {
            $t->id();
            $t->string('bill_number');
            $t->unsignedInteger('patient_id');
            $t->unsignedBigInteger('patient_insurance_id')->nullable();
            $t->unsignedBigInteger('ipd_patient_department_id')->nullable();
            $t->double('net_amount');
            $t->decimal('payer_amount', 15, 2)->default(0);
            $t->decimal('patient_amount', 15, 2)->nullable();
            $t->integer('payment_status')->default(0);
            $t->dateTime('dispensed_at')->nullable();
            $t->dateTime('bill_date');
            $t->timestamps();
        });
        Schema::create('ipd_patient_departments', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('patient_id');
            $t->unsignedBigInteger('patient_insurance_id')->nullable();
            $t->string('voucher_number')->nullable();
            $t->string('ipd_number')->nullable();
            $t->dateTime('discharge_date')->nullable();
        });
        Schema::create('ipd_bills', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ipd_patient_department_id');
            $t->double('gross_total')->default(0);
            $t->double('other_charges')->default(0);
            $t->decimal('payer_amount', 15, 2)->default(0);
            $t->decimal('reduction_amount', 15, 2)->default(0);
            $t->timestamps();
        });
        Schema::create('claim_invoices', function (Blueprint $t) {
            $t->id();
            $t->string('number');
            $t->unsignedInteger('insurance_id');
            $t->string('billing_option');
            $t->date('period_from');
            $t->date('period_to');
            $t->date('issued_on');
            $t->date('due_on')->nullable();
            $t->string('status');
            $t->decimal('total_claimed', 15, 2)->default(0);
            $t->string('note')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('cancelled_by')->nullable();
            $t->dateTime('cancelled_at')->nullable();
            $t->timestamps();
        });
        Schema::create('claim_lines', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('claim_invoice_id');
            $t->string('source_type');
            $t->unsignedBigInteger('source_id');
            $t->unsignedInteger('patient_id')->nullable();
            $t->unsignedBigInteger('patient_insurance_id')->nullable();
            $t->string('voucher_number')->nullable();
            $t->date('service_date');
            $t->string('description');
            $t->decimal('total', 15, 2)->default(0);
            $t->decimal('patient_amount', 15, 2)->default(0);
            $t->decimal('payer_amount', 15, 2)->default(0);
            $t->decimal('paid_amount', 15, 2)->default(0);
            $t->string('status')->default('pending');
            $t->unsignedInteger('rejection_reason')->nullable();
            $t->string('note')->nullable();
            $t->timestamps();
        });
    }

    private function seedActs(): void
    {
        DB::table('insurances')->insert(['id' => 1, 'name' => 'MUGEF', 'billing_option' => 'monthly', 'credit_days' => 30]);
        DB::table('patient_insurances')->insert([['id' => 10, 'patient_id' => 7, 'insurance_id' => 1], ['id' => 11, 'patient_id' => 8, 'insurance_id' => 1]]);
        DB::table('opd_patient_departments')->insert([
            ['patient_id' => 7, 'patient_insurance_id' => 10, 'voucher_number' => 'B-1', 'appointment_date' => '2026-11-05 09:00', 'standard_charge' => 6000, 'payer_amount' => 4800, 'patient_amount' => 1200],
            ['patient_id' => 8, 'patient_insurance_id' => 11, 'voucher_number' => 'B-2', 'appointment_date' => '2026-11-06 09:00', 'standard_charge' => 6000, 'payer_amount' => 4800, 'patient_amount' => 1200],
            ['patient_id' => 7, 'patient_insurance_id' => null, 'voucher_number' => null, 'appointment_date' => '2026-11-07 09:00', 'standard_charge' => 6000, 'payer_amount' => 0, 'patient_amount' => 6000],
            ['patient_id' => 7, 'patient_insurance_id' => 10, 'voucher_number' => 'B-9', 'appointment_date' => '2026-12-02 09:00', 'standard_charge' => 6000, 'payer_amount' => 4800, 'patient_amount' => 1200],
        ]);
        DB::table('exam_orders')->insert([['id' => 1, 'patient_id' => 7, 'ipd_patient_department_id' => null, 'patient_insurance_id' => 10, 'voucher_number' => 'B-1', 'ordered_at' => '2026-11-05 10:00'],
            ['id' => 2, 'patient_id' => 7, 'ipd_patient_department_id' => 1, 'patient_insurance_id' => 10, 'voucher_number' => null, 'ordered_at' => '2026-11-10 10:00']]);
        DB::table('exam_order_items')->insert([['exam_order_id' => 1, 'description' => 'NFS', 'total' => 3000, 'payer_amount' => 2400, 'patient_amount' => 600],
            ['exam_order_id' => 2, 'description' => 'CRP', 'total' => 2000, 'payer_amount' => 1600, 'patient_amount' => 400]]);
        DB::table('medicine_bills')->insert([
            ['bill_number' => 'PH1', 'patient_id' => 8, 'patient_insurance_id' => 11, 'net_amount' => 2000, 'payer_amount' => 1600, 'patient_amount' => 400, 'payment_status' => 1, 'bill_date' => '2026-11-06 11:00'],
            ['bill_number' => 'PH2', 'patient_id' => 8, 'patient_insurance_id' => 11, 'net_amount' => 1000, 'payer_amount' => 800, 'patient_amount' => 200, 'payment_status' => 0, 'bill_date' => '2026-11-06 12:00'],
        ]);
        DB::table('ipd_patient_departments')->insert(['id' => 1, 'patient_id' => 7, 'patient_insurance_id' => 10, 'voucher_number' => 'H-1', 'ipd_number' => 'IPD1', 'discharge_date' => '2026-11-12 10:00']);
        DB::table('ipd_bills')->insert(['ipd_patient_department_id' => 1, 'gross_total' => 50000, 'payer_amount' => 36000, 'reduction_amount' => 5000, 'created_at' => '2026-11-12 11:00']);
    }

    public function test_invoice_numbers_follow_the_admin_pattern(): void
    {
        $this->assertSame('0002/11/26/SUKA', ClaimInvoice::formatNumber('{seq}/{mm}/{yy}/SUKA', 2, Carbon::parse('2026-11-22'), 4));
        $this->assertSame('FT-2026-17', ClaimInvoice::formatNumber('FT-{yyyy}-{seq}', 17, Carbon::parse('2026-01-01'), 2));
    }

    public function test_a_month_of_acts_is_claimed_once_in_one_batch_with_the_patient_share_per_line(): void
    {
        $this->seedActs();
        $from = Carbon::parse('2026-11-01');
        $to = Carbon::parse('2026-11-30');
        $eligible = ClaimInvoice::eligible(1, $from, $to);
        $this->assertSame(['opd_visit', 'exam_item', 'opd_visit', 'medicine_bill', 'ipd_bill'], $eligible->pluck('source_type')->all(),
            'not the uninsured visit, the December visit, the unpaid pharmacy bill or the admission exam (on the admission bill)');

        $invoices = ClaimInvoice::generate(1, $from, $to, 1);
        $this->assertCount(1, $invoices, 'monthly: one batch');
        $invoice = $invoices->first();
        $this->assertEquals(4800 + 2400 + 4800 + 1600 + 36000, $invoice->total_claimed);
        $this->assertSame(now()->addDays(30)->toDateString(), $invoice->due_on->toDateString());
        $ipdLine = $invoice->lines()->where('source_type', 'ipd_bill')->first();
        $this->assertEquals([50000, 9000, 'H-1'], [$ipdLine->total, $ipdLine->patient_amount, $ipdLine->voucher_number], 'T.M. = total - reductions - payer');

        $this->assertTrue(ClaimInvoice::eligible(1, $from, $to)->isEmpty(), 'nothing is claimed twice');
        $invoice->cancel(1);
        $this->assertCount(5, ClaimInvoice::eligible(1, $from, $to), 'a cancelled invoice frees its acts');
    }

    public function test_billing_options_split_the_invoices_and_numbers_restart_each_month(): void
    {
        $this->seedActs();
        DB::table('insurances')->where('id', 1)->update(['billing_option' => 'per_admission']);
        $invoices = ClaimInvoice::generate(1, Carbon::parse('2026-11-01'), Carbon::parse('2026-11-30'), 1);
        // patient 7 on 05/11 (visit + exam), patient 8 on 06/11 (visit + pharmacy), the admission
        $this->assertSame([2, 2, 1], $invoices->map(fn ($i) => $i->lines()->count())->all());
        $month = now()->format('m/y');
        $this->assertSame(['0001/'.$month, '0002/'.$month, '0003/'.$month], $invoices->pluck('number')->all());

        DB::table('insurances')->where('id', 1)->update(['billing_option' => 'per_visit']);
        $this->assertCount(1, ClaimInvoice::generate(1, Carbon::parse('2026-12-01'), Carbon::parse('2026-12-31'), 1));
    }
}
