<!DOCTYPE html>
<html lang="id">
@php
    $orientasi = $orientasi ?? 'portrait';
    $rombelNama = $rombelNama ?? 'Kelas X / XI / XII';
    $jurusanNama = $jurusanNama ?? 'Semua Program Keahlian';
    $tahunAjaran = $tahunAjaran ?? date('Y') . '/' . (date('Y') + 1);
    $docId = $docId ?? 'SAE-MUT-OFFICIAL';
    $qrUri = $qrUri ?? null;
    $siswa = $siswa ?? ($mutasi->siswa ?? null);
@endphp
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Keterangan Mutasi — {{ $siswa?->nama ?? $mutasi->siswa?->nama ?? 'Peserta Didik' }}</title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
</head>

<body>

    {{-- Floating Bar --}}
    <div class="no-print-bar">
        <a href="{{ route('dashboard.kesiswaan.index', ['tab' => 'mutasi']) }}" class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>

        <div class="orientation-switch">
            <a href="{{ request()->fullUrlWithQuery(['orientasi' => 'portrait']) }}"
                class="btn-orientasi {{ $orientasi === 'portrait' ? 'active' : '' }}">
                <i class="fas fa-file"></i> Portrait
            </a>
            <a href="{{ request()->fullUrlWithQuery(['orientasi' => 'landscape']) }}"
                class="btn-orientasi {{ $orientasi === 'landscape' ? 'active' : '' }}">
                <i class="fas fa-file-invoice"></i> Landscape
            </a>
        </div>

        <button onclick="window.print()" class="btn-action btn-print">
            <i class="fas fa-print"></i> Cetak Surat Mutasi (Print / PDF)
        </button>
    </div>

    {{-- Lembar Cetak --}}
    <div class="cetak-page">
        {{-- Watermark Logo SAE --}}
        <div class="watermark-sae">
            <img src="{{ asset('img/logo-icon.png') }}" alt="Watermark SAE">
        </div>

        {{-- Kop Surat Sekolah --}}
        <div class="kop-container">
            @if (!empty($sekolahMeta?->kop_url))
                <img src="{{ $sekolahMeta->kop_url }}" alt="Kop Surat" class="kop-image">
            @else
                <div class="kop-fallback">
                    @php
                        $logoUrl = !empty($sekolahMeta?->logo_url) ? $sekolahMeta->logo_url : asset('img/logo-dark.png');
                    @endphp
                    <img src="{{ $logoUrl }}" alt="Logo" class="kop-logo" onerror="this.src='/img/logo-dark.png'">
                    <div class="kop-text-wrap">
                        <div class="kop-title">{{ $sekolah->nama ?? 'SISTEM APLIKASI EDUKASI (SAE)' }}</div>
                        <div class="kop-subtitle">
                            NPSN: {{ $sekolah->npsn ?? '-' }} &bull; Alamat: {{ $sekolah->alamat_jalan ?? 'Jalan Pendidikan' }},
                            {{ $sekolah->kabupaten_kota ?? '' }}<br>
                            Kontak: {{ $sekolah->nomor_telepon ?? '-' }} &bull; Email: {{ $sekolah->email ?? '-' }}
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Header Judul Surat --}}
        <div class="doc-header">
            <div class="doc-title-main">SURAT KETERANGAN PINDAH / MUTASI SISWA</div>
            <div class="doc-subtitle">Nomor: {{ $mutasi->nomor_surat_mutasi }}</div>
        </div>

        {{-- Naskah Surat --}}
        <div class="surat-body">
            <p>
                Yang bertanda tangan di bawah ini, Kepala {{ $sekolah->nama ?? 'SMK NEGERI 1 PAGELARAN' }}, menerangkan bahwa:
            </p>

            <table class="table-bio">
                <tr>
                    <td class="label">Nama Peserta Didik</td>
                    <td class="colon">:</td>
                    <td class="val">{{ $siswa->nama }}</td>
                </tr>
                <tr>
                    <td class="label">NISN / NIPD</td>
                    <td class="colon">:</td>
                    <td class="val">{{ $siswa->nisn ?: '—' }} / {{ $siswa->nipd ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Tempat, Tanggal Lahir</td>
                    <td class="colon">:</td>
                    <td class="val">{{ $siswa->tempat_lahir ?: '—' }}, {{ $siswa->tanggal_lahir ? \Carbon\Carbon::parse($siswa->tanggal_lahir)->translatedFormat('d F Y') : '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Jenis Kelamin</td>
                    <td class="colon">:</td>
                    <td class="val">{{ $siswa->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
                </tr>
                <tr>
                    <td class="label">Tingkat / Kelas Terakhir</td>
                    <td class="colon">:</td>
                    <td class="val">{{ $rombelNama }}</td>
                </tr>
                <tr>
                    <td class="label">Program Keahlian</td>
                    <td class="colon">:</td>
                    <td class="val">{{ $jurusanNama }}</td>
                </tr>
                <tr>
                    <td class="label">Nama Orang Tua / Wali</td>
                    <td class="colon">:</td>
                    <td class="val">{{ $siswa->nama_ayah ?: ($siswa->nama_ibu ?: ($siswa->nama_wali ?: '—')) }}</td>
                </tr>
            </table>

            <p>
                Sesuai dengan permohonan tertulis dari orang tua/wali siswa bersangkutan, terhitung sejak tanggal <strong>{{ \Carbon\Carbon::parse($mutasi->tanggal_mutasi)->translatedFormat('d F Y') }}</strong> dinyatakan telah <strong>PINDAH / KELUAR</strong> dari {{ $sekolah->nama ?? 'SMK NEGERI 1 PAGELARAN' }} menuju:
            </p>

            <table class="table-bio">
                <tr>
                    <td class="label">Sekolah Tujuan</td>
                    <td class="colon">:</td>
                    <td class="val">{{ $mutasi->sekolah_tujuan_asal ?: 'Sesuai Permohonan Orang Tua' }}</td>
                </tr>
                <tr>
                    <td class="label">Alasan Kepindahan</td>
                    <td class="colon">:</td>
                    <td class="val">{{ $mutasi->alasan }}</td>
                </tr>
            </table>

            <p>
                Surat keterangan pindah sekolah ini diterbitkan dengan catatan bahwa seluruh administrasi dan kewajiban peserta didik selama berada di satuan pendidikan kami telah diselesaikan.
            </p>

            <p>
                Demikian surat keterangan pindah sekolah ini kami buat untuk dapat dipergunakan sebagaimana mestinya.
            </p>
        </div>

        {{-- Tanda Tangan Kepala Sekolah --}}
        <div class="signature-container">
            <div class="sig-box">
                <div class="sig-date">
                    {{ $sekolah->kabupaten_kota ? ucwords(strtolower($sekolah->kabupaten_kota)) : 'Pagelaran' }},
                    {{ \Carbon\Carbon::parse($mutasi->tanggal_mutasi)->translatedFormat('d F Y') }}
                </div>
                <div class="sig-title">
                    Kepala Satuan Pendidikan,
                </div>
                <div class="sig-name">{{ $kepsek?->nama ?: ($sekolah->nama_kepsek ?? 'Drs. H. Mulyadi, M.Pd.') }}</div>
                <div class="sig-nip">NIP: {{ $kepsek?->nip ?: ($sekolah->nip_kepsek ?? '—') }}</div>
            </div>
        </div>

        {{-- Footer Keabsahan & QR Code Verifikasi --}}
        <div class="doc-footer">
            <div class="qr-badge">
                <img src="{{ $qrUri }}" alt="QR Code Verifikasi">
                <div>
                    <strong>Dokumen Sah Mutasi Siswa — Sistem Aplikasi Edukasi (SAE)</strong><br>
                    ID Dokumen: <code>{{ $docId }}</code> &bull; Pindai QR Code untuk verifikasi keaslian berkas digital.
                </div>
            </div>
            <div style="text-align: right;">
                Halaman 1 / 1<br>
                Format Kertas: A4 {{ ucfirst($orientasi) }}
            </div>
        </div>
    </div>

</body>

</html>
