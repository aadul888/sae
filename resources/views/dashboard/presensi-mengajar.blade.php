@extends('layouts.dashboard')

@section('title', 'Presensi Mengajar — SAE')
@section('dash_title', 'Presensi Mengajar')

@push('styles')
    <style>
        #modalFormPresensi .modal-card-responsive,
        #modalDetailPresensi .modal-card-responsive {
            max-width: 580px !important;
            border-radius: 14px !important;
            display: flex !important;
            flex-direction: column !important;
            overflow: hidden !important;
            padding: 18px 20px !important;
        }

        #modalFormPresensi .modal-body-scroll,
        #modalDetailPresensi .modal-body-scroll {
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch !important;
            touch-action: pan-y !important;
        }

        #modalFormPresensi .pm-modal-head {
            margin-bottom: 10px !important;
            padding-bottom: 8px !important;
            flex-shrink: 0 !important;
        }

        #modalFormPresensi .pm-field,
        #modalFormPresensi .form-grid-2,
        #modalFormPresensi .form-grid-3 {
            margin-bottom: 10px !important;
        }

        #modalFormPresensi .form-grid-2,
        #modalFormPresensi .form-grid-3 {
            gap: 10px !important;
        }

        #modalFormPresensi label {
            font-size: 0.74rem !important;
            margin-bottom: 4px !important;
        }

        #modalFormPresensi input,
        #modalFormPresensi select {
            height: 36px !important;
            font-size: 0.82rem !important;
            border-radius: 8px !important;
        }

        #modalFormPresensi textarea {
            min-height: 48px !important;
            font-size: 0.82rem !important;
            border-radius: 8px !important;
        }

        #modalFormPresensi #infoKalenderTanggalPresensi,
        #modalFormPresensi #wrapWaktuKbm,
        #modalFormPresensi #wrapGuruPengganti {
            margin-bottom: 10px !important;
            padding: 8px 12px !important;
            font-size: 0.76rem !important;
            border-radius: 8px !important;
        }

        #modalFormPresensi .status-pill-group {
            display: flex !important;
            gap: 6px !important;
            padding: 4px !important;
        }

        #modalFormPresensi .status-pill-item {
            flex: 1 1 0;
            min-width: 0;
            padding: 7px 4px !important;
            font-size: 0.74rem !important;
            gap: 4px !important;
        }

        #modalFormPresensi .pm-actions {
            flex-shrink: 0 !important;
            padding-top: 10px !important;
            margin-top: 8px !important;
        }

        @media (max-width: 520px) {
            #modalFormPresensi,
            #modalDetailPresensi {
                padding: 10px !important;
            }

            #modalFormPresensi .modal-card-responsive,
            #modalDetailPresensi .modal-card-responsive {
                max-height: 90vh !important;
                padding: 14px 14px !important;
                width: 96% !important;
            }

            #modalFormPresensi .form-grid-2,
            #modalFormPresensi .form-grid-3 {
                grid-template-columns: 1fr !important;
            }

            #modalFormPresensi .pm-grid-jam,
            #modalFormPresensi .pm-grid-waktu {
                grid-template-columns: 1fr 1fr !important;
            }
        }
    </style>
@endpush

@section('content')
    <!-- 1. Header Banner & Actions -->
    <div class="dash-banner"
        style="display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div
                style="width: 44px; height: 44px; border-radius: 12px; background: rgba(16,185,129,0.12); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div>
                <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--text-color); margin: 0 0 2px 0;">
                    Presensi Mengajar
                </h2>
                <p style="color: var(--text-muted); font-size: 0.82rem; margin: 0;">
                    Kehadiran KBM terintegrasi Jadwal, Rombel &amp; Mapel.
                </p>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('dashboard.presensi.kelas') }}" class="btn btn-outline"
                style="font-size: 0.8rem; padding: 7px 14px; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-users-viewfinder"></i> Presensi Kelas
            </a>
            <div
                style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: 20px; font-size: 0.78rem; font-weight: 600; color: var(--text-color);">
                <i class="fas fa-calendar-day text-primary"></i>
                <span>{{ $hariIni }}, {{ \Carbon\Carbon::parse($tanggalHariIni)->translatedFormat('d M Y') }}</span>
            </div>
        </div>
    </div>

    <!-- 2. Flash Messages -->
    @if (session('success'))
        <div
            style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: #10b981; padding: 10px 14px; border-radius: 10px; margin-bottom: 16px; font-size: 0.84rem; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-check-circle"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if (session('error'))
        <div
            style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #ef4444; padding: 10px 14px; border-radius: 10px; margin-bottom: 16px; font-size: 0.84rem; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-circle-exclamation"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <!-- 3. Stat Grid Baku SAE (Minimalis & Compact) -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                <i class="fas fa-calendar-days"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: #10b981;">{{ number_format($stats['hari_efektif'] ?? 0) }} <span
                        style="font-size: 0.72rem; font-weight: 500;">Hari</span></div>
                <div class="dash-stat-label">HEB Bulan Ini (Jalan: {{ $stats['hari_efektif_berjalan'] ?? 0 }})</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
                <i class="fas fa-chalkboard-user"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_sesi'] ?? 0) }}</div>
                <div class="dash-stat-label">Total Sesi</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                <i class="fas fa-circle-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_hadir'] ?? 0) }}</div>
                <div class="dash-stat-label">Hadir</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                <i class="fas fa-notes-medical"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_izin'] ?? 0) }}</div>
                <div class="dash-stat-label">Izin / Sakit</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6,182,212,0.12); color: var(--accent);">
                <i class="fas fa-user-clock"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_inval'] ?? 0) }}</div>
                <div class="dash-stat-label">Tugas / Inval</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(168,85,247,0.12); color: #a855f7;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_jp'] ?? 0) }} <span
                        style="font-size: 0.72rem; font-weight: 500;">JP</span></div>
                <div class="dash-stat-label">Beban JP</div>
            </div>
        </div>
    </div>

    @php
        $isHariLibur = ($statusHariIni['is_libur'] ?? false) || ($statusHariIni['libur_gtk'] ?? false);
    @endphp
    @if ($isHariLibur || !empty($agendaHariIni))
        <!-- Banner Peringatan / Info Kalender Pendidikan Hari Ini -->
        <div class="card"
            style="padding: 12px 18px; margin-bottom: 20px; border-radius: 12px; border: 1px solid {{ $isHariLibur ? 'rgba(239,68,68,0.3)' : 'rgba(99,102,241,0.3)' }}; background: {{ $isHariLibur ? 'rgba(239,68,68,0.06)' : 'rgba(99,102,241,0.06)' }}; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div
                    style="width: 38px; height: 38px; border-radius: 10px; background: {{ $isHariLibur ? 'rgba(239,68,68,0.15)' : 'rgba(99,102,241,0.15)' }}; color: {{ $isHariLibur ? '#ef4444' : 'var(--primary)' }}; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;">
                    <i class="fas {{ $isHariLibur ? 'fa-umbrella-beach' : 'fa-calendar-day' }}"></i>
                </div>
                <div>
                    <div style="font-size: 0.88rem; font-weight: 700; color: var(--text-color);">
                        @if ($isHariLibur)
                            <span style="color: #ef4444;">Perhatian: Hari Ini Libur Sekolah</span>
                            <span class="badge"
                                style="background: #ef4444; color: #fff; font-size: 0.7rem; padding: 2px 7px; margin-left: 6px;">KBM
                                Off</span>
                        @else
                            <span style="color: var(--primary);">Agenda Khusus Kalender Pendidikan</span>
                        @endif
                    </div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                        @if (!empty($agendaHariIni))
                            <strong>{{ $agendaHariIni->nama_agenda }}</strong>
                            @if ($agendaHariIni->keterangan)
                                &mdash; {{ $agendaHariIni->keterangan }}
                            @endif
                        @else
                            {{ $statusHariIni['keterangan'] ?? 'Kegiatan KBM reguler disesuaikan dengan kalender pendidikan.' }}
                        @endif
                    </div>
                </div>
            </div>
            <div>
                <a href="{{ route('dashboard.kalender-pendidikan.index') }}" class="btn btn-outline"
                    style="padding: 6px 12px; font-size: 0.8rem;" title="Detail Kalender Pendidikan">
                    <i class="fas fa-calendar-alt"></i>
                </a>
            </div>
        </div>
    @endif

    <!-- 4. Quick Action Widget: Jadwal Mengajar Hari Ini -->
    <div class="card card-widget-kbm"
        style="border-radius: 14px; border: 1px solid var(--border-color); margin-bottom: 20px; background: var(--bg-card); padding: 16px 18px;">
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div
                    style="width: 32px; height: 32px; border-radius: 8px; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 0.95rem;">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div>
                    <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--text-color); margin: 0;">
                        Jadwal Hari Ini ({{ $hariIni }})
                    </h3>
                </div>
            </div>
            <span class="badge"
                style="background: rgba(99,102,241,0.1); color: var(--primary); font-size: 0.75rem; padding: 3px 8px; font-weight: 700;">
                {{ count($jadwalHariIni) }} Sesi
            </span>
        </div>

        @if ($isHariLibur)
            <div
                style="text-align: center; padding: 22px 14px; background: rgba(239,68,68,0.05); border: 1px dashed rgba(239,68,68,0.25); border-radius: 10px; color: var(--text-muted); font-size: 0.84rem;">
                <i class="fas fa-umbrella-beach" style="font-size: 2rem; color: #ef4444; margin-bottom: 8px; display: block; opacity: 0.85;"></i>
                <strong style="color: #ef4444; font-size: 0.92rem; display: block; margin-bottom: 2px;">Hari Ini Libur Sekolah — KBM Reguler Ditiadakan</strong>
                <span>Sesuai Kalender Pendidikan, Anda tidak memiliki jadwal mengajar aktif untuk hari ini.</span>
            </div>
        @elseif (!empty($isGuru) && empty($isJadwalDiberlakukan))
            <div
                style="text-align: center; padding: 22px 14px; background: rgba(245,158,11,0.06); border: 1px dashed rgba(245,158,11,0.3); border-radius: 10px; color: var(--text-muted); font-size: 0.84rem;">
                <i class="fas fa-clock" style="font-size: 2rem; color: #f59e0b; margin-bottom: 8px; display: block; opacity: 0.85;"></i>
                <strong style="color: #f59e0b; font-size: 0.92rem; display: block; margin-bottom: 2px;">Jadwal KBM Masih Berstatus Draft</strong>
                <span>Jadwal KBM semester ini sedang dalam proses penyusunan/finalisasi oleh Tim Kurikulum dan belum resmi diberlakukan.</span>
            </div>
        @elseif (count($jadwalHariIni) > 0)
            <div class="card-kbm-today-grid">
                @foreach ($jadwalHariIni as $j)
                    @php
                        $key = 'jadwal_' . $j->id;
                        $presensiToday = $presensiHariIniKeyed[$key] ?? null;
                        $rombelNama =
                            \Illuminate\Support\Facades\DB::table('rombongan_belajar')
                                ->where('rombongan_belajar_id', $j->rombongan_belajar_id)
                                ->value('nama') ?? $j->rombongan_belajar_id;

                        // Periksa apakah waktu saat ini belum mencapai jam_mulai jadwal KBM
                        $nowTime = \Carbon\Carbon::now()->format('H:i');
                        $jamMulai = !empty($j->jam_mulai) ? substr($j->jam_mulai, 0, 5) : null;
                        $isBelumSaatnya = $jamMulai && ($nowTime < $jamMulai);
                    @endphp
                    <div class="today-kbm-item {{ $presensiToday ? 'done' : '' }}">
                        <div>
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; gap: 6px; width: 100%;">
                                <span class="badge badge-primary"
                                    style="font-size: 0.74rem; font-weight: 700; padding: 2px 7px; flex-shrink: 0;">
                                    <i class="fas fa-chalkboard me-1"></i>{{ $rombelNama }}
                                </span>
                                <span class="badge-jam">
                                    <i class="far fa-clock me-1"></i>Jam {{ $j->jam_ke_mulai }}-{{ $j->jam_ke_selesai }}
                                    ({{ $j->durasi_jp }} JP)
                                </span>
                            </div>

                            <div class="mapel-title" title="{{ $j->nama_mata_pelajaran }}">
                                {{ $j->nama_mata_pelajaran }}
                            </div>

                            <div
                                style="font-size: 0.74rem; color: var(--text-muted); display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                                <span><i class="far fa-clock me-1"></i>{{ $j->jam_waktu_range }}</span>
                                @if ($j->ruangan)
                                    <span><i class="fas fa-location-dot me-1"></i>{{ $j->ruangan }}</span>
                                @endif
                            </div>
                        </div>

                        <div
                            style="border-top: 1px solid var(--border-color); padding-top: 8px; display: flex; justify-content: space-between; align-items: center; gap: 8px; width: 100%;">
                            @if ($presensiToday)
                                <div style="display: flex; align-items: center; gap: 6px; min-width: 0;">
                                    {!! $presensiToday->status_badge !!}
                                    <span style="font-size: 0.72rem; color: var(--text-muted); white-space: nowrap;">
                                        {{ substr($presensiToday->jam_masuk, 0, 5) }}
                                    </span>
                                </div>
                                <button type="button" class="btn btn-outline btn-sm btn-edit-row"
                                    data-id="{{ $presensiToday->id }}" title="Edit Presensi"
                                    style="font-size: 0.74rem; padding: 3px 8px; border-radius: 6px; flex-shrink: 0;">
                                    <i class="fas fa-pen"></i>
                                </button>
                            @elseif ($isBelumSaatnya)
                                <span
                                    style="font-size: 0.74rem; color: var(--text-muted); font-weight: 600; display: inline-flex; align-items: center; gap: 4px; flex-shrink: 0;"
                                    title="Sesi KBM belum dimulai (dimulai pukul {{ $jamMulai }})">
                                    <i class="fas fa-hourglass-start text-muted"></i> Belum Saatnya
                                </span>
                            @else
                                <span
                                    style="font-size: 0.74rem; color: #f59e0b; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; flex-shrink: 0;">
                                    <i class="fas fa-clock"></i> Belum
                                </span>
                                @if ($canCreate)
                                    <button type="button" class="btn btn-primary btn-sm btn-quick-checkin"
                                        data-jadwal-id="{{ $j->id }}"
                                        data-rombel-id="{{ $j->rombongan_belajar_id }}"
                                        data-rombel-nama="{{ $rombelNama }}"
                                        data-mapel="{{ $j->nama_mata_pelajaran }}"
                                        data-pembelajaran-id="{{ $j->pembelajaran_id }}"
                                        data-mapel-id="{{ $j->mata_pelajaran_id }}"
                                        data-jam-mulai="{{ $j->jam_ke_mulai }}"
                                        data-jam-selesai="{{ $j->jam_ke_selesai }}"
                                        data-jam-masuk="{{ !empty($j->jam_mulai) ? substr($j->jam_mulai, 0, 5) : '' }}"
                                        data-jam-keluar="{{ !empty($j->jam_selesai) ? substr($j->jam_selesai, 0, 5) : '' }}"
                                        data-jam-waktu="{{ $j->jam_waktu_range }}" data-durasi-jp="{{ $j->durasi_jp }}"
                                        data-hari="{{ $j->hari }}" data-ptk-id="{{ $j->ptk_id }}"
                                        title="Presensi Sekarang"
                                        style="font-size: 0.8rem; padding: 5px 10px; border-radius: 7px; background: linear-gradient(135deg, #10b981, #059669); font-weight: 600; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; color: #fff; border: none; box-shadow: 0 2px 5px rgba(16,185,129,0.3); cursor: pointer;">
                                        <i class="fas fa-calendar-check"></i>
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div
                style="text-align: center; padding: 18px 12px; background: var(--bg-hover); border-radius: 8px; color: var(--text-muted); font-size: 0.82rem;">
                <i class="fas fa-coffee" style="font-size: 1.5rem; margin-bottom: 6px; display: block; opacity: 0.5;"></i>
                Tidak ada jadwal KBM aktif untuk Anda pada hari {{ $hariIni }}.
            </div>
        @endif
    </div>

    <!-- 5. Toolbar & Filter Presensi Mengajar Baku SAE -->
    <div class="card" style="padding: 16px; margin-bottom: 20px;">
        <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
            <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                <div class="toolbar-entries">
                    <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                    <select id="perPageSelect" class="per-page-select">
                        @foreach ([10, 15, 25, 50, 100] as $n)
                            <option value="{{ $n }}" {{ request('per_page', 15) == $n ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                    <span>entri</span>
                </div>

                <!-- Filter Tanggal -->
                <div class="toolbar-entries">
                    <label style="margin: 0; white-space: nowrap;"><i class="fas fa-calendar-day me-1"></i> Tanggal:</label>
                    <input type="date" id="filterTanggalMulai" value="{{ request('tanggal_mulai') }}" title="Tanggal Mulai" class="form-control" style="font-size: 0.85rem; padding: 7px 10px; border-radius: 8px;">
                    <span style="color: var(--text-muted);">&ndash;</span>
                    <input type="date" id="filterTanggalSelesai" value="{{ request('tanggal_selesai') }}" title="Tanggal Selesai" class="form-control" style="font-size: 0.85rem; padding: 7px 10px; border-radius: 8px;">
                </div>

                <!-- Filter Rombel -->
                <select id="filterRombel" class="toolbar-filter-select" style="min-width: 140px;">
                    <option value="">Semua Kelas</option>
                    @foreach ($rombelList as $r)
                        <option value="{{ $r->rombongan_belajar_id }}"
                            {{ request('rombongan_belajar_id') === $r->rombongan_belajar_id ? 'selected' : '' }}>
                            {{ $r->nama }}
                        </option>
                    @endforeach
                </select>

                <!-- Filter Status -->
                <select id="filterStatus" class="toolbar-filter-select" style="min-width: 130px;">
                    <option value="">Semua Status</option>
                    <option value="H" {{ request('status') === 'H' ? 'selected' : '' }}>Hadir</option>
                    <option value="I" {{ request('status') === 'I' ? 'selected' : '' }}>Izin</option>
                    <option value="S" {{ request('status') === 'S' ? 'selected' : '' }}>Sakit</option>
                    <option value="T" {{ request('status') === 'T' ? 'selected' : '' }}>Tugas</option>
                    <option value="D" {{ request('status') === 'D' ? 'selected' : '' }}>Inval</option>
                </select>

                @if (!$isGuru && count($guruList) > 0)
                    <select id="filterPtk" class="toolbar-filter-select" style="min-width: 140px;">
                        <option value="">Semua Guru</option>
                        @foreach ($guruList as $g)
                            <option value="{{ $g->ptk_id }}" {{ request('filter_ptk_id') === $g->ptk_id ? 'selected' : '' }}>
                                {{ $g->nama }}
                            </option>
                        @endforeach
                    </select>
                @endif

                @if (request('q') || request('tanggal_mulai') || request('tanggal_selesai') || request('rombongan_belajar_id') || request('status') || request('filter_ptk_id'))
                    <button type="button" id="btnResetFilter" class="btn btn-outline btn-responsive-icon" style="padding: 7px 12px; font-size: 0.8rem;" title="Reset filter">
                        <i class="fas fa-undo"></i> <span class="btn-responsive-text">Reset</span>
                    </button>
                @endif
            </div>

            <!-- Live Search Box Baku SAE -->
            <div class="live-search-wrap">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="liveSearchInput" placeholder="Cari mapel, kelas, catatan..." value="{{ request('q') }}" autocomplete="off">
                <button type="button" class="clear-search {{ request('q') ? 'visible' : '' }}" title="Hapus pencarian">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- 6. Datatable Container Baku SAE -->
    <div class="card table-responsive-stack" id="tableDataContainer"
        style="padding: 0; margin-bottom: 24px; border: 1px solid var(--border-color); border-radius: 14px; overflow: hidden;">
        @include('dashboard.presensi-mengajar-table')
    </div>

    <!-- 6. Modal Form Catat Presensi Mengajar (z-index: 99999 !important) -->
    <div id="modalFormPresensi" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px); padding: 12px; box-sizing: border-box; overflow-y: auto;">
        <div class="card modal-card-responsive"
            style="max-width: 580px; width: 96%; max-height: 88vh; display: flex; flex-direction: column; margin: auto; border-radius: 14px; padding: 18px 20px; box-shadow: 0 16px 40px rgba(0,0,0,0.5); border: 1px solid var(--border-color); background: var(--bg-card); box-sizing: border-box; overflow: hidden;">
            <div class="pm-modal-head"
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; flex-shrink: 0;">
                <h3 id="modalPresensiTitle"
                    style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-calendar-check text-primary"></i> Presensi Mengajar
                </h3>
                <button type="button" class="btn-close-modal"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem; padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formPresensiMengajar" style="display: flex; flex-direction: column; flex: 1; min-height: 0; overflow: hidden;">
                @csrf
                <input type="hidden" id="presensiId" name="id">
                <input type="hidden" id="inputJadwalKbmId" name="jadwal_kbm_id">
                <input type="hidden" id="inputPembelajaranId" name="pembelajaran_id">
                <input type="hidden" id="inputMataPelajaranId" name="mata_pelajaran_id">
                <input type="hidden" id="inputPtkId" name="ptk_id" value="{{ $ptkId }}">
                <input type="hidden" id="inputHari" name="hari">

                <!-- Body Modal Scrollable (persis seperti peserta-didik-aktif) -->
                <div class="modal-body-scroll" style="overflow-y: auto; flex: 1; min-height: 0; padding-right: 4px; -webkit-overflow-scrolling: touch;">

                <!-- Selector Sumber Jadwal -->
                <div class="pm-field" style="margin-bottom: 12px;">
                    <label
                        style="font-size: 0.78rem; font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                        <i class="fas fa-calendar-days text-primary"></i> Pilih dari Jadwal Terdaftar
                    </label>
                    <select id="selectJadwalKbm"
                        style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box;">
                        <option value="">-- Pilih Jadwal KBM --</option>
                        @foreach ($jadwalList as $j)
                            <option value="{{ $j['id'] }}" data-rombel-id="{{ $j['rombongan_belajar_id'] }}"
                                data-mapel="{{ $j['nama_mata_pelajaran'] }}"
                                data-pembelajaran-id="{{ $j['pembelajaran_id'] }}"
                                data-mapel-id="{{ $j['mata_pelajaran_id'] }}" data-hari="{{ $j['hari'] }}"
                                data-jam-mulai="{{ $j['jam_ke_mulai'] }}" data-jam-selesai="{{ $j['jam_ke_selesai'] }}"
                                data-jam-masuk="{{ !empty($j['jam_mulai']) ? substr($j['jam_mulai'], 0, 5) : '' }}"
                                data-jam-keluar="{{ !empty($j['jam_selesai']) ? substr($j['jam_selesai'], 0, 5) : '' }}"
                                data-jam-waktu="{{ $j['jam_waktu_range'] ?? '' }}"
                                data-durasi-jp="{{ $j['durasi_jp'] }}" data-ptk-id="{{ $j['ptk_id'] }}">
                                [{{ $j['hari'] }}] {{ $j['rombel_nama'] }} — {{ $j['nama_mata_pelajaran'] }} (Jam
                                {{ $j['jam_ke_mulai'] }}-{{ $j['jam_ke_selesai'] }}{{ !empty($j['jam_waktu_range']) ? ' • ' . $j['jam_waktu_range'] : '' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Alert Info Kalender Pendidikan pada Tanggal Terpilih -->
                <div id="infoKalenderTanggalPresensi"
                    style="display: none; margin-bottom: 12px; padding: 8px 12px; border-radius: 8px; font-size: 0.8rem;">
                </div>

                <div class="form-grid-2">
                    <div>
                        <label
                            style="font-size: 0.78rem; font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                            <i class="fas fa-chalkboard text-primary"></i> Kelas <span style="color: #ef4444;">*</span>
                        </label>
                        <select id="inputRombonganBelajarId" name="rombongan_belajar_id" required
                            style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box;">
                            <option value="">-- Pilih Kelas --</option>
                            @foreach ($rombelList as $r)
                                <option value="{{ $r->rombongan_belajar_id }}">{{ $r->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label
                            style="font-size: 0.78rem; font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                            <i class="fas fa-book text-primary"></i> Mata Pelajaran <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="text" id="inputNamaMataPelajaran" name="nama_mata_pelajaran" required
                            placeholder="Mata Pelajaran..."
                            style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box;">
                    </div>
                </div>

                <div class="form-grid-3 pm-grid-jam">
                    <div>
                        <label
                            style="font-size: 0.78rem; font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                            <i class="fas fa-calendar-day text-primary"></i> Tanggal <span
                                style="color: #ef4444;">*</span>
                        </label>
                        <input type="date" id="inputTanggal" name="tanggal" value="{{ $tanggalHariIni }}" required
                            style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box;">
                    </div>
                    <div>
                        <label
                            style="font-size: 0.74rem; font-weight: 600; color: var(--text-muted); display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                            <span style="display: flex; align-items: center; gap: 4px;">
                                <i class="fas fa-play text-primary"></i> Jam Mulai
                            </span>
                            <span id="badgeAutoJamMulai" style="font-size: 0.68rem; color: #10b981; font-weight: 600;">
                                <i class="fas fa-lock me-1"></i> Otomatis
                            </span>
                        </label>
                        <input type="number" id="inputJamKeMulai" name="jam_ke_mulai" min="0" max="20"
                            value="1" readonly required
                            style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem; font-weight: 700; box-sizing: border-box; cursor: not-allowed;">
                    </div>
                    <div>
                        <label
                            style="font-size: 0.74rem; font-weight: 600; color: var(--text-muted); display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                            <span style="display: flex; align-items: center; gap: 4px;">
                                <i class="fas fa-stop text-primary"></i> Jam Selesai
                            </span>
                            <span id="labelDurasiJp" style="font-size: 0.68rem; color: var(--text-muted);">
                                - JP
                            </span>
                        </label>
                        <input type="number" id="inputJamKeSelesai" name="jam_ke_selesai" min="0"
                            max="20" value="2" readonly required
                            style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem; font-weight: 700; box-sizing: border-box; cursor: not-allowed;">
                    </div>
                </div>

                <!-- Info Banner Waktu KBM Nyata dari Jadwal -->
                <div id="wrapWaktuKbm"
                    style="display: none; margin-bottom: 12px; margin-top: -4px; padding: 7px 12px; border-radius: 8px; background: rgba(99,102,241,0.08); border: 1px dashed rgba(99,102,241,0.3); font-size: 0.75rem; color: var(--text-color); justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <i class="far fa-clock text-primary"></i>
                        <span>Waktu Sesuai Jadwal: <strong id="textWaktuKbm"
                                style="color: var(--primary);">-</strong></span>
                    </div>
                    <span id="badgeHariJadwal" class="badge"
                        style="background: rgba(99,102,241,0.15); color: var(--primary); font-size: 0.7rem; padding: 2px 7px; font-weight: 600;">-</span>
                </div>

                <!-- Pilihan Status Kehadiran (Segmented Pill Buttons Touch-Friendly) -->
                <div class="pm-field" style="margin-bottom: 14px;">
                    <label
                        style="font-size: 0.78rem; font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                        <i class="fas fa-signal text-primary"></i> Status Kehadiran Guru <span
                            style="color: #ef4444;">*</span>
                    </label>
                    <div class="status-pill-group">
                        <label class="status-pill-item active" data-status="H">
                            <input type="radio" name="status" value="H" checked>
                            <i class="fas fa-check-circle" style="color: #10b981;"></i>
                            <span>Hadir</span>
                        </label>
                        <label class="status-pill-item" data-status="I">
                            <input type="radio" name="status" value="I">
                            <i class="fas fa-file-signature" style="color: var(--primary);"></i>
                            <span>Izin</span>
                        </label>
                        <label class="status-pill-item" data-status="S">
                            <input type="radio" name="status" value="S">
                            <i class="fas fa-notes-medical" style="color: #f59e0b;"></i>
                            <span>Sakit</span>
                        </label>
                        <label class="status-pill-item" data-status="T">
                            <input type="radio" name="status" value="T">
                            <i class="fas fa-briefcase" style="color: #64748b;"></i>
                            <span>Tugas</span>
                        </label>
                        <label class="status-pill-item" data-status="D">
                            <input type="radio" name="status" value="D">
                            <i class="fas fa-user-clock" style="color: #ef4444;"></i>
                            <span>Inval</span>
                        </label>
                    </div>
                </div>

                <!-- Input Guru Pengganti (Muncul jika status Inval) -->
                <div id="wrapGuruPengganti"
                    style="display: none; margin-bottom: 12px; padding: 10px 12px; background: rgba(239,68,68,0.06); border: 1px solid rgba(239,68,68,0.2); border-radius: 8px;">
                    <label
                        style="font-size: 0.78rem; font-weight: 600; color: #ef4444; display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                        <i class="fas fa-user-clock"></i> Nama Guru Pengganti (Inval) <span
                            style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" id="inputNamaGuruPengganti" name="nama_guru_pengganti"
                        placeholder="Ketik nama guru pengganti..."
                        style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box;">
                </div>

                <!-- Jam Masuk & Jam Selesai Sesuai Jadwal (Grid 2 Kolom) -->
                <div class="form-grid-2 pm-grid-waktu" style="margin-bottom: 12px;">
                    <div>
                        <label
                            style="font-size: 0.74rem; font-weight: 600; color: var(--text-color); display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                            <span style="display: flex; align-items: center; gap: 4px;">
                                <i class="far fa-clock text-primary"></i> Jam Masuk
                            </span>
                            <span id="badgeAutoJamMasuk" style="font-size: 0.68rem; color: #10b981; font-weight: 600;">
                                <i class="fas fa-check-double me-1"></i> Sesuai Jadwal
                            </span>
                        </label>
                        <input type="time" id="inputJamMasuk" name="jam_masuk" value="{{ date('H:i') }}"
                            style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem; font-weight: 600; box-sizing: border-box;">
                    </div>
                    <div>
                        <label
                            style="font-size: 0.74rem; font-weight: 600; color: var(--text-color); display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                            <span style="display: flex; align-items: center; gap: 4px;">
                                <i class="far fa-clock text-primary"></i> Jam Selesai
                            </span>
                            <span id="badgeAutoJamKeluar" style="font-size: 0.68rem; color: #10b981; font-weight: 600;">
                                <i class="fas fa-check-double me-1"></i> Sesuai Jadwal
                            </span>
                        </label>
                        <input type="time" id="inputJamKeluar" name="jam_keluar"
                            style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem; font-weight: 600; box-sizing: border-box;">
                    </div>
                </div>

                <div class="pm-field" style="margin-bottom: 16px;">
                    <label
                        style="font-size: 0.78rem; font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                        <i class="fas fa-pen-fancy text-primary"></i> Catatan Sesi KBM
                    </label>
                    <textarea id="inputKeterangan" name="keterangan" rows="2"
                        placeholder="Catatan opsional KBM, topik bahasan, kendala teknis..."
                        style="width: 100%; padding: 8px 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box; resize: vertical;"></textarea>
                </div>

                </div>

                <!-- Footer Modal (tetap di dasar modal) -->
                <div class="pm-actions"
                    style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 12px; margin-top: 8px; flex-shrink: 0;">
                    <button type="button" class="btn btn-outline btn-close-modal"
                        style="padding: 7px 14px; font-size: 0.82rem; border-radius: 8px;">
                        Batal
                    </button>
                    <button type="submit" id="btnSavePresensi" class="btn btn-primary"
                        style="padding: 7px 18px; font-size: 0.82rem; border-radius: 8px; font-weight: 600; background: linear-gradient(135deg, #10b981, #059669);">
                        <i class="fas fa-check me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 7. Modal Detail Presensi Mengajar -->
    <div id="modalDetailPresensi" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px); padding: 12px; box-sizing: border-box; overflow-y: auto;">
        <div class="card modal-card-responsive"
            style="max-width: 520px; width: 96%; max-height: 88vh; display: flex; flex-direction: column; margin: auto; border-radius: 14px; padding: 18px 20px; box-shadow: 0 16px 40px rgba(0,0,0,0.5); border: 1px solid var(--border-color); background: var(--bg-card); box-sizing: border-box; overflow: hidden;">
            <div class="pm-modal-head"
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; flex-shrink: 0;">
                <h3
                    style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-circle-info text-primary"></i> Rincian Presensi
                </h3>
                <button type="button" class="btn-close-detail"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem; padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body-scroll" style="overflow-y: auto; flex: 1; min-height: 0; padding-right: 4px; -webkit-overflow-scrolling: touch;">
                <div id="detailPresensiContent" style="font-size: 0.84rem; display: flex; flex-direction: column; gap: 8px;">
                    <!-- Konten dinamis via JS -->
                </div>
            </div>

            <div
                style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 12px; margin-top: 10px; flex-shrink: 0;">
                <button type="button" class="btn btn-outline btn-close-detail"
                    style="padding: 7px 16px; font-size: 0.82rem; border-radius: 8px;">
                    Tutup
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/presensi-mengajar.js') }}"></script>
@endpush
