<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Bahan Habis Pakai (BHP) Laboratorium</title>
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

        table.tbl-bhp {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        table.tbl-bhp th,
        table.tbl-bhp td {
            border: 1px solid #94a3b8;
            padding: 5px 6px;
            vertical-align: middle;
        }

        table.tbl-bhp th {
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
            <select class="select-cetak" onchange="if(this.value){window.location.href=this.value;}"
                title="Pilih versi laporan">
                <option value="{{ request()->fullUrlWithQuery(['mode' => 'ringkasan']) }}"
                    {{ ($mode ?? 'ringkasan') === 'ringkasan' ? 'selected' : '' }}>Ringkasan</option>
                <option value="{{ request()->fullUrlWithQuery(['mode' => 'lengkap']) }}"
                    {{ ($mode ?? 'ringkasan') === 'lengkap' ? 'selected' : '' }}>Lengkap</option>
            </select>
        </div>

        @include('dashboard.laboran.partials.pilih-kaprog')

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
                <strong>BUKU DAFTAR BAHAN HABIS PAKAI (BHP)</strong><br>
                Jurusan / Bidang: {{ $scopeBadge['label'] ?? 'Semua Jurusan' }}<br>
                Sumber Anggaran: <strong>{{ !empty($sumberFilter) ? $sumberFilter : 'Semua Sumber Anggaran' }}</strong>
            </div>
            <div style="text-align: right;">
                Ruang / Lab: {{ !empty($labFilter) ? $labFilter : 'Semua Ruang' }}<br>
                Status Kedaluwarsa: {{ !empty($expFilter) ? ($expFilter === 'segera' ? 'Mendekati Exp (<= 90 hari)' : 'Sudah Kedaluwarsa') : 'Semua Status' }}<br>
                Total: <strong>{{ count($items) }}</strong> jenis bahan
            </div>
        </div>

        @if (($mode ?? 'ringkasan') === 'lengkap')
        <table class="tbl-bhp">
            <thead>
                <tr>
                    <th style="width: 4%;">No</th>
                    <th style="width: 13%;">Kode Item</th>
                    <th style="width: 22%;">Nama Bahan Habis Pakai</th>
                    <th style="width: 14%;">Ruang / Lokasi</th>
                    <th style="width: 10%;">Lemari / Rak</th>
                    <th style="width: 8%;">Stok Total</th>
                    <th style="width: 8%;">Tersedia</th>
                    <th style="width: 6%;">Satuan</th>
                    <th style="width: 8%;">Kondisi</th>
                    <th style="width: 10%;">Tgl Kedaluwarsa</th>
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
                        <td class="right" style="font-weight: 700; color: {{ (float) $it->stok_tersedia <= 2 ? 'var(--danger, #ef4444)' : 'var(--success, #10b981)' }};">
                            {{ (float) $it->stok_tersedia }}
                        </td>
                        <td class="center">{{ $it->satuan }}</td>
                        <td class="center">
                            {{ ucfirst(str_replace('_', ' ', $it->kondisi)) }}
                        </td>
                        <td class="center">
                            {{ !empty($it->tgl_kedaluwarsa) ? \Carbon\Carbon::parse($it->tgl_kedaluwarsa)->translatedFormat('d/m/Y') : '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="center" style="padding: 24px; color: #64748b;">
                            Tidak ada data bahan habis pakai yang sesuai filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @else
            @php
                $rekapBhp = collect($items)->groupBy(fn ($it) => $it->ruang_lab_nama ?: '—')
                    ->map(function ($g, $ruang) {
                        return (object) [
                            'ruang'    => $ruang,
                            'jenis'    => $g->count(),
                            'stok'     => $g->sum(fn ($x) => (float) $x->stok_total),
                            'tersedia' => $g->sum(fn ($x) => (float) $x->stok_tersedia),
                            'menipis'  => $g->filter(fn ($x) => (float) $x->stok_tersedia <= 2)->count(),
                        ];
                    })->sortByDesc('jenis')->values();
            @endphp

            <table class="tbl-bhp is-ringkasan">
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th>Ruang / Lokasi Penyimpanan</th>
                        <th style="width: 12%;">Jenis Bahan</th>
                        <th style="width: 12%;">Stok Total</th>
                        <th style="width: 12%;">Stok Tersedia</th>
                        <th style="width: 13%;">Perlu Pengadaan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rekapBhp as $i => $rb)
                        <tr>
                            <td class="center">{{ $i + 1 }}</td>
                            <td><strong>{{ $rb->ruang }}</strong></td>
                            <td class="center font-bold">{{ $rb->jenis }}</td>
                            <td class="right font-bold">{{ (float) $rb->stok }}</td>
                            <td class="right font-bold">{{ (float) $rb->tersedia }}</td>
                            <td class="center">{{ $rb->menipis }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="center" style="padding: 24px; color: #64748b;">
                                Tidak ada data bahan habis pakai yang sesuai filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="right">Jumlah</td>
                        <td class="center">{{ count($items) }}</td>
                        <td class="right">{{ (float) collect($items)->sum(fn ($x) => (float) $x->stok_total) }}</td>
                        <td class="right">{{ (float) collect($items)->sum(fn ($x) => (float) $x->stok_tersedia) }}</td>
                        <td class="center">{{ collect($items)->filter(fn ($x) => (float) $x->stok_tersedia <= 2)->count() }}</td>
                    </tr>
                </tfoot>
            </table>

            <div class="doc-trace">
                <i class="fas fa-circle-info"></i>
                Mode <strong>Ringkasan</strong> menampilkan rekapitulasi BHP per ruang penyimpanan.
                Pilih <strong>Lengkap</strong> pada bar alat di atas untuk rincian
                {{ count($items) }} jenis bahan beserta tanggal kedaluwarsa.
            </div>
        @endif

        {{-- Tanda Tangan --}}
        @include('dashboard.laboran.partials.ttd-cetak')
    </div>
</body>

</html>
