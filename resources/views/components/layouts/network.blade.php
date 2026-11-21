<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Netwerk & DNS Menu | Heimdall' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-950 text-gray-100 font-sans antialiased m-0 p-0 min-h-dvh flex flex-col">
    <a
        href="#main-content"
        class="visually-hidden focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2.5 focus:rounded-xl focus:bg-teal-400 focus:text-gray-950 focus:font-semibold"
    >
        Ga direct naar het netwerkoverzicht
    </a>

    <header class="border-b border-gray-800/80 bg-gray-900/70 backdrop-blur-md">
        <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-teal-500/15 border border-teal-400/30 text-flashy shrink-0">
                    <svg aria-hidden="true" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5a17.92 17.92 0 0 1-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <p class="text-xs uppercase tracking-widest text-teal-400/90 font-semibold">Lokaal Netwerk</p>
                    <p class="text-sm text-gray-400 truncate">Snelle DNS- en poortnavigatie</p>
                </div>
            </div>

            <nav aria-label="Beheer">
                <a
                    href="{{ url('/admin/network-hosts') }}"
                    class="inline-flex items-center gap-2 min-h-11 px-4 py-2 rounded-xl bg-gray-800/90 hover:bg-gray-800 border border-gray-700 text-sm font-medium text-gray-200 transition-colors"
                >
                    <svg aria-hidden="true" class="w-4 h-4 text-teal-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                    </svg>
                    <span>Beheren in Admin</span>
                </a>
            </nav>
        </div>
    </header>

    <main id="main-content" class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{ $slot }}
    </main>

    <footer class="border-t border-gray-900 py-5 text-center text-xs text-gray-500">
        <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            Heimdall Lokaal Netwerkoverzicht &middot; Beheer DNS-namen en poorten via <a href="{{ url('/admin/network-hosts') }}" class="text-gray-400 hover:text-teal-400 underline underline-offset-2">/admin</a>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
