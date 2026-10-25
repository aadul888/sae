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

<body class="cetak-rombel-body" style="background: #475569; min-height: 100vh; margin: 0; padding: 0;">

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
            @if (!empty($filter_status_request))
                <span
                    style="font-size: var(--text-xs, 0.75rem); opacity: 0.9; font-weight: 600;">({{ ucfirst($filter_status_request) }})</span>
            @endif
        </button>

        <button type="button" id="btnUnduhSemuaJpg" onclick="unduhSemuaKartuRombelZip()" class="btn-action"
            style="background: #059669 !important; color: #ffffff !important;">
            <i class="fas fa-file-zipper"></i> Unduh Semua JPG (.zip)
        </button>
    </div>

    <!-- Container Cetak Kartu: Terbagi Presisi per Lembar A4 Landscape (5 Pasang = 10 Sisi per Lembar) -->
    @php
        $cardChunks = collect($cards)->chunk(5);
    @endphp

    @if ($cardChunks->isNotEmpty())
        <div class="kp-sheet-wrapper">
            @foreach ($cardChunks as $chunkIdx => $chunk)
                <div class="kp-a4-sheet" data-page="{{ $chunkIdx + 1 }}">
                    <!-- Baris Atas: Sisi Depan Kartu (Maksimal 5 Sisi) -->
                    <div class="kp-sheet-row kp-row-front">
                        @foreach ($chunk as $card)
                            @include('kartu-pelajar.card-front', ['card' => $card])
                        @endforeach
                    </div>

                    <!-- Jarak Kosong & Garis Panduan Potong Tengah Manual (12mm) -->
                    <div class="kp-sheet-cut-gap">
                        <div class="kp-cut-line"></div>
                    </div>

                    <!-- Baris Bawah: Sisi Belakang Kartu (Maksimal 5 Sisi) -->
                    <div class="kp-sheet-row kp-row-back">
                        @foreach ($chunk as $card)
                            @include('kartu-pelajar.card-back', ['card' => $card])
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="no-print"
            style="text-align: center; padding: 48px; background: #fff; border-radius: var(--radius-lg, 12px); width: 100%; max-width: 600px; margin: 80px auto; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
            <i class="fas fa-users-slash" style="font-size: 40px; color: #94a3b8; margin-bottom: 12px;"></i>
            <h3 style="font-size: var(--text-lg, 1.125rem); color: #1e293b;">Tidak Ada Peserta Didik Ditemukan</h3>
            <p style="font-size: var(--text-base, 0.875rem); color: #64748b;">
                @if (!empty($filter_status_request) && !empty($filter_hanya_ada_foto))
                    Tidak ada peserta didik di rombel {{ $rombel->nama }} dengan antrean request
                    "{{ $filter_status_request }}" yang sudah memiliki foto formal.
                @elseif (!empty($filter_status_request))
                    Tidak ada peserta didik di rombel {{ $rombel->nama }} dengan status antrean request
                    "{{ $filter_status_request }}".
                @elseif (!empty($filter_hanya_ada_foto))
                    Belum ada peserta didik di rombel {{ $rombel->nama }} yang sudah mengunggah foto formal.
                @else
                    Belum ada data peserta didik yang terhubung ke rombongan belajar {{ $rombel->nama }}.
                @endif
            </p>
        </div>
    @endif

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
