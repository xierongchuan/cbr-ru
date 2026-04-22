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

    private const string KEY_FETCH_DATE_OFFSET = 'fetch_date_offset';

    /**
     * Получить значение настройки с кэшированием.
     *
     * @param  string  $key  Ключ настройки.
     * @param  mixed|null  $default  Значение по умолчанию.
     * @return mixed Значение настройки.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        try {
            return Cache::remember(self::CACHE_KEY_PREFIX.$key, self::CACHE_TTL, function () use ($key, $default) {
                $setting = Setting::where('key', $key)->first();

                return $setting ? $setting->value : $default;
            });
        } catch (\Throwable $e) {
            // Если кэш не доступен (например, в тестах), возвращаем из БД
            $setting = Setting::where('key', $key)->first();

            return $setting ? $setting->value : $default;
        }
    }

    /**
     * Установить значение настройки (сохраняет в БД и обновляет кэш).
     *
     * @param  string  $key  Ключ настройки.
     * @param  mixed  $value  Значение настройки.
     */
    public function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        try {
            Cache::put(self::CACHE_KEY_PREFIX.$key, $value, self::CACHE_TTL);
        } catch (\Throwable $e) {
            // Если кэш не доступен, игнорируем
        }
    }

    /**
     * Список валют для загрузки из ЦБ.
     *
     * @return array<string>
     */
    public function getCbrFetchCurrencies(): array
    {
        return $this->get(self::KEY_CBR_FETCH_CURRENCIES, ['USD', 'EUR', 'CNY']);
    }

    /**
     * Список валют для отображения в виджете.
     *
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

    /**
     * Смещение даты для загрузки курсов (0 = сегодня, 1 = завтра).
     */
    public function getFetchDateOffset(): int
    {
        return (int) $this->get(self::KEY_FETCH_DATE_OFFSET, 0);
    }
}
