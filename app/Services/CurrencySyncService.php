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
    ) {}

    /**
     * Выполняет синхронизацию курсов валют из внешнего API в БД.
     * Оптимизировано: запрашивает только настроенные валюты, если их CBR ID известны.
     */
    public function sync(): void
    {
        Log::channel('cbr')->info('Начало синхронизации курсов валют с ЦБ РФ.');

        try {
            $allowedCurrencies = $this->settingsService->getCbrFetchCurrencies();
            $today = Carbon::today();

            // Получаем валюты из БД с их CBR ID
            $currenciesInDb = Currency::whereIn('char_code', $allowedCurrencies)
                ->pluck('cbr_id', 'char_code');

            $dtos = collect();

            // Если все валюты имеют CBR ID, запрашиваем индивидуально
            if ($currenciesInDb->filter()->count() === count($allowedCurrencies)) {
                Log::channel('cbr')->info('Все валюты имеют CBR ID, запрашиваем индивидуально.');
                foreach ($allowedCurrencies as $charCode) {
                    $cbrId = $currenciesInDb[$charCode];
                    $xmlRawData = $this->client->getCurrencyRatesRawData($cbrId, $today);
                    $parsedDtos = $this->parser->parse($xmlRawData);
                    $dtos = $dtos->merge($parsedDtos);
                }
            } else {
                // Иначе запрашиваем все и фильтруем
                Log::channel('cbr')->info('Не все валюты имеют CBR ID, запрашиваем все валюты.');
                $xmlRawData = $this->client->getDailyRatesRawData();
                $allDtos = $this->parser->parse($xmlRawData);
                $dtos = collect($allDtos)->filter(fn ($dto) => in_array($dto->charCode, $allowedCurrencies, true));
            }

            DB::transaction(function () use ($dtos, $today) {
                $savedCount = 0;

                foreach ($dtos as $dto) {
                    // Обновляем или создаем валюту с CBR ID
                    $currency = Currency::updateOrCreate(
                        ['char_code' => $dto->charCode],
                        [
                            'name' => $dto->name,
                            'nominal' => $dto->nominal,
                            'cbr_id' => $dto->cbrId,
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
            Log::channel('cbr')->error('Ошибка синхронизации курсов валют: '.$e->getMessage(), [
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }
    }
}
