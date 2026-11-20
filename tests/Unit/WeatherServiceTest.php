<?php

use App\Models\Setting;
use App\Services\WeatherService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

test('T01: converts Buienradar 0-255 values accurately to mm/h', function (): void {
    $weather = app(WeatherService::class);

    expect($weather->buienradarValueToMm(0))->toBe(0.0)
        ->and($weather->buienradarValueToMm(77))->toBe(0.1)
        ->and($weather->buienradarValueToMm(109))->toBe(1.0);
});

test('T02: formats outdoor temperature with Dutch decimals and maps WMO day/night weather codes', function (): void {
    $weather = app(WeatherService::class);

    expect($weather->formatDutchTemp(-0.02))->toBe('0,0°C')
        ->and($weather->getOutdoorTempSummary([
            'currentTemp' => 3.3,
            'apparentTemp' => -0.2,
            'weatherCode' => 3,
            'isDay' => 0,
        ]))->toBe([
            'tempLabel' => '3,3°C',
            'feelsLikeLabel' => '-0,2°C',
            'icon' => 'cloudy',
            'conditionLabel' => 'Bewolkt',
        ])
        ->and($weather->getOutdoorTempSummary(null))->toBeNull();

    expect($weather->mapWeatherCodeToIcon(0, 1))->toBe(['icon' => 'clear-day', 'conditionLabel' => 'Zonnig'])
        ->and($weather->mapWeatherCodeToIcon(0, 0))->toBe(['icon' => 'clear-night', 'conditionLabel' => 'Helder'])
        ->and($weather->mapWeatherCodeToIcon(2, 1))->toBe(['icon' => 'partly-cloudy-day', 'conditionLabel' => 'Halfbewolkt'])
        ->and($weather->mapWeatherCodeToIcon(2, 0))->toBe(['icon' => 'partly-cloudy-night', 'conditionLabel' => 'Halfbewolkt'])
        ->and($weather->mapWeatherCodeToIcon(45, 1))->toBe(['icon' => 'fog', 'conditionLabel' => 'Mist'])
        ->and($weather->mapWeatherCodeToIcon(61, 1))->toBe(['icon' => 'rain', 'conditionLabel' => 'Regen'])
        ->and($weather->mapWeatherCodeToIcon(71, 0))->toBe(['icon' => 'snow', 'conditionLabel' => 'Sneeuw'])
        ->and($weather->mapWeatherCodeToIcon(95, 0))->toBe(['icon' => 'thunder', 'conditionLabel' => 'Onweer']);
});

test('T03: returns null when rain forecast is dry or below drizzle threshold', function (): void {
    $weather = app(WeatherService::class);
    $now = CarbonImmutable::create(2026, 11, 10, 18, 0, 0);

    $dryPoints = [
        ['time' => '18:00', 'value' => 0, 'mmPerHour' => 0],
        ['time' => '18:30', 'value' => 40, 'mmPerHour' => 0.01],
        ['time' => '19:00', 'value' => 0, 'mmPerHour' => 0],
    ];

    expect($weather->getActiveRainAlert($dryPoints, $now, ['windowMinutes' => 75]))->toBeNull();
});

test('T04: returns Paraplu with Vanaf HH:MM within lookahead window including midnight wrap-around', function (): void {
    $weather = app(WeatherService::class);

    $evening = CarbonImmutable::create(2026, 11, 10, 18, 0, 0);
    $points = [
        ['time' => '18:00', 'value' => 0, 'mmPerHour' => 0],
        ['time' => '18:35', 'value' => 109, 'mmPerHour' => 1.0],
    ];

    $alert = $weather->getActiveRainAlert($points, $evening, ['windowMinutes' => 75]);
    expect($alert)->not->toBeNull()
        ->and($alert['kind'])->toBe('rain')
        ->and($alert['label'])->toBe('Paraplu')
        ->and($alert['dayLabel'])->toBe('Vanaf 18:35');

    $lateNight = CarbonImmutable::create(2026, 11, 10, 23, 30, 0);
    expect($weather->getActiveRainAlert([['time' => '01:00', 'value' => 110, 'mmPerHour' => 1.07]], $lateNight, ['windowMinutes' => 75]))->toBeNull();

    $midnightAlert = $weather->getActiveRainAlert([['time' => '00:15', 'value' => 110, 'mmPerHour' => 1.07]], $lateNight, ['windowMinutes' => 75]);
    expect($midnightAlert)->not->toBeNull()
        ->and($midnightAlert['dayLabel'])->toBe('Vanaf 00:15');
});

test('T05: returns Nu regen or Harde regen when raining within 7 minutes', function (): void {
    $weather = app(WeatherService::class);
    $now = CarbonImmutable::create(2026, 11, 18, 12, 0, 0);

    $lightRain = [['time' => '12:05', 'value' => 95, 'mmPerHour' => 0.36]];
    expect($weather->getActiveRainAlert($lightRain, $now)['dayLabel'])->toBe('Nu regen');

    $heavyRain = [['time' => '12:05', 'value' => 130, 'mmPerHour' => 4.5]];
    expect($weather->getActiveRainAlert($heavyRain, $now)['dayLabel'])->toBe('Harde regen');
});

test('T06: shows Krabben with Nu -X,X°C whenever currentTemp <= 0 regardless of time of day', function (): void {
    $weather = app(WeatherService::class);
    $afternoon = CarbonImmutable::create(2026, 11, 10, 14, 30, 0);

    $alert = $weather->getActiveFrostAlert(['currentTemp' => -1.8, 'hourly' => []], $afternoon);
    expect($alert)->not->toBeNull()
        ->and($alert['kind'])->toBe('frost')
        ->and($alert['label'])->toBe('Krabben')
        ->and($alert['dayLabel'])->toBe('Nu -1,8°C');
});

test('T07: shows Krabben from 17:00 the evening before when overnight frost is forecast', function (): void {
    $weather = app(WeatherService::class);
    $upcomingFreezeHourly = [
        ['time' => '2026-11-10T18:00', 'temp' => 4.2],
        ['time' => '2026-11-11T02:00', 'temp' => -0.5],
        ['time' => '2026-11-11T06:00', 'temp' => -1.9],
        ['time' => '2026-11-11T09:00', 'temp' => 1.1],
    ];

    $before17 = CarbonImmutable::create(2026, 11, 10, 16, 30, 0);
    expect($weather->getActiveFrostAlert(['currentTemp' => 5.0, 'hourly' => $upcomingFreezeHourly], $before17))->toBeNull();

    $after17 = CarbonImmutable::create(2026, 11, 10, 18, 30, 0);
    $alert = $weather->getActiveFrostAlert(['currentTemp' => 4.2, 'hourly' => $upcomingFreezeHourly], $after17);
    expect($alert)->not->toBeNull()
        ->and($alert['dayLabel'])->toBe('Vannacht -1,9°C');
});

test('T08: shows Krabben in the morning after overnight frost until 11:00 or thaw >= 5°C', function (): void {
    $weather = app(WeatherService::class);
    $nightHourly = [
        ['time' => '2026-11-09T21:00', 'temp' => 1.2],
        ['time' => '2026-11-10T04:00', 'temp' => -2.4],
        ['time' => '2026-11-10T08:00', 'temp' => 1.5],
    ];

    $morning = CarbonImmutable::create(2026, 11, 10, 8, 30, 0);
    $alert = $weather->getActiveFrostAlert(['currentTemp' => 1.6, 'hourly' => $nightHourly], $morning);
    expect($alert)->not->toBeNull()
        ->and($alert['dayLabel'])->toBe('Vannacht -2,4°C');

    $after11 = CarbonImmutable::create(2026, 11, 10, 11, 15, 0);
    expect($weather->getActiveFrostAlert(['currentTemp' => 2.5, 'hourly' => $nightHourly], $after11))->toBeNull();

    $thawedMorning = CarbonImmutable::create(2026, 11, 10, 9, 30, 0);
    expect($weather->getActiveFrostAlert(['currentTemp' => 5.5, 'hourly' => $nightHourly], $thawedMorning))->toBeNull();
});

test('T09: fetches live forecast via Http with Open-Meteo fallback and caches result', function (): void {
    Cache::flush();
    Setting::setMany([
        'homey_base_url' => 'http://192.168.1.50',
        'homey_api_key' => 'secret-token',
        'weather_lat' => '52.3676',
        'weather_lon' => '4.9041',
        'rain_window_minutes' => 90,
    ]);

    Http::fake([
        'https://gpsgadget.buienradar.nl/*' => Http::response('', 503),
        'https://api.open-meteo.com/v1/forecast*minutely_15*' => Http::response([
            'minutely_15' => [
                'time' => ['2026-11-14T11:00', '2026-11-14T11:15'],
                'precipitation' => [0.0, 0.5],
            ],
        ], 200),
        'https://api.open-meteo.com/v1/forecast*current=temperature_2m*' => Http::response([
            'current' => [
                'temperature_2m' => 8.4,
                'apparent_temperature' => 5.9,
                'weather_code' => 2,
                'is_day' => 1,
            ],
            'hourly' => [
                'time' => ['2026-11-14T10:00'],
                'temperature_2m' => [7.8],
            ],
        ], 200),
    ]);

    $weather = app(WeatherService::class);
    $now = CarbonImmutable::create(2026, 11, 14, 11, 0, 0);

    $result = $weather->fetchForecast($now);

    expect($result['source'])->toBe('open-meteo')
        ->and($result['windowMinutes'])->toBe(90)
        ->and($result['points'])->toHaveCount(2)
        ->and($result['points'][1]['mmPerHour'])->toBe(2.0)
        ->and($result['frost']['currentTemp'])->toBe(8.4)
        ->and(Cache::has(Setting::WEATHER_CACHE_KEY))->toBeTrue();
});
