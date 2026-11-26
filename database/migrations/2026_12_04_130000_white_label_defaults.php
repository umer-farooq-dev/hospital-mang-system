<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/** Nothing of the stock product on prints or tabs: company name = hospital name, favicon on the hospital's own domain. */
return new class extends Migration
{
    public function up(): void
    {
        $company = Setting::where('key', 'company_name')->first();
        if ($company && in_array($company->value, ['InfyOmLabs', 'InfyOm Labs', 'InfyOm Technologies', ''], true)) {
            $company->update(['value' => Setting::where('key', 'app_name')->value('value') ?: 'Hospital']);
        }
        $favicon = Setting::where('key', 'favicon')->first();
        if ($favicon && str_contains((string) $favicon->value, 'e-hms.test')) {
            $favicon->update(['value' => 'web/img/favicon.png']);
        }
        // the stock social links point to the software vendor's own pages
        foreach (['facebook_url', 'twitter_url', 'instagram_url', 'linkedIn_url'] as $key) {
            $link = Setting::where('key', $key)->first();
            if ($link && (stripos((string) $link->value, 'infyom') !== false || $link->value === 'https://www.instagram.com/?hl=en')) {
                $link->update(['value' => '']);
            }
        }
        $app = Setting::where('key', 'app_name')->first();
        if ($app && in_array($app->value, ['HMS', 'InfyHMS'], true)) {
            $app->update(['value' => 'Hospital']);
        }
    }

    public function down(): void
    {
    }
};
