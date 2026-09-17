<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Presensi Peserta Didik — {{ $siswa->nama }} ({{ $range['label'] }})</title>
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

        .cetak-page {
            background: #fff;
            max-width: 900px;
            margin: 0 auto 28px auto;
            padding: 24px 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-radius: 8px;
            position: relative;
            overflow: hidden;
        }

        /* Watermark Logo SAE */
        .watermark-sae {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 400px;
            height: 400px;
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

        /* Kop Surat Sekolah Resmi */
        .kop-container {
            margin-bottom: 14px;
            position: relative;
            z-index: 1;
        }
        .kop-image {
            width: 100%;
            max-height: 125px;
            object-fit: contain;
            display: block;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 6px;
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
            letter-spacing: 0.6px;
            color: #111827;
        }
        .kop-subtitle {
            font-size: 0.78rem;
            color: #4b5563;
            line-height: 1.35;
        }

        /* Header Judul Dokumen (Sesuai Modul Jadwal KBM) */
        .doc-header {
            text-align: center;
            margin-bottom: 16px;
            position: relative;
            z-index: 1;
        }
        .doc-header .doc-title-main {
            font-size: 1.15rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #1e3a8a;
            margin: 0 0 2px 0;
        }
        .doc-header .doc-subtitle {
            font-size: 0.82rem;
            color: #4b5563;
            font-weight: 600;
        }

        /* Info Box Identitas Siswa (Sesuai Modul Jadwal KBM) */
        .info-box {
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 0.82rem;
            position: relative;
            z-index: 1;
        }
        .info-item {
            margin-bottom: 4px;
        }
        .info-label {
            font-weight: 600;
            color: #6b7280;
            display: inline-block;
            width: 140px;
        }
        .info-val {
            font-weight: 700;
            color: #111827;
        }

        /* Tabel Rekapitulasi Statistik */
        .table-rekap {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.76rem;
            margin-bottom: 16px;
            position: relative;
            z-index: 1;
        }
        .table-rekap th, .table-rekap td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
            text-align: center;
        }
        .table-rekap th {
            background-color: #f3f4f6;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            font-size: 0.72rem;
        }

        /* Tabel Rincian Kehadiran Harian */
        .table-presensi {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.78rem;
            margin-bottom: 24px;
            position: relative;
            z-index: 1;
        }
        .table-presensi th, .table-presensi td {
            border: 1px solid #d1d5db;
            padding: 6px 10px;
            vertical-align: middle;
        }
        .table-presensi th {
            background-color: #f3f4f6;
            font-weight: 700;
            color: #374151;
            text-align: center;
            text-transform: uppercase;
            font-size: 0.72rem;
            letter-spacing: 0.5px;
        }
        .text-center { text-align: center; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }

        /* Tanda Tangan 3 Pihak */
        .ttd-box {
            width: 100%;
            margin-top: 20px;
            page-break-inside: avoid;
            position: relative;
            z-index: 1;
        }
        .ttd-date {
            text-align: right;
            font-size: 0.82rem;
            color: #374151;
            margin-bottom: 10px;
        }
        .ttd-table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
            font-size: 0.82rem;
        }
        .ttd-table td {
            padding: 4px;
            vertical-align: top;
        }
        .ttd-space {
            height: 60px;
        }
        .ttd-name {
            font-weight: 700;
            text-decoration: underline;
            color: #111827;
        }
        .ttd-nip {
            font-size: 0.76rem;
            color: #4b5563;
        }

        /* Bar Navigasi Cetak / Kembali Melayang (Sesuai Modul Jadwal KBM) */
        .no-print-bar {
            position: fixed;
            top: 16px;
            right: 24px;
            display: flex;
            gap: 10px;
            z-index: 9999;
            background: rgba(17, 24, 39, 0.85);
            backdrop-filter: blur(8px);
            padding: 8px 14px;
            border-radius: 40px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.25);
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            font-size: 0.82rem;
            font-weight: 700;
            border-radius: 30px;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-print { background: #2563eb; color: #fff; }
        .btn-print:hover { background: #1d4ed8; }
        .btn-back { background: #4b5563; color: #fff; }
        .btn-back:hover { background: #374151; }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .cetak-page {
                box-shadow: none;
                padding: 0;
                max-width: 100%;
                margin: 0;
                border-radius: 0;
            }
            .no-print-bar {
                display: none !important;
            }
            @page {
                size: portrait;
                margin: 10mm 12mm 12mm 12mm;
            }
        }
    </style>
</head>
<body>

    <!-- Bar Aksi Cetak & Kembali Melayang -->
    <div class="no-print-bar">
        <a href="{{ route('dashboard.peserta-didik.presensi.index') }}" class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Kembali ke Riwayat
        </a>
        <button onclick="window.print()" class="btn-action btn-print">
            <i class="fas fa-print"></i> Cetak Dokumen (Print / PDF)
        </button>
    </div>

    <div class="cetak-page">
        <!-- Watermark Logo SAE -->
        <div class="watermark-sae">
            <img src="{{ asset('img/logo-icon.png') }}" alt="Watermark SAE">
        </div>

        <!-- 1. KOP SURAT RESMI SEKOLAH YANG SUDAH DISIAPKAN -->
        <div class="kop-container">
            @if (!empty($sekolahMeta?->kop_url))
                <!-- Menggunakan File Gambar Kop Surat Resmi yang sudah diunggah -->
                <img src="{{ $sekolahMeta->kop_url }}" alt="Kop Surat Resmi {{ $sekolah->nama ?? 'Sekolah' }}" class="kop-image">
            @else
                <!-- Fallback Kop Standar jika belum ada file gambar kop -->
                <div class="kop-fallback">
                    @php
                        $logoUrl = !empty($sekolahMeta?->logo_url) ? $sekolahMeta->logo_url : asset('img/logo-dark.png');
                    @endphp
                    <img src="{{ $logoUrl }}" alt="Logo" class="kop-logo" onerror="this.src='/img/logo-dark.png'">
                    <div class="kop-text-wrap">
                        <div class="kop-title">{{ $sekolah->nama ?? 'SISTEM APLIKASI EDUKASI (SAE)' }}</div>
                        <div class="kop-subtitle">
                            NPSN: {{ $sekolah->npsn ?? '-' }} &bull; Alamat: {{ $sekolah->alamat_jalan ?? 'Jalan Pendidikan' }},
                            {{ $sekolah->kabupaten_kota ?? '' }}, {{ $sekolah->provinsi ?? '' }}<br>
                            Kontak: {{ $sekolah->nomor_telepon ?? '-' }} &bull; Email: {{ $sekolah->email ?? '-' }} &bull; Website: {{ $sekolah->website ?? '-' }}
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- 2. Header Judul Dokumen (Format Bersih Jadwal KBM) -->
        <div class="doc-header">
            <div class="doc-title-main">REKAPITULASI PRESENSI KEHADIRAN PESERTA DIDIK</div>
            <div class="doc-subtitle">Periode: {{ $range['label'] }}</div>
        </div>

        <!-- 3. Info Box Identitas Siswa -->
        <div class="info-box">
            <div>
                <div class="info-item">
                    <span class="info-label">Nama Siswa:</span>
                    <span class="info-val">{{ $siswa->nama ?? '-' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">NISN / NIPD:</span>
                    <span class="info-val font-mono">{{ ($siswa->nisn ?? null) ?: '-' }} / {{ ($siswa->nipd ?? null) ?: '-' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Jenis Kelamin:</span>
                    <span>{{ ($siswa->jenis_kelamin ?? '') === 'L' ? 'Laki-Laki' : (($siswa->jenis_kelamin ?? '') === 'P' ? 'Perempuan' : '-') }}</span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Kelas / Rombel:</span>
                    <span class="info-val" style="color: #1e3a8a;">{{ ($siswa->nama_rombel ?? null) ?: '-' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Program Keahlian:</span>
                    <span>{{ ($siswa->jurusan_id_str ?? null) ?: 'Umum' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Wali Kelas:</span>
                    <span class="info-val">{{ ($siswa->wali_nama ?? null) ?: '-' }}</span>
                </div>
            </div>
        </div>

        <!-- 4. Tabel Rekapitulasi Statistik Kehadiran -->
        <table class="table-rekap">
            <thead>
                <tr>
                    <th>Hadir Tepat (H)</th>
                    <th>Terlambat (T)</th>
                    <th>Izin (I)</th>
                    <th>Sakit (S)</th>
                    <th>Dispen (D)</th>
                    <th>Alpha (A)</th>
                    <th>Total Belajar</th>
                    <th>Tingkat Hadir (%)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>{{ $stats['hadir'] }}</strong> hari</td>
                    <td><strong>{{ $stats['terlambat'] }}</strong> hari ({{ $stats['menit_terlambat'] }}m)</td>
                    <td><strong>{{ $stats['izin'] }}</strong> hari</td>
                    <td><strong>{{ $stats['sakit'] }}</strong> hari</td>
                    <td><strong>{{ $stats['dispen'] }}</strong> hari</td>
                    <td><strong style="color: #ef4444;">{{ $stats['alpha'] }}</strong> hari</td>
                    <td><strong>{{ $stats['total'] }}</strong> hari</td>
                    <td><strong style="font-size: 0.88rem; color: #16a34a;">{{ $stats['persen'] }}%</strong></td>
                </tr>
            </tbody>
        </table>

        <!-- 5. Tabel Rincian Presensi Harian -->
        <table class="table-presensi">
            <thead>
                <tr>
                    <th style="width: 32px;">No</th>
                    <th style="width: 80px;">Tanggal</th>
                    <th style="width: 75px;">Hari</th>
                    <th style="width: 75px;">Masuk</th>
                    <th style="width: 75px;">Pulang</th>
                    <th style="width: 85px;">Status</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $i => $l)
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td class="text-center font-mono">{{ \Carbon\Carbon::parse($l->tanggal)->format('d/m/Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($l->tanggal)->translatedFormat('l') }}</td>
                        <td class="text-center font-mono">
                            {{ $l->jam_masuk ? substr($l->jam_masuk, 0, 5) . ' WIB' : '-' }}
                        </td>
                        <td class="text-center font-mono">
                            {{ $l->jam_pulang ? substr($l->jam_pulang, 0, 5) . ' WIB' : '-' }}
                        </td>
                        <td class="text-center">
                            @if ($l->status === 'H')
                                <span style="font-weight: 700; color: #059669;">Hadir</span>
                            @elseif ($l->status === 'T')
                                <span style="font-weight: 700; color: #d97706;">Terlambat (+{{ $l->menit_terlambat }}m)</span>
                            @elseif ($l->status === 'I')
                                <span style="font-weight: 700; color: #2563eb;">Izin</span>
                            @elseif ($l->status === 'S')
                                <span style="font-weight: 700; color: #7c3aed;">Sakit</span>
                            @elseif ($l->status === 'D')
                                <span style="font-weight: 700; color: #0891b2;">Dispen</span>
                            @else
                                <span style="font-weight: 700; color: #dc2626;">Alpha</span>
                            @endif
                        </td>
                        <td>{{ $l->keterangan ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 16px; color: #6b7280;">
                            Tidak ada catatan presensi pada rentang periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- 6. Tanda Tangan Resmi 3 Pihak -->
        <div class="ttd-box">
            <div class="ttd-date">
                {{ $sekolah->kabupaten_kota ? str_replace(['Kabupaten ', 'Kota '], '', $sekolah->kabupaten_kota) : 'Ditetapkan' }},
                {{ now()->translatedFormat('d F Y') }}
            </div>

            <table class="ttd-table">
                <tr>
                    <td style="width: 33%;">
                        Mengetahui,<br>
                        Orang Tua / Wali Murid
                        <div class="ttd-space"></div>
                        <div class="ttd-name">...................................................</div>
                    </td>
                    <td style="width: 33%;">
                        Wali Kelas
                        <div class="ttd-space"></div>
                        <div class="ttd-name">{{ ($siswa->wali_nama ?? null) ?: '...................................................' }}</div>
                        <div class="ttd-nip">NIP. {{ ($siswa->wali_nip ?? null) ?: '-' }}</div>
                    </td>
                    <td style="width: 34%;">
                        Kepala Sekolah
                        <div class="ttd-space"></div>
                        <div class="ttd-name">{{ $kepsek?->nama ?: '...................................................' }}</div>
                        <div class="ttd-nip">NIP. {{ $kepsek?->nip ?: '-' }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <script>
        window.addEventListener('load', function () {
            setTimeout(function () {
                window.print();
            }, 400);
        });
    </script>
</body>
</html>
