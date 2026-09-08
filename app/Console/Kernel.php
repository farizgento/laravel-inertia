<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('ldap:sync-users')->dailyAt('01:00')->withoutOverlapping();

        // Dijalankan sekali sehari pada jam kerja supaya email pengingat masuk
        // saat peminjam kemungkinan besar membacanya.
        $schedule->command('peminjaman:kirim-pengingat-pengembalian')
            ->dailyAt('07:00')
            ->withoutOverlapping();
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
