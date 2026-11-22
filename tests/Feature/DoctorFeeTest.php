<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\DoctorFeePlan;
use App\Models\DoctorStatement;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Tests\TestCase;

class DoctorFeeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $tables = [
            'settings' => fn ($t) => [$t->string('key'), $t->text('value')->nullable(), $t->timestamps()],
            'users' => fn ($t) => [$t->string('first_name')->nullable(), $t->string('last_name')->nullable()],
            'patients' => fn ($t) => [$t->unsignedBigInteger('user_id')],
            'doctors' => fn ($t) => [$t->unsignedBigInteger('user_id')->nullable()],
            'doctor_opd_charges' => fn ($t) => [$t->unsignedBigInteger('doctor_id'), $t->double('standard_charge')->default(0), $t->timestamps()],
            'price_lists' => fn ($t) => [$t->string('name'), $t->boolean('is_default')->default(false), $t->unsignedInteger('sort_order')->default(0), $t->timestamps()],
            'item_prices' => fn ($t) => [$t->string('priceable_type'), $t->unsignedBigInteger('priceable_id'), $t->unsignedBigInteger('price_list_id'),
                $t->decimal('price', 15, 2)->nullable(), $t->decimal('doctor_share_percent', 5, 2)->nullable(), $t->decimal('assistant_share_percent', 5, 2)->nullable(), $t->timestamps()],
            'opd_patient_departments' => fn ($t) => [$t->unsignedInteger('patient_id'), $t->unsignedBigInteger('doctor_id'), $t->unsignedBigInteger('price_list_id')->nullable(),
                $t->dateTime('appointment_date'), $t->double('standard_charge')],
            'exam_orders' => fn ($t) => [$t->unsignedBigInteger('price_list_id')->nullable()],
            'exam_order_items' => fn ($t) => [$t->unsignedBigInteger('exam_order_id'), $t->string('testable_type'), $t->unsignedBigInteger('testable_id'), $t->string('description'),
                $t->decimal('total', 15, 2), $t->unsignedBigInteger('validated_by')->nullable(), $t->dateTime('validated_at')->nullable(), $t->timestamps()],
            'operations' => fn ($t) => [$t->string('name')],
            'ipd_operation' => fn ($t) => [$t->unsignedBigInteger('operation_id')->nullable(), $t->date('operation_date'), $t->unsignedBigInteger('doctor_id'),
                $t->unsignedBigInteger('assistant_doctor_id')->nullable(), $t->unsignedBigInteger('anaesthetist_doctor_id')->nullable(), $t->unsignedInteger('status')->nullable(),
                $t->decimal('surgeon_fee', 14, 2)->default(0), $t->decimal('assistant_fee', 14, 2)->default(0), $t->decimal('anaesthetist_fee', 14, 2)->default(0), $t->timestamps()],
            'doctor_fee_plans' => fn ($t) => [$t->unsignedBigInteger('doctor_id'), $t->string('pay_type')->default('share'), $t->decimal('rate', 14, 2)->default(0),
                $t->boolean('withholding_exempt')->default(false), $t->timestamps()],
            'doctor_statements' => fn ($t) => [$t->string('number'), $t->unsignedBigInteger('doctor_id'), $t->date('period_from'), $t->date('period_to'), $t->string('pay_type'),
                $t->decimal('hours', 8, 2)->nullable(), $t->decimal('gross', 15, 2)->default(0), $t->decimal('withholding', 15, 2)->default(0), $t->decimal('net', 15, 2)->default(0),
                $t->string('status'), $t->date('paid_on')->nullable(), $t->unsignedInteger('payment_method')->nullable(), $t->string('payment_reference')->nullable(),
                $t->string('note')->nullable(), $t->unsignedBigInteger('created_by')->nullable(), $t->unsignedBigInteger('validated_by')->nullable(), $t->dateTime('validated_at')->nullable(), $t->timestamps()],
            'doctor_statement_lines' => fn ($t) => [$t->unsignedBigInteger('doctor_statement_id'), $t->string('kind'), $t->string('source_type')->nullable(), $t->unsignedBigInteger('source_id')->nullable(),
                $t->date('line_date')->nullable(), $t->string('description'), $t->decimal('base_amount', 15, 2)->default(0), $t->decimal('percent', 6, 2)->nullable(),
                $t->decimal('amount', 15, 2)->default(0), $t->timestamps()],
        ];
        foreach ($tables as $name => $columns) {
            Schema::create($name, function (Blueprint $t) use ($columns) {
                $t->id();
                $columns($t);
            });
        }
        DB::table('users')->insert([['id' => 1, 'first_name' => 'Dr', 'last_name' => 'Kaboré'], ['id' => 2, 'first_name' => 'Awa', 'last_name' => 'Patient']]);
        DB::table('patients')->insert(['id' => 1, 'user_id' => 2]);
        DB::table('doctors')->insert([['id' => 1, 'user_id' => 1], ['id' => 2, 'user_id' => null]]);
        DB::table('doctor_opd_charges')->insert(['id' => 1, 'doctor_id' => 1, 'standard_charge' => 6000]);
        DB::table('price_lists')->insert(['id' => 1, 'name' => 'Cash', 'is_default' => true]);
        DB::table('item_prices')->insert(['priceable_type' => 'doctor_opd_charge', 'priceable_id' => 1, 'price_list_id' => 1, 'doctor_share_percent' => 40]);
        DB::table('opd_patient_departments')->insert([
            ['patient_id' => 1, 'doctor_id' => 1, 'price_list_id' => 1, 'appointment_date' => '2026-11-03 09:00', 'standard_charge' => 6000],
            ['patient_id' => 1, 'doctor_id' => 1, 'price_list_id' => 1, 'appointment_date' => '2026-11-20 09:00', 'standard_charge' => 5000],
            ['patient_id' => 1, 'doctor_id' => 1, 'price_list_id' => 1, 'appointment_date' => '2026-12-01 09:00', 'standard_charge' => 6000],
        ]);
        DB::table('operations')->insert(['id' => 1, 'name' => 'Césarienne']);
        DB::table('ipd_operation')->insert(['operation_id' => 1, 'operation_date' => '2026-11-10', 'doctor_id' => 2, 'assistant_doctor_id' => 1, 'surgeon_fee' => 90000, 'assistant_fee' => 20000]);
        \App\Models\Setting::create(['key' => 'doctor_withholdings', 'value' => json_encode([['name' => 'BIC', 'percent' => 10], ['name' => 'Taxe', 'percent' => 1]])]);
    }

    public function test_share_plan_takes_the_act_share_adds_operation_fees_and_withholds(): void
    {
        $doctor = Doctor::find(1);
        $statement = DoctorStatement::open($doctor, Carbon::parse('2026-11-01'), Carbon::parse('2026-11-30'), null, 5000, 'Garde', 1);

        // 40% of 6 000 and 5 000, assistant fee 20 000, other 5 000 = 2 400 + 2 000 + 20 000 + 5 000
        $this->assertEquals(29400, $statement->gross);
        $this->assertEquals(2940 + 294, $statement->withholding);
        $this->assertEquals(29400 - 3234, $statement->net);
        $this->assertSame(['consultation', 'consultation', 'operation', 'other', 'withholding', 'withholding'], $statement->lines()->pluck('kind')->all());
        $this->assertSame('HD-'.now()->year.'-0001', $statement->number);

        try {
            DoctorStatement::open($doctor, Carbon::parse('2026-11-15'), Carbon::parse('2026-12-15'), null, 0, null, 1);
            $this->fail('overlapping days are paid once');
        } catch (UnprocessableEntityHttpException $e) {
        }
    }

    public function test_other_plans_and_the_statement_steps(): void
    {
        $doctor = Doctor::find(1);
        DoctorFeePlan::create(['doctor_id' => 1, 'pay_type' => 'per_patient', 'rate' => 1500, 'withholding_exempt' => true]);
        $statement = DoctorStatement::open($doctor, Carbon::parse('2026-11-01'), Carbon::parse('2026-11-30'), null, 0, null, 1);
        $this->assertEquals(1500 * 2 + 20000, $statement->net, 'two patients, the assistant fee, no withholding');

        DoctorFeePlan::where('doctor_id', 1)->update(['pay_type' => 'monthly', 'rate' => 300000]);
        $statement->recompute();
        $this->assertEquals(300000 + 20000, $statement->fresh()->net, 'a draft follows the new plan');

        $statement->validateStatement(1);
        try {
            $statement->fresh()->recompute();
            $this->fail('a validated statement is frozen');
        } catch (UnprocessableEntityHttpException $e) {
        }
        $statement->fresh()->markPaid('2026-12-05', null, 'VIR-12');
        $this->assertSame('paid', $statement->fresh()->status);

        DoctorFeePlan::where('doctor_id', 1)->update(['pay_type' => 'hourly', 'rate' => 10000]);
        $hours = DoctorStatement::compute($doctor, Carbon::parse('2026-12-01'), Carbon::parse('2026-12-31'), 12.5);
        $this->assertEquals(125000, collect($hours)->where('kind', 'hours')->sum('amount'));
        $this->assertSame(2, (int) Carbon::parse('2026-11-15')->startOfMonth()->diffInMonths(Carbon::parse('2027-01-10')->startOfMonth()));
    }
}
