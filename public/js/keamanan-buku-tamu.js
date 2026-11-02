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

    const jenis = document.getElementById('tamu_jenis');
    if (jenis) jenis.value = 'umum';

    const pdId = document.getElementById('tamu_pd_id');
    if (pdId) pdId.value = '';

    const feedback = document.getElementById('manualLookupFeedback');
    if (feedback) feedback.innerHTML = '';

    window.toggleManualOrtuSection();

    modal.style.display = 'flex';
};

window.closeModalTamu = function () {
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

window.toggleManualOrtuSection = function () {
    const jenis = document.getElementById('tamu_jenis');
    const sectionOrtu = document.getElementById('manualSectionOrtu');
    if (!jenis || !sectionOrtu) return;

    if (jenis.value === 'orang_tua') {
        sectionOrtu.style.display = 'block';
    } else {
        sectionOrtu.style.display = 'none';
    }
};

window.lookupManualStudent = function () {
    const nikInput = document.getElementById('manual_nik_ortu');
    const nisnInput = document.getElementById('manual_nisn_siswa');
    const feedback = document.getElementById('manualLookupFeedback');
    const btn = document.getElementById('btnManualCekSiswa');

    const nik = nikInput ? nikInput.value.trim() : '';
    const nisn = nisnInput ? nisnInput.value.trim() : '';

    if (!nik && !nisn) {
        if (feedback) feedback.innerHTML = '<span style="color:#b45309;"><i class="fas fa-exclamation-triangle me-1"></i> Masukkan NIK Ortu atau NISN/NIK Siswa.</span>';
        return;
    }

    const url = (window.BUKU_TAMU_CONFIG && window.BUKU_TAMU_CONFIG.checkStudentUrl) || '/tamu/api/cek-siswa';

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    }

    fetch(`${url}?nik_ortu=${encodeURIComponent(nik)}&nisn_siswa=${encodeURIComponent(nisn)}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-search"></i>';
        }

        if (data.found && data.students && data.students.length > 0) {
            const st = data.students[0];
            const namaSiswaInput = document.getElementById('tamu_nama_siswa');
            const rombelInput = document.getElementById('tamu_rombel_siswa');
            const pdIdInput = document.getElementById('tamu_pd_id');
            const tamuNamaInput = document.getElementById('tamu_nama');

            if (namaSiswaInput) namaSiswaInput.value = st.nama;
            if (rombelInput) rombelInput.value = st.nama_rombel || '';
            if (pdIdInput) pdIdInput.value = st.peserta_didik_id || '';

            if (data.suggested_parent_name && tamuNamaInput && !tamuNamaInput.value.trim()) {
                tamuNamaInput.value = data.suggested_parent_name;
            }

            if (feedback) {
                feedback.innerHTML = `<span style="color:#047857; font-weight:600;"><i class="fas fa-check-circle me-1"></i> Ditemukan: ${st.nama} (${st.nama_rombel || '-'})</span>`;
            }
        } else {
            if (feedback) {
                feedback.innerHTML = '<span style="color:#b45309;"><i class="fas fa-times-circle me-1"></i> Data tidak ditemukan di database. Silakan isi nama siswa & rombel manual.</span>';
            }
        }
    })
    .catch(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-search"></i>';
        }
        if (feedback) {
            feedback.innerHTML = '<span style="color:#b91c1c;"><i class="fas fa-exclamation-circle me-1"></i> Gagal memeriksa data siswa.</span>';
        }
    });
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
    setVal('tamu_jenis', data.jenis_tamu || 'umum');
    setVal('manual_nik_ortu', data.nik_tamu);
    setVal('manual_nisn_siswa', data.nisn_siswa);
    setVal('tamu_pd_id', data.peserta_didik_id);
    setVal('tamu_nama_siswa', data.nama_siswa);
    setVal('tamu_rombel_siswa', data.rombel_siswa);
    setVal('tamu_nama', data.nama_tamu);
    setVal('tamu_instansi', data.instansi_asal);
    setVal('tamu_kontak', data.nomor_kontak);
    setVal('tamu_tujuan', data.tujuan_bertemu);
    setVal('tamu_keperluan', data.keperluan);
    setVal('tamu_kartu', data.nomor_kartu_visitor);
    setVal('tamu_nopol', data.nomor_polisi_kendaraan);
    setVal('tamu_status', data.status || 'berada_di_lokasi');

    const feedback = document.getElementById('manualLookupFeedback');
    if (feedback) feedback.innerHTML = '';

    window.toggleManualOrtuSection();

    modal.style.display = 'flex';
};

document.addEventListener('DOMContentLoaded', function () {
    const modalTamu = document.getElementById('modalTamu');
    const modalQr = document.getElementById('modalQrCode');

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

    // Enter key di input NIK / NISN modal satpam
    const nikInput = document.getElementById('manual_nik_ortu');
    const nisnInput = document.getElementById('manual_nisn_siswa');
    [nikInput, nisnInput].forEach(inp => {
        if (inp) {
            inp.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    window.lookupManualStudent();
                }
            });
        }
    });

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
                if (confirm(`Apakah Anda yakin ingin menghapus catatan tamu "${name}"?`)) {
                    delForm.submit();
                }
            }
        });
    });
});
