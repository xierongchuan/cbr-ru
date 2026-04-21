<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    private const string CACHE_KEY_PREFIX = 'setting:';
    private const int CACHE_TTL = 86400; // 24 часа

    private const string KEY_CBR_FETCH_CURRENCIES = 'cbr_fetch_currencies';
    private const string KEY_WIDGET_CURRENCIES = 'widget_currencies';
    private const string KEY_WIDGET_UPDATE_INTERVAL = 'widget_update_interval';

    /**
     * Получить значение настройки с кэшированием.
     * @param string $key Ключ настройки.
     * @param mixed|null $default Значение по умолчанию.
     * @return mixed Значение настройки.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember(self::CACHE_KEY_PREFIX . $key, self::CACHE_TTL, function () use ($key, $default) {
            $setting = Setting::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Установить значение настройки (сохраняет в БД и обновляет кэш).
     * @param string $key Ключ настройки.
     * @param mixed $value Значение настройки.
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::put(self::CACHE_KEY_PREFIX . $key, $value, self::CACHE_TTL);
    }

    /**
     * Список валют для загрузки из ЦБ.
     * @return array<string>
     */
    public function getCbrFetchCurrencies(): array
    {
        return $this->get(self::KEY_CBR_FETCH_CURRENCIES, ['USD', 'EUR', 'CNY']);
    }

    /**
     * Список валют для отображения в виджете.
     * @return array<string>
     */
    public function getWidgetCurrencies(): array
    {
        return $this->get(self::KEY_WIDGET_CURRENCIES, ['USD', 'EUR']);
    }

    /**
     * Интервал обновления виджета в секундах.
     */
    public function getWidgetUpdateInterval(): int
    {
        return (int) $this->get(self::KEY_WIDGET_UPDATE_INTERVAL, 60);
    }
}
