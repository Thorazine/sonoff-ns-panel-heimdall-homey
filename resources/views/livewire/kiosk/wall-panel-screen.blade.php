<div
    wire:init="refreshSidebar"
    wire:poll.60s="refreshSidebar"
    @if ($wallPanel->show_doorbell_popup)
        wire:poll.2s="checkDoorbell"
    @endif
    x-data="{
        sleepTimer: null,
        scheduleSleep() {
            if (this.sleepTimer) {
                clearTimeout(this.sleepTimer);
                this.sleepTimer = null;
            }
            if ($wire.isAsleep || $wire.doorbellActive) {
                return;
            }
            this.sleepTimer = setTimeout(() => {
                if ($wire.isAsleep || $wire.doorbellActive) {
                    return;
                }
                if (document.querySelector('[data-two-way-active=&quot;true&quot;]')) {
                    this.scheduleSleep();
                    return;
                }
                $wire.enterSleepMode();
            }, 30000);
        },
        destroy() {
            if (this.sleepTimer) clearTimeout(this.sleepTimer);
        }
    }"
    x-init="
        scheduleSleep();
        $watch('$wire.isAsleep', () => scheduleSleep());
        $watch('$wire.doorbellActive', () => scheduleSleep());
    "
    @click.window="scheduleSleep()"
    @touchstart.window.passive="scheduleSleep()"
    @keydown.window="scheduleSleep()"
    class="w-full h-screen h-dvh bg-black text-gray-100 overflow-clip relative flex p-4 gap-4"
>
    {{-- Full-Screen Feedback Overlay (Exact 480x480 Kiosk Style) --}}
    @if ($feedbackMsg !== null)
        <div
            role="status"
            wire:click="dismissFeedback"
            class="absolute inset-0 flex items-center justify-center bg-black/85 z-50 cursor-pointer animate-fade-in"
        >
            <p
                @class([
                    'text-2xl font-bold select-none px-8 py-5 rounded-3xl bg-gray-950 border tracking-widest uppercase text-center',
                    'text-rose-400 border-rose-500/60 shadow-[0_0_28px_rgba(244,63,94,0.2)]' => $feedbackType === 'error',
                    'text-flashy border-flashy/60 shadow-[0_0_28px_rgba(0,255,204,0.2)]' => $feedbackType !== 'error',
                ])
            >
                {{ $feedbackMsg }}
            </p>
        </div>
    @endif

    @if ($doorbellActive)
        {{-- ===================================================================== --}}
        {{-- FULLSCREEN 5-CAMERA DOORBELL INTERCOM VIEW (1 DOORBELL + 4 GARDEN)     --}}
        {{-- ===================================================================== --}}
        <section
            aria-label="Deurbel 5-camera intercom"
            class="w-full h-full flex flex-col gap-3.5 overflow-clip"
        >
            <header class="flex items-center justify-between gap-4 rounded-3xl border border-amber-400/70 bg-gray-900 px-5 py-3 shrink-0 shadow-[0_0_24px_rgba(245,158,11,0.18)]">
                <div class="flex items-center gap-3.5 min-w-0">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-amber-400 text-black animate-pulse">
                        <x-kiosk-icon icon="bell" class="h-6 w-6" />
                    </span>
                    <div class="min-w-0">
                        <h1 class="truncate text-xl font-bold tracking-widest uppercase text-amber-300">
                            ER WORDT AANGEBELD — 5 CAMERA OVERZICHT
                        </h1>
                        <p class="truncate text-sm text-gray-200">
                            {{ $wallPanel->name }} • Tik op een tuincamera rechts om te vergroten of start 2-weg audio
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <span class="rounded-2xl border border-amber-400/60 bg-gray-950 px-4 py-2.5 text-base font-bold tabular-nums text-amber-300">
                        {{ $doorbellRemainingSeconds }}s
                    </span>

                    @if ($cameraSettings['doorbellActionFlowId'] !== '')
                        <button
                            type="button"
                            wire:click="triggerDoorbellAction"
                            class="inline-flex items-center gap-2 rounded-2xl border border-flashy/60 bg-flashy/20 px-5 py-2.5 text-sm font-bold uppercase tracking-wider text-flashy shadow-[0_0_16px_rgba(0,255,204,0.15)] active:bg-flashy active:text-black active:scale-95 transition-all cursor-pointer"
                        >
                            <x-kiosk-icon icon="lock-open" class="h-5 w-5" />
                            <span>{{ $cameraSettings['doorbellActionLabel'] }}</span>
                        </button>
                    @endif

                    <button
                        type="button"
                        wire:click="dismissDoorbell"
                        class="rounded-2xl border border-gray-600 bg-gray-800 px-5 py-2.5 text-sm font-bold uppercase tracking-wider text-white active:bg-gray-700 active:scale-95 transition-all cursor-pointer"
                    >
                        Sluiten
                    </button>
                </div>
            </header>

            <div class="flex-1 min-h-0 grid grid-cols-12 gap-3.5">
                <div class="col-span-7 h-full min-h-0">
                    @if ($doorbellLayout['hero'] !== null)
                        <x-reolink-stream-tile
                            :camera="$doorbellLayout['hero']"
                            :is-hero="true"
                            context="doorbell"
                        />
                    @endif
                </div>

                <div class="col-span-5 h-full min-h-0 grid grid-cols-2 grid-rows-2 gap-3">
                    @foreach ($doorbellLayout['companions'] as $companionCam)
                        <x-reolink-stream-tile
                            :camera="$companionCam"
                            :is-hero="false"
                            context="doorbell"
                        />
                    @endforeach
                </div>
            </div>
        </section>
    @elseif ($isAsleep)
        {{-- ===================================================================== --}}
        {{-- PURE BLACK AMBIENT STANDBY SCREEN (KRABBEN / PARAPLU / KLIKO / WEER)   --}}
        {{-- ===================================================================== --}}
        <section
            aria-label="Stand-by scherm {{ $wallPanel->name }}"
            wire:click="wakeUp"
            class="w-full h-full bg-black cursor-pointer relative overflow-clip flex flex-col items-center justify-center gap-5 select-none"
        >
            @if (! $isConfigured)
                <div class="flex flex-col items-center justify-center text-center gap-3 px-8 py-8 rounded-3xl bg-gray-950 border border-amber-500/40 max-w-md">
                    <h1 class="text-xl font-bold tracking-wider uppercase text-amber-400">
                        Setup Vereist
                    </h1>
                    <p class="text-sm text-gray-300 leading-relaxed [text-wrap:pretty]">
                        De Homey Pro verbinding is nog niet ingesteld. Configureer het paneel vanaf een ander apparaat op het lokale netwerk.
                    </p>
                </div>
            @else
                {{-- Compact Top Weather Bar when alert cards (Krabben / Paraplu / Kliko) ARE active --}}
                @if ($outdoorWeather && count($dashboardItems) > 0)
                    <div class="flex items-center justify-center gap-3 py-2 px-6 rounded-2xl bg-gray-950/90 border border-gray-800 shrink-0 tabular-nums">
                        @if ($outdoorWeather['icon'])
                            <x-weather-icon :icon="$outdoorWeather['icon']" class="w-7 h-7 shrink-0" />
                        @else
                            <span class="text-xs uppercase tracking-widest text-gray-400 font-semibold">Buiten</span>
                        @endif
                        <span class="text-xl font-bold text-gray-100">{{ $outdoorWeather['tempLabel'] }}</span>
                        @if ($outdoorWeather['feelsLikeLabel'])
                            <span class="text-gray-600" aria-hidden="true">&bull;</span>
                            <span class="text-base font-medium text-gray-300">Gevoel {{ $outdoorWeather['feelsLikeLabel'] }}</span>
                        @endif
                    </div>
                @endif

                {{-- Centered Ambient Weather Display on Pure Black when NO alert cards are active --}}
                @if ($outdoorWeather && count($dashboardItems) === 0)
                    <div class="flex flex-col items-center justify-center gap-2.5 tabular-nums">
                        @if ($outdoorWeather['icon'])
                            <x-weather-icon :icon="$outdoorWeather['icon']" class="w-28 h-28 mb-1 shrink-0" />
                        @else
                            <span class="text-xs uppercase tracking-[0.25em] text-gray-400 font-semibold">Buiten</span>
                        @endif
                        <span class="text-7xl font-light tracking-tight text-gray-200 leading-none">
                            {{ $outdoorWeather['tempLabel'] }}
                        </span>
                        @if ($outdoorWeather['feelsLikeLabel'])
                            <span class="text-xl font-medium text-gray-400 mt-1">
                                Gevoel {{ $outdoorWeather['feelsLikeLabel'] }}
                            </span>
                        @endif
                        @if ($outdoorWeather['conditionLabel'])
                            <span class="text-xs uppercase tracking-[0.22em] text-gray-500 font-semibold mt-0.5">
                                {{ $outdoorWeather['conditionLabel'] }}
                            </span>
                        @endif
                    </div>
                @endif

                {{-- Pure Black Minimal Touch Prompt if weather is unavailable and no alerts are active --}}
                @if (! $outdoorWeather && count($dashboardItems) === 0)
                    <div class="flex flex-col items-center justify-center gap-2 text-gray-600">
                        <span class="text-xs uppercase tracking-[0.3em] font-semibold">Tik voor bediening</span>
                    </div>
                @endif

                {{-- Widescreen Row of Active Alert Cards (Krabben + Paraplu + Kliko reminders) --}}
                @if (count($dashboardItems) > 0)
                    <div class="flex flex-wrap items-center justify-center content-center gap-5 min-h-0">
                        @foreach ($dashboardItems as $item)
                            <x-kiosk-alert-card
                                :item="$item"
                                :compact="count($dashboardItems) >= 4"
                                click-action="wakeUp"
                            />
                        @endforeach
                    </div>
                @endif
            @endif
        </section>
    @else
        {{-- ===================================================================== --}}
        {{-- WIDESCREEN SPLIT-VIEW IN 480x480 KIOSK STYLING                        --}}
        {{-- ===================================================================== --}}
        <aside class="w-68 xl:w-76 shrink-0 h-full flex flex-col justify-between gap-3 overflow-clip">
            {{-- Clock & Ambient Weather Card (480x480 StartScreen Aesthetic) --}}
            <div
                x-data="{
                    timeStr: '',
                    dateStr: '',
                    tick() {
                        const now = new Date();
                        this.timeStr = now.toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' });
                        this.dateStr = now.toLocaleDateString('nl-NL', { weekday: 'long', day: 'numeric', month: 'short' });
                    }
                }"
                x-init="tick(); setInterval(() => tick(), 1000)"
                class="rounded-3xl bg-gray-900/80 border border-gray-700/90 p-4 flex flex-col gap-3"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="truncate text-xs font-bold uppercase tracking-widest text-flashy">
                        {{ $wallPanel->name }}
                    </span>
                    @if ($wallPanel->location)
                        <span class="truncate text-xs font-medium text-gray-300">
                            {{ $wallPanel->location }}
                        </span>
                    @endif
                </div>

                <div>
                    <div class="text-5xl font-light tracking-tight tabular-nums text-white leading-none" x-text="timeStr">--:--</div>
                    <div class="text-sm font-medium uppercase tracking-widest text-gray-300 mt-1.5" x-text="dateStr"></div>
                </div>

                @if ($outdoorWeather !== null)
                    <div class="border-t border-gray-700/80 pt-3 flex items-center justify-between gap-2 tabular-nums">
                        <div class="flex items-center gap-2.5">
                            @if (! empty($outdoorWeather['icon']))
                                <x-weather-icon :icon="$outdoorWeather['icon']" class="w-8 h-8 shrink-0" />
                            @endif
                            <div>
                                <div class="text-2xl font-bold text-white leading-none">
                                    {{ $outdoorWeather['tempLabel'] }}
                                </div>
                                @if (! empty($outdoorWeather['feelsLikeLabel']))
                                    <div class="text-xs font-medium text-gray-300 mt-1">
                                        Gevoel {{ $outdoorWeather['feelsLikeLabel'] }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if (! empty($outdoorWeather['conditionLabel']))
                            <span class="text-right text-xs font-semibold uppercase tracking-wider text-gray-300">
                                {{ $outdoorWeather['conditionLabel'] }}
                            </span>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Alarm Tile & Active Reminders (480x480 Card Styling) --}}
            <div class="flex-1 min-h-0 flex flex-col justify-center gap-3 overflow-clip">
                <button
                    type="button"
                    wire:click="openModuleByKey('alarm')"
                    @class([
                        'w-full rounded-3xl bg-gray-900/80 border p-4 text-left transition-all active:scale-95 active:bg-gray-800 cursor-pointer flex items-center gap-3.5',
                        'border-rose-400/60 shadow-[0_0_20px_rgba(244,63,94,0.15)] text-rose-300' => $alarmStatus === 'armed',
                        'border-amber-400/60 shadow-[0_0_20px_rgba(245,158,11,0.15)] text-amber-300' => $alarmStatus === 'partially_armed',
                        'border-flashy/60 shadow-[0_0_20px_rgba(0,255,204,0.14)] text-flashy' => ! in_array($alarmStatus, ['armed', 'partially_armed'], true),
                    ])
                >
                    <x-kiosk-icon icon="shield-check" class="h-10 w-10 shrink-0" />
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-widest text-gray-300">Heimdall Alarm</p>
                        <p class="truncate text-base font-bold uppercase tracking-wider mt-0.5">
                            @if ($alarmStatus === 'armed')
                                Volledig Actief
                            @elseif ($alarmStatus === 'partially_armed')
                                Gedeeltelijk
                            @elseif ($alarmStatus === 'disarmed')
                                Uitgeschakeld
                            @else
                                Beveiliging
                            @endif
                        </p>
                    </div>
                </button>

                @if ($dashboardItems !== [])
                    <div class="grid grid-cols-1 gap-2 overflow-clip">
                        @foreach (array_slice($dashboardItems, 0, 2) as $item)
                            <div class="rounded-2xl px-4 py-2.5 flex items-center justify-between gap-2 {{ $item['bgClass'] ?? 'bg-gray-900 border border-gray-700' }}">
                                <span
                                    class="text-sm font-bold tracking-wider uppercase truncate"
                                    @if (! empty($item['textHex'])) style="color: {{ $item['textHex'] }}" @endif
                                >
                                    {{ $item['label'] ?? $item['title'] ?? '' }}
                                </span>
                                @if (! empty($item['dayLabel']))
                                    <span class="text-xs font-semibold text-gray-200 shrink-0">
                                        {{ $item['dayLabel'] }}
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Quick Switcher Buttons + Sluimer (Black Screen) --}}
            <div class="grid grid-cols-3 gap-2 shrink-0">
                <button
                    type="button"
                    wire:click="closeModule"
                    @class([
                        'py-3.5 px-2 rounded-2xl border text-xs xl:text-sm font-bold uppercase tracking-wider transition-all cursor-pointer flex items-center justify-center gap-1.5 active:scale-95',
                        'bg-gray-900 border-flashy/60 text-flashy shadow-[0_0_16px_rgba(0,255,204,0.14)]' => $activeModule === null,
                        'bg-gray-900 border-gray-700 text-gray-200 active:bg-gray-800' => $activeModule !== null,
                    ])
                >
                    <x-kiosk-icon icon="grid" class="h-4 w-4 shrink-0" />
                    <span>Menu</span>
                </button>

                <button
                    type="button"
                    wire:click="openModuleByKey('cameras')"
                    @class([
                        'py-3.5 px-2 rounded-2xl border text-xs xl:text-sm font-bold uppercase tracking-wider transition-all cursor-pointer flex items-center justify-center gap-1.5 active:scale-95',
                        'bg-gray-900 border-sky-400/70 text-sky-300 shadow-[0_0_16px_rgba(14,165,233,0.16)]' => $activeModule === \App\Enums\KioskModule::Cameras,
                        'bg-gray-900 border-gray-700 text-sky-300 active:bg-gray-800' => $activeModule !== \App\Enums\KioskModule::Cameras,
                    ])
                >
                    <x-kiosk-icon icon="video-camera" class="h-4 w-4 shrink-0" />
                    <span>Cam's</span>
                </button>

                <button
                    type="button"
                    wire:click="enterSleepMode"
                    class="py-3.5 px-2 rounded-2xl bg-gray-900 border border-gray-700 text-xs xl:text-sm font-bold uppercase tracking-wider text-gray-300 active:bg-gray-800 active:scale-95 transition-all cursor-pointer flex items-center justify-center gap-1.5"
                >
                    <x-kiosk-icon icon="moon" class="h-4 w-4 shrink-0" />
                    <span>Zwart</span>
                </button>
            </div>
        </aside>

        {{-- Right Main Content Area --}}
        <section class="flex-1 min-w-0 h-full flex flex-col gap-3 overflow-clip">
            @if ($activeModule === null)
                {{-- ============================================================= --}}
                {{-- 4x2 (8 BLOCKS) MENU GRID — IDENTICAL TO 480x480 HUB SCREEN    --}}
                {{-- ============================================================= --}}
                @if (! $currentMenu->is_root)
                    <header class="flex items-center justify-between gap-3 px-1 shrink-0">
                        <h2 class="text-base font-bold uppercase tracking-widest text-gray-200 truncate [text-wrap:balance]">
                            {{ $currentMenu->title }}
                        </h2>
                        <button
                            type="button"
                            wire:click="goBack"
                            class="rounded-2xl bg-gray-900 border border-gray-700 px-5 py-2.5 text-sm font-bold uppercase tracking-wider text-gray-200 active:bg-gray-800 transition-colors cursor-pointer"
                        >
                            ← Terug
                        </button>
                    </header>
                @endif

                <div class="flex-1 min-h-0 grid grid-cols-4 grid-rows-2 gap-3.5">
                    @foreach ($currentMenu->blocks as $block)
                        @if ($block->type === \App\Enums\MenuBlockType::Empty)
                            <div
                                wire:key="wp-block-{{ $block->id }}"
                                aria-hidden="true"
                                class="w-full h-full rounded-3xl bg-gray-950/40 border border-gray-900"
                            ></div>
                        @else
                            <button
                                type="button"
                                wire:key="wp-block-{{ $block->id }}"
                                wire:click="activateBlock({{ $block->id }})"
                                wire:loading.attr="disabled"
                                class="w-full h-full rounded-3xl bg-gray-900/80 border {{ $block->resolvedColor()->kioskCardClasses() }} relative flex flex-col items-center justify-center gap-3 px-3 active:scale-95 active:bg-gray-800 transition-all select-none cursor-pointer disabled:opacity-50 overflow-clip"
                            >
                                @if ($block->type === \App\Enums\MenuBlockType::Submenu)
                                    <span
                                        aria-hidden="true"
                                        class="absolute top-3 right-3 rounded-lg bg-gray-950/90 border border-white/15 p-1.5 text-gray-300"
                                    >
                                        <svg aria-hidden="true" viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="3" width="7" height="7" rx="1" />
                                            <rect x="14" y="3" width="7" height="7" rx="1" />
                                            <rect x="3" y="14" width="7" height="7" rx="1" />
                                            <rect x="14" y="14" width="7" height="7" rx="1" />
                                        </svg>
                                    </span>
                                @endif

                                <x-kiosk-icon :icon="$block->resolvedIcon()" class="w-14 h-14 xl:w-16 xl:h-16 shrink-0" />

                                <span class="text-lg xl:text-xl font-bold tracking-widest uppercase leading-tight text-center px-1">
                                    {{ $block->resolvedLabel() }}
                                </span>
                            </button>
                        @endif
                    @endforeach
                </div>
            @elseif ($activeModule === \App\Enums\KioskModule::Cameras)
                {{-- ============================================================= --}}
                {{-- REOLINK 5-CAMERA WIDESCREEN VIEW + TWO-WAY AUDIO              --}}
                {{-- ============================================================= --}}
                <header class="flex items-center justify-between gap-3 px-1 shrink-0">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <x-kiosk-icon icon="video-camera" class="h-6 w-6 text-sky-300 shrink-0" />
                        <h1 class="truncate text-base font-bold uppercase tracking-widest text-gray-100 [text-wrap:balance]">
                            Reolink Camera's &amp; Twee-Weg Audio (5 Camera's)
                        </h1>
                    </div>

                    <div class="flex items-center gap-2.5 shrink-0">
                        @if ($cameraSettings['doorbellActionFlowId'] !== '')
                            <button
                                type="button"
                                wire:click="triggerDoorbellAction"
                                class="inline-flex items-center gap-2 rounded-2xl border border-flashy/60 bg-flashy/20 px-4 py-2.5 text-sm font-bold uppercase tracking-wider text-flashy active:bg-flashy active:text-black cursor-pointer"
                            >
                                <x-kiosk-icon icon="lock-open" class="h-4 w-4" />
                                <span>{{ $cameraSettings['doorbellActionLabel'] }}</span>
                            </button>
                        @endif

                        <button
                            type="button"
                            wire:click="goBack"
                            class="rounded-2xl bg-gray-900 border border-gray-700 px-5 py-2.5 text-sm font-bold uppercase tracking-wider text-gray-200 active:bg-gray-800 cursor-pointer"
                        >
                            ← Terug
                        </button>
                    </div>
                </header>

                <div class="flex-1 min-h-0 grid grid-cols-12 gap-3.5">
                    <div class="col-span-7 h-full min-h-0">
                        @if ($camerasModuleLayout['hero'] !== null)
                            <x-reolink-stream-tile
                                :camera="$camerasModuleLayout['hero']"
                                :is-hero="true"
                                context="module"
                            />
                        @endif
                    </div>

                    <div class="col-span-5 h-full min-h-0 grid grid-cols-2 grid-rows-2 gap-3">
                        @foreach ($camerasModuleLayout['companions'] as $companionCam)
                            <x-reolink-stream-tile
                                :camera="$companionCam"
                                :is-hero="false"
                                context="module"
                            />
                        @endforeach
                    </div>
                </div>
            @elseif ($activeModule === \App\Enums\KioskModule::Alarm)
                {{-- ============================================================= --}}
                {{-- NATIVE WIDESCREEN ALARM MODULE (480x480 MENU + ENTRY STYLE)   --}}
                {{-- ============================================================= --}}
                <header class="flex items-center justify-between gap-3 px-1 shrink-0">
                    <h1 class="text-base font-bold uppercase tracking-widest text-flashy [text-wrap:balance]">
                        Heimdall Beveiliging
                    </h1>
                    <button
                        type="button"
                        wire:click="goBack"
                        class="rounded-2xl bg-gray-900 border border-gray-700 px-5 py-2.5 text-sm font-bold uppercase tracking-wider text-gray-200 active:bg-gray-800 cursor-pointer"
                    >
                        ← Terug
                    </button>
                </header>

                @if ($alarmStep === 'choose')
                    <div class="flex-1 min-h-0 grid grid-cols-2 gap-4">
                        <button
                            type="button"
                            wire:click="selectAlarmMode('armed')"
                            class="rounded-3xl border border-flashy/60 bg-gray-900/80 p-6 flex flex-col items-center justify-center gap-3.5 text-flashy shadow-[0_0_22px_rgba(0,255,204,0.14)] active:scale-95 active:bg-gray-800 transition-all cursor-pointer text-center"
                        >
                            <x-kiosk-icon icon="lock-closed" class="h-16 w-16" />
                            <span class="text-2xl font-bold uppercase tracking-widest">Volledig Ingeschakeld</span>
                            <span class="text-sm font-medium text-gray-200 [text-wrap:pretty]">Alle deur-, raam- en bewegingssensoren actief</span>
                        </button>

                        <button
                            type="button"
                            wire:click="selectAlarmMode('partially_armed')"
                            class="rounded-3xl border border-amber-400/60 bg-gray-900/80 p-6 flex flex-col items-center justify-center gap-3.5 text-amber-300 shadow-[0_0_22px_rgba(245,158,11,0.14)] active:scale-95 active:bg-gray-800 transition-all cursor-pointer text-center"
                        >
                            <x-kiosk-icon icon="moon" class="h-16 w-16" />
                            <span class="text-2xl font-bold uppercase tracking-widest">Gedeeltelijk Ingeschakeld</span>
                            <span class="text-sm font-medium text-gray-200 [text-wrap:pretty]">Omtrekbeveiliging voor de nacht</span>
                        </button>
                    </div>
                @elseif ($alarmStep === 'sensors')
                    <div class="flex-1 min-h-0 rounded-3xl border border-red-500/60 bg-gray-950 p-6 flex flex-col justify-between gap-4">
                        <div class="space-y-3 overflow-y-auto wall-panel-scroll">
                            <h2 class="text-xl font-bold uppercase tracking-wider text-red-400 [text-wrap:balance]">
                                Open Ramen / Deuren Gevonden
                            </h2>
                            <ul role="list" class="grid grid-cols-2 gap-3 text-base text-gray-100">
                                @foreach ($openSensors as $sensorName)
                                    <li class="rounded-2xl border border-gray-700 bg-gray-800/70 px-4 py-3 font-semibold [text-wrap:pretty]">
                                        {{ $sensorName }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="grid grid-cols-2 gap-3 shrink-0">
                            <button
                                type="button"
                                wire:click="closeModule"
                                class="rounded-2xl border border-red-500 bg-red-600/20 py-3.5 text-base font-bold uppercase tracking-wider text-red-300 active:bg-red-600/40 cursor-pointer"
                            >
                                Annuleer
                            </button>
                            <button
                                type="button"
                                wire:click="acceptOpenSensors"
                                class="rounded-2xl border border-emerald-500 bg-emerald-600/20 py-3.5 text-base font-bold uppercase tracking-wider text-emerald-300 active:bg-emerald-600/40 cursor-pointer"
                            >
                                Accepteer &amp; Doorgaan →
                            </button>
                        </div>
                    </div>
                @else
                    <div class="flex-1 min-h-0 grid grid-cols-12 gap-4 items-center">
                        <div class="col-span-5 h-full rounded-3xl border border-gray-700 bg-gray-900/80 p-6 flex flex-col items-center justify-center gap-4 text-center">
                            @if ($isLockedOut)
                                <p class="text-xl font-bold uppercase tracking-widest text-red-400">
                                    Geblokkeerd ({{ $lockoutSecondsLeft }}s)
                                </p>
                            @else
                                <p class="text-base font-bold uppercase tracking-widest text-gray-200">
                                    {{ $pendingAlarmMode === 'disarmed' ? 'Alarm Uitschakelen' : 'Voer Pincode In' }}
                                </p>
                                <div class="w-full py-4 rounded-2xl bg-gray-950 border-2 border-gray-700 flex items-center justify-center gap-3">
                                    @for ($i = 0; $i < max(4, strlen($pinBuffer)); $i++)
                                        <span
                                            @class([
                                                'h-4 w-4 rounded-full transition-all',
                                                'bg-flashy shadow-[0_0_10px_rgba(0,255,204,0.8)] scale-110' => $i < strlen($pinBuffer),
                                                'bg-gray-700' => $i >= strlen($pinBuffer),
                                            ])
                                        ></span>
                                    @endfor
                                </div>
                                <p class="text-sm text-gray-300 [text-wrap:pretty]">Toets je pincode in en bevestig met OK</p>
                            @endif
                        </div>

                        <div class="col-span-7 h-full grid grid-cols-3 grid-rows-4 gap-2.5">
                            @foreach (['1', '2', '3', '4', '5', '6', '7', '8', '9'] as $digit)
                                <button
                                    type="button"
                                    wire:click="appendPinDigit('{{ $digit }}')"
                                    class="rounded-2xl border border-gray-700 bg-gray-800 text-2xl font-bold text-gray-100 active:bg-flashy active:text-black transition-colors cursor-pointer"
                                >
                                    {{ $digit }}
                                </button>
                            @endforeach

                            <button
                                type="button"
                                wire:click="clearPinBuffer"
                                class="rounded-2xl border border-gray-700 bg-gray-800 text-base font-bold uppercase tracking-wider text-gray-300 active:bg-gray-700 cursor-pointer"
                            >
                                Wissen
                            </button>

                            <button
                                type="button"
                                wire:click="appendPinDigit('0')"
                                class="rounded-2xl border border-gray-700 bg-gray-800 text-2xl font-bold text-gray-100 active:bg-flashy active:text-black transition-colors cursor-pointer"
                            >
                                0
                            </button>

                            <button
                                type="button"
                                wire:click="submitWallPanelPin"
                                class="rounded-2xl border border-flashy/60 bg-flashy/20 text-lg font-bold uppercase tracking-wider text-flashy active:bg-flashy active:text-black cursor-pointer"
                            >
                                OK
                            </button>
                        </div>
                    </div>
                @endif
            @elseif ($activeModule === \App\Enums\KioskModule::Gym && $gymOptions !== null)
                {{-- ============================================================= --}}
                {{-- NATIVE WIDESCREEN GYM MODULE (480x480 GYM SCREEN STYLE)       --}}
                {{-- ============================================================= --}}
                <header class="flex items-center justify-between gap-3 px-1 shrink-0">
                    <h1 class="text-base font-bold uppercase tracking-widest text-gray-100 [text-wrap:balance]">
                        Gym Bediening
                    </h1>
                    <button
                        type="button"
                        wire:click="goBack"
                        class="rounded-2xl bg-gray-900 border border-gray-700 px-5 py-2.5 text-sm font-bold uppercase tracking-wider text-gray-200 active:bg-gray-800 cursor-pointer"
                    >
                        ← Terug
                    </button>
                </header>

                <div class="flex-1 min-h-0 grid grid-cols-2 gap-4">
                    <button
                        type="button"
                        wire:click="triggerGymState('on')"
                        class="rounded-3xl bg-emerald-950/45 border border-emerald-500/60 shadow-[0_0_22px_rgba(16,185,129,0.16)] p-6 flex flex-col items-center justify-center gap-3.5 text-emerald-300 active:scale-95 active:bg-emerald-900/50 transition-all cursor-pointer"
                    >
                        <x-kiosk-icon icon="power" class="h-16 w-16" />
                        <span class="text-2xl font-bold uppercase tracking-widest">{{ $gymOptions['onLabel'] }}</span>
                    </button>

                    <button
                        type="button"
                        wire:click="triggerGymState('off')"
                        class="rounded-3xl bg-red-950/45 border border-red-500/60 shadow-[0_0_22px_rgba(239,68,68,0.16)] p-6 flex flex-col items-center justify-center gap-3.5 text-red-300 active:scale-95 active:bg-red-900/50 transition-all cursor-pointer"
                    >
                        <x-kiosk-icon icon="power" class="h-16 w-16" />
                        <span class="text-2xl font-bold uppercase tracking-widest">{{ $gymOptions['offLabel'] }}</span>
                    </button>
                </div>
            @elseif ($activeModule === \App\Enums\KioskModule::AllOff && $allOffOptions !== null)
                {{-- ============================================================= --}}
                {{-- NATIVE WIDESCREEN ALL-OFF MODULE (480x480 ALL-OFF STYLE)      --}}
                {{-- ============================================================= --}}
                <div class="flex-1 min-h-0 rounded-3xl border border-rose-500/50 bg-gray-950 shadow-[0_0_30px_rgba(244,63,94,0.15)] p-6 flex flex-col items-center justify-between text-center">
                    <div class="flex-1 flex flex-col items-center justify-center gap-4">
                        <div class="w-20 h-20 rounded-full bg-rose-950/70 border border-rose-500/50 flex items-center justify-center">
                            <x-kiosk-icon icon="power" class="h-11 w-11 text-rose-400" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold uppercase tracking-wider text-rose-400 [text-wrap:balance]">
                                {{ $allOffOptions['title'] }}
                            </h1>
                            <p class="max-w-md text-base text-gray-300 mt-2 [text-wrap:pretty]">
                                {{ $allOffOptions['subtitle'] }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 w-full max-w-xl">
                        <button
                            type="button"
                            wire:click="closeModule"
                            class="py-4 rounded-2xl bg-gray-900 border border-gray-700 text-gray-200 text-base font-bold uppercase tracking-wider active:bg-gray-800 cursor-pointer"
                        >
                            Annuleren
                        </button>
                        <button
                            type="button"
                            wire:click="executeAllOff"
                            class="py-4 rounded-2xl bg-rose-600/25 border border-rose-500 text-rose-300 text-base font-bold uppercase tracking-wider active:bg-rose-600 active:text-white cursor-pointer"
                        >
                            {{ $allOffOptions['confirmLabel'] }}
                        </button>
                    </div>
                </div>
            @elseif ($activeModule !== null && $presetOptions !== null)
                {{-- ============================================================= --}}
                {{-- NATIVE WIDESCREEN PRESET MODULE (480x480 MODULE SCREEN STYLE) --}}
                {{-- ============================================================= --}}
                <header class="flex items-center justify-between gap-3 px-1 shrink-0">
                    <div class="flex items-center gap-3 min-w-0">
                        <x-kiosk-icon :icon="$activeModule->defaultIcon()" class="w-6 h-6 shrink-0 text-gray-200" />
                        <h1 class="text-base font-bold uppercase tracking-widest text-gray-100 truncate [text-wrap:balance]">
                            {{ $presetOptions['screenTitle'] }}
                        </h1>
                        @if ($presetOptions['badge'] !== '')
                            <span class="rounded-full border border-white/20 bg-gray-900 px-3.5 py-0.5 text-sm font-bold tracking-wider text-flashy tabular-nums shrink-0">
                                {{ $presetOptions['badge'] }}
                            </span>
                        @endif
                    </div>
                    <button
                        type="button"
                        wire:click="goBack"
                        class="rounded-2xl bg-gray-900 border border-gray-700 px-5 py-2.5 text-sm font-bold uppercase tracking-wider text-gray-200 active:bg-gray-800 cursor-pointer"
                    >
                        ← Terug
                    </button>
                </header>

                @if ($confirmingSlot !== null)
                    <div class="flex-1 min-h-0 rounded-3xl border border-amber-500/50 bg-gray-950 shadow-[0_0_28px_rgba(245,158,11,0.15)] p-6 flex flex-col items-center justify-between text-center">
                        <div class="flex-1 flex flex-col items-center justify-center gap-4">
                            <div class="w-18 h-18 rounded-full bg-amber-950/70 border border-amber-500/50 flex items-center justify-center text-amber-300">
                                <x-kiosk-icon :icon="$presetOptions['slots'][$confirmingSlot]['icon']" class="w-9 h-9" />
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold uppercase tracking-wider text-amber-300 [text-wrap:balance]">
                                    {{ $presetOptions['slots'][$confirmingSlot]['label'] ?? '' }}?
                                </h2>
                                <p class="text-base text-gray-300 mt-1.5 [text-wrap:pretty]">
                                    Bevestig dat je <strong>{{ strtolower($presetOptions['screenTitle']) }}</strong> wilt activeren.
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 w-full max-w-xl">
                            <button
                                type="button"
                                wire:click="goBack"
                                class="py-4 rounded-2xl bg-gray-900 border border-gray-700 text-gray-200 text-base font-bold uppercase tracking-wider active:bg-gray-800 cursor-pointer"
                            >
                                Annuleren
                            </button>
                            <button
                                type="button"
                                wire:click="confirmPendingPresetSlot"
                                class="py-4 rounded-2xl bg-amber-500/20 border border-amber-400 text-amber-300 text-base font-bold uppercase tracking-wider active:bg-amber-500 active:text-black cursor-pointer"
                            >
                                Ja, start
                            </button>
                        </div>
                    </div>
                @else
                    <div class="flex-1 min-h-0 grid grid-cols-4 gap-3.5">
                        @foreach ($presetOptions['slots'] as $slotPos => $slotItem)
                            <button
                                type="button"
                                wire:click="triggerPresetSlot({{ $slotPos }})"
                                wire:loading.attr="disabled"
                                class="rounded-3xl border {{ $slotItem['color']->kioskCardClasses() }} bg-gray-900/80 p-4 flex flex-col items-center justify-center gap-3.5 active:scale-95 active:bg-gray-800 transition-all cursor-pointer text-center disabled:opacity-50"
                            >
                                <x-kiosk-icon :icon="$slotItem['icon']" class="h-14 w-14 xl:h-16 xl:w-16 shrink-0" />
                                <span class="text-lg xl:text-xl font-bold uppercase tracking-widest leading-tight text-center">
                                    {{ $slotItem['label'] }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                @endif
            @endif
        </section>
    @endif
</div>
