<div class="w-full h-full bg-black relative overflow-hidden p-4 flex flex-col gap-3">
    @if (! $currentMenu->is_root)
        <header class="flex items-center justify-center shrink-0 pt-0.5">
            <h1 class="text-sm font-bold tracking-widest uppercase text-gray-400 select-none [text-wrap:balance]">
                {{ $currentMenu->title }}
            </h1>
        </header>
    @endif

    {{-- Fixed 2x2 Grid of 4 Equal Blocks --}}
    <div class="flex-1 grid grid-cols-2 grid-rows-2 gap-3 min-h-0">
        @foreach ($currentMenu->blocks as $block)
            <div class="w-full h-full flex items-center justify-center">
                @if ($block->type === \App\Enums\MenuBlockType::Empty)
                    <div class="w-full h-full rounded-3xl bg-gray-950/40 border border-gray-900" aria-hidden="true"></div>
                @else
                    <button
                        type="button"
                        wire:key="kiosk-block-{{ $block->id }}"
                        wire:click="activateBlock({{ $block->id }})"
                        wire:loading.attr="disabled"
                        class="w-full h-full rounded-3xl bg-gray-900/80 border {{ $block->resolvedColor()->kioskCardClasses() }} relative flex flex-col items-center justify-center gap-2.5 px-3 active:scale-95 active:bg-gray-800 transition-all select-none cursor-pointer disabled:opacity-50"
                    >
                        @if ($block->type === \App\Enums\MenuBlockType::Submenu)
                            <span
                                aria-hidden="true"
                                class="absolute top-3 right-3 rounded-lg bg-gray-950/80 border border-white/10 p-1.5 text-gray-400"
                            >
                                <svg aria-hidden="true" viewBox="0 0 24 24" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="7" height="7" rx="1" />
                                    <rect x="14" y="3" width="7" height="7" rx="1" />
                                    <rect x="3" y="14" width="7" height="7" rx="1" />
                                    <rect x="14" y="14" width="7" height="7" rx="1" />
                                </svg>
                            </span>
                        @endif

                        <x-kiosk-icon :icon="$block->resolvedIcon()" class="w-16 h-16 shrink-0" />

                        <span class="text-xl font-bold tracking-widest uppercase leading-tight text-center [text-wrap:balance]">
                            {{ $block->resolvedLabel() }}
                        </span>
                    </button>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Bottom Back / Cancel Button --}}
    <button
        type="button"
        wire:click="goBack"
        class="w-full py-3 rounded-2xl bg-gray-900 border border-gray-800 text-gray-400 text-base font-semibold active:bg-gray-800 transition-colors shrink-0 select-none cursor-pointer"
    >
        {{ $currentMenu->is_root ? 'Annuleren' : 'Terug' }}
    </button>

    {{-- Error Overlay --}}
    @if ($error)
        <div
            role="alert"
            wire:click="dismissFeedback"
            class="absolute inset-0 flex items-center justify-center bg-black/85 z-20 cursor-pointer"
        >
            <p class="text-red-500 text-xl font-bold select-none px-6 py-4 rounded-2xl bg-gray-950 border border-red-600/50">
                {{ $error }}
            </p>
        </div>
    @endif
</div>
