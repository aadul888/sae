<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jurnal Kegiatan Laboran — {{ $range['label'] }}</title>
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
        <a href="{{ route('dashboard.laboran.jurnal.index', request()->except('orientasi')) }}" class="btn-action btn-back">
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
            <div class="doc-title-main">JURNAL KEGIATAN HARIAN LABORAN</div>
            <div class="doc-subtitle">PERIODE: {{ strtoupper($range['label']) }} &bull; A4 {{ strtoupper($orientasi) }}</div>
        </div>

        <div class="info-box">
            <div>
                <div class="info-item">
                    <span class="info-label">Petugas Laboran:</span>
                    <span class="info-val">{{ $petugas['nama'] }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">NIP:</span>
                    <span class="info-val">{{ $petugas['nip'] }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Jurusan:</span>
                    <span class="info-val">{{ $jurusan->nama_jurusan ?? '—' }}</span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Rentang:</span>
                    <span class="info-val">{{ $range['label'] }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Total Kegiatan:</span>
                    <span class="info-val">{{ $items->count() }} kegiatan</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Akumulasi Durasi:</span>
                    <span class="info-val">{{ intdiv($totalMenit, 60) }} jam {{ $totalMenit % 60 }} menit</span>
                </div>
            </div>
        </div>

        @if ($rekapKategori->count() > 0)
            <div class="section-subhead"><i class="fas fa-chart-pie"></i> A. Rekapitulasi per Indikator Tupoksi</div>
            <table class="table-data">
                <thead>
                    <tr>
                        <th style="width: 34px;">No</th>
                        <th style="width: 70px;">Indikator</th>
                        <th>Kategori Kegiatan</th>
                        <th style="width: 90px;">Jumlah</th>
                        <th style="width: 100px;">Durasi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rekapKategori as $i => $rk)
                        <tr>
                            <td class="center font-mono">{{ $i + 1 }}</td>
                            <td class="center font-bold">{{ $rk->indikator > 0 ? '#' . $rk->indikator : '—' }}</td>
                            <td><strong>{{ $rk->label }}</strong></td>
                            <td class="center font-bold">{{ $rk->jumlah }}</td>
                            <td class="center font-mono">{{ intdiv($rk->menit, 60) }}j {{ $rk->menit % 60 }}m</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="section-subhead"><i class="fas fa-list"></i> B. Rincian Jurnal Kegiatan</div>
        <table class="table-data">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th style="width: 72px;">Tanggal</th>
                    <th style="width: 76px;">Jam</th>
                    <th>Kegiatan</th>
                    <th style="width: 130px;">Kategori</th>
                    <th style="width: 80px;">Hasil</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $i => $j)
                    @php $meta = $kategoriList[$j->kategori] ?? [ucfirst($j->kategori), 0]; @endphp
                    <tr>
                        <td class="center font-mono">{{ $i + 1 }}</td>
                        <td class="center">{{ \Carbon\Carbon::parse($j->tanggal)->format('d/m/Y') }}</td>
                        <td class="center font-mono">
                            {{ $j->jam_mulai ? substr($j->jam_mulai, 0, 5) : '—' }}{{ $j->jam_selesai ? '-' . substr($j->jam_selesai, 0, 5) : '' }}
                        </td>
                        <td>
                            <strong>{{ $j->kegiatan }}</strong>
                            @if ($j->uraian)
                                <div style="font-size: 9px; color: #4b5563;">{{ \Illuminate\Support\Str::limit($j->uraian, 110) }}</div>
                            @endif
                        </td>
                        <td>{{ $meta[0] }}{{ $meta[1] > 0 ? ' (#' . $meta[1] . ')' : '' }}</td>
                        <td style="font-size: 9px;">{{ \Illuminate\Support\Str::limit($j->hasil, 60) ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="center" style="padding: 14px; color: #6b7280;">
                            <em>Tidak ada kegiatan pada periode ini.</em>
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
                <div class="sig-title">Kepala Laboratorium / Bengkel,</div>
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
