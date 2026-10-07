<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Kartu Pelajar — {{ $card['nama'] }} (NISN: {{ $card['nisn'] }})</title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">

    <!-- Local Fonts & FontAwesome -->
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">

    <!-- Card CSS -->
    <link rel="stylesheet"
        href="{{ asset('css/kartu-pelajar.css') }}?v={{ file_exists(public_path('css/kartu-pelajar.css')) ? filemtime(public_path('css/kartu-pelajar.css')) : time() }}">
</head>

<body>

    <!-- Header Navbar -->
    <header class="verif-nav">
        <a href="{{ route('home') }}" class="verif-nav-brand">
            @if (!empty($card['sekolah']['logo_url']))
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
                Data peserta didik ini terdaftar resmi secara aktif pada pangkalan data sekolah dan sistem SAE
                terintegrasi Dapodik.
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
                    @if (!empty($card['jurusan_logo_url']))
                        <div class="watermark-jurusan" title="Logo Jurusan {{ $card['jurusan'] }}">
                            <img src="{{ $card['jurusan_logo_url'] }}" alt="Logo Jurusan">
                        </div>
                    @endif

                    @if (!empty($card['foto_url']))
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
                        <span class="pd-field-value"
                            style="font-size: 1.15rem; color: #0284c7;">{{ $card['nama'] }}</span>
                    </div>

                    <div class="pd-field-item">
                        <span class="pd-field-label">Nomor Induk Siswa Nasional (NISN)</span>
                        <span class="pd-field-value"
                            style="font-family: monospace; font-size: 1.05rem;">{{ $card['nisn'] }}</span>
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
                        <span class="pd-field-value">{{ $card['sekolah']['nama'] }} (NPSN:
                            {{ $card['sekolah']['npsn'] }})</span>
                    </div>
                </div>
            </div>

            <!-- Privacy Notice -->
            <div class="privacy-box">
                <i class="fas fa-shield-halved" style="font-size: 1.2rem; flex-shrink: 0; margin-top: 1px;"></i>
                <div>
                    <strong>Perlindungan Privasi Data Peserta Didik:</strong>
                    Halaman verifikasi publik ini dirancang dengan prinsip <em>Privacy-by-Design</em>. Nomor Induk
                    Kependudukan (NIK), alamat domisili lengkap, serta data kontak keluarga tidak dipublikasikan demi
                    keamanan anak di bawah umur.
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="verif-footer">
            <p><strong>{{ $card['sekolah']['nama'] }}</strong> &bull; NPSN: {{ $card['sekolah']['npsn'] }}</p>
            <p>{{ $card['sekolah']['alamat'] }} &bull; Telp: {{ $card['sekolah']['telepon'] }}</p>
            <p style="margin-top: 6px; font-size: 0.7rem; color: #94a3b8;">
                &copy; {{ date('Y') }} Sistem Aplikasi Edukasi (SAE). Seluruh Hak Cipta Dilindungi
                Undang-Undang.
            </p>
        </footer>
    </main>

</body>

</html>
