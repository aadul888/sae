@extends('layouts.app')

@section('title', 'Portal Buku Tamu Digital (e-Tamu) — ' . ($sekolah->nama ?? 'Sekolah'))
@section('meta_description', 'Portal monitoring kunjungan tamu sekolah, statistik realtime pos keamanan, dan scan QR Code registrasi mandiri tamu.')

@section('content')
    <div class="container">
        {{-- ===== Running Info Ticker ===== --}}
        <div class="ticker-wrap">
            <div class="ticker-label"><i class="fas fa-bullhorn"></i> INFO TAMU</div>
            <div class="ticker-track">
                <div class="ticker-content">
                    @foreach ($running_info as $info)
                        <span class="ticker-item">{{ $info }}</span>
                    @endforeach
                </div>
                <div class="ticker-content" aria-hidden="true">
                    @foreach ($running_info as $info)
                        <span class="ticker-item">{{ $info }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ===== Hero + Card QR Code Mandiri ===== --}}
        <section class="hero">
            <div>
                <span class="hero-kicker"><i class="fas fa-clipboard-user"></i> Buku Tamu Digital &amp; Pos Keamanan</span>
                <h1 class="hero-title">Buku Tamu Digital<br><span>Pos Keamanan (e-Tamu)</span></h1>
                <p class="hero-desc">
                    Layanan resmi pemantauan dan pencatatan tamu di lingkungan <strong>{{ $sekolah->nama ?? 'Sekolah' }}</strong>.
                    Pengunjung dapat memindai QR Code di samping menggunakan smartphone untuk melakukan registrasi mandiri secara cepat.
                </p>
                <div class="hero-actions">
                    <a href="{{ route('tamu.form') }}" class="btn btn-primary"
                        style="display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-pen-to-square"></i> Buka Formulir Tamu Mandiri
                    </a>
                    @if (session('user'))
                        <a href="{{ route('dashboard.keamanan.buku-tamu.index') }}" class="btn btn-outline"
                            style="display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fas fa-shield-halved"></i> Dashboard Pos Satpam
                        </a>
                    @endif
                    <a href="#daftar-tamu" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-list-check"></i> Daftar Tamu Hari Ini
                    </a>
                    <a href="{{ route('e-izin.public') }}" class="btn btn-outline"
                        style="display: inline-flex; align-items: center; gap: 8px;"
                        title="Buka Layanan e-Izin Pos Gerbang">
                        <i class="fas fa-door-open text-primary"></i> Layanan e-Izin
                    </a>
                </div>
                <div class="hero-badges">
                    <span class="tech-pill"><i class="fas fa-qrcode"></i> QR Code Mandiri</span>
                    <span class="tech-pill"><i class="fas fa-user-check"></i> Deteksi NIK Orang Tua</span>
                    <span class="tech-pill"><i class="fas fa-clock"></i> Monitoring Realtime</span>
                </div>
            </div>

            {{-- Card Pindai QR Code Mandiri --}}
            <div class="card-nisn" style="text-align: center;">
                <div class="card-nisn-head">
                    <span class="card-nisn-title"><i class="fas fa-qrcode text-primary"></i> Pindai QR Tamu Mandiri</span>
                    <span style="font-size: var(--text-xs, 0.75rem); color: var(--accent); background: rgba(6,182,212,0.1); padding: 2px 8px; border-radius: var(--radius-sm, 6px); font-weight: 600;">
                        Scan QR
                    </span>
                </div>
                <p style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-bottom: 1rem;">
                    Arahkan kamera smartphone ke QR Code di bawah untuk membuka formulir kunjungan mandiri:
                </p>

                <div style="background: #ffffff; border-radius: var(--radius-xl, 16px); padding: 14px; display: inline-block; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-bottom: 12px; border: 1px solid var(--border-color, #e2e8f0);">
                    {!! $qrSvg !!}
                </div>

                <div style="font-family: var(--font-family-mono, monospace); font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 14px; word-break: break-all;">
                    {{ $formUrl }}
                </div>

                <a href="{{ route('tamu.form') }}" class="btn btn-primary"
                    style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fas fa-arrow-up-right-from-square"></i> Atau Isi Formulir di Layar Ini
                </a>
            </div>
        </section>

        {{-- ===== Statistik KPI Layanan e-Tamu ===== --}}
        <section class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon blue"><i class="fas fa-clipboard-user"></i></div>
                <div>
                    <div class="kpi-num">{{ number_format($stats['hari_ini']) }}</div>
                    <div class="kpi-desc">Total Tamu Hari Ini</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon orange"><i class="fas fa-street-view"></i></div>
                <div>
                    <div class="kpi-num">{{ number_format($stats['di_lokasi']) }}</div>
                    <div class="kpi-desc">Sedang di Lingkungan Sekolah</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon teal"><i class="fas fa-user-graduate"></i></div>
                <div>
                    <div class="kpi-num">{{ number_format($stats['orang_tua']) }}</div>
                    <div class="kpi-desc">Kunjungan Orang Tua</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon purple"><i class="fas fa-circle-check"></i></div>
                <div>
                    <div class="kpi-num">{{ number_format($stats['selesai']) }}</div>
                    <div class="kpi-desc">Kunjungan Selesai / Keluar</div>
                </div>
            </div>
        </section>

        {{-- ===== Daftar Tamu Hari Ini (Live Monitoring Kiosk Tamu) ===== --}}
        <section id="daftar-tamu" style="margin-bottom: 3.5rem;">
            <div class="card-nisn">
                <div class="card-nisn-head" style="margin-bottom: 1.25rem;">
                    <div>
                        <span class="card-nisn-title" style="font-size: var(--text-md, 1rem);">
                            <i class="fas fa-clock-rotate-left text-primary"></i> Daftar Kunjungan Tamu Hari Ini
                        </span>
                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                            {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }} &bull; Data realtime Pos Keamanan
                        </div>
                    </div>
                    <span style="font-size: var(--text-xs, 0.75rem); color: var(--success, #10b981); background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.25); padding: 3px 10px; border-radius: var(--radius-sm, 6px); font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fas fa-circle" style="font-size: 8px;"></i> Live Monitoring
                    </span>
                </div>

                @if ($daftarTamu->isEmpty())
                    <div style="text-align: center; padding: 2.5rem 1rem; color: var(--text-muted);">
                        <div style="width: 52px; height: 52px; border-radius: 50%; background: var(--input-bg, rgba(255,255,255,0.05)); display: inline-flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); color: var(--text-muted); margin-bottom: 10px;">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <div style="font-weight: 700; font-size: var(--text-sm, 0.875rem); color: var(--text-heading); margin-bottom: 4px;">
                            Belum Ada Tamu Hari Ini
                        </div>
                        <p style="font-size: var(--text-xs, 0.75rem); margin: 0;">
                            Pencatatan kunjungan mandiri akan langsung muncul di tabel pemantauan ini.
                        </p>
                    </div>
                @else
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: var(--text-sm, 0.8125rem);">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--border-glass, rgba(255,255,255,0.1)); text-align: left; color: var(--text-muted); font-size: var(--text-xs, 0.75rem);">
                                    <th style="padding: 10px 12px; font-weight: 700;">JAM</th>
                                    <th style="padding: 10px 12px; font-weight: 700;">NAMA TAMU &amp; KATEGORI</th>
                                    <th style="padding: 10px 12px; font-weight: 700;">TUJUAN &amp; KEPERLUAN</th>
                                    <th style="padding: 10px 12px; font-weight: 700; text-align: center;">STATUS</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($daftarTamu as $item)
                                    <tr style="border-bottom: 1px solid var(--border-glass, rgba(255,255,255,0.06));">
                                        <td style="padding: 12px; white-space: nowrap; font-family: var(--font-family-mono, monospace); font-weight: 600; color: var(--text-heading);">
                                            {{ substr($item->jam_masuk, 0, 5) }} WIB
                                        </td>
                                        <td style="padding: 12px;">
                                            <div style="font-weight: 700; color: var(--text-heading);">
                                                {{ $item->nama_tamu }}
                                            </div>
                                            <div style="margin-top: 3px; display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                                                @if ($item->jenis_tamu === 'orang_tua')
                                                    <span style="font-size: var(--text-2xs, 0.7rem); background: rgba(14,165,233,0.15); color: #0ea5e9; padding: 1px 7px; border-radius: var(--radius-xs, 4px); font-weight: 600;">
                                                        Orang Tua
                                                    </span>
                                                    @if (!empty($item->nama_siswa))
                                                        <span style="font-size: var(--text-2xs, 0.7rem); color: var(--text-muted);">
                                                            (Wali dari: {{ $item->nama_siswa }})
                                                        </span>
                                                    @endif
                                                @elseif ($item->jenis_tamu === 'kedinasan')
                                                    <span style="font-size: var(--text-2xs, 0.7rem); background: rgba(6,182,212,0.15); color: var(--accent, #06b6d4); padding: 1px 7px; border-radius: var(--radius-xs, 4px); font-weight: 600;">
                                                        Kedinasan
                                                    </span>
                                                    @if (!empty($item->instansi_asal))
                                                        <span style="font-size: var(--text-2xs, 0.7rem); color: var(--text-muted);">{{ $item->instansi_asal }}</span>
                                                    @endif
                                                    @if (!empty($item->jabatan_tamu))
                                                        <span style="font-size: var(--text-2xs, 0.7rem); color: var(--text-muted);">({{ $item->jabatan_tamu }})</span>
                                                    @endif
                                                    @if (!empty($item->ada_surat_tugas))
                                                        <span style="font-size: var(--text-2xs, 0.65rem); background: rgba(16,185,129,0.15); color: var(--success, #10b981); padding: 1px 6px; border-radius: var(--radius-xs, 3px); font-weight: 600;" title="No. {{ $item->nomor_surat_tugas }}">
                                                            <i class="fas fa-file-circle-check me-1"></i>Surat Tugas
                                                        </span>
                                                    @endif
                                                @elseif ($item->jenis_tamu === 'alumni')
                                                    <span style="font-size: var(--text-2xs, 0.7rem); background: rgba(245,158,11,0.15); color: var(--warning, #f59e0b); padding: 1px 7px; border-radius: var(--radius-xs, 4px); font-weight: 600;">
                                                        Alumni
                                                    </span>
                                                @else
                                                    <span style="font-size: var(--text-2xs, 0.7rem); background: rgba(16,185,129,0.15); color: var(--success, #10b981); padding: 1px 7px; border-radius: var(--radius-xs, 4px); font-weight: 600;">
                                                        Umum
                                                    </span>
                                                @endif

                                                @if ($item->is_mandiri)
                                                    <span style="font-size: var(--text-2xs, 0.65rem); color: var(--text-muted); background: var(--input-bg, rgba(255,255,255,0.06)); padding: 1px 5px; border-radius: var(--radius-xs, 3px);">
                                                        <i class="fas fa-qrcode me-1"></i>Mandiri
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td style="padding: 12px;">
                                            <div style="font-weight: 600; color: var(--text-heading);">
                                                {{ $item->tujuan_bertemu }}
                                            </div>
                                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                                {{ $item->keperluan }}
                                            </div>
                                        </td>
                                        <td style="padding: 12px; text-align: center; white-space: nowrap;">
                                            @if ($item->status === 'berada_di_lokasi')
                                                <span style="display: inline-flex; align-items: center; gap: 5px; background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b); border: 1px solid rgba(245,158,11,0.25); padding: 3px 10px; border-radius: var(--radius-full, 999px); font-size: var(--text-xs, 0.72rem); font-weight: 700;">
                                                    <i class="fas fa-street-view"></i> Di Lokasi
                                                </span>
                                            @else
                                                <span style="display: inline-flex; align-items: center; gap: 5px; background: rgba(16,185,129,0.12); color: var(--success, #10b981); border: 1px solid rgba(16,185,129,0.25); padding: 3px 10px; border-radius: var(--radius-full, 999px); font-size: var(--text-xs, 0.72rem); font-weight: 700;">
                                                    <i class="fas fa-check"></i> Sudah Keluar
                                                </span>
                                                @if (!empty($item->jam_keluar))
                                                    <div style="font-size: var(--text-2xs, 0.68rem); color: var(--text-muted); margin-top: 2px;">
                                                        {{ substr($item->jam_keluar, 0, 5) }} WIB
                                                    </div>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>

        {{-- ===== Alur & SOP 4 Tahap Kunjungan Tamu ===== --}}
        <section id="sop" class="services" style="margin-bottom: 3.5rem;">
            <div class="section-header">
                <span class="section-kicker"><i class="fas fa-diagram-project"></i> STANDAR OPERASIONAL PROSEDUR (SOP)</span>
                <h2 class="section-title">Alur Pelayanan Tamu Sekolah (e-Tamu)</h2>
                <p class="section-desc">
                    Prosedur penerimaan tamu dirancang tertib, cepat, dan terdata akurat demi kenyamanan bersama di lingkungan pendidikan.
                </p>
            </div>

            <div class="services-grid">
                {{-- Langkah 1 --}}
                <div class="service-card">
                    <div>
                        <div class="service-icon" style="color: var(--primary, #0284c7);"><i class="fas fa-qrcode"></i></div>
                        <h3 class="service-title">1. Pindai QR di Pos Gerbang</h3>
                        <ul class="service-list">
                            <li><i class="fas fa-check text-success me-2"></i> Pindai poster QR code di pos keamanan</li>
                            <li><i class="fas fa-check text-success me-2"></i> Akses formulir digital lewat browser HP</li>
                            <li><i class="fas fa-check text-success me-2"></i> Tanpa perlu antre buku tamu kertas</li>
                        </ul>
                    </div>
                    <span class="service-cta">Pindai QR <i class="fas fa-arrow-right"></i></span>
                </div>

                {{-- Langkah 2 --}}
                <div class="service-card">
                    <div>
                        <div class="service-icon" style="color: var(--warning, #f59e0b);"><i class="fas fa-id-card-clip"></i></div>
                        <h3 class="service-title">2. Isi Formulir &amp; Deteksi Anak</h3>
                        <ul class="service-list">
                            <li><i class="fas fa-check text-success me-2"></i> Pilih kategori Orang Tua / Tamu Umum</li>
                            <li><i class="fas fa-check text-success me-2"></i> Masukkan NIK / NISN untuk auto-deteksi</li>
                            <li><i class="fas fa-check text-success me-2"></i> Isi tujuan bertemu &amp; maksud kunjungan</li>
                        </ul>
                    </div>
                    <span class="service-cta">Verifikasi Data <i class="fas fa-arrow-right"></i></span>
                </div>

                {{-- Langkah 3 --}}
                <div class="service-card">
                    <div>
                        <div class="service-icon" style="color: var(--accent, #06b6d4);"><i class="fas fa-shield-halved"></i></div>
                        <h3 class="service-title">3. Verifikasi &amp; Kartu Visitor</h3>
                        <ul class="service-list">
                            <li><i class="fas fa-check text-success me-2"></i> Tunjukkan kode tiket ke Satpam pos gerbang</li>
                            <li><i class="fas fa-check text-success me-2"></i> Petugas satpam menyerahkan kartu visitor fisik</li>
                            <li><i class="fas fa-check text-success me-2"></i> Tamu diarahkan menuju ruang tujuan</li>
                        </ul>
                    </div>
                    <span class="service-cta">Pos Keamanan <i class="fas fa-arrow-right"></i></span>
                </div>

                {{-- Langkah 4 --}}
                <div class="service-card">
                    <div>
                        <div class="service-icon" style="color: var(--success, #10b981);"><i class="fas fa-door-open"></i></div>
                        <h3 class="service-title">4. Selesai &amp; Lapor Checkout</h3>
                        <ul class="service-list">
                            <li><i class="fas fa-check text-success me-2"></i> Menyelesaikan urusan di ruang tujuan</li>
                            <li><i class="fas fa-check text-success me-2"></i> Mengembalikan kartu visitor ke Satpam</li>
                            <li><i class="fas fa-check text-success me-2"></i> Petugas mencatat jam keluar selesai</li>
                        </ul>
                    </div>
                    <span class="service-cta">Kunjungan Berakhir <i class="fas fa-check"></i></span>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('styles')
    <style>
        .service-list {
            list-style: none;
            padding-left: 0;
            margin: 0 0 1.25rem 0;
        }
        .service-list li {
            margin-bottom: 0.4rem;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }
    </style>
@endsection
