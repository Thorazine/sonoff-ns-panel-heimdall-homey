<?php

namespace App\Filament\Pages;

use App\Enums\CameraRole;
use App\Enums\LogLevel;
use App\Models\ActivityLog;
use App\Models\ReolinkCamera;
use App\Models\Setting;
use App\Services\DoorbellService;
use App\Services\HomeyService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ManageCameras extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedVideoCamera;

    protected static ?string $navigationLabel = 'Camera\'s & Deurbel';

    protected static ?string $title = 'Reolink Camera\'s, 2-Weg Audio & Deurbel';

    protected static ?string $slug = 'cameras';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.manage-cameras';

    public string $go2rtcBaseUrl = '';

    public int $doorbellTimeoutSeconds = 60;

    public string $doorbellActionFlowId = '';

    public string $doorbellActionLabel = 'VOORDEUR / POORT';

    public string $doorbellWebhookToken = '';

    /** @var array<int, array<string, mixed>> */
    public array $cameras = [];

    /** @var array<string, string> */
    public array $flowOptions = [];

    public bool $showAddCameraForm = false;

    public string $newCameraName = '';

    public string $newCameraRole = 'garden';

    public string $newCameraGo2rtcStream = '';

    public function mount(): void
    {
        ReolinkCamera::ensureDefaultsExist();
        $this->loadState();
    }

    public function loadState(): void
    {
        $settings = Setting::cameraAndDoorbellSettings();
        $this->go2rtcBaseUrl = $settings['go2rtcBaseUrl'];
        $this->doorbellTimeoutSeconds = $settings['doorbellTimeoutSeconds'];
        $this->doorbellActionFlowId = $settings['doorbellActionFlowId'];
        $this->doorbellActionLabel = $settings['doorbellActionLabel'];
        $this->doorbellWebhookToken = $settings['doorbellWebhookToken'];

        $rows = ReolinkCamera::query()
            ->orderByRaw("CASE WHEN role = 'doorbell' THEN 0 WHEN role = 'garden' THEN 1 ELSE 2 END")
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $cameraState = [];
        foreach ($rows as $cam) {
            $cameraState[$cam->id] = [
                'id' => $cam->id,
                'name' => $cam->name,
                'slug' => $cam->slug,
                'role' => $cam->role->value,
                'go2rtc_stream' => (string) ($cam->go2rtc_stream ?? ''),
                'stream_url' => (string) ($cam->stream_url ?? ''),
                'snapshot_url' => (string) ($cam->snapshot_url ?? ''),
                'supports_two_way_audio' => (bool) $cam->supports_two_way_audio,
                'show_on_doorbell' => (bool) $cam->show_on_doorbell,
                'sort_order' => (int) $cam->sort_order,
                'is_active' => (bool) $cam->is_active,
            ];
        }

        $this->cameras = $cameraState;
    }

    public function loadFlowOptions(): void
    {
        $options = [];
        foreach (app(HomeyService::class)->getFlows() as $flow) {
            $options[$flow['id']] = $flow['name'];
        }
        $this->flowOptions = $options;
    }

    public function getWebhookUrlProperty(): string
    {
        return url('/api/webhook/doorbell').'?token='.$this->doorbellWebhookToken;
    }

    public function saveAll(): void
    {
        $this->validate([
            'go2rtcBaseUrl' => ['nullable', 'string', 'max:255'],
            'doorbellTimeoutSeconds' => ['required', 'integer', 'min:10', 'max:300'],
            'doorbellActionFlowId' => ['nullable', 'string', 'max:120'],
            'doorbellActionLabel' => ['required', 'string', 'max:30'],
            'cameras.*.name' => ['required', 'string', 'max:80'],
            'cameras.*.role' => ['required', Rule::in(array_map(fn (CameraRole $r): string => $r->value, CameraRole::cases()))],
            'cameras.*.go2rtc_stream' => ['nullable', 'string', 'max:120'],
            'cameras.*.stream_url' => ['nullable', 'string', 'max:500'],
            'cameras.*.snapshot_url' => ['nullable', 'string', 'max:500'],
            'cameras.*.sort_order' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $cleanGo2rtc = rtrim(trim($this->go2rtcBaseUrl), '/');
        $cleanFlowId = preg_replace('/[^a-zA-Z0-9_\-]/', '', trim($this->doorbellActionFlowId)) ?? '';
        $cleanActionLabel = trim($this->doorbellActionLabel) !== '' ? trim($this->doorbellActionLabel) : 'VOORDEUR / POORT';

        Setting::setMany([
            'go2rtc_base_url' => $cleanGo2rtc,
            'doorbell_timeout_seconds' => max(10, min(300, $this->doorbellTimeoutSeconds)),
            'doorbell_action_flow_id' => $cleanFlowId,
            'doorbell_action_label' => $cleanActionLabel,
        ]);

        foreach ($this->cameras as $camId => $data) {
            $cam = ReolinkCamera::query()->find((int) $camId);
            if ($cam === null) {
                continue;
            }

            $cleanStream = trim((string) ($data['go2rtc_stream'] ?? ''));
            $cleanStreamUrl = trim((string) ($data['stream_url'] ?? ''));
            $cleanSnapshotUrl = trim((string) ($data['snapshot_url'] ?? ''));

            $cam->update([
                'name' => trim((string) $data['name']),
                'role' => CameraRole::from((string) $data['role']),
                'go2rtc_stream' => $cleanStream !== '' ? $cleanStream : null,
                'stream_url' => $cleanStreamUrl !== '' ? $cleanStreamUrl : null,
                'snapshot_url' => $cleanSnapshotUrl !== '' ? $cleanSnapshotUrl : null,
                'supports_two_way_audio' => ! empty($data['supports_two_way_audio']),
                'show_on_doorbell' => ! empty($data['show_on_doorbell']),
                'sort_order' => (int) ($data['sort_order'] ?? 1),
                'is_active' => ! empty($data['is_active']),
            ]);
        }

        $this->loadState();

        ActivityLog::record(LogLevel::Info, 'Reolink camera- en deurbelinstellingen opgeslagen');

        Notification::make()
            ->title('Camera- en deurbelinstellingen opgeslagen')
            ->success()
            ->send();
    }

    public function toggleAddCameraForm(): void
    {
        $this->showAddCameraForm = ! $this->showAddCameraForm;
        $this->newCameraName = '';
        $this->newCameraRole = CameraRole::Garden->value;
        $this->newCameraGo2rtcStream = '';
    }

    public function createCamera(): ReolinkCamera
    {
        $this->validate([
            'newCameraName' => ['required', 'string', 'max:80'],
            'newCameraRole' => ['required', Rule::in(array_map(fn (CameraRole $r): string => $r->value, CameraRole::cases()))],
            'newCameraGo2rtcStream' => ['nullable', 'string', 'max:120'],
        ]);

        $cleanName = trim($this->newCameraName);
        $baseSlug = Str::slug($cleanName) ?: 'camera';
        $slug = $baseSlug;
        $i = 2;
        while (ReolinkCamera::query()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$i}";
            $i++;
        }

        $role = CameraRole::from($this->newCameraRole);
        $cleanStream = trim($this->newCameraGo2rtcStream);

        $camera = ReolinkCamera::query()->create([
            'name' => $cleanName,
            'slug' => $slug,
            'role' => $role,
            'go2rtc_stream' => $cleanStream !== '' ? $cleanStream : null,
            'supports_two_way_audio' => $role === CameraRole::Doorbell,
            'show_on_doorbell' => ReolinkCamera::query()->where('show_on_doorbell', true)->count() < 5,
            'sort_order' => ReolinkCamera::query()->max('sort_order') + 1,
            'is_active' => true,
        ]);

        $this->showAddCameraForm = false;
        $this->loadState();

        ActivityLog::record(LogLevel::Info, "Reolink camera toegevoegd: {$camera->name}");

        Notification::make()
            ->title("Camera '{$camera->name}' toegevoegd")
            ->success()
            ->send();

        return $camera;
    }

    public function deleteCamera(int $cameraId): void
    {
        $cam = ReolinkCamera::query()->find($cameraId);
        if ($cam === null) {
            return;
        }

        $name = $cam->name;
        $cam->delete();
        $this->loadState();

        ActivityLog::record(LogLevel::Warning, "Reolink camera verwijderd: {$name}");

        Notification::make()
            ->title("Camera '{$name}' verwijderd")
            ->success()
            ->send();
    }

    public function regenerateWebhookToken(): void
    {
        $this->doorbellWebhookToken = Setting::regenerateDoorbellWebhookToken();

        ActivityLog::record(LogLevel::Warning, 'Deurbel webhook-token opnieuw gegenereerd');

        Notification::make()
            ->title('Nieuw deurbel webhook-token aangemaakt')
            ->body('Pas de URL in je Homey Flow aan naar het nieuwe token.')
            ->success()
            ->send();
    }

    public function triggerTestDoorbell(): void
    {
        $event = app(DoorbellService::class)->ring(source: 'admin-test');

        Notification::make()
            ->title('Test-aanbellen gestart!')
            ->body("Alle grote schermen met deurbel-weergave tonen nu gedurende {$event['remaining_seconds']}s de 5 camera's.")
            ->success()
            ->send();
    }

    public function stopDoorbell(): void
    {
        app(DoorbellService::class)->clear();

        Notification::make()
            ->title('Deurbel-weergave gestopt')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testDoorbell')
                ->label('Test Aanbellen (5 Camera\'s)')
                ->color('warning')
                ->icon(Heroicon::OutlinedBellAlert)
                ->action(fn () => $this->triggerTestDoorbell()),

            Action::make('stopDoorbell')
                ->label('Stop Deurbel')
                ->color('gray')
                ->action(fn () => $this->stopDoorbell()),

            Action::make('openWallPanels')
                ->label('Grote Schermen Builder')
                ->color('info')
                ->icon(Heroicon::OutlinedComputerDesktop)
                ->url(fn (): string => WallPanelBuilder::getUrl()),
        ];
    }
}
