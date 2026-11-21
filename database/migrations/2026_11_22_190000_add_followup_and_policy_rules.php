<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Follow-up visit rule (client sample: receipt valid 14 days for the same doctor) and what
     * to do when a policy is not valid. Follow-up stays off until the client confirms it.
     */
    public function up(): void
    {
        Schema::table('opd_patient_departments', function (Blueprint $table) {
            $table->boolean('is_followup')->default(false)->after('patient_amount');
        });
        $now = now();
        foreach (['followup_mode' => 'off', 'followup_percent' => '0', 'policy_expired_mode' => 'self_pay'] as $key => $value) {
            if (! DB::table('settings')->where('key', $key)->exists()) {
                DB::table('settings')->insert(['key' => $key, 'value' => $value, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', ['followup_mode', 'followup_percent', 'policy_expired_mode'])->delete();
        Schema::table('opd_patient_departments', function (Blueprint $table) {
            $table->dropColumn('is_followup');
        });
    }
};
