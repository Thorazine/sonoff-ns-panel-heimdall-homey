<?php

namespace Database\Seeders;

use App\Enums\NetworkProtocol;
use App\Models\NetworkHost;
use Illuminate\Database\Seeder;

class NetworkHostSeeder extends Seeder
{
    public function run(): void
    {
        if (NetworkHost::query()->exists()) {
            return;
        }

        $nas = NetworkHost::query()->create([
            'name' => 'Synology NAS',
            'dns_name' => 'nas.local',
            'category' => 'Servers & NAS',
            'description' => 'Centrale NAS-opslag, Docker containers en Heimdall Kiosk',
            'protocol' => NetworkProtocol::Http,
            'has_root_link' => false,
            'open_in_new_tab' => true,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $nas->ports()->createMany([
            [
                'label' => 'DSM Beheer',
                'protocol' => NetworkProtocol::Http,
                'port' => 5000,
                'path' => null,
                'open_in_new_tab' => true,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'label' => 'Heimdall Beheer',
                'protocol' => NetworkProtocol::Http,
                'port' => 8000,
                'path' => '/admin',
                'open_in_new_tab' => true,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'label' => 'go2rtc Camera Streams',
                'protocol' => NetworkProtocol::Http,
                'port' => 1984,
                'path' => null,
                'open_in_new_tab' => true,
                'sort_order' => 3,
                'is_active' => true,
            ],
        ]);

        NetworkHost::query()->create([
            'name' => 'Homey Pro',
            'dns_name' => 'homey.local',
            'category' => 'Smart Home',
            'description' => 'Domotica controller en Heimdall alarm',
            'protocol' => NetworkProtocol::Http,
            'port' => null,
            'path' => null,
            'has_root_link' => true,
            'open_in_new_tab' => true,
            'sort_order' => 2,
            'is_active' => true,
        ]);
    }
}
