@extends('layouts.app')

@section('title', 'Layanan e-Izin Pos Gerbang — ' . ($sekolah->nama ?? 'Sekolah'))
@section('meta_description', 'Portal resmi layanan e-Izin keluar-masuk peserta didik, pelacakan status tiket digital realtime, dan alur validasi bertingkat sekolah.')

@section('content')
    <div class="container">
        {{-- ===== Running Info Ticker ===== --}}
        <div class="ticker-wrap">
            <div class="ticker-label"><i class="fas fa-bullhorn"></i> INFO GERBANG</div>
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

        {{-- ===== Hero + Card Pengecekan Tiket ===== --}}
        <section class="hero">
            <div>
                <span class="hero-kicker"><i class="fas fa-door-open"></i> Portal e-Izin &amp; Pos Gerbang Sekolah</span>
                <h1 class="hero-title">Layanan e-Izin &amp;<br><span>Pos Gerbang Digital</span></h1>
                <p class="hero-desc">
                    Sistem perizinan keluar-masuk peserta didik terintegrasi secara digital. Pengajuan izin mandiri oleh siswa,
                    rekomendasi persetujuan Wali Kelas, otorisasi Guru Piket, serta pemantauan realtime di Pos Keamanan Gerbang.
                </p>
                <div class="hero-actions">
                    @if (session('user'))
                        <a href="{{ route('dashboard.e-izin.index') }}" class="btn btn-primary"
                            style="display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fas fa-id-card"></i> Buka Dashboard e-Izin
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary"
                            style="display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fas fa-right-to-bracket"></i> Masuk Akun Siswa
                        </a>
                    @endif
                    <a href="#sop" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-list-check"></i> Alur 4 Tahap SOP
                    </a>
                    <a href="{{ route('kiosk.e-izin') }}" class="btn btn-outline"
                        style="display: inline-flex; align-items: center; gap: 8px;" target="_blank"
                        title="Buka Terminal Scanner Pos Gerbang">
                        <i class="fas fa-desktop text-primary"></i> Terminal Kiosk
                    </a>
                </div>
                <div class="hero-badges">
                    <span class="tech-pill"><i class="fas fa-qrcode"></i> QR &amp; Barcode Digital</span>
                    <span class="tech-pill"><i class="fas fa-shield-halved"></i> Otorisasi 3 Tahap</span>
                    <span class="tech-pill"><i class="fas fa-clock"></i> Monitoring Realtime</span>
                </div>
            </div>

            {{-- Card Pengecekan Tiket e-Izin (Sama gaya dengan NISN & Perpustakaan) --}}
            <div id="cek-tiket" class="card-nisn">
                <div class="card-nisn-head">
                    <span class="card-nisn-title"><i class="fas fa-magnifying-glass text-primary"></i> Lacak Tiket e-Izin</span>
                    <span
                        style="font-size: var(--text-xs, 0.75rem); color: var(--accent); background: rgba(6,182,212,0.1); padding: 2px 8px; border-radius: var(--radius-sm, 6px);">
                        Live Check
                    </span>
                </div>
                <p style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-bottom: 1rem;">
                    Ketik Nomor Tiket (cth: <strong>IZN-...</strong>) atau NISN siswa untuk memeriksa status persetujuan keluar-masuk.
                </p>

                <form id="formCekTiket" novalidate>
                    @csrf
                    <div class="input-group" style="position: relative;">
                        <label class="input-label" for="ticketInput">Nomor Tiket / NISN Siswa</label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="text" id="ticketInput" class="input-field"
                                placeholder="Contoh: IZN-20261102-0001 atau NISN..." autocomplete="off" autofocus
                                style="font-family: var(--font-family-mono, monospace); font-weight: 600; letter-spacing: 0.5px; padding-right: 36px;">
                            <span id="ticketScanDot"
                                style="position: absolute; right: 12px; width: 9px; height: 9px; border-radius: 50%; background: var(--primary, #0ea5e9); box-shadow: 0 0 8px var(--primary, #0ea5e9);"></span>
                        </div>
                    </div>

                    <button type="submit" id="btnCekTiket" class="btn btn-primary"
                        style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 8px; margin-top: 14px;">
                        <i class="fas fa-magnifying-glass"></i> Periksa Status Tiket
                    </button>
                </form>

                {{-- Container Hasil Pelacakan Tiket --}}
                <div id="ticketResultWrap" style="display: none; margin-top: 16px;"></div>
            </div>
        </section>

        {{-- ===== Statistik KPI Layanan e-Izin ===== --}}
        <section class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon blue"><i class="fas fa-ticket"></i></div>
                <div>
                    <div class="kpi-num">{{ number_format($stats['hari_ini']) }}</div>
                    <div class="kpi-desc">Total Izin Hari Ini</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon orange"><i class="fas fa-person-walking-arrow-right"></i></div>
                <div>
                    <div class="kpi-num">{{ number_format($stats['di_luar']) }}</div>
                    <div class="kpi-desc">Sedang di Luar Sekolah</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon teal"><i class="fas fa-clipboard-check"></i></div>
                <div>
                    <div class="kpi-num">{{ number_format($stats['disetujui']) }}</div>
                    <div class="kpi-desc">Disetujui (Siap Gerbang)</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon purple"><i class="fas fa-door-closed"></i></div>
                <div>
                    <div class="kpi-num">{{ number_format($stats['selesai']) }}</div>
                    <div class="kpi-desc">Selesai / Sudah Kembali</div>
                </div>
            </div>
        </section>

        {{-- ===== Alur & SOP 4 Tahap Perizinan Siswa ===== --}}
        <section id="sop" class="services" style="margin-top: 40px;">
            <div class="section-header">
                <span class="section-kicker"><i class="fas fa-diagram-project"></i> STANDAR OPERASIONAL PROSEDUR (SOP)</span>
                <h2 class="section-title">Alur Terpadu 4 Tahap e-Izin</h2>
                <p class="section-desc">
                    Proses perizinan siswa dirancang transparan dan akuntabel guna memastikan keamanan dan ketertiban seluruh peserta didik.
                </p>
            </div>

            <div class="services-grid">
                {{-- Langkah 1 --}}
                <div class="service-card">
                    <div>
                        <div class="service-icon" style="color: var(--primary, #0284c7);"><i class="fas fa-mobile-screen"></i></div>
                        <h3 class="service-title">1. Pengajuan oleh Siswa</h3>
                        <ul class="service-list">
                            <li><i class="fas fa-check text-success me-2"></i> Siswa login ke akun SAE mandiri</li>
                            <li><i class="fas fa-check text-success me-2"></i> Memilih izin keluar sebentar / pulang cepat</li>
                            <li><i class="fas fa-check text-success me-2"></i> Mengisi jam keluar &amp; alasan jelas</li>
                        </ul>
                    </div>
                    <span class="service-cta">Tahap Awal <i class="fas fa-arrow-right"></i></span>
                </div>

                {{-- Langkah 2 --}}
                <div class="service-card">
                    <div>
                        <div class="service-icon" style="color: var(--warning, #f59e0b);"><i class="fas fa-user-tie"></i></div>
                        <h3 class="service-title">2. Validasi Wali Kelas</h3>
                        <ul class="service-list">
                            <li><i class="fas fa-check text-success me-2"></i> Notifikasi otomatis ke Wali Kelas</li>
                            <li><i class="fas fa-check text-success me-2"></i> Konfirmasi urgensi &amp; orang tua siswa</li>
                            <li><i class="fas fa-check text-success me-2"></i> Persetujuan diteruskan ke Guru Piket</li>
                        </ul>
                    </div>
                    <span class="service-cta">Verifikasi Kelas <i class="fas fa-arrow-right"></i></span>
                </div>

                {{-- Langkah 3 --}}
                <div class="service-card">
                    <div>
                        <div class="service-icon" style="color: var(--accent, #06b6d4);"><i class="fas fa-clipboard-user"></i></div>
                        <h3 class="service-title">3. Otorisasi Guru Piket</h3>
                        <ul class="service-list">
                            <li><i class="fas fa-check text-success me-2"></i> Pemeriksaan oleh Guru Piket harian</li>
                            <li><i class="fas fa-check text-success me-2"></i> Penerbitan Tiket QR &amp; Barcode Digital</li>
                            <li><i class="fas fa-check text-success me-2"></i> Cetak slip izin fisik jika dibutuhkan</li>
                        </ul>
                    </div>
                    <span class="service-cta">Penerbitan Tiket <i class="fas fa-arrow-right"></i></span>
                </div>

                {{-- Langkah 4 --}}
                <div class="service-card">
                    <div>
                        <div class="service-icon" style="color: var(--success, #10b981);"><i class="fas fa-shield-halved"></i></div>
                        <h3 class="service-title">4. Scan Pos Gerbang Satpam</h3>
                        <ul class="service-list">
                            <li><i class="fas fa-check text-success me-2"></i> Scan barcode/QR saat checkout gerbang</li>
                            <li><i class="fas fa-check text-success me-2"></i> Tap RFID kembali saat siswa tiba di sekolah</li>
                            <li><i class="fas fa-check text-success me-2"></i> Riwayat terekam otomatis ke rekap presensi</li>
                        </ul>
                    </div>
                    <span class="service-cta">Pos Keamanan <i class="fas fa-check"></i></span>
                </div>
            </div>
        </section>

        {{-- ===== Quick Links Modul Terkait ===== --}}
        <section style="margin-top: 40px; margin-bottom: 20px;">
            <div class="card" style="padding: 24px 28px; background: rgba(2, 132, 199, 0.04); border: 1px solid rgba(2, 132, 199, 0.2); border-radius: var(--radius-xl, 16px);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <h3 style="margin: 0 0 6px; font-size: var(--text-lg, 1.15rem); font-weight: 800; color: var(--text-heading);">
                            <i class="fas fa-clipboard-user text-primary me-2"></i> Kunjungan Tamu Sekolah (e-Tamu)
                        </h3>
                        <p style="margin: 0; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                            Bagi tamu umum, dinas, maupun orang tua siswa, silakan mengisi formulir kunjungan mandiri pos keamanan.
                        </p>
                    </div>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <a href="{{ route('tamu.public') }}" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fas fa-book-open"></i> Isi Buku Tamu Digital
                        </a>
                        <a href="{{ route('kiosk.e-izin') }}" target="_blank" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fas fa-desktop"></i> Terminal Kiosk Gerbang
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('styles')
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
@endpush

@section('scripts')
    <script>
        window.E_IZIN_CONFIG = {
            cekTiketUrl: "{{ route('e-izin.cek-tiket') }}"
        };
    </script>
    <script src="{{ asset('js/e-izin-publik.js') }}?v={{ time() }}"></script>
@endsection
