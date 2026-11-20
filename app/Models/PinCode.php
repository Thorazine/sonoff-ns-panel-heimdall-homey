<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

#[Fillable(['name', 'pin', 'pin_hash', 'is_active', 'is_admin', 'last_used_at'])]
#[Hidden(['pin_hash'])]
class PinCode extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::updating(function (self $pinCode): void {
            if ($pinCode->isDirty('is_active') && ! $pinCode->is_active && $pinCode->getOriginal('is_active')) {
                $otherActiveCount = static::query()
                    ->whereKeyNot($pinCode->getKey())
                    ->where('is_active', true)
                    ->count();

                if ($otherActiveCount === 0) {
                    throw ValidationException::withMessages([
                        'is_active' => 'Er moet minimaal één actieve PIN-code overblijven.',
                    ]);
                }
            }

            $wasActiveAdmin = (bool) $pinCode->getOriginal('is_active') && (bool) $pinCode->getOriginal('is_admin');
            $willBeActiveAdmin = (bool) $pinCode->is_active && (bool) $pinCode->is_admin;

            if ($wasActiveAdmin && ! $willBeActiveAdmin) {
                $otherActiveAdmins = static::query()
                    ->whereKeyNot($pinCode->getKey())
                    ->where('is_active', true)
                    ->where('is_admin', true)
                    ->count();

                if ($otherActiveAdmins === 0) {
                    throw ValidationException::withMessages([
                        'is_admin' => 'Er moet minimaal één actieve beheerder-PIN overblijven.',
                    ]);
                }
            }
        });

        static::deleting(function (self $pinCode): void {
            $isActive = (bool) ($pinCode->getOriginal('is_active') ?? $pinCode->is_active);
            $isAdmin = (bool) ($pinCode->getOriginal('is_admin') ?? $pinCode->is_admin);

            if ($isActive) {
                $otherActiveCount = static::query()
                    ->whereKeyNot($pinCode->getKey())
                    ->where('is_active', true)
                    ->count();

                if ($otherActiveCount === 0) {
                    throw ValidationException::withMessages([
                        'pin' => 'De laatste actieve PIN-code kan niet worden verwijderd.',
                    ]);
                }
            }

            if ($isActive && $isAdmin) {
                $otherActiveAdmins = static::query()
                    ->whereKeyNot($pinCode->getKey())
                    ->where('is_active', true)
                    ->where('is_admin', true)
                    ->count();

                if ($otherActiveAdmins === 0) {
                    throw ValidationException::withMessages([
                        'is_admin' => 'De laatste actieve beheerder-PIN kan niet worden verwijderd.',
                    ]);
                }
            }
        });
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

    public static function verify(string $plainPin): ?self
    {
        $trimmed = trim($plainPin);
        if ($trimmed === '' || ! preg_match('/^\d{4,6}$/', $trimmed)) {
            return null;
        }

        $activePins = static::query()->where('is_active', true)->get();

        foreach ($activePins as $pinCode) {
            if (Hash::check($trimmed, $pinCode->pin_hash)) {
                $pinCode->forceFill(['last_used_at' => now()])->saveQuietly();

                return $pinCode;
            }
        }

        return null;
    }

    public static function verifyAdmin(string $plainPin): ?self
    {
        $pinCode = static::verify($plainPin);

        if ($pinCode === null || ! $pinCode->is_admin) {
            return null;
        }

        return $pinCode;
    }

    public function resolveAdminUser(): User
    {
        return User::query()->updateOrCreate(
            ['email' => "pin-{$this->id}@heimdall.local"],
            [
                'name' => $this->name,
                'password' => Str::random(32),
            ]
        );
    }

    public static function seedDefaultIfEmpty(string $defaultPin = '1234'): void
    {
        if (static::query()->exists()) {
            return;
        }

        $clean = preg_replace('/\D/', '', $defaultPin) ?? '';
        if ($clean !== '' && strlen($clean) >= 4 && strlen($clean) <= 6) {
            static::query()->create([
                'name' => 'Standaard PIN',
                'pin' => $clean,
                'is_active' => true,
                'is_admin' => true,
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_admin' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }
}
