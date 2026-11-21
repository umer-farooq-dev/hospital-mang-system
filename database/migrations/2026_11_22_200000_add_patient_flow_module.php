<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /** Patient flow reports ("État de passage des patients"), from the receipts. */
    public function up(): void
    {
        if (! Module::where('name', 'Patient Flow')->exists()) {
            Module::create(['name' => 'Patient Flow', 'is_active' => 1, 'route' => 'patient-flow.index']);
        }
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
        Module::where('name', 'Patient Flow')->delete();
    }
};
