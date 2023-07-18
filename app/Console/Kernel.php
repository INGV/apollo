<?php

namespace App\Console;

use App\Api\v2\Controllers\PopulateCacheController;
use App\Api\v2\Requests\PopulateCacheRequest;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Validator;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        /* Set log file: It must be the same set into 'docker/apollo/extra/etc/cron/application' */
        $log_file = storage_path('logs/' . date('Ymd') . '__laravelSchedulerFromCrontab.log');

        /* Remove old logs(s) */
        $schedule->command('ingv-logging:clear --keep_last=files 31')
            ->name('schedule__ingv-logging')
            ->withoutOverlapping()
            ->daily()
            ->appendOutputTo($log_file);

        /* Laravel-Horizon Metrics */
        $schedule->command('horizon:snapshot')
            ->name('schedule__horizon-snapshot')
            ->withoutOverlapping()
            ->everyFiveMinutes()
            ->appendOutputTo($log_file);

        /* Populare Cache */
        $schedule->call(function () {
            $insertRequest = new PopulateCacheRequest();
            $insertRequest->setValidator(Validator::make([
                //'net' => 'NI',
                //'sta' => 'ACER',
                //'cha' => 'HHZ',
                //'cache' => 'true',
            ], $insertRequest->rules()));
            (new PopulateCacheController())->query($insertRequest);
        })
            ->cron('00 10 * * *')
            ->name('schedule__populate-cache')
            ->withoutOverlapping()
            ->onOneServer()
            ->appendOutputTo($log_file);

        $schedule->command('migrate --isolated=true -vvv')
            ->name('schedule__migrate')
            ->withoutOverlapping()
            ->everyMinute()
            ->appendOutputTo($log_file);
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
