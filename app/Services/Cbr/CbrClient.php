<?php

declare(strict_types=1);

namespace App\Services\Cbr;

use App\Contracts\ExchangeRatesClientInterface;
use App\Exceptions\Cbr\CbrConnectionException;
use App\Exceptions\Cbr\CbrException;
use App\Exceptions\Cbr\CbrTimeoutException;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class CbrClient implements ExchangeRatesClientInterface
{
    private const string ENDPOINT_DAILY = '/scripts/XML_daily.asp';

    private const string ENDPOINT_VALUTES = '/scripts/XML_val.asp';

    private const string ENDPOINT_DYNAMIC = '/scripts/XML_dynamic.asp';

    private const int TIMEOUT_SECONDS = 10;

    private readonly string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.cbr_api_base_url');
    }

    public function getDailyRatesRawData(?Carbon $date = null): string
    {
        $url = $this->baseUrl.self::ENDPOINT_DAILY;

        if ($date) {
            $url .= '?date_req='.$date->format('d/m/Y');
        }

        return $this->executeRequest($url, $date?->format('d.m.Y'));
    }

    /**
     * Выполняет запрос к API ЦБ РФ и возвращает XML.
     *
     * @return string XML с курсами валют
     *
     * @throws CbrConnectionException
     * @throws CbrTimeoutException
     * @throws CbrException
     */
    private function executeRequest(string $url, ?string $dateInfo = null): string
    {
        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->get($url);

            if ($response->failed()) {
                $dateStr = $dateInfo ? ' за '.$dateInfo : '';
                throw new CbrConnectionException(
                    sprintf('Не удалось получить данные от ЦБ РФ%s. HTTP Статус: %d', $dateStr, $response->status())
                );
            }

            return $response->body();

        } catch (ConnectionException $e) {
            if (str_contains(strtolower($e->getMessage()), 'timeout') || str_contains(strtolower($e->getMessage()), 'timed out')) {
                throw new CbrTimeoutException('Превышено время ожидания ответа от ЦБ РФ', 0, $e);
            }
            throw new CbrConnectionException('Ошибка подключения к ЦБ РФ: '.$e->getMessage(), 0, $e);
        } catch (Throwable $e) {
            if ($e instanceof CbrException) {
                throw $e;
            }
            throw new CbrException('Непредвиденная ошибка при запросе к ЦБ РФ: '.$e->getMessage(), 0, $e);
        }
    }
}
