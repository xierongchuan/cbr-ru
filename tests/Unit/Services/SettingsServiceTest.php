<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    private SettingsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SettingsService();
        Cache::flush();
    }

    #[Test]
    public function it_returns_default_value_when_setting_does_not_exist(): void
    {
        $result = $this->service->get('nonexistent_key', 'default_value');

        $this->assertSame('default_value', $result);
    }

    #[Test]
    public function it_saves_setting_to_database(): void
    {
        $this->service->set('test_key', ['USD', 'EUR']);

        $this->assertDatabaseHas('settings', ['key' => 'test_key']);
    }

    #[Test]
    public function it_retrieves_setting_from_database(): void
    {
        $this->service->set('test_key', ['USD', 'EUR']);
        Cache::flush(); // Принудительно сбрасываем кэш для чистоты теста

        $result = $this->service->get('test_key');

        $this->assertSame(['USD', 'EUR'], $result);
    }

    #[Test]
    public function it_caches_setting_after_first_get(): void
    {
        Setting::create(['key' => 'cached_key', 'value' => ['CNY']]);

        // Первый вызов должен загрузить из БД и закэшировать
        $this->service->get('cached_key');

        // Удаляем запись из БД — теперь данные должны браться из кэша
        Setting::where('key', 'cached_key')->delete();

        $result = $this->service->get('cached_key');

        $this->assertSame(['CNY'], $result);
    }

    #[Test]
    public function it_updates_cache_when_setting_is_changed(): void
    {
        $this->service->set('upd_key', ['USD']);
        $this->service->set('upd_key', ['EUR']); // Обновляем

        $result = $this->service->get('upd_key');

        $this->assertSame(['EUR'], $result);
    }

    #[Test]
    public function it_returns_default_cbr_fetch_currencies(): void
    {
        $currencies = $this->service->getCbrFetchCurrencies();

        $this->assertIsArray($currencies);
        $this->assertContains('USD', $currencies);
        $this->assertContains('EUR', $currencies);
    }

    #[Test]
    public function it_returns_default_widget_update_interval_as_integer(): void
    {
        $interval = $this->service->getWidgetUpdateInterval();

        $this->assertIsInt($interval);
        $this->assertGreaterThan(0, $interval);
    }

    #[Test]
    public function it_uses_updateorcreate_so_duplicate_keys_are_not_created(): void
    {
        $this->service->set('unique_key', ['first']);
        $this->service->set('unique_key', ['second']);

        $this->assertDatabaseCount('settings', 1);
    }
}
