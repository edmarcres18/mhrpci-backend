<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled Tasks
// -------------------------------------------------

/**
 * Database Backup Schedule
 * 
 * Automatically creates database backups and cleans old files.
 * Customize the schedule according to your needs:
 * 
 * - daily() - Run once a day at midnight
 * - dailyAt('02:00') - Run daily at 2:00 AM
 * - twiceDaily(1, 13) - Run at 1:00 AM and 1:00 PM
 * - weekly() - Run once a week on Sunday at midnight
 * - monthly() - Run once a month on the first day
 * - everyMinute() - Run every minute (testing only)
 * 
 * Timezone Configuration:
 * The schedule respects your application's timezone setting from config/app.php
 * Default: 'timezone' => 'UTC'
 * Change to your local timezone (e.g., 'Asia/Manila', 'America/New_York', 'Europe/London')
 */

// Daily backup at 2:00 AM, keeping 30 days of backups
Schedule::command('backup:database --keep-days=30')
    ->dailyAt('02:00')
    ->timezone(config('app.timezone'))
    ->onSuccess(function () {
        info('Database backup completed successfully at ' . now()->toDateTimeString());
    })
    ->onFailure(function () {
        error('Database backup failed at ' . now()->toDateTimeString());
    });

// Weekly cleanup on Sundays at 3:00 AM, removing backups older than 30 days
Schedule::command('backup:cleanup --days=30')
    ->weeklyOn(0, '03:00')
    ->timezone(config('app.timezone'))
    ->onSuccess(function () {
        info('Backup cleanup completed successfully at ' . now()->toDateTimeString());
    });
