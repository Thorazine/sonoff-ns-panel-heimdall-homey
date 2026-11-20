<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

#[Fillable(['name', 'email', 'password', 'is_active', 'is_admin', 'pin', 'pin_hash', 'pin_last_used_at'])]
#[Hidden(['password', 'remember_token', 'pin_hash'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            if (! filled($user->email)) {
                $user->email = null;
                $user->password = null;
                $user->is_admin = false;
            } else {
                $user->is_admin = filled($user->password);
            }
        });

        static::updating(function (self $user): void {
            $wasActiveAdmin = (bool) $user->getOriginal('is_active')
                && filled($user->getOriginal('email'))
                && filled($user->getOriginal('password'));

            $willBeActiveAdmin = (bool) $user->is_active
                && filled($user->email)
                && filled($user->password);

            if ($wasActiveAdmin && ! $willBeActiveAdmin) {
                $otherActiveAdmins = static::query()
                    ->whereKeyNot($user->getKey())
                    ->where('is_active', true)
                    ->whereNotNull('email')
                    ->where('email', '!=', '')
                    ->whereNotNull('password')
                    ->where('password', '!=', '')
                    ->exists();

                if (! $otherActiveAdmins) {
                    $errorKey = ! $user->is_active ? 'is_active' : 'email';

                    throw ValidationException::withMessages([
                        $errorKey => 'Er moet minimaal één actieve beheerder (gebruiker met e-mailadres) overblijven.',
                    ]);
                }
            }

            $wasActivePin = (bool) $user->getOriginal('is_active') && filled($user->getOriginal('pin_hash'));
            $willBeActivePin = (bool) $user->is_active && filled($user->pin_hash);

            if ($wasActivePin && ! $willBeActivePin) {
                $otherActivePins = static::query()
                    ->whereKeyNot($user->getKey())
                    ->where('is_active', true)
                    ->whereNotNull('pin_hash')
                    ->where('pin_hash', '!=', '')
                    ->exists();

                if (! $otherActivePins) {
                    $errorKey = ! $user->is_active ? 'is_active' : 'pin';

                    throw ValidationException::withMessages([
                        $errorKey => 'Er moet minimaal één actieve gebruiker met een PIN-code overblijven.',
                    ]);
                }
            }
        });

        static::deleting(function (self $user): void {
            $wasActiveAdmin = (bool) ($user->getOriginal('is_active') ?? $user->is_active)
                && filled($user->getOriginal('email') ?? $user->email)
                && filled($user->getOriginal('password') ?? $user->password);

            if ($wasActiveAdmin) {
                $otherActiveAdmins = static::query()
                    ->whereKeyNot($user->getKey())
                    ->where('is_active', true)
                    ->whereNotNull('email')
                    ->where('email', '!=', '')
                    ->whereNotNull('password')
                    ->where('password', '!=', '')
                    ->exists();

                if (! $otherActiveAdmins) {
                    throw ValidationException::withMessages([
                        'email' => 'De laatste actieve beheerder kan niet worden verwijderd.',
                    ]);
                }
            }

            $wasActivePin = (bool) ($user->getOriginal('is_active') ?? $user->is_active)
                && filled($user->getOriginal('pin_hash') ?? $user->pin_hash);

            if ($wasActivePin) {
                $otherActivePins = static::query()
                    ->whereKeyNot($user->getKey())
                    ->where('is_active', true)
                    ->whereNotNull('pin_hash')
                    ->where('pin_hash', '!=', '')
                    ->exists();

                if (! $otherActivePins) {
                    throw ValidationException::withMessages([
                        'pin' => 'De laatste actieve gebruiker met een PIN-code kan niet worden verwijderd.',
                    ]);
                }
            }
        });
    }

    public function hasAdminAccess(): bool
    {
        return filled($this->email) && filled($this->password);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_active && $this->hasAdminAccess();
    }

    public function hasPin(): bool
    {
        return filled($this->pin_hash);
    }

    public static function hasActiveAdmin(): bool
    {
        return static::query()
            ->where('is_active', true)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereNotNull('password')
            ->where('password', '!=', '')
            ->exists();
    }

    public static function needsInitialAdminSetup(): bool
    {
        return ! static::hasActiveAdmin();
    }

    public function isLastActiveAdmin(): bool
    {
        if (! $this->is_active || ! $this->hasAdminAccess()) {
            return false;
        }

        return ! static::query()
            ->whereKeyNot($this->getKey())
            ->where('is_active', true)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereNotNull('password')
            ->where('password', '!=', '')
            ->exists();
    }

    public function isLastActivePin(): bool
    {
        if (! $this->is_active || ! $this->hasPin()) {
            return false;
        }

        return ! static::query()
            ->whereKeyNot($this->getKey())
            ->where('is_active', true)
            ->whereNotNull('pin_hash')
            ->where('pin_hash', '!=', '')
            ->exists();
    }

    /**
     * Normalize empty email strings to null so multiple panel-only users can coexist.
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => ($value !== null && trim($value) !== '')
                ? strtolower(trim($value))
                : null,
        );
    }

    /**
     * Virtual write-only attribute that hashes plain digits into pin_hash.
     */
    protected function pin(): Attribute
    {
        return Attribute::make(
            set: function (?string $value): array {
                if ($value === null || $value === '') {
                    return [];
                }

                return ['pin_hash' => Hash::make($value)];
            }
        );
    }

    public static function verifyPin(string $plainPin): ?self
    {
        $trimmed = trim($plainPin);
        if ($trimmed === '' || ! preg_match('/^\d{4,6}$/', $trimmed)) {
            return null;
        }

        $activeUsers = static::query()
            ->where('is_active', true)
            ->whereNotNull('pin_hash')
            ->where('pin_hash', '!=', '')
            ->get();

        foreach ($activeUsers as $user) {
            if (Hash::check($trimmed, $user->pin_hash)) {
                $user->forceFill(['pin_last_used_at' => now()])->saveQuietly();

                return $user;
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_admin' => 'boolean',
            'pin_last_used_at' => 'datetime',
        ];
    }
}
