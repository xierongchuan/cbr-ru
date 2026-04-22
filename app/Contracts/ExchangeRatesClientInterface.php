<?php

declare(strict_types=1);

namespace App\Contracts;

use Carbon\Carbon;

interface ExchangeRatesClientInterface
{
    /**
     * Получить сырые данные с курсами валют на указанную дату (XML).
     */
    public function getDailyRatesRawData(?Carbon $date = null): string;
}
