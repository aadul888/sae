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
        href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
    <link rel="stylesheet"
        href="{{ asset('css/kartu-pelajar.css') }}?v={{ file_exists(public_path('css/kartu-pelajar.css')) ? filemtime(public_path('css/kartu-pelajar.css')) : time() }}">
    <link rel="stylesheet"
        href="{{ asset('css/dashboard.css') }}?v={{ file_exists(public_path('css/dashboard.css')) ? filemtime(public_path('css/dashboard.css')) : time() }}">
</head>

<body>

    <!-- Sticky Floating Toolbar (Non-Printable) -->
    <div class="no-print-bar">
        <a href="javascript:if(window.history.length>1){window.history.back();}else{window.close();}"
            class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Tutup
        </a>

        <div class="orientation-switch">
            <select id="sideFilter" class="select-cetak" onchange="changeSideFilter(this.value)"
                title="Pilih Tampilan Sisi Kartu">
                <option value="both">Depan &amp; Belakang</option>
                <option value="front-only">Halaman Depan Saja</option>
                <option value="back-only">Halaman Belakang Saja</option>
            </select>
        </div>

        <button type="button" onclick="window.SAE_Print ? window.SAE_Print() : window.print()"
            class="btn-action btn-print">
            <i class="fas fa-print"></i> Cetak Kartu: {{ $rombel->nama }}
        </button>

        <button type="button" id="btnUnduhSemuaJpg" onclick="unduhSemuaKartuRombelZip()" class="btn-action"
            style="background: #059669 !important; color: #ffffff !important;">
            <i class="fas fa-file-zipper"></i> Unduh Semua JPG (.zip)
        </button>
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
        src="{{ asset('js/cetak.js') }}?v={{ file_exists(public_path('js/cetak.js')) ? filemtime(public_path('js/cetak.js')) : time() }}">
    </script>
    <script
        src="{{ asset('js/kartu-pelajar-cetak.js') }}?v={{ file_exists(public_path('js/kartu-pelajar-cetak.js')) ? filemtime(public_path('js/kartu-pelajar-cetak.js')) : time() }}">
    </script>
</body>

</html>
