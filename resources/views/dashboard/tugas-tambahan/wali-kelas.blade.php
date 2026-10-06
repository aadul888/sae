@extends('layouts.dashboard')

@section('title', 'Dashboard Wali Kelas ' . ($rombel->nama ?? '') . ' — SAE')
@section('dash_title', 'Dashboard Wali Kelas')

@section('content')
    <!-- 1. Banner Header Wali Kelas -->
    <div class="dash-banner"
        style="margin-bottom: 20px; padding: 16px 20px; border-radius: 14px; background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(16, 185, 129, 0.08) 100%); border: 1px solid rgba(99, 102, 241, 0.3);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
            <div style="display: flex; align-items: center; gap: 14px; flex: 1; min-width: 240px;">
                <div
                    style="width: 44px; height: 44px; border-radius: 12px; background: rgba(99, 102, 241, 0.2); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; box-shadow: 0 4px 14px rgba(99, 102, 241, 0.2);">
                    <i class="fas fa-chalkboard-user"></i>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 3px;">
                        <span class="badge badge-primary" style="font-size: 0.72rem; padding: 2px 8px; font-weight: 700;">
                            WALI KELAS AKTIF
                        </span>
                        <span class="badge badge-outline" style="font-size: 0.72rem; padding: 2px 8px;">
                            TA. {{ \App\Support\SemesterHelper::getActiveSemesterLabel() }}
                        </span>
                    </div>
                    <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--text-color); margin: 0 0 2px 0;">
                        {{ $rombel->nama ?? 'Kelas Perwalian' }}
                    </h2>
                    <p style="color: var(--text-muted); font-size: 0.8rem; margin: 0; line-height: 1.4;">
                        Kelola administrasi, rekap presensi, biodata peserta didik, serta pantau kedisiplinan siswa di kelas
                        Anda secara langsung.
                    </p>
                </div>
            </div>

            <div style="display: flex; align-items: center; flex-shrink: 0;">
                <a href="{{ route('dashboard.wali-kelas.presensi.index') }}" class="btn btn-primary"
                    style="padding: 7px 14px; font-size: 0.8rem; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;">
                    <i class="fas fa-calendar-check"></i> Input Presensi Kelas
                </a>
            </div>
        </div>
    </div>

    <!-- 2. Stat Cards Perwalian -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 22px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
                <i class="fas fa-users"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $totalSiswa }}</div>
                <div class="dash-stat-label">Total Peserta Didik</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: #10b981;">
                    {{ $presensiHariIni['H'] ?? 0 }}
                    <span
                        style="font-size: 0.78rem; font-weight: 600; color: var(--text-muted);">({{ $presensiHariIni['persen'] ?? 0 }}%)</span>
                </div>
                <div class="dash-stat-label">Hadir Hari Ini</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: #f59e0b;">{{ $presensiHariIni['T'] ?? 0 }}</div>
                <div class="dash-stat-label">Terlambat Masuk</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6;">
                <i class="fas fa-envelope-open-text"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: #8b5cf6;">
                    {{ ($presensiHariIni['S'] ?? 0) + ($presensiHariIni['I'] ?? 0) }}</div>
                <div class="dash-stat-label">Sakit / Izin (S:{{ $presensiHariIni['S'] ?? 0 }},
                    I:{{ $presensiHariIni['I'] ?? 0 }})</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239, 68, 68, 0.15); color: #ef4444;">
                <i class="fas fa-user-xmark"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: #ef4444;">{{ $presensiHariIni['A'] ?? 0 }}</div>
                <div class="dash-stat-label">Tanpa Keterangan (Alpha)</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6, 182, 212, 0.15); color: var(--accent, #06b6d4);">
                <i class="fas fa-file-circle-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: var(--accent, #06b6d4);">
                    {{ $kualitasData['persen'] ?? 0 }}%
                </div>
                <div class="dash-stat-label">Kualitas Data ({{ $kualitasData['valid'] ?? 0 }}/{{ $totalSiswa }})</div>
            </div>
        </div>
    </div>

    <!-- 3. Akses Cepat Menu Wali Kelas (Gaya Dashboard Tendik: Responsive 6-item Grid) -->
    <div class="kepegawaian-quick-grid">
        <a href="{{ route('dashboard.wali-kelas.peserta-didik-aktif.index') }}" class="kepegawaian-quick-btn"
            style="--quick-color: #10b981;" title="Data Induk Siswa Aktif">
            <div class="kepegawaian-quick-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                <i class="fas fa-user-check"></i>
            </div>
            <span class="kepegawaian-quick-label">Siswa Aktif</span>
        </a>

        <a href="{{ route('dashboard.wali-kelas.presensi.index') }}" class="kepegawaian-quick-btn"
            style="--quick-color: #6366f1;" title="Presensi &amp; Rekap Kehadiran">
            <div class="kepegawaian-quick-icon" style="background: rgba(99, 102, 241, 0.15); color: #6366f1;">
                <i class="fas fa-clipboard-user"></i>
            </div>
            <span class="kepegawaian-quick-label">Presensi Kelas</span>
        </a>

        <a href="{{ route('dashboard.wali-kelas.peserta-didik-tidak-aktif.index') }}" class="kepegawaian-quick-btn"
            style="--quick-color: #ef4444;" title="Arsip Siswa Mutasi / Keluar">
            <div class="kepegawaian-quick-icon" style="background: rgba(239, 68, 68, 0.15); color: #ef4444;">
                <i class="fas fa-user-slash"></i>
            </div>
            <span class="kepegawaian-quick-label">Siswa Mutasi</span>
        </a>

        <a href="{{ route('dashboard.peserta-didik.izin.index') }}" class="kepegawaian-quick-btn"
            style="--quick-color: #f59e0b;" title="Verifikasi e-Izin &amp; Surat Sakit">
            <div class="kepegawaian-quick-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                <i class="fas fa-envelope-open-text"></i>
            </div>
            <span class="kepegawaian-quick-label">e-Izin Siswa</span>
        </a>

        <a href="{{ route('dashboard.wali-kelas.peserta-didik-aktif.index') }}" class="kepegawaian-quick-btn"
            style="--quick-color: #06b6d4;" title="Cetak Kartu Pelajar Digital">
            <div class="kepegawaian-quick-icon" style="background: rgba(6, 182, 212, 0.15); color: #06b6d4;">
                <i class="fas fa-id-card"></i>
            </div>
            <span class="kepegawaian-quick-label">Kartu Pelajar</span>
        </a>

        <a href="{{ route('dashboard.wali-kelas.presensi.index') }}" class="kepegawaian-quick-btn"
            style="--quick-color: #ec4899;" title="Laporan &amp; Rekapitulasi Presensi">
            <div class="kepegawaian-quick-icon" style="background: rgba(236, 72, 153, 0.15); color: #ec4899;">
                <i class="fas fa-file-invoice"></i>
            </div>
            <span class="kepegawaian-quick-label">Rekap Presensi</span>
        </a>
    </div>

    <!-- 4. Interactive Mixed Analytics Charts Section -->
    <div style="margin-top: 4px; margin-bottom: 24px;">
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
            <div>
                <h3
                    style="font-size: 1.08rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-chart-line text-primary"></i> Analitik &amp; Visualisasi Kedisiplinan Kelas
                </h3>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin: 3px 0 0 0;">
                    Monitoring interaktif tren kehadiran harian, komposisi status hari ini, rasio gender, dan ketepatan
                    waktu siswa.
                </p>
            </div>
            <div
                style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.25); border-radius: 20px; font-size: 0.72rem; font-weight: 700; color: #10b981;">
                <i class="fas fa-circle-dot" style="font-size: 0.55rem; color: #10b981;"></i> Monitoring Real-Time
                {{ $rombel->nama ?? '' }}
            </div>
        </div>

        <!-- Charts Row 1: Line & Bar Combo (Tren Presensi) & Doughnut (Komposisi Hari Ini) -->
        <div class="dash-grid-2" style="margin-bottom: 18px;">
            <!-- Chart 1: Tren Presensi 7 Hari Terakhir -->
            <div class="card" style="margin-bottom: 0; padding: 18px 20px;">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                    <div>
                        <h4
                            style="font-size: 0.94rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-chart-area text-success"></i> Tren Kehadiran Siswa (Kombinasi Garis &amp;
                            Batang)
                        </h4>
                        <span style="font-size: 0.72rem; color: var(--text-muted);">Tren kehadiran mingguan kelas</span>
                    </div>
                    <span class="badge badge-success" style="font-size: 0.72rem; padding: 3px 8px;">
                        <i class="fas fa-arrow-trend-up me-1"></i> {{ $presensiHariIni['persen'] ?? 94.3 }}%
                    </span>
                </div>
                <div style="position: relative; height: 220px; width: 100%;">
                    <canvas id="chartWaliTrend"></canvas>
                </div>
            </div>

            <!-- Chart 2: Komposisi Status Presensi Hari Ini -->
            <div class="card" style="margin-bottom: 0; padding: 18px 20px;">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                    <div>
                        <h4
                            style="font-size: 0.94rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-chart-pie text-accent"></i> Komposisi Status Kehadiran Hari Ini
                        </h4>
                        <span style="font-size: 0.72rem; color: var(--text-muted);">Total {{ $totalSiswa }} Siswa
                            Terdaftar</span>
                    </div>
                    <span class="badge"
                        style="background: rgba(99,102,241,0.12); color: var(--primary); font-size: 0.72rem; padding: 3px 8px;">
                        {{ $presensiHariIni['H'] ?? 0 }} Hadir / {{ $totalSiswa }} Siswa
                    </span>
                </div>
                <div style="position: relative; height: 220px; width: 100%;">
                    <canvas id="chartWaliKomposisi"></canvas>
                </div>
            </div>
        </div>

        <!-- Charts Row 2: Gender Siswa & Evaluasi Ketepatan Waktu -->
        <div class="dash-grid-2">
            <!-- Chart 3: Komposisi Gender Siswa Kelas -->
            <div class="card" style="margin-bottom: 0; padding: 18px 20px;">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                    <div>
                        <h4
                            style="font-size: 0.94rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-people-arrows text-primary"></i> Komposisi Gender Siswa Kelas
                        </h4>
                        <span style="font-size: 0.72rem; color: var(--text-muted);">Distribusi Laki-laki vs
                            Perempuan</span>
                    </div>
                    <span class="badge badge-outline" style="font-size: 0.72rem; padding: 3px 8px;">
                        L: {{ $chartPayload['gender']['data'][0] ?? 0 }} &bull; P:
                        {{ $chartPayload['gender']['data'][1] ?? 0 }}
                    </span>
                </div>
                <div style="position: relative; height: 200px; width: 100%;">
                    <canvas id="chartWaliGender"></canvas>
                </div>
            </div>

            <!-- Chart 4: Ketepatan Waktu Masuk Siswa -->
            <div class="card" style="margin-bottom: 0; padding: 18px 20px;">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                    <div>
                        <h4
                            style="font-size: 0.94rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-business-time text-warning"></i> Evaluasi Ketepatan Waktu &amp; Disiplin
                        </h4>
                        <span style="font-size: 0.72rem; color: var(--text-muted);">Pola jam masuk gerbang sekolah</span>
                    </div>
                    <span class="badge badge-warning" style="font-size: 0.72rem; padding: 3px 8px;">
                        Batas Masuk: 07:00 WIB
                    </span>
                </div>
                <div style="position: relative; height: 200px; width: 100%;">
                    <canvas id="chartWaliDisiplin"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================= -->
    <!-- DATATABLE 1: LOG AKTIVITAS PESERTA DIDIK PERWALIAN                -->
    <!-- (Login Akun, e-Izin, Unggah Berkas, Usulan Data, Konfirmasi)      -->
    <!-- ================================================================= -->
    <div class="card" style="padding: 18px 20px; border-radius: 14px; margin-bottom: 22px;">
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
            <div>
                <h3
                    style="font-size: 1.02rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-clock-rotate-left text-primary"></i> Log Aktivitas Peserta Didik Perwalian
                </h3>
                <span style="font-size: 0.78rem; color: var(--text-muted);">
                    Rekam jejak login akun siswa, pengajuan e-Izin, unggah berkas PDF, dan usulan data.
                </span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span class="badge badge-outline" id="countAktivitasFilter" style="font-size: 0.74rem;">
                    {{ $logAktivitas->count() }} Aktivitas
                </span>
            </div>
        </div>

        <!-- Toolbar Filter & Live Search Aktivitas -->
        <div
            style="display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center; margin-bottom: 14px; background: var(--bg-hover); padding: 10px 14px; border-radius: 10px; border: 1px solid var(--border-color);">
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <label for="filterKategoriAktivitas"
                    style="font-size: 0.76rem; font-weight: 700; color: var(--text-muted); margin: 0;">
                    Kategori:
                </label>
                <select id="filterKategoriAktivitas" class="form-select form-select-sm"
                    style="font-size: 0.78rem; border-radius: 8px; min-width: 160px; height: 32px;">
                    <option value="">Semua Aktivitas</option>
                    <option value="login">Autentikasi Login (Web/App)</option>
                    <option value="izin">e-Izin &amp; Surat Sakit</option>
                    <option value="izin_keluar">Izin Keluar Kampus</option>
                    <option value="usulan">Usulan Perubahan Data</option>
                    <option value="berkas">Unggah Berkas PDF</option>
                </select>
            </div>

            <div class="live-search-wrap" style="min-width: 220px; margin: 0;">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchAktivitas" placeholder="Cari nama, NISN, atau aktivitas..."
                    autocomplete="off" style="font-size: 0.8rem; height: 32px;">
            </div>
        </div>

        <!-- Tabel Log Aktivitas (Compact & Responsive) -->
        <div class="table-responsive-stack" style="max-height: 420px; overflow-y: auto;">
            <table class="table table-pd table-wali-log"
                style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead style="position: sticky; top: 0; background: var(--card-bg, #1e293b); z-index: 2;">
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <th
                            style="padding: 9px 12px; font-size: 0.74rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 40px; text-align: center;">
                            No</th>
                        <th
                            style="padding: 9px 12px; font-size: 0.74rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 170px;">
                            Waktu</th>
                        <th
                            style="padding: 9px 12px; font-size: 0.74rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Peserta Didik</th>
                        <th
                            style="padding: 9px 12px; font-size: 0.74rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 165px;">
                            Aktivitas</th>
                        <th
                            style="padding: 9px 12px; font-size: 0.74rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Keterangan</th>
                        <th
                            style="padding: 9px 12px; font-size: 0.74rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 95px;">
                            Status</th>
                    </tr>
                </thead>
                <tbody id="tbodyAktivitas">
                    @forelse ($logAktivitas as $idx => $act)
                        <tr class="row-aktivitas" data-kategori="{{ $act->kategori }}"
                            style="border-bottom: 1px solid var(--border-color); font-size: 0.82rem;">
                            <td class="cell-no"
                                style="padding: 8px 12px; text-align: center; color: var(--text-muted); font-weight: 600;">
                                {{ $idx + 1 }}
                            </td>
                            <td class="cell-waktu" style="padding: 8px 12px;">
                                <div class="row-meta-top">
                                    <div style="font-weight: 700; color: var(--text-color); font-size: 0.8rem;">
                                        {{ $act->waktu->translatedFormat('d M Y, H:i') }} WIB
                                    </div>
                                    <div class="d-none-desktop">
                                        {!! $act->status_badge !!}
                                    </div>
                                    <div class="d-none-mobile" style="font-size: 0.7rem; color: var(--text-muted);">
                                        {{ $act->waktu->diffForHumans() }}
                                    </div>
                                </div>
                            </td>
                            <td class="cell-siswa" style="padding: 8px 12px;">
                                <div class="row-siswa">
                                    @if (!empty($act->foto_url))
                                        <div
                                            style="width: 30px; height: 30px; border-radius: 8px; overflow: hidden; border: 1px solid var(--border-color); flex-shrink: 0;">
                                            <img src="{{ $act->foto_url }}" alt="{{ $act->siswa_nama }}"
                                                style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                    @else
                                        <div
                                            style="width: 30px; height: 30px; border-radius: 8px; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 0.82rem; flex-shrink: 0;">
                                            <i class="fas fa-user-graduate"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <div
                                            style="font-weight: 700; color: var(--text-color); font-size: 0.82rem; line-height: 1.25;">
                                            {{ $act->siswa_nama }}
                                        </div>
                                        <div style="font-size: 0.7rem; font-family: monospace; color: var(--text-muted);">
                                            NISN: {{ $act->siswa_nisn }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="cell-aktivitas" style="padding: 8px 12px;">
                                <div class="row-content">
                                    <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                        {!! $act->tipe_badge !!}
                                        <span style="font-size: 0.76rem; font-weight: 600; color: var(--text-color);">
                                            {{ $act->aktivitas }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="cell-keterangan"
                                style="padding: 8px 12px; color: var(--text-muted); font-size: 0.76rem; line-height: 1.35;">
                                <div class="row-desc">{{ $act->keterangan }}</div>
                            </td>
                            <td class="cell-status d-none-mobile" style="padding: 8px 12px; text-align: right;">
                                {!! $act->status_badge !!}
                            </td>
                        </tr>
                    @empty
                        <tr id="emptyAktivitasRow">
                            <td colspan="6" style="padding: 26px; text-align: center; color: var(--text-muted);">
                                <i class="fas fa-clock-rotate-left mb-2"
                                    style="font-size: 1.6rem; opacity: 0.4; display: block;"></i>
                                <div style="font-size: 0.82rem;">Belum ada rekaman aktivitas peserta didik untuk rombel
                                    ini.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ================================================================= -->
    <!-- DATATABLE 2: LOG KHUSUS PRESENSI HARIAN SISWA PERWALIAN          -->
    <!-- (Hadir, Terlambat, Izin, Sakit, Alpha, Metode Presensi)           -->
    <!-- ================================================================= -->
    <div class="card" style="padding: 18px 20px; border-radius: 14px; margin-bottom: 22px;">
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
            <div>
                <h3
                    style="font-size: 1.02rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-calendar-check text-success"></i> Log Khusus Presensi Harian Siswa Perwalian
                </h3>
                <span style="font-size: 0.78rem; color: var(--text-muted);">
                    Rincian presensi kehadiran kelas, jam masuk, jam pulang, ketepatan waktu, dan metode tap gerbang.
                </span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <span class="badge badge-outline" id="countPresensiFilter" style="font-size: 0.74rem;">
                    {{ $logPresensi->count() }} Presensi
                </span>
                <a href="{{ route('dashboard.wali-kelas.presensi.index') }}" class="btn btn-primary"
                    style="padding: 5px 12px; font-size: 0.78rem; font-weight: 700; border-radius: 8px;">
                    <i class="fas fa-pen-to-square me-1"></i> Input Presensi
                </a>
            </div>
        </div>

        <!-- Toolbar Filter & Live Search Presensi -->
        <div
            style="display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center; margin-bottom: 14px; background: var(--bg-hover); padding: 10px 14px; border-radius: 10px; border: 1px solid var(--border-color);">
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <label for="filterStatusPresensi"
                    style="font-size: 0.76rem; font-weight: 700; color: var(--text-muted); margin: 0;">
                    Status:
                </label>
                <select id="filterStatusPresensi" class="form-select form-select-sm"
                    style="font-size: 0.78rem; border-radius: 8px; min-width: 150px; height: 32px;">
                    <option value="">Semua Status</option>
                    <option value="H">Hadir Tepat Waktu</option>
                    <option value="T">Terlambat Masuk</option>
                    <option value="S">Sakit</option>
                    <option value="I">Izin</option>
                    <option value="A">Alpha (Tanpa Keterangan)</option>
                </select>
            </div>

            <div class="live-search-wrap" style="min-width: 220px; margin: 0;">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchPresensi" placeholder="Cari nama, NISN, atau metode..."
                    autocomplete="off" style="font-size: 0.8rem; height: 32px;">
            </div>
        </div>

        <!-- Tabel Log Presensi Harian (Compact & Responsive) -->
        <div class="table-responsive-stack" style="max-height: 420px; overflow-y: auto;">
            <table class="table table-pd table-wali-log"
                style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead style="position: sticky; top: 0; background: var(--card-bg, #1e293b); z-index: 2;">
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <th
                            style="padding: 9px 12px; font-size: 0.74rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 40px; text-align: center;">
                            No</th>
                        <th
                            style="padding: 9px 12px; font-size: 0.74rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px;">
                            Tanggal</th>
                        <th
                            style="padding: 9px 12px; font-size: 0.74rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Peserta Didik</th>
                        <th
                            style="padding: 9px 12px; font-size: 0.74rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 110px;">
                            Status</th>
                        <th
                            style="padding: 9px 12px; font-size: 0.74rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px;">
                            Jam Masuk/Pulang</th>
                        <th
                            style="padding: 9px 12px; font-size: 0.74rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 120px;">
                            Metode</th>
                        <th
                            style="padding: 9px 12px; font-size: 0.74rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Keterangan</th>
                    </tr>
                </thead>
                <tbody id="tbodyPresensi">
                    @forelse ($logPresensi as $idx => $lp)
                        @php
                            $stBadge = match ($lp->status) {
                                'H'
                                    => '<span class="badge badge-success" style="font-size: 0.72rem;"><i class="fas fa-circle-check me-1"></i> Hadir</span>',
                                'T'
                                    => '<span class="badge badge-warning" style="font-size: 0.72rem;"><i class="fas fa-clock me-1"></i> Terlambat</span>',
                                'S'
                                    => '<span class="badge" style="background: rgba(139,92,246,0.15); color: #8b5cf6; font-size: 0.72rem;"><i class="fas fa-notes-medical me-1"></i> Sakit</span>',
                                'I'
                                    => '<span class="badge" style="background: rgba(59,130,246,0.15); color: #3b82f6; font-size: 0.72rem;"><i class="fas fa-file-signature me-1"></i> Izin</span>',
                                'A'
                                    => '<span class="badge badge-danger" style="font-size: 0.72rem;"><i class="fas fa-circle-xmark me-1"></i> Alpha</span>',
                                default => '<span class="badge badge-outline" style="font-size: 0.72rem;">' .
                                    ($lp->status ?: 'Belum') .
                                    '</span>',
                            };
                            $metodeBadge = match ($lp->metode_masuk) {
                                'rfid'
                                    => '<span class="badge" style="background: rgba(99,102,241,0.12); color: var(--primary); font-size: 0.7rem;"><i class="fas fa-id-card me-1"></i> RFID</span>',
                                'kiosk'
                                    => '<span class="badge" style="background: rgba(6,182,212,0.12); color: var(--accent); font-size: 0.7rem;"><i class="fas fa-tablet-screen-button me-1"></i> Kiosk</span>',
                                'face'
                                    => '<span class="badge" style="background: rgba(16,185,129,0.12); color: #10b981; font-size: 0.7rem;"><i class="fas fa-face-smile me-1"></i> Face</span>',
                                'manual'
                                    => '<span class="badge badge-outline" style="font-size: 0.7rem;"><i class="fas fa-chalkboard-user me-1"></i> Manual</span>',
                                default => '<span class="badge badge-outline" style="font-size: 0.7rem;">' .
                                    ($lp->metode_masuk ?: '-') .
                                    '</span>',
                            };
                        @endphp
                        <tr class="row-presensi" data-status="{{ $lp->status }}"
                            style="border-bottom: 1px solid var(--border-color); font-size: 0.82rem;">
                            <td class="cell-no"
                                style="padding: 8px 12px; text-align: center; color: var(--text-muted); font-weight: 600;">
                                {{ $idx + 1 }}
                            </td>
                            <td class="cell-tanggal" style="padding: 8px 12px;">
                                <div class="row-meta-top">
                                    <div style="font-weight: 700; color: var(--text-color); font-size: 0.8rem;">
                                        {{ \Carbon\Carbon::parse($lp->tanggal)->translatedFormat('d M Y') }}
                                        <span
                                            style="font-weight: normal; font-size: 0.7rem; color: var(--text-muted);">({{ \Carbon\Carbon::parse($lp->tanggal)->translatedFormat('l') }})</span>
                                    </div>
                                    <div class="d-none-desktop">
                                        {!! $stBadge !!}
                                    </div>
                                </div>
                            </td>
                            <td class="cell-siswa" style="padding: 8px 12px;">
                                <div class="row-siswa">
                                    @if (!empty($lp->foto_url))
                                        <div
                                            style="width: 30px; height: 30px; border-radius: 8px; overflow: hidden; border: 1px solid var(--border-color); flex-shrink: 0;">
                                            <img src="{{ $lp->foto_url }}" alt="{{ $lp->siswa_nama }}"
                                                style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                    @else
                                        <div
                                            style="width: 30px; height: 30px; border-radius: 8px; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 0.82rem; flex-shrink: 0;">
                                            <i class="fas fa-user-graduate"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <div
                                            style="font-weight: 700; color: var(--text-color); font-size: 0.82rem; line-height: 1.25;">
                                            {{ $lp->siswa_nama }}
                                        </div>
                                        <div style="font-size: 0.7rem; font-family: monospace; color: var(--text-muted);">
                                            NISN: {{ $lp->siswa_nisn }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="cell-status d-none-mobile" style="padding: 8px 12px; text-align: center;">
                                {!! $stBadge !!}
                            </td>
                            <td class="cell-jam" style="padding: 8px 12px;">
                                <div class="row-content">
                                    <div
                                        style="font-family: monospace; font-size: 0.8rem; font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 6px;">
                                        <span><i
                                                class="fas fa-right-to-bracket text-success me-1"></i>{{ $lp->jam_masuk ? substr($lp->jam_masuk, 0, 5) : '-' }}</span>
                                        @if ($lp->jam_pulang)
                                            <span style="color: var(--text-muted);">&bull;</span>
                                            <span><i
                                                    class="fas fa-right-from-bracket text-primary me-1"></i>{{ substr($lp->jam_pulang, 0, 5) }}</span>
                                        @endif
                                    </div>
                                    <div>
                                        @if ($lp->status === 'T')
                                            <span style="color: #f59e0b; font-size: 0.7rem; font-weight: 700;"><i
                                                    class="fas fa-clock me-1"></i>+{{ $lp->menit_terlambat }} mnt</span>
                                        @elseif ($lp->status === 'H')
                                            <span style="color: #10b981; font-size: 0.7rem;"><i
                                                    class="fas fa-check me-1"></i>Tepat</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="cell-metode" style="padding: 8px 12px;">
                                <div class="row-content">
                                    {!! $metodeBadge !!}
                                </div>
                            </td>
                            <td class="cell-keterangan"
                                style="padding: 8px 12px; color: var(--text-muted); font-size: 0.74rem; line-height: 1.35;">
                                <div class="row-desc">
                                    {{ $lp->keterangan ?: 'Presensi harian tercatat' }}
                                    @if ($lp->verified_by)
                                        <span style="opacity: 0.75;"> &bull; {{ $lp->verified_by }}</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr id="emptyPresensiRow">
                            <td colspan="7" style="padding: 26px; text-align: center; color: var(--text-muted);">
                                <i class="fas fa-calendar-xmark mb-2"
                                    style="font-size: 1.6rem; opacity: 0.4; display: block;"></i>
                                <div style="font-size: 0.82rem;">Belum ada catatan log presensi harian untuk siswa di kelas
                                    ini.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- JSON Payload untuk Chart Interaktif Wali Kelas -->
    <script id="waliChartPayload" type="application/json">
        {!! json_encode($chartPayload) !!}
    </script>
@endsection

@push('scripts')
    <script
        src="{{ asset('js/wali-kelas-dashboard.js') }}?v={{ file_exists(public_path('js/wali-kelas-dashboard.js')) ? filemtime(public_path('js/wali-kelas-dashboard.js')) : time() }}">
    </script>
@endpush
