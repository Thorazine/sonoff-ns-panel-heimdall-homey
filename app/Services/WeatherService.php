<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WeatherService
{
    public function __construct(
        protected HomeyService $homey,
    ) {}

    public function buienradarValueToMm(int|float|string|null $value): float
    {
        if (! is_numeric($value)) {
            return 0.0;
        }

        $num = (float) $value;
        if (! is_finite($num) || $num <= 0) {
            return 0.0;
        }

        return round(pow(10, ($num - 109) / 32), 2);
    }

    public function formatDutchTemp(int|float $temp): string
    {
        $rounded = round((float) $temp, 1);
        if ($rounded == 0.0) {
            $rounded = 0.0;
        }

        return str_replace('.', ',', number_format($rounded, 1, '.', '')).'°C';
    }

    /**
     * @return array{icon: string|null, conditionLabel: string|null}
     */
    public function mapWeatherCodeToIcon(?int $weatherCode, int|bool $isDay = 1): array
    {
        if ($weatherCode === null) {
            return ['icon' => null, 'conditionLabel' => null];
        }

        $daytime = (bool) $isDay;

        if ($weatherCode === 0 || $weatherCode === 1) {
            return $daytime
                ? ['icon' => 'clear-day', 'conditionLabel' => 'Zonnig']
                : ['icon' => 'clear-night', 'conditionLabel' => 'Helder'];
        }
        if ($weatherCode === 2) {
            return $daytime
                ? ['icon' => 'partly-cloudy-day', 'conditionLabel' => 'Halfbewolkt']
                : ['icon' => 'partly-cloudy-night', 'conditionLabel' => 'Halfbewolkt'];
        }
        if ($weatherCode === 3) {
            return ['icon' => 'cloudy', 'conditionLabel' => 'Bewolkt'];
        }
        if ($weatherCode === 45 || $weatherCode === 48) {
            return ['icon' => 'fog', 'conditionLabel' => 'Mist'];
        }
        if ($weatherCode >= 51 && $weatherCode <= 57) {
            return ['icon' => 'rain', 'conditionLabel' => 'Motregen'];
        }
        if (($weatherCode >= 61 && $weatherCode <= 67) || ($weatherCode >= 80 && $weatherCode <= 82)) {
            return ['icon' => 'rain', 'conditionLabel' => 'Regen'];
        }
        if (($weatherCode >= 71 && $weatherCode <= 77) || $weatherCode === 85 || $weatherCode === 86) {
            return ['icon' => 'snow', 'conditionLabel' => 'Sneeuw'];
        }
        if ($weatherCode >= 95) {
            return ['icon' => 'thunder', 'conditionLabel' => 'Onweer'];
        }

        return ['icon' => 'cloudy', 'conditionLabel' => 'Bewolkt'];
    }

    /**
     * @param  array<string, mixed>|null  $frostData
     * @return array{tempLabel: string, feelsLikeLabel: string|null, icon: string|null, conditionLabel: string|null}|null
     */
    public function getOutdoorTempSummary(?array $frostData): ?array
    {
        if ($frostData === null || ! isset($frostData['currentTemp']) || ! is_numeric($frostData['currentTemp'])) {
            return null;
        }

        $currentTemp = (float) $frostData['currentTemp'];
        $hasApparent = isset($frostData['apparentTemp']) && is_numeric($frostData['apparentTemp']);
        $weatherCode = isset($frostData['weatherCode']) && is_numeric($frostData['weatherCode'])
            ? (int) $frostData['weatherCode']
            : null;
        $isDay = isset($frostData['isDay']) && is_numeric($frostData['isDay'])
            ? (int) $frostData['isDay']
            : 1;

        $mapped = $this->mapWeatherCodeToIcon($weatherCode, $isDay);

        return [
            'tempLabel' => $this->formatDutchTemp($currentTemp),
            'feelsLikeLabel' => $hasApparent ? $this->formatDutchTemp((float) $frostData['apparentTemp']) : null,
            'icon' => $mapped['icon'],
            'conditionLabel' => $mapped['conditionLabel'],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $points
     * @param  array{windowMinutes?: int, minMmPerHour?: float}  $options
     * @return array<string, mixed>|null
     */
    public function getActiveRainAlert(array $points, ?CarbonInterface $now = null, array $options = []): ?array
    {
        if ($points === []) {
            return null;
        }

        $nowDt = CarbonImmutable::instance($now ?? now());
        $windowMinutes = $options['windowMinutes'] ?? Setting::publicSettings()['rainWindowMinutes'];
        $minMmPerHour = $options['minMmPerHour'] ?? 0.1;

        $candidates = [];

        foreach ($points as $pt) {
            if (! is_array($pt) || ! isset($pt['time']) || ! is_string($pt['time'])) {
                continue;
            }

            $slotDate = $this->parseSlotDate($pt['time'], $nowDt);
            if ($slotDate === null) {
                continue;
            }

            $diffMinutes = ($slotDate->getTimestamp() - $nowDt->getTimestamp()) / 60;
            if ($diffMinutes < -5 || $diffMinutes > $windowMinutes) {
                continue;
            }

            $mmPerHour = isset($pt['mmPerHour']) && is_numeric($pt['mmPerHour'])
                ? (float) $pt['mmPerHour']
                : $this->buienradarValueToMm($pt['value'] ?? 0);

            $candidates[] = [
                'time' => substr($pt['time'], 0, 5),
                'diffMinutes' => $diffMinutes,
                'mmPerHour' => $mmPerHour,
            ];
        }

        $rainySlots = array_values(array_filter(
            $candidates,
            fn (array $c): bool => $c['mmPerHour'] >= $minMmPerHour
        ));

        if ($rainySlots === []) {
            return null;
        }

        usort($rainySlots, fn (array $a, array $b): int => $a['diffMinutes'] <=> $b['diffMinutes']);
        $firstRain = $rainySlots[0];
        $maxMmPerHour = max(array_column($rainySlots, 'mmPerHour'));

        $dayLabel = "Vanaf {$firstRain['time']}";
        if ($firstRain['diffMinutes'] <= 7) {
            $dayLabel = $maxMmPerHour >= 2.5 ? 'Harde regen' : 'Nu regen';
        }

        return [
            'kind' => 'rain',
            'type' => 'RAIN',
            'label' => 'Paraplu',
            'dayLabel' => $dayLabel,
            'maxMmPerHour' => $maxMmPerHour,
            'colorHex' => '#38bdf8',
            'textHex' => '#7dd3fc',
            'bgClass' => 'bg-sky-950/40 border border-sky-400/40 shadow-[0_0_20px_rgba(56,189,248,0.2)]',
        ];
    }

    /**
     * @param  array<string, mixed>|null  $frostData
     * @param  array{freezeThresholdC?: float, eveningPreviewHour?: int, morningCutoffHour?: int, thawTempC?: float}  $options
     * @return array<string, mixed>|null
     */
    public function getActiveFrostAlert(?array $frostData, ?CarbonInterface $now = null, array $options = []): ?array
    {
        if ($frostData === null) {
            return null;
        }

        $nowDt = CarbonImmutable::instance($now ?? now());
        $freezeThresholdC = $options['freezeThresholdC'] ?? 0.0;
        $eveningPreviewHour = $options['eveningPreviewHour'] ?? 17;
        $morningCutoffHour = $options['morningCutoffHour'] ?? 11;
        $thawTempC = $options['thawTempC'] ?? 5.0;

        $currentTemp = isset($frostData['currentTemp']) && is_numeric($frostData['currentTemp'])
            ? (float) $frostData['currentTemp']
            : null;

        $frostCardBase = [
            'kind' => 'frost',
            'type' => 'FROST',
            'label' => 'Krabben',
            'colorHex' => '#a5f3fc',
            'textHex' => '#cffafe',
            'bgClass' => 'bg-cyan-950/45 border border-cyan-200/50 shadow-[0_0_22px_rgba(165,243,252,0.22)]',
        ];

        // 1. Freezing right now
        if ($currentTemp !== null && $currentTemp <= $freezeThresholdC) {
            return [
                ...$frostCardBase,
                'dayLabel' => 'Nu '.$this->formatDutchTemp($currentTemp),
                'currentTemp' => $currentTemp,
            ];
        }

        $hourly = isset($frostData['hourly']) && is_array($frostData['hourly']) ? $frostData['hourly'] : [];
        if ($hourly === []) {
            return null;
        }

        $hour = $nowDt->hour;

        // 2. Evening before (from 17:00): look ahead to upcoming night through 10:00 tomorrow morning
        if ($hour >= $eveningPreviewHour) {
            $tonightStart = $nowDt->setTime($eveningPreviewHour, 0, 0);
            $tomorrowMorningEnd = $nowDt->addDay()->setTime(10, 0, 0);
            $minUpcomingNightTemp = $this->getMinTempBetween($hourly, $tonightStart, $tomorrowMorningEnd);

            if ($minUpcomingNightTemp !== null && $minUpcomingNightTemp <= $freezeThresholdC) {
                return [
                    ...$frostCardBase,
                    'dayLabel' => 'Vannacht '.$this->formatDutchTemp($minUpcomingNightTemp),
                    'minNightTemp' => $minUpcomingNightTemp,
                ];
            }
        }

        // 3. Morning (before 11:00 and while outdoor temp < 5°C): inspect past/current night
        if ($hour < $morningCutoffHour) {
            if ($currentTemp !== null && $currentTemp >= $thawTempC) {
                return null;
            }

            $lastEveningStart = $nowDt->subDay()->setTime($eveningPreviewHour, 0, 0);
            $thisMorningEnd = $nowDt->setTime(max($hour, 10), 0, 0);
            $minNightTemp = $this->getMinTempBetween($hourly, $lastEveningStart, $thisMorningEnd);

            if ($minNightTemp !== null && $minNightTemp <= $freezeThresholdC) {
                return [
                    ...$frostCardBase,
                    'dayLabel' => 'Vannacht '.$this->formatDutchTemp($minNightTemp),
                    'minNightTemp' => $minNightTemp,
                ];
            }
        }

        return null;
    }

    /**
     * @return array{source: string, points: array<int, array<string, mixed>>, frost: array<string, mixed>|null, windowMinutes: int}
     */
    public function fetchForecast(?CarbonInterface $now = null): array
    {
        $nowDt = CarbonImmutable::instance($now ?? now());
        $nowTs = $nowDt->getTimestamp();
        $windowMinutes = Setting::publicSettings()['rainWindowMinutes'];

        /** @var array<string, mixed>|null $cache */
        $cache = Cache::get(Setting::WEATHER_CACHE_KEY);

        $rainFresh = is_array($cache)
            && isset($cache['fetchedAt'], $cache['points'])
            && ($nowTs - (int) $cache['fetchedAt']) < 180;

        $frostFresh = is_array($cache)
            && isset($cache['frostFetchedAt'], $cache['frost'])
            && is_array($cache['frost'])
            && array_key_exists('apparentTemp', $cache['frost'])
            && array_key_exists('weatherCode', $cache['frost'])
            && ($nowTs - (int) $cache['frostFetchedAt']) < 900;

        if ($rainFresh && $frostFresh) {
            return [
                'source' => (string) ($cache['source'] ?? 'cache'),
                'points' => $cache['points'],
                'frost' => $cache['frost'],
                'windowMinutes' => $windowMinutes,
            ];
        }

        $latRaw = Setting::getValue('weather_lat');
        $lonRaw = Setting::getValue('weather_lon');
        $lat = is_numeric($latRaw) ? (float) $latRaw : null;
        $lon = is_numeric($lonRaw) ? (float) $lonRaw : null;
        $locFetchedAt = is_array($cache) ? (int) ($cache['locFetchedAt'] ?? 0) : 0;

        if ($lat === null || $lon === null) {
            if (is_array($cache) && isset($cache['lat'], $cache['lon']) && ($nowTs - $locFetchedAt) < 86400) {
                $lat = (float) $cache['lat'];
                $lon = (float) $cache['lon'];
            } else {
                $geo = $this->homey->getGeolocation();
                if ($geo !== null) {
                    $lat = $geo['lat'];
                    $lon = $geo['lon'];
                    $locFetchedAt = $nowTs;
                }
            }
        } else {
            $locFetchedAt = $nowTs;
        }

        if ($lat === null || $lon === null) {
            return [
                'source' => 'unavailable',
                'points' => [],
                'frost' => null,
                'windowMinutes' => $windowMinutes,
            ];
        }

        $latFormatted = number_format($lat, 4, '.', '');
        $lonFormatted = number_format($lon, 4, '.', '');

        $points = $rainFresh ? $cache['points'] : [];
        $source = $rainFresh ? (string) ($cache['source'] ?? 'buienradar') : 'buienradar';
        $rainFetchedAt = $rainFresh ? (int) $cache['fetchedAt'] : $nowTs;

        if (! $rainFresh) {
            $rawBr = $this->fetchExternal("https://gpsgadget.buienradar.nl/data/raintext/?lat={$latFormatted}&lon={$lonFormatted}");
            if ($rawBr !== null && trim($rawBr) !== '') {
                $lines = preg_split('/\r\n|\r|\n/', trim($rawBr)) ?: [];
                foreach ($lines as $line) {
                    $parts = explode('|', trim($line));
                    if (count($parts) === 2 && preg_match('/^\d{2}:\d{2}$/', $parts[1])) {
                        $val = (int) $parts[0];
                        $points[] = [
                            'time' => $parts[1],
                            'value' => $val,
                            'mmPerHour' => $this->buienradarValueToMm($val),
                        ];
                    }
                }
            }

            if ($points === []) {
                $omUrl = "https://api.open-meteo.com/v1/forecast?latitude={$latFormatted}&longitude={$lonFormatted}&minutely_15=precipitation&forecast_minutely_15=8&timezone=Europe%2FAmsterdam";
                $rawOm = $this->fetchExternal($omUrl);
                $omData = $rawOm !== null ? json_decode($rawOm, true) : null;
                $times = $omData['minutely_15']['time'] ?? [];
                $precips = $omData['minutely_15']['precipitation'] ?? [];

                if (is_array($times) && is_array($precips) && $times !== []) {
                    $source = 'open-meteo';
                    foreach ($times as $idx => $isoTime) {
                        $mm15 = (float) ($precips[$idx] ?? 0);
                        $timePart = substr((string) $isoTime, 11, 5);
                        if (preg_match('/^\d{2}:\d{2}$/', $timePart)) {
                            $points[] = [
                                'time' => $timePart,
                                'value' => 0,
                                'mmPerHour' => round($mm15 * 4, 2),
                            ];
                        }
                    }
                }
            }

            if ($points === [] && is_array($cache) && ! empty($cache['points'])) {
                $points = $cache['points'];
            }
        }

        $frost = $frostFresh ? $cache['frost'] : null;
        $frostFetchedAt = $frostFresh ? (int) $cache['frostFetchedAt'] : 0;

        if (! $frostFresh) {
            $tempUrl = "https://api.open-meteo.com/v1/forecast?latitude={$latFormatted}&longitude={$lonFormatted}&current=temperature_2m,apparent_temperature,weather_code,is_day&hourly=temperature_2m&past_days=1&forecast_days=2&timezone=Europe%2FAmsterdam";
            $rawTemp = $this->fetchExternal($tempUrl);
            $tempData = $rawTemp !== null ? json_decode($rawTemp, true) : null;

            if (is_array($tempData) && isset($tempData['current']['temperature_2m'])) {
                $hourlyList = [];
                $hTimes = $tempData['hourly']['time'] ?? [];
                $hTemps = $tempData['hourly']['temperature_2m'] ?? [];
                if (is_array($hTimes) && is_array($hTemps)) {
                    foreach ($hTimes as $idx => $iso) {
                        if (isset($hTemps[$idx]) && is_numeric($hTemps[$idx])) {
                            $hourlyList[] = [
                                'time' => (string) $iso,
                                'temp' => round((float) $hTemps[$idx], 1),
                            ];
                        }
                    }
                }

                $apparentRaw = $tempData['current']['apparent_temperature'] ?? null;
                $wCodeRaw = $tempData['current']['weather_code'] ?? null;
                $isDayRaw = $tempData['current']['is_day'] ?? null;

                $frost = [
                    'currentTemp' => round((float) $tempData['current']['temperature_2m'], 1),
                    'apparentTemp' => is_numeric($apparentRaw) ? round((float) $apparentRaw, 1) : null,
                    'weatherCode' => is_numeric($wCodeRaw) ? (int) $wCodeRaw : null,
                    'isDay' => is_numeric($isDayRaw) ? (int) $isDayRaw : 1,
                    'hourly' => $hourlyList,
                ];
                $frostFetchedAt = $nowTs;
            } else {
                $fallbackTemp = $this->homey->getFallbackWeatherTemp();
                if ($fallbackTemp !== null) {
                    $frost = [
                        'currentTemp' => $fallbackTemp,
                        'apparentTemp' => $cache['frost']['apparentTemp'] ?? null,
                        'weatherCode' => $cache['frost']['weatherCode'] ?? null,
                        'isDay' => $cache['frost']['isDay'] ?? (($nowDt->hour >= 7 && $nowDt->hour < 20) ? 1 : 0),
                        'hourly' => is_array($cache['frost']['hourly'] ?? null) ? $cache['frost']['hourly'] : [],
                    ];
                    $frostFetchedAt = $nowTs;
                } elseif (is_array($cache['frost'] ?? null)) {
                    $frost = $cache['frost'];
                    $frostFetchedAt = (int) ($cache['frostFetchedAt'] ?? 0);
                }
            }
        }

        if ($points !== [] || is_array($frost)) {
            Cache::put(Setting::WEATHER_CACHE_KEY, [
                'lat' => $lat,
                'lon' => $lon,
                'locFetchedAt' => $locFetchedAt,
                'fetchedAt' => $rainFetchedAt,
                'source' => $source,
                'points' => $points,
                'frostFetchedAt' => $frostFetchedAt,
                'frost' => $frost,
            ], now()->addDay());
        }

        return [
            'source' => $source,
            'points' => $points,
            'frost' => $frost,
            'windowMinutes' => $windowMinutes,
        ];
    }

    protected function fetchExternal(string $url): ?string
    {
        try {
            $response = Http::withoutVerifying()
                ->connectTimeout(2)
                ->timeout(3)
                ->withHeaders([
                    'User-Agent' => 'HeimdallNSPanel/2.0',
                    'Accept' => 'text/plain, application/json',
                ])
                ->get($url);

            return $response->successful() ? $response->body() : null;
        } catch (ConnectionException) {
            return null;
        }
    }

    protected function parseSlotDate(string $timeStr, CarbonImmutable $now): ?CarbonImmutable
    {
        $parts = explode(':', trim($timeStr));
        if (count($parts) < 2 || ! is_numeric($parts[0]) || ! is_numeric($parts[1])) {
            return null;
        }

        $slotDate = $now->setTime((int) $parts[0], (int) $parts[1], 0);
        $diffSeconds = $slotDate->getTimestamp() - $now->getTimestamp();

        if ($diffSeconds < -6 * 3600) {
            $slotDate = $slotDate->addDay();
        } elseif ($diffSeconds > 18 * 3600) {
            $slotDate = $slotDate->subDay();
        }

        return $slotDate;
    }

    /**
     * @param  array<int, array<string, mixed>>  $hourly
     */
    protected function getMinTempBetween(array $hourly, CarbonImmutable $start, CarbonImmutable $end): ?float
    {
        $startTs = $start->getTimestamp();
        $endTs = $end->getTimestamp();
        $temps = [];

        foreach ($hourly as $item) {
            if (! is_array($item) || ! isset($item['temp'], $item['time']) || ! is_numeric($item['temp'])) {
                continue;
            }
            if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/', (string) $item['time'], $m)) {
                continue;
            }
            $dt = CarbonImmutable::create(
                (int) $m[1],
                (int) $m[2],
                (int) $m[3],
                (int) $m[4],
                (int) $m[5],
                0,
                $start->getTimezone()
            );
            if ($dt === null) {
                continue;
            }
            $ts = $dt->getTimestamp();
            if ($ts >= $startTs && $ts <= $endTs) {
                $temps[] = (float) $item['temp'];
            }
        }

        return $temps !== [] ? min($temps) : null;
    }
}
