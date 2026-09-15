<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Status Formulir' }} — SAE</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">

    <link rel="stylesheet" href="{{ asset('css/formulir-public.css') }}">
</head>

<body>

    @php
        $dashLogoDark = asset('img/logo-dark.png') . '?v=' . (@filemtime(public_path('img/logo-dark.png')) ?: '1');
        $dashLogoIcon = asset('img/logo-icon.png') . '?v=' . (@filemtime(public_path('img/logo-icon.png')) ?: '1');
    @endphp

    <div class="form-container" style="max-width: 540px;">
        <!-- Header Branding SAE -->
        <div style="text-align: center; margin-bottom: 24px;">
            <a href="{{ url('/') }}" style="display: inline-block; text-decoration: none;">
                <img src="{{ $dashLogoDark }}" alt="SAE Logo" style="height: 38px; max-width: 160px; object-fit: contain;"
                    onerror="this.onerror=null; this.src='{{ $dashLogoIcon }}';">
            </a>
            @if (!empty($sekolah->nama))
                <div style="font-size: 0.8rem; color: #64748b; margin-top: 6px; font-weight: 600;">
                    {{ $sekolah->nama }}
                </div>
            @endif
        </div>

        <div class="status-box">
            @if ($status === 'already_submitted')
                <div class="status-icon icon-done">
                    <i class="fas fa-circle-check"></i>
                </div>
            @elseif ($status === 'expired' || $status === 'inactive')
                <div class="status-icon icon-closed">
                    <i class="fas fa-calendar-xmark"></i>
                </div>
            @elseif ($status === 'not_started')
                <div class="status-icon icon-wait">
                    <i class="fas fa-hourglass-half"></i>
                </div>
            @elseif ($status === 'auth_required')
                <div class="status-icon icon-auth">
                    <i class="fas fa-user-lock"></i>
                </div>
            @else
                <div class="status-icon icon-lock">
                    <i class="fas fa-shield-halved"></i>
                </div>
            @endif

            @if (!empty($targetLabel))
                <div class="target-role-badge">
                    <i class="fas fa-users-gear me-1"></i> {{ $targetLabel }}
                </div>
            @endif

            <h1 class="status-title">{{ $title }}</h1>
            <p class="status-msg">{{ $message }}</p>

            @if ($status === 'auth_required')
                <div class="auth-redirect-notice">
                    <i class="fas fa-spinner fa-spin me-1 text-primary"></i> Mengarahkan otomatis ke login dalam <strong
                        id="authGateCountdown" data-redirect-url="{{ route('login') }}">3</strong> detik...
                </div>

                <div class="status-btn-group">
                    <a href="{{ route('login') }}" class="btn-action btn-action-primary">
                        <i class="fas fa-right-to-bracket"></i> Masuk dengan Akun SAE
                    </a>
                    <a href="{{ url('/') }}" class="btn-action btn-action-secondary">
                        <i class="fas fa-arrow-left"></i> Beranda Sekolah
                    </a>
                </div>
            @elseif (session('user'))
                <div class="status-btn-group">
                    <a href="{{ route('dashboard.' . (session('user')['role'] ?? 'admin')) }}"
                        class="btn-action btn-action-primary">
                        <i class="fas fa-gauge-high"></i> Kembali ke Dashboard
                    </a>
                </div>
            @else
                <div class="status-btn-group">
                    <a href="{{ url('/') }}" class="btn-action btn-action-primary">
                        <i class="fas fa-home"></i> Halaman Utama Sekolah
                    </a>
                </div>
            @endif
        </div>

        <div class="footer-branding" style="margin-top: 24px;">
            Didukung oleh <strong>SAE Digital Forms</strong> &bull; Sistem Aplikasi Edukasi
        </div>
    </div>

    <script src="{{ asset('js/formulir-public.js') }}"></script>
</body>

</html>
