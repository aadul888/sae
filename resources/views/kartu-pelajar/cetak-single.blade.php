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
        href="{{ asset('css/kartu-pelajar.css') }}?v={{ file_exists(public_path('css/kartu-pelajar.css')) ? filemtime(public_path('css/kartu-pelajar.css')) : time() }}">
    <link rel="stylesheet"
        href="{{ asset('css/dashboard.css') }}?v={{ file_exists(public_path('css/dashboard.css')) ? filemtime(public_path('css/dashboard.css')) : time() }}">
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

    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/html2canvas.min.js') }}"></script>
    <script
        src="{{ asset('js/kartu-pelajar-cetak.js') }}?v={{ file_exists(public_path('js/kartu-pelajar-cetak.js')) ? filemtime(public_path('js/kartu-pelajar-cetak.js')) : time() }}">
    </script>
</body>

</html>
