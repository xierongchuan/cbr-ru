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

class CurrencySyncService
{
    public function __construct(
        private readonly ExchangeRatesClientInterface $client,
        private readonly CbrXmlParser $parser,
        private readonly SettingsService $settingsService,
    ) {
    }

    /**
     * Выполняет синхронизацию курсов валют из внешнего API в БД.
     *
     * @return void
     */
    public function sync(): void
    {
        Log::channel('cbr')->info('Начало синхронизации курсов валют с ЦБ РФ.');

        try {
            $xmlRawData = $this->client->getDailyRatesRawData();
            $dtos = $this->parser->parse($xmlRawData);

            $allowedCurrencies = $this->settingsService->getCbrFetchCurrencies();
            $today = Carbon::today();

            DB::transaction(function () use ($dtos, $allowedCurrencies, $today) {
                $savedCount = 0;

                foreach ($dtos as $dto) {
                    if (!in_array($dto->charCode, $allowedCurrencies, true)) {
                        continue;
                    }

                    // Обновляем или создаем валюту
                    $currency = Currency::updateOrCreate(
                        ['char_code' => $dto->charCode],
                        [
                            'name' => $dto->name,
                            'nominal' => $dto->nominal,
                        ]
                    );

                    // Сохраняем курс валюты на сегодня
                    Rate::updateOrCreate(
                        [
                            'currency_id' => $currency->id,
                            'date' => $today,
                        ],
                        [
                            'value' => $dto->value,
                            'vunit_rate' => $dto->vunitRate,
                        ]
                    );

                    $savedCount++;
                }

                Log::channel('cbr')->info("Синхронизация успешно завершена. Обновлено валют: {$savedCount}");
            });

        } catch (\Throwable $e) {
            Log::channel('cbr')->error('Ошибка синхронизации курсов валют: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            throw $e;
        }
    }
}
