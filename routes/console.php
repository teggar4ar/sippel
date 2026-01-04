<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| Define scheduled tasks using the Schedule facade.
|
*/

// Calculate and cache report statistics daily at 1:00 AM
Schedule::command('reports:calculate')->dailyAt('01:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/reports-calculate.log'));
