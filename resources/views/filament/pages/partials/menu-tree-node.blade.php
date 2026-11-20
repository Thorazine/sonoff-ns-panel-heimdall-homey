@props(['node', 'activeMenuId', 'depth' => 0, 'gridSize' => 4])

<div class="{{ $depth > 0 ? 'ml-5 mt-2 border-l-2 border-gray-200 pl-4 dark:border-white/10' : '' }}">
    <div class="flex flex-wrap items-center gap-2">
        <button
            type="button"
            wire:click="openMenu({{ $node['id'] }})"
            @class([
                'inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-bold transition-colors cursor-pointer border',
                'border-teal-500 bg-teal-500/10 text-teal-700 dark:text-teal-300' => $activeMenuId === $node['id'],
                'border-gray-200 bg-gray-50 text-gray-800 hover:bg-gray-100 dark:border-white/10 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' => $activeMenuId !== $node['id'],
            ])
        >
            <x-kiosk-icon icon="grid" class="h-3.5 w-3.5" />
            <span>{{ $node['title'] }}</span>
            <span class="text-[10px] font-normal opacity-75">({{ $gridSize }} blocks)</span>
        </button>
    </div>

    <ul role="list" class="mt-2 grid grid-cols-1 gap-1.5 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($node['blocks'] as $item)
            <li class="rounded-xl border border-gray-200/80 bg-white px-3 py-2 text-xs dark:border-white/10 dark:bg-gray-900/70">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-semibold text-gray-400 dark:text-gray-500">Vak {{ $item['position'] }}</span>
                    @if ($item['type'] === 'module')
                        <span class="rounded-md bg-teal-500/10 px-1.5 py-0.5 text-[10px] font-semibold text-teal-600 dark:text-teal-400">Module</span>
                    @elseif ($item['type'] === 'submenu')
                        <span class="rounded-md bg-sky-500/10 px-1.5 py-0.5 text-[10px] font-semibold text-sky-600 dark:text-sky-400">{{ $gridSize }} Blocks</span>
                    @else
                        <span class="text-[10px] text-gray-400">Leeg</span>
                    @endif
                </div>

                @if ($item['type'] === 'module')
                    <p class="mt-1 truncate font-bold text-gray-900 dark:text-white">{{ $item['label'] }}</p>
                    <p class="truncate text-[11px] text-gray-500 dark:text-gray-400">{{ $item['module_label'] }}</p>
                @elseif ($item['type'] === 'submenu')
                    <p class="mt-1 truncate font-bold text-gray-900 dark:text-white">{{ $item['label'] }}</p>
                    @if ($item['submenu'])
                        <button
                            type="button"
                            wire:click="openMenu({{ $item['submenu']['id'] }})"
                            class="mt-1 text-[11px] font-semibold text-sky-600 hover:underline dark:text-sky-400 cursor-pointer"
                        >
                            Open {{ $gridSize }} blocks →
                        </button>
                    @endif
                @else
                    <p class="mt-1 italic text-gray-400 dark:text-gray-600">—</p>
                @endif
            </li>
        @endforeach
    </ul>

    @foreach ($node['blocks'] as $item)
        @if ($item['type'] === 'submenu' && $item['submenu'])
            @include('filament.pages.partials.menu-tree-node', [
                'node' => $item['submenu'],
                'activeMenuId' => $activeMenuId,
                'depth' => $depth + 1,
                'gridSize' => $gridSize,
            ])
        @endif
    @endforeach
</div>
