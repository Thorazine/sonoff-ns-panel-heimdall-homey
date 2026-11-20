<?php

use App\Enums\LogLevel;
use App\Filament\Pages\Auth\PinLogin;
use App\Filament\Pages\ManageSettings;
use App\Filament\Pages\SensorsOverview;
use App\Filament\Resources\ActivityLogs\Pages\ManageActivityLogs;
use App\Filament\Resources\PinCodes\Pages\CreatePinCode;
use App\Filament\Resources\PinCodes\Pages\ListPinCodes;
use App\Livewire\Kiosk\HubScreen;
use App\Models\ActivityLog;
use App\Models\PinCode;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Vite;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->admin = User::factory()->create([
        'name' => 'Heimdall Beheerder',
        'email' => 'admin@heimdall.local',
    ]);

    Setting::setMany([
        'homey_base_url' => 'http://192.168.1.50',
        'homey_api_key' => 'test-token',
    ]);
});

test('T35: unauthenticated guests are redirected to /admin/login and authenticated admins access Filament dashboard', function (): void {
    $this->get('/admin')
        ->assertRedirect('/admin/login');

    Http::fake([
        'http://192.168.1.50/api/manager/devices/device' => Http::response([
            'dev-hd' => ['driverId' => 'surveillancemodeswitch'],
        ], 200),
        'http://192.168.1.50/api/manager/devices/device/dev-hd' => Http::response([
            'capabilitiesObj' => ['homealarm_state' => ['value' => 'disarmed']],
        ], 200),
        'http://192.168.1.50/api/manager/zones/zone' => Http::response([], 200),
    ]);

    $this->actingAs($this->admin)
        ->get('/admin')
        ->assertOk()
        ->assertSee('Heimdall Beheer');
});

test('T36: PinCodeResource lists PIN codes and creates a new bcrypt-hashed PIN code', function (): void {
    $existing = PinCode::query()->create([
        'name' => 'Standaard PIN',
        'pin' => '1234',
        'is_active' => true,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(ListPinCodes::class)
        ->assertCanSeeTableRecords([$existing]);

    Livewire::test(CreatePinCode::class)
        ->fillForm([
            'name' => 'Schoonmaak',
            'pin' => '567890',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $created = PinCode::query()->where('name', 'Schoonmaak')->first();
    expect($created)->not->toBeNull()
        ->and(Hash::check('567890', $created->pin_hash))->toBeTrue();
});

test('T37: PinCodeResource rejects invalid non-numeric PINs and prevents deleting or deactivating the last active PIN', function (): void {
    $onlyPin = PinCode::query()->create([
        'name' => 'Enige PIN',
        'pin' => '1234',
        'is_active' => true,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(CreatePinCode::class)
        ->fillForm([
            'name' => 'Ongeldig',
            'pin' => '12ab',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['pin']);

    expect(fn () => $onlyPin->delete())->toThrow(ValidationException::class);
    expect(fn () => $onlyPin->update(['is_active' => false]))->toThrow(ValidationException::class);
});

test('T38: ManageSettings Filament page loads and saves Homey URL, API key, and weather settings', function (): void {
    $this->actingAs($this->admin);

    Livewire::test(ManageSettings::class)
        ->fillForm([
            'homey_base_url' => 'http://192.168.1.99',
            'homey_api_key' => 'new-secret-key',
            'rain_window_minutes' => 60,
            'weather_lat' => '52.1234',
            'weather_lon' => '5.1234',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified('Instellingen opgeslagen');

    expect(Setting::getValue('homey_base_url'))->toBe('http://192.168.1.99')
        ->and(Setting::getValue('homey_api_key'))->toBe('new-secret-key')
        ->and(Setting::publicSettings()['rainWindowMinutes'])->toBe(60)
        ->and(Setting::getValue('weather_lat'))->toBe('52.1234');
});

test('T39: ManageSettings Test Verbinding header action tests Homey connection and sends notification', function (): void {
    Http::fake([
        'http://192.168.1.50/api/manager/flow/*' => Http::response([], 200),
        'http://192.168.1.50/api/manager/devices/device' => Http::response([
            'dev-hd' => ['driverId' => 'surveillancemodeswitch'],
        ], 200),
    ]);

    $this->actingAs($this->admin);

    Livewire::test(ManageSettings::class)
        ->callAction('testConnection')
        ->assertNotified('Verbonden! Heimdall apparaat gevonden.');
});

test('T40: SensorsOverview Filament page renders live Homey contact and motion sensors with status and battery', function (): void {
    Http::fake([
        'http://192.168.1.50/api/manager/devices/device' => Http::response([
            's-1' => [
                'name' => 'Garagedeur',
                'zone' => 'z-g',
                'capabilitiesObj' => [
                    'alarm_contact' => ['value' => true],
                    'measure_battery' => ['value' => 81],
                ],
            ],
        ], 200),
        'http://192.168.1.50/api/manager/zones/zone' => Http::response([
            'z-g' => ['name' => 'Garage'],
        ], 200),
    ]);

    $this->actingAs($this->admin);

    Livewire::test(SensorsOverview::class)
        ->call('loadSensors')
        ->assertSee('Garagedeur')
        ->assertSee('Garage')
        ->assertSee('81%')
        ->assertSee('OPEN')
        ->callAction('refreshSensors')
        ->assertNotified();
});

test('T41: ActivityLogResource lists logs, filters by LogLevel, and clears logs via header action', function (): void {
    $infoLog = ActivityLog::record(LogLevel::Info, 'Systeem gestart');
    $dangerLog = ActivityLog::record(LogLevel::Danger, 'Foutieve inlogpoging');

    $this->actingAs($this->admin);

    Livewire::test(ManageActivityLogs::class)
        ->assertCanSeeTableRecords([$infoLog, $dangerLog])
        ->filterTable('type', LogLevel::Danger->value)
        ->assertCanSeeTableRecords([$dangerLog])
        ->assertCanNotSeeTableRecords([$infoLog])
        ->resetTableFilters()
        ->callAction('clearLogs');

    expect(ActivityLog::query()->count())->toBe(0);
});

test('T42: Vite manifest exists and all Kiosk HTTP routes render compiled assets with HTTP 200', function (): void {
    Vite::useHotFile(storage_path('framework/testing/vite.hot'));

    Http::fake([
        '*' => Http::response([], 200),
    ]);

    expect(file_exists(public_path('build/manifest.json')))->toBeTrue();

    foreach (['/', '/hub', '/gym', '/all-off', '/menu', '/entry'] as $uri) {
        $this->get($uri)
            ->assertOk()
            ->assertSeeHtml('/build/assets/');
    }
});

test('T43: PinLogin (/admin/login) authenticates only PIN codes with is_admin enabled', function (): void {
    PinCode::query()->create([
        'name' => 'Gast PIN',
        'pin' => '1111',
        'is_active' => true,
        'is_admin' => false,
    ]);

    PinCode::query()->create([
        'name' => 'Beheerder PIN',
        'pin' => '2222',
        'is_active' => true,
        'is_admin' => true,
    ]);

    Livewire::test(PinLogin::class)
        ->fillForm(['pin' => '9999'])
        ->call('authenticate')
        ->assertHasFormErrors(['pin']);

    expect(auth()->check())->toBeFalse();

    Livewire::test(PinLogin::class)
        ->fillForm(['pin' => '1111'])
        ->call('authenticate')
        ->assertHasFormErrors(['pin']);

    expect(auth()->check())->toBeFalse();

    Livewire::test(PinLogin::class)
        ->fillForm(['pin' => '2222'])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect('/admin');

    expect(auth()->check())->toBeTrue()
        ->and(auth()->user()->name)->toBe('Beheerder PIN');
});

test('T44: Kiosk Hub has no Settings/Admin link, last admin PIN is protected, and PIN entry pages enforce throttling', function (): void {
    Setting::setMany([
        'max_failed_attempts' => 3,
        'lockout_seconds' => 60,
    ]);

    $guestPin = PinCode::query()->create([
        'name' => 'Schoonmaak',
        'pin' => '3333',
        'is_active' => true,
        'is_admin' => false,
    ]);

    $adminPin = PinCode::query()->create([
        'name' => 'Hoofdbeheerder',
        'pin' => '4444',
        'is_active' => true,
        'is_admin' => true,
    ]);

    // Cannot uncheck is_admin or delete the last active admin PIN
    expect(fn () => $adminPin->update(['is_admin' => false]))->toThrow(ValidationException::class);
    expect(fn () => $adminPin->delete())->toThrow(ValidationException::class);
    expect($guestPin->is_admin)->toBeFalse();

    // HubScreen has no Instellingen button or /admin link
    Livewire::test(HubScreen::class)
        ->assertDontSee('INSTELLINGEN')
        ->assertDontSeeHtml('href="/admin"');

    // PinLogin throttles after max_failed_attempts (3) and blocks even valid PIN while locked out
    for ($i = 0; $i < 3; $i++) {
        Livewire::test(PinLogin::class)
            ->fillForm(['pin' => '9999'])
            ->call('authenticate')
            ->assertHasFormErrors(['pin']);
    }

    Livewire::test(PinLogin::class)
        ->fillForm(['pin' => '4444'])
        ->call('authenticate')
        ->assertHasFormErrors(['pin']);

    expect(auth()->check())->toBeFalse();

    // HTTP route throttle on /entry returns 429 Too Many Requests when flooded (> 30/min)
    for ($i = 0; $i < 30; $i++) {
        $this->get('/entry')->assertOk();
    }
    $this->get('/entry')->assertTooManyRequests();
});

test('T46: Admin session logs out immediately when returning to Kiosk and expires after 60 seconds of inactivity', function (): void {
    Http::fake([
        '*' => Http::response([], 200),
    ]);

    $adminPin = PinCode::query()->create([
        'name' => 'Deur Admin',
        'pin' => '5555',
        'is_active' => true,
        'is_admin' => true,
    ]);

    // 1. Login via PIN and verify /admin access works
    Livewire::test(PinLogin::class)
        ->fillForm(['pin' => '5555'])
        ->call('authenticate')
        ->assertRedirect('/admin');

    expect(auth()->check())->toBeTrue();

    $this->get('/admin')
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store, private');

    // 2. Returning to Kiosk ('/') immediately logs out the admin session
    $this->get('/')->assertOk();

    expect(auth()->check())->toBeFalse();

    $this->get('/admin')->assertRedirect('/admin/login');

    // 3. Log in again via PIN and test 60-second server-side idle timeout
    Livewire::test(PinLogin::class)
        ->fillForm(['pin' => '5555'])
        ->call('authenticate')
        ->assertRedirect('/admin');

    $this->get('/admin')->assertOk();

    // Activity at 30 seconds keeps session alive
    $this->travel(30)->seconds();
    $this->get('/admin')->assertOk();
    expect(auth()->check())->toBeTrue();

    // 61 seconds without activity logs out and redirects to /admin/login
    $this->travel(61)->seconds();
    $this->get('/admin')->assertRedirect('/admin/login');
    expect(auth()->check())->toBeFalse();
});
