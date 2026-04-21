<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WidgetRatesResource;
use App\Models\Rate;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class WidgetController extends Controller
{
    public function __construct(
        private readonly SettingsService $settingsService,
    ) {}

    /**
     * Получить курсы валют для виджета.
     * Возвращает курсы на сегодня и вчера для валют, настроенных для отображения в виджете.
     * Данные кэшируются на 1 час.
     */
    public function rates(): JsonResponse
    {
        // Получаем список валют, которые должны отображаться в виджете
        $currencies = $this->settingsService->getWidgetCurrencies();

        // Определяем даты: сегодня и вчера
        $today = Carbon::today();
        $yesterday = $today->copy()->subDay();

        // Генерируем уникальный ключ кэша на основе списка валют и даты
        $cacheKey = 'widget_rates_'.md5(implode(',', $currencies).'_'.$today->toDateString());

        // Кэшируем данные на 1 час (3600 секунд)
        $data = Cache::remember($cacheKey, 3600, function () use ($currencies, $today, $yesterday) {
            // Запрашиваем курсы валют на сегодня для настроенных валют
            $todayRates = Rate::whereHas('currency', function ($query) use ($currencies) {
                $query->whereIn('char_code', $currencies);
            })
                ->where('date', $today)
                ->with('currency:id,char_code,name') // Загружаем связанные данные валюты
                ->get()
                ->keyBy('currency.char_code'); // Индексируем по коду валюты

            // Запрашиваем курсы валют на вчера для тех же валют
            $yesterdayRates = Rate::whereHas('currency', function ($query) use ($currencies) {
                $query->whereIn('char_code', $currencies);
            })
                ->where('date', $yesterday)
                ->with('currency:id,char_code') // Загружаем код валюты
                ->get()
                ->keyBy('currency.char_code'); // Индексируем по коду валюты

            // Формируем результирующий массив данных
            $result = [];
            foreach ($currencies as $currencyCode) {
                // Получаем данные за сегодня и вчера для текущей валюты
                $todayRate = $todayRates->get($currencyCode);
                $yesterdayRate = $yesterdayRates->get($currencyCode);

                // Структура данных для каждой валюты
                $result[$currencyCode] = [
                    'currency' => $todayRate ? [
                        'char_code' => $todayRate->currency->char_code,
                        'name' => $todayRate->currency->name,
                    ] : null, // Информация о валюте (если данные есть)
                    'today' => $todayRate ? [
                        'value' => $todayRate->value,
                        'vunit_rate' => $todayRate->vunit_rate,
                    ] : null, // Курс на сегодня
                    'yesterday' => $yesterdayRate ? [
                        'value' => $yesterdayRate->value,
                        'vunit_rate' => $yesterdayRate->vunit_rate,
                    ] : null, // Курс на вчера (для сравнения)
                ];
            }

            return $result;
        });

        // Возвращаем данные в формате JSON через ресурс
        return response()->json(new WidgetRatesResource($data));
    }
}
