<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Presensi Tendik — {{ $pegawai['nama'] }} ({{ $range['label'] }})</title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
</head>

<body>

    {{-- Floating Bar (Identik dengan Jadwal KBM) --}}
    <div class="no-print-bar">
        <a href="{{ route('dashboard.tendik.presensi.index', request()->except('orientasi')) }}" class="btn-action btn-back">
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
            <i class="fas fa-print"></i> Cetak Presensi (Print / PDF)
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
            <div class="doc-title-main">LAPORAN REKAPITULASI KEHADIRAN TENAGA KEPENDIDIKAN</div>
            <div class="doc-subtitle">PERIODE: {{ strtoupper($range['label']) }} &bull; NOMOR: {{ $docId }} &bull; FORMAT: A4 {{ strtoupper($orientasi) }}</div>
        </div>

        <!-- 3. Info Box Identitas Pegawai (Identik dengan Jadwal KBM) -->
        <div class="info-box">
            <div>
                <div class="info-item">
                    <span class="info-label">Nama Pegawai:</span>
                    <span class="info-val">{{ $pegawai['nama'] }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">NIP / NUPTK:</span>
                    <span class="info-val">{{ $pegawai['nip'] ?: ($pegawai['nuptk'] ?: '—') }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Jabatan / Bagian:</span>
                    <span class="info-val">{{ $pegawai['jabatan'] }}</span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Status Kepegawaian:</span>
                    <span class="info-val">{{ $pegawai['status_kepegawaian'] ?? 'Pegawai Tetap' }}</span>
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

        <!-- 4. Ringkasan Presensi (Stats Box) -->
        <div class="stats-box">
            <div class="stat-item">
                <span class="val">{{ $stats['hari_efektif'] }} Hari</span>
                <span class="lbl">Hari Efektif</span>
            </div>
            <div class="stat-item">
                <span class="val" style="color: #047857;">{{ $stats['hadir'] }} Hari</span>
                <span class="lbl">Total Hadir</span>
            </div>
            <div class="stat-item">
                <span class="val" style="color: #b45309;">{{ $stats['terlambat'] + $stats['izin'] + $stats['sakit'] }} Hari</span>
                <span class="lbl">Izin / Terlambat</span>
            </div>
            <div class="stat-item">
                <span class="val" style="color: #b91c1c;">{{ $stats['alpha'] }} Hari</span>
                <span class="lbl">Alpha</span>
            </div>
            <div class="stat-item">
                <span class="val" style="color: #1e3a8a;">{{ $stats['persen'] }}%</span>
                <span class="lbl">Tingkat Kedisiplinan</span>
            </div>
        </div>

        <!-- 5. Tabel Rekapitulasi Presensi -->
        <div class="section-subhead">
            <i class="fas fa-list-check"></i> Rincian Indikator Evaluasi Presensi &amp; Pelayanan
        </div>
        <table class="table-data">
            <thead>
                <tr>
                    <th style="width: 35px;">No</th>
                    <th>Komponen Evaluasi Presensi Pegawai</th>
                    <th style="width: 140px;">Akumulasi Hari</th>
                    <th style="width: 130px;">Persentase (%)</th>
                    <th>Keterangan Standar Kedisiplinan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="center">1</td>
                    <td><strong>Kehadiran Tepat Waktu (Hadir Fisik / Pelayanan)</strong></td>
                    <td class="center"><strong>{{ $stats['hadir'] }} Hari</strong></td>
                    <td class="center" style="color: #047857; font-weight: 700;">{{ $stats['persen'] }}%</td>
                    <td>Memenuhi standar jam operasional pelayanan administrasi satuan pendidikan</td>
                </tr>
                <tr>
                    <td class="center">2</td>
                    <td>Terlambat Masuk Jam Operasional (T)</td>
                    <td class="center">{{ $stats['terlambat'] }} Hari</td>
                    <td class="center">0.0%</td>
                    <td>Toleransi kedatangan jam kerja sesuai tata tertib kepegawaian sekolah</td>
                </tr>
                <tr>
                    <td class="center">3</td>
                    <td>Izin Tugas Kedinasan / Sakit / Cuti (I/S)</td>
                    <td class="center">{{ $stats['izin'] + $stats['sakit'] }} Hari</td>
                    <td class="center">0.0%</td>
                    <td>Disertai bukti surat permohonan dispensasi / surat keterangan resmi dokter</td>
                </tr>
                <tr>
                    <td class="center">4</td>
                    <td>Tanpa Keterangan / Alpha (A)</td>
                    <td class="center">{{ $stats['alpha'] }} Hari</td>
                    <td class="center">0.0%</td>
                    <td>Nihil pelanggaran disiplin kehadiran operasional kerja</td>
                </tr>
            </tbody>
        </table>

        <!-- 6. Tanda Tangan Tiga Pihak (Identik dengan Jadwal KBM) -->
        <div class="signature-container">
            <div class="sig-box">
                <div class="sig-title">
                    Pegawai yang Bersangkutan,
                </div>
                <div class="sig-name">{{ $pegawai['nama'] }}</div>
                <div class="sig-nip">NIP: {{ $pegawai['nip'] ?: ($pegawai['nuptk'] ?: '—') }}</div>
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
                <div class="sig-name">{{ $kepalaSekolah?->nama ?: ($sekolah->nama_kepsek ?? 'Drs. H. Mulyadi, M.Pd.') }}</div>
                <div class="sig-nip">NIP: {{ $kepalaSekolah?->nip ?: ($sekolah->nip_kepsek ?? '—') }}</div>
            </div>
        </div>

        <!-- 7. Footer Verifikasi Dokumen Digital & QR Code -->
        <div class="doc-footer">
            <div class="qr-badge">
                <img src="{{ $qrUri }}" alt="QR Code Verifikasi">
                <div>
                    <strong>Dokumen Sah Kehadiran Resmi Sistem Aplikasi Edukasi (SAE)</strong><br>
                    Dicetak secara otomatis pada {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y H:i:s') }} WIB.
                    Pindai kode QR untuk validasi keaslian dokumen digital.
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
