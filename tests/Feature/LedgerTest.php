<?php

namespace Tests\Feature;

use App\Models\Journal;
use App\Utils\Ledger;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Tests\TestCase;

class LedgerTest extends TestCase
{
    protected array $acc = [];

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('key');
            $t->text('value')->nullable();
            $t->timestamps();
        });
        Schema::create('accounts', function (Blueprint $t) {
            $t->id();
            $t->string('code')->nullable();
            $t->string('name');
            $t->tinyInteger('type')->default(1);
            $t->string('account_class')->nullable();
            $t->unsignedInteger('parent_id')->nullable();
            $t->boolean('is_postable')->default(true);
            $t->boolean('status')->default(1);
            $t->text('description')->nullable();
            $t->timestamps();
        });
        Schema::create('fiscal_years', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->date('starts_on');
            $t->date('ends_on');
            $t->string('status')->default('open');
            $t->timestamps();
        });
        Schema::create('journals', function (Blueprint $t) {
            $t->id();
            $t->string('code');
            $t->string('name');
            $t->string('kind');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('journal_entries', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('journal_id');
            $t->unsignedBigInteger('fiscal_year_id');
            $t->string('number');
            $t->date('entry_date');
            $t->string('reference')->nullable();
            $t->string('label');
            $t->string('source_type')->nullable();
            $t->unsignedBigInteger('source_id')->nullable();
            $t->string('source_event')->nullable();
            $t->unsignedBigInteger('reversal_of')->nullable();
            $t->dateTime('exported_at')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
        });
        Schema::create('journal_lines', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('journal_entry_id');
            $t->unsignedInteger('account_id');
            $t->string('label')->nullable();
            $t->decimal('debit', 15, 2)->default(0);
            $t->decimal('credit', 15, 2)->default(0);
            $t->timestamps();
        });
        foreach ([['5', false], ['5711', true], ['4111', true], ['706100', true]] as [$code, $postable]) {
            $this->acc[$code] = DB::table('accounts')->insertGetId(['code' => $code, 'name' => 'A'.$code, 'is_postable' => $postable]);
        }
        DB::table('fiscal_years')->insert([['name' => '2025', 'starts_on' => '2025-01-01', 'ends_on' => '2025-12-31', 'status' => 'closed'],
            ['name' => '2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'status' => 'open']]);
        DB::table('journals')->insert(['code' => 'CA', 'name' => 'Caisse', 'kind' => 'cash']);
    }

    private function sale(float $amount, string $date = '2026-11-05'): \App\Models\JournalEntry
    {
        return Ledger::post('CA', $date, 'Consultation', [['account_id' => $this->acc['5711'], 'debit' => $amount], ['account_id' => $this->acc['706100'], 'credit' => $amount]], 'R-1');
    }

    private function refused(callable $call): void
    {
        try {
            $call();
            $this->fail('should be refused');
        } catch (UnprocessableEntityHttpException $e) {
            $this->assertNotSame('', $e->getMessage());
        }
    }

    public function test_balanced_entries_are_numbered_per_journal_and_year_and_the_balance_is_zero(): void
    {
        $first = $this->sale(6000);
        $second = $this->sale(1242);
        $this->assertSame(['CA26-00001', 'CA26-00002'], [$first->number, $second->number]);

        $totals = Ledger::totals('2026-01-01', '2026-12-31');
        $this->assertEquals(7242, $totals[$this->acc['5711']]['debit']);
        $this->assertEquals(0, round(array_sum(array_column($totals, 'debit')) - array_sum(array_column($totals, 'credit')), 2), 'trial balance is zero');
    }

    public function test_unbalanced_group_accounts_closed_years_and_locked_dates_are_refused(): void
    {
        $this->refused(fn () => Ledger::post('CA', '2026-11-05', 'x', [['account_id' => $this->acc['5711'], 'debit' => 100], ['account_id' => $this->acc['706100'], 'credit' => 90]]));
        $this->refused(fn () => Ledger::post('CA', '2026-11-05', 'x', [['account_id' => $this->acc['5'], 'debit' => 100], ['account_id' => $this->acc['706100'], 'credit' => 100]]));
        $this->refused(fn () => Ledger::post('CA', '2026-11-05', 'x', [['account_id' => $this->acc['5711'], 'debit' => 100]]));
        $this->refused(fn () => $this->sale(100, '2025-06-01'));
        $this->refused(fn () => $this->sale(100, '2027-01-02'));
        \App\Models\Setting::create(['key' => 'accounting_lock_date', 'value' => '2026-10-31']);
        $this->refused(fn () => $this->sale(100, '2026-10-31'));
        $this->assertSame('CA26-00001', $this->sale(100, '2026-11-01')->number);
        $this->assertSame(1, DB::table('journal_entries')->count(), 'nothing half-written');
    }

    public function test_an_entry_is_reversed_once(): void
    {
        $entry = $this->sale(6000);
        $reversal = Ledger::reverse($entry->load('lines', 'journal'), '2026-11-06');
        $this->assertSame($entry->id, (int) $reversal->reversal_of);
        $totals = Ledger::totals('2026-01-01', '2026-12-31');
        $this->assertEquals($totals[$this->acc['5711']]['debit'], $totals[$this->acc['5711']]['credit'], 'back to nothing');
        $this->refused(fn () => Ledger::reverse($entry->fresh()->load('lines', 'journal')));
        $this->refused(fn () => Ledger::reverse($reversal->load('lines', 'journal')));
        $this->assertSame(Journal::first()->id, $reversal->journal_id);
    }
}
