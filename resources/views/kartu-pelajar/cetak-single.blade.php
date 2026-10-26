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
    <link rel="stylesheet"
        href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
    <link rel="stylesheet"
        href="{{ asset('css/kartu-pelajar.css') }}?v={{ file_exists(public_path('css/kartu-pelajar.css')) ? filemtime(public_path('css/kartu-pelajar.css')) : time() }}">
    <link rel="stylesheet"
        href="{{ asset('css/dashboard.css') }}?v={{ file_exists(public_path('css/dashboard.css')) ? filemtime(public_path('css/dashboard.css')) : time() }}">
</head>

<body class="cetak-single-body" style="background: #475569; min-height: 100vh; margin: 0; padding: 0;">

    <div class="no-print-bar">
        <a href="javascript:if(window.history.length>1){window.history.back();}else{window.close();}"
            class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Tutup Jendela
        </a>

        <div class="orientation-switch">
            <select id="sideFilter" class="select-cetak" onchange="changeSideFilter(this.value)"
                title="Pilih Tampilan Sisi Kartu">
                <option value="both">Depan &amp; Belakang</option>
                <option value="front-only">Depan Saja</option>
                <option value="back-only">Belakang Saja</option>
            </select>
        </div>

        <button type="button" onclick="window.SAE_Print ? window.SAE_Print() : window.print()"
            class="btn-action btn-print">
            <i class="fas fa-print"></i> Cetak Kartu Sekarang
        </button>

        <div style="position: relative; display: inline-block;" id="dropdownUnduhJpgWrap">
            <button type="button" id="btnUnduhJpg" onclick="toggleUnduhJpgDropdown(event)" class="btn-action"
                style="background: var(--success, #059669) !important; color: #ffffff !important;">
                <i class="fas fa-file-image"></i> Unduh JPG <i class="fas fa-chevron-down"
                    style="font-size: var(--text-xs, 0.75rem); margin-left: 3px;"></i>
            </button>
            <div id="menuUnduhJpg"
                style="display: none; position: absolute; top: calc(100% + 8px); right: 0; background: var(--text-main, #0f172a); border: 1px solid rgba(255,255,255,0.15); border-radius: var(--radius-lg, 12px); box-shadow: 0 10px 25px rgba(0,0,0,0.4); min-width: 200px; padding: 6px 0; z-index: 1000;"
                onclick="event.stopPropagation()">
                <button type="button" onclick="unduhHalamanSingleJpg('both')"
                    style="width: 100%; text-align: left; padding: 8px 14px; background: transparent; border: none; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: #f8fafc; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-images" style="color: var(--success, #10b981); width: 16px;"></i> Depan &amp;
                    Belakang (2 JPG)
                </button>
                <button type="button" onclick="unduhHalamanSingleJpg('front')"
                    style="width: 100%; text-align: left; padding: 8px 14px; background: transparent; border: none; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: #f8fafc; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-image" style="color: #38bdf8; width: 16px;"></i> Sisi Depan Saja (.jpg)
                </button>
                <button type="button" onclick="unduhHalamanSingleJpg('back')"
                    style="width: 100%; text-align: left; padding: 8px 14px; background: transparent; border: none; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: #f8fafc; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-image" style="color: var(--text-muted, #94a3b8); width: 16px;"></i> Sisi Belakang
                    Saja (.jpg)
                </button>
            </div>
        </div>
    </div>

    <!-- Container Cetak Kartu: 1 Lembar A4 Landscape Presisi, Posisi Kartu di Sebelah Kanan -->
    <div class="kp-sheet-wrapper kp-single-sheet-wrapper">
        <div class="kp-a4-sheet kp-single-a4-sheet" data-page="1">
            <!-- Baris Atas: Sisi Depan Kartu (Di Sebelah Kanan) -->
            <div class="kp-sheet-row kp-row-front kp-row-single">
                @include('kartu-pelajar.card-front', ['card' => $card])
            </div>

            <!-- Jarak Kosong & Garis Panduan Potong Tengah Manual (12mm) -->
            <div class="kp-sheet-cut-gap">
                <div class="kp-cut-line"></div>
            </div>

            <!-- Baris Bawah: Sisi Belakang Kartu (Di Sebelah Kanan) -->
            <div class="kp-sheet-row kp-row-back kp-row-single">
                @include('kartu-pelajar.card-back', ['card' => $card])
            </div>
        </div>
    </div>

    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/html2canvas.min.js') }}"></script>
    <script
        src="{{ asset('js/cetak.js') }}?v={{ file_exists(public_path('js/cetak.js')) ? filemtime(public_path('js/cetak.js')) : time() }}">
    </script>
    <script
        src="{{ asset('js/kartu-pelajar-cetak.js') }}?v={{ file_exists(public_path('js/kartu-pelajar-cetak.js')) ? filemtime(public_path('js/kartu-pelajar-cetak.js')) : time() }}">
    </script>
</body>

</html>
