<?php

use App\Models\FrontSetting;
use Illuminate\Database\Migrations\Migration;

/** Website languages: French by default, English as the second one (admin can change both). */
return new class extends Migration
{
    public function up(): void
    {
        FrontSetting::firstOrCreate(['key' => 'website_default_lang'], ['value' => 'fr', 'type' => FrontSetting::HOME_PAGE]);
        FrontSetting::firstOrCreate(['key' => 'website_languages'], ['value' => 'fr,en', 'type' => FrontSetting::HOME_PAGE]);
    }

    public function down(): void
    {
        FrontSetting::whereIn('key', ['website_default_lang', 'website_languages'])->delete();
    }
};
