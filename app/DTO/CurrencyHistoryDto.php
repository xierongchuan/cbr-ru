<?php

declare(strict_types=1);

namespace App\DTO;

readonly class CurrencyHistoryDto
{
    public function __construct(
        public string $date,
        public float $value,
        public float $vunitRate,
    ) {}
}
