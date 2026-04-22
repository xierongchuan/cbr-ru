<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Страницы виджета
Route::get('/widget', function () {
    return view('widget');
});

Route::get('/settings', function () {
    return view('settings');
});
