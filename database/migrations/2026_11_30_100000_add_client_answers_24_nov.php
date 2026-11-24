<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Salaries paid in cash or by bank transfer; insurer contracts that start and end (renewed each year). */
    public function up(): void
    {
        Schema::table('employee_payrolls', function (Blueprint $table) {
            $table->unsignedInteger('payment_method')->nullable()->after('status');
        });
        Schema::table('insurances', function (Blueprint $table) {
            $table->date('contract_from')->nullable()->after('credit_days');
            $table->date('contract_to')->nullable()->after('contract_from');
        });
    }

    public function down(): void
    {
        Schema::table('employee_payrolls', fn (Blueprint $table) => $table->dropColumn('payment_method'));
        Schema::table('insurances', fn (Blueprint $table) => $table->dropColumn(['contract_from', 'contract_to']));
    }
};
