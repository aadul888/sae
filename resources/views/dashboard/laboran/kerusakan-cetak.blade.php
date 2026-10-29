<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara Kerusakan Alat — {{ $ba->nomor_ba }}</title>
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
                margin: 12mm 15mm;
            }
        </style>
    @endif
</head>

<body>
    @php
        $alat = $alat ?? collect();
        $totalAlat = $alat->count();
    @endphp

    {{-- Floating Bar --}}
    <div class="no-print-bar">
        <a href="{{ route('dashboard.laboran.kerusakan.index') }}" class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>

        @include('dashboard.laboran.partials.pilih-orientasi')

        @include('dashboard.laboran.partials.pilih-kaprog')

        <button onclick="window.SAE_Print ? window.SAE_Print() : window.print()" class="btn-action btn-print">
            <i class="fas fa-print"></i> Cetak / Simpan PDF
        </button>
    </div>

    {{-- =====================================================================
         HALAMAN 1 — BERITA ACARA
         ===================================================================== --}}
    <div class="cetak-page page-break">
        <!-- Watermark -->
        <div class="watermark-sae">
            <img src="{{ asset('img/logo-icon.png') }}" alt="Watermark SAE">
        </div>

        <!-- Kop Surat -->
        @include('partials.kop-surat')

        <!-- Header Dokumen -->
        <div class="doc-header">
            @if (empty($sekolahMeta?->kop_url))
                <div class="school-name">{{ $sekolah->nama ?? 'SATUAN PENDIDIKAN' }}</div>
            @endif
            <div class="doc-title-main">BERITA ACARA KERUSAKAN ALAT</div>
            <div class="doc-subtitle">Nomor: {{ $ba->nomor_ba }}</div>
        </div>

        <!-- Pembuka -->
        <div style="font-size: var(--text-sm); line-height: 1.7; color: #1f2937; margin-bottom: 16px; position: relative; z-index: 1;">
            Pada hari ini, <strong>{{ \Carbon\Carbon::parse($ba->tanggal_ba ?: $ba->tanggal_kejadian)->translatedFormat('l') }}</strong>,
            tanggal <strong>{{ \Carbon\Carbon::parse($ba->tanggal_ba ?: $ba->tanggal_kejadian)->translatedFormat('d F Y') }}</strong>,
            bertempat di <strong>{{ $ba->ruang_lab_nama ?: ($sekolah->nama ?? 'lingkungan sekolah') }}</strong>,
            telah terjadi kerusakan alat praktik sebanyak
            <strong>{{ $totalAlat }} jenis alat</strong> sebagaimana rincian pada
            <strong>Lampiran</strong> (halaman 2) yang merupakan bagian tidak terpisahkan dari berita acara ini.
        </div>

        <!-- Info Box Rincian -->
        <div class="info-box">
            <div>
                <div class="info-item">
                    <span class="info-label">Jumlah Alat Dilaporkan:</span>
                    <span class="info-val">{{ $totalAlat }} jenis</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Total Kerusakan:</span>
                    <span class="info-val">{{ $alat->sum(fn ($a) => (float) $a->jumlah_rusak) }} unit</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Lokasi:</span>
                    <span class="info-val">{{ $ba->ruang_lab_nama ?: '—' }}</span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Tanggal Kejadian:</span>
                    <span class="info-val">{{ \Carbon\Carbon::parse($ba->tanggal_kejadian)->translatedFormat('d F Y') }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tingkat Kerusakan:</span>
                    <span class="info-val"><strong>{{ $tingkatList[$ba->tingkat_kerusakan] ?? $ba->tingkat_kerusakan }}</strong></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Pelaku / PJ:</span>
                    <span class="info-val">
                        {{ $ba->pelaku_nama ?: '—' }}
                        <span style="color: #6b7280; font-weight: 600;">({{ $pelakuList[$ba->pelaku_tipe] ?? $ba->pelaku_tipe }})</span>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Jurusan:</span>
                    <span class="info-val">{{ $jurusan->nama_jurusan ?? '—' }}</span>
                </div>
            </div>
        </div>

        <!-- Kronologi -->
        @if ($ba->kronologi)
            <div class="section-subhead"><i class="fas fa-list-ol"></i> A. Kronologi Kejadian</div>
            <div style="font-size: var(--text-sm); line-height: 1.7; color: #1f2937; margin-bottom: 14px; padding: 10px 14px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: var(--radius-sm); position: relative; z-index: 1;">
                {{ $ba->kronologi }}
            </div>
        @endif

        <!-- Penyebab -->
        @if ($ba->penyebab)
            <div class="section-subhead"><i class="fas fa-magnifying-glass"></i> B. Penyebab Kerusakan</div>
            <div style="font-size: var(--text-sm); line-height: 1.7; color: #1f2937; margin-bottom: 14px; padding: 10px 14px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: var(--radius-sm); position: relative; z-index: 1;">
                {{ $ba->penyebab }}
            </div>
        @endif

        <!-- Tindakan Lanjut & Biaya -->
        <div class="section-subhead"><i class="fas fa-screwdriver-wrench"></i> C. Tindakan Lanjut</div>
        <table class="table-data">
            <tbody>
                <tr>
                    <td style="width: 30%; font-weight: 700;">Tindakan Lanjut</td>
                    <td>{{ $ba->tindakan_lanjut ?: 'Belum ditentukan' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700;">Estimasi Biaya Perbaikan</td>
                    <td>
                        @if ($ba->estimasi_biaya)
                            <strong>Rp {{ number_format((float) $ba->estimasi_biaya, 0, ',', '.') }}</strong>
                        @else
                            —
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: 700;">Perlu Usulan Penggantian</td>
                    <td>{{ $ba->perlu_usulan_penggantian ? 'Ya' : 'Tidak' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700;">Status Berita Acara</td>
                    <td>{{ $statusList[$ba->status] ?? $ba->status }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Penutup -->
        <div style="font-size: var(--text-sm); line-height: 1.7; color: #1f2937; margin: 16px 0; position: relative; z-index: 1;">
            Demikian Berita Acara Kerusakan Alat ini dibuat dengan sebenarnya dan penuh tanggung jawab,
            untuk dipergunakan sebagaimana mestinya.
        </div>

        <!-- Saksi -->
        @if ($ba->saksi_1_nama || $ba->saksi_2_nama)
            <div class="section-subhead"><i class="fas fa-users"></i> D. Saksi</div>
            <table class="table-data" style="margin-bottom: 18px;">
                <thead>
                    <tr>
                        <th style="width: 40px;">No</th>
                        <th>Nama Saksi</th>
                        <th style="width: 140px;">Tanda Tangan</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($ba->saksi_1_nama)
                        <tr>
                            <td class="center">1</td>
                            <td>{{ $ba->saksi_1_nama }}</td>
                            <td style="height: 40px;"></td>
                        </tr>
                    @endif
                    @if ($ba->saksi_2_nama)
                        <tr>
                            <td class="center">2</td>
                            <td>{{ $ba->saksi_2_nama }}</td>
                            <td style="height: 40px;"></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        @endif

        <!-- Tanda Tangan -->
        @php
            $sigTitles = [
                'Yang Melaporkan,<br>Petugas Laboran,',
                'Mengetahui,<br>Kepala Program Keahlian,',
                'Menyetujui,<br>Kepala Satuan Pendidikan,',
            ];
            // Pelapor pada berita acara adalah pihak yang menandatangani kolom pertama.
            if (!empty($ba->pelapor_nama)) {
                $petugas = array_merge($petugas ?? [], ['nama' => $ba->pelapor_nama]);
            }
        @endphp
        @include('dashboard.laboran.partials.ttd-cetak')

        <!-- Footer -->
        <div class="doc-footer">
            <div class="qr-badge">
                <img src="{{ asset('img/logo-icon.png') }}" alt="Logo Verifikasi SAE">
                <div>
                    <strong>Dokumen Berita Acara Resmi Sistem Aplikasi Edukasi (SAE)</strong><br>
                    Dicetak otomatis pada {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y H:i:s') }} WIB.
                    @if (!empty($logCetak?->nomor_cetak))
                        <br>Nomor cetak: <strong>{{ $logCetak->nomor_cetak }}</strong>
                    @endif
                </div>
            </div>
            <div style="text-align: right;">
                Halaman 1 / 2
            </div>
        </div>
    </div>

    {{-- =====================================================================
         HALAMAN 2 — LAMPIRAN DAFTAR ALAT
         ===================================================================== --}}
    <div class="cetak-page">
        <div class="watermark-sae">
            <img src="{{ asset('img/logo-icon.png') }}" alt="Watermark SAE">
        </div>

        @include('partials.kop-surat')

        <div class="doc-header">
            @if (empty($sekolahMeta?->kop_url))
                <div class="school-name">{{ $sekolah->nama ?? 'SATUAN PENDIDIKAN' }}</div>
            @endif
            <div class="doc-title-main">LAMPIRAN BERITA ACARA KERUSAKAN ALAT</div>
            <div class="doc-subtitle">Nomor: {{ $ba->nomor_ba }}</div>
        </div>

        <div style="font-size: var(--text-sm); line-height: 1.6; color: #1f2937; margin-bottom: 12px; position: relative; z-index: 1;">
            Daftar alat yang dilaporkan rusak pada Berita Acara Kerusakan Alat
            tanggal <strong>{{ \Carbon\Carbon::parse($ba->tanggal_ba ?: $ba->tanggal_kejadian)->translatedFormat('d F Y') }}</strong>:
        </div>

        <table class="table-data">
            <thead>
                <tr>
                    <th style="width: 34px;">No</th>
                    <th>Nama Alat</th>
                    <th style="width: 90px;">Kode</th>
                    <th style="width: 70px;">Jumlah</th>
                    <th style="width: 70px;">Satuan</th>
                    <th style="width: 110px;">Tingkat</th>
                    <th style="width: 120px;">Lokasi</th>
                    <th style="width: 130px;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($alat as $idx => $a)
                    <tr>
                        <td class="center">{{ $idx + 1 }}</td>
                        <td>{{ $a->nama_alat }}</td>
                        <td class="center">{{ $a->kode_alat ?: '—' }}</td>
                        <td class="center">{{ (float) $a->jumlah_rusak }}</td>
                        <td class="center">{{ $a->satuan }}</td>
                        <td class="center">{{ $tingkatList[$a->tingkat_kerusakan ?? ''] ?? ($a->tingkat_kerusakan ?: '—') }}</td>
                        <td>{{ $a->ruang_lab_nama ?: '—' }}</td>
                        <td>{{ $a->keterangan ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="center">Tidak ada rincian alat.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right; font-weight: 700;">Jumlah</td>
                    <td class="center" style="font-weight: 700;">{{ $alat->sum(fn ($a) => (float) $a->jumlah_rusak) }}</td>
                    <td colspan="4"></td>
                </tr>
            </tfoot>
        </table>

        <div style="font-size: var(--text-xs); color: #6b7280; margin-bottom: 18px; position: relative; z-index: 1;">
            Lampiran ini merupakan bagian tidak terpisahkan dari Berita Acara Kerusakan Alat Nomor
            {{ $ba->nomor_ba }} dan hanya sah apabila disertakan bersama dokumen tersebut.
        </div>

        @include('dashboard.laboran.partials.ttd-cetak')

        <div class="doc-footer">
            <div class="qr-badge">
                <img src="{{ asset('img/logo-icon.png') }}" alt="Logo Verifikasi SAE">
                <div>
                    <strong>Lampiran Dokumen Berita Acara Sistem Aplikasi Edukasi (SAE)</strong><br>
                    Dicetak otomatis pada {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y H:i:s') }} WIB.
                </div>
            </div>
            <div style="text-align: right;">
                Halaman 2 / 2
            </div>
        </div>
    </div>

    <script
        src="{{ asset('js/cetak.js') }}?v={{ file_exists(public_path('js/cetak.js')) ? filemtime(public_path('js/cetak.js')) : time() }}">
    </script>
</body>

</html>
