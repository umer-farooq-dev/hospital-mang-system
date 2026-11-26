<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

/** Reception: one page from the patient to the printed receipt (department, doctor, exams, payer, discount). */
return new class extends Migration
{
    public function up(): void
    {
        // highest reduction a desk user may grant on one visit (Settings > Reception)
        Setting::firstOrCreate(['key' => 'reception_max_discount'], ['value' => '100']);
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
        Setting::where('key', 'reception_max_discount')->delete();
    }
};
