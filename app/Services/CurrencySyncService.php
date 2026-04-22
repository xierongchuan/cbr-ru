<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ExchangeRatesClientInterface;
use App\Models\Currency;
use App\Models\Rate;
use App\Services\Cbr\CbrXmlParser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис синхронизации курсов валют из API ЦБ РФ в БД.
 *
 * Загружает курсы валют с API ЦБ РФ и сохраняет их в базу данных.
 * Фильтрует валюты согласно настройкам (cbr_fetch_currencies).
 */
class CurrencySyncService
{
    public function __construct(
        private readonly ExchangeRatesClientInterface $client,
        private readonly CbrXmlParser $parser,
        private readonly SettingsService $settingsService,
    ) {}

    /**
     * Выполняет синхронизацию курсов валют из API ЦБ РФ в БД.
     *
     * Запрашивает курсы на указанную дату (или сегодня с учётом offset),
     * парсит XML ответ и сохраняет данные в таблицы currencies и rates.
     *
     * @param  Carbon|null  $date  Дата для синхронизации (по умолчанию - сегодня с учётом offset)
     */
    public function sync(?Carbon $date = null): void
    {
        if ($date === null) {
            $offset = $this->settingsService->getFetchDateOffset();
            $date = Carbon::today()->addDays($offset);
        }

        Log::channel('cbr')->info(
            "Начало синхронизации курсов валют с ЦБ РФ за {$date->format(
                'd.m.Y',
            )}.",
        );

        try {
            // Получаем список валют для загрузки из настроек
            $allowedCurrencies = $this->settingsService->getCbrFetchCurrencies();

            // Запрашиваем курсы на указанную дату
            $xmlRawData = $this->client->getDailyRatesOnDate($date);
            $allDtos = $this->parser->parseDailyRates($xmlRawData);

            // Фильтруем только разрешенные валюты
            $dtos = collect($allDtos)->filter(
                fn ($dto) => in_array($dto->charCode, $allowedCurrencies, true),
            );

            DB::transaction(function () use ($dtos, $date) {
                $savedCount = 0;

                foreach ($dtos as $dto) {
                    // Обновляем или создаём валюту
                    $currency = Currency::updateOrCreate(
                        ['char_code' => $dto->charCode],
                        [
                            'name' => $dto->name,
                            'nominal' => $dto->nominal,
                            'cbr_id' => $dto->cbrId,
                            'num_code' => $dto->numCode,
                        ],
                    );

                    // Сохраняем курс валюты на указанную дату
                    Rate::updateOrCreate(
                        [
                            'currency_id' => $currency->id,
                            'date' => $date,
                        ],
                        [
                            'value' => $dto->value,
                            'vunit_rate' => $dto->vunitRate,
                        ],
                    );

                    $savedCount++;
                }

                Log::channel('cbr')->info(
                    "Синхронизация за {$date->format(
                        'd.m.Y',
                    )} завершена. Обновлено валют: {$savedCount}",
                );
            });
        } catch (\Throwable $e) {
            Log::channel('cbr')->error(
                'Ошибка синхронизации курсов валют: '.$e->getMessage(),
                [
                    'exception_class' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            );

            throw $e;
        }
    }
}
