<?php

use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\WidgetController;
use Illuminate\Support\Facades\Route;

// Версионированная API v1
Route::prefix('v1')->group(function () {
    // API для виджета
    Route::prefix('widget')->group(function () {
        Route::get('rates', [WidgetController::class, 'rates']);
    });

    // API для настроек
    Route::get('settings', [SettingController::class, 'index']);
    Route::put('settings', [SettingController::class, 'update']);
});
