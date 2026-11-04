<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Kartu Pegawai — {{ $card['nama'] }}</title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">

    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
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
                <div class="verif-nav-sub">Verifikasi Resmi Kartu Pegawai Digital SAE</div>
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
            <h1 class="status-hero-title">KARTU PEGAWAI VALID &amp; TERVERIFIKASI</h1>
            <p class="status-hero-desc">
                Data pendidik / tenaga kependidikan ini terdaftar resmi secara aktif pada sistem kepegawaian sekolah dan pangkalan data SAE terintegrasi.
            </p>
            <div class="status-hero-time">
                <i class="fas fa-clock"></i> Dipindai pada: {{ $verifiedAt }}
            </div>
        </div>

        <!-- Biodata Ringkas Pegawai (Privacy-by-Design) -->
        <div class="verif-card">
            <div class="verif-card-header">
                <div class="verif-card-title">
                    <i class="fas fa-id-card-clip"></i> Biodata Ringkas Pegawai
                </div>
                <div class="badge-aktif">
                    <i class="fas fa-circle-check"></i> STATUS AKTIF
                </div>
            </div>

            <div class="pd-profile-grid">
                <!-- Foto Pegawai -->
                <div class="pd-photo-col">
                    <div class="pd-photo-wrap">
                        @if (!empty($card['foto_url']))
                            <img src="{{ $card['foto_url'] }}" alt="Foto {{ $card['nama'] }}" class="main-photo">
                        @else
                            <div style="position: relative; z-index: 2; text-align: center; color: var(--text-muted, #94a3b8);">
                                <i class="fas fa-user-tie" style="font-size: 42px; opacity: 0.65;"></i>
                                <div style="font-size: var(--text-2xs, 0.6875rem); font-weight: 700; margin-top: 6px; letter-spacing: 0.5px;">
                                    PASFOTO
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="pd-photo-tag">
                        <i class="fas fa-camera text-primary"></i> Pasfoto Pegawai
                    </div>
                </div>

                <!-- Info Fields -->
                <div class="pd-fields">
                    <div class="pd-field-item full-width">
                        <span class="pd-field-label">
                            <i class="fas fa-user-tie"></i> Nama Lengkap Pegawai
                        </span>
                        <span class="pd-field-value">{{ $card['nama'] }}</span>
                    </div>

                    <div class="pd-field-item full-width">
                        <span class="pd-field-label">
                            <i class="fas fa-id-badge"></i> NIP (Nomor Induk Pegawai)
                        </span>
                        <span class="pd-field-value mono">{{ !empty($card['nip']) && $card['nip'] !== '-' ? $card['nip'] : '-' }}</span>
                    </div>

                    <div class="pd-field-item full-width">
                        <span class="pd-field-label">
                            <i class="fas fa-school"></i> Satuan Pendidikan (Sekolah)
                        </span>
                        <span class="pd-field-value">{{ $card['sekolah']['nama'] }} (NPSN: {{ $card['sekolah']['npsn'] }})</span>
                    </div>
                </div>
            </div>

            <!-- Privacy Notice -->
            <div class="privacy-box">
                <i class="fas fa-shield-halved"></i>
                <div>
                    <strong>Perlindungan Privasi Data Pegawai (Privacy-by-Design):</strong><br>
                    Halaman verifikasi publik ini membatasi pemaparan data sensitif pribadi. Nomor Induk Kependudukan (NIK), rincian kontak keluarga, riwayat kepangkatan, dan alamat rumah tidak dipublikasikan secara terbuka demi keamanan serta privasi pegawai.
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
                    &copy; {{ date('Y') }} Sistem Aplikasi Edukasi (SAE). Hak Cipta Dilindungi Undang-Undang.
                </div>
            </div>
        </footer>
    </main>

    <script>
        function copyVerifyUrl() {
            const url = window.location.href;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(showCopiedState);
            } else {
                const input = document.createElement('input');
                input.value = url;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                showCopiedState();
            }
        }

        function showCopiedState() {
            const btn = document.getElementById('btnCopyLink');
            const txt = document.getElementById('btnCopyText');
            if (!btn || !txt) return;
            const orig = txt.textContent;
            btn.classList.add('copied');
            txt.textContent = 'Tautan Berhasil Disalin!';
            setTimeout(() => {
                btn.classList.remove('copied');
                txt.textContent = orig;
            }, 2500);
        }
    </script>
</body>

</html>
