<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Each insurer's claim invoice layout: its own title and the columns it wants (ICD-10 code, voucher...). */
    public function up(): void
    {
        Schema::table('insurances', function (Blueprint $table) {
            $table->string('claim_title', 100)->nullable()->after('claim_note');
            $table->json('claim_columns')->nullable()->after('claim_title');
        });
    }

    public function down(): void
    {
        Schema::table('insurances', function (Blueprint $table) {
            $table->dropColumn(['claim_title', 'claim_columns']);
        });
    }
};
