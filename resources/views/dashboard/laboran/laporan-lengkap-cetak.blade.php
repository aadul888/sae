<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penggunaan &amp; Kerusakan Alat — {{ $range['label'] }}</title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
    <link rel="stylesheet"
        href="{{ asset('css/cetak-laboran.css') }}?v={{ file_exists(public_path('css/cetak-laboran.css')) ? filemtime(public_path('css/cetak-laboran.css')) : time() }}">
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

    <div class="no-print-bar">
        <a href="{{ route('dashboard.laboran.laporan-lengkap.index', request()->except('orientasi')) }}"
            class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>

        <div class="doc-toolbar-group">
            <span class="doc-toolbar-label">Laporan:</span>
            <a href="{{ request()->fullUrlWithQuery(['mode' => 'ringkasan']) }}"
                class="btn-orientasi {{ ($mode ?? 'ringkasan') === 'ringkasan' ? 'active' : '' }}"
                title="Versi ringkas: rekap sesi, kerusakan, peminjaman, perawatan">
                <i class="fas fa-list"></i> Ringkasan
            </a>
            <a href="{{ request()->fullUrlWithQuery(['mode' => 'lengkap']) }}"
                class="btn-orientasi {{ ($mode ?? 'ringkasan') === 'lengkap' ? 'active' : '' }}"
                title="Versi lengkap: seluruh rekap + rincian alat rusak">
                <i class="fas fa-table-list"></i> Lengkap
            </a>
            <span class="doc-toolbar-sep"></span>
        </div>

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

    <div class="cetak-page">
        <!-- Watermark -->
        <div class="watermark-sae">
            <img src="{{ asset('img/logo-icon.png') }}" alt="Watermark SAE">
        </div>

        <!-- Kop Surat -->
        @include('partials.kop-surat')

        <!-- Header -->
        <div class="doc-header">
            @if (empty($sekolahMeta?->kop_url))
                <div class="school-name">{{ $sekolah->nama ?? 'SATUAN PENDIDIKAN' }}</div>
            @endif
            <div class="doc-title-main">LAPORAN PENGGUNAAN &amp; KERUSAKAN ALAT PRAKTIK</div>
            <div class="doc-subtitle">
                PERIODE: {{ strtoupper($range['label']) }}
            </div>
        </div>

        <!-- Info Box -->
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
                    <span class="info-label">Tanggal Cetak:</span>
                    <span class="info-val">{{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i') }} WIB</span>
                </div>
            </div>
        </div>

        <!-- Ringkasan -->
        <div class="stats-box">
            <div class="stat-item">
                <span class="val">{{ number_format($totalSesi, 0, ',', '.') }}</span>
                <span class="lbl">Sesi Praktik</span>
            </div>
            <div class="stat-item">
                <span class="val" style="color: #0e7490;">{{ number_format($totalPeminjaman, 0, ',', '.') }}</span>
                <span class="lbl">Peminjaman</span>
            </div>
            <div class="stat-item">
                <span class="val" style="color: #b91c1c;">{{ number_format($totalKerusakan, 0, ',', '.') }}</span>
                <span class="lbl">Kerusakan</span>
            </div>
            <div class="stat-item">
                <span class="val" style="color: #b45309;">{{ number_format($totalPerawatan, 0, ',', '.') }}</span>
                <span class="lbl">Perawatan</span>
            </div>
            <div class="stat-item">
                <span class="val">{{ number_format($totalPemeriksaan, 0, ',', '.') }}</span>
                <span class="lbl">Pemeriksaan</span>
            </div>
            <div class="stat-item">
                <span class="val" style="color: #6d28d9;">{{ number_format($totalPengadaan, 0, ',', '.') }}</span>
                <span class="lbl">Usulan Pengadaan</span>
            </div>
        </div>

        <!-- A. Penggunaan Lokasi Praktik -->
        <div class="section-subhead">
            <i class="fas fa-map-location-dot"></i> A. Penggunaan Lokasi Praktik
        </div>
        <table class="table-data">
            <thead>
                <tr>
                    <th style="width: 34px;">No</th>
                    <th>Lokasi Praktik</th>
                    <th style="width: 70px;">Sesi</th>
                    <th style="width: 80px;">Selesai</th>
                    <th style="width: 100px;">Durasi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rekapLokasi as $i => $rl)
                    <tr>
                        <td class="center font-mono">{{ $i + 1 }}</td>
                        <td><strong>{{ $rl->nama }}</strong></td>
                        <td class="center font-bold">{{ $rl->sesi }}</td>
                        <td class="center" style="color: #047857; font-weight: 700;">{{ $rl->selesai }}</td>
                        <td class="center font-mono">{{ $rl->jam }}j {{ $rl->menit }}m</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="center" style="padding: 12px; color: #6b7280;">
                            <em>Tidak ada sesi praktik pada periode ini.</em>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- B. Rekapitulasi Kerusakan -->
        <div class="section-subhead">
            <i class="fas fa-triangle-exclamation"></i> B. Rekapitulasi Kerusakan Alat
        </div>
        <table class="table-data">
            <thead>
                <tr>
                    <th style="width: 34px;">No</th>
                    <th>Tingkat Kerusakan</th>
                    <th style="width: 90px;">Jumlah Kasus</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rekapTingkat as $i => $rt)
                    <tr>
                        <td class="center font-mono">{{ $i + 1 }}</td>
                        <td><strong>{{ $rt->label }}</strong></td>
                        <td class="center font-bold">{{ $rt->total }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="center" style="padding: 12px; color: #6b7280;">
                            <em>Tidak ada kerusakan pada periode ini.</em>
                        </td>
                    </tr>
                @endforelse
                @if ($totalEstimasi > 0)
                    <tr>
                        <td colspan="2" style="text-align: right; font-weight: 700;">Total Estimasi Biaya Kerusakan</td>
                        <td class="center" style="font-weight: 800; color: #b91c1c;">
                            Rp {{ number_format($totalEstimasi, 0, ',', '.') }}
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>

        <!-- C. Alat Paling Sering Rusak -->
        @if (($mode ?? 'ringkasan') === 'lengkap' && $rekapAlatRusak->count() > 0)
            <div class="section-subhead">
                <i class="fas fa-tools"></i> C. Alat Paling Sering Mengalami Kerusakan
            </div>
            <table class="table-data">
                <thead>
                    <tr>
                        <th style="width: 34px;">No</th>
                        <th>Nama Alat</th>
                        <th style="width: 80px;">Kejadian</th>
                        <th style="width: 80px;">Jumlah Unit</th>
                        <th style="width: 110px;">Estimasi Biaya</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rekapAlatRusak as $i => $ar)
                        <tr>
                            <td class="center font-mono">{{ $i + 1 }}</td>
                            <td><strong>{{ $ar->nama }}</strong></td>
                            <td class="center font-bold">{{ $ar->kejadian }}</td>
                            <td class="center">{{ (float) $ar->jumlah }}</td>
                            <td class="center font-mono">Rp {{ number_format((float) $ar->biaya, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <!-- D. Rekapitulasi Peminjaman -->
        <div class="section-subhead">
            <i class="fas fa-hand-holding"></i> D. Rekapitulasi Peminjaman Alat
        </div>
        <table class="table-data">
            <thead>
                <tr>
                    <th style="width: 34px;">No</th>
                    <th>Kategori Peminjam</th>
                    <th style="width: 100px;">Jumlah Peminjaman</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rekapPeminjam as $i => $rp)
                    <tr>
                        <td class="center font-mono">{{ $i + 1 }}</td>
                        <td><strong>{{ $rp->label }}</strong></td>
                        <td class="center font-bold">{{ $rp->total }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="center" style="padding: 12px; color: #6b7280;">
                            <em>Tidak ada peminjaman pada periode ini.</em>
                        </td>
                    </tr>
                @endforelse
                <tr>
                    <td colspan="2" style="text-align: right; font-weight: 700;">Status Sirkulasi</td>
                    <td class="center" style="font-size: var(--text-xs);">
                        Dipinjam: <strong>{{ $totalDipinjam }}</strong> &bull;
                        Kembali: <strong style="color: #047857;">{{ $totalDikembalikan }}</strong> &bull;
                        Terlambat: <strong style="color: #b91c1c;">{{ $totalTerlambat }}</strong>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- E. Rekapitulasi Perawatan -->
        @if ($rekapJenisRawat->count() > 0)
            <div class="section-subhead">
                <i class="fas fa-screwdriver-wrench"></i> E. Rekapitulasi Perawatan Alat
            </div>
            <table class="table-data">
                <thead>
                    <tr>
                        <th style="width: 34px;">No</th>
                        <th>Jenis Perawatan</th>
                        <th style="width: 80px;">Total</th>
                        <th style="width: 90px;">Selesai</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rekapJenisRawat as $i => $jr)
                        <tr>
                            <td class="center font-mono">{{ $i + 1 }}</td>
                            <td><strong>{{ $jr->label }}</strong></td>
                            <td class="center font-bold">{{ $jr->total }}</td>
                            <td class="center" style="color: #047857; font-weight: 700;">{{ $jr->selesai }}</td>
                        </tr>
                    @endforeach
                    @if ($totalBiayaRawat > 0)
                        <tr>
                            <td colspan="3" style="text-align: right; font-weight: 700;">Total Biaya Perawatan</td>
                            <td class="center" style="font-weight: 800; color: #b45309;">
                                Rp {{ number_format($totalBiayaRawat, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        @endif

        <!-- F. Hasil Pemeriksaan Ceklist -->
        <div class="section-subhead">
            <i class="fas fa-list-check"></i> F. Hasil Pemeriksaan Ceklist Alat &amp; Bahan
        </div>
        <table class="table-data">
            <tbody>
                <tr>
                    <td style="width: 45%; font-weight: 700;">Jumlah Pemeriksaan</td>
                    <td class="center font-bold">{{ $totalPemeriksaan }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700;">Total Item Diperiksa</td>
                    <td class="center font-bold">{{ $totalItemDiperiksa }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700;">Item Tidak Sesuai Standar</td>
                    <td class="center font-bold" style="color: #b45309;">{{ $totalItemTidakSesuai }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700;">Item Tidak Ditemukan</td>
                    <td class="center font-bold" style="color: #b91c1c;">{{ $totalItemTidakAda }}</td>
                </tr>
            </tbody>
        </table>

        <!-- G. Kebutuhan & Pengadaan -->
        <div class="section-subhead">
            <i class="fas fa-file-invoice-dollar"></i> G. Kebutuhan &amp; Usulan Pengadaan
        </div>
        <table class="table-data">
            <tbody>
                <tr>
                    <td style="width: 45%; font-weight: 700;">Kebutuhan Alat/Bahan Tercatat</td>
                    <td class="center font-bold">{{ $totalKebutuhan }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700;">Kebutuhan Prioritas Kritis</td>
                    <td class="center font-bold" style="color: #b91c1c;">{{ $totalKebutuhanKritis }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700;">Jumlah Usulan Pengadaan</td>
                    <td class="center font-bold">{{ $totalPengadaan }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700;">Usulan Disetujui / Direalisasi</td>
                    <td class="center font-bold" style="color: #047857;">{{ $totalPengadaanDisetujui }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700;">Total Nilai Usulan Pengadaan</td>
                    <td class="center" style="font-weight: 800; color: #6d28d9;">
                        Rp {{ number_format($totalNilaiPengadaan, 0, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- H. Kondisi Inventaris -->
        @if ($rekapKondisi->count() > 0)
            <div class="section-subhead">
                <i class="fas fa-vials"></i> H. Kondisi Inventaris Alat &amp; Bahan
            </div>
            <table class="table-data">
                <thead>
                    <tr>
                        <th style="width: 34px;">No</th>
                        <th>Kondisi</th>
                        <th style="width: 90px;">Jumlah Item</th>
                        <th style="width: 100px;">Total Stok</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rekapKondisi as $i => $rk)
                        <tr>
                            <td class="center font-mono">{{ $i + 1 }}</td>
                            <td><strong>{{ $rk->label }}</strong></td>
                            <td class="center font-bold">{{ $rk->total }}</td>
                            <td class="center font-mono">{{ (float) $rk->stok }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if (($mode ?? 'ringkasan') === 'ringkasan')
            <div class="doc-trace">
                <i class="fas fa-circle-info"></i>
                Mode <strong>Ringkasan</strong> memuat rekap agregat (sesi praktik, kerusakan,
                peminjaman, perawatan, pemeriksaan, kebutuhan &amp; pengadaan).
                Pilih <strong>Lengkap</strong> pada bar alat di atas untuk menampilkan
                rincian alat yang paling sering mengalami kerusakan.
            </div>
        @endif

        <!-- Tanda Tangan -->
        @include('dashboard.laboran.partials.ttd-cetak')

        <!-- Footer -->
        <div class="doc-footer">
            <div class="qr-badge">
                <img src="{{ asset('img/logo-icon.png') }}" alt="Logo Verifikasi SAE">
                <div>
                    <strong>Dokumen Laporan Resmi Sistem Aplikasi Edukasi (SAE)</strong><br>
                    Dicetak otomatis pada {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y H:i:s') }} WIB.
                    @if (!empty($logCetak?->nomor_cetak))
                        <br>Nomor cetak: <strong>{{ $logCetak->nomor_cetak }}</strong>
                    @endif
                </div>
            </div>
            <div style="text-align: right;">
                Halaman 1 / 1
            </div>
        </div>
    </div>

    <script
        src="{{ asset('js/cetak.js') }}?v={{ file_exists(public_path('js/cetak.js')) ? filemtime(public_path('js/cetak.js')) : time() }}">
    </script>
</body>

</html>
