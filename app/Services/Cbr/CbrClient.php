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

/**
 * Клиент для работы с API ЦБ РФ.
 *
 * Эндпоинты API:
 * - XML_daily.asp     — курсы валют на дату
 * - XML_val.asp      — справочник валют
 * - XML_dynamic.asp — динамика курсов валюты
 *
 * Все ответы возвращаются в формате XML в кодировке Windows-1251.
 */
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

    /**
     * Получить курсы валют на указанную дату.
     *
     * @param  Carbon|null  $date  Дата (null = последние данные)
     * @param  bool  $monthly  true = ежемесячные курсы, false = ежедневные
     * @return string XML с курсами валют
     */
    public function getDailyRatesOnDate(
        ?Carbon $date = null,
        bool $monthly = false,
    ): string {
        $url = $this->baseUrl.self::ENDPOINT_DAILY;

        $queryParams = [];

        if ($date) {
            $queryParams['date_req'] = $date->format('d/m/Y');
        }

        if ($monthly) {
            $queryParams['d'] = 1;
        }

        if ($queryParams) {
            $url .= '?'.http_build_query($queryParams);
        }

        return $this->executeRequest($url, $date?->format('d.m.Y'));
    }

    /**
     * Получить справочник валют ЦБ РФ.
     *
     * @param  bool  $monthly  true = ежемесячные коды, false = ежедневные
     * @return string XML со справочником валют
     */
    public function getCurrencyDictionary(bool $monthly = false): string
    {
        $url =
            $this->baseUrl.
            self::ENDPOINT_VALUTES.
            '?d='.
            ($monthly ? 1 : 0);

        return $this->executeRequest($url);
    }

    /**
     * Получить динамику курса конкретной валюты за период.
     *
     * @param  string  $cbrId  ID валюты в ЦБ (например, R01235 для USD)
     * @param  Carbon  $from  Дата начала периода
     * @param  Carbon  $to  Дата окончания периода
     * @return string XML с динамикой курсов
     */
    public function getCurrencyDynamics(
        string $cbrId,
        Carbon $from,
        Carbon $to,
    ): string {
        $url =
            $this->baseUrl.
            self::ENDPOINT_DYNAMIC.
            '?'.
            http_build_query([
                'date_req1' => $from->format('d/m/Y'),
                'date_req2' => $to->format('d/m/Y'),
                'VAL_NM_RQ' => $cbrId,
            ]);

        return $this->executeRequest(
            $url,
            "{$from->format('d.m.Y')}-{$to->format('d.m.Y')}",
        );
    }

    /**
     * Выполняет HTTP-запрос к API ЦБ РФ.
     *
     * @param  string  $url  Полный URL запроса
     * @param  string|null  $dateInfo  Информация о дате для логирования
     * @return string XML-ответ от API
     *
     * @throws CbrConnectionException При ошибке соединения или HTTP-ошибке
     * @throws CbrTimeoutException При таймауте
     * @throws CbrException При других ошибках
     */
    private function executeRequest(
        string $url,
        ?string $dateInfo = null,
    ): string {
        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)->get($url);

            if ($response->failed()) {
                $dateStr = $dateInfo ? ' за '.$dateInfo : '';
                throw new CbrConnectionException(
                    sprintf(
                        'Не удалось получить данные от ЦБ РФ%s. HTTP Статус: %d',
                        $dateStr,
                        $response->status(),
                    ),
                );
            }

            return $response->body();
        } catch (ConnectionException $e) {
            if (
                str_contains(strtolower($e->getMessage()), 'timeout') ||
                str_contains(strtolower($e->getMessage()), 'timed out')
            ) {
                throw new CbrTimeoutException(
                    'Превышено время ожидания ответа от ЦБ РФ',
                    0,
                    $e,
                );
            }
            throw new CbrConnectionException(
                'Ошибка подключения к ЦБ РФ: '.$e->getMessage(),
                0,
                $e,
            );
        } catch (Throwable $e) {
            if ($e instanceof CbrException) {
                throw $e;
            }
            throw new CbrException(
                'Непредвиденная ошибка при запросе к ЦБ РФ: '.
                    $e->getMessage(),
                0,
                $e,
            );
        }
    }
}
