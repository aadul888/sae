<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kinerja - {{ $profile['nama'] }} - {{ $range['label'] }}</title>
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

        /* Container Lembar Dokumen A4 (Identik dengan Jadwal KBM) */
        .cetak-page {
            background: #fff;
            margin: 0 auto 28px auto;
            padding: 24px 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            position: relative;
            overflow: hidden;
            @if ($orientasi === 'landscape')
                max-width: 1100px;
            @else
                max-width: 850px;
            @endif
        }

        /* Watermark Logo SAE (Identik dengan Jadwal KBM) */
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
            opacity: 0.05;
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
            letter-spacing: 0.6px;
            color: #111827;
        }

        .kop-subtitle {
            font-size: 0.78rem;
            color: #4b5563;
            line-height: 1.35;
        }

        /* Header Judul Dokumen (Identik dengan Jadwal KBM) */
        .doc-header {
            text-align: center;
            margin-bottom: 18px;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .doc-header .school-name {
            font-size: 1.15rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #111827;
        }

        .doc-header .doc-title-main {
            font-size: 1.1rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #1e3a8a;
            margin: 3px 0 2px 0;
        }

        .doc-header .doc-subtitle {
            font-size: 0.8rem;
            color: #4b5563;
            font-weight: 600;
        }

        /* Info Box Identitas Pegawai (Identik dengan Info Box Jadwal KBM) */
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
            width: 130px;
        }

        .info-val {
            font-weight: 700;
            color: #111827;
        }

        /* Stats Box Ringkasan Kinerja (Selaras dengan Jadwal KBM) */
        .stats-box {
            display: flex;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            border-radius: 6px;
            margin-bottom: 16px;
            overflow: hidden;
            position: relative;
            z-index: 1;
        }

        .stat-item {
            flex: 1;
            text-align: center;
            padding: 8px 6px;
            border-right: 1px solid #e5e7eb;
        }

        .stat-item:last-child {
            border-right: none;
        }

        .stat-item .val {
            font-size: 1.05rem;
            font-weight: 800;
            color: #1e3a8a;
            display: block;
        }

        .stat-item .lbl {
            font-size: 0.72rem;
            text-transform: uppercase;
            font-weight: 700;
            color: #6b7280;
            letter-spacing: 0.4px;
        }

        /* Section Subheading */
        .section-subhead {
            font-size: 0.82rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #1e3a8a;
            margin: 16px 0 8px 0;
            display: flex;
            align-items: center;
            gap: 6px;
            position: relative;
            z-index: 1;
        }

        /* Tabel Data (Identik dengan .table-rombel / .table-guru di Jadwal KBM) */
        .table-data {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.76rem;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .table-data th,
        .table-data td {
            border: 1px solid #9ca3af;
            padding: 6px 8px;
            vertical-align: middle;
        }

        .table-data th {
            background-color: #f3f4f6;
            font-weight: 700;
            color: #111827;
            text-align: center;
            font-size: 0.74rem;
            text-transform: uppercase;
        }

        .table-data tr:nth-child(even) td {
            background-color: #fafbfc;
        }

        .table-data .col-no {
            width: 32px;
            text-align: center;
        }

        .table-data .col-tgl {
            width: 85px;
            text-align: center;
            white-space: nowrap;
        }

        .table-data .col-jam {
            width: 85px;
            text-align: center;
            white-space: nowrap;
            font-family: monospace;
            font-size: 0.76rem;
        }

        .table-data .col-output {
            width: 95px;
            text-align: center;
        }

        .table-data .col-durasi {
            width: 65px;
            text-align: center;
            font-family: monospace;
            font-weight: 600;
        }

        .table-data .col-status {
            width: 80px;
            text-align: center;
        }

        /* Status Badges (Sesuai palette warna Jadwal KBM) */
        .badge-kbm {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 700;
            text-align: center;
            white-space: nowrap;
        }

        .badge-selesai {
            background-color: #f0fdf4;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .badge-proses {
            background-color: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }

        .badge-tertunda {
            background-color: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .center {
            text-align: center;
        }

        /* Signatures (Identik dengan .signature-container di Jadwal KBM) */
        .signature-container {
            display: flex;
            justify-content: space-between;
            margin-top: 24px;
            font-size: 0.82rem;
            page-break-inside: avoid;
            position: relative;
            z-index: 1;
        }

        .sig-box {
            text-align: center;
            width: 230px;
        }

        .sig-title {
            color: #4b5563;
            line-height: 1.35;
            margin-bottom: 55px;
        }

        .sig-name {
            font-weight: 700;
            text-decoration: underline;
            color: #111827;
        }

        .sig-nip {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 2px;
        }

        /* Footer Verifikasi Dokumen Digital */
        .doc-footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px dashed #d1d5db;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
            color: #6b7280;
            position: relative;
            z-index: 1;
        }

        .qr-badge {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .qr-badge img {
            width: 44px;
            height: 44px;
            border: 1px solid #d1d5db;
            padding: 2px;
            background: #fff;
            border-radius: 4px;
        }

        /* Floating bar (Identik dengan Jadwal KBM) */
        .no-print-bar {
            position: fixed;
            bottom: 20px;
            right: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.96);
            padding: 8px 16px;
            border-radius: 50px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(6px);
            z-index: 1000;
            border: 1px solid rgba(229, 231, 235, 0.8);
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 0.82rem;
            font-weight: 700;
            border-radius: 30px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .btn-print {
            background: #2563eb;
            color: #fff;
        }

        .btn-print:hover {
            background: #1d4ed8;
        }

        .btn-back {
            background: #4b5563;
            color: #fff;
        }

        .btn-back:hover {
            background: #374151;
        }

        /* Orientation switch pills */
        .orientation-switch {
            display: inline-flex;
            align-items: center;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 30px;
            padding: 2px;
        }

        .btn-orientasi {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 14px;
            font-size: 0.8rem;
            font-weight: 700;
            color: #4b5563;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .btn-orientasi:hover {
            color: #111827;
        }

        .btn-orientasi.active {
            background: #1e3a8a;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(30, 58, 138, 0.3);
        }

        /* Media Print (Identik dengan Jadwal KBM) */
        @page {
            size: A4 {{ $orientasi }};
            margin: 10mm 12mm;
        }

        @media print {
            body {
                background: #fff !important;
                padding: 0 !important;
            }

            .cetak-page {
                box-shadow: none !important;
                padding: 0 !important;
                max-width: 100% !important;
                margin-bottom: 0 !important;
                border-radius: 0 !important;
            }

            .no-print-bar {
                display: none !important;
            }
        }
    </style>
</head>

<body>

    {{-- Floating Bar (Identik dengan Jadwal KBM) --}}
    <div class="no-print-bar">
        <a href="{{ route('dashboard.tendik.laporan.index', request()->except('orientasi')) }}" class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>

        <div class="orientation-switch">
            <a href="{{ request()->fullUrlWithQuery(['orientasi' => 'portrait']) }}"
                class="btn-orientasi {{ $orientasi === 'portrait' ? 'active' : '' }}" title="Kertas A4 Portrait">
                <i class="fas fa-file"></i> Portrait
            </a>
            <a href="{{ request()->fullUrlWithQuery(['orientasi' => 'landscape']) }}"
                class="btn-orientasi {{ $orientasi === 'landscape' ? 'active' : '' }}" title="Kertas A4 Landscape">
                <i class="fas fa-file-invoice"></i> Landscape
            </a>
        </div>

        <button onclick="window.print()" class="btn-action btn-print">
            <i class="fas fa-print"></i> Cetak Laporan (Print / PDF)
        </button>
    </div>

    {{-- Lembar Halaman Cetak --}}
    <div class="cetak-page">
        <!-- Watermark Logo SAE (Identik dengan Jadwal KBM) -->
        <div class="watermark-sae">
            <img src="{{ asset('img/logo-icon.png') }}" alt="Watermark SAE">
        </div>

        <!-- 1. KOP SURAT SEKOLAH RESMI YANG TELAH DISEDIAKAN & DITETAPKAN -->
        <div class="kop-container">
            @if (!empty($sekolahMeta?->kop_url))
                <!-- File Gambar Kop Surat Resmi dari Identitas Sekolah -->
                <img src="{{ $sekolahMeta->kop_url }}" alt="Kop Surat Resmi {{ $sekolah->nama ?? 'Sekolah' }}" class="kop-image">
            @else
                <!-- Fallback Kop Standar Resmi -->
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

        <!-- 2. Header Dokumen Bersih (Identik dengan Jadwal KBM) -->
        <div class="doc-header">
            @if (empty($sekolahMeta?->kop_url))
                <div class="school-name">{{ $sekolah->nama ?? 'SMK NEGERI 1 PAGELARAN' }}</div>
            @endif
            <div class="doc-title-main">LAPORAN CAPAIAN KINERJA &amp; LOG AKTIVITAS HARIAN</div>
            <div class="doc-subtitle">PERIODE: {{ strtoupper($range['label']) }} &bull; FORMAT: A4 {{ strtoupper($orientasi) }}</div>
        </div>

        <!-- 3. Info Box Identitas Pegawai (Identik dengan Jadwal KBM) -->
        <div class="info-box">
            <div>
                <div class="info-item">
                    <span class="info-label">Nama Pegawai:</span>
                    <span class="info-val">{{ $profile['nama'] }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">NIP / NUPTK:</span>
                    <span class="info-val">{{ $profile['nip'] ?: ($profile['nuptk'] ?: '—') }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Jabatan / Bagian:</span>
                    <span class="info-val">{{ $profile['jabatan'] }}</span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Status Kepegawaian:</span>
                    <span class="info-val">{{ $profile['status_kepegawaian'] ?? 'Pegawai Tetap' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Rentang Waktu:</span>
                    <span class="info-val">
                        {{ \Carbon\Carbon::parse($range['start'])->translatedFormat('d M Y') }} s.d.
                        {{ \Carbon\Carbon::parse($range['end'])->translatedFormat('d M Y') }}
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Unit Administrasi:</span>
                    <span class="info-val">{{ $sekolah->nama ?? 'SMK NEGERI 1 PAGELARAN' }}</span>
                </div>
            </div>
        </div>

        <!-- 4. Ringkasan Kinerja (Stats Box) -->
        <div class="stats-box">
            <div class="stat-item">
                <span class="val">{{ $totalAktivitas }}</span>
                <span class="lbl">Total Tugas</span>
            </div>
            <div class="stat-item">
                <span class="val" style="color: #047857;">{{ $persentaseSelesai }}%</span>
                <span class="lbl">Tuntas Dikoreksi</span>
            </div>
            <div class="stat-item">
                <span class="val" style="color: #b45309;">{{ $totalProses }}</span>
                <span class="lbl">Dalam Proses</span>
            </div>
            <div class="stat-item">
                <span class="val">{{ $totalJam }} Jam</span>
                <span class="lbl">Akumulasi Kerja</span>
            </div>
            <div class="stat-item">
                <span class="val">{{ $hariAktifBekerja }} Hari</span>
                <span class="lbl">Hari Berkontribusi</span>
            </div>
        </div>

        <!-- 5. Tabel Rekapitulasi Bulanan (Jika Lebih dari 1 Bulan) -->
        @if (count($rekapBulanan) > 1)
            <div class="section-subhead">
                <i class="fas fa-chart-pie"></i> Rekapitulasi Progres Bulanan
            </div>
            <table class="table-data">
                <thead>
                    <tr>
                        <th style="width: 35px;">No</th>
                        <th>Periode Bulan</th>
                        <th style="width: 100px;">Total Pekerjaan</th>
                        <th style="width: 90px;">Selesai</th>
                        <th style="width: 90px;">Proses</th>
                        <th style="width: 110px;">Durasi Kerja</th>
                        <th style="width: 100px;">Capaian (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rekapBulanan as $idx => $rb)
                        @php
                            $pct = $rb['total'] > 0 ? round(($rb['selesai'] / $rb['total']) * 100, 1) : 0;
                        @endphp
                        <tr>
                            <td class="center">{{ $idx + 1 }}</td>
                            <td><strong>{{ $rb['bulan_nama'] }}</strong></td>
                            <td class="center">{{ $rb['total'] }} Tugas</td>
                            <td class="center" style="color: #047857; font-weight: 700;">{{ $rb['selesai'] }}</td>
                            <td class="center" style="color: #b45309;">{{ $rb['proses'] }}</td>
                            <td class="center font-mono">{{ $rb['durasi_jam'] }} Jam</td>
                            <td class="center">
                                <span class="badge-kbm {{ $pct >= 80 ? 'badge-selesai' : ($pct >= 50 ? 'badge-proses' : 'badge-tertunda') }}">
                                    {{ $pct }}%
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <!-- 6. Buku Jurnal & Rincian Log Aktivitas Harian -->
        <div class="section-subhead">
            <i class="fas fa-book-open"></i> Buku Jurnal &amp; Rincian Log Aktivitas Harian
        </div>
        <table class="table-data">
            <thead>
                <tr>
                    <th class="col-no">No</th>
                    <th class="col-tgl">Tanggal</th>
                    <th class="col-jam">Waktu</th>
                    <th>Uraian Tugas / Pekerjaan / Aktivitas Pelayanan</th>
                    <th class="col-output">Hasil / Output</th>
                    <th class="col-durasi">Durasi</th>
                    <th class="col-status">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($aktivitasList as $index => $item)
                    <tr>
                        <td class="col-no">{{ $index + 1 }}</td>
                        <td class="col-tgl">
                            {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d/m/Y') }}
                        </td>
                        <td class="col-jam">
                            {{ substr($item->jam_mulai, 0, 5) }} - {{ substr($item->jam_selesai, 0, 5) }}
                        </td>
                        <td>
                            <strong>{{ $item->judul_aktivitas }}</strong>
                            @if (!empty($item->deskripsi))
                                <div style="color: #4b5563; font-size: 0.72rem; margin-top: 2px;">
                                    {{ $item->deskripsi }}
                                </div>
                            @endif
                        </td>
                        <td class="col-output">
                            {{ $item->output_hasil ?: 'Terlaksana' }}
                        </td>
                        <td class="col-durasi">
                            {{ $item->durasi_menit ? round($item->durasi_menit / 60, 1) . ' Jam' : '—' }}
                        </td>
                        <td class="col-status">
                            @if ($item->status === 'selesai')
                                <span class="badge-kbm badge-selesai">Selesai</span>
                            @elseif($item->status === 'proses')
                                <span class="badge-kbm badge-proses">Proses</span>
                            @else
                                <span class="badge-kbm badge-tertunda">Tertunda</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="center" style="padding: 24px; color: #6b7280;">
                            <em>Belum ada catatan log aktivitas yang diinputkan pada periode ini.</em>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- 7. Tanda Tangan Tiga Pihak (Identik dengan Jadwal KBM) -->
        <div class="signature-container">
            <div class="sig-box">
                <div class="sig-title">
                    Pegawai yang Bersangkutan,
                </div>
                <div class="sig-name">{{ $profile['nama'] }}</div>
                <div class="sig-nip">NIP: {{ $profile['nip'] ?: ($profile['nuptk'] ?: '—') }}</div>
            </div>

            <div class="sig-box">
                <div class="sig-title">
                    Kepala Tenaga Administrasi (TAS),
                </div>
                <div class="sig-name">{{ $kepalaTas?->nama ?: 'Euis Nur Komariah, S.Pd.' }}</div>
                <div class="sig-nip">NIP: {{ $kepalaTas?->nip ?: ($kepalaTas?->nuptk ?: '—') }}</div>
            </div>

            <div class="sig-box">
                <div class="sig-title">
                    Mengetahui,<br>Kepala Satuan Pendidikan,
                </div>
                <div class="sig-name">{{ $kepsek?->nama ?: ($sekolah->nama_kepsek ?? 'Drs. H. Mulyadi, M.Pd.') }}</div>
                <div class="sig-nip">NIP: {{ $kepsek?->nip ?: ($sekolah->nip_kepsek ?? '—') }}</div>
            </div>
        </div>

        <!-- 8. Footer Verifikasi Dokumen Digital & QR Code -->
        <div class="doc-footer">
            <div class="qr-badge">
                @if (!empty($qrCodeBase64))
                    <img src="data:image/png;base64,{{ $qrCodeBase64 }}" alt="QR Code Verifikasi">
                @else
                    <img src="{{ asset('img/logo-icon.png') }}" alt="QR Code Verifikasi">
                @endif
                <div>
                    <strong>Dokumen Sah Kinerja Resmi Sistem Aplikasi Edukasi (SAE)</strong><br>
                    Dicetak secara otomatis pada {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y H:i:s') }} WIB.
                    Pindai kode QR untuk validasi keabsahan dokumen digital.
                </div>
            </div>
            <div style="text-align: right;">
                Halaman 1 / 1<br>
                Format: Kertas A4 {{ ucfirst($orientasi) }}
            </div>
        </div>
    </div>

</body>

</html>
