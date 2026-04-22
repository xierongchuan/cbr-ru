<?php

declare(strict_types=1);

namespace App\DTO;

readonly class CurrencyCodeDto
{
    public function __construct(
        public string $cbrId,
        public string $numCode,
        public string $charCode,
        public string $name,
    ) {}
}
