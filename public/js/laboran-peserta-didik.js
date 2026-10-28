/**
 * Laboratorium — Data Peserta Didik (baca-saja) — Frontend JS
 *
 * Menangani:
 *  1. Modal detail biodata peserta didik (tab aktif & tidak aktif).
 *  2. Modal ekspor Excel dengan filter per kelas (rombel).
 *
 * Catatan: modul ini SENGAJA tidak memiliki aksi CRUD. Satu-satunya aksi
 * adalah melihat detail biodata dan mengekspor data ke .xlsx.
 */
(function () {
    "use strict";

    // ---------------------------------------------------------------------
    // Helper
    // ---------------------------------------------------------------------

    function esc(str) {
        if (typeof window.escapeHtml === "function") return window.escapeHtml(str);
        if (str === null || str === undefined) return "";
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function $(id) {
        return document.getElementById(id);
    }

    function setText(id, value) {
        const el = $(id);
        if (el) el.textContent = value;
    }

    function firstFilled() {
        for (let i = 0; i < arguments.length; i++) {
            const v = arguments[i];
            if (v !== null && v !== undefined && String(v).trim() !== "") return String(v).trim();
        }
        return "";
    }

    function joinParts(parts, sep) {
        const list = (parts || []).filter((p) => p !== null && p !== undefined && String(p).trim() !== "");
        return list.length ? list.join(sep || " • ") : "";
    }

    /**
     * Format tanggal ISO / Y-m-d menjadi dd/mm/yyyy. Selain format tersebut
     * dikembalikan apa adanya agar tidak ada data yang "hilang".
     */
    function formatTanggal(value) {
        if (!value) return "";
        const raw = String(value).trim();
        if (!raw) return "";
        const m = raw.match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (m) return `${m[3]}/${m[2]}/${m[1]}`;
        const d = new Date(raw);
        if (!isNaN(d.getTime())) {
            return `${String(d.getDate()).padStart(2, "0")}/${String(d.getMonth() + 1).padStart(2, "0")}/${d.getFullYear()}`;
        }
        return raw;
    }

    function labelKelamin(kode) {
        if (kode === "L") return "Laki-Laki (L)";
        if (kode === "P") return "Perempuan (P)";
        return firstFilled(kode, "-");
    }

    // ---------------------------------------------------------------------
    // Route helper (diisi dari view)
    // ---------------------------------------------------------------------

    function showUrl(id, tab) {
        const template =
            window.LABORAN_PD_SHOW_URL ||
            "/dashboard/laboran/peserta-didik/__ID__";
        const base = template.replace("__ID__", encodeURIComponent(id));
        return base + "?tab=" + encodeURIComponent(tab || "aktif");
    }

    // ---------------------------------------------------------------------
    // 1. MODAL BIODATA
    // ---------------------------------------------------------------------

    function resetBiodataModal() {
        const fields = [
            "bioNisn",
            "bioNik",
            "bioJk",
            "bioTtl",
            "bioAgama",
            "bioAnak",
            "bioFisik",
            "bioKhusus",
            "bioRombelAkademik",
            "bioJurusan",
            "bioPendaftaran",
            "bioTglMasuk",
            "bioStatusKeluar",
            "bioAyah",
            "bioIbu",
            "bioWali",
            "bioHp",
            "bioAlamat",
        ];
        fields.forEach((f) => setText(f, "-"));

        setText("bioNama", "Biodata Peserta Didik");
        setText("bioRombel", "Memuat data...");

        const statusRow = $("bioStatusKeluarRow");
        if (statusRow) statusRow.style.display = "none";

        const mapelSection = $("bioMapelSection");
        if (mapelSection) mapelSection.style.display = "none";
        const mapelList = $("bioMapelList");
        if (mapelList) mapelList.innerHTML = "";
        setText("bioJmlMapel", "0 Mapel");

        const fotoBox = $("bioFotoContainer");
        if (fotoBox) {
            fotoBox.innerHTML =
                '<i class="fas fa-user-graduate text-primary" style="font-size: 1.3rem;"></i>';
        }

        const err = $("bioError");
        if (err) {
            err.style.display = "none";
            err.textContent = "";
        }
    }

    function showBiodataError(message) {
        const err = $("bioError");
        if (err) {
            err.innerHTML =
                '<i class="fas fa-triangle-exclamation me-1"></i> ' + esc(message);
            err.style.display = "block";
        }
        const loading = $("bioLoading");
        if (loading) loading.style.display = "none";
        const content = $("bioContent");
        if (content) content.style.display = "none";
    }

    function renderMapel(pemList, totalJam) {
        const section = $("bioMapelSection");
        const list = $("bioMapelList");
        const badge = $("bioJmlMapel");
        if (!section || !list) return;

        const items = Array.isArray(pemList) ? pemList : [];
        if (items.length === 0) {
            section.style.display = "none";
            list.innerHTML = "";
            if (badge) badge.textContent = "0 Mapel";
            return;
        }

        if (badge) {
            badge.textContent = items.length + " Mapel (" + (totalJam || 0) + " JP)";
        }

        list.innerHTML = items
            .map(function (p) {
                const nama = firstFilled(
                    p.nama_mata_pelajaran,
                    p.mata_pelajaran_id_str,
                    "-",
                );
                const guru = firstFilled(p.nama_guru, "Belum Ditugaskan");
                const jam = firstFilled(p.jam_mengajar_per_minggu, "0");
                return (
                    '<tr style="border-bottom: 1px solid var(--border-color);">' +
                    '<td style="padding: 6px 10px; font-weight: 600; color: var(--text-color);">' +
                    esc(nama) +
                    "</td>" +
                    '<td style="padding: 6px 10px; color: var(--text-color); font-size: 0.78rem;">' +
                    esc(guru) +
                    "</td>" +
                    '<td style="padding: 6px 10px; text-align: center; color: #f59e0b; font-weight: 700;">' +
                    esc(jam) +
                    " JP</td>" +
                    "</tr>"
                );
            })
            .join("");

        section.style.display = "block";
    }

    function fillAktif(json) {
        const d = json.data || {};
        const fotoUrl = firstFilled(json.foto_url, d.foto_url);

        setText("bioNama", firstFilled(d.nama, "Tanpa Nama"));
        setText(
            "bioRombel",
            joinParts([d.nama_rombel, d.kurikulum_id_str], " • ") || "-",
        );

        setText(
            "bioNisn",
            joinParts(
                [
                    d.nisn ? "NISN: " + d.nisn : null,
                    d.nipd ? "NIPD: " + d.nipd : null,
                ],
                " / ",
            ) || "-",
        );
        setText("bioNik", firstFilled(d.nik, "-"));
        setText("bioJk", labelKelamin(d.jenis_kelamin));
        setText(
            "bioTtl",
            joinParts([d.tempat_lahir, formatTanggal(d.tanggal_lahir)], ", ") || "-",
        );
        setText("bioAgama", firstFilled(d.agama_id_str, "-"));
        setText("bioAnak", d.anak_keberapa ? "Anak ke-" + d.anak_keberapa : "-");

        const tb = d.tinggi_badan ? d.tinggi_badan + " cm" : null;
        const bb = d.berat_badan ? d.berat_badan + " kg" : null;
        setText("bioFisik", joinParts([tb, bb], " • ") || "Belum dicatat");

        setText("bioKhusus", firstFilled(d.kebutuhan_khusus, "Tidak Ada"));

        // --- Akademik ---
        setText(
            "bioRombelAkademik",
            joinParts(
                [
                    d.nama_rombel,
                    d.tingkat_pendidikan_id ? "Tingkat " + d.tingkat_pendidikan_id : null,
                ],
                " • ",
            ) || "-",
        );
        setText(
            "bioJurusan",
            firstFilled(json.jurusan_nama, d.jurusan_id_str, d.kurikulum_id_str, "-"),
        );
        setText(
            "bioPendaftaran",
            joinParts(
                [
                    d.jenis_pendaftaran_id_str ||
                        (json.anggota ? json.anggota.jenis_pendaftaran_id_str : null) ||
                        "Peserta Didik Baru",
                    d.sekolah_asal ? "Asal: " + d.sekolah_asal : null,
                ],
                " • ",
            ) || "-",
        );
        setText("bioTglMasuk", formatTanggal(d.tanggal_masuk_sekolah) || "-");

        const statusRow = $("bioStatusKeluarRow");
        if (statusRow) statusRow.style.display = "none";

        // --- Orang tua / wali ---
        setText(
            "bioAyah",
            joinParts(
                [
                    d.nama_ayah,
                    d.pekerjaan_ayah_id_str
                        ? "Pekerjaan: " + d.pekerjaan_ayah_id_str
                        : null,
                ],
                " • ",
            ) || "-",
        );
        setText(
            "bioIbu",
            joinParts(
                [
                    d.nama_ibu,
                    d.pekerjaan_ibu_id_str
                        ? "Pekerjaan: " + d.pekerjaan_ibu_id_str
                        : null,
                ],
                " • ",
            ) || "-",
        );
        setText(
            "bioWali",
            joinParts(
                [
                    d.nama_wali,
                    d.pekerjaan_wali_id_str
                        ? "Pekerjaan: " + d.pekerjaan_wali_id_str
                        : null,
                ],
                " • ",
            ) || "Tidak Ada (Ikut Orang Tua)",
        );

        // --- Kontak & domisili ---
        setText(
            "bioHp",
            joinParts(
                [
                    d.nomor_telepon_seluler ? "HP/WA: " + d.nomor_telepon_seluler : null,
                    d.nomor_telepon_rumah ? "Telp: " + d.nomor_telepon_rumah : null,
                    d.email ? "Email: " + d.email : null,
                ],
                " • ",
            ) || "-",
        );
        setText(
            "bioAlamat",
            joinParts(
                [
                    d.alamat_jalan,
                    d.dusun,
                    d.kelurahan,
                    d.kecamatan,
                    d.kode_pos,
                ],
                ", ",
            ) || "-",
        );

        setBiodataFoto(fotoUrl, d.nama);
        renderMapel(json.pembelajaran, json.total_jam);
    }

    function fillTidakAktif(json) {
        const d = json.data || {};
        const fotoUrl = firstFilled(d.foto_url);

        setText("bioNama", firstFilled(d.nama, "Tanpa Nama"));
        setText(
            "bioRombel",
            joinParts(
                [d.nama_rombel_terakhir, firstFilled(json.jurusan, d.rb_jurusan_nama)],
                " • ",
            ) || "-",
        );

        setText(
            "bioNisn",
            joinParts(
                [
                    d.nisn ? "NISN: " + d.nisn : null,
                    d.nipd ? "NIPD: " + d.nipd : null,
                ],
                " / ",
            ) || "-",
        );
        setText("bioNik", firstFilled(d.nik, "-"));
        setText("bioJk", labelKelamin(d.jenis_kelamin));
        setText(
            "bioTtl",
            joinParts([d.tempat_lahir, formatTanggal(d.tanggal_lahir)], ", ") || "-",
        );
        setText("bioAgama", firstFilled(d.agama_id_str, "-"));
        setText("bioAnak", "-");
        setText("bioFisik", "Belum dicatat");
        setText("bioKhusus", firstFilled(d.kebutuhan_khusus, "Tidak Ada"));

        // --- Akademik ---
        setText(
            "bioRombelAkademik",
            joinParts(
                [
                    d.nama_rombel_terakhir,
                    d.tingkat_pendidikan_terakhir
                        ? "Tingkat " + d.tingkat_pendidikan_terakhir
                        : null,
                ],
                " • ",
            ) || "-",
        );
        setText(
            "bioJurusan",
            firstFilled(json.jurusan, d.rb_jurusan_nama, d.kurikulum_id_str, "-"),
        );
        setText("bioPendaftaran", "Arsip Peserta Didik Tidak Aktif");
        setText("bioTglMasuk", "-");

        const statusRow = $("bioStatusKeluarRow");
        if (statusRow) statusRow.style.display = "";
        setText(
            "bioStatusKeluar",
            joinParts(
                [
                    d.status_keluar,
                    d.tanggal_keluar ? formatTanggal(d.tanggal_keluar) : null,
                    d.tahun_lulus ? "Lulus " + d.tahun_lulus : null,
                    d.alasan_keluar ? "Alasan: " + d.alasan_keluar : null,
                ],
                " • ",
            ) || "-",
        );

        // --- Orang tua / wali ---
        setText(
            "bioAyah",
            joinParts(
                [d.nama_ayah, d.pekerjaan_ayah ? "Pekerjaan: " + d.pekerjaan_ayah : null],
                " • ",
            ) || "-",
        );
        setText(
            "bioIbu",
            joinParts(
                [d.nama_ibu, d.pekerjaan_ibu ? "Pekerjaan: " + d.pekerjaan_ibu : null],
                " • ",
            ) || "-",
        );
        setText(
            "bioWali",
            joinParts(
                [d.nama_wali, d.pekerjaan_wali ? "Pekerjaan: " + d.pekerjaan_wali : null],
                " • ",
            ) || "Tidak Ada (Ikut Orang Tua)",
        );

        // --- Kontak & domisili ---
        setText(
            "bioHp",
            joinParts(
                [
                    d.nomor_telepon_seluler ? "HP/WA: " + d.nomor_telepon_seluler : null,
                    d.nomor_telepon_rumah ? "Telp: " + d.nomor_telepon_rumah : null,
                    d.email ? "Email: " + d.email : null,
                ],
                " • ",
            ) || "-",
        );
        setText(
            "bioAlamat",
            joinParts(
                [d.alamat_jalan, d.dusun, d.kelurahan, d.kecamatan, d.kode_pos],
                ", ",
            ) || "-",
        );

        setBiodataFoto(fotoUrl, d.nama);
        renderMapel([], 0);
    }

    function setBiodataFoto(url, nama) {
        const box = $("bioFotoContainer");
        if (!box) return;
        if (url) {
            box.innerHTML =
                '<img src="' +
                esc(url) +
                '" alt="Foto ' +
                esc(nama || "Peserta Didik") +
                '" style="width: 100%; height: 100%; object-fit: cover;">';
        } else {
            box.innerHTML =
                '<i class="fas fa-user-graduate text-primary" style="font-size: 1.3rem;"></i>';
        }
    }

    window.openBiodataPdModal = async function (id, tab) {
        const modal = $("biodataModal");
        if (!modal) return;

        const activeTab = tab === "tidak_aktif" ? "tidak_aktif" : "aktif";

        resetBiodataModal();
        modal.style.display = "flex";

        const loading = $("bioLoading");
        const content = $("bioContent");
        if (loading) loading.style.display = "block";
        if (content) content.style.display = "none";

        try {
            const res = await fetch(showUrl(id, activeTab), {
                credentials: "same-origin",
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            });

            let json = null;
            try {
                json = await res.json();
            } catch (parseErr) {
                json = null;
            }

            if (!res.ok) {
                if (loading) loading.style.display = "none";
                showBiodataError(
                    (json && (json.message || json.error)) ||
                        "Gagal memuat biodata peserta didik (HTTP " + res.status + ").",
                );
                setText("bioRombel", "Gagal memuat biodata.");
                return;
            }

            if (!json || (json.status && json.status !== "success")) {
                if (loading) loading.style.display = "none";
                showBiodataError(
                    (json && (json.message || json.error)) ||
                        "Biodata peserta didik tidak dapat ditampilkan.",
                );
                setText("bioRombel", "Gagal memuat biodata.");
                return;
            }

            if (activeTab === "tidak_aktif") fillTidakAktif(json);
            else fillAktif(json);

            if (loading) loading.style.display = "none";
            if (content) content.style.display = "block";
        } catch (e) {
            if (loading) loading.style.display = "none";
            showBiodataError(
                "Tidak dapat menghubungi server. Periksa koneksi Anda lalu coba lagi.",
            );
            setText("bioRombel", "Gagal memuat biodata.");
        }
    };

    window.closeBiodataModal = function () {
        const modal = $("biodataModal");
        if (modal) modal.style.display = "none";
    };

    // ---------------------------------------------------------------------
    // 2. MODAL EKSPOR EXCEL
    // ---------------------------------------------------------------------

    function syncExportFilters() {
        // Samakan pilihan filter modal ekspor dengan filter toolbar yang aktif
        // agar hasil unduhan sesuai apa yang sedang dilihat pengguna.
        const rombelToolbar = $("filterRombel");
        const genderToolbar = $("filterGender");
        const rombelExport = $("exportRombelSelect");
        const genderExport = $("exportGenderSelect");

        if (rombelExport && rombelToolbar) rombelExport.value = rombelToolbar.value || "";
        if (genderExport && genderToolbar) genderExport.value = genderToolbar.value || "";
        if (genderExport && !genderToolbar) genderExport.value = "";
    }

    function openExportModal() {
        const modal = $("exportPdModal");
        if (!modal) return;
        syncExportFilters();
        modal.style.display = "flex";
    }

    function closeExportModal() {
        const modal = $("exportPdModal");
        if (modal) modal.style.display = "none";
    }

    window.openExportPdModal = openExportModal;
    window.closeExportPdModal = closeExportModal;

    // ---------------------------------------------------------------------
    // 3. EVENT WIRING (delegated — tabel di-refresh via AJAX)
    // ---------------------------------------------------------------------

    document.addEventListener("click", function (e) {
        // --- Tombol detail biodata (di dalam tabel, bisa ter-render ulang) ---
        const detailBtn = e.target.closest(".btn-detail-pd");
        if (detailBtn) {
            e.preventDefault();
            const id = detailBtn.getAttribute("data-id");
            if (!id) return;
            window.openBiodataPdModal(
                id,
                detailBtn.getAttribute("data-tab") || "aktif",
            );
            return;
        }

        // --- Buka modal ekspor ---
        if (e.target.closest("#btnOpenExportPd, #btnOpenExportPdToolbar")) {
            e.preventDefault();
            openExportModal();
            return;
        }

        // --- Tutup modal ekspor ---
        if (e.target.closest(".btn-close-export-pd")) {
            e.preventDefault();
            closeExportModal();
            return;
        }

        // --- Tutup modal biodata ---
        if (e.target.closest("#btnCloseBiodataPd, #btnCloseBiodataPdFooter")) {
            e.preventDefault();
            window.closeBiodataModal();
            return;
        }

        // --- Klik backdrop untuk menutup ---
        if (e.target.id === "biodataModal") {
            window.closeBiodataModal();
            return;
        }
        if (e.target.id === "exportPdModal") {
            closeExportModal();
        }
    });

    document.addEventListener("keydown", function (e) {
        if (e.key !== "Escape" && e.key !== "Esc") return;

        const bioModal = $("biodataModal");
        if (bioModal && bioModal.style.display !== "none" && bioModal.style.display !== "") {
            window.closeBiodataModal();
        }

        const exportModal = $("exportPdModal");
        if (
            exportModal &&
            exportModal.style.display !== "none" &&
            exportModal.style.display !== ""
        ) {
            closeExportModal();
        }
    });
})();
