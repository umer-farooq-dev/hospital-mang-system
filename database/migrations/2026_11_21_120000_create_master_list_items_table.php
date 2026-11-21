<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lists that were PHP constants become admin-managed rows. Each row keeps the
     * old constant key as its "code", so values already stored in incomes,
     * expenses, charges, visitors and prescriptions keep pointing to the same item.
     */
    public function up(): void
    {
        Schema::create('master_list_items', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->unsignedInteger('code');
            $table->string('name');
            $table->string('lang_key')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['type', 'code']);
            $table->index(['type', 'is_active', 'sort_order']);
        });

        // [type, code, name, translation key of the original label]
        $items = [
            ['income_head', 1, 'Canteen Rent', 'messages.incomes.Canteen Rent'],
            ['income_head', 2, 'Hospital Charges', 'messages.incomes.Hospital Charges'],
            ['income_head', 3, 'Special Campaign', 'messages.incomes.Special Campaign'],
            ['income_head', 4, 'Vehicle Stand Charges', 'messages.incomes.Vehicle Stand Charges'],

            ['expense_head', 1, 'Building Rent', 'messages.expense_head.Building Rent'],
            ['expense_head', 2, 'Electricity Bill', 'messages.expense_head.Electricity Bill'],
            ['expense_head', 3, 'Equipments', 'messages.expense_head.Equipments'],
            ['expense_head', 4, 'Power Generator Fuel Charge', 'messages.expense_head.Power Generator Fuel Charge'],
            ['expense_head', 5, 'Tea Expense', 'messages.expense_head.Tea Expense'],
            ['expense_head', 6, 'Telephone Bill', 'messages.expense_head.Telephone Bill'],

            ['charge_type', 1, 'Investigations', 'messages.charge.Investigations'],
            ['charge_type', 2, 'Operation Theatre', 'messages.charge.Operation Theatre'],
            ['charge_type', 3, 'Others', 'messages.charge.Others'],
            ['charge_type', 4, 'Procedures', 'messages.charge.Procedures'],
            ['charge_type', 5, 'Supplier', 'messages.charge.Supplier'],

            ['visitor_purpose', 1, 'Enquiry', 'messages.visitor.enquiry'],
            ['visitor_purpose', 2, 'Seminar', 'messages.visitor.seminar'],
            ['visitor_purpose', 3, 'Visit', 'messages.visitor.visit'],

            ['dose_interval', 1, 'Daily morning', null],
            ['dose_interval', 2, 'Daily morning and evening', null],
            ['dose_interval', 3, 'Daily morning, noon, and evening', null],
            ['dose_interval', 4, '4 times in a day', null],

            ['dose_duration', 1, 'Only one day', null],
            ['dose_duration', 3, 'Upto Three days', null],
            ['dose_duration', 7, 'Upto One week', null],
            ['dose_duration', 14, 'Upto two week', null],
            ['dose_duration', 30, 'Upto one month', null],

            ['meal_timing', 0, 'After Meal', null],
            ['meal_timing', 1, 'Before Meal', null],
        ];

        $now = now();
        $sort = [];
        DB::table('master_list_items')->insert(array_map(function ($item) use ($now, &$sort) {
            $sort[$item[0]] = ($sort[$item[0]] ?? 0) + 1;

            return [
                'type' => $item[0], 'code' => $item[1], 'name' => $item[2], 'lang_key' => $item[3],
                'sort_order' => $sort[$item[0]], 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ];
        }, $items));

        Module::create(['name' => 'Master Lists', 'is_active' => 1, 'route' => 'master-lists.index']);
    }

    public function down(): void
    {
        Module::where('name', 'Master Lists')->delete();
        Schema::dropIfExists('master_list_items');
    }
};
