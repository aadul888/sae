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

        {{-- Modul Utama --}}
        <section id="modul">
            <div class="section-header">
                <span class="section-kicker">Cakupan Sistem</span>
                <h2 class="section-title">Modul Utama</h2>
                <p class="section-desc">Fitur inti yang tersedia di dalam ekosistem SAE.</p>
            </div>
            <div class="services-grid">
                @php
                    $modul = [
                        ['fa-fingerprint', 'Presensi &amp; RFID', 'Absensi peserta didik dan GTK berbasis kartu RFID, mesin tap, serta scanner webcam.'],
                        ['fa-user-graduate', 'Manajemen Peserta Didik', 'Master data, identitas, riwayat, berkas, serta kartu pelajar digital.'],
                        ['fa-id-badge', 'Manajemen GTK', 'Data guru dan tenaga kependidikan, tugas tambahan, dan kepegawaian.'],
                        ['fa-envelope-open-text', 'Persuratan', 'Surat masuk, surat keluar, ekspedisi, disposisi, dan template otomatis.'],
                        ['fa-book-open', 'Perpustakaan Digital', 'Katalog koleksi, sirkulasi, kartu anggota, dan kunjungan harian.'],
                        ['fa-building', 'Sarana &amp; Prasarana', 'Ruang, inventaris, bahan habis pakai, dan peminjaman aset.'],
                        ['fa-clipboard-list', 'Formulir &amp; Survei', 'Formulir publik maupun internal dengan rekap respon otomatis.'],
                        ['fa-award', 'Kelulusan &amp; Dokumen', 'Pengumuman kelulusan, SKL digital, dan verifikasi keaslian dokumen.'],
                    ];
                @endphp
                @foreach ($modul as [$icon, $title, $desc])
                    <div class="service-card">
                        <div>
                            <div class="service-icon"><i class="fas {{ $icon }}"></i></div>
                            <h3 class="service-title">{!! $title !!}</h3>
                            <p class="service-list">{!! $desc !!}</p>
                        </div>
                    </div>
                @endforeach
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
