<?php

namespace App\Services;

use App\Enums\LogLevel;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DoorbellService
{
    public const CACHE_KEY = 'heimdall.doorbell.active';

    /**
     * @return array{
     *     event_id: string,
     *     triggered_at: int,
     *     expires_at: int,
     *     panel_slug: string|null,
     *     source: string,
     *     remaining_seconds: int
     * }
     */
    public function ring(?string $panelSlug = null, string $source = 'webhook'): array
    {
        $settings = Setting::cameraAndDoorbellSettings();
        $ttlSeconds = $settings['doorbellTimeoutSeconds'];
        $cleanPanelSlug = $panelSlug !== null && trim($panelSlug) !== '' ? trim($panelSlug) : null;
        $now = now();

        $event = [
            'event_id' => (string) Str::uuid(),
            'triggered_at' => $now->getTimestamp(),
            'expires_at' => $now->copy()->addSeconds($ttlSeconds)->getTimestamp(),
            'panel_slug' => $cleanPanelSlug,
            'source' => $source,
        ];

        Cache::put(self::CACHE_KEY, $event, $ttlSeconds);

        ActivityLog::record(LogLevel::Info, 'Deurbel geactiveerd (5-camera weergave)', [
            'source' => $source,
            'panel_slug' => $cleanPanelSlug,
            'timeout_seconds' => $ttlSeconds,
        ]);

        return [
            ...$event,
            'remaining_seconds' => $ttlSeconds,
        ];
    }

    /**
     * @return array{
     *     event_id: string,
     *     triggered_at: int,
     *     expires_at: int,
     *     panel_slug: string|null,
     *     source: string,
     *     remaining_seconds: int
     * }|null
     */
    public function activeEvent(?string $panelSlug = null): ?array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (! is_array($cached) || ! isset($cached['event_id'], $cached['expires_at'])) {
            return null;
        }

        $nowTimestamp = now()->getTimestamp();
        $expiresAt = (int) $cached['expires_at'];

        if ($expiresAt <= $nowTimestamp) {
            $this->clear();

            return null;
        }

        $targetPanel = isset($cached['panel_slug']) && is_string($cached['panel_slug']) && $cached['panel_slug'] !== ''
            ? $cached['panel_slug']
            : null;

        if ($panelSlug !== null && $targetPanel !== null && $targetPanel !== $panelSlug) {
            return null;
        }

        return [
            'event_id' => (string) $cached['event_id'],
            'triggered_at' => (int) ($cached['triggered_at'] ?? $nowTimestamp),
            'expires_at' => $expiresAt,
            'panel_slug' => $targetPanel,
            'source' => (string) ($cached['source'] ?? 'webhook'),
            'remaining_seconds' => max(1, $expiresAt - $nowTimestamp),
        ];
    }

    public function clear(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
