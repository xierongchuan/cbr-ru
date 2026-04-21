<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\ExchangeRatesClientInterface;
use App\Services\Cbr\CbrClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ExchangeRatesClientInterface::class, CbrClient::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
