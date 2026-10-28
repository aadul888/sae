<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumentasi Kebersihan — {{ $range['label'] }}</title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
    <link rel="stylesheet"
        href="{{ asset('css/cetak-laboran.css') }}?v={{ file_exists(public_path('css/cetak-laboran.css')) ? filemtime(public_path('css/cetak-laboran.css')) : time() }}">
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
        <a href="{{ route('dashboard.laboran.kebersihan.index', request()->except('orientasi')) }}" class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>

        <div class="doc-toolbar-group">
            <span class="doc-toolbar-label">Laporan:</span>
            <a href="{{ request()->fullUrlWithQuery(['mode' => 'ringkasan']) }}"
                class="btn-orientasi {{ ($mode ?? 'ringkasan') === 'ringkasan' ? 'active' : '' }}"
                title="Versi ringkas: rekap kegiatan per area">
                <i class="fas fa-list"></i> Ringkasan
            </a>
            <a href="{{ request()->fullUrlWithQuery(['mode' => 'lengkap']) }}"
                class="btn-orientasi {{ ($mode ?? 'ringkasan') === 'lengkap' ? 'active' : '' }}"
                title="Versi lengkap: rincian seluruh kegiatan">
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
            <div class="doc-title-main">DOKUMENTASI KEGIATAN KEBERSIHAN LINGKUNGAN PRODUKTIF</div>
            <div class="doc-subtitle">PERIODE: {{ strtoupper($range['label']) }}</div>
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
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Jurusan:</span>
                    <span class="info-val">{{ $jurusan->nama_jurusan ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Jumlah Kegiatan:</span>
                    <span class="info-val">{{ $items->count() }} kegiatan</span>
                </div>
            </div>
        </div>

        @if (($mode ?? 'ringkasan') === 'lengkap')
            <table class="table-data">
                <thead>
                    <tr>
                        <th style="width: 30px;">No</th>
                        <th style="width: 72px;">Tanggal</th>
                        <th style="width: 120px;">Area</th>
                        <th>Uraian Kegiatan</th>
                        <th style="width: 70px;">Petugas</th>
                        <th style="width: 85px;">Kondisi</th>
                        <th style="width: 110px;">Tindak Lanjut</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $i => $k)
                        <tr>
                            <td class="center font-mono">{{ $i + 1 }}</td>
                            <td class="center">{{ \Carbon\Carbon::parse($k->tanggal_kegiatan)->format('d/m/Y') }}</td>
                            <td><strong>{{ $k->area }}</strong></td>
                            <td>{{ \Illuminate\Support\Str::limit($k->uraian_kegiatan, 110) ?: '—' }}</td>
                            <td class="center">{{ $k->jumlah_petugas }}</td>
                            <td class="center">{{ $kondisiList[$k->kondisi_akhir] ?? $k->kondisi_akhir }}</td>
                            <td style="font-size: 9px;">{{ \Illuminate\Support\Str::limit($k->tindak_lanjut, 60) ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="center" style="padding: 14px; color: #6b7280;">
                                <em>Tidak ada kegiatan kebersihan pada periode ini.</em>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @else
            @php
                $rekapArea = $items->groupBy('area')->map(function ($g, $area) {
                    return (object) [
                        'area'     => $area ?: '—',
                        'jumlah'   => $g->count(),
                        'petugas'  => (int) $g->sum('jumlah_petugas'),
                        'terakhir' => $g->max('tanggal_kegiatan'),
                    ];
                })->sortByDesc('jumlah')->values();
            @endphp

            <div class="section-subhead"><i class="fas fa-chart-pie"></i> Rekapitulasi Kebersihan per Area</div>
            <table class="table-data is-ringkasan">
                <thead>
                    <tr>
                        <th style="width: 30px;">No</th>
                        <th>Area / Lingkungan Produktif</th>
                        <th style="width: 90px;">Kegiatan</th>
                        <th style="width: 90px;">Total Petugas</th>
                        <th style="width: 100px;">Terakhir</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rekapArea as $i => $ra)
                        <tr>
                            <td class="center font-mono">{{ $i + 1 }}</td>
                            <td><strong>{{ $ra->area }}</strong></td>
                            <td class="center font-bold">{{ $ra->jumlah }}</td>
                            <td class="center">{{ $ra->petugas }}</td>
                            <td class="center">
                                {{ $ra->terakhir ? \Carbon\Carbon::parse($ra->terakhir)->format('d/m/Y') : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="center" style="padding: 14px; color: #6b7280;">
                                <em>Tidak ada kegiatan kebersihan pada periode ini.</em>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="right">Jumlah</td>
                        <td class="center">{{ $items->count() }}</td>
                        <td class="center">{{ (int) $items->sum('jumlah_petugas') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>

            <div class="doc-trace">
                <i class="fas fa-circle-info"></i>
                Mode <strong>Ringkasan</strong> menampilkan rekapitulasi per area.
                Pilih <strong>Lengkap</strong> pada bar alat di atas untuk rincian
                {{ $items->count() }} kegiatan beserta uraian &amp; tindak lanjut.
            </div>
        @endif

        @include('dashboard.laboran.partials.ttd-cetak')

        <div class="doc-footer">
            <div class="qr-badge">
                <img src="{{ asset('img/logo-icon.png') }}" alt="Logo Verifikasi SAE">
                <div>
                    <strong>Dokumen Resmi Sistem Aplikasi Edukasi (SAE)</strong><br>
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

    <script src="{{ asset('js/cetak.js') }}?v={{ file_exists(public_path('js/cetak.js')) ? filemtime(public_path('js/cetak.js')) : time() }}"></script>
</body>

</html>
