<?php

declare(strict_types=1);

namespace App\Contracts;

interface ExchangeRatesClientInterface
{
    /**
     * Получить сырые данные с курсами валют (XML/JSON/etc).
     *
     * @return string
     */
    public function getDailyRatesRawData(): string;
}
