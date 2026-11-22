<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Requisitions, transfers and returns between stores, and returns to suppliers. */
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('number', 40)->unique();
            $table->string('kind', 20);
            $table->string('status', 20)->index();
            $table->unsignedBigInteger('from_store_id')->nullable()->index();
            $table->unsignedBigInteger('to_store_id')->nullable()->index();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('note', 500)->nullable();
            foreach (['requested', 'approved', 'sent', 'received', 'cancelled'] as $step) {
                $table->unsignedBigInteger($step.'_by')->nullable();
                $table->dateTime($step.'_at')->nullable();
            }
            $table->timestamps();
        });
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_transfer_id')->index();
            $table->string('stockable_type', 100);
            $table->unsignedBigInteger('stockable_id');
            $table->integer('quantity_requested');
            $table->integer('quantity_approved')->nullable();
            $table->integer('quantity_sent')->nullable();
            $table->timestamps();
        });
        $now = now();
        foreach (['stock_transfer_prefix' => 'TR', 'stock_transfer_approval' => '1'] as $key => $value) {
            if (! DB::table('settings')->where('key', $key)->exists()) {
                DB::table('settings')->insert(['key' => $key, 'value' => $value, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
        if (! Module::where('name', 'Stock Transfers')->exists()) {
            Module::create(['name' => 'Stock Transfers', 'is_active' => 1, 'route' => 'stock-transfers.index']);
        }
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
        Module::where('name', 'Stock Transfers')->delete();
        DB::table('settings')->whereIn('key', ['stock_transfer_prefix', 'stock_transfer_approval'])->delete();
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
    }
};
