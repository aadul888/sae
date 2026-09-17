@extends('layouts.dashboard')

@section('title', 'Presensi Mengajar — SAE')
@section('dash_title', 'Presensi Mengajar')

@section('content')
    <!-- 1. Header Banner & Actions -->
    <div class="dash-banner" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16,185,129,0.12); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0;">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div>
                <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--text-color); margin: 0 0 2px 0;">
                    Presensi Mengajar Guru
                </h2>
                <p style="color: var(--text-muted); font-size: 0.84rem; margin: 0;">
                    Pencatatan kehadiran KBM otomatis terintegrasi dengan Jadwal, Rombel, dan Mata Pelajaran.
                </p>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <div style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: 20px; font-size: 0.8rem; font-weight: 600; color: var(--text-color);">
                <i class="fas fa-calendar-day text-primary"></i>
                <span>{{ $hariIni }}, {{ \Carbon\Carbon::parse($tanggalHariIni)->translatedFormat('d F Y') }}</span>
            </div>

            @if ($canCreate)
                <button type="button" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.84rem; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;" id="btnOpenCreateModal">
                    <i class="fas fa-plus"></i> Catat Presensi Mengajar
                </button>
            @endif
        </div>
    </div>

    <!-- 2. Flash Messages -->
    @if (session('success'))
        <div style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: #10b981; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 0.86rem; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-check-circle"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if (session('error'))
        <div style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #ef4444; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 0.86rem; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-circle-exclamation"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <!-- 3. Stat Grid Baku SAE (Bulan Ini) -->
    <div class="dash-stat-grid" style="margin-bottom: 22px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
                <i class="fas fa-chalkboard-user"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_sesi'] ?? 0) }}</div>
                <div class="dash-stat-label">Total Sesi KBM (Bulan Ini)</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                <i class="fas fa-check-double"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_hadir'] ?? 0) }}</div>
                <div class="dash-stat-label">Hadir Mengajar</div>
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
                <div class="dash-stat-label">Tugas Luar / Inval</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(168,85,247,0.12); color: #a855f7;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_jp'] ?? 0) }} JP</div>
                <div class="dash-stat-label">Total Jam Terlaksana</div>
            </div>
        </div>
    </div>

    <!-- 4. Quick Action Widget: Jadwal Mengajar Hari Ini -->
    <div class="card" style="padding: 18px 20px; border-radius: 14px; border: 1px solid var(--border-color); margin-bottom: 22px; background: var(--bg-card);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 34px; height: 34px; border-radius: 8px; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1rem;">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div>
                    <h3 style="font-size: 0.98rem; font-weight: 700; color: var(--text-color); margin: 0;">
                        Jadwal Mengajar Hari Ini ({{ $hariIni }})
                    </h3>
                    <div style="font-size: 0.78rem; color: var(--text-muted);">
                        Pintasan cepat presensi KBM berdasarkan jadwal mingguan Anda hari ini
                    </div>
                </div>
            </div>
            <span class="badge" style="background: rgba(99,102,241,0.1); color: var(--primary); font-size: 0.78rem; padding: 4px 10px;">
                {{ count($jadwalHariIni) }} Jadwal Terdaftar
            </span>
        </div>

        @if (count($jadwalHariIni) > 0)
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 12px;">
                @foreach ($jadwalHariIni as $j)
                    @php
                        $key = 'jadwal_' . $j->id;
                        $presensiToday = $presensiHariIniKeyed[$key] ?? null;
                        $rombelNama = \Illuminate\Support\Facades\DB::table('rombongan_belajar')->where('rombongan_belajar_id', $j->rombongan_belajar_id)->value('nama') ?? $j->rombongan_belajar_id;
                    @endphp
                    <div style="border: 1px solid {{ $presensiToday ? 'rgba(16,185,129,0.3)' : 'var(--border-color)' }}; background: {{ $presensiToday ? 'rgba(16,185,129,0.03)' : 'var(--bg-hover)' }}; border-radius: 12px; padding: 14px; display: flex; flex-direction: column; justify-content: space-between; gap: 10px; transition: all 0.2s ease;">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px; gap: 6px;">
                                <span class="badge badge-primary" style="font-size: 0.74rem; font-weight: 700; padding: 3px 8px;">
                                    <i class="fas fa-chalkboard me-1"></i>{{ $rombelNama }}
                                </span>
                                <span class="badge" style="background: var(--bg-card); border: 1px solid var(--border-color); font-size: 0.72rem; color: var(--text-muted);">
                                    Jam {{ $j->jam_ke_mulai }}-{{ $j->jam_ke_selesai }} ({{ $j->durasi_jp }} JP)
                                </span>
                            </div>

                            <div style="font-weight: 700; color: var(--text-color); font-size: 0.92rem; margin-bottom: 2px;">
                                {{ $j->nama_mata_pelajaran }}
                            </div>

                            <div style="font-size: 0.76rem; color: var(--text-muted); display: flex; align-items: center; gap: 10px;">
                                <span><i class="far fa-clock me-1"></i>{{ $j->jam_waktu_range }}</span>
                                @if ($j->ruangan)
                                    <span><i class="fas fa-location-dot me-1"></i>{{ $j->ruangan }}</span>
                                @endif
                            </div>
                        </div>

                        <div style="border-top: 1px solid var(--border-color); padding-top: 10px; display: flex; justify-content: space-between; align-items: center;">
                            @if ($presensiToday)
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    {!! $presensiToday->status_badge !!}
                                    <span style="font-size: 0.74rem; color: var(--text-muted);">
                                        {{ substr($presensiToday->jam_masuk, 0, 5) }}
                                    </span>
                                </div>
                                <button type="button" class="btn btn-outline btn-sm btn-edit-row" data-id="{{ $presensiToday->id }}" style="font-size: 0.74rem; padding: 4px 10px; border-radius: 6px;">
                                    <i class="fas fa-edit me-1"></i> Edit
                                </button>
                            @else
                                <span style="font-size: 0.75rem; color: #f59e0b; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="fas fa-clock"></i> Belum Check-in
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
                                        style="font-size: 0.76rem; padding: 5px 12px; border-radius: 6px; background: linear-gradient(135deg, #10b981, #059669); font-weight: 600;">
                                        <i class="fas fa-check me-1"></i> Presensi Sekarang
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div style="text-align: center; padding: 24px 14px; background: var(--bg-hover); border-radius: 10px; color: var(--text-muted); font-size: 0.84rem;">
                <i class="fas fa-coffee" style="font-size: 1.8rem; margin-bottom: 8px; display: block; opacity: 0.5;"></i>
                Tidak ada jadwal KBM yang tercatat untuk hari {{ $hariIni }}. Anda tetap dapat mencatat presensi mengajar jam tambahan melalui tombol di atas.
            </div>
        @endif
    </div>

    <!-- 5. Main Card Datatable Riwayat Presensi -->
    <div class="card" style="padding: 20px; border-radius: 14px; border: 1px solid var(--border-color); margin-bottom: 20px;">
        <!-- Toolbar Filter & Search Baku SAE -->
        <div class="dash-toolbar" style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 16px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 260px;">
                <div class="live-search-wrap" style="width: 100%; max-width: 360px;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="liveSearchInput" placeholder="Cari mapel, kelas, catatan..." value="{{ request('q') }}" autocomplete="off">
                    <button type="button" class="clear-search" title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <!-- Filter Tanggal -->
                <input type="date" id="filterTanggalMulai" value="{{ request('tanggal_mulai') }}" title="Tanggal Mulai"
                    style="height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.8rem;">
                <span style="color: var(--text-muted); font-size: 0.8rem;">s.d</span>
                <input type="date" id="filterTanggalSelesai" value="{{ request('tanggal_selesai') }}" title="Tanggal Selesai"
                    style="height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.8rem;">

                <!-- Filter Rombel -->
                <select id="filterRombel" style="height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem;">
                    <option value="">Semua Kelas</option>
                    @foreach ($rombelList as $r)
                        <option value="{{ $r->rombongan_belajar_id }}" {{ request('rombongan_belajar_id') === $r->rombongan_belajar_id ? 'selected' : '' }}>
                            {{ $r->nama }}
                        </option>
                    @endforeach
                </select>

                <!-- Filter Status -->
                <select id="filterStatus" style="height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem;">
                    <option value="">Semua Status</option>
                    <option value="H" {{ request('status') === 'H' ? 'selected' : '' }}>Hadir Mengajar</option>
                    <option value="I" {{ request('status') === 'I' ? 'selected' : '' }}>Izin</option>
                    <option value="S" {{ request('status') === 'S' ? 'selected' : '' }}>Sakit</option>
                    <option value="T" {{ request('status') === 'T' ? 'selected' : '' }}>Tugas Luar</option>
                    <option value="D" {{ request('status') === 'D' ? 'selected' : '' }}>Digantikan / Inval</option>
                </select>

                @if (!$isGuru && count($guruList) > 0)
                    <select id="filterPtk" style="height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; max-width: 180px;">
                        <option value="">Semua Guru</option>
                        @foreach ($guruList as $g)
                            <option value="{{ $g->ptk_id }}" {{ request('filter_ptk_id') === $g->ptk_id ? 'selected' : '' }}>
                                {{ $g->nama }}
                            </option>
                        @endforeach
                    </select>
                @endif

                <!-- Per Page -->
                <select id="perPageSelect" style="height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem;">
                    <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10 Baris</option>
                    <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15 Baris</option>
                    <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 Baris</option>
                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 Baris</option>
                </select>

                <button type="button" id="btnResetFilter" class="btn btn-outline" title="Reset Semua Filter" style="height: 38px; padding: 0 12px; font-size: 0.8rem; border-radius: 8px;">
                    <i class="fas fa-rotate-left"></i>
                </button>
            </div>
        </div>

        <!-- Datatable Container Baku SAE -->
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 0; border: 1px solid var(--border-color); overflow: hidden; border-radius: 10px;">
            @include('dashboard.presensi-mengajar-table')
        </div>
    </div>

    <!-- 6. Modal Form Catat Presensi Mengajar Baku SAE (z-index: 99999 !important) -->
    <div id="modalFormPresensi" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px); padding: 16px; overflow-y: auto;">
        <div class="card" style="max-width: 620px; width: 100%; margin: auto; border-radius: 14px; padding: 22px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); border: 1px solid var(--border-color); background: var(--bg-card);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3 id="modalPresensiTitle" style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-calendar-check text-primary"></i> Catat Presensi Mengajar
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
                <div style="margin-bottom: 14px;">
                    <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                        Pilih dari Jadwal KBM Terdaftar <span style="font-size: 0.74rem; font-weight: 400; color: var(--text-muted);">(Opsional / Otomatisasi data)</span>
                    </label>
                    <select id="selectJadwalKbm" style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem; box-sizing: border-box;">
                        <option value="">-- Pilih Slot Jadwal KBM Anda --</option>
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

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                            Kelas / Rombel <span style="color: #ef4444;">*</span>
                        </label>
                        <select id="inputRombonganBelajarId" name="rombongan_belajar_id" required
                            style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem; box-sizing: border-box;">
                            <option value="">-- Pilih Rombel --</option>
                            @foreach ($rombelList as $r)
                                <option value="{{ $r->rombongan_belajar_id }}">{{ $r->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                            Mata Pelajaran <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="text" id="inputNamaMataPelajaran" name="nama_mata_pelajaran" required placeholder="Nama Mata Pelajaran..."
                            style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem; box-sizing: border-box;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                            Tanggal KBM <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="date" id="inputTanggal" name="tanggal" value="{{ $tanggalHariIni }}" required
                            style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                            Jam Ke Mulai <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="number" id="inputJamKeMulai" name="jam_ke_mulai" min="0" max="20" value="1" required
                            style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                            Jam Ke Selesai <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="number" id="inputJamKeSelesai" name="jam_ke_selesai" min="0" max="20" value="2" required
                            style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem; box-sizing: border-box;">
                    </div>
                </div>

                <!-- Pilihan Status Kehadiran -->
                <div style="margin-bottom: 14px;">
                    <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 6px;">
                        Status Kehadiran Mengajar <span style="color: #ef4444;">*</span>
                    </label>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <label class="status-radio-label" style="flex: 1; min-width: 100px; padding: 8px 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-hover); cursor: pointer; display: flex; align-items: center; gap: 6px; font-size: 0.8rem;">
                            <input type="radio" name="status" value="H" checked>
                            <span style="font-weight: 600; color: #10b981;">Hadir</span>
                        </label>
                        <label class="status-radio-label" style="flex: 1; min-width: 90px; padding: 8px 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-hover); cursor: pointer; display: flex; align-items: center; gap: 6px; font-size: 0.8rem;">
                            <input type="radio" name="status" value="I">
                            <span style="font-weight: 600; color: var(--primary);">Izin</span>
                        </label>
                        <label class="status-radio-label" style="flex: 1; min-width: 90px; padding: 8px 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-hover); cursor: pointer; display: flex; align-items: center; gap: 6px; font-size: 0.8rem;">
                            <input type="radio" name="status" value="S">
                            <span style="font-weight: 600; color: #f59e0b;">Sakit</span>
                        </label>
                        <label class="status-radio-label" style="flex: 1; min-width: 110px; padding: 8px 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-hover); cursor: pointer; display: flex; align-items: center; gap: 6px; font-size: 0.8rem;">
                            <input type="radio" name="status" value="T">
                            <span style="font-weight: 600; color: var(--text-muted);">Tugas Luar</span>
                        </label>
                        <label class="status-radio-label" style="flex: 1; min-width: 120px; padding: 8px 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-hover); cursor: pointer; display: flex; align-items: center; gap: 6px; font-size: 0.8rem;">
                            <input type="radio" name="status" value="D">
                            <span style="font-weight: 600; color: #ef4444;">Inval / Diganti</span>
                        </label>
                    </div>
                </div>

                <!-- Input Guru Pengganti (Muncul jika status Digantikan / Inval) -->
                <div id="wrapGuruPengganti" style="display: none; margin-bottom: 14px; padding: 10px 14px; background: rgba(239,68,68,0.06); border: 1px solid rgba(239,68,68,0.2); border-radius: 8px;">
                    <label style="font-size: 0.8rem; font-weight: 600; color: #ef4444; display: block; margin-bottom: 4px;">
                        Nama Guru Pengganti / Guru Piket (Inval)
                    </label>
                    <input type="text" id="inputNamaGuruPengganti" name="nama_guru_pengganti" placeholder="Nama Guru yang menggantikan KBM..."
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color); border-radius: 8px; font-size: 0.84rem; box-sizing: border-box;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 10px; margin-bottom: 14px;">
                    <div>
                        <label style="font-size: 0.78rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px;">
                            Jam Masuk
                        </label>
                        <input type="time" id="inputJamMasuk" name="jam_masuk" value="{{ date('H:i') }}"
                            style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="font-size: 0.78rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px;">
                            Jam Keluar
                        </label>
                        <input type="time" id="inputJamKeluar" name="jam_keluar"
                            style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="font-size: 0.78rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px;">
                            Siswa Hadir
                        </label>
                        <input type="number" id="inputJumlahSiswaHadir" name="jumlah_siswa_hadir" min="0" placeholder="0"
                            style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="font-size: 0.78rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px;">
                            Siswa Absen
                        </label>
                        <input type="number" id="inputJumlahSiswaTidakHadir" name="jumlah_siswa_tidak_hadir" min="0" placeholder="0"
                            style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box;">
                    </div>
                </div>

                <div style="margin-bottom: 18px;">
                    <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                        Keterangan / Catatan Sesi KBM
                    </label>
                    <textarea id="inputKeterangan" name="keterangan" rows="2" placeholder="Catatan opsional KBM, kendala teknis, materi selingan..."
                        style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem; box-sizing: border-box; resize: vertical;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" class="btn btn-outline btn-close-modal" style="padding: 8px 16px; font-size: 0.84rem; border-radius: 8px;">
                        Batal
                    </button>
                    <button type="submit" id="btnSavePresensi" class="btn btn-primary" style="padding: 8px 20px; font-size: 0.84rem; border-radius: 8px; font-weight: 600; background: linear-gradient(135deg, #10b981, #059669);">
                        <i class="fas fa-check me-1"></i> Simpan Presensi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 7. Modal Detail Presensi Mengajar (z-index: 99999 !important) -->
    <div id="modalDetailPresensi" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px); padding: 16px;">
        <div class="card" style="max-width: 520px; width: 100%; margin: auto; border-radius: 14px; padding: 22px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); border: 1px solid var(--border-color); background: var(--bg-card);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-circle-info text-primary"></i> Rincian Presensi Mengajar
                </h3>
                <button type="button" class="btn-close-detail" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem; padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="detailPresensiContent" style="font-size: 0.86rem; display: flex; flex-direction: column; gap: 10px;">
                <!-- Konten dinamis via JS -->
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 14px; margin-top: 16px;">
                <button type="button" class="btn btn-outline btn-close-detail" style="padding: 8px 18px; font-size: 0.84rem; border-radius: 8px;">
                    Tutup
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/presensi-mengajar.js') }}"></script>
@endpush