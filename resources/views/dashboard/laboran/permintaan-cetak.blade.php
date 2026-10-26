<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bon Permintaan Alat &amp; Bahan — {{ $permintaan->nomor_permintaan }}</title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
    @if ($orientasi === 'landscape')
        <style>
            @page { size: A4 landscape; margin: 8mm 10mm; }
        </style>
    @else
        <style>
            @page { size: A4 portrait; margin: 12mm 15mm; }
        </style>
    @endif
</head>

<body>

    <div class="no-print-bar">
        <a href="{{ route('dashboard.laboran.permintaan.show', $permintaan->id) }}" class="btn-action btn-back">
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
        <div class="watermark-sae">
            <img src="{{ asset('img/logo-icon.png') }}" alt="Watermark SAE">
        </div>

        @include('partials.kop-surat')

        <div class="doc-header">
            @if (empty($sekolahMeta?->kop_url))
                <div class="school-name">{{ $sekolah->nama ?? 'SATUAN PENDIDIKAN' }}</div>
            @endif
            <div class="doc-title-main">BON / FORM PERMINTAAN BAHAN &amp; ALAT PRAKTIK</div>
            <div class="doc-subtitle">Nomor: {{ $permintaan->nomor_permintaan }}</div>
        </div>

        <div class="info-box">
            <div>
                <div class="info-item">
                    <span class="info-label">Pemohon:</span>
                    <span class="info-val">{{ $permintaan->pemohon_nama }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Kelas / Rombel:</span>
                    <span class="info-val">{{ $permintaan->rombel_nama ?: '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Mata Pelajaran:</span>
                    <span class="info-val">{{ $permintaan->mata_pelajaran ?: '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Jurusan:</span>
                    <span class="info-val">{{ $jurusan->nama_jurusan ?? '—' }}</span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Tanggal Permintaan:</span>
                    <span class="info-val">{{ \Carbon\Carbon::parse($permintaan->tanggal_permintaan)->translatedFormat('d F Y') }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tanggal Dibutuhkan:</span>
                    <span class="info-val">
                        {{ $permintaan->tanggal_dibutuhkan ? \Carbon\Carbon::parse($permintaan->tanggal_dibutuhkan)->translatedFormat('d F Y') : '—' }}
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Ruang Lab / Bengkel:</span>
                    <span class="info-val">{{ $permintaan->ruang_lab_nama ?: '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Status:</span>
                    <span class="info-val"><strong>{{ $statusList[$permintaan->status] ?? $permintaan->status }}</strong></span>
                </div>
            </div>
        </div>

        @if ($permintaan->keperluan)
            <div style="font-size: var(--text-sm); line-height: 1.6; color: #1f2937; margin-bottom: 12px; position: relative; z-index: 1;">
                <strong>Keperluan:</strong> {{ $permintaan->keperluan }}
            </div>
        @endif

        <table class="table-data">
            <thead>
                <tr>
                    <th style="width: 34px;">No</th>
                    <th>Nama Alat / Bahan</th>
                    <th style="width: 90px;">Kode</th>
                    <th style="width: 70px;">Jenis</th>
                    <th style="width: 70px;">Diminta</th>
                    <th style="width: 75px;">Diberikan</th>
                    <th style="width: 65px;">Satuan</th>
                    <th style="width: 95px;">Ketersediaan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $i => $it)
                    <tr>
                        <td class="center font-mono">{{ $i + 1 }}</td>
                        <td><strong>{{ $it->nama_item }}</strong></td>
                        <td class="center font-mono">{{ $it->kode_item ?: '—' }}</td>
                        <td class="center">{{ $jenisList[$it->jenis] ?? $it->jenis }}</td>
                        <td class="center font-bold">{{ (float) $it->jumlah_diminta }}</td>
                        <td class="center font-bold">{{ $it->jumlah_diberikan !== null ? (float) $it->jumlah_diberikan : '—' }}</td>
                        <td class="center">{{ $it->satuan }}</td>
                        <td class="center">{{ $ketersediaanList[$it->status_ketersediaan] ?? $it->status_ketersediaan }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="center" style="padding: 14px; color: #6b7280;">
                            <em>Tidak ada item pada permintaan ini.</em>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($permintaan->catatan_pemohon || $permintaan->catatan_petugas)
            <div style="font-size: var(--text-sm); line-height: 1.6; color: #1f2937; margin: 12px 0; position: relative; z-index: 1;">
                @if ($permintaan->catatan_pemohon)
                    <div><strong>Catatan Pemohon:</strong> {{ $permintaan->catatan_pemohon }}</div>
                @endif
                @if ($permintaan->catatan_petugas)
                    <div style="margin-top: 4px;"><strong>Catatan Petugas:</strong> {{ $permintaan->catatan_petugas }}</div>
                @endif
            </div>
        @endif

        <div class="signature-container">
            <div class="sig-box">
                <div class="sig-title">Pemohon,</div>
                <div class="sig-name">{{ $permintaan->pemohon_nama }}</div>
                <div class="sig-nip">NIP: —</div>
            </div>

            <div class="sig-box">
                <div class="sig-title">Petugas Laboran,</div>
                <div class="sig-name">{{ $permintaan->petugas_nama ?: '...................................' }}</div>
                <div class="sig-nip">NIP: —</div>
            </div>

            <div class="sig-box">
                <div class="sig-title">Mengetahui,<br>Kepala Laboratorium / Bengkel</div>
                <div class="sig-name">{{ $kepalaLab?->nama ?: '...................................' }}</div>
                <div class="sig-nip">NIP: {{ $kepalaLab?->nip ?: ($kepalaLab?->nuptk ?: '—') }}</div>
            </div>
        </div>

        <div class="doc-footer">
            <div class="qr-badge">
                <img src="{{ asset('img/logo-icon.png') }}" alt="Logo Verifikasi SAE">
                <div>
                    <strong>Dokumen Resmi Sistem Aplikasi Edukasi (SAE)</strong><br>
                    Dicetak otomatis pada {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y H:i:s') }} WIB.
                </div>
            </div>
            <div style="text-align: right;">
                Halaman 1 / 1<br>
                Format: Kertas A4 {{ ucfirst($orientasi) }}
            </div>
        </div>
    </div>

    <script src="{{ asset('js/cetak.js') }}?v={{ file_exists(public_path('js/cetak.js')) ? filemtime(public_path('js/cetak.js')) : time() }}"></script>
</body>

</html>
