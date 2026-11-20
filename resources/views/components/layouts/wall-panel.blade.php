<!DOCTYPE html>
<html lang="nl" class="dark wall-panel-no-zoom">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $title ?? 'Heimdall Wandpaneel' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-black text-gray-100 font-sans antialiased m-0 p-0 w-full h-screen h-dvh overflow-clip select-none touch-none">
    <main class="w-full h-screen h-dvh bg-black text-gray-100 relative overflow-clip touch-none">
        {{ $slot }}
    </main>

    <script>
        document.addEventListener('touchstart', function (event) {
            if (event.touches.length > 1) {
                event.preventDefault();
            }
        }, { passive: false });

        document.addEventListener('touchmove', function (event) {
            if (event.touches.length > 1) {
                event.preventDefault();
            }
        }, { passive: false });

        document.addEventListener('gesturestart', function (event) {
            event.preventDefault();
        }, { passive: false });

        document.addEventListener('dblclick', function (event) {
            event.preventDefault();
        }, { passive: false });
    </script>

    @livewireScripts
</body>
</html>
