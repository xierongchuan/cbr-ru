<?php

declare(strict_types=1);

namespace App\Services\Cbr;

use App\DTO\CurrencyRateDto;
use App\Exceptions\Cbr\CbrException;
use App\Exceptions\Cbr\CbrParseException;
use Throwable;

class CbrXmlParser
{
    /**
     * Кодировка ответа ЦБ РФ.
     */
    private const string CBR_ENCODING = 'Windows-1251';

    /**
     * Парсит XML ответ от ЦБ и возвращает массив DTO.
     * Автоматически конвертирует кодировку Windows-1251 -> UTF-8.
     *
     * @param  string  $xmlContent  Сырой XML в кодировке Windows-1251
     * @return array<CurrencyRateDto>
     *
     * @throws CbrException
     */
    public function parse(string $xmlContent): array
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
                // ЦБ использует запятую как разделитель дробной части: 53,4510 -> 53.4510
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
     * Конвертирует строку из Windows-1251 в UTF-8 и обновляет XML-декларацию.
     *
     * @param  string  $content  Содержимое XML в кодировке Windows-1251
     * @return string Содержимое XML в кодировке UTF-8
     */
    private function convertToUtf8(string $content): string
    {
        $converted = mb_convert_encoding($content, 'UTF-8', self::CBR_ENCODING);

        // Заменяем объявление кодировки в заголовке XML
        return str_replace(
            'encoding="windows-1251"',
            'encoding="UTF-8"',
            $converted
        );
    }
}
