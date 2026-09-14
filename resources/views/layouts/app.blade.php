<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SAE — Sistem Aplikasi Edukasi')</title>

    <!-- Prevent Theme Flicker (FOUC) -->
    <script>
        (function() {
            try {
                const savedTheme = localStorage.getItem('sae_theme') || (window.matchMedia(
                    '(prefers-color-scheme: light)').matches ? 'light' : 'dark');
                if (savedTheme === 'light') {
                    document.documentElement.setAttribute('data-theme', 'light');
                } else {
                    document.documentElement.removeAttribute('data-theme');
                }
            } catch (e) {}
        })();
    </script>

    @php
        $logoIconVer = @filemtime(public_path('img/logo-icon.png')) ?: '1';
        $faviconVer = @filemtime(public_path('favicon.png')) ?: '1';
        $logoJsVer = @filemtime(public_path('js/sae-logos.js')) ?: '1';
    @endphp
    <link rel="icon" type="image/png" href="{{ asset('img/logo-icon.png') }}?v={{ $logoIconVer }}">
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}?v={{ $faviconVer }}">

    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <script src="{{ asset('js/sae-logos.js') }}?v={{ $logoJsVer }}"></script>
    <link rel="stylesheet" href="{{ asset('css/sae.css') }}?v={{ @filemtime(public_path('css/sae.css')) ?: '1' }}">
    @yield('styles')
</head>

<body>
    <div class="ambient-glow"></div>
    <div class="ambient-glow-2"></div>

    @include('partials.header')

    <main>
        @include('partials.flash-messages')
        @yield('content')
    </main>

    @include('partials.footer')

    <script src="{{ asset('js/sae.js') }}"></script>
    @yield('scripts')
</body>

</html>
