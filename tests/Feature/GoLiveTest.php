<?php

namespace Tests\Feature;

use App\Console\Commands\BackupDatabase;
use App\Models\Setting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GoLiveTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('key');
            $t->text('value')->nullable();
            $t->timestamps();
        });
    }

    private function commands(): array
    {
        $schedule = new Schedule();
        (new \ReflectionMethod($this->app->make(\Illuminate\Contracts\Console\Kernel::class), 'schedule'))->invoke($this->app->make(\Illuminate\Contracts\Console\Kernel::class), $schedule);

        return collect($schedule->events())->mapWithKeys(fn ($e) => [trim(preg_replace('/^.*artisan["\']?\s+/', '', (string) $e->command)) ?: $e->description => $e->expression])->all();
    }

    public function test_night_tasks_follow_the_admin_settings(): void
    {
        $tasks = $this->commands();
        $this->assertSame('0 1 * * *', $tasks['db:backup'], 'backup on by default at 01:00');
        $this->assertArrayNotHasKey('accounting:post', $tasks, 'posting off until the rules are checked');
        $this->assertArrayHasKey('scheduler-heartbeat', $tasks);

        foreach (['backup_enabled' => '0', 'accounting_auto_post' => '1', 'accounting_post_time' => '03:15'] as $key => $value) {
            Setting::create(['key' => $key, 'value' => $value]);
        }
        $tasks = $this->commands();
        $this->assertArrayNotHasKey('db:backup', $tasks);
        $this->assertSame('15 3 * * *', $tasks['accounting:post']);
    }

    public function test_only_the_newest_backups_are_kept(): void
    {
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'hms_bk_'.uniqid();
        mkdir($dir);
        foreach (['hms_20261101_010000.sql.gz', 'hms_20261102_010000.sql.gz', 'hms_20261103_010000.sql.gz', 'hms_20261103_010000_files.zip', 'hms_20261101_010000_files.zip', 'notes.txt'] as $f) {
            touch($dir.DIRECTORY_SEPARATOR.$f);
        }
        BackupDatabase::prune($dir, 2);
        $left = array_map('basename', glob($dir.DIRECTORY_SEPARATOR.'*'));
        sort($left);
        $this->assertSame(['hms_20261101_010000_files.zip', 'hms_20261102_010000.sql.gz', 'hms_20261103_010000.sql.gz', 'hms_20261103_010000_files.zip', 'notes.txt'], $left);
        array_map('unlink', glob($dir.DIRECTORY_SEPARATOR.'*'));
        rmdir($dir);
    }
}
