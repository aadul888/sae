<!DOCTYPE html>
<html lang="id">
@php
    $orientasi = $orientasi ?? 'portrait';
    $surat = $surat ?? null;
    $disposisi = $disposisi ?? null;
    $kepsek = $kepsek ?? null;
    $kepalaTas = $kepalaTas ?? null;
    $disposisiCatatan = $disposisiCatatan ?? ($disposisi?->catatan ?? null);
@endphp
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lembar Disposisi — {{ $surat->nomor_surat }}</title>
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
            padding: 24px 30px;
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
            margin-bottom: 16px;
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

        /* Header Dokumen Bersih */
        .doc-header {
            text-align: center;
            margin-bottom: 18px;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .doc-header .doc-title-main {
            font-size: 1.15rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #1e3a8a;
            margin: 0 0 2px 0;
        }

        .doc-header .doc-subtitle {
            font-size: 0.8rem;
            color: #4b5563;
            font-weight: 600;
        }

        /* Table Box Disposisi */
        .table-disp {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .table-disp th,
        .table-disp td {
            border: 1px solid #9ca3af;
            padding: 8px 10px;
            vertical-align: top;
        }

        .table-disp th {
            background-color: #f3f4f6;
            font-weight: 700;
            color: #111827;
            width: 160px;
        }

        /* Checkbox list instruksi */
        .instruksi-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            font-size: 0.82rem;
        }

        .instruksi-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .box-check {
            width: 16px;
            height: 16px;
            border: 1px solid #111827;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
        }

        /* Signatures Area */
        .signature-container {
            display: flex;
            justify-content: space-between;
            margin-top: 24px;
            font-size: 0.82rem;
            page-break-inside: avoid;
            position: relative;
            z-index: 1;
        }

        .sig-box {
            text-align: center;
            width: 240px;
        }

        .sig-title {
            color: #4b5563;
            line-height: 1.35;
            margin-bottom: 60px;
        }

        .sig-name {
            font-weight: 700;
            text-decoration: underline;
            color: #111827;
        }

        .sig-nip {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 2px;
        }

        /* Footer Verifikasi Dokumen Digital */
        .doc-footer {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px dashed #d1d5db;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
            color: #6b7280;
            position: relative;
            z-index: 1;
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
            margin: 10mm 12mm;
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
        <a href="{{ route('dashboard.persuratan.index') }}" class="btn-action btn-back">
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
            <i class="fas fa-print"></i> Cetak Lembar Disposisi (Print / PDF)
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

        {{-- Header Lembar Disposisi --}}
        <div class="doc-header">
            <div class="doc-title-main">LEMBAR DISPOSISI SURAT DINAS</div>
            <div class="doc-subtitle">Nomor Agenda: #{{ str_pad($surat->id, 4, '0', STR_PAD_LEFT) }} &bull; Klasifikasi: {{ strtoupper($surat->jenis_surat) }}</div>
        </div>

        {{-- Data Surat Masuk --}}
        <table class="table-disp">
            <tr>
                <th>Surat Dari</th>
                <td><strong>{{ $surat->pengirim_asal ?: '—' }}</strong></td>
                <th style="width: 130px;">Tanggal Surat</th>
                <td style="width: 170px;">{{ \Carbon\Carbon::parse($surat->tanggal_surat)->translatedFormat('d F Y') }}</td>
            </tr>
            <tr>
                <th>Nomor Surat</th>
                <td><strong>{{ $surat->nomor_surat }}</strong></td>
                <th>Tanggal Diterima</th>
                <td>{{ $surat->tanggal_diterima ? \Carbon\Carbon::parse($surat->tanggal_diterima)->translatedFormat('d F Y') : '—' }}</td>
            </tr>
            <tr>
                <th>Perihal / Isi Ringkas</th>
                <td colspan="3">
                    <div style="font-weight: 700; color: #1e3a8a; margin-bottom: 4px;">{{ $surat->perihal }}</div>
                    @if ($surat->keterangan)
                        <div style="font-size: 0.8rem; color: #4b5563;">{{ $surat->keterangan }}</div>
                    @endif
                </td>
            </tr>
        </table>

        {{-- Instruksi Disposisi Kepala Sekolah / Pimpinan --}}
        <table class="table-disp">
            <tr>
                <th style="width: 50%;">DITERUSKAN KEPADA:</th>
                <th style="width: 50%;">DENGAN HORMAT HARAP:</th>
            </tr>
            <tr>
                <td style="padding: 12px;">
                    <div class="instruksi-grid">
                        <div class="instruksi-item">
                            <span class="box-check">{{ in_array('Waka Kurikulum', $disposisiTujuan ?? []) ? '✓' : '' }}</span>
                            <span>Waka Kurikulum</span>
                        </div>
                        <div class="instruksi-item">
                            <span class="box-check">{{ in_array('Waka Kesiswaan', $disposisiTujuan ?? []) ? '✓' : '' }}</span>
                            <span>Waka Kesiswaan</span>
                        </div>
                        <div class="instruksi-item">
                            <span class="box-check">{{ in_array('Waka Sarpras', $disposisiTujuan ?? []) ? '✓' : '' }}</span>
                            <span>Waka Sarpras</span>
                        </div>
                        <div class="instruksi-item">
                            <span class="box-check">{{ in_array('Waka Hubin', $disposisiTujuan ?? []) ? '✓' : '' }}</span>
                            <span>Waka Hubin</span>
                        </div>
                        <div class="instruksi-item">
                            <span class="box-check">{{ in_array('Kepala TAS', $disposisiTujuan ?? []) || in_array('KTU', $disposisiTujuan ?? []) ? '✓' : '' }}</span>
                            <span>Kepala TAS / Tata Usaha</span>
                        </div>
                        <div class="instruksi-item">
                            <span class="box-check">{{ in_array('Guru Piket', $disposisiTujuan ?? []) ? '✓' : '' }}</span>
                            <span>Guru Piket / Kesiswaan</span>
                        </div>
                    </div>

                    @if (!empty($disposisiTujuanLain))
                        <div style="margin-top: 10px; font-size: 0.8rem; border-top: 1px dashed #cbd5e1; padding-top: 6px;">
                            <strong>Personel Ditunjuk:</strong> {{ $disposisiTujuanLain }}
                        </div>
                    @endif
                </td>
                <td style="padding: 12px;">
                    <div class="instruksi-grid">
                        <div class="instruksi-item">
                            <span class="box-check">{{ ($disposisiInstruksi ?? '') === 'Tanggapi / Tindak Lanjuti' ? '✓' : '' }}</span>
                            <span>Tanggapi / Tindak Lanjuti</span>
                        </div>
                        <div class="instruksi-item">
                            <span class="box-check">{{ ($disposisiInstruksi ?? '') === 'Hadir / Wakili' ? '✓' : '' }}</span>
                            <span>Hadir / Wakili</span>
                        </div>
                        <div class="instruksi-item">
                            <span class="box-check">{{ ($disposisiInstruksi ?? '') === 'Teliti & Laporkan' ? '✓' : '' }}</span>
                            <span>Teliti &amp; Laporkan</span>
                        </div>
                        <div class="instruksi-item">
                            <span class="box-check">{{ ($disposisiInstruksi ?? '') === 'Koordinasikan' ? '✓' : '' }}</span>
                            <span>Koordinasikan</span>
                        </div>
                        <div class="instruksi-item">
                            <span class="box-check">{{ ($disposisiInstruksi ?? '') === 'Bicarakan Bersama' ? '✓' : '' }}</span>
                            <span>Bicarakan Bersama</span>
                        </div>
                        <div class="instruksi-item">
                            <span class="box-check">{{ ($disposisiInstruksi ?? '') === 'Arsipkan' ? '✓' : '' }}</span>
                            <span>Ketahui / Arsipkan</span>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <th colspan="2">CATATAN / PETUNJUK KHUSUS PIMPINAN:</th>
            </tr>
            <tr>
                <td colspan="2" style="min-height: 80px; padding: 12px; font-size: 0.84rem; line-height: 1.45;">
                    {{ $disposisiCatatan ?: 'Segera koordinasikan dengan unit terkait dan laporkan hasilnya kepada Kepala Sekolah.' }}
                </td>
            </tr>
        </table>

        {{-- Tanda Tangan Pimpinan --}}
        <div class="signature-container">
            <div class="sig-box">
                <div class="sig-title">
                    Kepala Tenaga Administrasi (TAS),
                </div>
                <div class="sig-name">{{ $kepalaTas?->nama ?: 'Euis Nur Komariah, S.Pd.' }}</div>
                <div class="sig-nip">NIP: {{ $kepalaTas?->nip ?: ($kepalaTas?->nuptk ?: '—') }}</div>
            </div>

            <div class="sig-box">
                <div class="sig-title">
                    Kepala Satuan Pendidikan,
                </div>
                <div class="sig-name">{{ $kepsek?->nama ?: ($sekolah->nama_kepsek ?? 'Drs. H. Mulyadi, M.Pd.') }}</div>
                <div class="sig-nip">NIP: {{ $kepsek?->nip ?: ($sekolah->nip_kepsek ?? '—') }}</div>
            </div>
        </div>

        {{-- Footer Keabsahan --}}
        <div class="doc-footer">
            <div>
                <strong>Dokumen Sah Tata Naskah Dinas Sekolah — Sistem Aplikasi Edukasi (SAE)</strong><br>
                Dicetak pada {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y H:i') }} WIB &bull; Status: {{ strtoupper($surat->status) }}
            </div>
            <div>
                Format A4 {{ ucfirst($orientasi) }}
            </div>
        </div>
    </div>

</body>

</html>
