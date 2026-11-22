<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Imaging: modality on radiology tests, report templates, optional PACS viewer link. */
    public function up(): void
    {
        Schema::create('imaging_report_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('lang_key')->nullable();
            $table->unsignedInteger('modality')->nullable();
            $table->longText('body')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::table('radiology_tests', function (Blueprint $table) {
            $table->unsignedInteger('modality')->nullable()->after('category_id');
            $table->unsignedBigInteger('report_template_id')->nullable()->after('modality');
        });
        $now = now();
        $modalities = [[1, 'Ultrasound', 'ultrasound'], [2, 'X-ray', 'xray'], [3, 'CT scan', 'ct'], [4, 'MRI', 'mri'], [5, 'Mammography', 'mammography'], [6, 'ECG', 'ecg']];
        foreach ($modalities as $i => [$code, $name, $key]) {
            DB::table('master_list_items')->insertOrIgnore([
                'type' => 'imaging_modality', 'code' => $code, 'name' => $name, 'lang_key' => 'messages.imaging.lists.imaging_modality.' . $key,
                'sort_order' => $i + 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        foreach ([['abdominal_ultrasound', 1], ['chest_xray', 2]] as $i => [$key, $modality]) {
            DB::table('imaging_report_templates')->insert(['name' => ucfirst(str_replace('_', ' ', $key)), 'lang_key' => 'messages.imaging.templates.' . $key,
                'modality' => $modality, 'body' => null, 'sort_order' => $i + 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }
        if (! DB::table('settings')->where('key', 'pacs_viewer_url')->exists()) {
            DB::table('settings')->insert(['key' => 'pacs_viewer_url', 'value' => '', 'created_at' => $now, 'updated_at' => $now]);
        }
        if (! Module::where('name', 'Imaging Report Templates')->exists()) {
            Module::create(['name' => 'Imaging Report Templates', 'is_active' => 1, 'route' => 'imaging-report-templates.index']);
        }
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
        Module::where('name', 'Imaging Report Templates')->delete();
        DB::table('settings')->where('key', 'pacs_viewer_url')->delete();
        DB::table('master_list_items')->where('type', 'imaging_modality')->delete();
        Schema::table('radiology_tests', fn (Blueprint $table) => $table->dropColumn(['modality', 'report_template_id']));
        Schema::dropIfExists('imaging_report_templates');
    }
};
