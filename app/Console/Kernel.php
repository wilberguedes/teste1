<?php
/**
 * Concord CRM - https://www.concordcrm.com
 *
 * @version   1.2.2
 *
 * @link      Releases - https://www.concordcrm.com/releases
 * @link      Terms Of Service - https://www.concordcrm.com/terms
 *
 * @copyright Copyright (c) 2022-2023 KONKORD DIGITAL
 */

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Console\PruneCommand as PruneModelCommand;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Queue\Console\FlushFailedCommand;
use Illuminate\Support\Facades\Artisan;
use Modules\Core\Facades\Innoclapps;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(function () {
            settings()->set(['last_cron_run' => now()])->save();
        })->everyMinute();

        if (app()->environment('local')) {
            $schedule->command('telescope:prune')->daily();
        }

        if (Innoclapps::canRunProcess()) {
            $this->scheduleWithProcess($schedule);
        } else {
            $this->scheduleWithoutProcess($schedule);
        }
    }

    /**
     * Define the application's command schedule when the proc_* functions are available.
     */
    protected function scheduleWithProcess(Schedule $schedule): void
    {
        $schedule->command(PruneModelCommand::class)->daily();
        $schedule->command(FlushFailedCommand::class)->weekly();
    }

    /**
     * Define the application's command schedule when the proc_* functions are not available.
     */
    protected function scheduleWithoutProcess(Schedule $schedule): void
    {
        $schedule->call(function () {
            Artisan::call(PruneModelCommand::class);
        })->daily();

        $schedule->call(function () {
            Artisan::call(FlushFailedCommand::class);
        })->weekly();
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
