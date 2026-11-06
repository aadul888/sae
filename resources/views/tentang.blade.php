@extends('layouts.app')

@section('title', 'Tentang Aplikasi — SAE (Sistem Aplikasi Edukasi)')
@section('meta_description', 'Tentang SAE (Sistem Aplikasi Edukasi): platform digital terpadu untuk absensi RFID, manajemen data peserta didik & GTK, validasi berkas, persuratan, perpustakaan, dan layanan akademik sekolah.')

@section('content')
    <div class="container">
        {{-- Hero --}}
        <section class="hero">
            <div>
                <span class="hero-kicker"><i class="fas fa-circle-info"></i> Profil &amp; Informasi Aplikasi</span>
                <h1 class="hero-title">Tentang<br><span>Aplikasi SAE</span></h1>
                <p class="hero-desc">
                    SAE (Sistem Aplikasi Edukasi) adalah platform digital terpadu yang menyatukan absensi
                    cerdas, manajemen data peserta didik dan GTK, validasi berkas, persuratan, perpustakaan,
                    serta layanan informasi akademik dalam satu ekosistem. Aplikasi ini dapat diakses oleh
                    pengguna internal sekolah maupun publik melalui portal layanan mandiri.
                </p>
                <div class="hero-actions">
                    <a href="{{ route('login') }}" class="btn btn-primary">
                        <i class="fas fa-right-to-bracket"></i> Masuk Portal
                    </a>
                    <a href="{{ url('/') }}" class="btn btn-outline">
                        <i class="fas fa-house"></i> Beranda Publik
                    </a>
                </div>
            </div>

            {{-- Kartu Ringkasan --}}
            <div class="card-nisn">
                <div class="card-nisn-head">
                    <span class="card-nisn-title"><i class="fas fa-id-badge text-primary"></i> Ringkasan Aplikasi</span>
                    <span
                        style="font-size: var(--text-xs, 0.75rem); color: var(--accent); background: rgba(6,182,212,0.1); padding: 2px 8px; border-radius: var(--radius-sm, 6px);">v{{ $appVersion ?? '1.0.1' }}</span>
                </div>
                <div>
                    @php
                        $ringkasan = [
                            ['Nama Aplikasi', 'SAE (Sistem Aplikasi Edukasi)'],
                            ['Versi', 'v' . ($appVersion ?? '1.0.1')],
                            ['Basis Teknologi', 'Laravel 13 · PHP 8.3 · MySQL/MariaDB'],
                            ['Peran Pengguna', 'Admin, Guru, Tendik, Peserta Didik, Orang Tua'],
                            ['Akses', 'Internal Sekolah &amp; Publik'],
                        ];
                    @endphp
                    @foreach ($ringkasan as [$label, $value])
                        <div
                            style="display: flex; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--border-glass);">
                            <span style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); font-weight: 600;">{{ $label }}</span>
                            <span
                                style="font-size: var(--text-sm, 0.8125rem); color: var(--text-main); font-weight: 600; text-align: right;">{!! $value !!}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Atribut Utama --}}
        <section class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon blue"><i class="fas fa-cubes"></i></div>
                <div>
                    <div class="kpi-num">Multi-Modul</div>
                    <div class="kpi-desc">Absensi, Data, Persuratan, Perpus</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon teal"><i class="fas fa-users-viewfinder"></i></div>
                <div>
                    <div class="kpi-num">5 Peran</div>
                    <div class="kpi-desc">Hak akses berbasis RBAC</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon purple"><i class="fas fa-tower-broadcast"></i></div>
                <div>
                    <div class="kpi-num">Realtime</div>
                    <div class="kpi-desc">RFID, Webcam &amp; Notifikasi</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon orange"><i class="fas fa-mobile-screen-button"></i></div>
                <div>
                    <div class="kpi-num">PWA</div>
                    <div class="kpi-desc">Install &amp; akses via HP</div>
                </div>
            </div>
        </section>

        {{-- Apa itu SAE --}}
        <section class="analytics-card">
            <div class="analytics-head">
                <span class="section-kicker">Pengenalan</span>
                <h2 class="section-title" style="text-align: left; margin-bottom: 0.5rem;">Apa itu SAE?</h2>
                <p class="section-desc" style="text-align: left; margin: 0;">
                    Satu aplikasi untuk seluruh kebutuhan administrasi dan layanan digital sekolah.
                </p>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.25rem;">
                <p style="color: var(--text-muted); line-height: 1.7; font-size: var(--text-base, 0.875rem); margin: 0;">
                    <strong class="text-primary">SAE</strong> dikembangkan sebagai sistem informasi manajemen
                    sekolah yang menggabungkan pengelolaan master data, presensi, kesiswaan, kepegawaian,
                    persuratan, sarana-prasarana, perpustakaan, hingga kelulusan. Data utama terhubung dengan
                    Dapodik sehingga informasi peserta didik dan GTK selalu selaras.
                </p>
                <p style="color: var(--text-muted); line-height: 1.7; font-size: var(--text-base, 0.875rem); margin: 0;">
                    Aplikasi menyediakan portal publik untuk layanan mandiri seperti cek data peserta didik,
                    buku tamu digital, e-izin, dan katalog perpustakaan. Sementara pengguna internal masuk
                    melalui portal terautentikasi dengan hak akses sesuai peran dan tugas tambahannya.
                </p>
            </div>
        </section>

        {{-- Modul & Layanan Utama Terintegrasi --}}
        <section id="modul">
            <div class="section-header">
                <span class="section-kicker">Cakupan Sistem &amp; Ekosistem</span>
                <h2 class="section-title">Layanan &amp; Modul Utama Terintegrasi</h2>
                <p class="section-desc">Katalog lengkap modul dan layanan modular yang mempermudah seluruh operasional sekolah Anda.</p>
            </div>
            <div class="services-grid">
                <div class="service-card">
                    <div>
                        <div class="service-icon"><i class="fas fa-fingerprint"></i></div>
                        <h3 class="service-title">Sistem Absensi Terpadu</h3>
                        <ul class="service-list">
                            <li><i class="fas fa-check text-success me-2"></i> Integrasi Mesin RFID Tap</li>
                            <li><i class="fas fa-check text-success me-2"></i> Notifikasi WhatsApp Terjadwal</li>
                            <li><i class="fas fa-check text-success me-2"></i> Rekap Kehadiran Realtime</li>
                        </ul>
                    </div>
                    <a href="{{ route('login') }}" class="service-cta">Akses Presensi <i class="fas fa-chevron-right"></i></a>
                </div>

                <a href="{{ route('e-izin.public') }}" class="service-card">
                    <div>
                        <div class="service-icon" style="color:var(--accent);"><i class="fas fa-door-open"></i></div>
                        <h3 class="service-title">e-Izin &amp; Pos Gerbang</h3>
                        <ul class="service-list">
                            <li><i class="fas fa-check text-success me-2"></i> Pengajuan Dispensasi Mandiri</li>
                            <li><i class="fas fa-check text-success me-2"></i> Approval Wali Kelas &amp; Piket</li>
                            <li><i class="fas fa-check text-success me-2"></i> Scan Barcode Gerbang Satpam</li>
                        </ul>
                    </div>
                    <span class="service-cta">Form Izin Siswa <i class="fas fa-chevron-right"></i></span>
                </a>

                <a href="{{ route('tamu.public') }}" class="service-card" target="_blank">
                    <div>
                        <div class="service-icon" style="color:var(--success);"><i class="fas fa-address-book"></i></div>
                        <h3 class="service-title">Buku Tamu (e-Tamu)</h3>
                        <ul class="service-list">
                            <li><i class="fas fa-check text-success me-2"></i> Pindai KTP / QR Digital</li>
                            <li><i class="fas fa-check text-success me-2"></i> Deteksi Siswa Terintegrasi</li>
                            <li><i class="fas fa-check text-success me-2"></i> Cetak Tiket Pos Keamanan</li>
                        </ul>
                    </div>
                    <span class="service-cta">Buka Portal Tamu <i class="fas fa-chevron-right"></i></span>
                </a>

                <a href="{{ route('perpustakaan.index') }}" class="service-card" target="_blank">
                    <div>
                        <div class="service-icon" style="color:var(--purple);"><i class="fas fa-book-open"></i></div>
                        <h3 class="service-title">Perpustakaan Digital</h3>
                        <ul class="service-list">
                            <li><i class="fas fa-check text-success me-2"></i> Katalog Koleksi &amp; E-Book</li>
                            <li><i class="fas fa-check text-success me-2"></i> Kiosk Mandiri Kartu Pelajar</li>
                            <li><i class="fas fa-check text-success me-2"></i> Sirkulasi Cepat Tanpa Antre</li>
                        </ul>
                    </div>
                    <span class="service-cta">Katalog Buku <i class="fas fa-chevron-right"></i></span>
                </a>

                <div class="service-card">
                    <div>
                        <div class="service-icon" style="color:#0ea5e9;"><i class="fas fa-award"></i></div>
                        <h3 class="service-title">Kelulusan &amp; Legalisir</h3>
                        <ul class="service-list">
                            <li><i class="fas fa-check text-success me-2"></i> Pengumuman Kelulusan Online</li>
                            <li><i class="fas fa-check text-success me-2"></i> SKL Digital dengan QR-Sign</li>
                            <li><i class="fas fa-check text-success me-2"></i> Verifikasi Keaslian Dokumen</li>
                        </ul>
                    </div>
                    <a href="{{ route('login') }}" class="service-cta">Cek Dokumen <i class="fas fa-chevron-right"></i></a>
                </div>

                <div class="service-card">
                    <div>
                        <div class="service-icon" style="color:#8b5cf6;"><i class="fas fa-calendar-days"></i></div>
                        <h3 class="service-title">Agenda &amp; Akademik</h3>
                        <ul class="service-list">
                            <li><i class="fas fa-check text-success me-2"></i> Jadwal Pelajaran Dinamis</li>
                            <li><i class="fas fa-check text-success me-2"></i> Kalender Event Sekolah</li>
                            <li><i class="fas fa-check text-success me-2"></i> Broadcast Informasi Internal</li>
                        </ul>
                    </div>
                    <a href="{{ route('login') }}" class="service-cta">Jadwal Belajar <i class="fas fa-chevron-right"></i></a>
                </div>

                <div class="service-card">
                    <div>
                        <div class="service-icon" style="color:var(--warning);"><i class="fas fa-chart-line"></i></div>
                        <h3 class="service-title">Monitoring Mutu Data</h3>
                        <ul class="service-list">
                            <li><i class="fas fa-check text-success me-2"></i> Kualitas Data Dapodik</li>
                            <li><i class="fas fa-check text-success me-2"></i> Sinkronisasi NISN &amp; NIK</li>
                            <li><i class="fas fa-check text-success me-2"></i> Validasi Berkas Peserta Didik</li>
                        </ul>
                    </div>
                    <a href="{{ route('login') }}" class="service-cta">Buka Monitoring <i class="fas fa-chevron-right"></i></a>
                </div>

                <div class="service-card">
                    <div>
                        <div class="service-icon" style="color:var(--success);"><i class="fas fa-sliders"></i></div>
                        <h3 class="service-title">Pusat Administrasi &amp; GTK</h3>
                        <ul class="service-list">
                            <li><i class="fas fa-check text-success me-2"></i> Master Data Peserta Didik &amp; GTK</li>
                            <li><i class="fas fa-check text-success me-2"></i> Cetak Kartu Pelajar Otomatis</li>
                            <li><i class="fas fa-check text-success me-2"></i> Manajemen Akses Role (RBAC)</li>
                        </ul>
                    </div>
                    <a href="{{ route('login') }}" class="service-cta">Dashboard Admin <i class="fas fa-chevron-right"></i></a>
                </div>

                <div class="service-card">
                    <div>
                        <div class="service-icon"><i class="fas fa-network-wired"></i></div>
                        <h3 class="service-title">Rest API &amp; SSO Hub</h3>
                        <ul class="service-list">
                            <li><i class="fas fa-check text-success me-2"></i> Single Sign On Akun Google</li>
                            <li><i class="fas fa-check text-success me-2"></i> Integrasi Webhook Eksternal</li>
                            <li><i class="fas fa-check text-success me-2"></i> Otentikasi Terpusat Berkecepatan Tinggi</li>
                        </ul>
                    </div>
                    <a href="{{ route('login') }}" class="service-cta">Dokumentasi API <i class="fas fa-chevron-right"></i></a>
                </div>

                <div class="service-card">
                    <div>
                        <div class="service-icon" style="color: #ec4899;"><i class="fas fa-envelope-open-text"></i></div>
                        <h3 class="service-title">Tata Persuratan Digital</h3>
                        <ul class="service-list">
                            <li><i class="fas fa-check text-success me-2"></i> Registrasi Surat Masuk &amp; Keluar</li>
                            <li><i class="fas fa-check text-success me-2"></i> Buku Ekspedisi &amp; Tracking Distribusi</li>
                            <li><i class="fas fa-check text-success me-2"></i> Disposisi Berjenjang Pejabat</li>
                        </ul>
                    </div>
                    <a href="{{ route('login') }}" class="service-cta">Akses Persuratan <i class="fas fa-chevron-right"></i></a>
                </div>

                <div class="service-card">
                    <div>
                        <div class="service-icon" style="color: #f59e0b;"><i class="fas fa-building-circle-check"></i></div>
                        <h3 class="service-title">Sarana &amp; Prasarana</h3>
                        <ul class="service-list">
                            <li><i class="fas fa-check text-success me-2"></i> Inventaris Ruang &amp; Barang Habis Pakai</li>
                            <li><i class="fas fa-check text-success me-2"></i> Peminjaman Aset &amp; Pencatatan Kondisi</li>
                            <li><i class="fas fa-check text-success me-2"></i> Manajemen Pemeliharaan Fasilitas</li>
                        </ul>
                    </div>
                    <a href="{{ route('login') }}" class="service-cta">Kelola Sarpras <i class="fas fa-chevron-right"></i></a>
                </div>

                <div class="service-card">
                    <div>
                        <div class="service-icon" style="color: #10b981;"><i class="fas fa-clipboard-question"></i></div>
                        <h3 class="service-title">Formulir &amp; Survei</h3>
                        <ul class="service-list">
                            <li><i class="fas fa-check text-success me-2"></i> Pembuat Formulir Dinamis Tanpa Koding</li>
                            <li><i class="fas fa-check text-success me-2"></i> Rekap &amp; Ekspor Respon Otomatis</li>
                            <li><i class="fas fa-check text-success me-2"></i> Pembatasan Akses Publik &amp; Internal</li>
                        </ul>
                    </div>
                    <a href="{{ route('login') }}" class="service-cta">Buka Formulir <i class="fas fa-chevron-right"></i></a>
                </div>
            </div>
        </section>

        {{-- Teknologi --}}
        <section class="analytics-card">
            <div class="analytics-head">
                <span class="section-kicker">Infrastruktur</span>
                <h2 class="section-title" style="text-align: left; margin-bottom: 0.5rem;">Teknologi yang Digunakan</h2>
                <p class="section-desc" style="text-align: left; margin: 0;">
                    Dibangun dengan teknologi modern dan ringan untuk menunjang performa harian.
                </p>
            </div>
            <div class="hero-badges">
                @foreach (['Laravel 13', 'PHP 8.3', 'MySQL / MariaDB', 'Blade + Design Token', 'Chart.js', 'SweetAlert2', 'PWA', 'REST Dapodik'] as $tech)
                    <span class="tech-pill"><i class="fas fa-circle-check text-success"></i> {{ $tech }}</span>
                @endforeach
            </div>
        </section>

        {{-- Identitas Sekolah --}}
        @if ($sekolah)
            <section class="analytics-card">
                <div class="analytics-head">
                    <span class="section-kicker">Penyelenggara</span>
                    <h2 class="section-title" style="text-align: left; margin-bottom: 0.5rem;">
                        {{ $sekolah->nama ?? 'Satuan Pendidikan' }}</h2>
                    <p class="section-desc" style="text-align: left; margin: 0;">
                        Aplikasi ini diselenggarakan oleh satuan pendidikan berikut.
                    </p>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
                    @php
                        $identitas = [
                            ['NPSN', $sekolah->npsn ?? null, 'fa-hashtag'],
                            [
                                'Alamat',
                                trim(implode(', ', array_filter([
                                    $sekolah->alamat_jalan ?? null,
                                    $sekolah->kecamatan ?? null,
                                    $sekolah->kabupaten_kota ?? null,
                                    $sekolah->provinsi ?? null,
                                ]))),
                                'fa-location-dot',
                            ],
                            ['Telepon', $sekolah->nomor_telepon ?? null, 'fa-phone'],
                            ['Email', $sekolah->email ?? null, 'fa-envelope'],
                            ['Website', $sekolah->website ?? null, 'fa-globe'],
                        ];
                    @endphp
                    @foreach ($identitas as [$label, $value, $icon])
                        @if (!empty($value))
                            <div
                                style="display: flex; gap: 10px; align-items: flex-start; background: var(--input-bg); border: 1px solid var(--border-glass); border-radius: var(--radius-xl, 16px); padding: 1rem;">
                                <span class="nav-icon"><i class="fas {{ $icon }} text-primary"></i></span>
                                <div>
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">{{ $label }}</div>
                                    <div style="font-size: var(--text-base, 0.875rem); color: var(--text-main); font-weight: 600; word-break: break-word;">{{ $value }}</div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
