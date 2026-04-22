<?php

declare(strict_types=1);

namespace App\Contracts;

use Carbon\Carbon;

interface ExchangeRatesClientInterface
{
    /**
     * Получить курсы валют на указанную дату.
     *
     * @param  Carbon|null  $date  Дата (null = последние данные)
     * @param  bool  $monthly  true = ежемесячные курсы, false = ежедневные
     * @return string XML с курсами валют
     */
    public function getDailyRatesOnDate(?Carbon $date = null, bool $monthly = false): string;

    /**
     * Получить справочник валют ЦБ РФ.
     *
     * @param  bool  $monthly  true = ежемесячные коды, false = ежедневные
     * @return string XML со справочником валют
     */
    public function getCurrencyDictionary(bool $monthly = false): string;

    /**
     * Получить динамику курса конкретной валюты за период.
     *
     * @param  string  $cbrId  ID валюты в ЦБ (например, R01235 для USD)
     * @param  Carbon  $from  Дата начала периода
     * @param  Carbon  $to  Дата окончания периода
     * @return string XML с динамикой курсов
     */
    public function getCurrencyDynamics(string $cbrId, Carbon $from, Carbon $to): string;
}
