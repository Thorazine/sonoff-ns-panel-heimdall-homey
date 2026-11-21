<?php

namespace Database\Factories;

use App\Enums\NetworkProtocol;
use App\Models\NetworkHost;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<NetworkHost>
 */
class NetworkHostFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slug = Str::slug(fake()->unique()->words(2, true));

        return [
            'name' => ucfirst(str_replace('-', ' ', $slug)),
            'dns_name' => $slug.'.local',
            'ip_address' => fake()->localIpv4(),
            'category' => 'Netwerk',
            'description' => fake()->sentence(5),
            'protocol' => NetworkProtocol::Http,
            'port' => null,
            'path' => null,
            'has_root_link' => true,
            'open_in_new_tab' => true,
            'sort_order' => 1,
            'is_active' => true,
        ];
    }

    public function https(?int $port = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'protocol' => NetworkProtocol::Https,
            'port' => $port,
        ]);
    }

    public function withoutRootLink(): static
    {
        return $this->state(fn (array $attributes): array => [
            'has_root_link' => false,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
