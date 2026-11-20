<x-filament-panels::page>
    @php
        $panels = $this->panels;
        $panel = $this->activePanel;
        $menu = $this->currentMenu;
        $breadcrumbs = $menu->breadcrumbs();
        /** @var \App\Models\KioskMenuBlock|null $activeBlock */
        $activeBlock = $menu->blocks->firstWhere('position', $selectedPosition);
        $previewColor = \App\Enums\MenuBlockColor::tryFrom($blockColor) ?? \App\Enums\MenuBlockColor::Sky;
        $previewIcon = \App\Enums\MenuBlockIcon::tryFrom($blockIcon) ?? \App\Enums\MenuBlockIcon::Grid;
        $panelUrl = route('kiosk.wall-panel', ['wallPanel' => $panel->slug]);
    @endphp

    <div class="space-y-6">
        {{-- Panel Selector Tabs + Create New Large Screen --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-gray-900 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-gray-950 dark:text-white">
                        Kies welk groot Android scherm (~30×15 cm) je wilt inrichten
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Elk groot wandpaneel heeft zijn eigen losse 8-blocks menustructuur, eigen URL en eigen deurbel-gedrag.
                    </p>
                </div>

                <x-filament::button
                    type="button"
                    size="sm"
                    color="info"
                    wire:click="toggleCreatePanelForm"
                >
                    {{ $showCreatePanelForm ? 'Sluiten' : '+ Nieuw groot scherm toevoegen' }}
                </x-filament::button>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                @foreach ($panels as $item)
                    @php
                        $isCurrent = $item->id === $panel->id;
                    @endphp
                    <button
                        type="button"
                        wire:key="panel-tab-{{ $item->id }}"
                        wire:click="selectPanel({{ $item->id }})"
                        @class([
                            'flex items-center gap-2.5 rounded-xl border px-4 py-2.5 text-left transition-all cursor-pointer',
                            'border-teal-500 bg-teal-500/10 text-teal-800 ring-1 ring-teal-500 dark:text-teal-200' => $isCurrent,
                            'border-gray-200 bg-gray-50 text-gray-700 hover:bg-gray-100 dark:border-white/10 dark:bg-gray-800/80 dark:text-gray-200 dark:hover:bg-gray-800' => ! $isCurrent,
                        ])
                    >
                        <x-kiosk-icon icon="video-camera" class="h-4 w-4 shrink-0" />
                        <div>
                            <div class="text-sm font-bold leading-tight">{{ $item->name }}</div>
                            <div class="text-[11px] opacity-75">/panel/{{ $item->slug }}</div>
                        </div>
                        @if ($item->show_doorbell_popup)
                            <span class="rounded-md bg-amber-500/15 px-1.5 py-0.5 text-[10px] font-bold text-amber-600 dark:text-amber-300">
                                5-Cam Bel
                            </span>
                        @endif
                    </button>
                @endforeach
            </div>

            @if ($showCreatePanelForm)
                <form wire:submit="createPanel" class="rounded-xl border border-sky-500/30 bg-sky-500/5 p-4 grid grid-cols-1 gap-4 sm:grid-cols-12 items-end">
                    <div class="sm:col-span-5 space-y-1">
                        <label for="new-panel-name" class="block text-xs font-semibold text-gray-900 dark:text-white">
                            Naam nieuw scherm
                        </label>
                        <input
                            id="new-panel-name"
                            type="text"
                            wire:model="newPanelName"
                            maxlength="60"
                            placeholder="Bijv. Zolder of Woonkeuken"
                            class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-white/15 dark:bg-gray-950 dark:text-white"
                        />
                        @error('newPanelName')
                            <p class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-5 space-y-1">
                        <label for="new-panel-location" class="block text-xs font-semibold text-gray-900 dark:text-white">
                            Locatie / omschrijving (optioneel)
                        </label>
                        <input
                            id="new-panel-location"
                            type="text"
                            wire:model="newPanelLocation"
                            maxlength="80"
                            placeholder="Bijv. Wandpaneel 2e verdieping"
                            class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-white/15 dark:bg-gray-950 dark:text-white"
                        />
                    </div>

                    <div class="sm:col-span-2">
                        <x-filament::button type="submit" class="w-full">
                            Aanmaken
                        </x-filament::button>
                    </div>
                </form>
            @endif

            {{-- Active Panel Settings Bar --}}
            <form wire:submit="savePanelSettings" class="border-t border-gray-100 pt-4 dark:border-white/10 grid grid-cols-1 gap-4 lg:grid-cols-12 items-end">
                <div class="lg:col-span-3 space-y-1">
                    <label for="panel-name-input" class="block text-xs font-semibold text-gray-700 dark:text-gray-300">
                        Schermnaam
                    </label>
                    <input
                        id="panel-name-input"
                        type="text"
                        wire:model="panelName"
                        maxlength="60"
                        class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-white/15 dark:bg-gray-950 dark:text-white"
                    />
                </div>

                <div class="lg:col-span-3 space-y-1">
                    <label for="panel-location-input" class="block text-xs font-semibold text-gray-700 dark:text-gray-300">
                        Locatie
                    </label>
                    <input
                        id="panel-location-input"
                        type="text"
                        wire:model="panelLocation"
                        maxlength="80"
                        class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-white/15 dark:bg-gray-950 dark:text-white"
                    />
                </div>

                <div class="lg:col-span-3 flex flex-col gap-2 pb-1">
                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-gray-800 dark:text-gray-200 cursor-pointer">
                        <input
                            type="checkbox"
                            wire:model="panelShowDoorbellPopup"
                            class="rounded text-teal-600 focus:ring-teal-500"
                        />
                        <span>5 camera's tonen zodra er wordt aangebeld</span>
                    </label>
                    <label class="inline-flex items-center gap-2 text-xs font-medium text-gray-600 dark:text-gray-400 cursor-pointer">
                        <input
                            type="checkbox"
                            wire:model="panelIsActive"
                            class="rounded text-teal-600 focus:ring-teal-500"
                        />
                        <span>Scherm-URL actief (<code>/panel/{{ $panel->slug }}</code>)</span>
                    </label>
                </div>

                <div class="lg:col-span-3 flex flex-wrap items-center justify-end gap-2">
                    <a
                        href="{{ $panelUrl }}"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-100 dark:border-white/10 dark:bg-gray-800 dark:text-gray-200"
                    >
                        Open scherm ↗
                    </a>

                    @if ($panels->count() > 1)
                        <x-filament::button
                            type="button"
                            size="sm"
                            color="danger"
                            outlined
                            wire:click="deleteActivePanel"
                            wire:confirm="Weet je zeker dat je het scherm '{{ $panel->name }}' en de bijbehorende menu's wilt verwijderen?"
                        >
                            Verwijder
                        </x-filament::button>
                    @endif

                    <x-filament::button type="submit" size="sm">
                        Scherm opslaan
                    </x-filament::button>
                </div>
            </form>
        </div>

        {{-- Breadcrumb Bar --}}
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-xs dark:border-white/10 dark:bg-gray-900 flex flex-wrap items-center justify-between gap-4">
            <nav aria-label="Menupad groot scherm" class="flex flex-wrap items-center gap-2">
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
                        <x-kiosk-icon :icon="$crumb['is_root'] ? 'home' : 'grid'" class="h-4 w-4" />
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
                    ← Niveau omhoog (8 blocks)
                </button>
            @else
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    Breedbeeld indeling voor <strong>{{ $panel->name }}</strong>: steeds van 8 blocks (4×2) naar 8 blocks tot een eind-module.
                </span>
            @endif
        </div>

        {{-- Main Builder Grid: Widescreen 2:1 Preview + Block Editor --}}
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12 items-start">
            {{-- Left 7 Cols: Simulated 30x15cm (2:1) Widescreen Android Panel --}}
            <div class="xl:col-span-7 rounded-2xl border border-gray-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-gray-900 space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-gray-950 dark:text-white">
                            {{ $menu->title }} — 8 Blocks Breedbeeld (4×2)
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Simulatie van het 30×15 cm Android scherm met links de statusbalk en rechts de 8 knoppen.
                        </p>
                    </div>

                    <x-filament::badge color="info">
                        {{ $menu->filledBlocksCount() }} / 8 gevuld
                    </x-filament::badge>
                </div>

                @if (! $menu->is_root)
                    <form wire:submit="saveMenuTitle" class="flex items-center gap-2">
                        <label for="wp-submenu-title" class="sr-only">Submenu naam</label>
                        <input
                            id="wp-submenu-title"
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

                {{-- Simulated ~2:1 Widescreen Wall Panel Frame --}}
                <div class="w-full aspect-[2/1] rounded-3xl border border-gray-800 bg-black p-3.5 flex gap-3 shadow-xl overflow-clip">
                    {{-- Simulated Left Sidebar --}}
                    <div class="w-1/4 rounded-2xl border border-gray-800/90 bg-gray-950 p-3 flex flex-col justify-between text-gray-300 select-none">
                        <div>
                            <p class="truncate text-[9px] font-bold uppercase tracking-widest text-teal-300">{{ $panel->name }}</p>
                            <p class="text-2xl font-black text-white leading-tight mt-0.5">14:30</p>
                            <p class="text-[10px] text-gray-500">Status &amp; Weer</p>
                        </div>

                        <div class="space-y-1.5">
                            <div class="rounded-lg border border-emerald-500/30 bg-emerald-950/30 px-2 py-1 text-[9px] font-bold text-emerald-300 truncate">
                                Alarm Status
                            </div>
                            <div class="rounded-lg border border-sky-500/30 bg-sky-950/30 px-2 py-1 text-[9px] font-bold text-sky-300 truncate">
                                Snelknop Camera's
                            </div>
                        </div>
                    </div>

                    {{-- Interactive 4x2 (8 Blocks) Grid --}}
                    <div class="flex-1 min-w-0 grid grid-cols-4 grid-rows-2 gap-2.5">
                        @foreach ($menu->blocks as $block)
                            @php
                                $isSelected = $selectedPosition === $block->position;
                            @endphp

                            <div class="relative w-full h-full min-h-0">
                                <button
                                    type="button"
                                    wire:key="wp-builder-tile-{{ $block->id }}"
                                    wire:click="selectBlock({{ $block->position }})"
                                    aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                                    @class([
                                        'w-full h-full rounded-2xl border p-2 flex flex-col items-center justify-center gap-1.5 transition-all cursor-pointer text-center relative',
                                        $block->resolvedColor()->adminPreviewClasses() => $block->type !== \App\Enums\MenuBlockType::Empty,
                                        'border-dashed border-gray-800 bg-gray-950/70 text-gray-500 hover:border-gray-600' => $block->type === \App\Enums\MenuBlockType::Empty,
                                        'ring-3 ring-teal-400 ring-offset-2 ring-offset-black' => $isSelected,
                                    ])
                                >
                                    <span class="absolute top-1.5 left-2 rounded bg-black/70 px-1 py-0.5 text-[9px] font-semibold text-gray-400 border border-white/10">
                                        {{ $block->position }}
                                    </span>

                                    @if ($block->type === \App\Enums\MenuBlockType::Empty)
                                        <span class="text-[11px] font-medium text-gray-500">Leeg</span>
                                    @else
                                        <x-kiosk-icon :icon="$block->resolvedIcon()" class="h-7 w-7 shrink-0 mt-1" />
                                        <span class="text-[11px] font-bold tracking-wider uppercase leading-tight line-clamp-2">
                                            {{ $block->resolvedLabel() }}
                                        </span>
                                        <span class="rounded-full bg-black/60 border border-white/10 px-1.5 py-0.5 text-[9px] text-gray-300 truncate max-w-full">
                                            @if ($block->type === \App\Enums\MenuBlockType::Module)
                                                {{ $block->module_key?->defaultButtonLabel() }}
                                            @else
                                                Sub ({{ $block->submenu ? $block->submenu->filledBlocksCount() : 0 }}/8)
                                            @endif
                                        </span>
                                    @endif
                                </button>

                                @if ($block->type === \App\Enums\MenuBlockType::Submenu && $block->submenu_id !== null)
                                    <button
                                        type="button"
                                        wire:click="openMenu({{ $block->submenu_id }})"
                                        class="absolute top-1.5 right-1.5 z-10 rounded-md bg-sky-500 px-1.5 py-0.5 text-[9px] font-bold text-gray-950 hover:bg-sky-400 shadow cursor-pointer"
                                    >
                                        8 blocks →
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Right 5 Cols: Selected Block Configuration --}}
            <div class="xl:col-span-5 rounded-2xl border border-gray-200 bg-white p-6 shadow-xs dark:border-white/10 dark:bg-gray-900 space-y-5">
                <div class="space-y-3 border-b border-gray-100 pb-4 dark:border-white/10">
                    <div>
                        <h2 class="text-base font-bold text-gray-950 dark:text-white">
                            {{ $activeBlock?->positionLabel() ?? "Vak {$selectedPosition}" }} op {{ $panel->name }}
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Koppel een eind-module (zoals Reolink Camera's) of een volgend 8-blocks submenu.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-1">
                        <span class="text-xs text-gray-400 mr-1">Wissel met:</span>
                        @foreach (\App\Filament\Pages\WallPanelBuilder::POSITIONS as $pos)
                            @if ($pos !== $selectedPosition)
                                <button
                                    type="button"
                                    wire:click="swapWithPosition({{ $pos }})"
                                    class="rounded-lg border border-gray-200 bg-gray-50 px-2 py-0.5 text-[11px] font-semibold text-gray-700 hover:bg-gray-100 dark:border-white/10 dark:bg-gray-800 dark:text-gray-300 cursor-pointer"
                                >
                                    {{ $pos }}
                                </button>
                            @endif
                        @endforeach
                    </div>
                </div>

                <form wire:submit="saveBlock" class="space-y-5">
                    <fieldset class="space-y-2">
                        <legend class="text-sm font-semibold text-gray-900 dark:text-white">
                            1. Wat doet deze knop?
                        </legend>

                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                            @foreach (\App\Enums\MenuBlockType::cases() as $typeOption)
                                <label
                                    @class([
                                        'flex flex-col gap-1 rounded-xl border p-3 cursor-pointer transition-all',
                                        'border-teal-500 bg-teal-500/10 ring-1 ring-teal-500' => $blockType === $typeOption->value,
                                        'border-gray-200 bg-gray-50/50 hover:bg-gray-100/60 dark:border-white/10 dark:bg-gray-950/50' => $blockType !== $typeOption->value,
                                    ])
                                >
                                    <span class="flex items-center gap-2 text-xs font-bold text-gray-900 dark:text-white">
                                        <input
                                            type="radio"
                                            name="wpBlockType"
                                            value="{{ $typeOption->value }}"
                                            wire:model.live="blockType"
                                            class="text-teal-600 focus:ring-teal-500"
                                        />
                                        <span>{{ $typeOption->getLabel() }}</span>
                                    </span>
                                    <span class="text-[10px] text-gray-500 dark:text-gray-400 pl-5">
                                        @if ($typeOption === \App\Enums\MenuBlockType::Empty)
                                            Leeg vak
                                        @elseif ($typeOption === \App\Enums\MenuBlockType::Submenu)
                                            Opent 8 nieuwe blocks
                                        @else
                                            Opent module op paneel
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    @if ($blockType === \App\Enums\MenuBlockType::Module->value)
                        <div class="space-y-2">
                            <label for="wp-block-module" class="block text-sm font-semibold text-gray-900 dark:text-white">
                                2. Gekoppelde Module
                            </label>
                            <select
                                id="wp-block-module"
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
                                        href="{{ $selectedMod === \App\Enums\KioskModule::Cameras ? \App\Filament\Pages\ManageCameras::getUrl() : \App\Filament\Pages\ModulesOverview::getUrl() }}"
                                        class="shrink-0 font-semibold text-teal-600 hover:underline dark:text-teal-400"
                                    >
                                        Instellen →
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if ($blockType !== \App\Enums\MenuBlockType::Empty->value)
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2 space-y-1.5">
                                <label for="wp-block-label" class="block text-sm font-semibold text-gray-900 dark:text-white">
                                    Tekst op de knop
                                </label>
                                <input
                                    id="wp-block-label"
                                    type="text"
                                    wire:model="blockLabel"
                                    maxlength="40"
                                    placeholder="{{ $blockType === 'submenu' ? 'Bijv. BOVENVERDIEPING of WELLNESS' : 'Standaard modulenaam' }}"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 dark:border-white/15 dark:bg-gray-950 dark:text-white"
                                />
                                @error('blockLabel')
                                    <p class="text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-1.5">
                                <label for="wp-block-icon" class="block text-sm font-semibold text-gray-900 dark:text-white">
                                    Icoon
                                </label>
                                <select
                                    id="wp-block-icon"
                                    wire:model.live="blockIcon"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 dark:border-white/15 dark:bg-gray-950 dark:text-white"
                                >
                                    @foreach (\App\Enums\MenuBlockIcon::options() as $val => $label)
                                        <option value="{{ $val }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="space-y-1.5">
                                <label for="wp-block-color" class="block text-sm font-semibold text-gray-900 dark:text-white">
                                    Accentkleur
                                </label>
                                <select
                                    id="wp-block-color"
                                    wire:model.live="blockColor"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 dark:border-white/15 dark:bg-gray-950 dark:text-white"
                                >
                                    @foreach (\App\Enums\MenuBlockColor::options() as $val => $label)
                                        <option value="{{ $val }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-950 p-3 dark:border-white/10">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl border {{ $previewColor->adminPreviewClasses() }}">
                                <x-kiosk-icon :icon="$previewIcon" class="h-6 w-6" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-[11px] uppercase tracking-wider text-gray-400">Voorbeeld op {{ $panel->name }}</p>
                                <p class="truncate text-sm font-bold uppercase tracking-widest text-white">
                                    {{ trim($blockLabel) !== '' ? $blockLabel : ($blockType === 'submenu' ? 'SUBMENU' : 'MODULE') }}
                                </p>
                            </div>
                        </div>
                    @endif

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

                        <div class="flex flex-wrap items-center gap-2">
                            @if ($blockType === \App\Enums\MenuBlockType::Submenu->value)
                                <x-filament::button
                                    type="button"
                                    color="info"
                                    wire:click="saveAndOpenSubmenu"
                                >
                                    Opslaan &amp; 8 blocks invullen →
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

        {{-- Complete 8-Blocks Menu Hierarchy Tree --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs dark:border-white/10 dark:bg-gray-900 space-y-4">
            <div>
                <h2 class="text-base font-bold text-gray-950 dark:text-white">
                    Menustructuur van {{ $panel->name }} (8 Blocks Boomweergave)
                </h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Overzicht van alle 8-blocks lagen op <code>/panel/{{ $panel->slug }}</code>.
                </p>
            </div>

            @include('filament.pages.partials.menu-tree-node', [
                'node' => $this->menuTree,
                'activeMenuId' => $menu->id,
                'depth' => 0,
                'gridSize' => 8,
            ])
        </div>
    </div>
</x-filament-panels::page>
