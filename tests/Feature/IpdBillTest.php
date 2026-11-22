<?php

namespace Tests\Feature;

use App\Models\IpdPatientDepartment;
use App\Repositories\IpdBillRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IpdBillTest extends TestCase
{
    public function test_bed_units_per_day_night_or_hour(): void
    {
        $in = Carbon::parse('2026-11-20 22:00');
        $out = Carbon::parse('2026-11-23 09:00'); // 2 days 11 hours, 3 midnights

        $this->assertSame(3, IpdBillRepository::bedUnits($in, $out, 'day'), 'a started day counts');
        $this->assertSame(3, IpdBillRepository::bedUnits($in, $out, 'night'));
        $this->assertSame(59, IpdBillRepository::bedUnits($in, $out, 'hour'));
        $this->assertSame(1, IpdBillRepository::bedUnits($in, $in->copy()->addMinutes(30), 'night'), 'at least one unit');
        $this->assertSame(1, IpdBillRepository::bedUnits($in, $in->copy()->subHour(), 'day'), 'end before start');
    }

    public function test_net_payable_taxes_the_charges_and_deducts_cover_payments_and_advances(): void
    {
        // 3 bed days at 10 000 + 20 000 of charges; 10% tax; insurer covers 40 000; 5 000 paid; 2 000 advance.
        $net = IpdBillRepository::netPayable(['charges' => 20000, 'bed' => 30000, 'other' => 1000, 'discount_percent' => 0,
            'tax_percent' => 10, 'cover' => 40000, 'payments' => 5000, 'advances' => 2000]);

        $this->assertSame(9000.0, $net, '50 000 + 1 000 + 5 000 tax - 40 000 - 5 000 - 2 000');
    }

    public function test_unpaid_pharmacy_bills_of_the_stay_go_on_one_admission_bill(): void
    {
        Schema::create('ipd_patient_departments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('patient_id');
            $t->dateTime('admission_date');
            $t->timestamps();
        });
        Schema::create('medicine_bills', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('patient_id');
            $t->unsignedBigInteger('ipd_patient_department_id')->nullable();
            $t->string('bill_number');
            $t->decimal('net_amount', 12, 2);
            $t->integer('payment_status');
            $t->dateTime('bill_date');
            $t->timestamps();
        });
        DB::table('ipd_patient_departments')->insert([['id' => 1, 'patient_id' => 7, 'admission_date' => '2026-11-20 08:00'], ['id' => 2, 'patient_id' => 7, 'admission_date' => '2026-12-10 08:00']]);
        DB::table('medicine_bills')->insert([
            ['patient_id' => 7, 'bill_number' => 'M1', 'net_amount' => 4000, 'payment_status' => 0, 'bill_date' => '2026-11-21 10:00'], // during the stay
            ['patient_id' => 7, 'bill_number' => 'M2', 'net_amount' => 900, 'payment_status' => 1, 'bill_date' => '2026-11-21 11:00'],  // paid at the pharmacy
            ['patient_id' => 7, 'bill_number' => 'M3', 'net_amount' => 700, 'payment_status' => 0, 'bill_date' => '2026-11-10 09:00'],  // before admission
        ]);

        $this->assertSame(['M1'], IpdBillRepository::medicineBillsFor(IpdPatientDepartment::find(1))->pluck('bill_number')->all());
        $this->assertSame(1, IpdBillRepository::claimMedicineBills(1));
        $this->assertSame([], IpdBillRepository::medicineBillsFor(IpdPatientDepartment::find(2))->pluck('bill_number')->all(), 'billed once');
        $this->assertSame(['M1'], IpdBillRepository::medicineBillsFor(IpdPatientDepartment::find(1))->pluck('bill_number')->all());
    }

    public function test_an_advance_paid_before_admission_goes_on_one_bill_only(): void
    {
        Schema::create('ipd_patient_departments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('patient_id');
            $t->timestamps();
        });
        Schema::create('advanced_payments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('patient_id');
            $t->unsignedBigInteger('ipd_patient_department_id')->nullable();
            $t->decimal('amount', 12, 2);
            $t->timestamps();
        });
        DB::table('ipd_patient_departments')->insert([['id' => 1, 'patient_id' => 7], ['id' => 2, 'patient_id' => 7]]);
        DB::table('advanced_payments')->insert([
            ['patient_id' => 7, 'ipd_patient_department_id' => null, 'amount' => 50000], // paid before the surgery
            ['patient_id' => 7, 'ipd_patient_department_id' => 1, 'amount' => 3000],    // already on admission 1
            ['patient_id' => 9, 'ipd_patient_department_id' => null, 'amount' => 999],  // another patient
        ]);
        $first = IpdPatientDepartment::find(1);
        $second = IpdPatientDepartment::find(2);

        $this->assertSame(53000.0, (float) IpdBillRepository::advancesFor($first)->sum('amount'));
        $this->assertSame(1, IpdBillRepository::claimAdvances(1));
        $this->assertSame(0.0, (float) IpdBillRepository::advancesFor($second)->sum('amount'), 'a claimed advance is not counted twice');
        $this->assertSame(53000.0, (float) IpdBillRepository::advancesFor($first)->sum('amount'));
    }
}
