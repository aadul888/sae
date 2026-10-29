<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} — SAE</title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
    <style>
        @page { size: A4 portrait; margin: 12mm 14mm; }
        .lap-page { page-break-after: always; }
        .lap-page:last-child { page-break-after: auto; }
        .lap-table { width: 100%; border-collapse: collapse; font-size: 12px; margin-top: 10px; }
        .lap-table td { border: 1px solid #333; padding: 7px 9px; vertical-align: top; }
        .lap-table td.lbl { width: 200px; background: #eef2f7; font-weight: 700; }
        .lap-meta { width: 100%; font-size: 12px; margin: 4px 0 8px; }
        .lap-meta td { padding: 2px 4px; }
        .lap-pageno { margin-top: 14px; font-size: 10px; color: #555; text-align: right; }
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

    @forelse ($items as $i => $item)
        <div class="cetak-page lap-page">
            <div class="watermark-sae">
                <img src="{{ asset('img/logo-icon.png') }}" alt="Watermark SAE">
            </div>

            @include('partials.kop-surat')

            <div class="doc-header">
                <div class="doc-title-main">{{ $title }}</div>
                <div class="doc-subtitle">PERIODE: {{ strtoupper($range['label']) }}</div>
            </div>

            <table class="lap-meta">
                <tr>
                    <td style="width:110px;"><strong>Nomor</strong></td>
                    <td>: {{ $item->nomor ?: '—' }}</td>
                    <td style="width:120px;"><strong>Data ke-</strong></td>
                    <td>: {{ $i + 1 }} dari {{ $items->count() }}</td>
                </tr>
            </table>

            <table class="lap-table">
                <tbody>
                    @foreach ($item->fields as $f)
                        <tr>
                            <td class="lbl">{{ $f['label'] }}</td>
                            <td>{{ $f['value'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="lap-pageno">Halaman {{ $i + 1 }} / {{ $items->count() }}</div>
        </div>
    @empty
        <div class="cetak-page">
            @include('partials.kop-surat')
            <div class="doc-header">
                <div class="doc-title-main">{{ $title }}</div>
                <div class="doc-subtitle">PERIODE: {{ strtoupper($range['label']) }}</div>
            </div>
            <p style="text-align:center; padding: 40px; color:#6b7280;"><em>Tidak ada data pada periode ini.</em></p>
        </div>
    @endforelse

    <script src="{{ asset('js/cetak.js') }}?v={{ file_exists(public_path('js/cetak.js')) ? filemtime(public_path('js/cetak.js')) : time() }}"></script>
</body>

</html>
