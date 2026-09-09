<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard — SAE')</title>

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

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('img/logo-icon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('img/logo-icon.png') }}">

    <!-- FontAwesome 6 Local -->
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <script src="{{ asset('js/sae-logos.js') }}"></script>
    <script defer src="{{ asset('vendor/fontawesome/js/all.min.js') }}"></script>

    <!-- Global App & Dashboard Stylesheets with Cache Busting -->
    <link rel="stylesheet"
        href="{{ asset('css/sae.css') }}?v={{ file_exists(public_path('css/sae.css')) ? filemtime(public_path('css/sae.css')) : time() }}">
    <link rel="stylesheet"
        href="{{ asset('css/dashboard.css') }}?v={{ file_exists(public_path('css/dashboard.css')) ? filemtime(public_path('css/dashboard.css')) : time() }}">

    <!-- Local Chart.js & SweetAlert2 -->
    <script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
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

        <!-- Global & Dashboard JS Scripts with Cache Busting -->
        <script
            src="{{ asset('js/sae.js') }}?v={{ file_exists(public_path('js/sae.js')) ? filemtime(public_path('js/sae.js')) : time() }}">
        </script>
        <script
            src="{{ asset('js/dashboard.js') }}?v={{ file_exists(public_path('js/dashboard.js')) ? filemtime(public_path('js/dashboard.js')) : time() }}">
        </script>
        @stack('scripts')
</body>

</html>
