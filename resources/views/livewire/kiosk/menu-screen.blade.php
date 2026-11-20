<div
    @class([
        'w-full h-full bg-black flex flex-col relative',
        'p-6 items-center justify-center gap-8' => count($openSensors) === 0,
        'pt-24 pb-6 px-6' => count($openSensors) > 0,
    ])
>
    @if (count($openSensors) > 0)
        {{-- Floating Top Bar --}}
        <div class="absolute top-0 left-0 w-full bg-gray-900 border-b border-gray-700 flex justify-between px-6 py-4 z-10 shadow-lg">
            <button
                type="button"
                wire:click="goBack"
                class="px-8 py-3 rounded-xl bg-red-600/20 border border-red-500 text-red-500 font-bold active:bg-red-600/40 transition-colors cursor-pointer"
            >
                Annuleer
            </button>
            <button
                type="button"
                wire:click="acceptAndProceed"
                class="px-8 py-3 rounded-xl bg-green-600/20 border border-green-500 text-green-500 font-bold active:bg-green-600/40 transition-colors cursor-pointer"
            >
                Accepteer
            </button>
        </div>

        <h1 class="text-2xl text-red-400 font-light tracking-wider mb-6 text-center mt-4">
            OPEN RAMEN / DEUREN
        </h1>

        <ul role="list" class="flex-1 overflow-y-auto w-full flex flex-col gap-3 scrollbar-hide">
            @foreach ($openSensors as $sensor)
                <li class="w-full p-4 rounded-xl bg-gray-800/50 border border-gray-700 text-gray-300 text-lg">
                    {{ $sensor }}
                </li>
            @endforeach
        </ul>
    @else
        <h1 class="text-3xl text-gray-400 font-light tracking-wider mb-4 select-none">
            <span wire:loading.remove wire:target="selectMode">KIES MODUS</span>
            <span wire:loading wire:target="selectMode">CONTROLE...</span>
        </h1>

        <button
            type="button"
            wire:click="selectMode('armed')"
            wire:loading.attr="disabled"
            class="w-full py-6 rounded-2xl bg-gray-900 border border-gray-700 text-flashy text-2xl font-bold shadow-[0_0_15px_rgba(0,255,204,0.1)] active:scale-95 active:bg-gray-800 transition-all disabled:opacity-50 cursor-pointer"
        >
            VOLLEDIG INGESCHAKELD
        </button>

        <button
            type="button"
            wire:click="selectMode('partially_armed')"
            wire:loading.attr="disabled"
            class="w-full py-6 rounded-2xl bg-gray-900 border border-gray-700 text-yellow-400 text-2xl font-bold shadow-[0_0_15px_rgba(250,204,21,0.1)] active:scale-95 active:bg-gray-800 transition-all disabled:opacity-50 cursor-pointer"
        >
            GEDEELTELIJK INGESCHAKELD
        </button>

        <button
            type="button"
            wire:click="goBack"
            wire:loading.attr="disabled"
            class="mt-4 px-8 py-3 rounded-xl text-gray-500 border border-gray-800 active:bg-gray-900 transition-colors disabled:opacity-50 cursor-pointer"
        >
            Annuleren
        </button>
    @endif
</div>
