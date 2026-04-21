<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\CurrencySyncService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('cbr:fetch-rates')]
#[Description('Fetch and sync currency rates from CBR API')]
class FetchCbrRatesCommand extends Command
{
    public function __construct(
        private readonly CurrencySyncService $syncService,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting CBR rates synchronization...');

        try {
            $this->syncService->sync();
            $this->info('CBR rates synchronization completed successfully.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('CBR rates synchronization failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
