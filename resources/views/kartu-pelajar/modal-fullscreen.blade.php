{{-- 
    Tampilan Penuh Layar (Fullscreen Immersive) Kartu Pelajar Digital SAE
    - Tanpa bingkai modal / frame luar (full immersion card experience)
    - Kartu digital portrait tampil bersih, terpusat, dan proporsional (100% utuh)
    - Hanya 1 sisi kartu yang tampil dalam satu waktu, beralih dengan di-GESER (swipe / drag / tap)
    - Dilengkapi Tombol Unduh (PNG resolusi tinggi / PDF) dan Tombol Tutup
--}}

<div id="kpFullscreenViewer" class="kp-fullscreen-overlay" style="display: none;" onclick="handleKpBackdropClick(event)">
    <!-- Topbar Aksi (Floating Glassmorphism) -->
    <div class="kp-viewer-topbar" onclick="event.stopPropagation()">
        <div class="kp-viewer-badge">
            <i class="fas fa-id-card"></i>
            <span>Kartu Pelajar Digital</span>
        </div>

        <div class="kp-viewer-actions">
            <!-- Tombol Perbesar QR Transaksi / Presensi -->
            <button type="button" class="kp-viewer-btn kp-btn-zoom-qr" id="kpBtnZoomQr" onclick="zoomKpQrCode(event)"
                title="Perbesar QR Code untuk Presensi / Transaksi">
                <i class="fas fa-qrcode"></i>
                <span>Perbesar QR</span>
            </button>

            <!-- Tombol Unduh dengan Menu Opsi -->
            <button type="button" class="kp-viewer-btn kp-btn-download" id="kpBtnDownload"
                onclick="toggleKpDownloadMenu(event)">
                <i class="fas fa-download"></i>
                <span id="kpBtnDownloadLabel">Unduh</span>
            </button>

            <!-- Dropdown Pilihan Unduh -->
            <div id="kpDownloadMenu" class="kp-download-menu">
                <button type="button" class="kp-download-item" onclick="executeDownload('active')">
                    <i class="fas fa-image text-accent"></i>
                    <span>Unduh Sisi Aktif (PNG)</span>
                </button>
                <button type="button" class="kp-download-item" onclick="executeDownload('front')">
                    <i class="fas fa-id-badge text-primary"></i>
                    <span>Unduh Sisi Depan (PNG)</span>
                </button>
                <button type="button" class="kp-download-item" onclick="executeDownload('back')">
                    <i class="fas fa-barcode text-success"></i>
                    <span>Unduh Sisi Belakang (PNG)</span>
                </button>
                <button type="button" class="kp-download-item" onclick="executeDownload('both')">
                    <i class="fas fa-layer-group text-warning"></i>
                    <span>Unduh Kedua Sisi (2 File PNG)</span>
                </button>
                <div class="kp-download-divider"></div>
                <a href="#" id="kpBtnPrintDirect" target="_blank" class="kp-download-item"
                    style="text-decoration: none;">
                    <i class="fas fa-print" style="color: #cbd5e1;"></i>
                    <span>Cetak / Simpan PDF</span>
                </a>
            </div>

            <!-- Tombol Tutup -->
            <button type="button" class="kp-viewer-btn kp-btn-close" onclick="closeKartuPelajarModal()"
                title="Tutup Layar Penuh">
                <i class="fas fa-times"></i>
                <span>Tutup</span>
            </button>
        </div>
    </div>

    <!-- Viewport Utama Kartu -->
    <div class="kp-viewer-viewport" id="kpViewerArea">
        <!-- Loading Spinner (Tepat di Tengah-tengah Layar) -->
        <div id="kpViewerLoading" class="kp-viewer-loading">
            <i class="fas fa-circle-notch fa-spin"></i>
            <span>Memuat Kartu Pelajar Digital...</span>
        </div>

        <!-- Stage Area (Tempat Kartu Mengambang & Bisa Digeser) -->
        <div id="kpViewerStage" class="kp-viewer-stage" style="display: none;">
            <!-- Navigasi Panah Kiri (Desktop) -->
            <button type="button" class="kp-nav-arrow kp-arrow-left" id="kpArrowLeft" onclick="slideCardTo('front')"
                title="Lihat Sisi Depan">
                <i class="fas fa-chevron-left"></i>
            </button>

            <!-- Swipe Wrapper (Hanya 1 Kartu Utuh yang Tampil) -->
            <div class="kp-swipe-wrapper" id="kpSwipeWrapper"
                title="Geser ke kiri / kanan atau ketuk kartu untuk membalik">
                <div class="kp-swipe-track" id="kpSwipeTrack">
                    <!-- Slide 1: Sisi Depan -->
                    <div class="kp-swipe-slide" id="kpSlideFront" data-side="front"></div>
                    <!-- Slide 2: Sisi Belakang -->
                    <div class="kp-swipe-slide" id="kpSlideBack" data-side="back"></div>
                </div>
            </div>

            <!-- Navigasi Panah Kanan (Desktop) -->
            <button type="button" class="kp-nav-arrow kp-arrow-right" id="kpArrowRight" onclick="slideCardTo('back')"
                title="Lihat Sisi Belakang">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>

    <!-- Bottombar: Pengalih Sisi (Pill) & Petunjuk Gestur Geser -->
    <div class="kp-viewer-bottombar" id="kpViewerBottomBar" style="display: none;" onclick="event.stopPropagation()">
        <!-- Switcher Pill -->
        <div class="kp-side-switcher">
            <button type="button" class="kp-side-pill active" id="kpPillFront" onclick="slideCardTo('front')">
                <i class="fas fa-id-badge"></i> Sisi Depan
            </button>
            <button type="button" class="kp-side-pill" id="kpPillBack" onclick="slideCardTo('back')">
                <i class="fas fa-barcode"></i> Sisi Belakang
            </button>
            <button type="button" class="kp-side-pill" onclick="zoomKpQrCode(event)"
                style="background: rgba(56, 189, 248, 0.15); border-color: rgba(56, 189, 248, 0.4); color: #38bdf8; font-weight: 700;"
                title="Perbesar QR Code Transaksi & Presensi">
                <i class="fas fa-qrcode"></i> QR Presensi
            </button>
        </div>

        <!-- Petunjuk Geser Layar -->
        <div class="kp-swipe-hint">
            <i class="fas fa-arrows-left-right"></i>
            <span>Geser layar ke kiri atau kanan untuk membalik kartu</span>
        </div>
    </div>

    <!-- Toast Notifikasi Ringkas -->
    <div id="kpViewerToast" class="kp-viewer-toast">
        <i class="fas fa-circle-check text-success"></i>
        <span id="kpToastText">Berhasil</span>
    </div>
</div>

<!-- Modal Zoom QR Code Layar Penuh (Untuk Transaksi Cepat & Presensi) -->
<div id="kpQrZoomOverlay" class="kp-qr-zoom-overlay" style="display: none;" onclick="closeKpQrZoom()">
    <div class="kp-qr-zoom-card" onclick="event.stopPropagation()">
        <div class="kp-qr-zoom-header">
            <div class="kp-qr-zoom-badge">
                <i class="fas fa-qrcode"></i>
                <span>QR Transaksi &amp; Presensi</span>
            </div>
            <button type="button" class="kp-qr-zoom-close" onclick="closeKpQrZoom()" title="Tutup Zoom QR">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="kp-qr-zoom-box" id="kpQrZoomBox" title="Arahkan ke mesin scanner">
            <!-- Disuntikkan via JavaScript -->
        </div>

        <div class="kp-qr-zoom-info">
            <div class="kp-qr-zoom-name" id="kpQrZoomName">-</div>
            <div class="kp-qr-zoom-pills">
                <span class="kp-qr-zoom-pill" id="kpQrZoomNisn">
                    <i class="fas fa-id-badge"></i> NISN: -
                </span>
                <span class="kp-qr-zoom-pill" id="kpQrZoomRombel">
                    <i class="fas fa-users-rectangle"></i> Kelas: -
                </span>
            </div>
            <div class="kp-qr-zoom-hint">
                <i class="fas fa-circle-info"></i> Tunjukkan QR Code ini langsung ke mesin scanner atau petugas. Ketuk
                di mana saja untuk menutup.
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/kartu-pelajar-fullscreen.js') }}"></script>
