<?php

namespace App\Services;

use App\Enums\LogLevel;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Support\Facades\RateLimiter;

class PinLockoutService
{
    protected function key(?string $identifier = null): string
    {
        $id = $identifier ?? (app()->runningInConsole() ? 'console' : (string) request()->ip());

        return 'kiosk-pin-lockout:'.$id;
    }

    public function isLockedOut(?string $identifier = null): bool
    {
        $maxAttempts = Setting::publicSettings()['maxFailedAttempts'];

        return RateLimiter::tooManyAttempts($this->key($identifier), $maxAttempts);
    }

    public function remainingSeconds(?string $identifier = null): int
    {
        if (! $this->isLockedOut($identifier)) {
            return 0;
        }

        return max(1, RateLimiter::availableIn($this->key($identifier)));
    }

    /**
     * @return array{lockedOut: bool, remainingSeconds: int, attempts: int, maxAttempts: int}
     */
    public function recordFailure(?string $identifier = null, string $contextLabel = 'PIN-verificatie'): array
    {
        $settings = Setting::publicSettings();
        $maxAttempts = $settings['maxFailedAttempts'];
        $lockoutSeconds = $settings['lockoutSeconds'];
        $key = $this->key($identifier);

        $attempts = RateLimiter::hit($key, $lockoutSeconds);
        $lockedOut = $attempts >= $maxAttempts;

        if ($lockedOut) {
            ActivityLog::record(
                LogLevel::Danger,
                "Paneel geblokkeerd na {$attempts} foutieve pogingen ({$contextLabel})",
                ['attempts' => $attempts, 'lockout_seconds' => $lockoutSeconds]
            );
        } else {
            ActivityLog::record(
                LogLevel::Warning,
                "Foutieve PIN ingevoerd ({$contextLabel})",
                ['attempts' => $attempts, 'max_attempts' => $maxAttempts]
            );
        }

        return [
            'lockedOut' => $lockedOut,
            'remainingSeconds' => $lockedOut ? max(1, RateLimiter::availableIn($key)) : 0,
            'attempts' => $attempts,
            'maxAttempts' => $maxAttempts,
        ];
    }

    public function clear(?string $identifier = null): void
    {
        RateLimiter::clear($this->key($identifier));
    }
}
