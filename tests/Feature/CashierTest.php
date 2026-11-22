<?php

namespace Tests\Feature;

use App\Models\CashCounter;
use App\Models\User;
use App\Repositories\CashierRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CashierTest extends TestCase
{
    private CashierRepository $cashier;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('first_name')->nullable();
            $t->string('last_name')->nullable();
            $t->unsignedBigInteger('cash_counter_id')->nullable();
            $t->timestamps();
        });
        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('key');
            $t->string('value')->nullable();
            $t->timestamps();
        });
        Schema::create('cash_counters', function (Blueprint $t) {
            $t->id();
            $t->string('code');
            $t->string('name');
            $t->boolean('is_reception')->default(false);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('cash_sessions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('cash_counter_id');
            $t->unsignedBigInteger('user_id');
            $t->dateTime('opened_at');
            $t->decimal('opening_float', 15, 2)->default(0);
            $t->dateTime('closed_at')->nullable();
            $t->unsignedBigInteger('closing_number')->nullable();
            $t->decimal('expected_cash', 15, 2)->nullable();
            $t->decimal('counted_cash', 15, 2)->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });
        Schema::create('receipts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('receipt_number');
            $t->unsignedBigInteger('cash_session_id');
            $t->unsignedBigInteger('cash_counter_id');
            $t->unsignedBigInteger('user_id');
            $t->unsignedInteger('patient_id');
            $t->unsignedInteger('payment_method');
            $t->decimal('amount', 15, 2);
            $t->decimal('amount_received', 15, 2)->nullable();
            $t->decimal('change_given', 15, 2)->default(0);
            $t->string('cheque_number')->nullable();
            $t->string('cheque_drawer')->nullable();
            $t->dateTime('voided_at')->nullable();
            $t->unsignedInteger('void_reason')->nullable();
            $t->unsignedBigInteger('voided_by')->nullable();
            $t->timestamps();
        });
        Schema::create('refunds', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('receipt_id');
            $t->unsignedBigInteger('cash_session_id');
            $t->unsignedBigInteger('cash_counter_id');
            $t->unsignedBigInteger('user_id');
            $t->unsignedInteger('reason');
            $t->unsignedInteger('payment_method');
            $t->decimal('amount', 15, 2);
            $t->text('notes')->nullable();
            $t->timestamps();
        });
        Schema::create('receipt_lines', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('receipt_id');
            $t->string('payable_type');
            $t->unsignedBigInteger('payable_id');
            $t->string('description');
            $t->decimal('total', 15, 2);
            $t->decimal('discount_amount', 15, 2)->default(0);
            $t->decimal('payer_amount', 15, 2)->default(0);
            $t->decimal('amount', 15, 2);
            $t->timestamps();
        });
        Schema::create('exam_orders', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('patient_id');
            $t->unsignedInteger('ipd_patient_department_id')->nullable();
            $t->unsignedBigInteger('doctor_id')->nullable();
            $t->unsignedBigInteger('patient_insurance_id')->nullable();
            $t->unsignedBigInteger('price_list_id')->nullable();
            $t->string('voucher_number')->nullable();
            $t->dateTime('ordered_at');
            $t->unsignedBigInteger('user_id');
            $t->text('notes')->nullable();
            $t->timestamps();
        });
        Schema::create('exam_order_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('exam_order_id');
            $t->string('testable_type');
            $t->unsignedBigInteger('testable_id');
            $t->string('description');
            $t->decimal('total', 15, 2);
            $t->decimal('discount_amount', 15, 2)->default(0);
            $t->decimal('payer_amount', 15, 2)->default(0);
            $t->decimal('patient_amount', 15, 2);
            $t->timestamps();
        });
        Schema::create('fund_transfers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('from_session_id');
            $t->unsignedBigInteger('from_counter_id');
            $t->unsignedBigInteger('to_session_id')->nullable();
            $t->unsignedBigInteger('to_counter_id')->nullable();
            $t->unsignedInteger('destination')->nullable();
            $t->unsignedInteger('payment_method');
            $t->decimal('amount', 15, 2);
            $t->unsignedBigInteger('user_id');
            $t->text('notes')->nullable();
            $t->timestamps();
        });
        Schema::create('master_list_items', function (Blueprint $t) {
            $t->id();
            $t->string('type');
            $t->unsignedInteger('code');
            $t->string('name');
            $t->string('lang_key')->nullable();
            $t->unsignedInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        \App\Models\MasterListItem::flushCache();
        Schema::create('patients', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('patient_unique_id')->nullable();
        });
        Schema::create('doctors', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('doctor_department_id')->nullable();
        });
        Schema::create('doctor_departments', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->decimal('discount_percent', 5, 2)->nullable();
        });
        Schema::create('opd_patient_departments', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('patient_id');
            $t->unsignedBigInteger('doctor_id');
            $t->string('opd_number')->nullable();
            $t->dateTime('appointment_date');
            $t->double('standard_charge');
            $t->decimal('discount_amount', 15, 2)->default(0);
            $t->decimal('payer_amount', 15, 2)->default(0);
            $t->decimal('patient_amount', 15, 2)->nullable();
            $t->boolean('is_followup')->default(false);
        });
        (require base_path('database/migrations/2026_11_20_120000_create_audit_logs_table.php'))->up();

        $counter = CashCounter::create(['code' => 'C04', 'name' => 'Caisse accueil']);
        $this->user = User::unguarded(fn () => User::create(['first_name' => 'Awa', 'last_name' => 'Caissière', 'cash_counter_id' => $counter->id]));
        DB::table('settings')->insert([['key' => 'receipt_start_number', 'value' => '117511'], ['key' => 'cash_payment_method', 'value' => '1']]);
        $doctorUser = DB::table('users')->insertGetId(['first_name' => 'Dr', 'last_name' => 'Ouattara']);
        DB::table('patients')->insert(['id' => 7, 'user_id' => DB::table('users')->insertGetId(['first_name' => 'Aminata', 'last_name' => 'Ouedraogo']), 'patient_unique_id' => 'P7']);
        DB::table('doctor_departments')->insert(['id' => 1, 'title' => 'Médecine générale']);
        DB::table('doctors')->insert(['id' => 1, 'user_id' => $doctorUser, 'doctor_department_id' => 1]);
        DB::table('opd_patient_departments')->insert([
            ['id' => 1, 'patient_id' => 7, 'doctor_id' => 1, 'appointment_date' => '2026-11-22 09:00', 'standard_charge' => 6000, 'discount_amount' => 0, 'payer_amount' => 0, 'patient_amount' => 6000],
            ['id' => 2, 'patient_id' => 7, 'doctor_id' => 1, 'appointment_date' => '2026-11-22 10:00', 'standard_charge' => 6900, 'discount_amount' => 690, 'payer_amount' => 4968, 'patient_amount' => 1242],
            ['id' => 3, 'patient_id' => 7, 'doctor_id' => 1, 'appointment_date' => '2026-11-22 11:00', 'standard_charge' => 5000, 'discount_amount' => 0, 'payer_amount' => 5000, 'patient_amount' => 0], // insurer pays all
        ]);
        $this->cashier = app(CashierRepository::class);
    }

    public function test_collect_numbers_receipts_from_the_start_number_and_gives_change(): void
    {
        $session = $this->cashier->open($this->user, 10000);
        $this->assertSame(['opd_visit:1', 'opd_visit:2'], $this->cashier->unpaidItems(7)->pluck('key')->all(), 'a visit fully paid by the insurer is not listed');

        $receipt = $this->cashier->collect($session, $this->user, 7, ['opd_visit:1', 'opd_visit:2'], ['payment_method' => 1, 'amount_received' => 10000]);

        $this->assertSame([117511, 7242.0, 2758.0], [$receipt->receipt_number, $receipt->amount, $receipt->change_given]);
        $this->assertSame([6000.0, 1242.0], $receipt->lines->pluck('amount')->all());
        $this->assertTrue($this->cashier->unpaidItems(7)->isEmpty(), 'nothing left to pay');

        DB::table('opd_patient_departments')->where('id', 3)->update(['payer_amount' => 4000, 'patient_amount' => 1000]);
        $next = $this->cashier->collect($session, $this->user, 7, ['opd_visit:3'], ['payment_method' => 2]);
        $this->assertSame([117512, 1000.0, 0.0], [$next->receipt_number, $next->amount, $next->change_given]);
    }

    public function test_collect_refuses_nothing_selected_or_too_little_cash(): void
    {
        $session = $this->cashier->open($this->user, 0);

        try {
            $this->cashier->collect($session, $this->user, 7, ['opd_visit:3'], ['payment_method' => 1]);
            $this->fail('nothing to pay on visit 3');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('items', $e->errors());
        }

        $this->expectException(ValidationException::class);
        $this->cashier->collect($session, $this->user, 7, ['opd_visit:1'], ['payment_method' => 1, 'amount_received' => 5000]);
    }

    public function test_close_gives_the_next_closing_number_and_expected_cash(): void
    {
        $session = $this->cashier->open($this->user, 10000);
        $this->assertSame($session->id, $this->cashier->open($this->user, 0)->id, 'one open session per register');
        $this->cashier->collect($session, $this->user, 7, ['opd_visit:1'], ['payment_method' => 1]);   // cash 6 000
        $this->cashier->collect($session, $this->user, 7, ['opd_visit:2'], ['payment_method' => 2]);   // cheque 1 242

        $closed = $this->cashier->close($session, 15500, 'manque 500');
        $this->assertSame([1, 16000.0, 15500.0], [$closed->closing_number, $closed->expected_cash, $closed->counted_cash]);
        $this->assertNull($this->cashier->openSessionFor($this->user));

        $second = $this->cashier->close($this->cashier->open($this->user, 0), 0, null);
        $this->assertSame(2, $second->closing_number);
    }

    public function test_a_user_without_a_register_cannot_open(): void
    {
        $this->expectException(ValidationException::class);
        $this->cashier->open(User::unguarded(fn () => User::create(['first_name' => 'Sans', 'last_name' => 'Caisse'])), 0);
    }

    public function test_void_only_while_the_session_is_open_and_the_items_become_payable_again(): void
    {
        $session = $this->cashier->open($this->user, 0);
        $receipt = $this->cashier->collect($session, $this->user, 7, ['opd_visit:1'], ['payment_method' => 1]);

        $this->cashier->changeMethod($receipt, 2);
        $this->assertSame([2 => 6000.0], $session->fresh()->netByMethod(), 'method corrected while open');

        $this->cashier->void($receipt, $this->user, 4);
        $this->assertSame('voided', $receipt->fresh()->status);
        $this->assertSame([], $session->fresh()->netByMethod());
        $this->assertSame(['opd_visit:1', 'opd_visit:2'], $this->cashier->unpaidItems(7)->pluck('key')->all());

        $again = $this->cashier->collect($session, $this->user, 7, ['opd_visit:1'], ['payment_method' => 1]);
        $this->cashier->close($session, 6000, null);
        $this->expectException(ValidationException::class);
        $this->cashier->void($again, $this->user, 5);
    }

    public function test_refund_leaves_the_closed_session_unchanged_and_comes_out_of_the_current_one(): void
    {
        $first = $this->cashier->open($this->user, 0);
        $receipt = $this->cashier->collect($first, $this->user, 7, ['opd_visit:1'], ['payment_method' => 1]);
        $this->cashier->close($first, 6000, null);

        $second = $this->cashier->open($this->user, 10000);
        $refund = $this->cashier->refund($receipt, $this->user, 1, 'patient parti');

        $this->assertSame([6000.0, 1], [$refund->amount, $refund->payment_method]);
        $this->assertSame([1 => 6000.0], $first->fresh()->netByMethod(), 'closed session keeps its total');
        $this->assertSame([1 => -6000.0], $second->fresh()->netByMethod());
        $this->assertSame(4000.0, $this->cashier->close($second, 4000, null)->expected_cash, 'float 10 000 minus the refund');

        $this->expectException(ValidationException::class);
        $this->cashier->refund($receipt->fresh(), $this->user, 1, null);
    }

    public function test_cash_journal_runs_the_balance_from_the_session_float(): void
    {
        $session = $this->cashier->open($this->user, 10000);
        $a = $this->cashier->collect($session, $this->user, 7, ['opd_visit:1'], ['payment_method' => 1]);
        $this->cashier->collect($session, $this->user, 7, ['opd_visit:2'], ['payment_method' => 1]);
        $this->cashier->refund($a, $this->user, 1, null);

        $repo = app(\App\Repositories\CashJournalRepository::class);
        $journal = $repo->journal($repo->filters(['session' => $session->id, 'counter' => $session->cash_counter_id], $this->user));

        $this->assertSame(10000.0, $journal['opening']);
        $this->assertSame([16000.0, 17242.0, 11242.0], $journal['rows']->pluck('balance')->all());
        $this->assertSame([true, true, false], $journal['rows']->pluck('credit')->all());
        $this->assertSame([1 => 1242.0], $journal['totals']);
        $this->assertSame(11242.0, $journal['closing']);
    }

    public function test_consultation_and_exams_are_paid_on_one_receipt_like_the_client_sample(): void
    {
        $order = \App\Models\ExamOrder::create(['patient_id' => 7, 'doctor_id' => 1, 'ordered_at' => '2026-11-22 09:30', 'user_id' => $this->user->id]);
        $ge = $order->items()->create(['testable_type' => 'pathology_test', 'testable_id' => 1, 'description' => 'G.E. avec frottis', 'total' => 1500, 'patient_amount' => 1500]);
        $nfs = $order->items()->create(['testable_type' => 'pathology_test', 'testable_id' => 2, 'description' => 'NFS', 'total' => 3500, 'payer_amount' => 2800, 'patient_amount' => 700]);

        $this->assertSame(['opd_visit:1', 'exam_item:'.$ge->id, 'exam_item:'.$nfs->id, 'opd_visit:2'], $this->cashier->unpaidItems(7)->pluck('key')->all(), 'oldest first');

        $session = $this->cashier->open($this->user, 0);
        $receipt = $this->cashier->collect($session, $this->user, 7, ['exam_item:'.$ge->id, 'exam_item:'.$nfs->id], ['payment_method' => 1]);

        $this->assertSame(2200.0, $receipt->amount);
        $this->assertSame(['G.E. avec frottis', 'NFS'], $receipt->lines->pluck('description')->all());
        $this->assertSame(['exam_item', 'exam_item'], $receipt->lines->pluck('payable_type')->all());
        $this->assertSame('Dr Ouattara', $receipt->lines->first()->payable->doctor->doctorUser->full_name, 'the prescriber is named on the receipt');
        $this->assertSame(['opd_visit:1', 'opd_visit:2'], $this->cashier->unpaidItems(7)->pluck('key')->all());
    }

    public function test_fund_transfer_moves_cash_between_registers_and_to_the_safe(): void
    {
        $other = CashCounter::create(['code' => 'C.PH', 'name' => 'Caisse pharmacie']);
        $colleague = User::unguarded(fn () => User::create(['first_name' => 'Ali', 'last_name' => 'Pharma', 'cash_counter_id' => $other->id]));
        $mine = $this->cashier->open($this->user, 10000);
        $theirs = $this->cashier->open($colleague, 0);
        $this->cashier->collect($mine, $this->user, 7, ['opd_visit:1'], ['payment_method' => 1]); // drawer: 10 000 + 6 000

        $this->cashier->transfer($mine, $this->user, ['to_counter_id' => $other->id, 'payment_method' => 1, 'amount' => 5000]);
        $this->cashier->transfer($mine, $this->user, ['destination' => 1, 'payment_method' => 1, 'amount' => 8000]);

        $this->assertSame([1 => -7000.0], $mine->fresh()->netByMethod(), '6 000 in, 13 000 out');
        $this->assertSame([1 => 5000.0], $theirs->fresh()->netByMethod());
        $this->assertSame(3000.0, $this->cashier->close($mine, 3000, null)->expected_cash, 'float 10 000 - 7 000');

        try {
            $this->cashier->transfer($theirs, $colleague, ['destination' => 2, 'payment_method' => 1, 'amount' => 5001]);
            $this->fail('more than the register holds');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }
        $this->expectException(ValidationException::class);
        $this->cashier->transfer($theirs, $colleague, ['to_counter_id' => $mine->cash_counter_id, 'payment_method' => 1, 'amount' => 100]); // closed now
    }

    public function test_cumulative_journal_adds_every_register_and_hides_internal_transfers(): void
    {
        $other = CashCounter::create(['code' => 'C.PH', 'name' => 'Caisse pharmacie']);
        $colleague = User::unguarded(fn () => User::create(['first_name' => 'Ali', 'last_name' => 'Pharma', 'cash_counter_id' => $other->id]));
        $mine = $this->cashier->open($this->user, 0);
        $theirs = $this->cashier->open($colleague, 0);
        $this->cashier->collect($mine, $this->user, 7, ['opd_visit:1'], ['payment_method' => 1]);
        $this->cashier->collect($theirs, $colleague, 7, ['opd_visit:2'], ['payment_method' => 1]);
        $this->cashier->transfer($mine, $this->user, ['to_counter_id' => $other->id, 'payment_method' => 1, 'amount' => 1000]);
        $this->cashier->transfer($theirs, $colleague, ['destination' => 1, 'payment_method' => 1, 'amount' => 500]);

        $repo = app(\App\Repositories\CashJournalRepository::class);
        $journal = $repo->journal($repo->filters(['counter' => 'all'], $this->user));

        $this->assertSame(3, $journal['rows']->count(), '2 receipts + the transfer to the safe; the register-to-register transfer is internal');
        $this->assertSame(6742.0, $journal['closing'], '6 000 + 1 242 - 500');
    }

    public function test_follow_up_with_the_same_doctor_after_a_paid_consultation(): void
    {
        $input = ['patient_id' => 7, 'doctor_id' => 1, 'standard_charge' => 6000, 'appointment_date' => '2026-11-30 10:00'];
        $this->assertNull(\App\Models\OpdPatientDepartment::followupFactor($input), 'off by default');

        DB::table('settings')->insert([['key' => 'followup_mode', 'value' => 'free'], ['key' => 'receipt_followup_days', 'value' => '14'], ['key' => 'followup_percent', 'value' => '50']]);
        $this->assertNull(\App\Models\OpdPatientDepartment::followupFactor($input), 'nothing paid yet');

        $session = $this->cashier->open($this->user, 0);
        $this->cashier->collect($session, $this->user, 7, ['opd_visit:1'], ['payment_method' => 1]); // visit of 22/11 paid
        [, $split] = \App\Models\OpdPatientDepartment::splitFor($input);
        $this->assertSame([0.0, true, ['followup_free']], [$split['patient_amount'], $split['is_followup'], $split['warnings']]);

        DB::table('settings')->where('key', 'followup_mode')->update(['value' => 'percent']);
        $this->assertSame(0.5, \App\Models\OpdPatientDepartment::followupFactor($input));
        $this->assertNull(\App\Models\OpdPatientDepartment::followupFactor(['appointment_date' => '2026-12-07 10:00'] + $input), 'after 14 days');
        $this->assertNull(\App\Models\OpdPatientDepartment::followupFactor(['doctor_id' => 2] + $input), 'another doctor');
    }

    public function test_patient_flow_reports_from_the_receipts(): void
    {
        $session = $this->cashier->open($this->user, 0);
        $this->cashier->collect($session, $this->user, 7, ['opd_visit:1'], ['payment_method' => 1]);     // self-pay 6 000
        $b = $this->cashier->collect($session, $this->user, 7, ['opd_visit:2'], ['payment_method' => 1]); // insured, patient 1 242
        DB::table('opd_patient_departments')->where('id', 3)->update(['payer_amount' => 4000, 'patient_amount' => 1000]);
        $c = $this->cashier->collect($session, $this->user, 7, ['opd_visit:3'], ['payment_method' => 1]);
        $this->cashier->void($c, $this->user, 5);

        $repo = app(\App\Repositories\PatientFlowRepository::class);
        $report = fn ($tab) => $repo->report($repo->filters(['tab' => $tab, 'from' => now()->toDateString()]));

        $detailed = $report('detailed');
        $this->assertSame(3, $detailed['rows']->count(), 'cancelled receipts are listed, flagged');
        $this->assertSame(['valid', 'valid', 'voided'], $detailed['rows']->pluck('status')->all());
        $this->assertSame(7242.0, $detailed['totals']['paid'], 'totals count valid receipts only');
        $this->assertSame([1242.0], $report('patient_shares')['rows']->pluck('paid')->all());
        $this->assertSame([6000.0], $report('without_insurance')['rows']->pluck('paid')->all());
        $this->assertSame(['Médecine générale'], $report('by_service')['groups']->keys()->all());
    }

    public function test_payments_from_other_screens_get_a_receipt_on_the_open_session(): void
    {
        $this->assertNull($this->cashier->recordPayment($this->user, 7, 'advance_payment', 1, 'Avance', 20000, 1), 'no session, not required: no receipt');

        DB::table('settings')->insert(['key' => 'cashier_session_required', 'value' => '1']);
        try {
            $this->cashier->assertCanCollect($this->user);
            $this->fail('required without a session');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('cashier', $e->errors());
        }

        $session = $this->cashier->open($this->user, 0);
        $receipt = $this->cashier->recordPayment($this->user, 7, 'ipd_payment', 5, 'Paiement hospitalisation · IPD1', 50000, 1);
        $this->assertSame([117511, 50000.0, 'ipd_payment'], [$receipt->receipt_number, $receipt->amount, $receipt->lines->first()->payable_type]);
        $this->assertSame([1 => 50000.0], $session->fresh()->netByMethod());
        $this->assertTrue($this->cashier->unpaidItems(7)->every(fn ($i) => $i['type'] === 'opd_visit'), 'not an item to collect');
    }
}
