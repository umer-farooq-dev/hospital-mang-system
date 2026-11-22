<?php

namespace Tests\Feature;

use App\Models\MasterListItem;
use App\Models\Patient;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PatientRegistrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->date('dob')->nullable();
        });
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('patient_unique_id')->nullable();
            $table->unsignedInteger('id_document_type')->nullable();
            $table->string('id_document_number')->nullable();
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
        (require base_path('database/migrations/2026_11_20_120000_create_audit_logs_table.php'))->up();

        DB::table('master_list_items')->insert([
            ['type' => 'id_document_type', 'code' => 1, 'name' => 'National ID card', 'sort_order' => 1, 'is_active' => true],
            ['type' => 'id_document_type', 'code' => 2, 'name' => 'Passport', 'sort_order' => 2, 'is_active' => true],
        ]);
        $this->settings([
            'patient_required_fields' => 'phone',
            'patient_portal_login' => '1',
            'patient_duplicate_check' => 'warn',
            'patient_duplicate_fields' => 'id_document,phone,name_dob',
        ]);

        $userId = DB::table('users')->insertGetId(['first_name' => 'Ismael', 'last_name' => 'Kabore', 'phone' => '+22676584902', 'dob' => '1990-05-04']);
        DB::table('patients')->insert(['user_id' => $userId, 'patient_unique_id' => 'P0001', 'id_document_type' => 1, 'id_document_number' => 'B1234567']);
    }

    private function settings(array $values): void
    {
        foreach ($values as $key => $value) {
            DB::table('settings')->updateOrInsert(['key' => $key], ['value' => $value]);
        }
        Patient::flushRegistrationSettings();
        MasterListItem::flushCache();
    }

    private function fails(array $input, ?int $userId = null): array
    {
        $base = ['first_name' => 'Awa', 'last_name' => 'Ouedraogo', 'gender' => 1, 'phone' => '70000000'];

        return Validator::make(array_merge($base, $input), Patient::registrationRules($userId))->errors()->keys();
    }

    public function test_walk_in_patient_needs_no_email_or_password(): void
    {
        $this->assertSame([], $this->fails([]));
        // A password is only asked together with an e-mail, for portal login.
        $this->assertSame(['password'], $this->fails(['email' => 'awa@example.com']));
        $this->assertSame([], $this->fails(['email' => 'awa@example.com', 'password' => 'secret12', 'password_confirmation' => 'secret12']));
        $this->assertSame(['password'], $this->fails(['email' => 'awa@example.com', 'password' => 'secret1', 'password_confirmation' => 'secret1']), 'at least 8 characters');
        // The phone stays required by default.
        $this->assertSame(['phone'], $this->fails(['phone' => '']));
    }

    public function test_admin_controls_required_fields_and_portal_login(): void
    {
        $this->settings(['patient_required_fields' => 'email,id_document', 'patient_portal_login' => '0']);

        $this->assertEqualsCanonicalizing(['email', 'id_document_type', 'id_document_number'], $this->fails(['phone' => '']));
        // Portal login off: no password rule at all.
        $this->assertSame([], $this->fails(['email' => 'awa@example.com', 'id_document_type' => 2, 'id_document_number' => 'X1']));
    }

    public function test_id_type_and_number_go_together_and_type_comes_from_the_master_list(): void
    {
        $this->assertSame(['id_document_type'], $this->fails(['id_document_number' => 'X1']));
        $this->assertSame(['id_document_number'], $this->fails(['id_document_type' => 1]));
        $this->assertSame(['id_document_type'], $this->fails(['id_document_type' => 99, 'id_document_number' => 'X1']));
    }

    public function test_duplicates_are_found_by_the_configured_fields(): void
    {
        $this->assertCount(1, Patient::findDuplicates(['id_document_number' => ' b123 4567 ']));
        $this->assertCount(1, Patient::findDuplicates(['phone' => '76584902', 'prefix_code' => '226']));
        $this->assertCount(1, Patient::findDuplicates(['first_name' => 'Ismael', 'last_name' => 'Kabore', 'dob' => '1990-05-04']));
        $this->assertCount(0, Patient::findDuplicates(['first_name' => 'Ismael', 'last_name' => 'Kabore', 'dob' => '1991-05-04']));
        // Editing the same patient is not a duplicate of itself.
        $this->assertCount(0, Patient::findDuplicates(['id_document_number' => 'B1234567'], 1));

        $this->settings(['patient_duplicate_fields' => 'name_dob']);
        $this->assertCount(0, Patient::findDuplicates(['id_document_number' => 'B1234567']));
    }

    public function test_block_mode_refuses_a_duplicate_and_warn_mode_does_not(): void
    {
        $input = ['first_name' => 'X', 'last_name' => 'Y', 'gender' => 0, 'phone' => '70000000', 'id_document_type' => 1, 'id_document_number' => 'B1234567'];

        $validator = Validator::make($input, Patient::registrationRules());
        $validator->after(fn ($v) => Patient::blockDuplicates($v, $input));
        $this->assertTrue($validator->passes());

        $this->settings(['patient_duplicate_check' => 'block']);
        $validator = Validator::make($input, Patient::registrationRules());
        $validator->after(fn ($v) => Patient::blockDuplicates($v, $input));
        $this->assertFalse($validator->passes());
        $this->assertStringContainsString('P0001', $validator->errors()->first('duplicate'));
    }

    public function test_id_number_is_stored_normalised(): void
    {
        $patient = new Patient(['id_document_type' => '', 'id_document_number' => ' ab 12 ']);
        $this->assertNull($patient->id_document_type);
        $this->assertSame('AB12', $patient->id_document_number);
    }
}
