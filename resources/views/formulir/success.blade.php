<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tanggapan Terkirim — {{ $formulir->judul }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">

    <link rel="stylesheet" href="{{ asset('css/formulir-public.css') }}">
    <style>
        .receipt-card {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 24px;
            text-align: left;
            font-size: 0.82rem;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .receipt-row {
            display: flex;
            justify-content: space-between;
        }
    </style>
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
        </div>

        <div class="success-box">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>

            <h1 class="success-title">{{ $formulir->pengaturan['success_title'] ?? 'Tanggapan Berhasil Dikirim!' }}</h1>
            <p class="success-msg">
                {{ $formulir->pengaturan['success_message'] ?? 'Terima kasih telah mengisi formulir ini. Data tanggapan Anda telah tersimpan secara resmi di sistem SAE.' }}
            </p>

            <!-- Bukti Ringkas Transaksi / Tanggapan -->
            <div class="receipt-card">
                <div class="receipt-row">
                    <span style="color: #64748b;">No. Bukti Respon:</span>
                    <strong>#RES-{{ str_pad($respon->id, 6, '0', STR_PAD_LEFT) }}</strong>
                </div>
                <div class="receipt-row">
                    <span style="color: #64748b;">Nama Responden:</span>
                    <strong>{{ $respon->nama_responden ?: 'Responden Publik' }}</strong>
                </div>
                <div class="receipt-row">
                    <span style="color: #64748b;">Waktu Simpan:</span>
                    <span>{{ $respon->created_at ? $respon->created_at->translatedFormat('d F Y, H:i') : now()->format('d/m/Y H:i') }}
                        WIB</span>
                </div>
            </div>

            <div class="status-btn-group">
                @if (session('user'))
                    <a href="{{ route('dashboard.' . (session('user')['role'] ?? 'admin')) }}" class="btn-action btn-action-primary">
                        <i class="fas fa-gauge-high"></i> Kembali ke Dashboard
                    </a>
                @else
                    <a href="{{ url('/') }}" class="btn-action btn-action-primary">
                        <i class="fas fa-home"></i> Halaman Utama Sekolah
                    </a>
                @endif
            </div>
        </div>

        <div class="footer-branding" style="margin-top: 24px;">
            Didukung oleh <strong>SAE Digital Forms</strong> &bull; Sistem Aplikasi Edukasi
        </div>
    </div>

</body>

</html>
