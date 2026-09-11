<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Kartu Pelajar — {{ $card['nama'] }} (NISN: {{ $card['nisn'] }})</title>
    <link rel="icon" type="image/png" href="{{ asset('img/logo-icon.png') }}">
    
    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Card CSS -->
    <link rel="stylesheet" href="{{ asset('css/kartu-pelajar.css') }}">

    <style>
        :root {
            --bg-page: #f8fafc;
            --surface: #ffffff;
            --primary: #0f172a;
            --primary-accent: #2563eb;
            --success: #10b981;
            --border: #e2e8f0;
            --text-main: #0f172a;
            --text-muted: #64748b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-page);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding-bottom: 40px;
        }

        /* Top Header */
        .verif-nav {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .verif-nav-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .verif-nav-logo {
            height: 38px;
            width: auto;
        }

        .verif-nav-title {
            font-size: 0.95rem;
            font-weight: 800;
            color: var(--primary);
            line-height: 1.2;
        }

        .verif-nav-sub {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
        }

        .verif-container {
            max-width: 820px;
            margin: 28px auto 0;
            padding: 0 16px;
            width: 100%;
        }

        /* Verification Badge Card */
        .status-hero {
            background: linear-gradient(135deg, #064e3b 0%, #047857 100%);
            color: #ffffff;
            border-radius: 18px;
            padding: 24px;
            text-align: center;
            box-shadow: 0 10px 25px -5px rgba(5, 150, 105, 0.3);
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
        }

        .status-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -20%;
            width: 140%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 60%);
            pointer-events: none;
        }

        .status-icon-bubble {
            width: 68px;
            height: 68px;
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: #ffffff;
            margin-bottom: 12px;
            animation: pulse-badge 2s infinite ease-in-out;
        }

        @keyframes pulse-badge {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .status-hero-title {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -0.3px;
            margin-bottom: 6px;
        }

        .status-hero-desc {
            font-size: 0.88rem;
            color: rgba(255, 255, 255, 0.9);
            max-width: 520px;
            margin: 0 auto 10px;
        }

        .status-hero-time {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(0, 0, 0, 0.2);
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #d1fae5;
        }

        /* Profile Card */
        .verif-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
            margin-bottom: 24px;
        }

        .verif-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 20px;
        }

        .verif-card-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .verif-card-title i {
            color: var(--primary-accent);
        }

        .pd-profile-grid {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 24px;
            align-items: start;
        }

        @media (max-width: 640px) {
            .pd-profile-grid {
                grid-template-columns: 1fr;
                text-align: center;
            }
        }

        /* Photo Column */
        .pd-photo-wrap {
            width: 140px;
            height: 180px;
            border-radius: 14px;
            overflow: hidden;
            position: relative;
            border: 2px solid #cbd5e1;
            background: #f1f5f9;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pd-photo-wrap .watermark-jurusan {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.35;
            z-index: 1;
        }

        .pd-photo-wrap .watermark-jurusan img {
            width: 80%;
            height: 80%;
            object-fit: contain;
        }

        .pd-photo-wrap .main-photo {
            position: relative;
            z-index: 2;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Data Fields */
        .pd-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        @media (max-width: 540px) {
            .pd-fields {
                grid-template-columns: 1fr;
            }
        }

        .pd-field-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .pd-field-label {
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .pd-field-value {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--primary);
        }

        .badge-aktif {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #dcfce7;
            color: #15803d;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 800;
            border: 1px solid #86efac;
            width: fit-content;
        }

        /* Digital Card View Section */
        .card-preview-section {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
            margin-bottom: 24px;
            text-align: center;
        }

        .card-preview-header {
            margin-bottom: 20px;
        }

        .card-preview-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 4px;
        }

        .card-preview-subtitle {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .privacy-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            padding: 14px 18px;
            font-size: 0.78rem;
            color: #1e40af;
            line-height: 1.45;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 20px;
        }

        .verif-footer {
            text-align: center;
            font-size: 0.75rem;
            color: var(--text-muted);
            line-height: 1.5;
            margin-top: 24px;
        }
    </style>
</head>
<body>

    <!-- Header Navbar -->
    <header class="verif-nav">
        <a href="{{ route('home') }}" class="verif-nav-brand">
            @if(!empty($card['sekolah']['logo_url']))
                <img src="{{ $card['sekolah']['logo_url'] }}" alt="Logo Sekolah" class="verif-nav-logo">
            @else
                <img src="{{ asset('img/logo-icon.png') }}" alt="Logo Sekolah" class="verif-nav-logo">
            @endif
            <div>
                <div class="verif-nav-title">{{ $card['sekolah']['nama'] }}</div>
                <div class="verif-nav-sub">Verifikasi Resmi Kartu Pelajar Digital SAE</div>
            </div>
        </a>
        <div style="display: flex; align-items: center; gap: 8px;">
            <img src="{{ asset('img/logo-icon.png') }}" alt="SAE" style="height: 28px;">
        </div>
    </header>

    <main class="verif-container">
        <!-- Status Verification Hero -->
        <div class="status-hero">
            <div class="status-icon-bubble">
                <i class="fas fa-circle-check"></i>
            </div>
            <h1 class="status-hero-title">KARTU PELAJAR VALID & TERVERIFIKASI</h1>
            <p class="status-hero-desc">
                Data peserta didik ini terdaftar resmi secara aktif pada pangkalan data sekolah dan sistem SAE terintegrasi Dapodik.
            </p>
            <div class="status-hero-time">
                <i class="fas fa-clock"></i> Dipindai pada: {{ $verifiedAt }}
            </div>
        </div>

        <!-- Biodata Ringkas Peserta Didik (Privacy-by-Design) -->
        <div class="verif-card">
            <div class="verif-card-header">
                <div class="verif-card-title">
                    <i class="fas fa-id-badge"></i> Biodata Ringkas Peserta Didik
                </div>
                <div class="badge-aktif">
                    <i class="fas fa-circle-check"></i> STATUS AKTIF
                </div>
            </div>

            <div class="pd-profile-grid">
                <!-- Foto dengan Watermark Jurusan -->
                <div class="pd-photo-wrap">
                    @if(!empty($card['jurusan_logo_url']))
                        <div class="watermark-jurusan" title="Logo Jurusan {{ $card['jurusan'] }}">
                            <img src="{{ $card['jurusan_logo_url'] }}" alt="Logo Jurusan">
                        </div>
                    @endif

                    @if(!empty($card['foto_url']))
                        <img src="{{ $card['foto_url'] }}" alt="Foto {{ $card['nama'] }}" class="main-photo">
                    @else
                        <div style="position: relative; z-index: 2; text-align: center; color: #94a3b8;">
                            <i class="fas fa-user-graduate" style="font-size: 38px;"></i>
                            <div style="font-size: 0.7rem; font-weight: 700; margin-top: 6px;">PASFOTO</div>
                        </div>
                    @endif
                </div>

                <!-- Info Fields -->
                <div class="pd-fields">
                    <div class="pd-field-item" style="grid-column: 1 / -1;">
                        <span class="pd-field-label">Nama Lengkap Peserta Didik</span>
                        <span class="pd-field-value" style="font-size: 1.15rem; color: #0284c7;">{{ $card['nama'] }}</span>
                    </div>

                    <div class="pd-field-item">
                        <span class="pd-field-label">Nomor Induk Siswa Nasional (NISN)</span>
                        <span class="pd-field-value" style="font-family: monospace; font-size: 1.05rem;">{{ $card['nisn'] }}</span>
                    </div>

                    <div class="pd-field-item">
                        <span class="pd-field-label">Nomor Induk Peserta Didik (NIPD)</span>
                        <span class="pd-field-value">{{ $card['nipd'] }}</span>
                    </div>

                    <div class="pd-field-item">
                        <span class="pd-field-label">Rombongan Belajar (Kelas)</span>
                        <span class="pd-field-value">{{ $card['rombel'] }}</span>
                    </div>

                    <div class="pd-field-item">
                        <span class="pd-field-label">Kompetensi Keahlian / Jurusan</span>
                        <span class="pd-field-value">{{ $card['jurusan'] }}</span>
                    </div>

                    <div class="pd-field-item">
                        <span class="pd-field-label">Tahun Pelajaran & Semester</span>
                        <span class="pd-field-value">{{ $card['tahun_pelajaran'] }}</span>
                    </div>

                    <div class="pd-field-item">
                        <span class="pd-field-label">Satuan Pendidikan (Sekolah)</span>
                        <span class="pd-field-value">{{ $card['sekolah']['nama'] }} (NPSN: {{ $card['sekolah']['npsn'] }})</span>
                    </div>
                </div>
            </div>

            <!-- Privacy Notice -->
            <div class="privacy-box">
                <i class="fas fa-shield-halved" style="font-size: 1.2rem; flex-shrink: 0; margin-top: 1px;"></i>
                <div>
                    <strong>Perlindungan Privasi Data Peserta Didik:</strong>
                    Halaman verifikasi publik ini dirancang dengan prinsip <em>Privacy-by-Design</em>. Nomor Induk Kependudukan (NIK), alamat domisili lengkap, serta data kontak keluarga tidak dipublikasikan demi keamanan anak di bawah umur.
                </div>
            </div>
        </div>

        <!-- Tampilan Kartu Pelajar Digital Resmi -->
        <div class="card-preview-section">
            <div class="card-preview-header">
                <h2 class="card-preview-title"><i class="fas fa-id-card"></i> Bentuk Fisik Kartu Pelajar Digital</h2>
                <p class="card-preview-subtitle">Standar CR-80 (Depan & Belakang) &bull; Dilengkapi Logo Jurusan & Barcode Otentikasi</p>
            </div>

            @include('kartu-pelajar.template', ['card' => $card, 'wrapperClass' => ''])
        </div>

        <!-- Footer -->
        <footer class="verif-footer">
            <p><strong>{{ $card['sekolah']['nama'] }}</strong> &bull; NPSN: {{ $card['sekolah']['npsn'] }}</p>
            <p>{{ $card['sekolah']['alamat'] }} &bull; Telp: {{ $card['sekolah']['telepon'] }}</p>
            <p style="margin-top: 6px; font-size: 0.7rem; color: #94a3b8;">
                &copy; {{ date('Y') }} Sistem Administrasi Edukasi (SAE). Seluruh Hak Cipta Dilindungi Undang-Undang.
            </p>
        </footer>
    </main>

</body>
</html>
