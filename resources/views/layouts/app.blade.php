<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SAE — Sistem Aplikasi Edukasi')</title>

    <link rel="icon" type="image/png" href="{{ asset('img/logo-icon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}">

    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <script src="{{ asset('js/sae-logos.js') }}"></script>
    <script defer src="{{ asset('vendor/fontawesome/js/all.min.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('css/sae.css') }}">
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
