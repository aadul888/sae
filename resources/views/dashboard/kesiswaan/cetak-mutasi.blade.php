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
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #111827;
        }

        body {
            background-color: #f3f4f6;
            padding: 24px;
        }

        .cetak-page {
            background: #fff;
            margin: 0 auto 28px auto;
            padding: 24px 35px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            position: relative;
            overflow: hidden;
            @if ($orientasi === 'landscape')
                max-width: 1100px;
            @else
                max-width: 850px;
            @endif
        }

        /* Watermark Logo SAE */
        .watermark-sae {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 420px;
            height: 420px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.05;
            pointer-events: none;
            z-index: 0;
        }

        .watermark-sae img {
            width: 100%;
            height: auto;
            filter: grayscale(100%);
        }

        /* Kop Surat Sekolah Resmi */
        .kop-container {
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .kop-image {
            width: 100%;
            max-height: 125px;
            object-fit: contain;
            display: block;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 8px;
        }

        .kop-fallback {
            display: flex;
            align-items: center;
            gap: 16px;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 10px;
            text-align: center;
        }

        .kop-logo {
            width: 68px;
            height: 68px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .kop-text-wrap {
            flex: 1;
        }

        .kop-title {
            font-size: 1.15rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #111827;
        }

        .kop-subtitle {
            font-size: 0.78rem;
            color: #4b5563;
            line-height: 1.35;
        }

        /* Header Judul Surat Resmi */
        .doc-header {
            text-align: center;
            margin-bottom: 24px;
            position: relative;
            z-index: 1;
        }

        .doc-header .doc-title-main {
            font-size: 1.15rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #111827;
            text-decoration: underline;
            margin: 0 0 2px 0;
        }

        .doc-header .doc-subtitle {
            font-size: 0.85rem;
            color: #374151;
            font-weight: 600;
        }

        /* Isi Naskah Surat */
        .surat-body {
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 24px;
            text-align: justify;
            position: relative;
            z-index: 1;
        }

        .surat-body p {
            margin-bottom: 12px;
            text-indent: 28px;
        }

        /* Tabel Biodata Siswa */
        .table-bio {
            width: 100%;
            margin: 12px 0 16px 20px;
            font-size: 0.88rem;
            border-collapse: collapse;
        }

        .table-bio td {
            padding: 4px 6px;
            vertical-align: top;
        }

        .table-bio td.label {
            width: 190px;
            color: #4b5563;
            font-weight: 600;
        }

        .table-bio td.colon {
            width: 12px;
        }

        .table-bio td.val {
            font-weight: 700;
            color: #111827;
        }

        /* Signatures Area */
        .signature-container {
            display: flex;
            justify-content: flex-end;
            margin-top: 30px;
            font-size: 0.86rem;
            page-break-inside: avoid;
            position: relative;
            z-index: 1;
        }

        .sig-box {
            text-align: center;
            width: 260px;
        }

        .sig-date {
            margin-bottom: 6px;
            color: #374151;
        }

        .sig-title {
            color: #111827;
            font-weight: 700;
            margin-bottom: 65px;
        }

        .sig-name {
            font-weight: 800;
            text-decoration: underline;
            color: #111827;
        }

        .sig-nip {
            font-size: 0.78rem;
            color: #4b5563;
            margin-top: 2px;
        }

        /* Footer Verifikasi Dokumen Digital */
        .doc-footer {
            margin-top: 30px;
            padding-top: 12px;
            border-top: 1px dashed #d1d5db;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
            color: #6b7280;
            position: relative;
            z-index: 1;
        }

        .qr-badge {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .qr-badge img {
            width: 50px;
            height: 50px;
            border: 1px solid #d1d5db;
            padding: 2px;
            background: #fff;
            border-radius: 4px;
        }

        /* Floating bar */
        .no-print-bar {
            position: fixed;
            bottom: 20px;
            right: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.96);
            padding: 8px 16px;
            border-radius: 50px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(6px);
            z-index: 1000;
            border: 1px solid rgba(229, 231, 235, 0.8);
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 0.82rem;
            font-weight: 700;
            border-radius: 30px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .btn-print {
            background: #2563eb;
            color: #fff;
        }

        .btn-print:hover {
            background: #1d4ed8;
        }

        .btn-back {
            background: #4b5563;
            color: #fff;
        }

        .btn-back:hover {
            background: #374151;
        }

        .orientation-switch {
            display: inline-flex;
            align-items: center;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 30px;
            padding: 2px;
        }

        .btn-orientasi {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 14px;
            font-size: 0.8rem;
            font-weight: 700;
            color: #4b5563;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .btn-orientasi:hover {
            color: #111827;
        }

        .btn-orientasi.active {
            background: #1e3a8a;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(30, 58, 138, 0.3);
        }

        @page {
            size: A4 {{ $orientasi }};
            margin: 10mm 14mm;
        }

        @media print {
            body {
                background: #fff !important;
                padding: 0 !important;
            }

            .cetak-page {
                box-shadow: none !important;
                padding: 0 !important;
                max-width: 100% !important;
                margin-bottom: 0 !important;
                border-radius: 0 !important;
            }

            .no-print-bar {
                display: none !important;
            }
        }
    </style>
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
