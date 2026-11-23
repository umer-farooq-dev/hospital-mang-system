<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /** Permissions for the posting rules screen and the automatic entries. */
    public function up(): void
    {
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
    }
};
