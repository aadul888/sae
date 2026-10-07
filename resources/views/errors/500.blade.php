<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 &mdash; Server Error | SAE</title>
    <meta name="description" content="Terjadi kesalahan server internal pada SAE.">
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

<body class="error-page">
    <div class="error-container">
        <div class="error-logo">
            <img src="{{ asset('img/logo-icon.png') }}" alt="SAE" onerror="this.style.display='none'">
            <span>Sistem Aplikasi Edukasi</span>
        </div>

        <div class="error-card">
            <div class="error-icon-wrap">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <div class="error-code">500</div>
            <h1 class="error-title">Terjadi Kesalahan Server Internal</h1>
            <p class="error-desc">
                Maaf, server tidak dapat memproses permintaan Anda saat ini karena terjadi kesalahan internal.
                Silakan kembali ke halaman sebelumnya, atau coba beberapa saat lagi.
            </p>

            <div class="error-actions">
                <button type="button" class="btn-err btn-back" data-err-action="back">
                    <i class="fas fa-arrow-left"></i>
                    Kembali ke Halaman Sebelumnya
                </button>

                @if (session()->has('user'))
                    @php $errRole = is_array(session('user')) ? (session('user')['role'] ?? 'admin') : (session('user')->role ?? 'admin'); @endphp
                    <a href="{{ route('dashboard.' . $errRole) }}" class="btn-err btn-primary">
                        <i class="fas fa-house"></i> Ke Dashboard Saya
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn-err btn-primary">
                        <i class="fas fa-right-to-bracket"></i> Login Kembali
                    </a>
                @endif

                <button type="button" class="btn-err btn-outline" data-err-action="reload">
                    <i class="fas fa-rotate-right"></i> Muat Ulang
                </button>
            </div>

            @if (config('app.debug') && isset($exception))
                <div class="divider">Detail Error (Mode Debug Aktif)</div>
                <div class="debug-box">
                    <strong>{{ get_class($exception) }}</strong><br>
                    {{ $exception->getMessage() }}<br>
                    <span>{{ $exception->getFile() }}:{{ $exception->getLine() }}</span>
                </div>
            @endif
        </div>

        <div class="error-footer">
            Kode referensi: <code>SAE-500-{{ date('YmdHis') }}</code>
            &nbsp;|&nbsp;
            @if (session()->has('user'))
                <a href="{{ route('logout') }}">Keluar &amp; Login Ulang</a>
            @else
                <a href="{{ route('login') }}">Kembali ke Login</a>
            @endif
        </div>
    </div>
</body>

</html>
