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
     * Double-entry accounting: the accounts become a chart of accounts (code, class, parent), plus fiscal years,
     * journals and journal entries. A starting chart in the SYSCOHADA style (as the old program used) is added;
     * the accountant changes it freely.
     */
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->unique()->after('id');
            $table->string('account_class', 20)->nullable()->after('type');
            $table->unsignedInteger('parent_id')->nullable()->after('account_class');
            $table->boolean('is_postable')->default(true)->after('parent_id');
        });
        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 20)->default('open');
            $table->timestamps();
        });
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 100);
            $table->string('kind', 20);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('journal_id')->index();
            $table->unsignedBigInteger('fiscal_year_id')->index();
            $table->string('number', 40)->unique();
            $table->date('entry_date')->index();
            $table->string('reference', 100)->nullable();
            $table->string('label', 255);
            $table->string('source_type', 40)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_event', 40)->nullable();
            $table->unsignedBigInteger('reversal_of')->nullable();
            $table->dateTime('exported_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['source_type', 'source_id', 'source_event']);
        });
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('journal_entry_id')->index();
            $table->unsignedInteger('account_id')->index();
            $table->string('label', 255)->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->timestamps();
        });

        $now = now();
        // [code, name, class, normal balance (1 debit, 2 credit), postable]
        $chart = [
            ['1', 'Capitaux et ressources durables', 'equity', 2, false], ['101', 'Capital', 'equity', 2, true], ['121', 'Report à nouveau', 'equity', 2, true],
            ['3', 'Stocks', 'asset', 1, false], ['311', 'Marchandises - médicaments', 'asset', 1, true], ['331', 'Fournitures consommables', 'asset', 1, true],
            ['4', 'Tiers', 'asset', 1, false], ['401', 'Fournisseurs', 'liability', 2, true], ['4111', 'Clients - patients', 'asset', 1, true],
            ['4112', 'Clients - tiers payants', 'asset', 1, true], ['4191', 'Avances reçues des patients', 'liability', 2, true],
            ['447', 'État - impôts retenus à la source', 'liability', 2, true], ['4671', 'Médecins - honoraires à payer', 'liability', 2, true],
            ['4672', 'Apporteurs - commissions à payer', 'liability', 2, true],
            ['5', 'Trésorerie', 'asset', 1, false], ['521', 'Banque', 'asset', 1, true], ['554', 'Monnaie électronique', 'asset', 1, true],
            ['5711', 'Caisse principale', 'asset', 1, true], ['585', 'Virements de fonds', 'asset', 1, true],
            ['6', 'Charges', 'expense', 1, false], ['601', 'Achats de marchandises', 'expense', 1, true], ['6031', 'Variation des stocks de marchandises', 'expense', 1, true],
            ['605', 'Autres achats (fournitures)', 'expense', 1, true], ['632', 'Honoraires', 'expense', 1, true], ['651', 'Pertes sur créances', 'expense', 1, true],
            ['658', 'Charges diverses', 'expense', 1, true], ['661', 'Rémunérations du personnel', 'expense', 1, true],
            ['7', 'Produits', 'income', 2, false], ['701', 'Ventes de marchandises - pharmacie', 'income', 2, true], ['706100', 'Prestations de services - consultations', 'income', 2, true],
            ['706200', 'Prestations de services - hospitalisation', 'income', 2, true], ['706300', 'Prestations de services - examens', 'income', 2, true],
            ['758', 'Produits divers', 'income', 2, true],
        ];
        $ids = [];
        foreach ($chart as [$code, $name, $class, $type, $postable]) {
            if (DB::table('accounts')->where('code', $code)->exists()) {
                $ids[$code] = DB::table('accounts')->where('code', $code)->value('id');

                continue;
            }
            $parent = null;
            foreach ($ids as $c => $id) {
                if (strlen($c) === 1 && str_starts_with($code, $c) && $code !== $c) {
                    $parent = $id;
                }
            }
            $ids[$code] = DB::table('accounts')->insertGetId(['code' => $code, 'name' => $name, 'type' => $type, 'account_class' => $class,
                'parent_id' => $parent, 'is_postable' => $postable, 'status' => 1, 'created_at' => $now, 'updated_at' => $now]);
        }
        $journals = [['CA', 'Caisse', 'cash'], ['BQ', 'Banque', 'bank'], ['VT', 'Ventes', 'sales'], ['AC', 'Achats', 'purchases'],
            ['TP', 'Tiers payants', 'insurance'], ['OD', 'Opérations diverses', 'general']];
        foreach ($journals as [$code, $name, $kind]) {
            DB::table('journals')->insertOrIgnore(['code' => $code, 'name' => $name, 'kind' => $kind, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }
        $year = (int) $now->format('Y');
        if (! DB::table('fiscal_years')->exists()) {
            DB::table('fiscal_years')->insert(['name' => (string) $year, 'starts_on' => $year.'-01-01', 'ends_on' => $year.'-12-31', 'status' => 'open', 'created_at' => $now, 'updated_at' => $now]);
        }
        if (! DB::table('settings')->where('key', 'accounting_lock_date')->exists()) {
            DB::table('settings')->insert(['key' => 'accounting_lock_date', 'value' => '', 'created_at' => $now, 'updated_at' => $now]);
        }
        if (! Module::where('name', 'Accounting')->exists()) {
            Module::create(['name' => 'Accounting', 'is_active' => 1, 'route' => 'accounting.entries.index']);
        }
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
        Module::where('name', 'Accounting')->delete();
        DB::table('settings')->where('key', 'accounting_lock_date')->delete();
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('journals');
        Schema::dropIfExists('fiscal_years');
        DB::table('accounts')->whereNotNull('code')->whereNotExists(fn ($q) => $q->from('payments')->whereColumn('payments.account_id', 'accounts.id'))->delete();
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn(['code', 'account_class', 'parent_id', 'is_postable']);
        });
    }
};
