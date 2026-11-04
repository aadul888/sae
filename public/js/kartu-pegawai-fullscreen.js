/**
 * SAE - Kartu Pegawai Fullscreen Card Viewer Script
 * ISO CR-80 card swipe/drag gestures, QR zoom, and PNG download.
 */

(function() {
    let currentPegawaiId = '';
    let currentSide = 'front';
    let isSwiping = false;
    let startX = 0;
    let startY = 0;
    let currentX = 0;
    let dragStartTime = 0;

    function refreshPegawaiViewerDimensions() {
        const area = document.getElementById('kpPegawaiViewerArea');
        if (!area) return;

        const availW = area.clientWidth;
        const availH = area.clientHeight;
        if (availW <= 0 || availH <= 0) return;

        const baseW = 204.1;
        const baseH = 323.5;

        const fitW = Math.max(120, availW - 24);
        const fitH = Math.max(160, availH - 24);

        const scaleW = fitW / baseW;
        const scaleH = fitH / baseH;

        let scale = Math.min(scaleW, scaleH);
        scale = Math.max(0.65, Math.min(scale, 1.35));

        document.documentElement.style.setProperty('--kp-viewer-scale', scale.toFixed(3));
        slidePegawaiCardTo(currentSide, false);
    }

    window.addEventListener('resize', refreshPegawaiViewerDimensions);
    window.addEventListener('orientationchange', function() {
        setTimeout(refreshPegawaiViewerDimensions, 150);
    });

    window.openKartuPegawaiModal = function(id) {
        currentPegawaiId = id;
        if (typeof window.closeKpPegawaiQrZoom === 'function') {
            window.closeKpPegawaiQrZoom();
        }
        const viewer = document.getElementById('kpPegawaiFullscreenViewer');
        if (!viewer) return;

        viewer.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        const loading = document.getElementById('kpPegawaiViewerLoading');
        const stage = document.getElementById('kpPegawaiViewerStage');
        const bottomBar = document.getElementById('kpPegawaiViewerBottomBar');
        const slideFront = document.getElementById('kpPegawaiSlideFront');
        const slideBack = document.getElementById('kpPegawaiSlideBack');
        const printLink = document.getElementById('kpPegawaiBtnPrintDirect');

        if (loading) {
            loading.classList.remove('hidden');
            loading.style.setProperty('display', 'flex', 'important');
        }
        if (stage) stage.style.display = 'none';
        if (bottomBar) bottomBar.style.display = 'none';
        if (slideFront) slideFront.innerHTML = '';
        if (slideBack) slideBack.innerHTML = '';

        if (printLink) {
            printLink.href = '/dashboard/kartu-pegawai/cetak/' + encodeURIComponent(id);
        }

        fetch('/kartu-pegawai/preview/' + encodeURIComponent(id), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                if (window.Swal) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Memuat Kartu',
                        text: data.message || 'Gagal memuat kartu pegawai.',
                        confirmButtonColor: '#ef4444'
                    });
                } else if (window.SAE && typeof window.SAE.alert === 'function') {
                    window.SAE.alert(data.message || 'Gagal memuat kartu pegawai.', 'Informasi', 'info');
                } else {
                    alert(data.message || 'Gagal memuat kartu pegawai.');
                }
                window.closeKartuPegawaiModal();
                return;
            }

            const parser = new DOMParser();
            const doc = parser.parseFromString(data.html, 'text/html');

            const frontCard = doc.querySelector('.kp-card-front');
            const backCard = doc.querySelector('.kp-card-back');

            if (frontCard && slideFront) slideFront.appendChild(frontCard);
            if (backCard && slideBack) slideBack.appendChild(backCard);

            if (loading) {
                loading.classList.add('hidden');
                loading.style.setProperty('display', 'none', 'important');
            }
            if (stage) stage.style.display = 'flex';
            if (bottomBar) bottomBar.style.display = 'flex';

            currentSide = 'front';
            slidePegawaiCardTo('front', false);

            refreshPegawaiViewerDimensions();
            requestAnimationFrame(refreshPegawaiViewerDimensions);
            setTimeout(refreshPegawaiViewerDimensions, 50);
            setTimeout(refreshPegawaiViewerDimensions, 180);

            setupPegawaiSwipeGestures();
        })
        .catch(err => {
            console.error(err);
            if (loading) {
                loading.classList.add('hidden');
                loading.style.setProperty('display', 'none', 'important');
            }
            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'Kesalahan Sistem',
                    text: 'Terjadi kesalahan saat memuat kartu pegawai.',
                    confirmButtonColor: '#ef4444'
                });
            } else {
                alert('Terjadi kesalahan saat memuat kartu pegawai.');
            }
            window.closeKartuPegawaiModal();
        });
    };

    window.closeKartuPegawaiModal = function() {
        if (typeof window.closeKpPegawaiQrZoom === 'function') {
            window.closeKpPegawaiQrZoom();
        }
        const viewer = document.getElementById('kpPegawaiFullscreenViewer');
        if (!viewer) return;
        viewer.style.display = 'none';
        const loading = document.getElementById('kpPegawaiViewerLoading');
        if (loading) {
            loading.classList.add('hidden');
            loading.style.setProperty('display', 'none', 'important');
        }
        document.body.style.overflow = '';
        closePegawaiDownloadMenu();
    };

    window.handleKpPegawaiBackdropClick = function(e) {
        if (e.target.id === 'kpPegawaiFullscreenViewer' || e.target.id === 'kpPegawaiViewerArea') {
            window.closeKartuPegawaiModal();
        }
    };

    window.slidePegawaiCardTo = function(side, animated = true) {
        currentSide = side;
        const track = document.getElementById('kpPegawaiSwipeTrack');
        const pillFront = document.getElementById('kpPegawaiPillFront');
        const pillBack = document.getElementById('kpPegawaiPillBack');
        const arrowLeft = document.getElementById('kpPegawaiArrowLeft');
        const arrowRight = document.getElementById('kpPegawaiArrowRight');

        if (track) {
            track.style.transition = animated ? 'transform 0.38s cubic-bezier(0.16, 1, 0.3, 1)' : 'none';
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

    function setupPegawaiSwipeGestures() {
        const wrapper = document.getElementById('kpPegawaiSwipeWrapper');
        const track = document.getElementById('kpPegawaiSwipeTrack');
        if (!wrapper || !track || wrapper.dataset.gestureBound) return;

        wrapper.dataset.gestureBound = 'true';

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
                const dragPercent = (deltaX / (wrapW * 2)) * 100;
                const basePercent = currentSide === 'front' ? 0 : -50;
                let targetOffset = basePercent + dragPercent;

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

            if (Math.abs(deltaX) < 10 && duration < 260) {
                const qrBox = e.target ? e.target.closest('.kp-qrcode-box') : null;
                if (qrBox) {
                    window.zoomKpPegawaiQrCode(e, qrBox);
                    return;
                }
                slidePegawaiCardTo(currentSide === 'front' ? 'back' : 'front');
                return;
            }

            if (deltaX < -30) {
                slidePegawaiCardTo('back');
            } else if (deltaX > 30) {
                slidePegawaiCardTo('front');
            } else {
                slidePegawaiCardTo(currentSide);
            }
        });

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

            if (Math.abs(deltaX) < 8 && duration < 260) {
                const qrBox = e.target ? e.target.closest('.kp-qrcode-box') : null;
                if (qrBox) {
                    window.zoomKpPegawaiQrCode(e, qrBox);
                    return;
                }
                slidePegawaiCardTo(currentSide === 'front' ? 'back' : 'front');
                return;
            }

            if (deltaX < -35) {
                slidePegawaiCardTo('back');
            } else if (deltaX > 35) {
                slidePegawaiCardTo('front');
            } else {
                slidePegawaiCardTo(currentSide);
            }
        });
    }

    window.toggleKpPegawaiDownloadMenu = function(e) {
        if (e) e.stopPropagation();
        const menu = document.getElementById('kpPegawaiDownloadMenu');
        if (!menu) return;
        menu.classList.toggle('active');
    };

    function closePegawaiDownloadMenu() {
        const menu = document.getElementById('kpPegawaiDownloadMenu');
        if (menu) menu.classList.remove('active');
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('#kpPegawaiBtnDownload') && !e.target.closest('#kpPegawaiDownloadMenu')) {
            closePegawaiDownloadMenu();
        }
    });

    window.executePegawaiDownload = async function(mode) {
        closePegawaiDownloadMenu();
        const btn = document.getElementById('kpPegawaiBtnDownload');
        const origContent = btn ? btn.innerHTML : '';

        if (typeof html2canvas === 'undefined') {
            try {
                await new Promise((resolve, reject) => {
                    const s = document.createElement('script');
                    s.src = '/js/html2canvas.min.js';
                    s.onload = resolve;
                    s.onerror = reject;
                    document.head.appendChild(s);
                });
            } catch (err) {
                window.open('/dashboard/kartu-pegawai/cetak/' + encodeURIComponent(currentPegawaiId), '_blank');
                return;
            }
        }

        if (btn) {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Menyiapkan...</span>';
            btn.disabled = true;
        }

        const captureAndDownload = async (cardEl, sideName) => {
            if (!cardEl) return false;
            let staging = null;
            try {
                staging = document.createElement('div');
                staging.style.position = 'fixed';
                staging.style.left = '-9999px';
                staging.style.top = '0';
                staging.style.width = '204px';
                staging.style.height = '324px';
                staging.style.overflow = 'hidden';
                staging.style.zIndex = '-9999';
                staging.style.background = '#ffffff';

                const clone = cardEl.cloneNode(true);
                clone.classList.add('kp-card-capture-target');
                clone.style.transform = 'none';
                clone.style.margin = '0';
                clone.style.boxShadow = 'none';
                staging.appendChild(clone);
                document.body.appendChild(staging);

                await new Promise(r => setTimeout(r, 120));

                const canvas = await html2canvas(clone, {
                    scale: 3,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#ffffff',
                    logging: false,
                    width: clone.offsetWidth,
                    height: clone.offsetHeight
                });

                const link = document.createElement('a');
                link.download = `Kartu_Pegawai_${currentPegawaiId}_${sideName}.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();
                return true;
            } catch (e) {
                console.error('Error rendering card:', e);
                return false;
            } finally {
                if (staging && staging.parentNode) {
                    staging.parentNode.removeChild(staging);
                }
            }
        };

        const frontEl = document.querySelector('#kpPegawaiSlideFront .kp-card');
        const backEl = document.querySelector('#kpPegawaiSlideBack .kp-card');

        try {
            if (mode === 'active') {
                const target = currentSide === 'front' ? frontEl : backEl;
                await captureAndDownload(target, currentSide);
                showPegawaiToast('Sisi kartu berhasil diunduh!');
            } else if (mode === 'front') {
                await captureAndDownload(frontEl, 'depan');
                showPegawaiToast('Sisi depan berhasil diunduh!');
            } else if (mode === 'back') {
                await captureAndDownload(backEl, 'belakang');
                showPegawaiToast('Sisi belakang berhasil diunduh!');
            } else if (mode === 'both') {
                await captureAndDownload(frontEl, 'depan');
                await new Promise(r => setTimeout(r, 300));
                await captureAndDownload(backEl, 'belakang');
                showPegawaiToast('Kedua sisi kartu berhasil diunduh!');
            }
        } catch (e) {
            console.error(e);
            alert('Gagal mengunduh kartu. Gunakan opsi Cetak / PDF.');
        } finally {
            if (btn) {
                btn.innerHTML = origContent;
                btn.disabled = false;
            }
        }
    };

    function showPegawaiToast(msg) {
        const toast = document.getElementById('kpPegawaiViewerToast');
        const txt = document.getElementById('kpPegawaiToastText');
        if (!toast || !txt) return;
        txt.textContent = msg;
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 2600);
    }

    window.zoomKpPegawaiQrCode = function(e, sourceEl) {
        if (e) e.stopPropagation();
        const overlay = document.getElementById('kpPegawaiQrZoomOverlay');
        const box = document.getElementById('kpPegawaiQrZoomBox');
        const nameEl = document.getElementById('kpPegawaiQrZoomName');
        const codeEl = document.getElementById('kpPegawaiQrZoomCode');
        const roleEl = document.getElementById('kpPegawaiQrZoomRole');
        if (!overlay || !box) return;

        let qrSvg = null;
        let pName = '-';
        let pCode = '-';
        let pRole = '-';

        const qrSource = sourceEl || document.querySelector('#kpPegawaiSlideFront .kp-qrcode-box');
        if (qrSource) {
            const svgEl = qrSource.querySelector('svg');
            if (svgEl) qrSvg = svgEl.outerHTML;
            pName = qrSource.dataset.pegawaiName || '-';
            pCode = qrSource.dataset.pegawaiCode || '-';
            pRole = qrSource.dataset.pegawaiRole || '-';
        }

        if (!qrSvg) return;

        box.innerHTML = qrSvg;
        if (nameEl) nameEl.textContent = pName;
        if (codeEl) codeEl.innerHTML = '<i class="fas fa-id-badge"></i> NIP: ' + pCode;
        if (roleEl) roleEl.style.display = 'none';

        overlay.style.display = 'flex';
        requestAnimationFrame(() => overlay.classList.add('active'));
    };

    window.closeKpPegawaiQrZoom = function() {
        const overlay = document.getElementById('kpPegawaiQrZoomOverlay');
        if (!overlay) return;
        overlay.classList.remove('active');
        setTimeout(() => {
            overlay.style.display = 'none';
        }, 220);
    };

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const qrZoom = document.getElementById('kpPegawaiQrZoomOverlay');
            if (qrZoom && qrZoom.style.display === 'flex') {
                window.closeKpPegawaiQrZoom();
                return;
            }
            const viewer = document.getElementById('kpPegawaiFullscreenViewer');
            if (viewer && viewer.style.display === 'flex') {
                window.closeKartuPegawaiModal();
            }
        }
    });
})();
