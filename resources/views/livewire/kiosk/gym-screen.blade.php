<div
    @navigate-after-delay.window="setTimeout(() => { window.Livewire?.navigate ? window.Livewire.navigate($event.detail.url) : (window.location.href = $event.detail.url) }, $event.detail.delay || 1200)"
    class="w-full h-full bg-black relative overflow-hidden p-4 flex flex-col gap-3"
>
    {{-- 2x2 Grid with 4 Tiles --}}
    <div class="flex-1 grid grid-cols-2 grid-rows-2 gap-3 min-h-0">
        {{-- Tile 1 (Top-Left): Gym AAN --}}
        <div class="w-full h-full flex items-center justify-center">
            <button
                type="button"
                wire:click="triggerGym('on')"
                wire:loading.attr="disabled"
                class="w-full h-full rounded-3xl bg-emerald-950/40 border border-emerald-500/50 shadow-[0_0_20px_rgba(16,185,129,0.15)] flex flex-col items-center justify-center gap-2.5 active:scale-95 active:bg-emerald-900/50 transition-all select-none cursor-pointer disabled:opacity-50"
            >
                <x-kiosk-icon icon="power" class="w-16 h-16 text-emerald-400 shrink-0" />
                <span class="text-xl font-bold tracking-widest uppercase text-emerald-400 leading-none">
                    {{ $options['onLabel'] }}
                </span>
            </button>
        </div>

        {{-- Tile 2 (Top-Right): Gym UIT --}}
        <div class="w-full h-full flex items-center justify-center">
            <button
                type="button"
                wire:click="triggerGym('off')"
                wire:loading.attr="disabled"
                class="w-full h-full rounded-3xl bg-red-950/40 border border-red-500/50 shadow-[0_0_20px_rgba(239,68,68,0.15)] flex flex-col items-center justify-center gap-2.5 active:scale-95 active:bg-red-900/50 transition-all select-none cursor-pointer disabled:opacity-50"
            >
                <x-kiosk-icon icon="power" class="w-16 h-16 text-red-400 shrink-0" />
                <span class="text-xl font-bold tracking-widest uppercase text-red-400 leading-none">
                    {{ $options['offLabel'] }}
                </span>
            </button>
        </div>

        <div class="w-full h-full flex items-center justify-center">
            <div class="w-full h-full rounded-3xl bg-gray-950/40 border border-gray-900" aria-hidden="true"></div>
        </div>

        <div class="w-full h-full flex items-center justify-center">
            <div class="w-full h-full rounded-3xl bg-gray-950/40 border border-gray-900" aria-hidden="true"></div>
        </div>
    </div>

    {{-- Bottom Cancel Button --}}
    <button
        type="button"
        wire:click="goBack"
        class="w-full py-3 rounded-2xl bg-gray-900 border border-gray-800 text-gray-400 text-base font-semibold active:bg-gray-800 transition-colors shrink-0 select-none cursor-pointer"
    >
        {{ $from !== null ? 'Terug' : 'Annuleren' }}
    </button>

    {{-- Feedback Overlay --}}
    @if ($feedbackMsg)
        <div
            role="status"
            wire:click="dismissFeedback"
            class="absolute inset-0 flex items-center justify-center bg-black/85 z-20 cursor-pointer"
        >
            <p
                @class([
                    'text-xl font-bold select-none px-6 py-4 rounded-2xl bg-gray-950 border tracking-wider uppercase',
                    'text-red-400 border-red-600/50' => $feedbackType === 'error',
                    'text-flashy border-flashy/50' => $feedbackType !== 'error',
                ])
            >
                {{ $feedbackMsg }}
            </p>
        </div>
    @endif
</div>
