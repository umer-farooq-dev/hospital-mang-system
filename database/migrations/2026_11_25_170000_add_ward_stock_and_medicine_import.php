<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** A ward store serves a bed type: doses marked given on the MAR can be taken from it (off by default). */
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->unsignedInteger('bed_type_id')->nullable()->after('store_type');
        });
        $now = now();
        foreach (['mar_auto_issue' => '0', 'mar_given_status' => '1'] as $key => $value) {
            if (! DB::table('settings')->where('key', $key)->exists()) {
                DB::table('settings')->insert(['key' => $key, 'value' => $value, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', ['mar_auto_issue', 'mar_given_status'])->delete();
        Schema::table('stores', fn (Blueprint $table) => $table->dropColumn('bed_type_id'));
    }
};
