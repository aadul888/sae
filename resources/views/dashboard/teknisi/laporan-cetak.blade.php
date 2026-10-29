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
            margin: 12mm 14mm;
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

        .bar-field {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .bar-field select,
        .bar-field input {
            height: 30px;
            border-radius: var(--radius-md, 8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            padding: 0 6px;
            font-size: var(--text-xs, 0.75rem);
        }

        .bar-field select option {
            color: #0f172a;
        }
    </style>
</head>

<body>

    @php
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
        // Bila dokumen dibuka lewat tautan lama (dari/sampai → tipe "kustom"),
        // pemilih periode jatuh ke "Bulanan" agar bar alat tetap konsisten.
        $tipeForm  = array_key_exists($tipeAktif, \App\Support\PeriodeLaporan::labelTipe()) ? $tipeAktif : 'bulan';
        $tahunOpsi = range((int) date('Y') - 3, (int) date('Y') + 2);

        // Paginasi dokumen (di atas kertas, bukan di peramban): maksimal 2
        // catatan per halaman agar tampilan "seperti biasa" tetap berlaku.
        $perHalaman   = 2;
        $totalHalaman = max(1, (int) ceil($items->count() / $perHalaman));
        $halamanAktif = min(max(1, (int) request()->query('hal', 1)), $totalHalaman);
        $halamanItems = $items->slice(($halamanAktif - 1) * $perHalaman, $perHalaman)->values();
        $nomorAwal    = (($halamanAktif - 1) * $perHalaman) + 1;
    @endphp

    <div class="no-print-bar">
        <button type="button"
            onclick="if(window.history.length>1){window.history.back();}else{window.close();}"
            class="btn-action btn-back" title="Kembali">
            <i class="fas fa-arrow-left"></i>
        </button>

        @foreach (['ringkasan' => 'Ringkasan', 'lengkap' => 'Lengkap'] as $mk => $ml)
            <a href="{{ route('dashboard.teknisi.laporan.cetak', $params(['mode' => $mk])) }}"
                class="btn-action {{ $mode === $mk ? 'btn-print' : 'btn-back' }}">{{ $ml }}</a>
        @endforeach

        @foreach (['portrait' => 'Tegak', 'landscape' => 'Mendatar'] as $ok => $ol)
            <a href="{{ route('dashboard.teknisi.laporan.cetak', $params(['orientasi' => $ok])) }}"
                class="btn-action {{ $orientasi === $ok ? 'btn-print' : 'btn-back' }}" title="Orientasi {{ $ol }}">
                <i class="fas fa-{{ $ok === 'portrait' ? '-file' : '-table-columns' }}"></i>
            </a>
        @endforeach

        @if ($mode === 'lengkap' && $totalHalaman > 1)
            <a href="{{ route('dashboard.teknisi.laporan.cetak', $params(['hal' => max(1, $halamanAktif - 1)])) }}"
                class="btn-action btn-back {{ $halamanAktif <= 1 ? 'disabled' : '' }}" style="{{ $halamanAktif <= 1 ? 'opacity:.45;pointer-events:none;' : '' }}">
                <i class="fas fa-chevron-left"></i>
            </a>
            <span class="btn-action btn-back" style="pointer-events:none;">
                Hal {{ $halamanAktif }} / {{ $totalHalaman }}
            </span>
            <a href="{{ route('dashboard.teknisi.laporan.cetak', $params(['hal' => min($totalHalaman, $halamanAktif + 1)])) }}"
                class="btn-action btn-back {{ $halamanAktif >= $totalHalaman ? 'disabled' : '' }}" style="{{ $halamanAktif >= $totalHalaman ? 'opacity:.45;pointer-events:none;' : '' }}">
                <i class="fas fa-chevron-right"></i>
            </a>
        @endif

        <button type="button" onclick="window.SAE_Print ? window.SAE_Print() : window.print()" class="btn-action btn-print">
            <i class="fas fa-print"></i> Cetak
        </button>
    </div>

    {{-- Pemilih periode (bukan bagian dokumen tercetak) --}}
    <div class="no-print-bar" style="top: 62px !important;">
        <form method="GET" action="{{ route('dashboard.teknisi.laporan.cetak', ['modul' => $modul]) }}"
            style="display: inline-flex; align-items: center; gap: 8px; margin: 0; flex-wrap: wrap;">
            <input type="hidden" name="mode" value="{{ $mode }}">
            <input type="hidden" name="orientasi" value="{{ $orientasi }}">

            <span class="bar-field">
                <label for="selPeriode" style="color:#e2e8f0; font-size:var(--text-xs,0.75rem);">Periode</label>
                <select name="periode" id="selPeriode" onchange="saeTogglePeriode()">
                    @foreach (\App\Support\PeriodeLaporan::labelTipe() as $tipe => $labelTipe)
                        <option value="{{ $tipe }}" {{ $tipeForm === $tipe ? 'selected' : '' }}>
                            {{ $labelTipe }}
                        </option>
                    @endforeach
                </select>
            </span>

            <span class="bar-field" data-p="minggu">
                <label for="inTanggal" style="color:#e2e8f0; font-size:var(--text-xs,0.75rem);">Tgl acuan</label>
                <input type="date" name="tanggal" id="inTanggal"
                    value="{{ request()->query('tanggal', now()->toDateString()) }}">
            </span>

            <span class="bar-field" data-p="bulan triwulan tahun">
                <label for="inBulan" style="color:#e2e8f0; font-size:var(--text-xs,0.75rem);">Bulan</label>
                <select name="bulan" id="inBulan">
                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}" {{ (int) request()->query('bulan', date('n')) === $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                        </option>
                    @endforeach
                </select>
            </span>

            <span class="bar-field" data-p="triwulan">
                <label for="inTW" style="color:#e2e8f0; font-size:var(--text-xs,0.75rem);">Triwulan</label>
                <select name="triwulan" id="inTW">
                    @foreach ([1 => 'I (Jan-Mar)', 2 => 'II (Apr-Jun)', 3 => 'III (Jul-Sep)', 4 => 'IV (Okt-Des)'] as $tw => $twl)
                        <option value="{{ $tw }}" {{ (int) request()->query('triwulan', (int) ceil(date('n') / 3)) === $tw ? 'selected' : '' }}>
                            {{ $twl }}
                        </option>
                    @endforeach
                </select>
            </span>

            <span class="bar-field" data-p="semester">
                <label for="inSem" style="color:#e2e8f0; font-size:var(--text-xs,0.75rem);">Semester</label>
                <select name="semester" id="inSem">
                    <option value="1" {{ request()->query('semester', date('n') >= 7 ? '1' : '2') === '1' ? 'selected' : '' }}>Ganjil (Jul-Des)</option>
                    <option value="2" {{ request()->query('semester', date('n') >= 7 ? '1' : '2') === '2' ? 'selected' : '' }}>Genap (Jan-Jun)</option>
                </select>
            </span>

            <span class="bar-field" data-p="bulan triwulan semester tahun">
                <label for="inTahun" style="color:#e2e8f0; font-size:var(--text-xs,0.75rem);">Tahun</label>
                <select name="tahun" id="inTahun">
                    @foreach ($tahunOpsi as $y)
                        <option value="{{ $y }}" {{ (int) request()->query('tahun', date('Y')) === $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </span>

            <span class="bar-field" data-p="semester">
                <label for="inTA" style="color:#e2e8f0; font-size:var(--text-xs,0.75rem);">TA</label>
                <input type="text" name="tahun_ajaran" id="inTA" placeholder="2026/2027" style="width:96px;"
                    value="{{ request()->query('tahun_ajaran', '') }}">
            </span>

            <button type="submit" class="btn-action btn-print">
                <i class="fas fa-filter"></i> Terapkan
            </button>
        </form>
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
        @forelse ($halamanItems as $i => $item)
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
                        <td>: {{ $nomorAwal + $i }} dari {{ $items->count() }}</td>
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

                <div class="lap-pageno">
                    Halaman {{ $halamanAktif }} / {{ $totalHalaman }}
                    &bull; {{ ($halamanAktif - 1) * $perHalaman + 1 }}–{{ $nomorAwal + $halamanItems->count() - 1 }}
                    dari {{ $items->count() }} data
                </div>

                @if ($loop->last)
                    @include('dashboard.teknisi.partials.ttd-cetak')
                @endif
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
    @endif

    <script>
        function saeTogglePeriode() {
            var sel = document.getElementById('selPeriode');
            if (!sel) return;
            var tipe = sel.value;
            document.querySelectorAll('.bar-field[data-p]').forEach(function (el) {
                var list = (el.getAttribute('data-p') || '').split(/\s+/);
                el.style.display = list.indexOf(tipe) !== -1 ? 'inline-flex' : 'none';
            });
        }
        saeTogglePeriode();
    </script>

    <script src="{{ asset('js/cetak.js') }}?v={{ file_exists(public_path('js/cetak.js')) ? filemtime(public_path('js/cetak.js')) : time() }}"></script>
</body>

</html>
