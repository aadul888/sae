<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Integritas Sistem — SAE</title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('css/error-page.css') }}?v={{ file_exists(public_path('css/error-page.css')) ? filemtime(public_path('css/error-page.css')) : time() }}">
</head>

<body class="error-page">
    <div class="fault-card">
        <div class="fault-icon">
            <i class="fas fa-shield-halved"></i>
        </div>

        <h1 class="fault-title">Pemeriksaan Integritas Gagal</h1>
        <p class="fault-subtitle">
            Sistem mendeteksi modifikasi atau penghapusan komponen penting aplikasi.
            Layanan dihentikan demi menjaga integritas data dan hak kepemilikan pengembang.
        </p>

        @if (!empty($dev['n']))
            <div class="info-box">
                <div class="info-row">
                    <i class="fas fa-user-shield"></i>
                    <div><strong>Pengembang:</strong> <span>{{ $dev['n'] }}</span></div>
                </div>
                @if (!empty($dev['w']))
                    <div class="info-row">
                        <i class="fab fa-whatsapp"></i>
                        <div><strong>WhatsApp:</strong> <span>{{ $dev['w'] }}</span></div>
                    </div>
                @endif
                @if (!empty($dev['a']))
                    <div class="info-row">
                        <i class="fas fa-map-marker-alt"></i>
                        <div><strong>Alamat:</strong> <span>{{ $dev['a'] }}</span></div>
                    </div>
                @endif
            </div>

            @if (!empty($dev['w']))
                @php
                    $waClean = preg_replace('/^0/', '62', $dev['w']);
                @endphp
                <a href="https://wa.me/{{ $waClean }}" target="_blank" rel="noopener" class="wa-btn">
                    <i class="fab fa-whatsapp wa-btn-icon"></i>
                    Hubungi Pengembang Resmi
                </a>
            @endif
        @endif

        <p class="footer-note">
            Silakan hubungi pengembang resmi untuk memulihkan lisensi dan sistem.
            <br>
            <span class="error-code-badge">SEC_INTEGRITY_MISMATCH_503</span>
        </p>
    </div>
</body>

</html>
