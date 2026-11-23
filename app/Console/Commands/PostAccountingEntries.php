<?php

namespace App\Console\Commands;

use App\Utils\Posting;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/** Post the automatic accounting entries (for a scheduler, e.g. every night for the previous days). */
class PostAccountingEntries extends Command
{
    protected $signature = 'accounting:post {from? : first day (default: 7 days ago)} {to? : last day (default: yesterday)}';

    protected $description = 'Post the journal entries of the activity between two dates (never twice).';

    public function handle(): int
    {
        $from = Carbon::parse($this->argument('from') ?: now()->subDays(7));
        $to = Carbon::parse($this->argument('to') ?: now()->subDay());
        $result = Posting::generate($from, $to);
        $this->info("posted {$result['posted']}, already posted {$result['skipped']}, errors ".count($result['errors']));
        foreach ($result['errors'] as $error) {
            $this->warn($error);
        }

        return $result['errors'] ? self::FAILURE : self::SUCCESS;
    }
}
