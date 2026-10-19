/**
 * Wali Kelas & Koordinator — Agenda Kegiatan Belajar Mengajar Kelas Binaan
 * Standard SAE (Vanilla JavaScript + SweetAlert2)
 */

function escapeHtml(str) {
    if (!str) return "-";
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

document.addEventListener("DOMContentLoaded", function () {
    const CFG = window.WALI_AGENDA_CFG || { routes: {} };

    // ==========================================
    // 1. FILTER & LIVE SEARCH
    // ==========================================
    const perPageSelect = document.getElementById("perPageSelect");
    const filterTanggalMulai = document.getElementById("filterTanggalMulai");
    const filterTanggalSelesai = document.getElementById("filterTanggalSelesai");
    const filterPtk = document.getElementById("filterPtk");
    const filterMapel = document.getElementById("filterMapel");
    const filterStatus = document.getElementById("filterStatus");
    const adminRombelSelect = document.getElementById("adminRombelSelect");
    const btnResetFilter = document.getElementById("btnResetFilter");

    function applyFilter(overrideParams = {}) {
        const url = new URL(window.location.href);

        if (perPageSelect) url.searchParams.set("perPage", perPageSelect.value);
        if (filterTanggalMulai) {
            if (filterTanggalMulai.value) url.searchParams.set("tanggal_mulai", filterTanggalMulai.value);
            else url.searchParams.delete("tanggal_mulai");
        }
        if (filterTanggalSelesai) {
            if (filterTanggalSelesai.value) url.searchParams.set("tanggal_selesai", filterTanggalSelesai.value);
            else url.searchParams.delete("tanggal_selesai");
        }
        if (filterPtk) {
            if (filterPtk.value) url.searchParams.set("ptk_id", filterPtk.value);
            else url.searchParams.delete("ptk_id");
        }
        if (filterMapel) {
            if (filterMapel.value) url.searchParams.set("nama_mata_pelajaran", filterMapel.value);
            else url.searchParams.delete("nama_mata_pelajaran");
        }
        if (filterStatus) {
            if (filterStatus.value) url.searchParams.set("status_kbm", filterStatus.value);
            else url.searchParams.delete("status_kbm");
        }
        if (adminRombelSelect) {
            if (adminRombelSelect.value) url.searchParams.set("rombel_id", adminRombelSelect.value);
            else url.searchParams.delete("rombel_id");
        }

        const searchEl = document.getElementById("liveSearchInput");
        if (searchEl) {
            const qVal = searchEl.value.trim();
            if (qVal) url.searchParams.set("q", qVal);
            else url.searchParams.delete("q");
        }

        Object.keys(overrideParams).forEach((k) => {
            const v = overrideParams[k];
            if (v !== null && v !== undefined && v !== "") url.searchParams.set(k, v);
            else url.searchParams.delete(k);
        });

        // Reset page on filter change
        if (!("page" in overrideParams)) {
            url.searchParams.delete("page");
        }

        window.location.href = url.toString();
    }

    if (perPageSelect) perPageSelect.addEventListener("change", () => applyFilter());
    if (filterTanggalMulai) filterTanggalMulai.addEventListener("change", () => applyFilter());
    if (filterTanggalSelesai) filterTanggalSelesai.addEventListener("change", () => applyFilter());
    if (filterPtk) filterPtk.addEventListener("change", () => applyFilter());
    if (filterMapel) filterMapel.addEventListener("change", () => applyFilter());
    if (filterStatus) filterStatus.addEventListener("change", () => applyFilter());
    if (adminRombelSelect) adminRombelSelect.addEventListener("change", () => applyFilter());

    // Date picker & Quick date buttons untuk Jadwal Harian
    const inputJadwalTanggal = document.getElementById("inputJadwalTanggal");
    if (inputJadwalTanggal) {
        inputJadwalTanggal.addEventListener("change", function () {
            applyFilter({ tanggal: this.value, tab: "jadwal" });
        });
    }

    document.addEventListener("click", function (e) {
        const qBtn = e.target.closest(".btn-quick-date");
        if (qBtn && qBtn.getAttribute("data-date")) {
            applyFilter({ tanggal: qBtn.getAttribute("data-date"), tab: "jadwal" });
        }
    });

    if (btnResetFilter) {
        btnResetFilter.addEventListener("click", function () {
            const url = new URL(window.location.href);
            const rombelId = url.searchParams.get("rombel_id");
            url.search = "";
            if (rombelId) url.searchParams.set("rombel_id", rombelId);
            window.location.href = url.toString();
        });
    }

    // Live search with 400ms debounce
    let searchDebounce = null;
    document.addEventListener("input", function (e) {
        if (e.target && e.target.id === "liveSearchInput") {
            const clearBtn = e.target.parentElement?.querySelector(".clear-search");
            if (clearBtn) {
                clearBtn.classList.toggle("visible", e.target.value.trim().length > 0);
            }
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => {
                applyFilter();
            }, 400);
        }
    });

    document.addEventListener("keydown", function (e) {
        if (e.target && e.target.id === "liveSearchInput" && e.key === "Enter") {
            e.preventDefault();
            clearTimeout(searchDebounce);
            applyFilter();
        }
    });

    document.addEventListener("click", function (e) {
        const cBtn = e.target.closest(".clear-search");
        if (cBtn && cBtn.previousElementSibling?.id === "liveSearchInput") {
            const sEl = cBtn.previousElementSibling;
            sEl.value = "";
            cBtn.classList.remove("visible");
            sEl.focus();
            applyFilter({ q: "" });
        }
    });

    // Sortable Column Headers
    document.addEventListener("click", function (e) {
        const th = e.target.closest(".sortable-th");
        if (!th) return;

        const sortField = th.getAttribute("data-sort");
        if (!sortField) return;

        const url = new URL(window.location.href);
        const currentSort = url.searchParams.get("sort") || "tanggal";
        const currentDir = url.searchParams.get("sort_dir") || "desc";

        let nextDir = "asc";
        if (currentSort === sortField) {
            nextDir = currentDir === "asc" ? "desc" : "asc";
        }

        applyFilter({ sort: sortField, sort_dir: nextDir });
    });

    // ==========================================
    // 2. MODAL FORM CREATE / EDIT AGENDA KELAS
    // ==========================================
    const modalForm = document.getElementById("modalFormAgendaKelas");
    const formAgenda = document.getElementById("formAgendaKelas");
    const btnOpenCreate = document.getElementById("btnOpenCreateAgenda");
    const selectJadwal = document.getElementById("selectJadwalKelas");
    const selectGuru = document.getElementById("selectGuruPengampu");
    const inputMapel = document.getElementById("inputNamaMataPelajaran");
    const inputPtkId = document.getElementById("inputPtkId");
    const inputPertemuan = document.getElementById("inputPertemuanKe");

    function openModalForm() {
        if (!modalForm) return;
        modalForm.style.display = "flex";
        document.body.style.overflow = "hidden";
    }

    function closeModalForm() {
        if (!modalForm) return;
        modalForm.style.display = "none";
        document.body.style.overflow = "";
    }

    document.querySelectorAll(".close-agenda-modal").forEach((btn) => {
        btn.addEventListener("click", closeModalForm);
    });

    if (modalForm) {
        modalForm.addEventListener("click", function (e) {
            if (e.target === modalForm) closeModalForm();
        });
    }

    if (btnOpenCreate) {
        btnOpenCreate.addEventListener("click", function () {
            if (!formAgenda) return;
            formAgenda.reset();
            document.getElementById("agendaKelasId").value = "";
            document.getElementById("inputJadwalKbmId").value = "";
            document.getElementById("inputPembelajaranId").value = "";
            document.getElementById("inputMataPelajaranId").value = "";
            document.getElementById("inputPtkId").value = "";
            document.getElementById("inputHari").value = "";

            const banner = document.getElementById("slotJadwalBanner");
            if (banner) banner.style.display = "none";
            const wrapSelect = document.getElementById("wrapSelectJadwalKelas");
            if (wrapSelect) wrapSelect.style.display = "block";

            const titleEl = document.getElementById("modalAgendaKelasTitle");
            if (titleEl) {
                titleEl.innerHTML = '<i class="fas fa-book-open-reader text-primary"></i> Input Agenda Kegiatan Kelas';
            }

            // Default dates and hours
            const now = new Date();
            const yyyy = now.getFullYear();
            const mm = String(now.getMonth() + 1).padStart(2, "0");
            const dd = String(now.getDate()).padStart(2, "0");
            document.getElementById("inputTanggal").value = CFG.currentTanggal || `${yyyy}-${mm}-${dd}`;
            document.getElementById("inputPertemuanKe").value = 1;
            document.getElementById("inputJamKeMulai").value = 1;
            document.getElementById("inputJamKeSelesai").value = 2;
            document.getElementById("inputStatusKbm").value = "Terlaksana";

            openModalForm();
        });
    }

    // Klik "Isi Agenda" langsung dari baris slot mata pelajaran terjadwal
    document.addEventListener("click", function (e) {
        const btn = e.target.closest(".btn-bantu-isi-slot");
        if (!btn) return;

        if (!formAgenda) return;
        formAgenda.reset();

        const jadwalId = btn.getAttribute("data-jadwal-id") || "";
        const ptkId = btn.getAttribute("data-ptk-id") || "";
        const guru = btn.getAttribute("data-guru") || "-";
        const mapel = btn.getAttribute("data-mapel") || "";
        const pembelajaranId = btn.getAttribute("data-pembelajaran-id") || "";
        const mapelId = btn.getAttribute("data-mapel-id") || "";
        const hari = btn.getAttribute("data-hari") || "";
        const jamMulai = btn.getAttribute("data-jam-mulai") || "1";
        const jamSelesai = btn.getAttribute("data-jam-selesai") || "2";
        const pertemuan = btn.getAttribute("data-pertemuan") || "1";

        document.getElementById("agendaKelasId").value = "";
        document.getElementById("inputJadwalKbmId").value = jadwalId;
        document.getElementById("inputPembelajaranId").value = pembelajaranId;
        document.getElementById("inputMataPelajaranId").value = mapelId;
        document.getElementById("inputPtkId").value = ptkId;
        document.getElementById("inputHari").value = hari;

        if (inputMapel) inputMapel.value = mapel;
        if (selectGuru) selectGuru.value = ptkId;
        if (selectJadwal) selectJadwal.value = jadwalId;

        document.getElementById("inputTanggal").value = CFG.currentTanggal || new Date().toISOString().split("T")[0];
        document.getElementById("inputPertemuanKe").value = pertemuan;
        document.getElementById("inputJamKeMulai").value = jamMulai;
        document.getElementById("inputJamKeSelesai").value = jamSelesai;
        document.getElementById("inputStatusKbm").value = "Terlaksana";

        // Tampilkan banner slot terpilih
        const banner = document.getElementById("slotJadwalBanner");
        if (banner) {
            document.getElementById("bannerSlotMapel").textContent = mapel;
            document.getElementById("bannerSlotGuru").textContent = guru;
            document.getElementById("bannerSlotWaktu").textContent = `${hari} • Jam ${jamMulai} s.d. ${jamSelesai}`;
            banner.style.display = "block";
        }
        const wrapSelect = document.getElementById("wrapSelectJadwalKelas");
        if (wrapSelect) wrapSelect.style.display = "none";

        const titleEl = document.getElementById("modalAgendaKelasTitle");
        if (titleEl) {
            titleEl.innerHTML = '<i class="fas fa-plus-circle text-primary"></i> Isi Agenda Mata Pelajaran Terjadwal';
        }

        openModalForm();
        setTimeout(() => document.getElementById("inputMateriPokok")?.focus(), 250);
    });

    // Auto-fill dari Jadwal Pelajaran Kelas
    if (selectJadwal) {
        selectJadwal.addEventListener("change", async function () {
            const opt = this.options[this.selectedIndex];
            if (!opt || !this.value) return;

            const ptkId = opt.getAttribute("data-ptk-id") || "";
            const mapel = opt.getAttribute("data-mapel") || "";
            const pembelajaranId = opt.getAttribute("data-pembelajaran-id") || "";
            const mapelId = opt.getAttribute("data-mapel-id") || "";
            const hari = opt.getAttribute("data-hari") || "";
            const jamMulai = opt.getAttribute("data-jam-mulai") || "1";
            const jamSelesai = opt.getAttribute("data-jam-selesai") || "2";

            if (inputMapel) inputMapel.value = mapel;
            if (inputPtkId) inputPtkId.value = ptkId;
            if (selectGuru) selectGuru.value = ptkId;

            document.getElementById("inputJadwalKbmId").value = this.value;
            document.getElementById("inputPembelajaranId").value = pembelajaranId;
            document.getElementById("inputMataPelajaranId").value = mapelId;
            document.getElementById("inputHari").value = hari;
            document.getElementById("inputJamKeMulai").value = jamMulai;
            document.getElementById("inputJamKeSelesai").value = jamSelesai;

            // Fetch pertemuan ke berikutnya
            await fetchNextPertemuan(pembelajaranId, mapel);
        });
    }

    if (selectGuru) {
        selectGuru.addEventListener("change", function () {
            if (inputPtkId) inputPtkId.value = this.value;
        });
    }

    async function fetchNextPertemuan(pembelajaranId, namaMapel) {
        if (!CFG.routes.nextPertemuan) return;
        try {
            const params = new URLSearchParams();
            if (pembelajaranId) params.set("pembelajaran_id", pembelajaranId);
            if (namaMapel) params.set("nama_mata_pelajaran", namaMapel);

            const res = await fetch(`${CFG.routes.nextPertemuan}?${params}`, {
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            });
            const json = await res.json();
            if (json.status === "success" && json.next_pertemuan) {
                if (inputPertemuan) inputPertemuan.value = json.next_pertemuan;
            }
        } catch (e) {
            // ignore
        }
    }

    // Submit form (Create / Update)
    if (formAgenda) {
        formAgenda.addEventListener("submit", async function (e) {
            e.preventDefault();

            const ptkId = inputPtkId?.value || selectGuru?.value;
            if (!ptkId) {
                Swal.fire("Peringatan", "Silakan pilih Guru Pengampu mata pelajaran terlebih dahulu.", "warning");
                return;
            }

            const id = document.getElementById("agendaKelasId")?.value;
            const isEdit = Boolean(id);
            const url = isEdit ? `${CFG.routes.update}/${encodeURIComponent(id)}` : CFG.routes.store;
            const method = isEdit ? "PUT" : "POST";

            const submitBtn = document.getElementById("btnSubmitAgendaKelas");
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

            const payload = {
                jadwal_kbm_id: document.getElementById("inputJadwalKbmId")?.value || null,
                pembelajaran_id: document.getElementById("inputPembelajaranId")?.value || null,
                mata_pelajaran_id: document.getElementById("inputMataPelajaranId")?.value || null,
                ptk_id: ptkId,
                nama_mata_pelajaran: document.getElementById("inputNamaMataPelajaran")?.value || "",
                tanggal: document.getElementById("inputTanggal")?.value || "",
                hari: document.getElementById("inputHari")?.value || "",
                jam_ke_mulai: document.getElementById("inputJamKeMulai")?.value || 1,
                jam_ke_selesai: document.getElementById("inputJamKeSelesai")?.value || 2,
                pertemuan_ke: document.getElementById("inputPertemuanKe")?.value || 1,
                materi_pokok: document.getElementById("inputMateriPokok")?.value || "",
                uraian_kegiatan: document.getElementById("inputUraianKegiatan")?.value || "",
                penugasan: document.getElementById("inputPenugasan")?.value || "",
                status_kbm: document.getElementById("inputStatusKbm")?.value || "Terlaksana",
                hambatan_catatan: document.getElementById("inputHambatanCatatan")?.value || "",
            };

            const csrfToken =
                document.querySelector('meta[name="csrf-token"]')?.content ||
                document.querySelector('input[name="_token"]')?.value ||
                "";

            try {
                const res = await fetch(url, {
                    method: method,
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    body: JSON.stringify(payload),
                });
                const json = await res.json();
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;

                if (res.ok && json.status === "success") {
                    closeModalForm();
                    Swal.fire({
                        icon: "success",
                        title: isEdit ? "Agenda Diperbarui!" : "Agenda Tersimpan!",
                        text: json.message,
                        timer: 1600,
                        showConfirmButton: false,
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire("Gagal", json.message || "Terjadi kesalahan saat menyimpan data.", "error");
                }
            } catch (err) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                Swal.fire("Kesalahan Jaringan", "Tidak dapat terhubung ke server.", "error");
            }
        });
    }

    // ==========================================
    // 3. DETAIL MODAL AGENDA KELAS
    // ==========================================
    const modalDetail = document.getElementById("modalDetailAgendaKelas");
    const detailLoading = document.getElementById("detailLoading");
    const detailContent = document.getElementById("detailContent");

    function openModalDetail() {
        if (!modalDetail) return;
        modalDetail.style.display = "flex";
        document.body.style.overflow = "hidden";
    }

    function closeModalDetail() {
        if (!modalDetail) return;
        modalDetail.style.display = "none";
        document.body.style.overflow = "";
    }

    document.querySelectorAll(".close-detail-modal").forEach((btn) => {
        btn.addEventListener("click", closeModalDetail);
    });

    if (modalDetail) {
        modalDetail.addEventListener("click", function (e) {
            if (e.target === modalDetail) closeModalDetail();
        });
    }

    document.addEventListener("click", async function (e) {
        const btn = e.target.closest(".btn-detail-agenda");
        if (!btn) return;

        const id = btn.getAttribute("data-id");
        if (!id) return;

        openModalDetail();
        if (detailLoading) detailLoading.style.display = "block";
        if (detailContent) detailContent.style.display = "none";

        try {
            const res = await fetch(`${CFG.routes.detail}/${encodeURIComponent(id)}`, {
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            });
            const json = await res.json();

            if (detailLoading) detailLoading.style.display = "none";
            if (detailContent) detailContent.style.display = "flex";

            if (json.status === "success" && json.data) {
                const d = json.data;
                document.getElementById("dtMapel").textContent = d.nama_mata_pelajaran || "-";
                document.getElementById("dtGuru").textContent = d.nama_guru || "-";
                document.getElementById("dtWaktu").textContent = `${d.hari || ""}, ${d.tanggal_formatted || "-"} (Jam ${d.jam_ke_mulai}-${d.jam_ke_selesai} • Pertemuan #${d.pertemuan_ke})`;
                document.getElementById("dtStatus").innerHTML = d.status_badge || "-";
                document.getElementById("dtMateri").textContent = d.materi_pokok || "-";
                document.getElementById("dtUraian").textContent = d.uraian_kegiatan || "-";
                document.getElementById("dtPenugasan").textContent = d.penugasan || "Tidak ada penugasan khusus.";
                document.getElementById("dtCreatedBy").textContent = d.created_by || "Guru Pengampu";

                const boxCatatan = document.getElementById("boxDtCatatan");
                if (boxCatatan) {
                    if (d.hambatan_catatan) {
                        boxCatatan.style.display = "block";
                        document.getElementById("dtCatatan").textContent = d.hambatan_catatan;
                    } else {
                        boxCatatan.style.display = "none";
                    }
                }
            } else {
                Swal.fire("Error", json.message || "Gagal memuat detail agenda.", "error");
                closeModalDetail();
            }
        } catch (err) {
            if (detailLoading) detailLoading.style.display = "none";
            closeModalDetail();
            Swal.fire("Kesalahan Jaringan", "Tidak dapat terhubung ke server.", "error");
        }
    });

    // ==========================================
    // 4. EDIT AGENDA KELAS
    // ==========================================
    document.addEventListener("click", async function (e) {
        const btn = e.target.closest(".btn-edit-agenda");
        if (!btn) return;

        const id = btn.getAttribute("data-id");
        if (!id) return;

        Swal.fire({
            title: "Memuat Data...",
            text: "Mengambil data agenda untuk diedit...",
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading(),
        });

        try {
            const res = await fetch(`${CFG.routes.detail}/${encodeURIComponent(id)}`, {
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            });
            const json = await res.json();
            Swal.close();

            if (json.status === "success" && json.data) {
                const d = json.data;

                document.getElementById("agendaKelasId").value = d.id;
                document.getElementById("inputJadwalKbmId").value = d.jadwal_kbm_id || "";
                document.getElementById("inputPembelajaranId").value = d.pembelajaran_id || "";
                document.getElementById("inputMataPelajaranId").value = d.mata_pelajaran_id || "";
                document.getElementById("inputPtkId").value = d.ptk_id || "";
                document.getElementById("inputHari").value = d.hari || "";

                if (selectGuru) selectGuru.value = d.ptk_id || "";
                if (inputMapel) inputMapel.value = d.nama_mata_pelajaran || "";
                if (selectJadwal) selectJadwal.value = d.jadwal_kbm_id || "";

                document.getElementById("inputTanggal").value = d.tanggal || "";
                document.getElementById("inputPertemuanKe").value = d.pertemuan_ke || 1;
                document.getElementById("inputJamKeMulai").value = d.jam_ke_mulai || 1;
                document.getElementById("inputJamKeSelesai").value = d.jam_ke_selesai || 2;
                document.getElementById("inputMateriPokok").value = d.materi_pokok || "";
                document.getElementById("inputUraianKegiatan").value = d.uraian_kegiatan || "";
                document.getElementById("inputPenugasan").value = d.penugasan || "";
                document.getElementById("inputStatusKbm").value = d.status_kbm || "Terlaksana";
                document.getElementById("inputHambatanCatatan").value = d.hambatan_catatan || "";

                const banner = document.getElementById("slotJadwalBanner");
                if (banner) banner.style.display = "none";
                const wrapSelect = document.getElementById("wrapSelectJadwalKelas");
                if (wrapSelect) wrapSelect.style.display = "block";

                const titleEl = document.getElementById("modalAgendaKelasTitle");
                if (titleEl) {
                    titleEl.innerHTML = '<i class="fas fa-pen-to-square text-warning"></i> Edit Agenda Kegiatan Kelas';
                }

                openModalForm();
            } else {
                Swal.fire("Error", json.message || "Gagal memuat data agenda.", "error");
            }
        } catch (err) {
            Swal.close();
            Swal.fire("Kesalahan Jaringan", "Tidak dapat terhubung ke server.", "error");
        }
    });

    // ==========================================
    // 5. DELETE AGENDA KELAS
    // ==========================================
    document.addEventListener("click", function (e) {
        const btn = e.target.closest(".btn-delete-agenda");
        if (!btn) return;

        const id = btn.getAttribute("data-id");
        if (!id) return;

        Swal.fire({
            title: "Hapus Agenda Kelas?",
            text: "Data agenda kegiatan ini akan dihapus dari arsip kelas binaan.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#ef4444",
            cancelButtonColor: "#64748b",
            confirmButtonText: "Ya, Hapus!",
            cancelButtonText: "Batal",
        }).then(async (result) => {
            if (!result.isConfirmed) return;

            const csrfToken =
                document.querySelector('meta[name="csrf-token"]')?.content ||
                document.querySelector('input[name="_token"]')?.value ||
                "";

            try {
                const res = await fetch(`${CFG.routes.destroy}/${encodeURIComponent(id)}`, {
                    method: "DELETE",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                });
                const json = await res.json();
                if (res.ok && json.status === "success") {
                    Swal.fire({
                        icon: "success",
                        title: "Dihapus!",
                        text: json.message,
                        timer: 1500,
                        showConfirmButton: false,
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire("Gagal", json.message || "Tidak dapat menghapus agenda.", "error");
                }
            } catch (err) {
                Swal.fire("Kesalahan Jaringan", "Tidak dapat terhubung ke server.", "error");
            }
        });
    });
});
