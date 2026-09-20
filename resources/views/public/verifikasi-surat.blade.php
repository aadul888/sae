<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Keaslian Dokumen Persuratan — {{ $sekolah->nama ?? 'SMK SAE' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('img/icons/sae-icon-96x96.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #3730a3;
            --success: #10b981;
            --success-dark: #059669;
            --danger: #ef4444;
            --bg-page: #f8fafc;
            --card-bg: #ffffff;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-page);
            color: var(--text-dark);
            line-height: 1.5;
            padding: 24px 16px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .verify-wrapper {
            width: 100%;
            max-width: 640px;
        }

        .verify-card {
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.08), 0 4px 10px -2px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--border-color);
            overflow: hidden;
            position: relative;
        }

        .verify-header {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
            color: #ffffff;
            padding: 28px 24px 24px;
            text-align: center;
            position: relative;
        }

        .school-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(8px);
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 0.76rem;
            font-weight: 600;
            letter-spacing: 0.03em;
            margin-bottom: 12px;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .school-name {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -0.01em;
            margin-bottom: 4px;
        }

        .school-address {
            font-size: 0.78rem;
            color: rgba(255, 255, 255, 0.75);
            max-width: 480px;
            margin: 0 auto;
        }

        .status-pill {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 20px;
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 0.01em;
        }

        .status-pill.valid {
            background: rgba(16, 185, 129, 0.12);
            color: var(--success-dark);
            border-bottom: 1px solid rgba(16, 185, 129, 0.2);
        }

        .status-pill.invalid {
            background: rgba(239, 68, 68, 0.12);
            color: var(--danger);
            border-bottom: 1px solid rgba(239, 68, 68, 0.2);
        }

        .status-icon {
            font-size: 1.3rem;
        }

        .verify-body {
            padding: 24px;
        }

        .section-title {
            font-size: 0.76rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title::after {
            content: "";
            flex: 1;
            height: 1px;
            background: var(--border-color);
        }

        .doc-meta-grid {
            background: var(--bg-page);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 14px 16px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        @media (max-width: 500px) {
            .doc-meta-grid {
                grid-template-columns: 1fr;
            }
        }

        .meta-item-label {
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .meta-item-value {
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--text-dark);
            word-break: break-word;
        }

        .meta-item-value.code {
            font-family: monospace;
            color: var(--primary);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .data-table tr {
            border-bottom: 1px solid var(--border-color);
        }

        .data-table tr:last-child {
            border-bottom: none;
        }

        .data-table td {
            padding: 10px 4px;
            font-size: 0.86rem;
            vertical-align: top;
        }

        .data-table td.label-col {
            width: 36%;
            color: var(--text-muted);
            font-weight: 500;
        }

        .data-table td.val-col {
            width: 64%;
            color: var(--text-dark);
            font-weight: 600;
        }

        .security-seal {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.04) 0%, rgba(16, 185, 129, 0.05) 100%);
            border: 1px dashed rgba(79, 70, 229, 0.25);
            border-radius: 12px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            margin-top: 20px;
        }

        .seal-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: rgba(79, 70, 229, 0.12);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .seal-text {
            font-size: 0.74rem;
            color: var(--text-muted);
            line-height: 1.45;
        }

        .verify-footer {
            text-align: center;
            padding: 18px 24px;
            border-top: 1px solid var(--border-color);
            background: var(--bg-page);
            font-size: 0.76rem;
            color: var(--text-muted);
        }

        .verify-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
    </style>
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
                    NPSN: {{ $sekolah->npsn ?? '-' }} | {{ $sekolah->alamat_jalan ?? ($sekolah->desa_kelurahan ?? 'Kota Bandung, Jawa Barat') }}
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
                                <td class="val-col" style="font-size: 0.95rem; font-weight: 800; color: var(--primary-dark);">
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
                                    {{ $siswa->tempat_lahir ?: '-' }}, {{ $siswa->tanggal_lahir ? date('d F Y', strtotime($siswa->tanggal_lahir)) : '-' }}
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
                                    <span style="display: inline-flex; align-items: center; gap: 6px; background: rgba(16,185,129,0.12); color: #059669; padding: 2px 10px; border-radius: 9999px; font-size: 0.78rem; font-weight: 700;">
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
                            Dokumen ini diterbitkan secara sah melalui database administrasi Sistem Aplikasi Edukasi (SAE) dan tersimpan secara permanen pada arsip digital satuan pendidikan.
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
                    <div style="width: 64px; height: 64px; border-radius: 50%; background: rgba(239,68,68,0.12); color: var(--danger); display: inline-flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 16px;">
                        <i class="fas fa-ban"></i>
                    </div>
                    <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-dark); margin-bottom: 8px;">
                        Data Dokumen Tidak Ditemukan
                    </h3>
                    <p style="font-size: 0.84rem; color: var(--text-muted); max-width: 440px; margin: 0 auto 16px; line-height: 1.5;">
                        Kode dokumen <code>{{ $doc_id }}</code> tidak terdaftar pada pangkalan data persuratan sekolah. Harap pastikan kembali keaslian fisik dokumen atau hubungi pihak tata usaha sekolah.
                    </p>
                </div>
            @endif

            <!-- Footer Publik -->
            <div class="verify-footer">
                <div>&copy; {{ date('Y') }} {{ $sekolah->nama ?? 'SMK SAE' }}. Hak cipta dilindungi undang-undang.</div>
                <div style="margin-top: 4px;">Diverifikasi secara otomatis oleh <a href="{{ url('/') }}">Sistem Aplikasi Edukasi (SAE)</a></div>
            </div>
        </div>
    </div>
</body>
</html>
