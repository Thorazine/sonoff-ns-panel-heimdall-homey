<?php

use App\Livewire\Kiosk\CountdownScreen;
use App\Livewire\Kiosk\EntryScreen;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\PinLockoutService;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function (): void {
    app(PinLockoutService::class)->clear();

    Setting::setMany([
        'homey_base_url' => 'http://192.168.1.50',
        'homey_api_key' => 'test-token',
        'countdown_seconds' => 10,
        'alarm_countdown_seconds' => 12,
        'max_failed_attempts' => 3,
        'lockout_seconds' => 60,
    ]);

    User::factory()->panelOnly('1234')->create([
        'name' => 'Hoofd PIN',
    ]);
});

test('T30: EntryScreen verifies valid PIN when arming, authorizes session, and redirects to CountdownScreen', function (): void {
    Livewire::withQueryParams(['mode' => 'armed'])
        ->test(EntryScreen::class)
        ->call('submitPin', '1234')
        ->assertRedirect('/countdown?mode=armed');

    expect(session('kiosk.verified_arm.mode'))->toBe('armed');
});

test('T31: EntryScreen verifies PIN when disarming, sets Homey to disarmed, logs event, and shows GEDEACTIVEERD', function (): void {
    Http::fake([
        'http://192.168.1.50/api/manager/devices/device' => Http::response([
            'dev-hd' => ['driverId' => 'surveillancemodeswitch'],
        ], 200),
        'http://192.168.1.50/api/manager/devices/device/dev-hd/capability/homealarm_state' => Http::response([
            'value' => 'disarmed',
        ], 200),
    ]);

    Livewire::test(EntryScreen::class)
        ->call('submitPin', '1234')
        ->assertSet('disarmed', true)
        ->assertSee('GEDEACTIVEERD')
        ->assertDispatched('kiosk-sound', type: 'disarmed');

    expect(ActivityLog::query()->where('message', 'Alarm status: Uitgeschakeld')->exists())->toBeTrue();
});

test('T32: EntryScreen with ?alarm=true shows urgent alarm countdown and flashing red state until disarmed', function (): void {
    Http::fake([
        'http://192.168.1.50/api/manager/devices/device' => Http::response([
            'dev-hd' => ['driverId' => 'surveillancemodeswitch'],
        ], 200),
        'http://192.168.1.50/api/manager/devices/device/dev-hd/capability/homealarm_state' => Http::response([
            'value' => 'disarmed',
        ], 200),
    ]);

    Livewire::withQueryParams(['alarm' => true])
        ->test(EntryScreen::class)
        ->assertSee('ALARM GAAT AF IN')
        ->assertSee('12')
        ->call('triggerAlarmFlash')
        ->assertSet('isFlashing', true)
        ->assertSee('ALARM GEACTIVEERD')
        ->call('submitPin', '1234')
        ->assertSet('isFlashing', false)
        ->assertSet('disarmed', true);
});

test('T33: EntryScreen logs wrong PIN attempts and activates GEBLOKKEERD lockout after max_failed_attempts', function (): void {
    $component = Livewire::test(EntryScreen::class);

    $component->call('submitPin', '9999')
        ->assertSet('showErrorScreen', true)
        ->assertSet('isLockedOut', false)
        ->assertSee('FOUTIEVE CODE');

    $component->call('submitPin', '8888')
        ->assertSet('isLockedOut', false);

    $component->call('submitPin', '7777')
        ->assertSet('isLockedOut', true)
        ->assertSee('GEBLOKKEERD');

    expect(ActivityLog::query()->where('type', 'danger')->exists())->toBeTrue();
});

test('T34: CountdownScreen blocks unauthorized direct access, arms Homey on completion, and clears auth on cancel', function (): void {
    // Unauthorized direct access -> redirected to /alarm-mode
    Livewire::withQueryParams(['mode' => 'armed'])
        ->test(CountdownScreen::class)
        ->assertRedirect('/alarm-mode');

    Http::fake([
        'http://192.168.1.50/api/manager/devices/device' => Http::response([
            'dev-hd' => ['driverId' => 'surveillancemodeswitch'],
        ], 200),
        'http://192.168.1.50/api/manager/devices/device/dev-hd/capability/homealarm_state' => Http::response([
            'value' => 'partially_armed',
        ], 200),
    ]);

    // Authorized session -> completes arming and consumes token
    session()->put('kiosk.verified_arm', [
        'mode' => 'partially_armed',
        'expires_at' => now()->addMinutes(5)->getTimestamp(),
    ]);

    Livewire::withQueryParams(['mode' => 'partially_armed'])
        ->test(CountdownScreen::class)
        ->assertSee('ALARM SCHAKELT IN...')
        ->call('completeArming')
        ->assertSet('armed', true)
        ->assertSee('INGESCHAKELD');

    expect(session()->has('kiosk.verified_arm'))->toBeFalse()
        ->and(ActivityLog::query()->where('message', 'Alarm status: Gedeeltelijk ingeschakeld')->exists())->toBeTrue();

    // Cancel clears session authorization
    session()->put('kiosk.verified_arm', [
        'mode' => 'armed',
        'expires_at' => now()->addMinutes(5)->getTimestamp(),
    ]);

    Livewire::withQueryParams(['mode' => 'armed'])
        ->test(CountdownScreen::class)
        ->call('cancel')
        ->assertRedirect('/alarm-mode');

    expect(session()->has('kiosk.verified_arm'))->toBeFalse();
});
