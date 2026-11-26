<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

/** "History" of a bill, receipt or visit (admin). */
return new class extends Migration
{
    public function up(): void
    {
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
    }
};
