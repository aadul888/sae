{{-- 
    Tampilan Penuh Layar (Fullscreen Immersive) Kartu Pelajar Digital SAE
    - Tanpa bingkai modal / frame luar (full immersion card experience)
    - Kartu digital portrait tampil bersih, terpusat, dan proporsional (100% utuh)
    - Hanya 1 sisi kartu yang tampil dalam satu waktu, beralih dengan di-GESER (swipe / drag / tap)
    - Dilengkapi Tombol Unduh (PNG resolusi tinggi / PDF) dan Tombol Tutup
--}}

<style>
/* Fullscreen Viewer Embedded Styles (Anti-Cache Protection) */
.kp-fullscreen-overlay {
    position: fixed !important;
    inset: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    height: 100dvh !important;
    max-height: 100dvh !important;
    background: radial-gradient(circle at 50% 30%, rgba(15, 23, 42, 0.96) 0%, rgba(2, 6, 23, 0.99) 100%) !important;
    backdrop-filter: blur(16px) !important;
    -webkit-backdrop-filter: blur(16px) !important;
    z-index: 9999999 !important;
    display: flex;
    flex-direction: column !important;
    justify-content: space-between !important;
    align-items: center !important;
    padding: calc(env(safe-area-inset-top, 6px) + 6px) 12px calc(env(safe-area-inset-bottom, 8px) + 6px) 12px !important;
    box-sizing: border-box !important;
    user-select: none !important;
    -webkit-user-select: none !important;
    overflow: hidden !important;
}

.kp-viewer-topbar {
    width: 100% !important;
    max-width: 480px !important;
    height: 40px !important;
    flex-shrink: 0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    z-index: 20 !important;
    gap: 8px !important;
    margin-bottom: 2px !important;
}

.kp-viewer-viewport {
    flex: 1 1 0% !important;
    width: 100% !important;
    height: 100% !important;
    min-height: 0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    position: relative !important;
    overflow: hidden !important;
    touch-action: pan-y !important;
}

.kp-viewer-loading {
    position: absolute !important;
    inset: 0 !important;
    width: 100% !important;
    height: 100% !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 16px !important;
    color: #f1f5f9 !important;
    font-size: 0.95rem !important;
    font-weight: 600 !important;
    letter-spacing: 0.4px !important;
    text-align: center !important;
    z-index: 50 !important;
    pointer-events: none !important;
}

.kp-viewer-loading i {
    font-size: 2.8rem !important;
    color: #38bdf8 !important;
    filter: drop-shadow(0 0 18px rgba(56, 189, 248, 0.65)) !important;
}

.kp-viewer-stage {
    width: 100% !important;
    height: 100% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    position: relative !important;
}

/* Container jendela pandang kartu: HANYA muat 1 kartu */
.kp-swipe-wrapper {
    position: relative !important;
    width: 100% !important;
    max-width: 440px !important;
    height: 100% !important;
    overflow: hidden !important;
    display: block !important;
    margin: 0 auto !important;
    cursor: grab;
    user-select: none !important;
    -webkit-user-select: none !important;
}

.kp-swipe-wrapper:active {
    cursor: grabbing;
}

/* Track rel geser horizontal (200% = Sisi Depan + Sisi Belakang) */
.kp-swipe-track {
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    bottom: 0 !important;
    width: 200% !important;
    min-width: 200% !important;
    max-width: 200% !important;
    flex-shrink: 0 !important;
    height: 100% !important;
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: nowrap !important;
    margin: 0 !important;
    padding: 0 !important;
    transform: translateX(0%);
    will-change: transform;
    transition: transform 0.38s cubic-bezier(0.16, 1, 0.3, 1);
}

/* Masing-masing slide = tepat 50% track = 100% lebar wrapper */
.kp-swipe-slide {
    width: 50% !important;
    min-width: 50% !important;
    max-width: 50% !important;
    flex: 0 0 50% !important;
    height: 100% !important;
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
    box-sizing: border-box !important;
    padding: 4px 8px !important;
    margin: 0 !important;
    overflow: visible !important;
}

/* Kartu di fullscreen: diskalakan otomatis pas vertikal & horizontal */
.kp-fullscreen-overlay .kp-card {
    transform: scale(var(--kp-viewer-scale, 1.2)) !important;
    transform-origin: center center !important;
    box-shadow: 0 20px 50px -10px rgba(0, 0, 0, 0.8), 0 0 0 1px rgba(255, 255, 255, 0.22) !important;
    border-radius: 3.8mm !important;
    margin: 0 !important;
    flex-shrink: 0 !important;
    transition: box-shadow 0.2s ease !important;
}

/* Logo Jurusan Transparansi 85% (Opacity 0.15) */
.kp-card-watermark {
    opacity: 0.15 !important;
}

.kp-qr-zoom-overlay {
    display: none !important;
}

.kp-qr-zoom-overlay.show,
.kp-qr-zoom-overlay.active {
    display: flex !important;
}

.kp-viewer-bottombar {
    width: 100% !important;
    max-width: 440px !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    gap: 6px !important;
    z-index: 20 !important;
    flex-shrink: 0 !important;
    margin-top: 2px !important;
}
</style>

<div id="kpFullscreenViewer" class="kp-fullscreen-overlay" style="display: none;" onclick="handleKpBackdropClick(event)">
    <!-- Topbar Aksi (Floating Glassmorphism) -->
    <div class="kp-viewer-topbar" onclick="event.stopPropagation()">
        <div class="kp-viewer-badge">
            <i class="fas fa-id-card"></i>
            <span>Kartu Pelajar Digital</span>
        </div>

        <div class="kp-viewer-actions">
            <!-- Tombol Unduh dengan Menu Opsi -->
            <button type="button" class="kp-viewer-btn kp-btn-download" id="kpBtnDownload" onclick="toggleKpDownloadMenu(event)">
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
                <a href="#" id="kpBtnPrintDirect" target="_blank" class="kp-download-item" style="text-decoration: none;">
                    <i class="fas fa-print" style="color: #cbd5e1;"></i>
                    <span>Cetak / Simpan PDF</span>
                </a>
            </div>

            <!-- Tombol Tutup -->
            <button type="button" class="kp-viewer-btn kp-btn-close" onclick="closeKartuPelajarModal()" title="Tutup Layar Penuh">
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
            <button type="button" class="kp-nav-arrow kp-arrow-left" id="kpArrowLeft" onclick="slideCardTo('front')" title="Lihat Sisi Depan">
                <i class="fas fa-chevron-left"></i>
            </button>

            <!-- Swipe Wrapper (Hanya 1 Kartu Utuh yang Tampil) -->
            <div class="kp-swipe-wrapper" id="kpSwipeWrapper" title="Geser ke kiri / kanan atau ketuk kartu untuk membalik">
                <div class="kp-swipe-track" id="kpSwipeTrack">
                    <!-- Slide 1: Sisi Depan -->
                    <div class="kp-swipe-slide" id="kpSlideFront" data-side="front"></div>
                    <!-- Slide 2: Sisi Belakang -->
                    <div class="kp-swipe-slide" id="kpSlideBack" data-side="back"></div>
                </div>
            </div>

            <!-- Navigasi Panah Kanan (Desktop) -->
            <button type="button" class="kp-nav-arrow kp-arrow-right" id="kpArrowRight" onclick="slideCardTo('back')" title="Lihat Sisi Belakang">
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
                <i class="fas fa-circle-info"></i> Tunjukkan QR Code ini langsung ke mesin scanner atau petugas. Ketuk di mana saja untuk menutup.
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    let currentKpNisn = '';
    let currentSide = 'front';
    let isSwiping = false;
    let startX = 0;
    let startY = 0;
    let currentX = 0;
    let dragStartTime = 0;

    // Menyelaraskan skala kartu agar pas 100% di layar (tanpa terpotong atas, bawah, maupun samping)
    function refreshViewerDimensions() {
        const area = document.getElementById('kpViewerArea');
        if (!area) return;

        const availW = area.clientWidth;
        const availH = area.clientHeight;
        if (availW <= 0 || availH <= 0) return;

        // Dimensi dasar kartu ISO CR-80: 54mm x 85.6mm (204.1px x 323.5px pada 96 DPI)
        const baseW = 204.1;
        const baseH = 323.5;

        // Ruang aman bernapas (margin) agar tidak menempel tombol atas & bawah
        const fitW = Math.max(120, availW - 24);
        const fitH = Math.max(160, availH - 24);

        const scaleW = fitW / baseW;
        const scaleH = fitH / baseH;

        // Math.min memastikan kartu 100% UTUH baik secara tinggi maupun lebar
        let scale = Math.min(scaleW, scaleH);

        // Batasi rentang skala proporsional: min 0.65 (layar mungil), max 1.35 (desktop)
        scale = Math.max(0.65, Math.min(scale, 1.35));

        document.documentElement.style.setProperty('--kp-viewer-scale', scale.toFixed(3));

        // Sinkronisasi posisi track ke sisi aktif saat ini
        slideCardTo(currentSide, false);
    }

    window.addEventListener('resize', refreshViewerDimensions);
    window.addEventListener('orientationchange', function() {
        setTimeout(refreshViewerDimensions, 150);
    });

    // Fungsi Utama: Membuka Kartu Digital Layar Penuh
    window.openKartuPelajarModal = function(nisn) {
        currentKpNisn = nisn;
        closeKpQrZoom();
        const viewer = document.getElementById('kpFullscreenViewer');
        if (!viewer) return;

        viewer.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        const loading = document.getElementById('kpViewerLoading');
        const stage = document.getElementById('kpViewerStage');
        const bottomBar = document.getElementById('kpViewerBottomBar');
        const slideFront = document.getElementById('kpSlideFront');
        const slideBack = document.getElementById('kpSlideBack');
        const printLink = document.getElementById('kpBtnPrintDirect');

        loading.style.display = 'flex';
        stage.style.display = 'none';
        bottomBar.style.display = 'none';
        slideFront.innerHTML = '';
        slideBack.innerHTML = '';

        if (printLink) {
            printLink.href = '{{ url("/dashboard/kartu-pelajar/cetak") }}/' + encodeURIComponent(nisn);
        }

        fetch('{{ url("/kartu-pelajar/preview") }}/' + encodeURIComponent(nisn), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert(data.message || 'Gagal memuat kartu pelajar.');
                closeKartuPelajarModal();
                return;
            }

            // Parsing kartu depan dan kartu belakang dari template HTML
            const parser = new DOMParser();
            const doc = parser.parseFromString(data.html, 'text/html');
            const frontCard = doc.querySelector('.kp-card-front');
            const backCard = doc.querySelector('.kp-card-back');

            if (frontCard) slideFront.appendChild(frontCard);
            if (backCard) slideBack.appendChild(backCard);

            loading.style.display = 'none';
            stage.style.display = 'flex';
            bottomBar.style.display = 'flex';

            currentSide = 'front';
            slideCardTo('front', false);

            // Reflow presisi dimensi & setup gesture
            refreshViewerDimensions();
            requestAnimationFrame(refreshViewerDimensions);
            setTimeout(refreshViewerDimensions, 50);
            setTimeout(refreshViewerDimensions, 180);

            setupSwipeGestures();
        })
        .catch(err => {
            console.error(err);
            alert('Terjadi kesalahan saat memuat kartu pelajar.');
            closeKartuPelajarModal();
        });
    };

    // Fungsi Menutup Layar Penuh
    window.closeKartuPelajarModal = function() {
        closeKpQrZoom();
        const viewer = document.getElementById('kpFullscreenViewer');
        if (!viewer) return;
        viewer.style.display = 'none';
        document.body.style.overflow = '';
        closeDownloadMenu();
    };

    // Klik backdrop (di luar kartu dan tombol) untuk menutup
    window.handleKpBackdropClick = function(e) {
        if (e.target.id === 'kpFullscreenViewer' || e.target.id === 'kpViewerArea') {
            closeKartuPelajarModal();
        }
    };

    // Fungsi Menggeser Kartu: Pure Percentage Translation
    window.slideCardTo = function(side, animated = true) {
        currentSide = side;
        const track = document.getElementById('kpSwipeTrack');
        const pillFront = document.getElementById('kpPillFront');
        const pillBack = document.getElementById('kpPillBack');
        const arrowLeft = document.getElementById('kpArrowLeft');
        const arrowRight = document.getElementById('kpArrowRight');

        if (track) {
            track.style.transition = animated ? 'transform 0.38s cubic-bezier(0.16, 1, 0.3, 1)' : 'none';
            // 0% = Sisi Depan 100% penuh di layar, Sisi Belakang di luar layar kanan
            // -50% = Sisi Belakang 100% penuh di layar, Sisi Depan di luar layar kiri
            track.style.transform = side === 'front' ? 'translateX(0%)' : 'translateX(-50%)';
        }

        if (pillFront && pillBack) {
            if (side === 'front') {
                pillFront.classList.add('active');
                pillBack.classList.remove('active');
                if (arrowLeft) arrowLeft.style.opacity = '0.35';
                if (arrowRight) arrowRight.style.opacity = '1';
            } else {
                pillFront.classList.remove('active');
                pillBack.classList.add('active');
                if (arrowLeft) arrowLeft.style.opacity = '1';
                if (arrowRight) arrowRight.style.opacity = '0.35';
            }
        }
    };

    // Setup Gestur Geser (Swipe Touch & Mouse Drag)
    function setupSwipeGestures() {
        const wrapper = document.getElementById('kpSwipeWrapper');
        const track = document.getElementById('kpSwipeTrack');
        if (!wrapper || !track || wrapper.dataset.gestureBound) return;

        wrapper.dataset.gestureBound = 'true';

        // 1. Touch Events (Mobile Touchscreen)
        wrapper.addEventListener('touchstart', function(e) {
            if (e.touches.length !== 1) return;
            isSwiping = true;
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            currentX = startX;
            dragStartTime = Date.now();
            track.style.transition = 'none';
        }, { passive: true });

        wrapper.addEventListener('touchmove', function(e) {
            if (!isSwiping) return;
            currentX = e.touches[0].clientX;
            const deltaX = currentX - startX;
            const deltaY = e.touches[0].clientY - startY;

            if (Math.abs(deltaX) > Math.abs(deltaY)) {
                const wrapW = wrapper.clientWidth || 360;
                // Track adalah 200% dari wrapper, jadi deltaX di track = (deltaX / (wrapW * 2)) * 100
                const dragPercent = (deltaX / (wrapW * 2)) * 100;
                const basePercent = currentSide === 'front' ? 0 : -50;
                let targetOffset = basePercent + dragPercent;

                // Hambatan elastis di tepi
                if (currentSide === 'front' && targetOffset > 0) {
                    targetOffset = dragPercent * 0.25;
                } else if (currentSide === 'back' && targetOffset < -50) {
                    targetOffset = -50 + (targetOffset - (-50)) * 0.25;
                }

                track.style.transform = `translateX(${targetOffset}%)`;
            }
        }, { passive: true });

        wrapper.addEventListener('touchend', function(e) {
            if (!isSwiping) return;
            isSwiping = false;
            const deltaX = currentX - startX;
            const duration = Date.now() - dragStartTime;

            // Ketukan singkat (Tap) tanpa geser = balik kartu
            if (Math.abs(deltaX) < 10 && duration < 260) {
                slideCardTo(currentSide === 'front' ? 'back' : 'front');
                return;
            }

            // Ambang batas geser 30px
            if (deltaX < -30) {
                slideCardTo('back');
            } else if (deltaX > 30) {
                slideCardTo('front');
            } else {
                slideCardTo(currentSide);
            }
        });

        // 2. Mouse Drag Events (Desktop)
        let isMouseDown = false;
        wrapper.addEventListener('mousedown', function(e) {
            if (e.button !== 0) return;
            isMouseDown = true;
            startX = e.clientX;
            currentX = startX;
            dragStartTime = Date.now();
            track.style.transition = 'none';
        });

        window.addEventListener('mousemove', function(e) {
            if (!isMouseDown) return;
            currentX = e.clientX;
            const deltaX = currentX - startX;
            const wrapW = wrapper.clientWidth || 360;
            const dragPercent = (deltaX / (wrapW * 2)) * 100;
            const basePercent = currentSide === 'front' ? 0 : -50;
            let targetOffset = basePercent + dragPercent;

            if (currentSide === 'front' && targetOffset > 0) {
                targetOffset = dragPercent * 0.25;
            } else if (currentSide === 'back' && targetOffset < -50) {
                targetOffset = -50 + (targetOffset - (-50)) * 0.25;
            }

            track.style.transform = `translateX(${targetOffset}%)`;
        });

        window.addEventListener('mouseup', function(e) {
            if (!isMouseDown) return;
            isMouseDown = false;
            const deltaX = currentX - startX;
            const duration = Date.now() - dragStartTime;

            // Klik singkat tanpa geser = balik kartu
            if (Math.abs(deltaX) < 8 && duration < 260) {
                slideCardTo(currentSide === 'front' ? 'back' : 'front');
                return;
            }

            if (deltaX < -30) {
                slideCardTo('back');
            } else if (deltaX > 30) {
                slideCardTo('front');
            } else {
                slideCardTo(currentSide);
            }
        });
    }

    // Toggle Dropdown Menu Unduh
    window.toggleKpDownloadMenu = function(e) {
        e.stopPropagation();
        const menu = document.getElementById('kpDownloadMenu');
        if (menu) {
            menu.classList.toggle('show');
        }
    };

    function closeDownloadMenu() {
        const menu = document.getElementById('kpDownloadMenu');
        if (menu) menu.classList.remove('show');
    }

    document.addEventListener('click', closeDownloadMenu);

    // Toast Notifikasi
    function showToast(msg, isError = false) {
        const toast = document.getElementById('kpViewerToast');
        const text = document.getElementById('kpToastText');
        if (!toast || !text) return;

        text.textContent = msg;
        toast.style.borderColor = isError ? 'rgba(239, 68, 68, 0.6)' : 'rgba(56, 189, 248, 0.5)';
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    // Eksekusi Unduh Kartu (High-Res PNG via html2canvas)
    window.executeDownload = async function(mode) {
        closeDownloadMenu();
        const btn = document.getElementById('kpBtnDownload');
        const originalHtml = btn.innerHTML;

        // Cek ketersediaan html2canvas
        if (typeof html2canvas === 'undefined') {
            showToast('Memuat pustaka gambar...', false);
            try {
                await new Promise((resolve, reject) => {
                    const s = document.createElement('script');
                    s.src = '{{ asset("js/html2canvas.min.js") }}';
                    s.onload = resolve;
                    s.onerror = reject;
                    document.head.appendChild(s);
                });
            } catch (err) {
                window.open('{{ url("/dashboard/kartu-pelajar/cetak") }}/' + encodeURIComponent(currentKpNisn), '_blank');
                return;
            }
        }

        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Menyiapkan...</span>';
        btn.disabled = true;

        const captureAndDownload = async (cardEl, sideName) => {
            if (!cardEl) return false;
            try {
                const canvas = await html2canvas(cardEl, {
                    scale: 3, // Kualitas cetak retina 300dpi
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#ffffff',
                    logging: false,
                    onclone: function(clonedDoc) {
                        const clonedCard = clonedDoc.querySelector('#' + cardEl.id) || clonedDoc.querySelector('.kp-card');
                        if (clonedCard) {
                            clonedCard.style.transform = 'none';
                            clonedCard.style.boxShadow = 'none';
                            clonedCard.style.margin = '0';
                        }
                    }
                });

                const link = document.createElement('a');
                link.download = `Kartu_Pelajar_${currentKpNisn}_${sideName}.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();
                return true;
            } catch (e) {
                console.error('Error rendering card:', e);
                return false;
            }
        };

        const frontEl = document.querySelector('#kpSlideFront .kp-card');
        const backEl = document.querySelector('#kpSlideBack .kp-card');

        try {
            if (mode === 'active') {
                const target = currentSide === 'front' ? frontEl : backEl;
                const sideName = currentSide === 'front' ? 'DEPAN' : 'BELAKANG';
                const success = await captureAndDownload(target, sideName);
                if (success) showToast(`Kartu Sisi ${sideName} berhasil diunduh!`);
            } else if (mode === 'front') {
                const success = await captureAndDownload(frontEl, 'DEPAN');
                if (success) showToast('Kartu Sisi Depan berhasil diunduh!');
            } else if (mode === 'back') {
                const success = await captureAndDownload(backEl, 'BELAKANG');
                if (success) showToast('Kartu Sisi Belakang berhasil diunduh!');
            } else if (mode === 'both') {
                await captureAndDownload(frontEl, 'DEPAN');
                await new Promise(r => setTimeout(r, 450));
                await captureAndDownload(backEl, 'BELAKANG');
                showToast('Kedua sisi kartu berhasil diunduh!');
            }
        } catch (err) {
            console.error(err);
            showToast('Gagal memproses unduhan kartu.', true);
        } finally {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    };

    // Fungsi Membuka Zoom QR Code Fullscreen (Untuk Transaksi & Presensi Cepat)
    window.zoomKpQrCode = function(e, el) {
        if (e) {
            e.stopPropagation();
            if (e.preventDefault) e.preventDefault();
        }

        const overlay = document.getElementById('kpQrZoomOverlay');
        const box = document.getElementById('kpQrZoomBox');
        const nameEl = document.getElementById('kpQrZoomName');
        const nisnEl = document.getElementById('kpQrZoomNisn');
        const rombelEl = document.getElementById('kpQrZoomRombel');

        if (!overlay || !box) return;

        // Ambil SVG QR Code dari elemen yang di-klik atau dari kartu aktif
        const targetBox = el || document.querySelector('.kp-qrcode-box');
        const qrSvg = targetBox ? targetBox.querySelector('svg') : null;
        if (qrSvg) {
            box.innerHTML = qrSvg.outerHTML;
        }

        // Ambil biodata siswa dari dataset atau DOM kartu
        const name = (targetBox && targetBox.dataset.studentName) ? targetBox.dataset.studentName : (document.querySelector('.kp-student-name')?.textContent?.trim() || '-');
        const nisn = (targetBox && targetBox.dataset.studentNisn) ? targetBox.dataset.studentNisn : (document.querySelector('.kp-nisn-value')?.textContent?.trim() || currentKpNisn);
        const rombel = (targetBox && targetBox.dataset.studentRombel) ? targetBox.dataset.studentRombel : (document.querySelector('.kp-rombel-name')?.textContent?.trim() || '-');

        if (nameEl) nameEl.textContent = name;
        if (nisnEl) nisnEl.innerHTML = '<i class="fas fa-id-badge"></i> NISN: ' + escapeKpText(nisn);
        if (rombelEl) rombelEl.innerHTML = '<i class="fas fa-users-rectangle"></i> ' + escapeKpText(rombel);

        overlay.classList.add('show');
        overlay.style.setProperty('display', 'flex', 'important');
    };

    window.closeKpQrZoom = function() {
        const overlay = document.getElementById('kpQrZoomOverlay');
        if (overlay) {
            overlay.classList.remove('show');
            overlay.style.setProperty('display', 'none', 'important');
        }
    };

    function escapeKpText(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

    // Navigasi Keyboard (Panah Kiri/Kanan & Escape & Spasi)
    document.addEventListener('keydown', function(e) {
        // Jika QR Zoom sedang terbuka, tombol Escape menutup QR Zoom lebih dahulu
        const qrOverlay = document.getElementById('kpQrZoomOverlay');
        if (qrOverlay && qrOverlay.style.display !== 'none') {
            if (e.key === 'Escape') {
                closeKpQrZoom();
                return;
            }
        }

        const viewer = document.getElementById('kpFullscreenViewer');
        if (!viewer || viewer.style.display === 'none') return;

        if (e.key === 'Escape') {
            closeKartuPelajarModal();
        } else if (e.key === 'ArrowLeft') {
            slideCardTo('front');
        } else if (e.key === 'ArrowRight') {
            slideCardTo('back');
        } else if (e.key === ' ' || e.code === 'Space') {
            slideCardTo(currentSide === 'front' ? 'back' : 'front');
        }
    });
})();
</script>
