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
        // ── Temp group (every 30 minutes) ────────────────────────────────
        // Cleans two directories:
        //   api_certificates_temp/  — preview images older than 30 minutes
        //   tmp_uploads/            — uploaded photos, imports, PDF/ZIP downloads
        //                             older than 2 hours
        $schedule->command('files:cleanup --temp-only --force')
            ->everyThirtyMinutes()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/cleanup.log'));

        // ── Generated group (daily at 02:00) ─────────────────────────────
        // Cleans two directories:
        //   certificates/           — web-generated certificate images older than 24h
        //   api_certificates/       — API-generated certificates older than 48h
        //                             (signed download URLs are valid 24h; 48h is
        //                             a safe grace period before deletion)
        $schedule->command('files:cleanup --generated-only --force')
            ->dailyAt('02:00')
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/cleanup.log'));
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
