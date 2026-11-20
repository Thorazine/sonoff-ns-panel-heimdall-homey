<x-filament-panels::page>
    <div @if ($isConfigured) wire:init="loadSensors" @endif class="space-y-6">
        @if (! $isConfigured)
            <x-filament::section>
                <div class="text-sm font-medium text-amber-600 dark:text-amber-400">
                    Homey is nog niet gekoppeld. Configureer eerst de Homey URL en API Key onder <strong>Instellingen &amp; Homey</strong>.
                </div>
            </x-filament::section>
        @elseif (! $sensorsLoaded)
            <div class="grid grid-cols-[repeat(auto-fill,minmax(240px,1fr))] gap-4" aria-busy="true">
                @for ($i = 0; $i < 6; $i++)
                    <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-gray-900 animate-pulse space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="h-11 w-11 rounded-xl bg-gray-200 dark:bg-gray-800"></div>
                            <div class="h-6 w-16 rounded-lg bg-gray-200 dark:bg-gray-800"></div>
                        </div>
                        <div class="space-y-2">
                            <div class="h-4 w-2/3 rounded bg-gray-200 dark:bg-gray-800"></div>
                            <div class="h-3 w-1/2 rounded bg-gray-200 dark:bg-gray-800"></div>
                        </div>
                        <div class="h-3 w-full rounded bg-gray-200 dark:bg-gray-800"></div>
                    </article>
                @endfor
            </div>
        @elseif (count($sensors) === 0)
            <x-filament::section>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Geen contact- of bewegingssensoren gevonden in Homey.
                </div>
            </x-filament::section>
        @else
            @php
                $totalCount = count($sensors);
                $activeCount = count(array_filter($sensors, fn (array $s): bool => $s['active']));
                $lowBatteryCount = count(array_filter($sensors, fn (array $s): bool => $s['battery'] !== null && $s['battery'] < 20));
            @endphp

            {{-- Compact Summary Cards --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-xs dark:border-white/10 dark:bg-gray-900 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Totaal Sensoren</p>
                        <p class="mt-1 text-2xl font-bold tabular-nums text-gray-950 dark:text-white">{{ $totalCount }}</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-500/10 text-teal-600 dark:text-teal-400">
                        <svg aria-hidden="true" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-xs dark:border-white/10 dark:bg-gray-900 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Open / Actief</p>
                        <p @class([
                            'mt-1 text-2xl font-bold tabular-nums',
                            'text-rose-600 dark:text-rose-400' => $activeCount > 0,
                            'text-emerald-600 dark:text-emerald-400' => $activeCount === 0,
                        ])>
                            {{ $activeCount }}
                        </p>
                    </div>
                    <div @class([
                        'flex h-10 w-10 items-center justify-center rounded-xl',
                        'bg-rose-500/10 text-rose-600 dark:text-rose-400' => $activeCount > 0,
                        'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' => $activeCount === 0,
                    ])>
                        <svg aria-hidden="true" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-xs dark:border-white/10 dark:bg-gray-900 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Lage Batterij (&lt; 20%)</p>
                        <p @class([
                            'mt-1 text-2xl font-bold tabular-nums',
                            'text-amber-600 dark:text-amber-400' => $lowBatteryCount > 0,
                            'text-gray-950 dark:text-white' => $lowBatteryCount === 0,
                        ])>
                            {{ $lowBatteryCount }}
                        </p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
                        <svg aria-hidden="true" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 10.5h.375c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125H21M3.75 18h15A2.25 2.25 0 0021 15.75v-6a2.25 2.25 0 00-2.25-2.25h-15A2.25 2.25 0 001.5 9.75v6A2.25 2.25 0 003.75 18z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Sensor Blocks Grid --}}
            <div class="grid grid-cols-[repeat(auto-fill,minmax(240px,1fr))] gap-4">
                @foreach ($sensors as $sensor)
                    <article
                        @class([
                            'rounded-2xl border p-5 shadow-xs flex flex-col justify-between gap-4 transition-colors',
                            'border-rose-500/60 bg-rose-50/50 dark:border-rose-500/50 dark:bg-rose-950/20' => $sensor['active'],
                            'border-gray-200 bg-white hover:border-gray-300 dark:border-white/10 dark:bg-gray-900 dark:hover:border-white/20' => ! $sensor['active'],
                        ])
                    >
                        {{-- Top: Icon + Status Badge --}}
                        <header class="flex items-start justify-between gap-3">
                            <div
                                @class([
                                    'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border',
                                    'border-rose-500/30 bg-rose-500/15 text-rose-600 dark:text-rose-400' => $sensor['active'],
                                    'border-emerald-500/25 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' => ! $sensor['active'],
                                ])
                            >
                                @if ($sensor['type'] === 'contact')
                                    {{-- Door / Window Contact Icon --}}
                                    <svg aria-hidden="true" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                                    </svg>
                                @else
                                    {{-- Motion Sensor Icon --}}
                                    <svg aria-hidden="true" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.348 14.651a3.75 3.75 0 010-5.303m5.304 0a3.75 3.75 0 010 5.303m-7.425 2.122a6.75 6.75 0 010-9.546m9.546 0a6.75 6.75 0 010 9.546M5.106 18.894c-3.808-3.808-3.808-9.98 0-13.789m13.788 0c3.808 3.808 3.808 9.981 0 13.79M12 12h.008v.007H12V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                    </svg>
                                @endif
                            </div>

                            <x-filament::badge :color="$sensor['active'] ? 'danger' : 'success'">
                                {{ $sensor['active'] ? ($sensor['type'] === 'contact' ? 'OPEN' : 'ACTIEF') : 'OK' }}
                            </x-filament::badge>
                        </header>

                        {{-- Middle: Sensor Name & Zone --}}
                        <hgroup class="min-w-0">
                            <h3 class="truncate text-base font-semibold text-gray-950 dark:text-white" title="{{ $sensor['name'] }}">
                                {{ $sensor['name'] }}
                            </h3>
                            <p class="mt-0.5 truncate text-xs font-medium text-gray-500 dark:text-gray-400">
                                {{ $sensor['zone'] }}
                            </p>
                        </hgroup>

                        {{-- Bottom: Sensor Type & Battery Bar --}}
                        <footer class="flex items-center justify-between gap-3 border-t border-gray-100 pt-3 text-xs dark:border-white/5">
                            <span class="font-medium text-gray-500 dark:text-gray-400">
                                {{ $sensor['type'] === 'contact' ? 'Contactsensor' : 'Bewegingssensor' }}
                            </span>

                            @if ($sensor['battery'] !== null)
                                <div class="flex items-center gap-2 tabular-nums">
                                    <div class="h-1.5 w-12 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800" aria-hidden="true">
                                        <div
                                            @class([
                                                'h-full rounded-full',
                                                'bg-emerald-500' => $sensor['battery'] >= 50,
                                                'bg-amber-500' => $sensor['battery'] >= 20 && $sensor['battery'] < 50,
                                                'bg-rose-500' => $sensor['battery'] < 20,
                                            ])
                                            style="width: {{ max(6, min(100, $sensor['battery'])) }}%"
                                        ></div>
                                    </div>
                                    <span @class([
                                        'font-semibold',
                                        'text-gray-700 dark:text-gray-300' => $sensor['battery'] >= 20,
                                        'text-rose-600 dark:text-rose-400' => $sensor['battery'] < 20,
                                    ])>
                                        {{ $sensor['battery'] }}%
                                    </span>
                                </div>
                            @else
                                <span class="text-gray-400 dark:text-gray-500">Netstroom</span>
                            @endif
                        </footer>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
