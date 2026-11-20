@props(['icon' => null])

@if ($icon)
<svg
    {{ $attributes->merge(['aria-hidden' => 'true', 'viewBox' => '0 0 64 64', 'fill' => 'none', 'xmlns' => 'http://www.w3.org/2000/svg']) }}
>
    @if ($icon === 'clear-day')
        <circle cx="32" cy="32" r="13" fill="#fbbf24" fill-opacity="0.9" />
        <g stroke="#fbbf24" stroke-width="3.2" stroke-linecap="round">
            <path d="M32 8V13" />
            <path d="M32 51V56" />
            <path d="M8 32H13" />
            <path d="M51 32H56" />
            <path d="M15 15L18.6 18.6" />
            <path d="M45.4 45.4L49 49" />
            <path d="M49 15L45.4 18.6" />
            <path d="M18.6 45.4L15 49" />
        </g>
    @elseif ($icon === 'clear-night')
        <path
            d="M41.5 41.2C32.4 41.2 25 33.8 25 24.7C25 19.6 27.3 15 31 12C21.3 13.1 13.8 21.3 13.8 31.3C13.8 42.1 22.5 50.8 33.3 50.8C41.8 50.8 49 45.4 51.7 37.8C48.7 40 45.2 41.2 41.5 41.2Z"
            fill="#cbd5e1"
            fill-opacity="0.9"
        />
        <circle cx="45" cy="17" r="1.8" fill="#e2e8f0" />
        <circle cx="52" cy="25" r="1.3" fill="#94a3b8" />
    @elseif ($icon === 'partly-cloudy-day')
        <circle cx="24" cy="24" r="10" fill="#fbbf24" fill-opacity="0.9" />
        <g stroke="#fbbf24" stroke-width="2.8" stroke-linecap="round">
            <path d="M24 8V11" />
            <path d="M8 24H11" />
            <path d="M12.7 12.7L14.9 14.9" />
            <path d="M35.3 12.7L33.1 14.9" />
        </g>
        <path
            d="M22 48H46C51 48 55 44 55 39C55 34.3 51.4 30.5 46.8 30.1C45.3 23.8 39.7 19 33 19C25.3 19 19 25.3 19 33C19 33.4 19 33.8 19.1 34.2C15.1 35.3 12.2 39 12.2 43.3C12.2 45.9 16.5 48 22 48Z"
            fill="#94a3b8"
            fill-opacity="0.92"
        />
    @elseif ($icon === 'partly-cloudy-night')
        <path
            d="M32 25C26.5 25 22 20.5 22 15C22 12.3 23.1 9.8 24.9 8C19.2 8.8 14.8 13.7 14.8 19.6C14.8 26.1 20.1 31.4 26.6 31.4C31.2 31.4 35.2 28.7 37.1 24.8C35.5 24.9 33.8 25 32 25Z"
            fill="#cbd5e1"
            fill-opacity="0.88"
        />
        <path
            d="M22 50H46C51 50 55 46 55 41C55 36.3 51.4 32.5 46.8 32.1C45.3 25.8 39.7 21 33 21C25.3 21 19 27.3 19 35C19 35.4 19 35.8 19.1 36.2C15.1 37.3 12.2 41 12.2 45.3C12.2 47.9 16.5 50 22 50Z"
            fill="#64748b"
            fill-opacity="0.95"
        />
    @elseif ($icon === 'fog')
        <path
            d="M19 38H45C49.4 38 53 34.4 53 30C53 25.8 49.8 22.4 45.7 22.1C44.3 16.3 39.1 12 33 12C25.8 12 20 17.8 20 25C20 25.4 20 25.8 20.1 26.1C16 27 13 30.6 13 35C13 36.7 15.7 38 19 38Z"
            fill="#94a3b8"
            fill-opacity="0.82"
        />
        <g stroke="#cbd5e1" stroke-width="3" stroke-linecap="round" opacity="0.85">
            <path d="M14 45H50" />
            <path d="M19 52H45" />
        </g>
    @elseif ($icon === 'rain')
        <path
            d="M19 39H45C49.4 39 53 35.4 53 31C53 26.8 49.8 23.4 45.7 23.1C44.3 17.3 39.1 13 33 13C25.8 13 20 18.8 20 26C20 26.4 20 26.8 20.1 27.1C16 28 13 31.6 13 36C13 37.7 15.7 39 19 39Z"
            fill="#64748b"
            fill-opacity="0.92"
        />
        <g stroke="#38bdf8" stroke-width="3" stroke-linecap="round">
            <path d="M23 45L20 53" />
            <path d="M33 45L30 53" />
            <path d="M43 45L40 53" />
        </g>
    @elseif ($icon === 'snow')
        <path
            d="M19 39H45C49.4 39 53 35.4 53 31C53 26.8 49.8 23.4 45.7 23.1C44.3 17.3 39.1 13 33 13C25.8 13 20 18.8 20 26C20 26.4 20 26.8 20.1 27.1C16 28 13 31.6 13 36C13 37.7 15.7 39 19 39Z"
            fill="#94a3b8"
            fill-opacity="0.9"
        />
        <circle cx="22" cy="47" r="2.5" fill="#e0f2fe" />
        <circle cx="32" cy="51" r="2.5" fill="#e0f2fe" />
        <circle cx="42" cy="47" r="2.5" fill="#e0f2fe" />
    @elseif ($icon === 'thunder')
        <path
            d="M19 38H45C49.4 38 53 34.4 53 30C53 25.8 49.8 22.4 45.7 22.1C44.3 16.3 39.1 12 33 12C25.8 12 20 17.8 20 25C20 25.4 20 25.8 20.1 26.1C16 27 13 30.6 13 35C13 36.7 15.7 38 19 38Z"
            fill="#475569"
            fill-opacity="0.95"
        />
        <path
            d="M34 36L26 47H33L30 57L41 44H34L37 36H34Z"
            fill="#fbbf24"
        />
    @else
        <path
            d="M25 38H49C53.4 38 57 34.4 57 30C57 25.9 53.9 22.5 49.8 22.1C48.4 16.8 43.6 13 38 13C31.4 13 26 18.4 26 25"
            fill="#475569"
            fill-opacity="0.75"
        />
        <path
            d="M18 46H44C49 46 53 42 53 37C53 32.3 49.4 28.5 44.8 28.1C43.3 21.8 37.7 17 31 17C23.3 17 17 23.3 17 31C17 31.4 17 31.8 17.1 32.2C13.1 33.3 10.2 37 10.2 41.3C10.2 43.9 13.7 46 18 46Z"
            fill="#94a3b8"
            fill-opacity="0.92"
        />
    @endif
</svg>
@endif
