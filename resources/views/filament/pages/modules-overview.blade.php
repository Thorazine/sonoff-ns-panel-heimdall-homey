<x-filament-panels::page>
    <div wire:init="loadFlowOptions" class="space-y-6">
        {{-- Overview Grid of All 9 Available Kiosk Modules --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @php
                $placements = $this->modulePlacements;
            @endphp

            @foreach (\App\Enums\KioskModule::cases() as $module)
                @php
                    $linkedSpots = $placements[$module->value] ?? [];
                    $isSelected = $activeModule === $module->value;
                    $accent = $module->defaultColor();
                @endphp

                <article
                    @class([
                        'rounded-2xl border p-5 shadow-xs flex flex-col justify-between gap-4 transition-all',
                        'border-teal-500 ring-2 ring-teal-500/25 bg-teal-50/30 dark:border-teal-400 dark:bg-teal-950/20' => $isSelected,
                        'border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900' => ! $isSelected,
                    ])
                >
                    <div class="space-y-3">
                        <header class="flex items-start justify-between gap-3">
                            <div
                                @class([
                                    'flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border',
                                    'border-teal-500/30 bg-teal-500/10 text-teal-600 dark:text-teal-400' => $accent === \App\Enums\MenuBlockColor::Flashy,
                                    'border-sky-500/30 bg-sky-500/10 text-sky-600 dark:text-sky-400' => $accent === \App\Enums\MenuBlockColor::Sky,
                                    'border-amber-500/30 bg-amber-500/10 text-amber-600 dark:text-amber-400' => $accent === \App\Enums\MenuBlockColor::Amber,
                                    'border-violet-500/30 bg-violet-500/10 text-violet-600 dark:text-violet-400' => $accent === \App\Enums\MenuBlockColor::Violet,
                                    'border-emerald-500/30 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' => $accent === \App\Enums\MenuBlockColor::Emerald,
                                    'border-rose-500/30 bg-rose-500/10 text-rose-600 dark:text-rose-400' => $accent === \App\Enums\MenuBlockColor::Rose,
                                    'border-gray-400/30 bg-gray-500/10 text-gray-600 dark:text-gray-300' => $accent === \App\Enums\MenuBlockColor::Gray,
                                ])
                            >
                                <x-kiosk-icon :icon="$module->defaultIcon()" class="h-6 w-6" />
                            </div>

                            <x-filament::badge :color="count($linkedSpots) > 0 ? 'success' : 'gray'">
                                {{ count($linkedSpots) > 0 ? count($linkedSpots).'x gekoppeld' : 'Niet gekoppeld' }}
                            </x-filament::badge>
                        </header>

                        <div>
                            <h2 class="text-base font-bold text-gray-950 dark:text-white">
                                {{ $module->getLabel() }}
                            </h2>
                            <p class="mt-1 text-xs leading-relaxed text-gray-600 dark:text-gray-400">
                                {{ $module->description() }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-3 border-t border-gray-100 pt-3 dark:border-white/10">
                        <div class="min-h-[2rem]">
                            @if (count($linkedSpots) > 0)
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                                    Actief onder knop:
                                </p>
                                <ul role="list" class="mt-1 space-y-0.5 text-xs font-medium text-gray-700 dark:text-gray-300">
                                    @foreach ($linkedSpots as $spot)
                                        <li class="truncate" title="{{ $spot }}">• {{ $spot }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-xs text-gray-400 dark:text-gray-500">
                                    Koppel deze module aan een knop in de Menu Builder.
                                </p>
                            @endif
                        </div>

                        @if ($module === \App\Enums\KioskModule::Cameras)
                            <a
                                href="{{ \App\Filament\Pages\ManageCameras::getUrl() }}"
                                class="block w-full text-center rounded-xl border border-sky-500/40 bg-sky-500/10 px-3 py-2 text-xs font-semibold text-sky-700 hover:bg-sky-500/20 transition-colors dark:text-sky-300"
                            >
                                Camera's &amp; 2-Weg Audio instellen →
                            </a>
                        @else
                            <button
                                type="button"
                                wire:click="selectModuleFilter('{{ $module->value }}')"
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-100 transition-colors dark:border-white/10 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 cursor-pointer"
                            >
                                {{ $isSelected ? 'Toon alle '.count(\App\Enums\KioskModule::cases()).' modules' : 'Opties van deze module openen' }}
                            </button>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Module Options Form --}}
        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <div class="flex items-center justify-end gap-3 pt-2">
                @if ($activeModule !== null)
                    <x-filament::button type="button" color="gray" wire:click="selectModuleFilter(null)">
                        Alle modules tonen
                    </x-filament::button>
                @endif

                <x-filament::button type="submit" size="lg">
                    Module-opties Opslaan
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
