<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>
        @if ($tipe === 'siswa')
            Rekap Presensi Peserta Didik — {{ $siswa->nama }}
        @elseif ($tipe === 'hari')
            Presensi Harian Kelas — {{ $activeRombel->nama }} ({{ $tanggal }})
        @elseif ($tipe === 'bulan')
            Rekap Presensi Bulanan — {{ $activeRombel->nama }} ({{ $bulan }})
        @elseif ($tipe === 'semester')
            Rekap Presensi Semester {{ $semester }} — {{ $activeRombel->nama }}
        @elseif ($tipe === 'tahun')
            Rekap Presensi Tahunan — {{ $activeRombel->nama }} ({{ $tahunAjaran }})
        @else
            Laporan Presensi — {{ $activeRombel->nama }}
        @endif
    </title>
    <style>
        * {
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }
        @page {
            margin: {{ ($tipe === 'semester' || $tipe === 'tahun' || $tipe === 'bulan') ? '8mm 8mm 8mm 8mm' : '10mm 10mm 12mm 10mm' }};
            size: {{ ($tipe === 'bulan' || $tipe === 'semester' || $tipe === 'tahun') ? 'A4 landscape' : 'A4 portrait' }};
        }
        body {
            margin: 0;
            padding: 0;
            color: #111827;
            font-size: {{ ($tipe === 'semester' || $tipe === 'tahun') ? '9.5px' : '10.5px' }};
            line-height: 1.35;
            background: #fff;
        }

        /* Kop Surat Banner Resmi (Letterhead Hasil Upload) */
        .kop-banner-wrap {
            width: 100%;
            text-align: center;
            margin-bottom: {{ ($tipe === 'semester' || $tipe === 'tahun') ? '4px' : '8px' }};
        }
        .kop-banner-img {
            width: 100%;
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .kop-table td {
            vertical-align: middle;
            padding: 0;
        }
        .kop-logo {
            width: 70px;
            text-align: center;
        }
        .kop-logo img {
            width: 60px;
            height: auto;
        }
        .kop-text {
            text-align: center;
            padding-right: 70px;
        }
        .kop-instansi {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #374151;
            margin: 0;
        }
        .kop-sekolah {
            font-size: 16px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #000;
            margin: 2px 0;
        }
        .kop-alamat {
            font-size: 9.5px;
            color: #4b5563;
            margin: 0;
        }
        .kop-divider {
            border-top: 2.5px solid #000;
            border-bottom: 1px solid #000;
            height: 3px;
            margin-bottom: 10px;
        }

        .doc-title-wrap {
            text-align: center;
            margin-bottom: {{ ($tipe === 'semester' || $tipe === 'tahun') ? '5px' : '10px' }};
        }
        .doc-title {
            font-size: {{ ($tipe === 'semester' || $tipe === 'tahun') ? '12px' : '13px' }};
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 2px 0;
            color: #111827;
        }
        .doc-subtitle {
            font-size: {{ ($tipe === 'semester' || $tipe === 'tahun') ? '9.5px' : '10.5px' }};
            color: #4b5563;
            margin: 0;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: {{ ($tipe === 'semester' || $tipe === 'tahun') ? '6px' : '10px' }};
            font-size: {{ ($tipe === 'semester' || $tipe === 'tahun') ? '9.5px' : '10px' }};
        }
        .meta-table td {
            padding: 1.5px 0;
            vertical-align: top;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: {{ ($tipe === 'semester' || $tipe === 'tahun') ? '6px' : '10px' }};
            font-size: {{ $tipe === 'bulan' ? '8.5px' : (($tipe === 'semester' || $tipe === 'tahun') ? '9px' : '10px') }};
        }
        .data-table th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: 700;
            text-align: center;
            border: 1px solid #94a3b8;
            padding: {{ $tipe === 'bulan' ? '3px 2px' : (($tipe === 'semester' || $tipe === 'tahun') ? '3px 4px' : '5px 6px') }};
            vertical-align: middle;
        }
        .data-table td {
            border: 1px solid #cbd5e1;
            padding: {{ $tipe === 'bulan' ? '2.5px 2px' : (($tipe === 'semester' || $tipe === 'tahun') ? '2px 4px' : '4px 6px') }};
            vertical-align: middle;
        }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-mono { font-family: monospace; }
        .font-bold { font-weight: bold; }

        /* Badge status sederhana untuk print */
        .status-badge {
            font-weight: bold;
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 9px;
        }
        .status-h { background: #dcfce7; color: #15803d; }
        .status-t { background: #fef3c7; color: #b45309; }
        .status-i { background: #dbeafe; color: #1d4ed8; }
        .status-s { background: #ede9fe; color: #6d28d9; }
        .status-a { background: #fee2e2; color: #b91c1c; }

        /* Summary Box */
        .summary-box {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 14px;
            font-size: 10px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table td {
            padding: 2px 6px;
            border: none;
        }

        /* Signatures */
        .sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: {{ ($tipe === 'semester' || $tipe === 'tahun') ? '8px' : '15px' }};
            page-break-inside: avoid;
            font-size: {{ ($tipe === 'semester' || $tipe === 'tahun') ? '9.5px' : '10.5px' }};
        }
        .sig-col-left {
            width: 40%;
            text-align: center;
            vertical-align: top;
            padding: 0 5px;
        }
        .sig-col-spacer {
            width: 20%;
        }
        .sig-col-right {
            width: 40%;
            text-align: center;
            vertical-align: top;
            padding: 0 5px;
        }
        .sig-space {
            height: {{ ($tipe === 'semester' || $tipe === 'tahun') ? '38px' : '50px' }};
        }
        .sig-name {
            font-weight: 800;
            text-decoration: underline;
            color: #000;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: #fff !important;
                padding: 0 !important;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="background: #f1f5f9; min-height: 100vh; padding: 20px 0;">

    <!-- Action Toolbar (Hanya Muncul di Layar, Tersembunyi saat Dicetak) -->
    <div class="no-print" style="position: fixed; top: 16px; right: 24px; z-index: 999999; display: flex; gap: 10px; background: rgba(15, 23, 42, 0.88); backdrop-filter: blur(8px); padding: 10px 16px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.35); border: 1px solid rgba(255,255,255,0.15);">
        <button type="button" onclick="window.print()" style="background: #4f46e5; color: #fff; border: none; padding: 8px 18px; border-radius: 8px; font-weight: 700; font-size: 0.88rem; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 6px rgba(79, 70, 229, 0.4);">
            <i class="fas fa-print"></i> Cetak / Simpan PDF
        </button>
        <button type="button" onclick="window.close()" style="background: rgba(255,255,255,0.18); color: #fff; border: none; padding: 8px 14px; border-radius: 8px; font-weight: 600; font-size: 0.88rem; cursor: pointer; display: flex; align-items: center; gap: 6px;">
            <i class="fas fa-times"></i> Tutup
        </button>
    </div>

    <div style="background: #fff; max-width: {{ ($tipe === 'bulan' || $tipe === 'semester' || $tipe === 'tahun') ? '297mm' : '210mm' }}; margin: 0 auto; padding: 15mm; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border-radius: 6px;">

    @php
        $meta = $sekolahMeta ?? (class_exists(\App\Models\SekolahMeta::class) ? \App\Models\SekolahMeta::first() : null);

        // 1. Cek berkas Kop Surat Resmi (Banner Letterhead) yang telah diunggah di Identitas Sekolah
        $kopBase64 = null;
        if (!empty($meta?->kop_path)) {
            $storageKop = storage_path('app/public/' . $meta->kop_path);
            if (file_exists($storageKop)) {
                $kopBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($storageKop));
            } elseif (file_exists(public_path('storage/' . $meta->kop_path))) {
                $kopBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('storage/' . $meta->kop_path)));
            }
        }

        // 2. Cek berkas Logo Resmi Sekolah (digunakan jika kop surat banner belum diunggah)
        $logoBase64 = null;
        if (!empty($meta?->logo_path)) {
            $storageLogo = storage_path('app/public/' . $meta->logo_path);
            if (file_exists($storageLogo)) {
                $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($storageLogo));
            } elseif (file_exists(public_path('storage/' . $meta->logo_path))) {
                $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('storage/' . $meta->logo_path)));
            }
        }
        if (!$logoBase64) {
            $defaultLogos = [
                public_path('img/logo-sekolah.png'),
                public_path('img/logo-icon.png'),
            ];
            foreach ($defaultLogos as $def) {
                if (file_exists($def)) {
                    $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($def));
                    break;
                }
            }
        }

        // 3. Resolusi NIP Wali Kelas jika belum didefinisikan
        if (empty($waliNip) || $waliNip === '—') {
            $waliGtkObj = null;
            if (!empty($activeRombel?->ptk_id)) {
                $waliGtkObj = \Illuminate\Support\Facades\DB::table('gtk')->where('ptk_id', $activeRombel->ptk_id)->first();
            }
            if (!$waliGtkObj && !empty($activeRombel?->ptk_id_str)) {
                $waliGtkObj = \Illuminate\Support\Facades\DB::table('gtk')->where('nama', $activeRombel->ptk_id_str)->first();
            }
            if ($waliGtkObj) {
                $waliNip = $waliGtkObj->nip ?: ($waliGtkObj->nuptk ?: '—');
            }
        }
    @endphp


    <div style="padding: 10px;">
        @if ($kopBase64)
            <!-- KOP SURAT RESMI SEKOLAH (DARI BERKAS YANG DIUNGGAH DI SISTEM) -->
            <div class="kop-banner-wrap">
                <img src="{{ $kopBase64 }}" class="kop-banner-img" alt="Kop Surat Resmi Sekolah">
            </div>
        @else
            <!-- KOP SURAT TEKS DENGAN LOGO RESMI SEKOLAH -->
            <table class="kop-table">
                <tr>
                    <td class="kop-logo">
                        @if ($logoBase64)
                            <img src="{{ $logoBase64 }}" alt="Logo Sekolah">
                        @endif
                    </td>
                    <td class="kop-text">
                        <div class="kop-instansi">DINAS PENDIDIKAN DAN KEBUDAYAAN</div>
                        <div class="kop-sekolah">{{ $sekolah?->nama ?? 'SATUAN PENDIDIKAN' }}</div>
                        <div class="kop-alamat">
                            NPSN: {{ $sekolah?->npsn ?? '-' }} &bull;
                            {{ $sekolah?->alamat_jalan ?? '' }}
                            {{ $sekolah?->desa_kelurahan ? 'Desa ' . $sekolah->desa_kelurahan . ',' : '' }}
                            {{ $sekolah?->kecamatan ? 'Kec. ' . $sekolah->kecamatan . ',' : '' }}
                            {{ $sekolah?->kabupaten_kota ?? '' }}
                            {{ $sekolah?->kode_pos ? 'Kode Pos ' . $sekolah->kode_pos : '' }}
                        </div>
                        <div class="kop-alamat">
                            Telp: {{ $sekolah?->nomor_telepon ?? '-' }} &bull; Email: {{ $sekolah?->email ?? '-' }} &bull; Website: {{ $sekolah?->website ?? '-' }}
                        </div>
                    </td>
                </tr>
            </table>
            <div class="kop-divider"></div>
        @endif

        <!-- JUDUL LAPORAN BERDASARKAN TIPE -->
        <div class="doc-title-wrap">
            @if ($tipe === 'siswa')
                <div class="doc-title">KARTU REKAPITULASI PRESENSI PESERTA DIDIK</div>
                <div class="doc-subtitle">Periode Bulan: {{ $bulanLabel }}</div>
            @elseif ($tipe === 'hari')
                <div class="doc-title">LEMBAR PRESENSI HARIAN KELAS</div>
                <div class="doc-subtitle">Hari / Tanggal: {{ $tanggalLabel }}</div>
            @elseif ($tipe === 'bulan')
                <div class="doc-title">MATRIKS REKAPITULASI PRESENSI BULANAN</div>
                <div class="doc-subtitle">Bulan: {{ $bulanLabel }}</div>
            @elseif ($tipe === 'semester')
                <div class="doc-title">REKAPITULASI PRESENSI KELAS PER SEMESTER</div>
                <div class="doc-subtitle">Semester {{ $semester }} &bull; Tahun Ajaran {{ $tahunAkademik }}</div>
            @elseif ($tipe === 'tahun')
                <div class="doc-title">REKAPITULASI PRESENSI KELAS PER TAHUN AJARAN</div>
                <div class="doc-subtitle">Tahun Ajaran {{ $tahunAjaran }}</div>
            @endif
        </div>

        <!-- METADATA INFORMASI KELAS & IDENTITAS -->
        <table class="meta-table">
            <tr>
                <td style="width: 15%; font-weight: bold;">Rombongan Belajar</td>
                <td style="width: 35%;">: {{ $activeRombel->nama }}</td>
                <td style="width: 15%; font-weight: bold;">Wali Kelas</td>
                <td style="width: 35%;">: {{ $waliNama }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Tingkat / Jurusan</td>
                <td>: Kelas {{ $activeRombel->tingkat_pendidikan_id ?? '-' }} &bull; {{ $activeRombel->jurusan_id_str ?: 'Umum' }}</td>
                <td style="font-weight: bold;">Waktu Cetak</td>
                <td>: {{ $generatedAt }}</td>
            </tr>
            @if ($tipe === 'siswa')
                <tr>
                    <td style="font-weight: bold;">Nama Peserta Didik</td>
                    <td>: <strong>{{ $siswa->nama }}</strong></td>
                    <td style="font-weight: bold;">NISN / NIPD</td>
                    <td>: {{ $siswa->nisn ?: '-' }} / {{ $siswa->nipd ?: '-' }} ({{ $siswa->jenis_kelamin === 'L' ? 'Laki-Laki' : 'Perempuan' }})</td>
                </tr>
            @endif
        </table>

        <!-- ================================================================= -->
        <!-- KONTEN 1: PER PESERTA DIDIK (INDIVIDU) -->
        <!-- ================================================================= -->
        @if ($tipe === 'siswa')
            <div class="summary-box">
                <table class="summary-table">
                    <tr>
                        <td><strong>Hadir Tepat (H):</strong> {{ $stats['hadir'] }} hari</td>
                        <td><strong>Terlambat (T):</strong> {{ $stats['terlambat'] }} hari</td>
                        <td><strong>Izin (I):</strong> {{ $stats['izin'] }} hari</td>
                        <td><strong>Sakit (S):</strong> {{ $stats['sakit'] }} hari</td>
                        <td><strong>Alpha (A):</strong> {{ $stats['alpha'] }} hari</td>
                        <td><strong>% Kehadiran:</strong> <span style="font-size: 12px; font-weight: bold; color: #16a34a;">{{ $stats['persen'] }}%</span></td>
                    </tr>
                </table>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 35px;">No</th>
                        <th style="width: 85px;">Tanggal</th>
                        <th style="width: 70px;">Hari</th>
                        <th style="width: 65px;">Status</th>
                        <th style="width: 80px;">Jam Masuk</th>
                        <th style="width: 80px;">Jam Pulang</th>
                        <th style="width: 75px;">Metode</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $i => $log)
                        <tr>
                            <td class="text-center">{{ $i + 1 }}</td>
                            <td class="text-center font-mono">{{ date('d/m/Y', strtotime($log->tanggal)) }}</td>
                            <td>{{ \Carbon\Carbon::parse($log->tanggal)->translatedFormat('l') }}</td>
                            <td class="text-center">
                                @php
                                    $cls = match($log->status) {
                                        'H' => 'status-h',
                                        'T' => 'status-t',
                                        'I' => 'status-i',
                                        'S' => 'status-s',
                                        'A' => 'status-a',
                                        default => ''
                                    };
                                    $lbl = \App\Models\PresensiHarian::STATUS_LABELS[$log->status] ?? $log->status;
                                @endphp
                                <span class="status-badge {{ $cls }}">{{ $lbl }}</span>
                            </td>
                            <td class="text-center font-mono">{{ $log->jam_masuk ? substr($log->jam_masuk, 0, 5) . ' WIB' : '-' }}</td>
                            <td class="text-center font-mono">{{ $log->jam_pulang ? substr($log->jam_pulang, 0, 5) . ' WIB' : '-' }}</td>
                            <td class="text-center">{{ strtoupper($log->metode_masuk ?: '-') }}</td>
                            <td>{{ $log->keterangan ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center" style="padding: 16px; color: #64748b;">
                                Belum ada rekam presensi untuk peserta didik pada bulan ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

        <!-- ================================================================= -->
        <!-- KONTEN 2: PER HARI (HARIAN) -->
        <!-- ================================================================= -->
        @elseif ($tipe === 'hari')
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 30px;">No</th>
                        <th style="width: 90px;">NISN / NIPD</th>
                        <th>Nama Peserta Didik</th>
                        <th style="width: 35px;">L/P</th>
                        <th style="width: 75px;">Status</th>
                        <th style="width: 80px;">Masuk</th>
                        <th style="width: 80px;">Pulang</th>
                        <th style="width: 65px;">Metode</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $cHadir = 0; $cTelat = 0; $cIzin = 0; $cSakit = 0; $cAlpha = 0; $cBelum = 0;
                    @endphp
                    @forelse ($siswaList as $i => $s)
                        @php
                            if ($s->status === 'H') $cHadir++;
                            elseif ($s->status === 'T') $cTelat++;
                            elseif ($s->status === 'I') $cIzin++;
                            elseif ($s->status === 'S') $cSakit++;
                            elseif ($s->status === 'A') $cAlpha++;
                            else $cBelum++;

                            $cls = match($s->status) {
                                'H' => 'status-h',
                                'T' => 'status-t',
                                'I' => 'status-i',
                                'S' => 'status-s',
                                'A' => 'status-a',
                                default => ''
                            };
                            $lbl = \App\Models\PresensiHarian::STATUS_LABELS[$s->status] ?? ($s->status ?: 'Belum');
                        @endphp
                        <tr>
                            <td class="text-center">{{ $i + 1 }}</td>
                            <td class="font-mono">{{ $s->nisn ?: ($s->nipd ?: '-') }}</td>
                            <td class="font-bold">{{ $s->nama }}</td>
                            <td class="text-center">{{ $s->jenis_kelamin }}</td>
                            <td class="text-center">
                                <span class="status-badge {{ $cls }}">{{ $lbl }}</span>
                            </td>
                            <td class="text-center font-mono">{{ $s->jam_masuk ? substr($s->jam_masuk, 0, 5) . ' WIB' : '-' }}</td>
                            <td class="text-center font-mono">{{ $s->jam_pulang ? substr($s->jam_pulang, 0, 5) . ' WIB' : '-' }}</td>
                            <td class="text-center">{{ strtoupper($s->metode_masuk ?: '-') }}</td>
                            <td>{{ $s->keterangan ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center" style="padding: 16px; color: #64748b;">
                                Tidak ada data peserta didik pada rombel ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="summary-box">
                <table class="summary-table">
                    <tr>
                        <td><strong>Total Peserta Didik:</strong> {{ $siswaList->count() }} orang</td>
                        <td><strong>Hadir (H):</strong> {{ $cHadir }}</td>
                        <td><strong>Terlambat (T):</strong> {{ $cTelat }}</td>
                        <td><strong>Izin (I):</strong> {{ $cIzin }}</td>
                        <td><strong>Sakit (S):</strong> {{ $cSakit }}</td>
                        <td><strong>Alpha (A):</strong> {{ $cAlpha }}</td>
                        <td><strong>Belum Absen:</strong> {{ $cBelum }}</td>
                        <td><strong>% Kehadiran:</strong> <strong style="color: #16a34a;">{{ $siswaList->count() > 0 ? round((($cHadir + $cTelat) / $siswaList->count()) * 100, 1) : 0 }}%</strong></td>
                    </tr>
                </table>
            </div>

        <!-- ================================================================= -->
        <!-- KONTEN 3: PER BULAN (MATRIKS 1 S.D. 31) -->
        <!-- ================================================================= -->
        @elseif ($tipe === 'bulan')
            <table class="data-table">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 25px;">No</th>
                        <th rowspan="2" style="width: 75px;">NISN</th>
                        <th rowspan="2" style="min-width: 140px;">Nama Peserta Didik</th>
                        <th rowspan="2" style="width: 22px;">L/P</th>
                        <th colspan="{{ $daysInMonth }}" style="padding: 2px;">Tanggal Presensi (1 s.d. {{ $daysInMonth }})</th>
                        <th colspan="6" style="padding: 2px;">Rekapitulasi</th>
                    </tr>
                    <tr>
                        @for ($d = 1; $d <= $daysInMonth; $d++)
                            <th style="width: 16px; padding: 2px 0;">{{ $d }}</th>
                        @endfor
                        <th style="width: 20px;">H</th>
                        <th style="width: 20px;">T</th>
                        <th style="width: 20px;">I</th>
                        <th style="width: 20px;">S</th>
                        <th style="width: 20px;">A</th>
                        <th style="width: 32px;">%</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($matrix as $idx => $row)
                        <tr>
                            <td class="text-center">{{ $idx + 1 }}</td>
                            <td class="font-mono text-center">{{ $row['siswa']->nisn ?: '-' }}</td>
                            <td class="font-bold">{{ $row['siswa']->nama }}</td>
                            <td class="text-center">{{ $row['siswa']->jenis_kelamin }}</td>
                            @for ($d = 1; $d <= $daysInMonth; $d++)
                                @php
                                    $st = $row['days'][$d] ?? '-';
                                    $stColor = match($st) {
                                        'H' => '#15803d',
                                        'T' => '#b45309',
                                        'I' => '#1d4ed8',
                                        'S' => '#6d28d9',
                                        'A' => '#b91c1c',
                                        default => '#94a3b8'
                                    };
                                @endphp
                                <td class="text-center font-bold" style="color: {{ $stColor }}; padding: 2px 0;">
                                    {{ $st !== '-' ? $st : '.' }}
                                </td>
                            @endfor
                            <td class="text-center font-bold" style="background: #f0fdf4;">{{ $row['rekap']['h'] }}</td>
                            <td class="text-center font-bold" style="background: #fffbeb;">{{ $row['rekap']['t'] }}</td>
                            <td class="text-center font-bold" style="background: #eff6ff;">{{ $row['rekap']['i'] }}</td>
                            <td class="text-center font-bold" style="background: #faf5ff;">{{ $row['rekap']['s'] }}</td>
                            <td class="text-center font-bold" style="background: #fef2f2;">{{ $row['rekap']['a'] }}</td>
                            <td class="text-center font-bold">{{ $row['rekap']['persen'] }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="font-size: 9px; color: #475569; margin-bottom: 10px;">
                <strong>Keterangan Kode:</strong>
                <span style="color: #15803d; font-weight: bold; margin-right: 8px;">H = Hadir Tepat Waktu</span>
                <span style="color: #b45309; font-weight: bold; margin-right: 8px;">T = Terlambat</span>
                <span style="color: #1d4ed8; font-weight: bold; margin-right: 8px;">I = Izin</span>
                <span style="color: #6d28d9; font-weight: bold; margin-right: 8px;">S = Sakit</span>
                <span style="color: #b91c1c; font-weight: bold; margin-right: 8px;">A = Alpha / Tanpa Keterangan</span>
                <span style="color: #94a3b8; font-weight: bold;">. = Libur / Belum Ada Data</span>
            </div>

        <!-- ================================================================= -->
        <!-- KONTEN 4: PER SEMESTER -->
        <!-- ================================================================= -->
        @elseif ($tipe === 'semester')
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 35px;">No</th>
                        <th style="width: 95px;">NISN</th>
                        <th>Nama Peserta Didik</th>
                        <th style="width: 40px;">L/P</th>
                        <th style="width: 70px;">Hadir (H)</th>
                        <th style="width: 70px;">Terlambat (T)</th>
                        <th style="width: 70px;">Izin (I)</th>
                        <th style="width: 70px;">Sakit (S)</th>
                        <th style="width: 70px;">Alpha (A)</th>
                        <th style="width: 80px;">Total Pertemuan</th>
                        <th style="width: 85px;">% Kehadiran</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rekapSemester as $idx => $r)
                        <tr>
                            <td class="text-center">{{ $idx + 1 }}</td>
                            <td class="font-mono text-center">{{ $r['siswa']->nisn ?: '-' }}</td>
                            <td class="font-bold">{{ $r['siswa']->nama }}</td>
                            <td class="text-center">{{ $r['siswa']->jenis_kelamin }}</td>
                            <td class="text-center font-bold" style="color: #15803d;">{{ $r['h'] }}</td>
                            <td class="text-center font-bold" style="color: #b45309;">{{ $r['t'] }}</td>
                            <td class="text-center font-bold" style="color: #1d4ed8;">{{ $r['i'] }}</td>
                            <td class="text-center font-bold" style="color: #6d28d9;">{{ $r['s'] }}</td>
                            <td class="text-center font-bold" style="color: #b91c1c;">{{ $r['a'] }}</td>
                            <td class="text-center font-mono">{{ $r['total'] }} hari</td>
                            <td class="text-center font-bold" style="color: {{ $r['persen'] >= 85 ? '#15803d' : ($r['persen'] >= 75 ? '#b45309' : '#b91c1c') }};">
                                {{ $r['persen'] }}%
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        <!-- ================================================================= -->
        <!-- KONTEN 5: PER TAHUN AJARAN -->
        <!-- ================================================================= -->
        @elseif ($tipe === 'tahun')
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 35px;">No</th>
                        <th style="width: 95px;">NISN</th>
                        <th>Nama Peserta Didik</th>
                        <th style="width: 40px;">L/P</th>
                        <th style="width: 70px;">Hadir (H)</th>
                        <th style="width: 70px;">Terlambat (T)</th>
                        <th style="width: 70px;">Izin (I)</th>
                        <th style="width: 70px;">Sakit (S)</th>
                        <th style="width: 70px;">Alpha (A)</th>
                        <th style="width: 80px;">Total Hari</th>
                        <th style="width: 85px;">% Kehadiran</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rekapTahun as $idx => $r)
                        <tr>
                            <td class="text-center">{{ $idx + 1 }}</td>
                            <td class="font-mono text-center">{{ $r['siswa']->nisn ?: '-' }}</td>
                            <td class="font-bold">{{ $r['siswa']->nama }}</td>
                            <td class="text-center">{{ $r['siswa']->jenis_kelamin }}</td>
                            <td class="text-center font-bold" style="color: #15803d;">{{ $r['h'] }}</td>
                            <td class="text-center font-bold" style="color: #b45309;">{{ $r['t'] }}</td>
                            <td class="text-center font-bold" style="color: #1d4ed8;">{{ $r['i'] }}</td>
                            <td class="text-center font-bold" style="color: #6d28d9;">{{ $r['s'] }}</td>
                            <td class="text-center font-bold" style="color: #b91c1c;">{{ $r['a'] }}</td>
                            <td class="text-center font-mono">{{ $r['total'] }} hari</td>
                            <td class="text-center font-bold" style="color: {{ $r['persen'] >= 85 ? '#15803d' : ($r['persen'] >= 75 ? '#b45309' : '#b91c1c') }};">
                                {{ $r['persen'] }}%
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <!-- TANDA TANGAN (SIGNATURE BLOCK) -->
        <table class="sig-table">
            <tr>
                <td class="sig-col-left">
                    Mengetahui,<br>
                    Kepala {{ $sekolah?->nama ?? 'Sekolah' }}
                    <div class="sig-space"></div>
                    <div class="sig-name">{{ $kepalaSekolah?->nama ?? '......................................................' }}</div>
                    <div class="sig-nip">NIP: {{ $kepalaSekolah?->nip ?: ($kepalaSekolah?->nuptk ?: '—') }}</div>
                </td>
                <td class="sig-col-spacer"></td>
                <td class="sig-col-right">
                    {{ $sekolah?->kabupaten_kota ? str_replace(['Kabupaten ', 'Kota '], '', $sekolah->kabupaten_kota) : 'Tempat' }}, {{ now()->translatedFormat('d F Y') }}<br>
                    Wali Kelas {{ $activeRombel->nama }}
                    <div class="sig-space"></div>
                    <div class="sig-name">{{ $waliNama }}</div>
                    <div class="sig-nip">NIP: {{ !empty($waliNip) && $waliNip !== '—' ? $waliNip : '—' }}</div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
