<?php

declare(strict_types=1);

namespace App\Contracts;

use Carbon\Carbon;

interface ExchangeRatesClientInterface
{
    /**
     * Получить сырые данные с курсами валют на текущую дату (XML).
     */
    public function getDailyRatesRawData(): string;

    /**
     * Получить сырые данные с курсом конкретной валюты на указанную дату (XML).
     */
    public function getCurrencyRatesRawData(string $cbrId, Carbon $date): string;
}
