<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Utils\Stock;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * Compressed copy of the database (and, if chosen, of the uploaded files) in storage/app/backups,
 * also copied to a second folder (another disk, a network share) when one is set. Old copies are removed.
 */
class BackupDatabase extends Command
{
    protected $signature = 'db:backup';

    protected $description = 'Back up the database (gzip) and optionally the uploaded files, keeping the last copies.';

    public static function folder(): string
    {
        return storage_path('app/backups');
    }

    public function handle(): int
    {
        $connection = config('database.connections.'.config('database.default'));
        if (! in_array($connection['driver'] ?? '', ['mysql', 'mariadb'], true)) {
            return $this->failed('only MySQL / MariaDB databases are backed up');
        }
        @mkdir(self::folder(), 0750, true);
        $name = $connection['database'].'_'.now()->format('Ymd_His');
        $file = self::folder().DIRECTORY_SEPARATOR.$name.'.sql.gz';

        // The password goes in a private option file, never on the command line.
        $options = tempnam(sys_get_temp_dir(), 'bk');
        $quote = fn ($v) => '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], (string) $v).'"';
        file_put_contents($options, "[client]\nuser=".$quote($connection['username'])."\npassword=".$quote($connection['password'])
            ."\nhost=".$quote($connection['host'])."\nport=".(int) ($connection['port'] ?? 3306)."\n");
        @chmod($options, 0600);
        $error = '';
        try {
            $gz = gzopen($file.'.part', 'wb6');
            $process = new Process([Stock::setting('backup_dump_path', 'mysqldump') ?: 'mysqldump', '--defaults-extra-file='.$options,
                '--single-transaction', '--quick', '--routines', '--triggers', '--default-character-set=utf8mb4', $connection['database']],
                // Windows sockets need SystemRoot, which a web server process may not pass on.
                null, PHP_OS_FAMILY === 'Windows' ? ['SystemRoot' => getenv('SystemRoot') ?: (getenv('windir') ?: 'C:\Windows')] : null);
            $process->setTimeout(3600)->run(function ($type, $buffer) use ($gz, &$error) {
                $type === Process::OUT ? gzwrite($gz, $buffer) : $error .= $buffer;
            });
            gzclose($gz);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        } finally {
            @unlink($options);
        }
        if (! isset($process) || ! $process->isSuccessful()) {
            @unlink($file.'.part');

            return $this->failed(trim($error) ?: 'mysqldump failed');
        }
        rename($file.'.part', $file);
        $made = [$file];

        if (Stock::setting('backup_include_files', '0') === '1' && is_dir(storage_path('app/public'))) {
            $zipFile = self::folder().DIRECTORY_SEPARATOR.$name.'_files.zip';
            $zip = new \ZipArchive();
            if ($zip->open($zipFile, \ZipArchive::CREATE) === true) {
                $base = strlen(storage_path('app/public')) + 1;
                foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(storage_path('app/public'), \FilesystemIterator::SKIP_DOTS)) as $f) {
                    $zip->addFile($f->getPathname(), str_replace('\\', '/', substr($f->getPathname(), $base)));
                }
                $zip->close();
                $made[] = $zipFile;
            }
        }

        $copyTo = trim((string) Stock::setting('backup_copy_path', ''));
        if ($copyTo !== '') {
            if (! is_dir($copyTo) || ! is_writable($copyTo)) {
                $error = 'second copy: folder not writable: '.$copyTo;
            } else {
                foreach ($made as $f) {
                    copy($f, rtrim($copyTo, '/\\').DIRECTORY_SEPARATOR.basename($f));
                }
                self::prune($copyTo, (int) Stock::setting('backup_keep', 14));
            }
        }
        self::prune(self::folder(), (int) Stock::setting('backup_keep', 14));

        foreach (['backup_last_run' => now()->toDateTimeString(), 'backup_last_file' => basename($file), 'backup_last_error' => $error] as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        $this->info('backup '.basename($file).' ('.round(filesize($file) / 1048576, 1).' MB)');
        if ($error) {
            $this->warn($error);
        }

        return self::SUCCESS;
    }

    /** Keep the newest $keep backups (database dumps and file archives counted separately). */
    public static function prune(string $folder, int $keep): void
    {
        foreach (['*.sql.gz', '*_files.zip'] as $pattern) {
            $files = glob(rtrim($folder, '/\\').DIRECTORY_SEPARATOR.$pattern) ?: [];
            rsort($files); // names carry the date and time
            foreach (array_slice($files, max(1, $keep)) as $old) {
                @unlink($old);
            }
        }
    }

    private function failed(string $message): int
    {
        Setting::updateOrCreate(['key' => 'backup_last_error'], ['value' => now()->toDateTimeString().' '.$message]);
        $this->error($message);

        return self::FAILURE;
    }
}
