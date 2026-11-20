<?php

namespace App\Services;

use App\Enums\AlarmMode;
use App\Enums\KioskModule;
use App\Enums\LogLevel;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class HomeyService
{
    public const OFFLINE_COOLDOWN_SECONDS = 30;

    /** @var array<string, bool> */
    protected array $unreachableInMemory = [];

    protected function stateCache(): CacheRepository
    {
        $limiterStore = config('cache.limiter');

        return is_string($limiterStore) && $limiterStore !== ''
            ? Cache::store($limiterStore)
            : Cache::store();
    }

    public static function offlineCacheKey(string $baseUrl): string
    {
        return 'heimdall.homey.offline:'.md5(strtolower(rtrim(trim($baseUrl), '/')));
    }

    public function clearOfflineState(?string $baseUrl = null): void
    {
        $resolved = rtrim(trim($baseUrl ?? $this->getBaseUrl()), '/');
        unset($this->unreachableInMemory[$resolved]);

        if ($resolved !== '') {
            $this->stateCache()->forget(self::offlineCacheKey($resolved));
        }
    }

    protected function isMarkedOffline(string $baseUrl): bool
    {
        if (! empty($this->unreachableInMemory[$baseUrl])) {
            return true;
        }

        return (bool) $this->stateCache()->has(self::offlineCacheKey($baseUrl));
    }

    protected function markOffline(string $baseUrl): void
    {
        $this->unreachableInMemory[$baseUrl] = true;
        $this->stateCache()->put(self::offlineCacheKey($baseUrl), true, self::OFFLINE_COOLDOWN_SECONDS);
    }

    public function getBaseUrl(): string
    {
        return rtrim(trim((string) Setting::getValue('homey_base_url', '')), '/');
    }

    public function getApiKey(): string
    {
        return trim((string) Setting::getValue('homey_api_key', ''));
    }

    public function isConfigured(): bool
    {
        return $this->getBaseUrl() !== '' && $this->getApiKey() !== '';
    }

    /**
     * @param  array<string, mixed>|null  $body
     * @return array{code: int, data: mixed}
     */
    public function request(
        string $method,
        string $path,
        ?array $body = null,
        string $prefix = '/api/manager',
        ?string $overrideUrl = null,
        ?string $overrideToken = null,
    ): array {
        $baseUrl = rtrim(trim($overrideUrl ?? $this->getBaseUrl()), '/');
        $token = trim($overrideToken ?? $this->getApiKey());

        if ($baseUrl === '' || $token === '') {
            return ['code' => 500, 'data' => null];
        }

        $bypassCircuitBreaker = $overrideUrl !== null;

        if (! $bypassCircuitBreaker && $this->isMarkedOffline($baseUrl)) {
            return ['code' => 500, 'data' => null];
        }

        $url = $baseUrl.$prefix.$path;

        try {
            $response = $this->sendHttpRequest($method, $url, $token, $body);
        } catch (ConnectionException) {
            if (stripos($baseUrl, 'https://') === 0) {
                $fallbackUrl = 'http://'.substr($baseUrl, 8).$prefix.$path;
                try {
                    $response = $this->sendHttpRequest($method, $fallbackUrl, $token, $body);
                } catch (ConnectionException) {
                    if (! $bypassCircuitBreaker) {
                        $this->markOffline($baseUrl);
                    }

                    return ['code' => 500, 'data' => null];
                }
            } else {
                if (! $bypassCircuitBreaker) {
                    $this->markOffline($baseUrl);
                }

                return ['code' => 500, 'data' => null];
            }
        }

        if ($bypassCircuitBreaker) {
            $this->clearOfflineState($baseUrl);
        }

        return [
            'code' => $response->status(),
            'data' => $response->json(),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $body
     */
    protected function sendHttpRequest(string $method, string $url, string $token, ?array $body): Response
    {
        $pending = Http::withoutVerifying()
            ->connectTimeout(1)
            ->timeout(2)
            ->withToken($token)
            ->acceptJson();

        return match (strtoupper($method)) {
            'POST' => $pending->post($url, $body ?? []),
            'PUT' => $pending->put($url, $body ?? []),
            'DELETE' => $pending->delete($url, $body ?? []),
            default => $pending->get($url),
        };
    }

    public function getHeimdallDeviceId(?string $url = null, ?string $token = null): ?string
    {
        $res = $this->request('GET', '/devices/device', null, '/api/manager', $url, $token);

        if ($res['code'] !== 200 || ! is_array($res['data'])) {
            return null;
        }

        foreach ($res['data'] as $id => $device) {
            if (! is_array($device)) {
                continue;
            }
            if (($device['driverId'] ?? '') === 'surveillancemodeswitch'
                || stripos((string) ($device['name'] ?? ''), 'surveillance') !== false) {
                return (string) $id;
            }
        }

        return null;
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function testConnection(?string $url = null, ?string $token = null): array
    {
        $resolvedUrl = rtrim(trim($url ?? $this->getBaseUrl()), '/');
        $resolvedToken = trim($token ?? $this->getApiKey());

        if ($resolvedUrl === '' || $resolvedToken === '') {
            return ['ok' => false, 'message' => 'Vul zowel URL als API Key in'];
        }

        $deviceId = $this->getHeimdallDeviceId($resolvedUrl, $resolvedToken);
        if ($deviceId !== null) {
            return ['ok' => true, 'message' => 'Verbonden! Heimdall apparaat gevonden.'];
        }

        $res = $this->request('GET', '/devices/device', null, '/api/manager', $resolvedUrl, $resolvedToken);
        if ($res['code'] === 401 || $res['code'] === 403) {
            return ['ok' => false, 'message' => 'Geweigerd (ongeldige API Key of scopes)'];
        }
        if ($res['code'] === 200) {
            return ['ok' => false, 'message' => 'Verbonden met Homey, maar geen Heimdall switch gevonden'];
        }

        return ['ok' => false, 'message' => "Kan Homey niet bereiken op {$resolvedUrl}"];
    }

    public function getAlarmStatus(): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $deviceId = $this->getHeimdallDeviceId();
        if ($deviceId === null) {
            return null;
        }

        $res = $this->request('GET', "/devices/device/{$deviceId}");
        $state = $res['data']['capabilitiesObj']['homealarm_state']['value'] ?? null;

        return is_string($state) && $state !== '' ? $state : null;
    }

    public function setAlarmMode(AlarmMode|string $mode): bool
    {
        $modeValue = $mode instanceof AlarmMode ? $mode->value : $mode;
        $alarmEnum = AlarmMode::tryFrom($modeValue);

        if ($alarmEnum === null || ! $this->isConfigured()) {
            return false;
        }

        $deviceId = $this->getHeimdallDeviceId();
        if ($deviceId === null) {
            return false;
        }

        $res = $this->request('PUT', "/devices/device/{$deviceId}/capability/homealarm_state", [
            'value' => $alarmEnum->value,
        ]);

        if ($res['code'] >= 200 && $res['code'] < 300) {
            ActivityLog::record(LogLevel::Success, 'Alarm status: '.$alarmEnum->getLabel(), [
                'mode' => $alarmEnum->value,
            ]);

            return true;
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    public function getOpenSensors(): array
    {
        $sensors = $this->getAllSensors();

        $open = [];
        foreach ($sensors as $sensor) {
            if ($sensor['active']) {
                $open[] = $sensor['zone'].' - '.$sensor['name'];
            }
        }

        return $open;
    }

    /**
     * @return array<int, array{id: string, name: string, zone: string, type: string, active: bool, battery: int|null}>
     */
    public function getAllSensors(): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $res = $this->request('GET', '/devices/device');
        if ($res['code'] !== 200 || ! is_array($res['data'])) {
            return [];
        }

        $zonesRes = $this->request('GET', '/zones/zone');
        $zonesMap = [];
        if ($zonesRes['code'] === 200 && is_array($zonesRes['data'])) {
            foreach ($zonesRes['data'] as $zoneId => $zone) {
                if (is_array($zone) && isset($zone['name'])) {
                    $zonesMap[(string) $zoneId] = (string) $zone['name'];
                }
            }
        }

        $sensors = [];
        foreach ($res['data'] as $id => $device) {
            if (! is_array($device)) {
                continue;
            }
            $caps = $device['capabilitiesObj'] ?? [];
            if (! is_array($caps)) {
                continue;
            }

            $hasContact = isset($caps['alarm_contact']);
            $hasMotion = isset($caps['alarm_motion']);

            if ($hasContact || $hasMotion) {
                $zoneId = (string) ($device['zone'] ?? '');
                $zoneName = $zonesMap[$zoneId] ?? 'Onbekend';
                $isActive = ($hasContact && ! empty($caps['alarm_contact']['value']))
                    || ($hasMotion && ! empty($caps['alarm_motion']['value']));
                $battery = isset($caps['measure_battery']['value']) && is_numeric($caps['measure_battery']['value'])
                    ? (int) $caps['measure_battery']['value']
                    : null;

                $sensors[] = [
                    'id' => (string) $id,
                    'name' => (string) ($device['name'] ?? 'Sensor'),
                    'zone' => $zoneName,
                    'type' => $hasContact ? 'contact' : 'motion',
                    'active' => $isActive,
                    'battery' => $battery,
                ];
            }
        }

        usort($sensors, function (array $a, array $b): int {
            if ($a['active'] !== $b['active']) {
                return $a['active'] ? -1 : 1;
            }

            return strcasecmp($a['zone'].' '.$a['name'], $b['zone'].' '.$b['name']);
        });

        return $sensors;
    }

    /**
     * @return array<int, array{id: string, name: string, type: string}>
     */
    public function getFlows(): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $flows = [];

        $stdRes = $this->request('GET', '/flow/flow');
        if ($stdRes['code'] === 200 && is_array($stdRes['data'])) {
            foreach ($stdRes['data'] as $id => $flow) {
                if (! is_array($flow) || (($flow['enabled'] ?? true) === false)) {
                    continue;
                }
                $flows[] = [
                    'id' => (string) $id,
                    'name' => (string) ($flow['name'] ?? $id),
                    'type' => 'standard',
                ];
            }
        }

        $advRes = $this->request('GET', '/flow/advancedflow');
        if ($advRes['code'] === 200 && is_array($advRes['data'])) {
            foreach ($advRes['data'] as $id => $flow) {
                if (! is_array($flow) || (($flow['enabled'] ?? true) === false)) {
                    continue;
                }
                $flows[] = [
                    'id' => (string) $id,
                    'name' => ((string) ($flow['name'] ?? $id)).' (Advanced)',
                    'type' => 'advanced',
                ];
            }
        }

        usort($flows, fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']));

        return $flows;
    }

    public function triggerFlow(string $flowId): bool
    {
        if ($flowId === '' || ! $this->isConfigured()) {
            return false;
        }

        $encodedId = rawurlencode($flowId);
        $res = $this->request('POST', "/flow/flow/{$encodedId}/trigger");

        if ($res['code'] < 200 || $res['code'] >= 300) {
            $res = $this->request('POST', "/flow/advancedflow/{$encodedId}/trigger");
        }

        return $res['code'] >= 200 && $res['code'] < 300;
    }

    /**
     * @return array{ok: bool, turnedOff?: int, message?: string}
     */
    public function triggerAllOff(): array
    {
        $flowId = Setting::flowBindings()['allOffFlowId'];

        if ($flowId === '') {
            return ['ok' => false, 'message' => 'KOPPEL FLOW IN ADMIN'];
        }

        if ($flowId === '__lights_off__') {
            return $this->turnOffAllLights('Alles Uit');
        }

        if ($this->triggerFlow($flowId)) {
            ActivityLog::record(LogLevel::Info, 'Alles Uit flow getriggerd', ['flow_id' => $flowId]);

            return ['ok' => true];
        }

        return ['ok' => false, 'message' => 'KON FLOW NIET STARTEN'];
    }

    /**
     * @return array{ok: bool, turnedOff?: int, message?: string}
     */
    protected function turnOffAllLights(string $contextLabel): array
    {
        $res = $this->request('GET', '/devices/device');
        if ($res['code'] !== 200 || ! is_array($res['data'])) {
            return ['ok' => false, 'message' => 'KON APPARATEN NIET OPHALEN'];
        }

        $turnedOff = 0;
        foreach ($res['data'] as $devId => $device) {
            if (! is_array($device)) {
                continue;
            }
            $class = $device['class'] ?? '';
            $vClass = $device['virtualClass'] ?? '';
            $isOn = ! empty($device['capabilitiesObj']['onoff']['value']);

            if (($class === 'light' || $vClass === 'light') && $isOn) {
                $putRes = $this->request('PUT', "/devices/device/{$devId}/capability/onoff", ['value' => false]);
                if ($putRes['code'] >= 200 && $putRes['code'] < 300) {
                    $turnedOff++;
                }
            }
        }

        ActivityLog::record(LogLevel::Info, "{$contextLabel}: {$turnedOff} lamp(en) uitgeschakeld", [
            'turned_off' => $turnedOff,
        ]);

        return ['ok' => true, 'turnedOff' => $turnedOff];
    }

    /**
     * @return array{ok: bool, turnedOff?: int, message?: string}
     */
    public function triggerModuleAction(KioskModule $module, string $actionLabel, string $flowId): array
    {
        $cleanFlowId = trim($flowId);

        if ($cleanFlowId === '') {
            return ['ok' => false, 'message' => 'FLOW NOG NIET GEKOPPELD'];
        }

        if ($cleanFlowId === '__lights_off__') {
            return $this->turnOffAllLights($module->getLabel());
        }

        if ($this->triggerFlow($cleanFlowId)) {
            ActivityLog::record(LogLevel::Info, "{$module->getLabel()}: {$actionLabel} geactiveerd", [
                'module' => $module->value,
                'flow_id' => $cleanFlowId,
            ]);

            return ['ok' => true];
        }

        return ['ok' => false, 'message' => 'KON FLOW NIET STARTEN'];
    }

    /**
     * @return array{ok: bool, message?: string}
     */
    public function triggerGym(string $state): array
    {
        $bindings = Setting::flowBindings();
        $flowId = $state === 'on' ? $bindings['gymOnFlowId'] : $bindings['gymOffFlowId'];

        if ($flowId === '') {
            return ['ok' => false, 'message' => 'FLOW NOG NIET GEKOPPELD'];
        }

        if ($this->triggerFlow($flowId)) {
            $label = $state === 'on' ? 'AAN' : 'UIT';
            ActivityLog::record(LogLevel::Info, "Gym flow getriggerd: {$label}", ['flow_id' => $flowId]);

            return ['ok' => true];
        }

        return ['ok' => false, 'message' => 'KON FLOW NIET STARTEN'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTrashCollections(): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $res = $this->request('GET', '/com.trashchecker/trashcollectiondays', null, '/api/app');

        return ($res['code'] === 200 && is_array($res['data'])) ? array_values($res['data']) : [];
    }

    /**
     * @return array{lat: float, lon: float}|null
     */
    public function getGeolocation(): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $geoRes = $this->request('GET', '/geolocation/option/location');
        $geoVal = $geoRes['data']['value'] ?? null;

        if (
            $geoRes['code'] === 200
            && is_array($geoVal)
            && isset($geoVal['latitude'], $geoVal['longitude'])
            && is_numeric($geoVal['latitude'])
            && is_numeric($geoVal['longitude'])
        ) {
            return [
                'lat' => (float) $geoVal['latitude'],
                'lon' => (float) $geoVal['longitude'],
            ];
        }

        return null;
    }

    public function getFallbackWeatherTemp(): ?float
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $hwRes = $this->request('GET', '/weather/weather');
        $temp = $hwRes['data']['temperatureCelsius'] ?? null;

        if ($hwRes['code'] === 200 && is_numeric($temp)) {
            return round((float) $temp, 1);
        }

        return null;
    }
}
