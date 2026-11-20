<?php

namespace App\Livewire\Kiosk;

use App\Services\HomeyService;
use App\Services\TrashService;
use App\Services\WeatherService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.kiosk')]
#[Title('Start | Heimdall Kiosk')]
class StartScreen extends Component
{
    public bool $isConfigured = true;

    /** @var array{tempLabel: string, feelsLikeLabel: string|null, icon: string|null, conditionLabel: string|null}|null */
    public ?array $outdoorWeather = null;

    /** @var array<int, array<string, mixed>> */
    public array $dashboardItems = [];

    public function mount(): void
    {
        if (request()->query('alarm') === 'true') {
            $this->redirect('/entry?alarm=true');

            return;
        }

        $this->isConfigured = app(HomeyService::class)->isConfigured();
    }

    public function refreshDashboard(): void
    {
        $homey = app(HomeyService::class);
        $weather = app(WeatherService::class);
        $trash = app(TrashService::class);

        $this->isConfigured = $homey->isConfigured();
        if (! $this->isConfigured) {
            $this->outdoorWeather = null;
            $this->dashboardItems = [];

            return;
        }

        $now = now();
        $forecast = $weather->fetchForecast($now);
        $collections = $homey->getTrashCollections();

        $this->outdoorWeather = $weather->getOutdoorTempSummary($forecast['frost']);

        $items = [];
        $frostAlert = $weather->getActiveFrostAlert($forecast['frost'], $now);
        if ($frostAlert !== null) {
            $items[] = $frostAlert;
        }

        $rainAlert = $weather->getActiveRainAlert($forecast['points'], $now, [
            'windowMinutes' => $forecast['windowMinutes'],
        ]);
        if ($rainAlert !== null) {
            $items[] = $rainAlert;
        }

        $trashItems = array_slice($trash->getActiveTrashContainers($collections, $now), 0, 2);
        array_push($items, ...$trashItems);

        $this->dashboardItems = $items;
    }

    public function openHub(): void
    {
        $this->redirect('/hub', navigate: true);
    }

    public function render()
    {
        return view('livewire.kiosk.start-screen');
    }
}
