@extends('layouts.app')

@section('title', 'SAE — Sistem Aplikasi Edukasi')

@section('content')
<div class="container">
    <!-- Info Ticker -->
    <div class="ticker-wrap">
        <div class="ticker-label"><i class="fas fa-bullhorn"></i> INFO</div>
        <div class="ticker-text">
            @foreach($running_info as $info)
                <span>● {{ $info }} &nbsp;&nbsp;&nbsp;&nbsp;</span>
            @endforeach
        </div>
    </div>

    <!-- Hero Section -->
    <section class="hero">
        <div>
            <span class="hero-kicker"><i class="fas fa-shield-halved"></i> Ekosistem Pendidikan Digital 2026</span>
            <h1 class="hero-title">Sistem Aplikasi<br><span>Edukasi (SAE)</span></h1>
            <p class="hero-desc">
                Aplikasi terintegrasi untuk absensi pintar, manajemen data siswa, validasi berkas, dan layanan informasi akademik realtime.
            </p>
            <div class="hero-actions">
                <a href="/login" class="btn btn-primary"><i class="fas fa-user-graduate"></i> Portal Siswa</a>
                <a href="/admin" class="btn btn-outline"><i class="fas fa-chalkboard-user"></i> Portal Guru &amp; Tendik</a>
            </div>
        </div>

        <!-- NISN Verification Card -->
        <div id="nisn" class="card-nisn">
            <div class="card-nisn-head">
                <span class="card-nisn-title"><i class="fas fa-id-card-clip text-primary"></i> Pengecekan Data Siswa</span>
                <span style="font-size: 0.7rem; color: var(--accent); background: rgba(6,182,212,0.1); padding: 2px 8px; border-radius: 6px;">Live Check</span>
            </div>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1rem;">
                Masukkan NISN untuk memverifikasi status aktif peserta didik.
            </p>
            <form id="nisnCheckForm">
                <div class="input-group">
                    <label class="input-label" for="nisnInput">Nomor Induk Siswa Nasional (NISN)</label>
                    <input type="text" id="nisnInput" class="input-field" placeholder="Ketik 10 digit NISN..." required pattern="[0-9]{8,12}">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-magnifying-glass"></i> Verifikasi Data
                </button>
            </form>
            <div id="nisnResult" class="nisn-result"></div>
        </div>
    </section>

    <!-- Key Statistics -->
    <section class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon blue"><i class="fas fa-user-group"></i></div>
            <div>
                <div class="kpi-num">{{ number_format($stats['total_students']) }}</div>
                <div class="kpi-desc">Total Peserta Didik</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon teal"><i class="fas fa-person"></i></div>
            <div>
                <div class="kpi-num">{{ number_format($stats['male_count']) }}</div>
                <div class="kpi-desc">Siswa Laki-laki</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon purple"><i class="fas fa-person-dress"></i></div>
            <div>
                <div class="kpi-num">{{ number_format($stats['female_count']) }}</div>
                <div class="kpi-desc">Siswa Perempuan</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon orange"><i class="fas fa-chalkboard"></i></div>
            <div>
                <div class="kpi-num">{{ $stats['grade_x'] + $stats['grade_xi'] + $stats['grade_xii'] }}</div>
                <div class="kpi-desc">Rombongan Belajar</div>
            </div>
        </div>
    </section>

    <!-- Features & Services Grid -->
    <section id="fitur">
        <div class="section-header">
            <span class="section-kicker">Modul &amp; Ekosistem</span>
            <h2 class="section-title">Layanan Utama Terintegrasi</h2>
            <p class="section-desc">Platform modular yang mempermudah seluruh operasional sekolah Anda.</p>
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
                <span class="service-cta">Akses Presensi <i class="fas fa-chevron-right"></i></span>
            </div>

            <div class="service-card">
                <div>
                    <div class="service-icon" style="color:var(--accent);"><i class="fas fa-award"></i></div>
                    <h3 class="service-title">Kelulusan &amp; Legalisir</h3>
                    <ul class="service-list">
                        <li><i class="fas fa-check text-success me-2"></i> Pengumuman Kelulusan Online</li>
                        <li><i class="fas fa-check text-success me-2"></i> Unduh SKL Digital (QR-Sign)</li>
                        <li><i class="fas fa-check text-success me-2"></i> Verifikasi Keaslian Dokumen</li>
                    </ul>
                </div>
                <span class="service-cta">Lihat Kelulusan <i class="fas fa-chevron-right"></i></span>
            </div>

            <div class="service-card">
                <div>
                    <div class="service-icon" style="color:var(--purple);"><i class="fas fa-calendar-check"></i></div>
                    <h3 class="service-title">Agenda &amp; Akademik</h3>
                    <ul class="service-list">
                        <li><i class="fas fa-check text-success me-2"></i> Jadwal Pelajaran Mingguan</li>
                        <li><i class="fas fa-check text-success me-2"></i> Kalender Event Sekolah</li>
                        <li><i class="fas fa-check text-success me-2"></i> Broadcast Pengumuman</li>
                    </ul>
                </div>
                <span class="service-cta">Cek Jadwal <i class="fas fa-chevron-right"></i></span>
            </div>

            <div class="service-card">
                <div>
                    <div class="service-icon" style="color:var(--warning);"><i class="fas fa-chart-line"></i></div>
                    <h3 class="service-title">Monitoring Data Mutu</h3>
                    <ul class="service-list">
                        <li><i class="fas fa-check text-success me-2"></i> Kualitas Data Dapodik</li>
                        <li><i class="fas fa-check text-success me-2"></i> Sinkronisasi NISN &amp; NIK</li>
                        <li><i class="fas fa-check text-success me-2"></i> Kelengkapan Berkas Murid</li>
                    </ul>
                </div>
                <span class="service-cta">Buka Monitoring <i class="fas fa-chevron-right"></i></span>
            </div>

            <div class="service-card">
                <div>
                    <div class="service-icon"><i class="fas fa-network-wired"></i></div>
                    <h3 class="service-title">Rest API &amp; SSO Hub</h3>
                    <ul class="service-list">
                        <li><i class="fas fa-check text-success me-2"></i> Single Sign On Akun Google</li>
                        <li><i class="fas fa-check text-success me-2"></i> Integrasi Webhook Eksternal</li>
                        <li><i class="fas fa-check text-success me-2"></i> Otentikasi Terpusat</li>
                    </ul>
                </div>
                <span class="service-cta">Dokumentasi API <i class="fas fa-chevron-right"></i></span>
            </div>

            <div class="service-card">
                <div>
                    <div class="service-icon" style="color:var(--success);"><i class="fas fa-sliders"></i></div>
                    <h3 class="service-title">Pusat Administrasi</h3>
                    <ul class="service-list">
                        <li><i class="fas fa-check text-success me-2"></i> Master Data Siswa &amp; GTK</li>
                        <li><i class="fas fa-check text-success me-2"></i> Cetak Kartu Pelajar Otomatis</li>
                        <li><i class="fas fa-check text-success me-2"></i> Manajemen Akses Role</li>
                    </ul>
                </div>
                <span class="service-cta">Dashboard Admin <i class="fas fa-chevron-right"></i></span>
            </div>
        </div>
    </section>

    <!-- Interactive Data Analytics -->
    <section id="statistik" class="analytics-card">
        <div class="analytics-head">
            <h3 style="font-size: clamp(1.15rem, 3vw, 1.4rem); font-weight: 800; margin-bottom: 4px;">Statistik &amp; Demografi Siswa</h3>
            <p style="color: var(--text-muted); font-size: clamp(0.75rem, 2vw, 0.875rem);">Visualisasi perbandingan jurusan, tingkat kelas, dan rasio gender.</p>
        </div>

        <!-- Charts Container -->
        <div class="charts-row">
            <div class="chart-box">
                <div class="chart-box-title"><i class="fas fa-chart-column me-2 text-primary"></i>Distribusi Siswa per Program Keahlian</div>
                <div class="chart-container-inner">
                    <canvas id="barMajorChart"></canvas>
                </div>
            </div>
            <div class="chart-box">
                <div class="chart-box-title"><i class="fas fa-chart-pie me-2 text-accent"></i>Proporsi Tingkat Kelas</div>
                <div class="chart-container-inner">
                    <canvas id="pieGradeChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Dynamic Filter Bar -->
        <div class="filter-strip">
            <select id="filterMajor" class="filter-select" aria-label="Pilih Jurusan">
                <option value="">Semua Jurusan</option>
                @foreach($major_data as $m)
                    <option value="{{ $m['nama_jurusan'] }}">{{ $m['nama_jurusan'] }}</option>
                @endforeach
            </select>
            <select id="filterGrade" class="filter-select" aria-label="Pilih Tingkat">
                <option value="">Semua Tingkat</option>
                <option value="X">Kelas X</option>
                <option value="XI">Kelas XI</option>
                <option value="XII">Kelas XII</option>
            </select>
        </div>
    </section>
</div>
@endsection

@section('scripts')
<script src="/vendor/chartjs/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const stats = @json($stats);
    const majorData = @json($major_data);

    // Bar Chart (Jurusan)
    const ctxBar = document.getElementById('barMajorChart')?.getContext('2d');
    if (ctxBar) {
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: majorData.map(m => m.code || m.nama_jurusan),
                datasets: [{
                    label: 'Jumlah Siswa',
                    data: majorData.map(m => m.total_siswa),
                    backgroundColor: '#3b82f6',
                    borderRadius: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8', font: { size: 11 } } },
                    y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8', font: { size: 11 } } }
                }
            }
        });
    }

    // Pie Chart (Tingkat)
    const ctxPie = document.getElementById('pieGradeChart')?.getContext('2d');
    if (ctxPie) {
        new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: ['Kelas X', 'Kelas XI', 'Kelas XII'],
                datasets: [{
                    data: [stats.grade_x, stats.grade_xi, stats.grade_xii],
                    backgroundColor: ['#3b82f6', '#06b6d4', '#8b5cf6'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { color: '#94a3b8', font: { size: 11 } } }
                }
            }
        });
    }
});
</script>
