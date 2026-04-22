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
    private string $url;

    private const int TIMEOUT_SECONDS = 10;

    public function __construct()
    {
        $this->url = config('services.cbr_api_url');
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
    public function getDailyRatesRawData(?Carbon $date = null): string
    {
        $url = $this->url;
        if ($date) {
            $url .= '?date_req='.$date->format('d/m/Y');
        }

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->get($url);

            if ($response->failed()) {
                $dateStr = $date ? ' за '.$date->format('d.m.Y') : '';
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
