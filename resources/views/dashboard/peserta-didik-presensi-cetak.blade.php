<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Presensi Peserta Didik — {{ $siswa->nama }} ({{ $range['label'] }})</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
</head>
<body data-auto-print>

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
                    <th>Hari Efektif</th>
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
                    <td><strong>{{ $stats['hari_efektif_berjalan'] ?? $stats['total'] }}</strong> hari</td>
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

    <script src="{{ asset('js/cetak.js') }}?v={{ file_exists(public_path('js/cetak.js')) ? filemtime(public_path('js/cetak.js')) : time() }}"></script>
</body>
</html>
