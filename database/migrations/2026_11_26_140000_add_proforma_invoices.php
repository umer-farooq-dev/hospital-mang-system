<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Pro-forma invoices (quotes): the Invoices module with a type, validity, payer and price list. */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('type', 20)->default('invoice')->after('invoice_id')->index();
            $table->date('valid_until')->nullable()->after('invoice_date');
            $table->unsignedInteger('insurance_id')->nullable()->after('valid_until');
            $table->unsignedBigInteger('price_list_id')->nullable()->after('insurance_id');
            $table->unsignedInteger('converted_invoice_id')->nullable()->after('price_list_id');
        });
        if (! DB::table('settings')->where('key', 'proforma_validity_days')->exists()) {
            DB::table('settings')->insert(['key' => 'proforma_validity_days', 'value' => '30', 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'proforma_validity_days')->delete();
        Schema::table('invoices', fn (Blueprint $table) => $table->dropColumn(['type', 'valid_until', 'insurance_id', 'price_list_id', 'converted_invoice_id']));
    }
};
