<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Surat Panggilan Ortu - {{ $siswa->nama }} - {{ $panggilan->nomor_surat }}</title>
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
        .meta-surat {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 11.5pt;
        }
        .isi-surat {
            font-size: 11.5pt;
            text-align: justify;
        }
        .tabel-agenda {
            width: 100%;
            margin: 16px 0 20px 24px;
            border-collapse: collapse;
        }
        .tabel-agenda td {
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
        <div><strong>Dokumen Resmi:</strong> Surat Panggilan Orang Tua / Wali Murid</div>
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

        <!-- Meta Surat -->
        <div class="meta-surat">
            <div>
                <table>
                    <tr><td style="width: 80px;">Nomor</td><td>: {{ $panggilan->nomor_surat }}</td></tr>
                    <tr><td>Lampiran</td><td>: -</td></tr>
                    <tr><td>Perihal</td><td>: <strong>Panggilan Orang Tua / Wali Murid</strong></td></tr>
                </table>
            </div>
            <div style="text-align: right;">
                {{ $sekolah?->kabupaten_kota ?: 'Sekolah' }}, {{ date('d F Y', strtotime($panggilan->tanggal_surat)) }}
            </div>
        </div>

        <div style="margin-bottom: 16px; font-size: 11.5pt;">
            Kepada Yth.<br>
            <strong>Bapak / Ibu Orang Tua / Wali dari:</strong><br>
            <span style="font-weight: bold; text-transform: uppercase;">{{ $siswa->nama }}</span> (Kelas: {{ $rombelNama }})<br>
            di Tempat
        </div>

        <!-- Isi Surat -->
        <div class="isi-surat">
            <p style="text-indent: 36px;">
                Dengan hormat, sehubungan dengan perkembangan kedisiplinan dan proses belajar putra/putri Bapak/Ibu di sekolah, bersama surat ini kami mengundang Bapak/Ibu untuk hadir ke sekolah pada:
            </p>

            <table class="tabel-agenda">
                <tr>
                    <td style="width: 140px;">Hari, Tanggal</td>
                    <td style="width: 10px;">:</td>
                    <td style="font-weight: bold;">{{ date('l, d F Y', strtotime($panggilan->tanggal_hadir)) }}</td>
                </tr>
                <tr>
                    <td>Pukul / Waktu</td>
                    <td>:</td>
                    <td>{{ substr($panggilan->jam_hadir, 0, 5) }} WIB s.d. Selesai</td>
                </tr>
                <tr>
                    <td>Tempat</td>
                    <td>:</td>
                    <td>{{ $panggilan->tempat }}</td>
                </tr>
                <tr>
                    <td>Menghadap</td>
                    <td>:</td>
                    <td>{{ $panggilan->menghadap_ke }}</td>
                </tr>
                <tr>
                    <td>Keperluan</td>
                    <td>:</td>
                    <td style="font-weight: bold; color: #b91c1c;">{{ $panggilan->alasan }}</td>
                </tr>
            </table>

            <p style="text-indent: 36px;">
                Mengingat pentingnya koordinasi ini demi masa depan dan pembinaan karakter putra/putri Bapak/Ibu, kami sangat mengharapkan kehadiran Bapak/Ibu tepat pada waktu yang telah ditentukan (tidak dapat diwakilkan).
            </p>

            <p style="text-indent: 36px;">
                Demikian surat pemanggilan ini kami sampaikan. Atas perhatian dan kerja sama yang baik dari Bapak/Ibu, kami ucapkan terima kasih.
            </p>
        </div>

        <!-- Tanda Tangan & QR Code -->
        <div class="ttd-box">
            <div>
                <img src="{{ $qrUri }}" alt="QR Code Verifikasi" style="width: 100px; height: 100px;">
                <div style="font-size: 8pt; color: #475569; font-family: sans-serif; margin-top: 4px;">
                    Scan untuk verifikasi dokumen<br>
                    ID: {{ $docId }}
                </div>
            </div>
            <div class="ttd-kanan">
                <div style="font-weight: bold;">Kepala Sekolah,</div>
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
