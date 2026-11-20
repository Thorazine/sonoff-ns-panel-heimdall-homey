<?php

namespace App\Filament\Pages;

use App\Services\HomeyService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class SensorsOverview extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Sensoren';

    protected static ?string $title = 'Homey Sensoren Overzicht';

    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.pages.sensors-overview';

    /** @var array<int, array{id: string, name: string, zone: string, type: string, active: bool, battery: int|null}> */
    public array $sensors = [];

    public bool $isConfigured = false;

    public bool $sensorsLoaded = false;

    public function mount(): void
    {
        $this->isConfigured = app(HomeyService::class)->isConfigured();
    }

    public function loadSensors(): void
    {
        $homey = app(HomeyService::class);
        $this->isConfigured = $homey->isConfigured();
        $this->sensors = $this->isConfigured ? $homey->getAllSensors() : [];
        $this->sensorsLoaded = true;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refreshSensors')
                ->label('Ververs Sensoren')
                ->icon(Heroicon::OutlinedArrowPath)
                ->action(function (): void {
                    $this->loadSensors();

                    Notification::make()
                        ->title('Sensoren bijgewerkt ('.count($this->sensors).' gevonden)')
                        ->success()
                        ->send();
                }),
        ];
    }
}
