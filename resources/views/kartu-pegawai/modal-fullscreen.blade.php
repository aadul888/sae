{{-- 
    Tampilan Layar Penuh (Fullscreen Immersive) Kartu Pegawai Digital SAE
    - Pengalaman kartu portrait ISO CR-80 responsif & interaktif
    - Geser layar (swipe / drag / tap) untuk membalik sisi depan & belakang
    - Dilengkapi menu unduh (PNG resolusi tinggi / PDF) dan Zoom QR Presensi
--}}

<div id="kpPegawaiFullscreenViewer" class="kp-fullscreen-overlay" style="display: none;" onclick="handleKpPegawaiBackdropClick(event)">
    <!-- Topbar Aksi (Floating Glassmorphism) -->
    <div class="kp-viewer-topbar" onclick="event.stopPropagation()">
        <div class="kp-viewer-badge">
            <i class="fas fa-id-badge"></i>
            <span>Kartu Pegawai Digital</span>
        </div>

        <div class="kp-viewer-actions">
            <!-- Tombol Perbesar QR Transaksi / Presensi -->
            <button type="button" class="kp-viewer-btn kp-btn-zoom-qr" id="kpPegawaiBtnZoomQr" onclick="zoomKpPegawaiQrCode(event)"
                title="Perbesar QR Code untuk Presensi / Verifikasi">
                <i class="fas fa-qrcode"></i>
                <span>Perbesar QR</span>
            </button>

            <!-- Tombol Unduh dengan Menu Opsi -->
            <button type="button" class="kp-viewer-btn kp-btn-download" id="kpPegawaiBtnDownload"
                onclick="toggleKpPegawaiDownloadMenu(event)">
                <i class="fas fa-download"></i>
                <span id="kpPegawaiBtnDownloadLabel">Unduh</span>
            </button>

            <!-- Dropdown Pilihan Unduh -->
            <div id="kpPegawaiDownloadMenu" class="kp-download-menu">
                <button type="button" class="kp-download-item" onclick="executePegawaiDownload('active')">
                    <i class="fas fa-image text-accent"></i>
                    <span>Unduh Sisi Aktif (PNG)</span>
                </button>
                <button type="button" class="kp-download-item" onclick="executePegawaiDownload('front')">
                    <i class="fas fa-id-badge text-primary"></i>
                    <span>Unduh Sisi Depan (PNG)</span>
                </button>
                <button type="button" class="kp-download-item" onclick="executePegawaiDownload('back')">
                    <i class="fas fa-barcode text-success"></i>
                    <span>Unduh Sisi Belakang (PNG)</span>
                </button>
                <button type="button" class="kp-download-item" onclick="executePegawaiDownload('both')">
                    <i class="fas fa-layer-group text-warning"></i>
                    <span>Unduh Kedua Sisi (2 File PNG)</span>
                </button>
                <div class="kp-download-divider"></div>
                <a href="#" id="kpPegawaiBtnPrintDirect" target="_blank" class="kp-download-item"
                    style="text-decoration: none;">
                    <i class="fas fa-print" style="color: #cbd5e1;"></i>
                    <span>Cetak / Simpan PDF</span>
                </a>
            </div>

            <!-- Tombol Tutup -->
            <button type="button" class="kp-viewer-btn kp-btn-close" onclick="closeKartuPegawaiModal()"
                title="Tutup Layar Penuh">
                <i class="fas fa-times"></i>
                <span>Tutup</span>
            </button>
        </div>
    </div>

    <!-- Viewport Utama Kartu -->
    <div class="kp-viewer-viewport" id="kpPegawaiViewerArea">
        <!-- Loading Spinner -->
        <div id="kpPegawaiViewerLoading" class="kp-viewer-loading">
            <i class="fas fa-circle-notch fa-spin"></i>
            <span>Memuat Kartu Pegawai Digital...</span>
        </div>

        <!-- Stage Area (Tempat Kartu Mengambang & Bisa Digeser) -->
        <div id="kpPegawaiViewerStage" class="kp-viewer-stage" style="display: none;">
            <!-- Navigasi Panah Kiri -->
            <button type="button" class="kp-nav-arrow kp-arrow-left" id="kpPegawaiArrowLeft" onclick="slidePegawaiCardTo('front')"
                title="Lihat Sisi Depan">
                <i class="fas fa-chevron-left"></i>
            </button>

            <!-- Swipe Wrapper (Hanya 1 Kartu Utuh yang Tampil) -->
            <div class="kp-swipe-wrapper" id="kpPegawaiSwipeWrapper"
                title="Geser ke kiri / kanan atau ketuk kartu untuk membalik">
                <div class="kp-swipe-track" id="kpPegawaiSwipeTrack">
                    <!-- Slide 1: Sisi Depan -->
                    <div class="kp-swipe-slide" id="kpPegawaiSlideFront" data-side="front"></div>
                    <!-- Slide 2: Sisi Belakang -->
                    <div class="kp-swipe-slide" id="kpPegawaiSlideBack" data-side="back"></div>
                </div>
            </div>

            <!-- Navigasi Panah Kanan -->
            <button type="button" class="kp-nav-arrow kp-arrow-right" id="kpPegawaiArrowRight" onclick="slidePegawaiCardTo('back')"
                title="Lihat Sisi Belakang">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>

    <!-- Bottombar: Pengalih Sisi (Pill) & Petunjuk Gestur Geser -->
    <div class="kp-viewer-bottombar" id="kpPegawaiViewerBottomBar" style="display: none;" onclick="event.stopPropagation()">
        <div class="kp-side-switcher">
            <button type="button" class="kp-side-pill active" id="kpPegawaiPillFront" onclick="slidePegawaiCardTo('front')">
                <i class="fas fa-id-badge"></i> Sisi Depan
            </button>
            <button type="button" class="kp-side-pill" id="kpPegawaiPillBack" onclick="slidePegawaiCardTo('back')">
                <i class="fas fa-barcode"></i> Sisi Belakang
            </button>
            <button type="button" class="kp-side-pill" onclick="zoomKpPegawaiQrCode(event)"
                style="background: rgba(56, 189, 248, 0.15); border-color: rgba(56, 189, 248, 0.4); color: #38bdf8; font-weight: 700;"
                title="Perbesar QR Code Presensi & Verifikasi">
                <i class="fas fa-qrcode"></i> QR Presensi
            </button>
        </div>

        <div class="kp-swipe-hint">
            <i class="fas fa-arrows-left-right"></i>
            <span>Geser layar ke kiri atau kanan untuk membalik kartu</span>
        </div>
    </div>

    <!-- Toast Notifikasi Ringkas -->
    <div id="kpPegawaiViewerToast" class="kp-viewer-toast">
        <i class="fas fa-circle-check text-success"></i>
        <span id="kpPegawaiToastText">Berhasil</span>
    </div>
</div>

<!-- Modal Zoom QR Code Layar Penuh -->
<div id="kpPegawaiQrZoomOverlay" class="kp-qr-zoom-overlay" style="display: none;" onclick="closeKpPegawaiQrZoom()">
    <div class="kp-qr-zoom-card" onclick="event.stopPropagation()">
        <div class="kp-qr-zoom-header">
            <div class="kp-qr-zoom-badge">
                <i class="fas fa-qrcode"></i>
                <span>QR Presensi &amp; Verifikasi Pegawai</span>
            </div>
            <button type="button" class="kp-qr-zoom-close" onclick="closeKpPegawaiQrZoom()" title="Tutup Zoom QR">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="kp-qr-zoom-box" id="kpPegawaiQrZoomBox" title="Arahkan ke mesin scanner presensi">
            <!-- Disuntikkan via JavaScript -->
        </div>

        <div class="kp-qr-zoom-info">
            <div class="kp-qr-zoom-name" id="kpPegawaiQrZoomName">-</div>
            <div class="kp-qr-zoom-pills">
                <span class="kp-qr-zoom-pill" id="kpPegawaiQrZoomCode">
                    <i class="fas fa-id-badge"></i> NIP: -
                </span>
            </div>
            <div class="kp-qr-zoom-hint">
                <i class="fas fa-circle-info"></i> Tunjukkan QR Code ini langsung ke scanner presensi atau petugas. Ketuk di mana saja untuk menutup.
            </div>
        </div>
    </div>
</div>

<script
    src="{{ asset('js/kartu-pegawai-fullscreen.js') }}?v={{ file_exists(public_path('js/kartu-pegawai-fullscreen.js')) ? filemtime(public_path('js/kartu-pegawai-fullscreen.js')) : time() }}">
</script>
