/**
 * Logika JavaScript Modular: Peserta Didik (Aktif, Tidak Aktif, Alumni, Berkas, Usulan Perubahan)
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

    const btnOpenUsulan = document.getElementById('btnOpenUsulanModal');
    if (btnOpenUsulan) {
        btnOpenUsulan.addEventListener('click', () => openModal('#modalUsulan'));
    }

    // 2. Submit Usulan Perubahan Data
    const formUsulan = document.getElementById('formUsulan');
    if (formUsulan) {
        formUsulan.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            Swal.fire({
                title: 'Memproses...',
                text: 'Mengirim usulan perubahan data...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('Berhasil!', data.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Gagal!', data.message || 'Terjadi kesalahan.', 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error!', 'Gagal mengirim usulan.', 'error');
            });
        });
    }

    // 3. Edit Checklist Berkas Fisik
    document.querySelectorAll('.btn-edit-berkas').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const nama = this.getAttribute('data-nama');
            const akta = this.getAttribute('data-akta') === '1';
            const kk = this.getAttribute('data-kk') === '1';
            const ijazah = this.getAttribute('data-ijazah') === '1';
            const ktp = this.getAttribute('data-ktp') === '1';
            const kip = this.getAttribute('data-kip') === '1';

            Swal.fire({
                title: `Verifikasi Berkas: ${nama}`,
                html: `
                    <div style="text-align: left; font-size: 0.9rem; line-height: 2;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" id="swalAkta" ${akta ? 'checked' : ''}> Akta Kelahiran
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" id="swalKk" ${kk ? 'checked' : ''}> Kartu Keluarga (KK)
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" id="swalIjazah" ${ijazah ? 'checked' : ''}> Ijazah / SKL SMP
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" id="swalKtp" ${ktp ? 'checked' : ''}> KTP Orang Tua / Wali
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" id="swalKip" ${kip ? 'checked' : ''}> Kartu KIP / PIP (Jika Ada)
                        </label>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Simpan Verifikasi',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#2563eb',
                preConfirm: () => {
                    return {
                        akta_kelahiran: document.getElementById('swalAkta').checked,
                        kartu_keluarga: document.getElementById('swalKk').checked,
                        ijazah_smp: document.getElementById('swalIjazah').checked,
                        ktp_orang_tua: document.getElementById('swalKtp').checked,
                        kip_pip: document.getElementById('swalKip').checked,
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/dashboard/kesiswaan/peserta-didik/berkas/${id}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(result.value)
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire('Berhasil!', data.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Gagal!', data.message || 'Terjadi kesalahan.', 'error');
                        }
                    })
                    .catch(err => {
                        Swal.fire('Error!', 'Gagal memperbarui berkas.', 'error');
                    });
                }
            });
        });
    });

    // 4. Verifikasi Usulan Perubahan Data (Setujui / Tolak)
    document.querySelectorAll('.btn-verif-usulan').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const nama = this.getAttribute('data-nama');
            const kolom = this.getAttribute('data-kolom');
            const nilai = this.getAttribute('data-nilai');

            Swal.fire({
                title: 'Verifikasi Usulan Data',
                html: `
                    <div style="text-align: left; font-size: 0.88rem; margin-bottom: 12px;">
                        <div><strong>Siswa:</strong> ${nama}</div>
                        <div><strong>Kolom:</strong> ${kolom}</div>
                        <div><strong>Nilai Baru:</strong> ${nilai}</div>
                        <div style="margin-top: 10px;">
                            <label style="display:block; font-weight:700; margin-bottom:4px;">Catatan Verifikasi:</label>
                            <input id="swalCatatanVerif" class="swal2-input" placeholder="Tuliskan catatan verifikasi..." style="width: 100%; margin: 0; box-sizing: border-box;">
                        </div>
                    </div>
                `,
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: '<i class="fas fa-check"></i> Setujui',
                denyButtonText: '<i class="fas fa-times"></i> Tolak',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#10b981',
                denyButtonColor: '#ef4444',
            }).then((result) => {
                let status = null;
                if (result.isConfirmed) {
                    status = 'disetujui';
                } else if (result.isDenied) {
                    status = 'ditolak';
                }

                if (status) {
                    const catatan = document.getElementById('swalCatatanVerif')?.value || '';
                    fetch(`/dashboard/kesiswaan/peserta-didik/usulan/${id}/verifikasi`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ status: status, catatan_verifikasi: catatan })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire('Berhasil!', data.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Gagal!', data.message || 'Terjadi kesalahan.', 'error');
                        }
                    })
                    .catch(err => {
                        Swal.fire('Error!', 'Gagal memverifikasi usulan.', 'error');
                    });
                }
            });
        });
    });

    // 5. Modal Detail Biodata Lengkap Peserta Didik
    window.openBiodataPesertaDidikModal = async function (id) {
        const modal = document.getElementById("biodataModal");
        const bioNama = document.getElementById("bioNama");
        const bioRombel = document.getElementById("bioRombel");
        const bioLoading = document.getElementById("bioLoading");
        const bioContent = document.getElementById("bioContent");

        if (!modal) return;

        bioNama.textContent = "Biodata Peserta Didik";
        bioRombel.textContent = "Memuat data...";
        bioLoading.style.display = "block";
        bioContent.style.display = "none";
        modal.style.display = "flex";
        document.body.style.overflow = "hidden";

        try {
            const res = await fetch(
                "/dashboard/kesiswaan/peserta-didik/" + encodeURIComponent(id),
                {
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                }
            );
            const json = await res.json();

            bioLoading.style.display = "none";
            bioContent.style.display = "block";

            if ((json.status === "success" || json.success) && json.data) {
                const d = json.data;
                bioNama.textContent = d.nama || "Tanpa Nama";
                bioRombel.textContent =
                    [d.nama_rombel || d.nama_rombel_terakhir || d.rombel_terakhir, d.kurikulum_id_str]
                        .filter(Boolean)
                        .join(" • ") || "-";

                const fotoBox = document.getElementById("bioFotoContainer");
                if (fotoBox) {
                    const fUrl = json.foto_url || d.foto_url;
                    if (fUrl) {
                        fotoBox.innerHTML = `<img src="${fUrl}?v=${Date.now()}" alt="Foto ${escapeHtml(d.nama)}" style="width: 100%; height: 100%; object-fit: cover;">`;
                    } else {
                        fotoBox.innerHTML = `<i class="fas fa-user-graduate text-primary" style="font-size: 1.3rem;"></i>`;
                    }
                }

                document.getElementById("bioNisn").textContent =
                    [
                        d.nisn ? "NISN: " + d.nisn : null,
                        d.nipd ? "NIPD: " + d.nipd : null,
                    ]
                        .filter(Boolean)
                        .join(" / ") || "-";
                document.getElementById("bioNik").textContent = d.nik || "-";
                document.getElementById("bioJk").textContent =
                    d.jenis_kelamin === "L"
                        ? "Laki-Laki (L)"
                        : d.jenis_kelamin === "P"
                          ? "Perempuan (P)"
                          : "-";
                document.getElementById("bioTtl").textContent =
                    [d.tempat_lahir, d.tanggal_lahir].filter(Boolean).join(", ") || "-";
                document.getElementById("bioAgama").textContent =
                    d.agama_id_str || "-";
                document.getElementById("bioAnak").textContent = d.anak_keberapa
                    ? "Anak ke-" + d.anak_keberapa
                    : "-";

                const tb = d.tinggi_badan ? d.tinggi_badan + " cm" : null;
                const bb = d.berat_badan ? d.berat_badan + " kg" : null;
                document.getElementById("bioFisik").textContent =
                    [tb, bb].filter(Boolean).join(" • ") || "Belum dicatat";

                document.getElementById("bioKhusus").textContent =
                    d.kebutuhan_khusus || "Tidak Ada";

                const pendaftaranStr = [
                    d.jenis_pendaftaran_id_str ||
                        (json.anggota
                            ? json.anggota.jenis_pendaftaran_id_str
                            : null) ||
                        "Peserta Didik",
                    d.sekolah_asal ? "Asal: " + d.sekolah_asal : null,
                ]
                    .filter(Boolean)
                    .join(" • ");
                document.getElementById("bioPendaftaran").textContent =
                    pendaftaranStr || "-";

                document.getElementById("bioTglMasuk").textContent =
                    d.tanggal_masuk_sekolah || "-";

                const regArr = [
                    d.registrasi_id ? "Reg: " + d.registrasi_id : null,
                    json.anggota && json.anggota.anggota_rombel_id
                        ? "Anggota ID: " + json.anggota.anggota_rombel_id
                        : null,
                ].filter(Boolean);
                document.getElementById("bioRegId").textContent =
                    regArr.length > 0 ? regArr.join(" • ") : "-";

                document.getElementById("bioAyah").textContent =
                    [d.nama_ayah, d.pekerjaan_ayah_id_str]
                        .filter(Boolean)
                        .join(" • Pekerjaan: ") || "-";
                document.getElementById("bioIbu").textContent =
                    [d.nama_ibu, d.pekerjaan_ibu_id_str]
                        .filter(Boolean)
                        .join(" • Pekerjaan: ") || "-";
                document.getElementById("bioWali").textContent =
                    [d.nama_wali, d.pekerjaan_wali_id_str]
                        .filter(Boolean)
                        .join(" • Pekerjaan: ") || "-";

                const hpEmail = [
                    d.nomor_telepon_seluler || d.no_hp,
                    d.email,
                ].filter(Boolean).join(" / ");
                document.getElementById("bioHp").textContent = hpEmail || "-";
                document.getElementById("bioAlamat").textContent =
                    d.alamat_jalan || "-";

                const mapelSec = document.getElementById("bioMapelSection");
                const mapelList = document.getElementById("bioMapelList");
                const jmlMapel = document.getElementById("bioJmlMapel");
                if (mapelSec && mapelList) {
                    if (json.pembelajaran && json.pembelajaran.length > 0) {
                        mapelSec.style.display = "block";
                        if (jmlMapel) jmlMapel.textContent = `${json.total_mapel || json.pembelajaran.length} Mapel (${json.total_jam || 0} Jam)`;
                        mapelList.innerHTML = json.pembelajaran.map(p => `
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 6px 10px; font-weight: 600;">${escapeHtml(p.nama_mata_pelajaran || p.mata_pelajaran_id_str || '-')}</td>
                                <td style="padding: 6px 10px; color: var(--text-muted);">${escapeHtml(p.nama_guru || '-')}</td>
                                <td style="padding: 6px 10px; text-align: center; font-weight: 700;">${p.jam_mengajar_per_minggu || 0}</td>
                            </tr>
                        `).join('');
                    } else {
                        mapelSec.style.display = "none";
                        mapelList.innerHTML = "";
                    }
                }
            } else {
                Swal.fire('Error', json.message || 'Gagal memuat biodata peserta didik', 'error');
                modal.style.display = "none";
                document.body.style.overflow = "";
            }
        } catch (err) {
            bioLoading.style.display = "none";
            Swal.fire('Error', 'Terjadi kesalahan saat memuat biodata', 'error');
            modal.style.display = "none";
            document.body.style.overflow = "";
        }
    };

    window.closeBiodataModal = function () {
        const modal = document.getElementById("biodataModal");
        if (modal) modal.style.display = "none";
        document.body.style.overflow = "";
    };

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Delegated click event listener for clicking student photo or name
    document.addEventListener('click', function (e) {
        const target = e.target.closest('[data-biodata-id]');
        if (target) {
            e.preventDefault();
            const id = target.getAttribute('data-biodata-id');
            if (id) {
                window.openBiodataPesertaDidikModal(id);
            }
        }
    });

    // Datatable Realtime Live Search, Entri perPage, dan Filter Standar SAE
    const searchInput = document.getElementById("liveSearch");
    const clearBtn = document.getElementById("clearSearch");
    const perPageSelect = document.getElementById("perPageSelect");
    const filterRombel = document.getElementById("filterRombel");
    const filterGender = document.getElementById("filterGender");
    const filterTahunLulus = document.getElementById("filterTahunLulus");

    function applyFilter() {
        const url = new URL(window.location.href);
        if (searchInput && searchInput.value.trim()) {
            url.searchParams.set("q", searchInput.value.trim());
        } else {
            url.searchParams.delete("q");
        }

        if (filterRombel && filterRombel.value) {
            url.searchParams.set("rombel", filterRombel.value);
        } else {
            url.searchParams.delete("rombel");
        }

        if (filterGender && filterGender.value) {
            url.searchParams.set("gender", filterGender.value);
        } else {
            url.searchParams.delete("gender");
        }

        if (filterTahunLulus && filterTahunLulus.value.trim()) {
            url.searchParams.set("tahun_lulus", filterTahunLulus.value.trim());
        } else if (filterTahunLulus) {
            url.searchParams.delete("tahun_lulus");
        }

        if (perPageSelect && perPageSelect.value) {
            url.searchParams.set("perPage", perPageSelect.value);
        }

        // Reset sub-tab pages
        url.searchParams.delete("aktif_page");
        url.searchParams.delete("tidak_aktif_page");
        url.searchParams.delete("alumni_page");
        url.searchParams.delete("berkas_page");
        url.searchParams.delete("usulan_page");
        url.searchParams.delete("page");

        if (typeof window.refreshLiveTable === "function") {
            window.refreshLiveTable(url.toString());
        } else {
            window.location.href = url.toString();
        }
    }

    if (searchInput) {
        let timer = null;
        searchInput.addEventListener("input", function () {
            if (clearBtn) clearBtn.classList.toggle("visible", this.value.trim().length > 0);
            clearTimeout(timer);
            timer = setTimeout(applyFilter, 300);
        });

        searchInput.addEventListener("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                clearTimeout(timer);
                applyFilter();
            }
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener("click", function () {
            if (searchInput) {
                searchInput.value = "";
                clearBtn.classList.remove("visible");
                applyFilter();
            }
        });
    }

    if (filterRombel) filterRombel.addEventListener("change", applyFilter);
    if (filterGender) filterGender.addEventListener("change", applyFilter);
    if (filterTahunLulus) {
        filterTahunLulus.addEventListener("change", applyFilter);
        filterTahunLulus.addEventListener("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                applyFilter();
            }
        });
    }
    if (perPageSelect) perPageSelect.addEventListener("change", applyFilter);
});
