/**
 * Modul Analisis & Kebutuhan Alat/Bahan Laboratorium.
 * Standar Baku SAE: Vanilla JS, Modal Handling (z-index: 99999), SweetAlert2.
 */
document.addEventListener('DOMContentLoaded', function () {
    if (!window.location.pathname.includes('/dashboard/laboran/kebutuhan')) return;

    const modal = document.getElementById('modalKebutuhan');
    const form = document.getElementById('formKebutuhan');
    const title = document.getElementById('modalKebutuhanTitle');
    const methodInput = document.getElementById('kebutuhanMethod');
    const btnOpen = document.getElementById('btnOpenKebutuhan');
    const btnClose = document.getElementById('btnCloseKebutuhan');
    const btnCancel = document.getElementById('btnCancelKebutuhan');

    const STORE_URL = '/dashboard/laboran/kebutuhan';

    // Helper bersama (laboran-common.js dimuat lebih dulu oleh Blade).
    const L = window.SAELaboran;

    const selectInventarisRef = document.getElementById('selectInventarisRef');
    const kbKebutuhan = document.getElementById('kbKebutuhan');
    const kbTersedia = document.getElementById('kbTersedia');
    const kbSatuan = document.getElementById('kbSatuan');
    const kbPrioritas = document.getElementById('kbPrioritas');
    const labelKekurangan = document.getElementById('labelKekurangan');

    function kalkulasiSelisih(autoPrioritas = false) {
        const keb = kbKebutuhan ? (parseFloat(kbKebutuhan.value) || 0) : 0;
        const ter = kbTersedia ? (parseFloat(kbTersedia.value) || 0) : 0;
        const sel = Math.max(0, keb - ter);
        const sat = kbSatuan ? (kbSatuan.value || 'unit') : 'unit';

        if (labelKekurangan) {
            labelKekurangan.textContent = sel + ' ' + sat;
            labelKekurangan.style.color = sel > 0 ? 'var(--danger, #ef4444)' : 'var(--success, #10b981)';
        }

        if (autoPrioritas && kbPrioritas) {
            if (ter <= 0 || sel >= keb) {
                kbPrioritas.value = 'kritis';
            } else if (keb > 0 && (sel / keb) >= 0.5) {
                kbPrioritas.value = 'tinggi';
            } else if (sel > 0) {
                kbPrioritas.value = 'sedang';
            } else {
                kbPrioritas.value = 'rendah';
            }
        }
    }

    if (kbKebutuhan) kbKebutuhan.addEventListener('input', () => kalkulasiSelisih(false));
    if (kbTersedia) kbTersedia.addEventListener('input', () => kalkulasiSelisih(false));
    if (kbSatuan) kbSatuan.addEventListener('input', () => kalkulasiSelisih(false));

    if (selectInventarisRef) {
        selectInventarisRef.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (!opt || !this.value) return;

            const setVal = function (id, v) {
                const el = document.getElementById(id);
                if (el && v != null) el.value = v;
            };

            setVal('kbNama', opt.dataset.nama || '');
            setVal('kbJenis', opt.dataset.jenis || 'alat');
            setVal('kbKategori', opt.dataset.kategori || '');
            setVal('kbLokasi', opt.dataset.lokasi || '');
            setVal('kbSatuan', opt.dataset.satuan || 'unit');
            setVal('kbSpesifikasi', opt.dataset.spek || '');

            const total = parseFloat(opt.dataset.total) || 1;
            const tersedia = parseFloat(opt.dataset.tersedia) || 0;
            const rusakBerat = parseFloat(opt.dataset.rusakberat) || 0;
            const rusakRingan = parseFloat(opt.dataset.rusakringan) || 0;

            setVal('kbKebutuhan', total);
            setVal('kbTersedia', tersedia);

            // Susun kalimat justifikasi otomatis
            const kbAnalisis = document.getElementById('kbAnalisis');
            const kbSumber = document.getElementById('kbSumber');
            if (kbAnalisis) {
                let alasan = [];
                if (rusakBerat > 0) {
                    alasan.push(`Ditemukan ${rusakBerat} ${opt.dataset.satuan} rusak berat.`);
                    if (kbSumber) kbSumber.value = 'kerusakan';
                }
                if (rusakRingan > 0) {
                    alasan.push(`Terdapat ${rusakRingan} ${opt.dataset.satuan} rusak ringan.`);
                    if (kbSumber && rusakBerat <= 0) kbSumber.value = 'kerusakan';
                }
                if (tersedia <= 0) {
                    alasan.push(`Stok habis total.`);
                    if (kbSumber && rusakBerat <= 0) kbSumber.value = 'praktik';
                }
                alasan.push(`Kapasitas ideal: ${total} ${opt.dataset.satuan}, tersedia laik: ${tersedia} ${opt.dataset.satuan}.`);
                kbAnalisis.value = alasan.join(' ');
            }

            kalkulasiSelisih(true);
        });
    }

    function openCreate(prefill = null) {
        if (!form) return;
        form.reset();
        form.action = STORE_URL;
        if (methodInput) methodInput.value = 'POST';
        if (title) title.textContent = 'Tambah Kebutuhan';
        if (selectInventarisRef) selectInventarisRef.value = '';

        const tgl = document.getElementById('kbTanggal');
        if (tgl) {
            const now = new Date();
            tgl.value = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().split('T')[0];
        }

        if (prefill) {
            const set = function (id, val) {
                const el = document.getElementById(id);
                if (el && val != null) el.value = val;
            };
            set('kbNama', prefill.nama);
            set('kbJenis', prefill.jenis || 'alat');
            set('kbKategori', prefill.kategori || '');
            set('kbSpesifikasi', prefill.spesifikasi || '');
            set('kbKebutuhan', prefill.kebutuhan || 1);
            set('kbTersedia', prefill.tersedia || 0);
            set('kbSatuan', prefill.satuan || 'unit');
            set('kbPrioritas', prefill.prioritas || 'kritis');
            set('kbSumber', prefill.sumber || 'praktik');
            set('kbAnalisis', prefill.analisis || '');
            set('kbLokasi', prefill.lokasi || '');
        }

        kalkulasiSelisih(!prefill);
        L.openModal(modal);
    }

    function openEdit(d) {
        if (!form) return;
        form.reset();
        form.action = STORE_URL + '/' + d.id;
        if (methodInput) methodInput.value = 'PUT';
        if (title) title.textContent = 'Ubah Data Kebutuhan';
        if (selectInventarisRef) selectInventarisRef.value = '';

        const set = function (id, val) {
            const el = document.getElementById(id);
            if (el) el.value = val != null ? val : '';
        };

        set('kbNama', d.nama);
        set('kbJenis', d.jenis);
        set('kbKategori', d.kategori);
        set('kbSpesifikasi', d.spesifikasi);
        set('kbKebutuhan', d.kebutuhan);
        set('kbTersedia', d.tersedia);
        set('kbSatuan', d.satuan);
        set('kbPrioritas', d.prioritas);
        set('kbSumber', d.sumber);
        set('kbAnalisis', d.analisis);
        set('kbMapel', d.mapel);
        set('kbLokasi', d.lokasi);
        set('kbTA', d.ta);
        set('kbSemester', d.semester);
        set('kbTanggal', d.tanggal);
        set('kbStatus', d.status);
        set('kbJurusan', d.jurusan);

        kalkulasiSelisih(false);
        L.openModal(modal);
    }

    if (btnOpen) btnOpen.addEventListener('click', openCreate);
    if (btnClose) btnClose.addEventListener('click', function () { L.closeModal(modal); });
    if (btnCancel) btnCancel.addEventListener('click', function () { L.closeModal(modal); });

    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) L.closeModal(modal);
        });
    }

    document.addEventListener('click', function (e) {
        // Tombol Tambah di header banner
        if (e.target.closest('#btnOpenKebutuhan')) {
            openCreate();
            return;
        }

        // Tombol Quick Analisis dari kartu panel analisis ketersediaan
        const quickBtn = e.target.closest('.btn-quick-kebutuhan');
        if (quickBtn) {
            openCreate({
                nama: quickBtn.dataset.nama,
                jenis: quickBtn.dataset.jenis,
                kategori: quickBtn.dataset.kategori,
                lokasi: quickBtn.dataset.lokasi,
                satuan: quickBtn.dataset.satuan,
                kebutuhan: quickBtn.dataset.kebutuhan,
                tersedia: quickBtn.dataset.tersedia,
                prioritas: quickBtn.dataset.prioritas,
                sumber: quickBtn.dataset.sumber,
                analisis: quickBtn.dataset.analisis,
            });
            return;
        }

        // Tombol Batal/Tutup
        if (e.target.closest('#btnCloseKebutuhan, #btnCancelKebutuhan')) {
            L.closeModal(modal);
            return;
        }

        const editBtn = e.target.closest('.btn-edit-kebutuhan');
        if (editBtn) {
            openEdit({
                id: editBtn.dataset.id,
                nama: editBtn.dataset.nama,
                jenis: editBtn.dataset.jenis,
                kategori: editBtn.dataset.kategori,
                spesifikasi: editBtn.dataset.spesifikasi,
                kebutuhan: editBtn.dataset.kebutuhan,
                tersedia: editBtn.dataset.tersedia,
                satuan: editBtn.dataset.satuan,
                prioritas: editBtn.dataset.prioritas,
                sumber: editBtn.dataset.sumber,
                analisis: editBtn.dataset.analisis,
                mapel: editBtn.dataset.mapel,
                lokasi: editBtn.dataset.lokasi,
                ta: editBtn.dataset.ta,
                semester: editBtn.dataset.semester,
                tanggal: editBtn.dataset.tanggal,
                status: editBtn.dataset.status,
                jurusan: editBtn.dataset.jurusan
            });
            return;
        }

        const delBtn = e.target.closest('.btn-delete-kebutuhan');
        if (delBtn) {
            L.deleteData(STORE_URL + '/' + delBtn.dataset.id, delBtn.dataset.nama || 'data ini');
            return;
        }

        // Tombol Jalankan Analisis
        const triggerAnalisis = e.target.closest('#btnJalankanAnalisis, .btn-trigger-analisis');
        if (triggerAnalisis) {
            const doAnalisis = function () {
                Swal.fire({
                    title: 'Menganalisis Ketersediaan...',
                    html: 'Sedang memindai inventaris dan riwayat kerusakan...',
                    allowOutsideClick: false,
                    didOpen: function () {
                        Swal.showLoading();
                    }
                });

                fetch(STORE_URL + '/jalankan-analisis', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.status === 'success' || data.success) {
                        Swal.fire({
                            title: 'Analisis Selesai',
                            text: data.message || 'Hasil analisis berhasil diterapkan.',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(function () {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Galat',
                            text: data.message || 'Gagal menjalankan analisis kebutuhan.',
                            icon: 'error'
                        });
                    }
                })
                .catch(function (err) {
                    Swal.fire({
                        title: 'Kesalahan Sistem',
                        text: err.message || 'Terjadi kesalahan saat memproses analisis.',
                        icon: 'error'
                    });
                });
            };

            Swal.fire({
                title: 'Jalankan Analisis Kebutuhan?',
                html: 'Sistem akan memindai stok inventaris yang <strong>kritis, habis, rusak</strong>, atau menipis dalam cakupan jurusan Anda dan otomatis memasukkannya ke daftar kebutuhan.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#6b7280',
                confirmButtonText: '<i class="fas fa-wand-magic-sparkles me-1"></i> Jalankan Sekarang',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (result.isConfirmed) doAnalisis();
            });
        }
    });

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            L.submitForm(form, {
                onSuccess: function (data) {
                    L.closeModal(modal);
                    L.notifySuccess(data.message || 'Data kebutuhan berhasil disimpan.');
                }
            });
        });
    }
});
