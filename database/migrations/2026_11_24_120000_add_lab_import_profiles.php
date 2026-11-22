<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Column mappings for importing analyser result files (one profile per machine or export). */
    public function up(): void
    {
        Schema::create('lab_import_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->unsignedInteger('header_row')->default(1);
            $table->string('sample_column', 100);
            $table->text('mappings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        if (! Module::where('name', 'Lab Import Profiles')->exists()) {
            Module::create(['name' => 'Lab Import Profiles', 'is_active' => 1, 'route' => 'lab-import-profiles.index']);
        }
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
        Module::where('name', 'Lab Import Profiles')->delete();
        Schema::dropIfExists('lab_import_profiles');
    }
};
