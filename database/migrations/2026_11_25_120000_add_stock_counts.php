<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Stock counts: count sheet per store, variance, approval and adjustment. */
    public function up(): void
    {
        Schema::create('stock_counts', function (Blueprint $table) {
            $table->id();
            $table->string('number', 40)->unique();
            $table->unsignedBigInteger('store_id')->index();
            $table->string('status', 20)->index();
            $table->boolean('blind')->default(false);
            $table->string('note', 500)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            foreach (['submitted', 'approved', 'cancelled'] as $step) {
                $table->unsignedBigInteger($step.'_by')->nullable();
                $table->dateTime($step.'_at')->nullable();
            }
            $table->timestamps();
        });
        Schema::create('stock_count_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_count_id')->index();
            $table->unsignedBigInteger('stock_batch_id')->nullable();
            $table->string('stockable_type', 100);
            $table->unsignedBigInteger('stockable_id');
            $table->string('batch_no', 100)->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->integer('system_quantity')->default(0);
            $table->integer('counted_quantity')->nullable();
            $table->timestamps();
        });
        $now = now();
        foreach (['stock_count_prefix' => 'SC', 'stock_count_blind' => '0'] as $key => $value) {
            if (! DB::table('settings')->where('key', $key)->exists()) {
                DB::table('settings')->insert(['key' => $key, 'value' => $value, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
        if (! Module::where('name', 'Stock Counts')->exists()) {
            Module::create(['name' => 'Stock Counts', 'is_active' => 1, 'route' => 'stock-counts.index']);
        }
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
        Module::where('name', 'Stock Counts')->delete();
        DB::table('settings')->whereIn('key', ['stock_count_prefix', 'stock_count_blind'])->delete();
        Schema::dropIfExists('stock_count_lines');
        Schema::dropIfExists('stock_counts');
    }
};
