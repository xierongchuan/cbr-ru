<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CurrenciesControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_list_of_currencies(): void
    {
        Currency::create([
            'char_code' => 'USD',
            'name' => 'US Dollar',
            'nominal' => 1,
            'cbr_id' => 'R01235',
            'num_code' => '840',
        ]);

        Currency::create([
            'char_code' => 'EUR',
            'name' => 'Euro',
            'nominal' => 1,
            'cbr_id' => 'R01239',
            'num_code' => '978',
        ]);

        $response = $this->getJson('/api/v1/currencies');

        $response->assertOk()
            ->assertJsonStructure([
                'currencies' => [
                    '*' => [
                        'id',
                        'cbr_id',
                        'char_code',
                        'num_code',
                        'name',
                        'nominal',
                    ],
                ],
            ])
            ->assertJsonCount(2, 'currencies');
    }

    #[Test]
    public function it_returns_currencies_ordered_by_char_code(): void
    {
        Currency::create(['char_code' => 'USD', 'name' => 'US Dollar', 'nominal' => 1, 'cbr_id' => 'R01235']);
        Currency::create(['char_code' => 'CNY', 'name' => 'Yuan', 'nominal' => 1, 'cbr_id' => 'R01375']);
        Currency::create(['char_code' => 'EUR', 'name' => 'Euro', 'nominal' => 1, 'cbr_id' => 'R01239']);

        $response = $this->getJson('/api/v1/currencies');

        $response->assertOk()
            ->assertJsonPath('currencies.0.char_code', 'CNY')
            ->assertJsonPath('currencies.1.char_code', 'EUR')
            ->assertJsonPath('currencies.2.char_code', 'USD');
    }
}
