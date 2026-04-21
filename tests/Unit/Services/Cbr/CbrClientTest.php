<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cbr;

use App\Exceptions\Cbr\CbrConnectionException;
use App\Exceptions\Cbr\CbrTimeoutException;
use App\Services\Cbr\CbrClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CbrClientTest extends TestCase
{
    #[Test]
    public function it_returns_raw_xml_on_successful_response(): void
    {
        Http::fake([
            '*' => Http::response('<ValCurs>OK</ValCurs>', 200),
        ]);

        $client = new CbrClient();
        $result = $client->getDailyRatesRawData();

        $this->assertStringContainsString('<ValCurs>', $result);
    }

    #[Test]
    public function it_throws_cbr_connection_exception_on_server_error(): void
    {
        Http::fake([
            '*' => Http::response('Internal Server Error', 500),
        ]);

        $this->expectException(CbrConnectionException::class);
        $this->expectExceptionMessageMatches('/HTTP Статус: 500/');

        (new CbrClient())->getDailyRatesRawData();
    }

    #[Test]
    public function it_throws_cbr_connection_exception_on_404(): void
    {
        Http::fake([
            '*' => Http::response('Not Found', 404),
        ]);

        $this->expectException(CbrConnectionException::class);
        $this->expectExceptionMessageMatches('/HTTP Статус: 404/');

        (new CbrClient())->getDailyRatesRawData();
    }

    #[Test]
    public function it_throws_cbr_timeout_exception_on_timeout(): void
    {
        Http::fake(function () {
            throw new ConnectionException('cURL error 28: Operation timed out after 10000 milliseconds');
        });

        $this->expectException(CbrTimeoutException::class);

        (new CbrClient())->getDailyRatesRawData();
    }

    #[Test]
    public function it_throws_cbr_connection_exception_on_network_failure(): void
    {
        Http::fake(function () {
            throw new ConnectionException('cURL error 6: Could not resolve host: www.cbr.ru');
        });

        $this->expectException(CbrConnectionException::class);

        (new CbrClient())->getDailyRatesRawData();
    }
}
