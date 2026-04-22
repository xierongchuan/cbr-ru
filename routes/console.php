<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Планировщик задач — синхронизация всех курсов (сегодня + вчера)
Schedule::command('cbr:fetch-rates --date=both --all')
    ->dailyAt('08:00')
    ->dailyAt('13:00')
    ->dailyAt('18:00')
    ->dailyAt('22:00')
    ->withoutOverlapping()
    ->runInBackground();
