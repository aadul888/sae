<!DOCTYPE html>
<html lang="id">
@php
    $suratKet = $suratKet ?? ($surat ?? null);
    $siswa = $siswa ?? ($suratKet?->pesertaDidik ?? null);
    $orientasi = $orientasi ?? 'portrait';
    $rombelNama = $rombelNama ?? 'Kelas Binaan';
    $jurusanNama = $jurusanNama ?? 'Semua Keahlian';
    $tahunAjaran = $tahunAjaran ?? date('Y') . '/' . (date('Y') + 1);
    $qrUri = $qrUri ?? null;
    $jenisSurat = $suratKet->jenis_surat ?? 'siswa_aktif';
    $judulSurat = match($jenisSurat) {
        'kelakuan_baik' => 'SURAT KETERANGAN BERKELAKUAN BAIK',
        'panggilan_ortu' => 'SURAT PANGGILAN ORANG TUA / WALI',
        'rekomendasi' => 'SURAT REKOMENDASI SISWA',
        default => 'SURAT KETERANGAN SISWA AKTIF',
    };
@endphp
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $judulSurat }} — {{ $siswa->nama }} ({{ $suratKet->nomor_surat }})</title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
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
            <i class="fas fa-print"></i> Cetak Surat Keterangan (Print / PDF)
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

        {{-- Header Judul Surat --}}
        <div class="doc-header">
            <div class="doc-title-main">{{ $judulSurat }}</div>
            <div class="doc-subtitle">Nomor: {{ $suratKet->nomor_surat }}</div>
        </div>

        {{-- Naskah Surat Sesuai Jenis Surat --}}
        @if ($jenisSurat === 'panggilan_ortu')
            <div class="surat-body">
                <div style="margin-bottom: 16px; text-indent: 0; line-height: 1.5;">
                    Kepada Yth.<br>
                    <strong>Bapak / Ibu Orang Tua / Wali dari:</strong><br>
                    <span style="font-size: 1rem; font-weight: 800; color: #1e3a8a;">{{ $siswa->nama }}</span> (NISN: {{ $siswa->nisn ?: '-' }} &bull; Kelas: {{ $rombelNama ?: 'Binaan' }})<br>
                    Di Tempat
                </div>

                <p>
                    Dengan hormat,<br>
                    Sehubungan dengan perlunya koordinasi, konsultasi, serta pembinaan bersama antara pihak sekolah dan orang tua/wali demi perkembangan proses belajar dan kedisiplinan putra/putri Bapak/Ibu di sekolah, bersama surat ini kami mengundang kehadiran Bapak/Ibu pada:
                </p>

                <table class="table-bio" style="margin-left: 14px;">
                    <tr>
                        <td class="label">Hari / Tanggal</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $suratKet->tanggal_agenda ? \Carbon\Carbon::parse($suratKet->tanggal_agenda)->translatedFormat('l, d F Y') : \Carbon\Carbon::parse($suratKet->tanggal_surat)->translatedFormat('l, d F Y') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Waktu / Pukul</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $suratKet->waktu_agenda ?: '08:00 WIB s/d Selesai' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Tempat</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $suratKet->tempat_agenda ?: 'Ruang Bimbingan Konseling (BK) / Tata Usaha' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Menghadap Kepada</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $suratKet->menghadap_agenda ?: 'Guru BK / Wali Kelas / Waka Kesiswaan' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Perihal / Keperluan</td>
                        <td class="colon">:</td>
                        <td class="val"><strong>{{ $suratKet->keperluan }}</strong></td>
                    </tr>
                    @if (!empty($suratKet->catatan_khusus))
                    <tr>
                        <td class="label">Catatan Khusus</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $suratKet->catatan_khusus }}</td>
                    </tr>
                    @endif
                </table>

                <p>
                    Mengingat pentingnya agenda pembinaan dan koordinasi tersebut, kami sangat mengharapkan kehadiran Bapak/Ibu Orang Tua / Wali tepat pada waktu yang telah ditentukan (tidak dapat diwakilkan).
                </p>

                <p>
                    Demikian surat panggilan ini kami sampaikan. Atas perhatian, kerja sama, dan kehadiran Bapak/Ibu, kami ucapkan terima kasih.
                </p>
            </div>
        @elseif ($jenisSurat === 'kelakuan_baik')
            <div class="surat-body">
                <p>
                    Yang bertanda tangan di bawah ini, Kepala {{ $sekolah->nama ?? 'SMK NEGERI 1 PAGELARAN' }}, Kabupaten {{ $sekolah->kabupaten_kota ?? 'Pandeglang' }}, Provinsi {{ $sekolah->provinsi ?? 'Banten' }}, menerangkan bahwa:
                </p>

                <table class="table-bio">
                    <tr>
                        <td class="label">Nama Lengkap</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $siswa->nama }}</td>
                    </tr>
                    <tr>
                        <td class="label">NISN / NIPD</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $siswa->nisn ?: '—' }} / {{ $siswa->nipd ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Tempat, Tanggal Lahir</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $siswa->tempat_lahir ?: '—' }}, {{ $siswa->tanggal_lahir ? \Carbon\Carbon::parse($siswa->tanggal_lahir)->translatedFormat('d F Y') : '—' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Jenis Kelamin</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $siswa->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Tingkat / Rombel</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $rombelNama ?: 'Kelas X / XI / XII' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Program / Kompetensi Keahlian</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $jurusanNama ?: 'Semua Jurusan' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Nama Orang Tua / Wali</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $siswa->nama_ayah ?: ($siswa->nama_ibu ?: ($siswa->nama_wali ?: '—')) }}</td>
                    </tr>
                    <tr>
                        <td class="label">Alamat Tinggal Siswa</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $siswa->alamat_jalan ?: 'Sesuai Data Pokok Pendidikan' }}</td>
                    </tr>
                </table>

                <p>
                    Adalah benar yang bersangkutan adalah peserta didik {{ $sekolah->nama ?? 'SMK NEGERI 1 PAGELARAN' }}, dan berdasarkan catatan resmi kesiswaan, tata tertib, dan bimbingan konseling di satuan pendidikan kami, yang bersangkutan selama menempuh pendidikan senantiasa <strong>BERKELAKUAN BAIK</strong>, berakhlak mulia, tidak pernah terlibat tindak pidana, penyalahgunaan narkotika/zat adiktif, tawuran antarpelajar, serta tidak sedang menjalani sanksi pelanggaran tata tertib sekolah.
                </p>

                <p>
                    Surat keterangan ini kami berikan kepada yang bersangkutan untuk keperluan: <strong>{{ $suratKet->keperluan }}</strong>.
                </p>

                <p>
                    Demikian surat keterangan ini kami buat dengan sebenar-benarnya agar dapat dipergunakan sebagaimana mestinya oleh pihak yang berkepentingan.
                </p>
            </div>
        @elseif ($jenisSurat === 'rekomendasi')
            <div class="surat-body">
                <p>
                    Yang bertanda tangan di bawah ini, Kepala {{ $sekolah->nama ?? 'SMK NEGERI 1 PAGELARAN' }}, Kabupaten {{ $sekolah->kabupaten_kota ?? 'Pandeglang' }}, Provinsi {{ $sekolah->provinsi ?? 'Banten' }}, memberikan rekomendasi resmi kepada:
                </p>

                <table class="table-bio">
                    <tr>
                        <td class="label">Nama Lengkap</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $siswa->nama }}</td>
                    </tr>
                    <tr>
                        <td class="label">NISN / NIPD</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $siswa->nisn ?: '—' }} / {{ $siswa->nipd ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Tempat, Tanggal Lahir</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $siswa->tempat_lahir ?: '—' }}, {{ $siswa->tanggal_lahir ? \Carbon\Carbon::parse($siswa->tanggal_lahir)->translatedFormat('d F Y') : '—' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Tingkat / Rombel</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $rombelNama ?: 'Kelas X / XI / XII' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Program / Kompetensi Keahlian</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $jurusanNama ?: 'Semua Jurusan' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Alamat Tinggal Siswa</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $siswa->alamat_jalan ?: 'Sesuai Data Pokok Pendidikan' }}</td>
                    </tr>
                </table>

                <p>
                    Berdasarkan pertimbangan rekam jejak akademik, kedisiplinan, minat bakat, serta integritas yang bersangkutan selama belajar di sekolah kami, dengan ini pihak sekolah memberikan <strong>REKOMENDASI PENUH</strong> kepada peserta didik tersebut di atas untuk keperluan: <strong>{{ $suratKet->keperluan }}</strong>.
                </p>

                <p>
                    Demikian surat rekomendasi ini kami terbitkan dengan penuh tanggung jawab agar dapat dipergunakan sebagaimana mestinya.
                </p>
            </div>
        @else
            {{-- Default: Siswa Aktif --}}
            <div class="surat-body">
                <p>
                    Yang bertanda tangan di bawah ini, Kepala {{ $sekolah->nama ?? 'SMK NEGERI 1 PAGELARAN' }}, Kabupaten {{ $sekolah->kabupaten_kota ?? 'Pandeglang' }}, Provinsi {{ $sekolah->provinsi ?? 'Banten' }}, menerangkan bahwa:
                </p>

                <table class="table-bio">
                    <tr>
                        <td class="label">Nama Lengkap</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $siswa->nama }}</td>
                    </tr>
                    <tr>
                        <td class="label">NISN / NIPD</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $siswa->nisn ?: '—' }} / {{ $siswa->nipd ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Tempat, Tanggal Lahir</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $siswa->tempat_lahir ?: '—' }}, {{ $siswa->tanggal_lahir ? \Carbon\Carbon::parse($siswa->tanggal_lahir)->translatedFormat('d F Y') : '—' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Jenis Kelamin</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $siswa->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Tingkat / Rombel</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $rombelNama ?: 'Kelas X / XI / XII' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Program / Kompetensi Keahlian</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $jurusanNama ?: 'Semua Jurusan' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Nama Orang Tua / Wali</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $siswa->nama_ayah ?: ($siswa->nama_ibu ?: ($siswa->nama_wali ?: '—')) }}</td>
                    </tr>
                    <tr>
                        <td class="label">Alamat Tinggal Siswa</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $siswa->alamat_jalan ?: 'Sesuai Data Pokok Pendidikan' }}</td>
                    </tr>
                </table>

                <p>
                    Adalah benar yang bersangkutan tercatat sebagai <strong>Peserta Didik Aktif</strong> pada {{ $sekolah->nama ?? 'SMK NEGERI 1 PAGELARAN' }} Tahun Ajaran {{ $tahunAjaran }} dan senantiasa menaati seluruh tata tertib yang berlaku di satuan pendidikan.
                </p>

                <p>
                    Surat keterangan ini diberikan kepada yang bersangkutan untuk keperluan: <strong>{{ $suratKet->keperluan }}</strong>.
                </p>

                <p>
                    Demikian surat keterangan ini kami terbitkan dengan sebenar-benarnya untuk dapat dipergunakan sebagaimana mestinya oleh pihak yang berkepentingan.
                </p>
            </div>
        @endif

        {{-- Tanda Tangan Kepala Sekolah --}}
        <div class="signature-container">
            <div class="sig-box">
                <div class="sig-date">
                    {{ $sekolah->kabupaten_kota ? ucwords(strtolower($sekolah->kabupaten_kota)) : 'Pagelaran' }},
                    {{ \Carbon\Carbon::parse($suratKet->tanggal_surat)->translatedFormat('d F Y') }}
                </div>
                <div class="sig-title">
                    Kepala Satuan Pendidikan,
                </div>
                <div class="sig-name">{{ $kepsek?->nama ?: ($sekolah->nama_kepsek ?? 'Drs. H. Mulyadi, M.Pd.') }}</div>
                <div class="sig-nip">NIP: {{ $kepsek?->nip ?: ($sekolah->nip_kepsek ?? '—') }}</div>
            </div>
        </div>

        {{-- Footer Keabsahan & QR Code Verifikasi --}}
        <div class="doc-footer">
            <div class="qr-badge">
                <img src="{{ $qrUri }}" alt="QR Code Verifikasi">
                <div>
                    <strong>Dokumen Sah Resmi — Sistem Aplikasi Edukasi (SAE)</strong><br>
                    ID Dokumen: <code>{{ $suratKet->doc_id }}</code> &bull; Pindai QR Code untuk verifikasi keaslian dokumen digital.
                </div>
            </div>
            <div style="text-align: right;">
                Halaman 1 / 1<br>
                Format Kertas: A4 {{ ucfirst($orientasi) }}
            </div>
        </div>
    </div>

</body>

</html>
