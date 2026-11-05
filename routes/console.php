<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:sync-ad-accounts-data')
    ->cron('8-59/15 * * * *')
    ->withoutOverlapping(10)
    ->appendOutputTo(storage_path('logs/sync-ad-accounts-'.date('Y-m-d').'.log'));

Schedule::command('payment-verification:process')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->appendOutputTo(storage_path('logs/payment-verification-'.date('Y-m-d').'.log'));

Schedule::command('queue:work --queue=high,default,low --tries=3 --delay=60 --timeout=600 --stop-when-empty')
    ->everyMinute()
    ->runInBackground()
    ->withoutOverlapping(10)
    ->appendOutputTo(storage_path('logs/queue-'.date('Y-m-d').'.log'));
