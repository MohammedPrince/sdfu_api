<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Scheduled notification campaigns (daily / weekly, e.g. Google Play testers).
// Each campaign sends at most once a day at its own send_time (default 13:30),
// so the check runs every minute.
Schedule::command('notifications:scheduled-campaigns')
    ->everyMinute()
    ->withoutOverlapping();
