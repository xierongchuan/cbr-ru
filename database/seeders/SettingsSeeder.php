<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::updateOrCreate(
            ['key' => 'cbr_fetch_currencies'],
            ['value' => ['USD', 'EUR', 'CNY']]
        );

        Setting::updateOrCreate(
            ['key' => 'widget_currencies'],
            ['value' => ['USD', 'EUR']]
        );

        Setting::updateOrCreate(
            ['key' => 'widget_update_interval'],
            ['value' => 60]
        );

        Setting::updateOrCreate(
            ['key' => 'fetch_date_offset'],
            ['value' => 0] // 0 = сегодня, 1 = завтра
        );
    }
}
