<?php

use App\Models\Setting;
use App\Models\User;
use App\Services\PinLockoutService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

test('T21: stores user PIN codes hashed with bcrypt, ignores inactive users, and updates pin_last_used_at', function (): void {
    $active = User::factory()->panelOnly('2468')->create([
        'name' => 'Bewoner',
    ]);

    User::factory()->panelOnly('9999')->inactive()->create([
        'name' => 'Uitgeschakelde Gast',
    ]);

    expect($active->pin_hash)->not->toBe('2468')
        ->and(Hash::check('2468', $active->pin_hash))->toBeTrue()
        ->and(User::verifyPin('9999'))->toBeNull()
        ->and(User::verifyPin('0000'))->toBeNull();

    $matched = User::verifyPin('2468');
    expect($matched)->not->toBeNull()
        ->and($matched->id)->toBe($active->id)
        ->and($matched->fresh()->pin_last_used_at)->not->toBeNull();
});

test('T22: encrypts homey_api_key at rest in MySQL and invalidates cache on update', function (): void {
    Cache::put(Setting::WEATHER_CACHE_KEY, ['points' => []], 3600);

    Setting::setValue('homey_api_key', 'super-secret-bearer-token');
    Setting::setValue('countdown_seconds', 25);

    $rawDbValue = DB::table('settings')->where('key', 'homey_api_key')->value('value');

    expect($rawDbValue)->not->toBe('super-secret-bearer-token')
        ->and(Setting::getValue('homey_api_key'))->toBe('super-secret-bearer-token')
        ->and(Setting::publicSettings()['countdownSeconds'])->toBe(25)
        ->and(Cache::has(Setting::WEATHER_CACHE_KEY))->toBeFalse();

    Setting::setValue('countdown_seconds', 40);
    expect(Setting::publicSettings()['countdownSeconds'])->toBe(40);

    // Clearing a database setting persists as empty in DB without any .env/config fallback
    Setting::setValue('homey_base_url', 'http://192.168.1.50');
    expect(Setting::getValue('homey_base_url'))->toBe('http://192.168.1.50');

    Setting::setValue('homey_base_url', '');
    expect(Setting::getValue('homey_base_url'))->toBe('');
});

test('T23: enforces server-side PIN rate-limiting lockout and clears upon valid verification', function (): void {
    Setting::setMany([
        'max_failed_attempts' => 3,
        'lockout_seconds' => 120,
    ]);

    $lockout = app(PinLockoutService::class);
    $lockout->clear('panel-1');

    expect($lockout->isLockedOut('panel-1'))->toBeFalse();

    $r1 = $lockout->recordFailure('panel-1');
    $r2 = $lockout->recordFailure('panel-1');
    expect($r1['lockedOut'])->toBeFalse()
        ->and($r2['lockedOut'])->toBeFalse()
        ->and($lockout->isLockedOut('panel-1'))->toBeFalse();

    $r3 = $lockout->recordFailure('panel-1');
    expect($r3['lockedOut'])->toBeTrue()
        ->and($lockout->isLockedOut('panel-1'))->toBeTrue()
        ->and($lockout->remainingSeconds('panel-1'))->toBeGreaterThan(0);

    $lockout->clear('panel-1');
    expect($lockout->isLockedOut('panel-1'))->toBeFalse();
});
