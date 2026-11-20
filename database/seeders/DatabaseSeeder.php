<?php

namespace Database\Seeders;

use App\Models\KioskMenu;
use App\Models\PinCode;
use App\Models\ReolinkCamera;
use App\Models\Setting;
use App\Models\User;
use App\Models\WallPanel;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => (string) config('heimdall.admin_email', 'admin@heimdall.local')],
            [
                'name' => (string) config('heimdall.admin_name', 'Heimdall Beheerder'),
                'password' => (string) config('heimdall.admin_password', 'heimdall-admin'),
            ]
        );

        PinCode::seedDefaultIfEmpty();
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
