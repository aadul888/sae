<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lembar Observasi Supervisi Teknisi — {{ $supervisi->teknisi_nama }}</title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
    <style>
        @page { size: A4 portrait; margin: 10mm 12mm; }
        .sv-table { width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 8px; }
        .sv-table th, .sv-table td { border: 1px solid #333; padding: 5px 6px; vertical-align: top; }
        .sv-table th { background: #eef2f7; text-align: center; font-weight: 700; }
        .sv-center { text-align: center; }
        .sv-info { width: 100%; font-size: 11px; margin-bottom: 6px; }
        .sv-info td { padding: 2px 0; }
        .sv-title-sm { font-weight: 700; }
        .sv-ttd { width: 100%; margin-top: 30px; font-size: 11px; }
        .sv-ttd td { text-align: center; padding-top: 60px; }
        .sv-note { font-size: 9px; color: #555; white-space: pre-line; }
    </style>
</head>

<body>

    <div class="no-print-bar">
        <button type="button"
            onclick="if(window.history.length>1){window.history.back();}else{window.close();}"
            class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Kembali
        </button>
        <button type="button" onclick="window.SAE_Print ? window.SAE_Print() : window.print()" class="btn-action btn-print">
            <i class="fas fa-print"></i> Cetak / Simpan PDF
        </button>
    </div>

    <div class="cetak-page">
        <div class="watermark-sae">
            <img src="{{ asset('img/logo-icon.png') }}" alt="Watermark SAE">
        </div>

        @include('partials.kop-surat')

        <div class="doc-header">
            <div class="doc-title-main">LEMBAR OBSERVASI SUPERVISI TEKNISI &amp; IT</div>
            <div class="doc-subtitle">
                NOMOR: {{ $supervisi->nomor_supervisi }}
                @if ($supervisi->periode_label) &bull; PERIODE: {{ strtoupper($supervisi->periode_label) }} @endif
            </div>
        </div>

        <table class="sv-info">
            <tr>
                <td style="width:180px;"><span class="sv-title-sm">Tanggal Supervisi</span></td>
                <td>: {{ \Carbon\Carbon::parse($supervisi->tanggal_supervisi)->translatedFormat('d F Y') }}</td>
                <td style="width:160px;"><span class="sv-title-sm">Tahun Ajaran / Semester</span></td>
                <td>: {{ $supervisi->tahun_ajaran ?: '—' }} {{ $supervisi->semester ? '/ ' . $supervisi->semester : '' }}</td>
            </tr>
            <tr>
                <td><span class="sv-title-sm">Teknisi yang Disupervisi</span></td>
                <td>: {{ $supervisi->teknisi_nama }}</td>
                <td><span class="sv-title-sm">Supervisor</span></td>
                <td>: {{ $supervisi->supervisor_nama }}{{ $supervisi->supervisor_jabatan ? ' (' . $supervisi->supervisor_jabatan . ')' : '' }}</td>
            </tr>
        </table>

        <table class="sv-table">
            <thead>
                <tr>
                    <th style="width:26px;">No</th>
                    <th>Indikator Tupoksi</th>
                    <th style="width:220px;">Bukti Fisik / Dokumen yang Diperiksa</th>
                    <th style="width:52px;">Skor (1–4)</th>
                    <th style="width:170px;">Catatan / Rekomendasi Supervisor</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $it)
                    <tr>
                        <td class="sv-center">{{ $it->no_indikator }}</td>
                        <td>{{ $it->indikator_tupoksi }}</td>
                        <td class="sv-note">{{ $it->bukti_fisik }}</td>
                        <td class="sv-center" style="font-weight:700; font-size:13px;">{{ $it->skor ?? '—' }}</td>
                        <td>{{ $it->catatan_supervisor }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align:right; font-weight:700;">Nilai Akhir (Rata-rata)</td>
                    <td class="sv-center" style="font-weight:800; font-size:14px;">{{ number_format((float) $supervisi->nilai_akhir, 2, ',', '.') }}</td>
                    <td style="font-weight:700;">{{ $supervisi->kualifikasi ?: '—' }}</td>
                </tr>
            </tfoot>
        </table>

        @if ($supervisi->catatan_umum || $supervisi->rekomendasi)
            <div style="margin-top:12px; font-size:11px;">
                @if ($supervisi->catatan_umum)
                    <div style="margin-bottom:6px;"><span class="sv-title-sm">Catatan Umum:</span> {{ $supervisi->catatan_umum }}</div>
                @endif
                @if ($supervisi->rekomendasi)
                    <div><span class="sv-title-sm">Rekomendasi:</span> {{ $supervisi->rekomendasi }}</div>
                @endif
            </div>
        @endif

        <table class="sv-ttd">
            <tr>
                <td>
                    Teknisi yang Disupervisi,<br>
                    <strong style="display:block; margin-top:60px;">{{ $supervisi->teknisi_nama }}</strong>
                </td>
                <td>
                    Supervisor,<br>
                    <strong style="display:block; margin-top:60px;">{{ $supervisi->supervisor_nama }}</strong>
                    {{ $supervisi->supervisor_jabatan ?: '' }}
                </td>
            </tr>
        </table>

        <div style="margin-top:20px; font-size:9px; color:#666; border-top:1px solid #ccc; padding-top:6px;">
            Dicetak otomatis dari Sistem Aplikasi Edukasi (SAE) pada
            {{ ($dicetakPada ?? now())->translatedFormat('l, d F Y H:i:s') }} WIB.
        </div>
    </div>

    <script src="{{ asset('js/cetak.js') }}?v={{ file_exists(public_path('js/cetak.js')) ? filemtime(public_path('js/cetak.js')) : time() }}"></script>
</body>

</html>
