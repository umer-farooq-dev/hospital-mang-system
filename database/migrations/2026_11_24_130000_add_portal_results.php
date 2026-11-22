<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Patients see their released results in the portal only when the admin allows it. */
    public function up(): void
    {
        if (! DB::table('settings')->where('key', 'portal_show_results')->exists()) {
            DB::table('settings')->insert(['key' => 'portal_show_results', 'value' => '0', 'created_at' => now(), 'updated_at' => now()]);
        }
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'portal_show_results')->delete();
    }
};
