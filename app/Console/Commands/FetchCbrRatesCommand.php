<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\CurrencySyncService;
use App\Services\SettingsService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;

#[Signature('cbr:fetch-rates {--date=today : Date to fetch (today|yesterday)}')]
#[Description('Fetch and sync currency rates from CBR API')]
class FetchCbrRatesCommand extends Command
{
    public function __construct(
        private readonly CurrencySyncService $syncService,
        private readonly SettingsService $settingsService,
    ) {
        parent::__construct();
    }

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

        $this->info("Starting CBR rates synchronization for {$date->format('d.m.Y')} (offset: {$offset})...");

        try {
            $this->syncService->sync($date);
            $this->info("CBR rates synchronization for {$date->format('d.m.Y')} completed successfully.");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('CBR rates synchronization failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
