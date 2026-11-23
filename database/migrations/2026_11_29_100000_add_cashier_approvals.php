<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Register close counted per payment method with a supervisor approval; voids that wait for a second person. */
    public function up(): void
    {
        Schema::table('cash_sessions', function (Blueprint $table) {
            $table->json('counted_by_method')->nullable()->after('counted_cash');
            $table->json('expected_by_method')->nullable()->after('counted_by_method');
            $table->string('approval_status', 20)->nullable()->after('notes'); // pending, approved
            $table->unsignedBigInteger('approved_by')->nullable()->after('approval_status');
            $table->dateTime('approved_at')->nullable()->after('approved_by');
            $table->string('approval_note', 1000)->nullable()->after('approved_at');
        });
        Schema::table('receipts', function (Blueprint $table) {
            $table->dateTime('void_requested_at')->nullable()->after('voided_by');
            $table->unsignedBigInteger('void_requested_by')->nullable()->after('void_requested_at');
            $table->unsignedInteger('void_request_reason')->nullable()->after('void_requested_by');
        });
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
        Schema::table('cash_sessions', function (Blueprint $table) {
            $table->dropColumn(['counted_by_method', 'expected_by_method', 'approval_status', 'approved_by', 'approved_at', 'approval_note']);
        });
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropColumn(['void_requested_at', 'void_requested_by', 'void_request_reason']);
        });
    }
};
