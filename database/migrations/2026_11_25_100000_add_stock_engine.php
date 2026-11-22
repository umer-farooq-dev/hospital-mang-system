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
     * Stock by store and batch with a ledger of every movement. Existing medicine stock
     * becomes one opening batch in the first store, so totals do not change.
     */
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('code', 30)->nullable();
            $table->unsignedInteger('store_type')->nullable();
            $table->boolean('can_sell')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('store_user', function (Blueprint $table) {
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('user_id');
            $table->primary(['store_id', 'user_id']);
        });
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('contact_person', 150)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('tax_number', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->string('stockable_type', 100);
            $table->unsignedBigInteger('stockable_id');
            $table->string('batch_no', 100)->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->integer('quantity')->default(0);
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->timestamps();
            $table->index(['stockable_type', 'stockable_id', 'store_id']);
            $table->index('expiry_date');
        });
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('stock_batch_id');
            $table->string('stockable_type', 100);
            $table->unsignedBigInteger('stockable_id');
            $table->integer('quantity');
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->string('movement_type', 30);
            $table->string('document_type', 100)->nullable();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamps();
            $table->index(['document_type', 'document_id']);
            $table->index(['stockable_type', 'stockable_id', 'store_id']);
        });
        Schema::table('medicines', function (Blueprint $table) {
            $table->integer('quantity')->default(0)->change();
            $table->integer('available_quantity')->default(0)->change();
            $table->string('common_name', 191)->nullable()->after('name');
            $table->string('barcode', 100)->nullable()->after('common_name')->index();
            $table->string('cis_code', 50)->nullable()->after('barcode');
            $table->unsignedInteger('unit')->nullable()->after('cis_code');
            $table->unsignedInteger('dosage_form')->nullable()->after('unit');
            $table->unsignedInteger('route')->nullable()->after('dosage_form');
        });
        Schema::table('purchase_medicines', function (Blueprint $table) {
            $table->unsignedBigInteger('store_id')->nullable()->after('purchase_no');
            $table->unsignedBigInteger('supplier_id')->nullable()->after('store_id');
        });
        // The batch (and so the expiry) of a sale line is chosen by the stock, not typed.
        Schema::table('sale_medicines', fn (Blueprint $table) => $table->date('expiry_date')->nullable()->change());
        Schema::table('medicine_bills', function (Blueprint $table) {
            $table->unsignedBigInteger('store_id')->nullable()->after('bill_number');
            $table->dateTime('dispensed_at')->nullable()->after('bill_date');
            $table->unsignedBigInteger('dispensed_by')->nullable()->after('dispensed_at');
        });

        $now = now();
        $lists = [
            'store_type' => ['main' => 'Main store', 'pharmacy' => 'Pharmacy', 'ward' => 'Ward', 'theatre' => 'Operating theatre', 'other' => 'Other'],
            'medicine_unit' => ['tablet' => 'Tablet', 'capsule' => 'Capsule', 'bottle' => 'Bottle', 'ampoule' => 'Ampoule', 'vial' => 'Vial',
                'sachet' => 'Sachet', 'tube' => 'Tube', 'box' => 'Box', 'ml' => 'ml', 'piece' => 'Piece'],
            'dosage_form' => ['tablet' => 'Tablet', 'capsule' => 'Capsule', 'syrup' => 'Syrup', 'suspension' => 'Suspension', 'injection' => 'Injection',
                'infusion' => 'Infusion', 'cream' => 'Cream / ointment', 'drops' => 'Drops', 'suppository' => 'Suppository', 'inhaler' => 'Inhaler', 'powder' => 'Powder'],
            'medicine_route' => ['oral' => 'Oral', 'iv' => 'Intravenous', 'im' => 'Intramuscular', 'sc' => 'Subcutaneous', 'topical' => 'Topical',
                'rectal' => 'Rectal', 'vaginal' => 'Vaginal', 'inhaled' => 'Inhaled', 'eye' => 'Eye', 'ear' => 'Ear', 'nasal' => 'Nasal', 'sublingual' => 'Sublingual'],
        ];
        foreach ($lists as $type => $items) {
            $i = 0;
            foreach ($items as $key => $name) {
                $i++;
                DB::table('master_list_items')->insertOrIgnore(['type' => $type, 'code' => $i, 'name' => $name,
                    'lang_key' => 'messages.stock.lists.'.$type.'.'.$key, 'sort_order' => $i, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        $storeId = DB::table('stores')->insertGetId(['name' => __('messages.stock.seed_pharmacy'), 'store_type' => 2, 'can_sell' => true,
            'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now]);
        $settings = ['stock_fefo' => '1', 'stock_allow_negative' => '0', 'stock_sell_expired' => '0', 'stock_expiry_alert_days' => '90',
            'pharmacy_default_store' => (string) $storeId];
        foreach ($settings as $key => $value) {
            if (! DB::table('settings')->where('key', $key)->exists()) {
                DB::table('settings')->insert(['key' => $key, 'value' => $value, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        foreach (DB::table('medicines')->where('available_quantity', '>', 0)->get(['id', 'available_quantity', 'buying_price']) as $medicine) {
            $batchId = DB::table('stock_batches')->insertGetId(['store_id' => $storeId, 'stockable_type' => \App\Models\Medicine::class,
                'stockable_id' => $medicine->id, 'unit_cost' => round((float) $medicine->buying_price, 2), 'quantity' => $medicine->available_quantity,
                'created_at' => $now, 'updated_at' => $now]);
            DB::table('stock_movements')->insert(['store_id' => $storeId, 'stock_batch_id' => $batchId, 'stockable_type' => \App\Models\Medicine::class,
                'stockable_id' => $medicine->id, 'quantity' => $medicine->available_quantity, 'unit_cost' => round((float) $medicine->buying_price, 2),
                'movement_type' => 'opening', 'document_type' => \App\Models\Medicine::class, 'document_id' => $medicine->id,
                'created_at' => $now, 'updated_at' => $now]);
        }

        foreach (['Stock' => 'stock.index', 'Stores' => 'stores.index', 'Suppliers' => 'suppliers.index'] as $name => $route) {
            if (! Module::where('name', $name)->exists()) {
                Module::create(['name' => $name, 'is_active' => 1, 'route' => $route]);
            }
        }
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
        Module::whereIn('name', ['Stock', 'Stores', 'Suppliers'])->delete();
        DB::table('settings')->whereIn('key', ['stock_fefo', 'stock_allow_negative', 'stock_sell_expired', 'stock_expiry_alert_days', 'pharmacy_default_store'])->delete();
        DB::table('master_list_items')->whereIn('type', ['store_type', 'medicine_unit', 'dosage_form', 'medicine_route'])->delete();
        Schema::table('medicine_bills', fn (Blueprint $table) => $table->dropColumn(['store_id', 'dispensed_at', 'dispensed_by']));
        Schema::table('purchase_medicines', fn (Blueprint $table) => $table->dropColumn(['store_id', 'supplier_id']));
        Schema::table('medicines', fn (Blueprint $table) => $table->dropColumn(['common_name', 'barcode', 'cis_code', 'unit', 'dosage_form', 'route']));
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_batches');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('store_user');
        Schema::dropIfExists('stores');
    }
};
