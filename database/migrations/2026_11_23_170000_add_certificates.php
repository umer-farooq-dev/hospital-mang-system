<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Medical certificates written from templates the admin manages. */
    public function up(): void
    {
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('lang_key')->nullable();
            $table->longText('body')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('number', 40)->unique();
            $table->unsignedBigInteger('certificate_template_id');
            $table->string('title', 150);
            $table->unsignedBigInteger('patient_id')->index();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('ipd_patient_department_id')->nullable();
            $table->dateTime('issued_at');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->longText('body');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
        $now = now();
        foreach (['medical', 'sick_leave', 'fitness', 'hospital_stay'] as $i => $key) {
            DB::table('certificate_templates')->insert(['name' => ucfirst(str_replace('_', ' ', $key)), 'lang_key' => 'messages.certificate.templates.' . $key,
                'body' => null, 'sort_order' => $i + 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }
        if (! DB::table('settings')->where('key', 'certificate_prefix')->exists()) {
            DB::table('settings')->insert(['key' => 'certificate_prefix', 'value' => 'CM', 'created_at' => $now, 'updated_at' => $now]);
        }
        foreach ([['Certificates', 'certificates.index'], ['Certificate Templates', 'certificate-templates.index']] as [$name, $route]) {
            if (! Module::where('name', $name)->exists()) {
                Module::create(['name' => $name, 'is_active' => 1, 'route' => $route]);
            }
        }
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
        Module::whereIn('name', ['Certificates', 'Certificate Templates'])->delete();
        DB::table('settings')->where('key', 'certificate_prefix')->delete();
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('certificate_templates');
    }
};
