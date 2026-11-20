<?php

namespace Database\Factories;

use App\Models\KioskMenu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KioskMenu>
 */
class KioskMenuFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->words(2, true),
            'is_root' => false,
        ];
    }

    public function root(): static
    {
        return $this->state(fn (): array => [
            'title' => 'Hoofdmenu',
            'is_root' => true,
        ]);
    }
}
