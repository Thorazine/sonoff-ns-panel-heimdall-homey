<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'is_active' => true,
            'is_admin' => true,
            'pin_hash' => null,
            'pin_last_used_at' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Attach a hashed panel PIN code to the user.
     */
    public function withPin(string $pin = '1234'): static
    {
        return $this->state(fn (array $attributes): array => [
            'pin_hash' => Hash::make($pin),
        ]);
    }

    /**
     * Configure a panel-only user without /admin login access.
     */
    public function panelOnly(string $pin = '1234'): static
    {
        return $this->state(fn (array $attributes): array => [
            'email' => null,
            'email_verified_at' => null,
            'password' => null,
            'is_admin' => false,
            'pin_hash' => Hash::make($pin),
            'remember_token' => null,
        ]);
    }

    /**
     * Configure an inactive user.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
