<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Rekapitulasi Prestasi Siswa - {{ $sekolah->nama ?? 'Sekolah' }}</title>
    <link rel="stylesheet" href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
</head>
<body>
    <div class="no-print" style="background: #f1f5f9; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #cbd5e1; font-family: sans-serif; margin-bottom: 20px;">
        <div style="font-size: 0.9rem; color: #334155;">
            <strong>Pratinjau Cetak Laporan Prestasi Siswa</strong> (Ukuran A4 Landscape)
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" style="background: #2563eb; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                Cetak Laporan
            </button>
            <button onclick="window.close()" style="background: #64748b; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                Tutup
            </button>
        </div>
    </div>

    <!-- KOP SURAT RESMI SEKOLAH -->
    <div class="kop-surat">
        @if ($sekolahMeta && $sekolahMeta->logo_kiri)
            <img src="{{ asset($sekolahMeta->logo_kiri) }}" alt="Logo" class="kop-logo">
        @elseif (file_exists(public_path('images/logo.png')))
            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="kop-logo">
        @endif
        <div class="kop-text">
            <h3>PEMERINTAH DAERAH PROVINSI / KABUPATEN / KOTA</h3>
            <h2>{{ $sekolah->nama ?? 'NAMA SATUAN PENDIDIKAN' }}</h2>
            <p>{{ $sekolah->alamat_jalan ?? 'Alamat Sekolah' }}, NPSN: {{ $sekolah->npsn ?? '-' }} | Telepon: {{ $sekolah->nomor_telepon ?? '-' }}</p>
            <p>Email: {{ $sekolah->email ?? '-' }} | Website: {{ $sekolah->website ?? '-' }}</p>
        </div>
    </div>

    <!-- JUDUL LAPORAN -->
    <div class="judul-laporan">
        <h4>REKAPITULASI PRESTASI PESERTA DIDIK</h4>
        <p>
            Tahun: {{ $tahun ?: 'Semua' }} | 
            Kategori: {{ $kategori ? ucfirst($kategori) : 'Semua Kategori' }} | 
            Tingkat: {{ $tingkat ? ucwords(str_replace('_', ' ', $tingkat)) : 'Semua Tingkat' }}
        </p>
    </div>

    <!-- TABEL DATA PRESTASI -->
    <table class="tabel-data">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th>Nama Peserta Didik</th>
                <th>NISN</th>
                <th>Kategori</th>
                <th>Bidang Lomba / Event</th>
                <th>Peringkat</th>
                <th>Tingkat</th>
                <th>Tanggal</th>
                <th>Guru Pembimbing</th>
                <th>Nomor Piagam</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($list as $idx => $item)
                <tr>
                    <td style="text-align: center;">{{ $idx + 1 }}</td>
                    <td><strong>{{ $item->siswa?->nama ?: '-' }}</strong></td>
                    <td style="text-align: center;">{{ $item->siswa?->nisn ?: '-' }}</td>
                    <td style="text-align: center;">{{ ucfirst($item->kategori) }}</td>
                    <td>
                        <strong>{{ $item->bidang_lomba }}</strong>
                        <div style="font-size: 8pt; color: #444;">{{ $item->nama_event }}</div>
                    </td>
                    <td style="text-align: center; font-weight: bold;">
                        {{ strtoupper(str_replace('_', ' ', $item->peringkat)) }}
                    </td>
                    <td style="text-align: center;">
                        {{ ucwords(str_replace('_', ' ', $item->tingkat)) }}
                    </td>
                    <td style="text-align: center;">
                        {{ date('d/m/Y', strtotime($item->tanggal_prestasi)) }}
                    </td>
                    <td>
                        {{ $item->pembimbing?->nama ?: ($item->pembimbing_nama ?: '-') }}
                    </td>
                    <td style="text-align: center; font-size: 8.5pt;">
                        {{ $item->nomor_piagam ?: '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align: center; padding: 20px; font-style: italic;">
                        Tidak ada data prestasi peserta didik yang sesuai dengan kriteria filter.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- TANDA TANGAN -->
    <div class="ttd-box">
        <div class="ttd-kolom">
            <p>Mengetahui,<br>Wakasek Bidang Kesiswaan</p>
            <div style="height: 60px;"></div>
            <p><strong>(......................................................)</strong><br>NIP. -</p>
        </div>
        <div class="ttd-kolom" style="text-align: right;">
            <p>
                {{ $sekolah->kabupaten_kota ?? 'Kota' }}, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>
                Kepala Sekolah,
            </p>
            <div style="height: 60px;"></div>
            <p>
                <strong><u>{{ $kepsek->nama ?? 'Nama Kepala Sekolah' }}</u></strong><br>
                NIP. {{ $kepsek->nip ?? '-' }}
            </p>
        </div>
    </div>
</body>
</html>
