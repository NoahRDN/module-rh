<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('dashboard:refresh')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('payroll:refresh --mois=' . now()->format('Y-m'))->hourly()->withoutOverlapping();
Schedule::command('read-models:refresh --only=caisse --date=' . now()->format('Y-m-d'))->hourly()->withoutOverlapping();
