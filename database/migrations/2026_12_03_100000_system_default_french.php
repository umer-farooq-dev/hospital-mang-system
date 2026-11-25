<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The InfyHMS seed wrote "default_lang" twice: Settings saved the first row while the app read the last one,
 * so the default language could never be changed. Keep one row per key (the one the Settings screen edits),
 * forbid duplicates, and start the whole system in French (English stays available).
 */
return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('settings')->select('key', DB::raw('MIN(id) as keep'))->groupBy('key')->havingRaw('COUNT(*) > 1')->get();
        foreach ($duplicates as $row) {
            DB::table('settings')->where('key', $row->key)->where('id', '!=', $row->keep)->delete();
        }
        Schema::table('settings', fn (Blueprint $table) => $table->unique('key'));

        $lang = Setting::where('key', 'default_lang')->first();
        if ($lang && $lang->value === 'en') {
            $lang->update(['value' => 'fr']);
        }
    }

    public function down(): void
    {
        Schema::table('settings', fn (Blueprint $table) => $table->dropUnique(['key']));
    }
};
