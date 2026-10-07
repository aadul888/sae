/**
 * SAE - Kartu Pelajar Fullscreen Card Viewer Script
 * Modern ISO CR-80 card swipe/drag gestures, QR zoom, and PNG download.
 */

(function() {
    let currentKpNisn = '';
    let currentSide = 'front';
    let isSwiping = false;
    let startX = 0;
    let startY = 0;
    let currentX = 0;
    let dragStartTime = 0;

    function refreshViewerDimensions() {
        const area = document.getElementById('kpViewerArea');
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
        slideCardTo(currentSide, false);
    }

    window.addEventListener('resize', refreshViewerDimensions);
    window.addEventListener('orientationchange', function() {
        setTimeout(refreshViewerDimensions, 150);
    });

    window.openKartuPelajarModal = function(nisn) {
        currentKpNisn = nisn;
        if (typeof window.closeKpQrZoom === 'function') {
            window.closeKpQrZoom();
        }
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

        if (loading) {
            loading.classList.remove('hidden');
            loading.style.setProperty('display', 'flex', 'important');
        }
        if (stage) stage.style.display = 'none';
        if (bottomBar) bottomBar.style.display = 'none';
        if (slideFront) slideFront.innerHTML = '';
        if (slideBack) slideBack.innerHTML = '';

        if (printLink) {
            printLink.href = '/dashboard/kartu-pelajar/cetak/' + encodeURIComponent(nisn);
        }

        fetch('/kartu-pelajar/preview/' + encodeURIComponent(nisn), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert(data.message || 'Gagal memuat kartu pelajar.');
                window.closeKartuPelajarModal();
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
            slideCardTo('front', false);

            refreshViewerDimensions();
            requestAnimationFrame(refreshViewerDimensions);
            setTimeout(refreshViewerDimensions, 50);
            setTimeout(refreshViewerDimensions, 180);

            setupSwipeGestures();
        })
        .catch(err => {
            console.error(err);
            if (loading) {
                loading.classList.add('hidden');
                loading.style.setProperty('display', 'none', 'important');
            }
            alert('Terjadi kesalahan saat memuat kartu pelajar.');
            window.closeKartuPelajarModal();
        });
    };

    window.closeKartuPelajarModal = function() {
        if (typeof window.closeKpQrZoom === 'function') {
            window.closeKpQrZoom();
        }
        const viewer = document.getElementById('kpFullscreenViewer');
        if (!viewer) return;
        viewer.style.display = 'none';
        const loading = document.getElementById('kpViewerLoading');
        if (loading) {
            loading.classList.add('hidden');
            loading.style.setProperty('display', 'none', 'important');
        }
        document.body.style.overflow = '';
        closeDownloadMenu();
    };

    window.handleKpBackdropClick = function(e) {
        if (e.target.id === 'kpFullscreenViewer' || e.target.id === 'kpViewerArea') {
            window.closeKartuPelajarModal();
        }
    };

    window.slideCardTo = function(side, animated = true) {
        currentSide = side;
        const track = document.getElementById('kpSwipeTrack');
        const pillFront = document.getElementById('kpPillFront');
        const pillBack = document.getElementById('kpPillBack');
        const arrowLeft = document.getElementById('kpArrowLeft');
        const arrowRight = document.getElementById('kpArrowRight');

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

    function setupSwipeGestures() {
        const wrapper = document.getElementById('kpSwipeWrapper');
        const track = document.getElementById('kpSwipeTrack');
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
                    window.zoomKpQrCode(e, qrBox);
                    return;
                }
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
                    window.zoomKpQrCode(e, qrBox);
                    return;
                }
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

    window.toggleKpDownloadMenu = function(e) {
        if (e) e.stopPropagation();
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

    window.executeDownload = async function(mode) {
        closeDownloadMenu();
        const btn = document.getElementById('kpBtnDownload');
        const originalHtml = btn ? btn.innerHTML : '';

        if (typeof html2canvas === 'undefined') {
            showToast('Memuat pustaka gambar...', false);
            try {
                await new Promise((resolve, reject) => {
                    const s = document.createElement('script');
                    s.src = '/js/html2canvas.min.js';
                    s.onload = resolve;
                    s.onerror = reject;
                    document.head.appendChild(s);
                });
            } catch (err) {
                window.open('/dashboard/kartu-pelajar/cetak/' + encodeURIComponent(currentKpNisn), '_blank');
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
                link.download = `Kartu_Pelajar_${currentKpNisn}_${sideName}.png`;
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
            if (btn) {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        }
    };

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

        const name = (targetBox && targetBox.dataset.studentName) ? targetBox.dataset.studentName : (document.querySelector('.kp-student-name')?.textContent?.trim() || '-');
        const nisn = (targetBox && targetBox.dataset.studentNisn) ? targetBox.dataset.studentNisn : (document.querySelector('.kp-nisn-value')?.textContent?.trim() || currentKpNisn);
        const rombel = (targetBox && targetBox.dataset.studentRombel) ? targetBox.dataset.studentRombel : (document.querySelector('.kp-rombel-name')?.textContent?.trim() || '-');

        if (nameEl) nameEl.textContent = name;
        if (nisnEl) nisnEl.innerHTML = '<i class="fas fa-id-badge"></i> NISN: ' + escapeKpText(nisn);
        if (rombelEl) {
            if (rombel && rombel !== '-') {
                rombelEl.innerHTML = '<i class="fas fa-users-rectangle"></i> ' + escapeKpText(rombel);
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

    function escapeKpText(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

    document.addEventListener('keydown', function(e) {
        const qrOverlay = document.getElementById('kpQrZoomOverlay');
        if (qrOverlay && qrOverlay.style.display !== 'none') {
            if (e.key === 'Escape') {
                window.closeKpQrZoom();
                return;
            }
        }

        const viewer = document.getElementById('kpFullscreenViewer');
        if (!viewer || viewer.style.display === 'none') return;

        if (e.key === 'Escape') {
            window.closeKartuPelajarModal();
        } else if (e.key === 'ArrowLeft') {
            window.slideCardTo('front');
        } else if (e.key === 'ArrowRight') {
            window.slideCardTo('back');
        } else if (e.key === ' ' || e.code === 'Space') {
            window.slideCardTo(currentSide === 'front' ? 'back' : 'front');
        }
    });
})();
