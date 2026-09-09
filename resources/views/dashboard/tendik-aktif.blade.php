@extends('layouts.dashboard')

@section('title', 'Manajemen Data — Tendik Aktif — SAE')
@section('dash_title', 'Tendik Aktif')

@section('content')
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-id-badge text-primary me-2"></i> Manajemen Data — Tendik Aktif
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Direktori Tenaga Kependidikan (TU, Laboran, Perpustakaan, Staf) bersumber dari tabel <strong>GTK</strong> (Dapodik).
            </p>
        </div>
        <div class="dash-banner-actions">
            <a href="{{ route('dashboard.dapodik') }}" class="btn btn-outline" style="padding: 9px 16px; font-size: 0.85rem;">
                <i class="fas fa-cloud-arrow-down me-1"></i> Tarik Data Dapodik
            </a>
        </div>
    </div>

    <!-- Summary Stats Grid -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                <i class="fas fa-users"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ number_format($summary['total'], 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Total Tendik Aktif</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                <i class="fas fa-id-badge"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ $summary['pns'] }}
                </div>
                <div class="dash-stat-label">PNS / ASN</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
                <i class="fas fa-briefcase"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ $summary['non_pns'] }}
                </div>
                <div class="dash-stat-label">Non-PNS / Honorer</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59,130,246,0.15); color: #3b82f6;">
                <i class="fas fa-venus-mars"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ $summary['laki'] }} / {{ $summary['perempuan'] }}
                </div>
                <div class="dash-stat-label">Laki / Perempuan</div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="card" style="padding: 16px 20px; margin-bottom: 20px;">
        <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
            <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; flex: 1; min-width: 280px;">
                <!-- Per Page -->
                <div style="display: flex; align-items: center; gap: 6px; font-size: 0.82rem; color: var(--text-muted);">
                    <span>Tampilkan</span>
                    <select id="perPageSelect" class="form-select"
                        style="padding: 6px 10px; font-size: 0.82rem; border-radius: 6px; width: auto; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color);">
                        @foreach ([10, 15, 25, 50, 100] as $n)
                            <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Status Kepegawaian -->
                <select id="filterStatus" class="form-select"
                    style="padding: 6px 10px; font-size: 0.82rem; border-radius: 6px; width: auto; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color); max-width: 220px;">
                    <option value="">Semua Status Kepegawaian</option>
                    @foreach ($filterStatus as $st)
                        <option value="{{ $st }}" {{ $status === $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>

                <!-- Filter Gender -->
                <select id="filterGender" class="form-select"
                    style="padding: 6px 10px; font-size: 0.82rem; border-radius: 6px; width: auto; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color);">
                    <option value="">Semua Gender</option>
                    <option value="L" {{ $gender === 'L' ? 'selected' : '' }}>Laki-Laki (L)</option>
                    <option value="P" {{ $gender === 'P' ? 'selected' : '' }}>Perempuan (P)</option>
                </select>

                @if ($status || $gender || $q)
                    <a href="{{ route('dashboard.tendik-aktif.index') }}" class="btn btn-outline"
                        style="padding: 6px 12px; font-size: 0.8rem; border-radius: 6px; color: var(--text-muted);">
                        <i class="fas fa-times me-1"></i> Reset Filter
                    </a>
                @endif
            </div>

            <!-- Live Search Box -->
            <div style="position: relative; min-width: 260px;">
                <i class="fas fa-search"
                    style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.85rem;"></i>
                <input type="text" id="liveSearch" value="{{ $q }}"
                    placeholder="Cari nama / NUPTK / NIP / tugas..."
                    style="padding: 7px 32px 7px 34px; font-size: 0.82rem; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color); width: 100%;">
                @if ($q)
                    <button type="button" id="clearSearch"
                        style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 0;">
                        <i class="fas fa-times"></i>
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Table Tendik -->
    <div class="card table-responsive-stack" style="padding: 0; overflow: hidden; margin-bottom: 20px;">
        <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--border-color);">
                    <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: left; cursor: pointer;"
                        data-sort="nama" class="sortable-th">
                        Nama Tendik
                        @if ($sort === 'nama')
                            <span style="color: var(--primary);">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                        @else
                            <span style="opacity: 0.3;">▲▼</span>
                        @endif
                    </th>
                    <th style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: left; cursor: pointer;"
                        data-sort="nuptk" class="sortable-th">
                        NUPTK / NIP
                        @if ($sort === 'nuptk')
                            <span style="color: var(--primary);">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                        @else
                            <span style="opacity: 0.3;">▲▼</span>
                        @endif
                    </th>
                    <th style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; cursor: pointer;"
                        data-sort="jenis_kelamin" class="sortable-th">
                        L/P
                        @if ($sort === 'jenis_kelamin')
                            <span style="color: var(--primary);">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                        @else
                            <span style="opacity: 0.3;">▲▼</span>
                        @endif
                    </th>
                    <th style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: left; cursor: pointer;"
                        data-sort="jabatan_ptk_id_str" class="sortable-th">
                        Tugas / Jabatan
                        @if ($sort === 'jabatan_ptk_id_str')
                            <span style="color: var(--primary);">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                        @else
                            <span style="opacity: 0.3;">▲▼</span>
                        @endif
                    </th>
                    <th style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: left; cursor: pointer;"
                        data-sort="status_kepegawaian_id_str" class="sortable-th">
                        Status
                        @if ($sort === 'status_kepegawaian_id_str')
                            <span style="color: var(--primary);">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                        @else
                            <span style="opacity: 0.3;">▲▼</span>
                        @endif
                    </th>
                    <th style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: left;">
                        Pendidikan
                    </th>
                    <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                        Aksi
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $tendik)
                    <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.15s ease;">
                        <td style="padding: 12px 16px; font-size: 0.85rem;" data-label="Nama Tendik">
                            <div style="font-weight: 700; color: var(--text-color);">{{ $tendik->nama }}</div>
                            @if ($tendik->email)
                                <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 2px;">
                                    <i class="far fa-envelope me-1"></i>{{ $tendik->email }}
                                </div>
                            @endif
                        </td>
                        <td style="padding: 12px 14px; font-size: 0.83rem; color: var(--text-color);" data-label="NUPTK / NIP">
                            <div>{{ $tendik->nuptk ?: '-' }}</div>
                            @if ($tendik->nip)
                                <div style="font-size: 0.76rem; color: var(--text-muted);">NIP: {{ $tendik->nip }}</div>
                            @endif
                        </td>
                        <td style="padding: 12px 14px; font-size: 0.83rem; text-align: center;" data-label="L/P">
                            <span class="badge {{ $tendik->jenis_kelamin === 'L' ? 'badge-primary' : 'badge-danger' }}"
                                style="font-size: 0.75rem; padding: 3px 8px;">
                                {{ $tendik->jenis_kelamin }}
                            </span>
                        </td>
                        <td style="padding: 12px 14px; font-size: 0.83rem;" data-label="Tugas / Jabatan">
                            <span class="badge badge-info" style="font-size: 0.75rem; padding: 4px 8px; font-weight: 600;">
                                {{ $tendik->jabatan_ptk ?: ($tendik->jenis_ptk ?: 'Tenaga Kependidikan') }}
                            </span>
                        </td>
                        <td style="padding: 12px 14px; font-size: 0.83rem;" data-label="Status">
                            <span class="badge {{ str_contains(strtoupper($tendik->status_kepegawaian ?? ''), 'PNS') ? 'badge-success' : 'badge-warning' }}"
                                style="font-size: 0.75rem; padding: 3px 8px;">
                                {{ $tendik->status_kepegawaian ?: '-' }}
                            </span>
                        </td>
                        <td style="padding: 12px 14px; font-size: 0.82rem; color: var(--text-muted);" data-label="Pendidikan">
                            <div>{{ $tendik->pendidikan_terakhir ?: '-' }}</div>
                            @if ($tendik->bidang_studi_terakhir)
                                <div style="font-size: 0.75rem; color: var(--text-muted); opacity: 0.85;">
                                    {{ $tendik->bidang_studi_terakhir }}
                                </div>
                            @endif
                        </td>
                        <td style="padding: 12px 16px; text-align: center;" data-label="Aksi">
                            <button type="button" class="btn btn-outline btn-detail-tendik"
                                data-id="{{ $tendik->ptk_id }}"
                                style="padding: 5px 10px; font-size: 0.78rem; border-radius: 6px;"
                                title="Lihat Profil Lengkap">
                                <i class="fas fa-eye me-1"></i> Detail
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding: 40px; text-align: center; color: var(--text-muted);">
                            <i class="fas fa-user-slash" style="font-size: 2.5rem; opacity: 0.4; margin-bottom: 12px;"></i>
                            <p style="font-size: 0.9rem; margin: 0;">Tidak ada data Tenaga Kependidikan yang sesuai dengan kriteria pencarian.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if ($list->hasPages())
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 24px;">
            <div style="font-size: 0.82rem; color: var(--text-muted);">
                Menampilkan {{ $list->firstItem() ?? 0 }} - {{ $list->lastItem() ?? 0 }} dari {{ $total }} Tendik
            </div>
            <div>
                {{ $list->appends(request()->query())->links() }}
            </div>
        </div>
    @endif

    <!-- Detail Tendik Modal -->
    <div id="modalDetailTendik" class="modal-backdrop" style="display: none;">
        <div class="modal-box" style="max-width: 650px; width: 92%;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
                <h3 style="font-size: 1.1rem; font-weight: 700; margin: 0; color: var(--text-color);">
                    <i class="fas fa-id-badge text-primary me-2"></i> Profil Tenaga Kependidikan
                </h3>
                <button type="button" class="btn-close-modal"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="modalBodyTendik" style="font-size: 0.85rem; color: var(--text-color); max-height: 70vh; overflow-y: auto;">
                <div style="text-align: center; padding: 30px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 1.8rem; color: var(--primary);"></i>
                    <p style="margin-top: 10px; color: var(--text-muted);">Memuat data...</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @if (file_exists(public_path('js/tendik-aktif.js')))
        <script src="{{ asset('js/tendik-aktif.js') }}?v={{ filemtime(public_path('js/tendik-aktif.js')) }}"></script>
    @endif
@endpush
