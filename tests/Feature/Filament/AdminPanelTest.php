<?php

use App\Enums\LogLevel;
use App\Filament\Pages\Auth\AdminLogin;
use App\Filament\Pages\Auth\InitialAdminSetup;
use App\Filament\Pages\ManageSettings;
use App\Filament\Pages\SensorsOverview;
use App\Filament\Resources\ActivityLogs\Pages\ManageActivityLogs;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Livewire\Kiosk\HubScreen;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Vite;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->admin = User::factory()->create([
        'name' => 'Heimdall Beheerder',
        'email' => 'admin@heimdall.local',
        'password' => 'heimdall-admin',
        'is_active' => true,
        'is_admin' => true,
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
        ->assertSee('Heimdall Beheer')
        ->assertSee('Gebruikers')
        ->assertDontSee('PIN-codes');
});

test('T36: UserResource lists users and manages both panel-only PIN users (without email) and /admin users (with email)', function (): void {
    $existingResident = User::factory()->panelOnly('1234')->create([
        'name' => 'Bewoner',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$this->admin, $existingResident]);

    // 1. Create a user with ONLY name + PIN (no email or password needed -> alarm only)
    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Schoonmaak',
            'is_active' => true,
            'pin' => '567890',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $cleaner = User::query()->where('name', 'Schoonmaak')->first();
    expect($cleaner)->not->toBeNull()
        ->and($cleaner->email)->toBeNull()
        ->and($cleaner->hasAdminAccess())->toBeFalse()
        ->and(Hash::check('567890', $cleaner->pin_hash))->toBeTrue();

    // 2. Create a user with email + password AND PIN -> automatically gets /admin access
    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Tweede Beheerder',
            'is_active' => true,
            'pin' => '4321',
            'email' => 'beheer2@heimdall.local',
            'password' => 'wachtwoord123',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $secondAdmin = User::query()->where('email', 'beheer2@heimdall.local')->first();
    expect($secondAdmin)->not->toBeNull()
        ->and($secondAdmin->hasAdminAccess())->toBeTrue()
        ->and($secondAdmin->is_admin)->toBeTrue()
        ->and(Hash::check('wachtwoord123', $secondAdmin->password))->toBeTrue()
        ->and(Hash::check('4321', $secondAdmin->pin_hash))->toBeTrue();

    // 3. Edit user name while leaving PIN and password blank preserves existing hashes
    Livewire::test(EditUser::class, ['record' => $secondAdmin->getRouteKey()])
        ->fillForm([
            'name' => 'Tweede Beheerder Bijgewerkt',
            'pin' => '',
            'password' => '',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $secondAdmin->refresh();
    expect($secondAdmin->name)->toBe('Tweede Beheerder Bijgewerkt')
        ->and(Hash::check('wachtwoord123', $secondAdmin->password))->toBeTrue()
        ->and(Hash::check('4321', $secondAdmin->pin_hash))->toBeTrue();

    // 4. Clearing email revokes /admin access while keeping their panel PIN active
    Livewire::test(EditUser::class, ['record' => $secondAdmin->getRouteKey()])
        ->fillForm([
            'email' => '',
            'pin' => '',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $secondAdmin->refresh();
    expect($secondAdmin->email)->toBeNull()
        ->and($secondAdmin->hasAdminAccess())->toBeFalse()
        ->and(Hash::check('4321', $secondAdmin->pin_hash))->toBeTrue();
});

test('T37: UserResource requires PIN when email is empty, requires password when email is given, and protects last PIN/admin', function (): void {
    $onlyPinUser = User::factory()->panelOnly('1234')->create([
        'name' => 'Enige PIN Gebruiker',
    ]);

    $this->actingAs($this->admin);

    // Invalid non-numeric PIN rejected
    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Ongeldig',
            'is_active' => true,
            'pin' => '12ab',
        ])
        ->call('create')
        ->assertHasFormErrors(['pin']);

    // Neither PIN nor email filled -> PIN required
    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Leeg',
            'is_active' => true,
            'pin' => '',
            'email' => '',
        ])
        ->call('create')
        ->assertHasFormErrors(['pin']);

    // Email filled without password -> password required
    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Zonder Wachtwoord',
            'is_active' => true,
            'email' => 'nieuw@heimdall.local',
            'password' => '',
        ])
        ->call('create')
        ->assertHasFormErrors(['password']);

    // Last active PIN user cannot be deleted or deactivated
    expect(fn () => $onlyPinUser->delete())->toThrow(ValidationException::class);
    expect(fn () => $onlyPinUser->update(['is_active' => false]))->toThrow(ValidationException::class);

    // Last active admin user cannot be deleted, deactivated, or have email cleared
    expect(fn () => $this->admin->delete())->toThrow(ValidationException::class);
    expect(fn () => $this->admin->update(['is_active' => false]))->toThrow(ValidationException::class);
    expect(fn () => $this->admin->update(['email' => null]))->toThrow(ValidationException::class);
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

test('T43: AdminLogin (/admin/login) authenticates only active users with email and password', function (): void {
    User::factory()->panelOnly('1111')->create([
        'name' => 'Alleen PIN Gebruiker',
    ]);

    User::factory()->create([
        'name' => 'Inactieve Admin',
        'email' => 'inactief@heimdall.local',
        'password' => 'wachtwoord123',
        'is_active' => false,
    ]);

    // Wrong password rejected
    Livewire::test(AdminLogin::class)
        ->fillForm([
            'email' => 'admin@heimdall.local',
            'password' => 'fout-wachtwoord',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

    expect(auth()->check())->toBeFalse();

    // Inactive admin user rejected
    Livewire::test(AdminLogin::class)
        ->fillForm([
            'email' => 'inactief@heimdall.local',
            'password' => 'wachtwoord123',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

    expect(auth()->check())->toBeFalse();

    // Active user with email + password succeeds
    Livewire::test(AdminLogin::class)
        ->fillForm([
            'email' => 'admin@heimdall.local',
            'password' => 'heimdall-admin',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect('/admin');

    expect(auth()->check())->toBeTrue()
        ->and(auth()->user()->email)->toBe('admin@heimdall.local');
});

test('T44: Kiosk Hub has no Settings/Admin link, AdminLogin throttles failed attempts, and /entry enforces route throttling', function (): void {
    Setting::setMany([
        'max_failed_attempts' => 3,
        'lockout_seconds' => 60,
    ]);

    // HubScreen has no Instellingen button or /admin link
    Livewire::test(HubScreen::class)
        ->assertDontSee('INSTELLINGEN')
        ->assertDontSeeHtml('href="/admin"');

    // AdminLogin throttles after max_failed_attempts (3) and blocks even valid credentials while locked out
    for ($i = 0; $i < 3; $i++) {
        Livewire::test(AdminLogin::class)
            ->fillForm([
                'email' => 'admin@heimdall.local',
                'password' => 'verkeerd',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);
    }

    Livewire::test(AdminLogin::class)
        ->fillForm([
            'email' => 'admin@heimdall.local',
            'password' => 'heimdall-admin',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

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

    // 1. Login via email + password and verify /admin access works
    Livewire::test(AdminLogin::class)
        ->fillForm([
            'email' => 'admin@heimdall.local',
            'password' => 'heimdall-admin',
        ])
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

    // 3. Log in again and test 60-second server-side idle timeout
    Livewire::test(AdminLogin::class)
        ->fillForm([
            'email' => 'admin@heimdall.local',
            'password' => 'heimdall-admin',
        ])
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

test('T47: Fresh start redirects /admin/login to /admin/setup, creates first admin with optional PIN, and locks /admin/setup afterwards', function (): void {
    // When an active admin already exists, /admin/setup is inaccessible and redirects to /admin/login
    $this->get('/admin/setup')
        ->assertRedirect('/admin/login');

    // Simulate a fresh empty database without any users
    DB::table('users')->delete();
    expect(User::needsInitialAdminSetup())->toBeTrue();

    // Visiting /admin/login on a fresh start automatically redirects to /admin/setup
    $this->get('/admin/login')
        ->assertRedirect('/admin/setup');

    $this->get('/admin/setup')
        ->assertOk()
        ->assertSee('Welkom bij Heimdall Beheer');

    // Validation catches mismatched password confirmation and invalid PIN format
    Livewire::test(InitialAdminSetup::class)
        ->fillForm([
            'name' => 'Matthijs',
            'email' => 'matthijs@voorbeeld.nl',
            'password' => 'wachtwoord123',
            'passwordConfirmation' => 'anders123',
            'pin' => '12ab',
        ])
        ->call('register')
        ->assertHasFormErrors(['password', 'pin']);

    expect(User::query()->count())->toBe(0);

    // Valid submission creates the first admin + optional panel PIN and logs in immediately
    Livewire::test(InitialAdminSetup::class)
        ->fillForm([
            'name' => 'Matthijs',
            'email' => 'matthijs@voorbeeld.nl',
            'password' => 'wachtwoord123',
            'passwordConfirmation' => 'wachtwoord123',
            'pin' => '1357',
        ])
        ->call('register')
        ->assertHasNoFormErrors()
        ->assertRedirect('/admin');

    expect(auth()->check())->toBeTrue();

    $created = User::query()->where('email', 'matthijs@voorbeeld.nl')->first();
    expect($created)->not->toBeNull()
        ->and($created->is_active)->toBeTrue()
        ->and($created->is_admin)->toBeTrue()
        ->and(Hash::check('wachtwoord123', $created->password))->toBeTrue()
        ->and(User::verifyPin('1357')?->id)->toBe($created->id)
        ->and(User::needsInitialAdminSetup())->toBeFalse();

    // Once logged out, /admin/setup is permanently locked and redirects back to /admin/login
    auth()->logout();

    $this->get('/admin/setup')
        ->assertRedirect('/admin/login');

    Livewire::test(InitialAdminSetup::class)
        ->assertRedirect('/admin/login');

    expect(User::query()->count())->toBe(1);
});
