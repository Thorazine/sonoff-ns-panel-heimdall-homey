<?php

namespace Database\Factories;

use App\Models\WallPanel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WallPanel>
 */
class WallPanelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'location' => fake()->word(),
            'show_doorbell_popup' => true,
            'is_active' => true,
        ];
    }
}
