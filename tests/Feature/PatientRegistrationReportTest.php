<?php

namespace Tests\Feature;

use App\Models\MasterListItem;
use App\Repositories\PatientRegistrationReportRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PatientRegistrationReportTest extends TestCase
{
    private PatientRegistrationReportRepository $report;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-21 10:00:00');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->integer('gender')->default(0);
        });
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('patient_unique_id')->nullable();
            $table->unsignedInteger('id_document_type')->nullable();
            $table->string('id_document_number')->nullable();
            $table->timestamps();
        });
        Schema::create('doctor_departments', function (Blueprint $table) {
            $table->id();
            $table->string('title');
        });
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_department_id');
        });
        Schema::create('opd_patient_departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->dateTime('appointment_date');
            $table->timestamps();
        });
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
        DB::table('master_list_items')->insert(['type' => 'id_document_type', 'code' => 1, 'name' => 'National ID card']);
        MasterListItem::flushCache();

        // [first name, gender, registered at, ID]
        $people = [
            ['Old', 0, '2026-10-02 09:00', null],        // registered last month
            ['Awa', 1, '2026-11-03 09:00', 'A1'],
            ['Issa', 0, '2026-11-03 11:00', null],
            ['Mariam', 1, '2026-11-20 15:00', 'B2'],
        ];
        foreach ($people as [$name, $gender, $at, $id]) {
            $userId = DB::table('users')->insertGetId(['first_name' => $name, 'last_name' => 'Test', 'gender' => $gender]);
            DB::table('patients')->insert(['user_id' => $userId, 'patient_unique_id' => strtoupper($name), 'created_at' => $at,
                'id_document_type' => $id ? 1 : null, 'id_document_number' => $id]);
        }
        DB::table('doctor_departments')->insert([['title' => 'Pediatrics'], ['title' => 'General']]);
        DB::table('doctors')->insert([['doctor_department_id' => 1], ['doctor_department_id' => 2]]);
        DB::table('opd_patient_departments')->insert([
            ['patient_id' => 1, 'doctor_id' => 2, 'appointment_date' => '2026-11-05 10:00'], // returning
            ['patient_id' => 1, 'doctor_id' => 2, 'appointment_date' => '2026-11-12 10:00'], // same patient again
            ['patient_id' => 2, 'doctor_id' => 2, 'appointment_date' => '2026-11-03 10:00'], // new
            ['patient_id' => 4, 'doctor_id' => 1, 'appointment_date' => '2026-11-20 16:00'], // new
            ['patient_id' => 3, 'doctor_id' => 1, 'appointment_date' => '2026-10-30 10:00'], // outside the period
        ]);

        $this->report = app(PatientRegistrationReportRepository::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_default_period_is_the_current_month_and_swapped_dates_are_fixed(): void
    {
        $filters = $this->report->filters([]);
        $this->assertSame('2026-11-01', $filters['from']->toDateString());
        $this->assertSame('2026-11-21', $filters['to']->toDateString());

        $filters = $this->report->filters(['from' => '2026-11-20', 'to' => '2026-11-01', 'gender' => 'nonsense', 'id_document_type' => 99]);
        $this->assertSame('2026-11-01', $filters['from']->toDateString());
        $this->assertSame('2026-11-20', $filters['to']->toDateString());
        $this->assertSame('', $filters['gender']);
        $this->assertNull($filters['id_document_type']);
    }

    public function test_summary_counts_registrations_and_splits_visits_into_new_and_returning(): void
    {
        $summary = $this->report->summary($this->report->filters([]));

        $this->assertSame(3, $summary['total']);
        $this->assertSame(1, $summary['male']);
        $this->assertSame(2, $summary['female']);
        $this->assertSame(2, $summary['with_id']);
        $this->assertSame(['2026-11-03' => 2, '2026-11-20' => 1], $summary['by_day']->all());

        $this->assertSame(['visits' => 3, 'patients' => 2, 'new' => 1, 'returning' => 1], $summary['visits']['General']);
        $this->assertSame(['visits' => 1, 'patients' => 1, 'new' => 1, 'returning' => 0], $summary['visits']['Pediatrics']);
    }

    public function test_filters_narrow_the_list(): void
    {
        $women = $this->report->patients($this->report->filters(['gender' => 'female']))->pluck('patient_unique_id')->all();
        $this->assertSame(['MARIAM', 'AWA'], $women);

        $withId = $this->report->patients($this->report->filters(['id_document_type' => 1]))->count();
        $this->assertSame(2, $withId);
    }
}
