<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\JsonResponse;

/**
 * Контроллер справочника валют.
 *
 * Предоставляет API для получения списка всех валют,
 * которые были синхронизированы из ЦБ РФ.
 */
class CurrenciesController extends Controller
{
    /**
     * Получить список всех валют.
     *
     * Возвращает справочник валют с их кодами ЦБ, числовыми кодами,
     * буквенными кодами, названиями и номиналами.
     */
    public function index(): JsonResponse
    {
        $currencies = Currency::select('id', 'cbr_id', 'char_code', 'num_code', 'name', 'nominal')
            ->orderBy('char_code')
            ->get()
            ->map(fn (Currency $c) => [
                'id' => $c->id,
                'cbr_id' => $c->cbr_id,
                'char_code' => $c->char_code,
                'num_code' => $c->num_code,
                'name' => $c->name,
                'nominal' => $c->nominal,
            ]);

        return response()->json(['currencies' => $currencies]);
    }
}
