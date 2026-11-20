@props([
    'camera',
    'isHero' => false,
    'context' => 'cameras',
])

@if ($isHero)
    <div
        wire:key="reolink-{{ $context }}-hero-{{ $camera['id'] }}"
        wire:ignore
        x-data="reolinkStreamTile(@js($camera))"
        :data-two-way-active="twoWayActive ? 'true' : 'false'"
        class="relative w-full h-full min-h-0 rounded-3xl border border-sky-400/60 bg-gray-900 overflow-clip flex flex-col justify-between shadow-[0_0_24px_rgba(14,165,233,0.16)]"
    >
        {{-- Video / Stream / Snapshot Layer --}}
        <div class="absolute inset-0 w-full h-full aspect-video bg-gray-950 flex items-center justify-center overflow-clip">
            <video
                x-ref="videoEl"
                x-show="mode === 'webrtc' || mode === 'connecting'"
                autoplay
                playsinline
                muted
                class="block w-full h-full aspect-video object-cover bg-black"
            ></video>

            <template x-if="mode === 'video' && streamUrl">
                <video
                    :src="streamUrl"
                    autoplay
                    playsinline
                    :muted="audioMuted"
                    class="block w-full h-full aspect-video object-cover bg-black"
                ></video>
            </template>

            <template x-if="mode === 'mjpeg' && streamUrl">
                <img
                    :src="streamUrl"
                    :alt="cameraName"
                    class="block w-full h-full aspect-video object-cover bg-black"
                />
            </template>

            <template x-if="mode === 'snapshot' && snapshotSrc">
                <img
                    :src="snapshotSrc"
                    :alt="cameraName"
                    class="block w-full h-full aspect-video object-cover bg-black"
                />
            </template>

            <div
                x-show="mode === 'unconfigured' || mode === 'connecting'"
                class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-gray-900/95 text-center p-6"
            >
                <x-kiosk-icon icon="video-camera" class="h-16 w-16 text-sky-300" />
                <p class="text-xl font-bold tracking-widest uppercase text-white [text-wrap:balance]">
                    {{ $camera['name'] }}
                </p>
                <p class="text-sm font-semibold tracking-wider uppercase text-gray-300" x-text="statusText"></p>
            </div>
        </div>

        {{-- Top Camera Info Bar --}}
        <div class="relative z-10 flex items-center justify-between gap-3 bg-gradient-to-b from-black/90 via-black/60 to-transparent px-5 py-3.5">
            <div class="flex items-center gap-3 min-w-0">
                @if ($camera['role'] === 'doorbell')
                    <span class="shrink-0 rounded-xl bg-amber-950/90 border border-amber-400/70 px-3 py-1 text-xs font-bold uppercase tracking-wider text-amber-300">
                        Deurbel
                    </span>
                @else
                    <span class="shrink-0 rounded-xl bg-sky-950/90 border border-sky-400/70 px-3 py-1 text-xs font-bold uppercase tracking-wider text-sky-300">
                        Tuin
                    </span>
                @endif
                <span class="truncate text-lg font-bold tracking-wide text-white">
                    {{ $camera['name'] }}
                </span>
            </div>

            <span
                :class="twoWayActive
                    ? 'bg-emerald-950/90 border-emerald-400 text-emerald-300 animate-pulse'
                    : 'bg-gray-900/90 border-gray-600 text-gray-100'"
                class="shrink-0 inline-flex items-center gap-2 rounded-full border px-3.5 py-1 text-xs font-bold uppercase tracking-wider"
            >
                <span
                    :class="twoWayActive ? 'bg-emerald-400' : (mode === 'unconfigured' ? 'bg-amber-400' : 'bg-flashy')"
                    class="h-2.5 w-2.5 rounded-full"
                ></span>
                <span x-text="statusText"></span>
            </span>
        </div>

        {{-- Bottom Intercom & Audio Controls --}}
        <div class="relative z-10 bg-gradient-to-t from-black/95 via-black/75 to-transparent px-5 py-4 space-y-2.5">
            <p
                x-show="micError"
                x-text="micError"
                class="rounded-xl border border-amber-400/70 bg-amber-950/95 px-4 py-2 text-sm font-semibold text-amber-200 [text-wrap:pretty]"
            ></p>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2.5">
                    @if ($camera['supportsTwoWayAudio'])
                        <button
                            type="button"
                            @click="toggleTwoWayAudio()"
                            :class="twoWayActive
                                ? 'bg-emerald-500 text-black border-emerald-300 shadow-[0_0_22px_rgba(16,185,129,0.5)]'
                                : 'bg-flashy/20 text-flashy border-flashy/60 shadow-[0_0_16px_rgba(0,255,204,0.15)] active:bg-flashy active:text-black'"
                            class="inline-flex items-center gap-2.5 rounded-2xl border px-5 py-3 text-sm font-bold uppercase tracking-wider transition-all cursor-pointer active:scale-95"
                        >
                            <x-kiosk-icon icon="microphone" class="h-5 w-5 shrink-0" />
                            <span x-text="twoWayActive ? 'Stop 2-Weg Audio (Spreek nu)' : 'Start 2-Weg Audio'">
                                Start 2-Weg Audio
                            </span>
                        </button>
                    @endif

                    <button
                        type="button"
                        @click="toggleMute()"
                        :class="audioMuted
                            ? 'bg-gray-900 text-gray-200 border-gray-600'
                            : 'bg-sky-500/25 text-sky-200 border-sky-400/70'"
                        class="inline-flex items-center gap-2 rounded-2xl border px-4 py-3 text-sm font-bold uppercase tracking-wider transition-colors cursor-pointer active:bg-gray-800"
                    >
                        <span x-text="audioMuted ? 'Geluid Uit' : 'Geluid Aan'">Geluid Uit</span>
                    </button>
                </div>

                <button
                    type="button"
                    @click="connect(false)"
                    class="rounded-2xl border border-gray-600 bg-gray-900 px-4 py-3 text-sm font-semibold uppercase tracking-wider text-gray-200 active:bg-gray-800 cursor-pointer"
                >
                    Herverbind
                </button>
            </div>
        </div>
    </div>
@else
    <button
        type="button"
        wire:key="reolink-{{ $context }}-sub-btn-{{ $camera['id'] }}"
        wire:click="focusCamera({{ $camera['id'] }})"
        class="group relative w-full h-full min-h-0 rounded-3xl border border-gray-700 bg-gray-900 overflow-clip text-left transition-all hover:border-sky-400/80 active:scale-95 cursor-pointer"
    >
        <div
            wire:ignore
            x-data="reolinkStreamTile(@js($camera))"
            class="relative w-full h-full aspect-video overflow-clip bg-gray-900"
        >
            <video
                x-ref="videoEl"
                x-show="mode === 'webrtc' || mode === 'connecting'"
                autoplay
                playsinline
                muted
                class="block w-full h-full aspect-video object-cover bg-black"
            ></video>

            <template x-if="mode === 'video' && streamUrl">
                <video
                    :src="streamUrl"
                    autoplay
                    playsinline
                    muted
                    class="block w-full h-full aspect-video object-cover bg-black"
                ></video>
            </template>

            <template x-if="mode === 'mjpeg' && streamUrl">
                <img
                    :src="streamUrl"
                    :alt="cameraName"
                    class="block w-full h-full aspect-video object-cover bg-black"
                />
            </template>

            <template x-if="mode === 'snapshot' && snapshotSrc">
                <img
                    :src="snapshotSrc"
                    :alt="cameraName"
                    class="block w-full h-full aspect-video object-cover bg-black"
                />
            </template>

            <div
                x-show="mode === 'unconfigured' || mode === 'connecting'"
                class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-gray-900/95 p-3 text-center"
            >
                <x-kiosk-icon icon="video-camera" class="h-9 w-9 text-sky-300" />
                <span class="text-xs font-bold uppercase tracking-wider text-gray-300" x-text="statusText"></span>
            </div>

            <div class="absolute inset-x-0 bottom-0 flex items-center justify-between gap-2 bg-gradient-to-t from-black/95 via-black/75 to-transparent px-3.5 py-2.5">
                <span class="truncate text-sm font-bold text-white">
                    {{ $camera['name'] }}
                </span>
                <span class="shrink-0 rounded-lg bg-gray-950/90 border border-flashy/50 px-2 py-0.5 text-xs font-bold uppercase tracking-wider text-flashy">
                    Vergroot
                </span>
            </div>
        </div>
    </button>
@endif
