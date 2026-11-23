<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Imports that update records (prices, coverage rules, surgical acts) count the updated lines. */
    public function up(): void
    {
        Schema::table('data_import_runs', function (Blueprint $table) {
            $table->unsignedInteger('updated')->default(0)->after('created');
        });
    }

    public function down(): void
    {
        Schema::table('data_import_runs', function (Blueprint $table) {
            $table->dropColumn('updated');
        });
    }
};
