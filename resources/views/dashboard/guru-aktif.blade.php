@extends('layouts.dashboard')

@section('title', 'Manajemen Data — Guru Aktif — SAE')
@section('dash_title', 'Guru & Tendik Aktif')

@section('content')
    @php
        $isAdmin = $isAdmin ?? (session('user')['role'] ?? (session('user')->role ?? '')) === 'admin';
    @endphp
    <div class="dash-banner">
        <div>
            <h2 style="font-size: var(--text-2xl, 1.5rem); font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-chalkboard-user text-primary me-2"></i> Manajemen Data — Guru Aktif
            </h2>
            <p style="color: var(--text-muted); font-size: var(--text-base, 0.875rem);">
                Direktori Guru &amp; Tenaga Pendidik aktif bersumber dari tabel <strong>GTK</strong> (Dapodik).
            </p>
        </div>
        <div class="dash-banner-actions">
            <a href="{{ route('dashboard.dapodik') }}" class="btn btn-outline" style="padding: 8px 14px; font-size: var(--text-base, 0.875rem);"
                title="Tarik Data Dapodik">
                <i class="fas fa-cloud-arrow-down"></i>
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
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                    {{ number_format($summary['total'], 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Total Guru Aktif</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: var(--success, #10b981);">
                <i class="fas fa-id-badge"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                    {{ $summary['pns'] }}
                </div>
                <div class="dash-stat-label">PNS / ASN</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: var(--warning, #f59e0b);">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                    {{ $summary['non_pns'] }}
                </div>
                <div class="dash-stat-label">Non-PNS / Honorer</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
                <i class="fas fa-venus-mars"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                    {{ $summary['laki'] }} / {{ $summary['perempuan'] }}
                </div>
                <div class="dash-stat-label">Laki / Perempuan</div>
            </div>
        </div>
    </div>

    <!-- Unified Datatable Guru & Tendik (Toolbar + Table + Pagination dalam 1 Card Terpadu) -->
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
                                    {{ $n }}
                                </option>
                            @endforeach
                        </select>
                        <span>entri</span>
                    </div>

                    <select id="filterJenis" name="jenis" class="toolbar-filter-select" style="height: 38px;">
                        <option value="">Semua Jenis PTK</option>
                        @foreach ($filterJenis as $j)
                            <option value="{{ $j }}" {{ $jenis === $j ? 'selected' : '' }}>{{ $j }}
                            </option>
                        @endforeach
                    </select>

                    <select id="filterStatus" name="status" class="toolbar-filter-select" style="height: 38px;">
                        <option value="">Semua Status Kepegawaian</option>
                        @foreach ($filterStatus as $s)
                            <option value="{{ $s }}" {{ $status === $s ? 'selected' : '' }}>
                                {{ $s }}
                            </option>
                        @endforeach
                    </select>

                    <select id="filterGender" name="gender" class="toolbar-filter-select"
                        style="height: 38px; min-width: 120px;">
                        <option value="">Semua Gender</option>
                        <option value="L" {{ $gender === 'L' ? 'selected' : '' }}>Laki-Laki (L)</option>
                        <option value="P" {{ $gender === 'P' ? 'selected' : '' }}>Perempuan (P)</option>
                    </select>

                    @if ($q || $jenis || $status || $gender)
                        <a href="{{ route('dashboard.guru-aktif.index') }}" class="btn btn-outline btn-reset-filter"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                            title="Reset filter" data-live-reset>
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>

                <div class="live-search-wrap" style="height: 38px;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="liveSearch" name="q" placeholder="Cari nama / NUPTK / NIP..."
                        value="{{ $q }}" autocomplete="off">
                    <button type="button" id="clearSearch" class="clear-search {{ $q ? 'visible' : '' }}"
                        title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. Konten Datatable Responsive Stack -->
        <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        @php
                            $cols = [
                                ['nama', 'Nama GTK'],
                                ['nuptk', 'NUPTK / NIP'],
                                ['jenis_kelamin', 'L/P'],
                                ['jenis_ptk_id_str', 'Jenis PTK'],
                                ['status_kepegawaian_id_str', 'Status'],
                            ];
                        @endphp
                        @foreach ($cols as [$key, $label])
                            <th class="sortable-th {{ $sort === $key ? 'sorted' : '' }}"
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; {{ $key === 'jenis_kelamin' ? 'text-align: center;' : '' }}"
                                data-sort="{{ $key }}">
                                {{ $label }}
                                <span class="sort-icon">{!! $sort === $key ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                        @endforeach
                        <th
                            style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Pendidikan / Mapel
                        </th>
                        <th
                            style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($list as $item)
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <td style="padding: 14px 18px; font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);"
                                data-label="Nama">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div
                                        style="width: 32px; height: 32px; border-radius: var(--radius-md, 8px); background: rgba(99,102,241,0.12); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: var(--text-sm, 0.8125rem); flex-shrink: 0;">
                                        <i class="fas fa-chalkboard-user"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 700; color: var(--text-color);">{{ $item->nama }}</div>
                                        @if (!empty($item->nik))
                                            <div
                                                style="font-size: var(--text-xs, 0.75rem); font-family: monospace; color: var(--text-muted); margin-top: 1px;">
                                                NIK: <span class="copyable" data-copy="{{ $item->nik }}"
                                                    data-label="NIK"
                                                    title="Klik untuk salin NIK">{{ $item->nik }}</span>
                                            </div>
                                        @endif
                                        @if ($item->email)
                                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                                {{ $item->email }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 14px 18px; font-family: monospace; font-size: var(--text-base, 0.875rem); color: var(--primary);"
                                data-label="NUPTK/NIP">
                                <div class="cell-col-right">
                                    <div>
                                        @if ($item->nuptk)
                                            <span class="copyable" data-copy="{{ $item->nuptk }}" data-label="NUPTK"
                                                title="Klik untuk salin NUPTK">{{ $item->nuptk }}</span>
                                        @else
                                            -
                                        @endif
                                    </div>
                                    @if ($item->nip)
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                            <span class="copyable" data-copy="{{ $item->nip }}" data-label="NIP"
                                                title="Klik untuk salin NIP">NIP: {{ $item->nip }}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td style="padding: 14px 18px; text-align: center;" data-label="L/P">
                                <span class="badge {{ $item->jenis_kelamin === 'L' ? 'badge-primary' : 'badge-danger' }}"
                                    style="font-size: var(--text-xs, 0.75rem); padding: 2px 7px;">
                                    {{ $item->jenis_kelamin ?: '-' }}
                                </span>
                            </td>
                            <td style="padding: 14px 18px; font-size: var(--text-base, 0.875rem); color: var(--text-color);"
                                data-label="Jenis">
                                {{ $item->jenis_ptk ?: '-' }}
                            </td>
                            <td style="padding: 14px 18px; font-size: var(--text-sm, 0.8125rem);" data-label="Status">
                                <span
                                    class="badge {{ str_contains(strtoupper($item->status_kepegawaian ?? ''), 'PNS') ? 'badge-primary' : 'badge-outline' }}"
                                    style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;">
                                    {{ $item->status_kepegawaian ?: '-' }}
                                </span>
                            </td>
                            <td style="padding: 14px 18px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);"
                                data-label="Pendidikan">
                                <div class="cell-col-right">
                                    <div style="color: var(--text-color); font-weight: 500;">
                                        {{ $item->pendidikan_terakhir ?: '-' }}</div>
                                    @if ($item->bidang_studi_terakhir)
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                            {{ $item->bidang_studi_terakhir }}</div>
                                    @endif
                                </div>
                            </td>
                            <td style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                                <div class="table-actions">
                                    @if ($isAdmin)
                                        <button type="button" class="btn-icon" title="Lihat Profil Lengkap"
                                            onclick="openBiodataGuruModal('{{ $item->ptk_id }}')">
                                            <i class="fas fa-id-card"></i>
                                        </button>
                                    @else
                                        <span style="color: var(--text-muted); font-size: var(--text-sm, 0.8125rem);">-</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7"
                                style="padding: 30px; text-align: center; color: var(--text-muted); font-size: var(--text-base, 0.875rem);">
                                <i class="fas fa-folder-open mb-2" style="font-size: var(--text-3xl, 1.875rem); opacity: 0.5;"></i>
                                <div>Belum ada data Guru &amp; Tendik yang cocok.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 3. Footer Paginasi Terpadu Datatable -->
        @if ($list->hasPages())
            <div class="datatable-footer">
                @include('partials.datatable-pagination', ['paginator' => $list])
            </div>
        @endif
    </div>

    {{-- Modal Profil GTK --}}
    <div id="gtkModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 640px; width: 92%; max-height: 85vh; display: flex; flex-direction: column; margin: 0; border-radius: var(--radius-xl, 16px); padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div>
                    <h3 id="gtkJabatan"
                        style="font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-color); margin: 0;">Profil Guru
                        &amp; Tendik</h3>
                    <div id="gtkSubtitle" style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">-</div>
                </div>
                <button type="button" onclick="closeBiodataGuruModal()"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.125rem);" aria-label="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="gtkLoading" style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin me-2" style="font-size: var(--text-2xl, 1.5rem);"></i>
                <div>Memuat profil GTK...</div>
            </div>

            <div id="gtkContent" style="overflow-y: auto; flex: 1; display: none; font-size: var(--text-base, 0.875rem);">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted); width: 140px;">Nama Lengkap</td>
                        <td id="gtkJkNama" style="font-weight: 700;">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">NUPTK / NIP</td>
                        <td id="gtkJkNuptk" style="font-family: monospace; color: var(--primary);">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">NIK</td>
                        <td id="gtkJkNik" style="font-family: monospace;">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Jenis Kelamin</td>
                        <td id="gtkJkGender">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Tempat, Tgl Lahir</td>
                        <td id="gtkJkTtl">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Agama</td>
                        <td id="gtkJkAgama">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Status Kepegawaian</td>
                        <td id="gtkJkStatus">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Pendidikan Terakhir</td>
                        <td id="gtkJkPend">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Bidang Studi / Mapel</td>
                        <td id="gtkJkMapel">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Status Induk / Tgl Tugas</td>
                        <td id="gtkJkInduk">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">No. HP / Email</td>
                        <td id="gtkJkHp">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Alamat</td>
                        <td id="gtkJkAlamat">-</td>
                    </tr>
                </table>

                <div id="gtkBebanSection"
                    style="margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color); display: none;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);"><i
                                class="fas fa-book-bookmark text-primary me-1"></i> Penugasan Mengajar
                            (Pembelajaran)</span>
                        <span id="gtkJmlJam" class="badge"
                            style="background: rgba(245,158,11,0.15); color: var(--warning, #f59e0b); font-size: var(--text-xs, 0.75rem); padding: 2px 7px;">0
                            JP</span>
                    </div>
                    <div style="overflow-x: auto;">
                        <table class="table"
                            style="width: 100%; border-collapse: collapse; font-size: var(--text-sm, 0.8125rem); margin-bottom: 0;">
                            <thead>
                                <tr
                                    style="background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--border-color);">
                                    <th style="padding: 6px 10px; color: var(--text-muted);">Mata Pelajaran</th>
                                    <th style="padding: 6px 10px; color: var(--text-muted);">Rombel</th>
                                    <th style="padding: 6px 10px; color: var(--text-muted); text-align: center;">Jam/Mg
                                    </th>
                                    <th style="padding: 6px 10px; color: var(--text-muted);">Status</th>
                                </tr>
                            </thead>
                            <tbody id="gtkBebanList"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div
                style="display: flex; justify-content: flex-end; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color);">
                <button type="button" class="btn btn-outline" onclick="closeBiodataGuruModal()"
                    style="padding: 8px 18px; font-size: var(--text-sm, 0.8125rem);">Tutup</button>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/guru-aktif.js') }}?v={{ filemtime(public_path('js/guru-aktif.js')) }}"></script>
    @endpush
@endsection
