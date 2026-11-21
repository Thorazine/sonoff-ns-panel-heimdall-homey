<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @return list<array{
     *     name: string,
     *     dns_name: string,
     *     ip_address: string,
     *     category: string,
     *     description: string,
     *     protocol: string,
     *     port: int|null,
     *     path: string|null,
     *     has_root_link: bool,
     *     sort_order: int,
     *     ports: list<array{label: string, protocol: string, port: int, path: string|null, sort_order: int}>
     * }>
     */
    private function knownHosts(): array
    {
        return [
            [
                'name' => 'Synology NAS (DSM)',
                'dns_name' => 'nas.lan',
                'ip_address' => '192.168.0.50',
                'category' => 'Servers & NAS',
                'description' => 'Synology DS918+ DSM beheeromgeving en centrale opslag',
                'protocol' => 'https',
                'port' => 5001,
                'path' => null,
                'has_root_link' => true,
                'sort_order' => 1,
                'ports' => [
                    [
                        'label' => 'DSM Beheer (HTTP)',
                        'protocol' => 'http',
                        'port' => 5000,
                        'path' => null,
                        'sort_order' => 1,
                    ],
                ],
            ],
            [
                'name' => 'MEGATRON (NAS alias)',
                'dns_name' => 'megatron.lan',
                'ip_address' => '192.168.0.50',
                'category' => 'Servers & NAS',
                'description' => 'Synology DS918+ server (DSM, Docker containers & services)',
                'protocol' => 'https',
                'port' => 5001,
                'path' => null,
                'has_root_link' => true,
                'sort_order' => 2,
                'ports' => [
                    [
                        'label' => 'DSM Beheer (HTTP)',
                        'protocol' => 'http',
                        'port' => 5000,
                        'path' => null,
                        'sort_order' => 1,
                    ],
                ],
            ],
            [
                'name' => 'Heimdall Kiosk & Admin',
                'dns_name' => 'heimdall.lan',
                'ip_address' => '192.168.0.50',
                'category' => 'Smart Home',
                'description' => 'Sonoff NS Panel Pro Heimdall Kiosk en Filament beheerpaneel',
                'protocol' => 'http',
                'port' => 8000,
                'path' => null,
                'has_root_link' => true,
                'sort_order' => 3,
                'ports' => [
                    [
                        'label' => 'Admin Beheerpaneel',
                        'protocol' => 'http',
                        'port' => 8000,
                        'path' => '/admin',
                        'sort_order' => 1,
                    ],
                ],
            ],
            [
                'name' => 'Homey Pro 2026',
                'dns_name' => 'homey2026.lan',
                'ip_address' => '192.168.30.10',
                'category' => 'Smart Home',
                'description' => 'Primaire Homey Pro (2026) domotica-controller en Heimdall alarm',
                'protocol' => 'http',
                'port' => null,
                'path' => null,
                'has_root_link' => true,
                'sort_order' => 4,
                'ports' => [],
            ],
            [
                'name' => 'Homey 2018',
                'dns_name' => 'homey2018.lan',
                'ip_address' => '192.168.30.11',
                'category' => 'Smart Home',
                'description' => 'Tweede Homey (2018) domotica-controller op Thuis_IoT',
                'protocol' => 'http',
                'port' => null,
                'path' => null,
                'has_root_link' => true,
                'sort_order' => 5,
                'ports' => [],
            ],
            [
                'name' => 'AdGuard Home',
                'dns_name' => 'adguard.lan',
                'ip_address' => '192.168.0.50',
                'category' => 'Netwerk',
                'description' => 'Netwerkbrede DNS-server, lokale .lan-rewrites en advertentieblokkering',
                'protocol' => 'http',
                'port' => 8080,
                'path' => null,
                'has_root_link' => true,
                'sort_order' => 6,
                'ports' => [],
            ],
            [
                'name' => 'Omada OC200 Controller',
                'dns_name' => 'omada.lan',
                'ip_address' => '192.168.0.101',
                'category' => 'Netwerk',
                'description' => 'TP-Link Omada SDN controller voor router, switch, VLANs en wifi',
                'protocol' => 'https',
                'port' => null,
                'path' => null,
                'has_root_link' => true,
                'sort_order' => 7,
                'ports' => [],
            ],
            [
                'name' => 'TP-Link ER707-M2 Router',
                'dns_name' => 'router.lan',
                'ip_address' => '192.168.0.1',
                'category' => 'Netwerk',
                'description' => 'TP-Link Omada Multi-Gigabit VPN gateway',
                'protocol' => 'http',
                'port' => null,
                'path' => null,
                'has_root_link' => true,
                'sort_order' => 8,
                'ports' => [],
            ],
            [
                'name' => 'Plex Media Server',
                'dns_name' => 'plex.lan',
                'ip_address' => '192.168.0.50',
                'category' => 'Media',
                'description' => 'Plex Media Server webspeler en bibliotheekbeheer op Synology NAS',
                'protocol' => 'http',
                'port' => 32400,
                'path' => '/web',
                'has_root_link' => true,
                'sort_order' => 9,
                'ports' => [],
            ],
            [
                'name' => 'Reolink NVR',
                'dns_name' => 'nvr.lan',
                'ip_address' => '192.168.50.10',
                'category' => 'Beveiliging',
                'description' => 'Reolink NVR camerarecorder en deurbel-opnames op CCTV VLAN',
                'protocol' => 'https',
                'port' => null,
                'path' => null,
                'has_root_link' => true,
                'sort_order' => 10,
                'ports' => [
                    [
                        'label' => 'Webinterface (HTTP)',
                        'protocol' => 'http',
                        'port' => 80,
                        'path' => null,
                        'sort_order' => 1,
                    ],
                ],
            ],
            [
                'name' => 'Reolink NVR (alias)',
                'dns_name' => 'reolink.lan',
                'ip_address' => '192.168.50.10',
                'category' => 'Beveiliging',
                'description' => 'Alias voor de Reolink NVR camerarecorder',
                'protocol' => 'https',
                'port' => null,
                'path' => null,
                'has_root_link' => true,
                'sort_order' => 11,
                'ports' => [],
            ],
        ];
    }

    public function up(): void
    {
        if (! Schema::hasTable('network_hosts') || ! Schema::hasTable('network_host_ports')) {
            return;
        }

        $now = now();

        foreach ($this->knownHosts() as $entry) {
            $existingHost = DB::table('network_hosts')
                ->where('dns_name', $entry['dns_name'])
                ->first();

            if ($existingHost !== null) {
                $hostId = (int) $existingHost->id;
            } else {
                $hostId = (int) DB::table('network_hosts')->insertGetId([
                    'name' => $entry['name'],
                    'dns_name' => $entry['dns_name'],
                    'ip_address' => $entry['ip_address'],
                    'category' => $entry['category'],
                    'description' => $entry['description'],
                    'protocol' => $entry['protocol'],
                    'port' => $entry['port'],
                    'path' => $entry['path'],
                    'has_root_link' => $entry['has_root_link'],
                    'open_in_new_tab' => true,
                    'sort_order' => $entry['sort_order'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach ($entry['ports'] as $portEntry) {
                $portQuery = DB::table('network_host_ports')
                    ->where('network_host_id', $hostId)
                    ->where('port', $portEntry['port']);

                if ($portEntry['path'] === null) {
                    $portQuery->whereNull('path');
                } else {
                    $portQuery->where('path', $portEntry['path']);
                }

                if (! $portQuery->exists()) {
                    DB::table('network_host_ports')->insert([
                        'network_host_id' => $hostId,
                        'label' => $portEntry['label'],
                        'protocol' => $portEntry['protocol'],
                        'port' => $portEntry['port'],
                        'path' => $portEntry['path'],
                        'open_in_new_tab' => true,
                        'sort_order' => $portEntry['sort_order'],
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('network_hosts')) {
            return;
        }

        $dnsNames = array_map(
            static fn (array $entry): string => $entry['dns_name'],
            $this->knownHosts(),
        );

        $hostIds = DB::table('network_hosts')
            ->whereIn('dns_name', $dnsNames)
            ->pluck('id')
            ->all();

        if ($hostIds !== [] && Schema::hasTable('network_host_ports')) {
            DB::table('network_host_ports')
                ->whereIn('network_host_id', $hostIds)
                ->delete();
        }

        DB::table('network_hosts')
            ->whereIn('dns_name', $dnsNames)
            ->delete();
    }
};
