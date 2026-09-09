<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistem Aplikasi Edukasi (SAE)</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/sae.css') }}">
</head>

<body class="antialiased"
    style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: var(--bg-main, #0f172a); color: var(--text-main, #f8fafc); font-family: Figtree, sans-serif;">
    <div style="text-align: center; padding: 24px;">
        <h1 style="font-size: 2rem; font-weight: 800; margin-bottom: 8px;">Sistem Aplikasi Edukasi (SAE)</h1>
        <p style="color: var(--text-muted, #94a3b8); margin-bottom: 24px;">Portal Aplikasi Manajemen Sekolah &amp;
            Integrasi Dapodik</p>
        <div style="display: flex; gap: 12px; justify-content: center;">
            <a href="{{ route('home') }}" class="btn btn-primary"
                style="padding: 10px 20px; border-radius: 8px; background: #6366f1; color: #fff; text-decoration: none; font-weight: 600;">Beranda</a>
            <a href="{{ route('login') }}" class="btn btn-outline"
                style="padding: 10px 20px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); color: #fff; text-decoration: none; font-weight: 600;">Masuk</a>
        </div>
        <p style="font-size: 0.78rem; color: #64748b; margin-top: 32px;">Laravel
            v{{ Illuminate\Foundation\Application::VERSION }} (PHP v{{ PHP_VERSION }})</p>
    </div>
</body>

</html>
