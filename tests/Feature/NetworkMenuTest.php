<?php

use App\Enums\NetworkProtocol;
use App\Filament\Resources\NetworkHosts\Pages\CreateNetworkHost;
use App\Filament\Resources\NetworkHosts\Pages\EditNetworkHost;
use App\Filament\Resources\NetworkHosts\Pages\ListNetworkHosts;
use App\Livewire\NetworkMenuScreen;
use App\Models\ActivityLog;
use App\Models\NetworkHost;
use App\Models\NetworkHostPort;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Repeater;
use Livewire\Livewire;

test('known .lan DNS migration populates /menu with configured network hosts and service ports', function (): void {
    $expectedDnsNames = [
        'nas.lan',
        'megatron.lan',
        'heimdall.lan',
        'homey2026.lan',
        'homey2018.lan',
        'adguard.lan',
        'omada.lan',
        'router.lan',
        'plex.lan',
        'nvr.lan',
        'reolink.lan',
    ];

    expect(NetworkHost::query()->orderBy('sort_order')->pluck('dns_name')->all())
        ->toBe($expectedDnsNames);

    $this->get('/menu')
        ->assertOk()
        ->assertSeeHtml('href="https://nas.lan:5001"')
        ->assertSeeHtml('href="http://nas.lan:5000"')
        ->assertSeeHtml('href="https://megatron.lan:5001"')
        ->assertSeeHtml('href="http://heimdall.lan:8000"')
        ->assertSeeHtml('href="http://heimdall.lan:8000/admin"')
        ->assertSeeHtml('href="http://adguard.lan:8080"')
        ->assertSeeHtml('href="http://plex.lan:32400/web"')
        ->assertSeeHtml('href="https://omada.lan"')
        ->assertSeeHtml('href="http://router.lan"')
        ->assertSeeHtml('href="http://homey2026.lan"')
        ->assertSeeHtml('href="http://homey2018.lan"')
        ->assertSeeHtml('href="https://nvr.lan"')
        ->assertSeeHtml('href="http://nvr.lan:80"')
        ->assertSeeHtml('href="https://reolink.lan"');

    $initialPortCount = NetworkHostPort::query()->count();
    $migration = require database_path('migrations/2026_11_21_093701_add_known_lan_network_hosts.php');

    $migration->up();

    expect(NetworkHost::query()->count())->toBe(count($expectedDnsNames));
    expect(NetworkHostPort::query()->count())->toBe($initialPortCount);

    $migration->down();

    expect(NetworkHost::query()->whereIn('dns_name', $expectedDnsNames)->count())->toBe(0);
    expect(NetworkHostPort::query()->count())->toBe(0);
});

test('public /menu page is open without login and preserves an active /admin session', function (): void {
    NetworkHost::query()->delete();

    $this->get('/menu')
        ->assertOk()
        ->assertSee('Netwerk & DNS Menu')
        ->assertSee('Nog geen DNS-namen ingesteld')
        ->assertSeeHtml('/admin/network-hosts/create');

    $admin = User::factory()->create([
        'email' => 'admin@heimdall.local',
        'is_active' => true,
        'is_admin' => true,
    ]);

    $this->actingAs($admin)
        ->get('/menu')
        ->assertOk();

    expect(auth()->check())->toBeTrue();
});

test('/menu displays active DNS hosts, primary endpoints, and port additions while hiding inactive entries', function (): void {
    NetworkHost::query()->delete();

    $nas = NetworkHost::factory()->withoutRootLink()->create([
        'name' => 'Synology NAS',
        'dns_name' => 'nas.local',
        'ip_address' => '192.168.1.10',
        'category' => 'Servers & NAS',
        'description' => 'Centrale opslag en containers',
        'sort_order' => 1,
    ]);

    NetworkHostPort::factory()->create([
        'network_host_id' => $nas->id,
        'label' => 'DSM Beheer',
        'protocol' => NetworkProtocol::Http,
        'port' => 5000,
        'path' => null,
        'sort_order' => 1,
    ]);

    NetworkHostPort::factory()->https()->create([
        'network_host_id' => $nas->id,
        'label' => 'Portainer',
        'port' => 9443,
        'path' => '/#!/home',
        'sort_order' => 2,
    ]);

    NetworkHostPort::factory()->inactive()->create([
        'network_host_id' => $nas->id,
        'label' => 'Verborgen Testpoort',
        'port' => 32400,
        'sort_order' => 3,
    ]);

    NetworkHost::factory()->https(8443)->create([
        'name' => 'Unifi Router',
        'dns_name' => 'router.lan',
        'category' => 'Netwerk',
        'path' => '/network',
        'has_root_link' => true,
        'sort_order' => 2,
    ]);

    NetworkHost::factory()->inactive()->create([
        'name' => 'Oude Pi',
        'dns_name' => 'oude-pi.local',
    ]);

    $response = $this->get('/menu');

    $response->assertOk()
        ->assertSee('Synology NAS')
        ->assertSee('nas.local')
        ->assertSee('192.168.1.10')
        ->assertSee('DSM Beheer')
        ->assertSeeHtml('href="http://nas.local:5000"')
        ->assertSee('Portainer')
        ->assertSeeHtml('href="https://nas.local:9443/#!/home"')
        ->assertSee('Unifi Router')
        ->assertSeeHtml('href="https://router.lan:8443/network"')
        ->assertDontSeeHtml('href="http://nas.local"')
        ->assertDontSee('Verborgen Testpoort')
        ->assertDontSee(':32400')
        ->assertDontSee('Oude Pi')
        ->assertDontSee('oude-pi.local');
});

test('NetworkMenuScreen filters DNS hosts by search query, port number, and category', function (): void {
    NetworkHost::query()->delete();

    $nas = NetworkHost::factory()->withoutRootLink()->create([
        'name' => 'Synology NAS',
        'dns_name' => 'nas.local',
        'ip_address' => '192.168.1.10',
        'category' => 'Servers & NAS',
    ]);

    NetworkHostPort::factory()->create([
        'network_host_id' => $nas->id,
        'label' => 'Heimdall Beheer',
        'port' => 8000,
        'path' => '/admin',
    ]);

    NetworkHost::factory()->create([
        'name' => 'Homey Pro',
        'dns_name' => 'homey.local',
        'ip_address' => '192.168.1.50',
        'category' => 'Smart Home',
    ]);

    Livewire::test(NetworkMenuScreen::class)
        ->set('search', ':8000')
        ->assertSee('Synology NAS')
        ->assertDontSee('Homey Pro')
        ->set('search', '192.168.1.50')
        ->assertSee('Homey Pro')
        ->assertDontSee('Synology NAS')
        ->set('search', 'onbestaand-apparaat')
        ->assertSee('Geen DNS-namen of poorten gevonden')
        ->call('resetFilters')
        ->assertSet('search', '')
        ->assertSee('Synology NAS')
        ->assertSee('Homey Pro')
        ->call('selectCategory', 'Smart Home')
        ->assertSee('Homey Pro')
        ->assertDontSee('Synology NAS');
});

test('guests are redirected to /admin/login when accessing /admin/network-hosts', function (): void {
    $this->get('/admin/network-hosts')
        ->assertRedirect('/admin/login');
});

test('admin creates, updates, and deletes a NetworkHost with multiple port additions in Filament', function (): void {
    NetworkHost::query()->delete();

    $undoRepeaterFake = Repeater::fake();

    $admin = User::factory()->create([
        'email' => 'admin@heimdall.local',
        'is_active' => true,
        'is_admin' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test(CreateNetworkHost::class)
        ->fillForm([
            'name' => 'Media & Docker Server',
            'dns_name' => 'HTTP://DOCKER.LOCAL/',
            'category' => 'Servers & NAS',
            'ip_address' => '192.168.1.20',
            'description' => 'Lokale Docker host',
            'is_active' => true,
            'open_in_new_tab' => true,
            'has_root_link' => false,
            'protocol' => NetworkProtocol::Http->value,
            'port' => null,
            'path' => null,
            'ports' => [
                [
                    'label' => 'Jellyfin',
                    'protocol' => NetworkProtocol::Http->value,
                    'port' => 8096,
                    'path' => 'web',
                    'is_active' => true,
                    'open_in_new_tab' => true,
                ],
                [
                    'label' => 'AdGuard Home',
                    'protocol' => NetworkProtocol::Https->value,
                    'port' => 3000,
                    'path' => '/',
                    'is_active' => true,
                    'open_in_new_tab' => true,
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $host = NetworkHost::query()->where('dns_name', 'docker.local')->first();

    expect($host)->not->toBeNull();
    $this->assertModelExists($host);

    expect($host->name)->toBe('Media & Docker Server')
        ->and($host->has_root_link)->toBeFalse()
        ->and($host->ports)->toHaveCount(2)
        ->and($host->ports[0]->resolvedUrl($host))->toBe('http://docker.local:8096/web')
        ->and($host->ports[1]->resolvedUrl($host))->toBe('https://docker.local:3000');

    expect(ActivityLog::query()->where('message', 'Netwerk DNS-naam toegevoegd: Media & Docker Server (docker.local)')->exists())->toBeTrue();

    Livewire::test(EditNetworkHost::class, ['record' => $host->getRouteKey()])
        ->fillForm([
            'name' => 'Docker Host Bijgewerkt',
            'has_root_link' => true,
            'protocol' => NetworkProtocol::Https->value,
            'port' => 443,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $host->refresh();
    expect($host->name)->toBe('Docker Host Bijgewerkt')
        ->and($host->has_root_link)->toBeTrue()
        ->and($host->resolvedUrl())->toBe('https://docker.local:443');

    $portIds = $host->ports()->pluck('id')->all();

    Livewire::test(ListNetworkHosts::class)
        ->assertCanSeeTableRecords([$host])
        ->callTableAction(DeleteAction::class, $host);

    $this->assertModelMissing($host);
    expect(NetworkHostPort::query()->whereIn('id', $portIds)->count())->toBe(0)
        ->and(ActivityLog::query()->where('message', 'Netwerk DNS-naam verwijderd: Docker Host Bijgewerkt (docker.local)')->exists())->toBeTrue();

    $undoRepeaterFake();
});

test('NetworkHostForm validates required fields, DNS host syntax, IP address, and port bounds, and escapes HTML on /menu', function (): void {
    NetworkHost::query()->delete();

    $undoRepeaterFake = Repeater::fake();

    $admin = User::factory()->create([
        'email' => 'admin@heimdall.local',
        'is_active' => true,
        'is_admin' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test(CreateNetworkHost::class)
        ->fillForm([
            'name' => '',
            'dns_name' => '',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'name' => 'Geef een herkenbare naam op.',
            'dns_name' => 'Vul een DNS-naam of IP-adres in.',
        ]);

    Livewire::test(CreateNetworkHost::class)
        ->fillForm([
            'name' => 'Ongeldige Host',
            'dns_name' => 'javascript:alert(1)',
            'ip_address' => '999.999.999.999',
            'port' => 70000,
            'ports' => [
                [
                    'label' => '',
                    'protocol' => NetworkProtocol::Http->value,
                    'port' => 0,
                ],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors([
            'dns_name' => 'Vul een geldige DNS-naam of IP-adres in zonder poort of pad (bijv. nas.local of 192.168.1.10).',
            'ip_address' => 'Vul een geldig IPv4- of IPv6-adres in.',
            'port' => 'Poortnummer moet tussen 1 en 65535 liggen.',
            'ports.0.label' => 'Geef een naam op voor deze poort/dienst.',
            'ports.0.port' => 'Poortnummer moet tussen 1 en 65535 liggen.',
        ]);

    expect(NetworkHost::query()->count())->toBe(0);

    $undoRepeaterFake();

    NetworkHost::factory()->create([
        'name' => 'Test <script>alert(1)</script> Server',
        'dns_name' => 'veilig.local',
        'description' => '<img src=x onerror=alert(1)>',
    ]);

    $this->get('/menu')
        ->assertOk()
        ->assertSee('Test <script>alert(1)</script> Server')
        ->assertDontSee('<script>alert(1)</script>', escape: false)
        ->assertDontSee('<img src=x onerror=alert(1)>', escape: false);
});
