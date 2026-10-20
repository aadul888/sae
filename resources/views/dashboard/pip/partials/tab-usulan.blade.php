@php
    $statusColor = [
        'belum_diajukan' => 'badge-warning',
        'sudah_diajukan' => 'badge-success',
        'ditolak' => 'badge-danger',
    ];
    $kuotaPenuh = $stats['sisa_kuota_kelas'] <= 0;
@endphp

<style>
    /* Hilangkan panah spinner bawaan browser pada input number stepper */
    input.pip-stepper-input::-webkit-outer-spin-button,
    input.pip-stepper-input::-webkit-inner-spin-button {
        -webkit-appearance: none !important;
        margin: 0 !important;
    }

    input.pip-stepper-input {
        -moz-appearance: textfield !important;
        appearance: textfield !important;
        width: 34px;
        height: 28px;
        border: 1px solid transparent;
        background: transparent;
        text-align: center;
        font-weight: 800;
        font-size: 0.95rem;
        color: var(--text-color);
        border-radius: 6px;
        padding: 0;
    }

    .pip-priority-stepper {
        display: inline-flex;
        align-items: center;
        background: var(--bg-hover, rgba(0, 0, 0, 0.03));
        border: 1px solid var(--border-color);
        border-radius: 9px;
        padding: 2px 3px;
        gap: 2px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        vertical-align: middle;
    }

    .pip-stepper-btn {
        width: 28px;
        height: 28px;
        min-width: 28px;
        min-height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--border-color);
        background: var(--card-bg, #fff);
        color: var(--text-color);
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.76rem;
        transition: all 0.15s ease;
        touch-action: manipulation;
        user-select: none;
    }

    .pip-stepper-btn:hover {
        background: var(--primary);
        color: #fff;
        border-color: var(--primary);
    }

    .pip-stepper-btn:active {
        transform: scale(0.92);
    }

    .pip-stepper-badge {
        display: inline-flex;
        align-items: center;
        padding: 0 2px;
    }

    .pip-stepper-hash {
        font-size: 0.72rem;
        font-weight: 800;
        color: var(--text-muted);
        margin-right: 1px;
    }

    .pip-stepper-input:focus {
        background: var(--card-bg, #fff);
        border-color: var(--primary);
        outline: none;
    }

    @media (max-width: 768px) {
        .table-responsive-stack td[data-label="Prioritas"] {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            padding: 10px 14px !important;
        }

        .pip-priority-stepper {
            padding: 4px;
            gap: 6px;
        }

        .pip-stepper-btn {
            width: 36px;
            height: 36px;
            min-width: 36px;
            min-height: 36px;
            font-size: 0.9rem;
        }

        .pip-stepper-input {
            width: 40px;
            height: 32px;
            font-size: 1rem;
        }
    }
</style>

@if (!$isAdmin && !empty($isWaliLocked))
    <!-- Notice Usulan Terkunci untuk Wali Kelas -->
    <div
        style="margin-bottom: 18px; padding: 14px 18px; border-radius: 12px; background: rgba(239,68,68,0.08); border: 1.5px solid rgba(239,68,68,0.3); color: #ef4444; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div
                style="width: 38px; height: 38px; border-radius: 10px; background: rgba(239,68,68,0.15); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                <i class="fas fa-lock"></i>
            </div>
            <div>
                <strong style="font-size: 0.92rem; color: #ef4444;">Pengusulan PIP Sedang Ditutup / Terkunci</strong>
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                    Pengusulan oleh Wali Kelas saat ini dikunci oleh Administrator atau telah melewati batas waktu
                    pengisian.
                    Data usulan hanya dapat dipantau dan tidak dapat ditambah atau diubah urutan prioritasnya.
                </div>
            </div>
        </div>
        <span class="badge badge-danger" style="font-size: 0.78rem; padding: 4px 10px;">
            <i class="fas fa-ban me-1"></i> Mode Readonly
        </span>
    </div>
@endif

<div class="dash-stat-grid"
    style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <!-- 1. Total Usulan -->
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
            <i class="fas fa-users"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.35rem; color: var(--primary);">
                {{ number_format($stats['total_usulan'], 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Total Usulan</div>
        </div>
    </div>

    <!-- 2. Belum Diusulkan -->
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
            <i class="fas fa-hourglass-half"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.35rem; color: #f59e0b;">
                {{ number_format($stats['belum_diajukan'], 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Belum Diusulkan</div>
        </div>
    </div>

    <!-- 3. Sudah Diusulkan -->
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
            <i class="fas fa-circle-check"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.35rem; color: #10b981;">
                {{ number_format($stats['sudah_diajukan'], 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Sudah Diusulkan</div>
        </div>
    </div>

    <!-- 4. Ditolak -->
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(239,68,68,0.12); color: #ef4444;">
            <i class="fas fa-circle-xmark"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.35rem; color: #ef4444;">
                {{ number_format($stats['ditolak'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Ditolak</div>
        </div>
    </div>

    <!-- 5. Diperbarui / Ada Perubahan -->
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(139,92,246,0.12); color: #8b5cf6;">
            <i class="fas fa-clock-rotate-left"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.35rem; color: #8b5cf6;">
                {{ number_format($stats['total_usulan_diubah'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Ada Perubahan</div>
        </div>
    </div>

    <!-- 6. Kuota Kelas / Pagu Kuota -->
    @if ($activeRombel)
        <div class="dash-stat-card">
            <div class="dash-stat-icon"
                style="background: {{ $kuotaPenuh ? 'rgba(239,68,68,0.12)' : 'rgba(6,182,212,0.12)' }}; color: {{ $kuotaPenuh ? '#ef4444' : 'var(--accent)' }};">
                <i class="fas fa-ticket"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value"
                    style="font-size: 1.35rem; color: {{ $kuotaPenuh ? '#ef4444' : 'var(--accent)' }};">
                    {{ $stats['terpakai_kelas'] }} / {{ $stats['kuota_kelas'] }}
                </div>
                <div class="dash-stat-label">Kuota Kelas {{ $activeRombel->nama }}</div>
            </div>
        </div>
    @else
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6,182,212,0.12); color: var(--accent);">
                <i class="fas fa-ticket"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem; color: var(--accent);">
                    {{ number_format($stats['total_usulan'], 0, ',', '.') }} /
                    {{ number_format($stats['total_pagu_sekolah'] ?? 0, 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Pagu Sekolah</div>
            </div>
        </div>
    @endif
</div>

<!-- Unified Datatable Usulan PIP (Toolbar + Table + Pagination dalam 1 Card Terpadu) -->
<div class="card datatable-card-wrapper" id="tableDataContainer">
    <!-- 1. Toolbar Header (Entries, Filters & Live Search) -->
    <div class="datatable-toolbar">
        <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
            <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                <div class="toolbar-entries">
                    <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                    <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                        @foreach ([10, 15, 25, 50, 100] as $n)
                            <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>
                                {{ $n }}</option>
                        @endforeach
                    </select>
                    <span>entri</span>
                </div>

                @if ($isAdmin)
                    <select id="filterRombel" name="rombel_id" class="toolbar-filter-select" style="height: 38px;">
                        <option value="">Semua Kelas</option>
                        @foreach ($rombelList as $r)
                            <option value="{{ $r->rombongan_belajar_id }}"
                                {{ $activeRombel?->rombongan_belajar_id === $r->rombongan_belajar_id ? 'selected' : '' }}>
                                {{ $r->nama }}
                            </option>
                        @endforeach
                    </select>
                @endif

                <select id="filterStatusVerif" name="status_verifikasi" class="toolbar-filter-select"
                    style="height: 38px;">
                    <option value="">Semua Status Verifikasi</option>
                    @foreach ($statusVerifikasiList as $k => $label)
                        <option value="{{ $k }}" {{ $statusVerif === $k ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                <select id="filterFase" name="fase" class="toolbar-filter-select" style="height: 38px;">
                    <option value="">Semua Fase</option>
                    @foreach (\App\Models\PipUsulan::FASE_LIST as $fKey => $fLabel)
                        <option value="{{ $fKey }}" {{ ($faseFilter ?? '') === $fKey ? 'selected' : '' }}>
                            {{ $fLabel }}
                        </option>
                    @endforeach
                </select>

                <select id="filterPerubahan" name="status_perubahan" class="toolbar-filter-select"
                    style="height: 38px;">
                    <option value="">Semua Status Data</option>
                    <option value="diubah" {{ ($statusPerubahanFilter ?? '') === 'diubah' ? 'selected' : '' }}>
                        Ada Perubahan ({{ $stats['total_usulan_diubah'] ?? 0 }})
                    </option>
                    <option value="asli" {{ ($statusPerubahanFilter ?? '') === 'asli' ? 'selected' : '' }}>
                        Data Asli (Belum Diubah)
                    </option>
                </select>

                @if ($q || $statusVerif || ($faseFilter ?? '') || ($statusPerubahanFilter ?? '') || ($isAdmin && $activeRombel))
                    <a href="{{ route('dashboard.pip.index', ['tab' => 'usulan', 'tahun' => $activeTahun]) }}"
                        class="btn btn-outline btn-reset-filter"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;"
                        title="Reset filter" data-live-reset>
                        <i class="fas fa-undo"></i>
                    </a>
                @endif
            </div>

            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <div class="live-search-wrap" style="height: 38px;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchPip" name="q" placeholder="Cari nama / NISN / alasan..."
                        value="{{ $q }}" autocomplete="off">
                    <button type="button" id="clearSearchPip"
                        class="clear-search {{ !empty($q) ? 'visible' : '' }}" title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <button type="button" class="btn btn-outline" onclick="pipExportUsulanExcel()"
                    style="height: 38px; padding: 0 12px; display: inline-flex; align-items: center; gap: 6px; border-radius: 8px; font-size: 0.82rem; font-weight: 700; border-color: #10b981; color: #10b981;"
                    title="Export Seluruh Data Usulan PIP ke Format Excel (.xlsx) Lengkap">
                    <i class="fas fa-file-excel"></i>
                    <span>Export</span>
                </button>
            </div>
        </div>

        @if ($isAdmin)
            <div id="bulkActionBar"
                style="display: none; margin-top: 14px; padding: 10px 14px; border-radius: 8px; background: rgba(99,102,241,0.08); border: 1px solid rgba(99,102,241,0.25); align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div
                    style="font-size: 0.85rem; font-weight: 700; color: var(--primary); display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-check-square"></i>
                    <span><span id="bulkSelectedCount">0</span> usulan</span>
                </div>
                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                    <button type="button" class="btn btn-outline btn-sm"
                        onclick="pipBulkVerifikasi('set_sudah_diajukan')"
                        style="border-color: #10b981; color: #10b981; font-weight: 600;"
                        title="Tandai Sudah Diusulkan">
                        <i class="fas fa-check me-1"></i> Diajukan
                    </button>
                    <button type="button" class="btn btn-outline btn-sm"
                        onclick="pipBulkVerifikasi('set_belum_diajukan')"
                        style="border-color: #f59e0b; color: #f59e0b; font-weight: 600;"
                        title="Tandai Belum Diusulkan">
                        <i class="fas fa-clock me-1"></i> Belum
                    </button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="pipBulkVerifikasi('set_ditolak')"
                        style="border-color: #ef4444; color: #ef4444; font-weight: 600;" title="Tandai Ditolak">
                        <i class="fas fa-ban me-1"></i> Tolak
                    </button>
                </div>
            </div>
        @endif
    </div>

    <!-- 2. Konten Datatable Responsive Stack -->
    <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                    @if ($isAdmin)
                        <th style="width: 36px; text-align: center; padding: 12px 8px;">
                            <input type="checkbox" id="checkAllPip" title="Pilih Semua">
                        </th>
                    @endif
                    <th
                        style="padding: 12px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); width: {{ $isAdmin ? '80px' : '110px' }}; text-align: center;">
                        Rank</th>
                    <th
                        style="padding: 12px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">
                        Peserta Didik</th>
                    <th
                        style="padding: 12px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">
                        Kelas</th>
                    <th
                        style="padding: 12px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">
                        Kriteria / Alasan</th>
                    <th
                        style="padding: 12px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">
                        Status PIP</th>
                    <th
                        style="padding: 12px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); text-align: center; width: 140px;">
                        Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($usulanList as $u)
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        @if ($isAdmin)
                            <td style="text-align: center; padding: 12px 8px;" data-label="Pilih">
                                <input type="checkbox" class="check-pip-row" value="{{ $u->id }}">
                            </td>
                        @endif
                        <td style="padding: 8px 10px; text-align: center;" data-label="Prioritas">
                            @if ($isAdmin)
                                <span class="badge badge-primary"
                                    style="font-size: 0.84rem; font-weight: 800; padding: 3px 10px; border-radius: 8px; letter-spacing: 0.5px;"
                                    title="Prioritas #{{ $u->prioritas }} (Hanya dapat diatur oleh Wali Kelas)">
                                    #{{ $u->prioritas }}
                                </span>
                            @elseif (!empty($isWaliLocked))
                                <span class="badge badge-primary"
                                    style="font-size: 0.84rem; font-weight: 800; padding: 3px 10px; border-radius: 8px; letter-spacing: 0.5px;"
                                    title="Prioritas #{{ $u->prioritas }} (Pengusulan saat ini terkunci)">
                                    #{{ $u->prioritas }}
                                </span>
                            @else
                                <div class="pip-priority-stepper">
                                    <button type="button" class="pip-stepper-btn pip-btn-up"
                                        title="Naikkan prioritas"
                                        onclick="pipMovePrioritas({{ $u->id }}, 'up')">
                                        <i class="fas fa-chevron-up"></i>
                                    </button>
                                    <div class="pip-stepper-badge" title="Urutan Prioritas">
                                        <span class="pip-stepper-hash">#</span>
                                        <input type="number" min="1" value="{{ $u->prioritas }}"
                                            class="pip-stepper-input pip-rank-input" data-id="{{ $u->id }}"
                                            data-current="{{ $u->prioritas }}"
                                            title="Ketik angka urutan lalu tekan Enter">
                                    </div>
                                    <button type="button" class="pip-stepper-btn pip-btn-down"
                                        title="Turunkan prioritas"
                                        onclick="pipMovePrioritas({{ $u->id }}, 'down')">
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                </div>
                            @endif
                        </td>
                        <td style="padding: 12px 14px;" data-label="Peserta Didik">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="{{ $u->foto_url ?: asset('img/logo-dark.png') }}"
                                    alt="{{ $u->nama_pd }}"
                                    style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 1.5px solid var(--border-color); flex-shrink: 0;"
                                    onerror="this.src='/img/logo-dark.png'">
                                <div>
                                    <div style="font-weight: 700; font-size: 0.88rem;">{{ $u->nama_pd }}</div>
                                    <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace;">
                                        NISN: {{ $u->nisn ?: '-' }} &bull; {{ $u->nipd ?: '-' }}
                                    </div>
                                    <div
                                        style="display: flex; align-items: center; gap: 4px; margin-top: 3px; flex-wrap: wrap;">
                                        <span class="badge badge-primary"
                                            style="font-size: 0.66rem; padding: 1px 6px;">
                                            {{ \App\Models\PipUsulan::getFaseLabel($u->fase ?? null) }}
                                        </span>
                                        @if (!empty($u->tahun_pelajaran))
                                            <span class="badge badge-outline"
                                                style="font-size: 0.66rem; padding: 1px 5px;">
                                                TP {{ $u->tahun_pelajaran }}
                                            </span>
                                        @endif
                                        @if (!empty($u->diubah_at))
                                            <span class="badge badge-warning"
                                                style="font-size: 0.66rem; padding: 1px 6px; font-weight: 700; cursor: pointer;"
                                                onclick="pipLihatRiwayatUsulan({{ $u->id }})"
                                                title="Klik untuk melihat riwayat perubahan data usulan ini">
                                                <i class="fas fa-clock-rotate-left me-1"></i> Ada Perubahan
                                            </span>
                                            @if ($u->verifikasi_admin_at && \Carbon\Carbon::parse($u->diubah_at)->gt(\Carbon\Carbon::parse($u->verifikasi_admin_at)))
                                                <span class="badge badge-danger"
                                                    style="font-size: 0.65rem; padding: 1px 5px;"
                                                    title="Diubah oleh wali kelas setelah status usulan diverifikasi oleh admin!">
                                                    <i class="fas fa-triangle-exclamation me-1"></i> Pasca-Verifikasi
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                    @if (!empty($u->diubah_at))
                                        <div style="font-size: 0.68rem; color: #f59e0b; margin-top: 2px;">
                                            <i class="fas fa-pen-to-square me-1"></i> Diperbarui:
                                            {{ \Carbon\Carbon::parse($u->diubah_at)->format('d/m/Y H:i') }}
                                            @if (!empty($u->diubah_oleh))
                                                &bull; {{ $u->diubah_oleh }}
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td style="padding: 12px 14px;" data-label="Kelas">
                            <span class="badge badge-outline"
                                style="font-size: 0.76rem; padding: 3px 8px;">{{ $u->nama_rombel ?: '-' }}</span>
                        </td>
                        <td style="padding: 12px 14px;" data-label="Alasan">
                            <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                @foreach ($u->alasan_list as $al)
                                    <span class="badge badge-outline"
                                        style="font-size: 0.7rem; padding: 2px 7px;">{{ $al }}</span>
                                @endforeach
                            </div>
                            @if ($u->keterangan)
                                <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 4px;">
                                    {{ $u->keterangan }}</div>
                            @endif
                        </td>
                        <td style="padding: 12px 14px;" data-label="Status">
                            @php
                                $statusIcon = [
                                    'belum_diajukan' => 'fa-clock',
                                    'sudah_diajukan' => 'fa-circle-check',
                                    'ditolak' => 'fa-circle-xmark',
                                ];
                            @endphp
                            <span class="badge {{ $statusColor[$u->status_verifikasi] ?? 'badge-outline' }}"
                                style="font-size: 0.72rem; padding: 3px 8px; display: inline-flex; align-items: center; gap: 5px;">
                                <i class="fas {{ $statusIcon[$u->status_verifikasi] ?? 'fa-info-circle' }}"></i>
                                {{ $statusVerifikasiList[$u->status_verifikasi] ?? $u->status_verifikasi }}
                            </span>
                            @if ($u->diverifikasi_oleh)
                                <div style="font-size: 0.68rem; color: var(--text-muted); margin-top: 3px;">
                                    {{ $u->diverifikasi_oleh }}
                                    @if ($u->verifikasi_admin_at)
                                        &bull; {{ \Carbon\Carbon::parse($u->verifikasi_admin_at)->format('d/m/Y') }}
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td style="padding: 12px 14px; text-align: center;" data-label="Aksi">
                            <div style="display: flex; gap: 6px; justify-content: center; align-items: center;">
                                @if ($isAdmin)
                                    <select class="form-control pip-verif-select" data-id="{{ $u->id }}"
                                        data-current="{{ $u->status_verifikasi }}"
                                        style="min-width: 105px; height: 30px; padding: 0 6px; font-size: 0.76rem; border-radius: 6px;"
                                        title="Ubah Status Verifikasi">
                                        <option value="belum_diajukan"
                                            {{ $u->status_verifikasi === 'belum_diajukan' ? 'selected' : '' }}>⏳ Belum
                                        </option>
                                        <option value="sudah_diajukan"
                                            {{ $u->status_verifikasi === 'sudah_diajukan' ? 'selected' : '' }}>✓
                                            Diajukan
                                        </option>
                                        <option value="ditolak"
                                            {{ $u->status_verifikasi === 'ditolak' ? 'selected' : '' }}>✕ Ditolak
                                        </option>
                                    </select>
                                @endif

                                <!-- Tombol Edit Usulan (Wali Kelas saat tidak terkunci & Admin) -->
                                @if ($isAdmin || empty($isWaliLocked))
                                    <button type="button" class="btn-icon text-primary"
                                        style="color: var(--primary); width: 30px; height: 30px;"
                                        title="Edit Kriteria Alasan &amp; Keterangan Usulan"
                                        onclick="pipBukaEditUsulan({{ $u->id }}, @js($u->nama_pd), @js($u->alasan_list), @js($u->keterangan))">
                                        <i class="fas fa-pen-to-square"></i>
                                    </button>
                                @endif

                                <!-- Tombol Riwayat Perubahan jika ada riwayat -->
                                @if (!empty($u->diubah_at))
                                    <button type="button" class="btn-icon"
                                        style="color: #f59e0b; width: 30px; height: 30px;"
                                        title="Lihat Riwayat Perubahan Usulan"
                                        onclick="pipLihatRiwayatUsulan({{ $u->id }})">
                                        <i class="fas fa-history"></i>
                                    </button>
                                @endif

                                @if ($u->status_verifikasi === 'sudah_diajukan')
                                    <button type="button" class="btn-icon"
                                        style="color: var(--text-muted); opacity: 0.4; width: 30px; height: 30px; cursor: not-allowed;"
                                        title="Tidak dapat dihapus karena sudah diusulkan" disabled>
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                @elseif ($isAdmin || empty($isWaliLocked))
                                    <button type="button" class="btn-icon"
                                        style="color: #ef4444; width: 30px; height: 30px;" title="Hapus Usulan"
                                        onclick="pipHapusUsulan({{ $u->id }}, @js($u->nama_pd), @js($u->status_verifikasi))">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                @else
                                    <span class="badge badge-outline"
                                        style="font-size: 0.7rem; padding: 3px 8px; color: var(--text-muted);"
                                        title="Pengusulan PIP saat ini terkunci">
                                        <i class="fas fa-lock me-1"></i> Terkunci
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isAdmin ? 7 : 6 }}"
                            style="padding: 35px; text-align: center; color: var(--text-muted);">
                            <i class="fas fa-hand-holding-dollar mb-2"
                                style="font-size: 2rem; opacity: 0.4; display: block;"></i>
                            Belum ada usulan PIP untuk tahun {{ $activeTahun }}.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- 3. Footer Paginasi Terpadu Datatable -->
    @if ($usulanList->hasPages())
        <div class="datatable-footer">
            @include('partials.datatable-pagination', ['paginator' => $usulanList])
        </div>
    @endif
</div>
