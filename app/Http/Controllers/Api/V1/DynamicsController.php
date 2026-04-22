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
 * Контроллер динамики курсов валют.
 *
 * Предоставляет API для получения истории курсов валюты
 * за заданный период.
 */
class DynamicsController extends Controller
{
    /**
     * Получить динамику курса валюты за период.
     *
     * Параметры запроса (обязательные):
     * - char_code - буквенный код валюты (например, USD)
     * - from - дата начала периода в формате YYYY-MM-DD
     * - to - дата окончания периода в формате YYYY-MM-DD
     *
     * Пример запроса:
     *   GET /api/v1/dynamics?char_code=USD&from=2026-04-01&to=2026-04-22
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'char_code' => 'required|string|size:3',
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        $charCode = $request->query('char_code');
        $from = Carbon::parse($request->query('from'))->startOfDay();
        $to = Carbon::parse($request->query('to'))->startOfDay();

        // Находим валюту по коду
        $currency = Currency::where('char_code', $charCode)->first();

        if (! $currency) {
            return response()->json(['error' => 'Валюта не найдена'], 404);
        }

        // Получаем историю курсов за период
        $rates = Rate::where('currency_id', $currency->id)
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->get()
            ->map(fn (Rate $rate) => [
                'date' => $rate->date->toDateString(),
                'value' => $rate->value,
                'vunit_rate' => $rate->vunit_rate,
            ]);

        return response()->json([
            'char_code' => $charCode,
            'cbr_id' => $currency->cbr_id,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'dynamics' => $rates,
        ]);
    }
}
