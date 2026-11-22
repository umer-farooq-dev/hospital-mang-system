<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Proposed birth and death certificate templates (the client asked us to propose them). */
    public function up(): void
    {
        $order = (int) DB::table('certificate_templates')->max('sort_order');
        foreach (['birth', 'death'] as $i => $key) {
            if (! DB::table('certificate_templates')->where('lang_key', 'messages.certificate.templates.' . $key)->exists()) {
                DB::table('certificate_templates')->insert(['name' => ucfirst($key) . ' certificate', 'lang_key' => 'messages.certificate.templates.' . $key,
                    'body' => null, 'sort_order' => $order + $i + 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        DB::table('certificate_templates')->whereIn('lang_key', ['messages.certificate.templates.birth', 'messages.certificate.templates.death'])->delete();
    }
};
