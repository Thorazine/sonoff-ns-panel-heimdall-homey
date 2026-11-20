<div
    x-data="{
        seconds: {{ $timeLeft }},
        timer: null,
        init() {
            if ({{ $armed ? 'true' : 'false' }}) return;
            if (this.seconds <= 0) {
                $wire.completeArming();
                return;
            }
            this.timer = setInterval(() => {
                if (this.seconds > 1) {
                    window.kioskAudio?.beep();
                    this.seconds--;
                } else {
                    clearInterval(this.timer);
                    this.seconds = 0;
                    $wire.completeArming();
                }
            }, 1000);
        },
        stopAndCancel() {
            if (this.timer) clearInterval(this.timer);
            $wire.cancel();
        },
        destroy() {
            if (this.timer) clearInterval(this.timer);
        }
    }"
    @navigate-after-delay.window="setTimeout(() => { window.Livewire?.navigate ? window.Livewire.navigate($event.detail.url) : (window.location.href = $event.detail.url) }, $event.detail.delay || 2000)"
    class="w-full h-full bg-black flex flex-col items-center justify-center p-6 relative"
>
    @if (! $armed)
        <div class="flex flex-col items-center justify-center">
            <h1 class="text-2xl text-gray-400 font-light mb-8">ALARM SCHAKELT IN...</h1>

            <div class="relative w-48 h-48 flex items-center justify-center bg-gray-900 rounded-full border-4 border-gray-800 shadow-[0_0_30px_rgba(0,255,204,0.15)]">
                <span x-text="seconds" class="text-7xl font-bold text-flashy tabular-nums">{{ $timeLeft }}</span>
            </div>

            <button
                type="button"
                @click="stopAndCancel()"
                class="absolute bottom-10 px-8 py-3 rounded-xl text-red-500 border border-red-900 bg-red-950/30 active:bg-red-900/50 transition-colors cursor-pointer"
            >
                ANNULEREN
            </button>
        </div>
    @else
        <div role="status" class="flex flex-col items-center justify-center animate-fade-in">
            <svg aria-hidden="true" class="w-32 h-32 text-flashy mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
            <h1 class="text-4xl text-flashy font-bold tracking-widest text-shadow-flashy">INGESCHAKELD</h1>
        </div>
    @endif
</div>
