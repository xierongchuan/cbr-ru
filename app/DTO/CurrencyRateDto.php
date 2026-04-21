<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * Data Transfer Object для передачи распарсенных данных валюты
 */
readonly class CurrencyRateDto
{
    public function __construct(
        public string $cbrId,
        public string $numCode,
        public string $charCode,
        public int $nominal,
        public string $name,
        public float $value,
        public float $vunitRate,
    ) {
    }
}
