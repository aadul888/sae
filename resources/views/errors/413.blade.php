<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>413 &mdash; Berkas Terlalu Besar | SAE</title>
    <meta name="description" content="Ukuran berkas unggahan melebihi batas server pada SAE.">
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
        $errUser = session('user');
        $errRole = $isLoggedIn
            ? (is_array($errUser) ? $errUser['role'] ?? 'admin' : $errUser->role ?? 'admin')
            : null;
        $homeUrl = $isLoggedIn
            ? (\Illuminate\Support\Facades\Route::has('dashboard.' . $errRole)
                ? route('dashboard.' . $errRole)
                : route('dashboard.tendik'))
            : route('login');
    @endphp

    <div class="error-container">
        <div class="error-logo">
            <img src="{{ asset('img/logo-icon.png') }}" alt="SAE" onerror="this.style.display='none'">
            <span>Sistem Aplikasi Edukasi</span>
        </div>

        <div class="error-card">
            <div class="error-icon-wrap">
                <i class="fas fa-file-circle-exclamation"></i>
            </div>
            <div class="error-code">413</div>
            <h1 class="error-title">Ukuran Berkas Terlalu Besar</h1>
            <p class="error-desc">
                Berkas yang Anda unggah melebihi batas maksimum server, sehingga permintaan ditolak
                sebelum diproses. Batas unggah dokumen peserta didik adalah <strong>5 MB</strong> per berkas PDF.
                Perkecil ukuran berkas lalu unggah ulang.
            </p>

            <div class="error-hint">
                <i class="fas fa-circle-info"></i>
                Jika berkas dipindai terlalu besar, kompres atau turunkan resolusi hasil pindai menjadi PDF
                berukuran di bawah 5 MB.
            </div>

            <div class="error-actions">
                <button type="button" class="btn-err btn-back" data-err-action="back-refresh"
                    data-fallback="{{ $homeUrl }}">
                    <i class="fas fa-rotate-right"></i>
                    Muat Ulang Halaman Sebelumnya
                </button>

                <a href="{{ $homeUrl }}" class="btn-err btn-primary">
                    <i class="fas fa-house"></i> {{ $isLoggedIn ? 'Ke Dashboard Saya' : 'Login Kembali' }}
                </a>
            </div>
        </div>

        <div class="error-footer">
            Kode referensi: <code>SAE-413-{{ date('YmdHis') }}</code>
            &nbsp;|&nbsp;
            @if ($isLoggedIn)
                <a href="{{ route('logout') }}">Keluar &amp; Login Ulang</a>
            @else
                <a href="{{ route('login') }}">Kembali ke Login</a>
            @endif
        </div>
    </div>
</body>

</html>
