<?php

namespace App\Livewire;

use App\Models\NetworkHost;
use App\Models\NetworkHostPort;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.network')]
#[Title('Netwerk & DNS Menu | Heimdall')]
class NetworkMenuScreen extends Component
{
    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'categorie', except: '')]
    public string $category = '';

    public function selectCategory(string $selectedCategory): void
    {
        $clean = trim($selectedCategory);
        $this->category = $this->category === $clean ? '' : $clean;
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->category = '';
    }

    protected function normalizeCategory(?string $rawCategory): string
    {
        $trimmed = trim((string) $rawCategory);

        return $trimmed !== '' ? $trimmed : 'Algemeen';
    }

    protected function hostMatchesSearch(NetworkHost $host, string $needle, string $portNeedle): bool
    {
        if ($needle === '') {
            return true;
        }

        $haystacks = [
            $host->name,
            $host->dns_name,
            $host->ip_address,
            $host->category,
            $host->description,
            $host->displayAddress(),
            $host->port !== null ? (string) $host->port : null,
        ];

        foreach ($haystacks as $field) {
            if ($field !== null && str_contains(mb_strtolower($field), $needle)) {
                return true;
            }
        }

        foreach ($host->activePorts as $portItem) {
            if ($this->portMatchesSearch($portItem, $needle, $portNeedle)) {
                return true;
            }
        }

        return false;
    }

    public function portMatchesSearch(NetworkHostPort $portItem, string $needle, string $portNeedle): bool
    {
        if ($needle === '') {
            return false;
        }

        if (str_contains(mb_strtolower($portItem->label), $needle)) {
            return true;
        }

        if ($portItem->path !== null && str_contains(mb_strtolower($portItem->path), $needle)) {
            return true;
        }

        if ($portNeedle !== '' && str_contains((string) $portItem->port, $portNeedle)) {
            return true;
        }

        return str_contains(mb_strtolower($portItem->displayPortTarget()), $needle);
    }

    public function render(): View
    {
        /** @var Collection<int, NetworkHost> $allHosts */
        $allHosts = NetworkHost::query()
            ->where('is_active', true)
            ->with('activePorts')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        $totalHostCount = $allHosts->count();
        $totalPortCount = $allHosts->sum(fn (NetworkHost $host): int => $host->activePorts->count());

        /** @var array<string, int> $categories */
        $categories = [];
        foreach ($allHosts as $host) {
            $catName = $this->normalizeCategory($host->category);
            $categories[$catName] = ($categories[$catName] ?? 0) + 1;
        }

        $needle = mb_strtolower(trim($this->search));
        $portNeedle = ltrim($needle, ':');
        $selectedCategory = trim($this->category);

        $filteredHosts = $allHosts->filter(function (NetworkHost $host) use ($needle, $portNeedle, $selectedCategory): bool {
            if ($selectedCategory !== '' && $this->normalizeCategory($host->category) !== $selectedCategory) {
                return false;
            }

            return $this->hostMatchesSearch($host, $needle, $portNeedle);
        })->values();

        /** @var array<string, array<int, NetworkHost>> $groupedHosts */
        $groupedHosts = [];
        foreach ($filteredHosts as $host) {
            $catName = $this->normalizeCategory($host->category);
            $groupedHosts[$catName][] = $host;
        }

        return view('livewire.network-menu-screen', [
            'totalHostCount' => $totalHostCount,
            'totalPortCount' => $totalPortCount,
            'filteredHostCount' => $filteredHosts->count(),
            'categories' => $categories,
            'groupedHosts' => $groupedHosts,
            'needle' => $needle,
            'portNeedle' => $portNeedle,
            'hasActiveFilters' => $needle !== '' || $selectedCategory !== '',
        ]);
    }
}
