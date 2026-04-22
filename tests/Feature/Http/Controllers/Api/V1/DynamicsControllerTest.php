<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Models\Currency;
use App\Models\Rate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DynamicsControllerTest extends TestCase
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
    public function it_returns_dynamics_for_currency_in_date_range(): void
    {
        $currency = Currency::create([
            'char_code' => 'USD',
            'name' => 'US Dollar',
            'nominal' => 1,
            'cbr_id' => 'R01235',
        ]);

        Rate::create(['currency_id' => $currency->id, 'date' => '2026-04-20', 'value' => 74.0000, 'vunit_rate' => 74.0000]);
        Rate::create(['currency_id' => $currency->id, 'date' => '2026-04-21', 'value' => 74.5000, 'vunit_rate' => 74.5000]);
        Rate::create(['currency_id' => $currency->id, 'date' => '2026-04-22', 'value' => 75.0000, 'vunit_rate' => 75.0000]);

        $response = $this->getJson('/api/v1/dynamics?char_code=USD&from=2026-04-20&to=2026-04-22');

        $response->assertOk()
            ->assertJsonStructure([
                'char_code',
                'cbr_id',
                'from',
                'to',
                'dynamics' => [
                    '*' => ['date', 'value', 'vunit_rate'],
                ],
            ])
            ->assertJsonCount(3, 'dynamics')
            ->assertJsonPath('char_code', 'USD');
    }

    #[Test]
    public function it_returns_404_when_currency_not_found(): void
    {
        $response = $this->getJson('/api/v1/dynamics?char_code=XXX&from=2026-04-20&to=2026-04-22');

        $response->assertNotFound()
            ->assertJsonPath('error', 'Валюта не найдена');
    }

    #[Test]
    public function it_validates_required_parameters(): void
    {
        $response = $this->getJson('/api/v1/dynamics');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['char_code', 'from', 'to']);
    }
}
