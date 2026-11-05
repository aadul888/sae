@php
    // Orientasi default: dokumen dengan banyak kolom otomatis mendatar
    // (landscape) agar tabel lengkap muat; pengguna tetap bisa memilih
    // portrait lewat bar alat (parameter `orientasi` eksplisit menang).
    $orientasiDiminta = strtolower((string) request()->query('orientasi', ''));
    $orientasi = in_array($orientasiDiminta, ['portrait', 'landscape'], true)
        ? $orientasiDiminta
        : (count($fieldMap ?? []) >= 7 ? 'landscape' : 'portrait');
@endphp
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
    {{-- Blok tanda tangan 3 kolom (flexbox) berasal dari stylesheet cetak bersama;
         di-<span>load</span> terakhir agar aturannya menang atas `cetak.css`. --}}
    <link rel="stylesheet"
        href="{{ asset('css/cetak-laboran.css') }}?v={{ file_exists(public_path('css/cetak-laboran.css')) ? filemtime(public_path('css/cetak-laboran.css')) : time() }}">
    <style>
        @page {
            size: A4 {{ $orientasi === 'landscape' ? 'landscape' : 'portrait' }};
            margin: 1.5cm;
        }

        .lap-page {
            page-break-after: always;
        }

        .lap-page:last-child {
            page-break-after: auto;
        }

        .lap-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-top: 10px;
        }

        .lap-table td,
        .lap-table th {
            border: 1px solid #333;
            padding: 7px 9px;
            vertical-align: top;
        }

        .lap-table td.lbl {
            width: 200px;
            background: #eef2f7;
            font-weight: 700;
        }

        .lap-table thead th {
            background: #e2e8f0;
            text-align: left;
            font-weight: 700;
        }

        /* Tabel detail (versi lengkap): kolom banyak, nilai jangan terpotong. */
        .lap-detail-page {
            max-width: 1120px;
        }

        .lap-detail {
            font-size: 11px;
        }

        .lap-detail td,
        .lap-detail th {
            padding: 5px 7px;
        }

        .lap-detail thead {
            display: table-header-group;
        }

        .lap-detail thead th {
            text-align: center;
            white-space: nowrap;
        }

        .lap-meta {
            width: 100%;
            font-size: 12px;
            margin: 4px 0 8px;
        }

        .lap-meta td {
            padding: 2px 4px;
        }

        .lap-pageno {
            margin-top: 14px;
            font-size: 10px;
            color: #555;
            text-align: right;
        }

        .lap-rekap {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-top: 8px;
        }

        .lap-rekap th,
        .lap-rekap td {
            border: 1px solid #333;
            padding: 6px 9px;
        }

        .lap-rekap thead th {
            background: #e2e8f0;
            text-align: left;
        }

        .lap-rekap td.num {
            text-align: right;
        }
    </style>
</head>

<body>

    @php
        // URL cetak: modul generik mengoper closure sendiri; laporan TUPOKSI
        // memakai rute bawaannya.
        $cetakUrl = $cetakUrl ?? fn (array $p = []) => route('dashboard.teknisi.laporan.cetak', array_merge(['modul' => $modul], $p));
        $detail = array_filter([
            'bulan'        => request()->query('bulan'),
            'tahun'        => request()->query('tahun'),
            'triwulan'     => request()->query('triwulan'),
            'semester'     => request()->query('semester'),
            'tahun_ajaran' => request()->query('tahun_ajaran'),
            'tanggal'      => request()->query('tanggal'),
            'dari'         => request()->query('dari'),
            'sampai'       => request()->query('sampai'),
        ], fn ($v) => $v !== null && $v !== '');

        $params = fn (array $override = []) => array_merge([
            'modul'      => $modul,
            'periode'    => $range['tipe'] ?? 'bulan',
            'mode'       => $mode,
            'orientasi'  => $orientasi,
        ], $detail, $override);

        $tipeAktif = $range['tipe'] ?? 'bulan';
        // Pemilih periode dipindah ke halaman INDEKS (dropdown cetak), jadi
        // halaman cetak ini tidak lagi merender kontrol periode.
    @endphp

    <div class="no-print-bar">
        <button type="button"
            onclick="if(window.history.length>1){window.history.back();}else{window.close();}"
            class="btn-action btn-back" title="Kembali">
            <i class="fas fa-arrow-left"></i>
        </button>

        <div class="doc-toolbar-group">
            <select class="select-cetak" onchange="if(this.value){window.location.href=this.value;}"
                title="Pilih versi laporan">
                <option value="{{ $cetakUrl($params(['mode' => 'ringkasan'])) }}"
                    {{ $mode === 'ringkasan' ? 'selected' : '' }}>Ringkasan</option>
                <option value="{{ $cetakUrl($params(['mode' => 'lengkap'])) }}"
                    {{ $mode === 'lengkap' ? 'selected' : '' }}>Lengkap</option>
            </select>
        </div>

        <div class="doc-toolbar-group">
            <select class="select-cetak" onchange="if(this.value){window.location.href=this.value;}"
                title="Pilih orientasi kertas A4">
                <option value="{{ $cetakUrl($params(['orientasi' => 'portrait'])) }}"
                    {{ $orientasi === 'portrait' ? 'selected' : '' }}>Tegak (Portrait)</option>
                <option value="{{ $cetakUrl($params(['orientasi' => 'landscape'])) }}"
                    {{ $orientasi === 'landscape' ? 'selected' : '' }}>Mendatar (Landscape)</option>
            </select>
        </div>

        <button type="button" onclick="window.SAE_Print ? window.SAE_Print() : window.print()" class="btn-action btn-print">
            <i class="fas fa-print"></i> Cetak
        </button>
    </div>

    @if ($mode === 'ringkasan')
        @php
            // Kelompokkan per kolom kategorikal pertama (selain tanggal & teks panjang).
            $kandidat = null;
            foreach ($fieldMap as [$labelK, $keyK]) {
                $tipeK = $types[$keyK] ?? 'string';
                if ($tipeK === 'date' || in_array($keyK, ['id', 'uraian', 'catatan', 'laporan_siswa'], true)) {
                    continue;
                }
                $kandidat = [$labelK, $keyK];
                break;
            }
            $grup = $kandidat
                ? $rows->groupBy(fn ($r) => (string) ($r->{$kandidat[1]} ?? '') ?: '—')
                    ->map(fn ($g, $n) => ['nama' => $n, 'jumlah' => $g->count()])
                    ->sortByDesc('jumlah')->values()
                : collect();
        @endphp

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
                    <td style="width:130px;"><strong>Jenis Periode</strong></td>
                    <td>: {{ \App\Support\PeriodeLaporan::labelTipe()[$tipeAktif] ?? 'Kustom' }}</td>
                    <td style="width:130px;"><strong>Jumlah Data</strong></td>
                    <td>: {{ $items->count() }}</td>
                </tr>
                <tr>
                    <td><strong>Rentang</strong></td>
                    <td>: {{ $range['singkat'] ?? $range['label'] }}</td>
                    <td><strong>Versi</strong></td>
                    <td>: Ringkasan</td>
                </tr>
            </table>

            <p style="font-size:12px; margin-top:10px;">
                Rekapitulasi jumlah catatan pada periode di atas. Pilih
                <strong>Lengkap</strong> pada bar alat untuk rincian setiap catatan.
            </p>

            <table class="lap-rekap">
                <thead>
                    <tr>
                        <th style="width:40px;">No</th>
                        <th>{{ $kandidat[0] ?? 'Kategori' }}</th>
                        <th style="width:90px; text-align:right;">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($grup as $i => $g)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $g['nama'] }}</td>
                            <td class="num">{{ $g['jumlah'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align:center; color:#6b7280;">
                                <em>Tidak ada data pada periode ini.</em>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($grup->isNotEmpty())
                    <tfoot>
                        <tr>
                            <th colspan="2" style="text-align:right;">Jumlah</th>
                            <th class="num">{{ $items->count() }}</th>
                        </tr>
                    </tfoot>
                @endif
            </table>

            <div class="lap-pageno">Halaman 1 / 1</div>

            @include('dashboard.teknisi.partials.ttd-cetak')
        </div>
    @else
        {{-- Versi LENGKAP: satu tabel utuh berisi SELURUH catatan periode ini
             (tanpa paginasi per-2-catatan). Kolom diambil dari `fieldMap`;
             nilai tiap baris dari `items[].fields`. Orientasi otomatis
             landscape bila kolom banyak (lihat blok @php di atas). --}}
        <div class="cetak-page lap-page lap-detail-page">
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
                    <td style="width:130px;"><strong>Jenis Periode</strong></td>
                    <td>: {{ \App\Support\PeriodeLaporan::labelTipe()[$tipeAktif] ?? 'Kustom' }}</td>
                    <td style="width:130px;"><strong>Jumlah Data</strong></td>
                    <td>: {{ $items->count() }}</td>
                </tr>
                <tr>
                    <td><strong>Rentang</strong></td>
                    <td>: {{ $range['singkat'] ?? $range['label'] }}</td>
                    <td><strong>Versi</strong></td>
                    <td>: Lengkap</td>
                </tr>
            </table>

            <table class="lap-table lap-detail">
                <thead>
                    <tr>
                        <th style="width:34px; text-align:center;">No</th>
                        @foreach ($fieldMap as [$label])
                            <th>{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td style="text-align:center;">{{ $loop->iteration }}</td>
                            @foreach ($item->fields as $f)
                                <td>{{ $f['value'] }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($fieldMap) + 1 }}" style="text-align:center; color:#6b7280;">
                                <em>Tidak ada data pada periode ini.</em>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="lap-pageno">Jumlah {{ $items->count() }} data</div>

            @include('dashboard.teknisi.partials.ttd-cetak')
        </div>
    @endif

    <script src="{{ asset('js/cetak.js') }}?v={{ file_exists(public_path('js/cetak.js')) ? filemtime(public_path('js/cetak.js')) : time() }}"></script>
</body>

</html>
