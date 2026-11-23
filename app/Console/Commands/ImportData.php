<?php

namespace App\Console\Commands;

use App\Http\Controllers\DataImportController;
use App\Models\DataImportRun;
use App\Utils\DataImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/** Go-live import from the command line, for large files (same checks as the screen). */
class ImportData extends Command
{
    protected $signature = 'data:import {kind : '.'patients, policies, doctors, staff, insurers, suppliers, opening_stock or opening_balances} {file}
        {--store= : store id for opening stock lines without a store} {--prefix= : phone country code} {--date= : date of the opening balances}
        {--journal=OD : journal of the opening balances} {--commit : write the data (without it the file is only checked)}';

    protected $description = 'Check, then import, a file of starting data (CSV or Excel).';

    public function handle(): int
    {
        $kind = $this->argument('kind');
        if (! in_array($kind, DataImport::KINDS, true)) {
            $this->error('kind: '.implode(', ', DataImport::KINDS));

            return self::FAILURE;
        }
        $file = realpath($this->argument('file'));
        if (! $file) {
            $this->error('file not found');

            return self::FAILURE;
        }
        $path = Storage::putFileAs('imports', new \Illuminate\Http\File($file), uniqid('cli_', true).'.'.pathinfo($file, PATHINFO_EXTENSION));
        try {
            $options = array_filter(['store_id' => $this->option('store'), 'prefix' => $this->option('prefix'), 'date' => $this->option('date'), 'journal' => $this->option('journal')]);
            $rows = DataImportController::rows($path, basename($file));
            $import = new DataImport($kind, $options);
            if (! $this->option('commit')) {
                $p = $import->preview($rows);
                $this->info("lines {$p['total']}: to create {$p['create']}, already there {$p['exists']}, errors {$p['failed']}");
                foreach ($p['unknown'] ? ['unknown columns: '.implode(', ', $p['unknown'])] : [] as $w) {
                    $this->warn($w);
                }
                foreach (array_slice($p['errors'], 0, 50) as $e) {
                    $this->warn("line {$e['line']}: {$e['message']}");
                }

                return $p['errors'] ? self::FAILURE : self::SUCCESS;
            }
            $run = $import->run($rows, DataImportRun::create(['kind' => $kind, 'file_name' => basename($file), 'options' => $options]));
            $this->info("created {$run->created}, already there {$run->existing}, errors {$run->failed}".($run->note ? " ({$run->note})" : ''));
            foreach (array_slice($run->errors ?? [], 0, 50) as $e) {
                $this->warn("line {$e['line']}: {$e['message']}");
            }

            return $run->failed ? self::FAILURE : self::SUCCESS;
        } finally {
            Storage::delete($path);
        }
    }
}
