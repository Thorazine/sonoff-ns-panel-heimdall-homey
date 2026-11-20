@props(['icon' => 'grid'])

@php
    $iconKey = $icon instanceof \BackedEnum ? $icon->value : (string) $icon;
@endphp

<svg
    {{ $attributes->merge([
        'aria-hidden' => 'true',
        'viewBox' => '0 0 24 24',
        'fill' => 'none',
        'stroke' => 'currentColor',
        'stroke-width' => '1.8',
        'stroke-linecap' => 'round',
        'stroke-linejoin' => 'round',
    ]) }}
>
    @switch($iconKey)
        @case('shield-check')
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
            <path d="M9 12l2 2 4-4" />
            @break

        @case('video-camera')
            <rect x="2" y="6" width="14" height="12" rx="2" />
            <path d="M16 10l6-3v10l-6-3v-4z" />
            @break

        @case('bell')
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
            @break

        @case('microphone')
            <rect x="9" y="2" width="6" height="11" rx="3" />
            <path d="M19 10v1a7 7 0 0 1-14 0v-1" />
            <path d="M12 18v4" />
            <path d="M8 22h8" />
            @break

        @case('dumbbell')
            <path d="M6.5 6.5L17.5 17.5" />
            <path d="M3 10l7-7" />
            <path d="M14 21l7-7" />
            <path d="M2 6l4-4" />
            <path d="M18 22l4-4" />
            @break

        @case('power')
            <path d="M12 2v10" />
            <path d="M18.4 6.6a9 9 0 1 1-12.77.04" />
            @break

        @case('waves')
            <path d="M2 13c2-2 4-2 6 0s4 2 6 0 4-2 6 0" />
            <path d="M2 18c2-2 4-2 6 0s4 2 6 0 4-2 6 0" />
            <path d="M8 8c0-1.5 1.5-2.5 1.5-4" />
            <path d="M12 8c0-1.5 1.5-2.5 1.5-4" />
            <path d="M16 8c0-1.5 1.5-2.5 1.5-4" />
            @break

        @case('thermometer')
            <path d="M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z" />
            <path d="M11.5 9v6" />
            @break

        @case('blinds')
            <path d="M3 4h18" />
            <path d="M4 4v14h16V4" />
            <path d="M4 8h16" />
            <path d="M4 12h16" />
            <path d="M12 18v3" />
            <circle cx="12" cy="21" r="1" />
            @break

        @case('droplet')
            <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z" />
            @break

        @case('fan')
            <path d="M12 12c0-3 2.5-5.5 5.5-5.5S21 8.5 19 11c-1.5 1.8-4 1-7 1z" />
            <path d="M12 12c3 0 5.5 2.5 5.5 5.5S15.5 21 13 19c-1.8-1.5-1-4-1-7z" />
            <path d="M12 12c0 3-2.5 5.5-5.5 5.5S3 15.5 5 13c1.5-1.8 4-1 7-1z" />
            <path d="M12 12c-3 0-5.5-2.5-5.5-5.5S8.5 3 11 5c1.8 1.5 1 4 1 7z" />
            <circle cx="12" cy="12" r="1.5" />
            @break

        @case('lightbulb')
            <path d="M9 18h6" />
            <path d="M10 22h4" />
            <path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 0 1 8.91 14" />
            @break

        @case('home')
            <path d="M3 10.5L12 3l9 7.5" />
            <path d="M5 9.5V20a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V9.5" />
            <path d="M9 21v-6h6v6" />
            @break

        @case('sun')
            <circle cx="12" cy="12" r="4" />
            <path d="M12 2v2" />
            <path d="M12 20v2" />
            <path d="M4.93 4.93l1.41 1.41" />
            <path d="M17.66 17.66l1.41 1.41" />
            <path d="M2 12h2" />
            <path d="M20 12h2" />
            <path d="M6.34 17.66l-1.41 1.41" />
            <path d="M19.07 4.93l-1.41 1.41" />
            @break

        @case('moon')
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
            @break

        @case('lock')
            <rect x="4" y="11" width="16" height="10" rx="2" />
            <path d="M8 11V7a4 4 0 0 1 8 0v4" />
            @break

        @case('lock-open')
            <rect x="4" y="11" width="16" height="10" rx="2" />
            <path d="M8 11V7a4 4 0 0 1 7.6-1.7" />
            @break

        @case('bolt')
            <path d="M13 2L3 14h8l-1 8 10-12h-8l1-8z" />
            @break

        @case('fire')
            <path d="M12 22c4.97 0 8-3.58 8-8 0-3.5-2.5-6.5-5-8.5-.5 2-2 3.5-3.5 3.5 0-2.5-1-5-3-7C6.5 5 4 9 4 14c0 4.42 3.03 8 8 8z" />
            @break

        @case('sparkles')
            <path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z" />
            <path d="M19 16l.9 2.1L22 19l-2.1.9L19 22l-.9-2.1L16 19l2.1-.9L19 16z" />
            @break

        @case('chevron-up')
            <path d="M18 15l-6-6-6 6" />
            <path d="M18 20l-6-6-6 6" />
            @break

        @case('chevron-down')
            <path d="M6 9l6 6 6-6" />
            <path d="M6 4l6 6 6-6" />
            @break

        @case('stop')
            <rect x="6" y="6" width="12" height="12" rx="2" />
            @break

        @default
            <rect x="3" y="3" width="7" height="7" rx="1.5" />
            <rect x="14" y="3" width="7" height="7" rx="1.5" />
            <rect x="3" y="14" width="7" height="7" rx="1.5" />
            <rect x="14" y="14" width="7" height="7" rx="1.5" />
    @endswitch
</svg>
