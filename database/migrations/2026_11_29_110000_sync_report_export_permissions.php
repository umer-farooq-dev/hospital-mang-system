<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /** Export permission of the report screens that download their tables as Excel. */
    public function up(): void
    {
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
    }
};
