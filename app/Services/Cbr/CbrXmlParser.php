<?php

declare(strict_types=1);

namespace App\Services\Cbr;

use App\DTO\CurrencyCodeDto;
use App\DTO\CurrencyHistoryDto;
use App\DTO\CurrencyRateDto;
use App\Exceptions\Cbr\CbrException;
use App\Exceptions\Cbr\CbrParseException;
use Throwable;

class CbrXmlParser
{
    private const string CBR_ENCODING = 'Windows-1251';

    /**
     * Парсит XML дневных курсов валют.
     *
     * @param  string  $xmlContent  Сырой XML в кодировке Windows-1251
     * @return array<CurrencyRateDto>
     *
     * @throws CbrException
     */
    public function parseDailyRates(string $xmlContent): array
    {
        try {
            $xmlContent = $this->convertToUtf8($xmlContent);

            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xmlContent);

            if ($xml === false) {
                $errors = libxml_get_errors();
                libxml_clear_errors();
                $errorMessage = isset($errors[0]) ? trim($errors[0]->message) : 'Unknown XML parsing error';
                throw new CbrParseException('Ошибка парсинга XML от ЦБ: '.$errorMessage);
            }

            $dtos = [];

            foreach ($xml->Valute as $valute) {
                $value = (float) str_replace(',', '.', (string) $valute->Value);
                $vunitRate = (float) str_replace(',', '.', (string) $valute->VunitRate);

                $dtos[] = new CurrencyRateDto(
                    cbrId: (string) $valute['ID'],
                    numCode: (string) $valute->NumCode,
                    charCode: (string) $valute->CharCode,
                    nominal: (int) $valute->Nominal,
                    name: (string) $valute->Name,
                    value: $value,
                    vunitRate: $vunitRate,
                );
            }

            return $dtos;

        } catch (Throwable $e) {
            if ($e instanceof CbrException) {
                throw $e;
            }
            throw new CbrException('Непредвиденная ошибка при парсинге XML: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Парсит XML справочник валют.
     *
     * @param  string  $xmlContent  Сырой XML в кодировке Windows-1251
     * @return array<CurrencyCodeDto>
     */
    public function parseCurrencyDictionary(string $xmlContent): array
    {
        try {
            $xmlContent = $this->convertToUtf8($xmlContent);

            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xmlContent);

            if ($xml === false) {
                $errors = libxml_get_errors();
                libxml_clear_errors();
                $errorMessage = isset($errors[0]) ? trim($errors[0]->message) : 'Unknown XML parsing error';
                throw new CbrParseException('Ошибка парсинга справочника валют: '.$errorMessage);
            }

            $dtos = [];

            foreach ($xml->Valute as $valute) {
                $dtos[] = new CurrencyCodeDto(
                    cbrId: (string) $valute['ID'],
                    numCode: (string) $valute->NumCode,
                    charCode: (string) $valute->CharCode,
                    name: (string) $valute->Name,
                );
            }

            return $dtos;

        } catch (Throwable $e) {
            if ($e instanceof CbrException) {
                throw $e;
            }
            throw new CbrException('Непредвиденная ошибка при парсинге справочника: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Парсит XML динамики курсов валют.
     *
     * @param  string  $xmlContent  Сырой XML в кодировке Windows-1251
     * @return array<CurrencyHistoryDto>
     */
    public function parseCurrencyDynamics(string $xmlContent): array
    {
        try {
            $xmlContent = $this->convertToUtf8($xmlContent);

            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xmlContent);

            if ($xml === false) {
                $errors = libxml_get_errors();
                libxml_clear_errors();
                $errorMessage = isset($errors[0]) ? trim($errors[0]->message) : 'Unknown XML parsing error';
                throw new CbrParseException('Ошибка парсинга динамики курсов: '.$errorMessage);
            }

            $dtos = [];

            foreach ($xml->Record as $record) {
                $value = (float) str_replace(',', '.', (string) $record->Value);
                $vunitRate = (float) str_replace(',', '.', (string) $record->VunitRate);

                $dtos[] = new CurrencyHistoryDto(
                    date: (string) $record['Date'],
                    value: $value,
                    vunitRate: $vunitRate,
                );
            }

            return $dtos;

        } catch (Throwable $e) {
            if ($e instanceof CbrException) {
                throw $e;
            }
            throw new CbrException('Непредвиденная ошибка при парсинге динамики: '.$e->getMessage(), 0, $e);
        }
    }

    private function convertToUtf8(string $content): string
    {
        $converted = mb_convert_encoding($content, 'UTF-8', self::CBR_ENCODING);

        return str_replace(
            'encoding="windows-1251"',
            'encoding="UTF-8"',
            $converted
        );
    }
}
