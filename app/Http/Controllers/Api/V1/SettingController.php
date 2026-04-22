<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Currency;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function __construct(
        private readonly SettingsService $settingsService,
    ) {}

    /**
     * Получить текущие настройки.
     */
    public function index(): JsonResponse
    {
        $availableCurrencies = Currency::select('char_code', 'name')
            ->orderBy('char_code')
            ->get()
            ->map(fn ($currency) => [
                'code' => $currency->char_code,
                'name' => $currency->name,
            ]);

        // Если валюты не загружены, возвращаем пустой массив
        if ($availableCurrencies->isEmpty()) {
            $availableCurrencies = collect();
        }

        return response()->json([
            'available_currencies' => $availableCurrencies,
            'cbr_fetch_currencies' => $this->settingsService->get('cbr_fetch_currencies', ['USD', 'EUR', 'CNY']),
            'widget_currencies' => $this->settingsService->getWidgetCurrencies(),
            'widget_update_interval' => $this->settingsService->getWidgetUpdateInterval(),
        ]);
    }

    /**
     * Обновить настройки.
     */
    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (isset($validated['cbr_fetch_currencies'])) {
            $this->settingsService->set('cbr_fetch_currencies', $validated['cbr_fetch_currencies']);
        }

        if (isset($validated['widget_currencies'])) {
            $this->settingsService->set('widget_currencies', $validated['widget_currencies']);
        }

        if (isset($validated['widget_update_interval'])) {
            $this->settingsService->set('widget_update_interval', $validated['widget_update_interval']);
        }

        return response()->json(['message' => 'Настройки обновлены']);
    }
}
