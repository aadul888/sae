/**
 * Penyimpan gelar GTK bersama (Guru & Tendik) — Manajemen Data.
 *
 * Endpoint dan pembacaan input diserahkan pemanggil, sehingga satu fungsi
 * dipakai kedua menu. Gelar disimpan ke `gtk_meta` (aman dari tarik Dapodik).
 */
(function () {
    function showGelarToast(message, type) {
        let toast = document.getElementById("gtkGelarToast");
        if (!toast) {
            toast = document.createElement("div");
            toast.id = "gtkGelarToast";
            document.body.appendChild(toast);
        }

        const ok = type === "success";
        toast.textContent = message;
        toast.style.cssText = [
            "position: fixed",
            "bottom: 24px",
            "right: 24px",
            "z-index: 2000",
            "padding: 11px 16px",
            "border-radius: 10px",
            "font-size: 0.875rem",
            "font-weight: 600",
            "color: #fff",
            "box-shadow: 0 8px 24px rgba(0,0,0,0.25)",
            "background: " + (ok ? "#10b981" : "#ef4444"),
            "opacity: 0",
            "transition: opacity 0.2s ease",
        ].join(";");

        requestAnimationFrame(() => {
            toast.style.opacity = "1";
        });

        clearTimeout(window.__gtkGelarToastTimer);
        window.__gtkGelarToastTimer = setTimeout(() => {
            toast.style.opacity = "0";
        }, 2600);
    }

    /**
     * @param {Object} options
     * @param {string} options.ptkId
     * @param {string} options.endpoint
     * @param {HTMLElement|null} [options.btn]
     * @param {() => string} options.getDepan
     * @param {() => string} options.getBelakang
     * @param {(data: Object) => void} [options.onSuccess]
     * @returns {Promise<boolean>} true bila tersimpan
     */
    window.saveGtkGelar = async function (options) {
        const { ptkId, endpoint, btn, getDepan, getBelakang, onSuccess } = options;

        if (!ptkId) {
            showGelarToast("Data GTK tidak dikenali.", "error");
            return false;
        }

        const originalHtml = btn ? btn.innerHTML : "";
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
        }

        const csrfToken =
            document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

        try {
            const res = await fetch(endpoint, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({
                    gelar_depan: (getDepan() || "").trim(),
                    gelar_belakang: (getBelakang() || "").trim(),
                }),
            });

            const json = await res.json().catch(() => ({}));

            if (!res.ok || json.status !== "success") {
                throw new Error(json.message || "Gagal menyimpan gelar.");
            }

            showGelarToast("Gelar berhasil disimpan.", "success");
            if (typeof onSuccess === "function") onSuccess(json.data || {});
            return true;
        } catch (e) {
            showGelarToast(e.message || "Gagal menyimpan gelar.", "error");
            return false;
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        }
    };

    /** Susun nama lengkap ber-gelar, mis. "Drs. Budi Santoso, M.Pd.". */
    window.gtkNamaLengkap = function (nama, depan, belakang) {
        return (
            [depan, nama, belakang ? ", " + belakang : null]
                .filter(Boolean)
                .join(" ") || "-"
        );
    };
})();
