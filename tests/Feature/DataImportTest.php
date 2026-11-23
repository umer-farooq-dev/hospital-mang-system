<?php

namespace Tests\Feature;

use App\Models\DataImportRun;
use App\Utils\DataImport;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DataImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $tables = [
            'settings' => fn ($t) => [$t->string('key'), $t->text('value')->nullable()],
            'master_list_items' => fn ($t) => [$t->string('type'), $t->unsignedInteger('code'), $t->string('name'), $t->string('lang_key')->nullable(),
                $t->unsignedInteger('sort_order')->default(0), $t->boolean('is_active')->default(true)],
            'suppliers' => fn ($t) => [$t->string('name'), $t->string('contact_person')->nullable(), $t->string('phone')->nullable(), $t->string('email')->nullable(),
                $t->text('address')->nullable(), $t->string('tax_number')->nullable(), $t->boolean('is_active')->default(true)],
            'insurances' => fn ($t) => [$t->string('name'), $t->unsignedInteger('payer_type')->default(1), $t->decimal('coverage_percent', 5, 2)->nullable(), $t->double('discount')->nullable(),
                $t->string('insurance_code'), $t->string('contact_person')->nullable(), $t->string('phone')->nullable(), $t->string('email')->nullable(),
                $t->text('address')->nullable(), $t->string('billing_option')->default('per_visit'), $t->unsignedInteger('credit_days')->nullable(),
                $t->text('claim_note')->nullable(), $t->double('service_tax')->default(0), $t->double('hospital_rate')->default(0), $t->double('total')->default(0), $t->boolean('status')],
            'stores' => fn ($t) => [$t->string('name'), $t->string('code')->nullable(), $t->boolean('is_active')->default(true), $t->unsignedInteger('sort_order')->default(0)],
            'medicines' => fn ($t) => [$t->string('name'), $t->string('cis_code')->nullable(), $t->string('barcode')->nullable(), $t->double('buying_price')->default(0),
                $t->integer('available_quantity')->default(0)],
            'stock_batches' => fn ($t) => [$t->unsignedBigInteger('store_id'), $t->string('stockable_type'), $t->unsignedBigInteger('stockable_id'), $t->string('batch_no')->nullable(),
                $t->date('expiry_date')->nullable(), $t->decimal('unit_cost', 14, 2)->default(0), $t->integer('quantity')->default(0), $t->unsignedBigInteger('supplier_id')->nullable()],
            'stock_movements' => fn ($t) => [$t->unsignedBigInteger('store_id'), $t->unsignedBigInteger('stock_batch_id'), $t->string('stockable_type'), $t->unsignedBigInteger('stockable_id'),
                $t->integer('quantity'), $t->decimal('unit_cost', 14, 2)->default(0), $t->string('movement_type'), $t->string('document_type')->nullable(),
                $t->unsignedBigInteger('document_id')->nullable(), $t->unsignedBigInteger('user_id')->nullable(), $t->string('note')->nullable()],
            'accounts' => fn ($t) => [$t->string('code')->nullable(), $t->string('name'), $t->tinyInteger('type')->default(1), $t->string('account_class')->nullable(),
                $t->unsignedInteger('parent_id')->nullable(), $t->boolean('is_postable')->default(true), $t->boolean('status')->default(1)],
            'fiscal_years' => fn ($t) => [$t->string('name'), $t->date('starts_on'), $t->date('ends_on'), $t->string('status')->default('open')],
            'journals' => fn ($t) => [$t->string('code'), $t->string('name'), $t->string('kind'), $t->boolean('is_active')->default(true)],
            'journal_entries' => fn ($t) => [$t->unsignedBigInteger('journal_id'), $t->unsignedBigInteger('fiscal_year_id'), $t->string('number'), $t->date('entry_date'),
                $t->string('reference')->nullable(), $t->string('label'), $t->string('source_type')->nullable(), $t->unsignedBigInteger('source_id')->nullable(),
                $t->string('source_event')->nullable(), $t->unsignedBigInteger('reversal_of')->nullable(), $t->dateTime('exported_at')->nullable(), $t->unsignedBigInteger('created_by')->nullable()],
            'journal_lines' => fn ($t) => [$t->unsignedBigInteger('journal_entry_id'), $t->unsignedInteger('account_id'), $t->string('label')->nullable(),
                $t->decimal('debit', 15, 2)->default(0), $t->decimal('credit', 15, 2)->default(0)],
            'data_import_runs' => fn ($t) => [$t->string('kind'), $t->string('file_name'), $t->unsignedBigInteger('user_id')->nullable(), $t->json('options')->nullable(),
                $t->unsignedInteger('created')->default(0), $t->unsignedInteger('updated')->default(0), $t->unsignedInteger('existing')->default(0), $t->unsignedInteger('failed')->default(0), $t->json('errors')->nullable(), $t->string('note')->nullable()],
        ];
        foreach ($tables as $name => $columns) {
            Schema::create($name, function (Blueprint $t) use ($columns) {
                $t->id();
                $columns($t);
                $t->timestamps();
            });
        }
        DB::table('master_list_items')->insert(['type' => 'payer_type', 'code' => 2, 'name' => 'Company']);
    }

    private function run_(string $kind, array $rows, array $options = []): DataImportRun
    {
        return (new DataImport($kind, $options))->run($rows, DataImportRun::create(['kind' => $kind, 'file_name' => 'f.csv']));
    }

    public function test_titles_in_french_or_english_values_and_errors_are_checked_before_anything_is_written(): void
    {
        $rows = [['Raison sociale' => 'SONAPHARM', 'Téléphone' => '25 30 00 00', 'E-mail' => 'x@y.bf', 'Remarque' => 'z'],
            ['Raison sociale' => 'Laborex', 'E-mail' => 'pas-un-mail'], ['Raison sociale' => '', 'Téléphone' => '']];
        $preview = (new DataImport('suppliers'))->preview($rows);
        $this->assertSame([2, 1, 0, 1], [$preview['total'], $preview['create'], $preview['exists'], $preview['failed']], 'empty line ignored');
        $this->assertSame(['Remarque'], $preview['unknown']);
        $this->assertSame(3, $preview['errors'][0]['line']);
        $this->assertSame(0, DB::table('suppliers')->count(), 'preview writes nothing');

        $run = $this->run_('suppliers', $rows);
        $this->assertSame([1, 0, 1], [$run->created, $run->existing, $run->failed]);
        $this->assertSame('25 30 00 00', DB::table('suppliers')->value('phone'));
        $again = $this->run_('suppliers', $rows);
        $this->assertSame([0, 1], [$again->created, $again->existing], 'never created twice');
    }

    public function test_insurers_get_the_payer_type_from_the_master_list(): void
    {
        $run = $this->run_('insurers', [['Name' => 'UAB Vie', 'Payer type' => 'company', 'Coverage %' => '80,5', 'Credit days' => '45'],
            ['Name' => 'X', 'Payer type' => 'Unknown'], ['Name' => 'Y', 'Coverage %' => '120']]);
        $this->assertSame([1, 2], [$run->created, $run->failed]);
        $row = DB::table('insurances')->first();
        $this->assertSame([2, 80.5, 45, 'UABVIE'], [(int) $row->payer_type, (float) $row->coverage_percent, (int) $row->credit_days, $row->insurance_code]);
    }

    public function test_opening_stock_goes_in_by_store_and_batch_once(): void
    {
        $pharmacy = DB::table('stores')->insertGetId(['name' => 'Pharmacie', 'code' => 'PH']);
        $ward = DB::table('stores')->insertGetId(['name' => 'Urgences']);
        DB::table('medicines')->insert([['name' => 'Paracétamol 500', 'cis_code' => '6000001', 'buying_price' => 25], ['name' => 'Amoxicilline', 'cis_code' => null, 'buying_price' => 0]]);
        $rows = [['Code' => '6000001', 'Dépôt' => 'PH', 'Lot' => 'L1', 'Péremption' => '31/12/2027', 'Quantité' => '100'],
            ['Médicament' => 'Amoxicilline', 'Lot' => 'A9', 'Quantité' => '40', 'Coût unitaire' => '150'],
            ['Médicament' => 'Inconnu', 'Quantité' => '5'], ['Code' => '6000001', 'Quantité' => '2,5']];
        $preview = (new DataImport('opening_stock', ['store_id' => $ward]))->preview($rows);
        $this->assertSame([2, 2], [$preview['create'], $preview['failed']]);
        $this->assertEquals(100 * 25 + 40 * 150, $preview['value']);

        $run = $this->run_('opening_stock', $rows, ['store_id' => $ward]);
        $this->assertSame(2, $run->created);
        $batch = DB::table('stock_batches')->where('batch_no', 'L1')->first();
        $this->assertSame([$pharmacy, '2027-12-31', 100, 25.0], [(int) $batch->store_id, $batch->expiry_date, (int) $batch->quantity, (float) $batch->unit_cost]);
        $this->assertSame($ward, (int) DB::table('stock_batches')->where('batch_no', 'A9')->value('store_id'), 'default store');
        $this->assertSame(['opening'], DB::table('stock_movements')->distinct()->pluck('movement_type')->all());
        $this->assertSame(100, (int) DB::table('medicines')->where('cis_code', '6000001')->value('available_quantity'));

        $again = $this->run_('opening_stock', $rows, ['store_id' => $ward]);
        $this->assertSame([0, 2], [$again->created, $again->existing]);
        $this->assertSame(100, (int) DB::table('stock_batches')->where('batch_no', 'L1')->value('quantity'), 'not doubled');
    }

    public function test_opening_balances_post_one_balanced_entry_once_and_nothing_when_a_line_is_wrong(): void
    {
        foreach (['5711', '311', '101', '4'] as $code) {
            DB::table('accounts')->insert(['code' => $code, 'name' => 'A'.$code, 'is_postable' => $code !== '4']);
        }
        DB::table('fiscal_years')->insert(['name' => '2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);
        DB::table('journals')->insert(['code' => 'OD', 'name' => 'Opérations diverses', 'kind' => 'general']);
        $options = ['date' => '2026-11-30', 'journal' => 'OD'];

        $wrong = $this->run_('opening_balances', [['Compte' => '5711', 'Débit' => '1 500 000'], ['Compte' => '4', 'Crédit' => '1 500 000']], $options);
        $this->assertSame(0, $wrong->created);
        $this->assertSame(0, DB::table('journal_entries')->count(), 'nothing posted');
        $unbalanced = (new DataImport('opening_balances', $options))->preview([['Compte' => '5711', 'Débit' => '10'], ['Compte' => '101', 'Crédit' => '9']]);
        $this->assertSame(0, $unbalanced['errors'][0]['line'], 'balance checked on the preview');

        $rows = [['Compte' => '5711', 'Libellé' => 'Caisse', 'Débit' => '1 500 000'], ['Compte' => '311', 'Débit' => '8500'], ['Compte' => '101', 'Crédit' => '1508500']];
        $run = $this->run_('opening_balances', $rows, $options);
        $this->assertSame([3, 0], [$run->created, $run->failed]);
        $entry = DB::table('journal_entries')->first();
        $this->assertSame(['OD26-00001', 'opening', '2026-11-30'], [$entry->number, $entry->source_event, substr($entry->entry_date, 0, 10)]);
        $this->assertEquals(1508500, DB::table('journal_lines')->sum('debit'));

        $again = $this->run_('opening_balances', $rows, $options);
        $this->assertSame(0, $again->created);
        $this->assertStringContainsString('OD26-00001', $again->errors[0]['message']);
    }

    public function test_dates_and_numbers_from_spreadsheets(): void
    {
        DB::table('accounts')->insert([['code' => '5711', 'name' => 'C'], ['code' => '101', 'name' => 'K']]);
        $import = new DataImport('opening_balances');
        $date = (new \ReflectionMethod($import, 'date'));
        $this->assertSame(['2026-11-05', '2026-11-05', '1985-03-02', '2026-11-05'],
            [$date->invoke($import, '05/11/2026', 'dob'), $date->invoke($import, '46331', 'dob'), $date->invoke($import, '02.03.85', 'dob'), $date->invoke($import, '2026-11-05', 'dob')]);
        $number = (new \ReflectionMethod($import, 'number'));
        $this->assertSame([1250.5, 1250.5, 1500000.0], [$number->invoke($import, '1 250,50', 'debit'), $number->invoke($import, '1,250.50', 'debit'), $number->invoke($import, '1 500 000', 'debit')]);
        $this->expectException(\RuntimeException::class);
        $date->invoke($import, '31/02/2026', 'dob');
    }
}
