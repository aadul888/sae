<!DOCTYPE html>
<html lang="id" data-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard — SAE')</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('img/logo-icon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('img/logo-icon.png') }}">

    <!-- FontAwesome 6 Local -->
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <script src="{{ asset('js/sae-logos.js') }}"></script>
    <script defer src="{{ asset('vendor/fontawesome/js/all.min.js') }}"></script>

    <!-- Global App & Dashboard Stylesheets -->
    <link rel="stylesheet" href="{{ asset('css/sae.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">

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
    </div>

    <!-- Mobile Bottom Navigation (5 Menu Utama) -->
    @include('partials.mobile-bottom-nav')

    <!-- Global & Dashboard JS Scripts -->
    <script src="{{ asset('js/sae.js') }}"></script>
    <script src="{{ asset('js/dashboard.js') }}"></script>
    @stack('scripts')
</body>

</html>
