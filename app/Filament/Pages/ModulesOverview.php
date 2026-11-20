<?php

namespace App\Filament\Pages;

use App\Enums\KioskModule;
use App\Enums\LogLevel;
use App\Enums\MenuBlockType;
use App\Models\ActivityLog;
use App\Models\KioskMenu;
use App\Models\KioskMenuBlock;
use App\Models\Setting;
use App\Models\WallPanel;
use App\Services\HomeyService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * @property-read Schema $form
 */
class ModulesOverview extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?string $navigationLabel = 'Modules';

    protected static ?string $title = 'Kiosk Modules & Opties';

    protected static ?string $slug = 'modules';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.modules-overview';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, string> */
    public array $flowOptions = [];

    public ?string $activeModule = null;

    public function mount(): void
    {
        KioskMenu::ensureRootExists();
        WallPanel::ensureDefaultsExist();

        $alarm = Setting::alarmModuleOptions();
        $gym = Setting::gymModuleOptions();
        $allOff = Setting::allOffModuleOptions();

        $presetData = [];
        foreach (KioskModule::presetGridModules() as $module) {
            $opts = Setting::presetModuleOptions($module);
            $slotsState = [];
            foreach ($opts['slots'] as $pos => $slot) {
                $slotsState[$pos] = [
                    'label' => $slot['label'],
                    'flow_id' => $slot['flowId'],
                ];
            }

            $presetData[$module->value] = [
                'screen_title' => $opts['screenTitle'],
                'badge' => $opts['badge'],
                'confirm_first_slot' => $opts['confirmFirstSlot'],
                'auto_return' => $opts['autoReturn'],
                'slots' => $slotsState,
            ];
        }

        $this->form->fill([
            'countdown_seconds' => $alarm['countdownSeconds'],
            'alarm_countdown_seconds' => $alarm['alarmCountdownSeconds'],
            'max_failed_attempts' => $alarm['maxFailedAttempts'],
            'lockout_seconds' => $alarm['lockoutSeconds'],
            'alarm_check_open_sensors' => $alarm['checkOpenSensors'],
            'gym_on_flow_id' => $gym['onFlowId'],
            'gym_off_flow_id' => $gym['offFlowId'],
            'gym_on_label' => $gym['onLabel'],
            'gym_off_label' => $gym['offLabel'],
            'gym_auto_return' => $gym['autoReturn'],
            'all_off_flow_id' => $allOff['flowId'],
            'all_off_title' => $allOff['title'],
            'all_off_subtitle' => $allOff['subtitle'],
            'all_off_confirm_label' => $allOff['confirmLabel'],
            'all_off_require_confirmation' => $allOff['requireConfirmation'],
            'preset_modules' => $presetData,
        ]);
    }

    public function selectModuleFilter(?string $moduleKey): void
    {
        if ($moduleKey !== null && KioskModule::tryFrom($moduleKey) === null) {
            return;
        }

        $this->activeModule = $this->activeModule === $moduleKey ? null : $moduleKey;
    }

    public function loadFlowOptions(): void
    {
        $options = [];
        foreach (app(HomeyService::class)->getFlows() as $flow) {
            $options[$flow['id']] = $flow['name'];
        }
        $this->flowOptions = $options;
    }

    /**
     * @return array<string, string>
     */
    protected function flowOptionsWithCurrent(?string $currentId, bool $allowLightsOff = false): array
    {
        $options = $allowLightsOff
            ? ['__lights_off__' => '⚡ Ingebouwd: Alle verlichting direct uit', ...$this->flowOptions]
            : $this->flowOptions;

        if ($currentId !== null && $currentId !== '' && $currentId !== '__lights_off__' && ! isset($options[$currentId])) {
            $options[$currentId] = "Gekoppelde Flow ({$currentId})";
        }

        return $options;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getModulePlacementsProperty(): array
    {
        $placements = [];
        foreach (KioskModule::cases() as $module) {
            $placements[$module->value] = [];
        }

        $blocks = KioskMenuBlock::query()
            ->where('type', MenuBlockType::Module)
            ->with('menu.parentBlock.menu')
            ->orderBy('kiosk_menu_id')
            ->orderBy('position')
            ->get();

        foreach ($blocks as $block) {
            if (! $block->module_key instanceof KioskModule || $block->menu === null) {
                continue;
            }

            $trail = implode(' › ', array_map(
                fn (array $crumb): string => $crumb['title'],
                $block->menu->breadcrumbs()
            ));

            $placements[$block->module_key->value][] = "{$trail} (Vak {$block->position})";
        }

        return $placements;
    }

    public function form(Schema $schema): Schema
    {
        $sections = [
            Section::make('Module: Alarm (Heimdall)')
                ->description('Opties voor het volledige beveiligingsmenu (in-/uitschakelen, open sensoren controle, PIN-blokkade en timers).')
                ->columns(2)
                ->collapsible()
                ->visible(fn (): bool => $this->activeModule === null || $this->activeModule === KioskModule::Alarm->value)
                ->components([
                    TextInput::make('countdown_seconds')
                        ->label('Uitloopvertraging (sec)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(300)
                        ->required(),

                    TextInput::make('alarm_countdown_seconds')
                        ->label('Alarm countdown bij inbraak (sec)')
                        ->numeric()
                        ->minValue(5)
                        ->maxValue(300)
                        ->required(),

                    TextInput::make('max_failed_attempts')
                        ->label('Max. foutieve PIN-pogingen')
                        ->numeric()
                        ->minValue(2)
                        ->maxValue(20)
                        ->required(),

                    TextInput::make('lockout_seconds')
                        ->label('Blokkadetijd na foutieve pogingen (sec)')
                        ->numeric()
                        ->minValue(10)
                        ->maxValue(600)
                        ->required(),

                    Toggle::make('alarm_check_open_sensors')
                        ->label('Controleer open ramen & deuren vóór inschakelen')
                        ->helperText('Toont een waarschuwingsscherm met openstaande contact-/bewegingssensoren voordat de PIN wordt gevraagd.')
                        ->columnSpanFull(),
                ]),
        ];

        foreach (KioskModule::presetGridModules() as $presetModule) {
            $sections[] = $this->buildPresetModuleSection($presetModule);
        }

        $sections[] = Section::make('Module: Gym (Aan/Uit Schakelaar)')
            ->description('Opties voor het 2x2 Gym-scherm met eigen AAN- en UIT-knoppen gekoppeld aan Homey Flows.')
            ->columns(2)
            ->collapsible()
            ->collapsed(fn (): bool => $this->activeModule === null)
            ->visible(fn (): bool => $this->activeModule === null || $this->activeModule === KioskModule::Gym->value)
            ->components([
                Select::make('gym_on_flow_id')
                    ->label('Homey Flow voor AAN-knop')
                    ->placeholder('-- Niet gekoppeld --')
                    ->options(fn (): array => $this->flowOptionsWithCurrent((string) ($this->data['gym_on_flow_id'] ?? ''))),

                Select::make('gym_off_flow_id')
                    ->label('Homey Flow voor UIT-knop')
                    ->placeholder('-- Niet gekoppeld --')
                    ->options(fn (): array => $this->flowOptionsWithCurrent((string) ($this->data['gym_off_flow_id'] ?? ''))),

                TextInput::make('gym_on_label')
                    ->label('Label op AAN-knop')
                    ->maxLength(30)
                    ->required(),

                TextInput::make('gym_off_label')
                    ->label('Label op UIT-knop')
                    ->maxLength(30)
                    ->required(),

                Toggle::make('gym_auto_return')
                    ->label('Automatisch terug naar startscherm na schakelen')
                    ->columnSpanFull(),
            ]);

        $sections[] = Section::make('Module: Alles Uit / Flow Actie')
            ->description('Opties voor het schermvullende Alles Uit-menu met directe lamp-uitschakeling of Homey Flow.')
            ->columns(2)
            ->collapsible()
            ->collapsed(fn (): bool => $this->activeModule === null)
            ->visible(fn (): bool => $this->activeModule === null || $this->activeModule === KioskModule::AllOff->value)
            ->components([
                Select::make('all_off_flow_id')
                    ->label('Gekoppelde Homey Flow of Actie')
                    ->placeholder('-- Niet gekoppeld --')
                    ->options(fn (): array => $this->flowOptionsWithCurrent((string) ($this->data['all_off_flow_id'] ?? ''), allowLightsOff: true))
                    ->columnSpanFull(),

                TextInput::make('all_off_title')
                    ->label('Schermtitel')
                    ->maxLength(50)
                    ->required(),

                TextInput::make('all_off_confirm_label')
                    ->label('Tekst op bevestigknop')
                    ->maxLength(30)
                    ->required(),

                TextInput::make('all_off_subtitle')
                    ->label('Onderschrift bij bevestigingsvraag')
                    ->maxLength(140)
                    ->required()
                    ->columnSpanFull(),

                Toggle::make('all_off_require_confirmation')
                    ->label('Vraag om bevestiging voordat alles wordt uitgeschakeld')
                    ->columnSpanFull(),
            ]);

        return $schema
            ->statePath('data')
            ->components($sections);
    }

    protected function buildPresetModuleSection(KioskModule $module): Section
    {
        $defaults = $module->defaultPresetConfig();
        $key = $module->value;

        $fields = [
            TextInput::make("preset_modules.{$key}.screen_title")
                ->label('Schermtitel op Kiosk')
                ->maxLength(40)
                ->required(),

            TextInput::make("preset_modules.{$key}.badge")
                ->label('Status / Temperatuur Badge (optioneel)')
                ->placeholder('Bijv. 38°C of leeg laten')
                ->helperText($defaults['badgeHint'])
                ->maxLength(20),
        ];

        foreach ($defaults['slots'] as $pos => $slotMeta) {
            $allowLightsOff = ! empty($slotMeta['allowLightsOff']);

            $fields[] = TextInput::make("preset_modules.{$key}.slots.{$pos}.label")
                ->label("{$slotMeta['slotLabel']} — Knoptekst")
                ->maxLength(30)
                ->required();

            $fields[] = Select::make("preset_modules.{$key}.slots.{$pos}.flow_id")
                ->label("{$slotMeta['slotLabel']} — Homey Flow")
                ->placeholder('-- Niet gekoppeld --')
                ->options(fn (): array => $this->flowOptionsWithCurrent(
                    (string) ($this->data['preset_modules'][$key]['slots'][$pos]['flow_id'] ?? ''),
                    allowLightsOff: $allowLightsOff,
                ));
        }

        $fields[] = Toggle::make("preset_modules.{$key}.confirm_first_slot")
            ->label($defaults['confirmFirstSlotLabel'])
            ->columnSpanFull();

        $fields[] = Toggle::make("preset_modules.{$key}.auto_return")
            ->label('Automatisch terug naar startscherm na activeren van een tegel')
            ->columnSpanFull();

        return Section::make('Module: '.$module->getLabel())
            ->description($module->description())
            ->columns(2)
            ->collapsible()
            ->collapsed(fn (): bool => $this->activeModule === null)
            ->visible(fn (): bool => $this->activeModule === null || $this->activeModule === $key)
            ->components($fields);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openMenuBuilder')
                ->label('NS Panel Builder')
                ->color('gray')
                ->icon(Heroicon::OutlinedSquares2x2)
                ->url(fn (): string => MenuBuilder::getUrl()),

            Action::make('openWallPanels')
                ->label('Grote Schermen')
                ->color('info')
                ->icon(Heroicon::OutlinedComputerDesktop)
                ->url(fn (): string => WallPanelBuilder::getUrl()),

            Action::make('refreshFlows')
                ->label('Ververs Flows')
                ->color('gray')
                ->icon(Heroicon::OutlinedArrowPath)
                ->action(function (): void {
                    $this->loadFlowOptions();

                    Notification::make()
                        ->title('Flows bijgewerkt ('.count($this->flowOptions).' gevonden)')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $updates = [];

        if (array_key_exists('countdown_seconds', $state)) {
            $updates['countdown_seconds'] = (int) $state['countdown_seconds'];
            $updates['alarm_countdown_seconds'] = (int) ($state['alarm_countdown_seconds'] ?? 10);
            $updates['max_failed_attempts'] = (int) ($state['max_failed_attempts'] ?? 5);
            $updates['lockout_seconds'] = (int) ($state['lockout_seconds'] ?? 60);
            $updates['alarm_check_open_sensors'] = ! empty($state['alarm_check_open_sensors']) ? '1' : '0';
        }

        if (array_key_exists('gym_on_flow_id', $state)) {
            $updates['gym_on_flow_id'] = preg_replace('/[^a-zA-Z0-9_\-]/', '', trim((string) ($state['gym_on_flow_id'] ?? '')));
            $updates['gym_off_flow_id'] = preg_replace('/[^a-zA-Z0-9_\-]/', '', trim((string) ($state['gym_off_flow_id'] ?? '')));
            $updates['gym_on_label'] = trim((string) ($state['gym_on_label'] ?? 'AAN')) ?: 'AAN';
            $updates['gym_off_label'] = trim((string) ($state['gym_off_label'] ?? 'UIT')) ?: 'UIT';
            $updates['gym_auto_return'] = ! empty($state['gym_auto_return']) ? '1' : '0';
        }

        if (array_key_exists('all_off_flow_id', $state)) {
            $updates['all_off_flow_id'] = preg_replace('/[^a-zA-Z0-9_\-]/', '', trim((string) ($state['all_off_flow_id'] ?? '')));
            $updates['all_off_title'] = trim((string) ($state['all_off_title'] ?? 'ALLES UITZETTEN?')) ?: 'ALLES UITZETTEN?';
            $updates['all_off_subtitle'] = trim((string) ($state['all_off_subtitle'] ?? '')) ?: 'Weet je zeker dat je alles in huis wilt uitschakelen?';
            $updates['all_off_confirm_label'] = trim((string) ($state['all_off_confirm_label'] ?? 'Ja, zet uit')) ?: 'Ja, zet uit';
            $updates['all_off_require_confirmation'] = ! empty($state['all_off_require_confirmation']) ? '1' : '0';
        }

        if ($updates !== []) {
            Setting::setMany($updates);
        }

        if (isset($state['preset_modules']) && is_array($state['preset_modules'])) {
            foreach (KioskModule::presetGridModules() as $presetModule) {
                $moduleInput = $state['preset_modules'][$presetModule->value] ?? null;
                if (is_array($moduleInput)) {
                    Setting::savePresetModuleOptions($presetModule, $moduleInput);
                }
            }
        }

        ActivityLog::record(LogLevel::Info, 'Module-instellingen bijgewerkt');
        $this->loadFlowOptions();

        Notification::make()
            ->title('Module-opties opgeslagen')
            ->success()
            ->send();
    }
}
