<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $authUser = session('user');
        $authUserId = is_array($authUser) ? ($authUser['id'] ?? ($authUser['pengguna_id'] ?? '')) : ($authUser->id ?? ($authUser->pengguna_id ?? ''));
        $authPdId = is_array($authUser) ? ($authUser['peserta_didik_id'] ?? '') : ($authUser->peserta_didik_id ?? '');
        $authPtkId = is_array($authUser) ? ($authUser['ptk_id'] ?? '') : ($authUser->ptk_id ?? '');
        $authRole = is_array($authUser) ? ($authUser['role'] ?? '') : ($authUser->role ?? '');
    @endphp
    <meta name="user-id" content="{{ $authUserId }}">
    <meta name="user-pd-id" content="{{ $authPdId }}">
    <meta name="user-ptk-id" content="{{ $authPtkId }}">
    <meta name="user-role" content="{{ $authRole }}">
    <title>@yield('title', 'Dashboard — SAE (Sistem Aplikasi Edukasi)')</title>

    <!-- Standard SEO & Description -->
    <meta name="description" content="@yield('meta_description', 'SAE (Sistem Aplikasi Edukasi) — Platform digital terpadu untuk absensi pintar, administrasi sekolah, validasi berkas peserta didik, dan layanan akademik modern realtime.')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="SAE (Sistem Aplikasi Edukasi)">
    <meta property="og:title" content="@yield('title', 'Dashboard — SAE (Sistem Aplikasi Edukasi)')">
    <meta property="og:description" content="@yield('meta_description', 'SAE (Sistem Aplikasi Edukasi) — Platform digital terpadu untuk absensi pintar, administrasi sekolah, validasi berkas peserta didik, dan layanan akademik modern realtime.')">
    <meta property="og:image" content="{{ asset('img/logo-icon.png') }}">
    <meta property="og:image:secure_url" content="{{ asset('img/logo-icon.png') }}">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="881">
    <meta property="og:image:height" content="881">

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
        $dashLogoIconVer = @filemtime(public_path('img/logo-icon.png')) ?: '1';
        $dashLogoJsVer = @filemtime(public_path('js/sae-logos.js')) ?: '1';
    @endphp
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('img/logo-icon.png') }}?v={{ $dashLogoIconVer }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('img/logo-icon.png') }}?v={{ $dashLogoIconVer }}">

    @include('partials.pwa-head')

    <!-- FontAwesome 6 Local -->
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <script src="{{ asset('js/sae-logos.js') }}?v={{ $dashLogoJsVer }}"></script>

    <!-- Global App & Dashboard Stylesheets with Cache Busting -->
    <link rel="stylesheet"
        href="{{ asset('css/sae.css') }}?v={{ file_exists(public_path('css/sae.css')) ? filemtime(public_path('css/sae.css')) : time() }}">
    <link rel="stylesheet"
        href="{{ asset('css/dashboard.css') }}?v={{ file_exists(public_path('css/dashboard.css')) ? filemtime(public_path('css/dashboard.css')) : time() }}">
    <link rel="stylesheet"
        href="{{ asset('css/kartu-pelajar.css') }}?v={{ file_exists(public_path('css/kartu-pelajar.css')) ? filemtime(public_path('css/kartu-pelajar.css')) : time() }}">
    @stack('styles')

    <!-- Local Chart.js -->
    <script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
</head>

<body>
    <div class="dashboard-wrapper">
        <!-- Sidebar Khusus Dashboard -->
        @include('partials.dash-sidebar')

        <!-- Konten Utama Dashboard -->
        <div class="dash-main">
            <!-- Header Khusus Dashboard -->
            @include('partials.dash-header')

            <!-- Body View -->
            <main class="dash-body">
                @include('partials.flash-messages')

                @yield('content')
            </main>

            <!-- Footer Khusus Dashboard -->
            @include('partials.dash-footer')
        </div>

        <!-- Mobile Bottom Navigation (5 Menu Utama) -->
        @include('partials.mobile-bottom-nav')

        <!-- SweetAlert2 Local -->
        <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>

        <!-- Global & Dashboard JS Scripts with Cache Busting -->
        <script
            src="{{ asset('js/sae.js') }}?v={{ file_exists(public_path('js/sae.js')) ? filemtime(public_path('js/sae.js')) : time() }}">
        </script>
        <script
            src="{{ asset('js/dashboard.js') }}?v={{ file_exists(public_path('js/dashboard.js')) ? filemtime(public_path('js/dashboard.js')) : time() }}">
        </script>
        <script
            src="{{ asset('js/sae-realtime.js') }}?v={{ file_exists(public_path('js/sae-realtime.js')) ? filemtime(public_path('js/sae-realtime.js')) : time() }}">
        </script>
        <script
            src="{{ asset('js/pwa.js') }}?v={{ file_exists(public_path('js/pwa.js')) ? filemtime(public_path('js/pwa.js')) : time() }}">
        </script>
        @stack('scripts')
</body>

</html>
