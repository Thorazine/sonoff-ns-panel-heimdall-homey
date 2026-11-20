<?php

use App\Livewire\Kiosk\AllOffScreen;
use App\Livewire\Kiosk\GymScreen;
use App\Livewire\Kiosk\HubScreen;
use App\Livewire\Kiosk\MenuScreen;
use App\Livewire\Kiosk\StartScreen;
use App\Models\ActivityLog;
use App\Models\KioskMenu;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function (): void {
    Cache::flush();
    Setting::setMany([
        'homey_base_url' => 'http://192.168.1.50',
        'homey_api_key' => 'test-token',
        'weather_lat' => '52.3676',
        'weather_lon' => '4.9041',
    ]);
});

test('T24: StartScreen renders outdoor weather + active alert cards, navigates to /hub, and redirects ?alarm=true', function (): void {
    Carbon::setTestNow(Carbon::create(2026, 11, 8, 18, 0, 0, 'Europe/Amsterdam'));

    Http::fake([
        'https://gpsgadget.buienradar.nl/*' => Http::response("000|18:00\n109|18:30\n", 200),
        'https://api.open-meteo.com/*' => Http::response([
            'current' => [
                'temperature_2m' => 3.3,
                'apparent_temperature' => -0.2,
                'weather_code' => 3,
                'is_day' => 0,
            ],
            'hourly' => [
                'time' => ['2026-11-09T04:00'],
                'temperature_2m' => [-1.5],
            ],
        ], 200),
        'http://192.168.1.50/api/app/com.trashchecker/trashcollectiondays' => Http::response([
            ['type' => 'REST', 'localText' => 'Restafval', 'dates' => ['2026-11-09T00:00:00.000Z']],
        ], 200),
    ]);

    $component = Livewire::test(StartScreen::class);

    // Initial SSR page load makes zero blocking HTTP requests (loads via wire:init)
    Http::assertNothingSent();

    $component->call('refreshDashboard')
        ->assertSee('3,3°C')
        ->assertSee('Gevoel -0,2°C')
        ->assertSee('Krabben')
        ->assertSee('Paraplu')
        ->assertSee('Rest')
        ->call('openHub')
        ->assertRedirect('/hub');

    $this->get('/?alarm=true')
        ->assertRedirect('/entry?alarm=true');

    Carbon::setTestNow();
});

test('T25: StartScreen shows setup prompt pointing to /admin when Homey is not configured', function (): void {
    Setting::query()->delete();
    Cache::flush();

    Livewire::test(StartScreen::class)
        ->assertSet('isConfigured', false)
        ->assertSee('Setup Vereist')
        ->assertSee('/admin');
});

test('T26: HubScreen Alarm tile routes to /menu when disarmed and /entry when armed', function (): void {
    Http::fakeSequence()
        ->push(['hd' => ['driverId' => 'surveillancemodeswitch']], 200)
        ->push(['capabilitiesObj' => ['homealarm_state' => ['value' => 'disarmed']]], 200)
        ->push(['hd' => ['driverId' => 'surveillancemodeswitch']], 200)
        ->push(['capabilitiesObj' => ['homealarm_state' => ['value' => 'armed']]], 200);

    Livewire::test(HubScreen::class)
        ->call('openAlarm')
        ->assertRedirect('/menu');

    Livewire::test(HubScreen::class)
        ->call('openAlarm')
        ->assertRedirect('/entry');
});

test('T27: HubScreen routes Alles Uit module block to AllOffScreen which confirms and triggers Alles Uit flow', function (): void {
    Setting::setValue('all_off_flow_id', 'flow-alles-uit');

    Http::fake([
        'http://192.168.1.50/api/manager/flow/flow/flow-alles-uit/trigger' => Http::response([], 200),
    ]);

    $root = KioskMenu::ensureRootExists();
    $allOffBlock = $root->blocks->firstWhere('position', 3);

    Livewire::test(HubScreen::class)
        ->call('activateBlock', $allOffBlock->id)
        ->assertRedirect('/all-off');

    Livewire::test(AllOffScreen::class)
        ->assertSee('ALLES UITZETTEN?')
        ->call('confirmAllOff')
        ->assertSet('feedbackMsg', 'ALLES UITGESCHAKELD')
        ->assertDispatched('navigate-after-delay', url: '/');

    expect(ActivityLog::query()->where('message', 'Alles Uit flow getriggerd')->exists())->toBeTrue();
});

test('T28: GymScreen triggers gym_on and gym_off flows and reports unlinked flows gracefully', function (): void {
    Setting::setMany([
        'gym_on_flow_id' => 'flow-gym-on',
        'gym_off_flow_id' => '',
    ]);

    Http::fake([
        'http://192.168.1.50/api/manager/flow/flow/flow-gym-on/trigger' => Http::response([], 200),
    ]);

    Livewire::test(GymScreen::class)
        ->call('triggerGym', 'on')
        ->assertSet('feedbackMsg', 'GYM INGESCHAKELD')
        ->call('triggerGym', 'off')
        ->assertSet('feedbackMsg', 'FLOW NOG NIET GEKOPPELD');
});

test('T29: MenuScreen warns on open sensors with Accepteer/Annuleer before proceeding to EntryScreen', function (): void {
    Http::fakeSequence()
        ->push([
            's-1' => ['name' => 'Achterdeur', 'zone' => 'z-1', 'capabilitiesObj' => ['alarm_contact' => ['value' => true]]],
        ], 200)
        ->push(['z-1' => ['name' => 'Keuken']], 200)
        ->push([], 200)
        ->push([], 200);

    Livewire::test(MenuScreen::class)
        ->call('selectMode', 'armed')
        ->assertSet('openSensors', ['Keuken - Achterdeur'])
        ->assertSee('OPEN RAMEN / DEUREN')
        ->assertSee('Keuken - Achterdeur')
        ->call('acceptAndProceed')
        ->assertRedirect('/entry?mode=armed');

    Livewire::test(MenuScreen::class)
        ->call('selectMode', 'partially_armed')
        ->assertRedirect('/entry?mode=partially_armed');
});
