<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * General inventory (items) on the same stock engine: item stock entries are goods receipts into a store
     * from a supplier, issued items leave a store. Existing item quantities become an opening batch.
     */
    public function up(): void
    {
        Schema::table('item_stocks', function (Blueprint $table) {
            $table->unsignedBigInteger('store_id')->nullable()->after('item_id');
            $table->unsignedBigInteger('supplier_id')->nullable()->after('store_id');
        });
        Schema::table('issued_items', function (Blueprint $table) {
            $table->unsignedBigInteger('store_id')->nullable()->after('item_id');
        });
        $now = now();
        $storeId = (int) DB::table('settings')->where('key', 'pharmacy_default_store')->value('value')
            ?: (int) DB::table('stores')->where('is_active', true)->orderBy('sort_order')->value('id');
        if (! DB::table('settings')->where('key', 'inventory_default_store')->exists()) {
            DB::table('settings')->insert(['key' => 'inventory_default_store', 'value' => (string) $storeId, 'created_at' => $now, 'updated_at' => $now]);
        }
        if (! $storeId) {
            return;
        }
        foreach (DB::table('items')->where('available_quantity', '>', 0)->get(['id', 'available_quantity']) as $item) {
            $cost = round((float) DB::table('item_stocks')->where('item_id', $item->id)->orderByDesc('id')->value('purchase_price'), 2);
            $batchId = DB::table('stock_batches')->insertGetId(['store_id' => $storeId, 'stockable_type' => \App\Models\Item::class,
                'stockable_id' => $item->id, 'unit_cost' => $cost, 'quantity' => $item->available_quantity, 'created_at' => $now, 'updated_at' => $now]);
            DB::table('stock_movements')->insert(['store_id' => $storeId, 'stock_batch_id' => $batchId, 'stockable_type' => \App\Models\Item::class,
                'stockable_id' => $item->id, 'quantity' => $item->available_quantity, 'unit_cost' => $cost, 'movement_type' => 'opening',
                'document_type' => \App\Models\Item::class, 'document_id' => $item->id, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        DB::table('stock_movements')->where('stockable_type', \App\Models\Item::class)->delete();
        DB::table('stock_batches')->where('stockable_type', \App\Models\Item::class)->delete();
        DB::table('settings')->where('key', 'inventory_default_store')->delete();
        Schema::table('issued_items', fn (Blueprint $table) => $table->dropColumn('store_id'));
        Schema::table('item_stocks', fn (Blueprint $table) => $table->dropColumn(['store_id', 'supplier_id']));
    }
};
