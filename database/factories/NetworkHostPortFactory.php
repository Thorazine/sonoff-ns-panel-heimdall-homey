<?php

namespace Database\Factories;

use App\Enums\NetworkProtocol;
use App\Models\NetworkHost;
use App\Models\NetworkHostPort;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NetworkHostPort>
 */
class NetworkHostPortFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'network_host_id' => NetworkHost::factory(),
            'label' => ucfirst(fake()->word()),
            'protocol' => NetworkProtocol::Http,
            'port' => fake()->numberBetween(1024, 65000),
            'path' => null,
            'open_in_new_tab' => true,
            'sort_order' => 1,
            'is_active' => true,
        ];
    }

    public function https(): static
    {
        return $this->state(fn (array $attributes): array => [
            'protocol' => NetworkProtocol::Https,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
