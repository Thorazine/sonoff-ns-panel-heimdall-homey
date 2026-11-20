<x-filament-panels::page>
    <div wire:init="loadFlowOptions" class="space-y-6">
        {{-- Homey Webhook + go2rtc Streaming Overview --}}
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            {{-- Homey Flow Webhook Card --}}
            <div class="xl:col-span-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-xs dark:border-white/10 dark:bg-gray-900 space-y-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-gray-950 dark:text-white">
                            Homey Deurbel Trigger (5 Camera's bij Aanbellen)
                        </h2>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Maak in Homey een Flow: <strong>ALS Reolink Deurbel wordt ingedrukt → DAN Logica HTTP POST verzoek</strong> naar onderstaande URL. Alle grote schermen springen dan direct over op de deurbelcamera + 4 tuincamera's.
                        </p>
                    </div>
                    <x-filament::badge color="warning">HTTP POST</x-filament::badge>
                </div>

                <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-white/10 dark:bg-gray-950 space-y-2">
                    <label for="doorbell-webhook-url" class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Webhook URL voor Homey Flow
                    </label>
                    <input
                        id="doorbell-webhook-url"
                        type="text"
                        readonly
                        value="{{ $this->webhookUrl }}"
                        onclick="this.select()"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-mono text-xs text-gray-900 dark:border-white/15 dark:bg-gray-900 dark:text-teal-300"
                    />
                    <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-gray-500 dark:text-gray-400">
                        <span>Optioneel per scherm filteren: voeg <code>&amp;panel=eerste-verdieping</code> toe.</span>
                        <button
                            type="button"
                            wire:click="regenerateWebhookToken"
                            wire:confirm="Nieuw webhook-token genereren? De oude URL in Homey werkt daarna niet meer."
                            class="font-semibold text-rose-600 hover:underline dark:text-rose-400 cursor-pointer"
                        >
                            Nieuw token genereren
                        </button>
                    </div>
                </div>
            </div>

            {{-- go2rtc & Two-Way Audio Setup Hint --}}
            <div class="xl:col-span-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-xs dark:border-white/10 dark:bg-gray-900 space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-gray-950 dark:text-white">
                            Reolink Live Video &amp; Twee-Weg Audio (go2rtc)
                        </h2>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Voor vloeiende weergave van 5 gelijktijdige Reolink streams én twee-weg terugspreken via het Android wandpaneel wordt <strong>go2rtc</strong> WebRTC ondersteund, met automatische fallback naar directe stream- of snapshot-URL's.
                        </p>
                    </div>
                    <x-filament::badge color="info">WebRTC + 2-Weg Audio</x-filament::badge>
                </div>

                <div class="rounded-xl border border-gray-200 bg-gray-950 p-3.5 text-xs text-gray-300 font-mono space-y-1">
                    <div class="text-gray-400"># Voorbeeld go2rtc.yaml voor Reolink Deurbel (2-weg audio):</div>
                    <div>streams:</div>
                    <div class="pl-4 text-teal-300">deurbel: rtsp://admin:wachtwoord@192.168.1.60:554/h264Preview_01_sub#backchannel=1</div>
                    <div class="pl-4 text-sky-300">tuin_1: rtsp://admin:wachtwoord@192.168.1.61:554/h264Preview_01_sub</div>
                </div>
            </div>
        </div>

        {{-- Main Settings + All Cameras Form --}}
        <form wire:submit="saveAll" class="space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs dark:border-white/10 dark:bg-gray-900 space-y-4">
                <h2 class="text-base font-bold text-gray-950 dark:text-white">
                    Algemene Stream &amp; Deurbel Instellingen
                </h2>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div class="space-y-1.5">
                        <label for="go2rtc-base-url" class="block text-xs font-semibold text-gray-800 dark:text-gray-200">
                            go2rtc Basis-URL (aanbevolen)
                        </label>
                        <input
                            id="go2rtc-base-url"
                            type="text"
                            wire:model="go2rtcBaseUrl"
                            placeholder="Bijv. http://192.168.1.50:1984"
                            class="w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 dark:border-white/15 dark:bg-gray-950 dark:text-white"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <label for="doorbell-timeout" class="block text-xs font-semibold text-gray-800 dark:text-gray-200">
                            Deurbel pop-up duur (seconden)
                        </label>
                        <input
                            id="doorbell-timeout"
                            type="number"
                            min="10"
                            max="300"
                            wire:model="doorbellTimeoutSeconds"
                            class="w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 dark:border-white/15 dark:bg-gray-950 dark:text-white"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <label for="doorbell-flow" class="block text-xs font-semibold text-gray-800 dark:text-gray-200">
                            Homey Flow knop bij aanbellen (optioneel)
                        </label>
                        <select
                            id="doorbell-flow"
                            wire:model="doorbellActionFlowId"
                            class="w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 dark:border-white/15 dark:bg-gray-950 dark:text-white"
                        >
                            <option value="">-- Geen extra actieknop --</option>
                            @foreach ($flowOptions as $flowId => $flowName)
                                <option value="{{ $flowId }}">{{ $flowName }}</option>
                            @endforeach
                            @if ($doorbellActionFlowId !== '' && ! isset($flowOptions[$doorbellActionFlowId]))
                                <option value="{{ $doorbellActionFlowId }}">Gekoppelde Flow ({{ $doorbellActionFlowId }})</option>
                            @endif
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label for="doorbell-action-label" class="block text-xs font-semibold text-gray-800 dark:text-gray-200">
                            Tekst op deur/poort-knop
                        </label>
                        <input
                            id="doorbell-action-label"
                            type="text"
                            maxlength="30"
                            wire:model="doorbellActionLabel"
                            placeholder="VOORDEUR / POORT"
                            class="w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 dark:border-white/15 dark:bg-gray-950 dark:text-white"
                        />
                    </div>
                </div>
            </div>

            {{-- Cameras List Header --}}
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-gray-950 dark:text-white">
                        Geconfigureerde Reolink Camera's ({{ count($cameras) }})
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Standaard staan de <strong>Deurbelcamera</strong> (met 2-weg audio) + <strong>4 Tuincamera's</strong> klaar voor de 5-camera weergave.
                    </p>
                </div>

                <x-filament::button
                    type="button"
                    size="sm"
                    color="gray"
                    wire:click="toggleAddCameraForm"
                >
                    {{ $showAddCameraForm ? 'Sluiten' : '+ Extra camera toevoegen' }}
                </x-filament::button>
            </div>

            @if ($showAddCameraForm)
                <div class="rounded-2xl border border-sky-500/30 bg-sky-500/5 p-5 grid grid-cols-1 gap-4 sm:grid-cols-12 items-end">
                    <div class="sm:col-span-5 space-y-1">
                        <label for="new-cam-name" class="block text-xs font-semibold text-gray-900 dark:text-white">Camernaam</label>
                        <input
                            id="new-cam-name"
                            type="text"
                            wire:model="newCameraName"
                            placeholder="Bijv. Oprit Extra"
                            class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-white/15 dark:bg-gray-950 dark:text-white"
                        />
                    </div>

                    <div class="sm:col-span-3 space-y-1">
                        <label for="new-cam-role" class="block text-xs font-semibold text-gray-900 dark:text-white">Rol</label>
                        <select
                            id="new-cam-role"
                            wire:model="newCameraRole"
                            class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-white/15 dark:bg-gray-950 dark:text-white"
                        >
                            @foreach (\App\Enums\CameraRole::cases() as $roleCase)
                                <option value="{{ $roleCase->value }}">{{ $roleCase->getLabel() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2 space-y-1">
                        <label for="new-cam-stream" class="block text-xs font-semibold text-gray-900 dark:text-white">go2rtc stream</label>
                        <input
                            id="new-cam-stream"
                            type="text"
                            wire:model="newCameraGo2rtcStream"
                            placeholder="oprit"
                            class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-white/15 dark:bg-gray-950 dark:text-white"
                        />
                    </div>

                    <div class="sm:col-span-2">
                        <x-filament::button type="button" wire:click="createCamera" class="w-full">
                            Toevoegen
                        </x-filament::button>
                    </div>
                </div>
            @endif

            {{-- Individual Camera Cards --}}
            <div class="grid grid-cols-1 gap-4">
                @foreach ($cameras as $camId => $cam)
                    <div
                        wire:key="cam-card-{{ $camId }}"
                        class="rounded-2xl border border-gray-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-gray-900 space-y-4"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-3 dark:border-white/10">
                            <div class="flex items-center gap-2.5">
                                <x-kiosk-icon :icon="$cam['role'] === 'doorbell' ? 'bell' : 'video-camera'" class="h-5 w-5 text-sky-500" />
                                <span class="text-sm font-bold text-gray-950 dark:text-white">{{ $cam['name'] }}</span>
                                <x-filament::badge :color="$cam['role'] === 'doorbell' ? 'warning' : 'info'">
                                    {{ \App\Enums\CameraRole::tryFrom($cam['role'])?->getLabel() ?? 'Camera' }}
                                </x-filament::badge>
                                @if (! empty($cam['supports_two_way_audio']))
                                    <x-filament::badge color="success">2-Weg Audio</x-filament::badge>
                                @endif
                            </div>

                            <div class="flex items-center gap-3">
                                <label class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        wire:model="cameras.{{ $camId }}.show_on_doorbell"
                                        class="rounded text-teal-600 focus:ring-teal-500"
                                    />
                                    <span>Tonen in 5-camera deurbelweergave</span>
                                </label>

                                <label class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        wire:model="cameras.{{ $camId }}.supports_two_way_audio"
                                        class="rounded text-teal-600 focus:ring-teal-500"
                                    />
                                    <span>2-weg audio terugspreken</span>
                                </label>

                                <label class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        wire:model="cameras.{{ $camId }}.is_active"
                                        class="rounded text-teal-600 focus:ring-teal-500"
                                    />
                                    <span>Actief</span>
                                </label>

                                @if (count($cameras) > 1)
                                    <button
                                        type="button"
                                        wire:click="deleteCamera({{ $camId }})"
                                        wire:confirm="Camera '{{ $cam['name'] }}' verwijderen?"
                                        class="text-xs font-semibold text-rose-600 hover:underline dark:text-rose-400 cursor-pointer"
                                    >
                                        Verwijder
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                            <div class="md:col-span-3 space-y-1">
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Weergavenaam</label>
                                <input
                                    type="text"
                                    wire:model="cameras.{{ $camId }}.name"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-white/15 dark:bg-gray-950 dark:text-white"
                                />
                            </div>

                            <div class="md:col-span-2 space-y-1">
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Rol</label>
                                <select
                                    wire:model="cameras.{{ $camId }}.role"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-white/15 dark:bg-gray-950 dark:text-white"
                                >
                                    @foreach (\App\Enums\CameraRole::cases() as $roleCase)
                                        <option value="{{ $roleCase->value }}">{{ $roleCase->getLabel() }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="md:col-span-2 space-y-1">
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">go2rtc streamnaam</label>
                                <input
                                    type="text"
                                    wire:model="cameras.{{ $camId }}.go2rtc_stream"
                                    placeholder="deurbel"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 font-mono text-sm text-gray-900 dark:border-white/15 dark:bg-gray-950 dark:text-white"
                                />
                            </div>

                            <div class="md:col-span-2 space-y-1">
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Fallback Stream URL</label>
                                <input
                                    type="text"
                                    wire:model="cameras.{{ $camId }}.stream_url"
                                    placeholder="Optioneel MJPEG / MP4"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-white/15 dark:bg-gray-950 dark:text-white"
                                />
                            </div>

                            <div class="md:col-span-2 space-y-1">
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Fallback Snapshot URL</label>
                                <input
                                    type="text"
                                    wire:model="cameras.{{ $camId }}.snapshot_url"
                                    placeholder="Optioneel cgi-bin Snap"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-white/15 dark:bg-gray-950 dark:text-white"
                                />
                            </div>

                            <div class="md:col-span-1 space-y-1">
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Volgorde</label>
                                <input
                                    type="number"
                                    min="1"
                                    max="99"
                                    wire:model="cameras.{{ $camId }}.sort_order"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-white/15 dark:bg-gray-950 dark:text-white"
                                />
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <x-filament::button type="submit" size="lg">
                    Camera's &amp; Deurbel Opslaan
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
