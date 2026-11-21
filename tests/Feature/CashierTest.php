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
        Schema::create('doctors', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('doctor_department_id')->nullable();
        });
        Schema::create('doctor_departments', function (Blueprint $t) {
            $t->id();
            $t->string('title');
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
        });
        (require base_path('database/migrations/2026_11_20_120000_create_audit_logs_table.php'))->up();

        $counter = CashCounter::create(['code' => 'C04', 'name' => 'Caisse accueil']);
        $this->user = User::unguarded(fn () => User::create(['first_name' => 'Awa', 'last_name' => 'Caissière', 'cash_counter_id' => $counter->id]));
        DB::table('settings')->insert([['key' => 'receipt_start_number', 'value' => '117511'], ['key' => 'cash_payment_method', 'value' => '1']]);
        $doctorUser = DB::table('users')->insertGetId(['first_name' => 'Dr', 'last_name' => 'Ouattara']);
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
        $this->assertSame([1, 2], $this->cashier->unpaidVisits(7)->pluck('id')->all(), 'a visit fully paid by the insurer is not listed');

        $receipt = $this->cashier->collect($session, $this->user, 7, [1, 2], ['payment_method' => 1, 'amount_received' => 10000]);

        $this->assertSame([117511, 7242.0, 2758.0], [$receipt->receipt_number, $receipt->amount, $receipt->change_given]);
        $this->assertSame([6000.0, 1242.0], $receipt->lines->pluck('amount')->all());
        $this->assertTrue($this->cashier->unpaidVisits(7)->isEmpty(), 'nothing left to pay');

        DB::table('opd_patient_departments')->where('id', 3)->update(['payer_amount' => 4000, 'patient_amount' => 1000]);
        $next = $this->cashier->collect($session, $this->user, 7, [3], ['payment_method' => 2]);
        $this->assertSame([117512, 1000.0, 0.0], [$next->receipt_number, $next->amount, $next->change_given]);
    }

    public function test_collect_refuses_nothing_selected_or_too_little_cash(): void
    {
        $session = $this->cashier->open($this->user, 0);

        try {
            $this->cashier->collect($session, $this->user, 7, [3], ['payment_method' => 1]);
            $this->fail('nothing to pay on visit 3');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('visits', $e->errors());
        }

        $this->expectException(ValidationException::class);
        $this->cashier->collect($session, $this->user, 7, [1], ['payment_method' => 1, 'amount_received' => 5000]);
    }

    public function test_close_gives_the_next_closing_number_and_expected_cash(): void
    {
        $session = $this->cashier->open($this->user, 10000);
        $this->assertSame($session->id, $this->cashier->open($this->user, 0)->id, 'one open session per register');
        $this->cashier->collect($session, $this->user, 7, [1], ['payment_method' => 1]);   // cash 6 000
        $this->cashier->collect($session, $this->user, 7, [2], ['payment_method' => 2]);   // cheque 1 242

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
}
