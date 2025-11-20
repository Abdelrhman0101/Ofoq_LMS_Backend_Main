<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Database Backup Scheduler
// Backup every 3 days at 2:00 AM (cron: minute hour day-of-month month day-of-week)
Schedule::command('backup:run')->cron('0 2 */3 * *');
Schedule::command('backup:clean')->daily()->at('03:00');
Schedule::command('backup:monitor')->daily()->at('04:00');
