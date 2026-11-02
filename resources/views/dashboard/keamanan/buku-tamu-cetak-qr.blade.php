<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak QR Code e-Tamu — {{ $sekolah->nama ?? 'SAE' }}</title>
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sae.css') }}">
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm;
        }
        body {
            background-color: #f1f5f9;
            color: #0f172a;
            font-family: var(--font-family, system-ui, -apple-system, sans-serif);
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .no-print-toolbar {
            width: 100%;
            max-width: 650px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 12px;
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: var(--radius-md, 8px);
            font-size: var(--text-sm, 0.8125rem);
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        .btn-print {
            background: var(--primary, #0284c7);
            color: #ffffff;
        }
        .btn-print:hover {
            background: #0369a1;
        }
        .btn-back {
            background: #ffffff;
            color: var(--text-color, #334155);
            border: 1px solid var(--border-color, #cbd5e1);
        }
        .print-sheet {
            background: #ffffff;
            width: 100%;
            max-width: 650px;
            box-sizing: border-box;
            border: 1px solid #cbd5e1;
            border-radius: var(--radius-xl, 16px);
            padding: 32px 36px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            text-align: center;
        }
        .kop-sekolah {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            border-bottom: 3px double #0f172a;
            padding-bottom: 14px;
            margin-bottom: 24px;
            text-align: left;
        }
        .kop-logo {
            width: 64px;
            height: 64px;
            object-fit: contain;
        }
        .kop-info h2 {
            font-size: var(--text-lg, 1.15rem);
            font-weight: 800;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
        }
        .kop-info p {
            font-size: var(--text-xs, 0.75rem);
            color: #475569;
            margin: 2px 0 0;
            line-height: 1.35;
        }
        .poster-title-wrap {
            margin-bottom: 20px;
        }
        .poster-badge {
            display: inline-block;
            background: rgba(2, 132, 199, 0.1);
            color: var(--primary, #0284c7);
            padding: 4px 14px;
            border-radius: var(--radius-pill, 999px);
            font-size: var(--text-xs, 0.75rem);
            font-weight: 800;
            letter-spacing: 1px;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        .poster-title {
            font-size: var(--text-2xl, 1.6rem);
            font-weight: 900;
            color: #0f172a;
            margin: 0 0 6px;
            letter-spacing: 0.5px;
        }
        .poster-subtitle {
            font-size: var(--text-base, 0.875rem);
            color: #475569;
            margin: 0 auto;
            max-width: 480px;
            line-height: 1.4;
        }
        .qr-frame {
            display: inline-block;
            background: #ffffff;
            border: 3px solid #0284c7;
            border-radius: var(--radius-2xl, 20px);
            padding: 18px;
            box-shadow: 0 8px 20px rgba(2, 132, 199, 0.12);
            margin: 16px 0 12px;
        }
        .qr-frame svg {
            display: block;
            width: 250px;
            height: 250px;
        }
        .qr-url {
            font-family: monospace;
            font-size: var(--text-sm, 0.8125rem);
            color: #0284c7;
            font-weight: 700;
            margin-bottom: 22px;
            word-break: break-all;
        }
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
            margin-bottom: 16px;
        }
        .step-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: var(--radius-lg, 12px);
            padding: 12px 10px;
        }
        .step-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #0284c7;
            color: #ffffff;
            font-size: var(--text-sm, 0.8125rem);
            font-weight: 800;
            margin-bottom: 6px;
        }
        .step-title {
            font-size: var(--text-xs, 0.75rem);
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 2px;
        }
        .step-desc {
            font-size: var(--text-2xs, 0.7rem);
            color: #64748b;
            line-height: 1.25;
        }
        .ortu-highlight-note {
            background: rgba(2, 132, 199, 0.06);
            border-left: 4px solid #0284c7;
            border-radius: var(--radius-sm, 6px);
            padding: 10px 14px;
            text-align: left;
            font-size: var(--text-xs, 0.75rem);
            color: #0369a1;
            line-height: 1.35;
        }
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .no-print-toolbar {
                display: none !important;
            }
            .print-sheet {
                border: none;
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

    <div class="no-print-toolbar">
        <a href="{{ route('dashboard.keamanan.buku-tamu.index') }}" class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Kembali ke Buku Tamu
        </a>
        <button type="button" onclick="window.print()" class="btn-action btn-print">
            <i class="fas fa-print"></i> Cetak Dokumen / Poster
        </button>
    </div>

    <div class="print-sheet">
        {{-- Kop Resmi Sekolah --}}
        @php
            $schoolLogo = !empty($sekolahMeta->logo_url) ? $sekolahMeta->logo_url : (!empty($sekolah->logo_url) ? $sekolah->logo_url : asset('img/logo-icon.png'));
        @endphp
        <div class="kop-sekolah">
            <img src="{{ $schoolLogo }}" alt="Logo Sekolah" class="kop-logo" onerror="this.onerror=null; this.src='{{ asset('img/logo-icon.png') }}';">
            <div class="kop-info">
                <h2>{{ $sekolah->nama ?? 'Sistem Aplikasi Edukasi' }}</h2>
                <p>
                    @if (!empty($sekolah->npsn)) NPSN: {{ $sekolah->npsn }} &bull; @endif
                    {{ $sekolah->alamat_jalan ?? 'Lingkungan Kampus Sekolah' }}<br>
                    @if (!empty($sekolah->desa_kelurahan)) {{ $sekolah->desa_kelurahan }}, @endif
                    @if (!empty($sekolah->kecamatan)) {{ $sekolah->kecamatan }}, @endif
                    @if (!empty($sekolah->kabupaten_kota)) {{ $sekolah->kabupaten_kota }} @endif
                </p>
            </div>
        </div>

        {{-- Judul Poster --}}
        <div class="poster-title-wrap">
            <span class="poster-badge"><i class="fas fa-shield-halved me-1"></i> Pos Keamanan Terpadu</span>
            <h1 class="poster-title">BUKU TAMU DIGITAL (e-TAMU)</h1>
            <p class="poster-subtitle">Silakan pindai (scan) kode QR di bawah ini menggunakan kamera ponsel Anda untuk mengisi formulir kunjungan mandiri.</p>
        </div>

        {{-- QR Code Utama --}}
        <div class="qr-frame">
            {!! $qrSvg !!}
        </div>
        <div class="qr-url">{{ $targetUrl }}</div>

        {{-- Langkah Mudah Pengisian --}}
        <div class="steps-grid">
            <div class="step-box">
                <div class="step-num">1</div>
                <div class="step-title">Pindai QR Code</div>
                <div class="step-desc">Buka kamera ponsel Anda dan arahkan ke kode QR di atas.</div>
            </div>
            <div class="step-box">
                <div class="step-num">2</div>
                <div class="step-title">Isi Data Kunjungan</div>
                <div class="step-desc">Lengkapi identitas, tujuan kunjungan, dan keperluan Anda.</div>
            </div>
            <div class="step-box">
                <div class="step-num">3</div>
                <div class="step-title">Tunjukkan Bukti</div>
                <div class="step-desc">Tunjukkan tiket digital kepada Petugas Satpam di pos.</div>
            </div>
        </div>

        {{-- Catatan Khusus Tamu Orang Tua --}}
        <div class="ortu-highlight-note">
            <strong><i class="fas fa-user-graduate me-1"></i> Khusus Tamu Orang Tua / Wali Siswa:</strong><br>
            Sistem kami dapat <em>mendeteksi putra/putri Anda secara otomatis</em>. Cukup ketikkan <strong>NIK Anda (16 digit)</strong> atau <strong>NISN siswa</strong> pada formulir kunjungan, nama siswa dan kelas akan terisi secara instan.
        </div>
    </div>

</body>
</html>
