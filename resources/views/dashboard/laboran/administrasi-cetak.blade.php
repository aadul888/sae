<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Administrasi Jurusan — {{ $range['label'] }}</title>
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
            @page { size: A4 portrait; margin: 10mm 12mm; }
        </style>
    @endif
</head>

<body>

    <div class="no-print-bar">
        <a href="{{ route('dashboard.laboran.administrasi.index', request()->except('orientasi')) }}" class="btn-action btn-back">
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
            <div class="doc-title-main">BUKU ADMINISTRASI JURUSAN</div>
            <div class="doc-subtitle">PERIODE: {{ strtoupper($range['label']) }} &bull; A4 {{ strtoupper($orientasi) }}</div>
        </div>

        <div class="info-box">
            <div>
                <div class="info-item">
                    <span class="info-label">Jurusan:</span>
                    <span class="info-val">{{ $jurusan->nama_jurusan ?? 'Semua Jurusan' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Petugas Laboran:</span>
                    <span class="info-val">{{ $petugas['nama'] }}</span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Rentang:</span>
                    <span class="info-val">{{ $range['label'] }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Jumlah Dokumen:</span>
                    <span class="info-val">{{ $items->count() }} dokumen</span>
                </div>
            </div>
        </div>

        <table class="table-data">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th style="width: 68px;">Tanggal</th>
                    <th style="width: 95px;">Nomor</th>
                    <th style="width: 100px;">Jenis</th>
                    <th>Perihal</th>
                    <th style="width: 100px;">Asal/Tujuan</th>
                    <th style="width: 60px;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $i => $a)
                    <tr>
                        <td class="center font-mono">{{ $i + 1 }}</td>
                        <td class="center">{{ \Carbon\Carbon::parse($a->tanggal)->format('d/m/Y') }}</td>
                        <td class="center font-mono" style="font-size: 9px;">{{ $a->nomor_dokumen ?: '—' }}</td>
                        <td style="font-size: 9px;">{{ $jenisList[$a->jenis_dokumen] ?? $a->jenis_dokumen }}</td>
                        <td>
                            <strong>{{ $a->perihal }}</strong>
                            @if ($a->ringkasan)
                                <div style="font-size: 9px; color: #4b5563;">{{ \Illuminate\Support\Str::limit($a->ringkasan, 90) }}</div>
                            @endif
                        </td>
                        <td style="font-size: 9px;">{{ $a->asal_tujuan ?: '—' }}</td>
                        <td class="center" style="font-size: 9px;">{{ $statusList[$a->status] ?? $a->status }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="center" style="padding: 14px; color: #6b7280;">
                            <em>Tidak ada dokumen administrasi pada periode ini.</em>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="signature-container">
            <div class="sig-box">
                <div class="sig-title">Petugas Laboran,</div>
                <div class="sig-name">{{ $petugas['nama'] }}</div>
                <div class="sig-nip">NIP: {{ $petugas['nip'] }}</div>
            </div>

            <div class="sig-box">
                <div class="sig-title">Kepala Program Keahlian,</div>
                <div class="sig-name">{{ $kepalaLab?->nama ?: '...................................' }}</div>
                <div class="sig-nip">NIP: {{ $kepalaLab?->nip ?: ($kepalaLab?->nuptk ?: '—') }}</div>
            </div>

            <div class="sig-box">
                <div class="sig-title">Mengetahui,<br>Kepala Satuan Pendidikan,</div>
                <div class="sig-name">{{ $kepalaSekolah?->nama ?: '...................................' }}</div>
                <div class="sig-nip">NIP: {{ $kepalaSekolah?->nip ?: ($kepalaSekolah?->nuptk ?: '—') }}</div>
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
