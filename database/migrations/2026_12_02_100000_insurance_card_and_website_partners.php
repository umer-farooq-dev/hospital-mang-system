<?php

use App\Models\FrontSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Insurance cards (e.g. Yelen) print a group (INDIVIDUEL or the employer) and have no barcode:
 * the policy keeps the group and a photo of the card (stored in the patient's documents).
 * Insurers can be shown on the website with their logo ("Nos assurances partenaires").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_insurances', function (Blueprint $table) {
            $table->string('group_name', 160)->nullable()->after('plan_name');
            $table->unsignedInteger('card_document_id')->nullable()->after('notes');
        });
        Schema::table('insurances', function (Blueprint $table) {
            $table->boolean('show_on_website')->default(false)->after('status');
        });
        foreach (['section_partners_sub' => 'Nos assurances partenaires', 'section_partners_title' => 'Ils nous font confiance'] as $key => $value) {
            FrontSetting::firstOrCreate(['key' => $key], ['value' => $value, 'type' => FrontSetting::HOME_PAGE]);
        }
    }

    public function down(): void
    {
        Schema::table('patient_insurances', fn (Blueprint $table) => $table->dropColumn(['group_name', 'card_document_id']));
        Schema::table('insurances', fn (Blueprint $table) => $table->dropColumn('show_on_website'));
        FrontSetting::whereIn('key', ['section_partners_sub', 'section_partners_title'])->delete();
    }
};
