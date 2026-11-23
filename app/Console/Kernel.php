<?php

namespace App\Console;

use App\Console\Commands\SendAppointmentReminderEmail;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Cache;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\AppointmentReminder::class,
        SendAppointmentReminderEmail::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('appointment:reminder')->daily();
        $schedule->command(SendAppointmentReminderEmail::class)->hourly();
        // Seen by the go-live check page: proves the server runs "schedule:run" every minute.
        $schedule->call(fn () => Cache::put('scheduler_last_seen', now()->timestamp, 86400))->everyMinute()->name('scheduler-heartbeat');

        // Night tasks switched on and timed by the admin (Settings > Go-live check).
        try {
            $time = fn ($key, $default) => preg_match('/^\d{2}:\d{2}$/', (string) \App\Utils\Stock::setting($key)) ? \App\Utils\Stock::setting($key) : $default;
            if (\App\Utils\Stock::setting('backup_enabled', '1') === '1') {
                $schedule->command('db:backup')->dailyAt($time('backup_time', '01:00'))->withoutOverlapping();
            }
            if (\App\Utils\Stock::setting('accounting_auto_post', '0') === '1') {
                $schedule->command('accounting:post')->dailyAt($time('accounting_post_time', '02:00'))->withoutOverlapping();
            }
        } catch (\Throwable) {
            // no database yet (installation)
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
