/**
 * Logika JavaScript Modular: Kedisiplinan Siswa (Tata Tertib, Poin, Pembinaan, Pemanggilan)
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

    // 2. Tombol Buka Modal
    const btnOpenTataTertib = document.getElementById('btnOpenTataTertibModal');
    if (btnOpenTataTertib) {
        btnOpenTataTertib.addEventListener('click', () => openModal('#modalTataTertib'));
    }

    const btnOpenPoin = document.getElementById('btnOpenPoinModal');
    if (btnOpenPoin) {
        btnOpenPoin.addEventListener('click', () => openModal('#modalPoin'));
    }

    const btnOpenPembinaan = document.getElementById('btnOpenPembinaanModal');
    if (btnOpenPembinaan) {
        btnOpenPembinaan.addEventListener('click', () => openModal('#modalPembinaan'));
    }

    const btnOpenPanggilan = document.getElementById('btnOpenPanggilanModal');
    if (btnOpenPanggilan) {
        btnOpenPanggilan.addEventListener('click', () => openModal('#modalPanggilan'));
    }

    // 3. Helper Submit Form AJAX
    function handleFormSubmit(formId, loadingText, successCallback) {
        const form = document.getElementById(formId);
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            Swal.fire({
                title: 'Memproses...',
                text: loadingText,
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
                    if (successCallback) {
                        successCallback(data);
                    } else {
                        Swal.fire('Berhasil!', data.message, 'success').then(() => location.reload());
                    }
                } else {
                    Swal.fire('Gagal!', data.message || 'Terjadi kesalahan.', 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error!', 'Gagal memproses data.', 'error');
            });
        });
    }

    // 4. Inisialisasi Handlers Form
    handleFormSubmit('formTataTertib', 'Menyimpan aturan tata tertib...');
    handleFormSubmit('formPoin', 'Mencatat pelanggaran poin siswa...');
    handleFormSubmit('formPembinaan', 'Menyimpan sesi pembinaan konseling...');

    handleFormSubmit('formPanggilan', 'Menerbitkan surat pemanggilan orang tua...', function (data) {
        Swal.fire({
            title: 'Berhasil!',
            text: data.message,
            icon: 'success',
            showCancelButton: true,
            confirmButtonText: 'Cetak Surat Panggilan',
            cancelButtonText: 'Selesai',
            confirmButtonColor: '#2563eb'
        }).then((res) => {
            if (res.isConfirmed && data.cetak_url) {
                window.open(data.cetak_url, '_blank');
            }
            location.reload();
        });
    });
});
