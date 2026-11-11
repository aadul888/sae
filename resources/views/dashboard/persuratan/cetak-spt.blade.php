<!DOCTYPE html>
<html lang="id">
@php
    $sekolah = $sekolah ?? \App\Models\Sekolah::first();
    $sekolahMeta = $sekolahMeta ?? \App\Models\SekolahMeta::first();
    $rawKota = $sekolah?->kabupaten_kota ?: ($sekolahMeta?->kabupaten_kota ?? 'Cianjur');
    $kotaTtd = preg_replace('/^(kabupaten|kab\.|kota)\s+/i', '', trim($rawKota));
    $tglBerangkat = \Carbon\Carbon::parse($spt->tanggal_berangkat);
    $tglKembali = \Carbon\Carbon::parse($spt->tanggal_kembali);
    $hariTanggal = $tglBerangkat->translatedFormat('l, d F Y');

    // Resolusi Kepala Sekolah & Pejabat Penandatangan
    $kepsek = $kepsek ?? \App\Services\SuratSptService::resolveKepalaSekolah();
    $pejabatPtk = $pejabatPtk ?? null;
    if (!$pejabatPtk && !empty($spt->pejabat_penandatangan_ptk_id) && \App\Support\Table::exists('gtk')) {
        $pejabatPtk = \App\Models\Gtk::where('ptk_id', $spt->pejabat_penandatangan_ptk_id)->first();
    }
    if (!$pejabatPtk) {
        $pejabatPtk = $kepsek;
    }

    $namaKepsek = !empty($namaKepsek)
        ? $namaKepsek
        : (!empty($spt->pejabat_nama) && $spt->pejabat_nama !== 'Kepala Sekolah'
            ? $spt->pejabat_nama
            : ($pejabatPtk?->nama ?? ($kepsek?->nama ?? 'Kepala Sekolah')));

    $nipKepsek = (!empty($nipKepsek) && $nipKepsek !== '-')
        ? $nipKepsek
        : (!empty($spt->pejabat_nip)
            ? $spt->pejabat_nip
            : ($pejabatPtk?->nip ?: ($kepsek?->nip ?: ($pejabatPtk?->nuptk ?: ($kepsek?->nuptk ?? '-')))));

    $pangkatMap = \App\Services\SuratSptService::$pangkatMap;

    $pangkatGolKepsek = (!empty($pangkatGolKepsek) && $pangkatGolKepsek !== '-')
        ? $pangkatGolKepsek
        : (!empty($spt->pejabat_pangkat_golongan)
            ? \App\Services\SuratSptService::formatPangkatGolongan($spt->pejabat_pangkat_golongan)
            : \App\Services\SuratSptService::resolveGtkPangkat($pejabatPtk ?: $kepsek));
    if ($pangkatGolKepsek === '-') {
        $pangkatGolKepsek = '';
    }
    $tempatBerangkat = $spt->tempat_berangkat ?: ($sekolah?->nama ?? 'SMKN 1 Pagelaran');
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
            margin: 1.5cm;
        }

        @page sp-portrait {
            size: A4 portrait;
            margin: 1.5cm;
        }

        .sp-landscape {
            page: sp-landscape;
        }

        .cetak-page.sp-landscape {
            padding: 1.5cm !important;
            max-width: 297mm !important;
            box-sizing: border-box !important;
        }

        @media print {
            .cetak-page.sp-landscape {
                padding: 0 !important;
                margin: 0 !important;
                max-width: none !important;
                width: 100% !important;
                min-height: 0 !important;
                height: auto !important;
            }
        }

        .sp-portrait {
            page: sp-portrait;
        }

        /* 2-Kolom Layout SPPD Landscape */
        .sppd-grid {
            display: flex;
            justify-content: space-between;
            align-items: stretch;
            gap: 7mm;
            width: 100%;
            box-sizing: border-box;
            font-family: 'Bookman Old Style', 'Bookman', 'URW Bookman L', serif;
            color: #000;
        }

        .sppd-col {
            flex: 1 1 50%;
            width: calc(50% - 3.5mm);
            box-sizing: border-box;
        }

        .sppd-col .kop-container,
        .sppd-col .kop-surat {
            width: 100% !important;
            padding-bottom: 2px !important;
            margin-bottom: 6px !important;
            border-bottom: 1.5px solid #000 !important;
        }

        .sppd-col .kop-container img,
        .sppd-col .kop-surat img {
            width: 100% !important;
            max-width: 100% !important;
            height: auto !important;
            object-fit: contain !important;
            display: block !important;
        }

        .sppd-col .kop-title {
            font-size: 8pt !important;
            line-height: 1.1 !important;
            font-weight: 700;
        }

        .sppd-col .kop-subtitle {
            font-size: 6.5pt !important;
            line-height: 1.1 !important;
        }

        .sppd-meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            line-height: 1.25;
            margin-bottom: 8px; /* spasi enter setelah nomor surat */
        }

        .sppd-meta-table td {
            border: none !important;
            padding: 0 !important;
            vertical-align: top;
        }

        .sppd-title {
            text-align: center;
            font-weight: 700;
            font-size: 8.5pt;
            line-height: 1.25;
            text-decoration: underline;
            margin: 8px 0; /* spasi enter setelah judul SPPD */
            text-transform: uppercase;
        }

        .sppd-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
            line-height: 1.25;
        }

        .sppd-table th,
        .sppd-table td {
            border: 1px solid #000 !important;
            padding: 2.5px 4px;
            vertical-align: top;
        }

        .sppd-table-visum {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
            line-height: 1.2;
        }

        .sppd-table-visum tr.visum-row {
            height: 104px;
        }

        .sppd-table-visum tr.visum-row-compact {
            height: 92px; /* Row I dan V dikurangi tingginya sedikit */
        }

        .sppd-table-visum td {
            border: 1px solid #000 !important;
            padding: 3px 5px;
            vertical-align: top;
        }

        .sppd-col-left {
            display: flex;
            flex-direction: column;
        }

        .sppd-ttd-kpa {
            margin-left: 45%;
            margin-top: 18px; /* jarak setelah tabel ke tanda tangan dikeluarkan bla bla */
            font-size: 8pt;
            line-height: 1.25;
        }

        .sppd-qr-verify {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: auto; /* dorong ke paling bawah kolom 1 */
            padding-top: 8px;
            border-top: 1px dashed #cbd5e1;
            font-size: 6.8pt;
            color: #475569;
            line-height: 1.25;
            text-align: left;
            width: 100%;
            box-sizing: border-box;
        }

        .sppd-qr-verify img {
            width: 34px;
            height: 34px;
            border: 1px solid #cbd5e1;
            padding: 1.5px;
            background: #fff;
            border-radius: 2px;
            flex-shrink: 0;
        }

        .sp-doc.portrait {
            position: relative;
            box-sizing: border-box;
        }

        .cetak-page.sp-portrait {
            display: flex !important;
            flex-direction: column !important;
            justify-content: flex-start !important;
            min-height: 297mm !important;
            box-sizing: border-box !important;
            position: relative !important;
            padding: 1.5cm 1.5cm 1.5cm 1.5cm !important;
        }

        .spt-footer-verify {
            margin-top: auto !important;
            padding-top: 8px;
            border-top: 1px dashed #cbd5e1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 7.5pt;
            color: #475569;
            width: 100%;
            box-sizing: border-box;
        }

        @media print {
            .cetak-page.sp-portrait {
                min-height: 265mm !important;
                height: 265mm !important;
                max-height: 265mm !important;
                padding: 0 !important;
                margin: 0 !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                box-sizing: border-box !important;
            }

            .spt-footer-verify {
                margin-top: auto !important;
                width: 100% !important;
                padding-bottom: 0 !important;
            }
        }

        .spt-footer-verify .qr-box {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .spt-footer-verify img {
            width: 38px;
            height: 38px;
            border: 1px solid #cbd5e1;
            padding: 1.5px;
            background: #fff;
            border-radius: 3px;
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
