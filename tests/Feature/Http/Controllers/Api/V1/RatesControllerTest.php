<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Models\Currency;
use App\Models\Rate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RatesControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-04-22 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function it_returns_rates_for_today_by_default(): void
    {
        $currency = Currency::create([
            'char_code' => 'USD',
            'name' => 'US Dollar',
            'nominal' => 1,
            'cbr_id' => 'R01235',
            'num_code' => '840',
        ]);

        Rate::create([
            'currency_id' => $currency->id,
            'date' => Carbon::today(),
            'value' => 74.5897,
            'vunit_rate' => 74.5897,
        ]);

        $response = $this->getJson('/api/v1/rates');

        $response->assertOk()
            ->assertJsonStructure([
                'date',
                'rates' => [
                    '*' => [
                        'char_code',
                        'name',
                        'nominal',
                        'value',
                        'vunit_rate',
                    ],
                ],
            ])
            ->assertJsonPath('rates.0.char_code', 'USD')
            ->assertJsonPath('rates.0.value', '74.5897');
    }

    #[Test]
    public function it_filters_rates_by_date(): void
    {
        $currency = Currency::create([
            'char_code' => 'USD',
            'name' => 'US Dollar',
            'nominal' => 1,
            'cbr_id' => 'R01235',
        ]);

        Rate::create([
            'currency_id' => $currency->id,
            'date' => '2026-04-20',
            'value' => 74.5897,
            'vunit_rate' => 74.5897,
        ]);

        Rate::create([
            'currency_id' => $currency->id,
            'date' => '2026-04-21',
            'value' => 75.0000,
            'vunit_rate' => 75.0000,
        ]);

        $response = $this->getJson('/api/v1/rates?date=2026-04-20');

        $response->assertOk()
            ->assertJsonPath('date', '2026-04-20')
            ->assertJsonPath('rates.0.value', '74.5897');
    }

    #[Test]
    public function it_filters_rates_by_currencies(): void
    {
        $usd = Currency::create([
            'char_code' => 'USD',
            'name' => 'US Dollar',
            'nominal' => 1,
            'cbr_id' => 'R01235',
        ]);

        $eur = Currency::create([
            'char_code' => 'EUR',
            'name' => 'Euro',
            'nominal' => 1,
            'cbr_id' => 'R01239',
        ]);

        Rate::create(['currency_id' => $usd->id, 'date' => Carbon::today(), 'value' => 74.5897, 'vunit_rate' => 74.5897]);
        Rate::create(['currency_id' => $eur->id, 'date' => Carbon::today(), 'value' => 87.7659, 'vunit_rate' => 87.7659]);

        $response = $this->getJson('/api/v1/rates?currencies=USD');

        $response->assertOk()
            ->assertJsonCount(1, 'rates')
            ->assertJsonPath('rates.0.char_code', 'USD');
    }

    #[Test]
    public function it_includes_compare_data_when_compare_date_provided(): void
    {
        $currency = Currency::create([
            'char_code' => 'USD',
            'name' => 'US Dollar',
            'nominal' => 1,
            'cbr_id' => 'R01235',
        ]);

        Rate::create([
            'currency_id' => $currency->id,
            'date' => '2026-04-22',
            'value' => 75.0000,
            'vunit_rate' => 75.0000,
        ]);

        Rate::create([
            'currency_id' => $currency->id,
            'date' => '2026-04-21',
            'value' => 74.0000,
            'vunit_rate' => 74.0000,
        ]);

        $response = $this->getJson('/api/v1/rates?date=2026-04-22&compare_date=2026-04-21');

        $response->assertOk()
            ->assertJsonPath('compare_date', '2026-04-21')
            ->assertJsonPath('rates.0.value', '75.0000')
            ->assertJsonPath('rates.0.compare.value', '74.0000');
    }
}
