<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('vaccines:remind --days=7')->dailyAt('08:00');
        $schedule->command('vaccines:remind --days=3')->dailyAt('08:00');
        $schedule->command('appointments:generate-recurring')->dailyAt('03:00');
        $schedule->command('appointments:remind --days=1')->dailyAt('18:00');
        $schedule->command('recall:process')->weekly()->mondays()->at('09:00');
        $schedule->command('birthday:process')->dailyAt('08:00');
        $backupEnabled = \App\Models\Setting::get('backup.enabled', '1') === '1';
        if ($backupEnabled) {
            $freq = \App\Models\Setting::get('backup.frequency', 'daily');
            $time = \App\Models\Setting::get('backup.time', '01:00');
            $compress = \App\Models\Setting::get('backup.compress', '1') === '1' ? ' --compress' : '';

            $backupCmd = $schedule->command('backup:database' . $compress);

            match ($freq) {
                'every_six_hours' => $backupCmd->everySixHours(),
                'every_twelve_hours' => $backupCmd->everyTwelveHours(),
                'weekly' => $backupCmd->weekly()->sundays()->at($time),
                'monthly' => $backupCmd->monthlyOn(1, $time),
                default => $backupCmd->dailyAt($time),
            };

            $keepDays = (int) \App\Models\Setting::get('backup.keep_days', 30);
            $schedule->command("backup:cleanup --keep={$keepDays}")->dailyAt('02:00');
        }
        $schedule->command('queue:process')->everyMinute();
        $schedule->command('staff:remind')->dailyAt('18:00');
        $schedule->command('nfse:emit-pending')->everyTenMinutes();
        $schedule->command('nfe:emit-pending')->everyTenMinutes();
        $schedule->command('claims:auto-file')->everyThirtyMinutes();
        $schedule->command('stock:forecast --recalculate')->dailyAt('03:00');
        $schedule->command('stock:forecast --alert-expiry')->dailyAt('06:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
