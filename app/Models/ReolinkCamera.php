<?php

namespace App\Models;

use App\Enums\CameraRole;
use Database\Factories\ReolinkCameraFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'slug',
    'role',
    'go2rtc_stream',
    'stream_url',
    'snapshot_url',
    'supports_two_way_audio',
    'show_on_doorbell',
    'sort_order',
    'is_active',
])]
class ReolinkCamera extends Model
{
    /** @use HasFactory<ReolinkCameraFactory> */
    use HasFactory;

    public function resolvedWebrtcUrl(?string $go2rtcBaseUrl = null): ?string
    {
        $base = rtrim(trim($go2rtcBaseUrl ?? (string) Setting::getValue('go2rtc_base_url', '')), '/');
        $stream = trim((string) ($this->go2rtc_stream ?? ''));

        if ($base === '' || $stream === '') {
            return null;
        }

        return $base.'/api/webrtc?src='.rawurlencode($stream);
    }

    public function resolvedSnapshotUrl(?string $go2rtcBaseUrl = null): ?string
    {
        $explicit = trim((string) ($this->snapshot_url ?? ''));
        if ($explicit !== '') {
            return $explicit;
        }

        $base = rtrim(trim($go2rtcBaseUrl ?? (string) Setting::getValue('go2rtc_base_url', '')), '/');
        $stream = trim((string) ($this->go2rtc_stream ?? ''));

        if ($base !== '' && $stream !== '') {
            return $base.'/api/frame.jpeg?src='.rawurlencode($stream);
        }

        return null;
    }

    public function isConfigured(?string $go2rtcBaseUrl = null): bool
    {
        return $this->resolvedWebrtcUrl($go2rtcBaseUrl) !== null
            || trim((string) ($this->stream_url ?? '')) !== ''
            || trim((string) ($this->snapshot_url ?? '')) !== '';
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     role: string,
     *     webrtcUrl: string|null,
     *     streamUrl: string|null,
     *     snapshotUrl: string|null,
     *     supportsTwoWayAudio: bool,
     *     isConfigured: bool
     * }
     */
    public function toFrontendPayload(?string $go2rtcBaseUrl = null): array
    {
        $streamUrl = trim((string) ($this->stream_url ?? ''));

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'role' => $this->role->value,
            'webrtcUrl' => $this->resolvedWebrtcUrl($go2rtcBaseUrl),
            'streamUrl' => $streamUrl !== '' ? $streamUrl : null,
            'snapshotUrl' => $this->resolvedSnapshotUrl($go2rtcBaseUrl),
            'supportsTwoWayAudio' => (bool) $this->supports_two_way_audio,
            'isConfigured' => $this->isConfigured($go2rtcBaseUrl),
        ];
    }

    /**
     * @return Collection<int, self>
     */
    public static function ensureDefaultsExist(): Collection
    {
        if (! static::query()->exists()) {
            $defaults = [
                [
                    'name' => 'Deurbel Voordeur',
                    'slug' => 'deurbel',
                    'role' => CameraRole::Doorbell,
                    'go2rtc_stream' => 'deurbel',
                    'supports_two_way_audio' => true,
                    'show_on_doorbell' => true,
                    'sort_order' => 1,
                    'is_active' => true,
                ],
                [
                    'name' => 'Tuin Camera 1 (Voorzijde)',
                    'slug' => 'tuin-1',
                    'role' => CameraRole::Garden,
                    'go2rtc_stream' => 'tuin_1',
                    'supports_two_way_audio' => false,
                    'show_on_doorbell' => true,
                    'sort_order' => 2,
                    'is_active' => true,
                ],
                [
                    'name' => 'Tuin Camera 2 (Oprit / Zijtuin)',
                    'slug' => 'tuin-2',
                    'role' => CameraRole::Garden,
                    'go2rtc_stream' => 'tuin_2',
                    'supports_two_way_audio' => false,
                    'show_on_doorbell' => true,
                    'sort_order' => 3,
                    'is_active' => true,
                ],
                [
                    'name' => 'Tuin Camera 3 (Terras)',
                    'slug' => 'tuin-3',
                    'role' => CameraRole::Garden,
                    'go2rtc_stream' => 'tuin_3',
                    'supports_two_way_audio' => false,
                    'show_on_doorbell' => true,
                    'sort_order' => 4,
                    'is_active' => true,
                ],
                [
                    'name' => 'Tuin Camera 4 (Achtertuin)',
                    'slug' => 'tuin-4',
                    'role' => CameraRole::Garden,
                    'go2rtc_stream' => 'tuin_4',
                    'supports_two_way_audio' => false,
                    'show_on_doorbell' => true,
                    'sort_order' => 5,
                    'is_active' => true,
                ],
            ];

            foreach ($defaults as $item) {
                static::query()->create($item);
            }
        }

        return static::query()
            ->where('is_active', true)
            ->orderByRaw("CASE WHEN role = 'doorbell' THEN 0 WHEN role = 'garden' THEN 1 ELSE 2 END")
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, self>
     */
    public static function doorbellSet(): Collection
    {
        static::ensureDefaultsExist();

        return static::query()
            ->where('is_active', true)
            ->where('show_on_doorbell', true)
            ->orderByRaw("CASE WHEN role = 'doorbell' THEN 0 WHEN role = 'garden' THEN 1 ELSE 2 END")
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(5)
            ->get();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => CameraRole::class,
            'supports_two_way_audio' => 'boolean',
            'show_on_doorbell' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
