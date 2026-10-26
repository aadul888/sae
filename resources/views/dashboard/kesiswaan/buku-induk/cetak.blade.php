<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Induk — {{ $pd->nama }}</title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
</head>

<body>

    {{-- Floating Bar --}}
    <div class="no-print-bar">
        <a href="{{ route('dashboard.kesiswaan.buku-induk.index') }}" class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>

        <div class="orientation-switch">
            <a href="{{ request()->fullUrlWithQuery(['orientasi' => 'portrait']) }}"
                class="btn-orientasi {{ ($orientasi ?? 'portrait') === 'portrait' ? 'active' : '' }}">
                <i class="fas fa-file"></i> Portrait
            </a>
            <a href="{{ request()->fullUrlWithQuery(['orientasi' => 'landscape']) }}"
                class="btn-orientasi {{ ($orientasi ?? 'portrait') === 'landscape' ? 'active' : '' }}">
                <i class="fas fa-file-invoice"></i> Landscape
            </a>
        </div>

        <button onclick="window.SAE_Print ? window.SAE_Print() : window.print()" class="btn-action btn-print">
            <i class="fas fa-print"></i> Cetak Buku Induk (Print / PDF)
        </button>
    </div>

    <div class="cetak-page">
        <div class="watermark-sae">
            <img src="{{ asset('img/logo-icon.png') }}" alt="Watermark SAE">
        </div>

        @include('partials.kop-surat')

        <div class="doc-header">
            <div class="doc-title-main">LEMBAR BUKU INDUK PESERTA DIDIK</div>
            <div class="doc-subtitle">
                TAHUN PELAJARAN {{ date('Y') }}/{{ date('Y') + 1 }} &bull; NOMOR INDUK:
                {{ $klaper?->nomor_induk ?: ($pd->nipd ?: '—') }}
            </div>
        </div>

        {{-- Identitas Ringkas + Foto --}}
        <table class="table-data" style="margin-bottom: 10px;">
            <tbody>
                <tr>
                    <td style="width: 130px; font-weight: 700; background: #f3f4f6;">Nama Peserta Didik</td>
                    <td style="font-weight: 800; text-transform: uppercase;">{{ $pd->nama }}</td>
                    <td rowspan="6" style="width: 105px; text-align: center; vertical-align: middle;">
                        @if (!empty($meta?->foto_url))
                            <img src="{{ $meta->foto_url }}" alt="Pasfoto {{ $pd->nama }}"
                                style="width: 84px; height: 112px; object-fit: cover; border: 1px solid #9ca3af;">
                        @else
                            <div
                                style="width: 84px; height: 112px; border: 1px dashed #9ca3af; margin: 0 auto; display: flex; align-items: center; justify-content: center; font-size: var(--text-2xs, 0.6875rem); color: #9ca3af;">
                                FOTO 3×4
                            </div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: 700; background: #f3f4f6;">NISN / NIPD</td>
                    <td>{{ $pd->nisn ?: '—' }} / {{ $klaper?->nomor_induk ?: ($pd->nipd ?: '—') }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; background: #f3f4f6;">No. Klaper</td>
                    <td>{{ $klaper?->nomor_klaper ?: '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; background: #f3f4f6;">Kelas / Rombel</td>
                    <td>{{ $rombel?->rombel_nama ?: '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; background: #f3f4f6;">Angkatan Masuk</td>
                    <td>{{ $klaper?->tahun_masuk ?: '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; background: #f3f4f6;">Status Kependidikan</td>
                    <td style="text-transform: uppercase;">
                        {{ str_replace('_', ' ', $klaper?->status_klaper ?: 'aktif') }}</td>
                </tr>
            </tbody>
        </table>

        {{-- A. Keterangan Identitas Pokok --}}
        <div class="section-subhead"><i class="fas fa-id-card"></i> A. Keterangan Identitas Pokok</div>
        <table class="table-data" style="margin-bottom: 10px;">
            <tbody>
                <tr>
                    <td style="width: 200px; font-weight: 700; background: #f9fafb;">Nama Lengkap</td>
                    <td>{{ $identitas->nama ?: $pd->nama }}</td>
                    <td style="width: 180px; font-weight: 700; background: #f9fafb;">Jenis Kelamin</td>
                    <td style="width: 140px;">
                        {{ ($identitas->jenis_kelamin ?: $pd->jenis_kelamin) === 'L' ? 'Laki-laki' : 'Perempuan' }}
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: 700; background: #f9fafb;">NIK</td>
                    <td>{{ $identitas->nik ?: ($pd->nik ?: '—') }}</td>
                    <td style="font-weight: 700; background: #f9fafb;">No. Kartu Keluarga</td>
                    <td>{{ $identitas->no_kk ?: '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; background: #f9fafb;">No. Registrasi Akta Lahir</td>
                    <td>{{ $identitas->no_registrasi_akta_lahir ?: '—' }}</td>
                    <td style="font-weight: 700; background: #f9fafb;">Kewarganegaraan</td>
                    <td>{{ $identitas->kewarganegaraan ?: 'WNI' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; background: #f9fafb;">Tempat, Tanggal Lahir</td>
                    <td>
                        {{ $identitas->tempat_lahir ?: ($pd->tempat_lahir ?: '—') }},
                        {{ $identitas->tanggal_lahir
                            ? \Carbon\Carbon::parse($identitas->tanggal_lahir)->translatedFormat('d F Y')
                            : ($pd->tanggal_lahir
                                ? \Carbon\Carbon::parse($pd->tanggal_lahir)->translatedFormat('d F Y')
                                : '—') }}
                    </td>
                    <td style="font-weight: 700; background: #f9fafb;">Agama</td>
                    <td>{{ $identitas->agama_id_str ?: ($pd->agama_id_str ?: '—') }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; background: #f9fafb;">Anak Keberapa</td>
                    <td>{{ $identitas->anak_keberapa ?: '—' }}</td>
                    <td style="font-weight: 700; background: #f9fafb;">Jumlah Saudara Kandung</td>
                    <td>{{ $identitas->jumlah_saudara_kandung ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; background: #f9fafb;">Tinggi / Berat Badan</td>
                    <td>{{ $identitas->tinggi_badan ?: '—' }} cm / {{ $identitas->berat_badan ?: '—' }} kg</td>
                    <td style="font-weight: 700; background: #f9fafb;">Golongan Darah</td>
                    <td>{{ $identitas->golongan_darah ?: '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; background: #f9fafb;">Kelainan Jasmani</td>
                    <td>{{ $identitas->kelainan_jasmani ?: 'Tidak ada' }}</td>
                    <td style="font-weight: 700; background: #f9fafb;">Bahasa Sehari-hari</td>
                    <td>{{ $identitas->bahasa_sehari_hari ?: '—' }}</td>
                </tr>
            </tbody>
        </table>

        {{-- B. Tempat Tinggal --}}
        <div class="section-subhead"><i class="fas fa-house"></i> B. Keterangan Tempat Tinggal</div>
        <table class="table-data" style="margin-bottom: 10px;">
            <tbody>
                <tr>
                    <td style="width: 200px; font-weight: 700; background: #f9fafb;">Alamat Jalan / Dusun</td>
                    <td colspan="3">{{ $identitas->alamat_jalan ?: ($pd->alamat_jalan ?: '—') }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; background: #f9fafb;">RT / RW</td>
                    <td style="width: 180px;">{{ $identitas->rt ?: '—' }} / {{ $identitas->rw ?: '—' }}</td>
                    <td style="width: 160px; font-weight: 700; background: #f9fafb;">Kode Pos</td>
                    <td>{{ $identitas->kode_pos ?: '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; background: #f9fafb;">Desa / Kelurahan</td>
                    <td>{{ $identitas->desa_kelurahan ?: '—' }}</td>
                    <td style="font-weight: 700; background: #f9fafb;">Kecamatan</td>
                    <td>{{ $identitas->kecamatan ?: '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; background: #f9fafb;">Kabupaten / Kota</td>
                    <td>{{ $identitas->kabupaten_kota ?: '—' }}</td>
                    <td style="font-weight: 700; background: #f9fafb;">Provinsi</td>
                    <td>{{ $identitas->provinsi ?: '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; background: #f9fafb;">Status Tempat Tinggal</td>
                    <td>{{ $identitas->tempat_tinggal_str ?: '—' }}</td>
                    <td style="font-weight: 700; background: #f9fafb;">Moda Transportasi</td>
                    <td>{{ $identitas->transportasi_str ?: '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; background: #f9fafb;">No. HP / WhatsApp</td>
                    <td>{{ $identitas->nomor_telepon_seluler ?: ($pd->nomor_telepon_seluler ?: '—') }}</td>
                    <td style="font-weight: 700; background: #f9fafb;">Email</td>
                    <td>{{ $identitas->email ?: ($pd->email ?: '—') }}</td>
                </tr>
            </tbody>
        </table>

        {{-- C. Data Ayah / Ibu / Wali --}}
        <div class="section-subhead"><i class="fas fa-people-roof"></i> C. Keterangan Data Ayah, Ibu Kandung &amp; Wali
        </div>
        <table class="table-data" style="margin-bottom: 10px;">
            <thead>
                <tr>
                    <th style="width: 170px;">Keterangan</th>
                    <th>Ayah Kandung</th>
                    <th>Ibu Kandung</th>
                    <th>Wali</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="font-weight: 700; background: #f9fafb;">Nama Lengkap</td>
                    <td>{{ $identitas->nama_ayah ?: ($pd->nama_ayah ?: '—') }}</td>
                    <td>{{ $identitas->nama_ibu ?: ($pd->nama_ibu ?: '—') }}</td>
                    <td>{{ $identitas->nama_wali ?: ($pd->nama_wali ?: '—') }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; background: #f9fafb;">NIK</td>
                    <td>{{ $identitas->nik_ayah ?: '—' }}</td>
                    <td>{{ $identitas->nik_ibu ?: '—' }}</td>
                    <td>{{ $identitas->nik_wali ?: '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; background: #f9fafb;">Tahun Lahir</td>
                    <td>{{ $identitas->tahun_lahir_ayah ?: '—' }}</td>
                    <td>{{ $identitas->tahun_lahir_ibu ?: '—' }}</td>
                    <td>{{ $identitas->tahun_lahir_wali ?: '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; background: #f9fafb;">Pendidikan Terakhir</td>
                    <td>{{ $identitas->pendidikan_ayah_str ?: '—' }}</td>
                    <td>{{ $identitas->pendidikan_ibu_str ?: '—' }}</td>
                    <td>{{ $identitas->pendidikan_wali_str ?: '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; background: #f9fafb;">Pekerjaan</td>
                    <td>{{ $identitas->pekerjaan_ayah_str ?: ($pd->pekerjaan_ayah_id_str ?: '—') }}</td>
                    <td>{{ $identitas->pekerjaan_ibu_str ?: ($pd->pekerjaan_ibu_id_str ?: '—') }}</td>
                    <td>{{ $identitas->pekerjaan_wali_str ?: ($pd->pekerjaan_wali_id_str ?: '—') }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; background: #f9fafb;">Penghasilan per Bulan</td>
                    <td>{{ $identitas->penghasilan_ayah_str ?: '—' }}</td>
                    <td>{{ $identitas->penghasilan_ibu_str ?: '—' }}</td>
                    <td>{{ $identitas->penghasilan_wali_str ?: '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; background: #f9fafb;">Status / Hubungan</td>
                    <td>{{ ($identitas->status_hidup_ayah ?? '1') === '1' ? 'Masih Hidup' : 'Meninggal' }}</td>
                    <td>{{ ($identitas->status_hidup_ibu ?? '1') === '1' ? 'Masih Hidup' : 'Meninggal' }}</td>
                    <td>{{ $identitas->hubungan_wali ?: (!empty($identitas->nama_wali) ? 'Wali' : '—') }}</td>
                </tr>
            </tbody>
        </table>

        {{-- D. Registrasi Masuk --}}
        <div class="section-subhead"><i class="fas fa-clipboard-check"></i> D. Keterangan Registrasi Masuk</div>
        <table class="table-data" style="margin-bottom: 14px;">
            <tbody>
                <tr>
                    <td style="width: 200px; font-weight: 700; background: #f9fafb;">Sekolah Asal</td>
                    <td>{{ $identitas->sekolah_asal ?: ($pd->sekolah_asal ?: '—') }}</td>
                    <td style="width: 160px; font-weight: 700; background: #f9fafb;">Tanggal Masuk</td>
                    <td style="width: 160px;">
                        {{ $identitas->tanggal_masuk_sekolah
                            ? \Carbon\Carbon::parse($identitas->tanggal_masuk_sekolah)->translatedFormat('d F Y')
                            : ($pd->tanggal_masuk_sekolah
                                ? \Carbon\Carbon::parse($pd->tanggal_masuk_sekolah)->translatedFormat('d F Y')
                                : '—') }}
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: 700; background: #f9fafb;">Jenis Pendaftaran</td>
                    <td>{{ $identitas->jenis_pendaftaran_str ?: '—' }}</td>
                    <td style="font-weight: 700; background: #f9fafb;">Tahun Masuk / Angkatan</td>
                    <td>{{ $klaper?->tahun_masuk ?: '—' }}</td>
                </tr>
                @if (!empty($identitas->catatan_kesiswaan))
                    <tr>
                        <td style="font-weight: 700; background: #f9fafb;">Catatan Kesiswaan</td>
                        <td colspan="3">{{ $identitas->catatan_kesiswaan }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        {{-- Tanda Tangan --}}
        <div
            style="display: flex; justify-content: space-between; align-items: flex-start; margin-top: 18px; gap: 20px;">
            <div style="text-align: center; font-size: var(--text-xs, 0.75rem); min-width: 210px;">
                @if (!empty($qrUri))
                    <img src="{{ $qrUri }}" alt="QR Verifikasi"
                        style="width: 78px; height: 78px; display: block; margin: 0 auto 6px auto;">
                @endif
                <div style="color: #6b7280;">Dokumen terverifikasi sistem SAE</div>
                <div style="font-family: monospace; font-size: var(--text-2xs, 0.6875rem); color: #9ca3af;">
                    {{ $klaper?->nomor_induk ?: $pd->nisn }}
                </div>
            </div>

            <div style="text-align: center; font-size: var(--text-sm, 0.8125rem); min-width: 240px;">
                <div>Pagelaran, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</div>
                <div style="margin-top: 2px;">Petugas Buku Induk / Tata Usaha</div>
                <div style="height: 58px;"></div>
                <div style="font-weight: 800; text-decoration: underline;">
                    {{ $petugasKesiswaan->nama ?? '................................' }}
                </div>
                <div style="font-size: var(--text-xs, 0.75rem); color: #4b5563;">
                    NIP. {{ $petugasKesiswaan->nip ?? '................................' }}
                </div>
            </div>

            <div style="text-align: center; font-size: var(--text-sm, 0.8125rem); min-width: 240px;">
                <div>Mengetahui,</div>
                <div style="margin-top: 2px;">Kepala Sekolah</div>
                <div style="height: 58px;"></div>
                <div style="font-weight: 800; text-decoration: underline;">
                    {{ $kepsek->nama ?? '................................' }}
                </div>
                <div style="font-size: var(--text-xs, 0.75rem); color: #4b5563;">
                    NIP. {{ $kepsek->nip ?? '................................' }}
                </div>
            </div>
        </div>
    </div>
</body>

</html>
