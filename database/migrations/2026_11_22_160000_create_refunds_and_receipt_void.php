<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Money is never deleted: a wrong receipt is voided while its session is open, and money
     * given back is a refund order with a reason ("Ordre de remboursement"), paid from the
     * register of the person refunding.
     */
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->unsignedInteger('void_reason')->nullable()->after('voided_at');
            $table->foreignId('voided_by')->nullable()->after('void_reason')->constrained('users')->nullOnDelete();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->unique()->constrained('receipts');
            $table->foreignId('cash_session_id')->constrained('cash_sessions');
            $table->foreignId('cash_counter_id')->constrained('cash_counters');
            $table->foreignId('user_id')->constrained('users');
            $table->unsignedInteger('reason');
            $table->unsignedInteger('payment_method');
            $table->decimal('amount', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $now = now();
        $reasons = [
            [1, 'Consultation not done', 'messages.receipts.reasons.consultation_not_done'],
            [2, 'Exam not done', 'messages.receipts.reasons.exam_not_done'],
            [3, 'Products not served', 'messages.receipts.reasons.products_not_served'],
            [4, 'Wrong payment method', 'messages.receipts.reasons.wrong_method'],
            [5, 'Input error', 'messages.receipts.reasons.input_error'],
        ];
        foreach ($reasons as $i => [$code, $name, $langKey]) {
            DB::table('master_list_items')->insertOrIgnore([
                'type' => 'refund_reason', 'code' => $code, 'name' => $name, 'lang_key' => $langKey,
                'sort_order' => $i + 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        foreach ([['Receipts', 'receipts.index'], ['Cash Journal', 'cash-journal.index']] as [$name, $route]) {
            if (! Module::where('name', $name)->exists()) {
                Module::create(['name' => $name, 'is_active' => 1, 'route' => $route]);
            }
        }
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
        Module::whereIn('name', ['Receipts', 'Cash Journal'])->delete();
        DB::table('master_list_items')->where('type', 'refund_reason')->delete();
        Schema::dropIfExists('refunds');
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voided_by');
            $table->dropColumn('void_reason');
        });
    }
};
