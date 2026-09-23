@extends('layouts.dashboard')

@section('title', 'Dashboard Wali Kelas ' . ($rombel->nama ?? '') . ' — SAE')
@section('dash_title', 'Dashboard Wali Kelas')

@section('content')
    <!-- Banner Header Wali Kelas -->
    <div class="dash-banner" style="margin-bottom: 24px; padding: 22px 26px; border-radius: 16px; background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(16, 185, 129, 0.08) 100%); border: 1px solid rgba(99, 102, 241, 0.3);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
            <div style="display: flex; align-items: flex-start; gap: 16px; flex: 1; min-width: 280px;">
                <div style="width: 52px; height: 52px; border-radius: 14px; background: rgba(99, 102, 241, 0.2); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; box-shadow: 0 4px 14px rgba(99, 102, 241, 0.2);">
                    <i class="fas fa-chalkboard-user"></i>
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px;">
                        <span class="badge badge-primary" style="font-size: 0.72rem; padding: 3px 8px; font-weight: 700;">
                            WALI KELAS AKTIF
                        </span>
                        @if ($rombel)
                            <span class="badge badge-accent" style="font-size: 0.72rem; padding: 3px 8px; font-weight: 700;">
                                KELAS {{ $rombel->nama }}
                            </span>
                        @endif
                        <span class="badge badge-outline" style="font-size: 0.72rem; padding: 3px 8px;">
                            TA. {{ \App\Support\SemesterHelper::getActiveSemesterLabel() }}
                        </span>
                    </div>
                    <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--text-color); margin: 0 0 6px 0;">
                        Perwalian Kelas: {{ $rombel->nama ?? 'Belum Ditentukan' }}
                    </h2>
                    <p style="color: var(--text-muted); font-size: 0.84rem; margin: 0; line-height: 1.5;">
                        Kelola administrasi, rekap presensi, biodata peserta didik, serta pantau kedisiplinan siswa di kelas perwalian Anda secara langsung.
                    </p>
                </div>
            </div>

            <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                <a href="{{ route('dashboard.guru') }}" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.82rem; border-radius: 8px;">
                    <i class="fas fa-arrow-left me-1"></i> Dashboard Guru
                </a>
                <a href="{{ route('dashboard.wali-kelas.presensi.index') }}" class="btn btn-primary" style="padding: 8px 18px; font-size: 0.82rem; font-weight: 700; border-radius: 8px;">
                    <i class="fas fa-calendar-check me-1"></i> Input Presensi Kelas
                </a>
            </div>
        </div>
    </div>

    <!-- Stat Cards Perwalian -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 24px;">
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
                <div class="dash-stat-value" style="color: #10b981;">{{ $presensiHariIni['H'] ?? 0 }}</div>
                <div class="dash-stat-label">Hadir Hari Ini</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                <i class="fas fa-envelope-open-text"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: #f59e0b;">{{ ($presensiHariIni['S'] ?? 0) + ($presensiHariIni['I'] ?? 0) }}</div>
                <div class="dash-stat-label">Sakit / Izin</div>
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
    </div>

    <!-- Quick Shortcuts Modul Wali Kelas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; margin-bottom: 26px;">
        <a href="{{ route('dashboard.wali-kelas.peserta-didik-aktif.index') }}" class="card" style="padding: 16px 20px; border-radius: 12px; margin: 0; text-decoration: none; color: inherit; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 14px; transition: all 0.2s ease;">
            <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(16, 185, 129, 0.15); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                <i class="fas fa-user-check"></i>
            </div>
            <div>
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color);">Siswa Aktif</div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">Data induk perwalian</div>
            </div>
        </a>

        <a href="{{ route('dashboard.wali-kelas.presensi.index') }}" class="card" style="padding: 16px 20px; border-radius: 12px; margin: 0; text-decoration: none; color: inherit; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 14px; transition: all 0.2s ease;">
            <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(99, 102, 241, 0.15); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                <i class="fas fa-clipboard-user"></i>
            </div>
            <div>
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color);">Presensi Kelas</div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">Rekap &amp; cetak kehadiran</div>
            </div>
        </a>

        <a href="{{ route('dashboard.wali-kelas.peserta-didik-tidak-aktif.index') }}" class="card" style="padding: 16px 20px; border-radius: 12px; margin: 0; text-decoration: none; color: inherit; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 14px; transition: all 0.2s ease;">
            <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(239, 68, 68, 0.15); color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                <i class="fas fa-user-slash"></i>
            </div>
            <div>
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color);">Siswa Mutasi / Keluar</div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">Arsip peserta didik non-aktif</div>
            </div>
        </a>

        <a href="{{ route('dashboard.peserta-didik.izin.index') }}" class="card" style="padding: 16px 20px; border-radius: 12px; margin: 0; text-decoration: none; color: inherit; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 14px; transition: all 0.2s ease;">
            <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(245, 158, 11, 0.15); color: #f59e0b; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <div>
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color);">e-Izin Siswa</div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">Verifikasi izin &amp; sakit</div>
            </div>
        </a>
    </div>

    <!-- Tabel Daftar Siswa Kelas Perwalian -->
    <div class="card" style="padding: 20px 24px; border-radius: 12px; margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    <i class="fas fa-list-check text-primary me-2"></i> Daftar Peserta Didik Perwalian
                </h3>
                <span style="font-size: 0.8rem; color: var(--text-muted);">
                    Rombel: {{ $rombel->nama ?? 'Belum ada rombel' }} &bull; {{ $totalSiswa }} Siswa
                </span>
            </div>
            <a href="{{ route('dashboard.wali-kelas.peserta-didik-aktif.index') }}" class="btn btn-outline" style="padding: 6px 14px; font-size: 0.8rem;">
                Lihat Lengkap &amp; Cetak <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="table-responsive-stack" style="max-height: 420px; overflow-y: auto;">
            <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead style="position: sticky; top: 0; background: var(--card-bg, #1e293b); z-index: 2;">
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 10px 14px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 45px; text-align: center;">No</th>
                        <th style="padding: 10px 14px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Siswa</th>
                        <th style="padding: 10px 14px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px;">NISN / NIPD</th>
                        <th style="padding: 10px 14px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 80px; text-align: center;">L/P</th>
                        <th style="padding: 10px 14px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($siswaList as $idx => $s)
                        <tr style="border-bottom: 1px solid var(--border-color); font-size: 0.84rem;">
                            <td style="padding: 10px 14px; text-align: center; color: var(--text-muted); font-weight: 600;">{{ $idx + 1 }}</td>
                            <td style="padding: 10px 14px; font-weight: 700; color: var(--text-color);">{{ $s->nama }}</td>
                            <td style="padding: 10px 14px; font-family: monospace; color: var(--text-muted); font-size: 0.8rem;">
                                {{ $s->nisn ?: '-' }} / {{ $s->nipd ?: '-' }}
                            </td>
                            <td style="padding: 10px 14px; text-align: center;">
                                <span class="badge {{ $s->jenis_kelamin === 'L' ? 'badge-primary' : 'badge-accent' }}" style="font-size: 0.7rem; padding: 2px 6px;">
                                    {{ $s->jenis_kelamin }}
                                </span>
                            </td>
                            <td style="padding: 10px 14px; text-align: right;">
                                <a href="{{ route('dashboard.wali-kelas.presensi.index') }}" class="btn btn-outline" style="padding: 4px 8px; font-size: 0.72rem;">
                                    Presensi
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                Belum ada data peserta didik di rombel perwalian ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
