<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cbr;

use App\DTO\CurrencyRateDto;
use App\Exceptions\Cbr\CbrException;
use App\Services\Cbr\CbrXmlParser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CbrXmlParserTest extends TestCase
{
    private CbrXmlParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new CbrXmlParser();
    }

    #[Test]
    public function it_parses_valid_windows1251_xml_correctly(): void
    {
        // Создаём валидный XML как PHP-строку, затем конвертируем в Windows-1251
        $xmlUtf8 = <<<XML
<?xml version="1.0" encoding="windows-1251"?>
<ValCurs Date="22.04.2026" name="Foreign Currency Market">
    <Valute ID="R01235">
        <NumCode>840</NumCode>
        <CharCode>USD</CharCode>
        <Nominal>1</Nominal>
        <Name>Доллар США</Name>
        <Value>74,5897</Value>
        <VunitRate>74,5897</VunitRate>
    </Valute>
    <Valute ID="R01239">
        <NumCode>978</NumCode>
        <CharCode>EUR</CharCode>
        <Nominal>1</Nominal>
        <Name>Евро</Name>
        <Value>87,7659</Value>
        <VunitRate>87,7659</VunitRate>
    </Valute>
</ValCurs>
XML;
        // Эмулируем реальный ответ ЦБ: конвертируем строку в Windows-1251
        $xmlWindows1251 = mb_convert_encoding($xmlUtf8, 'Windows-1251', 'UTF-8');

        $dtos = $this->parser->parse($xmlWindows1251);

        $this->assertCount(2, $dtos);
        $this->assertContainsOnlyInstancesOf(CurrencyRateDto::class, $dtos);
    }

    #[Test]
    public function it_correctly_maps_dto_fields_from_xml(): void
    {
        $xmlUtf8 = <<<XML
<?xml version="1.0" encoding="windows-1251"?>
<ValCurs Date="22.04.2026" name="Foreign Currency Market">
    <Valute ID="R01235">
        <NumCode>840</NumCode>
        <CharCode>USD</CharCode>
        <Nominal>1</Nominal>
        <Name>Доллар США</Name>
        <Value>74,5897</Value>
        <VunitRate>74,5897</VunitRate>
    </Valute>
</ValCurs>
XML;
        $xml = mb_convert_encoding($xmlUtf8, 'Windows-1251', 'UTF-8');
        $dtos = $this->parser->parse($xml);

        $dto = $dtos[0];
        $this->assertSame('R01235', $dto->cbrId);
        $this->assertSame('840', $dto->numCode);
        $this->assertSame('USD', $dto->charCode);
        $this->assertSame(1, $dto->nominal);
        $this->assertSame('Доллар США', $dto->name);
        $this->assertSame(74.5897, $dto->value);
        $this->assertSame(74.5897, $dto->vunitRate);
    }

    #[Test]
    public function it_correctly_converts_comma_decimal_separator(): void
    {
        $xmlUtf8 = <<<XML
<?xml version="1.0" encoding="windows-1251"?>
<ValCurs Date="22.04.2026" name="Foreign Currency Market">
    <Valute ID="R01335">
        <NumCode>398</NumCode>
        <CharCode>KZT</CharCode>
        <Nominal>100</Nominal>
        <Name>Тенге</Name>
        <Value>15,8874</Value>
        <VunitRate>0,158874</VunitRate>
    </Valute>
</ValCurs>
XML;
        $xml = mb_convert_encoding($xmlUtf8, 'Windows-1251', 'UTF-8');
        $dtos = $this->parser->parse($xml);

        $this->assertSame(15.8874, $dtos[0]->value);
        $this->assertSame(0.158874, $dtos[0]->vunitRate);
    }

    #[Test]
    public function it_returns_empty_array_for_xml_with_no_valutes(): void
    {
        $xmlUtf8 = <<<XML
<?xml version="1.0" encoding="windows-1251"?>
<ValCurs Date="22.04.2026" name="Foreign Currency Market">
</ValCurs>
XML;
        $xml = mb_convert_encoding($xmlUtf8, 'Windows-1251', 'UTF-8');
        $dtos = $this->parser->parse($xml);

        $this->assertIsArray($dtos);
        $this->assertEmpty($dtos);
    }

    #[Test]
    public function it_throws_cbr_exception_on_invalid_xml(): void
    {
        $this->expectException(CbrException::class);
        $this->expectExceptionMessageMatches('/Ошибка парсинга XML/');

        $this->parser->parse('this is not xml at all <<broken>>');
    }

    #[Test]
    public function it_throws_cbr_exception_on_empty_string(): void
    {
        $this->expectException(CbrException::class);

        $this->parser->parse('');
    }
}
