<!DOCTYPE html>
<html lang="id" data-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard — SAE')</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/img/logo-icon.png">
    <link rel="shortcut icon" type="image/png" href="/img/logo-icon.png">

    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Global App & Dashboard Stylesheets -->
    <link rel="stylesheet" href="/css/sae.css">
    <link rel="stylesheet" href="/css/dashboard.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    <script src="/js/sae.js"></script>
    <script src="/js/dashboard.js"></script>
    @stack('scripts')
</body>

</html>
