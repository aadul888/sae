<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SAE — Sistem Aplikasi Edukasi')</title>

    <link rel="icon" type="image/png" href="{{ asset('img/logo-icon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/sae.css') }}">
    @yield('styles')
</head>
<body>
    <div class="ambient-glow"></div>
    <div class="ambient-glow-2"></div>

    @include('partials.header')

    <main>
        @yield('content')
    </main>

    @include('partials.footer')

    <script src="{{ asset('js/sae.js') }}"></script>
    @yield('scripts')
</body>
</html>
