<?php

use App\Enums\CameraRole;
use App\Enums\KioskModule;
use App\Enums\MenuBlockType;
use App\Filament\Pages\ManageCameras;
use App\Filament\Pages\WallPanelBuilder;
use App\Livewire\Kiosk\WallPanelScreen;
use App\Models\KioskMenu;
use App\Models\ReolinkCamera;
use App\Models\Setting;
use App\Models\User;
use App\Models\WallPanel;
use App\Services\DoorbellService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->admin = User::factory()->create([
        'name' => 'Heimdall Beheerder',
        'email' => 'admin@heimdall.local',
    ]);

    Setting::setMany([
        'homey_base_url' => 'http://192.168.1.50',
        'homey_api_key' => 'test-token',
    ]);
});

test('WallPanelBuilder manages independent 8-block menus per large screen without affecting small NS Panels', function (): void {
    $this->actingAs($this->admin);

    $nsRoot = KioskMenu::ensureRootExists();
    $builder = Livewire::test(WallPanelBuilder::class);

    $panels = WallPanel::query()->orderBy('id')->get();
    expect($panels)->toHaveCount(2)
        ->and($panels[0]->slug)->toBe('eerste-verdieping')
        ->and($panels[1]->slug)->toBe('zolder');

    $firstFloor = $panels[0];
    $attic = $panels[1];

    expect($firstFloor->ensureRootMenuExists()->blocks)->toHaveCount(8)
        ->and($attic->ensureRootMenuExists()->blocks)->toHaveCount(8)
        ->and($nsRoot->blocks)->toHaveCount(4);

    // Build an 8-block submenu on Eerste Verdieping (position 8 -> 8 blocks -> Sauna module)
    $builder->call('selectPanel', $firstFloor->id)
        ->call('selectBlock', 8)
        ->set('blockType', MenuBlockType::Submenu->value)
        ->set('blockLabel', 'WELLNESS BOVEN')
        ->call('saveAndOpenSubmenu')
        ->assertHasNoErrors();

    $subMenuId = $builder->get('currentMenuId');
    $subMenu = KioskMenu::query()->with('blocks')->find($subMenuId);

    expect($subMenu)->not->toBeNull()
        ->and($subMenu->wall_panel_id)->toBe($firstFloor->id)
        ->and($subMenu->grid_size)->toBe(8)
        ->and($subMenu->blocks)->toHaveCount(8);

    $builder->call('selectBlock', 5)
        ->set('blockType', MenuBlockType::Module->value)
        ->set('blockModuleKey', KioskModule::Sauna->value)
        ->set('blockLabel', 'SAUNA BOVEN')
        ->call('saveBlock')
        ->assertHasNoErrors();

    // Zolder menu remains completely independent
    $atticBlock8 = $attic->ensureRootMenuExists()->blocks->firstWhere('position', 8);
    expect($atticBlock8->type)->toBe(MenuBlockType::Empty);

    // Resetting the small NS Panel menu does NOT delete WallPanel menus
    KioskMenu::resetToDefault();
    expect(KioskMenu::query()->find($subMenuId))->not->toBeNull();

    // Create a third large panel (e.g. Woonkeuken)
    $builder->set('newPanelName', 'Woonkeuken')
        ->set('newPanelLocation', 'Begane grond')
        ->call('createPanel')
        ->assertHasNoErrors();

    $kitchenPanel = WallPanel::query()->where('slug', 'woonkeuken')->first();
    expect($kitchenPanel)->not->toBeNull()
        ->and($kitchenPanel->ensureRootMenuExists()->blocks)->toHaveCount(8);
});

test('ManageCameras configures 5 Reolink cameras with go2rtc WebRTC URLs, two-way audio, and test doorbell trigger', function (): void {
    $this->actingAs($this->admin);

    $page = Livewire::test(ManageCameras::class);

    $cameras = ReolinkCamera::doorbellSet();
    expect($cameras)->toHaveCount(5)
        ->and($cameras->first()->role)->toBe(CameraRole::Doorbell)
        ->and($cameras->first()->supports_two_way_audio)->toBeTrue()
        ->and($cameras->where('role', CameraRole::Garden))->toHaveCount(4);

    $doorbellCam = $cameras->first();

    $page->set('go2rtcBaseUrl', 'http://192.168.1.60:1984/')
        ->set('doorbellTimeoutSeconds', 75)
        ->set('doorbellActionFlowId', 'flow-open-poort')
        ->set('doorbellActionLabel', 'POORT OPENEN')
        ->set("cameras.{$doorbellCam->id}.go2rtc_stream", 'reolink_deurbel')
        ->call('saveAll')
        ->assertHasNoErrors();

    $doorbellCam->refresh();
    $payload = $doorbellCam->toFrontendPayload();

    expect($payload['webrtcUrl'])->toBe('http://192.168.1.60:1984/api/webrtc?src=reolink_deurbel')
        ->and($payload['snapshotUrl'])->toBe('http://192.168.1.60:1984/api/frame.jpeg?src=reolink_deurbel')
        ->and($payload['supportsTwoWayAudio'])->toBeTrue();

    $page->call('triggerTestDoorbell');

    $activeEvent = app(DoorbellService::class)->activeEvent('eerste-verdieping');
    expect($activeEvent)->not->toBeNull()
        ->and($activeEvent['remaining_seconds'])->toBeGreaterThan(60);
});

test('Doorbell webhook rejects invalid tokens and activates 5-camera state on valid token', function (): void {
    $token = Setting::ensureDoorbellWebhookToken();

    $this->postJson('/api/webhook/doorbell', ['token' => 'wrong-token'])
        ->assertStatus(401)
        ->assertJson(['ok' => false]);

    expect(app(DoorbellService::class)->activeEvent())->toBeNull();

    $this->postJson('/api/webhook/doorbell?token='.$token)
        ->assertOk()
        ->assertJson(['ok' => true]);

    expect(app(DoorbellService::class)->activeEvent('eerste-verdieping'))->not->toBeNull()
        ->and(app(DoorbellService::class)->activeEvent('zolder'))->not->toBeNull();
});

test('WallPanelScreen renders black standby screen with Krabben/Paraplu/Kliko alerts, wakes to 8-block menu, sleeps on inactivity, and wakes on doorbell ring', function (): void {
    Carbon::setTestNow(Carbon::create(2026, 11, 8, 18, 0, 0, 'Europe/Amsterdam'));
    Cache::flush();

    Http::fake([
        'https://gpsgadget.buienradar.nl/*' => Http::response("000|18:00\n109|18:30\n", 200),
        'https://api.open-meteo.com/*' => Http::response([
            'current' => [
                'temperature_2m' => 2.4,
                'apparent_temperature' => -1.1,
                'weather_code' => 3,
                'is_day' => 0,
            ],
            'hourly' => [
                'time' => ['2026-11-09T04:00'],
                'temperature_2m' => [-2.0],
            ],
        ], 200),
        'http://192.168.1.50/api/app/com.trashchecker/trashcollectiondays' => Http::response([
            ['type' => 'REST', 'localText' => 'Restafval', 'dates' => ['2026-11-09T00:00:00.000Z']],
        ], 200),
        'http://192.168.1.50/api/manager/flow/flow/flow-voordeur/trigger' => Http::response([], 200),
        'http://192.168.1.50/*' => Http::response([], 200),
    ]);

    Setting::setMany([
        'weather_lat' => '52.3676',
        'weather_lon' => '4.9041',
        'go2rtc_base_url' => 'http://192.168.1.60:1984',
        'doorbell_action_flow_id' => 'flow-voordeur',
        'doorbell_action_label' => 'OPEN VOORDEUR',
    ]);

    $panels = WallPanel::ensureDefaultsExist();
    /** @var WallPanel $firstFloor */
    $firstFloor = $panels->firstWhere('slug', 'eerste-verdieping');
    /** @var WallPanel $attic */
    $attic = $panels->firstWhere('slug', 'zolder');

    // Configure Zolder to NOT auto-popup on doorbell ring while Eerste Verdieping DOES
    $attic->update(['show_doorbell_popup' => false]);

    // Visiting wall panel HTTP route starts in black standby mode, logs out admin, and contains no /admin link
    $this->actingAs($this->admin);
    $response = $this->get('/panel/eerste-verdieping');
    $response->assertOk()
        ->assertSee('Tik voor bediening')
        ->assertDontSee('/admin');
    $this->assertGuest();

    // Standby screen renders outdoor weather + Krabben, Paraplu, and Kliko (Rest) cards; tapping wakes 8-block menu
    $cameras = ReolinkCamera::doorbellSet();
    $gardenCam2 = $cameras->values()[2];

    $screen = Livewire::test(WallPanelScreen::class, ['wallPanel' => $firstFloor])
        ->assertSet('isAsleep', true)
        ->call('refreshSidebar')
        ->assertSee('2,4°C')
        ->assertSee('Gevoel -1,1°C')
        ->assertSee('Krabben')
        ->assertSee('Paraplu')
        ->assertSee('Rest')
        ->call('wakeUp')
        ->assertSet('isAsleep', false)
        ->assertSee('Eerste Verdieping')
        ->assertSee('CAMERA\'S');

    // Open Cameras module: shows all 5 cameras + 2-way audio button
    $screen->call('openModuleByKey', KioskModule::Cameras->value)
        ->assertSet('module', 'cameras')
        ->assertSee('Deurbel Voordeur')
        ->assertSee('Tuin Camera 1 (Voorzijde)')
        ->assertSee('Tuin Camera 2 (Oprit / Zijtuin)')
        ->assertSee('Tuin Camera 3 (Terras)')
        ->assertSee('Tuin Camera 4 (Achtertuin)')
        ->assertSee('Start 2-Weg Audio');

    // Inactivity puts the large panel back to black standby screen and resets open module
    $screen->call('enterSleepMode')
        ->assertSet('isAsleep', true)
        ->assertSet('module', null)
        ->assertSee('Krabben');

    // Ring the doorbell while asleep -> Eerste Verdieping immediately wakes into 5-camera doorbell view
    app(DoorbellService::class)->ring(source: 'homey-webhook');

    $screen->call('checkDoorbell')
        ->assertSet('doorbellActive', true)
        ->assertDispatched('kiosk-sound', type: 'doorbell')
        ->assertSee('ER WORDT AANGEBELD')
        ->assertSee('OPEN VOORDEUR');

    // While doorbell is active, inactivity timer does not blank the 5-camera intercom
    $screen->call('enterSleepMode')
        ->assertSet('doorbellActive', true)
        ->assertSee('ER WORDT AANGEBELD');

    // Swap a garden camera into the large Hero slot while keeping all 5 cameras visible
    $screen->call('focusCamera', $gardenCam2->id)
        ->assertSet('focusedCameraId', $gardenCam2->id)
        ->assertSee('Deurbel Voordeur')
        ->assertSee('Tuin Camera 2 (Oprit / Zijtuin)');

    // Trigger Homey door-open flow directly from doorbell screen, then dismiss popup (returns to black standby)
    $screen->call('triggerDoorbellAction')
        ->assertSet('feedbackMsg', 'OPEN VOORDEUR GEACTIVEERD')
        ->call('dismissDoorbell')
        ->assertSet('doorbellActive', false)
        ->assertSet('isAsleep', true);

    // Zolder has show_doorbell_popup = false, so doorbell takeover stays inactive there
    Livewire::test(WallPanelScreen::class, ['wallPanel' => $attic])
        ->call('checkDoorbell')
        ->assertSet('doorbellActive', false)
        ->assertDontSee('ER WORDT AANGEBELD');

    // Inactive wall panel returns 404
    $attic->update(['is_active' => false]);
    $this->get('/panel/zolder')->assertNotFound();

    Carbon::setTestNow();
});
