<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * From the old program: reception hands out reusable numbered cards for the queue, and
     * records where a patient comes from, the emergency level and who referred them.
     */
    public function up(): void
    {
        Schema::table('opd_patient_departments', function (Blueprint $table) {
            $table->unsignedInteger('patient_origin')->nullable()->after('payment_mode');
            $table->unsignedInteger('emergency_level')->nullable()->after('patient_origin');
            $table->unsignedInteger('referrer')->nullable()->after('emergency_level');
        });

        $now = now();
        $lists = [
            'patient_origin' => [
                [1, 'Self-referred', 'messages.opd_patient.origins.self'],
                [2, 'Referred', 'messages.opd_patient.origins.referred'],
                [3, 'Evacuated', 'messages.opd_patient.origins.evacuated'],
                [4, 'Transferred', 'messages.opd_patient.origins.transferred'],
            ],
            'emergency_level' => [
                [1, 'Not urgent', 'messages.opd_patient.emergency_levels.not_urgent'],
                [2, 'Urgent', 'messages.opd_patient.emergency_levels.urgent'],
                [3, 'Life-threatening', 'messages.opd_patient.emergency_levels.vital'],
            ],
        ];
        foreach ($lists as $type => $items) {
            foreach ($items as $i => [$code, $name, $langKey]) {
                DB::table('master_list_items')->insertOrIgnore([
                    'type' => $type, 'code' => $code, 'name' => $name, 'lang_key' => $langKey,
                    'sort_order' => $i + 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        // 0 = no cards: tokens simply count up, as before.
        if (! DB::table('settings')->where('key', 'queue_card_count')->exists()) {
            DB::table('settings')->insert(['key' => 'queue_card_count', 'value' => '0', 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'queue_card_count')->delete();
        DB::table('master_list_items')->whereIn('type', ['patient_origin', 'emergency_level', 'referrer'])->delete();
        Schema::table('opd_patient_departments', function (Blueprint $table) {
            $table->dropColumn(['patient_origin', 'emergency_level', 'referrer']);
        });
    }
};
