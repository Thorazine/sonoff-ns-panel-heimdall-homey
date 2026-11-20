<?php

use App\Enums\AlarmMode;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Services\HomeyService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Setting::setMany([
        'homey_base_url' => 'http://192.168.1.50',
        'homey_api_key' => 'test-homey-bearer-token',
    ]);
});

test('T14: dynamically locates Heimdall surveillancemodeswitch device and reads alarm status', function (): void {
    Http::fake([
        'http://192.168.1.50/api/manager/devices/device' => Http::response([
            'dev-lamp' => ['name' => 'Plafondlamp', 'driverId' => 'zigbee_light'],
            'dev-heimdall' => ['name' => 'Surveillance Mode Switch', 'driverId' => 'surveillancemodeswitch'],
        ], 200),
        'http://192.168.1.50/api/manager/devices/device/dev-heimdall' => Http::response([
            'id' => 'dev-heimdall',
            'capabilitiesObj' => [
                'homealarm_state' => ['value' => 'partially_armed'],
            ],
        ], 200),
    ]);

    $homey = app(HomeyService::class);

    expect($homey->getHeimdallDeviceId())->toBe('dev-heimdall')
        ->and($homey->getAlarmStatus())->toBe('partially_armed');
});

test('T15: updates homealarm_state via PUT with Bearer token and records activity log', function (): void {
    Http::fake([
        'http://192.168.1.50/api/manager/devices/device' => Http::response([
            'dev-heimdall' => ['driverId' => 'surveillancemodeswitch'],
        ], 200),
        'http://192.168.1.50/api/manager/devices/device/dev-heimdall/capability/homealarm_state' => Http::response([
            'value' => 'armed',
        ], 200),
    ]);

    $ok = app(HomeyService::class)->setAlarmMode(AlarmMode::Armed);

    expect($ok)->toBeTrue();

    Http::assertSent(function (Request $request): bool {
        return $request->method() === 'PUT'
            && str_ends_with($request->url(), '/capability/homealarm_state')
            && $request->hasHeader('Authorization', 'Bearer test-homey-bearer-token')
            && ($request->data()['value'] ?? null) === 'armed';
    });

    expect(ActivityLog::query()->where('message', 'Alarm status: Volledig ingeschakeld')->exists())->toBeTrue();
});

test('T16: automatically falls back from https:// to http:// when local Homey rejects TLS connection', function (): void {
    Setting::setValue('homey_base_url', 'https://192.168.1.50');

    Http::fake([
        'https://192.168.1.50/*' => fn () => throw new ConnectionException('SSL handshake failed'),
        'http://192.168.1.50/api/manager/devices/device' => Http::response([
            'dev-heimdall' => ['driverId' => 'surveillancemodeswitch'],
        ], 200),
    ]);

    expect(app(HomeyService::class)->getHeimdallDeviceId())->toBe('dev-heimdall');
});

test('T17: retrieves sensors mapped to zones and sorts active sensors first with battery level', function (): void {
    Http::fake([
        'http://192.168.1.50/api/manager/devices/device' => Http::response([
            's-closed' => [
                'name' => 'Voordeur',
                'zone' => 'z-hal',
                'capabilitiesObj' => [
                    'alarm_contact' => ['value' => false],
                    'measure_battery' => ['value' => 92],
                ],
            ],
            's-open' => [
                'name' => 'Schuifpui',
                'zone' => 'z-woon',
                'capabilitiesObj' => [
                    'alarm_contact' => ['value' => true],
                    'measure_battery' => ['value' => 44],
                ],
            ],
        ], 200),
        'http://192.168.1.50/api/manager/zones/zone' => Http::response([
            'z-hal' => ['name' => 'Hal'],
            'z-woon' => ['name' => 'Woonkamer'],
        ], 200),
    ]);

    $homey = app(HomeyService::class);
    $all = $homey->getAllSensors();
    $open = $homey->getOpenSensors();

    expect($all)->toHaveCount(2)
        ->and($all[0]['name'])->toBe('Schuifpui')
        ->and($all[0]['active'])->toBeTrue()
        ->and($all[0]['battery'])->toBe(44)
        ->and($open)->toBe(['Woonkamer - Schuifpui']);
});

test('T18: fetches standard and advanced flows sorted alphabetically and triggers with fallback', function (): void {
    Http::fake([
        'http://192.168.1.50/api/manager/flow/flow' => Http::response([
            'f-std' => ['name' => 'Zolder Uit', 'enabled' => true],
            'f-disabled' => ['name' => 'Oude Flow', 'enabled' => false],
        ], 200),
        'http://192.168.1.50/api/manager/flow/advancedflow' => Http::response([
            'f-adv' => ['name' => 'Avond Scene', 'enabled' => true],
        ], 200),
        'http://192.168.1.50/api/manager/flow/flow/f-adv/trigger' => Http::response([], 404),
        'http://192.168.1.50/api/manager/flow/advancedflow/f-adv/trigger' => Http::response([], 200),
    ]);

    $homey = app(HomeyService::class);
    $flows = $homey->getFlows();

    expect($flows)->toHaveCount(2)
        ->and($flows[0]['name'])->toBe('Avond Scene (Advanced)')
        ->and($flows[1]['name'])->toBe('Zolder Uit')
        ->and($homey->triggerFlow('f-adv'))->toBeTrue();
});

test('T19: turns off all switched-on light devices when __lights_off__ is bound to Alles Uit', function (): void {
    Setting::setValue('all_off_flow_id', '__lights_off__');

    Http::fake([
        'http://192.168.1.50/api/manager/devices/device' => Http::response([
            'l-1' => ['class' => 'light', 'capabilitiesObj' => ['onoff' => ['value' => true]]],
            'l-2' => ['virtualClass' => 'light', 'capabilitiesObj' => ['onoff' => ['value' => true]]],
            'l-off' => ['class' => 'light', 'capabilitiesObj' => ['onoff' => ['value' => false]]],
            'plug-tv' => ['class' => 'socket', 'capabilitiesObj' => ['onoff' => ['value' => true]]],
        ], 200),
        'http://192.168.1.50/api/manager/devices/device/*/capability/onoff' => Http::response([], 200),
    ]);

    $res = app(HomeyService::class)->triggerAllOff();

    expect($res)->toBe(['ok' => true, 'turnedOff' => 2]);
});

test('T20: testConnection distinguishes auth failure, missing Heimdall switch, and valid connection', function (): void {
    $homey = app(HomeyService::class);

    expect($homey->testConnection('', ''))->toBe([
        'ok' => false,
        'message' => 'Vul zowel URL als API Key in',
    ]);

    Http::fakeSequence()
        ->push([], 401)
        ->push([], 401)
        ->push(['other' => ['driverId' => 'socket']], 200)
        ->push(['other' => ['driverId' => 'socket']], 200)
        ->push(['hd' => ['driverId' => 'surveillancemodeswitch']], 200);

    expect($homey->testConnection('http://192.168.1.50', 'bad-key')['ok'])->toBeFalse()
        ->and($homey->testConnection('http://192.168.1.50', 'valid-no-switch')['message'])->toContain('geen Heimdall switch')
        ->and($homey->testConnection('http://192.168.1.50', 'valid-key'))->toBe([
            'ok' => true,
            'message' => 'Verbonden! Heimdall apparaat gevonden.',
        ]);
});

test('T45: HomeyService trips offline circuit-breaker after ConnectionException to avoid repeated timeouts', function (): void {
    $attempts = 0;

    Http::fake(function () use (&$attempts) {
        $attempts++;
        throw new ConnectionException('Connection timed out');
    });

    $homey = app(HomeyService::class);
    $homey->clearOfflineState();

    // First call attempts connection and trips circuit-breaker
    expect($homey->getAlarmStatus())->toBeNull()
        ->and($attempts)->toBe(1);

    // Subsequent calls skip HTTP immediately without waiting on timeouts
    expect($homey->getOpenSensors())->toBe([])
        ->and($homey->getFlows())->toBe([])
        ->and($homey->getTrashCollections())->toBe([])
        ->and($attempts)->toBe(1);
});
