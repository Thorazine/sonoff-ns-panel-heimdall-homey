<div
    @navigate-after-delay.window="setTimeout(() => { window.Livewire?.navigate ? window.Livewire.navigate($event.detail.url) : (window.location.href = $event.detail.url) }, $event.detail.delay || 1200)"
    class="w-full h-full bg-black relative overflow-hidden p-4 flex flex-col gap-3"
>
    {{-- Module Header Bar --}}
    <header class="flex items-center justify-between gap-3 px-1 shrink-0">
        <div class="flex items-center gap-2.5 min-w-0">
            <x-kiosk-icon :icon="$module->defaultIcon()" class="w-5 h-5 shrink-0 text-gray-300" />
            <h1 class="text-sm font-bold tracking-widest uppercase text-gray-200 truncate [text-wrap:balance]">
                {{ $options['screenTitle'] }}
            </h1>
        </div>

        @if ($options['badge'] !== '')
            <span class="rounded-full border border-white/15 bg-gray-900 px-3 py-0.5 text-xs font-bold tracking-wider text-flashy shrink-0 tabular-nums">
                {{ $options['badge'] }}
            </span>
        @endif
    </header>

    @if ($confirmingSlot !== null && isset($options['slots'][$confirmingSlot]))
        @php
            $pendingSlot = $options['slots'][$confirmingSlot];
        @endphp

        {{-- In-Screen Safety Confirmation View --}}
        <div class="flex-1 rounded-3xl bg-gray-950 border border-amber-500/40 shadow-[0_0_28px_rgba(245,158,11,0.14)] p-6 flex flex-col items-center justify-between text-center">
            <div class="flex-1 flex flex-col items-center justify-center gap-4">
                <div class="w-18 h-18 rounded-full bg-amber-950/70 border border-amber-500/40 flex items-center justify-center text-amber-400">
                    <x-kiosk-icon :icon="$pendingSlot['icon']" class="w-9 h-9" />
                </div>

                <div>
                    <h2 class="text-2xl font-bold tracking-wider uppercase text-amber-400 [text-wrap:balance]">
                        {{ $pendingSlot['label'] }}?
                    </h2>
                    <p class="text-sm text-gray-400 mt-1.5 max-w-[320px] [text-wrap:pretty]">
                        Bevestig dat je <strong>{{ strtolower($options['screenTitle']) }}</strong> wilt activeren.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 w-full">
                <button
                    type="button"
                    wire:click="cancelConfirmation"
                    class="py-4 rounded-2xl bg-gray-900 border border-gray-700 text-gray-300 text-base font-bold active:bg-gray-800 transition-colors select-none cursor-pointer"
                >
                    Annuleren
                </button>
                <button
                    type="button"
                    wire:click="confirmPendingSlot"
                    wire:loading.attr="disabled"
                    class="py-4 rounded-2xl bg-amber-500/20 border border-amber-500 text-amber-300 text-base font-bold active:bg-amber-500 active:text-black transition-colors select-none cursor-pointer disabled:opacity-50"
                >
                    Ja, start
                </button>
            </div>
        </div>
    @else
        {{-- 2x2 Action Grid for this Module --}}
        <div class="flex-1 grid grid-cols-2 grid-rows-2 gap-3 min-h-0">
            @foreach ($options['slots'] as $slotNumber => $slot)
                <div class="w-full h-full flex items-center justify-center">
                    <button
                        type="button"
                        wire:key="module-slot-{{ $module->value }}-{{ $slotNumber }}"
                        wire:click="triggerSlot({{ $slotNumber }})"
                        wire:loading.attr="disabled"
                        class="w-full h-full rounded-3xl bg-gray-900/80 border {{ $slot['color']->kioskCardClasses() }} flex flex-col items-center justify-center gap-2.5 px-3 active:scale-95 active:bg-gray-800 transition-all select-none cursor-pointer disabled:opacity-50"
                    >
                        <x-kiosk-icon :icon="$slot['icon']" class="w-14 h-14 shrink-0" />
                        <span class="text-lg font-bold tracking-widest uppercase leading-tight text-center [text-wrap:balance]">
                            {{ $slot['label'] }}
                        </span>
                    </button>
                </div>
            @endforeach
        </div>

        {{-- Bottom Back / Cancel Button --}}
        <button
            type="button"
            wire:click="goBack"
            class="w-full py-3 rounded-2xl bg-gray-900 border border-gray-800 text-gray-400 text-base font-semibold active:bg-gray-800 transition-colors shrink-0 select-none cursor-pointer"
        >
            {{ $from !== null ? 'Terug' : 'Annuleren' }}
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
                    'text-xl font-bold select-none px-6 py-4 rounded-2xl bg-gray-950 border tracking-wider uppercase text-center [text-wrap:balance]',
                    'text-rose-400 border-rose-600/50' => $feedbackType === 'error',
                    'text-flashy border-flashy/50' => $feedbackType !== 'error',
                ])
            >
                {{ $feedbackMsg }}
            </p>
        </div>
    @endif
</div>
