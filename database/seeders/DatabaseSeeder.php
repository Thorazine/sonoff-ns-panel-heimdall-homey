<?php

namespace Database\Seeders;

use App\Models\KioskMenu;
use App\Models\ReolinkCamera;
use App\Models\Setting;
use App\Models\WallPanel;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        KioskMenu::ensureRootExists();
        WallPanel::ensureDefaultsExist();
        ReolinkCamera::ensureDefaultsExist();
        Setting::ensureDoorbellWebhookToken();

        foreach (Setting::DEFAULTS as $key => $defaultValue) {
            if (! Setting::query()->where('key', $key)->exists()) {
                Setting::setValue($key, $defaultValue);
            }
        }
    }
}
