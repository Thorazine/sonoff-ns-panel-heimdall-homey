<?php

namespace Database\Factories;

use App\Enums\CameraRole;
use App\Models\ReolinkCamera;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ReolinkCamera>
 */
class ReolinkCameraFactory extends Factory
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
            'role' => CameraRole::Garden,
            'go2rtc_stream' => Str::slug($name, '_'),
            'stream_url' => null,
            'snapshot_url' => null,
            'supports_two_way_audio' => false,
            'show_on_doorbell' => true,
            'sort_order' => 1,
            'is_active' => true,
        ];
    }

    public function doorbell(): static
    {
        return $this->state(fn (): array => [
            'role' => CameraRole::Doorbell,
            'supports_two_way_audio' => true,
            'show_on_doorbell' => true,
        ]);
    }
}
