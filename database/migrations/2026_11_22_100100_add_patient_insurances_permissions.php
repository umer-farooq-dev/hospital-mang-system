<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Patient insurance policies (patient file > Insurance tab) get their own permissions on
     * the Roles screen; the roles that manage patients get them by default.
     */
    public function up(): void
    {
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
    }
};
