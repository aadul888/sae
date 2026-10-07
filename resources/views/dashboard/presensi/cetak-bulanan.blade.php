<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Presensi Bulanan — {{ $namaRombel }} ({{ $bulanLabel }})</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
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
