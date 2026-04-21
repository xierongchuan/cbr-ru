<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Contracts\ExchangeRatesClientInterface;
use App\Exceptions\Cbr\CbrConnectionException;
use App\Models\Currency;
use App\Models\Rate;
use App\Models\Setting;
use App\Services\CurrencySyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CurrencySyncServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Carbon::setTestNow('2026-04-21 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Генерирует XML в кодировке Windows-1251 с заданными валютами.
     *
     * @param  array<array{id: string, charCode: string, name: string, nominal: int, value: string, vunitRate: string}>  $valutes
     * @return string XML в кодировке Windows-1251
     */
    private function makeXml(array $valutes): string
    {
        $items = '';
        foreach ($valutes as $v) {
            $items .= "<Valute ID=\"{$v['id']}\">
                <NumCode>840</NumCode>
                <CharCode>{$v['charCode']}</CharCode>
                <Nominal>{$v['nominal']}</Nominal>
                <Name>{$v['name']}</Name>
                <Value>{$v['value']}</Value>
                <VunitRate>{$v['vunitRate']}</VunitRate>
            </Valute>";
        }

        $xmlUtf8 = "<?xml version=\"1.0\" encoding=\"windows-1251\"?><ValCurs Date=\"22.04.2026\" name=\"Foreign Currency Market\">{$items}</ValCurs>";

        return mb_convert_encoding($xmlUtf8, 'Windows-1251', 'UTF-8');
    }

    /**
     * Создаёт fake-клиент, возвращающий заданный XML.
     *
     * @param  string  $xml  Содержимое XML в кодировке Windows-1251
     */
    private function makeFakeClient(string $xml): ExchangeRatesClientInterface
    {
        return new class($xml) implements ExchangeRatesClientInterface
        {
            public function __construct(private readonly string $xml) {}

            public function getDailyRatesRawData(): string
            {
                return $this->xml;
            }

            public function getCurrencyRatesRawData(string $cbrId, \Carbon\Carbon $date): string
            {
                // Для тестов возвращаем тот же XML, предполагая что он содержит нужную валюту
                return $this->xml;
            }
        };
    }

    /**
     * Создаёт fake-клиент, который всегда бросает исключение.
     */
    private function makeFailingClient(): ExchangeRatesClientInterface
    {
        return new class implements ExchangeRatesClientInterface
        {
            public function getDailyRatesRawData(): string
            {
                throw new CbrConnectionException('Simulated connection failure');
            }

            public function getCurrencyRatesRawData(string $cbrId, \Carbon\Carbon $date): string
            {
                throw new CbrConnectionException('Simulated connection failure');
            }
        };
    }

    #[Test]
    public function it_saves_allowed_currencies_and_rates_to_database(): void
    {
        Setting::create(['key' => 'cbr_fetch_currencies', 'value' => ['USD', 'EUR']]);

        $xml = $this->makeXml([
            ['id' => 'R01235', 'charCode' => 'USD', 'name' => 'Dollar', 'nominal' => 1, 'value' => '74,5897', 'vunitRate' => '74,5897'],
            ['id' => 'R01239', 'charCode' => 'EUR', 'name' => 'Euro', 'nominal' => 1, 'value' => '87,7659', 'vunitRate' => '87,7659'],
            ['id' => 'R01375', 'charCode' => 'CNY', 'name' => 'Yuan', 'nominal' => 1, 'value' => '10,9451', 'vunitRate' => '10,9451'],
        ]);

        $service = $this->app->make(CurrencySyncService::class, [
            'client' => $this->makeFakeClient($xml),
        ]);

        $service->sync();

        // Должны сохраниться только USD и EUR, CNY отфильтрован
        $this->assertDatabaseCount('currencies', 2);
        $this->assertDatabaseHas('currencies', ['char_code' => 'USD', 'cbr_id' => 'R01235']);
        $this->assertDatabaseHas('currencies', ['char_code' => 'EUR', 'cbr_id' => 'R01239']);
        $this->assertDatabaseMissing('currencies', ['char_code' => 'CNY']);
    }

    #[Test]
    public function it_saves_rate_for_today(): void
    {
        Setting::create(['key' => 'cbr_fetch_currencies', 'value' => ['USD']]);

        $xml = $this->makeXml([
            ['id' => 'R01235', 'charCode' => 'USD', 'name' => 'Dollar', 'nominal' => 1, 'value' => '74,5897', 'vunitRate' => '74,5897'],
        ]);

        $service = $this->app->make(CurrencySyncService::class, [
            'client' => $this->makeFakeClient($xml),
        ]);

        $service->sync();

        $currency = Currency::where('char_code', 'USD')->firstOrFail();
        $rate = Rate::where('currency_id', $currency->id)
            ->whereDate('date', today())
            ->firstOrFail();

        $this->assertEqualsWithDelta(74.5897, (float) $rate->value, 0.0001);
    }

    #[Test]
    public function it_updates_existing_rate_without_creating_duplicate(): void
    {
        Setting::create(['key' => 'cbr_fetch_currencies', 'value' => ['USD']]);

        $xml = $this->makeXml([
            ['id' => 'R01235', 'charCode' => 'USD', 'name' => 'Dollar', 'nominal' => 1, 'value' => '74,0000', 'vunitRate' => '74,0000'],
        ]);

        $service = $this->app->make(CurrencySyncService::class, [
            'client' => $this->makeFakeClient($xml),
        ]);

        // Первый запуск
        $service->sync();
        $this->assertDatabaseCount('rates', 1);

        // Обновляем XML с новым курсом
        $xmlUpdated = $this->makeXml([
            ['id' => 'R01235', 'charCode' => 'USD', 'name' => 'Dollar', 'nominal' => 1, 'value' => '75,0000', 'vunitRate' => '75,0000'],
        ]);

        $serviceUpdated = $this->app->make(CurrencySyncService::class, [
            'client' => $this->makeFakeClient($xmlUpdated),
        ]);

        // Второй запуск — не должен создать новую запись
        $serviceUpdated->sync();
        $this->assertDatabaseCount('rates', 1);

        // Значение должно обновиться
        $this->assertDatabaseHas('rates', ['value' => '75.0000']);
    }

    #[Test]
    public function it_rethrows_exception_when_client_fails(): void
    {
        $this->expectException(CbrConnectionException::class);

        $service = $this->app->make(CurrencySyncService::class, [
            'client' => $this->makeFailingClient(),
        ]);

        $service->sync();
    }

    #[Test]
    public function it_does_not_save_anything_when_no_currencies_configured(): void
    {
        Setting::create(['key' => 'cbr_fetch_currencies', 'value' => []]);

        $xml = $this->makeXml([
            ['id' => 'R01235', 'charCode' => 'USD', 'name' => 'Dollar', 'nominal' => 1, 'value' => '74,5897', 'vunitRate' => '74,5897'],
        ]);

        $service = $this->app->make(CurrencySyncService::class, [
            'client' => $this->makeFakeClient($xml),
        ]);

        $service->sync();

        $this->assertDatabaseCount('currencies', 0);
        $this->assertDatabaseCount('rates', 0);
    }
}
