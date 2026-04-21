<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\ExchangeRatesClientInterface;
use App\Models\Currency;
use App\Models\Rate;
use App\Services\Cbr\CbrXmlParser;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('cbr:fetch-all-rates')]
#[Description('Fetch and sync all currency rates from CBR API (ignores settings)')]
class FetchAllCbrRatesCommand extends Command
{
    public function __construct(
        private readonly ExchangeRatesClientInterface $client,
        private readonly CbrXmlParser $parser,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting CBR all rates synchronization...');

        try {
            $xmlRawData = $this->client->getDailyRatesRawData();
            $dtos = $this->parser->parse($xmlRawData);

            $today = Carbon::today();

            DB::transaction(function () use ($dtos, $today) {
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

                Log::channel('cbr')->info("Синхронизация всех валют завершена. Обновлено: {$savedCount}");
            });

            $this->info('CBR all rates synchronization completed successfully.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('CBR all rates synchronization failed: '.$e->getMessage());
            Log::channel('cbr')->error('Ошибка синхронизации всех валют: '.$e->getMessage(), [
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return self::FAILURE;
        }
    }
}
