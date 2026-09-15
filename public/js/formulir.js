/**
 * Formulir & Survei — SAE Core JavaScript Module
 * Digunakan pada Formulir Index & Tanggapan / Responses
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Inisialisasi Event Delegation SweetAlert2 untuk tombol Delete
    initDeleteConfirmations();

    // 2. Inisialisasi Modal Tanggapan & Escape Key Handler
    initResponseModal();

    // 3. Inisialisasi Live Search Respon Formulir
    initResponsesLiveSearch();

    // 4. Inisialisasi Per-Page Selector Respon Formulir
    initResponsesPerPage();
});

/**
 * Salin tautan publik formulir ke clipboard dengan notifikasi toast
 */
window.copyFormLink = function (url) {
    if (!url) return;

    const doSuccess = () => {
        if (typeof Swal !== 'undefined') {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
                background: document.documentElement.getAttribute('data-theme') === 'light' ? '#ffffff' : '#1e293b',
                color: document.documentElement.getAttribute('data-theme') === 'light' ? '#0f172a' : '#f8fafc'
            });
            Toast.fire({
                icon: 'success',
                title: 'Tautan formulir berhasil disalin!'
            });
        } else {
            const toast = document.getElementById('copyToast');
            if (toast) {
                toast.style.display = 'flex';
                setTimeout(() => { toast.style.display = 'none'; }, 2500);
            }
        }
    };

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(url).then(doSuccess).catch(() => fallbackCopy(url, doSuccess));
    } else {
        fallbackCopy(url, doSuccess);
    }
};

function fallbackCopy(text, onSuccess) {
    const tempInput = document.createElement('input');
    tempInput.value = text;
    tempInput.style.position = 'fixed';
    tempInput.style.opacity = '0';
    document.body.appendChild(tempInput);
    tempInput.select();
    try {
        document.execCommand('copy');
        if (onSuccess) onSuccess();
    } catch (err) {
        console.error('Gagal menyalin tautan:', err);
    }
    document.body.removeChild(tempInput);
}

/**
 * Konfirmasi SweetAlert2 untuk penghapusan
 */
function initDeleteConfirmations() {
    document.addEventListener('submit', function (e) {
        const form = e.target.closest('form[data-confirm="delete"]');
        if (!form) return;

        // Jika sudah dikonfirmasi secara programmatic, izinkan submit lanjut
        if (form.dataset.confirmed === 'true') return;

        e.preventDefault();
        const itemName = form.getAttribute('data-name') || 'item ini';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Konfirmasi Hapus',
                html: `Apakah Anda yakin ingin menghapus <b>${itemName}</b>?<br><small style="color: #ef4444;">Tindakan ini tidak dapat dibatalkan dan seluruh data terkait akan dihapus permanen.</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fas fa-trash me-1"></i> Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                focusCancel: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.dataset.confirmed = 'true';
                    form.submit();
                }
            });
        } else {
            if (confirm(`Apakah Anda yakin ingin menghapus ${itemName}?`)) {
                form.dataset.confirmed = 'true';
                form.submit();
            }
        }
    });
}

/**
 * Pengelolaan Modal Detail Respon
 */
function initResponseModal() {
    const modal = document.getElementById('responseModal');
    if (!modal) return;

    // Klik di luar kartu modal untuk menutup
    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            closeDetail();
        }
    });

    // Tombol ESC untuk menutup
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.style.display === 'flex') {
            closeDetail();
        }
    });
}

/**
 * Buka detail respon pada modal
 */
window.openDetail = function (data) {
    const modal = document.getElementById('responseModal');
    if (!modal) return;

    const modalResponden = document.getElementById('modalResponden');
    const modalTanggal = document.getElementById('modalTanggal');
    const modalQaList = document.getElementById('modalQaList') || document.getElementById('modalContent');

    if (modalResponden) modalResponden.textContent = data.responden || 'Anonim / Publik';
    if (modalTanggal) modalTanggal.textContent = data.tanggal || '-';

    if (modalQaList) {
        modalQaList.innerHTML = '';
        const answers = data.answers || {};

        if (Object.keys(answers).length === 0) {
            modalQaList.innerHTML = '<p class="text-muted" style="font-size: 0.85rem; font-style: italic;">Tidak ada jawaban tersimpan.</p>';
        } else {
            for (const [key, item] of Object.entries(answers)) {
                const qaDiv = document.createElement('div');
                qaDiv.className = 'qa-item';

                const qEl = document.createElement('div');
                qEl.className = 'qa-q';
                qEl.textContent = item.label || key;

                const aEl = document.createElement('div');
                aEl.className = 'qa-a';

                let val = item.value;
                if (Array.isArray(val)) {
                    val = val.join(', ');
                } else if (val === null || val === undefined || val === '') {
                    val = '- (Kosong)';
                }

                // Cek jika link berkas / file
                if (typeof val === 'string' && (val.startsWith('http://') || val.startsWith('https://') || val.startsWith('/storage/'))) {
                    aEl.innerHTML = `<a href="${val}" target="_blank" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.78rem; display: inline-flex; align-items: center; gap: 6px;"><i class="fas fa-arrow-up-right-from-square"></i> Buka Lampiran Berkas</a>`;
                } else {
                    aEl.textContent = val;
                }

                qaDiv.appendChild(qEl);
                qaDiv.appendChild(aEl);
                modalQaList.appendChild(qaDiv);
            }
        }
    }

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
};

/**
 * Buka detail respon dari objek tanggapan database & skema
 */
window.openDetailModal = function (responseObj, skema) {
    const modal = document.getElementById('responseModal');
    if (!modal) return;

    const meta = document.getElementById('modalMeta');
    const content = document.getElementById('modalContent') || document.getElementById('modalQaList');

    if (meta) {
        meta.textContent = (responseObj.nama_responden || 'Tamu') + ' • ' + (responseObj.identitas_responden || '-') +
            ' • ' + (responseObj.created_at ? new Date(responseObj.created_at).toLocaleString('id-ID') : '-');
    }

    if (content) {
        let html = '';
        const jawaban = responseObj.jawaban || {};

        (skema || []).forEach((field, i) => {
            const fieldId = field.id;
            const val = jawaban[fieldId] !== undefined ? jawaban[fieldId] : '-';
            let displayVal = val;

            if (Array.isArray(val)) {
                displayVal = val.join(', ');
            } else if (typeof val === 'string' && (val.startsWith('http://') || val.startsWith('https://')) && (
                    val.includes('/storage/formulir_uploads/'))) {
                displayVal =
                    `<a href="${val}" target="_blank" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.78rem; margin-top: 4px; display: inline-flex; align-items: center; gap: 6px;"><i class="fas fa-paperclip text-primary"></i> Buka / Unduh Berkas</a>`;
            } else if (!val || val === '') {
                displayVal = '<span class="text-muted" style="font-style: italic;">(Tidak diisi)</span>';
            }

            html += `
            <div class="qa-item">
                <div class="qa-q">${i + 1}. ${escapeHtml(field.label || fieldId)}</div>
                <div class="qa-a">${displayVal}</div>
            </div>
            `;
        });

        content.innerHTML = html || '<p class="text-muted" style="font-size: 0.85rem; font-style: italic;">Tidak ada rincian jawaban.</p>';
    }

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
};

/**
 * Tutup modal detail respon
 */
window.closeDetail = window.closeDetailModal = function () {
    const modal = document.getElementById('responseModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
};

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

/**
 * Export data respon ke CSV
 */
window.exportCsv = function (filename) {
    const table = document.getElementById('responsesTable');
    if (!table) return;

    let csv = [];
    const rows = table.querySelectorAll('tr');

    rows.forEach(row => {
        // Abaikan kolom aksi terakhir jika ada tombol aksi
        const cols = row.querySelectorAll('th, td');
        let rowData = [];
        cols.forEach((col, index) => {
            // Jika kolom terakhir berupa tombol aksi, jangan diikutkan
            if (index === cols.length - 1 && col.querySelector('.btn-action-mini, .btn, button')) {
                return;
            }
            let text = col.innerText.replace(/"/g, '""').trim();
            rowData.push('"' + text + '"');
        });
        if (rowData.length > 0) {
            csv.push(rowData.join(','));
        }
    });

    const csvContent = '\uFEFF' + csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = (filename || 'rekap-tanggapan-formulir') + '.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
};

/**
 * Live Search untuk Tabel Respon Formulir (850ms Debounce)
 */
function initResponsesLiveSearch() {
    const liveSearch = document.getElementById('liveSearch');
    const clearSearch = document.getElementById('clearSearch');
    if (!liveSearch) return;

    let debounceTimer = null;

    const applySearch = () => {
        const url = new URL(window.location.href);
        const query = liveSearch.value.trim();
        if (query) {
            url.searchParams.set('q', query);
        } else {
            url.searchParams.delete('q');
        }
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    };

    liveSearch.addEventListener('input', function () {
        if (clearSearch) {
            clearSearch.classList.toggle('visible', this.value.trim().length > 0);
        }
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(applySearch, 850);
    });

    liveSearch.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(debounceTimer);
            applySearch();
        }
    });

    if (clearSearch) {
        clearSearch.addEventListener('click', function () {
            liveSearch.value = '';
            clearSearch.classList.remove('visible');
            applySearch();
        });
    }
}

/**
 * Per-Page Selector untuk Tabel Respon Formulir
 */
function initResponsesPerPage() {
    const perPageSelect = document.getElementById('perPageSelect');
    if (!perPageSelect) return;

    perPageSelect.addEventListener('change', function () {
        const url = new URL(window.location.href);
        url.searchParams.set('perPage', this.value);
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    });
}

