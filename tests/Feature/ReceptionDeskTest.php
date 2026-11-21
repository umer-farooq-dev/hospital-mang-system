<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\PatientQueue;
use App\Repositories\ReceptionDeskRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReceptionDeskTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
        });
        Schema::create('insurances', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
        Schema::create('patient_insurances', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('patient_id');
            $table->unsignedInteger('insurance_id');
            $table->unsignedTinyInteger('priority')->default(1);
            $table->timestamps();
        });
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('patient_unique_id')->nullable();
            $table->unsignedInteger('id_document_type')->nullable();
            $table->string('id_document_number')->nullable();
            $table->timestamps();
        });
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->morphs('model');
            $table->string('collection_name');
        });
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->dateTime('opd_date');
            $table->integer('is_completed')->default(0);
            $table->timestamps();
        });
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('value')->nullable();
            $table->timestamps();
        });
        Schema::create('patient_queues', function (Blueprint $table) {
            $table->id();
            $table->integer('no');
            $table->unsignedBigInteger('appointment_id');
            $table->timestamps();
        });
        (require base_path('database/migrations/2026_11_20_120000_create_audit_logs_table.php'))->up();

        foreach ([['Ismael', 'Kabore', '+22676584902', 'GCVFKLMI', 'B1234567'], ['Aminata', 'Ouedraogo', '+22670000000', 'HQWERTYU', null]] as $i => $p) {
            $userId = DB::table('users')->insertGetId(['first_name' => $p[0], 'last_name' => $p[1], 'phone' => $p[2]]);
            DB::table('patients')->insert(['user_id' => $userId, 'patient_unique_id' => $p[3], 'id_document_type' => $p[4] ? 1 : null, 'id_document_number' => $p[4]]);
        }
    }

    private function found(string $term): array
    {
        return app(ReceptionDeskRepository::class)->search($term)->pluck('patient_unique_id')->all();
    }

    public function test_search_finds_a_patient_every_way_reception_knows_them(): void
    {
        $this->assertSame(['GCVFKLMI'], $this->found('kabore ismael'));   // words in any order
        $this->assertSame(['GCVFKLMI'], $this->found('Isma'));
        $this->assertSame(['GCVFKLMI'], $this->found('76 58 49 02'));     // phone, spaces ignored
        $this->assertSame(['GCVFKLMI'], $this->found('b123 4567'));       // ID number, normalised
        $this->assertSame(['HQWERTYU'], $this->found('HQWERTYU'));        // patient number
        $this->assertSame(['HQWERTYU'], $this->found('http://hms.test/qr-code/p/HQWERTYU')); // scanned card
        $this->assertSame([], $this->found('k'));                         // too short
        $this->assertSame([], $this->found('nobody'));
    }

    public function test_queue_tokens_restart_each_day_and_a_checked_in_appointment_keeps_its_token(): void
    {
        $yesterday = Appointment::create(['patient_id' => 1, 'opd_date' => now()->subDay(), 'is_completed' => 0]);
        Carbon::setTestNow(now()->subDay());
        $this->assertSame(1, PatientQueue::enqueue($yesterday)->no);
        $this->assertSame(1, PatientQueue::enqueue($yesterday)->no, 'queued twice keeps one token');
        Carbon::setTestNow();

        $first = Appointment::create(['patient_id' => 1, 'opd_date' => now(), 'is_completed' => 0]);
        $second = Appointment::create(['patient_id' => 2, 'opd_date' => now(), 'is_completed' => 0]);
        $this->assertSame(1, PatientQueue::enqueue($first)->no);
        $this->assertSame(2, PatientQueue::enqueue($second)->no);
        $this->assertSame(Appointment::STATUS_IN_QUEUE, $first->fresh()->is_completed);
        $this->assertSame(3, PatientQueue::count());
    }

    public function test_queue_numbers_follow_the_cards_and_skip_a_card_still_held(): void
    {
        DB::table('settings')->insert(['key' => 'queue_card_count', 'value' => '3']);
        $visit = fn () => Appointment::create(['patient_id' => 1, 'opd_date' => now(), 'is_completed' => 0]);

        $a = $visit();
        $this->assertSame([1, 2, 3], [PatientQueue::enqueue($a)->no, PatientQueue::enqueue($visit())->no, PatientQueue::enqueue($visit())->no]);

        // Card 1 comes back (consultation done); cards 2 and 3 are still held.
        $a->update(['is_completed' => Appointment::STATUS_COMPLETED]);
        $this->assertSame(1, PatientQueue::enqueue($visit())->no, 'goes round to the free card');

        // Every card is out: the number carries on above the cards.
        $this->assertSame(4, PatientQueue::enqueue($visit())->no);
    }

    public function test_without_cards_numbers_simply_count_up(): void
    {
        DB::table('settings')->insert(['key' => 'queue_card_count', 'value' => '0']);
        $first = Appointment::create(['patient_id' => 1, 'opd_date' => now(), 'is_completed' => 0]);
        PatientQueue::enqueue($first);
        $first->update(['is_completed' => Appointment::STATUS_COMPLETED]);

        $this->assertSame(2, PatientQueue::enqueue(Appointment::create(['patient_id' => 1, 'opd_date' => now(), 'is_completed' => 0]))->no);
    }
}
