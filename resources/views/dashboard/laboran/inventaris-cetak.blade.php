<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Inventaris Alat Praktik Laboratorium</title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
    <link rel="stylesheet"
        href="{{ asset('css/cetak-laboran.css') }}?v={{ file_exists(public_path('css/cetak-laboran.css')) ? filemtime(public_path('css/cetak-laboran.css')) : time() }}">
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 12mm;
        }

        .sched-head {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }

        .sched-head h1 {
            font-size: 15px;
            font-weight: 800;
            margin: 0;
            text-transform: uppercase;
        }

        .sched-head .sub {
            font-size: 10.5px;
            margin-top: 3px;
            color: #475569;
        }

        .meta {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-bottom: 12px;
            flex-wrap: wrap;
            gap: 6px;
        }

        table.tbl-inv {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        table.tbl-inv th,
        table.tbl-inv td {
            border: 1px solid #94a3b8;
            padding: 5px 6px;
            vertical-align: middle;
        }

        table.tbl-inv th {
            background: #e2e8f0;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 9px;
            text-align: center;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .sign {
            margin-top: 24px;
            display: flex;
            justify-content: space-between;
            font-size: 10.5px;
            page-break-inside: avoid;
        }

        .sign .box {
            width: 32%;
            text-align: center;
        }

        .sign .space {
            height: 50px;
        }

        .sign .name {
            font-weight: 700;
            text-decoration: underline;
        }
    </style>
</head>

<body>
    {{-- Floating Bar --}}
    <div class="no-print-bar">
        <button onclick="window.close()" class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Tutup
        </button>
        <div class="doc-toolbar-group">
            <a href="{{ request()->fullUrlWithQuery(['mode' => 'ringkasan']) }}"
                class="btn-orientasi {{ ($mode ?? 'ringkasan') === 'ringkasan' ? 'active' : '' }}"
                title="Versi ringkas: rekap per ruang/lokasi">
                <i class="fas fa-list"></i> Ringkasan
            </a>
            <a href="{{ request()->fullUrlWithQuery(['mode' => 'lengkap']) }}"
                class="btn-orientasi {{ ($mode ?? 'ringkasan') === 'lengkap' ? 'active' : '' }}"
                title="Versi lengkap: rincian seluruh item alat">
                <i class="fas fa-table-list"></i> Lengkap
            </a>
            <span class="doc-toolbar-sep"></span>
        </div>
        <button onclick="window.print()" class="btn-action btn-print">
            <i class="fas fa-print"></i> Cetak Laporan (Print / PDF)
        </button>
    </div>

    <div class="cetak-page">
        <div class="sched-head">
            <h1>{{ $sekolah->nama ?? 'SEKOLAH' }}</h1>
            <div class="sub">
                {{ $sekolah->alamat ?? '' }}
                @if (!empty($sekolah->npsn))
                    &bull; NPSN {{ $sekolah->npsn }}
                @endif
            </div>
        </div>

        <div class="meta">
            <div>
                <strong>BUKU INDUK INVENTARIS ALAT PRAKTIK</strong><br>
                Jurusan / Bidang: {{ $scopeBadge['label'] ?? 'Semua Jurusan' }}<br>
                Sumber Anggaran: <strong>{{ !empty($sumberFilter) ? $sumberFilter : 'Semua Sumber Anggaran' }}</strong>
            </div>
            <div style="text-align: right;">
                Ruang / Lab: {{ !empty($labFilter) ? $labFilter : 'Semua Ruang' }}<br>
                Kondisi: {{ !empty($kondisiFilter) ? ucfirst(str_replace('_', ' ', $kondisiFilter)) : 'Semua Kondisi' }}<br>
                Total: <strong>{{ count($items) }}</strong> item alat praktik &bull;
                VERSI: <strong>{{ strtoupper($mode ?? 'ringkasan') }}</strong>
            </div>
        </div>

        @if (($mode ?? 'ringkasan') === 'lengkap')
        <table class="tbl-inv">
            <thead>
                <tr>
                    <th style="width: 4%;">No</th>
                    <th style="width: 12%;">Kode Item</th>
                    <th style="width: 22%;">Nama Alat Praktik</th>
                    <th style="width: 14%;">Ruang / Lokasi</th>
                    <th style="width: 10%;">Lemari / Rak</th>
                    <th style="width: 8%;">Jml Stok</th>
                    <th style="width: 6%;">Satuan</th>
                    <th style="width: 9%;">Kondisi</th>
                    <th style="width: 15%;">Sumber Dana</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $idx => $it)
                    <tr>
                        <td class="center">{{ $idx + 1 }}</td>
                        <td class="center font-mono" style="font-weight: 700;">{{ $it->kode_item }}</td>
                        <td>
                            <strong>{{ $it->nama_item }}</strong>
                            @if (!empty($it->spesifikasi))
                                <div style="font-size: 8.5px; color: #475569;">{{ $it->spesifikasi }}</div>
                            @endif
                        </td>
                        <td>{{ $it->ruang_lab_nama }}</td>
                        <td>{{ $it->lokasi_lemari_rak ?: '-' }}</td>
                        <td class="right" style="font-weight: 700;">{{ (float) $it->stok_total }}</td>
                        <td class="center">{{ $it->satuan }}</td>
                        <td class="center">
                            {{ ucfirst(str_replace('_', ' ', $it->kondisi)) }}
                        </td>
                        <td class="center">{{ $it->sumber_dana ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="center" style="padding: 24px; color: #64748b;">
                            Tidak ada data inventaris alat praktik yang sesuai filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @else
            @php
                $rekapRuang = collect($items)->groupBy(fn ($it) => $it->ruang_lab_nama ?: '—')
                    ->map(fn ($g, $ruang) => (object) [
                        'ruang'  => $ruang,
                        'item'   => $g->count(),
                        'stok'   => $g->sum(fn ($x) => (float) $x->stok_total),
                        'baik'   => $g->where('kondisi', 'baik')->count(),
                        'rusak'  => $g->whereIn('kondisi', ['rusak_ringan', 'rusak_berat'])->count(),
                    ])->sortByDesc('item')->values();
            @endphp

            <table class="tbl-inv is-ringkasan">
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th>Ruang / Lokasi Praktik</th>
                        <th style="width: 12%;">Jumlah Item</th>
                        <th style="width: 12%;">Total Stok</th>
                        <th style="width: 12%;">Kondisi Baik</th>
                        <th style="width: 12%;">Perlu Perbaikan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rekapRuang as $i => $rr)
                        <tr>
                            <td class="center">{{ $i + 1 }}</td>
                            <td><strong>{{ $rr->ruang }}</strong></td>
                            <td class="center font-bold">{{ $rr->item }}</td>
                            <td class="right font-bold">{{ (float) $rr->stok }}</td>
                            <td class="center">{{ $rr->baik }}</td>
                            <td class="center">{{ $rr->rusak }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="center" style="padding: 24px; color: #64748b;">
                                Tidak ada data inventaris alat praktik yang sesuai filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="right">Jumlah</td>
                        <td class="center">{{ count($items) }}</td>
                        <td class="right">{{ (float) collect($items)->sum(fn ($x) => (float) $x->stok_total) }}</td>
                        <td class="center">{{ collect($items)->where('kondisi', 'baik')->count() }}</td>
                        <td class="center">{{ collect($items)->whereIn('kondisi', ['rusak_ringan', 'rusak_berat'])->count() }}</td>
                    </tr>
                </tfoot>
            </table>

            <div class="doc-trace">
                <i class="fas fa-circle-info"></i>
                Mode <strong>Ringkasan</strong> menampilkan rekapitulasi inventaris per ruang/lokasi.
                Pilih <strong>Lengkap</strong> pada bar alat di atas untuk rincian
                {{ count($items) }} item beserta kode, satuan, dan sumber dana.
            </div>
        @endif

        {{-- Tanda Tangan --}}
        @include('dashboard.laboran.partials.ttd-cetak')
    </div>
</body>

</html>
