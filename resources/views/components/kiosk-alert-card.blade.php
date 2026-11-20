@props([
    'item',
    'compact' => false,
    'clickAction' => 'openHub',
])

<button
    type="button"
    wire:click.stop="{{ $clickAction }}"
    @class([
        'rounded-3xl flex flex-col items-center justify-center gap-1.5 transition-transform active:scale-95 select-none cursor-pointer',
        'w-44 min-h-40 py-3 px-2' => $compact,
        'w-48 min-h-48 py-4 px-3' => ! $compact,
        $item['bgClass'],
    ])
>
    @if ($item['kind'] === 'frost')
        <svg
            aria-hidden="true"
            viewBox="0 0 64 64"
            class="{{ $compact ? 'w-18 h-18' : 'w-24 h-24' }} shrink-0"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path d="M32 8V56" stroke="{{ $item['colorHex'] }}" stroke-width="3.2" stroke-linecap="round" />
            <path d="M11.2 20L52.8 44" stroke="{{ $item['colorHex'] }}" stroke-width="3.2" stroke-linecap="round" />
            <path d="M52.8 20L11.2 44" stroke="{{ $item['colorHex'] }}" stroke-width="3.2" stroke-linecap="round" />
            <path d="M26 14L32 19L38 14" stroke="#ecfeff" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M26 50L32 45L38 50" stroke="#ecfeff" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M14.5 28.5L21.5 26L19.5 19" stroke="#ecfeff" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M49.5 35.5L42.5 38L44.5 45" stroke="#ecfeff" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M44.5 19L42.5 26L49.5 28.5" stroke="#ecfeff" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M19.5 45L21.5 38L14.5 35.5" stroke="#ecfeff" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" />
            <polygon points="32,24 38.9,28 38.9,36 32,40 25.1,36 25.1,28" fill="#083344" stroke="{{ $item['colorHex'] }}" stroke-width="2.4" />
            <circle cx="32" cy="32" r="2.5" fill="#ecfeff" />
        </svg>
    @elseif ($item['kind'] === 'rain')
        <svg
            aria-hidden="true"
            viewBox="0 0 64 64"
            class="{{ $compact ? 'w-18 h-18' : 'w-24 h-24' }} shrink-0"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path d="M21 7L18.5 12.5" stroke="#7dd3fc" stroke-width="2.5" stroke-linecap="round" opacity="0.75" />
            <path d="M33 4.5L30.5 10" stroke="#bae6fd" stroke-width="2.5" stroke-linecap="round" opacity="0.9" />
            <path d="M45 7L42.5 12.5" stroke="#7dd3fc" stroke-width="2.5" stroke-linecap="round" opacity="0.75" />
            <path d="M32 13V17" stroke="{{ $item['colorHex'] }}" stroke-width="3" stroke-linecap="round" />
            <path d="M10 34C10 21.85 19.85 16 32 16C44.15 16 54 21.85 54 34C49.5 31.5 44.5 31.5 40.5 34C36.5 31.5 27.5 31.5 23.5 34C19.5 31.5 14.5 31.5 10 34Z" fill="{{ $item['colorHex'] }}" fill-opacity="0.9" />
            <path d="M32 16C26.5 21 24.5 27 23.5 34" stroke="#000000" stroke-opacity="0.25" stroke-width="2" stroke-linecap="round" />
            <path d="M32 16C37.5 21 39.5 27 40.5 34" stroke="#000000" stroke-opacity="0.25" stroke-width="2" stroke-linecap="round" />
            <path d="M32 33V50C32 53.3137 29.3137 56 26 56C22.6863 56 20 53.3137 20 50V48.5" stroke="#e5e7eb" stroke-width="3.5" stroke-linecap="round" />
        </svg>
    @elseif ($item['kind'] === 'trash')
        <svg
            aria-hidden="true"
            viewBox="0 0 64 64"
            class="{{ $compact ? 'w-18 h-18' : 'w-24 h-24' }} shrink-0"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <rect x="24" y="6" width="16" height="4" rx="2" fill="{{ $item['colorHex'] }}" />
            <path d="M12 14C12 11.7909 13.7909 10 16 10H48C50.2091 10 52 11.7909 52 14V17H12V14Z" fill="{{ $item['colorHex'] }}" />
            <path d="M15 19H49L45 54C44.7 56.3 42.8 58 40.5 58H23.5C21.2 58 19.3 56.3 19 54L15 19Z" fill="{{ $item['colorHex'] }}" fill-opacity="0.88" />
            <rect x="24" y="25" width="3" height="24" rx="1.5" fill="#000000" fill-opacity="0.28" />
            <rect x="30.5" y="25" width="3" height="24" rx="1.5" fill="#000000" fill-opacity="0.28" />
            <rect x="37" y="25" width="3" height="24" rx="1.5" fill="#000000" fill-opacity="0.28" />
            <circle cx="20" cy="57" r="4.5" fill="#e5e7eb" />
            <circle cx="20" cy="57" r="2" fill="#1f2937" />
            <circle cx="44" cy="57" r="4.5" fill="#e5e7eb" />
            <circle cx="44" cy="57" r="2" fill="#1f2937" />
        </svg>
    @endif

    <span class="text-xl font-bold tracking-wider uppercase leading-none mt-1" style="color: {{ $item['textHex'] }}">
        {{ $item['label'] }}
    </span>
    @if (! empty($item['dayLabel']))
        <span class="text-base font-medium text-gray-200 leading-none">
            {{ $item['dayLabel'] }}
        </span>
    @endif
</button>
