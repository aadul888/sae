@extends('layouts.dashboard')

@section('title', 'Dashboard Guru Piket — SAE')
@section('dash_title', 'Dashboard Guru Piket')

@section('content')
    <!-- Banner Header Piket -->
    <div class="dash-banner" style="margin-bottom: 24px; padding: 22px 26px; border-radius: 16px; background: linear-gradient(135deg, rgba(139, 92, 246, 0.15) 0%, rgba(59, 130, 246, 0.08) 100%); border: 1px solid rgba(139, 92, 246, 0.3);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
            <div style="display: flex; align-items: flex-start; gap: 16px; flex: 1; min-width: 280px;">
                <div style="width: 52px; height: 52px; border-radius: 14px; background: rgba(139, 92, 246, 0.2); color: #8b5cf6; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; box-shadow: 0 4px 14px rgba(139, 92, 246, 0.2);">
                    <i class="fas fa-clipboard-user"></i>
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px;">
                        <span class="badge badge-primary" style="font-size: 0.72rem; padding: 3px 8px; font-weight: 700; background: #8b5cf6; border-color: #8b5cf6;">
                            GURU PIKET AKTIF
                        </span>
                        <span class="badge badge-outline" style="font-size: 0.72rem; padding: 3px 8px;">
                            {{ date('l, d F Y') }}
                        </span>
                    </div>
                    <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--text-color); margin: 0 0 6px 0;">
                        Portal Operasional Guru Piket
                    </h2>
                    <p style="color: var(--text-muted); font-size: 0.84rem; margin: 0; line-height: 1.5;">
                        Monitoring ketertiban KBM harian, verifikasi izin keluar-masuk siswa, pencatatan presensi guru mengajar, dan pengisian jurnal piket.
                    </p>
                </div>
            </div>

            <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                <a href="{{ route('dashboard.guru') }}" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.82rem; border-radius: 8px;">
                    <i class="fas fa-arrow-left me-1"></i> Dashboard Pokok
                </a>
                <a href="{{ route('dashboard.piket.izin.index') }}" class="btn btn-primary" style="padding: 8px 18px; font-size: 0.82rem; font-weight: 700; border-radius: 8px; background: #8b5cf6; border-color: #8b5cf6;">
                    <i class="fas fa-ticket-alt me-1"></i> Buat Surat Izin Siswa
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Shortcuts Modul Guru Piket -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; margin-bottom: 26px;">
        <a href="{{ route('dashboard.piket.izin.index') }}" class="card" style="padding: 16px 20px; border-radius: 12px; margin: 0; text-decoration: none; color: inherit; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 14px; transition: all 0.2s ease;">
            <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(139, 92, 246, 0.15); color: #8b5cf6; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <div>
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color);">e-Izin Keluar/Masuk</div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">Penerbitan dispensasi siswa</div>
            </div>
        </a>

        <a href="{{ route('dashboard.piket.jurnal.index') }}" class="card" style="padding: 16px 20px; border-radius: 12px; margin: 0; text-decoration: none; color: inherit; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 14px; transition: all 0.2s ease;">
            <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(59, 130, 246, 0.15); color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div>
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color);">Jurnal Guru Piket</div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">Catatan kejadian &amp; KBM</div>
            </div>
        </a>

        <a href="{{ route('dashboard.presensi-mengajar.index') }}" class="card" style="padding: 16px 20px; border-radius: 12px; margin: 0; text-decoration: none; color: inherit; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 14px; transition: all 0.2s ease;">
            <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(16, 185, 129, 0.15); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div>
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color);">Presensi Guru Mengajar</div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">Monitoring kehadiran KBM</div>
            </div>
        </a>
    </div>

    <!-- Jadwal KBM Hari Ini -->
    <div class="card" style="padding: 20px 24px; border-radius: 12px; margin-bottom: 24px;">
        <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0 0 14px 0;">
            <i class="fas fa-calendar-day text-primary me-2"></i> Jadwal KBM Terjadwal
        </h3>
        <div class="table-responsive-stack">
            <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 10px 14px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Mata Pelajaran</th>
                        <th style="padding: 10px 14px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Kelas / Rombel</th>
                        <th style="padding: 10px 14px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Guru Pengampu</th>
                        <th style="padding: 10px 14px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Beban Jam</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($jadwalHariIni as $j)
                        <tr style="border-bottom: 1px solid var(--border-color); font-size: 0.84rem;">
                            <td style="padding: 10px 14px; font-weight: 700; color: var(--text-color);">{{ $j->nama_mata_pelajaran }}</td>
                            <td style="padding: 10px 14px;"><span class="badge badge-accent">{{ $j->rombel_nama }}</span></td>
                            <td style="padding: 10px 14px; color: var(--text-color);">{{ $j->guru_nama ?: '-' }}</td>
                            <td style="padding: 10px 14px; text-align: center; font-weight: 700;">{{ $j->jam_mengajar_per_minggu }} JP</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="padding: 24px; text-align: center; color: var(--text-muted);">
                                Belum ada data pembelajaran tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
