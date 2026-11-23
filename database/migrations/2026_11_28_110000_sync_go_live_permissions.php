<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /** Permissions of the go-live check page. */
    public function up(): void
    {
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
    }
};
