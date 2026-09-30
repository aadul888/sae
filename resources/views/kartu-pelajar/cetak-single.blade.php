<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kartu Pelajar — {{ $card['nama'] }}</title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">

    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">

    <link rel="stylesheet" href="{{ asset('css/kartu-pelajar.css') }}">

    <style>
        body {
            background-color: #f1f5f9;
            margin: 0;
            padding: 24px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #0f172a;
        }

        .action-bar {
            max-width: 820px;
            margin: 0 auto 24px;
            background: #ffffff;
            padding: 14px 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
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
            transition: background 0.15s;
        }

        .btn-print:hover {
            background: #0369a1;
        }

        .btn-back {
            color: #64748b;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .filter-select {
            padding: 7px 12px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            font-size: 0.82rem;
            font-weight: 600;
            color: #1e293b;
            background-color: #ffffff;
            cursor: pointer;
        }

        .print-canvas {
            display: flex;
            justify-content: center;
            align-items: center;
        }

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

            .action-bar {
                display: none !important;
            }
        }
    </style>
</head>

<body>

    <div class="action-bar no-print">
        <a href="javascript:window.close();" class="btn-back"><i class="fas fa-arrow-left"></i> Tutup Jendela</a>

        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <label for="sideFilter" style="font-size: 0.8rem; font-weight: 700; color: #475569;">Sisi Kartu:</label>
            <select id="sideFilter" class="filter-select" onchange="changeSideFilter(this.value)">
                <option value="both">Depan &amp; Belakang</option>
                <option value="front-only">Depan Saja</option>
                <option value="back-only">Belakang Saja</option>
            </select>

            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print"></i> Cetak Kartu Sekarang
            </button>

            <div style="position: relative; display: inline-block;" id="dropdownUnduhJpgWrap">
                <button type="button" id="btnUnduhJpg" onclick="toggleUnduhJpgDropdown(event)" class="btn-print"
                    style="background: #059669; border-color: #059669;">
                    <i class="fas fa-file-image"></i> Unduh JPG <i class="fas fa-chevron-down"
                        style="font-size: 0.7rem; margin-left: 3px;"></i>
                </button>
                <div id="menuUnduhJpg"
                    style="display: none; position: absolute; top: calc(100% + 6px); right: 0; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15); min-width: 190px; padding: 6px 0; z-index: 1000;"
                    onclick="event.stopPropagation()">
                    <button type="button" onclick="unduhHalamanSingleJpg('both')"
                        style="width: 100%; text-align: left; padding: 8px 14px; background: transparent; border: none; font-size: 0.82rem; font-weight: 600; color: #1e293b; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-images" style="color: #059669; width: 16px;"></i> Depan &amp; Belakang (2 JPG)
                    </button>
                    <button type="button" onclick="unduhHalamanSingleJpg('front')"
                        style="width: 100%; text-align: left; padding: 8px 14px; background: transparent; border: none; font-size: 0.82rem; font-weight: 600; color: #1e293b; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-image" style="color: #0284c7; width: 16px;"></i> Sisi Depan Saja (.jpg)
                    </button>
                    <button type="button" onclick="unduhHalamanSingleJpg('back')"
                        style="width: 100%; text-align: left; padding: 8px 14px; background: transparent; border: none; font-size: 0.82rem; font-weight: 600; color: #1e293b; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-image" style="color: #64748b; width: 16px;"></i> Sisi Belakang Saja (.jpg)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="print-canvas">
        @include('kartu-pelajar.template', ['card' => $card])
    </div>

    <script src="{{ asset('js/html2canvas.min.js') }}"></script>
    <script>
        function changeSideFilter(val) {
            document.body.classList.remove('filter-front-only', 'filter-back-only');
            if (val === 'front-only') {
                document.body.classList.add('filter-front-only');
            } else if (val === 'back-only') {
                document.body.classList.add('filter-back-only');
            }
        }

        function toggleUnduhJpgDropdown(e) {
            if (e) e.stopPropagation();
            const m = document.getElementById('menuUnduhJpg');
            if (!m) return;
            m.style.display = (m.style.display === 'none' || m.style.display === '') ? 'block' : 'none';
        }

        document.addEventListener('click', function(e) {
            const wrap = document.getElementById('dropdownUnduhJpgWrap');
            if (wrap && !wrap.contains(e.target)) {
                const m = document.getElementById('menuUnduhJpg');
                if (m) m.style.display = 'none';
            }
        });

        async function unduhHalamanSingleJpg(mode) {
            const m = document.getElementById('menuUnduhJpg');
            if (m) m.style.display = 'none';

            const btn = document.getElementById('btnUnduhJpg');
            const orig = btn ? btn.innerHTML : '';
            if (btn) {
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyiapkan...';
                btn.disabled = true;
            }

            const captureCardToJpg = async (cardEl, sideName) => {
                if (!cardEl) return false;
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

                    await new Promise(r => setTimeout(r, 120));

                    const canvas = await html2canvas(clone, {
                        scale: 3, // 300 DPI high resolution
                        useCORS: true,
                        allowTaint: true,
                        backgroundColor: '#ffffff',
                        logging: false,
                        width: clone.offsetWidth,
                        height: clone.offsetHeight
                    });

                    const link = document.createElement('a');
                    link.download = `Kartu_Pelajar_{{ $card['nisn'] }}_${sideName}.jpg`;
                    link.href = canvas.toDataURL('image/jpeg', 0.95);
                    link.click();
                    return true;
                } catch (err) {
                    console.error('Error saat capture JPG:', err);
                    return false;
                } finally {
                    if (staging && staging.parentNode) {
                        staging.parentNode.removeChild(staging);
                    }
                }
            };

            const frontEl = document.querySelector('.print-canvas .kp-card-front');
            const backEl = document.querySelector('.print-canvas .kp-card-back');

            try {
                if (mode === 'front') {
                    await captureCardToJpg(frontEl, 'DEPAN');
                } else if (mode === 'back') {
                    await captureCardToJpg(backEl, 'BELAKANG');
                } else if (mode === 'both') {
                    await captureCardToJpg(frontEl, 'DEPAN');
                    await new Promise(r => setTimeout(r, 400));
                    await captureCardToJpg(backEl, 'BELAKANG');
                }
            } catch (err) {
                console.error(err);
                alert('Gagal mengunduh kartu dalam format JPG: ' + (err.message || 'Error'));
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
