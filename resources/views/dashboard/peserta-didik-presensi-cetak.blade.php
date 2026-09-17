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
            font-family: 'Times New Roman', Times, serif;
        }

        @page {
            size: A4 portrait;
            margin: 12mm 15mm 15mm 15mm;
        }

        body {
            color: #000;
            background: #f1f5f9;
            padding: 24px;
            font-size: 10.5pt;
            line-height: 1.35;
        }

        .paper {
            background: #fff;
            max-width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 15mm 18mm;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        /* Watermark Samar */
        .watermark-sae {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 380px;
            height: 380px;
            opacity: 0.04;
            pointer-events: none;
            z-index: 0;
        }
        .watermark-sae img {
            width: 100%;
            height: auto;
            filter: grayscale(100%);
        }

        /* Kop Surat Resmi Satuan Pendidikan */
        .kop-surat {
            display: flex;
            align-items: center;
            border-bottom: 3px double #000;
            padding-bottom: 8px;
            margin-bottom: 16px;
            position: relative;
            z-index: 1;
        }
        .kop-logo {
            width: 75px;
            height: 75px;
            object-fit: contain;
            margin-right: 16px;
            flex-shrink: 0;
        }
        .kop-teks {
            flex: 1;
            text-align: center;
        }
        .kop-yayasan {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .kop-sekolah {
            font-size: 15pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin: 2px 0;
        }
        .kop-npsn {
            font-size: 9.5pt;
            font-weight: bold;
        }
        .kop-alamat {
            font-size: 8.5pt;
            color: #222;
            line-height: 1.25;
            margin-top: 2px;
        }

        /* Judul Dokumen */
        .doc-title-wrap {
            text-align: center;
            margin-bottom: 14px;
            position: relative;
            z-index: 1;
        }
        .doc-title {
            font-size: 12.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-decoration: underline;
            margin-bottom: 3px;
        }
        .doc-subtitle {
            font-size: 10pt;
            font-style: italic;
        }

        /* Tabel Metadata Siswa */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 9.5pt;
            position: relative;
            z-index: 1;
        }
        .meta-table td {
            padding: 3px 4px;
            vertical-align: top;
        }
        .meta-label {
            width: 130px;
            font-weight: bold;
        }

        /* Tabel Rekapitulasi Metrik */
        .rekap-box {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 9pt;
            position: relative;
            z-index: 1;
        }
        .rekap-box th, .rekap-box td {
            border: 1px solid #333;
            padding: 5px 6px;
            text-align: center;
        }
        .rekap-box th {
            background: #f1f5f9;
            font-weight: bold;
        }

        /* Tabel Detail Harian */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 18px;
            position: relative;
            z-index: 1;
        }
        .data-table th, .data-table td {
            border: 1px solid #333;
            padding: 4px 6px;
        }
        .data-table th {
            background: #e2e8f0;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8.5pt;
        }
        .text-center { text-align: center; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }

        /* Area Tanda Tangan */
        .ttd-container {
            width: 100%;
            margin-top: 14px;
            page-break-inside: avoid;
            position: relative;
            z-index: 1;
        }
        .ttd-table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
            font-size: 9.5pt;
        }
        .ttd-table td {
            padding: 4px;
            vertical-align: top;
        }
        .ttd-space {
            height: 60px;
        }
        .ttd-name {
            font-weight: bold;
            text-decoration: underline;
        }

        /* Floating Action Bar (Hanya muncul di layar browser) */
        .print-actions {
            position: fixed;
            bottom: 24px;
            right: 24px;
            display: flex;
            gap: 10px;
            z-index: 9999;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(8px);
            padding: 10px 16px;
            border-radius: 40px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }
        .print-btn {
            background: #6366f1;
            color: #fff;
            border: none;
            padding: 8px 18px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.85rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.4);
        }
        .print-btn:hover { background: #4f46e5; }
        .close-btn {
            background: #475569;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            cursor: pointer;
            text-decoration: none;
        }
        .close-btn:hover { background: #334155; }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .paper {
                box-shadow: none;
                padding: 0;
                max-width: 100%;
                min-height: auto;
            }
            .print-actions {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <!-- Floating Print Controls -->
    <div class="print-actions">
        <button type="button" class="print-btn" onclick="window.print()">
            <i class="fas fa-print"></i> Cetak / Simpan PDF
        </button>
        <button type="button" class="close-btn" onclick="window.close()">
            <i class="fas fa-times"></i> Tutup
        </button>
    </div>

    <div class="paper">
        <!-- Watermark -->
        <div class="watermark-sae">
            <img src="{{ asset('img/logo-dark.png') }}" alt="Watermark SAE">
        </div>

        <!-- 1. Kop Satuan Pendidikan Resmi -->
        <div class="kop-surat">
            @php
                $logoSekolahUrl = !empty($sekolah?->logo_path) ? asset('storage/' . ltrim($sekolah->logo_path, '/')) : asset('img/logo-dark.png');
            @endphp
            <img src="{{ $logoSekolahUrl }}" alt="Logo Sekolah" class="kop-logo" onerror="this.src='/img/logo-dark.png'">
            <div class="kop-teks">
                <div class="kop-yayasan">PEMERINTAH PROVINSI / YAYASAN PENDIDIKAN</div>
                <div class="kop-sekolah">{{ $sekolah->nama ?? 'SISTEM APLIKASI EDUKASI (SAE)' }}</div>
                <div class="kop-npsn">NPSN: {{ $sekolah->npsn ?? '-' }} &bull; KODE POS: {{ $sekolah->kode_pos ?? '-' }}</div>
                <div class="kop-alamat">
                    {{ $sekolah->alamat_jalan ?? 'Jalan Pendidikan No. 1' }},
                    Desa/Kel. {{ $sekolah->desa_kelurahan ?? '-' }},
                    Kec. {{ $sekolah->kecamatan ?? '-' }},
                    {{ $sekolah->kabupaten_kota ?? '-' }},
                    {{ $sekolah->provinsi ?? '-' }}<br>
                    Telepon: {{ $sekolah->nomor_telepon ?? '-' }} &bull; Email: {{ $sekolah->email ?? '-' }} &bull; Website: {{ $sekolah->website ?? '-' }}
                </div>
            </div>
        </div>

        <!-- 2. Judul Dokumen -->
        <div class="doc-title-wrap">
            <div class="doc-title">REKAPITULASI PRESENSI KEHADIRAN PESERTA DIDIK</div>
            <div class="doc-subtitle">Periode: {{ $range['label'] }}</div>
        </div>

        <!-- 3. Metadata Identitas Siswa -->
        <table class="meta-table">
            <tr>
                <td class="meta-label">Nama Peserta Didik</td>
                <td>: <strong>{{ $siswa->nama }}</strong></td>
                <td class="meta-label">Rombongan Belajar</td>
                <td>: {{ $siswa->nama_rombel ?: '-' }}</td>
            </tr>
            <tr>
                <td class="meta-label">NISN / NIPD</td>
                <td>: {{ $siswa->nisn ?: '-' }} / {{ $siswa->nipd ?: '-' }}</td>
                <td class="meta-label">Kompetensi Keahlian</td>
                <td>: {{ $siswa->jurusan_id_str ?: 'Umum' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Jenis Kelamin</td>
                <td>: {{ $siswa->jenis_kelamin === 'L' ? 'Laki-Laki' : ($siswa->jenis_kelamin === 'P' ? 'Perempuan' : '-') }}</td>
                <td class="meta-label">Wali Kelas</td>
                <td>: {{ $siswa->wali_nama ?: '-' }}</td>
            </tr>
        </table>

        <!-- 4. Tabel Rekapitulasi Kehadiran (Statistik) -->
        <table class="rekap-box">
            <thead>
                <tr>
                    <th>Hadir Tepat (H)</th>
                    <th>Terlambat (T)</th>
                    <th>Izin (I)</th>
                    <th>Sakit (S)</th>
                    <th>Dispensasi (D)</th>
                    <th>Alpha (A)</th>
                    <th>Total Hari</th>
                    <th>% Kehadiran</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>{{ $stats['hadir'] }}</strong> hari</td>
                    <td><strong>{{ $stats['terlambat'] }}</strong> hari ({{ $stats['menit_terlambat'] }} mnt)</td>
                    <td><strong>{{ $stats['izin'] }}</strong> hari</td>
                    <td><strong>{{ $stats['sakit'] }}</strong> hari</td>
                    <td><strong>{{ $stats['dispen'] }}</strong> hari</td>
                    <td><strong style="color: red;">{{ $stats['alpha'] }}</strong> hari</td>
                    <td><strong>{{ $stats['total'] }}</strong> hari</td>
                    <td><strong style="font-size: 11pt; color: #16a34a;">{{ $stats['persen'] }}%</strong></td>
                </tr>
            </tbody>
        </table>

        <!-- 5. Tabel Detail Harian -->
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th style="width: 75px;">Tanggal</th>
                    <th style="width: 65px;">Hari</th>
                    <th style="width: 70px;">Masuk</th>
                    <th style="width: 70px;">Pulang</th>
                    <th style="width: 75px;">Status</th>
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
                                <strong>Hadir</strong>
                            @elseif ($l->status === 'T')
                                <strong>Terlambat</strong> (+{{ $l->menit_terlambat }}m)
                            @elseif ($l->status === 'I')
                                <strong>Izin</strong>
                            @elseif ($l->status === 'S')
                                <strong>Sakit</strong>
                            @elseif ($l->status === 'D')
                                <strong>Dispen</strong>
                            @else
                                <strong style="color: red;">Alpha</strong>
                            @endif
                        </td>
                        <td>{{ $l->keterangan ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 16px; color: #666;">
                            Tidak ada catatan presensi pada rentang periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- 6. Area Tanda Tangan Resmi 3 Pihak -->
        <div class="ttd-container">
            <div style="text-align: right; margin-bottom: 8px; font-size: 9.5pt;">
                {{ $sekolah->kabupaten_kota ? str_replace(['Kabupaten ', 'Kota '], '', $sekolah->kabupaten_kota) : 'Ditetapkan' }},
                {{ now()->translatedFormat('d F Y') }}
            </div>

            <table class="ttd-table">
                <tr>
                    <td style="width: 33%;">
                        Mengetahui,<br>
                        Orang Tua / Wali Peserta Didik
                        <div class="ttd-space"></div>
                        <div class="ttd-name">...................................................</div>
                    </td>
                    <td style="width: 33%;">
                        Wali Kelas
                        <div class="ttd-space"></div>
                        <div class="ttd-name">{{ $siswa->wali_nama ?: '...................................................' }}</div>
                        <div>NIP. {{ $siswa->wali_nip ?: '-' }}</div>
                    </td>
                    <td style="width: 34%;">
                        Kepala Sekolah
                        <div class="ttd-space"></div>
                        <div class="ttd-name">{{ $kepsek?->nama ?: '...................................................' }}</div>
                        <div>NIP. {{ $kepsek?->nip ?: '-' }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <script>
        window.addEventListener('load', function () {
            // Berikan sedikit jeda render sebelum mencetak
            setTimeout(function () {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
