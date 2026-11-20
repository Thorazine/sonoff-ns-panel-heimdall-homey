<div
    @navigate-after-delay.window="setTimeout(() => { window.Livewire?.navigate ? window.Livewire.navigate($event.detail.url) : (window.location.href = $event.detail.url) }, $event.detail.delay || 1200)"
    class="w-full h-full bg-black relative overflow-hidden p-4 flex flex-col gap-3"
>
    @if ($options['requireConfirmation'])
        <div class="flex-1 rounded-3xl bg-gray-950 border border-rose-500/40 shadow-[0_0_30px_rgba(244,63,94,0.14)] p-6 flex flex-col items-center justify-between text-center">
            <div class="flex-1 flex flex-col items-center justify-center gap-4">
                <div class="w-20 h-20 rounded-full bg-rose-950/70 border border-rose-500/40 flex items-center justify-center">
                    <x-kiosk-icon icon="power" class="w-11 h-11 text-rose-400" />
                </div>

                <div>
                    <h1 class="text-2xl font-bold tracking-wider uppercase text-rose-400 [text-wrap:balance]">
                        {{ $options['title'] }}
                    </h1>
                    <p class="text-sm text-gray-400 mt-2 max-w-[340px] [text-wrap:pretty]">
                        {{ $options['subtitle'] }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 w-full">
                <button
                    type="button"
                    wire:click="goBack"
                    class="py-4 rounded-2xl bg-gray-900 border border-gray-700 text-gray-300 text-base font-bold active:bg-gray-800 transition-colors select-none cursor-pointer"
                >
                    Annuleren
                </button>
                <button
                    type="button"
                    wire:click="confirmAllOff"
                    wire:loading.attr="disabled"
                    class="py-4 rounded-2xl bg-rose-600/25 border border-rose-500 text-rose-300 text-base font-bold active:bg-rose-600 active:text-white transition-colors select-none cursor-pointer disabled:opacity-50"
                >
                    {{ $options['confirmLabel'] }}
                </button>
            </div>
        </div>
    @else
        <div class="flex-1 flex items-center justify-center min-h-0">
            <button
                type="button"
                wire:click="confirmAllOff"
                wire:loading.attr="disabled"
                class="w-full h-full rounded-3xl bg-rose-950/35 border border-rose-500/50 shadow-[0_0_24px_rgba(244,63,94,0.16)] flex flex-col items-center justify-center gap-4 px-6 active:scale-95 active:bg-rose-900/40 transition-all select-none cursor-pointer disabled:opacity-50"
            >
                <x-kiosk-icon icon="power" class="w-20 h-20 text-rose-400 shrink-0" />
                <span class="text-2xl font-bold tracking-widest uppercase text-rose-400 text-center [text-wrap:balance]">
                    {{ $options['title'] }}
                </span>
            </button>
        </div>

        <button
            type="button"
            wire:click="goBack"
            class="w-full py-3 rounded-2xl bg-gray-900 border border-gray-800 text-gray-400 text-base font-semibold active:bg-gray-800 transition-colors shrink-0 select-none cursor-pointer"
        >
            Annuleren
        </button>
    @endif

    {{-- Feedback Overlay --}}
    @if ($feedbackMsg)
        <div
            role="status"
            wire:click="dismissFeedback"
            class="absolute inset-0 flex items-center justify-center bg-black/85 z-25 cursor-pointer"
        >
            <p
                @class([
                    'text-xl font-bold select-none px-6 py-4 rounded-2xl bg-gray-950 border tracking-wider uppercase text-center',
                    'text-rose-400 border-rose-600/50' => $feedbackType === 'error',
                    'text-flashy border-flashy/50' => $feedbackType !== 'error',
                ])
            >
                {{ $feedbackMsg }}
            </p>
        </div>
    @endif
</div>
