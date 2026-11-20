<div
    @if ($isConfigured)
        wire:init="refreshDashboard"
        wire:poll.300s="refreshDashboard"
        wire:click="openHub"
    @endif
    class="w-full h-full bg-black cursor-pointer relative overflow-hidden flex flex-col items-center justify-center p-4 gap-3"
>
    @if (! $isConfigured)
        <div class="flex flex-col items-center justify-center text-center gap-3 px-6 py-8 rounded-3xl bg-gray-950 border border-amber-500/40 max-w-[400px] select-none">
            <h1 class="text-xl font-bold tracking-wider uppercase text-amber-400">
                Setup Vereist
            </h1>
            <p class="text-sm text-gray-400 leading-relaxed">
                De Homey Pro verbinding is nog niet ingesteld. Configureer het paneel vanaf een ander apparaat via <code class="text-gray-200">/admin</code> op het lokale netwerk.
            </p>
        </div>
    @else
        {{-- Compact Top Bar when alert cards ARE visible --}}
        @if ($outdoorWeather && count($dashboardItems) > 0)
            <div class="w-full flex items-center justify-center gap-2.5 py-1.5 px-4 rounded-2xl bg-gray-950/90 border border-gray-800/80 shrink-0 tabular-nums select-none">
                @if ($outdoorWeather['icon'])
                    <x-weather-icon :icon="$outdoorWeather['icon']" class="w-6 h-6 shrink-0" />
                @else
                    <span class="text-xs uppercase tracking-widest text-gray-500 font-semibold">Buiten</span>
                @endif
                <span class="text-lg font-bold text-gray-200">{{ $outdoorWeather['tempLabel'] }}</span>
                @if ($outdoorWeather['feelsLikeLabel'])
                    <span class="text-gray-700" aria-hidden="true">&bull;</span>
                    <span class="text-sm font-medium text-gray-400">Gevoel {{ $outdoorWeather['feelsLikeLabel'] }}</span>
                @endif
            </div>
        @endif

        {{-- Centered Ambient Weather Display when NO alert cards are active --}}
        @if ($outdoorWeather && count($dashboardItems) === 0)
            <div class="flex flex-col items-center justify-center gap-2 select-none tabular-nums">
                @if ($outdoorWeather['icon'])
                    <x-weather-icon :icon="$outdoorWeather['icon']" class="w-24 h-24 mb-1 shrink-0" />
                @else
                    <span class="text-xs uppercase tracking-[0.25em] text-gray-500 font-semibold">Buiten</span>
                @endif
                <span class="text-6xl font-light tracking-tight text-gray-200 leading-none">
                    {{ $outdoorWeather['tempLabel'] }}
                </span>
                @if ($outdoorWeather['feelsLikeLabel'])
                    <span class="text-lg font-medium text-gray-400 mt-1">
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

        {{-- Fallback touch prompt if weather is unavailable and no cards are active --}}
        @if (! $outdoorWeather && count($dashboardItems) === 0)
            <div class="flex flex-col items-center justify-center gap-2 select-none text-gray-600">
                <span class="text-xs uppercase tracking-[0.3em] font-semibold">Tik voor bediening</span>
            </div>
        @endif

        {{-- Centered Dashboard Slots (Krabben + Paraplu + Kliko reminders when active) --}}
        @if (count($dashboardItems) > 0)
            <div
                @class([
                    'flex-1 flex flex-wrap items-center justify-center content-center min-h-0',
                    'gap-3 max-w-[380px]' => count($dashboardItems) >= 3,
                    'gap-4' => count($dashboardItems) < 3,
                ])
            >
                @foreach ($dashboardItems as $item)
                    <x-kiosk-alert-card
                        :item="$item"
                        :compact="count($dashboardItems) >= 3"
                        click-action="openHub"
                    />
                @endforeach
            </div>
        @endif
    @endif
</div>
