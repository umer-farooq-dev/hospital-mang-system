<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Registered patients report (Patients > Registrations): can be switched off in
     * Settings > Sidebar Setting; Admin and Receptionist get its permissions by default.
     */
    public function up(): void
    {
        if (! Module::where('name', 'Patient Registrations')->exists()) {
            Module::create(['name' => 'Patient Registrations', 'is_active' => 1, 'route' => 'patient-registrations.index']);
        }
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
        Module::where('name', 'Patient Registrations')->delete();
    }
};
