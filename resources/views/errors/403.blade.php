<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 &mdash; Akses Ditolak | SAE</title>
    <meta name="description" content="Akses ditolak: Anda tidak memiliki izin atau wewenang untuk membuka halaman ini.">
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

<body class="error-page error-page--forbidden">
    @php
        $userSession = session('user');
        $isLoggedIn = !empty($userSession);
        $userRole = $isLoggedIn
            ? (is_array($userSession)
                ? $userSession['role'] ?? 'guest'
                : $userSession->role ?? 'guest')
            : 'guest';
        $userName = $isLoggedIn
            ? (is_array($userSession)
                ? $userSession['nama'] ?? ($userSession['name'] ?? 'Pengguna')
                : $userSession->nama ?? ($userSession->name ?? 'Pengguna'))
            : null;

        $roleRoute = 'dashboard.' . str_replace('-', '_', $userRole);
        $roleRouteHyphen = 'dashboard.' . str_replace('_', '-', $userRole);
        if (\Illuminate\Support\Facades\Route::has($roleRoute)) {
            $homeUrl = route($roleRoute);
        } elseif (\Illuminate\Support\Facades\Route::has($roleRouteHyphen)) {
            $homeUrl = route($roleRouteHyphen);
        } else {
            $homeUrl = $isLoggedIn ? url('/dashboard') : route('login');
        }

        $exceptionMessage = trim($exception?->getMessage() ?? '');
    @endphp

    <div class="error-container">
        <div class="error-logo">
            <img src="{{ asset('img/logo-icon.png') }}" alt="SAE" onerror="this.style.display='none'">
            <span>Sistem Aplikasi Edukasi</span>
        </div>

        <div class="error-card">
            <div class="error-icon-wrap">
                <i class="fas fa-shield-halved"></i>
            </div>
            <div class="error-code">403</div>
            <h1 class="error-title">Akses Ditolak (Forbidden)</h1>

            @if ($isLoggedIn)
                <div class="error-user-strip">
                    <i class="fas fa-user-shield"></i>
                    <span>Akun: <strong>{{ $userName }}</strong></span>
                    <span class="role-badge">{{ $userRole }}</span>
                </div>
            @endif

            <p class="error-desc">
                {{ $exceptionMessage ?: 'Maaf, akun atau peran Anda saat ini tidak memiliki hak akses untuk membuka halaman atau menjalankan fitur ini.' }}
            </p>

            <div class="error-hint">
                <i class="fas fa-circle-info"></i>
                @if ($isLoggedIn)
                    Fitur ini dibatasi berdasarkan aturan hak akses (RBAC). Apabila Anda memerlukan akses ke modul ini
                    untuk keperluan tugas sekolah, silakan hubungi <strong>Administrator</strong> atau
                    <strong>Koordinator Tata Usaha</strong>.
                @else
                    Halaman ini membutuhkan hak akses khusus. Silakan login terlebih dahulu menggunakan akun dengan izin
                    yang sesuai.
                @endif
            </div>

            <div class="error-actions">
                <button type="button" class="btn-err btn-back" data-err-action="back"
                    data-fallback="{{ $homeUrl }}">
                    <i class="fas fa-arrow-left"></i>
                    Kembali ke Halaman Sebelumnya
                </button>

                @if ($isLoggedIn)
                    <a href="{{ $homeUrl }}" class="btn-err btn-primary">
                        <i class="fas fa-house"></i> Ke Dashboard Saya
                    </a>
                    <a href="{{ route('logout') }}" class="btn-err btn-outline">
                        <i class="fas fa-right-from-bracket"></i> Keluar &amp; Ganti Akun
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn-err btn-primary">
                        <i class="fas fa-right-to-bracket"></i> Login Kembali
                    </a>
                    <a href="{{ url('/') }}" class="btn-err btn-outline">
                        <i class="fas fa-house"></i> Ke Beranda
                    </a>
                @endif
            </div>
        </div>

        <div class="error-footer">
            Kode referensi: <code>SAE-403-{{ date('YmdHis') }}</code>
            &nbsp;|&nbsp;
            @if ($isLoggedIn)
                <a href="{{ route('logout') }}">Keluar dari Sesi</a>
                &nbsp;|&nbsp;
                <a href="{{ $homeUrl }}">Dashboard</a>
            @else
                <a href="{{ route('login') }}">Kembali ke Login</a>
                &nbsp;|&nbsp;
                <a href="{{ url('/') }}">Beranda</a>
            @endif
        </div>
    </div>
</body>

</html>
