// Definisikan helper modal di window level segera agar tombol onclick dapat memanggilnya kapan saja
window.openModalCetakPengambilan = function () {
    const m = document.getElementById("modalCetakPengambilan");
    if (m) {
        m.style.display = "flex";
        document.body.style.overflow = "hidden";
    }
};

window.closeModalCetakPengambilan = function () {
    const m = document.getElementById("modalCetakPengambilan");
    if (m) {
        m.style.display = "none";
        document.body.style.overflow = "";
    }
};

window.openTambahPengeluaran = function () {
    const m = document.getElementById("modalTambahPengeluaran");
    if (m) {
        m.style.display = "flex";
        document.body.style.overflow = "hidden";
        const form = document.getElementById("formTambahPengeluaran");
        if (form) form.reset();
        const jml = document.getElementById("pgJumlah");
        if (jml) jml.value = "1";
        const preview = document.getElementById("pgNominalPreview");
        if (preview) {
            preview.innerHTML = `<span>Total Pengeluaran:</span> <strong style="font-size: 0.92rem; color: var(--text-muted);">Rp 0</strong>`;
        }
        document.getElementById("pgJudul")?.focus();
    }
};

window.closeTambahPengeluaran = function () {
    const m = document.getElementById("modalTambahPengeluaran");
    if (m) {
        m.style.display = "none";
        document.body.style.overflow = "";
    }
};

window.switchRequestMode = function (mode) {
    const wrapIndividu = document.getElementById("rqModeWrapIndividu");
    const wrapRombel = document.getElementById("rqModeWrapRombel");
    const btnIndividu = document.getElementById("tabBtnRqIndividu");
    const btnRombel = document.getElementById("tabBtnRqRombel");
    const hiddenMode = document.getElementById("rqMode");
    const submit = document.getElementById("rqSubmit");
    const alasan = document.getElementById("rqAlasan");

    if (mode === "rombel") {
        if (hiddenMode) hiddenMode.value = "rombel";
        if (wrapIndividu) wrapIndividu.style.display = "none";
        if (wrapRombel) wrapRombel.style.display = "block";
        if (btnIndividu) {
            btnIndividu.style.background = "transparent";
            btnIndividu.style.color = "var(--text-muted)";
        }
        if (btnRombel) {
            btnRombel.style.background = "var(--primary)";
            btnRombel.style.color = "#ffffff";
        }
        if (alasan) alasan.placeholder = "Contoh: Request cetak serentak kelas...";
        const rombelSel = document.getElementById("rqRombelSelect");
        if (submit) submit.disabled = !(rombelSel && rombelSel.value);
        if (rombelSel) rombelSel.focus();
    } else {
        if (hiddenMode) hiddenMode.value = "individu";
        if (wrapRombel) wrapRombel.style.display = "none";
        if (wrapIndividu) wrapIndividu.style.display = "block";
        if (btnRombel) {
            btnRombel.style.background = "transparent";
            btnRombel.style.color = "var(--text-muted)";
        }
        if (btnIndividu) {
            btnIndividu.style.background = "var(--primary)";
            btnIndividu.style.color = "#ffffff";
        }
        if (alasan) alasan.placeholder = "Contoh: Kartu hilang, chip tidak terbaca, siswa baru...";
        const hiddenPdId = document.getElementById("rqPdId");
        if (submit) submit.disabled = !(hiddenPdId && hiddenPdId.value);
        document.getElementById("rqCari")?.focus();
    }
};

window.openTambahRequest = function () {
    const m = document.getElementById("modalTambahRequest");
    if (m) {
        m.style.display = "flex";
        document.body.style.overflow = "hidden";
        document.getElementById("formTambahRequest")?.reset();
        document.getElementById("rqPdId") && (document.getElementById("rqPdId").value = "");
        document.getElementById("rqStatusPembayaran") && (document.getElementById("rqStatusPembayaran").value = "");
        document.getElementById("rqMetodePembayaran") && (document.getElementById("rqMetodePembayaran").value = "");
        document.getElementById("rqPilihInfo") && (document.getElementById("rqPilihInfo").textContent = "Belum ada peserta didik dipilih.");
        document.getElementById("rqRombelFotoInfo") && (document.getElementById("rqRombelFotoInfo").style.display = "none");
        window.switchRequestMode("individu");
        document.getElementById("rqCari")?.focus();
    }
};

window.closeTambahRequest = function () {
    const m = document.getElementById("modalTambahRequest");
    if (m) {
        m.style.display = "none";
        document.body.style.overflow = "";
    }
};

document.addEventListener("DOMContentLoaded", function () {
    const csrfToken =
        document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ||
        document.querySelector('input[name="_token"]')?.value ||
        window.CSRF_TOKEN ||
        "";

    // =========================================================================
    // 8. Modal Cetak Pengambilan & Tambah Pengeluaran (sudah terdaftar di window)
    // =========================================================================

    window.openEditPengeluaran = function (data) {
        const m = document.getElementById("modalEditPengeluaran");
        const form = document.getElementById("formEditPengeluaran");
        if (!m || !form || !data) return;

        form.reset();
        document.getElementById("editPgId").value = data.id || "";
        form.action = `/dashboard/kartu-pelajar/pengeluaran/${data.id}`;

        const judulInput = document.getElementById("editPgJudul");
        if (judulInput) judulInput.value = data.judul || "";

        const katInput = document.getElementById("editPgKategori");
        if (katInput) katInput.value = data.kategori || "lainnya";

        const jumlah = parseInt(data.jumlah, 10) || 1;
        const jmlInput = document.getElementById("editPgJumlah");
        if (jmlInput) jmlInput.value = jumlah;

        const hargaSatuan = data.harga_satuan ? parseInt(data.harga_satuan, 10) : (jumlah > 0 && data.nominal ? Math.round(data.nominal / jumlah) : data.nominal);
        const nomInput = document.getElementById("editPgNominal");
        if (nomInput) nomInput.value = new Intl.NumberFormat("id-ID").format(hargaSatuan || 0);

        const tglInput = document.getElementById("editPgTanggal");
        if (tglInput) {
            if (data.tanggal) {
                tglInput.value = String(data.tanggal).substring(0, 10);
            } else {
                tglInput.value = new Date().toISOString().split("T")[0];
            }
        }

        const ketInput = document.getElementById("editPgKeterangan");
        if (ketInput) ketInput.value = data.keterangan || "";

        const hintNota = document.getElementById("editPgNotaHint");
        if (hintNota) {
            if (data.bukti_nota_path) {
                hintNota.innerHTML = `Nota saat ini: <a href="/storage/${data.bukti_nota_path.replace(/^\/+/, '')}" target="_blank" style="color: #4f46e5; text-decoration: underline;"><i class="fas fa-file-invoice me-1"></i>Lihat Nota</a>. Pilih berkas baru hanya jika ingin menggantinya.`;
            } else {
                hintNota.textContent = "JPG, PNG, WebP, PDF. Maks. 5 MB. Kosongkan jika tidak ada nota.";
            }
        }

        if (typeof updateEditPengeluaranCalc === "function") {
            updateEditPengeluaranCalc();
        }

        m.style.display = "flex";
        document.body.style.overflow = "hidden";
        if (judulInput) judulInput.focus();
    };

    window.closeEditPengeluaran = function () {
        const m = document.getElementById("modalEditPengeluaran");
        if (m) {
            m.style.display = "none";
            document.body.style.overflow = "";
        }
    };

    // Event listener Rombel Select untuk cek foto siswa masal
    (function initRombelFotoPreview() {
        const rombelSelect = document.getElementById("rqRombelSelect");
        const rombelFotoInfo = document.getElementById("rqRombelFotoInfo");
        const rombelFotoText = document.getElementById("rqRombelFotoText");
        const submitBtn = document.getElementById("rqSubmit");
        const hanyaFotoCheckbox = document.getElementById("rqHanyaAdaFoto");
        if (!rombelSelect) return;

        async function updateRombelFotoCount() {
            const val = rombelSelect.value;
            if (!val) {
                if (rombelFotoInfo) rombelFotoInfo.style.display = "none";
                if (submitBtn && document.getElementById("rqMode")?.value === "rombel") {
                    submitBtn.disabled = true;
                }
                return;
            }

            if (rombelFotoInfo && rombelFotoText) {
                rombelFotoInfo.style.display = "block";
                rombelFotoText.textContent = "Menghitung ketersediaan foto siswa...";
            }

            try {
                const res = await fetch(`/dashboard/kartu-pelajar/count-rombel-foto?rombel_id=${encodeURIComponent(val)}`, {
                    headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" }
                });
                const data = await res.json();
                if (data.success && rombelFotoInfo && rombelFotoText) {
                    rombelFotoInfo.style.display = "block";
                    let text = `Tersedia <strong>${data.total_foto}</strong> dari ${data.total_siswa} siswa berfoto formal.`;
                    const queueCount = data.already_in_queue !== undefined ? data.already_in_queue : (data.already_waiting || 0);
                    if (queueCount > 0) {
                        text += ` (${queueCount} siswa sudah pernah didaftarkan dan akan otomatis dilewati)`;
                    }
                    if (data.total_foto === 0 && hanyaFotoCheckbox?.checked) {
                        text += ` <br><span style="color: #ef4444; font-weight: 700;">Perhatian: Belum ada siswa berfoto formal di kelas ini!</span>`;
                    } else if (data.total_foto > 0 && queueCount >= data.total_foto && hanyaFotoCheckbox?.checked) {
                        text += ` <br><span style="color: #f59e0b; font-weight: 700;">Perhatian: Seluruh siswa berfoto di kelas ini sudah terdaftar dalam antrean/riwayat cetak!</span>`;
                    }
                    rombelFotoText.innerHTML = text;

                    if (submitBtn && document.getElementById("rqMode")?.value === "rombel") {
                        const isBlocked = (data.total_foto === 0 && hanyaFotoCheckbox?.checked) || 
                                          (data.total_foto > 0 && queueCount >= data.total_foto && hanyaFotoCheckbox?.checked);
                        submitBtn.disabled = isBlocked;
                    }
                }
            } catch (e) {
                if (submitBtn && document.getElementById("rqMode")?.value === "rombel") {
                    submitBtn.disabled = false;
                }
            }
        }

        rombelSelect.addEventListener("change", updateRombelFotoCount);
        if (hanyaFotoCheckbox) {
            hanyaFotoCheckbox.addEventListener("change", updateRombelFotoCount);
        }
    })();

    (function initRequestAutocomplete() {
        const input = document.getElementById("rqCari");
        const list = document.getElementById("rqAcList");
        const hidden = document.getElementById("rqPdId");
        const info = document.getElementById("rqPilihInfo");
        const submit = document.getElementById("rqSubmit");
        if (!input || !list || !hidden) return;

        let timer = null;
        const esc = (s) => String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

        function reset() {
            hidden.value = "";
            if (submit) submit.disabled = true;
            if (info) info.textContent = "Belum ada peserta didik dipilih.";
        }

        input.addEventListener("input", function () {
            reset();
            clearTimeout(timer);
            const q = this.value.trim();
            if (q.length < 2) {
                list.style.display = "none";
                return;
            }
            timer = setTimeout(async () => {
                try {
                    const res = await fetch(`/dashboard/kartu-pelajar/cari-siswa?q=${encodeURIComponent(q)}`, {
                        headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
                    });
                    const rows = await res.json();
                    list.innerHTML = rows.length
                        ? rows.map((r) => {
                            const stText = r.status_pembayaran === 'lunas' ? 'Lunas' : 'Belum Bayar';
                            const stColor = r.status_pembayaran === 'lunas' ? '#10b981' : '#f59e0b';
                            const mtText = r.metode_pembayaran ? ' &bull; ' + esc(r.metode_pembayaran.toUpperCase()) : '';
                            return `<button type="button" class="kp-ac-item" data-id="${esc(r.peserta_didik_id)}" data-label="${esc(r.nama)} (${esc(r.nama_rombel || "-")})" data-status="${esc(r.status_pembayaran || 'belum_bayar')}" data-metode="${esc(r.metode_pembayaran || '')}">
                                ${esc(r.nama)}
                                <small>${esc(r.nama_rombel || "-")} &bull; NISN: ${esc(r.nisn || "-")} &bull; <span style="color:${stColor}; font-weight:700;">${stText}</span>${mtText}</small>
                            </button>`;
                        }).join("")
                        : '<div class="kp-ac-item" style="cursor:default;">Tidak ditemukan.</div>';
                    list.style.display = "block";
                } catch (e) {
                    list.style.display = "none";
                }
            }, 300);
        });

        list.addEventListener("click", function (e) {
            const btn = e.target.closest(".kp-ac-item[data-id]");
            if (!btn) return;
            hidden.value = btn.dataset.id;
            input.value = btn.dataset.label;
            const curStatus = btn.dataset.status || "belum_bayar";
            const curMetode = btn.dataset.metode || "";
            if (document.getElementById("rqStatusPembayaran")) {
                document.getElementById("rqStatusPembayaran").value = curStatus;
            }
            if (document.getElementById("rqMetodePembayaran")) {
                document.getElementById("rqMetodePembayaran").value = curMetode;
            }
            if (info) {
                const badgeClass = curStatus === 'lunas' ? 'badge-success' : 'badge-warning';
                const stText = curStatus === 'lunas' ? 'LUNAS' : 'BELUM BAYAR';
                const mtText = curMetode ? ` (${curMetode.toUpperCase()})` : '';
                info.innerHTML = `Dipilih: <strong>${esc(btn.dataset.label)}</strong> &bull; Status saat ini: <span class="badge ${badgeClass}" style="font-size:0.7rem; padding:1px 6px;">${stText}${mtText}</span>`;
            }
            if (submit) submit.disabled = false;
            list.style.display = "none";
        });

        document.addEventListener("click", function (e) {
            if (!e.target.closest(".kp-ac-wrap")) list.style.display = "none";
        });
    })();

    // =========================================================================
    // 9. Inisialisasi Chart Keuangan Kartu Pelajar (Bar & Doughnut)
    // =========================================================================
    window._kpChartBarInstance = null;
    window._kpChartDoughnutInstance = null;

    window.initKeuanganCharts = function () {
        const payloadEl = document.getElementById("kartuPelajarChartPayload");
        if (!payloadEl || typeof Chart === "undefined") return;

        let chartData = {};
        try {
            chartData = JSON.parse(payloadEl.getAttribute("data-chart") || "{}");
        } catch (e) {
            console.error("Gagal parse kartuPelajarChartPayload", e);
            return;
        }

        // 1. Chart Bar Arus Kas (Pemasukan vs Pengeluaran)
        const ctxBar = document.getElementById("chartArusKasBar");
        if (ctxBar && chartData.labels) {
            if (window._kpChartBarInstance) {
                window._kpChartBarInstance.destroy();
            }
            window._kpChartBarInstance = new Chart(ctxBar, {
                type: "bar",
                data: {
                    labels: chartData.labels,
                    datasets: [
                        {
                            label: "Pemasukan (Siswa)",
                            data: chartData.pemasukan || [],
                            backgroundColor: "rgba(16, 185, 129, 0.75)",
                            borderColor: "#10b981",
                            borderWidth: 1.5,
                            borderRadius: 6,
                        },
                        {
                            label: "Pengeluaran (Bahan/Alat)",
                            data: chartData.pengeluaran || [],
                            backgroundColor: "rgba(239, 68, 68, 0.75)",
                            borderColor: "#ef4444",
                            borderWidth: 1.5,
                            borderRadius: 6,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: "top",
                            labels: {
                                font: {
                                    family: "Plus Jakarta Sans",
                                    size: 11,
                                },
                            },
                        },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    return (
                                        ctx.dataset.label +
                                        ": Rp " +
                                        new Intl.NumberFormat("id-ID").format(ctx.raw)
                                    );
                                },
                            },
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (v) {
                                    return (
                                        "Rp " +
                                        (v >= 1000000
                                            ? v / 1000000 + "jt"
                                            : v >= 1000
                                              ? v / 1000 + "rb"
                                              : v)
                                    );
                                },
                                font: {
                                    size: 10,
                                },
                            },
                        },
                        x: {
                            ticks: {
                                font: {
                                    size: 10,
                                },
                            },
                        },
                    },
                },
            });
        }

        // 2. Chart Doughnut Kategori Pengeluaran
        const ctxDoughnut = document.getElementById("chartKategoriDoughnut");
        if (ctxDoughnut && chartData.kategori_labels) {
            if (window._kpChartDoughnutInstance) {
                window._kpChartDoughnutInstance.destroy();
            }
            const colors = ["#6366f1", "#f59e0b", "#ef4444", "#06b6d4", "#8b5cf6", "#64748b"];
            const katVals = chartData.kategori_values || [];
            const hasData = katVals.length > 0 && katVals.some(v => v > 0);

            window._kpChartDoughnutInstance = new Chart(ctxDoughnut, {
                type: "doughnut",
                data: {
                    labels: hasData ? chartData.kategori_labels : ["Belum Ada Pengeluaran"],
                    datasets: [
                        {
                            data: hasData ? katVals : [1],
                            backgroundColor: hasData ? colors.slice(0, katVals.length) : ["#e2e8f0"],
                            borderWidth: 2,
                            borderColor: "#ffffff",
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: "bottom",
                            labels: {
                                font: {
                                    family: "Plus Jakarta Sans",
                                    size: 10,
                                },
                                boxWidth: 12,
                            },
                        },
                        tooltip: {
                            enabled: hasData,
                            callbacks: {
                                label: function (ctx) {
                                    return (
                                        ctx.label +
                                        ": Rp " +
                                        new Intl.NumberFormat("id-ID").format(ctx.raw)
                                    );
                                },
                            },
                        },
                    },
                    cutout: "62%",
                },
            });
        }
    };

    window.initKeuanganCharts();
    window.addEventListener("sae:tableRefreshed", window.initKeuanganCharts);

    // =========================================================================
    // 10. AJAX: Request Cetak Kartu Pelajar (store, status, delete) - refresh hanya tabel
    // =========================================================================
    const formTambahRequest = document.getElementById("formTambahRequest");
    if (formTambahRequest) {
        formTambahRequest.addEventListener("submit", async function (e) {
            e.preventDefault();
            const btnSubmit = document.getElementById("rqSubmit");
            const originalText = btnSubmit ? btnSubmit.innerHTML : "";
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Mengirim...';
            }

            const formData = new FormData(formTambahRequest);
            const payload = Object.fromEntries(formData.entries());
            if (document.getElementById("rqHanyaAdaFoto")) {
                payload.hanya_ada_foto = document.getElementById("rqHanyaAdaFoto").checked ? 1 : 0;
            }

            try {
                const res = await fetch(formTambahRequest.action, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = originalText;
                }

                if (res.ok && data.success) {
                    closeTambahRequest();
                    if (window.SAE && typeof window.SAE.toast === "function") {
                        window.SAE.toast(data.message, "success");
                    }
                    if (typeof window.refreshLiveTable === "function") {
                        window.refreshLiveTable(window.location.href);
                    }
                } else {
                    const errorMsg = data.errors
                        ? Object.values(data.errors).flat().join(" ")
                        : (data.message || "Terjadi kesalahan sistem.");
                    Swal.fire("Gagal", errorMsg, "error");
                }
            } catch (err) {
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = originalText;
                }
                Swal.fire("Error", (err && err.message) ? err.message : "Gagal menghubungi server.", "error");
            }
        });
    }

    // Delegated handler: status select change -> AJAX submit
    document.addEventListener("change", async function (e) {
        const sel = e.target;
        if (sel && sel.name === "status" && sel.closest(".form-update-request-status, form[action*='/request/']")) {
            const form = sel.closest("form");
            if (!form) return;
            e.preventDefault();
            const originalVal = sel.dataset.current || sel.value;
            const newStatus = sel.value;

            // Jangan disable elemen sebelum mengambil nilai
            const payload = {
                status: newStatus,
                _token: csrfToken,
            };

            sel.disabled = true;

            try {
                const res = await fetch(form.action, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();
                sel.disabled = false;

                if (res.ok && data.success) {
                    sel.dataset.current = newStatus;
                    if (window.SAE && typeof window.SAE.toast === "function") {
                        window.SAE.toast(data.message, "success");
                    }
                    if (typeof window.refreshLiveTable === "function") {
                        window.refreshLiveTable(window.location.href);
                    }
                } else {
                    sel.value = originalVal;
                    const errorMsg = data.errors && data.errors.status
                        ? data.errors.status.join(" ")
                        : (data.message || "Gagal memperbarui status.");
                    Swal.fire("Gagal", errorMsg, "error");
                }
            } catch (err) {
                sel.disabled = false;
                sel.value = originalVal;
                Swal.fire("Error", (err && err.message) ? err.message : "Gagal menghubungi server.", "error");
            }
        }
    });

    // Delegated handler: delete request -> AJAX
    document.addEventListener("submit", async function (e) {
        const form = e.target;
        if (form && form.matches(".delete-request-form, .form-destroy-request, form[action*='/delete']")) {
            e.preventDefault();
            const result = await Swal.fire({
                title: "Hapus Request?",
                text: "Data antrean cetak kartu ini akan dihapus permanen.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#ef4444",
                cancelButtonColor: "#64748b",
                confirmButtonText: "Ya, Hapus!",
                cancelButtonText: "Batal",
            });
            if (!result.isConfirmed) return;

            try {
                const res = await fetch(form.action, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    },
                    body: JSON.stringify({ _token: csrfToken }),
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    if (window.SAE && typeof window.SAE.toast === "function") {
                        window.SAE.toast(data.message, "success");
                    }
                    if (typeof window.refreshLiveTable === "function") {
                        window.refreshLiveTable(window.location.href);
                    }
                } else {
                    Swal.fire("Gagal", data.message || "Gagal menghapus request.", "error");
                }
            } catch (err) {
                Swal.fire("Error", (err && err.message) ? err.message : "Gagal menghubungi server.", "error");
            }
        }
    });

    // =========================================================================
    // 11. Modal & Form Tambah Pengeluaran (AJAX + Auto-format Rupiah & Perkalian Total)
    // =========================================================================
    const pgNominalInput = document.getElementById("pgNominal");
    const pgJumlahInput = document.getElementById("pgJumlah");
    const pgNominalPreview = document.getElementById("pgNominalPreview");

    function updatePengeluaranCalc() {
        if (!pgNominalInput) return;
        const rawDigits = String(pgNominalInput.value || "").replace(/[^0-9]/g, "");
        const unitPrice = rawDigits ? parseInt(rawDigits, 10) : 0;
        const formattedPrice = new Intl.NumberFormat("id-ID").format(unitPrice);
        pgNominalInput.value = rawDigits ? formattedPrice : "";

        const qty = pgJumlahInput ? (parseInt(pgJumlahInput.value, 10) || 1) : 1;
        const total = qty * unitPrice;
        const formattedTotal = new Intl.NumberFormat("id-ID").format(total);

        if (pgNominalPreview) {
            if (unitPrice > 0) {
                pgNominalPreview.innerHTML = `<span>Total Pengeluaran:</span> <span style="display: inline-flex; align-items: baseline; gap: 6px;"><strong style="color: #ef4444; font-size: 0.95rem;">Rp ${formattedTotal}</strong> <span style="font-weight: 500; font-size: 0.74rem; color: var(--text-muted);">(${qty} item &times; Rp ${formattedPrice})</span></span>`;
            } else {
                pgNominalPreview.innerHTML = `<span>Total Pengeluaran:</span> <strong style="color: var(--text-muted); font-size: 0.92rem;">Rp 0</strong>`;
            }
        }
    }

    if (pgNominalInput) {
        pgNominalInput.addEventListener("input", updatePengeluaranCalc);
    }
    if (pgJumlahInput) {
        pgJumlahInput.addEventListener("input", updatePengeluaranCalc);
        pgJumlahInput.addEventListener("change", updatePengeluaranCalc);
    }

    const formTambahPengeluaran = document.getElementById("formTambahPengeluaran");
    if (formTambahPengeluaran) {
        formTambahPengeluaran.addEventListener("submit", async function (e) {
            e.preventDefault();
            const btnSubmit = document.getElementById("pgSubmit") || formTambahPengeluaran.querySelector('button[type="submit"]');
            const originalText = btnSubmit ? btnSubmit.innerHTML : "";
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
            }

            const formData = new FormData(formTambahPengeluaran);
            // Pastikan nominal yang dikirim adalah angka murni
            const rawNom = (formData.get("nominal") || "").toString().replace(/[^0-9]/g, "");
            formData.set("nominal", rawNom);

            try {
                const res = await fetch(formTambahPengeluaran.action, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    },
                    body: formData,
                });
                const data = await res.json();
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = originalText;
                }

                if (res.ok && data.success) {
                    closeTambahPengeluaran();
                    Swal.fire({
                        icon: "success",
                        title: "Berhasil!",
                        text: data.message,
                        timer: 1600,
                        showConfirmButton: false,
                    }).then(() => {
                        if (typeof window.refreshLiveTable === "function") {
                            window.refreshLiveTable(window.location.href);
                        } else {
                            window.location.reload();
                        }
                    });
                } else {
                    const errorMsg = data.errors
                        ? Object.values(data.errors).flat().join("<br>")
                        : (data.message || "Gagal menyimpan pengeluaran.");
                    Swal.fire({
                        icon: "error",
                        title: "Gagal Menyimpan",
                        html: errorMsg,
                    });
                }
            } catch (err) {
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = originalText;
                }
                Swal.fire("Error", (err && err.message) ? err.message : "Gagal menghubungi server.", "error");
            }
        });
    }

    // =========================================================================
    // 11B. Modal & Form Edit Pengeluaran (AJAX + Auto-format Rupiah & Perkalian Total)
    // =========================================================================
    const editPgNominalInput = document.getElementById("editPgNominal");
    const editPgJumlahInput = document.getElementById("editPgJumlah");
    const editPgNominalPreview = document.getElementById("editPgNominalPreview");

    function updateEditPengeluaranCalc() {
        if (!editPgNominalInput) return;
        const rawDigits = String(editPgNominalInput.value || "").replace(/[^0-9]/g, "");
        const unitPrice = rawDigits ? parseInt(rawDigits, 10) : 0;
        const formattedPrice = new Intl.NumberFormat("id-ID").format(unitPrice);
        editPgNominalInput.value = rawDigits ? formattedPrice : "";

        const qty = editPgJumlahInput ? (parseInt(editPgJumlahInput.value, 10) || 1) : 1;
        const total = qty * unitPrice;
        const formattedTotal = new Intl.NumberFormat("id-ID").format(total);

        if (editPgNominalPreview) {
            if (unitPrice > 0) {
                editPgNominalPreview.innerHTML = `<span>Total Pengeluaran:</span> <span style="display: inline-flex; align-items: baseline; gap: 6px;"><strong style="color: #ef4444; font-size: 0.95rem;">Rp ${formattedTotal}</strong> <span style="font-weight: 500; font-size: 0.74rem; color: var(--text-muted);">(${qty} item &times; Rp ${formattedPrice})</span></span>`;
            } else {
                editPgNominalPreview.innerHTML = `<span>Total Pengeluaran:</span> <strong style="color: var(--text-muted); font-size: 0.92rem;">Rp 0</strong>`;
            }
        }
    }

    if (editPgNominalInput) {
        editPgNominalInput.addEventListener("input", updateEditPengeluaranCalc);
    }
    if (editPgJumlahInput) {
        editPgJumlahInput.addEventListener("input", updateEditPengeluaranCalc);
        editPgJumlahInput.addEventListener("change", updateEditPengeluaranCalc);
    }

    const formEditPengeluaran = document.getElementById("formEditPengeluaran");
    if (formEditPengeluaran) {
        formEditPengeluaran.addEventListener("submit", async function (e) {
            e.preventDefault();
            const btnSubmit = document.getElementById("editPgSubmit") || formEditPengeluaran.querySelector('button[type="submit"]');
            const originalText = btnSubmit ? btnSubmit.innerHTML : "";
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memperbarui...';
            }

            const formData = new FormData(formEditPengeluaran);
            const rawNom = (formData.get("nominal") || "").toString().replace(/[^0-9]/g, "");
            formData.set("nominal", rawNom);

            try {
                const res = await fetch(formEditPengeluaran.action, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    },
                    body: formData,
                });
                const data = await res.json();
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = originalText;
                }

                if (res.ok && data.success) {
                    closeEditPengeluaran();
                    Swal.fire({
                        icon: "success",
                        title: "Berhasil!",
                        text: data.message,
                        timer: 1600,
                        showConfirmButton: false,
                    }).then(() => {
                        if (typeof window.refreshLiveTable === "function") {
                            window.refreshLiveTable(window.location.href);
                        } else {
                            window.location.reload();
                        }
                    });
                } else {
                    const errorMsg = data.errors
                        ? Object.values(data.errors).flat().join("<br>")
                        : (data.message || "Gagal memperbarui pengeluaran.");
                    Swal.fire({
                        icon: "error",
                        title: "Gagal Memperbarui",
                        html: errorMsg,
                    });
                }
            } catch (err) {
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = originalText;
                }
                Swal.fire("Error", (err && err.message) ? err.message : "Gagal menghubungi server.", "error");
            }
        });
    }

    // Delegated handler: delete pengeluaran -> AJAX dengan SweetAlert
    document.addEventListener("submit", async function (e) {
        const form = e.target;
        if (form && (form.matches('form.delete-pengeluaran-form') || form.matches('form[action*="destroy-pengeluaran"]'))) {
            e.preventDefault();
            const result = await Swal.fire({
                title: "Hapus Pengeluaran?",
                text: "Catatan belanja/pengeluaran ini akan dihapus permanen.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#ef4444",
                cancelButtonColor: "#64748b",
                confirmButtonText: "Ya, Hapus!",
                cancelButtonText: "Batal",
            });
            if (!result.isConfirmed) return;

            try {
                const res = await fetch(form.action, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    },
                    body: JSON.stringify({ _token: csrfToken, _method: "DELETE" }),
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    if (window.SAE && typeof window.SAE.toast === "function") {
                        window.SAE.toast(data.message, "success");
                    }
                    if (typeof window.refreshLiveTable === "function") {
                        window.refreshLiveTable(window.location.href);
                    } else {
                        window.location.reload();
                    }
                } else {
                    Swal.fire("Gagal", data.message || "Gagal menghapus pengeluaran.", "error");
                }
            } catch (err) {
                Swal.fire("Error", (err && err.message) ? err.message : "Gagal menghubungi server.", "error");
            }
        }
    });

    // =========================================================================
    // 11C. Datatable Catatan Belanja: state mandiri (prefix belanja_) via refreshLiveTable
    // =========================================================================
    function refreshBelanja(mutate) {
        const url = new URL(window.location.href);
        mutate(url.searchParams);
        url.searchParams.delete("belanja_page");
        if (typeof window.refreshLiveTable === "function") {
            window.refreshLiveTable(url.toString());
        } else {
            window.location.href = url.toString();
        }
    }

    let belanjaTimer = null;
    document.addEventListener("input", function (e) {
        if (e.target.id !== "belanjaSearch") return;
        const val = e.target.value.trim();
        e.target.closest(".live-search-wrap")?.querySelector(".clear-search")?.classList.toggle("visible", val.length > 0);
        clearTimeout(belanjaTimer);
        belanjaTimer = setTimeout(() => {
            refreshBelanja((p) => (val ? p.set("belanja_q", val) : p.delete("belanja_q")));
        }, 400);
    });

    document.addEventListener("keydown", function (e) {
        if (e.key !== "Enter" || e.target.id !== "belanjaSearch") return;
        e.preventDefault();
        clearTimeout(belanjaTimer);
        const val = e.target.value.trim();
        refreshBelanja((p) => (val ? p.set("belanja_q", val) : p.delete("belanja_q")));
    });

    document.addEventListener("change", function (e) {
        if (e.target.id !== "belanjaPerPage") return;
        refreshBelanja((p) => p.set("belanja_per_page", e.target.value));
    });

    // Capture phase agar handler global (.clear-search / .sortable-th) tidak ikut menimpa state tabel lain
    document.addEventListener("click", function (e) {
        const clearBtn = e.target.closest(".clear-search");
        const belanjaInput = clearBtn?.closest(".live-search-wrap")?.querySelector("#belanjaSearch");
        if (belanjaInput) {
            e.preventDefault();
            e.stopImmediatePropagation();
            belanjaInput.value = "";
            clearBtn.classList.remove("visible");
            refreshBelanja((p) => p.delete("belanja_q"));
            return;
        }

        const th = e.target.closest("th[data-belanja-sort]");
        if (th) {
            e.preventDefault();
            e.stopImmediatePropagation();
            const field = th.dataset.belanjaSort;
            refreshBelanja((p) => {
                const asc = p.get("belanja_sort") === field && p.get("belanja_dir") === "asc";
                p.set("belanja_sort", field);
                p.set("belanja_dir", asc ? "desc" : "asc");
            });
        }
    }, true);

    // Close modals on escape key
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            closeEditPembayaran();
            closeModalWa();
            closeKlaimGaransi();
            closeModalBulkPembayaran();
            closeModalCetakPengambilan();
            closeTambahPengeluaran();
            closeEditPengeluaran();
            closeTambahRequest();
        }
    });
});
