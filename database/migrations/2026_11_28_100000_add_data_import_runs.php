<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Log of the go-live imports, and the permissions of the import screen. */
    public function up(): void
    {
        Schema::create('data_import_runs', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 30);
            $table->string('file_name');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->json('options')->nullable();
            $table->unsignedInteger('created')->default(0);
            $table->unsignedInteger('existing')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->json('errors')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
        Schema::dropIfExists('data_import_runs');
    }
};
