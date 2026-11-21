<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reception registers walk-in patients who often have no e-mail, identifies
     * them by an ID document, and the admin decides which fields are required.
     */
    public function up(): void
    {
        // E-mail stays unique when given; MySQL allows many NULLs under a unique index.
        Schema::table('users', function (Blueprint $table) {
            $table->string('email', 100)->nullable()->change();
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->unsignedInteger('id_document_type')->nullable()->after('patient_unique_id');
            $table->string('id_document_number', 50)->nullable()->after('id_document_type');
            $table->index('id_document_number');
        });

        $now = now();
        $types = [
            [1, 'National ID card', 'messages.reception.id_types.national_id'],
            [2, 'Passport', 'messages.reception.id_types.passport'],
            [3, 'Driving licence', 'messages.reception.id_types.driving_licence'],
        ];
        foreach ($types as $i => [$code, $name, $langKey]) {
            DB::table('master_list_items')->insertOrIgnore([
                'type' => 'id_document_type', 'code' => $code, 'name' => $name, 'lang_key' => $langKey,
                'sort_order' => $i + 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // Defaults keep today's behaviour (phone asked, portal login possible) and add a duplicate warning.
        $settings = [
            'patient_required_fields' => 'phone',
            'patient_portal_login' => '1',
            'patient_duplicate_check' => 'warn',
            'patient_duplicate_fields' => 'id_document,phone,name_dob',
        ];
        foreach ($settings as $key => $value) {
            if (! DB::table('settings')->where('key', $key)->exists()) {
                DB::table('settings')->insert(['key' => $key, 'value' => $value, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'patient_required_fields', 'patient_portal_login', 'patient_duplicate_check', 'patient_duplicate_fields',
        ])->delete();
        DB::table('master_list_items')->where('type', 'id_document_type')->delete();

        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex(['id_document_number']);
            $table->dropColumn(['id_document_type', 'id_document_number']);
        });

        // Rows without an e-mail must be given one before the column can be NOT NULL again.
        Schema::table('users', function (Blueprint $table) {
            $table->string('email', 100)->nullable(false)->change();
        });
    }
};
