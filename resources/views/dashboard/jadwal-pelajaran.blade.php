@extends('layouts.dashboard')

@section('title', ($isGuru ? 'Jadwal Mengajar' : 'Jadwal Pelajaran') . ' — SAE')
@section('dash_title', $isGuru ? 'Jadwal Mengajar' : 'Jadwal Pelajaran')

@push('styles')
    <style>
        .jadwal-card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .jadwal-item-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 16px 18px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .jadwal-item-card:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
        }

        .jadwal-item-card.is-berlangsung {
            border-color: #10b981;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.06) 0%, var(--bg-card) 100%);
            box-shadow: 0 0 16px rgba(16, 185, 129, 0.2);
        }

        .jadwal-item-card.is-berlangsung::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: #10b981;
        }

        @media (max-width: 640px) {
            .jadwal-card-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .jadwal-item-card {
                padding: 14px 16px;
            }
        }
    </style>
@endpush

@section('content')
    <!-- 1. Header Banner -->
    <div class="dash-banner" style="margin-bottom: 20px;">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-calendar-days text-primary me-2"></i>
                {{ $isGuru ? 'Jadwal Mengajar Guru' : ($isSiswa ? 'Jadwal Pelajaran Kelas' : 'Jadwal Pelajaran & Mengajar') }}
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
                @if ($isSiswa && $studentRombel)
                    Kelas: <strong style="color: var(--text-color);">{{ $studentRombel->nama }}</strong>
                    @if (!empty($studentRombel->ptk_id_str))
                        &bull; Wali Kelas: <strong style="color: var(--primary);">{{ $studentRombel->ptk_id_str }}</strong>
                    @endif
                @elseif ($isGuru)
                    Jadwal tatap muka kegiatan belajar mengajar aktif untuk Anda.
                @else
                    Direktori jadwal pelajaran semester aktif sekolah.
                @endif
            </p>
        </div>
        <div class="dash-banner-actions">
            @if ($isGuru)
                <a href="{{ route('dashboard.presensi-mengajar.index') }}" class="btn btn-outline btn-responsive-icon"
                    style="padding: 9px 16px; font-size: 0.85rem;" title="Presensi Mengajar Guru">
                    <i class="fas fa-calendar-check me-1"></i> <span class="btn-responsive-text">Presensi Mengajar</span>
                </a>
            @endif
        </div>
    </div>

    <!-- 2. Alert Status Jadwal Masih Draft (Jika Belum Diberlakukan) -->
    @if (!$isJadwalDiberlakukan)
        <div class="card"
            style="padding: 14px 18px; margin-bottom: 20px; background: rgba(245, 158, 11, 0.08); border: 1px dashed rgba(245, 158, 11, 0.35); border-radius: 12px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div
                    style="width: 40px; height: 40px; border-radius: 10px; background: rgba(245, 158, 11, 0.18); color: #f59e0b; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <div style="font-weight: 700; color: #f59e0b; font-size: 0.92rem;">Jadwal KBM Masih Berstatus Draft</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">
                        Jadwal pelajaran semester ini masih dalam tahap penyusunan / finalisasi kurikulum dan belum resmi diberlakukan.
                    </div>
                </div>
            </div>
            <span class="badge badge-warning">DRAFT</span>
        </div>
    @endif

    <!-- 3. Stat Grid Baku SAE (4 Kartu Ringkasan) -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: #10b981;">{{ $stats['total_sesi_hari_ini'] }} <span
                        style="font-size: 0.72rem; font-weight: 500;">Sesi</span></div>
                <div class="dash-stat-label">Jadwal Hari Ini ({{ $hariIni }})</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.12); color: var(--primary);">
                <i class="fas fa-calendar-days"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['total_sesi_mingguan'] }} <span
                        style="font-size: 0.72rem; font-weight: 500;">Sesi</span></div>
                <div class="dash-stat-label">Total Sesi Mingguan</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(168, 85, 247, 0.12); color: #a855f7;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['total_jp_mingguan'] }} <span
                        style="font-size: 0.72rem; font-weight: 500;">JP</span></div>
                <div class="dash-stat-label">Total Beban JP</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6, 182, 212, 0.12); color: var(--accent);">
                <i class="fas {{ $isGuru ? 'fa-chalkboard' : 'fa-book' }}"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">
                    {{ $isGuru ? $stats['total_kelas'] : $stats['total_mapel'] }}
                </div>
                <div class="dash-stat-label">{{ $isGuru ? 'Kelas Diampu' : 'Mata Pelajaran' }}</div>
            </div>
        </div>
    </div>

    <!-- 4. Navigasi Tab Hari Baku SAE -->
    <div class="periode-nav-wrapper" style="margin-bottom: 16px;">
        <div class="periode-nav-desktop" style="display: flex; gap: 8px; flex-wrap: wrap;">
            @foreach ($hariList as $h)
                <a href="{{ request()->fullUrlWithQuery(['hari' => $h]) }}"
                    class="btn {{ $selectedHari === $h ? 'btn-primary' : 'btn-outline' }}"
                    style="padding: 8px 14px; font-size: 0.82rem; font-weight: 700; border-radius: 8px;">
                    <i class="fas {{ $hariIni === $h ? 'fa-calendar-check text-success' : 'fa-calendar' }} me-1"></i>
                    {{ $h }}
                    @if ($hariIni === $h)
                        <span class="badge badge-success" style="font-size: 0.65rem; padding: 2px 5px; margin-left: 4px;">Hari Ini</span>
                    @endif
                </a>
            @endforeach
            <a href="{{ request()->fullUrlWithQuery(['hari' => 'semua']) }}"
                class="btn {{ $selectedHari === 'semua' ? 'btn-primary' : 'btn-outline' }}"
                style="padding: 8px 14px; font-size: 0.82rem; font-weight: 700; border-radius: 8px;">
                <i class="fas fa-list-ul me-1"></i> Semua Hari
            </a>
        </div>
    </div>

    <!-- 5. Toolbar Filter & Search -->
    @if ($rombelOptions->isNotEmpty() || request('q'))
        <div class="card" style="padding: 14px 16px; margin-bottom: 20px;">
            <form action="{{ route('dashboard.jadwal-pelajaran.index') }}" method="GET" style="display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap;">
                <input type="hidden" name="hari" value="{{ $selectedHari }}">
                
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; flex: 1;">
                    @if ($rombelOptions->isNotEmpty())
                        <select name="rombel_id" class="toolbar-filter-select" style="min-width: 170px;" onchange="this.form.submit()">
                            <option value="">Semua Kelas</option>
                            @foreach ($rombelOptions as $r)
                                <option value="{{ $r->rombongan_belajar_id }}" {{ request('rombel_id') === $r->rombongan_belajar_id ? 'selected' : '' }}>
                                    {{ $r->nama }}
                                </option>
                            @endforeach
                        </select>
                    @endif

                    @if (request('q') || request('rombel_id'))
                        <a href="{{ route('dashboard.jadwal-pelajaran.index', ['hari' => $selectedHari]) }}"
                            class="btn btn-outline" style="padding: 7px 12px; font-size: 0.82rem;" title="Reset filter">
                            <i class="fas fa-undo me-1"></i> Reset
                        </a>
                    @endif
                </div>

                <div class="live-search-wrap" style="min-width: 220px;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="q" placeholder="Cari mapel atau ruangan..." value="{{ request('q') }}" autocomplete="off">
                    @if (request('q'))
                        <a href="{{ route('dashboard.jadwal-pelajaran.index', ['hari' => $selectedHari, 'rombel_id' => request('rombel_id')]) }}" class="clear-search visible" title="Hapus pencarian">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    @endif

    <!-- 6. Schedule Card Grid (Responsive & Clean) -->
    @if ($schedules->isNotEmpty())
        <div class="jadwal-card-grid">
            @foreach ($schedules as $item)
                @php
                    $isBerlangsung = ($item->status_kbm === 'Berlangsung');
                @endphp
                <div class="jadwal-item-card {{ $isBerlangsung ? 'is-berlangsung' : '' }}">
                    <div>
                        <!-- Header Baris Atas: Jam Ke & Status Live KBM -->
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; gap: 8px;">
                            <span class="badge" style="background: rgba(99, 102, 241, 0.12); color: var(--primary); font-size: 0.76rem; font-weight: 700; padding: 3px 8px; border-radius: 6px;">
                                Jam {{ $item->jam_ke_mulai }}{{ $item->jam_ke_mulai != $item->jam_ke_selesai ? '-' . $item->jam_ke_selesai : '' }}
                                ({{ $item->durasi_jp ?: max(1, $item->jam_ke_selesai - $item->jam_ke_mulai + 1) }} JP)
                            </span>

                            @if ($item->status_kbm === 'Berlangsung')
                                <span class="badge badge-success" style="font-size: 0.72rem; padding: 3px 8px;">
                                    <i class="fas fa-spinner fa-spin me-1"></i> Berlangsung
                                </span>
                            @elseif ($item->status_kbm === 'Selesai')
                                <span class="badge badge-outline" style="font-size: 0.72rem; padding: 3px 8px; color: var(--text-muted);">
                                    <i class="fas fa-check me-1"></i> Selesai
                                </span>
                            @elseif ($item->status_kbm === 'Mendatang')
                                <span class="badge badge-primary" style="font-size: 0.72rem; padding: 3px 8px;">
                                    <i class="far fa-clock me-1"></i> Mendatang
                                </span>
                            @elseif ($selectedHari === 'semua')
                                <span class="badge" style="background: var(--bg-hover); color: var(--text-color); font-size: 0.72rem; padding: 2px 7px;">
                                    {{ $item->hari }}
                                </span>
                            @endif
                        </div>

                        <!-- Nama Mata Pelajaran -->
                        <div style="font-weight: 700; font-size: 1rem; color: var(--text-color); line-height: 1.35; margin-bottom: 6px;">
                            {{ $item->nama_mata_pelajaran }}
                        </div>

                        <!-- Kelas (jika guru) / Guru Pengampu (jika siswa) -->
                        <div style="font-size: 0.82rem; color: var(--text-muted); display: flex; align-items: center; gap: 8px; margin-bottom: 12px; flex-wrap: wrap;">
                            @if ($isGuru)
                                <span style="color: var(--primary); font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="fas fa-chalkboard"></i> {{ $item->nama_rombel }}
                                </span>
                            @else
                                <span style="color: var(--primary); font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="fas fa-chalkboard-user"></i> {{ $item->nama_guru }}
                                </span>
                                @if ($isAdmin)
                                    <span style="color: var(--text-muted);">&bull;</span>
                                    <span>{{ $item->nama_rombel }}</span>
                                @endif
                            @endif
                        </div>
                    </div>

                    <!-- Footer Kartu: Waktu & Lokasi Ruang -->
                    <div style="border-top: 1px solid var(--border-color); padding-top: 10px; display: flex; justify-content: space-between; align-items: center; font-size: 0.78rem; color: var(--text-muted); gap: 6px; flex-wrap: wrap;">
                        <span style="display: inline-flex; align-items: center; gap: 5px;">
                            <i class="far fa-clock text-primary"></i>
                            <strong>{{ $item->jam_waktu_range ?: (!empty($item->jam_mulai) ? substr($item->jam_mulai, 0, 5) . ' - ' . substr($item->jam_selesai, 0, 5) : 'Jam Sesuai KBM') }}</strong>
                        </span>
                        @if ($item->ruangan)
                            <span style="display: inline-flex; align-items: center; gap: 5px;" title="Ruang / Laboratorium">
                                <i class="fas fa-location-dot text-danger"></i>
                                <span>{{ $item->ruangan }}</span>
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- Empty State -->
        <div class="card" style="padding: 48px 20px; text-align: center; border-radius: 14px; margin-bottom: 24px;">
            <div style="width: 60px; height: 60px; border-radius: 50%; background: rgba(99, 102, 241, 0.08); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 14px auto;">
                <i class="fas fa-calendar-xmark"></i>
            </div>
            <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px;">
                Tidak Ada Jadwal Pelajaran
            </h3>
            <p style="color: var(--text-muted); font-size: 0.84rem; max-width: 420px; margin: 0 auto;">
                @if ($selectedHari !== 'semua')
                    Tidak ada jadwal KBM tatap muka untuk hari <strong>{{ $selectedHari }}</strong>. Silakan pilih hari lain pada tab di atas.
                @else
                    Belum ada rekaman jadwal tatap muka KBM yang terdaftar di sistem.
                @endif
            </p>
        </div>
    @endif
@endsection
