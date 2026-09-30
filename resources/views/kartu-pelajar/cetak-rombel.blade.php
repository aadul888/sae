<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kartu Pelajar Masal — {{ $rombel->nama }}</title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">

    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kartu-pelajar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">

    <style>
        body {
            background-color: #f1f5f9;
            margin: 0;
            padding: 20px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #0f172a;
        }

        .action-toolbar {
            max-width: 1040px;
            margin: 0 auto 20px;
            background: #ffffff;
            padding: 14px 20px;
            border-radius: 14px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .toolbar-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .toolbar-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: #0f172a;
        }

        .toolbar-desc {
            font-size: 0.78rem;
            color: #64748b;
        }

        .toolbar-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            font-size: 0.82rem;
            font-weight: 600;
            color: #1e293b;
            background-color: #ffffff;
            cursor: pointer;
        }

        .btn-print {
            background: #0284c7;
            color: #ffffff;
            border: none;
            padding: 9px 18px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 6px rgba(2, 132, 199, 0.3);
            transition: background 0.15s;
        }

        .btn-print:hover {
            background: #0369a1;
        }

        /* Print Sheet Container (Portrait) */
        .print-grid {
            max-width: 190mm;
            margin: 0 auto;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 5mm;
        }

        /* Filter visibility states */
        body.filter-front-only .kp-card-back {
            display: none !important;
        }

        body.filter-back-only .kp-card-front {
            display: none !important;
        }

        @media print {
            body {
                background: none !important;
                padding: 0 !important;
            }

            .no-print {
                display: none !important;
            }

            .print-grid {
                max-width: 100% !important;
                margin: 0 !important;
                gap: 4mm !important;
                justify-content: flex-start !important;
            }

            .kp-card-pair {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }
    </style>
</head>

<body>

    <!-- Sticky Toolbar (Non-Printable) -->
    <div class="action-toolbar no-print">
        <div class="toolbar-info">
            <div class="toolbar-title">
                <i class="fas fa-print" style="color: #0284c7;"></i> Cetak Masal Kartu Pelajar: {{ $rombel->nama }}
            </div>
            <div class="toolbar-desc">
                Total {{ count($cards) }} Peserta Didik &bull; Standar CR-80 Portrait (54mm &times; 85.6mm)
            </div>
        </div>

        <div class="toolbar-controls">
            <label for="sideFilter" style="font-size: 0.8rem; font-weight: 700; color: #475569;">Tampilan Sisi:</label>
            <select id="sideFilter" class="filter-select" onchange="changeSideFilter(this.value)">
                <option value="both">Depan &amp; Belakang</option>
                <option value="front-only">Halaman Depan Saja</option>
                <option value="back-only">Halaman Belakang Saja</option>
            </select>

            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print"></i> Cetak Dokumen
            </button>

            <button type="button" id="btnUnduhSemuaJpg" onclick="unduhSemuaKartuRombelZip()" class="btn-print"
                style="background: #059669; border-color: #059669;">
                <i class="fas fa-file-zipper"></i> Unduh Semua JPG (.zip)
            </button>
        </div>
    </div>

    <!-- Container Cetak Kartu -->
    <div class="print-grid">
        @forelse($cards as $card)
            @include('kartu-pelajar.template', ['card' => $card, 'wrapperClass' => ''])
        @empty
            <div class="no-print"
                style="text-align: center; padding: 48px; background: #fff; border-radius: 12px; width: 100%;">
                <i class="fas fa-users-slash" style="font-size: 40px; color: #94a3b8; margin-bottom: 12px;"></i>
                <h3 style="font-size: 1.1rem; color: #1e293b;">Tidak Ada Peserta Didik di Rombel Ini</h3>
                <p style="font-size: 0.85rem; color: #64748b;">Belum ada data peserta didik yang terhubung ke rombongan
                    belajar {{ $rombel->nama }}.</p>
            </div>
        @endforelse
    </div>

    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/html2canvas.min.js') }}"></script>
    <script src="{{ asset('js/jszip.min.js') }}"></script>
    <script>
        function changeSideFilter(val) {
            document.body.classList.remove('filter-front-only', 'filter-back-only');
            if (val === 'front-only') {
                document.body.classList.add('filter-front-only');
            } else if (val === 'back-only') {
                document.body.classList.add('filter-back-only');
            }
        }

        async function unduhSemuaKartuRombelZip() {
            const sideFilter = document.getElementById('sideFilter')?.value || 'both';
            const pairs = document.querySelectorAll('.print-grid .kp-card-pair');
            if (pairs.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Data Kosong',
                    text: 'Tidak ada kartu pelajar yang dapat diunduh pada rombel ini.',
                    confirmButtonColor: '#0284c7'
                });
                return;
            }

            if (typeof JSZip === 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Pustaka ZIP Tidak Ditemukan',
                    text: 'Library JSZip belum termuat sempurna. Silakan refresh halaman.',
                    confirmButtonColor: '#ef4444'
                });
                return;
            }

            const btn = document.getElementById('btnUnduhSemuaJpg');
            const orig = btn ? btn.innerHTML : '';
            if (btn) btn.disabled = true;

            const zip = new JSZip();
            const rombelName = '{{ preg_replace('/[^a-zA-Z0-9_-]/', '_', $rombel->nama) }}';
            const zipFolder = zip.folder(`Kartu_Pelajar_${rombelName}`);

            const captureCardToBlob = async (cardEl) => {
                if (!cardEl) return null;
                let staging = null;
                try {
                    staging = document.createElement('div');
                    staging.style.position = 'fixed';
                    staging.style.left = '-9999px';
                    staging.style.top = '0';
                    staging.style.width = '204px'; // 54mm @ 96dpi
                    staging.style.height = '324px'; // 85.6mm @ 96dpi
                    staging.style.overflow = 'hidden';
                    staging.style.zIndex = '-9999';
                    staging.style.background = '#ffffff';

                    const clone = cardEl.cloneNode(true);
                    clone.classList.add('kp-card-capture-target');
                    clone.style.display = 'flex';
                    clone.style.transform = 'none';
                    clone.style.margin = '0';
                    clone.style.boxShadow = 'none';
                    staging.appendChild(clone);
                    document.body.appendChild(staging);

                    await new Promise(r => setTimeout(r, 90));

                    const canvas = await html2canvas(clone, {
                        scale: 3,
                        useCORS: true,
                        allowTaint: true,
                        backgroundColor: '#ffffff',
                        logging: false,
                        width: clone.offsetWidth,
                        height: clone.offsetHeight
                    });

                    return new Promise((resolve) => {
                        canvas.toBlob((blob) => resolve(blob), 'image/jpeg', 0.95);
                    });
                } catch (err) {
                    console.error('Error saat capture JPG:', err);
                    return null;
                } finally {
                    if (staging && staging.parentNode) {
                        staging.parentNode.removeChild(staging);
                    }
                }
            };

            let progressSwal = null;
            if (typeof Swal !== 'undefined') {
                progressSwal = Swal.fire({
                    title: 'Membuat Berkas ZIP...',
                    html: `
                        <div style="font-size: 0.85rem; color: #475569; margin-bottom: 12px;">
                            Mengonversi kartu pelajar menjadi JPG resolusi tinggi...
                        </div>
                        <div style="width: 100%; background: #e2e8f0; border-radius: 8px; height: 10px; overflow: hidden;">
                            <div id="swalZipProgressBar" style="width: 0%; height: 100%; background: #059669; transition: width 0.2s;"></div>
                        </div>
                        <div id="swalZipProgressText" style="margin-top: 8px; font-size: 0.8rem; font-weight: 700; color: #0f172a;">
                            0 / ${pairs.length} Peserta Didik
                        </div>
                    `,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            }

            try {
                let totalFiles = 0;
                const bar = document.getElementById('swalZipProgressBar');
                const txt = document.getElementById('swalZipProgressText');

                for (let i = 0; i < pairs.length; i++) {
                    const pair = pairs[i];
                    const front = pair.querySelector('.kp-card-front');
                    const back = pair.querySelector('.kp-card-back');
                    const nisn = front?.id?.replace('card-front-', '') || ('siswa_' + (i + 1));

                    const pct = Math.round(((i + 1) / pairs.length) * 100);
                    if (bar) bar.style.width = pct + '%';
                    if (txt) txt.textContent = `${i + 1} / ${pairs.length} Peserta Didik (${pct}%)`;

                    if (btn) {
                        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Memproses ${i + 1}/${pairs.length}...`;
                    }

                    if (sideFilter === 'front-only') {
                        if (front) {
                            const blob = await captureCardToBlob(front);
                            if (blob) {
                                zipFolder.file(`${String(i + 1).padStart(2, '0')}_Kartu_${nisn}_DEPAN.jpg`, blob);
                                totalFiles++;
                            }
                        }
                    } else if (sideFilter === 'back-only') {
                        if (back) {
                            const blob = await captureCardToBlob(back);
                            if (blob) {
                                zipFolder.file(`${String(i + 1).padStart(2, '0')}_Kartu_${nisn}_BELAKANG.jpg`, blob);
                                totalFiles++;
                            }
                        }
                    } else {
                        if (front) {
                            const blobFront = await captureCardToBlob(front);
                            if (blobFront) {
                                zipFolder.file(`${String(i + 1).padStart(2, '0')}_Kartu_${nisn}_DEPAN.jpg`, blobFront);
                                totalFiles++;
                            }
                        }
                        if (back) {
                            const blobBack = await captureCardToBlob(back);
                            if (blobBack) {
                                zipFolder.file(`${String(i + 1).padStart(2, '0')}_Kartu_${nisn}_BELAKANG.jpg`,
                                blobBack);
                                totalFiles++;
                            }
                        }
                    }
                }

                if (txt) txt.textContent = 'Mengompresi ke format ZIP...';
                if (btn) btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengompresi ZIP...';

                const zipContent = await zip.generateAsync({
                    type: 'blob',
                    compression: 'DEFLATE',
                    compressionOptions: {
                        level: 6
                    }
                });

                const downloadLink = document.createElement('a');
                downloadLink.href = URL.createObjectURL(zipContent);
                downloadLink.download = `Kartu_Pelajar_${rombelName}_JPG.zip`;
                downloadLink.click();
                URL.revokeObjectURL(downloadLink.href);

                // Alert selesai standar sistem
                Swal.fire({
                    title: 'Unduh Selesai!',
                    html: `Arsip ZIP berhasil dibuat.<br><strong>${totalFiles} kartu JPG</strong> dari kelas <strong>{{ $rombel->nama }}</strong> siap disimpan.`,
                    icon: 'success',
                    confirmButtonColor: '#10b981',
                    confirmButtonText: 'Selesai'
                });
            } catch (e) {
                console.error(e);
                Swal.fire({
                    title: 'Gagal Mengunduh',
                    text: 'Terjadi kesalahan sistem saat membuat arsip ZIP: ' + (e.message || 'Error'),
                    icon: 'error',
                    confirmButtonColor: '#ef4444'
                });
            } finally {
                if (btn) {
                    btn.innerHTML = orig;
                    btn.disabled = false;
                }
            }
        }
    </script>
</body>

</html>
