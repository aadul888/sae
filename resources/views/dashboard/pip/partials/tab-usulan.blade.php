@php
    $statusColor = [
        'belum_diajukan' => 'badge-warning',
        'sudah_diajukan' => 'badge-success',
        'ditolak' => 'badge-danger',
    ];
    $kuotaPenuh = $stats['sisa_kuota_kelas'] <= 0;
@endphp

<style>
    .pip-priority-stepper {
        display: inline-flex;
        align-items: center;
        background: var(--bg-hover, rgba(0, 0, 0, 0.03));
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 3px;
        gap: 3px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        vertical-align: middle;
    }

    .pip-stepper-btn {
        width: 32px;
        height: 32px;
        min-width: 32px;
        min-height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--border-color);
        background: var(--card-bg, #fff);
        color: var(--text-color);
        border-radius: 7px;
        cursor: pointer;
        font-size: 0.8rem;
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
        padding: 0 3px;
    }

    .pip-stepper-hash {
        font-size: 0.76rem;
        font-weight: 800;
        color: var(--text-muted);
        margin-right: 1px;
    }

    .pip-stepper-input {
        width: 38px;
        height: 30px;
        border: 1px solid transparent;
        background: transparent;
        text-align: center;
        font-weight: 800;
        font-size: 0.92rem;
        color: var(--text-color);
        border-radius: 6px;
        padding: 0;
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
            width: 38px;
            height: 38px;
            min-width: 38px;
            min-height: 38px;
            font-size: 0.95rem;
        }

        .pip-stepper-input {
            width: 44px;
            height: 34px;
            font-size: 1rem;
        }
    }
</style>

<div class="dash-stat-grid"
    style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
            <i class="fas fa-users"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.35rem;">
                {{ number_format($stats['total_usulan'], 0, ',', '.') }}</div>
            <div class="dash-stat-label">Total Usulan {{ $activeTahun }}</div>
        </div>
    </div>
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
            <i class="fas fa-hourglass-half"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.35rem; color: #f59e0b;">
                {{ number_format($stats['belum_diajukan'], 0, ',', '.') }}</div>
            <div class="dash-stat-label">Belum Diusulkan ke Web PIP</div>
        </div>
    </div>
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
            <i class="fas fa-circle-check"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.35rem; color: #10b981;">
                {{ number_format($stats['sudah_diajukan'], 0, ',', '.') }}</div>
            <div class="dash-stat-label">Sudah Diusulkan ke Web PIP</div>
        </div>
    </div>
    @if ($activeRombel)
        <div class="dash-stat-card">
            <div class="dash-stat-icon"
                style="background: {{ $kuotaPenuh ? 'rgba(239,68,68,0.12)' : 'rgba(37,99,235,0.12)' }}; color: {{ $kuotaPenuh ? '#ef4444' : '#2563eb' }};">
                <i class="fas fa-ticket"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value"
                    style="font-size: 1.35rem; color: {{ $kuotaPenuh ? '#ef4444' : '#2563eb' }};">
                    {{ $stats['terpakai_kelas'] }} / {{ $stats['kuota_kelas'] }}
                </div>
                <div class="dash-stat-label">Kuota Kelas {{ $activeRombel->nama }}</div>
            </div>
        </div>
    @else
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(37,99,235,0.12); color: #2563eb;">
                <i class="fas fa-ticket"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem; color: #2563eb;">
                    {{ number_format($stats['total_usulan'], 0, ',', '.') }} /
                    {{ number_format($stats['total_pagu_sekolah'] ?? 0, 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Pagu Kuota Sekolah</div>
            </div>
        </div>
    @endif
</div>

<!-- Toolbar -->
<div class="card" style="padding: 16px; margin-bottom: 20px;">
    <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
        <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
            <div class="toolbar-entries">
                <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                <select id="perPageSelect" name="perPage" class="per-page-select">
                    @foreach ([10, 15, 25, 50, 100] as $n)
                        <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>
                            {{ $n }}</option>
                    @endforeach
                </select>
                <span>entri</span>
            </div>

            @if ($isAdmin)
                <select id="filterRombel" name="rombel_id" class="toolbar-filter-select">
                    <option value="">Semua Kelas</option>
                    @foreach ($rombelList as $r)
                        <option value="{{ $r->rombongan_belajar_id }}"
                            {{ $activeRombel?->rombongan_belajar_id === $r->rombongan_belajar_id ? 'selected' : '' }}>
                            {{ $r->nama }}
                        </option>
                    @endforeach
                </select>
            @endif

            <select id="filterStatusVerif" name="status_verifikasi" class="toolbar-filter-select">
                <option value="">Semua Status Verifikasi</option>
                @foreach ($statusVerifikasiList as $k => $label)
                    <option value="{{ $k }}" {{ $statusVerif === $k ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>

            @if ($q || $statusVerif || ($isAdmin && $activeRombel))
                <a href="{{ route('dashboard.pip.index', ['tab' => 'usulan', 'tahun' => $activeTahun]) }}"
                    class="btn btn-outline" style="padding: 7px 12px; font-size: 0.84rem;" title="Reset filter">
                    <i class="fas fa-undo"></i>
                </a>
            @endif
        </div>

        <div class="live-search-wrap">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="searchPip" name="q" placeholder="Cari nama / NISN / alasan..."
                value="{{ $q }}" autocomplete="off">
            <button type="button" id="clearSearchPip" class="clear-search {{ !empty($q) ? 'visible' : '' }}"
                title="Hapus pencarian">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    @if ($isAdmin)
        <div id="bulkActionBar"
            style="display: none; margin-top: 14px; padding: 10px 14px; border-radius: 8px; background: rgba(99,102,241,0.08); border: 1px solid rgba(99,102,241,0.25); align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
            <div
                style="font-size: 0.85rem; font-weight: 700; color: var(--primary); display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-check-square"></i>
                <span><span id="bulkSelectedCount">0</span> usulan dipilih</span>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button type="button" class="btn btn-outline btn-sm" onclick="pipBulkVerifikasi('set_sudah_diajukan')"
                    style="border-color: #10b981; color: #10b981; font-weight: 600;">
                    <i class="fas fa-check me-1"></i> Tandai Sudah Diusulkan
                </button>
                <button type="button" class="btn btn-outline btn-sm" onclick="pipBulkVerifikasi('set_belum_diajukan')"
                    style="border-color: #f59e0b; color: #f59e0b; font-weight: 600;">
                    <i class="fas fa-clock me-1"></i> Tandai Belum Diusulkan
                </button>
                <button type="button" class="btn btn-outline btn-sm" onclick="pipBulkVerifikasi('set_ditolak')"
                    style="border-color: #ef4444; color: #ef4444; font-weight: 600;">
                    <i class="fas fa-ban me-1"></i> Tandai Ditolak
                </button>
            </div>
        </div>
    @endif
</div>

<!-- Datatable Usulan -->
<div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
        <thead>
            <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                @if ($isAdmin)
                    <th style="width: 40px; text-align: center; padding: 12px 10px;">
                        <input type="checkbox" id="checkAllPip" title="Pilih Semua di Halaman Ini">
                    </th>
                @endif
                <th
                    style="padding: 12px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); width: 110px; text-align: center;">
                    Prioritas</th>
                <th
                    style="padding: 12px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">
                    Peserta Didik</th>
                <th
                    style="padding: 12px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">
                    Kelas</th>
                <th
                    style="padding: 12px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">
                    Kriteria / Alasan Usulan</th>
                <th
                    style="padding: 12px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">
                    Status Web PIP</th>
                <th
                    style="padding: 12px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); text-align: center; width: 150px;">
                    Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($usulanList as $u)
                <tr style="border-bottom: 1px solid var(--border-color);">
                    @if ($isAdmin)
                        <td style="text-align: center; padding: 12px 10px;" data-label="Pilih">
                            <input type="checkbox" class="check-pip-row" value="{{ $u->id }}">
                        </td>
                    @endif
                    <td style="padding: 10px 14px; text-align: center;" data-label="Prioritas">
                        <div class="pip-priority-stepper">
                            <button type="button" class="pip-stepper-btn pip-btn-up"
                                title="Naikkan prioritas (Urutan lebih awal)"
                                onclick="pipMovePrioritas({{ $u->id }}, 'up')">
                                <i class="fas fa-chevron-up"></i>
                            </button>
                            <div class="pip-stepper-badge" title="Nomor Urut Prioritas">
                                <span class="pip-stepper-hash">#</span>
                                <input type="number" min="1" value="{{ $u->prioritas }}"
                                    class="pip-stepper-input pip-rank-input" data-id="{{ $u->id }}"
                                    data-current="{{ $u->prioritas }}"
                                    title="Ketik angka urutan lalu tekan Enter / klik di luar">
                            </div>
                            <button type="button" class="pip-stepper-btn pip-btn-down"
                                title="Turunkan prioritas (Urutan berikutnya)"
                                onclick="pipMovePrioritas({{ $u->id }}, 'down')">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                    </td>
                    <td style="padding: 12px 14px;" data-label="Peserta Didik">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <img src="{{ $u->foto_url ?: asset('img/logo-dark.png') }}" alt="{{ $u->nama_pd }}"
                                style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 1.5px solid var(--border-color); flex-shrink: 0;"
                                onerror="this.src='/img/logo-dark.png'">
                            <div>
                                <div style="font-weight: 700; font-size: 0.88rem;">{{ $u->nama_pd }}</div>
                                <div style="font-size: 0.74rem; color: var(--text-muted); font-family: monospace;">
                                    NISN: {{ $u->nisn ?: '-' }} &bull; NIPD: {{ $u->nipd ?: '-' }}
                                </div>
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
                        <div style="font-size: 0.68rem; color: var(--text-muted); margin-top: 3px;">
                            oleh {{ $u->diusulkan_oleh ?: '-' }}</div>
                    </td>
                    <td style="padding: 12px 14px;" data-label="Status">
                        <span class="badge {{ $statusColor[$u->status_verifikasi] ?? 'badge-outline' }}"
                            style="font-size: 0.72rem; padding: 3px 8px;">
                            {{ $statusVerifikasiList[$u->status_verifikasi] ?? $u->status_verifikasi }}
                        </span>
                        @if ($u->diverifikasi_oleh)
                            <div style="font-size: 0.68rem; color: var(--text-muted); margin-top: 3px;">
                                {{ $u->diverifikasi_oleh }}
                                @if ($u->verifikasi_admin_at)
                                    &bull; {{ \Carbon\Carbon::parse($u->verifikasi_admin_at)->format('d/m/Y H:i') }}
                                @endif
                            </div>
                        @endif
                        @if ($u->catatan_admin)
                            <div style="font-size: 0.7rem; color: var(--primary); margin-top: 2px;">
                                <i class="fas fa-comment-dots me-1"></i>{{ $u->catatan_admin }}
                            </div>
                        @endif
                    </td>
                    <td style="padding: 12px 14px; text-align: center;" data-label="Aksi">
                        <div style="display: flex; gap: 6px; justify-content: center; align-items: center;">
                            @if ($isAdmin)
                                <select class="form-control pip-verif-select" data-id="{{ $u->id }}"
                                    data-current="{{ $u->status_verifikasi }}"
                                    style="min-width: 120px; height: 32px; padding: 0 8px; font-size: 0.76rem;">
                                    @foreach ($statusVerifikasiList as $k => $label)
                                        <option value="{{ $k }}"
                                            {{ $u->status_verifikasi === $k ? 'selected' : '' }}>
                                            {{ $label }}</option>
                                    @endforeach
                                </select>
                            @endif
                            <button type="button" class="btn-icon" style="color: #ef4444;" title="Hapus Usulan"
                                onclick="pipHapusUsulan({{ $u->id }}, @js($u->nama_pd))">
                                <i class="fas fa-trash-alt"></i>
                            </button>
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

@include('dashboard.pip.partials.pagination', ['paginator' => $usulanList])
