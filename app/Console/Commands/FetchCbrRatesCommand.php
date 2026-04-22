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
 * С флагом --tomorrow включает загрузку курсов на завтра (если доступны).
 */
#[Signature('cbr:fetch-rates {--date=today : Date to fetch (today|yesterday|both|tomorrow)} {--all : Fetch all currencies} {--tomorrow : Also fetch tomorrow rates if available}')]
#[Description('Fetch and sync currency rates from CBR API')]
class FetchCbrRatesCommand extends Command
{
    public function handle(): int
    {
        $dateOption = $this->option('date');
        $all = (bool) $this->option('all');
        $tomorrow = (bool) ($this->option('tomorrow') ?? false);
        $settingsService = app(SettingsService::class);
        $offset = $settingsService->getFetchDateOffset();

        try {
            return match ($dateOption) {
                'yesterday' => $this->sync('yesterday', $all, $offset),
                'tomorrow' => $this->sync('tomorrow', $all, $offset),
                'both' => $this->syncBoth($all, $offset, $tomorrow),
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
            'tomorrow' => Carbon::tomorrow(),
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

    private function syncBoth(bool $all, int $offset, bool $includeTomorrow): int
    {
        $yesterday = Carbon::yesterday()->addDays($offset);
        $today = Carbon::today()->addDays($offset);
        $tomorrow = Carbon::tomorrow()->addDays($offset);

        // Вчера
        $this->info("Starting CBR rates synchronization for {$yesterday->format('d.m.Y')} (offset: {$offset})...");
        if ($all) {
            $this->syncAll($yesterday);
        } else {
            app(CurrencySyncService::class)->sync($yesterday);
        }
        $this->info("CBR rates synchronization for {$yesterday->format('d.m.Y')} completed successfully.");

        // Сегодня
        $this->info("Starting CBR rates synchronization for {$today->format('d.m.Y')} (offset: {$offset})...");
        if ($all) {
            $this->syncAll($today);
        } else {
            app(CurrencySyncService::class)->sync($today);
        }
        $this->info("CBR rates synchronization for {$today->format('d.m.Y')} completed successfully.");

        // Завтра (если включено)
        if ($includeTomorrow) {
            $this->info("Starting CBR rates synchronization for {$tomorrow->format('d.m.Y')} (offset: {$offset})...");
            try {
                if ($all) {
                    $this->syncAll($tomorrow);
                } else {
                    app(CurrencySyncService::class)->sync($tomorrow);
                }
                $this->info("CBR rates synchronization for {$tomorrow->format('d.m.Y')} completed successfully.");
            } catch (Throwable $e) {
                $this->warn(" завтрашних курсов (могут быть недоступны): ".$e->getMessage());
            }
        }

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