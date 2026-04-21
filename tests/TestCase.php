<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Принудительно переключаем соединение на SQLite :memory: для всех тестов.
     * Это гарантирует изоляцию тестов от production БД независимо от .env.
     */
    protected function setUp(): void
    {
        if (! $this->app) {
            $this->refreshApplication();
        }

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        \Illuminate\Support\Facades\DB::purge();

        parent::setUp();
    }
}


