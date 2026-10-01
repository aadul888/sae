{{-- Modal Popup Interaktif Pratinjau Kartu Pelajar Digital (Fixed Centered Overlay) --}}
<div id="kartuPelajarModal"
    style="display: none; position: fixed; inset: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); z-index: 999999 !important; align-items: center; justify-content: center; padding: 16px; box-sizing: border-box;"
    onclick="handleKpModalBackdropClick(event)">

    <div style="background: #ffffff; border-radius: 16px; box-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.5); width: 100%; max-width: 660px; max-height: 92vh; display: flex; flex-direction: column; overflow: hidden; position: relative; z-index: 1000000;"
        onclick="event.stopPropagation()">

        <!-- Header Modal -->
        <div
            style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff; padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.08);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div
                    style="width: 34px; height: 34px; border-radius: 8px; background: rgba(56, 189, 248, 0.15); display: flex; align-items: center; justify-content: center; color: #38bdf8; font-size: 1.1rem;">
                    <i class="fas fa-id-card"></i>
                </div>
                <div>
                    <h5 style="font-size: 0.95rem; font-weight: 800; margin: 0; color: #ffffff;" id="kpModalTitle">
                        Kartu Pelajar Digital SAE
                    </h5>
                    <div style="font-size: 0.72rem; color: #94a3b8;" id="kpModalSubtitle">
                        Format Portrait Standar ISO CR-80 (54mm &times; 85.6mm)
                    </div>
                </div>
            </div>
            <button type="button" onclick="closeKartuPelajarModal()"
                style="background: rgba(255,255,255,0.08); border: none; color: #cbd5e1; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; cursor: pointer; transition: all 0.15s;"
                title="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body Modal -->
        <div style="background: #f8fafc; padding: 20px; overflow-y: auto; flex: 1; min-height: 240px;">
            <!-- Loading State (Tepat di Tengah) -->
            <div id="kpModalLoading"
                style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 260px; padding: 32px; color: #475569; gap: 14px;">
                <i class="fas fa-circle-notch fa-spin"
                    style="font-size: 40px; color: #0284c7; filter: drop-shadow(0 0 10px rgba(2,132,199,0.3));"></i>
                <div style="font-size: 0.92rem; font-weight: 700; letter-spacing: 0.2px;">Memuat Kartu Pelajar
                    Digital...</div>
            </div>

            <!-- Content State -->
            <div id="kpModalContent" style="display: none;">
                <!-- Tab Pilihan Tampilan Sisi -->
                <div style="display: flex; justify-content: center; margin-bottom: 16px;">
                    <div style="display: inline-flex; background: #e2e8f0; padding: 3px; border-radius: 8px; gap: 2px;">
                        <button type="button" id="kpBtnTabBoth" onclick="setKpModalTab('both')"
                            class="btn-kp-tab active">
                            Depan &amp; Belakang
                        </button>
                        <button type="button" id="kpBtnTabFront" onclick="setKpModalTab('front')" class="btn-kp-tab">
                            Depan Saja
                        </button>
                        <button type="button" id="kpBtnTabBack" onclick="setKpModalTab('back')" class="btn-kp-tab">
                            Belakang Saja
                        </button>
                    </div>
                </div>

                <!-- Container Kartu -->
                <div id="kpCardContainer"
                    style="display: flex; justify-content: center; align-items: center; margin: 10px 0;">
                    <!-- Injected via AJAX -->
                </div>

                <!-- Direct Verification Link Notice -->
                <div
                    style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 10px 14px; margin-top: 18px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                    <div style="display: flex; align-items: center; gap: 8px; font-size: 0.76rem; color: #1e40af;">
                        <i class="fas fa-shield-check" style="font-size: 0.95rem; color: #2563eb;"></i>
                        <span>QR Code siap scan direct ke halaman verifikasi resmi online.</span>
                    </div>
                    <a href="#" id="kpBtnVerifyLink" target="_blank"
                        style="font-size: 0.76rem; font-weight: 700; color: #0284c7; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                        <span>Coba Direct Scan Link</span> <i class="fas fa-external-link-alt"
                            style="font-size: 0.68rem;"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Footer Modal -->
        <div
            style="background: #ffffff; border-top: 1px solid #e2e8f0; padding: 12px 20px; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
            <button type="button" class="btn btn-outline" onclick="closeKartuPelajarModal()"
                style="font-size: 0.82rem; padding: 7px 16px;">
                Tutup
            </button>
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <div style="position: relative; display: inline-block;" id="kpDropdownUnduhJpgWrap">
                    <button type="button" id="kpBtnUnduhJpg" onclick="toggleKpUnduhDropdown(event)" class="btn"
                        style="font-size: 0.82rem; padding: 7px 14px; display: inline-flex; align-items: center; gap: 6px; background: #059669; border-color: #059669; color: #ffffff; font-weight: 700; border-radius: 8px; cursor: pointer;">
                        <i class="fas fa-file-image"></i> <span>Unduh JPG</span> <i class="fas fa-chevron-down"
                            style="font-size: 0.65rem; margin-left: 2px;"></i>
                    </button>
                    <div id="kpUnduhJpgMenu"
                        style="display: none; position: absolute; bottom: calc(100% + 6px); right: 0; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2); min-width: 190px; padding: 6px 0; z-index: 1000005;"
                        onclick="event.stopPropagation()">
                        <button type="button" onclick="unduhKartuJpg('both')"
                            style="width: 100%; text-align: left; padding: 8px 14px; background: transparent; border: none; font-size: 0.8rem; font-weight: 600; color: #1e293b; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-images" style="color: #059669; width: 16px;"></i> Depan &amp; Belakang (2
                            JPG)
                        </button>
                        <button type="button" onclick="unduhKartuJpg('front')"
                            style="width: 100%; text-align: left; padding: 8px 14px; background: transparent; border: none; font-size: 0.8rem; font-weight: 600; color: #1e293b; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-image" style="color: #0284c7; width: 16px;"></i> Sisi Depan Saja (.jpg)
                        </button>
                        <button type="button" onclick="unduhKartuJpg('back')"
                            style="width: 100%; text-align: left; padding: 8px 14px; background: transparent; border: none; font-size: 0.8rem; font-weight: 600; color: #1e293b; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-image" style="color: #64748b; width: 16px;"></i> Sisi Belakang Saja (.jpg)
                        </button>
                    </div>
                </div>
                <a href="#" id="kpBtnPrintLink" target="_blank" class="btn btn-primary"
                    style="font-size: 0.82rem; padding: 7px 18px; display: inline-flex; align-items: center; gap: 6px; background: #0284c7; border-color: #0284c7;">
                    <i class="fas fa-print"></i> Cetak Kartu Pelajar
                </a>
            </div>
        </div>
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

<script>
    let currentKpNisn = '';

    function openKartuPelajarModal(nisn) {
        currentKpNisn = nisn;
        closeKpQrZoom();
        const modal = document.getElementById('kartuPelajarModal');
        if (!modal) return;

        // Tampilkan modal sebagai fixed overlay popup
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        const loading = document.getElementById('kpModalLoading');
        const content = document.getElementById('kpModalContent');
        const container = document.getElementById('kpCardContainer');
        const title = document.getElementById('kpModalTitle');
        const printLink = document.getElementById('kpBtnPrintLink');
        const verifyLink = document.getElementById('kpBtnVerifyLink');

        loading.style.display = 'flex';
        content.style.display = 'none';

        fetch('{{ url('/kartu-pelajar/preview') }}/' + encodeURIComponent(nisn), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message || 'Gagal memuat kartu pelajar.');
                    closeKartuPelajarModal();
                    return;
                }

                title.innerHTML = 'Kartu Pelajar: ' + escapeKpHtml(data.card.nama) + ' (' + escapeKpHtml(data.card
                    .nisn) + ')';
                container.innerHTML = data.html;
                printLink.href = '{{ url('/dashboard/kartu-pelajar/cetak') }}/' + encodeURIComponent(data.card
                    .nisn);
                verifyLink.href = data.card.verify_url;

                loading.style.display = 'none';
                content.style.display = 'block';
                setKpModalTab('both');
            })
            .catch(err => {
                console.error(err);
                alert('Terjadi kesalahan saat memuat data kartu pelajar.');
                closeKartuPelajarModal();
            });
    }

    function toggleKpUnduhDropdown(e) {
        if (e) e.stopPropagation();
        const menu = document.getElementById('kpUnduhJpgMenu');
        if (!menu) return;
        menu.style.display = (menu.style.display === 'none' || menu.style.display === '') ? 'block' : 'none';
    }

    function closeKpUnduhDropdown() {
        const menu = document.getElementById('kpUnduhJpgMenu');
        if (menu) menu.style.display = 'none';
    }

    document.addEventListener('click', function(e) {
        const wrap = document.getElementById('kpDropdownUnduhJpgWrap');
        if (wrap && !wrap.contains(e.target)) {
            closeKpUnduhDropdown();
        }
    });

    async function loadHtml2CanvasLib() {
        if (typeof html2canvas !== 'undefined') return true;
        return new Promise((resolve, reject) => {
            const s = document.createElement('script');
            s.src = '{{ asset('js/html2canvas.min.js') }}';
            s.onload = () => resolve(true);
            s.onerror = () => reject(new Error('Gagal memuat pustaka html2canvas'));
            document.head.appendChild(s);
        });
    }

    async function unduhKartuJpg(mode) {
        closeKpUnduhDropdown();
        const btn = document.getElementById('kpBtnUnduhJpg');
        const originalHtml = btn ? btn.innerHTML : '';

        if (btn) {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Menyiapkan...</span>';
            btn.disabled = true;
        }

        try {
            await loadHtml2CanvasLib();
        } catch (e) {
            alert('Gagal memuat script html2canvas untuk unduh JPG.');
            if (btn) {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
            return;
        }

        const captureCardToJpg = async (cardEl, sideName) => {
            if (!cardEl) return false;
            let staging = null;
            try {
                staging = document.createElement('div');
                staging.style.position = 'fixed';
                staging.style.left = '-9999px';
                staging.style.top = '0';
                staging.style.width = '204px'; // 54mm @ 96dpi
                staging.style.height = '324px'; // 85.6mm @ 96dpi
                staging.style.overflow = 'hidden';
                staging.style.zIndex = '-9999';
                staging.style.background = '#ffffff';

                const clone = cardEl.cloneNode(true);
                clone.classList.add('kp-card-capture-target');
                clone.style.display = 'flex';
                clone.style.transform = 'none';
                clone.style.margin = '0';
                clone.style.boxShadow = 'none';
                staging.appendChild(clone);
                document.body.appendChild(staging);

                await new Promise(r => setTimeout(r, 120));

                const canvas = await html2canvas(clone, {
                    scale: 3, // 300 DPI high resolution
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#ffffff',
                    logging: false,
                    width: clone.offsetWidth,
                    height: clone.offsetHeight
                });

                const link = document.createElement('a');
                link.download = `Kartu_Pelajar_${currentKpNisn || 'Siswa'}_${sideName}.jpg`;
                link.href = canvas.toDataURL('image/jpeg', 0.95);
                link.click();
                return true;
            } catch (err) {
                console.error('Error saat capture JPG:', err);
                return false;
            } finally {
                if (staging && staging.parentNode) {
                    staging.parentNode.removeChild(staging);
                }
            }
        };

        const frontEl = document.querySelector('#kpCardContainer .kp-card-front');
        const backEl = document.querySelector('#kpCardContainer .kp-card-back');

        try {
            if (mode === 'front') {
                await captureCardToJpg(frontEl, 'DEPAN');
            } else if (mode === 'back') {
                await captureCardToJpg(backEl, 'BELAKANG');
            } else if (mode === 'both') {
                await captureCardToJpg(frontEl, 'DEPAN');
                await new Promise(r => setTimeout(r, 400));
                await captureCardToJpg(backEl, 'BELAKANG');
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Unduh Berhasil!',
                    text: 'Kartu pelajar format JPG berhasil diunduh.',
                    icon: 'success',
                    confirmButtonColor: '#10b981',
                    confirmButtonText: 'Selesai'
                });
            } else if (window.SAE && typeof window.SAE.toast === 'function') {
                window.SAE.toast('Kartu pelajar format JPG berhasil diunduh!', 'success');
            }
        } catch (err) {
            console.error(err);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Gagal Mengunduh',
                    text: 'Gagal mengunduh kartu dalam format JPG: ' + (err.message || 'Error'),
                    icon: 'error',
                    confirmButtonColor: '#ef4444'
                });
            } else {
                alert('Gagal mengunduh kartu dalam format JPG: ' + (err.message || 'Error'));
            }
        } finally {
            if (btn) {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        }
    }

    function closeKartuPelajarModal() {
        closeKpUnduhDropdown();
        closeKpQrZoom();
        const modal = document.getElementById('kartuPelajarModal');
        if (!modal) return;
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    function handleKpModalBackdropClick(e) {
        if (e.target.id === 'kartuPelajarModal') {
            closeKartuPelajarModal();
        }
    }

    function setKpModalTab(tab) {
        const btnBoth = document.getElementById('kpBtnTabBoth');
        const btnFront = document.getElementById('kpBtnTabFront');
        const btnBack = document.getElementById('kpBtnTabBack');

        [btnBoth, btnFront, btnBack].forEach(b => {
            if (b) b.classList.remove('active');
        });

        const frontCard = document.querySelector('#kpCardContainer .kp-card-front');
        const backCard = document.querySelector('#kpCardContainer .kp-card-back');

        if (!frontCard || !backCard) return;

        if (tab === 'front') {
            if (btnFront) btnFront.classList.add('active');
            frontCard.style.display = 'flex';
            backCard.style.display = 'none';
        } else if (tab === 'back') {
            if (btnBack) btnBack.classList.add('active');
            frontCard.style.display = 'none';
            backCard.style.display = 'flex';
        } else {
            if (btnBoth) btnBoth.classList.add('active');
            frontCard.style.display = 'flex';
            backCard.style.display = 'flex';
        }
    }

    function escapeKpHtml(text) {
        if (!text) return '';
        return text.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g,
            "&quot;").replace(/'/g, "&#039;");
    }

    // Fungsi Membuka Zoom QR Code Fullscreen
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

        const targetBox = el || document.querySelector('.kp-qrcode-box');
        const qrSvg = targetBox ? targetBox.querySelector('svg') : null;
        if (qrSvg) {
            box.innerHTML = qrSvg.outerHTML;
        }

        const name = (targetBox && targetBox.dataset.studentName) ? targetBox.dataset.studentName : (document
            .querySelector('.kp-student-name')?.textContent?.trim() || '-');
        const nisn = (targetBox && targetBox.dataset.studentNisn) ? targetBox.dataset.studentNisn : (document
            .querySelector('.kp-nisn-value')?.textContent?.trim() || currentKpNisn);
        const rombel = (targetBox && targetBox.dataset.studentRombel) ? targetBox.dataset.studentRombel : (document
            .querySelector('.kp-rombel-name')?.textContent?.trim() || '-');

        if (nameEl) nameEl.textContent = name;
        if (nisnEl) nisnEl.innerHTML = '<i class="fas fa-id-badge"></i> NISN: ' + escapeKpHtml(nisn);
        if (rombelEl) {
            if (rombel && rombel !== '-') {
                rombelEl.innerHTML = '<i class="fas fa-users-rectangle"></i> ' + escapeKpHtml(rombel);
                rombelEl.style.display = 'inline-flex';
            } else {
                rombelEl.style.display = 'none';
            }
        }

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

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const qrOverlay = document.getElementById('kpQrZoomOverlay');
            if (qrOverlay && qrOverlay.style.display !== 'none') {
                closeKpQrZoom();
                return;
            }
            closeKartuPelajarModal();
            if (typeof closeCetakRombelModal === 'function') {
                closeCetakRombelModal();
            }
        }
    });
</script>
