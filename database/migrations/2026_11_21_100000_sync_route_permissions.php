<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Create the route permissions and grant each one to the roles that can open
     * those routes today (the access:... list on each route), so nobody gains or
     * loses access when routes switch from role checks to permissions.
     */
    public function up(): void
    {
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
        //
    }
};
