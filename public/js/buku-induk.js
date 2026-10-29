/**
 * SAE — Pengelolaan Buku Induk Peserta Didik
 * Modular JavaScript (Vanilla JS + SweetAlert2), mengacu pada datatable global SAE.
 */

document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalBukuInduk');
    const loading = document.getElementById('modalBukuIndukLoading');
    const form = document.getElementById('formBukuInduk');
    const titleEl = document.getElementById('modalBukuIndukTitle');
    const subtitleEl = document.getElementById('modalBukuIndukSubtitle');
    const inputPdId = document.getElementById('biPesertaDidikId');
    const btnCetak = document.getElementById('btnCetakBukuInduk');
    const btnSimpan = document.getElementById('btnSimpanBukuInduk');

    function openModal() {
        if (!modal) return;
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        if (!modal) return;
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    // Delegated close handler (tombol X, tombol Batal, dan klik backdrop)
    document.addEventListener('click', function (e) {
        const closeBtn = e.target.closest('.close-modal[data-target="#modalBukuInduk"]');
        if (closeBtn) {
            e.preventDefault();
            closeModal();
            return;
        }

        if (modal && e.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal && modal.style.display !== 'none') {
            closeModal();
        }
    });

    function fillForm(d) {
        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.value = val === null || val === undefined ? '' : val;
        };

        setVal('biPesertaDidikId', d.peserta_didik_id);
        setVal('biNipd', d.nipd);
        setVal('biNomorKlaper', d.nomor_klaper);
        setVal('biNisn', d.nisn);
        setVal('biNama', d.nama);
        setVal('biJenisKelamin', d.jenis_kelamin || 'L');
        setVal('biTempatLahir', d.tempat_lahir);
        setVal('biTanggalLahir', d.tanggal_lahir);
        setVal('biAgama', d.agama_id_str);
        setVal('biNik', d.nik);
        setVal('biNoKk', d.no_kk);
        setVal('biNoAkta', d.no_registrasi_akta_lahir);
        setVal('biKewarganegaraan', d.kewarganegaraan);
        setVal('biAnakKe', d.anak_keberapa);
        setVal('biJumlahSaudara', d.jumlah_saudara_kandung);
        setVal('biGolonganDarah', d.golongan_darah);
        setVal('biBahasa', d.bahasa_sehari_hari);
        setVal('biKelainanJasmani', d.kelainan_jasmani);
        setVal('biPenyakit', d.penyakit_pernah_diderita);

        setVal('biAlamat', d.alamat_jalan);
        setVal('biRt', d.rt);
        setVal('biRw', d.rw);
        setVal('biKodePos', d.kode_pos);
        setVal('biDesa', d.desa_kelurahan);
        setVal('biKecamatan', d.kecamatan);
        setVal('biKabupaten', d.kabupaten_kota);

        setVal('biNamaAyah', d.nama_ayah);
        setVal('biNikAyah', d.nik_ayah);
        setVal('biPekerjaanAyah', d.pekerjaan_ayah_str);

        setVal('biNamaIbu', d.nama_ibu);
        setVal('biNikIbu', d.nik_ibu);
        setVal('biPekerjaanIbu', d.pekerjaan_ibu_str);

        setVal('biNamaWali', d.nama_wali);
        setVal('biNikWali', d.nik_wali);
        setVal('biHubunganWali', d.hubungan_wali);

        setVal('biSekolahAsal', d.sekolah_asal);
        setVal('biTahunMasuk', d.tahun_masuk);
        setVal('biStatusKlaper', d.status_klaper || 'aktif');
        setVal('biNoHp', d.nomor_telepon_seluler);
        setVal('biCatatan', d.catatan_kesiswaan);

        if (btnCetak) {
            btnCetak.href = `/dashboard/kesiswaan/buku-induk/${encodeURIComponent(d.peserta_didik_id)}/cetak`;
        }
    }

    window.openBukuIndukModal = async function (id, nama) {
        if (!modal || !form) return;

        openModal();
        if (loading) loading.style.display = 'block';
        form.style.display = 'none';
        if (titleEl) titleEl.textContent = 'Formulir Buku Induk';
        if (subtitleEl) subtitleEl.textContent = `Memuat data ${nama || 'peserta didik'}...`;

        try {
            const res = await fetch(`/dashboard/kesiswaan/buku-induk/${encodeURIComponent(id)}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const json = await res.json();

            if (loading) loading.style.display = 'none';

            if (json.status !== 'success') {
                Swal.fire('Gagal', json.message || 'Data buku induk tidak dapat dimuat.', 'error');
                closeModal();
                return;
            }

            const d = json.data;
            fillForm(d);

            if (form) {
                form.action = `/dashboard/kesiswaan/buku-induk/${encodeURIComponent(d.peserta_didik_id)}`;
                form.style.display = 'flex';
            }

            if (titleEl) titleEl.textContent = `Buku Induk — ${d.nama}`;
            if (subtitleEl) {
                subtitleEl.innerHTML =
                    `NISN: <strong>${escapeHtml(d.nisn || '-')}</strong> &bull; ` +
                    `Rombel: <strong>${escapeHtml(d.rombel_nama || '-')}</strong> &bull; ` +
                    `No. Induk: <strong>${escapeHtml(d.nipd || '-')}</strong>`;
            }
        } catch (err) {
            if (loading) loading.style.display = 'none';
            Swal.fire('Kesalahan Jaringan', 'Tidak dapat menghubungi server.', 'error');
            closeModal();
        }
    };

    // Delegated trigger tombol detail (tetap aktif setelah refreshLiveTable)
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-detail-buku-induk');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        const id = btn.getAttribute('data-id');
        const nama = btn.getAttribute('data-nama');
        if (id && typeof window.openBukuIndukModal === 'function') {
            window.openBukuIndukModal(id, nama);
        }
    });

    // Submit form simpan
    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const originalText = btnSimpan ? btnSimpan.innerHTML : '';
            if (btnSimpan) {
                btnSimpan.disabled = true;
                btnSimpan.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
            }

            try {
                const formData = new FormData(form);
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });

                const json = await res.json();

                if (btnSimpan) {
                    btnSimpan.disabled = false;
                    btnSimpan.innerHTML = originalText;
                }

                if (res.ok && json.status === 'success') {
                    closeModal();
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: json.message,
                        timer: 1600,
                        showConfirmButton: false,
                    }).then(() => {
                        if (typeof window.refreshLiveTable === 'function') {
                            window.refreshLiveTable(window.location.href);
                        } else {
                            location.reload();
                        }
                    });
                } else if (res.status === 422 && json.errors) {
                    const firstErr = Object.values(json.errors)[0];
                    Swal.fire('Periksa Isian', Array.isArray(firstErr) ? firstErr[0] : firstErr, 'warning');
                } else {
                    Swal.fire('Gagal', json.message || 'Data buku induk gagal disimpan.', 'error');
                }
            } catch (err) {
                if (btnSimpan) {
                    btnSimpan.disabled = false;
                    btnSimpan.innerHTML = originalText;
                }
                Swal.fire('Kesalahan Jaringan', 'Tidak dapat menghubungi server.', 'error');
            }
        });
    }

    // Sinkronisasi buku induk
    const btnSync = document.getElementById('btnSyncBukuInduk');
    if (btnSync) {
        btnSync.addEventListener('click', function () {
            window.SAE.confirm("Seluruh peserta didik akan diinisialisasi ke <strong>Buku Klaper</strong> dan <strong>profil identitas</strong> bila belum ada.", "Sinkronkan Buku Induk?", 'info', "Ya, Sinkronkan", "Batal").then(async (result) => {
                if (!result.isConfirmed) return;

                Swal.fire({
                    title: 'Memproses...',
                    text: 'Menyinkronkan data buku induk...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading(),
                });

                try {
                    const res = await fetch('/dashboard/kesiswaan/buku-induk/sync', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const json = await res.json();

                    if (res.ok && json.status === 'success') {
                        Swal.fire('Berhasil!', json.message, 'success').then(() => {
                            if (typeof window.refreshLiveTable === 'function') {
                                window.refreshLiveTable(window.location.href);
                            } else {
                                location.reload();
                            }
                        });
                    } else {
                        Swal.fire('Gagal', json.message || 'Sinkronisasi gagal.', 'error');
                    }
                } catch (err) {
                    Swal.fire('Kesalahan Jaringan', 'Tidak dapat menghubungi server.', 'error');
                }
            });
        });
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
