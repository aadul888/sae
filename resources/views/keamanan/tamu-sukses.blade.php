<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Tiket Kunjungan Tamu — {{ $sekolah->nama ?? 'SAE' }}</title>
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sae.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components.css') }}">
    <style>
        body {
            background-color: var(--bg-surface, #f8fafc);
            color: var(--text-color, #1e293b);
            font-family: var(--font-family, system-ui, -apple-system, sans-serif);
            margin: 0;
            padding: var(--space-4, 8px);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
        }
        .pass-wrapper {
            width: 100%;
            max-width: 520px;
            margin: var(--space-4, 8px) auto var(--space-8, 16px);
        }
        .pass-card {
            background: #ffffff;
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: var(--radius-2xl, 20px);
            padding: var(--space-8, 20px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .pass-header-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(16, 185, 129, 0.12);
            color: #059669;
            padding: 6px 14px;
            border-radius: var(--radius-pill, 999px);
            font-size: var(--text-xs, 0.75rem);
            font-weight: 700;
            margin-bottom: 12px;
        }
        .pass-title {
            font-size: var(--text-xl, 1.25rem);
            font-weight: 800;
            color: var(--text-heading, #0f172a);
            margin: 0 0 4px;
        }
        .pass-subtitle {
            font-size: var(--text-xs, 0.75rem);
            color: var(--text-muted, #64748b);
            margin: 0 0 16px;
        }
        .pass-ticket-box {
            background: rgba(2, 132, 199, 0.04);
            border: 2px dashed rgba(2, 132, 199, 0.3);
            border-radius: var(--radius-xl, 16px);
            padding: 16px;
            margin-bottom: 16px;
            text-align: left;
        }
        .pass-ticket-code {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px dashed rgba(2, 132, 199, 0.2);
            padding-bottom: 10px;
            margin-bottom: 12px;
        }
        .pass-ticket-code span:first-child {
            font-size: var(--text-xs, 0.75rem);
            color: var(--text-muted, #64748b);
            font-weight: 600;
        }
        .pass-ticket-code span:last-child {
            font-family: monospace;
            font-size: var(--text-base, 1rem);
            font-weight: 800;
            color: var(--primary, #0284c7);
            letter-spacing: 0.5px;
        }
        .pass-detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: var(--text-xs, 0.75rem);
            gap: 12px;
        }
        .pass-detail-label {
            color: var(--text-muted, #64748b);
            font-weight: 600;
            flex-shrink: 0;
        }
        .pass-detail-value {
            color: var(--text-heading, #0f172a);
            font-weight: 700;
            text-align: right;
            word-break: break-word;
        }
        .student-highlight-box {
            background: #ffffff;
            border: 1px solid rgba(2, 132, 199, 0.25);
            border-radius: var(--radius-md, 8px);
            padding: 10px;
            margin-top: 10px;
        }
        .instruction-box {
            background: rgba(245, 158, 11, 0.08);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: var(--radius-lg, 12px);
            padding: 12px 14px;
            font-size: var(--text-xs, 0.75rem);
            color: #92400e;
            text-align: left;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }
        .btn-new-tamu {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: var(--primary, #0284c7);
            color: #ffffff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: var(--radius-md, 8px);
            font-weight: 700;
            font-size: var(--text-sm, 0.8125rem);
            width: 100%;
            box-sizing: border-box;
            border: none;
        }
        .btn-new-tamu:hover {
            background: #0369a1;
            color: #ffffff;
        }
    </style>
</head>
<body>

    <div class="pass-wrapper">
        <div class="pass-card">
            <div class="pass-header-badge">
                <i class="fas fa-check-circle"></i> Kunjungan Tercatat
            </div>

            <h2 class="pass-title">Terima Kasih, {{ $tamu->nama_tamu }}</h2>
            <p class="pass-subtitle">Data kunjungan Anda telah tersimpan di sistem keamanan {{ $sekolah->nama ?? 'Sekolah' }}.</p>

            {{-- Box Detail Tiket --}}
            <div class="pass-ticket-box">
                <div class="pass-ticket-code">
                    <span>KODE TIKET</span>
                    <span>#VIS-{{ date('Ymd', strtotime($tamu->tanggal)) }}-{{ str_pad($tamu->id, 4, '0', STR_PAD_LEFT) }}</span>
                </div>

                <div class="pass-detail-row">
                    <span class="pass-detail-label">Tanggal & Jam:</span>
                    <span class="pass-detail-value">{{ \Carbon\Carbon::parse($tamu->tanggal)->translatedFormat('d F Y') }} &bull; {{ substr($tamu->jam_masuk, 0, 5) }} WIB</span>
                </div>

                <div class="pass-detail-row">
                    <span class="pass-detail-label">Kategori Tamu:</span>
                    <span class="pass-detail-value">
                        @if ($tamu->jenis_tamu === 'orang_tua')
                            Orang Tua / Wali Siswa
                        @elseif ($tamu->jenis_tamu === 'kedinasan')
                            Kedinasan / Instansi
                        @elseif ($tamu->jenis_tamu === 'alumni')
                            Alumni
                        @else
                            Umum / Masyarakat
                        @endif
                    </span>
                </div>

                @if ($tamu->nomor_kontak)
                    <div class="pass-detail-row">
                        <span class="pass-detail-label">Nomor Kontak:</span>
                        <span class="pass-detail-value">{{ $tamu->nomor_kontak }}</span>
                    </div>
                @endif

                @if ($tamu->nama_siswa)
                    <div class="student-highlight-box">
                        <div style="font-size: 11px; font-weight: 700; color: var(--primary, #0284c7); margin-bottom: 4px;">
                            <i class="fas fa-child me-1"></i> Data Siswa Terkait:
                        </div>
                        <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-heading, #0f172a);">
                            {{ $tamu->nama_siswa }}
                        </div>
                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted, #64748b);">
                            Kelas: <strong>{{ $tamu->rombel_siswa ?: '-' }}</strong>
                            @if ($tamu->nisn_siswa)
                                &bull; NISN: {{ $tamu->nisn_siswa }}
                            @endif
                        </div>
                    </div>
                @endif

                <div class="pass-detail-row" style="margin-top: 10px;">
                    <span class="pass-detail-label">Bertemu Dengan:</span>
                    <span class="pass-detail-value">{{ $tamu->tujuan_bertemu }}</span>
                </div>

                <div class="pass-detail-row">
                    <span class="pass-detail-label">Keperluan:</span>
                    <span class="pass-detail-value">{{ $tamu->keperluan }}</span>
                </div>

                @if ($tamu->nomor_polisi_kendaraan)
                    <div class="pass-detail-row">
                        <span class="pass-detail-label">Kendaraan:</span>
                        <span class="pass-detail-value">{{ $tamu->nomor_polisi_kendaraan }}</span>
                    </div>
                @endif
            </div>

            {{-- Instruksi untuk tamu --}}
            <div class="instruction-box">
                <i class="fas fa-info-circle" style="font-size: var(--text-lg, 1.1rem); flex-shrink: 0; margin-top: 2px;"></i>
                <div>
                    <strong>Langkah Selanjutnya:</strong><br>
                    1. Tunjukkan layar tiket ini kepada <strong>Petugas Pos Keamanan / Satpam</strong> untuk diverifikasi.<br>
                    2. Ambil Kartu Tamu / Visitor Badge dari petugas jika disediakan.<br>
                    3. Sebelum meninggalkan area sekolah, harap melapor kembali ke Pos Keamanan.
                </div>
            </div>

            <a href="{{ route('tamu.public') }}" class="btn-new-tamu">
                <i class="fas fa-plus"></i> Isi Buku Tamu Lain
            </a>
        </div>
    </div>

</body>
</html>
