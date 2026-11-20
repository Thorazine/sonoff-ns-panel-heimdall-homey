<?php

namespace App\Filament\Pages;

use App\Enums\LogLevel;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Services\HomeyService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * @property-read Schema $form
 */
class ManageSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Instellingen & Homey';

    protected static ?string $title = 'Instellingen & Homey Koppeling';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.manage-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $public = Setting::publicSettings();

        $this->form->fill([
            'homey_base_url' => (string) Setting::getValue('homey_base_url', ''),
            'homey_api_key' => (string) Setting::getValue('homey_api_key', ''),
            'rain_window_minutes' => $public['rainWindowMinutes'],
            'weather_lat' => Setting::getValue('weather_lat'),
            'weather_lon' => Setting::getValue('weather_lon'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Homey Pro Verbinding')
                    ->description('Lokale API-verbinding met de Homey Pro en de Heimdall Surveillance Mode Switch.')
                    ->columns(2)
                    ->components([
                        TextInput::make('homey_base_url')
                            ->label('Homey Base URL')
                            ->placeholder('http://192.168.1.100')
                            ->url()
                            ->required(),

                        TextInput::make('homey_api_key')
                            ->label('Homey API Key (Bearer Token)')
                            ->password()
                            ->revealable()
                            ->required(),
                    ]),

                Section::make('Startscherm & Weer')
                    ->description('Configureer de Buienradar-vooruitblik en optionele weerlocatie voor het Kiosk-startscherm. Module-specifieke opties (Alarm, Gym, Alles Uit) stel je in onder Modules.')
                    ->columns(3)
                    ->components([
                        TextInput::make('rain_window_minutes')
                            ->label('Paraplu vooruitblik (min)')
                            ->numeric()
                            ->minValue(15)
                            ->maxValue(180)
                            ->required(),

                        TextInput::make('weather_lat')
                            ->label('Breedtegraad (optioneel)')
                            ->placeholder('Automatisch via Homey')
                            ->numeric(),

                        TextInput::make('weather_lon')
                            ->label('Lengtegraad (optioneel)')
                            ->placeholder('Automatisch via Homey')
                            ->numeric(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openModules')
                ->label('Module-opties')
                ->color('gray')
                ->icon(Heroicon::OutlinedCube)
                ->url(fn (): string => ModulesOverview::getUrl()),

            Action::make('testConnection')
                ->label('Test Verbinding')
                ->color('gray')
                ->icon(Heroicon::OutlinedSignal)
                ->action(fn () => $this->testHomeyConnection()),
        ];
    }

    public function testHomeyConnection(): void
    {
        $url = trim((string) ($this->data['homey_base_url'] ?? ''));
        $token = trim((string) ($this->data['homey_api_key'] ?? ''));

        $result = app(HomeyService::class)->testConnection($url, $token);

        $notification = Notification::make()->title($result['message']);
        if ($result['ok']) {
            $notification->success();
        } else {
            $notification->danger();
        }
        $notification->send();
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Setting::setMany([
            'homey_base_url' => rtrim(trim((string) ($state['homey_base_url'] ?? '')), '/'),
            'homey_api_key' => trim((string) ($state['homey_api_key'] ?? '')),
            'rain_window_minutes' => (int) ($state['rain_window_minutes'] ?? 75),
            'weather_lat' => ($state['weather_lat'] ?? '') !== '' && $state['weather_lat'] !== null ? (string) $state['weather_lat'] : null,
            'weather_lon' => ($state['weather_lon'] ?? '') !== '' && $state['weather_lon'] !== null ? (string) $state['weather_lon'] : null,
        ]);

        ActivityLog::record(LogLevel::Info, 'Admin instellingen bijgewerkt');

        Notification::make()
            ->title('Instellingen opgeslagen')
            ->success()
            ->send();
    }
}
