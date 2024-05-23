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
        $schedule->command('send:specific-companies-to-env-api')->hourlyAt(15);
        $schedule->command('send:specific-companies-to-env-api')->hourlyAt(45);
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        $this->command('send:specific-reservoirs-to-env-api', function () {
            return app()->make('App\Console\Commands\SendSpecificCompaniesToApi')->handle();
        });

        require base_path('routes/console.php');
    }
}
