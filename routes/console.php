<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Планировщик задач
Schedule::command('cbr:fetch-rates')
    ->dailyAt('08:00')  // Каждый день в 8:00
    ->dailyAt('13:00')  // Каждый день в 13:00
    ->dailyAt('18:00')  // Каждый день в 18:00
    ->dailyAt('22:00')  // Каждый день в 22:00
    ->withoutOverlapping()
    ->runInBackground();
