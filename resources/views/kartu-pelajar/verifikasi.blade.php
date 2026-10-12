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

<body class="verif-body">

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
        <div style="display: flex; align-items: center; gap: 10px;">
            <span class="verif-nav-badge">
                <i class="fas fa-shield-check"></i> <span>SAE Verified</span>
            </span>
            <img src="{{ asset('img/logo-icon.png') }}" alt="SAE"
                style="height: 28px; width: auto; object-fit: contain;">
        </div>
    </header>

    <main class="verif-container">
        <!-- Status Verification Hero -->
        <div class="status-hero">
            <div class="status-icon-bubble">
                <i class="fas fa-circle-check"></i>
            </div>
            <div class="status-hero-tag">
                <i class="fas fa-shield-halved"></i> Pangkalan Data Resmi Sekolah
            </div>
            <h1 class="status-hero-title">KARTU PELAJAR VALID &amp; TERVERIFIKASI</h1>
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
                    <i class="fas fa-id-card-clip"></i> Biodata Ringkas Peserta Didik
                </div>
                <div class="badge-aktif">
                    <i class="fas fa-circle-check"></i> STATUS AKTIF
                </div>
            </div>

            <div class="pd-profile-grid">
                <!-- Foto dengan Watermark Jurusan -->
                <div class="pd-photo-col">
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
                                <i class="fas fa-user-graduate" style="font-size: 42px; opacity: 0.65;"></i>
                                <div
                                    style="font-size: 0.68rem; font-weight: 700; margin-top: 6px; letter-spacing: 0.5px;">
                                    PASFOTO</div>
                            </div>
                        @endif
                    </div>
                    <div class="pd-photo-tag">
                        <i class="fas fa-camera text-primary"></i> Pasfoto Siswa
                    </div>
                </div>

                <!-- Info Fields -->
                <div class="pd-fields">
                    <div class="pd-field-item full-width">
                        <span class="pd-field-label">
                            <i class="fas fa-user-graduate"></i> Nama Lengkap Peserta Didik
                        </span>
                        <span class="pd-field-value">{{ $card['nama'] }}</span>
                    </div>

                    <div class="pd-field-item">
                        <span class="pd-field-label">
                            <i class="fas fa-id-badge"></i> NISN (Nomor Induk Siswa Nasional)
                        </span>
                        <span class="pd-field-value mono">{{ $card['nisn'] }}</span>
                    </div>

                    <div class="pd-field-item">
                        <span class="pd-field-label">
                            <i class="fas fa-hashtag"></i> NIPD (Nomor Induk Peserta Didik)
                        </span>
                        <span class="pd-field-value mono">{{ $card['nipd'] }}</span>
                    </div>

                    <div class="pd-field-item">
                        <span class="pd-field-label">
                            <i class="fas fa-chalkboard-user"></i> Rombongan Belajar (Kelas)
                        </span>
                        <span class="pd-field-value">{{ $card['rombel'] }}</span>
                    </div>

                    <div class="pd-field-item">
                        <span class="pd-field-label">
                            <i class="fas fa-graduation-cap"></i> Kompetensi Keahlian / Jurusan
                        </span>
                        <span class="pd-field-value">{{ $card['jurusan'] }}</span>
                    </div>

                    <div class="pd-field-item">
                        <span class="pd-field-label">
                            <i class="fas fa-calendar-check"></i> Tahun Pelajaran &amp; Semester
                        </span>
                        <span class="pd-field-value">{{ $card['tahun_pelajaran'] }}</span>
                    </div>

                    <div class="pd-field-item">
                        <span class="pd-field-label">
                            <i class="fas fa-school"></i> Satuan Pendidikan (Sekolah)
                        </span>
                        <span class="pd-field-value">{{ $card['sekolah']['nama'] }} (NPSN:
                            {{ $card['sekolah']['npsn'] }})</span>
                    </div>
                </div>
            </div>

            <!-- Privacy Notice -->
            <div class="privacy-box">
                <i class="fas fa-shield-halved"></i>
                <div>
                    <strong>Perlindungan Privasi Data Peserta Didik (Privacy-by-Design):</strong><br>
                    Halaman verifikasi publik ini sengaja membatasi penampilan data sensitif. Nomor Induk
                    Kependudukan (NIK), alamat domisili lengkap, serta kontak keluarga tidak dipublikasikan secara
                    terbuka demi
                    keamanan dan privasi anak di bawah umur.
                </div>
            </div>

            <!-- Tombol Navigasi / Aksi Cepat -->
            <div class="verif-actions">
                <a href="{{ route('home') }}" class="btn-verif btn-verif-secondary">
                    <i class="fas fa-home"></i> <span>Beranda Sekolah</span>
                </a>
                <button type="button" class="btn-verif btn-verif-primary" id="btnCopyLink" onclick="copyVerifyUrl()">
                    <i class="fas fa-copy"></i> <span id="btnCopyText">Salin Tautan Verifikasi</span>
                </button>
            </div>
        </div>

        <!-- Footer -->
        <footer class="verif-footer">
            <div class="verif-footer-card">
                <div class="verif-footer-school">
                    {{ $card['sekolah']['nama'] }} &bull; NPSN: {{ $card['sekolah']['npsn'] }}
                </div>
                <div class="verif-footer-address">
                    {{ $card['sekolah']['alamat'] }}@if (!empty($card['sekolah']['telepon']) && $card['sekolah']['telepon'] !== '-')
                        &bull; Telp: {{ $card['sekolah']['telepon'] }}
                    @endif
                </div>
                <div class="verif-footer-copy">
                    &copy; {{ date('Y') }} Sistem Aplikasi Edukasi (SAE). Seluruh Hak Cipta Dilindungi
                    Undang-Undang.
                </div>
            </div>
        </footer>
    </main>

    <script>
        function copyVerifyUrl() {
            const url = window.location.href;
            const btnText = document.getElementById('btnCopyText');
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(() => {
                    showCopySuccess();
                }).catch(() => {
                    fallbackCopy(url);
                });
            } else {
                fallbackCopy(url);
            }

            function fallbackCopy(text) {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                try {
                    document.execCommand('copy');
                    showCopySuccess();
                } catch (e) {}
                document.body.removeChild(ta);
            }

            function showCopySuccess() {
                if (btnText) {
                    const orig = btnText.textContent;
                    btnText.textContent = 'Tautan Berhasil Disalin!';
                    setTimeout(() => {
                        btnText.textContent = orig;
                    }, 2200);
                }
            }
        }
    </script>
</body>

</html>
