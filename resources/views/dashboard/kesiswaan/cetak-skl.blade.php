<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak SKL - {{ $siswa->nama }} - {{ $kelulusan->nomor_skl }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
</head>
<body>
    <div class="no-print-bar">
        <button type="button" class="btn-action btn-back" onclick="if(window.history.length>1){window.history.back();}else{window.close();}">
            <i class="fas fa-arrow-left"></i> Kembali
        </button>
        <button type="button" class="btn-action btn-print" onclick="window.SAE_Print ? window.SAE_Print() : window.print()">
            <i class="fas fa-print"></i> Cetak SKL (Print / PDF)
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
    <script src="{{ asset('js/cetak.js') }}?v={{ file_exists(public_path('js/cetak.js')) ? filemtime(public_path('js/cetak.js')) : time() }}"></script>
</body>
</html>
