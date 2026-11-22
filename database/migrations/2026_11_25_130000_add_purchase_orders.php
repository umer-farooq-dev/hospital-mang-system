<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Replenishment: reorder levels per store, purchase orders, goods receipt against an order. */
    public function up(): void
    {
        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->string('stockable_type', 100);
            $table->unsignedBigInteger('stockable_id');
            $table->integer('reorder_level')->default(0);
            $table->integer('reorder_quantity')->default(0);
            $table->timestamps();
            $table->unique(['store_id', 'stockable_type', 'stockable_id']);
        });
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 40)->unique();
            $table->unsignedBigInteger('supplier_id')->index();
            $table->unsignedBigInteger('store_id')->index();
            $table->string('status', 20)->index();
            $table->date('expected_date')->nullable();
            $table->string('note', 500)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            foreach (['closed', 'cancelled'] as $step) {
                $table->unsignedBigInteger($step.'_by')->nullable();
                $table->dateTime($step.'_at')->nullable();
            }
            $table->timestamps();
        });
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id')->index();
            $table->string('stockable_type', 100);
            $table->unsignedBigInteger('stockable_id');
            $table->integer('quantity');
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->integer('received_quantity')->default(0);
            $table->timestamps();
        });
        Schema::table('purchase_medicines', function (Blueprint $table) {
            $table->unsignedBigInteger('purchase_order_id')->nullable()->after('supplier_id')->index();
        });
        $now = now();
        if (! DB::table('settings')->where('key', 'purchase_order_prefix')->exists()) {
            DB::table('settings')->insert(['key' => 'purchase_order_prefix', 'value' => 'PO', 'created_at' => $now, 'updated_at' => $now]);
        }
        if (! Module::where('name', 'Purchase Orders')->exists()) {
            Module::create(['name' => 'Purchase Orders', 'is_active' => 1, 'route' => 'purchase-orders.index']);
        }
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
        Module::where('name', 'Purchase Orders')->delete();
        DB::table('settings')->where('key', 'purchase_order_prefix')->delete();
        Schema::table('purchase_medicines', fn (Blueprint $table) => $table->dropColumn('purchase_order_id'));
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('stock_levels');
    }
};
