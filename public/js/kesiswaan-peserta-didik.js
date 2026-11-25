/**
 * Logika JavaScript Modular: Peserta Didik (Aktif, Tidak Aktif, Alumni, Berkas)
 * Standar Resmi SAE (Vanilla JS + SweetAlert2)
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Modal Helper
    function openModal(selector) {
        const modal = document.querySelector(selector);
        if (modal) modal.style.display = 'flex';
    }

    function closeModal(selector) {
        const modal = document.querySelector(selector);
        if (modal) modal.style.display = 'none';
    }

    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', function () {
            const target = this.getAttribute('data-target');
            if (target) closeModal(target);
        });
    });

    // 2b. Input Manual / Edit Data Alumni (tab Alumni)
    const formAlumni = document.getElementById('formAlumni');
    const alumniStoreUrl = formAlumni ? formAlumni.getAttribute('action') : '';

    const alumniFields = {
        alNama: 'nama',
        alNisn: 'nisn',
        alNipd: 'nipd',
        alNik: 'nik',
        alJenisKelamin: 'jenis_kelamin',
        alTempatLahir: 'tempat_lahir',
        alTanggalLahir: 'tanggal_lahir',
        alAgama: 'agama_id_str',
        alKebutuhanKhusus: 'kebutuhan_khusus',
        alRombel: 'nama_rombel_terakhir',
        alTahunLulus: 'tahun_lulus',
        alAlasanKeluar: 'alasan_keluar',
        alTanggalKeluar: 'tanggal_keluar',
        alAlamat: 'alamat_jalan',
        alRt: 'rt',
        alRw: 'rw',
        alDusun: 'dusun',
        alKelurahan: 'kelurahan',
        alKecamatan: 'kecamatan',
        alKodePos: 'kode_pos',
        alJenisTinggal: 'jenis_tinggal',
        alAlatTransportasi: 'alat_transportasi',
        alTeleponRumah: 'nomor_telepon_rumah',
        alNoHp: 'nomor_telepon_seluler',
        alEmail: 'email',
        alSkhun: 'skhun',
        alPenerimaKps: 'penerima_kps',
        alNoKps: 'no_kps',
        alNamaAyah: 'nama_ayah',
        alTahunLahirAyah: 'tahun_lahir_ayah',
        alPendidikanAyah: 'jenjang_pendidikan_ayah',
        alPekerjaanAyah: 'pekerjaan_ayah',
        alPenghasilanAyah: 'penghasilan_ayah',
        alKhususAyah: 'kebutuhan_khusus_ayah',
        alNamaIbu: 'nama_ibu',
        alTahunLahirIbu: 'tahun_lahir_ibu',
        alPendidikanIbu: 'jenjang_pendidikan_ibu',
        alPekerjaanIbu: 'pekerjaan_ibu',
        alPenghasilanIbu: 'penghasilan_ibu',
        alKhususIbu: 'kebutuhan_khusus_ibu',
        alNamaWali: 'nama_wali',
        alTahunLahirWali: 'tahun_lahir_wali',
        alPendidikanWali: 'jenjang_pendidikan_wali',
        alPekerjaanWali: 'pekerjaan_wali',
        alPenghasilanWali: 'penghasilan_wali',
    };

    // Field tanggal yang perlu dipotong ke format Y-m-d saat prefill.
    const alumniDateFields = ['alTanggalLahir', 'alTanggalKeluar'];

    function resetAlumniForm() {
        if (!formAlumni) return;
        formAlumni.reset();
        formAlumni.setAttribute('action', alumniStoreUrl);
        const method = document.getElementById('alumniMethod');
        if (method) method.value = 'POST';
        document.getElementById('modalAlumniTitle').textContent = 'Ubah Data Alumni';
        document.getElementById('modalAlumniSubtitle').textContent = 'Perbarui identitas alumni.';
    }

    document.addEventListener('click', function (e) {
        const btnEdit = e.target.closest('.btn-edit-alumni');
        if (btnEdit) {
            e.preventDefault();
            const id = btnEdit.getAttribute('data-id');
            if (!id) return;

            resetAlumniForm();
            openModal('#modalAlumni');
            document.getElementById('modalAlumniTitle').textContent = 'Ubah Data Alumni';
            document.getElementById('modalAlumniSubtitle').textContent = 'Memuat data alumni...';

            fetch(`${alumniStoreUrl}/${encodeURIComponent(id)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(res => res.json())
                .then(json => {
                    if (json.status !== 'success' || !json.data) {
                        Swal.fire('Gagal', json.message || 'Data alumni tidak ditemukan.', 'error');
                        closeModal('#modalAlumni');
                        return;
                    }

                    const row = json.data;
                    Object.keys(alumniFields).forEach(fieldId => {
                        const el = document.getElementById(fieldId);
                        if (!el) return;
                        let val = row[alumniFields[fieldId]] ?? '';
                        if (alumniDateFields.includes(fieldId) && val) val = String(val).slice(0, 10);
                        el.value = val;
                    });

                    formAlumni.setAttribute('action', `${alumniStoreUrl}/${encodeURIComponent(id)}`);
                    document.getElementById('alumniMethod').value = 'PUT';
                    document.getElementById('alumniPesertaDidikId').value = id;
                    document.getElementById('modalAlumniSubtitle').textContent = 'Perbarui identitas alumni.';
                })
                .catch(() => {
                    Swal.fire('Error', 'Gagal memuat data alumni.', 'error');
                    closeModal('#modalAlumni');
                });
        }
    });

    if (formAlumni) {
        formAlumni.addEventListener('submit', function (e) {
            e.preventDefault();

            const btn = document.getElementById('btnSimpanAlumni');
            const original = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
            }

            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                },
                body: new FormData(this)
            })
                .then(async res => {
                    const json = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        const firstError = json.errors ? Object.values(json.errors)[0]?.[0] : null;
                        throw new Error(firstError || json.message || 'Gagal menyimpan data alumni.');
                    }
                    return json;
                })
                .then(json => {
                    if (json.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: json.message,
                            timer: 1600,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('Gagal!', json.message || 'Terjadi kesalahan.', 'error');
                    }
                })
                .catch(err => {
                    Swal.fire('Gagal!', err.message, 'error');
                })
                .finally(() => {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = original;
                    }
                });
        });
    }

    // 3. Validasi Berkas Digital PDF Siswa (Modal 3)
    let berkasStatusChanged = false;

    // Delegated click event listener agar selalu aktif bahkan setelah live search / ajax reload
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-validasi-berkas-detail');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            const id = btn.getAttribute('data-id');
            const nama = btn.getAttribute('data-nama');
            if (id && typeof window.openModalVerifikasiBerkas === 'function') {
                window.openModalVerifikasiBerkas(id, nama);
            }
        }
    });

    // Delegated close listener for modalVerifikasiBerkasDigital & backdrop/ESC sync
    function handleModalVbClose() {
        const modal = document.getElementById('modalVerifikasiBerkasDigital');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
        if (berkasStatusChanged) {
            location.reload();
        }
    }

    document.addEventListener('click', function (e) {
        const closeBtn = e.target.closest('.close-modal[data-target="#modalVerifikasiBerkasDigital"]');
        if (closeBtn) {
            e.preventDefault();
            handleModalVbClose();
        }
    });

    const modalVbEl = document.getElementById('modalVerifikasiBerkasDigital');
    if (modalVbEl) {
        modalVbEl.addEventListener('click', function (e) {
            if (e.target === modalVbEl) {
                handleModalVbClose();
            }
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            const modalVb = document.getElementById('modalVerifikasiBerkasDigital');
            const modalPdf = document.getElementById('modalPdfViewer');
            // If PDF viewer was on top, let closePdfViewer handle it
            if (modalPdf && modalPdf.style.display !== 'none') {
                window.closePdfViewer();
                return;
            }
            if (modalVb && modalVb.style.display !== 'none' && berkasStatusChanged) {
                location.reload();
            }
        }
    });

    window.openModalVerifikasiBerkas = async function (id, nama) {
        const modal = document.getElementById('modalVerifikasiBerkasDigital');
        const loading = document.getElementById('modalVbLoading');
        const content = document.getElementById('modalVbContent');
        const namaEl = document.getElementById('modalVbNama');
        const infoEl = document.getElementById('modalVbInfo');

        if (!modal) return;

        berkasStatusChanged = false;
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        loading.style.display = 'block';
        content.style.display = 'none';
        content.innerHTML = '';
        namaEl.textContent = `Validasi Berkas: ${nama || 'Peserta Didik'}`;
        infoEl.textContent = 'Memuat data siswa dan berkas digital...';

        try {
            const res = await fetch(`/dashboard/kesiswaan/peserta-didik/berkas-detail/${encodeURIComponent(id)}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });
            const json = await res.json();

            loading.style.display = 'none';
            content.style.display = 'block';

            if (json.status !== 'success') {
                Swal.fire('Error', json.message || 'Gagal memuat berkas siswa.', 'error');
                modal.style.display = 'none';
                document.body.style.overflow = '';
                return;
            }

            const siswa = json.siswa;
            infoEl.innerHTML = `NISN: <strong>${escapeHtml(siswa.nisn)}</strong> &bull; NIPD: <strong>${escapeHtml(siswa.nipd)}</strong> &bull; Rombel: <strong>${escapeHtml(siswa.rombel)}</strong>`;

            // Kartu stepper + PDF inline 2 kolom ditangani bersama oleh validasi-berkas-modal.js
            const rekomendasiList = json.rekomendasi_penolakan || [];
            window.SaeVbModal.render(content, {
                items: json.items,
                siswa,
                pdId: id,
                rekomendasiList,
                canUpload: true,
                uploadUrl: (pid) => `/dashboard/kesiswaan/peserta-didik/berkas-upload/${encodeURIComponent(pid)}`,
                verifyUrl: (pid) => `/dashboard/kesiswaan/peserta-didik/berkas-verifikasi/${encodeURIComponent(pid)}`,
                csrf: () => document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '',
                reopen: (pid, nm) => window.openModalVerifikasiBerkas(pid, nm),
                isAdmin: json.is_admin === true,
                onChanged: () => { berkasStatusChanged = true; },
                finish: () => { berkasStatusChanged = false; modal.style.display = 'none'; document.body.style.overflow = ''; window.refreshLiveTable ? window.refreshLiveTable() : location.reload(); },
            });
        } catch (err) {
            loading.style.display = 'none';
            Swal.fire('Error', 'Terjadi kesalahan saat memuat berkas.', 'error');
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    };

    // 3b. Viewer Dokumen PDF Terpadu (In-App Modal tanpa Buka Tab Baru) dengan Kontrol Validasi Langsung
    let activePdfViewerData = null;

    window.openPdfViewer = function (url, title, pdId = null, key = null, label = null, currentStatus = null, catatan = null) {
        const modal = document.getElementById('modalPdfViewer');
        const frame = document.getElementById('pdfViewerFrame');
        const titleEl = document.getElementById('pdfViewerTitle');
        if (!modal || !frame) return;

        if (titleEl) titleEl.textContent = title || 'Pratinjau Dokumen PDF';
        frame.src = url;

        activePdfViewerData = pdId && key ? { pdId, key, label, currentStatus, catatan } : null;

        const controls = document.getElementById('pdfViewerVerifControls');
        const radioValid = document.getElementById('pdfViewerRadioValid');
        const radioTidakValid = document.getElementById('pdfViewerRadioTidakValid');
        const catatanInput = document.getElementById('pdfViewerCatatan');

        if (controls) {
            if (activePdfViewerData) {
                controls.style.display = 'flex';
                const card = document.getElementById(`cardBerkas_${key}`);
                const effectiveStatus = card ? (card.getAttribute('data-selected-status') || currentStatus) : currentStatus;

                if (radioValid) radioValid.checked = (effectiveStatus === 'valid');
                if (radioTidakValid) radioTidakValid.checked = (effectiveStatus === 'tidak_valid');

                if (catatanInput) {
                    const existingCatatan = document.getElementById(`textarea_${key}`)?.value?.trim() || catatan || '';
                    catatanInput.value = existingCatatan;
                    catatanInput.style.display = (effectiveStatus === 'tidak_valid') ? 'inline-block' : 'none';
                }
            } else {
                controls.style.display = 'none';
            }
        }

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    };

    window.closePdfViewer = function () {
        const modal = document.getElementById('modalPdfViewer');
        const frame = document.getElementById('pdfViewerFrame');
        if (modal) modal.style.display = 'none';
        if (frame) frame.src = '';

        const closedKey = activePdfViewerData?.key;
        activePdfViewerData = null;

        // Restore active window focus immediately so parent clicks don't get swallowed
        window.focus();

        const modalVb = document.getElementById('modalVerifikasiBerkasDigital');
        if (!modalVb || modalVb.style.display === 'none') {
            document.body.style.overflow = '';
        } else if (closedKey) {
            const card = document.getElementById(`cardBerkas_${closedKey}`);
            if (card) {
                card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
    };

    // Listener interaksi radio & simpan langsung di viewer PDF
    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('pdf-viewer-radio')) {
            const catatanInput = document.getElementById('pdfViewerCatatan');
            if (catatanInput) {
                catatanInput.style.display = e.target.value === 'tidak_valid' ? 'inline-block' : 'none';
                if (e.target.value === 'tidak_valid') catatanInput.focus();
            }
            if (activePdfViewerData?.key) {
                const card = document.getElementById(`cardBerkas_${activePdfViewerData.key}`);
                if (card) {
                    card.setAttribute('data-selected-status', e.target.value);
                    const cardRadio = card.querySelector(`input[name="status_${activePdfViewerData.key}"][value="${e.target.value}"]`);
                    if (cardRadio) {
                        cardRadio.checked = true;
                        cardRadio.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
            }
        }
    });

    document.addEventListener('click', async function (e) {
        const btnSimpan = e.target.closest('#btnPdfViewerSimpan');
        if (btnSimpan && activePdfViewerData) {
            e.preventDefault();
            const { pdId, key, label } = activePdfViewerData;
            const selectedRadio = document.querySelector('input[name="pdf_viewer_status"]:checked');
            if (!selectedRadio) {
                Swal.fire('Peringatan', 'Silakan pilih status validasi (Valid atau Tidak Valid).', 'warning');
                return;
            }

            const status = selectedRadio.value;
            const catatanInput = document.getElementById('pdfViewerCatatan');
            const catatan = catatanInput ? catatanInput.value.trim() : '';

            if (status === 'tidak_valid' && !catatan) {
                Swal.fire('Wajib Catatan', 'Untuk status Tidak Valid / Tidak Sesuai, Anda wajib mengisi catatan penolakan.', 'warning');
                if (catatanInput) catatanInput.focus();
                return;
            }

            const origText = btnSimpan.innerHTML;
            btnSimpan.disabled = true;
            btnSimpan.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                || document.querySelector('input[name="_token"]')?.value
                || '';

            try {
                const postRes = await fetch(`/dashboard/kesiswaan/peserta-didik/berkas-verifikasi/${encodeURIComponent(pdId)}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        jenis_berkas: key,
                        status: status,
                        catatan_penolakan: catatan,
                        rekomendasi_penolakan: catatan,
                    })
                });

                const postJson = await postRes.json();
                btnSimpan.disabled = false;
                btnSimpan.innerHTML = origText;

                if (postRes.ok && postJson.status === 'success') {
                    berkasStatusChanged = true;
                    const card = document.getElementById(`cardBerkas_${key}`);
                    if (card) {
                        const badgeContainer = card.querySelector('.status-badge-container');
                        if (badgeContainer) {
                            badgeContainer.innerHTML = status === 'valid'
                                ? `<span class="badge badge-success" style="font-size: 0.74rem; padding: 4px 10px;"><i class="fas fa-circle-check me-1"></i> Valid / Sesuai</span>`
                                : `<span class="badge badge-danger" style="font-size: 0.74rem; padding: 4px 10px;"><i class="fas fa-circle-xmark me-1"></i> Tidak Valid / Tidak Sesuai</span>`;
                        }
                        card.setAttribute('data-selected-status', status);
                        const cardRadio = card.querySelector(`input[name="status_${key}"][value="${status}"]`);
                        if (cardRadio) {
                            cardRadio.checked = true;
                            cardRadio.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                        const cardTextarea = document.getElementById(`textarea_${key}`);
                        if (cardTextarea && catatan) {
                            cardTextarea.value = catatan;
                        }
                    }

                    window.closePdfViewer();

                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: postJson.message,
                        timer: 1500,
                        showConfirmButton: false,
                    });
                } else {
                    Swal.fire('Gagal', postJson.message || 'Terjadi kesalahan sistem.', 'error');
                }
            } catch (err) {
                btnSimpan.disabled = false;
                btnSimpan.innerHTML = origText;
                Swal.fire('Error', 'Gagal menghubungi server.', 'error');
            }
        }
    });

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-preview-pdf-inline');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            const url = btn.getAttribute('data-url');
            const title = btn.getAttribute('data-title');
            const pdId = btn.getAttribute('data-pd-id');
            const key = btn.getAttribute('data-key');
            const label = btn.getAttribute('data-label');
            const status = btn.getAttribute('data-status');
            const catatan = btn.getAttribute('data-catatan');
            if (url) window.openPdfViewer(url, title, pdId, key, label, status, catatan);
        }

        const closeBtn = e.target.closest('.close-pdf-viewer');
        if (closeBtn) {
            e.preventDefault();
            e.stopPropagation();
            window.closePdfViewer();
        }
    });

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
