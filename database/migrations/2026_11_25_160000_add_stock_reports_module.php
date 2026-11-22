<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    public function up(): void
    {
        if (! Module::where('name', 'Stock Reports')->exists()) {
            Module::create(['name' => 'Stock Reports', 'is_active' => 1, 'route' => 'stock-reports.index']);
        }
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
        Module::where('name', 'Stock Reports')->delete();
    }
};
