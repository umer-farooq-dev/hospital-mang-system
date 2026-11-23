<?php

namespace Tests\Feature;

use App\Models\JournalEntry;
use App\Utils\Ledger;
use App\Utils\Posting;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PostingTest extends TestCase
{
    private function table(string $name, callable $columns): void
    {
        Schema::create($name, function (Blueprint $t) use ($columns) {
            $t->id();
            $columns($t);
            $t->timestamps();
        });
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->table('settings', fn ($t) => [$t->string('key'), $t->text('value')->nullable()]);
        $this->table('accounts', fn ($t) => [$t->string('code')->nullable(), $t->string('name'), $t->tinyInteger('type')->default(1), $t->boolean('is_postable')->default(true), $t->boolean('status')->default(1)]);
        $this->table('fiscal_years', fn ($t) => [$t->string('name'), $t->date('starts_on'), $t->date('ends_on'), $t->string('status')->default('open')]);
        $this->table('journals', fn ($t) => [$t->string('code'), $t->string('name'), $t->string('kind'), $t->boolean('is_active')->default(true)]);
        $this->table('journal_entries', fn ($t) => [$t->unsignedBigInteger('journal_id'), $t->unsignedBigInteger('fiscal_year_id'), $t->string('number'), $t->date('entry_date'),
            $t->string('reference')->nullable(), $t->string('label'), $t->string('source_type')->nullable(), $t->unsignedBigInteger('source_id')->nullable(),
            $t->string('source_event')->nullable(), $t->unsignedBigInteger('reversal_of')->nullable(), $t->dateTime('exported_at')->nullable(), $t->unsignedBigInteger('created_by')->nullable()]);
        $this->table('journal_lines', fn ($t) => [$t->unsignedBigInteger('journal_entry_id'), $t->unsignedInteger('account_id'), $t->string('label')->nullable(),
            $t->decimal('debit', 15, 2)->default(0), $t->decimal('credit', 15, 2)->default(0)]);
        $this->table('master_list_items', fn ($t) => [$t->string('type'), $t->unsignedInteger('code'), $t->string('name'), $t->string('lang_key')->nullable(),
            $t->unsignedInteger('quantity')->nullable(), $t->unsignedInteger('sort_order')->default(0), $t->boolean('is_active')->default(true)]);
        $this->table('cash_counters', fn ($t) => [$t->string('code'), $t->string('name')]);
        $this->table('receipts', fn ($t) => [$t->unsignedBigInteger('receipt_number'), $t->unsignedBigInteger('cash_counter_id'), $t->unsignedInteger('payment_method'),
            $t->decimal('amount', 15, 2), $t->dateTime('voided_at')->nullable()]);
        $this->table('receipt_lines', fn ($t) => [$t->unsignedBigInteger('receipt_id'), $t->string('payable_type'), $t->unsignedBigInteger('payable_id'), $t->decimal('amount', 15, 2)]);
        $this->table('refunds', fn ($t) => [$t->unsignedBigInteger('receipt_id'), $t->decimal('amount', 15, 2)]);
        $this->table('insurances', fn ($t) => [$t->string('name')]);
        $this->table('claim_invoices', fn ($t) => [$t->string('number'), $t->unsignedInteger('insurance_id'), $t->date('issued_on'), $t->decimal('total_claimed', 15, 2), $t->string('status'), $t->dateTime('cancelled_at')->nullable()]);
        $this->table('claim_lines', fn ($t) => [$t->unsignedBigInteger('claim_invoice_id'), $t->string('source_type'), $t->string('description')->default(''), $t->decimal('payer_amount', 15, 2),
            $t->decimal('paid_amount', 15, 2)->default(0), $t->string('status')->default('pending'), $t->date('settled_on')->nullable()]);
        $this->table('claim_payments', fn ($t) => [$t->unsignedBigInteger('claim_invoice_id'), $t->date('paid_on'), $t->decimal('amount', 15, 2), $t->unsignedInteger('payment_method')->nullable(), $t->string('reference')->nullable()]);
        $this->table('suppliers', fn ($t) => [$t->string('name')]);
        $this->table('purchase_medicines', fn ($t) => [$t->string('purchase_no'), $t->unsignedBigInteger('supplier_id')->nullable(), $t->float('net_amount'), $t->boolean('payment_status')->default(0)]);
        $this->table('item_stocks', fn ($t) => [$t->integer('quantity'), $t->double('purchase_price')]);
        $this->table('stock_movements', fn ($t) => [$t->unsignedBigInteger('store_id'), $t->unsignedBigInteger('stock_batch_id'), $t->string('stockable_type'), $t->unsignedBigInteger('stockable_id'),
            $t->integer('quantity'), $t->decimal('unit_cost', 14, 2), $t->string('movement_type'), $t->string('document_type')->nullable(), $t->unsignedBigInteger('document_id')->nullable()]);
        $this->table('doctor_statements', fn ($t) => [$t->string('number'), $t->unsignedBigInteger('doctor_id'), $t->string('status'), $t->date('paid_on')->nullable(), $t->decimal('gross', 15, 2),
            $t->decimal('withholding', 15, 2), $t->decimal('net', 15, 2), $t->unsignedInteger('payment_method')->nullable(), $t->string('payment_reference')->nullable()]);
        $this->table('referrer_payouts', fn ($t) => [$t->unsignedInteger('referrer_code'), $t->date('paid_on'), $t->decimal('amount', 15, 2), $t->unsignedInteger('payment_method')->nullable(), $t->string('reference')->nullable()]);
        $this->table('expenses', fn ($t) => [$t->integer('expense_head'), $t->string('name'), $t->string('invoice_number')->nullable(), $t->dateTime('date'), $t->double('amount')]);
        $this->table('incomes', fn ($t) => [$t->integer('income_head'), $t->string('name'), $t->string('invoice_number')->nullable(), $t->dateTime('date'), $t->double('amount')]);
        $this->table('payments', fn ($t) => [$t->date('payment_date'), $t->unsignedInteger('account_id'), $t->string('pay_to'), $t->double('amount')]);
        $this->table('employee_payrolls', fn ($t) => [$t->string('payroll_id'), $t->string('month'), $t->integer('year'), $t->double('net_salary'), $t->double('deductions')->default(0), $t->integer('status')]);
        $this->table('fund_transfers', fn ($t) => [$t->unsignedBigInteger('from_counter_id'), $t->unsignedBigInteger('to_counter_id')->nullable(), $t->unsignedInteger('destination')->nullable(),
            $t->unsignedInteger('payment_method'), $t->decimal('amount', 15, 2)]);

        foreach (array_unique(array_merge(array_values(Posting::ACCOUNTS), ['521', '554', '758', '658'])) as $code) {
            DB::table('accounts')->insert(['code' => $code, 'name' => 'A'.$code]);
        }
        foreach (Posting::JOURNALS as $code) {
            DB::table('journals')->insertOrIgnore(['code' => $code, 'name' => $code, 'kind' => 'general']);
        }
        DB::table('fiscal_years')->insert(['name' => 'Y', 'starts_on' => now()->subYear()->toDateString(), 'ends_on' => now()->addYear()->toDateString()]);
        DB::table('master_list_items')->insert([['type' => 'payment_method', 'code' => 1, 'name' => 'Espèces'], ['type' => 'payment_method', 'code' => 2, 'name' => 'Virement'],
            ['type' => 'payment_method', 'code' => 3, 'name' => 'Orange Money']]);
        \App\Models\Setting::create(['key' => 'cash_payment_method', 'value' => '1']);
        DB::table('cash_counters')->insert(['id' => 1, 'code' => 'C04', 'name' => 'Accueil']);
    }

    private function activity(): void
    {
        $y = now()->subDay();
        DB::table('receipts')->insert([['id' => 1, 'receipt_number' => 117511, 'cash_counter_id' => 1, 'payment_method' => 1, 'amount' => 7242, 'voided_at' => null, 'created_at' => $y],
            ['id' => 2, 'receipt_number' => 117512, 'cash_counter_id' => 1, 'payment_method' => 3, 'amount' => 900, 'voided_at' => $y->copy()->addHour(), 'created_at' => $y]]);
        DB::table('receipt_lines')->insert([['receipt_id' => 1, 'payable_type' => 'opd_visit', 'payable_id' => 1, 'amount' => 6000],
            ['receipt_id' => 1, 'payable_type' => 'exam_item', 'payable_id' => 1, 'amount' => 1242], ['receipt_id' => 2, 'payable_type' => 'medicine_bill', 'payable_id' => 1, 'amount' => 900]]);
        DB::table('insurances')->insert(['id' => 1, 'name' => 'MUGEF']);
        DB::table('claim_invoices')->insert(['id' => 1, 'number' => '0001/11/26', 'insurance_id' => 1, 'issued_on' => $y->toDateString(), 'total_claimed' => 10000, 'status' => 'partial']);
        DB::table('claim_lines')->insert([['claim_invoice_id' => 1, 'source_type' => 'opd_visit', 'payer_amount' => 4800, 'paid_amount' => 4800, 'status' => 'paid', 'settled_on' => null],
            ['claim_invoice_id' => 1, 'source_type' => 'ipd_bill', 'payer_amount' => 5200, 'paid_amount' => 1200, 'status' => 'written_off', 'settled_on' => $y->toDateString()]]);
        DB::table('claim_payments')->insert(['claim_invoice_id' => 1, 'paid_on' => $y->toDateString(), 'amount' => 6000, 'payment_method' => 2]);
        DB::table('purchase_medicines')->insert(['purchase_no' => 'P1', 'net_amount' => 5000, 'payment_status' => 0, 'created_at' => $y]);
        DB::table('stock_movements')->insert([
            ['store_id' => 1, 'stock_batch_id' => 1, 'stockable_type' => \App\Models\Medicine::class, 'stockable_id' => 1, 'quantity' => -3, 'unit_cost' => 100, 'movement_type' => 'sale', 'document_type' => \App\Models\MedicineBill::class, 'document_id' => 1, 'created_at' => $y],
            ['store_id' => 1, 'stock_batch_id' => 1, 'stockable_type' => \App\Models\Medicine::class, 'stockable_id' => 1, 'quantity' => 1, 'unit_cost' => 100, 'movement_type' => 'adjustment', 'document_type' => 'x', 'document_id' => 1, 'created_at' => $y],
            ['store_id' => 2, 'stock_batch_id' => 2, 'stockable_type' => \App\Models\Medicine::class, 'stockable_id' => 1, 'quantity' => -5, 'unit_cost' => 100, 'movement_type' => 'transfer_out', 'document_type' => \App\Models\StockTransfer::class, 'document_id' => 1, 'created_at' => $y],
        ]);
        DB::table('expenses')->insert(['expense_head' => 1, 'name' => 'Électricité', 'date' => $y, 'amount' => 2000]);
        DB::table('fund_transfers')->insert(['from_counter_id' => 1, 'destination' => 1, 'payment_method' => 1, 'amount' => 1000, 'created_at' => $y]);
    }

    private function balance(string $code): float
    {
        $id = DB::table('accounts')->where('code', $code)->value('id');
        $t = Ledger::totals(null, now()->toDateString(), $id)[$id] ?? ['debit' => 0, 'credit' => 0];

        return round($t['debit'] - $t['credit'], 2);
    }

    public function test_a_day_of_activity_posts_balanced_entries_once_and_the_trial_balance_is_zero(): void
    {
        $this->activity();
        $result = Posting::generate(now()->subDays(2), now());
        $this->assertSame([], $result['errors']);
        $this->assertSame(10, $result['posted'], 'receipts x2, void, claim, payer payment, write-off, purchase, stock day, expense, transfer');

        foreach (JournalEntry::with('lines')->get() as $entry) {
            $this->assertEquals($entry->lines->sum('debit'), $entry->lines->sum('credit'), $entry->label);
        }
        $totals = Ledger::totals(null, now()->toDateString());
        $this->assertEquals(0, round(array_sum(array_column($totals, 'debit')) - array_sum(array_column($totals, 'credit')), 2));

        $this->assertEquals(7242 - 2000 - 1000, $this->balance('5711'), 'cash: receipt in, expense and transfer out');
        $this->assertEquals(0, $this->balance('554'), 'the voided mobile-money receipt is reversed');
        $this->assertEquals(-(6000 + 4800), $this->balance('706100'), 'consultations: patient share + payer share');
        $this->assertEquals(10000 - 6000 - 4000, $this->balance('4112'), 'payer: claimed - paid - written off');
        $this->assertEquals(6000 + 1000, $this->balance('521'), "payer's transfer and the cash sent to the bank");
        $this->assertEquals(5000 - 300 + 100, $this->balance('311'), 'stock: purchase, cost of sales, count gain; transfers between stores ignored');
        $this->assertEquals(-5000, $this->balance('401'));

        $again = Posting::generate(now()->subDays(2), now());
        $this->assertSame([0, 0], [$again['posted'], count($again['errors'])], 'nothing posted twice');
    }

    public function test_a_missing_rule_is_reported_and_the_rest_is_posted(): void
    {
        $this->activity();
        DB::table('accounts')->where('code', '658')->delete(); // the expense head default account is gone
        $result = Posting::generate(now()->subDays(2), now());
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('Électricité', $result['errors'][0]);
        $this->assertSame(9, $result['posted']);

        \App\Models\Setting::create(['key' => 'posting_accounts', 'value' => json_encode(['expense_head.1' => DB::table('accounts')->where('code', '605')->value('id')])]);
        $this->assertSame(1, Posting::generate(now()->subDays(2), now())['posted'], 'fixed rule: only the missing entry is posted');
    }

    public function test_paid_salaries_post_gross_net_and_deductions(): void
    {
        DB::table('employee_payrolls')->insert([['payroll_id' => 'PR1', 'month' => 'November', 'year' => 2026, 'net_salary' => 90000, 'deductions' => 10000, 'status' => 1, 'updated_at' => now()->subDay()],
            ['payroll_id' => 'PR2', 'month' => 'November', 'year' => 2026, 'net_salary' => 50000, 'deductions' => 0, 'status' => 0, 'updated_at' => now()->subDay()]]);
        $result = Posting::generate(now()->subDays(2), now());
        $this->assertSame([1, []], [$result['posted'], $result['errors']], 'only the paid salary');
        $this->assertEquals(100000, $this->balance('661'));
        $this->assertEquals(-90000, $this->balance('5711'));
        $this->assertEquals(-10000, $this->balance('447'));
    }

    public function test_the_export_can_add_up_lines_per_entry_or_per_journal_and_day(): void
    {
        DB::table('receipts')->insert([['id' => 1, 'receipt_number' => 1, 'cash_counter_id' => 1, 'payment_method' => 1, 'amount' => 1000, 'created_at' => now()->subDay()],
            ['id' => 2, 'receipt_number' => 2, 'cash_counter_id' => 1, 'payment_method' => 1, 'amount' => 500, 'created_at' => now()->subDay()]]);
        DB::table('receipt_lines')->insert([['receipt_id' => 1, 'payable_type' => 'opd_visit', 'payable_id' => 1, 'amount' => 1000],
            ['receipt_id' => 2, 'payable_type' => 'opd_visit', 'payable_id' => 2, 'amount' => 500]]);
        Posting::generate(now()->subDays(2), now());
        $entries = \App\Utils\SageExport::entries(now()->subDays(2), now(), false);
        $options = ['columns' => ['account', 'direction', 'amount'], 'decimal' => '.', 'date_format' => 'd/m/Y'];
        $this->assertCount(4, \App\Utils\SageExport::rows($entries, $options + ['group' => 'none']));
        $this->assertSame([['5711', 'D', '1500.00'], ['706100', 'C', '1500.00']], \App\Utils\SageExport::rows($entries, $options + ['group' => 'day']));
    }
}
