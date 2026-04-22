<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\ExchangeRatesClientInterface;
use App\Models\Currency;
use App\Models\Rate;
use App\Services\Cbr\CbrXmlParser;
use App\Services\CurrencySyncService;
use App\Services\SettingsService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Команда синхронизации курсов валют с API ЦБ РФ.
 *
 * По умолчанию синхронизирует курсы на сегодня и вчера.
 * С флагом --all загружает все доступные валюты.
 */
#[Signature('cbr:fetch-rates {--date=today : Date to fetch (today|yesterday|both)} {--all : Fetch all currencies instead of configured list}')]
#[Description('Fetch and sync currency rates from CBR API')]
class FetchCbrRatesCommand extends Command
{
    public function handle(): int
    {
        $dateOption = $this->option('date');
        $all = (bool) $this->option('all');
        $settingsService = app(SettingsService::class);
        $offset = $settingsService->getFetchDateOffset();

        try {
            return match ($dateOption) {
                'yesterday' => $this->sync('yesterday', $all, $offset),
                'both' => $this->syncBoth($all, $offset),
                default => $this->sync('today', $all, $offset),
            };
        } catch (Throwable $e) {
            $this->error('CBR rates synchronization failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function sync(string $dateType, bool $all, int $offset): int
    {
        $baseDate = match ($dateType) {
            'yesterday' => Carbon::yesterday(),
            default => Carbon::today(),
        };

        $date = $baseDate->copy()->addDays($offset);

        $this->info("Starting CBR rates synchronization for {$date->format('d.m.Y')} (offset: {$offset})...");

        if ($all) {
            $this->syncAll($date);
        } else {
            app(CurrencySyncService::class)->sync($date);
        }

        $this->info("CBR rates synchronization for {$date->format('d.m.Y')} completed successfully.");

        return self::SUCCESS;
    }

    private function syncBoth(bool $all, int $offset): int
    {
        $yesterday = Carbon::yesterday()->addDays($offset);
        $today = Carbon::today()->addDays($offset);

        $this->info("Starting CBR rates synchronization for {$yesterday->format('d.m.Y')} (offset: {$offset})...");
        if ($all) {
            $this->syncAll($yesterday);
        } else {
            app(CurrencySyncService::class)->sync($yesterday);
        }
        $this->info("CBR rates synchronization for {$yesterday->format('d.m.Y')} completed successfully.");

        $this->info("Starting CBR rates synchronization for {$today->format('d.m.Y')} (offset: {$offset})...");
        if ($all) {
            $this->syncAll($today);
        } else {
            app(CurrencySyncService::class)->sync($today);
        }
        $this->info("CBR rates synchronization for {$today->format('d.m.Y')} completed successfully.");

        return self::SUCCESS;
    }

    private function syncAll(Carbon $date): void
    {
        $client = app(ExchangeRatesClientInterface::class);
        $parser = app(CbrXmlParser::class);

        $xmlRawData = $client->getDailyRatesOnDate($date);
        $dtos = $parser->parseDailyRates($xmlRawData);

        DB::transaction(function () use ($dtos, $date) {
            $savedCount = 0;

            foreach ($dtos as $dto) {
                $currency = Currency::updateOrCreate(
                    ['char_code' => $dto->charCode],
                    [
                        'name' => $dto->name,
                        'nominal' => $dto->nominal,
                        'cbr_id' => $dto->cbrId,
                        'num_code' => $dto->numCode,
                    ]
                );

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
    }
}