<?php

namespace App\Models;

use App\Enums\KioskModule;
use App\Enums\MenuBlockColor;
use App\Enums\MenuBlockIcon;
use App\Services\HomeyService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

#[Fillable(['key', 'value', 'is_encrypted'])]
class Setting extends Model
{
    public const CACHE_KEY = 'heimdall.settings.map';

    public const WEATHER_CACHE_KEY = 'heimdall.weather.cache';

    /** @var array<int, string> */
    public const ENCRYPTED_KEYS = [
        'homey_api_key',
        'doorbell_webhook_token',
    ];

    /** @var array<int, string> */
    public const WEATHER_SENSITIVE_KEYS = [
        'homey_base_url',
        'homey_api_key',
        'weather_lat',
        'weather_lon',
    ];

    /** @var array<string, int|string|null> */
    public const DEFAULTS = [
        'homey_base_url' => '',
        'homey_api_key' => '',
        'countdown_seconds' => 10,
        'alarm_countdown_seconds' => 10,
        'max_failed_attempts' => 5,
        'lockout_seconds' => 60,
        'alarm_check_open_sensors' => '1',
        'rain_window_minutes' => 75,
        'weather_lat' => null,
        'weather_lon' => null,
        'all_off_flow_id' => '',
        'all_off_title' => 'ALLES UITZETTEN?',
        'all_off_subtitle' => 'Weet je zeker dat je alles in huis wilt uitschakelen?',
        'all_off_confirm_label' => 'Ja, zet uit',
        'all_off_require_confirmation' => '1',
        'gym_on_flow_id' => '',
        'gym_off_flow_id' => '',
        'gym_on_label' => 'AAN',
        'gym_off_label' => 'UIT',
        'gym_auto_return' => '1',
        'go2rtc_base_url' => '',
        'doorbell_timeout_seconds' => 60,
        'doorbell_action_flow_id' => '',
        'doorbell_action_label' => 'VOORDEUR / POORT',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $setting): void {
            Cache::forget(self::CACHE_KEY);
            if (in_array($setting->key, self::WEATHER_SENSITIVE_KEYS, true)) {
                Cache::forget(self::WEATHER_CACHE_KEY);
            }
            if (in_array($setting->key, ['homey_base_url', 'homey_api_key'], true)) {
                app(HomeyService::class)->clearOfflineState((string) $setting->value);
            }
        });

        static::deleted(function (): void {
            Cache::forget(self::CACHE_KEY);
        });
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $map = static::allCached();

        if (array_key_exists($key, $map)) {
            return $map[$key] ?? $default;
        }

        return $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function setValue(string $key, mixed $value, ?bool $encrypt = null): self
    {
        $shouldEncrypt = $encrypt ?? in_array($key, self::ENCRYPTED_KEYS, true);
        $serialized = $value === null ? null : (string) $value;

        if ($shouldEncrypt && $serialized !== null && $serialized !== '') {
            $serialized = Crypt::encryptString($serialized);
        }

        return static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $serialized,
                'is_encrypted' => $shouldEncrypt,
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::setValue($key, $value);
        }
    }

    /**
     * @return array<string, string|null>
     */
    public static function allCached(): array
    {
        if (! Schema::hasTable('settings')) {
            return [];
        }

        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $result = [];

            foreach (static::query()->get() as $row) {
                if ($row->is_encrypted && $row->value !== null && $row->value !== '') {
                    try {
                        $result[$row->key] = Crypt::decryptString($row->value);
                    } catch (DecryptException) {
                        $result[$row->key] = null;
                    }
                } else {
                    $result[$row->key] = $row->value;
                }
            }

            return $result;
        });
    }

    /**
     * @return array{countdownSeconds: int, alarmCountdownSeconds: int, maxFailedAttempts: int, lockoutSeconds: int, rainWindowMinutes: int}
     */
    public static function publicSettings(): array
    {
        return [
            'countdownSeconds' => max(0, min(300, (int) static::getValue('countdown_seconds', 10))),
            'alarmCountdownSeconds' => max(5, min(300, (int) static::getValue('alarm_countdown_seconds', 10))),
            'maxFailedAttempts' => max(2, min(20, (int) static::getValue('max_failed_attempts', 5))),
            'lockoutSeconds' => max(10, min(600, (int) static::getValue('lockout_seconds', 60))),
            'rainWindowMinutes' => max(15, min(180, (int) static::getValue('rain_window_minutes', 75))),
        ];
    }

    /**
     * @return array{allOffFlowId: string, gymOnFlowId: string, gymOffFlowId: string}
     */
    public static function flowBindings(): array
    {
        return [
            'allOffFlowId' => trim((string) static::getValue('all_off_flow_id', '')),
            'gymOnFlowId' => trim((string) static::getValue('gym_on_flow_id', '')),
            'gymOffFlowId' => trim((string) static::getValue('gym_off_flow_id', '')),
        ];
    }

    /**
     * @return array{countdownSeconds: int, alarmCountdownSeconds: int, maxFailedAttempts: int, lockoutSeconds: int, checkOpenSensors: bool}
     */
    public static function alarmModuleOptions(): array
    {
        $public = static::publicSettings();
        $rawCheck = (string) static::getValue('alarm_check_open_sensors', '1');

        return [
            ...$public,
            'checkOpenSensors' => ! in_array($rawCheck, ['0', 'false', 'off'], true),
        ];
    }

    /**
     * @return array{onFlowId: string, offFlowId: string, onLabel: string, offLabel: string, autoReturn: bool}
     */
    public static function gymModuleOptions(): array
    {
        $onLabel = trim((string) static::getValue('gym_on_label', 'AAN'));
        $offLabel = trim((string) static::getValue('gym_off_label', 'UIT'));
        $rawAutoReturn = (string) static::getValue('gym_auto_return', '1');

        return [
            'onFlowId' => trim((string) static::getValue('gym_on_flow_id', '')),
            'offFlowId' => trim((string) static::getValue('gym_off_flow_id', '')),
            'onLabel' => $onLabel !== '' ? $onLabel : 'AAN',
            'offLabel' => $offLabel !== '' ? $offLabel : 'UIT',
            'autoReturn' => ! in_array($rawAutoReturn, ['0', 'false', 'off'], true),
        ];
    }

    /**
     * @return array{flowId: string, title: string, subtitle: string, confirmLabel: string, requireConfirmation: bool}
     */
    public static function allOffModuleOptions(): array
    {
        $title = trim((string) static::getValue('all_off_title', 'ALLES UITZETTEN?'));
        $subtitle = trim((string) static::getValue('all_off_subtitle', 'Weet je zeker dat je alles in huis wilt uitschakelen?'));
        $confirmLabel = trim((string) static::getValue('all_off_confirm_label', 'Ja, zet uit'));
        $rawConfirm = (string) static::getValue('all_off_require_confirmation', '1');

        return [
            'flowId' => trim((string) static::getValue('all_off_flow_id', '')),
            'title' => $title !== '' ? $title : 'ALLES UITZETTEN?',
            'subtitle' => $subtitle !== '' ? $subtitle : 'Weet je zeker dat je alles in huis wilt uitschakelen?',
            'confirmLabel' => $confirmLabel !== '' ? $confirmLabel : 'Ja, zet uit',
            'requireConfirmation' => ! in_array($rawConfirm, ['0', 'false', 'off'], true),
        ];
    }

    /**
     * @return array{
     *     screenTitle: string,
     *     badge: string,
     *     confirmFirstSlot: bool,
     *     autoReturn: bool,
     *     slots: array<int, array{label: string, flowId: string, icon: MenuBlockIcon, color: MenuBlockColor}>
     * }
     */
    public static function presetModuleOptions(KioskModule $module): array
    {
        $defaults = $module->defaultPresetConfig();
        $rawJson = static::getValue('module_config_'.$module->value);
        $decoded = is_string($rawJson) && $rawJson !== '' ? json_decode($rawJson, true) : null;
        $stored = is_array($decoded) ? $decoded : [];

        $screenTitle = isset($stored['screen_title']) ? trim((string) $stored['screen_title']) : '';
        $badge = array_key_exists('badge', $stored) ? trim((string) $stored['badge']) : $defaults['badge'];
        $confirmFirstSlot = array_key_exists('confirm_first_slot', $stored)
            ? (bool) $stored['confirm_first_slot']
            : $defaults['confirmFirstSlot'];
        $autoReturn = array_key_exists('auto_return', $stored)
            ? (bool) $stored['auto_return']
            : $defaults['autoReturn'];

        $slots = [];
        foreach ($defaults['slots'] as $pos => $slotDefault) {
            $storedSlot = is_array($stored['slots'][$pos] ?? null) ? $stored['slots'][$pos] : [];
            $customLabel = trim((string) ($storedSlot['label'] ?? ''));
            $rawFlowId = trim((string) ($storedSlot['flow_id'] ?? ''));

            $slots[$pos] = [
                'label' => $customLabel !== '' ? $customLabel : $slotDefault['defaultLabel'],
                'flowId' => preg_replace('/[^a-zA-Z0-9_\-]/', '', $rawFlowId) ?? '',
                'icon' => $slotDefault['icon'],
                'color' => $slotDefault['color'],
            ];
        }

        return [
            'screenTitle' => $screenTitle !== '' ? $screenTitle : $defaults['screenTitle'],
            'badge' => $badge,
            'confirmFirstSlot' => $confirmFirstSlot,
            'autoReturn' => $autoReturn,
            'slots' => $slots,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function savePresetModuleOptions(KioskModule $module, array $input): void
    {
        $defaults = $module->defaultPresetConfig();
        $slotsPayload = [];

        foreach ($defaults['slots'] as $pos => $slotDefault) {
            $slotInput = is_array($input['slots'][$pos] ?? null) ? $input['slots'][$pos] : [];
            $cleanLabel = trim((string) ($slotInput['label'] ?? ''));
            $cleanFlowId = preg_replace('/[^a-zA-Z0-9_\-]/', '', trim((string) ($slotInput['flow_id'] ?? ''))) ?? '';

            $slotsPayload[$pos] = [
                'label' => $cleanLabel !== '' ? $cleanLabel : $slotDefault['defaultLabel'],
                'flow_id' => $cleanFlowId,
            ];
        }

        $cleanScreenTitle = trim((string) ($input['screen_title'] ?? ''));

        $payload = [
            'screen_title' => $cleanScreenTitle !== '' ? $cleanScreenTitle : $defaults['screenTitle'],
            'badge' => trim((string) ($input['badge'] ?? '')),
            'confirm_first_slot' => ! empty($input['confirm_first_slot']),
            'auto_return' => ! empty($input['auto_return']),
            'slots' => $slotsPayload,
        ];

        static::setValue('module_config_'.$module->value, json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    public static function ensureDoorbellWebhookToken(): string
    {
        $existing = trim((string) static::getValue('doorbell_webhook_token', ''));

        if ($existing !== '') {
            return $existing;
        }

        return static::regenerateDoorbellWebhookToken();
    }

    public static function regenerateDoorbellWebhookToken(): string
    {
        $token = bin2hex(random_bytes(20));
        static::setValue('doorbell_webhook_token', $token, encrypt: true);

        return $token;
    }

    /**
     * @return array{
     *     go2rtcBaseUrl: string,
     *     doorbellTimeoutSeconds: int,
     *     doorbellActionFlowId: string,
     *     doorbellActionLabel: string,
     *     doorbellWebhookToken: string
     * }
     */
    public static function cameraAndDoorbellSettings(): array
    {
        $actionLabel = trim((string) static::getValue('doorbell_action_label', 'VOORDEUR / POORT'));

        return [
            'go2rtcBaseUrl' => rtrim(trim((string) static::getValue('go2rtc_base_url', '')), '/'),
            'doorbellTimeoutSeconds' => max(10, min(300, (int) static::getValue('doorbell_timeout_seconds', 60))),
            'doorbellActionFlowId' => trim((string) static::getValue('doorbell_action_flow_id', '')),
            'doorbellActionLabel' => $actionLabel !== '' ? $actionLabel : 'VOORDEUR / POORT',
            'doorbellWebhookToken' => static::ensureDoorbellWebhookToken(),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
        ];
    }
}
