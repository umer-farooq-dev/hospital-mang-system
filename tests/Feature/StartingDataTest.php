<?php

namespace Tests\Feature;

use App\Models\DataImportRun;
use App\Utils\DataImport;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Starting-data imports a new hospital fills: departments, prices, surgical acts, coverage rules. */
class StartingDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $tables = [
            'settings' => fn ($t) => [$t->string('key'), $t->text('value')->nullable()],
            'master_list_items' => fn ($t) => [$t->string('type'), $t->unsignedInteger('code'), $t->string('name'), $t->string('lang_key')->nullable(),
                $t->unsignedInteger('sort_order')->default(0), $t->boolean('is_active')->default(true)],
            'price_lists' => fn ($t) => [$t->string('name'), $t->string('lang_key')->nullable(), $t->decimal('adjustment_percent', 6, 2)->default(0), $t->decimal('k_value', 15, 2)->nullable(),
                $t->boolean('is_default')->default(false), $t->boolean('is_active')->default(true), $t->unsignedInteger('sort_order')->default(0)],
            'item_prices' => fn ($t) => [$t->string('priceable_type'), $t->unsignedBigInteger('priceable_id'), $t->unsignedBigInteger('price_list_id'), $t->decimal('price', 15, 2)->nullable(),
                $t->decimal('doctor_share_percent', 5, 2)->nullable(), $t->decimal('assistant_share_percent', 5, 2)->nullable()],
            'charge_categories' => fn ($t) => [$t->string('name'), $t->text('description')->nullable(), $t->integer('charge_type')],
            'charges' => fn ($t) => [$t->integer('charge_type'), $t->unsignedInteger('charge_category_id'), $t->string('code'), $t->string('standard_charge'), $t->text('description')->nullable(),
                $t->string('currency_symbol')->nullable()],
            'pathology_tests' => fn ($t) => [$t->string('test_name'), $t->string('standard_charge')->nullable()],
            'doctor_departments' => fn ($t) => [$t->string('title'), $t->text('description')->nullable(), $t->decimal('discount_percent', 5, 2)->nullable()],
            'operation_categories' => fn ($t) => [$t->string('name')],
            'operations' => fn ($t) => [$t->unsignedInteger('operation_category_id'), $t->string('name'), $t->decimal('k_surgeon', 10, 2)->nullable(),
                $t->decimal('k_assistant', 10, 2)->nullable(), $t->decimal('k_anaesthetist', 10, 2)->nullable()],
            'insurances' => fn ($t) => [$t->string('name'), $t->string('insurance_code')->nullable()],
            'coverage_rules' => fn ($t) => [$t->unsignedInteger('insurance_id'), $t->unsignedInteger('doctor_department_id')->nullable(), $t->unsignedInteger('charge_category_id')->nullable(),
                $t->decimal('coverage_percent', 5, 2)->nullable(), $t->boolean('not_covered')->default(false), $t->boolean('needs_approval')->default(false), $t->decimal('max_amount', 15, 2)->nullable()],
            'data_import_runs' => fn ($t) => [$t->string('kind'), $t->string('file_name'), $t->unsignedBigInteger('user_id')->nullable(), $t->json('options')->nullable(),
                $t->unsignedInteger('created')->default(0), $t->unsignedInteger('updated')->default(0), $t->unsignedInteger('existing')->default(0), $t->unsignedInteger('failed')->default(0),
                $t->json('errors')->nullable(), $t->string('note')->nullable()],
        ];
        foreach ($tables as $name => $columns) {
            Schema::create($name, function (Blueprint $t) use ($columns) {
                $t->id();
                $columns($t);
                $t->timestamps();
            });
        }
        DB::table('master_list_items')->insert(['type' => 'charge_type', 'code' => 3, 'name' => 'Procedures']);
        DB::table('price_lists')->insert([['name' => 'Comptant', 'is_default' => true, 'sort_order' => 1], ['name' => 'Prise en charge', 'is_default' => false, 'sort_order' => 2],
            ['name' => 'Week-end', 'is_default' => false, 'sort_order' => 3], ['name' => 'Old', 'is_default' => false, 'sort_order' => 4]]);
        DB::table('price_lists')->where('name', 'Old')->update(['is_active' => false]);
    }

    private function import(string $kind, array $rows): DataImportRun
    {
        return (new DataImport($kind))->run($rows, DataImportRun::create(['kind' => $kind, 'file_name' => 'f.csv']));
    }

    public function test_the_tariff_template_has_one_column_per_active_price_list_and_creates_then_updates_acts(): void
    {
        $this->assertSame(['Type', 'Act', 'Category', 'Charge type', 'Price', 'Doctor share %', 'Assistant share %', 'Prise en charge', 'Week-end'], DataImport::template('tariff'));
        DB::table('pathology_tests')->insert(['test_name' => 'NFS', 'standard_charge' => '3000']);
        $rows = [
            ['Type' => 'Acte', 'Acte' => 'Pansement simple', 'Catégorie' => 'Soins', 'Type de charge' => 'procedures', 'Prix' => '1 500', 'Prise en charge' => '1725', 'Part médecin %' => '10'],
            ['Type' => 'Laboratoire', 'Acte' => 'NFS', 'Prix' => '3500', 'Week-end' => '5000'],
            ['Type' => 'Laboratoire', 'Acte' => 'Inconnu', 'Prix' => '1'],
            ['Type' => 'Acte', 'Acte' => 'Sans catégorie', 'Prix' => '100'],
            ['Type' => 'Voiture', 'Acte' => 'X'],
        ];
        $preview = (new DataImport('tariff'))->preview($rows);
        $this->assertSame([1, 1, 3], [$preview['create'], $preview['update'], $preview['failed']]);

        $run = $this->import('tariff', $rows);
        $this->assertSame([1, 1, 3], [$run->created, $run->updated, $run->failed]);
        $charge = DB::table('charges')->first();
        $this->assertSame(['Pansement simple', 1500.0, 3], [$charge->code, (float) $charge->standard_charge, (int) $charge->charge_type]);
        $this->assertSame('Soins', DB::table('charge_categories')->value('name'));
        $this->assertEquals(1725, DB::table('item_prices')->where(['priceable_type' => 'charge', 'price_list_id' => 2])->value('price'));
        $this->assertEquals(10, DB::table('item_prices')->where(['priceable_type' => 'charge', 'price_list_id' => 1])->value('doctor_share_percent'), 'shares on the default list');
        $this->assertSame(3500.0, (float) DB::table('pathology_tests')->value('standard_charge'));
        $this->assertEquals(5000, DB::table('item_prices')->where(['priceable_type' => 'pathology_test', 'price_list_id' => 3])->value('price'));

        $again = $this->import('tariff', [['Type' => 'acte', 'Acte' => 'Pansement simple', 'Prix' => '2000', 'Prise en charge' => '2300']]);
        $this->assertSame([0, 1], [$again->created, $again->updated]);
        $this->assertSame(1, DB::table('charges')->count(), 'updated, not created twice');
        $this->assertEquals(2300, DB::table('item_prices')->where(['priceable_type' => 'charge', 'price_list_id' => 2])->value('price'));
    }

    public function test_a_hospital_tariff_file_without_type_column_is_read_from_its_title_line(): void
    {
        Schema::table('pathology_tests', function (Blueprint $t) {
            $t->string('short_name')->nullable();
            $t->string('test_type')->nullable();
            $t->unsignedInteger('category_id')->nullable();
            $t->unsignedInteger('charge_category_id')->nullable();
            $t->unsignedBigInteger('patient_id')->nullable();
            $t->string('currency_symbol')->nullable();
        });
        Schema::create('pathology_categories', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        $grid = [['TARIFS 2026'], [], ['SPECIALITE SOIN', 'SOIN', 'COMPTANT', 'PRISE EN CHARGE'],
            ['Laboratoire', 'NFS', '3500', '4025'], ['Pediatrie', 'Visite du médecin', '5000', '5000'], ['Gyneco', 'Visite du médecin', '6000', '6900']];
        $this->assertSame(3, DataImport::headerRow($grid, 'tariff'));
        $rows = \App\Models\LabImportProfile::rowsFromGrid($grid, 3);

        $run = $this->import('tariff', $rows);
        $this->assertSame([3, 0], [$run->created, $run->failed]);
        $this->assertSame(['NFS', 'Laboratoire'], [DB::table('pathology_tests')->value('test_name'), DB::table('pathology_categories')->value('name')]);
        $this->assertSame(2, DB::table('charges')->where('code', 'Visite du médecin')->count(), 'same act in two specialities');
        $this->assertSame(3, (int) DB::table('charges')->value('charge_type'), 'first charge type of the list');
        $this->assertEquals(4025, DB::table('item_prices')->where(['priceable_type' => 'pathology_test', 'price_list_id' => 2])->value('price'));
        $this->assertSame(0, $this->import('tariff', $rows)->created, 'a second run updates');
    }

    public function test_departments_surgical_acts_and_coverage_rules(): void
    {
        $this->assertSame(1, $this->import('departments', [['Service' => 'Pédiatrie', 'Réduction %' => '10'], ['Service' => 'Pédiatrie']])->created);
        $this->assertEquals(10, DB::table('doctor_departments')->value('discount_percent'));

        $acts = $this->import('surgical_acts', [['Acte' => 'Césarienne', 'Catégorie' => 'Gynécologie', 'K chirurgien' => '80', 'K assistant' => '20'], ['Acte' => 'Hernie', 'K chirurgien' => 'abc']]);
        $this->assertSame([1, 1], [$acts->created, $acts->failed]);
        $this->assertSame([80.0, 20.0, 'Gynécologie'], [(float) DB::table('operations')->value('k_surgeon'), (float) DB::table('operations')->value('k_assistant'), DB::table('operation_categories')->value('name')]);
        $this->assertSame(1, $this->import('surgical_acts', [['Acte' => 'Césarienne', 'K chirurgien' => '90']])->updated);
        $this->assertSame(90.0, (float) DB::table('operations')->value('k_surgeon'));

        DB::table('insurances')->insert(['name' => 'UAB', 'insurance_code' => 'UAB']);
        $rules = $this->import('coverage_rules', [['Assureur' => 'UAB', 'Service' => 'Pédiatrie', 'Taux' => '80', 'Accord préalable' => 'oui'],
            ['Assureur' => 'UAB'], ['Assureur' => 'Nope', 'Service' => 'Pédiatrie']]);
        $this->assertSame([1, 2], [$rules->created, $rules->failed]);
        $rule = DB::table('coverage_rules')->first();
        $this->assertSame([80.0, 1, 0], [(float) $rule->coverage_percent, (int) $rule->needs_approval, (int) $rule->not_covered]);
        $this->assertSame(1, $this->import('coverage_rules', [['Assureur' => 'uab', 'Service' => 'Pédiatrie', 'Taux' => '90']])->updated);
        $this->assertSame(1, DB::table('coverage_rules')->count());
    }
}
