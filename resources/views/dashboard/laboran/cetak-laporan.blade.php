<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penggunaan Lokasi Praktik — {{ $range['label'] }}</title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
    @if ($orientasi === 'landscape')
        <style>
            @page {
                size: A4 landscape;
                margin: 8mm 10mm;
            }
        </style>
    @else
        <style>
            @page {
                size: A4 portrait;
                margin: 10mm 12mm;
            }
        </style>
    @endif
</head>

<body>

    {{-- Floating Bar (Standar Baku SAE) --}}
    <div class="no-print-bar">
        <a href="{{ route('dashboard.laboran.laporan.index', request()->except('orientasi')) }}"
            class="btn-action btn-back">
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

        <button onclick="window.SAE_Print ? window.SAE_Print() : window.print()" class="btn-action btn-print">
            <i class="fas fa-print"></i> Cetak Laporan (Print / PDF)
        </button>
    </div>

    {{-- Lembar Halaman Cetak --}}
    <div class="cetak-page">
        <!-- Watermark Logo SAE -->
        <div class="watermark-sae">
            <img src="{{ asset('img/logo-icon.png') }}" alt="Watermark SAE">
        </div>

        <!-- 1. KOP SURAT SEKOLAH RESMI -->
        @include('partials.kop-surat')

        <!-- 2. Header Dokumen -->
        <div class="doc-header">
            @if (empty($sekolahMeta?->kop_url))
                <div class="school-name">{{ $sekolah->nama ?? 'SATUAN PENDIDIKAN' }}</div>
            @endif
            <div class="doc-title-main">LAPORAN PENGGUNAAN LOKASI PRAKTIK</div>
            <div class="doc-subtitle">
                PERIODE: {{ strtoupper($range['label']) }} &bull; FORMAT: A4 {{ strtoupper($orientasi) }}
            </div>
        </div>

        <!-- 3. Info Box Identitas Laporan -->
        <div class="info-box">
            <div>
                <div class="info-item">
                    <span class="info-label">Satuan Pendidikan:</span>
                    <span class="info-val">{{ $sekolah->nama ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">NPSN:</span>
                    <span class="info-val">{{ $sekolah?->npsn ?: ($sekolahMeta?->npsn ?: '—') }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Jenis Lokasi:</span>
                    <span class="info-val">
                        {{ $jenisFilter !== '' ? ($jenisLokasiLabels[$jenisFilter] ?? 'Semua Jenis') : 'Semua Jenis Lokasi' }}
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Petugas Laboran:</span>
                    <span class="info-val">{{ $petugas['nama'] ?? '—' }}</span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Rentang Waktu:</span>
                    <span class="info-val">
                        {{ \Carbon\Carbon::parse($range['start'])->translatedFormat('d M Y') }} s.d.
                        {{ \Carbon\Carbon::parse($range['end'])->translatedFormat('d M Y') }}
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Lokasi Difilter:</span>
                    <span class="info-val">{{ $lokasiFilter !== '' ? $lokasiFilter : 'Semua Lokasi' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Guru Difilter:</span>
                    <span class="info-val">
                        @if ($guruFilter !== '')
                            {{ optional($daftarGuru->firstWhere('ptk_id', $guruFilter))->nama ?? '—' }}
                        @else
                            Semua Guru
                        @endif
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tanggal Cetak:</span>
                    <span class="info-val">{{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i') }} WIB</span>
                </div>
            </div>
        </div>

        <!-- 4. Ringkasan Angka -->
        <div class="stats-box">
            <div class="stat-item">
                <span class="val">{{ number_format($totalSesi, 0, ',', '.') }}</span>
                <span class="lbl">Total Sesi</span>
            </div>
            <div class="stat-item">
                <span class="val" style="color: #047857;">{{ number_format($totalSelesai, 0, ',', '.') }}</span>
                <span class="lbl">Selesai ({{ $persentaseSelesai }}%)</span>
            </div>
            <div class="stat-item">
                <span class="val" style="color: #b45309;">{{ number_format($totalBatal, 0, ',', '.') }}</span>
                <span class="lbl">Dibatalkan</span>
            </div>
            <div class="stat-item">
                <span class="val">{{ $totalJam }} Jam</span>
                <span class="lbl">Akumulasi Durasi</span>
            </div>
            <div class="stat-item">
                <span class="val">{{ $hariAktif }} Hari</span>
                <span class="lbl">Hari Aktif</span>
            </div>
            <div class="stat-item">
                <span class="val">{{ $lokasiUnik }}</span>
                <span class="lbl">Lokasi Terpakai</span>
            </div>
        </div>

        <!-- 5. Rekapitulasi per Jenis Lokasi -->
        @if (count($rekapJenis) > 0)
            <div class="section-subhead">
                <i class="fas fa-chart-pie"></i> A. Rekapitulasi per Jenis Lokasi
            </div>
            <table class="table-data">
                <thead>
                    <tr>
                        <th style="width: 34px;">No</th>
                        <th>Jenis Lokasi Praktik</th>
                        <th style="width: 90px;">Jumlah Lokasi</th>
                        <th style="width: 70px;">Sesi</th>
                        <th style="width: 80px;">Selesai</th>
                        <th style="width: 100px;">Durasi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rekapJenis as $i => $rj)
                        <tr>
                            <td class="center font-mono">{{ $i + 1 }}</td>
                            <td><strong>{{ $rj['label'] }}</strong></td>
                            <td class="center">{{ $rj['lokasi'] }}</td>
                            <td class="center font-bold">{{ $rj['sesi'] }}</td>
                            <td class="center" style="color: #047857; font-weight: 700;">{{ $rj['selesai'] }}</td>
                            <td class="center font-mono">{{ $rj['jam'] }}j {{ $rj['menit'] }}m</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <!-- 6. Rekapitulasi Progres Bulanan -->
        @if (count($rekapBulanan) > 1)
            <div class="section-subhead">
                <i class="fas fa-calendar-days"></i> B. Rekapitulasi Progres Bulanan
            </div>
            <table class="table-data">
                <thead>
                    <tr>
                        <th style="width: 34px;">No</th>
                        <th>Periode Bulan</th>
                        <th style="width: 80px;">Hari Aktif</th>
                        <th style="width: 70px;">Sesi</th>
                        <th style="width: 80px;">Selesai</th>
                        <th style="width: 70px;">Batal</th>
                        <th style="width: 100px;">Durasi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rekapBulanan as $i => $rb)
                        <tr>
                            <td class="center font-mono">{{ $i + 1 }}</td>
                            <td><strong>{{ $rb['label'] }}</strong></td>
                            <td class="center">{{ $rb['hari'] }} hari</td>
                            <td class="center font-bold">{{ $rb['sesi'] }}</td>
                            <td class="center" style="color: #047857; font-weight: 700;">{{ $rb['selesai'] }}</td>
                            <td class="center" style="color: #b91c1c; font-weight: 700;">{{ $rb['batal'] }}</td>
                            <td class="center font-mono">{{ $rb['jam'] }}j {{ $rb['menit'] }}m</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <!-- 7. Rekapitulasi per Lokasi -->
        @if ($rekapLokasi->count() > 0)
            <div class="section-subhead">
                <i class="fas fa-map-location-dot"></i> C. Rekapitulasi per Lokasi Praktik
            </div>
            <table class="table-data">
                <thead>
                    <tr>
                        <th style="width: 34px;">No</th>
                        <th>Nama Lokasi</th>
                        <th style="width: 150px;">Jenis</th>
                        <th style="width: 70px;">Sesi</th>
                        <th style="width: 80px;">Selesai</th>
                        <th style="width: 100px;">Durasi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rekapLokasi as $i => $rl)
                        <tr>
                            <td class="center font-mono">{{ $i + 1 }}</td>
                            <td><strong>{{ $rl->nama }}</strong></td>
                            <td>{{ $rl->label }}</td>
                            <td class="center font-bold">{{ $rl->sesi }}</td>
                            <td class="center" style="color: #047857; font-weight: 700;">{{ $rl->selesai }}</td>
                            <td class="center font-mono">{{ $rl->jam }}j {{ $rl->menit }}m</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <!-- 8. Rekapitulasi per Guru -->
        @if ($rekapGuru->count() > 0)
            <div class="section-subhead">
                <i class="fas fa-user-tie"></i> D. Rekapitulasi per Guru Pengampu
            </div>
            <table class="table-data">
                <thead>
                    <tr>
                        <th style="width: 34px;">No</th>
                        <th>Nama Guru</th>
                        <th style="width: 130px;">NIP</th>
                        <th style="width: 80px;">Jumlah Mapel</th>
                        <th style="width: 70px;">Sesi</th>
                        <th style="width: 80px;">Selesai</th>
                        <th style="width: 100px;">Durasi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rekapGuru as $i => $rg)
                        <tr>
                            <td class="center font-mono">{{ $i + 1 }}</td>
                            <td><strong>{{ $rg->nama }}</strong></td>
                            <td class="font-mono">{{ $rg->nip ?: '—' }}</td>
                            <td class="center">{{ $rg->mapel }}</td>
                            <td class="center font-bold">{{ $rg->sesi }}</td>
                            <td class="center" style="color: #047857; font-weight: 700;">{{ $rg->selesai }}</td>
                            <td class="center font-mono">{{ $rg->jam }}j {{ $rg->menit }}m</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <!-- 9. Rekapitulasi per Mata Pelajaran -->
        @if ($rekapMapel->count() > 0)
            <div class="section-subhead">
                <i class="fas fa-book"></i> E. Rekapitulasi per Mata Pelajaran
            </div>
            <table class="table-data">
                <thead>
                    <tr>
                        <th style="width: 34px;">No</th>
                        <th>Mata Pelajaran</th>
                        <th style="width: 80px;">Jumlah Kelas</th>
                        <th style="width: 70px;">Sesi</th>
                        <th style="width: 80px;">Selesai</th>
                        <th style="width: 100px;">Durasi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rekapMapel as $i => $rm)
                        <tr>
                            <td class="center font-mono">{{ $i + 1 }}</td>
                            <td><strong>{{ $rm->nama }}</strong></td>
                            <td class="center">{{ $rm->kelas }}</td>
                            <td class="center font-bold">{{ $rm->sesi }}</td>
                            <td class="center" style="color: #047857; font-weight: 700;">{{ $rm->selesai }}</td>
                            <td class="center font-mono">{{ $rm->jam }}j {{ $rm->menit }}m</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <!-- 10. Rincian Seluruh Sesi Praktik -->
        <div class="section-subhead">
            <i class="fas fa-list-check"></i> F. Rincian Seluruh Sesi Praktik
        </div>
        <table class="table-data">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th style="width: 68px;">Tanggal</th>
                    <th style="width: 110px;">Lokasi</th>
                    <th style="width: 78px;">Kelas</th>
                    <th>Mata Pelajaran</th>
                    <th>Topik / Materi</th>
                    <th style="width: 105px;">Guru</th>
                    <th style="width: 82px;">Jam</th>
                    <th style="width: 62px;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $idx => $it)
                    <tr>
                        <td class="center font-mono">{{ $idx + 1 }}</td>
                        <td class="center">{{ \Carbon\Carbon::parse($it->tanggal)->format('d/m/Y') }}</td>
                        <td>{{ $it->ruang_lab_nama }}</td>
                        <td class="center">{{ $it->rombel_nama ?: '—' }}</td>
                        <td>{{ $it->mata_pelajaran }}</td>
                        <td>{{ $it->topik_praktik ?: '—' }}</td>
                        <td>{{ $it->guru_nama ?: '—' }}</td>
                        <td class="center font-mono">
                            {{ substr((string) $it->jam_mulai, 0, 5) }}-{{ substr((string) $it->jam_selesai, 0, 5) }}
                        </td>
                        <td class="center">
                            @if ($it->status === 'selesai')
                                <span class="badge-kbm badge-selesai">Selesai</span>
                            @elseif ($it->status === 'berlangsung')
                                <span class="badge-kbm badge-proses">Berlangsung</span>
                            @elseif ($it->status === 'batal')
                                <span class="badge-kbm badge-tertunda">Batal</span>
                            @else
                                <span class="badge-kbm badge-proses">Dijadwalkan</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="center" style="padding: 16px; color: #6b7280;">
                            <em>Tidak ada sesi praktik pada periode ini.</em>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- 11. Tanda Tangan Tiga Pihak -->
        <div class="signature-container">
            <div class="sig-box">
                <div class="sig-title">
                    Petugas Laboran,
                </div>
                <div class="sig-name">{{ $petugas['nama'] ?? '...................................' }}</div>
                <div class="sig-nip">NIP: {{ $petugas['nip'] ?: '—' }}</div>
            </div>

            <div class="sig-box">
                <div class="sig-title">
                    Kepala Laboratorium / Bengkel,
                </div>
                <div class="sig-name">{{ $kepalaLab?->nama ?: '...................................' }}</div>
                <div class="sig-nip">NIP: {{ $kepalaLab?->nip ?: ($kepalaLab?->nuptk ?: '—') }}</div>
            </div>

            <div class="sig-box">
                <div class="sig-title">
                    Mengetahui,<br>Kepala Satuan Pendidikan,
                </div>
                <div class="sig-name">{{ $kepalaSekolah?->nama ?: '...................................' }}</div>
                <div class="sig-nip">NIP: {{ $kepalaSekolah?->nip ?: ($kepalaSekolah?->nuptk ?: '—') }}</div>
            </div>
        </div>

        <!-- 12. Footer Verifikasi Dokumen -->
        <div class="doc-footer">
            <div class="qr-badge">
                <img src="{{ asset('img/logo-icon.png') }}" alt="Logo Verifikasi SAE">
                <div>
                    <strong>Dokumen Laporan Resmi Sistem Aplikasi Edukasi (SAE)</strong><br>
                    Dicetak otomatis pada {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y H:i:s') }} WIB.
                    Periode laporan: {{ $range['label'] }}.
                </div>
            </div>
            <div style="text-align: right;">
                Halaman 1 / 1<br>
                Format: Kertas A4 {{ ucfirst($orientasi) }}
            </div>
        </div>
    </div>

    <script
        src="{{ asset('js/cetak.js') }}?v={{ file_exists(public_path('js/cetak.js')) ? filemtime(public_path('js/cetak.js')) : time() }}">
    </script>
</body>

</html>
