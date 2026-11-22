<?php

namespace Tests\Feature;

use App\Models\Medicine;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MedicineImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('medicines', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('category_id')->nullable();
            $t->unsignedInteger('brand_id')->nullable();
            $t->string('name');
            $t->string('common_name')->nullable();
            $t->string('barcode')->nullable();
            $t->string('cis_code')->nullable();
            $t->unsignedInteger('unit')->nullable();
            $t->unsignedInteger('dosage_form')->nullable();
            $t->unsignedInteger('route')->nullable();
            $t->double('selling_price')->default(0);
            $t->double('buying_price')->default(0);
            $t->string('salt_composition')->nullable();
            $t->integer('quantity')->default(0);
            $t->integer('available_quantity')->default(0);
            $t->timestamps();
        });
        foreach (['categories', 'brands'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->timestamps();
            });
        }
        Schema::create('master_list_items', function (Blueprint $t) {
            $t->id();
            $t->string('type');
            $t->unsignedInteger('code');
            $t->string('name');
            $t->string('lang_key')->nullable();
            $t->unsignedInteger('quantity')->nullable();
            $t->unsignedInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        DB::table('categories')->insert([['id' => 1, 'name' => 'Général'], ['id' => 2, 'name' => 'Antibiotiques']]);
        DB::table('brands')->insert(['id' => 1, 'name' => 'Divers']);
        DB::table('master_list_items')->insert([
            ['type' => 'dosage_form', 'code' => 1, 'name' => 'Comprimé'], ['type' => 'medicine_route', 'code' => 1, 'name' => 'Orale'],
        ]);
    }

    public function test_reference_list_creates_then_updates_by_cis_code_and_reports_unknown_values(): void
    {
        $summary = Medicine::importRows([
            ['Code CIS' => '61234567', 'Nom' => 'Amoxicilline 500 mg', 'DCI' => 'amoxicilline', 'Forme' => 'comprimé', 'Voie' => 'Orale', 'Catégorie' => 'Antibiotiques', 'Prix vente' => '1 250,50'],
            ['Code CIS' => '69999999', 'Nom' => 'Sérum', 'Forme' => 'Poche', 'Voie' => ''],
            ['Code CIS' => '', 'Nom' => ''],
        ], 1, 1);

        $this->assertSame([2, 0, 1], [$summary['created'], $summary['updated'], $summary['skipped']]);
        $this->assertSame(['dosage_form' => ['Poche']], $summary['unknown'], 'not invented: reported');
        $amox = Medicine::where('cis_code', '61234567')->first();
        $this->assertSame(['amoxicilline', 1, 1, 2, 1250.5], [$amox->common_name, $amox->dosage_form, $amox->route, $amox->category_id, $amox->selling_price]);
        $this->assertNull(Medicine::where('cis_code', '69999999')->value('dosage_form'));

        $again = Medicine::importRows([['CIS' => '61234567', 'Name' => 'Amoxicilline 500 mg gélule', 'Barcode' => '3400930000001']], 1, 1);
        $this->assertSame([0, 1], [$again['created'], $again['updated']]);
        $this->assertSame('Amoxicilline 500 mg gélule', $amox->fresh()->name, 'matched by CIS code, renamed');
        $this->assertSame(2, (int) $amox->fresh()->category_id, 'category kept when the file has none');
    }
}
