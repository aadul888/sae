<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Keaslian Dokumen Persuratan — {{ $sekolah->nama ?? 'SMK SAE' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('img/icons/sae-icon-96x96.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('css/verifikasi-surat.css') }}?v={{ file_exists(public_path('css/verifikasi-surat.css')) ? filemtime(public_path('css/verifikasi-surat.css')) : time() }}">
</head>

<body>
    <div class="verify-wrapper">
        <div class="verify-card">
            <!-- Header Satuan Pendidikan -->
            <div class="verify-header">
                <div class="school-badge">
                    <i class="fas fa-shield-halved"></i> SISTEM VERIFIKASI RESMI SAE
                </div>
                <h1 class="school-name">{{ $sekolah->nama ?? 'SMK SAE INDONESIA' }}</h1>
                <div class="school-address">
                    NPSN: {{ $sekolah->npsn ?? '-' }} |
                    {{ $sekolah->alamat_jalan ?? ($sekolah->desa_kelurahan ?? 'Kota Bandung, Jawa Barat') }}
                </div>
            </div>

            @if ($isValid)
                <!-- Status Bar Valid -->
                <div class="status-pill valid">
                    <i class="fas fa-certificate status-icon"></i>
                    <span>DOKUMEN RESMI &amp; TERVERIFIKASI</span>
                </div>

                <div class="verify-body">
                    <!-- Ringkasan Dokumen -->
                    <div class="doc-meta-grid">
                        <div>
                            <div class="meta-item-label">Nomor Surat</div>
                            <div class="meta-item-value code">
                                {{ $suratKet->nomor_surat ?? ($suratUmum->nomor_surat ?? '-') }}
                            </div>
                        </div>
                        <div>
                            <div class="meta-item-label">Tanggal Terbit</div>
                            <div class="meta-item-value">
                                {{ date('d F Y', strtotime($suratKet->tanggal_surat ?? ($suratUmum->tanggal_surat ?? now()))) }}
                            </div>
                        </div>
                        <div>
                            <div class="meta-item-label">Jenis Dokumen</div>
                            <div class="meta-item-value">
                                {{ $suratKet ? 'Surat Keterangan Siswa Aktif' : 'Surat Dinas / Keputusan Resmi' }}
                            </div>
                        </div>
                        <div>
                            <div class="meta-item-label">Kode Verifikasi (Doc ID)</div>
                            <div class="meta-item-value code">
                                {{ $suratKet->doc_id ?? $doc_id }}
                            </div>
                        </div>
                    </div>

                    @if ($suratKet && $siswa)
                        <!-- Identitas Peserta Didik -->
                        <div class="section-title">Identitas Peserta Didik</div>
                        <table class="data-table">
                            <tr>
                                <td class="label-col">Nama Lengkap</td>
                                <td class="val-col"
                                    style="font-size: 0.95rem; font-weight: 800; color: var(--primary-dark);">
                                    {{ $siswa->nama }}
                                </td>
                            </tr>
                            <tr>
                                <td class="label-col">NISN / NIPD</td>
                                <td class="val-col" style="font-family: monospace;">
                                    {{ $siswa->nisn ?: '-' }} / {{ $siswa->nipd ?: '-' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="label-col">Tempat, Tgl Lahir</td>
                                <td class="val-col">
                                    {{ $siswa->tempat_lahir ?: '-' }},
                                    {{ $siswa->tanggal_lahir ? date('d F Y', strtotime($siswa->tanggal_lahir)) : '-' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="label-col">Kelas &amp; Jurusan</td>
                                <td class="val-col">
                                    {{ $rombelNama }} — {{ $jurusanNama }}
                                </td>
                            </tr>
                            <tr>
                                <td class="label-col">Keperluan Surat</td>
                                <td class="val-col" style="color: var(--text-dark);">
                                    {{ $suratKet->keperluan }}
                                </td>
                            </tr>
                            <tr>
                                <td class="label-col">Status Keaktifan</td>
                                <td class="val-col">
                                    <span
                                        style="display: inline-flex; align-items: center; gap: 6px; background: rgba(16,185,129,0.12); color: #059669; padding: 2px 10px; border-radius: 9999px; font-size: 0.78rem; font-weight: 700;">
                                        <i class="fas fa-circle" style="font-size: 0.5rem;"></i> Siswa Aktif Terdaftar
                                    </span>
                                </td>
                            </tr>
                        </table>
                    @elseif ($suratUmum)
                        <!-- Identitas Surat Umum -->
                        <div class="section-title">Rincian Dokumen Surat</div>
                        <table class="data-table">
                            <tr>
                                <td class="label-col">Perihal</td>
                                <td class="val-col">{{ $suratUmum->perihal }}</td>
                            </tr>
                            <tr>
                                <td class="label-col">Tujuan / Penerima</td>
                                <td class="val-col">{{ $suratUmum->tujuan_penerima ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="label-col">Instansi Asal</td>
                                <td class="val-col">{{ $suratUmum->pengirim_asal ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="label-col">Keterangan</td>
                                <td class="val-col">{{ $suratUmum->keterangan ?: '-' }}</td>
                            </tr>
                        </table>
                    @endif

                    <!-- Pejabat Penandatangan -->
                    <div class="section-title">Pejabat Penandatangan</div>
                    <table class="data-table">
                        <tr>
                            <td class="label-col">Nama Pejabat</td>
                            <td class="val-col">{{ $penandatanganNama }}</td>
                        </tr>
                        <tr>
                            <td class="label-col">NIP / NUPTK</td>
                            <td class="val-col" style="font-family: monospace;">{{ $penandatanganNip }}</td>
                        </tr>
                        <tr>
                            <td class="label-col">Jabatan</td>
                            <td class="val-col">{{ $penandatanganJabatan }}</td>
                        </tr>
                    </table>

                    <!-- Segel Keamanan Digital -->
                    <div class="security-seal">
                        <div class="seal-icon">
                            <i class="fas fa-fingerprint"></i>
                        </div>
                        <div class="seal-text">
                            <strong>Autentikasi Digital Terjamin</strong><br>
                            Dokumen ini diterbitkan secara sah melalui database administrasi Sistem Aplikasi Edukasi
                            (SAE) dan tersimpan secara permanen pada arsip digital satuan pendidikan.
                        </div>
                    </div>
                </div>
            @else
                <!-- Status Bar Invalid / Tidak Ditemukan -->
                <div class="status-pill invalid">
                    <i class="fas fa-triangle-exclamation status-icon"></i>
                    <span>DOKUMEN TIDAK DITEMUKAN / TIDAK VALID</span>
                </div>

                <div class="verify-body" style="text-align: center; padding: 40px 24px;">
                    <div
                        style="width: 64px; height: 64px; border-radius: 50%; background: rgba(239,68,68,0.12); color: var(--danger); display: inline-flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 16px;">
                        <i class="fas fa-ban"></i>
                    </div>
                    <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-dark); margin-bottom: 8px;">
                        Data Dokumen Tidak Ditemukan
                    </h3>
                    <p
                        style="font-size: 0.84rem; color: var(--text-muted); max-width: 440px; margin: 0 auto 16px; line-height: 1.5;">
                        Kode dokumen <code>{{ $doc_id }}</code> tidak terdaftar pada pangkalan data persuratan
                        sekolah. Harap pastikan kembali keaslian fisik dokumen atau hubungi pihak tata usaha sekolah.
                    </p>
                </div>
            @endif

            <!-- Footer Publik -->
            <div class="verify-footer">
                <div>&copy; {{ date('Y') }} {{ $sekolah->nama ?? 'SMK SAE' }}. Hak cipta dilindungi undang-undang.
                </div>
                <div style="margin-top: 4px;">Diverifikasi secara otomatis oleh <a href="{{ url('/') }}">Sistem
                        Aplikasi Edukasi (SAE)</a></div>
            </div>
        </div>
    </div>
</body>

</html>
