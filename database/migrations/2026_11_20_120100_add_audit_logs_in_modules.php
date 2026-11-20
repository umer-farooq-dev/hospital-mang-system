<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Module::create([
            'name' => 'Audit Logs',
            'is_active' => 1,
            'route' => 'audit-logs.index',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Module::where('name', 'Audit Logs')->delete();
    }
};
