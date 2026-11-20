<div
    x-data="{
        pin: '',
        alarmLeft: {{ $alarmCountdownSeconds }},
        lockoutLeft: @entangle('lockoutSecondsLeft'),
        alarmTimer: null,
        lockoutTimer: null,
        init() {
            if ({{ $alarm ? 'true' : 'false' }}) {
                this.startAlarmCountdown();
            }
            this.$watch('lockoutLeft', (val) => {
                if (val > 0 && !this.lockoutTimer) {
                    this.startLockoutCountdown();
                }
            });
            if (this.lockoutLeft > 0) {
                this.startLockoutCountdown();
            }
        },
        startAlarmCountdown() {
            this.alarmTimer = setInterval(() => {
                if ({{ $disarmed ? 'true' : 'false' }}) {
                    clearInterval(this.alarmTimer);
                    return;
                }
                if (this.alarmLeft > 0) {
                    window.kioskAudio?.urgentBeep();
                    this.alarmLeft--;
                    if (this.alarmLeft === 0) {
                        $wire.triggerAlarmFlash();
                        clearInterval(this.alarmTimer);
                    }
                }
            }, 1000);
        },
        startLockoutCountdown() {
            if (this.lockoutTimer) clearInterval(this.lockoutTimer);
            this.lockoutTimer = setInterval(() => {
                if (this.lockoutLeft > 1) {
                    this.lockoutLeft--;
                } else {
                    clearInterval(this.lockoutTimer);
                    this.lockoutTimer = null;
                    this.pin = '';
                    $wire.refreshLockout();
                }
            }, 1000);
        },
        append(num) {
            if (this.pin.length < 6) {
                this.pin += String(num);
            }
        },
        clear() {
            this.pin = '';
        },
        submit() {
            if (!this.pin) return;
            const current = this.pin;
            this.pin = '';
            $wire.submitPin(current);
        },
        handleKeydown(e) {
            if ({{ $isLockedOut || $disarmed ? 'true' : 'false' }}) return;
            if (/^[0-9]$/.test(e.key)) {
                e.preventDefault();
                this.append(e.key);
            } else if (e.key === 'Backspace') {
                e.preventDefault();
                this.pin = this.pin.slice(0, -1);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                this.submit();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                this.clear();
            }
        },
        destroy() {
            if (this.alarmTimer) clearInterval(this.alarmTimer);
            if (this.lockoutTimer) clearInterval(this.lockoutTimer);
        }
    }"
    @keydown.window="handleKeydown($event)"
    @pin-error-flash.window="setTimeout(() => { $wire.clearErrorScreen() }, 1000)"
    @navigate-after-delay.window="setTimeout(() => { window.Livewire?.navigate ? window.Livewire.navigate($event.detail.url) : (window.location.href = $event.detail.url) }, $event.detail.delay || 2000)"
    class="w-full h-full flex flex-col items-center justify-start pt-6 pb-2 relative bg-black"
>
    {{-- Flashing Border Overlay on Active Alarm --}}
    @if ($isFlashing)
        <div class="absolute inset-0 border-8 border-red-600 animate-pulse pointer-events-none z-50"></div>
    @endif

    @if ($isLockedOut)
        {{-- Lockout Screen --}}
        <div role="alert" class="flex flex-col items-center justify-center animate-fade-in z-20 h-full text-center px-4">
            <svg aria-hidden="true" class="w-32 h-32 text-red-500 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
            <h1 class="text-3xl text-red-500 font-bold tracking-widest mb-2">GEBLOKKEERD</h1>
            <p class="text-2xl text-gray-300 font-bold">
                Probeer opnieuw in <span x-text="lockoutLeft">{{ $lockoutSecondsLeft }}</span>s
            </p>
        </div>
    @elseif ($disarmed)
        {{-- Disarmed Success Screen --}}
        <div role="status" class="flex flex-col items-center justify-center animate-fade-in z-20 h-full">
            <svg aria-hidden="true" class="w-32 h-32 text-flashy mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h1 class="text-3xl text-flashy font-bold tracking-widest text-shadow-flashy">GEDEACTIVEERD</h1>
        </div>
    @else
        {{-- Main Entry Panel --}}
        <div class="w-full flex flex-col items-center justify-start relative z-10 h-full pt-4">
            @if ($alarm)
                <div class="text-center mb-3">
                    <h1 class="text-xl text-red-500 font-bold tracking-widest mb-1">
                        {{ $isFlashing ? 'ALARM GEACTIVEERD' : 'ALARM GAAT AF IN' }}
                    </h1>
                    @if (! $isFlashing)
                        <div class="text-4xl font-extrabold text-red-500 tabular-nums">
                            <span x-text="alarmLeft">{{ $alarmCountdownSeconds }}</span>s
                        </div>
                    @endif
                </div>
            @else
                <h1 class="sr-only">Voer PIN-code in</h1>
            @endif

            @if ($errorMessage && ! $showErrorScreen)
                <p role="alert" class="text-red-500 mb-2 font-bold text-sm">{{ $errorMessage }}</p>
            @endif

            {{-- PIN Dots Display --}}
            <div class="w-3/4 h-16 bg-gray-900 border-2 border-gray-700 rounded-xl flex items-center justify-center mb-5 shadow-inner relative z-10">
                <div class="flex gap-2" aria-hidden="true">
                    @for ($i = 1; $i <= 6; $i++)
                        <div
                            class="w-4 h-4 rounded-full transition-colors duration-200"
                            :class="pin.length >= {{ $i }} ? 'bg-flashy shadow-[0_0_10px_rgba(0,255,204,0.8)]' : 'bg-gray-800'"
                        ></div>
                    @endfor
                </div>
            </div>

            {{-- Numeric Keypad --}}
            <div class="grid grid-cols-3 gap-3.5 mb-4 w-3/4 relative z-10">
                @foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9] as $num)
                    <button
                        type="button"
                        @click="append({{ $num }})"
                        class="h-14 rounded-full bg-gray-800 text-gray-300 text-2xl font-semibold border border-gray-700 active:bg-flashy active:text-black transition-colors cursor-pointer"
                    >
                        {{ $num }}
                    </button>
                @endforeach

                <button
                    type="button"
                    @click="clear()"
                    class="h-14 rounded-full bg-gray-800 text-gray-400 text-lg font-medium border border-gray-700 active:bg-gray-600 transition-colors cursor-pointer"
                >
                    WISSEN
                </button>

                <button
                    type="button"
                    @click="append(0)"
                    class="h-14 rounded-full bg-gray-800 text-gray-300 text-2xl font-semibold border border-gray-700 active:bg-flashy active:text-black transition-colors cursor-pointer"
                >
                    0
                </button>

                <button
                    type="button"
                    @click="submit()"
                    :disabled="pin.length === 0"
                    wire:loading.attr="disabled"
                    class="h-14 rounded-full bg-flashy/20 text-flashy text-lg font-bold border border-flashy/50 active:bg-flashy active:text-black transition-colors disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                >
                    OK
                </button>
            </div>

            {{-- Bottom Cancel --}}
            <div class="flex gap-4 w-3/4 mt-auto relative z-10 pb-2">
                <button
                    type="button"
                    wire:click="cancelToStart"
                    class="flex-1 py-3.5 rounded-xl text-gray-400 bg-gray-900 border border-gray-800 active:bg-gray-800 cursor-pointer"
                >
                    Annuleren
                </button>
            </div>
        </div>
    @endif

    {{-- Wrong PIN Flash Screen --}}
    @if ($showErrorScreen)
        <div role="alert" class="absolute inset-0 flex flex-col items-center justify-center bg-red-950/90 z-30 animate-fade-in">
            <svg aria-hidden="true" class="w-32 h-32 text-red-500 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            <h2 class="text-3xl text-red-500 font-bold tracking-widest">FOUTIEVE CODE</h2>
        </div>
    @endif
</div>
