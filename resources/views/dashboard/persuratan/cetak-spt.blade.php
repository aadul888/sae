<!DOCTYPE html>
<html lang="id">
@php
    $sekolah = $sekolah ?? \App\Models\Sekolah::first();
    $sekolahMeta = $sekolahMeta ?? \App\Models\SekolahMeta::first();
    $kotaTtd = $sekolah?->kabupaten_kota ?: ($sekolahMeta?->kabupaten_kota ?? 'Cianjur');
    $tglBerangkat = \Carbon\Carbon::parse($spt->tanggal_berangkat);
    $tglKembali = \Carbon\Carbon::parse($spt->tanggal_kembali);
    $hariTanggal = $tglBerangkat->translatedFormat('l, d F Y');
    $namaKepsek = $spt->pejabat_nama ?: ($kepsek?->nama ?? 'Kepala Sekolah');
    $nipKepsek = $kepsek?->nip ?: ($kepsek?->nuptk ?? '-');
@endphp

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPT &amp; SPPD - {{ $spt->nomor_spt }}</title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
    <style>
        .sp-doc {
            font-size: 10.5pt;
            line-height: 1.5;
            color: #000;
        }

        .sp-title {
            text-align: center;
            font-weight: 800;
            text-transform: uppercase;
            text-decoration: underline;
            letter-spacing: 0.5px;
            font-size: 13pt;
            margin: 6px 0 2px 0;
        }

        .sp-subtitle {
            text-align: center;
            font-size: 10.5pt;
            margin-bottom: 14px;
        }

        .sp-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5pt;
        }

        .sp-table th,
        .sp-table td {
            border: 1px solid #000;
            padding: 5px 7px;
            vertical-align: top;
        }

        .sp-table th {
            background: #eef2f7;
            font-weight: 700;
            text-align: center;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .sp-table--plain,
        .sp-table--plain td {
            border: none;
            padding: 2px 0;
        }

        .sp-center {
            text-align: center;
        }

        .sp-ttd {
            width: 280px;
            margin-left: auto;
            margin-top: 22px;
            text-align: center;
            font-size: 10.5pt;
        }

        .sp-ttd--left {
            margin-left: 0;
        }

        .sp-space {
            height: 62px;
        }

        .sp-sign {
            font-weight: 700;
            text-decoration: underline;
        }

        .sp-muted {
            color: #333;
        }

        .sp-page-break {
            page-break-after: always;
            break-after: page;
        }

        .sp-page-break:last-child {
            page-break-after: auto;
            break-after: auto;
        }

        /* Halaman 2 (SPPD) dicetak landscape, halaman lain portrait. */
        @page sp-landscape {
            size: A4 landscape;
        }

        @page sp-portrait {
            size: A4 portrait;
        }

        .sp-landscape {
            page: sp-landscape;
        }

        /* `.cetak-page.landscape` memaksa min-height 210mm dengan !important
           (spesifisitas lebih tinggi dari aturan print). Netralkan saat cetak
           agar isi tidak meluber ke halaman kedua. */
        @media print {
            .cetak-page.sp-landscape {
                min-height: 0 !important;
            }
        }

        .sp-portrait {
            page: sp-portrait;
        }

        /* Halaman SPPD landscape: rapatkan agar tetap 1 halaman. */
        .sp-landscape {
            line-height: 1.1;
            font-size: 8.5pt;
            padding-bottom: 0 !important;
        }

        .sp-landscape .kop-container,
        .sp-landscape .kop-surat {
            padding-bottom: 4px !important;
            margin-bottom: 6px !important;
        }

        .sp-landscape .kop-container img,
        .sp-landscape .kop-surat img {
            max-height: 64px !important;
            width: auto !important;
        }

        .sp-landscape .sp-title {
            font-size: 10.5pt;
            margin: 0;
            line-height: 1.1;
        }

        .sp-landscape .sp-subtitle {
            display: none;
        }

        .sp-landscape .sp-table {
            font-size: 8.5pt;
        }

        .sp-landscape .sp-table th,
        .sp-landscape .sp-table td {
            padding: 1px 4px;
        }

        .sp-landscape .sp-ttd {
            margin-top: 4px;
            font-size: 8.5pt;
        }

        .sp-landscape .sp-space {
            height: 6px;
        }
    </style>
</head>

<body>
    {{-- Toolbar Aksi --}}
    <div class="no-print-bar">
        <a href="{{ route('dashboard.persuratan.keluar.index') }}" class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
        <button type="button" class="btn-action btn-print"
            onclick="window.SAE_Print ? window.SAE_Print() : window.print()">
            <i class="fas fa-print"></i> Cetak Dokumen (Print / PDF)
        </button>
    </div>

    <div class="cetak-page portrait sp-doc sp-page-break sp-portrait">
        @include('dashboard.persuratan.partials.cetak-spt-halaman-1')
    </div>

    <div class="cetak-page landscape sp-doc sp-page-break sp-landscape">
        @include('dashboard.persuratan.partials.cetak-spt-halaman-2')
    </div>

    <div class="cetak-page portrait sp-doc sp-portrait">
        @include('dashboard.persuratan.partials.cetak-spt-halaman-3')
    </div>

    <script
        src="{{ asset('js/cetak.js') }}?v={{ file_exists(public_path('js/cetak.js')) ? filemtime(public_path('js/cetak.js')) : time() }}">
    </script>
</body>

</html>
