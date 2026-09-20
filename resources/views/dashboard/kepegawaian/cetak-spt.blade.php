<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Perintah Tugas (SPT) - {{ $spt->nomor_spt }}</title>
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
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
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
            font-size: 13pt;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #111827;
        }
        .kop-fallback h1 {
            margin: 4px 0;
            font-size: 17pt;
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
            color: #374151;
        }
        .content-body {
            text-align: justify;
            margin-bottom: 20px;
        }
        table.table-gtk {
            width: 100%;
            border-collapse: collapse;
            margin: 14px 0 20px 0;
        }
        table.table-gtk th, table.table-gtk td {
            border: 1px solid #374151;
            padding: 8px 10px;
            font-size: 10pt;
        }
        table.table-gtk th {
            background-color: #f3f4f6;
            font-weight: 700;
            text-align: center;
        }
        .ttd-container {
            margin-top: 30px;
            display: flex;
            justify-content: flex-end;
        }
        .ttd-box {
            width: 250px;
            text-align: center;
        }
        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 999;
        }
        .btn-print {
            background: #2563eb;
            color: white;
            padding: 10px 18px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(37,99,235,0.3);
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        @media print {
            body {
                background: none;
                padding: 0;
            }
            .page-container {
                box-shadow: none;
                padding: 0;
                width: 100%;
                min-height: auto;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <a href="javascript:window.print()" class="btn-print">
            <i class="fas fa-print"></i> Cetak Dokumen
        </a>
        <a href="javascript:window.close()" class="btn-print" style="background: #6b7280;">
            <i class="fas fa-times"></i> Tutup
        </a>
    </div>

    <div class="page-container">
        <!-- KOP SURAT -->
        <div class="kop-surat">
            @if(!empty($sekolahMeta?->kop_surat_path))
                <img src="{{ asset('storage/' . $sekolahMeta->kop_surat_path) }}" alt="KOP Surat">
            @else
                <div class="kop-fallback">
                    <h2>PEMERINTAH PROVINSI / DAERAH</h2>
                    <h1>{{ $sekolah?->nama ?? 'SMK NEGERI CONTOH' }}</h1>
                    <p>{{ $sekolah?->alamat_jalan ?? 'Jl. Pendidikan No. 1' }} | NPSN: {{ $sekolah?->npsn ?? '-' }} | Email: {{ $sekolah?->email ?? '-' }}</p>
                </div>
            @endif
        </div>

        <!-- JUDUL SURAT -->
        <div class="doc-title">
            <h3>SURAT PERINTAH TUGAS</h3>
            <p class="doc-num">Nomor: {{ $spt->nomor_spt }}</p>
        </div>

        <!-- ISI SURAT -->
        <div class="content-body">
            <p>Yang bertanda tangan di bawah ini:</p>
            <table style="width: 100%; margin-bottom: 14px; border-collapse: collapse;">
                <tr>
                    <td style="width: 180px; padding: 3px 0;">Nama</td>
                    <td style="width: 15px; padding: 3px 0;">:</td>
                    <td style="font-weight: 700;">{{ $spt->pejabat_nama ?? ($kepsek?->nama ?? 'Kepala Sekolah') }}</td>
                </tr>
                <tr>
                    <td style="padding: 3px 0;">NIP</td>
                    <td style="padding: 3px 0;">:</td>
                    <td>{{ $kepsek?->nip ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding: 3px 0;">Jabatan</td>
                    <td style="padding: 3px 0;">:</td>
                    <td>{{ $spt->pejabat_jabatan ?? 'Kepala Sekolah' }}</td>
                </tr>
            </table>

            <p>Dengan ini menugaskan kepada Pendidik / Tenaga Kependidikan di bawah ini:</p>
            <table class="table-gtk">
                <thead>
                    <tr>
                        <th style="width: 35px;">No</th>
                        <th>Nama Pegawai</th>
                        <th>NIP / NUPTK</th>
                        <th>Jabatan / Unit Kerja</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($gtkList as $idx => $g)
                    <tr>
                        <td style="text-align: center;">{{ $idx + 1 }}</td>
                        <td style="font-weight: 600;">{{ $g->nama }}</td>
                        <td>NIP: {{ $g->nip ?: '-' }}<br><small style="color: #6b7280;">NUPTK: {{ $g->nuptk ?: '-' }}</small></td>
                        <td>{{ $g->jenis_ptk_id_str ?: 'Guru / Tendik' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align: center; color: #6b7280;">Tidak ada personil terdaftar</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <p>Untuk melaksanakan tugas kedinasan:</p>
            <table style="width: 100%; margin-bottom: 14px; border-collapse: collapse;">
                @if(!empty($spt->dasar_penugasan))
                <tr>
                    <td style="width: 180px; padding: 3px 0; vertical-align: top;">Dasar Penugasan</td>
                    <td style="width: 15px; padding: 3px 0; vertical-align: top;">:</td>
                    <td style="vertical-align: top;">{{ $spt->dasar_penugasan }}</td>
                </tr>
                @endif
                <tr>
                    <td style="width: 180px; padding: 3px 0; vertical-align: top;">Kegiatan</td>
                    <td style="width: 15px; padding: 3px 0; vertical-align: top;">:</td>
                    <td style="font-weight: 700; vertical-align: top;">{{ $spt->nama_kegiatan }}</td>
                </tr>
                <tr>
                    <td style="padding: 3px 0;">Tempat / Lokasi</td>
                    <td style="padding: 3px 0;">:</td>
                    <td>{{ $spt->lokasi_tujuan }}</td>
                </tr>
                <tr>
                    <td style="padding: 3px 0;">Waktu Pelaksanaan</td>
                    <td style="padding: 3px 0;">:</td>
                    <td>
                        {{ \Carbon\Carbon::parse($spt->tanggal_berangkat)->translatedFormat('d F Y') }} s/d 
                        {{ \Carbon\Carbon::parse($spt->tanggal_kembali)->translatedFormat('d F Y') }}
                        ({{ $spt->lama_hari }} hari)
                    </td>
                </tr>
                <tr>
                    <td style="padding: 3px 0;">Beban Anggaran</td>
                    <td style="padding: 3px 0;">:</td>
                    <td>{{ $spt->beban_anggaran }}</td>
                </tr>
            </table>

            <p style="margin-top: 16px;">
                Demikian Surat Perintah Tugas ini diberikan untuk dapat dilaksanakan dengan penuh tanggung jawab serta melaporkan hasil pelaksanaannya kepada Kepala Sekolah.
            </p>
        </div>

        <!-- TANDA TANGAN -->
        <div class="ttd-container">
            <div class="ttd-box">
                <p style="margin: 0 0 6px 0;">Dikeluarkan di: {{ $sekolah?->kabupaten_kota ?? 'Sekolah' }}</p>
                <p style="margin: 0 0 50px 0;">Pada tanggal: {{ \Carbon\Carbon::parse($spt->tanggal_berangkat)->translatedFormat('d F Y') }}</p>
                <p style="margin: 0; font-weight: 700; text-decoration: underline;">{{ $spt->pejabat_nama ?? ($kepsek?->nama ?? 'Kepala Sekolah') }}</p>
                <p style="margin: 0; font-size: 9.5pt;">NIP. {{ $kepsek?->nip ?? '-' }}</p>
            </div>
        </div>
    </div>
</body>
</html>
