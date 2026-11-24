<?php

use App\Models\FrontSetting;
use Illuminate\Database\Migrations\Migration;

/** Home page video block plays an uploaded video (muted, looping) instead of a YouTube link. */
return new class extends Migration
{
    public function up(): void
    {
        FrontSetting::firstOrCreate(['key' => FrontSetting::VIDEO_KEY], ['value' => '', 'type' => FrontSetting::HOME_PAGE]);
    }

    public function down(): void
    {
        FrontSetting::where('key', FrontSetting::VIDEO_KEY)->delete();
    }
};
