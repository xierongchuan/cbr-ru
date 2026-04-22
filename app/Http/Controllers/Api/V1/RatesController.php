<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Rate;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Контроллер курсов валют.
 *
 * Предоставляет API для получения курсов валют на заданную дату
 * с возможностью сравнения с предыдущим днём.
 */
class RatesController extends Controller
{
    /**
     * Получить курсы валют.
     *
     * Параметры запроса:
     * - date (опционально) - дата в формате YYYY-MM-DD (по умолчанию - сегодня)
     * - compare_date (опционально) - дата для сравнения в формате YYYY-MM-DD
     * - currencies (опционально) - список кодов валют через запятую
     *
     * Пример запроса:
     *   GET /api/v1/rates?date=2026-04-22&compare_date=2026-04-21&currencies=USD,EUR
     */
    public function index(Request $request): JsonResponse
    {
        // Дата курсов (по умолчанию - сегодня)
        $date = $request->query("date");

        if ($date) {
            $date = Carbon::parse($date)->startOfDay();
        } else {
            $date = Carbon::today();
        }

        // Дата для сравнения (например, вчера)
        $compareDate = $request->query("compare_date");

        if ($compareDate) {
            $compareDate = Carbon::parse($compareDate)->startOfDay();
        }

        // Фильтр по валютам
        $charCodes = $request->query("currencies");

        if ($charCodes) {
            $charCodes = is_string($charCodes)
                ? explode(",", $charCodes)
                : $charCodes;
        }

        // Проверяем, все ли запрошенные валюты синхронизированы
        $missingCurrencies = [];
        if ($charCodes) {
            $existingCodes = Currency::whereIn("char_code", $charCodes)
                ->pluck("char_code")
                ->toArray();
            $missingCurrencies = array_diff($charCodes, $existingCodes);
        }

        // Получаем курсы на основную дату
        $rates = Rate::where("date", $date)
            ->with("currency:id,char_code,name,nominal,cbr_id")
            ->when($charCodes, function ($query) use ($charCodes) {
                $query->whereHas(
                    "currency",
                    fn($q) => $q->whereIn("char_code", $charCodes),
                );
            })
            ->get()
            ->map(
                fn(Rate $rate) => [
                    "char_code" => $rate->currency->char_code,
                    "name" => $rate->currency->name,
                    "nominal" => $rate->currency->nominal,
                    "cbr_id" => $rate->currency->cbr_id,
                    "value" => $rate->value,
                    "vunit_rate" => $rate->vunit_rate,
                ],
            );

        $result = [
            "date" => $date->toDateString(),
            "rates" => $rates->toArray(),
        ];

        // Добавляем предупреждение о отсутствующих валютах
        if (!empty($missingCurrencies)) {
            $result["warnings"] = [
                "missing_currencies" => array_values($missingCurrencies),
                "message" =>
                    "Следующие валюты не синхронизированы: " .
                    implode(", ", $missingCurrencies) .
                    ". Запустите синхронизацию.",
            ];
        }

        // Если указана дата сравнения - добавляем данные для сравнения к каждой валюте
        if ($compareDate) {
            $compareRates = Rate::where("date", $compareDate)
                ->when($charCodes, function ($query) use ($charCodes) {
                    $query->whereHas(
                        "currency",
                        fn($q) => $q->whereIn("char_code", $charCodes),
                    );
                })
                ->get()
                ->keyBy(fn(Rate $r) => $r->currency->char_code);

            $rates = $rates->map(function ($rate) use ($compareRates) {
                $compare = $compareRates->get($rate["char_code"]);
                $rate["compare"] = $compare
                    ? [
                        "date" => $compare->date->toDateString(),
                        "value" => $compare->value,
                        "vunit_rate" => $compare->vunit_rate,
                    ]
                    : null;

                return $rate;
            });

            $result["compare_date"] = $compareDate->toDateString();
            $result["rates"] = $rates->toArray();
        }

        return response()->json($result);
    }
}
