<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Referrer ("apporteur") commissions: a rate per referrer, and the payments made to them. */
    public function up(): void
    {
        Schema::create('referrer_commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('referrer_code')->unique();
            $table->decimal('percent', 5, 2)->default(0);
            $table->timestamps();
        });
        Schema::create('referrer_payouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('referrer_code')->index();
            $table->date('period_from');
            $table->date('period_to');
            $table->decimal('amount', 15, 2);
            $table->date('paid_on');
            $table->unsignedInteger('payment_method')->nullable();
            $table->string('reference', 100)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
        if (! Module::where('name', 'Referrer Commissions')->exists()) {
            Module::create(['name' => 'Referrer Commissions', 'is_active' => 1, 'route' => 'referrer-commissions.index']);
        }
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
        Module::where('name', 'Referrer Commissions')->delete();
        Schema::dropIfExists('referrer_payouts');
        Schema::dropIfExists('referrer_commissions');
    }
};
