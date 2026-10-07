<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Peserta Didik Kelas {{ $rombel->nama }} — {{ $sekolah->nama ?? 'Sekolah' }}</title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
</head>

<body>

    {{-- Floating Action Bar --}}
    <div class="no-print-bar">
        <a href="javascript:window.close()" class="btn-back">
            <i class="fas fa-arrow-left"></i> Tutup
        </a>

        <div class="orientation-switch">
            <a href="{{ request()->fullUrlWithQuery(['orientasi' => 'landscape']) }}"
                class="btn-orientasi {{ $orientasi === 'landscape' ? 'active' : '' }}" title="Kertas A4 Landscape">
                <i class="fas fa-file-invoice"></i> Landscape
            </a>
            <a href="{{ request()->fullUrlWithQuery(['orientasi' => 'portrait']) }}"
                class="btn-orientasi {{ $orientasi === 'portrait' ? 'active' : '' }}" title="Kertas A4 Portrait">
                <i class="fas fa-file"></i> Portrait
            </a>
        </div>

        <button onclick="window.print()" class="btn-print">
            <i class="fas fa-print"></i> Cetak / Simpan PDF
        </button>
    </div>

    {{-- Lembar Cetak Dokumen Resmi --}}
    <div class="cetak-page">
        <!-- Watermark Logo SAE -->
        <div class="watermark-sae">
            <img src="{{ asset('img/logo-icon.png') }}" alt="Watermark SAE">
        </div>

        <!-- 1. KOP SURAT RESMI SEKOLAH -->
        <div class="kop-container">
            @if (!empty($sekolahMeta?->kop_url))
                <img src="{{ $sekolahMeta->kop_url }}" alt="Kop Surat Resmi {{ $sekolah->nama ?? 'Sekolah' }}"
                    class="kop-image">
            @else
                <div class="kop-fallback">
                    @php
                        $logoUrl = !empty($sekolahMeta?->logo_url)
                            ? $sekolahMeta->logo_url
                            : asset('img/logo-dark.png');
                    @endphp
                    <img src="{{ $logoUrl }}" alt="Logo Sekolah" class="kop-logo"
                        onerror="this.src='/img/logo-dark.png'">
                    <div class="kop-text-wrap">
                        <div class="kop-title">{{ $sekolah->nama ?? 'SISTEM APLIKASI EDUKASI (SAE)' }}</div>
                        <div class="kop-subtitle">
                            {{ $sekolah->alamat_jalan ?? 'Alamat Sekolah Belum Diatur' }}
                            @if (!empty($sekolah->desa_kelurahan))
                                , {{ $sekolah->desa_kelurahan }}
                            @endif
                            @if (!empty($sekolah->kecamatan))
                                , Kec. {{ $sekolah->kecamatan }}
                            @endif
                            @if (!empty($sekolah->kabupaten_kota))
                                , {{ $sekolah->kabupaten_kota }}
                            @endif
                            <br>
                            NPSN: {{ $sekolah->npsn ?? '-' }} | Website: {{ $sekolah->website ?? '-' }} | Email:
                            {{ $sekolah->email ?? '-' }}
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- 2. JUDUL DOKUMEN -->
        <div class="doc-title-block">
            <h2 class="doc-title">DAFTAR DATA PESERTA DIDIK</h2>
            <div class="doc-subtitle">KELAS {{ strtoupper($rombel->nama) }} &bull; TAHUN PELAJARAN
                {{ strtoupper($semesterLabel) }}</div>
        </div>

        <!-- 3. INFO GRID KELAS -->
        <div class="info-grid">
            <div class="info-row">
                <span class="info-lbl">Kelas / Rombel:</span>
                <span class="info-val">{{ $rombel->nama }}</span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Program Keahlian:</span>
                <span class="info-val">{{ $jurusanNama }}</span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Wali Kelas:</span>
                <span class="info-val">{{ $waliNama }}</span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Total Siswa:</span>
                <span class="info-val">{{ $totalSiswa }} Orang (L: {{ $totalL }}, P:
                    {{ $totalP }})</span>
            </div>
        </div>

        <!-- 4. TABEL DATA PESERTA DIDIK -->
        <table class="table-data">
            <thead>
                <tr>
                    <th style="width: 32px;">No</th>
                    <th style="width: 85px;">NISN</th>
                    <th style="width: 85px;">NIPD / NIS</th>
                    <th>Nama Lengkap Peserta Didik</th>
                    <th style="width: 36px;">L/P</th>
                    <th style="width: 145px;">Tempat, Tgl Lahir</th>
                    <th style="width: 70px;">Agama</th>
                    <th style="width: 140px;">Nama Orang Tua / Wali</th>
                    <th>Alamat Tinggal</th>
                    <th style="width: 95px;">No. HP</th>
                    <th style="width: 50px;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($students as $idx => $s)
                    @php
                        $tglLahir = !empty($s->tanggal_lahir)
                            ? \Carbon\Carbon::parse($s->tanggal_lahir)->translatedFormat('d/m/Y')
                            : '';
                        $ttl = trim(($s->tempat_lahir ? $s->tempat_lahir . ($tglLahir ? ', ' : '') : '') . $tglLahir);
                        $ortu = $s->nama_ayah ?: ($s->nama_ibu ?: ($s->nama_wali ?: '—'));
                    @endphp
                    <tr>
                        <td class="col-center">{{ $idx + 1 }}</td>
                        <td class="col-center col-mono">{{ $s->nisn ?: '—' }}</td>
                        <td class="col-center col-mono">{{ $s->nipd ?: '—' }}</td>
                        <td style="font-weight: 600;">{{ $s->nama }}</td>
                        <td class="col-center"
                            style="font-weight: 700; color: {{ $s->jenis_kelamin === 'L' ? '#0284c7' : '#db2777' }};">
                            {{ $s->jenis_kelamin ?: '—' }}
                        </td>
                        <td>{{ $ttl ?: '—' }}</td>
                        <td class="col-center">{{ $s->agama ?: '—' }}</td>
                        <td>{{ $ortu }}</td>
                        <td style="font-size: 0.73rem;">{{ $s->alamat_jalan ?: '—' }}</td>
                        <td class="col-center col-mono">{{ $s->no_hp ?: '—' }}</td>
                        <td class="col-center" style="color: #059669; font-weight: 700;">Aktif</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="col-center" style="padding: 24px; color: #64748b;">
                            Tidak ada data peserta didik di rombongan belajar ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- 5. TANDA TANGAN / PENGESAHAN DOKUMEN RESMI -->
        <div class="ttd-container">
            <div class="ttd-box">
                <div>Mengetahui,</div>
                <div>Kepala Sekolah</div>
                <div class="ttd-space"></div>
                <div style="font-weight: 700; text-decoration: underline;">
                    {{ $kepalaSekolah?->nama ?: $sekolah?->nama_kepala_sekolah ?? 'Kepala Sekolah' }}
                </div>
                <div style="font-size: 0.78rem; color: #4b5563;">
                    NIP. {{ $kepalaSekolah?->nip ?: $sekolah?->nip_kepala_sekolah ?? '—' }}
                </div>
            </div>

            <div class="ttd-box">
                <div>{{ $titimangsa }}</div>
                <div>Wali Kelas {{ $rombel->nama }}</div>
                <div class="ttd-space"></div>
                <div style="font-weight: 700; text-decoration: underline;">
                    {{ $waliNama }}
                </div>
                <div style="font-size: 0.78rem; color: #4b5563;">
                    NIP. {{ $waliNip ?: '—' }}
                </div>
            </div>
        </div>
    </div>

</body>

</html>
