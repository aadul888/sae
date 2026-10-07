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
    <link rel="stylesheet"
        href="{{ asset('css/kartu-pelajar.css') }}?v={{ file_exists(public_path('css/kartu-pelajar.css')) ? filemtime(public_path('css/kartu-pelajar.css')) : time() }}">
    <link rel="stylesheet"
        href="{{ asset('css/dashboard.css') }}?v={{ file_exists(public_path('css/dashboard.css')) ? filemtime(public_path('css/dashboard.css')) : time() }}">
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
    <script
        src="{{ asset('js/kartu-pelajar-cetak.js') }}?v={{ file_exists(public_path('js/kartu-pelajar-cetak.js')) ? filemtime(public_path('js/kartu-pelajar-cetak.js')) : time() }}">
    </script>
</body>

</html>
