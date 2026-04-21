<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Планировщик задач
Schedule::command('cbr:fetch-rates')
    ->dailyAt('09:00')  // Каждый день в 9:00
    ->dailyAt('15:00')  // Каждый день в 15:00
    ->withoutOverlapping()  // Предотвращает пересечение задач
    ->runInBackground();   // Запуск в фоне
