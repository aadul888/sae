<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SAE (Sistem Aplikasi Edukasi)')</title>

    <!-- Standard SEO & Description -->
    <meta name="description" content="@yield('meta_description', 'SAE (Sistem Aplikasi Edukasi) — Platform digital terpadu untuk absensi pintar, administrasi sekolah, validasi berkas peserta didik, dan layanan akademik modern realtime.')">
    <meta name="keywords" content="SAE, Sistem Aplikasi Edukasi, aplikasi sekolah, administrasi sekolah, dapodik, absensi rfid, kartu pelajar digital">
    <meta name="author" content="SAE (Sistem Aplikasi Edukasi)">

    <!-- Open Graph / WhatsApp / Telegram / Facebook Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="SAE (Sistem Aplikasi Edukasi)">
    <meta property="og:title" content="@yield('title', 'SAE (Sistem Aplikasi Edukasi)')">
    <meta property="og:description" content="@yield('meta_description', 'SAE (Sistem Aplikasi Edukasi) — Platform digital terpadu untuk absensi pintar, administrasi sekolah, validasi berkas peserta didik, dan layanan akademik modern realtime.')">
    <meta property="og:image" content="{{ asset('img/logo-icon.png') }}">
    <meta property="og:image:secure_url" content="{{ asset('img/logo-icon.png') }}">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="881">
    <meta property="og:image:height" content="881">
    <meta property="og:image:alt" content="SAE (Sistem Aplikasi Edukasi)">

    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:url" content="{{ url()->current() }}">
    <meta name="twitter:title" content="@yield('title', 'SAE (Sistem Aplikasi Edukasi)')">
    <meta name="twitter:description" content="@yield('meta_description', 'SAE (Sistem Aplikasi Edukasi) — Platform digital terpadu untuk absensi pintar, administrasi sekolah, validasi berkas peserta didik, dan layanan akademik modern realtime.')">
    <meta name="twitter:image" content="{{ asset('img/logo-icon.png') }}">

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

    @include('partials.pwa-head')
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

    <script src="{{ asset('js/sae.js') }}?v={{ @filemtime(public_path('js/sae.js')) ?: '1' }}"></script>
    <script src="{{ asset('js/pwa.js') }}?v={{ @filemtime(public_path('js/pwa.js')) ?: '1' }}"></script>
    @yield('scripts')
</body>

</html>
