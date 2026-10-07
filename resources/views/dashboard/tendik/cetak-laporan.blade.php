<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kinerja - {{ $profile['nama'] }} - {{ $range['label'] }}</title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
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

        <!-- 4b. Diagram Visual (Print-Safe SVG Bar Chart) -->
        @php
            $total = $totalAktivitas ?: 1;
            $pctSelesai = round($totalSelesai / $total * 100);
            $pctProses = round($totalProses / $total * 100);
            $pctTertunda = round($totalTertunda / $total * 100);

            $distribusiBidang = $aktivitasList->groupBy('bidang')->map->count()->sortDesc()->take(6);
            $maxBidang = $distribusiBidang->max() ?: 1;
        @endphp
        <div style="display: grid; grid-template-columns: 1fr 1.6fr; gap: 16px; margin-bottom: 16px; position: relative; z-index: 1;">
            <!-- Diagram Proporsi Status (Horizontal Stacked Bar) -->
            <div style="border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px 12px;">
                <div style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: #1e3a8a; margin-bottom: 8px;">
                    Proporsi Penyelesaian Tugas
                </div>

                <!-- Stacked bar SVG -->
                <div style="background: #f3f4f6; border-radius: 4px; height: 16px; overflow: hidden; display: flex; margin-bottom: 6px;">
                    @if ($totalSelesai > 0)
                        <div style="width: {{ $pctSelesai }}%; background: #047857; height: 100%;"></div>
                    @endif
                    @if ($totalProses > 0)
                        <div style="width: {{ $pctProses }}%; background: #b45309; height: 100%;"></div>
                    @endif
                    @if ($totalTertunda > 0)
                        <div style="width: {{ $pctTertunda }}%; background: #b91c1c; height: 100%;"></div>
                    @endif
                </div>

                <div style="display: flex; gap: 10px; font-size: 0.7rem; flex-wrap: wrap;">
                    <span style="display: inline-flex; align-items: center; gap: 3px;">
                        <span style="width: 8px; height: 8px; background: #047857; border-radius: 50%;"></span>
                        Selesai: {{ $totalSelesai }} ({{ $pctSelesai }}%)
                    </span>
                    <span style="display: inline-flex; align-items: center; gap: 3px;">
                        <span style="width: 8px; height: 8px; background: #b45309; border-radius: 50%;"></span>
                        Proses: {{ $totalProses }} ({{ $pctProses }}%)
                    </span>
                    <span style="display: inline-flex; align-items: center; gap: 3px;">
                        <span style="width: 8px; height: 8px; background: #b91c1c; border-radius: 50%;"></span>
                        Tertunda: {{ $totalTertunda }} ({{ $pctTertunda }}%)
                    </span>
                </div>
            </div>

            <!-- Distribusi Aktivitas per Bidang (Horizontal Bar) -->
            <div style="border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px 12px;">
                <div style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: #1e3a8a; margin-bottom: 8px;">
                    Distribusi Aktivitas per Bidang Kerja
                </div>
                @php
                    $bidangLabelsMap = \App\Models\TendikAktivitas::BIDANG_LABELS;
                @endphp
                @forelse ($distribusiBidang as $bKey => $bCount)
                    @php $barW = round($bCount / $maxBidang * 100); @endphp
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px; font-size: 0.7rem;">
                        <span style="width: 90px; flex-shrink: 0; color: #4b5563; font-weight: 600; text-align: right; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $bidangLabelsMap[$bKey] ?? $bKey }}">
                            {{ \Illuminate\Support\Str::limit($bidangLabelsMap[$bKey] ?? $bKey, 15) }}
                        </span>
                        <div style="flex: 1; background: #f3f4f6; border-radius: 3px; height: 10px; overflow: hidden;">
                            <div style="width: {{ $barW }}%; height: 100%; background: #1e3a8a; border-radius: 3px;"></div>
                        </div>
                        <span style="width: 24px; text-align: right; color: #111827; font-weight: 700;">{{ $bCount }}</span>
                    </div>
                @empty
                    <p style="font-size: 0.72rem; color: #9ca3af; margin: 0;">Tidak ada data distribusi bidang.</p>
                @endforelse
            </div>
        </div>

        <!-- 4c. Matriks Sasaran & Indikator Kinerja -->
        <div style="margin-top: 14px; margin-bottom: 16px; position: relative; z-index: 1;">
            <div style="text-align: center; font-size: 0.82rem; font-weight: 800; letter-spacing: 0.5px; margin-bottom: 8px; text-transform: uppercase; color: #111827;">
                MATRIKS SASARAN &amp; INDIKATOR CAPAIAN KINERJA PEGAWAI
            </div>
            <table class="table-data" style="margin-bottom: 14px;">
                <thead>
                    <tr>
                        <th style="width: 38px; text-align: center;">N0.</th>
                        <th style="width: 28%;">Sasaran</th>
                        <th>Indikator Kinerja</th>
                        <th style="width: 15%; text-align: center;">Target</th>
                        <th style="width: 16%; text-align: center;">Realisasi Capaian</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($indikatorKinerjaList ?? [] as $iIdx => $ind)
                        <tr>
                            <td class="center font-mono">{{ $iIdx + 1 }}</td>
                            <td><strong>{{ $ind->sasaran }}</strong></td>
                            <td>{{ $ind->indikator_kinerja }}</td>
                            <td class="center"><strong>{{ $ind->target_label }}</strong></td>
                            <td class="center">
                                <span class="badge-kbm {{ $ind->capaian_persen >= 100 ? 'badge-selesai' : 'badge-proses' }}">
                                    {{ $ind->realisasi_label }} ({{ $ind->capaian_persen }}%)
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="center" style="padding: 12px; color: #6b7280;">
                                <em>Tidak ada data indikator kinerja terdaftar untuk bidang ini.</em>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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
