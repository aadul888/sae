<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $isDinas = str_contains(strtolower($cuti->jenis), 'dinas');
        $judulDokumen = $isDinas ? 'SURAT PERINTAH TUGAS (SPT)' : 'SURAT KETERANGAN IZIN / CUTI';
    @endphp
    <title>{{ $judulDokumen }} - {{ $cuti->gtk?->nama }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 20mm 15mm 20mm;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #1f2937;
            background-color: #f3f4f6;
            margin: 0;
            padding: 20px;
        }
        .page-container {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm 20mm;
            margin: 0 auto;
            background: #ffffff;
            position: relative;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            box-sizing: border-box;
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 5.5rem;
            font-weight: 900;
            color: rgba(79, 70, 229, 0.035);
            text-transform: uppercase;
            letter-spacing: 12px;
            pointer-events: none;
            z-index: 0;
            white-space: nowrap;
            user-select: none;
        }
        .kop-surat {
            border-bottom: 3px double #1f2937;
            padding-bottom: 12px;
            margin-bottom: 24px;
            text-align: center;
        }
        .kop-surat img {
            width: 100%;
            max-height: 120px;
            object-fit: contain;
        }
        .kop-fallback h2 {
            margin: 0;
            font-size: 14pt;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #111827;
        }
        .kop-fallback h1 {
            margin: 4px 0;
            font-size: 18pt;
            font-weight: 800;
            text-transform: uppercase;
            color: #1e3a8a;
        }
        .kop-fallback p {
            margin: 0;
            font-size: 9.5pt;
            color: #4b5563;
        }
        .doc-title {
            text-align: center;
            margin-bottom: 24px;
        }
        .doc-title h3 {
            margin: 0;
            font-size: 13pt;
            text-decoration: underline;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #111827;
        }
        .doc-title .doc-num {
            margin: 4px 0 0 0;
            font-size: 10.5pt;
            color: #4b5563;
        }
        .content-body {
            position: relative;
            z-index: 1;
            font-size: 11pt;
            line-height: 1.6;
            text-align: justify;
        }
        .bio-table {
            width: 100%;
            border-collapse: collapse;
            margin: 14px 0 20px 0;
        }
        .bio-table td {
            padding: 5px 8px;
            vertical-align: top;
            font-size: 11pt;
        }
        .bio-table td.label {
            width: 28%;
            color: #374151;
        }
        .bio-table td.sep {
            width: 3%;
            text-align: center;
        }
        .bio-table td.val {
            font-weight: 600;
            color: #111827;
        }
        .ttd-section {
            display: flex;
            justify-content: flex-end;
            margin-top: 40px;
            position: relative;
            z-index: 1;
            page-break-inside: avoid;
        }
        .ttd-box {
            text-align: center;
            width: 250px;
        }
        .ttd-space {
            height: 75px;
        }
        .print-control-bar {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #ffffff;
            padding: 10px 16px;
            border-radius: 50px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
            display: flex;
            gap: 12px;
            align-items: center;
            z-index: 99999;
            border: 1px solid #e5e7eb;
        }
        .btn-print {
            background: #4f46e5;
            color: #ffffff;
            border: none;
            padding: 8px 18px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }
        .btn-print:hover {
            background: #4338ca;
        }
        .btn-close-print {
            background: #e5e7eb;
            color: #374151;
            border: none;
            padding: 8px 14px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
        }
        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
            }
            .page-container {
                box-shadow: none;
                margin: 0;
                padding: 0;
                width: 100%;
                min-height: auto;
            }
            .print-control-bar {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="print-control-bar">
        <button class="btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> Cetak Dokumen
        </button>
        <button class="btn-close-print" onclick="window.close()">
            <i class="fas fa-times"></i> Tutup
        </button>
    </div>

    <div class="page-container">
        <div class="watermark">SAE RESMI</div>

        {{-- Kop Surat --}}
        <div class="kop-surat">
            @if($sekolahMeta && $sekolahMeta->kop_url)
                <img src="{{ asset($sekolahMeta->kop_url) }}" alt="Kop Surat">
            @else
                <div class="kop-fallback">
                    <h2>PEMERINTAH PROVINSI / DAERAH</h2>
                    <h2>DINAS PENDIDIKAN DAN KEBUDAYAAN</h2>
                    <h1>{{ $sekolah ? $sekolah->nama : 'SMK NEGERI CONTOH' }}</h1>
                    <p>{{ $sekolah ? ($sekolah->alamat_jalan . ', ' . $sekolah->desa_kelurahan . ', ' . $sekolah->kecamatan) : 'Jalan Pendidikan No. 123' }}</p>
                    <p>NPSN: {{ $sekolah ? $sekolah->npsn : '-' }} | Surel: {{ $sekolah ? $sekolah->email : '-' }}</p>
                </div>
            @endif
        </div>

        {{-- Judul Surat --}}
        <div class="doc-title">
            <h3>{{ $judulDokumen }}</h3>
            <p class="doc-num">Nomor: 800 / {{ str_pad($cuti->id, 3, '0', STR_PAD_LEFT) }} / CADISDIK / {{ date('Y') }}</p>
        </div>

        {{-- Isi Surat --}}
        <div class="content-body">
            @if($isDinas)
            <p>Yang bertanda tangan di bawah ini Kepala Sekolah <strong>{{ $sekolah ? $sekolah->nama : 'Satuan Pendidikan' }}</strong>, dengan ini memberikan tugas kedinasan kepada:</p>
            @else
            <p>Yang bertanda tangan di bawah ini Kepala Sekolah <strong>{{ $sekolah ? $sekolah->nama : 'Satuan Pendidikan' }}</strong>, dengan ini memberikan izin / cuti kepada:</p>
            @endif

            <table class="bio-table">
                <tr>
                    <td class="label">Nama Lengkap</td>
                    <td class="sep">:</td>
                    <td class="val">{{ $cuti->gtk?->nama }}</td>
                </tr>
                <tr>
                    <td class="label">NIP / NUPTK</td>
                    <td class="sep">:</td>
                    <td class="val">{{ $cuti->gtk?->nip ?: ($cuti->gtk?->nuptk ?: '-') }}</td>
                </tr>
                <tr>
                    <td class="label">Jabatan / Jenis PTK</td>
                    <td class="sep">:</td>
                    <td class="val">{{ $cuti->gtk?->jenis_ptk_id_str ?: 'Pendidik & Tenaga Kependidikan' }}</td>
                </tr>
                <tr>
                    <td class="label">Jenis Permohonan</td>
                    <td class="sep">:</td>
                    <td class="val">{{ $cuti->jenis }}</td>
                </tr>
                <tr>
                    <td class="label">Periode / Tanggal</td>
                    <td class="sep">:</td>
                    <td class="val">
                        {{ \Carbon\Carbon::parse($cuti->tanggal_mulai)->translatedFormat('d F Y') }} s/d {{ \Carbon\Carbon::parse($cuti->tanggal_selesai)->translatedFormat('d F Y') }} ({{ $cuti->jumlah_hari }} Hari Kerja)
                    </td>
                </tr>
                <tr>
                    <td class="label">Maksud & Keperluan</td>
                    <td class="sep">:</td>
                    <td class="val">{{ $cuti->keperluan }}</td>
                </tr>
            </table>

            @if($isDinas)
            <p>Demikian Surat Perintah Tugas ini dibuat agar yang bersangkutan dapat melaksanakan tugas dengan sebaik-baiknya serta penuh rasa tanggung jawab, dan melaporkan hasilnya setelah selesai melaksanakan tugas.</p>
            @else
            <p>Demikian Surat Keterangan Izin / Cuti ini diberikan untuk dapat dipergunakan sebagaimana mestinya, dengan ketentuan kembali aktif bekerja setelah masa izin/cuti berakhir.</p>
            @endif
        </div>

        {{-- Tanda Tangan --}}
        <div class="ttd-section">
            <div class="ttd-box">
                <p style="margin: 0;">{{ $sekolah ? $sekolah->kabupaten_kota : 'Ditetapkan' }}, {{ \Carbon\Carbon::parse($cuti->created_at)->translatedFormat('d F Y') }}</p>
                <p style="margin: 4px 0 0 0; font-weight: 600;">Kepala Sekolah,</p>
                <div class="ttd-space"></div>
                <p style="margin: 0; font-weight: 700; text-decoration: underline;">{{ $kepsek ? $kepsek->nama : 'Kepala Sekolah' }}</p>
                <p style="margin: 2px 0 0 0; font-size: 9.5pt; color: #4b5563;">NIP. {{ $kepsek ? ($kepsek->nip ?: '-') : '-' }}</p>
            </div>
        </div>
    </div>
</body>
</html>
