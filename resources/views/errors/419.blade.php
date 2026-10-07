<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>419 &mdash; Sesi Berakhir | SAE</title>
    <meta name="description" content="Sesi halaman berakhir, silakan muat ulang atau login kembali.">
    <script src="{{ asset('js/error-page.js') }}"></script>
    @php $dashLogoIconVer = @filemtime(public_path('img/logo-icon.png')) ?: '1'; @endphp
    <link rel="icon" type="image/png" href="{{ asset('img/logo-icon.png') }}?v={{ $dashLogoIconVer }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('css/sae.css') }}?v={{ file_exists(public_path('css/sae.css')) ? filemtime(public_path('css/sae.css')) : time() }}">
    <link rel="stylesheet"
        href="{{ asset('css/dashboard.css') }}?v={{ file_exists(public_path('css/dashboard.css')) ? filemtime(public_path('css/dashboard.css')) : time() }}">
    <link rel="stylesheet"
        href="{{ asset('css/error-page.css') }}?v={{ file_exists(public_path('css/error-page.css')) ? filemtime(public_path('css/error-page.css')) : time() }}">
</head>

<body class="error-page error-page--warning">
    @php
        $isLoggedIn = session()->has('user');
        $errRole = $isLoggedIn
            ? (is_array(session('user'))
                ? session('user')['role'] ?? 'admin'
                : session('user')->role ?? 'admin')
            : null;
        $homeUrl = $isLoggedIn ? route('dashboard.' . $errRole) : route('login');
    @endphp

    <div class="error-container">
        <div class="error-logo">
            <img src="{{ asset('img/logo-icon.png') }}" alt="SAE" onerror="this.style.display='none'">
            <span>Sistem Aplikasi Edukasi</span>
        </div>

        <div class="error-card">
            <div class="error-icon-wrap">
                <i class="fas fa-hourglass-end"></i>
            </div>
            <div class="error-code">419</div>
            <h1 class="error-title">Sesi Halaman Telah Berakhir</h1>
            <p class="error-desc">
                Halaman terlalu lama dibiarkan terbuka sehingga sesi keamanannya kedaluwarsa.
                Data yang baru Anda kirim belum tersimpan. Muat ulang halaman lalu ulangi aksi Anda.
            </p>

            <div class="error-hint">
                <i class="fas fa-circle-info"></i>
                @if ($isLoggedIn)
                    Jika masalah berulang, keluar lalu login kembali untuk memperbarui sesi Anda.
                @else
                    Sesi login Anda sudah berakhir. Silakan login kembali untuk melanjutkan.
                @endif
            </div>

            <div class="error-actions">
                <button type="button" class="btn-err btn-back" data-err-action="back-refresh"
                    data-fallback="{{ $homeUrl }}">
                    <i class="fas fa-rotate-right"></i>
                    Muat Ulang Halaman Sebelumnya
                </button>

                @if ($isLoggedIn)
                    <a href="{{ $homeUrl }}" class="btn-err btn-primary">
                        <i class="fas fa-house"></i> Ke Dashboard Saya
                    </a>
                @else
                    <a href="{{ $homeUrl }}" class="btn-err btn-primary">
                        <i class="fas fa-right-to-bracket"></i> Login Kembali
                    </a>
                @endif
            </div>
        </div>

        <div class="error-footer">
            Kode referensi: <code>SAE-419-{{ date('YmdHis') }}</code>
            &nbsp;|&nbsp;
            @if ($isLoggedIn)
                <a href="{{ route('logout') }}">Keluar &amp; Login Ulang</a>
            @else
                <a href="{{ route('home') }}">Ke Beranda</a>
            @endif
        </div>
    </div>
</body>

</html>
