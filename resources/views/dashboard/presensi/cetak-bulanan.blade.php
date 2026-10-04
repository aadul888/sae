<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Presensi Bulanan — {{ $namaRombel }} ({{ $bulanLabel }})</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #111827;
        }

        body {
            background-color: #f1f5f9;
            padding: 20px;
            font-size: 10px;
            line-height: 1.35;
        }

        /* Floating Action Bar (Hidden when Printing) */
        .print-toolbar {
            position: fixed;
            top: 16px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 10px 20px;
            border-radius: 30px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            z-index: 9999;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .print-toolbar span {
            color: #f8fafc;
            font-size: 12px;
            font-weight: 600;
        }

        .print-btn {
            background: #2563eb;
            color: #ffffff;
            border: none;
            padding: 7px 16px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .print-btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .close-btn {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border: none;
            padding: 7px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .close-btn:hover {
            background: rgba(255, 255, 255, 0.22);
        }

        /* Paper Document Styling */
        .cetak-paper {
            background: #ffffff;
            max-width: 297mm;
            min-height: 210mm;
            margin: 50px auto 30px auto;
            padding: 12mm 15mm 15mm 15mm;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border-radius: 4px;
            position: relative;
            overflow: hidden;
        }

        /* Watermark Logo SAE */
        .watermark-sae {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 420px;
            height: 420px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.035;
            pointer-events: none;
            z-index: 0;
        }

        .watermark-sae img {
            width: 100%;
            height: auto;
            filter: grayscale(100%);
        }

        /* Kop Surat Sekolah */
        .kop-container {
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
        }

        .kop-image {
            width: 100%;
            max-height: 115px;
            object-fit: contain;
            display: block;
        }

        .kop-fallback {
            display: flex;
            align-items: center;
            gap: 16px;
            border-bottom: 3px double #1f2937;
            padding-bottom: 8px;
            text-align: center;
        }

        .kop-logo {
            width: 64px;
            height: 64px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .kop-text-wrap {
            flex: 1;
        }

        .kop-title {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #111827;
        }

        .kop-subtitle {
            font-size: 8.5pt;
            color: #4b5563;
            line-height: 1.35;
        }

        /* Header Dokumen */
        .doc-header {
            text-align: center;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
        }

        .doc-title-main {
            font-size: 12pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1e3a8a;
            margin-bottom: 2px;
        }

        .doc-subtitle {
            font-size: 9pt;
            color: #4b5563;
            font-weight: 600;
        }

        /* Meta Info Grid */
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 10px;
            font-size: 9pt;
            position: relative;
            z-index: 1;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
        }

        .meta-label {
            font-size: 7.5pt;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .meta-val {
            font-weight: 700;
            color: #0f172a;
        }

        /* Stat Pills */
        .stat-pills {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
        }

        .stat-pill {
            flex: 1;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 5px 8px;
            text-align: center;
        }

        .stat-pill .p-label {
            font-size: 7pt;
            text-transform: uppercase;
            font-weight: 700;
            color: #64748b;
        }

        .stat-pill .p-val {
            font-size: 10pt;
            font-weight: 800;
            color: #0f172a;
        }

        /* Data Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            margin-bottom: 16px;
            position: relative;
            z-index: 1;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #94a3b8;
            padding: 4px 5px;
            vertical-align: middle;
        }

        .data-table th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: 700;
            text-align: center;
        }

        .data-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .data-table td.center {
            text-align: center;
        }

        .data-table td.right {
            text-align: right;
        }

        .data-table tfoot td {
            background-color: #e2e8f0;
            font-weight: bold;
        }

        /* Status Badges */
        .badge-h {
            color: #15803d;
            font-weight: 700;
        }

        .badge-t {
            color: #b45309;
            font-weight: 700;
        }

        .badge-i {
            color: #0284c7;
            font-weight: 700;
        }

        .badge-s {
            color: #7e22ce;
            font-weight: 700;
        }

        .badge-d {
            color: #475569;
            font-weight: 700;
        }

        .badge-a {
            color: #b91c1c;
            font-weight: 800;
        }

        .badge-pct {
            font-weight: 800;
            color: #1e3a8a;
        }

        /* Signature Section */
        .sig-container {
            display: flex;
            justify-content: space-between;
            margin-top: 18px;
            page-break-inside: avoid;
            position: relative;
            z-index: 1;
        }

        .sig-box {
            width: 250px;
            text-align: center;
            font-size: 9pt;
        }

        .sig-space {
            height: 55px;
        }

        .sig-name {
            font-weight: bold;
            text-decoration: underline;
        }

        .sig-nip {
            font-size: 8pt;
            color: #4b5563;
        }

        /* Print Settings */
        @page {
            size: A4 landscape;
            margin: 8mm 10mm 10mm 10mm;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }

            .print-toolbar {
                display: none !important;
            }

            .cetak-paper {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
                border-radius: 0 !important;
            }

            .data-table th {
                background-color: #e2e8f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .data-table tbody tr:nth-child(even) {
                background-color: #f8fafc !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .data-table tfoot td {
                background-color: #cbd5e1 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>

    <!-- Floating Print Control Bar -->
    <div class="print-toolbar">
        <span><i class="fas fa-file-pdf me-1" style="color: #ef4444;"></i> Rekap Presensi {{ $namaRombel }}</span>
        <button type="button" class="print-btn" onclick="window.print()">
            <i class="fas fa-print"></i> Cetak / Simpan PDF
        </button>
        <button type="button" class="close-btn" onclick="window.close()">
            <i class="fas fa-xmark"></i> Tutup
        </button>
    </div>

    <!-- Paper Sheet Document -->
    <div class="cetak-paper">
        <!-- Watermark -->
        <div class="watermark-sae">
            <img src="{{ asset('img/logo-sae.png') }}" alt="Watermark SAE" onerror="this.style.display='none'">
        </div>

        <!-- Kop Surat -->
        @if ($sekolahMeta && $sekolahMeta->kop_path)
            <div class="kop-container">
                <img src="{{ asset('storage/' . $sekolahMeta->kop_path) }}" alt="Kop Surat" class="kop-image">
            </div>
        @else
            <div class="kop-fallback">
                @if ($sekolahMeta && $sekolahMeta->logo_path)
                    <img src="{{ asset('storage/' . $sekolahMeta->logo_path) }}" alt="Logo" class="kop-logo">
                @endif
                <div class="kop-text-wrap">
                    <div class="kop-title">{{ $sekolah->nama ?? 'PEMERINTAH PROVINSI JAWA BARAT — DINAS PENDIDIKAN' }}
                    </div>
                    <div class="kop-subtitle">
                        {{ $sekolah->alamat_jalan ?? 'Jl. Raya Pagelaran' }}
                        @if (!empty($sekolah->desa_kelurahan))
                            Ds. {{ $sekolah->desa_kelurahan }},
                        @endif
                        @if (!empty($sekolah->kecamatan))
                            Kec. {{ $sekolah->kecamatan }},
                        @endif
                        @if (!empty($sekolah->kabupaten_kota))
                            {{ $sekolah->kabupaten_kota }}
                        @endif
                        @if (!empty($sekolah->nomor_telepon))
                            | Telp: {{ $sekolah->nomor_telepon }}
                        @endif
                        @if (!empty($sekolah->email))
                            | Email: {{ $sekolah->email }}
                        @endif
                        @if (!empty($sekolah->npsn))
                            | NPSN: {{ $sekolah->npsn }}
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Document Header -->
        <div class="doc-header">
            <h1 class="doc-title-main">LAPORAN REKAPITULASI PRESENSI BULANAN PESERTA DIDIK</h1>
            <div class="doc-subtitle">
                Periode: {{ $bulanLabel }} &bull; Semester {{ now()->month >= 7 ? '1 (Ganjil)' : '2 (Genap)' }}
                &bull; Tahun Ajaran
                {{ now()->month >= 7 ? now()->year . '/' . (now()->year + 1) : now()->year - 1 . '/' . now()->year }}
            </div>
        </div>

        <!-- Meta Grid Info -->
        <div class="meta-grid">
            <div class="meta-item">
                <span class="meta-label">Rombongan Belajar</span>
                <span class="meta-val">{{ $namaRombel }}</span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Hari Efektif Belajar (HEB)</span>
                <span class="meta-val">{{ $hariEfektif }} Hari (Berjalan: {{ $hariEfektifBerjalan }} Hari)</span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Total Peserta Didik</span>
                <span class="meta-val">{{ count($items) }} Siswa</span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Rata-rata Kehadiran</span>
                <span class="meta-val" style="color: #1e3a8a;">{{ $avgPersen }}%</span>
            </div>
        </div>

        <!-- Stat Summary Pills -->
        <div class="stat-pills">
            <div class="stat-pill">
                <div class="p-label">Tepat Waktu (H)</div>
                <div class="p-val" style="color: #16a34a;">{{ number_format($sumH) }}</div>
            </div>
            <div class="stat-pill">
                <div class="p-label">Terlambat (T)</div>
                <div class="p-val" style="color: #d97706;">{{ number_format($sumT) }}</div>
            </div>
            <div class="stat-pill">
                <div class="p-label">Izin (I)</div>
                <div class="p-val" style="color: #0284c7;">{{ number_format($sumI) }}</div>
            </div>
            <div class="stat-pill">
                <div class="p-label">Sakit (S)</div>
                <div class="p-val" style="color: #7e22ce;">{{ number_format($sumS) }}</div>
            </div>
            <div class="stat-pill">
                <div class="p-label">Dispensasi (D)</div>
                <div class="p-val" style="color: #475569;">{{ number_format($sumD) }}</div>
            </div>
            <div class="stat-pill">
                <div class="p-label">Alpha (A)</div>
                <div class="p-val" style="color: #dc2626;">{{ number_format($sumA) }}</div>
            </div>
            <div class="stat-pill">
                <div class="p-label">Total Hadir (H+T)</div>
                <div class="p-val" style="color: #2563eb;">{{ number_format($sumH + $sumT) }}</div>
            </div>
        </div>

        <!-- Data Table -->
        <table class="data-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 32px;">No</th>
                    <th rowspan="2" style="width: 85px;">NISN</th>
                    <th rowspan="2" style="width: 75px;">NIPD / NIS</th>
                    <th rowspan="2">Nama Peserta Didik</th>
                    <th rowspan="2" style="width: 35px;">L/P</th>
                    @if (!$rombel)
                        <th rowspan="2" style="width: 90px;">Kelas</th>
                    @endif
                    <th colspan="6">Rekapitulasi Presensi (Hari)</th>
                    <th rowspan="2" style="width: 70px;">Total Hadir</th>
                    <th rowspan="2" style="width: 70px;">% Hadir</th>
                </tr>
                <tr>
                    <th style="width: 38px;">H</th>
                    <th style="width: 38px;">T</th>
                    <th style="width: 38px;">I</th>
                    <th style="width: 38px;">S</th>
                    <th style="width: 38px;">D</th>
                    <th style="width: 38px;">A</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $idx => $it)
                    <tr>
                        <td class="center">{{ $idx + 1 }}</td>
                        <td class="center" style="font-family: monospace;">{{ $it['siswa']->nisn ?? '—' }}</td>
                        <td class="center" style="font-family: monospace;">
                            {{ $it['siswa']->nipd ?? ($it['siswa']->nis ?? '—') }}</td>
                        <td style="font-weight: 600;">{{ $it['siswa']->nama }}</td>
                        <td class="center">{{ $it['siswa']->jenis_kelamin ?? 'L' }}</td>
                        @if (!$rombel)
                            <td class="center">{{ $it['siswa']->nama_rombel ?? '—' }}</td>
                        @endif
                        <td class="center badge-h">{{ $it['h'] }}</td>
                        <td class="center badge-t">{{ $it['t'] }}</td>
                        <td class="center badge-i">{{ $it['i'] }}</td>
                        <td class="center badge-s">{{ $it['s'] }}</td>
                        <td class="center badge-d">{{ $it['d'] }}</td>
                        <td class="center badge-a">{{ $it['a'] }}</td>
                        <td class="center" style="font-weight: 700;">{{ $it['total_hadir'] }}</td>
                        <td class="center badge-pct">{{ $it['persen'] }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $rombel ? 13 : 14 }}" class="center" style="padding: 20px; color: #64748b;">
                            Tidak ada data peserta didik pada rombel / filter ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="{{ $rombel ? 5 : 6 }}" style="text-align: right; padding-right: 12px;">TOTAL
                        KESELURUHAN / RATA-RATA:</td>
                    <td class="center badge-h">{{ number_format($sumH) }}</td>
                    <td class="center badge-t">{{ number_format($sumT) }}</td>
                    <td class="center badge-i">{{ number_format($sumI) }}</td>
                    <td class="center badge-s">{{ number_format($sumS) }}</td>
                    <td class="center badge-d">{{ number_format($sumD) }}</td>
                    <td class="center badge-a">{{ number_format($sumA) }}</td>
                    <td class="center">{{ number_format($sumH + $sumT) }}</td>
                    <td class="center badge-pct">{{ $avgPersen }}%</td>
                </tr>
            </tfoot>
        </table>

        <!-- Signatures -->
        <div class="sig-container">
            <div class="sig-box">
                <div>Mengetahui,</div>
                <div style="font-weight: 600;">Kepala Sekolah</div>
                <div class="sig-space"></div>
                <div class="sig-name">{{ $kepsek->nama ?? ($sekolah->nama_kepala_sekolah ?? 'Kepala Sekolah') }}</div>
                <div class="sig-nip">NIP. {{ $kepsek->nip ?? ($sekolah->nip_kepala_sekolah ?? '—') }}</div>
            </div>

            <div class="sig-box">
                <div>{{ $sekolah->kabupaten_kota ?? 'Pagelaran' }}, {{ $tanggalCetak }}</div>
                <div style="font-weight: 600;">{{ $waliKelas ? 'Wali Kelas ' . $namaRombel : 'Koordinator Presensi' }}
                </div>
                <div class="sig-space"></div>
                <div class="sig-name">{{ $waliKelas->nama ?? (session('user.nama') ?? 'Petugas Presensi') }}</div>
                <div class="sig-nip">NIP. {{ $waliKelas->nip ?? '—' }}</div>
            </div>
        </div>
    </div>

</body>

</html>
