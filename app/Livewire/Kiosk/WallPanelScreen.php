<?php

namespace App\Livewire\Kiosk;

use App\Enums\KioskModule;
use App\Enums\MenuBlockType;
use App\Models\KioskMenu;
use App\Models\ReolinkCamera;
use App\Models\Setting;
use App\Models\User;
use App\Models\WallPanel;
use App\Services\DoorbellService;
use App\Services\HomeyService;
use App\Services\PinLockoutService;
use App\Services\TrashService;
use App\Services\WeatherService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.wall-panel')]
class WallPanelScreen extends Component
{
    use WithRateLimiting;

    public WallPanel $wallPanel;

    #[Url]
    public ?int $menu = null;

    #[Url]
    public ?string $module = null;

    public bool $isAsleep = true;

    public ?int $focusedCameraId = null;

    public bool $doorbellActive = false;

    public int $doorbellRemainingSeconds = 0;

    public ?string $activeDoorbellEventId = null;

    public ?string $dismissedDoorbellEventId = null;

    public ?string $alarmStatus = null;

    public bool $isConfigured = true;

    /** @var array{tempLabel: string, feelsLikeLabel: string|null, icon: string|null, conditionLabel: string|null}|null */
    public ?array $outdoorWeather = null;

    /** @var array<int, array<string, mixed>> */
    public array $dashboardItems = [];

    public ?string $feedbackMsg = null;

    public string $feedbackType = 'info';

    public ?int $confirmingSlot = null;

    public bool $confirmingAllOff = false;

    public string $alarmStep = 'choose';

    public ?string $pendingAlarmMode = null;

    /** @var array<int, string> */
    public array $openSensors = [];

    public string $pinBuffer = '';

    public bool $isLockedOut = false;

    public int $lockoutSecondsLeft = 0;

    public function mount(WallPanel $wallPanel): void
    {
        abort_unless($wallPanel->is_active, 404);

        $this->wallPanel = $wallPanel;
        $this->wallPanel->ensureRootMenuExists();
        ReolinkCamera::ensureDefaultsExist();

        if ($this->module !== null && KioskModule::tryFrom($this->module) === null) {
            $this->module = null;
        }

        if ($this->module === KioskModule::Alarm->value) {
            $this->prepareAlarmModule();
        }

        $this->isAsleep = ($this->module === null && $this->menu === null);
        $this->isConfigured = app(HomeyService::class)->isConfigured();
        $this->checkDoorbell();
    }

    public function wakeUp(): void
    {
        $this->isAsleep = false;
    }

    public function enterSleepMode(): void
    {
        if ($this->doorbellActive) {
            return;
        }

        $this->isAsleep = true;
        $this->module = null;
        $this->menu = null;
        $this->confirmingSlot = null;
        $this->confirmingAllOff = false;
        $this->focusedCameraId = null;
        $this->pinBuffer = '';
        $this->openSensors = [];
        $this->pendingAlarmMode = null;
    }

    public function refreshSidebar(): void
    {
        $homey = app(HomeyService::class);
        $weather = app(WeatherService::class);
        $trash = app(TrashService::class);

        $this->isConfigured = $homey->isConfigured();

        if (! $this->isConfigured) {
            $this->alarmStatus = null;
            $this->outdoorWeather = null;
            $this->dashboardItems = [];

            return;
        }

        $this->alarmStatus = $homey->getAlarmStatus();

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

    public function checkDoorbell(): void
    {
        if (! $this->wallPanel->show_doorbell_popup) {
            $this->doorbellActive = false;
            $this->doorbellRemainingSeconds = 0;

            return;
        }

        $event = app(DoorbellService::class)->activeEvent($this->wallPanel->slug);

        if ($event === null || $event['event_id'] === $this->dismissedDoorbellEventId) {
            $this->doorbellActive = false;
            $this->doorbellRemainingSeconds = 0;

            return;
        }

        $isNewRing = $this->activeDoorbellEventId !== $event['event_id'];
        $this->activeDoorbellEventId = $event['event_id'];
        $this->doorbellRemainingSeconds = $event['remaining_seconds'];
        $this->doorbellActive = true;

        if ($isNewRing) {
            $this->focusedCameraId = null;
            $this->dispatch('kiosk-sound', type: 'doorbell');
        }
    }

    public function dismissDoorbell(): void
    {
        if ($this->activeDoorbellEventId !== null) {
            $this->dismissedDoorbellEventId = $this->activeDoorbellEventId;
        }

        $this->doorbellActive = false;
        $this->doorbellRemainingSeconds = 0;
        $this->focusedCameraId = null;
    }

    public function focusCamera(int $cameraId): void
    {
        $this->focusedCameraId = $cameraId;
    }

    public function triggerDoorbellAction(): void
    {
        $settings = Setting::cameraAndDoorbellSettings();
        $flowId = $settings['doorbellActionFlowId'];

        if ($flowId === '') {
            $this->feedbackType = 'error';
            $this->feedbackMsg = 'GEEN DEUR-FLOW GEKOPPELD';

            return;
        }

        $ok = app(HomeyService::class)->triggerFlow($flowId);
        if ($ok) {
            $this->feedbackType = 'info';
            $this->feedbackMsg = strtoupper($settings['doorbellActionLabel']).' GEACTIVEERD';
        } else {
            $this->feedbackType = 'error';
            $this->feedbackMsg = 'KON DEUR-FLOW NIET STARTEN';
        }
    }

    public function activateBlock(int $blockId): void
    {
        $this->isAsleep = false;
        $this->feedbackMsg = null;
        $currentMenu = $this->resolveCurrentMenu();
        $block = $currentMenu->blocks->firstWhere('id', $blockId);

        if ($block === null) {
            return;
        }

        if ($block->type === MenuBlockType::Submenu && $block->submenu_id !== null) {
            $this->menu = $block->submenu_id;
            $this->module = null;

            return;
        }

        if ($block->type === MenuBlockType::Module && $block->module_key instanceof KioskModule) {
            $this->openModule($block->module_key);
        }
    }

    public function openModuleByKey(string $moduleKey): void
    {
        $resolved = KioskModule::tryFrom($moduleKey);
        if ($resolved !== null) {
            $this->openModule($resolved);
        }
    }

    public function openModule(KioskModule $targetModule): void
    {
        $this->isAsleep = false;
        $this->feedbackMsg = null;
        $this->confirmingSlot = null;
        $this->confirmingAllOff = false;
        $this->focusedCameraId = null;
        $this->module = $targetModule->value;

        if ($targetModule === KioskModule::Alarm) {
            $this->prepareAlarmModule();
        } elseif ($targetModule === KioskModule::AllOff) {
            $allOff = Setting::allOffModuleOptions();
            if (! $allOff['requireConfirmation']) {
                $this->executeAllOff();
            } else {
                $this->confirmingAllOff = true;
            }
        }
    }

    public function closeModule(): void
    {
        $this->isAsleep = false;
        $this->module = null;
        $this->confirmingSlot = null;
        $this->confirmingAllOff = false;
        $this->focusedCameraId = null;
        $this->pinBuffer = '';
        $this->openSensors = [];
        $this->pendingAlarmMode = null;
    }

    public function goBack(): void
    {
        if ($this->confirmingSlot !== null) {
            $this->confirmingSlot = null;

            return;
        }

        if ($this->module !== null) {
            $this->closeModule();

            return;
        }

        $currentMenu = $this->resolveCurrentMenu();
        if (! $currentMenu->is_root) {
            $parent = $currentMenu->parentMenu();
            $this->menu = ($parent !== null && ! $parent->is_root) ? $parent->id : null;

            return;
        }

        $this->enterSleepMode();
    }

    public function dismissFeedback(): void
    {
        $this->feedbackMsg = null;
    }

    public function triggerGymState(string $state): void
    {
        if (! in_array($state, ['on', 'off'], true)) {
            return;
        }

        $gym = Setting::gymModuleOptions();
        $res = app(HomeyService::class)->triggerGym($state);

        if ($res['ok']) {
            $label = $state === 'on' ? $gym['onLabel'] : $gym['offLabel'];
            $this->feedbackType = 'info';
            $this->feedbackMsg = 'GYM '.strtoupper($label).' GEACTIVEERD';
            if ($gym['autoReturn']) {
                $this->module = null;
            }
        } else {
            $this->feedbackType = 'error';
            $this->feedbackMsg = $res['message'] ?? 'KON FLOW NIET STARTEN';
        }
    }

    public function executeAllOff(): void
    {
        $this->confirmingAllOff = false;
        $res = app(HomeyService::class)->triggerAllOff();

        if ($res['ok']) {
            $this->feedbackType = 'info';
            $this->feedbackMsg = 'ALLES UITGESCHAKELD';
            $this->module = null;
        } else {
            $this->feedbackType = 'error';
            $this->feedbackMsg = $res['message'] ?? 'KON FLOW NIET STARTEN';
        }
    }

    public function triggerPresetSlot(int $slot): void
    {
        $activeModule = $this->module !== null ? KioskModule::tryFrom($this->module) : null;
        if ($activeModule === null || ! $activeModule->isPresetGridModule() || ! in_array($slot, [1, 2, 3, 4], true)) {
            return;
        }

        $options = Setting::presetModuleOptions($activeModule);

        if ($slot === 1 && $options['confirmFirstSlot'] && $this->confirmingSlot !== 1) {
            $this->confirmingSlot = 1;

            return;
        }

        $this->confirmingSlot = null;
        $slotConfig = $options['slots'][$slot] ?? null;
        if (! is_array($slotConfig)) {
            return;
        }

        $res = app(HomeyService::class)->triggerModuleAction(
            $activeModule,
            (string) $slotConfig['label'],
            (string) $slotConfig['flowId'],
        );

        if ($res['ok']) {
            $this->feedbackType = 'info';
            $this->feedbackMsg = strtoupper((string) $slotConfig['label']).' GEACTIVEERD';
            if (! empty($options['autoReturn'])) {
                $this->module = null;
            }
        } else {
            $this->feedbackType = 'error';
            $this->feedbackMsg = $res['message'] ?? 'FLOW NOG NIET GEKOPPELD';
        }
    }

    public function confirmPendingPresetSlot(): void
    {
        if ($this->confirmingSlot === null) {
            return;
        }

        $slot = $this->confirmingSlot;
        $this->confirmingSlot = null;
        $this->triggerPresetSlot($slot);
    }

    protected function prepareAlarmModule(): void
    {
        $this->pinBuffer = '';
        $this->openSensors = [];
        $lockout = app(PinLockoutService::class);
        $this->isLockedOut = $lockout->isLockedOut();
        $this->lockoutSecondsLeft = $this->isLockedOut ? $lockout->remainingSeconds() : 0;

        $status = app(HomeyService::class)->getAlarmStatus();
        $this->alarmStatus = $status;

        if ($status === 'armed' || $status === 'partially_armed') {
            $this->pendingAlarmMode = 'disarmed';
            $this->alarmStep = 'pin';
        } else {
            $this->pendingAlarmMode = null;
            $this->alarmStep = 'choose';
        }
    }

    public function selectAlarmMode(string $mode): void
    {
        if (! in_array($mode, ['armed', 'partially_armed'], true)) {
            return;
        }

        $this->pendingAlarmMode = $mode;
        $alarmOptions = Setting::alarmModuleOptions();

        if ($alarmOptions['checkOpenSensors']) {
            $sensors = app(HomeyService::class)->getOpenSensors();
            if ($sensors !== []) {
                $this->openSensors = $sensors;
                $this->alarmStep = 'sensors';

                return;
            }
        }

        $this->pinBuffer = '';
        $this->alarmStep = 'pin';
    }

    public function acceptOpenSensors(): void
    {
        if ($this->pendingAlarmMode !== null) {
            $this->pinBuffer = '';
            $this->alarmStep = 'pin';
        }
    }

    public function appendPinDigit(string $digit): void
    {
        if (! preg_match('/^[0-9]$/', $digit) || strlen($this->pinBuffer) >= 8) {
            return;
        }

        $this->pinBuffer .= $digit;
    }

    public function clearPinBuffer(): void
    {
        $this->pinBuffer = '';
    }

    public function submitWallPanelPin(): void
    {
        if ($this->pinBuffer === '') {
            return;
        }

        $pin = $this->pinBuffer;
        $this->pinBuffer = '';
        $settings = Setting::publicSettings();
        $lockout = app(PinLockoutService::class);

        try {
            $this->rateLimit(max($settings['maxFailedAttempts'] * 2, 10), $settings['lockoutSeconds']);
        } catch (TooManyRequestsException $exception) {
            $this->isLockedOut = true;
            $this->lockoutSecondsLeft = max(1, $exception->secondsUntilAvailable);
            $this->dispatch('kiosk-sound', type: 'error');

            return;
        }

        if ($lockout->isLockedOut()) {
            $this->isLockedOut = true;
            $this->lockoutSecondsLeft = $lockout->remainingSeconds();
            $this->dispatch('kiosk-sound', type: 'error');

            return;
        }

        $verified = User::verifyPin($pin);

        if ($verified === null) {
            $failure = $lockout->recordFailure(contextLabel: "wandpaneel ({$this->wallPanel->name})");
            $this->dispatch('kiosk-sound', type: 'error');

            if ($failure['lockedOut']) {
                $this->isLockedOut = true;
                $this->lockoutSecondsLeft = $failure['remainingSeconds'];
            } else {
                $this->feedbackType = 'error';
                $this->feedbackMsg = 'FOUTIEVE PINCODE';
            }

            return;
        }

        $this->clearRateLimiter();
        $lockout->clear();
        $this->isLockedOut = false;

        $targetMode = $this->pendingAlarmMode ?? 'disarmed';
        $ok = app(HomeyService::class)->setAlarmMode($targetMode);

        if ($ok) {
            $this->alarmStatus = $targetMode;
            $this->dispatch('kiosk-sound', type: $targetMode === 'disarmed' ? 'disarmed' : 'armed');
            $this->feedbackType = 'info';
            $this->feedbackMsg = match ($targetMode) {
                'armed' => 'ALARM VOLLEDIG INGESCHAKELD',
                'partially_armed' => 'ALARM GEDEELTELIJK INGESCHAKELD',
                default => 'ALARM UITGESCHAKELD',
            };
            $this->closeModule();
        } else {
            $this->feedbackType = 'error';
            $this->feedbackMsg = 'KON HOMEY ALARM NIET SCHAKELEN';
        }
    }

    protected function resolveCurrentMenu(): KioskMenu
    {
        $root = $this->wallPanel->ensureRootMenuExists();

        if ($this->menu === null) {
            return $root;
        }

        $found = KioskMenu::query()
            ->where('wall_panel_id', $this->wallPanel->id)
            ->with(['blocks.submenu', 'parentBlock.menu'])
            ->find($this->menu);

        if ($found === null) {
            $this->menu = null;

            return $root;
        }

        $found->ensureBlocks();

        return $found->load(['blocks.submenu', 'parentBlock.menu']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $cameras
     * @return array{hero: array<string, mixed>|null, companions: array<int, array<string, mixed>>}
     */
    protected function splitHeroAndCompanions(array $cameras): array
    {
        if ($cameras === []) {
            return ['hero' => null, 'companions' => []];
        }

        $heroIndex = 0;
        if ($this->focusedCameraId !== null) {
            foreach ($cameras as $idx => $cam) {
                if ($cam['id'] === $this->focusedCameraId) {
                    $heroIndex = $idx;
                    break;
                }
            }
        }

        $hero = $cameras[$heroIndex];
        $companions = [];
        foreach ($cameras as $idx => $cam) {
            if ($idx !== $heroIndex) {
                $companions[] = $cam;
            }
        }

        return [
            'hero' => $hero,
            'companions' => array_slice($companions, 0, 4),
        ];
    }

    public function render(): View
    {
        $cameraSettings = Setting::cameraAndDoorbellSettings();
        $go2rtcBase = $cameraSettings['go2rtcBaseUrl'];

        $doorbellCameras = ReolinkCamera::doorbellSet()
            ->map(fn (ReolinkCamera $c): array => $c->toFrontendPayload($go2rtcBase))
            ->values()
            ->all();

        $allActiveCameras = ReolinkCamera::ensureDefaultsExist()
            ->map(fn (ReolinkCamera $c): array => $c->toFrontendPayload($go2rtcBase))
            ->values()
            ->all();

        $activeModuleEnum = $this->module !== null ? KioskModule::tryFrom($this->module) : null;

        return view('livewire.kiosk.wall-panel-screen', [
            'currentMenu' => $this->resolveCurrentMenu(),
            'activeModule' => $activeModuleEnum,
            'doorbellLayout' => $this->splitHeroAndCompanions($doorbellCameras),
            'camerasModuleLayout' => $this->splitHeroAndCompanions(array_slice($allActiveCameras, 0, 5)),
            'cameraSettings' => $cameraSettings,
            'gymOptions' => $activeModuleEnum === KioskModule::Gym ? Setting::gymModuleOptions() : null,
            'allOffOptions' => $activeModuleEnum === KioskModule::AllOff ? Setting::allOffModuleOptions() : null,
            'presetOptions' => ($activeModuleEnum !== null && $activeModuleEnum->isPresetGridModule())
                ? Setting::presetModuleOptions($activeModuleEnum)
                : null,
        ])->title($this->wallPanel->name.' | Heimdall Wandpaneel');
    }
}
