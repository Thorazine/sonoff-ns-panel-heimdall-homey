<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Heimdall Kiosk' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-black text-gray-300 font-sans antialiased m-0 p-0 overflow-hidden min-h-screen flex items-center justify-center">
    <main
        x-data="{
            sleepTimer: null,
            resetSleep() {
                if (this.sleepTimer) clearTimeout(this.sleepTimer);
                const params = new URLSearchParams(window.location.search);
                if (window.location.pathname === '/' || params.get('alarm') === 'true') {
                    return;
                }
                this.sleepTimer = setTimeout(() => {
                    if (window.Livewire?.navigate) {
                        window.Livewire.navigate('/');
                    } else {
                        window.location.href = '/';
                    }
                }, 30000);
            }
        }"
        x-init="resetSleep()"
        @click.window="resetSleep()"
        @touchstart.window.passive="resetSleep()"
        class="w-[480px] h-[480px] bg-black text-gray-300 relative overflow-hidden"
    >
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
