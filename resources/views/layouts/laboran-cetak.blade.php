<!DOCTYPE html>
<html lang="id">

{{--
    Layout dokumen cetak Laboran (A4).

    Dipakai seluruh halaman cetak modul Laboratorium per jurusan agar
    ukuran kertas, kop surat, bar alat, dan kaki dokumen SELALU sama:

      • `@page { size: A4 portrait|landscape; margin: ... }` sesuai $orientasi
      • Bar alat: Kembali · mode Ringkasan/Lengkap · orientasi · Cetak/PDF
      • Kop surat + judul + subjudul (periode & format)
      • Blok tanda tangan (partials.ttd-cetak) + footer verifikasi

    Variabel yang diharapkan dari controller (lihat LaboranDokumenService::dokumen):
      $judulDokumen, $mode, $orientasi, $range (opsional), $sekolah, $sekolahMeta
--}}

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', $judulDokumen ?? 'Dokumen Cetak — Laboratorium SAE')</title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
    <link rel="stylesheet"
        href="{{ asset('css/cetak-laboran.css') }}?v={{ file_exists(public_path('css/cetak-laboran.css')) ? filemtime(public_path('css/cetak-laboran.css')) : time() }}">

    @if (($orientasi ?? 'portrait') === 'landscape')
        <style>
            @page {
                size: A4 landscape;
                margin: 8mm 10mm;
            }
        </style>
    @else
        <style>
            @page {
                size: A4 portrait;
                margin: 10mm 12mm;
            }
        </style>
    @endif

    @stack('styles')
</head>

<body>

    @hasSection('toolbar')
        @yield('toolbar')
    @else
        <div class="no-print-bar">
            <a href="{{ $backUrl ?? '#' }}" class="btn-action btn-back">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>

            <div class="doc-toolbar-group">
                @php
                    $modeAktif = $mode ?? 'ringkasan';
                    $modeDasar = request()->except(['mode', 'orientasi']);
                @endphp

                <span class="doc-toolbar-label">Laporan:</span>
                <a href="{{ request()->url() }}?{{ http_build_query(array_merge($modeDasar, ['mode' => 'ringkasan', 'orientasi' => $orientasi ?? 'portrait'])) }}"
                    class="btn-orientasi {{ $modeAktif === 'ringkasan' ? 'active' : '' }}"
                    title="Tampilkan versi ringkas (rekap saja)">
                    <i class="fas fa-list"></i> Ringkasan
                </a>
                <a href="{{ request()->url() }}?{{ http_build_query(array_merge($modeDasar, ['mode' => 'lengkap', 'orientasi' => $orientasi ?? 'portrait'])) }}"
                    class="btn-orientasi {{ $modeAktif === 'lengkap' ? 'active' : '' }}"
                    title="Tampilkan versi lengkap (rincian per baris)">
                    <i class="fas fa-table-list"></i> Lengkap
                </a>

                <span class="doc-toolbar-sep"></span>

                <a href="{{ request()->fullUrlWithQuery(['orientasi' => 'portrait']) }}"
                    class="btn-orientasi {{ ($orientasi ?? 'portrait') === 'portrait' ? 'active' : '' }}"
                    title="Kertas A4 Portrait">
                    <i class="fas fa-file"></i> Portrait
                </a>
                <a href="{{ request()->fullUrlWithQuery(['orientasi' => 'landscape']) }}"
                    class="btn-orientasi {{ ($orientasi ?? 'portrait') === 'landscape' ? 'active' : '' }}"
                    title="Kertas A4 Landscape">
                    <i class="fas fa-file-invoice"></i> Landscape
                </a>
            </div>

            <button type="button" onclick="window.SAE_Print ? window.SAE_Print() : window.print()"
                class="btn-action btn-print">
                <i class="fas fa-print"></i> Cetak / Simpan PDF
            </button>
        </div>
    @endif

    <div class="cetak-page">
        <div class="watermark-sae">
            <img src="{{ asset('img/logo-icon.png') }}" alt="Watermark SAE">
        </div>

        @include('partials.kop-surat')

        <div class="doc-header">
            @if (empty($sekolahMeta?->kop_url))
                <div class="school-name">{{ $sekolah->nama ?? 'SATUAN PENDIDIKAN' }}</div>
            @endif
            <div class="doc-title-main">@yield('doc_title', strtoupper($judulDokumen ?? 'DOKUMEN LABORATORIUM'))</div>
            <div class="doc-subtitle">
                @hasSection('doc_subtitle')
                    @yield('doc_subtitle')
                @else
                    @if (!empty($range['label']))
                        PERIODE: {{ strtoupper($range['label']) }} &bull;
                    @endif
                    FORMAT: A4 {{ strtoupper($orientasi ?? 'portrait') }} &bull;
                    VERSI: {{ strtoupper($mode ?? 'ringkasan') }}
                @endif
            </div>
        </div>

        @yield('content')

        @hasSection('signature')
            @yield('signature')
        @else
            @include('dashboard.laboran.partials.ttd-cetak')
        @endif

        <div class="doc-footer">
            <div class="qr-badge">
                <img src="{{ asset('img/logo-icon.png') }}" alt="Logo Verifikasi SAE">
                <div>
                    <strong>Dokumen Resmi Sistem Aplikasi Edukasi (SAE)</strong><br>
                    Dicetak otomatis pada
                    {{ ($dicetakPada ?? now())->translatedFormat('l, d F Y H:i:s') }} WIB.
                    @if (!empty($logCetak?->nomor_cetak))
                        <br>Nomor cetak: <strong>{{ $logCetak->nomor_cetak }}</strong>
                    @endif
                </div>
            </div>
            <div style="text-align: right;">
                Halaman 1 / 1<br>
                Format: Kertas A4 {{ ucfirst($orientasi ?? 'portrait') }}
            </div>
        </div>
    </div>

    <script
        src="{{ asset('js/cetak.js') }}?v={{ file_exists(public_path('js/cetak.js')) ? filemtime(public_path('js/cetak.js')) : time() }}">
    </script>
    @stack('scripts')
</body>

</html>
