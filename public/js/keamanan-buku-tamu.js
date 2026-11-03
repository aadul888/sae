let modalCameraStream = null;
let modalFacingMode = 'user';

function stopModalCameraStream() {
    if (modalCameraStream) {
        modalCameraStream.getTracks().forEach(track => track.stop());
        modalCameraStream = null;
    }
    const video = document.getElementById('modalWebcamVideo');
    if (video) {
        video.pause();
        video.srcObject = null;
        video.style.display = 'none';
    }
    const placeholder = document.getElementById('modalCameraPlaceholder');
    if (placeholder) placeholder.style.display = 'block';
    const controls = document.getElementById('modalCameraControls');
    if (controls) controls.style.display = 'none';
}

function resetModalFoto(existingUrl = null) {
    stopModalCameraStream();
    const hiddenData = document.getElementById('modal_foto_tamu_data');
    const hiddenHapus = document.getElementById('modal_hapus_foto_tamu');
    const wrapPreview = document.getElementById('modalPhotoPreviewWrap');
    const imgPreview = document.getElementById('modalImgPhotoPreview');
    const inputFile = document.getElementById('modalInputFileFoto');

    if (hiddenData) hiddenData.value = '';
    if (hiddenHapus) hiddenHapus.value = '0';
    if (inputFile) inputFile.value = '';

    if (existingUrl) {
        if (imgPreview) imgPreview.src = existingUrl;
        if (wrapPreview) wrapPreview.style.display = 'block';
    } else {
        if (imgPreview) imgPreview.src = '';
        if (wrapPreview) wrapPreview.style.display = 'none';
    }

    // Default ke tab kamera
    window.switchModalPhotoTab('kamera');
}

window.switchModalPhotoTab = function (tab) {
    const btnKamera = document.getElementById('modalBtnTabKamera');
    const btnUnggah = document.getElementById('modalBtnTabUnggah');
    const panelKamera = document.getElementById('modalPanelKamera');
    const panelUnggah = document.getElementById('modalPanelUnggah');

    if (tab === 'kamera') {
        if (btnKamera) btnKamera.classList.add('active');
        if (btnUnggah) btnUnggah.classList.remove('active');
        if (panelKamera) panelKamera.style.display = 'block';
        if (panelUnggah) panelUnggah.style.display = 'none';
    } else {
        if (btnUnggah) btnUnggah.classList.add('active');
        if (btnKamera) btnKamera.classList.remove('active');
        if (panelUnggah) panelUnggah.style.display = 'block';
        if (panelKamera) panelKamera.style.display = 'none';
        stopModalCameraStream();
    }
};

window.previewFotoTamu = function (url, name) {
    const modal = document.getElementById('modalFotoPreview');
    const img = document.getElementById('modalFotoPreviewImg');
    const title = document.getElementById('modalFotoTamuNama');
    if (!modal || !img) return;
    img.src = url;
    if (title) title.textContent = 'Foto Tamu: ' + (name || '');
    modal.style.display = 'flex';
};

window.closeModalFotoPreview = function () {
    const modal = document.getElementById('modalFotoPreview');
    const img = document.getElementById('modalFotoPreviewImg');
    if (modal) modal.style.display = 'none';
    if (img) img.src = '';
};

window.openModalTamu = function () {
    const modal = document.getElementById('modalTamu');
    const form = document.getElementById('formTamu');
    const modalTitle = document.getElementById('modalTamuTitle');
    if (!modal || !form) return;
    form.reset();
    const methodInput = document.getElementById('tamuMethod');
    if (methodInput) methodInput.value = 'POST';
    form.action = form.dataset.storeUrl || '';
    if (modalTitle) modalTitle.textContent = 'Catat Tamu Masuk (Manual)';

    const now = new Date();
    const dateStr = now.toISOString().split('T')[0];
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');

    const tgl = document.getElementById('tamu_tanggal');
    if (tgl) tgl.value = dateStr;
    const jam = document.getElementById('tamu_jam_masuk');
    if (jam) jam.value = `${hours}:${minutes}`;
    const st = document.getElementById('tamu_status');
    if (st) st.value = 'berada_di_lokasi';

    window.clearManualStudentSelection();
    window.setManualCategory('orang_tua');

    // Reset dropdowns
    window.syncDropdownValue('manual_tujuan_select', 'wrapManualTujuanLainnya', 'manual_tujuan_lainnya_input', 'tamu_tujuan', '');
    window.syncDropdownValue('manual_keperluan_select', 'wrapManualKeperluanLainnya', 'manual_keperluan_lainnya_input', 'tamu_keperluan', '');
    window.syncDropdownValue('manual_instansi_select', 'wrapManualInstansiLainnya', 'manual_instansi_lainnya_input', 'tamu_instansi', '');

    // Reset foto
    resetModalFoto();

    modal.style.display = 'flex';
};

window.closeModalTamu = function () {
    stopModalCameraStream();
    const modal = document.getElementById('modalTamu');
    if (modal) modal.style.display = 'none';
};

window.openModalQr = function () {
    const modal = document.getElementById('modalQrCode');
    if (modal) modal.style.display = 'flex';
};

window.closeModalQr = function () {
    const modal = document.getElementById('modalQrCode');
    if (modal) modal.style.display = 'none';
};

window.setManualCategory = function (val) {
    const options = document.querySelectorAll('#manualCategoryGrid .category-option');
    options.forEach(opt => {
        if (opt.dataset.type === val) {
            opt.classList.add('active');
            const radio = opt.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        } else {
            opt.classList.remove('active');
        }
    });

    const sectionOrtu = document.getElementById('manualSectionOrtu');
    const sectionKedinasan = document.getElementById('manualSectionKedinasan');
    const kedinasanId = document.getElementById('manualKedinasanIdentitas');
    const groupInstansi = document.getElementById('manualGroupInstansi');
    const hiddenInstansi = document.getElementById('tamu_instansi');

    if (val === 'orang_tua') {
        if (sectionOrtu) sectionOrtu.style.display = 'block';
        if (sectionKedinasan) sectionKedinasan.style.display = 'none';
        if (kedinasanId) kedinasanId.style.display = 'none';
        if (groupInstansi) groupInstansi.style.display = 'none';
        if (hiddenInstansi) hiddenInstansi.value = 'Orang Tua / Wali Siswa';
        window.toggleManualSuratTugas(false);
    } else if (val === 'kedinasan') {
        if (sectionOrtu) sectionOrtu.style.display = 'none';
        if (sectionKedinasan) sectionKedinasan.style.display = 'block';
        if (kedinasanId) kedinasanId.style.display = 'block';
        if (groupInstansi) groupInstansi.style.display = 'block';
        window.clearManualStudentSelection();
    } else {
        if (sectionOrtu) sectionOrtu.style.display = 'none';
        if (sectionKedinasan) sectionKedinasan.style.display = 'none';
        if (kedinasanId) kedinasanId.style.display = 'none';
        if (groupInstansi) groupInstansi.style.display = 'block';
        window.toggleManualSuratTugas(false);
        window.clearManualStudentSelection();
    }
};

window.toggleManualOrtuSection = function () {
    const checked = document.querySelector('input[name="jenis_tamu"]:checked');
    const val = checked ? checked.value : 'orang_tua';
    window.setManualCategory(val);
};

window.toggleManualSuratTugas = function (show) {
    const box = document.getElementById('manualBoxSuratDetail');
    const nomorInp = document.getElementById('manual_nomor_surat');
    const tglInp = document.getElementById('manual_tanggal_surat');
    const radioYa = document.getElementById('manualRadioSuratYa');
    const radioTidak = document.getElementById('manualRadioSuratTidak');

    if (show) {
        if (box) box.style.display = 'block';
        if (radioYa) radioYa.checked = true;
    } else {
        if (box) box.style.display = 'none';
        if (radioTidak) radioTidak.checked = true;
        if (nomorInp) nomorInp.value = '';
        if (tglInp) tglInp.value = '';
    }
};

// Student search & selection in satpam modal
window.clearManualStudentSelection = function () {
    const pdId = document.getElementById('tamu_pd_id');
    const namaSiswa = document.getElementById('tamu_nama_siswa');
    const rombelSiswa = document.getElementById('tamu_rombel_siswa');
    const nisnSiswa = document.getElementById('manual_nisn_siswa');
    const nikOrtu = document.getElementById('manual_nik_ortu');
    const customNama = document.getElementById('manual_custom_nama_siswa');
    const customRombel = document.getElementById('manual_custom_rombel_siswa');
    const searchInput = document.getElementById('manual_cari_siswa');

    if (pdId) pdId.value = '';
    if (namaSiswa) namaSiswa.value = '';
    if (rombelSiswa) rombelSiswa.value = '';
    if (nisnSiswa) nisnSiswa.value = '';
    if (nikOrtu) nikOrtu.value = '';
    if (customNama) customNama.value = '';
    if (customRombel) customRombel.value = '';
    if (searchInput) searchInput.value = '';

    const selectedBox = document.getElementById('manualStudentSelectedBox');
    const searchWrap = document.getElementById('manualStudentSearchWrapper');
    const dropdownList = document.getElementById('manualStudentDropdownList');
    if (selectedBox) selectedBox.style.display = 'none';
    if (searchWrap) searchWrap.style.display = 'block';
    if (dropdownList) {
        dropdownList.style.display = 'none';
        dropdownList.innerHTML = '';
    }
};

window.selectManualStudent = function (st, matchedRole, suggestedParent) {
    const pdId = document.getElementById('tamu_pd_id');
    const namaSiswa = document.getElementById('tamu_nama_siswa');
    const rombelSiswa = document.getElementById('tamu_rombel_siswa');
    const nisnSiswa = document.getElementById('manual_nisn_siswa');
    const nikOrtu = document.getElementById('manual_nik_ortu');
    const tamuNama = document.getElementById('tamu_nama');

    if (pdId) pdId.value = st.peserta_didik_id || '';
    if (namaSiswa) namaSiswa.value = st.nama || '';
    if (rombelSiswa) rombelSiswa.value = st.nama_rombel || '';
    if (nisnSiswa) nisnSiswa.value = st.nisn || '';
    if (nikOrtu && st.nik) nikOrtu.value = st.nik;

    if (suggestedParent && tamuNama && !tamuNama.value.trim()) {
        tamuNama.value = suggestedParent;
    }

    const selectedBox = document.getElementById('manualStudentSelectedBox');
    const searchWrap = document.getElementById('manualStudentSearchWrapper');
    const namaEl = document.getElementById('manualSelectedStudentNama');
    const detailEl = document.getElementById('manualSelectedStudentDetail');
    const dropdownList = document.getElementById('manualStudentDropdownList');
    const manualWrap = document.getElementById('manualStudentInputWrap');

    if (namaEl) namaEl.textContent = st.nama || '-';
    if (detailEl) {
        const parts = [];
        if (st.nama_rombel) parts.push(`Kelas: ${st.nama_rombel}`);
        if (st.nisn) parts.push(`NISN: ${st.nisn}`);
        if (matchedRole) parts.push(matchedRole);
        detailEl.textContent = parts.join(' • ');
    }

    if (selectedBox) selectedBox.style.display = 'block';
    if (searchWrap) searchWrap.style.display = 'none';
    if (manualWrap) manualWrap.style.display = 'none';
    if (dropdownList) {
        dropdownList.style.display = 'none';
        dropdownList.innerHTML = '';
    }
};

// Dropdown Helper for sync
window.syncDropdownValue = function (selectId, wrapId, customInputId, hiddenId, targetValue) {
    const select = document.getElementById(selectId);
    const wrap = document.getElementById(wrapId);
    const customInp = document.getElementById(customInputId);
    const hidden = document.getElementById(hiddenId);
    if (!select || !hidden) return;

    if (!targetValue) {
        select.value = '';
        if (wrap) wrap.style.display = 'none';
        if (customInp) customInp.value = '';
        hidden.value = '';
        return;
    }

    // Check if targetValue is in select options
    let found = false;
    for (let i = 0; i < select.options.length; i++) {
        if (select.options[i].value === targetValue) {
            select.value = targetValue;
            found = true;
            break;
        }
    }

    if (found) {
        if (wrap) wrap.style.display = 'none';
        if (customInp) customInp.value = '';
        hidden.value = targetValue;
    } else {
        select.value = 'Lainnya';
        if (wrap) wrap.style.display = 'block';
        if (customInp) customInp.value = targetValue;
        hidden.value = targetValue;
    }
};

window.editTamu = function (data) {
    if (typeof data === 'string') {
        try { data = JSON.parse(data); } catch(e) {}
    }
    const modal = document.getElementById('modalTamu');
    const form = document.getElementById('formTamu');
    const modalTitle = document.getElementById('modalTamuTitle');
    if (!modal || !form || !data) return;
    form.reset();
    const methodInput = document.getElementById('tamuMethod');
    if (methodInput) methodInput.value = 'PUT';
    form.action = (form.dataset.updateUrl || '').replace(':id', data.id);
    if (modalTitle) modalTitle.textContent = 'Edit Data Tamu';

    const setVal = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.value = val !== undefined && val !== null ? val : '';
    };

    setVal('tamu_tanggal', data.tanggal);
    setVal('tamu_jam_masuk', data.jam_masuk ? data.jam_masuk.substring(0, 5) : '');
    setVal('tamu_jam_keluar', data.jam_keluar ? data.jam_keluar.substring(0, 5) : '');
    window.setManualCategory(data.jenis_tamu || 'umum');

    // Siswa
    if (data.nama_siswa || data.peserta_didik_id) {
        window.selectManualStudent({
            peserta_didik_id: data.peserta_didik_id,
            nama: data.nama_siswa,
            nama_rombel: data.rombel_siswa,
            nisn: data.nisn_siswa,
            nik: data.nik_tamu
        });
    } else {
        window.clearManualStudentSelection();
    }

    setVal('tamu_nama', data.nama_tamu);
    setVal('manual_nip_tamu', data.nip_tamu);
    setVal('manual_jabatan_tamu', data.jabatan_tamu);
    setVal('tamu_kontak', data.nomor_kontak);
    setVal('tamu_kartu', data.nomor_kartu_visitor);
    setVal('tamu_nopol', data.nomor_polisi_kendaraan);
    setVal('tamu_status', data.status || 'berada_di_lokasi');

    // Dropdowns
    window.syncDropdownValue('manual_tujuan_select', 'wrapManualTujuanLainnya', 'manual_tujuan_lainnya_input', 'tamu_tujuan', data.tujuan_bertemu || '');
    window.syncDropdownValue('manual_keperluan_select', 'wrapManualKeperluanLainnya', 'manual_keperluan_lainnya_input', 'tamu_keperluan', data.keperluan || '');
    window.syncDropdownValue('manual_instansi_select', 'wrapManualInstansiLainnya', 'manual_instansi_lainnya_input', 'tamu_instansi', data.instansi_asal || '');

    if (data.ada_surat_tugas) {
        window.toggleManualSuratTugas(true);
        setVal('manual_nomor_surat', data.nomor_surat_tugas);
        setVal('manual_tanggal_surat', data.tanggal_surat_tugas);
    } else {
        window.toggleManualSuratTugas(false);
    }

    // Set Foto Tamu jika ada
    const storageBase = (window.BUKU_TAMU_CONFIG && window.BUKU_TAMU_CONFIG.storageUrl) || '/storage/';
    if (data.foto_tamu) {
        const fullUrl = data.foto_tamu.startsWith('http') ? data.foto_tamu : (storageBase.replace(/\/$/, '') + '/' + data.foto_tamu.replace(/^\//, ''));
        resetModalFoto(fullUrl);
    } else {
        resetModalFoto();
    }

    modal.style.display = 'flex';
};

document.addEventListener('DOMContentLoaded', function () {
    const modalTamu = document.getElementById('modalTamu');
    const modalQr = document.getElementById('modalQrCode');
    const modalFotoPreview = document.getElementById('modalFotoPreview');

    if (modalTamu) {
        modalTamu.addEventListener('click', function (e) {
            if (e.target === modalTamu) window.closeModalTamu();
        });
    }

    if (modalQr) {
        modalQr.addEventListener('click', function (e) {
            if (e.target === modalQr) window.closeModalQr();
        });
    }

    if (modalFotoPreview) {
        modalFotoPreview.addEventListener('click', function (e) {
            if (e.target === modalFotoPreview) window.closeModalFotoPreview();
        });
    }

    // Modal Foto Controls (Kamera & Kompresi Berkas)
    const btnTabKamera = document.getElementById('modalBtnTabKamera');
    const btnTabUnggah = document.getElementById('modalBtnTabUnggah');
    const btnStartCamera = document.getElementById('modalBtnStartCamera');
    const btnStopCamera = document.getElementById('modalBtnStopCamera');
    const btnSwitchCamera = document.getElementById('modalBtnSwitchCamera');
    const btnCapturePhoto = document.getElementById('modalBtnCapturePhoto');
    const btnHapusFoto = document.getElementById('modalBtnHapusFoto');
    const inputFileFoto = document.getElementById('modalInputFileFoto');
    const webcamVideo = document.getElementById('modalWebcamVideo');
    const cameraPlaceholder = document.getElementById('modalCameraPlaceholder');
    const cameraControls = document.getElementById('modalCameraControls');
    const hiddenFotoData = document.getElementById('modal_foto_tamu_data');
    const hiddenHapusFoto = document.getElementById('modal_hapus_foto_tamu');
    const photoPreviewWrap = document.getElementById('modalPhotoPreviewWrap');
    const imgPhotoPreview = document.getElementById('modalImgPhotoPreview');

    if (btnTabKamera) btnTabKamera.addEventListener('click', () => window.switchModalPhotoTab('kamera'));
    if (btnTabUnggah) btnTabUnggah.addEventListener('click', () => window.switchModalPhotoTab('unggah'));

    async function startModalCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert('Browser tidak mendukung akses kamera langsung. Silakan gunakan tab Unggah Berkas.');
            window.switchModalPhotoTab('unggah');
            return;
        }

        stopModalCameraStream();

        try {
            const constraints = {
                video: {
                    facingMode: modalFacingMode,
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: false
            };
            modalCameraStream = await navigator.mediaDevices.getUserMedia(constraints);
            if (webcamVideo) {
                webcamVideo.srcObject = modalCameraStream;
                webcamVideo.style.display = 'block';
                await webcamVideo.play();
            }
            if (cameraPlaceholder) cameraPlaceholder.style.display = 'none';
            if (cameraControls) cameraControls.style.display = 'flex';
        } catch (err) {
            console.error('Kamera satpam gagal aktif:', err);
            alert('Kamera tidak dapat diakses: ' + (err.message || 'Izin kamera ditolak atau sedang dipakai.'));
        }
    }

    if (btnStartCamera) btnStartCamera.addEventListener('click', startModalCamera);
    if (btnStopCamera) btnStopCamera.addEventListener('click', stopModalCameraStream);

    if (btnSwitchCamera) {
        btnSwitchCamera.addEventListener('click', function () {
            modalFacingMode = modalFacingMode === 'user' ? 'environment' : 'user';
            startModalCamera();
        });
    }

    function compressCanvas(sourceEl, width, height, maxDim = 1000, quality = 0.8) {
        let w = width;
        let h = height;
        if (w > maxDim || h > maxDim) {
            const ratio = Math.min(maxDim / w, maxDim / h);
            w = Math.max(1, Math.round(w * ratio));
            h = Math.max(1, Math.round(h * ratio));
        }
        const canvas = document.createElement('canvas');
        canvas.width = w;
        canvas.height = h;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(sourceEl, 0, 0, w, h);
        return canvas.toDataURL('image/jpeg', quality);
    }

    if (btnCapturePhoto) {
        btnCapturePhoto.addEventListener('click', function () {
            if (!webcamVideo || !modalCameraStream) return;
            const w = webcamVideo.videoWidth || 640;
            const h = webcamVideo.videoHeight || 480;
            const dataUrl = compressCanvas(webcamVideo, w, h);
            if (hiddenFotoData) hiddenFotoData.value = dataUrl;
            if (hiddenHapusFoto) hiddenHapusFoto.value = '0';
            if (imgPhotoPreview) imgPhotoPreview.src = dataUrl;
            if (photoPreviewWrap) photoPreviewWrap.style.display = 'block';
            stopModalCameraStream();
        });
    }

    if (inputFileFoto) {
        inputFileFoto.addEventListener('change', function () {
            const file = this.files && this.files[0];
            if (!file) return;

            if (!file.type.startsWith('image/')) {
                alert('Pilih berkas format gambar (JPG/PNG).');
                this.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                const img = new Image();
                img.onload = function () {
                    const dataUrl = compressCanvas(img, img.width, img.height);
                    if (hiddenFotoData) hiddenFotoData.value = dataUrl;
                    if (hiddenHapusFoto) hiddenHapusFoto.value = '0';
                    if (imgPhotoPreview) imgPhotoPreview.src = dataUrl;
                    if (photoPreviewWrap) photoPreviewWrap.style.display = 'block';
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    if (btnHapusFoto) {
        btnHapusFoto.addEventListener('click', function () {
            if (hiddenFotoData) hiddenFotoData.value = '';
            if (hiddenHapusFoto) hiddenHapusFoto.value = '1';
            if (imgPhotoPreview) imgPhotoPreview.src = '';
            if (photoPreviewWrap) photoPreviewWrap.style.display = 'none';
            if (inputFileFoto) inputFileFoto.value = '';
        });
    }

    window.addEventListener('beforeunload', stopModalCameraStream);

    // Category radio click handler di modal satpam
    document.querySelectorAll('#manualCategoryGrid .category-option').forEach(opt => {
        opt.addEventListener('click', function () {
            const val = this.dataset.type;
            window.setManualCategory(val);
        });
    });

    // Student live search in modal satpam
    const inputCari = document.getElementById('manual_cari_siswa');
    const spinner = document.getElementById('manualStudentSearchSpinner');
    const dropdownList = document.getElementById('manualStudentDropdownList');
    const searchWrap = document.getElementById('manualStudentSearchWrapper');
    const btnClear = document.getElementById('manualBtnClearStudent');
    const btnToggleManual = document.getElementById('btnToggleManualModalStudent');
    const manualWrap = document.getElementById('manualStudentInputWrap');
    const customNama = document.getElementById('manual_custom_nama_siswa');
    const customRombel = document.getElementById('manual_custom_rombel_siswa');
    const hiddenNama = document.getElementById('tamu_nama_siswa');
    const hiddenRombel = document.getElementById('tamu_rombel_siswa');

    let searchTimer = null;
    let abortCtrl = null;

    if (btnClear) {
        btnClear.addEventListener('click', function () {
            window.clearManualStudentSelection();
            if (inputCari) inputCari.focus();
        });
    }

    if (btnToggleManual) {
        btnToggleManual.addEventListener('click', function () {
            if (!manualWrap) return;
            const isHidden = manualWrap.style.display === 'none';
            manualWrap.style.display = isHidden ? 'block' : 'none';
            if (isHidden && customNama) customNama.focus();
        });
    }

    if (customNama) {
        customNama.addEventListener('input', function () {
            if (hiddenNama) hiddenNama.value = this.value;
        });
    }
    if (customRombel) {
        customRombel.addEventListener('input', function () {
            if (hiddenRombel) hiddenRombel.value = this.value;
        });
    }

    if (inputCari) {
        inputCari.addEventListener('input', function () {
            const q = this.value.trim();
            clearTimeout(searchTimer);
            if (abortCtrl) abortCtrl.abort();

            if (q.length < 2) {
                if (dropdownList) {
                    dropdownList.style.display = 'none';
                    dropdownList.innerHTML = '';
                }
                if (spinner) spinner.style.display = 'none';
                return;
            }

            if (spinner) spinner.style.display = 'block';

            searchTimer = setTimeout(function () {
                abortCtrl = new AbortController();
                const url = (window.BUKU_TAMU_CONFIG && window.BUKU_TAMU_CONFIG.checkStudentUrl) || '/tamu/api/cek-siswa';

                fetch(`${url}?q=${encodeURIComponent(q)}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    signal: abortCtrl.signal
                })
                .then(res => res.json())
                .then(data => {
                    if (spinner) spinner.style.display = 'none';
                    if (!dropdownList) return;

                    if (data.found && data.students && data.students.length > 0) {
                        dropdownList.innerHTML = '';
                        data.students.forEach(st => {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'student-search-item';
                            btn.innerHTML = `
                                <div class="student-item-main">
                                    <span>${escapeHtml(st.nama)}</span>
                                    <span style="font-size: 0.72rem; color: #10b981; font-weight: 600;">${escapeHtml(st.nama_rombel || '-')}</span>
                                </div>
                                <div class="student-item-meta">
                                    NISN: ${escapeHtml(st.nisn || '-')} • ${st.nama_ayah ? 'Ayah: ' + escapeHtml(st.nama_ayah) : (st.nama_ibu ? 'Ibu: ' + escapeHtml(st.nama_ibu) : 'Siswa Aktif')}
                                </div>
                            `;
                            btn.addEventListener('click', function () {
                                window.selectManualStudent(st, data.matched_role, data.suggested_parent_name);
                            });
                            dropdownList.appendChild(btn);
                        });
                        dropdownList.style.display = 'block';
                    } else {
                        dropdownList.innerHTML = `
                            <div style="padding: 12px 14px; font-size: 0.78rem; color: var(--text-muted); text-align: center;">
                                Tidak ada siswa cocok dengan "<strong>${escapeHtml(q)}</strong>".<br>
                                <span style="font-size: 0.72rem;">Gunakan tombol ketik manual jika siswa baru.</span>
                            </div>
                        `;
                        dropdownList.style.display = 'block';
                    }
                })
                .catch(err => {
                    if (err.name !== 'AbortError') {
                        if (spinner) spinner.style.display = 'none';
                        if (dropdownList) dropdownList.style.display = 'none';
                    }
                });
            }, 250);
        });

        document.addEventListener('click', function (e) {
            if (!searchWrap || !searchWrap.contains(e.target)) {
                if (dropdownList) dropdownList.style.display = 'none';
            }
        });
    }

    // Modal Dropdowns helper
    function setupModalDropdown(selectId, wrapId, inputId, hiddenId) {
        const select = document.getElementById(selectId);
        const wrap = document.getElementById(wrapId);
        const input = document.getElementById(inputId);
        const hidden = document.getElementById(hiddenId);
        if (!select || !hidden) return;

        select.addEventListener('change', function () {
            if (this.value === 'Lainnya') {
                if (wrap) wrap.style.display = 'block';
                if (input) {
                    input.required = true;
                    hidden.value = input.value.trim();
                    input.focus();
                }
            } else {
                if (wrap) wrap.style.display = 'none';
                if (input) input.required = false;
                hidden.value = this.value;
            }
        });

        if (input) {
            input.addEventListener('input', function () {
                if (select.value === 'Lainnya') {
                    hidden.value = this.value.trim();
                }
            });
        }
    }

    setupModalDropdown('manual_tujuan_select', 'wrapManualTujuanLainnya', 'manual_tujuan_lainnya_input', 'tamu_tujuan');
    setupModalDropdown('manual_keperluan_select', 'wrapManualKeperluanLainnya', 'manual_keperluan_lainnya_input', 'tamu_keperluan');
    setupModalDropdown('manual_instansi_select', 'wrapManualInstansiLainnya', 'manual_instansi_lainnya_input', 'tamu_instansi');

    // Submit handler for form modal satpam
    const formTamu = document.getElementById('formTamu');
    if (formTamu) {
        formTamu.addEventListener('submit', function () {
            const selTujuan = document.getElementById('manual_tujuan_select');
            const inpTujuan = document.getElementById('manual_tujuan_lainnya_input');
            const hidTujuan = document.getElementById('tamu_tujuan');
            if (selTujuan && hidTujuan) {
                hidTujuan.value = selTujuan.value === 'Lainnya' && inpTujuan ? inpTujuan.value.trim() : selTujuan.value;
            }

            const selKep = document.getElementById('manual_keperluan_select');
            const inpKep = document.getElementById('manual_keperluan_lainnya_input');
            const hidKep = document.getElementById('tamu_keperluan');
            if (selKep && hidKep) {
                hidKep.value = selKep.value === 'Lainnya' && inpKep ? inpKep.value.trim() : selKep.value;
            }

            const selIns = document.getElementById('manual_instansi_select');
            const inpIns = document.getElementById('manual_instansi_lainnya_input');
            const hidIns = document.getElementById('tamu_instansi');
            const checkedJenis = document.querySelector('input[name="jenis_tamu"]:checked');
            if (checkedJenis && checkedJenis.value === 'orang_tua') {
                if (hidIns) hidIns.value = 'Orang Tua / Wali Siswa';
            } else if (selIns && hidIns) {
                hidIns.value = selIns.value === 'Lainnya' && inpIns ? inpIns.value.trim() : selIns.value;
            }
        });
    }

    // Delete confirmation with SweetAlert2
    document.querySelectorAll('form[data-confirm="delete"]').forEach(function (delForm) {
        delForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const name = delForm.dataset.name || 'tamu ini';
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Konfirmasi Hapus',
                    text: `Apakah Anda yakin ingin menghapus catatan tamu "${name}"?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#ef4444'
                }).then((result) => {
                    if (result.isConfirmed) {
                        delForm.submit();
                    }
                });
            } else {
                if (confirm(`Hapus catatan tamu "${name}"?`)) {
                    delForm.submit();
                }
            }
        });
    });

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
