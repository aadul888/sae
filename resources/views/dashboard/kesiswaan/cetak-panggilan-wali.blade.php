<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Surat Panggilan Ortu - {{ $siswa->nama }} - {{ $panggilan->nomor_surat }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
</head>

<body>
    <div class="no-print-bar">
        <button type="button" class="btn-action btn-back"
            onclick="if(window.history.length>1){window.history.back();}else{window.close();}">
            <i class="fas fa-arrow-left"></i> Kembali
        </button>
        <button type="button" class="btn-action btn-print"
            onclick="window.SAE_Print ? window.SAE_Print() : window.print()">
            <i class="fas fa-print"></i> Cetak Dokumen (Print / PDF)
        </button>
    </div>

    <div style="padding: 20px 0;">
        <!-- Kop Surat Resmi -->
        @include('partials.kop-surat')

        <!-- Meta Surat -->
        <div class="meta-surat">
            <div>
                <table>
                    <tr>
                        <td style="width: 80px;">Nomor</td>
                        <td>: {{ $panggilan->nomor_surat }}</td>
                    </tr>
                    <tr>
                        <td>Lampiran</td>
                        <td>: -</td>
                    </tr>
                    <tr>
                        <td>Perihal</td>
                        <td>: <strong>Panggilan Orang Tua / Wali Murid</strong></td>
                    </tr>
                </table>
            </div>
            <div style="text-align: right;">
                {{ $sekolah?->kabupaten_kota ?: 'Sekolah' }},
                {{ date('d F Y', strtotime($panggilan->tanggal_surat)) }}
            </div>
        </div>

        <div style="margin-bottom: 16px; font-size: 11.5pt;">
            Kepada Yth.<br>
            <strong>Bapak / Ibu Orang Tua / Wali dari:</strong><br>
            <span style="font-weight: bold; text-transform: uppercase;">{{ $siswa->nama }}</span> (Kelas:
            {{ $rombelNama }})<br>
            di Tempat
        </div>

        <!-- Isi Surat -->
        <div class="isi-surat">
            <p style="text-indent: 36px;">
                Dengan hormat, sehubungan dengan perkembangan kedisiplinan dan proses belajar putra/putri Bapak/Ibu di
                sekolah, bersama surat ini kami mengundang Bapak/Ibu untuk hadir ke sekolah pada:
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
                Mengingat pentingnya koordinasi ini demi masa depan dan pembinaan karakter putra/putri Bapak/Ibu, kami
                sangat mengharapkan kehadiran Bapak/Ibu tepat pada waktu yang telah ditentukan (tidak dapat diwakilkan).
            </p>

            <p style="text-indent: 36px;">
                Demikian surat pemanggilan ini kami sampaikan. Atas perhatian dan kerja sama yang baik dari Bapak/Ibu,
                kami ucapkan terima kasih.
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
    <script
        src="{{ asset('js/cetak.js') }}?v={{ file_exists(public_path('js/cetak.js')) ? filemtime(public_path('js/cetak.js')) : time() }}">
    </script>
</body>

</html>
