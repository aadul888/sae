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
    <link rel="stylesheet" href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
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
