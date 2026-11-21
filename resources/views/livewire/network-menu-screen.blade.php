<div class="flex flex-col gap-8">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white">
                Netwerk &amp; DNS Menu
            </h1>
            <p class="mt-1.5 text-sm sm:text-base text-gray-400">
                Overzicht van alle ingestelde DNS-namen en bijbehorende poorten binnen dit netwerk.
            </p>
        </div>

        @if ($totalHostCount > 0)
            <div class="flex flex-wrap items-center gap-2 text-xs font-medium text-gray-300">
                <span class="px-3 py-1.5 rounded-lg bg-gray-900 border border-gray-800">
                    {{ $totalHostCount }} {{ $totalHostCount === 1 ? 'DNS-naam' : 'DNS-namen' }}
                </span>
                <span class="px-3 py-1.5 rounded-lg bg-gray-900 border border-gray-800">
                    {{ $totalPortCount }} {{ $totalPortCount === 1 ? 'poort-koppeling' : 'poort-koppelingen' }}
                </span>
            </div>
        @endif
    </div>

    @if ($totalHostCount > 0)
        <search class="p-4 sm:p-5 rounded-2xl bg-gray-900/80 border border-gray-800 flex flex-col gap-4">
            <form action="{{ route('network.menu') }}" method="GET" @submit.prevent class="flex flex-col sm:flex-row sm:items-end gap-3">
                <div class="flex-1 flex flex-col gap-1.5 min-w-0">
                    <label for="dns-search" class="text-sm font-medium text-gray-300">
                        Zoek op naam, DNS-naam, IP-adres of poortnummer
                    </label>
                    <div class="relative">
                        <svg aria-hidden="true" class="w-5 h-5 text-gray-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <input
                            type="search"
                            id="dns-search"
                            name="q"
                            wire:model.live.debounce.200ms="search"
                            placeholder="Bijv. nas.local, homey, :5000 of 192.168..."
                            autocorrect="off"
                            autocapitalize="none"
                            spellcheck="false"
                            enterkeyhint="search"
                            class="w-full min-h-12 pl-11 pr-4 py-2.5 rounded-xl bg-gray-950 border border-gray-700 text-base text-gray-100 placeholder:text-gray-500 focus:border-teal-400 transition-colors"
                        >
                    </div>
                </div>

                @if ($hasActiveFilters)
                    <button
                        type="button"
                        wire:click="resetFilters"
                        class="min-h-12 px-4 py-2.5 rounded-xl bg-gray-800 hover:bg-gray-700 border border-gray-700 text-sm font-medium text-gray-200 transition-colors cursor-pointer shrink-0"
                    >
                        Filters wissen
                    </button>
                @endif
            </form>

            @if (count($categories) > 1)
                <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Filter op categorie">
                    <button
                        type="button"
                        wire:click="selectCategory('')"
                        aria-pressed="{{ $category === '' ? 'true' : 'false' }}"
                        @class([
                            'min-h-9 px-3.5 py-1.5 rounded-lg text-xs font-semibold border transition-colors cursor-pointer',
                            'bg-teal-500/20 border-teal-400 text-teal-200' => $category === '',
                            'bg-gray-950/70 border-gray-800 text-gray-400 hover:text-gray-200 hover:border-gray-700' => $category !== '',
                        ])
                    >
                        Alle ({{ $totalHostCount }})
                    </button>

                    @foreach ($categories as $categoryName => $categoryCount)
                        <button
                            type="button"
                            wire:click="selectCategory({{ \Illuminate\Support\Js::from($categoryName) }})"
                            aria-pressed="{{ $category === $categoryName ? 'true' : 'false' }}"
                            @class([
                                'min-h-9 px-3.5 py-1.5 rounded-lg text-xs font-semibold border transition-colors cursor-pointer',
                                'bg-teal-500/20 border-teal-400 text-teal-200' => $category === $categoryName,
                                'bg-gray-950/70 border-gray-800 text-gray-400 hover:text-gray-200 hover:border-gray-700' => $category !== $categoryName,
                            ])
                        >
                            {{ $categoryName }} ({{ $categoryCount }})
                        </button>
                    @endforeach
                </div>
            @endif
        </search>
    @endif

    @if ($hasActiveFilters && $totalHostCount > 0)
        <p role="status" aria-live="polite" class="text-sm text-gray-400 -my-3">
            {{ $filteredHostCount }} {{ $filteredHostCount === 1 ? 'resultaat' : 'resultaten' }} gevonden
        </p>
    @endif

    @if ($totalHostCount === 0)
        <section class="p-8 sm:p-12 rounded-2xl bg-gray-900/60 border border-dashed border-gray-800 text-center flex flex-col items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-teal-500/10 border border-teal-500/30 flex items-center justify-center text-teal-400">
                <svg aria-hidden="true" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5a17.92 17.92 0 0 1-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                </svg>
            </div>
            <div class="max-w-md">
                <h2 class="text-xl font-semibold text-white">Nog geen DNS-namen ingesteld</h2>
                <p class="mt-2 text-sm text-gray-400">
                    Voeg in het beheerpaneel je lokale DNS-namen en eventuele poort-toevoegingen toe om snel door je netwerk te navigeren.
                </p>
            </div>
            <a
                href="{{ url('/admin/network-hosts/create') }}"
                class="mt-2 inline-flex items-center gap-2 min-h-11 px-5 py-2.5 rounded-xl bg-teal-500 hover:bg-teal-400 text-gray-950 font-semibold text-sm transition-colors"
            >
                <span>DNS-naam toevoegen in Admin</span>
            </a>
        </section>
    @elseif ($filteredHostCount === 0)
        <section class="p-8 sm:p-10 rounded-2xl bg-gray-900/60 border border-gray-800 text-center flex flex-col items-center gap-3">
            <h2 class="text-lg font-semibold text-white">Geen DNS-namen of poorten gevonden</h2>
            <p class="text-sm text-gray-400">
                Er zijn geen actieve netwerkitems die overeenkomen met je huidige zoekopdracht of categorie.
            </p>
            <button
                type="button"
                wire:click="resetFilters"
                class="mt-2 min-h-11 px-4 py-2 rounded-xl bg-gray-800 hover:bg-gray-700 border border-gray-700 text-sm font-medium text-gray-200 transition-colors cursor-pointer"
            >
                Zoekopdracht wissen
            </button>
        </section>
    @else
        @foreach ($groupedHosts as $groupTitle => $hosts)
            <section class="flex flex-col gap-4">
                <div class="flex items-center gap-3">
                    <h2 class="text-base sm:text-lg font-semibold text-gray-200 tracking-wide">
                        {{ $groupTitle }}
                    </h2>
                    <span class="px-2 py-0.5 rounded-md bg-gray-900 border border-gray-800 text-xs text-gray-400">
                        {{ count($hosts) }}
                    </span>
                </div>

                <div class="network-card-grid">
                    @foreach ($hosts as $host)
                        <article
                            wire:key="network-host-{{ $host->id }}"
                            x-data="{ copied: false }"
                            class="rounded-2xl bg-gray-900/85 border border-gray-800 hover:border-gray-700 p-5 flex flex-col gap-4 overflow-clip transition-colors"
                        >
                            <header class="flex flex-col gap-2 min-w-0">
                                <div class="flex items-start justify-between gap-3 min-w-0">
                                    <h3 class="text-lg font-semibold text-white leading-snug break-words min-w-0">
                                        {{ $host->name }}
                                    </h3>

                                    @if ($host->ip_address)
                                        <span class="px-2.5 py-1 rounded-md bg-gray-950 border border-gray-800 font-mono text-xs text-gray-400 shrink-0">
                                            {{ $host->ip_address }}
                                        </span>
                                    @endif
                                </div>

                                <div class="flex items-center justify-between gap-2 px-3 py-2 rounded-xl bg-gray-950 border border-gray-800/90 min-w-0">
                                    <code class="font-mono text-sm text-teal-300 truncate min-w-0">{{ $host->dns_name }}</code>

                                    <button
                                        type="button"
                                        @click="navigator.clipboard?.writeText({{ \Illuminate\Support\Js::from($host->dns_name) }}); copied = true; setTimeout(() => copied = false, 1800)"
                                        class="min-h-8 px-2.5 py-1 rounded-lg bg-gray-900 hover:bg-gray-800 border border-gray-700/80 text-xs font-medium text-gray-300 transition-colors cursor-pointer shrink-0"
                                    >
                                        <span x-show="!copied">Kopieer<span class="visually-hidden"> {{ $host->dns_name }}</span></span>
                                        <span x-cloak x-show="copied" class="text-teal-300">Gekopieerd</span>
                                    </button>
                                </div>

                                @if ($host->description)
                                    <p class="text-sm text-gray-400 leading-relaxed">
                                        {{ $host->description }}
                                    </p>
                                @endif
                            </header>

                            <div class="flex flex-col gap-2.5 mt-auto pt-1">
                                @if ($host->has_root_link)
                                    <a
                                        href="{{ $host->resolvedUrl() }}"
                                        @if ($host->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                                        class="group flex items-center justify-between gap-3 min-h-11 px-3.5 py-2.5 rounded-xl bg-teal-500/12 hover:bg-teal-500/20 border border-teal-500/35 hover:border-teal-400 text-sm font-medium text-teal-100 transition-colors min-w-0"
                                    >
                                        <span class="flex items-center gap-2 min-w-0">
                                            <span @class([
                                                'px-1.5 py-0.5 rounded text-[11px] font-mono font-semibold uppercase shrink-0',
                                                'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' => $host->resolvedProtocol()->value === 'https',
                                                'bg-gray-900 text-gray-300 border border-gray-700' => $host->resolvedProtocol()->value !== 'https',
                                            ])>
                                                {{ $host->resolvedProtocol()->getLabel() }}
                                            </span>
                                            <span class="truncate">Open {{ $host->displayAddress() }}</span>
                                        </span>

                                        <svg aria-hidden="true" class="w-4 h-4 text-teal-300 group-hover:translate-x-0.5 transition-transform shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                        </svg>
                                    </a>
                                @endif

                                @if ($host->activePorts->isNotEmpty())
                                    <div class="flex flex-col gap-1.5">
                                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 px-0.5">
                                            Poorten &amp; Services
                                        </p>

                                        <ul role="list" class="flex flex-col gap-1.5">
                                            @foreach ($host->activePorts as $portItem)
                                                @php
                                                    $isPortMatch = $needle !== '' && $this->portMatchesSearch($portItem, $needle, $portNeedle);
                                                @endphp
                                                <li>
                                                    <a
                                                        href="{{ $portItem->resolvedUrl($host) }}"
                                                        @if ($portItem->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                                                        @class([
                                                            'group flex items-center justify-between gap-3 min-h-11 px-3.5 py-2 rounded-xl border text-sm transition-colors min-w-0',
                                                            'bg-teal-500/15 border-teal-400 text-white' => $isPortMatch,
                                                            'bg-gray-950/90 hover:bg-gray-800/90 border-gray-800 hover:border-gray-600 text-gray-200' => ! $isPortMatch,
                                                        ])
                                                    >
                                                        <span class="font-medium truncate min-w-0">
                                                            {{ $portItem->label }}<span class="visually-hidden"> op {{ $host->dns_name }}</span>
                                                        </span>

                                                        <span class="flex items-center gap-2 shrink-0">
                                                            <span @class([
                                                                'px-1.5 py-0.5 rounded text-[10px] font-mono uppercase',
                                                                'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' => $portItem->resolvedProtocol()->value === 'https',
                                                                'bg-gray-900 text-gray-400 border border-gray-800' => $portItem->resolvedProtocol()->value !== 'https',
                                                            ])>
                                                                {{ $portItem->resolvedProtocol()->getLabel() }}
                                                            </span>

                                                            <code class="px-2 py-0.5 rounded-md bg-gray-900 border border-gray-700 font-mono text-xs text-teal-300">
                                                                {{ $portItem->displayPortTarget() }}
                                                            </code>

                                                            <svg aria-hidden="true" class="w-4 h-4 text-gray-500 group-hover:text-teal-300 transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                                            </svg>
                                                        </span>
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @elseif (! $host->has_root_link)
                                    <p class="px-3 py-2 rounded-xl bg-gray-950/60 border border-gray-800/80 text-xs text-gray-500">
                                        Alleen DNS-vermelding (geen directe webpoort gekoppeld)
                                    </p>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach
    @endif
</div>
