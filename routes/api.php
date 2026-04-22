<?php

use App\Http\Controllers\Api\V1\CurrenciesController;
use App\Http\Controllers\Api\V1\DynamicsController;
use App\Http\Controllers\Api\V1\RatesController;
use App\Http\Controllers\Api\V1\SettingController;
use Illuminate\Support\Facades\Route;

/**
 * API v1 — Курсы валют ЦБ РФ
 *
 * Базовый путь: /api/v1
 *
 * Эндпоинты:
 * - GET  /rates      — Курсы валют на дату (?date=, ?compare_date=, ?currencies=)
 * - GET  /currencies — Справочник валют
 * - GET  /dynamics   — Динамика курсов (?char_code=, ?from=, ?to=)
 * - GET  /settings   — Текущие настройки
 * - PUT  /settings   — Обновить настройки
 */
Route::prefix('v1')->group(function () {
    // Настройки приложения
    Route::get('settings', [SettingController::class, 'index']);
    Route::put('settings', [SettingController::class, 'update']);

    // Курсы валют на дату (сравнение с предыдущим днём)
    Route::get('rates', [RatesController::class, 'index']);

    // Справочник валют
    Route::get('currencies', [CurrenciesController::class, 'index']);

    // Динамика курсов валюты за период
    Route::get('dynamics', [DynamicsController::class, 'index']);
});
