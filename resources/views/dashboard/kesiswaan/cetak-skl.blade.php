<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak SKL - {{ $siswa->nama }} - {{ $kelulusan->nomor_skl }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 20mm 20mm 20mm 20mm;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            color: #000;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            background: #fff;
        }
        .kop-surat {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            border-bottom: 3px double #000;
            padding-bottom: 12px;
            margin-bottom: 24px;
            text-align: center;
        }
        .kop-logo {
            width: 75px;
            height: 75px;
            object-fit: contain;
        }
        .kop-text h3 {
            margin: 0;
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .kop-text h2 {
            margin: 2px 0;
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .kop-text p {
            margin: 0;
            font-size: 9.5pt;
        }
        .judul-surat {
            text-align: center;
            margin-bottom: 24px;
        }
        .judul-surat h4 {
            margin: 0;
            font-size: 14pt;
            text-decoration: underline;
            text-transform: uppercase;
            font-weight: bold;
        }
        .judul-surat p {
            margin: 4px 0 0 0;
            font-size: 11pt;
        }
        .isi-surat {
            font-size: 11.5pt;
            text-align: justify;
        }
        .tabel-bio {
            width: 100%;
            margin: 16px 0 20px 24px;
            border-collapse: collapse;
        }
        .tabel-bio td {
            padding: 4px 6px;
            vertical-align: top;
            font-size: 11.5pt;
        }
        .ttd-box {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            page-break-inside: avoid;
        }
        .ttd-kanan {
            text-align: left;
            min-width: 240px;
        }
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="background: #f1f5f9; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #cbd5e1; font-family: sans-serif;">
        <div><strong>Dokumen Resmi:</strong> Surat Keterangan Lulus (SKL)</div>
        <button onclick="window.print()" style="background: #2563eb; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: bold;">
            Cetak Dokumen
        </button>
    </div>

    <div style="padding: 20px 0;">
        <!-- Kop Surat -->
        <div class="kop-surat">
            @if (!empty($sekolahMeta?->logo_path) && file_exists(public_path($sekolahMeta->logo_path)))
                <img src="{{ asset($sekolahMeta->logo_path) }}" class="kop-logo" alt="Logo">
            @else
                <img src="{{ asset('img/logo-dark.png') }}" class="kop-logo" alt="Logo">
            @endif
            <div class="kop-text">
                <h3>PEMERINTAH PROVINSI</h3>
                <h2>{{ $sekolah?->nama ?: 'SMK NEGERI 1' }}</h2>
                <p>{{ $sekolah?->alamat_jalan ?: 'Jl. Pendidikan No. 1' }}, {{ $sekolah?->desa_kelurahan ?: '' }}, {{ $sekolah?->kecamatan ?: '' }}</p>
                <p>NPSN: {{ $sekolah?->npsn ?: '-' }} | Website: {{ $sekolah?->website ?: '-' }} | Email: {{ $sekolah?->email ?: '-' }}</p>
            </div>
        </div>

        <!-- Judul -->
        <div class="judul-surat">
            <h4>SURAT KETERANGAN LULUS</h4>
            <p>Nomor: {{ $kelulusan->nomor_skl }}</p>
        </div>

        <!-- Isi Surat -->
        <div class="isi-surat">
            <p>Yang bertanda tangan di bawah ini Kepala {{ $sekolah?->nama ?: 'Sekolah' }}, menerangkan dengan sesungguhnya bahwa:</p>

            <table class="tabel-bio">
                <tr>
                    <td style="width: 220px;">Nama Lengkap</td>
                    <td style="width: 10px;">:</td>
                    <td style="font-weight: bold; text-transform: uppercase;">{{ $siswa->nama }}</td>
                </tr>
                <tr>
                    <td>Tempat, Tanggal Lahir</td>
                    <td>:</td>
                    <td>{{ $siswa->tempat_lahir ?: '-' }}, {{ $siswa->tanggal_lahir ? date('d F Y', strtotime($siswa->tanggal_lahir)) : '-' }}</td>
                </tr>
                <tr>
                    <td>Nomor Induk Siswa Nasional (NISN)</td>
                    <td>:</td>
                    <td>{{ $siswa->nisn ?: '-' }}</td>
                </tr>
                <tr>
                    <td>Nomor Induk Peserta Didik (NIPD)</td>
                    <td>:</td>
                    <td>{{ $siswa->nipd ?: '-' }}</td>
                </tr>
                <tr>
                    <td>Program / Kompetensi Keahlian</td>
                    <td>:</td>
                    <td>{{ $jurusanNama }}</td>
                </tr>
                <tr>
                    <td>Nomor Peserta Ujian</td>
                    <td>:</td>
                    <td>{{ $kelulusan->nomor_peserta_ujian ?: '-' }}</td>
                </tr>
            </table>

            <p style="text-indent: 36px;">
                Berdasarkan kriteria kelulusan satuan pendidikan dan hasil rapat dewan guru pada tanggal {{ date('d F Y', strtotime($kelulusan->tanggal_lulus ?: date('Y-m-d'))) }}, yang bersangkutan dinyatakan:
            </p>

            <div style="text-align: center; margin: 24px 0;">
                <span style="display: inline-block; padding: 10px 40px; border: 2px solid #000; font-size: 16pt; font-weight: bold; text-transform: uppercase; letter-spacing: 2px;">
                    {{ $kelulusan->status_kelulusan === 'lulus' ? 'L U L U S' : 'TIDAK LULUS' }}
                </span>
            </div>

            <p style="text-indent: 36px;">
                Surat Keterangan Lulus ini berlaku sementara sampai dengan diterbitkannya Ijazah asli yang sah, dan dapat dipergunakan sebagaimana mestinya untuk keperluan pendaftaran perguruan tinggi atau melamar pekerjaan.
            </p>
        </div>

        <!-- Tanda Tangan & QR Code -->
        <div class="ttd-box">
            <div>
                <img src="{{ $qrUri }}" alt="QR Code Verifikasi" style="width: 110px; height: 110px;">
                <div style="font-size: 8pt; color: #475569; font-family: sans-serif; margin-top: 4px;">
                    Scan untuk verifikasi keaslian dokumen<br>
                    ID: {{ $docId }}
                </div>
            </div>
            <div class="ttd-kanan">
                <div>Ditetapkan di: {{ $sekolah?->kabupaten_kota ?: 'Sekolah' }}</div>
                <div>Pada tanggal: {{ date('d F Y', strtotime($kelulusan->tanggal_lulus ?: date('Y-m-d'))) }}</div>
                <div style="margin-top: 6px; font-weight: bold;">Kepala Sekolah,</div>
                <div style="height: 70px;"></div>
                <div style="font-weight: bold; text-decoration: underline; text-transform: uppercase;">
                    {{ $kepsek?->nama ?: 'KEPALA SEKOLAH' }}
                </div>
                <div>NIP. {{ $kepsek?->nip ?: '-' }}</div>
            </div>
        </div>
    </div>
</body>
</html>
