@extends('layouts.dashboard')

@section('title', 'Presensi Mengajar — SAE')
@section('dash_title', 'Presensi Mengajar')

@section('content')
    <!-- 1. Header Banner & Actions -->
    <div class="dash-banner" style="display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(16,185,129,0.12); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
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
            <div style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: 20px; font-size: 0.78rem; font-weight: 600; color: var(--text-color);">
                <i class="fas fa-calendar-day text-primary"></i>
                <span>{{ $hariIni }}, {{ \Carbon\Carbon::parse($tanggalHariIni)->translatedFormat('d M Y') }}</span>
            </div>

            @if ($canCreate)
                <button type="button" class="btn btn-primary btn-responsive-icon" style="padding: 8px 14px; font-size: 0.84rem; border-radius: 8px; font-weight: 600; background: linear-gradient(135deg, #10b981, #059669);" id="btnOpenCreateModal" title="Catat Presensi Mengajar">
                    <i class="fas fa-calendar-plus"></i>
                    <span class="btn-responsive-text">Catat Presensi</span>
                </button>
            @endif
        </div>
    </div>

    <!-- 2. Flash Messages -->
    @if (session('success'))
        <div style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: #10b981; padding: 10px 14px; border-radius: 10px; margin-bottom: 16px; font-size: 0.84rem; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-check-circle"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if (session('error'))
        <div style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #ef4444; padding: 10px 14px; border-radius: 10px; margin-bottom: 16px; font-size: 0.84rem; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-circle-exclamation"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <!-- 3. Stat Grid Baku SAE (Minimalis & Compact) -->
    <div class="dash-stat-grid" style="margin-bottom: 20px;">
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
                <div class="dash-stat-value">{{ number_format($stats['total_jp'] ?? 0) }} <span style="font-size: 0.72rem; font-weight: 500;">JP</span></div>
                <div class="dash-stat-label">Beban JP</div>
            </div>
        </div>
    </div>

    <!-- 4. Quick Action Widget: Jadwal Mengajar Hari Ini -->
    <div class="card card-widget-kbm" style="border-radius: 14px; border: 1px solid var(--border-color); margin-bottom: 20px; background: var(--bg-card); padding: 16px 18px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 0.95rem;">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div>
                    <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--text-color); margin: 0;">
                        Jadwal Hari Ini ({{ $hariIni }})
                    </h3>
                </div>
            </div>
            <span class="badge" style="background: rgba(99,102,241,0.1); color: var(--primary); font-size: 0.75rem; padding: 3px 8px; font-weight: 700;">
                {{ count($jadwalHariIni) }} Sesi
            </span>
        </div>

        @if (count($jadwalHariIni) > 0)
            <div class="card-kbm-today-grid">
                @foreach ($jadwalHariIni as $j)
                    @php
                        $key = 'jadwal_' . $j->id;
                        $presensiToday = $presensiHariIniKeyed[$key] ?? null;
                        $rombelNama = \Illuminate\Support\Facades\DB::table('rombongan_belajar')->where('rombongan_belajar_id', $j->rombongan_belajar_id)->value('nama') ?? $j->rombongan_belajar_id;
                    @endphp
                    <div class="today-kbm-item {{ $presensiToday ? 'done' : '' }}">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; gap: 6px; width: 100%;">
                                <span class="badge badge-primary" style="font-size: 0.74rem; font-weight: 700; padding: 2px 7px; flex-shrink: 0;">
                                    <i class="fas fa-chalkboard me-1"></i>{{ $rombelNama }}
                                </span>
                                <span class="badge-jam">
                                    <i class="far fa-clock me-1"></i>Jam {{ $j->jam_ke_mulai }}-{{ $j->jam_ke_selesai }} ({{ $j->durasi_jp }} JP)
                                </span>
                            </div>

                            <div class="mapel-title" title="{{ $j->nama_mata_pelajaran }}">
                                {{ $j->nama_mata_pelajaran }}
                            </div>

                            <div style="font-size: 0.74rem; color: var(--text-muted); display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                                <span><i class="far fa-clock me-1"></i>{{ $j->jam_waktu_range }}</span>
                                @if ($j->ruangan)
                                    <span><i class="fas fa-location-dot me-1"></i>{{ $j->ruangan }}</span>
                                @endif
                            </div>
                        </div>

                        <div style="border-top: 1px solid var(--border-color); padding-top: 8px; display: flex; justify-content: space-between; align-items: center; gap: 8px; width: 100%;">
                            @if ($presensiToday)
                                <div style="display: flex; align-items: center; gap: 6px; min-width: 0;">
                                    {!! $presensiToday->status_badge !!}
                                    <span style="font-size: 0.72rem; color: var(--text-muted); white-space: nowrap;">
                                        {{ substr($presensiToday->jam_masuk, 0, 5) }}
                                    </span>
                                </div>
                                <button type="button" class="btn btn-outline btn-sm btn-edit-row" data-id="{{ $presensiToday->id }}" title="Edit Presensi" style="font-size: 0.74rem; padding: 3px 8px; border-radius: 6px; flex-shrink: 0;">
                                    <i class="fas fa-pen"></i>
                                </button>
                            @else
                                <span style="font-size: 0.74rem; color: #f59e0b; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; flex-shrink: 0;">
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
                                        data-ptk-id="{{ $j->ptk_id }}"
                                        title="Presensi Sekarang"
                                        style="font-size: 0.76rem; padding: 5px 12px; border-radius: 7px; background: linear-gradient(135deg, #10b981, #059669); font-weight: 600; flex-shrink: 0; display: inline-flex; align-items: center; gap: 6px; color: #fff; border: none; box-shadow: 0 2px 5px rgba(16,185,129,0.3); cursor: pointer;">
                                        <i class="fas fa-calendar-check"></i>
                                        <span>Presensi</span>
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div style="text-align: center; padding: 18px 12px; background: var(--bg-hover); border-radius: 8px; color: var(--text-muted); font-size: 0.82rem;">
                <i class="fas fa-coffee" style="font-size: 1.5rem; margin-bottom: 6px; display: block; opacity: 0.5;"></i>
                Tidak ada jadwal KBM hari {{ $hariIni }}. Gunakan tombol tambah untuk KBM jam tambahan.
            </div>
        @endif
    </div>

    <!-- 5. Main Card Datatable Riwayat Presensi -->
    <div class="card" style="padding: 16px 18px; border-radius: 14px; border: 1px solid var(--border-color); margin-bottom: 20px;">
        <!-- Toolbar Filter & Search Responsif -->
        <div class="toolbar-row" style="display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 14px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 220px;">
                <div class="live-search-wrap" style="width: 100%;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="liveSearchInput" placeholder="Cari mapel, kelas, catatan..." value="{{ request('q') }}" autocomplete="off">
                    <button type="button" class="clear-search" title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                <!-- Filter Tanggal -->
                <input type="date" id="filterTanggalMulai" value="{{ request('tanggal_mulai') }}" title="Tanggal Mulai" class="toolbar-filter-select"
                    style="height: 36px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.78rem;">
                <input type="date" id="filterTanggalSelesai" value="{{ request('tanggal_selesai') }}" title="Tanggal Selesai" class="toolbar-filter-select"
                    style="height: 36px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.78rem;">

                <!-- Filter Rombel -->
                <select id="filterRombel" class="toolbar-filter-select" style="height: 36px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.8rem;">
                    <option value="">Semua Kelas</option>
                    @foreach ($rombelList as $r)
                        <option value="{{ $r->rombongan_belajar_id }}" {{ request('rombongan_belajar_id') === $r->rombongan_belajar_id ? 'selected' : '' }}>
                            {{ $r->nama }}
                        </option>
                    @endforeach
                </select>

                <!-- Filter Status -->
                <select id="filterStatus" class="toolbar-filter-select" style="height: 36px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.8rem;">
                    <option value="">Semua Status</option>
                    <option value="H" {{ request('status') === 'H' ? 'selected' : '' }}>Hadir</option>
                    <option value="I" {{ request('status') === 'I' ? 'selected' : '' }}>Izin</option>
                    <option value="S" {{ request('status') === 'S' ? 'selected' : '' }}>Sakit</option>
                    <option value="T" {{ request('status') === 'T' ? 'selected' : '' }}>Tugas</option>
                    <option value="D" {{ request('status') === 'D' ? 'selected' : '' }}>Inval</option>
                </select>

                @if (!$isGuru && count($guruList) > 0)
                    <select id="filterPtk" class="toolbar-filter-select" style="height: 36px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.8rem; max-width: 150px;">
                        <option value="">Semua Guru</option>
                        @foreach ($guruList as $g)
                            <option value="{{ $g->ptk_id }}" {{ request('filter_ptk_id') === $g->ptk_id ? 'selected' : '' }}>
                                {{ $g->nama }}
                            </option>
                        @endforeach
                    </select>
                @endif

                <!-- Per Page -->
                <select id="perPageSelect" class="per-page-select" style="height: 36px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.8rem;">
                    <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                    <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                    <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                </select>

                <button type="button" id="btnResetFilter" class="btn btn-outline" title="Reset Filter" style="height: 36px; width: 36px; padding: 0; font-size: 0.8rem; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="fas fa-rotate-left"></i>
                </button>
            </div>
        </div>

        <!-- Datatable Container Baku SAE -->
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 0; border: 1px solid var(--border-color); overflow: hidden; border-radius: 10px;">
            @include('dashboard.presensi-mengajar-table')
        </div>
    </div>

    <!-- 6. Modal Form Catat Presensi Mengajar (z-index: 99999 !important) -->
    <div id="modalFormPresensi" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px); padding: 12px; overflow-y: auto;">
        <div class="card modal-card-responsive" style="max-width: 600px; width: 100%; max-height: 92vh; overflow-y: auto; margin: auto; border-radius: 14px; padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); border: 1px solid var(--border-color); background: var(--bg-card);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <h3 id="modalPresensiTitle" style="font-size: 1rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-calendar-check text-primary"></i> Presensi Mengajar
                </h3>
                <button type="button" class="btn-close-modal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem; padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formPresensiMengajar">
                @csrf
                <input type="hidden" id="presensiId" name="id">
                <input type="hidden" id="inputJadwalKbmId" name="jadwal_kbm_id">
                <input type="hidden" id="inputPembelajaranId" name="pembelajaran_id">
                <input type="hidden" id="inputMataPelajaranId" name="mata_pelajaran_id">
                <input type="hidden" id="inputPtkId" name="ptk_id" value="{{ $ptkId }}">

                <!-- Selector Sumber Jadwal -->
                <div style="margin-bottom: 12px;">
                    <label style="font-size: 0.78rem; font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                        <i class="fas fa-calendar-days text-primary"></i> Pilih dari Jadwal Terdaftar
                    </label>
                    <select id="selectJadwalKbm" style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box;">
                        <option value="">-- Pilih Slot Jadwal KBM --</option>
                        @foreach ($jadwalList as $j)
                            <option value="{{ $j['id'] }}"
                                data-rombel-id="{{ $j['rombongan_belajar_id'] }}"
                                data-mapel="{{ $j['nama_mata_pelajaran'] }}"
                                data-pembelajaran-id="{{ $j['pembelajaran_id'] }}"
                                data-mapel-id="{{ $j['mata_pelajaran_id'] }}"
                                data-hari="{{ $j['hari'] }}"
                                data-jam-mulai="{{ $j['jam_ke_mulai'] }}"
                                data-jam-selesai="{{ $j['jam_ke_selesai'] }}"
                                data-ptk-id="{{ $j['ptk_id'] }}">
                                [{{ $j['hari'] }}] {{ $j['rombel_nama'] }} — {{ $j['nama_mata_pelajaran'] }} (Jam {{ $j['jam_ke_mulai'] }}-{{ $j['jam_ke_selesai'] }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-grid-2">
                    <div>
                        <label style="font-size: 0.78rem; font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
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
                        <label style="font-size: 0.78rem; font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                            <i class="fas fa-book text-primary"></i> Mata Pelajaran <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="text" id="inputNamaMataPelajaran" name="nama_mata_pelajaran" required placeholder="Mata Pelajaran..."
                            style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box;">
                    </div>
                </div>

                <div class="form-grid-3">
                    <div>
                        <label style="font-size: 0.78rem; font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                            <i class="fas fa-calendar-day text-primary"></i> Tanggal <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="date" id="inputTanggal" name="tanggal" value="{{ $tanggalHariIni }}" required
                            style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="font-size: 0.78rem; font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                            <i class="fas fa-play text-primary"></i> Jam Mulai <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="number" id="inputJamKeMulai" name="jam_ke_mulai" min="0" max="20" value="1" required
                            style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="font-size: 0.78rem; font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                            <i class="fas fa-stop text-primary"></i> Jam Selesai <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="number" id="inputJamKeSelesai" name="jam_ke_selesai" min="0" max="20" value="2" required
                            style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box;">
                    </div>
                </div>

                <!-- Pilihan Status Kehadiran (Pill Buttons Touch-Friendly) -->
                <div style="margin-bottom: 12px;">
                    <label style="font-size: 0.78rem; font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                        <i class="fas fa-signal text-primary"></i> Status Kehadiran <span style="color: #ef4444;">*</span>
                    </label>
                    <div class="status-pill-group">
                        <label class="status-pill-item">
                            <input type="radio" name="status" value="H" checked>
                            <span style="color: #10b981;"><i class="fas fa-check-circle me-1"></i>Hadir</span>
                        </label>
                        <label class="status-pill-item">
                            <input type="radio" name="status" value="I">
                            <span style="color: var(--primary);"><i class="fas fa-file-signature me-1"></i>Izin</span>
                        </label>
                        <label class="status-pill-item">
                            <input type="radio" name="status" value="S">
                            <span style="color: #f59e0b;"><i class="fas fa-notes-medical me-1"></i>Sakit</span>
                        </label>
                        <label class="status-pill-item">
                            <input type="radio" name="status" value="T">
                            <span style="color: var(--text-muted);"><i class="fas fa-briefcase me-1"></i>Tugas</span>
                        </label>
                        <label class="status-pill-item">
                            <input type="radio" name="status" value="D">
                            <span style="color: #ef4444;"><i class="fas fa-user-clock me-1"></i>Inval</span>
                        </label>
                    </div>
                </div>

                <!-- Input Guru Pengganti (Muncul jika status Inval) -->
                <div id="wrapGuruPengganti" style="display: none; margin-bottom: 12px; padding: 10px 12px; background: rgba(239,68,68,0.06); border: 1px solid rgba(239,68,68,0.2); border-radius: 8px;">
                    <label style="font-size: 0.78rem; font-weight: 600; color: #ef4444; display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                        <i class="fas fa-user-clock"></i> Nama Guru Pengganti (Inval)
                    </label>
                    <input type="text" id="inputNamaGuruPengganti" name="nama_guru_pengganti" placeholder="Nama Guru Pengganti..."
                        style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box;">
                </div>

                <div class="form-grid-4">
                    <div>
                        <label style="font-size: 0.74rem; font-weight: 600; color: var(--text-muted); display: flex; align-items: center; gap: 4px; margin-bottom: 4px;">
                            <i class="far fa-clock"></i> Masuk
                        </label>
                        <input type="time" id="inputJamMasuk" name="jam_masuk" value="{{ date('H:i') }}"
                            style="width: 100%; height: 36px; padding: 0 6px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.8rem; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="font-size: 0.74rem; font-weight: 600; color: var(--text-muted); display: flex; align-items: center; gap: 4px; margin-bottom: 4px;">
                            <i class="far fa-clock"></i> Selesai
                        </label>
                        <input type="time" id="inputJamKeluar" name="jam_keluar"
                            style="width: 100%; height: 36px; padding: 0 6px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.8rem; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="font-size: 0.74rem; font-weight: 600; color: var(--text-muted); display: flex; align-items: center; gap: 4px; margin-bottom: 4px;">
                            <i class="fas fa-user-check text-success"></i> Siswa Hadir
                        </label>
                        <input type="number" id="inputJumlahSiswaHadir" name="jumlah_siswa_hadir" min="0" placeholder="0"
                            style="width: 100%; height: 36px; padding: 0 6px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.8rem; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="font-size: 0.74rem; font-weight: 600; color: var(--text-muted); display: flex; align-items: center; gap: 4px; margin-bottom: 4px;">
                            <i class="fas fa-user-xmark text-danger"></i> Siswa Absen
                        </label>
                        <input type="number" id="inputJumlahSiswaTidakHadir" name="jumlah_siswa_tidak_hadir" min="0" placeholder="0"
                            style="width: 100%; height: 36px; padding: 0 6px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.8rem; box-sizing: border-box;">
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="font-size: 0.78rem; font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                        <i class="fas fa-pen-fancy text-primary"></i> Catatan Sesi KBM
                    </label>
                    <textarea id="inputKeterangan" name="keterangan" rows="2" placeholder="Catatan opsional KBM, topik selingan, kendala teknis..."
                        style="width: 100%; padding: 8px 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box; resize: vertical;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 12px;">
                    <button type="button" class="btn btn-outline btn-close-modal" style="padding: 7px 14px; font-size: 0.82rem; border-radius: 8px;">
                        Batal
                    </button>
                    <button type="submit" id="btnSavePresensi" class="btn btn-primary" style="padding: 7px 18px; font-size: 0.82rem; border-radius: 8px; font-weight: 600; background: linear-gradient(135deg, #10b981, #059669);">
                        <i class="fas fa-check me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 7. Modal Detail Presensi Mengajar (z-index: 99999 !important) -->
    <div id="modalDetailPresensi" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px); padding: 12px;">
        <div class="card modal-card-responsive" style="max-width: 500px; width: 100%; max-height: 90vh; overflow-y: auto; margin: auto; border-radius: 14px; padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); border: 1px solid var(--border-color); background: var(--bg-card);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <h3 style="font-size: 1rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-circle-info text-primary"></i> Rincian Presensi
                </h3>
                <button type="button" class="btn-close-detail" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem; padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="detailPresensiContent" style="font-size: 0.84rem; display: flex; flex-direction: column; gap: 8px;">
                <!-- Konten dinamis via JS -->
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 12px; margin-top: 14px;">
                <button type="button" class="btn btn-outline btn-close-detail" style="padding: 7px 16px; font-size: 0.82rem; border-radius: 8px;">
                    Tutup
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/presensi-mengajar.js') }}"></script>
@endpush