@extends('layouts.dashboard')

@section('title', 'Jurnal & Agenda KBM — SAE')
@section('dash_title', 'Jurnal & Agenda KBM')

@section('content')
    <!-- 1. Header Banner & Actions -->
    <div class="dash-banner" style="display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                <i class="fas fa-book-open-reader"></i>
            </div>
            <div>
                <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--text-color); margin: 0 0 2px 0;">
                    Jurnal &amp; Agenda KBM
                </h2>
                <p style="color: var(--text-muted); font-size: 0.82rem; margin: 0;">
                    Dokumentasi materi, tujuan pembelajaran &amp; penugasan kelas.
                </p>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('dashboard.agenda-kbm.cetak', request()->all()) }}" target="_blank" class="btn btn-outline btn-responsive-icon"
                title="Cetak Jurnal KBM"
                style="padding: 8px 12px; font-size: 0.82rem; border-radius: 8px; text-decoration: none; border-color: var(--border-color); color: var(--text-color);">
                <i class="fas fa-print text-primary"></i>
                <span class="btn-responsive-text">Cetak Jurnal</span>
            </a>

            @if ($canCreate)
                <button type="button" class="btn btn-primary btn-responsive-icon" style="padding: 8px 14px; font-size: 0.82rem; border-radius: 8px; font-weight: 600;" id="btnOpenCreateAgenda" title="Tambah Agenda KBM">
                    <i class="fas fa-plus"></i>
                    <span class="btn-responsive-text">Tambah Agenda</span>
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
                <i class="fas fa-book-bookmark"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total'] ?? 0) }}</div>
                <div class="dash-stat-label">Total Agenda</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                <i class="fas fa-check-double"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['terlaksana'] ?? 0) }}</div>
                <div class="dash-stat-label">Terlaksana</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['sebagian'] ?? 0) }}</div>
                <div class="dash-stat-label">Sebagian</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239,68,68,0.12); color: #ef4444;">
                <i class="fas fa-clock-rotate-left"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['tertunda'] ?? 0) }}</div>
                <div class="dash-stat-label">Tertunda</div>
            </div>
        </div>
    </div>

    <!-- 4. Main Card Datatable Riwayat Jurnal & Agenda -->
    <div class="card" style="padding: 16px 18px; border-radius: 14px; border: 1px solid var(--border-color); margin-bottom: 20px;">
        <!-- Toolbar Filter & Search Responsif -->
        <div class="toolbar-row" style="display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 14px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 220px;">
                <div class="live-search-wrap" style="width: 100%;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="liveSearchInput" placeholder="Cari materi, tugas, uraian kegiatan..." value="{{ request('q') }}" autocomplete="off">
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

                <!-- Filter Status KBM -->
                <select id="filterStatusKbm" class="toolbar-filter-select" style="height: 36px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.8rem;">
                    <option value="">Semua Status</option>
                    <option value="Terlaksana" {{ request('status_kbm') === 'Terlaksana' ? 'selected' : '' }}>Terlaksana</option>
                    <option value="Sebagian" {{ request('status_kbm') === 'Sebagian' ? 'selected' : '' }}>Sebagian</option>
                    <option value="Tertunda" {{ request('status_kbm') === 'Tertunda' ? 'selected' : '' }}>Tertunda</option>
                    <option value="Digantikan" {{ request('status_kbm') === 'Digantikan' ? 'selected' : '' }}>Digantikan</option>
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
            @include('dashboard.agenda-kbm-table')
        </div>
    </div>

    <!-- 5. Modal Form Tambah / Edit Jurnal Agenda KBM (z-index: 99999 !important) -->
    <div id="modalFormAgenda" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px); padding: 12px; overflow-y: auto;">
        <div class="card modal-card-responsive" style="max-width: 620px; width: 100%; max-height: 92vh; overflow-y: auto; margin: auto; border-radius: 14px; padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); border: 1px solid var(--border-color); background: var(--bg-card);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <h3 id="modalAgendaTitle" style="font-size: 1rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-book-open-reader text-primary"></i> Jurnal &amp; Agenda KBM
                </h3>
                <button type="button" class="btn-close-modal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem; padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formAgendaKbm">
                @csrf
                <input type="hidden" id="agendaId" name="id">
                <input type="hidden" id="inputJadwalKbmId" name="jadwal_kbm_id">
                <input type="hidden" id="inputPembelajaranId" name="pembelajaran_id">
                <input type="hidden" id="inputMataPelajaranId" name="mata_pelajaran_id">
                <input type="hidden" id="inputPtkId" name="ptk_id" value="{{ $ptkId }}">

                <!-- Selector Jadwal KBM (Otomatisasi) -->
                <div id="wrapJadwalSelector" style="margin-bottom: 12px;">
                    <label style="font-size: 0.78rem; font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                        <i class="fas fa-calendar-days text-primary"></i> Pilih dari Jadwal KBM
                    </label>
                    <select id="selectJadwalKbm" style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box;">
                        <option value="">-- Pilih dari Jadwal KBM (Otomatis) --</option>
                        @foreach ($jadwalList as $j)
                            <option value="{{ $j['id'] }}"
                                data-rombel-id="{{ $j['rombongan_belajar_id'] }}"
                                data-mapel="{{ $j['nama_mata_pelajaran'] }}"
                                data-pembelajaran-id="{{ $j['pembelajaran_id'] }}"
                                data-mapel-id="{{ $j['mata_pelajaran_id'] }}"
                                data-hari="{{ $j['hari'] }}"
                                data-jam-mulai="{{ $j['jam_ke_mulai'] }}"
                                data-jam-selesai="{{ $j['jam_ke_selesai'] }}"
                                data-jam-waktu="{{ $j['jam_waktu_range'] }}"
                                data-durasi-jp="{{ $j['durasi_jp'] }}"
                                data-ptk-id="{{ $j['ptk_id'] }}">
                                [{{ $j['hari'] }}] {{ $j['rombel_nama'] }} — {{ $j['nama_mata_pelajaran'] }} (Jam {{ $j['jam_ke_mulai'] }}-{{ $j['jam_ke_selesai'] }}{{ !empty($j['jam_waktu_range']) ? ' • ' . $j['jam_waktu_range'] : '' }})
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
                        <input type="date" id="inputTanggal" name="tanggal" value="{{ date('Y-m-d') }}" required
                            style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="font-size: 0.78rem; font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                            <i class="fas fa-hashtag text-primary"></i> Pertemuan Ke <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="number" id="inputPertemuanKe" name="pertemuan_ke" min="1" max="100" value="1" required
                            style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem; box-sizing: border-box; font-weight: 700;">
                    </div>
                    <div>
                        <label style="font-size: 0.78rem; font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                            <i class="fas fa-signal text-primary"></i> Status KBM <span style="color: #ef4444;">*</span>
                        </label>
                        <select id="inputStatusKbm" name="status_kbm" required
                            style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box;">
                            <option value="Terlaksana">Terlaksana</option>
                            <option value="Sebagian">Sebagian</option>
                            <option value="Tertunda">Tertunda</option>
                            <option value="Digantikan">Digantikan</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div>
                        <label style="font-size: 0.74rem; font-weight: 600; color: var(--text-muted); display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                            <span style="display: flex; align-items: center; gap: 4px;">
                                <i class="fas fa-play text-primary"></i> Jam Mulai (Ke-)
                            </span>
                            <span id="badgeAutoJamMulai" style="font-size: 0.68rem; color: #10b981; font-weight: 600; display: none;">
                                <i class="fas fa-lock me-1"></i> Otomatis
                            </span>
                        </label>
                        <input type="number" id="inputJamKeMulai" name="jam_ke_mulai" min="0" max="20" value="1" readonly
                            style="width: 100%; height: 36px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem; font-weight: 700; box-sizing: border-box; cursor: not-allowed;">
                    </div>
                    <div>
                        <label style="font-size: 0.74rem; font-weight: 600; color: var(--text-muted); display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                            <span style="display: flex; align-items: center; gap: 4px;">
                                <i class="fas fa-stop text-primary"></i> Jam Selesai (Ke-)
                            </span>
                            <span id="labelDurasiJp" style="font-size: 0.68rem; color: var(--text-muted); display: none;">
                                - JP
                            </span>
                        </label>
                        <input type="number" id="inputJamKeSelesai" name="jam_ke_selesai" min="0" max="20" value="2" readonly
                            style="width: 100%; height: 36px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem; font-weight: 700; box-sizing: border-box; cursor: not-allowed;">
                    </div>
                </div>

                <!-- Info Banner Waktu Nyata KBM dari Jadwal -->
                <div id="wrapWaktuKbm" style="display: none; margin-bottom: 12px; margin-top: -4px; padding: 7px 12px; border-radius: 8px; background: rgba(99,102,241,0.08); border: 1px dashed rgba(99,102,241,0.3); font-size: 0.75rem; color: var(--text-color); justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <i class="far fa-clock text-primary"></i>
                        <span>Waktu KBM: <strong id="textWaktuKbm" style="color: var(--primary);">-</strong></span>
                    </div>
                    <span id="badgeHariJadwal" class="badge" style="background: rgba(99,102,241,0.15); color: var(--primary); font-size: 0.7rem; padding: 2px 7px; font-weight: 600;">-</span>
                </div>

                <!-- Materi Pokok -->
                <div style="margin-bottom: 12px;">
                    <label style="font-size: 0.78rem; font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                        <i class="fas fa-heading text-primary"></i> Materi Pokok / Tujuan Pembelajaran <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" id="inputMateriPokok" name="materi_pokok" required placeholder="Contoh: Algoritma Pengurutan (Sorting)..."
                        style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box;">
                </div>

                <!-- Uraian Kegiatan KBM -->
                <div style="margin-bottom: 12px;">
                    <label style="font-size: 0.78rem; font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                        <i class="fas fa-list-check text-primary"></i> Uraian Aktivitas KBM <span style="color: #ef4444;">*</span>
                    </label>
                    <textarea id="inputUraianKegiatan" name="uraian_kegiatan" rows="3" required placeholder="Uraian kegiatan KBM, apersepsi, eksplorasi materi, refleksi..."
                        style="width: 100%; padding: 8px 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box; resize: vertical;"></textarea>
                </div>

                <!-- Penugasan Siswa -->
                <div style="margin-bottom: 12px;">
                    <label style="font-size: 0.78rem; font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                        <i class="fas fa-tasks text-success"></i> Penugasan / Asesmen <span style="font-size: 0.72rem; font-weight: 400; color: var(--text-muted);">(Opsional)</span>
                    </label>
                    <textarea id="inputPenugasan" name="penugasan" rows="2" placeholder="Tugas mandiri, lembar kerja, kuis, atau PR..."
                        style="width: 100%; padding: 8px 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box; resize: vertical;"></textarea>
                </div>

                <!-- Hambatan & Catatan -->
                <div style="margin-bottom: 16px;">
                    <label style="font-size: 0.78rem; font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                        <i class="fas fa-triangle-exclamation text-warning"></i> Catatan Kendala <span style="font-size: 0.72rem; font-weight: 400; color: var(--text-muted);">(Opsional)</span>
                    </label>
                    <input type="text" id="inputHambatanCatatan" name="hambatan_catatan" placeholder="Catatan kendala / catatan khusus siswa..."
                        style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box;">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 12px;">
                    <button type="button" class="btn btn-outline btn-close-modal" style="padding: 7px 14px; font-size: 0.82rem; border-radius: 8px;">
                        Batal
                    </button>
                    <button type="submit" id="btnSaveAgenda" class="btn btn-primary" style="padding: 7px 18px; font-size: 0.82rem; border-radius: 8px; font-weight: 600;">
                        <i class="fas fa-check me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 6. Modal Detail Jurnal Agenda KBM (z-index: 99999 !important) -->
    <div id="modalDetailAgenda" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px); padding: 12px; overflow-y: auto;">
        <div class="card modal-card-responsive" style="max-width: 520px; width: 100%; max-height: 90vh; overflow-y: auto; margin: auto; border-radius: 14px; padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); border: 1px solid var(--border-color); background: var(--bg-card);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <h3 style="font-size: 1rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-book-open text-primary"></i> Rincian Agenda KBM
                </h3>
                <button type="button" class="btn-close-detail-agenda" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem; padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="detailAgendaContent" style="font-size: 0.84rem; display: flex; flex-direction: column; gap: 10px;">
                <!-- Konten dinamis via JS -->
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 12px; margin-top: 14px;">
                <button type="button" class="btn btn-outline btn-close-detail-agenda" style="padding: 7px 16px; font-size: 0.82rem; border-radius: 8px;">
                    Tutup
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/agenda-kbm.js') }}"></script>
@endpush