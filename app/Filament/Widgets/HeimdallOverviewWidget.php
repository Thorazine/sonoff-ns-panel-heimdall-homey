<?php

namespace App\Filament\Widgets;

use App\Enums\AlarmMode;
use App\Models\User;
use App\Services\HomeyService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HeimdallOverviewWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $homey = app(HomeyService::class);
        $configured = $homey->isConfigured();

        $statusRaw = $configured ? $homey->getAlarmStatus() : null;
        $alarmEnum = $statusRaw !== null ? AlarmMode::tryFrom($statusRaw) : null;

        $openSensors = $configured ? count($homey->getOpenSensors()) : 0;
        $activePinUsers = User::query()
            ->where('is_active', true)
            ->whereNotNull('pin_hash')
            ->where('pin_hash', '!=', '')
            ->count();
        $activeAdmins = User::query()
            ->where('is_active', true)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereNotNull('password')
            ->where('password', '!=', '')
            ->count();

        return [
            Stat::make(
                'Heimdall Status',
                $alarmEnum?->getLabel() ?? ($configured ? 'Onbekend / Offline' : 'Niet geconfigureerd')
            )
                ->description($configured ? 'Live status vanuit Homey Pro' : 'Stel Homey URL & API Key in')
                ->color($alarmEnum?->getColor() ?? 'gray'),

            Stat::make('Open / Actieve Sensoren', (string) $openSensors)
                ->description($openSensors === 0 ? 'Alle ramen & deuren gesloten' : 'Aandacht vereist')
                ->color($openSensors === 0 ? 'success' : 'danger'),

            Stat::make('Actieve Gebruikers (PIN)', (string) $activePinUsers)
                ->description("Waarvan {$activeAdmins} beheerder(s) op /admin")
                ->color('info'),
        ];
    }
}
