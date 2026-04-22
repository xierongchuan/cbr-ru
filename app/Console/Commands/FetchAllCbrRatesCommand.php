<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\ExchangeRatesClientInterface;
use App\Models\Currency;
use App\Models\Rate;
use App\Services\Cbr\CbrXmlParser;
use App\Services\SettingsService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('cbr:fetch-all-rates {--date=today : Date to fetch (today|yesterday)}')]
#[Description('Fetch and sync ALL currency rates from CBR API for specified date')]
class FetchAllCbrRatesCommand extends Command
{
    public function __construct(
        private readonly ExchangeRatesClientInterface $client,
        private readonly CbrXmlParser $parser,
        private readonly SettingsService $settingsService,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dateOption = $this->option('date');
        $baseDate = match ($dateOption) {
            'yesterday' => Carbon::yesterday(),
            default => Carbon::today(),
        };

        // Применяем смещение даты из настроек
        $offset = $this->settingsService->getFetchDateOffset();
        $date = $baseDate->copy()->addDays($offset);

        $this->info("Starting CBR ALL rates synchronization for {$date->format('d.m.Y')}...");

        try {
            // Загружаем все валюты на указанную дату
            $xmlRawData = $this->client->getDailyRatesOnDate($date);
            $dtos = $this->parser->parseDailyRates($xmlRawData);

            DB::transaction(function () use ($dtos, $date) {
                $savedCount = 0;

                foreach ($dtos as $dto) {
                    // Сохраняем все валюты с CBR ID
                    $currency = Currency::updateOrCreate(
                        ['char_code' => $dto->charCode],
                        [
                            'name' => $dto->name,
                            'nominal' => $dto->nominal,
                            'cbr_id' => $dto->cbrId,
                        ]
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
                        ]
                    );

                    $savedCount++;
                }

                Log::channel('cbr')->info("Синхронизация всех валют за {$date->format('d.m.Y')} завершена. Обновлено: {$savedCount}");
            });

            $this->info('CBR ALL rates synchronization completed successfully.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('CBR ALL rates synchronization failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
