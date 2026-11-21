<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Create the permissions for the Master Lists routes (granted to Admin by
     * default). Existing permissions are left as the admin set them.
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
