<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /** Permissions for the financial reports and the export to the accounting software. */
    public function up(): void
    {
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
    }
};
