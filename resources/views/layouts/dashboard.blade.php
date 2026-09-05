<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard — SAE')</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Global App & Dashboard Stylesheets -->
    <link rel="stylesheet" href="{{ asset('css/sae.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
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
                @if(session('success'))
                    <div style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: #10b981; padding: 12px 18px; border-radius: 12px; margin-bottom: 24px; font-size: 0.88rem; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-circle-check"></i> {{ session('success') }}
                    </div>
                @endif

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
