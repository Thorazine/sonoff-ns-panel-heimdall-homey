<x-filament-panels::page>
    @php
        $menu = $this->currentMenu;
        $breadcrumbs = $menu->breadcrumbs();
        /** @var \App\Models\KioskMenuBlock|null $activeBlock */
        $activeBlock = $menu->blocks->firstWhere('position', $selectedPosition);
        $previewColor = \App\Enums\MenuBlockColor::tryFrom($blockColor) ?? \App\Enums\MenuBlockColor::Sky;
        $previewIcon = \App\Enums\MenuBlockIcon::tryFrom($blockIcon) ?? \App\Enums\MenuBlockIcon::Grid;
    @endphp

    <div class="space-y-6">
        {{-- Breadcrumb Navigation Bar --}}
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-xs dark:border-white/10 dark:bg-gray-900 flex flex-wrap items-center justify-between gap-4">
            <nav aria-label="Menupad" class="flex flex-wrap items-center gap-2">
                @foreach ($breadcrumbs as $index => $crumb)
                    @if ($index > 0)
                        <span aria-hidden="true" class="text-gray-400 dark:text-gray-600">›</span>
                    @endif

                    <button
                        type="button"
                        wire:click="openMenu({{ $crumb['id'] }})"
                        @if ($crumb['id'] === $menu->id) aria-current="page" @endif
                        @class([
                            'inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-sm font-semibold transition-colors cursor-pointer',
                            'bg-teal-500/15 text-teal-700 border border-teal-500/40 dark:text-teal-300' => $crumb['id'] === $menu->id,
                            'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' => $crumb['id'] !== $menu->id,
                        ])
                    >
                        @if ($crumb['is_root'])
                            <x-kiosk-icon icon="home" class="h-4 w-4" />
                        @else
                            <x-kiosk-icon icon="grid" class="h-4 w-4" />
                        @endif
                        <span>{{ $crumb['title'] }}</span>
                    </button>
                @endforeach
            </nav>

            @if (! $menu->is_root)
                <button
                    type="button"
                    wire:click="goUp"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-100 dark:border-white/10 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 cursor-pointer"
                >
                    ← Niveau omhoog (4 blocks)
                </button>
            @else
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    Klik op een van de 4 vakken om een <strong>Module</strong> of een volgend <strong>Submenu (4 blocks)</strong> te koppelen.
                </span>
            @endif
        </div>

        {{-- Main 2-Column Builder: Left = 2x2 Kiosk Grid, Right = Selected Block Editor --}}
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12 items-start">
            {{-- Left Column: Interactive 2x2 (4 Blocks) Kiosk Screen Preview --}}
            <div class="xl:col-span-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-gray-900 space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-gray-950 dark:text-white">
                            {{ $menu->title }} — 4 Blocks Weergave
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Selecteer een vak in het 2x2 Kiosk-raster om het in te richten.
                        </p>
                    </div>

                    <x-filament::badge color="info">
                        {{ $menu->filledBlocksCount() }} / 4 gevuld
                    </x-filament::badge>
                </div>

                @if (! $menu->is_root)
                    <form wire:submit="saveMenuTitle" class="flex items-center gap-2">
                        <label for="submenu-title-input" class="sr-only">Submenu naam</label>
                        <input
                            id="submenu-title-input"
                            type="text"
                            wire:model="menuTitle"
                            maxlength="40"
                            placeholder="Submenu naam"
                            class="flex-1 rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-white/15 dark:bg-gray-950 dark:text-white"
                        />
                        <x-filament::button type="submit" size="sm" color="gray">
                            Hernoem
                        </x-filament::button>
                    </form>
                @endif

                {{-- Simulated 480x480 Dark Kiosk Frame --}}
                <div class="mx-auto aspect-square w-full max-w-[440px] rounded-3xl border border-gray-800 bg-black p-4 flex flex-col gap-3 shadow-lg">
                    @if (! $menu->is_root)
                        <div class="text-center text-xs font-bold tracking-widest uppercase text-gray-400">
                            {{ $menu->title }}
                        </div>
                    @endif

                    <div class="flex-1 grid grid-cols-2 grid-rows-2 gap-3 min-h-0">
                        @foreach ($menu->blocks as $block)
                            @php
                                $isSelected = $selectedPosition === $block->position;
                            @endphp

                            <div class="relative w-full h-full">
                                <button
                                    type="button"
                                    wire:key="builder-tile-{{ $block->id }}"
                                    wire:click="selectBlock({{ $block->position }})"
                                    aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                                    @class([
                                        'w-full h-full rounded-2xl border p-3 flex flex-col items-center justify-center gap-2 transition-all cursor-pointer text-center relative',
                                        $block->resolvedColor()->adminPreviewClasses() => $block->type !== \App\Enums\MenuBlockType::Empty,
                                        'border-dashed border-gray-800 bg-gray-950/70 text-gray-500 hover:border-gray-600' => $block->type === \App\Enums\MenuBlockType::Empty,
                                        'ring-3 ring-teal-400 ring-offset-2 ring-offset-black' => $isSelected,
                                    ])
                                >
                                    <span class="absolute top-2 left-2.5 rounded-md bg-black/70 px-1.5 py-0.5 text-[10px] font-semibold text-gray-400 border border-white/10">
                                        Vak {{ $block->position }}
                                    </span>

                                    @if ($block->type === \App\Enums\MenuBlockType::Empty)
                                        <span class="text-xs font-medium text-gray-500">Leeg vak</span>
                                        <span class="text-[11px] text-gray-600">Klik om in te stellen</span>
                                    @else
                                        <x-kiosk-icon :icon="$block->resolvedIcon()" class="h-11 w-11 shrink-0 mt-2" />

                                        <span class="text-sm font-bold tracking-wider uppercase leading-tight line-clamp-2">
                                            {{ $block->resolvedLabel() }}
                                        </span>

                                        <span class="rounded-full bg-black/60 border border-white/10 px-2 py-0.5 text-[10px] font-medium text-gray-300">
                                            @if ($block->type === \App\Enums\MenuBlockType::Module)
                                                Module: {{ $block->module_key?->defaultButtonLabel() }}
                                            @else
                                                Submenu ({{ $block->submenu ? $block->submenu->filledBlocksCount() : 0 }}/4)
                                            @endif
                                        </span>
                                    @endif
                                </button>

                                @if ($block->type === \App\Enums\MenuBlockType::Submenu && $block->submenu_id !== null)
                                    <button
                                        type="button"
                                        wire:click="openMenu({{ $block->submenu_id }})"
                                        class="absolute top-2 right-2 z-10 rounded-lg bg-sky-500 px-2 py-1 text-[10px] font-bold text-gray-950 hover:bg-sky-400 transition-colors shadow cursor-pointer"
                                    >
                                        Open 4 blocks →
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="w-full py-2.5 rounded-xl bg-gray-900 border border-gray-800 text-gray-500 text-xs font-semibold text-center select-none">
                        {{ $menu->is_root ? 'Annuleren' : 'Terug' }}
                    </div>
                </div>
            </div>

            {{-- Right Column: Selected Block Configuration Form --}}
            <div class="xl:col-span-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-xs dark:border-white/10 dark:bg-gray-900 space-y-6">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-4 dark:border-white/10">
                    <div>
                        <h2 class="text-base font-bold text-gray-950 dark:text-white">
                            {{ $activeBlock?->positionLabel() ?? "Vak {$selectedPosition}" }} instellen
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Kies of onder deze knop een volgend 4-blocks submenu of een eind-module zit.
                        </p>
                    </div>

                    {{-- Quick Position Swap --}}
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs text-gray-400 mr-1">Wissel met:</span>
                        @foreach (\App\Models\KioskMenu::POSITIONS as $pos)
                            @if ($pos !== $selectedPosition)
                                <button
                                    type="button"
                                    wire:click="swapWithPosition({{ $pos }})"
                                    class="rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-100 dark:border-white/10 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 cursor-pointer"
                                >
                                    Vak {{ $pos }}
                                </button>
                            @endif
                        @endforeach
                    </div>
                </div>

                <form wire:submit="saveBlock" class="space-y-5">
                    {{-- Step 1: Block Type Selector --}}
                    <fieldset class="space-y-2">
                        <legend class="text-sm font-semibold text-gray-900 dark:text-white">
                            1. Wat doet deze knop?
                        </legend>

                        <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
                            @foreach (\App\Enums\MenuBlockType::cases() as $typeOption)
                                <label
                                    @class([
                                        'flex flex-col gap-1 rounded-xl border p-3.5 cursor-pointer transition-all',
                                        'border-teal-500 bg-teal-500/10 ring-1 ring-teal-500' => $blockType === $typeOption->value,
                                        'border-gray-200 bg-gray-50/50 hover:bg-gray-100/60 dark:border-white/10 dark:bg-gray-950/50 dark:hover:bg-gray-800/60' => $blockType !== $typeOption->value,
                                    ])
                                >
                                    <span class="flex items-center gap-2 text-sm font-bold text-gray-900 dark:text-white">
                                        <input
                                            type="radio"
                                            name="blockType"
                                            value="{{ $typeOption->value }}"
                                            wire:model.live="blockType"
                                            class="text-teal-600 focus:ring-teal-500"
                                        />
                                        <span>{{ $typeOption->getLabel() }}</span>
                                    </span>
                                    <span class="text-[11px] text-gray-500 dark:text-gray-400 pl-6">
                                        @if ($typeOption === \App\Enums\MenuBlockType::Empty)
                                            Onklikbaar leeg vak
                                        @elseif ($typeOption === \App\Enums\MenuBlockType::Submenu)
                                            Opent weer 4 nieuwe blocks
                                        @else
                                            Opent een volledig modulescherm
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    @if ($blockType === \App\Enums\MenuBlockType::Module->value)
                        {{-- Step 2A: Module Selector --}}
                        <div class="space-y-2">
                            <label for="block-module-select" class="block text-sm font-semibold text-gray-900 dark:text-white">
                                2. Gekoppelde Module
                            </label>
                            <select
                                id="block-module-select"
                                wire:model.live="blockModuleKey"
                                class="w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 dark:border-white/15 dark:bg-gray-950 dark:text-white"
                            >
                                @foreach (\App\Enums\KioskModule::cases() as $mod)
                                    <option value="{{ $mod->value }}">{{ $mod->getLabel() }}</option>
                                @endforeach
                            </select>

                            @error('blockModuleKey')
                                <p class="text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror

                            @if ($selectedMod = \App\Enums\KioskModule::tryFrom((string) $blockModuleKey))
                                <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 text-xs text-gray-600 dark:border-white/10 dark:bg-gray-950 dark:text-gray-400 flex items-center justify-between gap-3">
                                    <span>{{ $selectedMod->description() }}</span>
                                    <a
                                        href="{{ \App\Filament\Pages\ModulesOverview::getUrl() }}"
                                        class="shrink-0 font-semibold text-teal-600 hover:underline dark:text-teal-400"
                                    >
                                        Opties →
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if ($blockType !== \App\Enums\MenuBlockType::Empty->value)
                        {{-- Step 3: Button Appearance (Label, Icon, Color) --}}
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2 space-y-1.5">
                                <label for="block-label-input" class="block text-sm font-semibold text-gray-900 dark:text-white">
                                    Tekst op de knop
                                </label>
                                <input
                                    id="block-label-input"
                                    type="text"
                                    wire:model="blockLabel"
                                    maxlength="40"
                                    placeholder="{{ $blockType === 'submenu' ? 'Bijv. VERLICHTING of BEGANE GROND' : 'Standaard modulenaam' }}"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 dark:border-white/15 dark:bg-gray-950 dark:text-white"
                                />
                                @error('blockLabel')
                                    <p class="text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-1.5">
                                <label for="block-icon-select" class="block text-sm font-semibold text-gray-900 dark:text-white">
                                    Icoon
                                </label>
                                <select
                                    id="block-icon-select"
                                    wire:model.live="blockIcon"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 dark:border-white/15 dark:bg-gray-950 dark:text-white"
                                >
                                    @foreach (\App\Enums\MenuBlockIcon::options() as $val => $label)
                                        <option value="{{ $val }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="space-y-1.5">
                                <label for="block-color-select" class="block text-sm font-semibold text-gray-900 dark:text-white">
                                    Accentkleur
                                </label>
                                <select
                                    id="block-color-select"
                                    wire:model.live="blockColor"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 dark:border-white/15 dark:bg-gray-950 dark:text-white"
                                >
                                    @foreach (\App\Enums\MenuBlockColor::options() as $val => $label)
                                        <option value="{{ $val }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Live Mini Button Preview --}}
                        <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-950 p-3 dark:border-white/10">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl border {{ $previewColor->adminPreviewClasses() }}">
                                <x-kiosk-icon :icon="$previewIcon" class="h-6 w-6" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-[11px] uppercase tracking-wider text-gray-400">Voorbeeld op Kiosk</p>
                                <p class="truncate text-sm font-bold uppercase tracking-widest text-white">
                                    {{ trim($blockLabel) !== '' ? $blockLabel : ($blockType === 'submenu' ? 'SUBMENU' : 'MODULE') }}
                                </p>
                            </div>
                        </div>
                    @endif

                    @if ($activeBlock?->type === \App\Enums\MenuBlockType::Submenu && $blockType !== \App\Enums\MenuBlockType::Submenu->value)
                        <div role="alert" class="rounded-xl border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-xs text-amber-700 dark:text-amber-300">
                            <strong>Let op:</strong> Dit vak bevat momenteel een submenu. Bij opslaan als '{{ \App\Enums\MenuBlockType::from($blockType)->getLabel() }}' worden de onderliggende 4 blocks van dit submenu verwijderd.
                        </div>
                    @endif

                    {{-- Action Buttons --}}
                    <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-gray-100 dark:border-white/10">
                        <div>
                            @if ($activeBlock && $activeBlock->type !== \App\Enums\MenuBlockType::Empty)
                                <x-filament::button
                                    type="button"
                                    color="danger"
                                    outlined
                                    wire:click="clearSelectedBlock"
                                >
                                    Vak leegmaken
                                </x-filament::button>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center gap-2.5">
                            @if ($blockType === \App\Enums\MenuBlockType::Submenu->value)
                                <x-filament::button
                                    type="button"
                                    color="info"
                                    wire:click="saveAndOpenSubmenu"
                                >
                                    Opslaan &amp; 4 blocks invullen →
                                </x-filament::button>
                            @endif

                            <x-filament::button type="submit">
                                Vak {{ $selectedPosition }} opslaan
                            </x-filament::button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Complete Menu Hierarchy Tree Overview --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs dark:border-white/10 dark:bg-gray-900 space-y-4">
            <div>
                <h2 class="text-base font-bold text-gray-950 dark:text-white">
                    Volledige Menustructuur (4 Blocks Boomweergave)
                </h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Overzicht van alle 4-blocks lagen tot aan de gekoppelde eind-modules. Klik op een menu om direct naar die 4 blocks te springen.
                </p>
            </div>

            @include('filament.pages.partials.menu-tree-node', [
                'node' => $this->menuTree,
                'activeMenuId' => $menu->id,
                'depth' => 0,
            ])
        </div>
    </div>
</x-filament-panels::page>
