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

    {{-- Floating Bar --}}
    <div class="no-print-bar">
        <a href="{{ route('dashboard.laboran.kerusakan.index') }}" class="btn-action btn-back">
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
            <i class="fas fa-print"></i> Cetak / Simpan PDF
        </button>
    </div>

    <div class="cetak-page">
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
            telah terjadi kerusakan alat praktik dengan rincian sebagai berikut:
        </div>

        <!-- Info Box Rincian -->
        <div class="info-box">
            <div>
                <div class="info-item">
                    <span class="info-label">Nama Alat:</span>
                    <span class="info-val">{{ $ba->nama_alat }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Kode Alat:</span>
                    <span class="info-val">{{ $ba->kode_alat ?: '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Jumlah Rusak:</span>
                    <span class="info-val">{{ (float) $ba->jumlah_rusak }} {{ $ba->satuan }}</span>
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
        <div class="signature-container">
            <div class="sig-box">
                <div class="sig-title">
                    Yang Melaporkan,<br>Petugas Laboran
                </div>
                <div class="sig-name">{{ $ba->pelapor_nama ?: '...................................' }}</div>
                <div class="sig-nip">NIP: —</div>
            </div>

            <div class="sig-box">
                <div class="sig-title">
                    Mengetahui,<br>Kepala Laboratorium / Bengkel
                </div>
                <div class="sig-name">{{ $kepalaLab?->nama ?: '...................................' }}</div>
                <div class="sig-nip">NIP: {{ $kepalaLab?->nip ?: ($kepalaLab?->nuptk ?: '—') }}</div>
            </div>

            <div class="sig-box">
                <div class="sig-title">
                    Menyetujui,<br>Kepala Satuan Pendidikan
                </div>
                <div class="sig-name">{{ $kepalaSekolah?->nama ?: '...................................' }}</div>
                <div class="sig-nip">NIP: {{ $kepalaSekolah?->nip ?: ($kepalaSekolah?->nuptk ?: '—') }}</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="doc-footer">
            <div class="qr-badge">
                <img src="{{ asset('img/logo-icon.png') }}" alt="Logo Verifikasi SAE">
                <div>
                    <strong>Dokumen Berita Acara Resmi Sistem Aplikasi Edukasi (SAE)</strong><br>
                    Dicetak otomatis pada {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y H:i:s') }} WIB.
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
