<?php

use App\Enums\KioskModule;
use App\Enums\MenuBlockColor;
use App\Enums\MenuBlockIcon;
use App\Enums\MenuBlockType;
use App\Filament\Pages\MenuBuilder;
use App\Filament\Pages\ModulesOverview;
use App\Livewire\Kiosk\AllOffScreen;
use App\Livewire\Kiosk\GymScreen;
use App\Livewire\Kiosk\HubScreen;
use App\Livewire\Kiosk\MenuScreen;
use App\Livewire\Kiosk\ModuleScreen;
use App\Models\ActivityLog;
use App\Models\KioskMenu;
use App\Models\KioskMenuBlock;
use App\Models\Setting;
use App\Models\User;
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

test('MenuBuilder builds recursive 4-block submenus down to a leaf module and cleans up orphaned subtrees', function (): void {
    $this->actingAs($this->admin);

    $builder = Livewire::test(MenuBuilder::class);
    $root = KioskMenu::ensureRootExists();

    expect($root->blocks)->toHaveCount(4)
        ->and($root->blocks->firstWhere('position', 1)->module_key)->toBe(KioskModule::Alarm)
        ->and($root->blocks->firstWhere('position', 4)->type)->toBe(MenuBlockType::Empty);

    // Validation: submenu requires a button label
    $builder->call('selectBlock', 4)
        ->set('blockType', MenuBlockType::Submenu->value)
        ->set('blockLabel', '')
        ->call('saveBlock')
        ->assertHasErrors(['blockLabel']);

    // Create Level 1 submenu on block 4 and open its 4 blocks immediately
    $builder->set('blockLabel', 'VERDIEPING 1')
        ->set('blockIcon', MenuBlockIcon::Home->value)
        ->set('blockColor', MenuBlockColor::Sky->value)
        ->call('saveAndOpenSubmenu')
        ->assertHasNoErrors();

    $level1MenuId = $builder->get('currentMenuId');
    expect($level1MenuId)->not->toBe($root->id);

    $level1Menu = KioskMenu::query()->with('blocks')->find($level1MenuId);
    expect($level1Menu)->not->toBeNull()
        ->and($level1Menu->blocks)->toHaveCount(4)
        ->and($level1Menu->title)->toBe('VERDIEPING 1');

    // Inside Level 1 (4 blocks), configure block 2 as Level 2 submenu (another 4 blocks)
    $builder->call('selectBlock', 2)
        ->set('blockType', MenuBlockType::Submenu->value)
        ->set('blockLabel', 'FITNESS RUIMTE')
        ->call('saveAndOpenSubmenu')
        ->assertHasNoErrors();

    $level2MenuId = $builder->get('currentMenuId');
    expect($level2MenuId)->not->toBe($level1MenuId);

    // Inside Level 2 (4 blocks), link the Gym module to block 1
    $builder->call('selectBlock', 1)
        ->set('blockType', MenuBlockType::Module->value)
        ->set('blockModuleKey', KioskModule::Gym->value)
        ->set('blockLabel', 'SPORTEN')
        ->call('saveBlock')
        ->assertHasNoErrors();

    $leafBlock = KioskMenuBlock::query()
        ->where('kiosk_menu_id', $level2MenuId)
        ->where('position', 1)
        ->first();

    expect($leafBlock->type)->toBe(MenuBlockType::Module)
        ->and($leafBlock->module_key)->toBe(KioskModule::Gym)
        ->and($leafBlock->resolvedLabel())->toBe('SPORTEN');

    // Swap block 1 with block 3 inside Level 2
    $builder->call('swapWithPosition', 3);

    $swappedBlock = KioskMenuBlock::query()
        ->where('kiosk_menu_id', $level2MenuId)
        ->where('position', 3)
        ->first();

    expect($swappedBlock->type)->toBe(MenuBlockType::Module)
        ->and($swappedBlock->module_key)->toBe(KioskModule::Gym);

    // Navigate back up to root and clear block 4 -> deletes both Level 1 and Level 2 submenus
    $builder->call('openMenu', $root->id)
        ->call('selectBlock', 4)
        ->call('clearSelectedBlock');

    expect(KioskMenu::query()->find($level1MenuId))->toBeNull()
        ->and(KioskMenu::query()->find($level2MenuId))->toBeNull();
});

test('ModulesOverview displays module placements in the menu tree and saves module-specific options', function (): void {
    Http::fake([
        'http://192.168.1.50/api/manager/flow/*' => Http::response([
            'flow-gym-aan' => ['name' => 'Gym Inschakelen', 'enabled' => true],
            'flow-gym-uit' => ['name' => 'Gym Uitschakelen', 'enabled' => true],
        ], 200),
    ]);

    $this->actingAs($this->admin);

    Livewire::test(ModulesOverview::class)
        ->assertSee('Alarm (Heimdall)')
        ->assertSee('Gym (Aan/Uit Schakelaar)')
        ->assertSee('Alles Uit / Flow Actie')
        ->assertSee('Hoofdmenu (Vak 1)')
        ->call('loadFlowOptions')
        ->fillForm([
            'countdown_seconds' => 25,
            'alarm_countdown_seconds' => 18,
            'max_failed_attempts' => 4,
            'lockout_seconds' => 90,
            'alarm_check_open_sensors' => false,
            'gym_on_flow_id' => 'flow-gym-aan',
            'gym_off_flow_id' => 'flow-gym-uit',
            'gym_on_label' => 'KRACHT AAN',
            'gym_off_label' => 'KRACHT UIT',
            'gym_auto_return' => false,
            'all_off_flow_id' => '__lights_off__',
            'all_off_title' => 'LICHTEN DOVEN',
            'all_off_subtitle' => 'Alle lampen direct uitschakelen?',
            'all_off_confirm_label' => 'Nu doven',
            'all_off_require_confirmation' => false,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified('Module-opties opgeslagen');

    expect(Setting::alarmModuleOptions())->toMatchArray([
        'countdownSeconds' => 25,
        'alarmCountdownSeconds' => 18,
        'maxFailedAttempts' => 4,
        'lockoutSeconds' => 90,
        'checkOpenSensors' => false,
    ])->and(Setting::gymModuleOptions())->toMatchArray([
        'onFlowId' => 'flow-gym-aan',
        'offFlowId' => 'flow-gym-uit',
        'onLabel' => 'KRACHT AAN',
        'offLabel' => 'KRACHT UIT',
        'autoReturn' => false,
    ])->and(Setting::allOffModuleOptions())->toMatchArray([
        'flowId' => '__lights_off__',
        'title' => 'LICHTEN DOVEN',
        'requireConfirmation' => false,
    ]);
});

test('Kiosk navigates 4-block submenus recursively, opens leaf modules, and respects module options', function (): void {
    $root = KioskMenu::ensureRootExists();
    $sub1 = KioskMenu::createEmptySubmenu('BEGANE GROND');
    $sub2 = KioskMenu::createEmptySubmenu('ACHTERTUIN');

    $rootBlock4 = $root->blocks->firstWhere('position', 4);
    $rootBlock4->update([
        'type' => MenuBlockType::Submenu,
        'label' => 'BEGANE GROND',
        'icon' => MenuBlockIcon::Home,
        'color' => MenuBlockColor::Sky,
        'submenu_id' => $sub1->id,
    ]);

    $sub1Block1 = $sub1->blocks()->where('position', 1)->first();
    $sub1Block1->update([
        'type' => MenuBlockType::Submenu,
        'label' => 'ACHTERTUIN',
        'icon' => MenuBlockIcon::Sun,
        'color' => MenuBlockColor::Emerald,
        'submenu_id' => $sub2->id,
    ]);

    $sub2Block2 = $sub2->blocks()->where('position', 2)->first();
    $sub2Block2->update([
        'type' => MenuBlockType::Module,
        'module_key' => KioskModule::Gym,
        'label' => 'BUITEN GYM',
        'icon' => MenuBlockIcon::Dumbbell,
        'color' => MenuBlockColor::Amber,
    ]);

    // Step through 4 blocks -> 4 blocks -> 4 blocks -> Terug -> Terug -> Module
    $hub = Livewire::test(HubScreen::class)
        ->assertSee('ALARM')
        ->assertSee('BEGANE GROND')
        ->call('activateBlock', $rootBlock4->id)
        ->assertSet('menu', $sub1->id)
        ->assertSee('ACHTERTUIN')
        ->call('activateBlock', $sub1Block1->id)
        ->assertSet('menu', $sub2->id)
        ->assertSee('BUITEN GYM');

    // Terug steps one 4-block level up (sub2 -> sub1 -> root)
    $hub->call('goBack')
        ->assertSet('menu', $sub1->id)
        ->call('goBack')
        ->assertSet('menu', null);

    // Opening leaf module inside sub2 passes ?from={sub2->id} so Terug returns to sub2
    Livewire::withQueryParams(['menu' => $sub2->id])
        ->test(HubScreen::class)
        ->call('activateBlock', $sub2Block2->id)
        ->assertRedirect('/gym?from='.$sub2->id);

    Setting::setMany([
        'gym_on_label' => 'START TRAINING',
        'gym_off_label' => 'STOP TRAINING',
        'alarm_check_open_sensors' => '0',
        'all_off_title' => 'SNEL ALLES UIT',
        'all_off_require_confirmation' => '0',
    ]);

    Livewire::withQueryParams(['from' => $sub2->id])
        ->test(GymScreen::class)
        ->assertSee('START TRAINING')
        ->assertSee('STOP TRAINING')
        ->call('goBack')
        ->assertRedirect('/hub?menu='.$sub2->id);

    // Alarm module option checkOpenSensors = false skips sensor HTTP lookup
    Http::fake();
    Livewire::test(MenuScreen::class)
        ->call('selectMode', 'armed')
        ->assertRedirect('/entry?mode=armed');
    Http::assertNothingSent();

    // AllOff module option requireConfirmation = false shows direct action tile
    Livewire::test(AllOffScreen::class)
        ->assertSee('SNEL ALLES UIT')
        ->assertDontSee('Weet je zeker');
});

test('Jacuzzi, Sauna, and smart-home preset modules configure options, enforce safety confirmation, and trigger Homey flows', function (): void {
    Http::fake([
        'http://192.168.1.50/api/manager/flow/flow/flow-jacuzzi-jets/trigger' => Http::response([], 200),
        'http://192.168.1.50/api/manager/flow/flow/flow-sauna-heat/trigger' => Http::response([], 200),
        'http://192.168.1.50/api/manager/flow/*' => Http::response([
            'flow-jacuzzi-jets' => ['name' => 'Jacuzzi Bubbels Aan', 'enabled' => true],
            'flow-sauna-heat' => ['name' => 'Sauna Opwarmen 85C', 'enabled' => true],
        ], 200),
    ]);

    $this->actingAs($this->admin);

    // Filter ModulesOverview on Jacuzzi and save custom badge & flow
    Livewire::test(ModulesOverview::class)
        ->assertSee('Jacuzzi & Spa')
        ->assertSee('Sauna & Wellness')
        ->assertSee('Verlichting & Scènes')
        ->assertSee('Zonwering & Rolluiken')
        ->assertSee('Klimaat & Verwarming')
        ->assertSee('Tuin, Poort & Beregening')
        ->call('loadFlowOptions')
        ->call('selectModuleFilter', KioskModule::Jacuzzi->value)
        ->fillForm([
            'preset_modules' => [
                'jacuzzi' => [
                    'screen_title' => 'BUITEN JACUZZI',
                    'badge' => '39°C',
                    'confirm_first_slot' => false,
                    'auto_return' => true,
                    'slots' => [
                        1 => ['label' => 'OPWARMEN', 'flow_id' => ''],
                        2 => ['label' => 'MASSAGE JETS', 'flow_id' => 'flow-jacuzzi-jets'],
                        3 => ['label' => 'LED SFEER', 'flow_id' => ''],
                        4 => ['label' => 'SLUIMERSTAND', 'flow_id' => ''],
                    ],
                ],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    // Save Sauna with safety confirmation enabled on slot 1
    Setting::savePresetModuleOptions(KioskModule::Sauna, [
        'screen_title' => 'FINSE SAUNA',
        'badge' => '90°C',
        'confirm_first_slot' => true,
        'auto_return' => true,
        'slots' => [
            1 => ['label' => 'START KACHEL', 'flow_id' => 'flow-sauna-heat'],
            2 => ['label' => 'INFRAROOD', 'flow_id' => ''],
            3 => ['label' => 'LICHT', 'flow_id' => ''],
            4 => ['label' => 'SAUNA UIT', 'flow_id' => ''],
        ],
    ]);

    // Link Jacuzzi to root menu block 4 and navigate from HubScreen -> /module/jacuzzi
    $root = KioskMenu::ensureRootExists();
    $block4 = $root->blocks->firstWhere('position', 4);
    $block4->update([
        'type' => MenuBlockType::Module,
        'module_key' => KioskModule::Jacuzzi,
        'label' => 'JACUZZI',
        'icon' => MenuBlockIcon::Waves,
        'color' => MenuBlockColor::Sky,
    ]);

    Livewire::test(HubScreen::class)
        ->call('activateBlock', $block4->id)
        ->assertRedirect('/module/jacuzzi');

    // Jacuzzi module renders custom title, 39°C badge, and triggers slot 2 flow
    Livewire::test(ModuleScreen::class, ['module' => KioskModule::Jacuzzi])
        ->assertSee('BUITEN JACUZZI')
        ->assertSee('39°C')
        ->assertSee('MASSAGE JETS')
        ->call('triggerSlot', 2)
        ->assertSet('feedbackMsg', 'MASSAGE JETS GEACTIVEERD')
        ->assertDispatched('navigate-after-delay', url: '/');

    expect(ActivityLog::query()->where('message', 'Jacuzzi & Spa: MASSAGE JETS geactiveerd')->exists())->toBeTrue();

    // Sauna module requires in-screen safety confirmation before heating (slot 1)
    Livewire::test(ModuleScreen::class, ['module' => KioskModule::Sauna])
        ->assertSee('FINSE SAUNA')
        ->assertSee('90°C')
        ->call('triggerSlot', 1)
        ->assertSet('confirmingSlot', 1)
        ->assertSee('START KACHEL?')
        ->call('confirmPendingSlot')
        ->assertSet('confirmingSlot', null)
        ->assertSet('feedbackMsg', 'START KACHEL GEACTIVEERD');

    expect(ActivityLog::query()->where('message', 'Sauna & Wellness: START KACHEL geactiveerd')->exists())->toBeTrue();

    // All 6 preset module HTTP routes return 200 OK; invalid slug returns 404
    foreach (KioskModule::presetGridModules() as $presetModule) {
        $this->get('/module/'.$presetModule->value)->assertOk();
    }

    $this->get('/module/bestaat-niet')->assertNotFound();
});
