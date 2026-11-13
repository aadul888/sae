// =========================================================================
// Laboran — Log Presensi: modal cetak multi-periode & snapshot foto
// =========================================================================
(function () {
    "use strict";

    // -------------------------------------------------------------------------
    // 1. Snapshot Foto Masuk / Pulang Modal
    // -------------------------------------------------------------------------
    function closeSnapshot() {
        var modal = document.getElementById("modalFotoSnapshot");
        if (!modal) return;
        modal.style.display = "none";
        document.body.style.overflow = "";

        var img = document.getElementById("imgFotoSnapshot");
        if (img) img.src = "";
    }

    // -------------------------------------------------------------------------
    // 2. Modal Cetak Laporan Presensi (Kelas / Murid x Periode)
    // -------------------------------------------------------------------------
    function openModalCetak() {
        var modal = document.getElementById("modalCetakPresensi");
        if (!modal) return;
        modal.style.display = "flex";
        document.body.style.overflow = "hidden";
        syncTipeLaporan();
        syncPeriode();
    }

    function closeModalCetak() {
        var modal = document.getElementById("modalCetakPresensi");
        if (!modal) return;
        modal.style.display = "none";
        document.body.style.overflow = "";
    }

    function syncTipeLaporan() {
        var isMurid = document.getElementById("radioTipeMurid")?.checked;
        var groupPilihSiswa = document.getElementById("groupPilihSiswa");
        var groupModeTampilan = document.getElementById("groupModeTampilan");

        if (groupPilihSiswa) {
            groupPilihSiswa.style.display = isMurid ? "block" : "none";
        }
        if (groupModeTampilan) {
            // Per murid selalu mode lengkap; mode ringkasan/lengkap hanya untuk per kelas
            groupModeTampilan.style.display = isMurid ? "none" : "block";
        }

        if (isMurid) {
            loadSiswaOptions();
        }
    }

    function syncPeriode() {
        var form = document.getElementById("formCetakPresensi");
        if (!form) return;
        var periodeVal = form.querySelector('input[name="periode"]:checked')?.value || "hari";

        var fHari = document.getElementById("fieldPeriodeHari");
        var fBulan = document.getElementById("fieldPeriodeBulan");
        var fSemester = document.getElementById("fieldPeriodeSemester");
        var fTahun = document.getElementById("fieldPeriodeTahun");

        if (fHari) fHari.style.display = (periodeVal === "hari") ? "block" : "none";
        if (fBulan) fBulan.style.display = (periodeVal === "bulan") ? "block" : "none";
        if (fSemester) fSemester.style.display = (periodeVal === "semester") ? "block" : "none";
        if (fTahun) fTahun.style.display = (periodeVal === "tahun") ? "block" : "none";
    }

    var _siswaCache = {};
    function loadSiswaOptions() {
        var rombelSelect = document.getElementById("modalCetakRombelId");
        var siswaSelect = document.getElementById("modalCetakSiswaId");
        var loader = document.getElementById("siswaLoadingHelp");
        if (!siswaSelect) return;

        var rombelId = rombelSelect ? rombelSelect.value : "";
        var cacheKey = "rombel_" + rombelId;

        if (_siswaCache[cacheKey]) {
            renderSiswaOptions(_siswaCache[cacheKey]);
            return;
        }

        if (loader) loader.style.display = "block";
        siswaSelect.disabled = true;

        var url = "/dashboard/laboran/log-presensi/siswa-options?rombel_id=" + encodeURIComponent(rombelId);
        fetch(url, { credentials: "same-origin", headers: { "X-Requested-With": "XMLHttpRequest" } })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                _siswaCache[cacheKey] = data;
                renderSiswaOptions(data);
            })
            .catch(function () {
                renderSiswaOptions([]);
            })
            .finally(function () {
                if (loader) loader.style.display = "none";
                siswaSelect.disabled = false;
            });
    }

    function renderSiswaOptions(list) {
        var select = document.getElementById("modalCetakSiswaId");
        if (!select) return;
        select.innerHTML = '<option value="">-- Pilih Siswa --</option>';

        (list || []).forEach(function (s) {
            var opt = document.createElement("option");
            opt.value = s.peserta_didik_id;
            var label = s.nama;
            if (s.nama_rombel) label += " (" + s.nama_rombel + ")";
            if (s.nisn) label += " — NISN: " + s.nisn;
            opt.textContent = label;
            select.appendChild(opt);
        });
    }

    // -------------------------------------------------------------------------
    // 3. Event Listeners
    // -------------------------------------------------------------------------
    document.addEventListener("DOMContentLoaded", function () {
        var btnOpen = document.getElementById("btnOpenModalCetak");
        if (btnOpen) {
            btnOpen.addEventListener("click", function (e) {
                e.preventDefault();
                openModalCetak();
            });
        }

        var btnClose = document.getElementById("btnCloseModalCetak");
        var btnCancel = document.getElementById("btnCancelModalCetak");
        if (btnClose) btnClose.addEventListener("click", closeModalCetak);
        if (btnCancel) btnCancel.addEventListener("click", closeModalCetak);

        var radioKelas = document.getElementById("radioTipeKelas");
        var radioMurid = document.getElementById("radioTipeMurid");
        if (radioKelas) radioKelas.addEventListener("change", syncTipeLaporan);
        if (radioMurid) radioMurid.addEventListener("change", syncTipeLaporan);

        var modalRombel = document.getElementById("modalCetakRombelId");
        if (modalRombel) {
            modalRombel.addEventListener("change", function () {
                if (document.getElementById("radioTipeMurid")?.checked) {
                    loadSiswaOptions();
                }
            });
        }

        var formCetak = document.getElementById("formCetakPresensi");
        if (formCetak) {
            formCetak.querySelectorAll('input[name="periode"]').forEach(function (r) {
                r.addEventListener("change", syncPeriode);
            });

            formCetak.addEventListener("submit", function (e) {
                var isMurid = document.getElementById("radioTipeMurid")?.checked;
                var siswaSelect = document.getElementById("modalCetakSiswaId");
                if (isMurid && (!siswaSelect || !siswaSelect.value)) {
                    e.preventDefault();
                    alert("Harap pilih nama peserta didik terlebih dahulu.");
                    if (siswaSelect) siswaSelect.focus();
                }
            });
        }
    });

    document.addEventListener("click", function (e) {
        var trigger = e.target.closest(".btn-preview-snapshot");
        if (trigger) {
            e.preventDefault();

            var url = trigger.getAttribute("data-url");
            var title = trigger.getAttribute("data-title") || "Foto Bukti Presensi";
            var modal = document.getElementById("modalFotoSnapshot");
            var img = document.getElementById("imgFotoSnapshot");
            var heading = document.getElementById("fotoSnapshotTitle");

            if (!url || !modal || !img) return;

            img.src = "";
            img.src = url + (url.indexOf("?") === -1 ? "?" : "&") + "t=" + Date.now();
            if (heading) heading.textContent = title;

            modal.style.display = "flex";
            document.body.style.overflow = "hidden";
            return;
        }

        if (e.target.closest("#btnCloseFotoModal")) {
            e.preventDefault();
            closeSnapshot();
            return;
        }

        var backdropFoto = document.getElementById("modalFotoSnapshot");
        if (backdropFoto && e.target === backdropFoto) {
            closeSnapshot();
        }

        var backdropCetak = document.getElementById("modalCetakPresensi");
        if (backdropCetak && e.target === backdropCetak) {
            closeModalCetak();
        }
    });

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            closeSnapshot();
            closeModalCetak();
        }
    });
})();
