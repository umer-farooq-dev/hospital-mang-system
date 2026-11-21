<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Webcam at reception (patient photo, hospital card scan), on by default;
     * the admin switches it off in Settings > Reception.
     */
    public function up(): void
    {
        if (! DB::table('settings')->where('key', 'reception_webcam')->exists()) {
            DB::table('settings')->insert(['key' => 'reception_webcam', 'value' => '1', 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'reception_webcam')->delete();
    }
};
