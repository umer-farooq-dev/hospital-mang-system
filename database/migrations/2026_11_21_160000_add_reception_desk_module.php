<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Reception Desk can be switched on or off in Settings > Sidebar Setting, and its
     * permissions are granted to Admin and Receptionist by default.
     */
    public function up(): void
    {
        if (! Module::where('name', 'Reception Desk')->exists()) {
            Module::create(['name' => 'Reception Desk', 'is_active' => 1, 'route' => 'reception-desk.index']);
        }
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
        Module::where('name', 'Reception Desk')->delete();
    }
};
