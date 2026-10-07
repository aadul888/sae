<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Perintah Tugas (SPT) - {{ $spt->nomor_spt }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
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
