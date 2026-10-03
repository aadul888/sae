<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Peserta Didik Kelas {{ $rombel->nama }} — {{ $sekolah->nama ?? 'Sekolah' }}</title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #111827;
        }

        body {
            background-color: #f3f4f6;
            padding: 24px;
        }

        /* Container Dokumen */
        .cetak-page {
            background: #fff;
            margin: 0 auto 28px auto;
            padding: 24px 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            position: relative;
            overflow: hidden;

            @if ($orientasi === 'landscape')
                max-width: 1120px;
            @else
                max-width: 850px;
            @endif
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
            opacity: 0.04;
            pointer-events: none;
            z-index: 0;
        }

        .watermark-sae img {
            width: 100%;
            height: auto;
            filter: grayscale(100%);
        }

        /* Kop Surat Resmi */
        .kop-container {
            margin-bottom: 16px;
            position: relative;
            z-index: 1;
        }

        .kop-image {
            width: 100%;
            max-height: 125px;
            object-fit: contain;
            display: block;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 8px;
        }

        .kop-fallback {
            display: flex;
            align-items: center;
            gap: 16px;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 10px;
            text-align: center;
        }

        .kop-logo {
            width: 68px;
            height: 68px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .kop-text-wrap {
            flex: 1;
        }

        .kop-title {
            font-size: 1.15rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #111827;
            line-height: 1.25;
        }

        .kop-subtitle {
            font-size: 0.78rem;
            color: #374151;
            margin-top: 3px;
            line-height: 1.35;
        }

        /* Judul Dokumen */
        .doc-title-block {
            text-align: center;
            margin: 16px 0 14px 0;
            position: relative;
            z-index: 1;
        }

        .doc-title {
            font-size: 1.15rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #0f172a;
        }

        .doc-subtitle {
            font-size: 0.82rem;
            font-weight: 700;
            color: #059669;
            margin-top: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Info Grid Kelas */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 8px 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 16px;
            margin-bottom: 16px;
            font-size: 0.8rem;
            position: relative;
            z-index: 1;
        }

        .info-row {
            display: flex;
            gap: 6px;
        }

        .info-lbl {
            font-weight: 600;
            color: #64748b;
            min-width: 115px;
        }

        .info-val {
            font-weight: 700;
            color: #0f172a;
        }

        /* Tabel Data Peserta Didik */
        .table-data {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.76rem;
            margin-bottom: 24px;
            position: relative;
            z-index: 1;
        }

        .table-data th,
        .table-data td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            vertical-align: middle;
        }

        .table-data th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.72rem;
            letter-spacing: 0.3px;
            text-align: center;
        }

        .table-data tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .col-center {
            text-align: center;
        }

        .col-mono {
            font-family: monospace;
            font-size: 0.76rem;
        }

        /* Tanda Tangan */
        .ttd-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-top: 24px;
            font-size: 0.82rem;
            page-break-inside: avoid;
            position: relative;
            z-index: 1;
        }

        .ttd-box {
            text-align: center;
            width: 250px;
        }

        .ttd-space {
            height: 65px;
        }

        /* Floating Action Bar (No-Print) */
        .no-print-bar {
            position: fixed;
            top: 16px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(15, 23, 42, 0.94);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            padding: 8px 16px;
            border-radius: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);
            z-index: 99999;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .no-print-bar a,
        .no-print-bar button {
            text-decoration: none;
            cursor: pointer;
            border: none;
            border-radius: 20px;
            padding: 6px 14px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.15);
            color: #f1f5f9;
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.25);
            color: #fff;
        }

        .orientation-switch {
            display: flex;
            background: rgba(0, 0, 0, 0.35);
            border-radius: 20px;
            padding: 2px;
        }

        .btn-orientasi {
            background: transparent;
            color: #94a3b8;
            padding: 4px 10px !important;
            font-size: 0.75rem !important;
        }

        .btn-orientasi.active {
            background: #059669;
            color: #fff;
        }

        .btn-print {
            background: #10b981;
            color: #fff;
        }

        .btn-print:hover {
            background: #059669;
        }

        /* Aturan Cetak Browser */
        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .no-print-bar {
                display: none !important;
            }

            .cetak-page {
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
            }

            @page {
                size: A4 {{ $orientasi }};
                margin: 8mm 10mm;
            }

            .table-data th {
                background-color: #f1f5f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .table-data tbody tr {
                page-break-inside: avoid;
            }

            .info-grid {
                background: #f8fafc !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>

    {{-- Floating Action Bar --}}
    <div class="no-print-bar">
        <a href="javascript:window.close()" class="btn-back">
            <i class="fas fa-arrow-left"></i> Tutup
        </a>

        <div class="orientation-switch">
            <a href="{{ request()->fullUrlWithQuery(['orientasi' => 'landscape']) }}"
                class="btn-orientasi {{ $orientasi === 'landscape' ? 'active' : '' }}" title="Kertas A4 Landscape">
                <i class="fas fa-file-invoice"></i> Landscape
            </a>
            <a href="{{ request()->fullUrlWithQuery(['orientasi' => 'portrait']) }}"
                class="btn-orientasi {{ $orientasi === 'portrait' ? 'active' : '' }}" title="Kertas A4 Portrait">
                <i class="fas fa-file"></i> Portrait
            </a>
        </div>

        <button onclick="window.print()" class="btn-print">
            <i class="fas fa-print"></i> Cetak / Simpan PDF
        </button>
    </div>

    {{-- Lembar Cetak Dokumen Resmi --}}
    <div class="cetak-page">
        <!-- Watermark Logo SAE -->
        <div class="watermark-sae">
            <img src="{{ asset('img/logo-icon.png') }}" alt="Watermark SAE">
        </div>

        <!-- 1. KOP SURAT RESMI SEKOLAH -->
        <div class="kop-container">
            @if (!empty($sekolahMeta?->kop_url))
                <img src="{{ $sekolahMeta->kop_url }}" alt="Kop Surat Resmi {{ $sekolah->nama ?? 'Sekolah' }}"
                    class="kop-image">
            @else
                <div class="kop-fallback">
                    @php
                        $logoUrl = !empty($sekolahMeta?->logo_url)
                            ? $sekolahMeta->logo_url
                            : asset('img/logo-dark.png');
                    @endphp
                    <img src="{{ $logoUrl }}" alt="Logo Sekolah" class="kop-logo"
                        onerror="this.src='/img/logo-dark.png'">
                    <div class="kop-text-wrap">
                        <div class="kop-title">{{ $sekolah->nama ?? 'SISTEM APLIKASI EDUKASI (SAE)' }}</div>
                        <div class="kop-subtitle">
                            {{ $sekolah->alamat_jalan ?? 'Alamat Sekolah Belum Diatur' }}
                            @if (!empty($sekolah->desa_kelurahan))
                                , {{ $sekolah->desa_kelurahan }}
                            @endif
                            @if (!empty($sekolah->kecamatan))
                                , Kec. {{ $sekolah->kecamatan }}
                            @endif
                            @if (!empty($sekolah->kabupaten_kota))
                                , {{ $sekolah->kabupaten_kota }}
                            @endif
                            <br>
                            NPSN: {{ $sekolah->npsn ?? '-' }} | Website: {{ $sekolah->website ?? '-' }} | Email:
                            {{ $sekolah->email ?? '-' }}
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- 2. JUDUL DOKUMEN -->
        <div class="doc-title-block">
            <h2 class="doc-title">DAFTAR DATA PESERTA DIDIK</h2>
            <div class="doc-subtitle">KELAS {{ strtoupper($rombel->nama) }} &bull; TAHUN PELAJARAN
                {{ strtoupper($semesterLabel) }}</div>
        </div>

        <!-- 3. INFO GRID KELAS -->
        <div class="info-grid">
            <div class="info-row">
                <span class="info-lbl">Kelas / Rombel:</span>
                <span class="info-val">{{ $rombel->nama }}</span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Program Keahlian:</span>
                <span class="info-val">{{ $jurusanNama }}</span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Wali Kelas:</span>
                <span class="info-val">{{ $waliNama }}</span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Total Siswa:</span>
                <span class="info-val">{{ $totalSiswa }} Orang (L: {{ $totalL }}, P:
                    {{ $totalP }})</span>
            </div>
        </div>

        <!-- 4. TABEL DATA PESERTA DIDIK -->
        <table class="table-data">
            <thead>
                <tr>
                    <th style="width: 32px;">No</th>
                    <th style="width: 85px;">NISN</th>
                    <th style="width: 85px;">NIPD / NIS</th>
                    <th>Nama Lengkap Peserta Didik</th>
                    <th style="width: 36px;">L/P</th>
                    <th style="width: 145px;">Tempat, Tgl Lahir</th>
                    <th style="width: 70px;">Agama</th>
                    <th style="width: 140px;">Nama Orang Tua / Wali</th>
                    <th>Alamat Tinggal</th>
                    <th style="width: 95px;">No. HP</th>
                    <th style="width: 50px;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($students as $idx => $s)
                    @php
                        $tglLahir = !empty($s->tanggal_lahir)
                            ? \Carbon\Carbon::parse($s->tanggal_lahir)->translatedFormat('d/m/Y')
                            : '';
                        $ttl = trim(($s->tempat_lahir ? $s->tempat_lahir . ($tglLahir ? ', ' : '') : '') . $tglLahir);
                        $ortu = $s->nama_ayah ?: ($s->nama_ibu ?: ($s->nama_wali ?: '—'));
                    @endphp
                    <tr>
                        <td class="col-center">{{ $idx + 1 }}</td>
                        <td class="col-center col-mono">{{ $s->nisn ?: '—' }}</td>
                        <td class="col-center col-mono">{{ $s->nipd ?: '—' }}</td>
                        <td style="font-weight: 600;">{{ $s->nama }}</td>
                        <td class="col-center"
                            style="font-weight: 700; color: {{ $s->jenis_kelamin === 'L' ? '#0284c7' : '#db2777' }};">
                            {{ $s->jenis_kelamin ?: '—' }}
                        </td>
                        <td>{{ $ttl ?: '—' }}</td>
                        <td class="col-center">{{ $s->agama ?: '—' }}</td>
                        <td>{{ $ortu }}</td>
                        <td style="font-size: 0.73rem;">{{ $s->alamat_jalan ?: '—' }}</td>
                        <td class="col-center col-mono">{{ $s->no_hp ?: '—' }}</td>
                        <td class="col-center" style="color: #059669; font-weight: 700;">Aktif</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="col-center" style="padding: 24px; color: #64748b;">
                            Tidak ada data peserta didik di rombongan belajar ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- 5. TANDA TANGAN / PENGESAHAN DOKUMEN RESMI -->
        <div class="ttd-container">
            <div class="ttd-box">
                <div>Mengetahui,</div>
                <div>Kepala Sekolah</div>
                <div class="ttd-space"></div>
                <div style="font-weight: 700; text-decoration: underline;">
                    {{ $kepalaSekolah?->nama ?: $sekolah?->nama_kepala_sekolah ?? 'Kepala Sekolah' }}
                </div>
                <div style="font-size: 0.78rem; color: #4b5563;">
                    NIP. {{ $kepalaSekolah?->nip ?: $sekolah?->nip_kepala_sekolah ?? '—' }}
                </div>
            </div>

            <div class="ttd-box">
                <div>{{ $titimangsa }}</div>
                <div>Wali Kelas {{ $rombel->nama }}</div>
                <div class="ttd-space"></div>
                <div style="font-weight: 700; text-decoration: underline;">
                    {{ $waliNama }}
                </div>
                <div style="font-size: 0.78rem; color: #4b5563;">
                    NIP. {{ $waliNip ?: '—' }}
                </div>
            </div>
        </div>
    </div>

</body>

</html>
