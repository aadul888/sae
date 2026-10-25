@extends('layouts.dashboard')

@section('title', 'Log & Aktivitas Kerja Harian Tendik — SAE')
@section('dash_title', 'Aktivitas Harian Tenaga Kependidikan')

@section('content')
    <!-- Top Welcome & Action Banner -->
    <div class="dash-banner"
        style="margin-bottom: 16px; background: linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, rgba(59, 130, 246, 0.08) 100%); border: 1px solid rgba(16, 185, 129, 0.25); display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; border-radius: var(--radius-xl, 16px); padding: 20px 24px;">
        <div style="flex: 1; min-width: 260px;">
            <h2
                style="font-size: var(--text-2xl, 1.5rem); font-weight: 800; color: var(--text-color); margin-bottom: 4px; line-height: 1.25;">
                <i class="fas fa-clipboard-check text-primary me-2"></i> Log Aktivitas &amp; Agenda Pekerjaan
                <span class="badge badge-primary"
                    style="font-size: var(--text-xs, 0.75rem); padding: 4px 10px; margin-left: 8px; vertical-align: middle; border-radius: var(--radius-sm, 6px);">
                    <i class="fas fa-briefcase me-1"></i>{{ $bidangOptions[$currentBidang] ?? 'Administrasi Umum' }}
                </span>
            </h2>
            <div
                style="display: flex; gap: 14px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); flex-wrap: wrap; margin-bottom: 6px;">
                <span title="Nama Pegawai"><i class="fas fa-user text-primary me-1"></i>
                    <strong>{{ $userName }}</strong></span>
                <span title="Bulan Aktif"><i class="fas fa-calendar-alt text-warning me-1"></i>
                    <strong>{{ \Carbon\Carbon::createFromDate($tahun, $bulan, 1)->translatedFormat('F Y') }}</strong></span>
            </div>
            <p style="margin: 0; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                Catat seluruh progres pekerjaan, agenda dinas, disposisi, dan hasil keluaran tugas harian khusus bidang
                {{ $bidangOptions[$currentBidang] ?? 'Administrasi Umum' }}.
            </p>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            @if ($canCreate)
                <button type="button" class="btn btn-primary btn-open-modal-tambah" id="btnOpenModalTambah"
                    title="Catat Aktivitas Harian Baru"
                    style="height: 38px; padding: 0 16px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem); font-weight: 600; background: #10b981; border: none; box-shadow: 0 4px 12px rgba(16,185,129,0.3); color: #fff; cursor: pointer;">
                    <i class="fas fa-plus"></i>
                    <span>Catat Aktivitas</span>
                </button>
            @endif
            <a href="{{ route('dashboard.tendik.target.index', ['bidang' => $currentBidang]) }}" class="btn btn-outline"
                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem); text-decoration: none;"
                title="Target &amp; Capaian Kinerja">
                <i class="fas fa-bullseye text-primary"></i>
            </a>
            <a href="{{ route('dashboard.tendik.laporan.index', ['bidang' => $currentBidang]) }}" class="btn btn-outline"
                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem); text-decoration: none;"
                title="Laporan Kinerja Tendik">
                <i class="fas fa-file-invoice text-info"></i>
            </a>
        </div>
    </div>

    <!-- Navigasi Terpadu 3 Menu Utama Tendik (Target -> Aktivitas -> Laporan) -->
    <div class="periode-nav-wrapper" style="margin-bottom: 22px;">
        <div class="periode-nav-desktop">
            <a href="{{ route('dashboard.tendik.target.index', ['bidang' => $currentBidang]) }}" class="periode-nav-tab">
                <i class="fas fa-bullseye"></i> 1. Target &amp; Capaian
            </a>
            <a href="{{ route('dashboard.tendik.aktivitas.index', ['bidang' => $currentBidang]) }}"
                class="periode-nav-tab active">
                <i class="fas fa-clipboard-check"></i> 2. Input Aktivitas Harian
            </a>
            <a href="{{ route('dashboard.tendik.laporan.index', ['bidang' => $currentBidang]) }}" class="periode-nav-tab">
                <i class="fas fa-file-invoice"></i> 3. Laporan Kinerja
            </a>
        </div>
    </div>

    <!-- Flash Messages Standar SAE -->
    @if (session('success'))
        <div
            style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: #10b981; padding: 10px 14px; border-radius: var(--radius-lg, 12px); margin-bottom: 16px; font-size: var(--text-base, 0.875rem); display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-check-circle"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if (session('error'))
        <div
            style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #ef4444; padding: 10px 14px; border-radius: var(--radius-lg, 12px); margin-bottom: 16px; font-size: var(--text-base, 0.875rem); display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-circle-exclamation"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif
    @if (isset($errors) && $errors->any())
        <div
            style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #ef4444; padding: 10px 14px; border-radius: var(--radius-lg, 12px); margin-bottom: 16px; font-size: var(--text-base, 0.875rem);">
            <div style="font-weight: 700; margin-bottom: 4px;"><i class="fas fa-triangle-exclamation me-1"></i> Periksa
                kembali isian formulir:</div>
            <ul style="margin: 0; padding-left: 18px;">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Quick Stats Grid -->
    <div class="dash-stat-grid"
        style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $totalAktivitas }} Agenda</div>
                <div class="dash-stat-label">Total Aktivitas Bulan Ini</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                <i class="fas fa-check-double"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: #10b981;">{{ $totalSelesai }} Selesai</div>
                <div class="dash-stat-label">Tuntas Dikerjakan</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                <i class="fas fa-spinner"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: #f59e0b;">{{ $totalProses }} Proses</div>
                <div class="dash-stat-label">Sedang Dikerjakan</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239, 68, 68, 0.15); color: #ef4444;">
                <i class="fas fa-clock-rotate-left"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: #ef4444;">{{ $totalTertunda }} Tertunda</div>
                <div class="dash-stat-label">Ada Kendala / Pending</div>
            </div>
        </div>
    </div>

    <!-- Unified Datatable Aktivitas Tendik (Toolbar + Table + Pagination dalam 1 Card Terpadu) -->
    <div class="card datatable-card-wrapper" id="tableDataContainer">
        <!-- 1. Toolbar Header (Entries, Filters & Live Search) -->
        <div class="datatable-toolbar">
            <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <div class="toolbar-entries">
                        <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                        <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                            @foreach ([10, 15, 25, 50, 100] as $n)
                                <option value="{{ $n }}" {{ ($perPage ?? 15) == $n ? 'selected' : '' }}>
                                    {{ $n }}</option>
                            @endforeach
                        </select>
                        <span>entri</span>
                    </div>

                    <!-- Bulan -->
                    <select id="filterBulan" name="bulan" class="toolbar-filter-select" style="height: 38px;">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ (int) $bulan === $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>

                    <!-- Tahun -->
                    <select id="filterTahun" name="tahun" class="toolbar-filter-select" style="height: 38px;">
                        @for ($y = date('Y') + 1; $y >= date('Y') - 2; $y--)
                            <option value="{{ $y }}" {{ (int) $tahun === $y ? 'selected' : '' }}>
                                {{ $y }}</option>
                        @endfor
                    </select>

                    <!-- Status -->
                    <select id="filterStatus" name="status" class="toolbar-filter-select" style="height: 38px;">
                        <option value="">Semua Status</option>
                        <option value="selesai" {{ ($filterStatus ?? '') === 'selesai' ? 'selected' : '' }}>Selesai</option>
                        <option value="proses" {{ ($filterStatus ?? '') === 'proses' ? 'selected' : '' }}>Sedang Proses
                        </option>
                        <option value="tertunda" {{ ($filterStatus ?? '') === 'tertunda' ? 'selected' : '' }}>Tertunda
                        </option>
                    </select>

                    @if ($isKepalaTas)
                        <!-- Bidang Filter untuk Kepala TAS / Admin (Khusus Per Bidang) -->
                        <select id="filterBidang" name="bidang" class="toolbar-filter-select" style="height: 38px;" title="Pilih Bidang Tugas">
                            @foreach ($bidangOptions as $bKey => $bLabel)
                                <option value="{{ $bKey }}" {{ $currentBidang === $bKey ? 'selected' : '' }}>
                                    {{ $bLabel }}</option>
                            @endforeach
                        </select>
                    @endif

                    <!-- Filter Sasaran / Target Indikator Kinerja (Khusus Bidang Ini) -->
                    <select id="filterIndikator" name="indikator_id" class="toolbar-filter-select" style="max-width: 260px; height: 38px;">
                        <option value="">Semua Sasaran {{ $bidangOptions[$currentBidang] ?? ucfirst($currentBidang) }}
                        </option>
                        @foreach ($indikatorKinerjaList ?? [] as $ind)
                            <option value="{{ $ind->id }}"
                                {{ (string) ($filterIndikator ?? '') === (string) $ind->id ? 'selected' : '' }}>
                                {{ \Illuminate\Support\Str::limit($ind->sasaran, 30) }}
                            </option>
                        @endforeach
                    </select>

                    @if (!empty($q) || !empty($filterStatus) || ($isKepalaTas && !empty($filterBidang) && $filterBidang !== $activeBidang) || !empty($filterIndikator) || (int)$bulan !== (int)date('m') || (int)$tahun !== (int)date('Y'))
                        <a href="{{ route('dashboard.tendik.aktivitas.index', ['bidang' => $currentBidang]) }}" class="btn btn-outline btn-reset-filter"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                            title="Reset filter" data-live-reset>
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>

                <div class="live-search-wrap" style="height: 38px;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="liveSearch" name="q" placeholder="Cari aktivitas / uraian..."
                        value="{{ $q ?? '' }}" autocomplete="off">
                    <button type="button" id="clearSearch" class="clear-search {{ !empty($q) ? 'visible' : '' }}"
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
                    <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                        <th class="sortable-th {{ ($sort ?? '') === 'tanggal' ? 'sorted' : '' }}" data-sort="tanggal"
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px; cursor: pointer;">
                            Waktu &amp; Tanggal
                            @if (($sort ?? '') === 'tanggal')
                                <span style="color: var(--primary);">{!! ($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;' !!}</span>
                            @else
                                <span style="opacity: 0.3;">&#9650;&#9660;</span>
                            @endif
                        </th>
                        @if ($isKepalaTas)
                            <th class="sortable-th {{ ($sort ?? '') === 'nama_pegawai' ? 'sorted' : '' }}"
                                data-sort="nama_pegawai"
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer;">
                                Pegawai / Bidang
                                @if (($sort ?? '') === 'nama_pegawai')
                                    <span style="color: var(--primary);">{!! ($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;' !!}</span>
                                @else
                                    <span style="opacity: 0.3;">&#9650;&#9660;</span>
                                @endif
                            </th>
                        @endif
                        <th class="sortable-th {{ ($sort ?? '') === 'judul_aktivitas' ? 'sorted' : '' }}"
                            data-sort="judul_aktivitas"
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer;">
                            Aktivitas / Sasaran Pekerjaan
                            @if (($sort ?? '') === 'judul_aktivitas')
                                <span style="color: var(--primary);">{!! ($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;' !!}</span>
                            @else
                                <span style="opacity: 0.3;">&#9650;&#9660;</span>
                            @endif
                        </th>
                        <th
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Hasil / Output
                        </th>
                        <th class="sortable-th {{ ($sort ?? '') === 'status' ? 'sorted' : '' }}" data-sort="status"
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 110px; cursor: pointer;">
                            Status
                            @if (($sort ?? '') === 'status')
                                <span style="color: var(--primary);">{!! ($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;' !!}</span>
                            @else
                                <span style="opacity: 0.3;">&#9650;&#9660;</span>
                            @endif
                        </th>
                        <th
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 100px;">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($aktivitasList as $item)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td data-label="Waktu & Tanggal" style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem);">
                                <div style="font-weight: 700; color: var(--text-color);">
                                    {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}
                                </div>
                                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-family: monospace;">
                                    <i class="far fa-clock me-1"></i>{{ substr($item->jam_mulai, 0, 5) }}
                                    {{ $item->jam_selesai ? '- ' . substr($item->jam_selesai, 0, 5) : 'WIB' }}
                                </div>
                            </td>

                            @if ($isKepalaTas)
                                <td data-label="Pegawai / Bidang" style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem);">
                                    <div style="font-weight: 600; color: var(--text-color);">{{ $item->nama_pegawai }}</div>
                                    <span class="badge badge-info" style="font-size: var(--text-xs, 0.75rem); padding: 2px 6px;">
                                        {{ $bidangOptions[$item->bidang] ?? ucfirst($item->bidang) }}
                                    </span>
                                </td>
                            @endif

                            <td data-label="Aktivitas" style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem);">
                                @if (!empty($item->indikator))
                                    <div style="margin-bottom: 4px;">
                                        <span class="badge"
                                            style="background: rgba(99,102,241,0.08); color: var(--primary); font-size: var(--text-2xs, 0.6875rem); padding: 2px 7px; border: 1px solid rgba(99,102,241,0.25); display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fas fa-bullseye"></i> {{ $item->indikator->sasaran }}
                                        </span>
                                    </div>
                                @endif
                                <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                                    {{ $item->judul_aktivitas }}
                                </div>
                                @if ($item->lampiran_path)
                                    <div style="margin-top: 6px;">
                                        <a href="{{ asset('storage/' . $item->lampiran_path) }}" target="_blank"
                                            class="btn btn-outline"
                                            style="font-size: var(--text-xs, 0.75rem); padding: 2px 8px; border-radius: var(--radius-xs, 4px); display: inline-flex; align-items: center; gap: 4px; text-decoration: none;">
                                            <i class="fas fa-paperclip text-primary"></i> Lampiran Dokumen
                                        </a>
                                    </div>
                                @endif
                            </td>

                            <td data-label="Hasil / Output"
                                style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); color: var(--text-color);">
                                <div style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600;">
                                    <i class="fas fa-file-circle-check text-success" style="font-size: var(--text-sm, 0.8125rem);"></i>
                                    <span>{{ $item->output_hasil ?: '1 Dokumen / Layanan Terlaksana' }}</span>
                                </div>
                            </td>

                            <td data-label="Status" style="padding: 12px 14px; text-align: center;">
                                @if ($item->status === 'selesai')
                                    <span class="badge badge-success"
                                        style="font-size: var(--text-xs, 0.75rem); padding: 4px 8px; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fas fa-circle-check"></i> Selesai
                                    </span>
                                @elseif ($item->status === 'proses')
                                    <span class="badge badge-warning"
                                        style="font-size: var(--text-xs, 0.75rem); padding: 4px 8px; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fas fa-spinner fa-spin"></i> Proses
                                    </span>
                                @else
                                    <span class="badge badge-danger"
                                        style="font-size: var(--text-xs, 0.75rem); padding: 4px 8px; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fas fa-clock-rotate-left"></i> Tertunda
                                    </span>
                                @endif
                            </td>

                            <td data-label="Aksi" style="padding: 12px 16px; text-align: center;">
                                <div class="table-actions" style="display: flex; gap: 6px; justify-content: center;">
                                    @if ($canUpdate)
                                        <button type="button" class="btn-icon btn-edit-aktivitas"
                                            data-id="{{ $item->id }}"
                                            data-tanggal="{{ $item->tanggal ? ($item->tanggal instanceof \Carbon\Carbon ? $item->tanggal->format('Y-m-d') : \Carbon\Carbon::parse($item->tanggal)->format('Y-m-d')) : '' }}"
                                            data-jam_mulai="{{ substr($item->jam_mulai, 0, 5) }}"
                                            data-jam_selesai="{{ $item->jam_selesai ? substr($item->jam_selesai, 0, 5) : '' }}"
                                            data-bidang="{{ $item->bidang }}" data-judul="{{ $item->judul_aktivitas }}"
                                            data-uraian="{{ $item->uraian_pekerjaan }}"
                                            data-output="{{ $item->output_hasil }}" data-status="{{ $item->status }}"
                                            data-ptk="{{ $item->ptk_id }}" data-indikator="{{ $item->indikator_id }}"
                                            title="Edit Aktivitas"
                                            style="border: 1px solid var(--border-color); background: transparent; padding: 6px; border-radius: var(--radius-sm, 6px); cursor: pointer; color: #3b82f6;">
                                            <i class="fas fa-pen-to-square"></i>
                                        </button>
                                    @endif
                                    @if ($canDelete)
                                        <form action="{{ route('dashboard.tendik.aktivitas.destroy', $item->id) }}"
                                            method="POST" data-confirm="delete" data-name="{{ $item->judul_aktivitas }}"
                                            style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon" title="Hapus Aktivitas"
                                                style="border: 1px solid var(--border-color); background: transparent; padding: 6px; border-radius: var(--radius-sm, 6px); cursor: pointer; color: #ef4444;">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if (!$canUpdate && !$canDelete)
                                        <span style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);"><i
                                                class="fas fa-lock"></i></span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isKepalaTas ? 6 : 5 }}"
                                style="text-align: center; padding: 40px 16px; color: var(--text-muted);">
                                <i class="fas fa-clipboard-list"
                                    style="font-size: var(--text-4xl, 2.25rem); opacity: 0.3; margin-bottom: 12px; display: block;"></i>
                                <div style="font-weight: 600; font-size: var(--text-md, 1rem);">Belum ada catatan aktivitas harian pada periode ini.</div>
                                <div style="font-size: var(--text-sm, 0.8125rem); margin-top: 4px;">Klik tombol <strong>"Catat Aktivitas"</strong> di atas untuk menambahkan log pekerjaan.</div>
                                @if ($canCreate)
                                    <div style="margin-top: 14px;">
                                        <button type="button" class="btn btn-primary btn-sm btn-open-modal-empty"
                                            style="background: #10b981; border: none; padding: 8px 18px; font-size: var(--text-base, 0.875rem); border-radius: var(--radius-md, 8px); font-weight: 600;">
                                            <i class="fas fa-plus me-1"></i> Catat Aktivitas Harian
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 3. Footer Paginasi Terpadu Datatable Baku SAE -->
        @if ($aktivitasList->hasPages())
            <div class="datatable-footer">
                @include('partials.datatable-pagination', ['paginator' => $aktivitasList])
            </div>
        @endif
    </div>

    <!-- Modal Form Tambah / Edit Aktivitas (Standar Baku Responsive SAE) -->
    <div id="modalAktivitas" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; z-index: 99999 !important; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 12px; box-sizing: border-box; overflow-y: auto;">
        <div class="card modal-card-responsive"
            style="max-width: 620px; width: 96%; max-height: 88vh; display: flex; flex-direction: column; margin: auto; border-radius: var(--radius-xl, 16px); padding: 18px 20px; box-shadow: 0 16px 40px rgba(0,0,0,0.5); border: 1px solid var(--border-color); background: var(--bg-card); box-sizing: border-box; overflow: hidden;">
            <div class="pm-modal-head"
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; flex-shrink: 0;">
                <h3 id="modalAktivitasTitle"
                    style="font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-pen-to-square text-primary"></i> Catat Aktivitas Harian
                </h3>
                <button type="button" id="btnCloseModalAktivitas"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-xl, 1.25rem); padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formAktivitas" action="{{ route('dashboard.tendik.aktivitas.store') }}" method="POST"
                enctype="multipart/form-data"
                style="display: flex; flex-direction: column; flex: 1; min-height: 0; overflow: hidden;">
                @csrf
                <div id="methodOverride"></div>

                <div class="modal-body-scroll"
                    style="overflow-y: auto; flex: 1; min-height: 0; padding-right: 4px; -webkit-overflow-scrolling: touch;">
                    <!-- Rekomendasi / Preset Tupoksi Bidang (Quick Manual Fill) -->
                    <div
                        style="margin-bottom: 12px; padding: 10px 12px; border-radius: var(--radius-md, 8px); background: rgba(59, 130, 246, 0.08); border: 1px dashed rgba(59, 130, 246, 0.3);">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <label
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--primary); display: flex; align-items: center; gap: 6px; margin: 0;">
                                <i class="fas fa-wand-magic-sparkles"></i> Rekomendasi Tupoksi Cepat (Otomatis Mengisi
                                Form)
                            </label>
                            <button type="button" id="btnTambahTupoksiManual" class="btn btn-outline"
                                onclick="openSubModalTupoksi()"
                                style="padding: 2px 8px; font-size: var(--text-xs, 0.75rem); height: 24px; border-radius: var(--radius-sm, 6px); border-color: rgba(99,102,241,0.4); color: var(--primary); display: inline-flex; align-items: center; gap: 4px;"
                                title="Tambah template tupoksi baru secara manual">
                                <i class="fas fa-plus-circle"></i> + Tambah Tupoksi Manual
                            </button>
                        </div>
                        <select id="selectTupoksiPreset" class="form-control"
                            style="font-size: var(--text-sm, 0.8125rem); height: 36px; border-radius: var(--radius-md, 8px); background: var(--bg-hover); color: var(--text-color);">
                            <option value="">-- Pilih dari Rekomendasi Tupoksi Bidang (Opsional) --</option>
                        </select>
                        <span style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 3px; display: block;">
                            Pilih template tugas untuk mengisi judul, uraian &amp; output secara otomatis, atau tambahkan
                            tupoksi manual.
                        </span>
                    </div>

                    @if ($isKepalaTas && count($pegawaiList) > 0)
                        <!-- Pilihan Pegawai (Khusus Kepala TAS / Admin) -->
                        <div class="pm-field" style="margin-bottom: 12px;">
                            <label
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: block;">
                                <i class="fas fa-user-gear text-primary me-1"></i> Catat Untuk Pegawai (Khusus Kepala TAS /
                                Admin)
                            </label>
                            <select name="pegawai_ptk_id" id="inputPegawaiPtk" class="form-control"
                                style="font-size: var(--text-sm, 0.8125rem); height: 38px; border-radius: var(--radius-md, 8px);">
                                <option value="">Saya Sendiri ({{ $userName }})</option>
                                @foreach ($pegawaiList as $p)
                                    <option value="{{ $p->ptk_id }}">{{ $p->nama }}
                                        {{ $p->nip ? '(' . $p->nip . ')' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="form-grid-2" style="margin-bottom: 10px;">
                        <div>
                            <label
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: block;">
                                Tanggal <span style="color: #ef4444;">*</span>
                            </label>
                            <input type="date" name="tanggal" id="inputTanggal" value="{{ date('Y-m-d') }}" required
                                class="form-control" style="font-size: var(--text-sm, 0.8125rem); height: 38px; border-radius: var(--radius-md, 8px);">
                        </div>
                        <div>
                            <label
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: block;">
                                Bidang Tugas <span style="color: #ef4444;">*</span>
                            </label>
                            <select name="bidang" id="inputBidang" required class="form-control"
                                style="font-size: var(--text-sm, 0.8125rem); height: 38px; border-radius: var(--radius-md, 8px);">
                                @foreach ($bidangOptions as $bKey => $bLabel)
                                    <option value="{{ $bKey }}" {{ $activeBidang === $bKey ? 'selected' : '' }}>
                                        {{ $bLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Pilihan Target & Indikator Kinerja (Khusus Bidang Ini) -->
                    <div class="pm-field" style="margin-bottom: 10px;">
                        <label
                            style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: block;">
                            <i class="fas fa-bullseye text-primary me-1"></i> Sasaran &amp; Indikator Kinerja Bidang
                        </label>
                        <select name="indikator_id" id="inputIndikatorId" class="form-control"
                            style="font-size: var(--text-sm, 0.8125rem); height: 38px; border-radius: var(--radius-md, 8px);">
                            <option value="">-- Hubungkan ke Sasaran Kinerja
                                {{ $bidangOptions[$currentBidang] ?? ucfirst($currentBidang) }} (Opsional) --</option>
                            @foreach ($indikatorKinerjaList ?? [] as $ind)
                                <option value="{{ $ind->id }}" data-bidang="{{ $ind->bidang }}"
                                    data-sasaran="{{ $ind->sasaran }}" data-target="{{ $ind->target_label }}">
                                    {{ $ind->sasaran }} &bull; {{ $ind->indikator_kinerja }} (Target:
                                    {{ $ind->target_label }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-grid-2" style="margin-bottom: 10px;">
                        <div>
                            <label
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: block;">
                                Jam Mulai <span style="color: #ef4444;">*</span>
                            </label>
                            <input type="time" name="jam_mulai" id="inputJamMulai" value="{{ date('H:i') }}"
                                required class="form-control"
                                style="font-size: var(--text-sm, 0.8125rem); height: 38px; border-radius: var(--radius-md, 8px);">
                        </div>
                        <div>
                            <label
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: block;">
                                Jam Selesai
                            </label>
                            <input type="time" name="jam_selesai" id="inputJamSelesai" class="form-control"
                                style="font-size: var(--text-sm, 0.8125rem); height: 38px; border-radius: var(--radius-md, 8px);">
                        </div>
                    </div>

                    <div class="pm-field" style="margin-bottom: 10px;">
                        <label
                            style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: block;">
                            Judul Pekerjaan / Agenda <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="text" name="judul_aktivitas" id="inputJudul"
                            placeholder="Contoh: Pengarsipan Berkas Ijazah, Maintenance Jaringan Lab..." required
                            class="form-control" style="font-size: var(--text-sm, 0.8125rem); height: 38px; border-radius: var(--radius-md, 8px);">
                    </div>

                    <div class="pm-field" style="margin-bottom: 10px;">
                        <label
                            style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: block;">
                            Uraian Pekerjaan / Kegiatan <span style="color: #ef4444;">*</span>
                        </label>
                        <textarea name="uraian_pekerjaan" id="inputUraian" rows="3"
                            placeholder="Jelaskan secara ringkas aktivitas dan langkah pekerjaan yang dilaksanakan..." required
                            class="form-control" style="font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); resize: vertical;"></textarea>
                    </div>

                    <div class="form-grid-2" style="margin-bottom: 10px;">
                        <div>
                            <label
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: block;">
                                Hasil / Output Keluaran
                            </label>
                            <input type="text" name="output_hasil" id="inputOutput"
                                placeholder="Contoh: 35 Berkas selesai, Server up..." class="form-control"
                                style="font-size: var(--text-sm, 0.8125rem); height: 38px; border-radius: var(--radius-md, 8px);">
                        </div>
                        <div>
                            <label
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: block;">
                                Status Pelaksanaan <span style="color: #ef4444;">*</span>
                            </label>
                            <select name="status" id="inputStatus" required class="form-control"
                                style="font-size: var(--text-sm, 0.8125rem); height: 38px; border-radius: var(--radius-md, 8px);">
                                <option value="selesai">Selesai</option>
                                <option value="proses">Sedang Proses</option>
                                <option value="tertunda">Tertunda / Ada Kendala</option>
                            </select>
                        </div>
                    </div>

                    <div class="pm-field" style="margin-bottom: 14px;">
                        <label
                            style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: block;">
                            Lampiran Bukti (Opsional - PDF/JPG/PNG/DOC)
                        </label>
                        <input type="file" name="lampiran" id="inputLampiran" class="form-control"
                            style="font-size: var(--text-sm, 0.8125rem); height: 38px; border-radius: var(--radius-md, 8px);">
                    </div>
                </div>

                <div class="pm-actions"
                    style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 12px; margin-top: 8px; flex-shrink: 0;">
                    <button type="button" id="btnCancelModalAktivitas" class="btn btn-outline"
                        style="padding: 7px 16px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px);">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary"
                        style="padding: 7px 20px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); font-weight: 600; background: linear-gradient(135deg, #10b981, #059669); border: none;">
                        <i class="fas fa-save me-1"></i> Simpan Aktivitas
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Sub-Modal Tambah Tupoksi Manual (Standalone & Bulletproof) -->
    <div id="modalSubTupoksiManual" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; z-index: 100005 !important; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 12px; box-sizing: border-box;">
        <div class="card modal-card-responsive"
            style="max-width: 480px; width: 94%; border-radius: var(--radius-xl, 16px); padding: 18px 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.6); border: 1px solid var(--border-color); background: var(--bg-card); box-sizing: border-box;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <h4
                    style="font-size: var(--text-md, 1rem); font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-plus-circle text-primary"></i> Tambah Tupoksi Manual
                </h4>
                <button type="button" onclick="closeSubModalTupoksi()"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-xl, 1.25rem); padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="margin-bottom: 12px;">
                <label
                    style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; display: block; margin-bottom: 4px; color: var(--text-color);">
                    Judul Tupoksi / Agenda <span style="color: #ef4444;">*</span>
                </label>
                <input type="text" id="manualTupoksiJudul" class="form-control"
                    placeholder="Contoh: Pemeliharaan Server &amp; Jaringan"
                    style="width: 100%; font-size: var(--text-base, 0.875rem); height: 38px; border-radius: var(--radius-md, 8px); background: var(--bg-hover); color: var(--text-color); border: 1px solid var(--border-color);">
            </div>
            <div style="margin-bottom: 12px;">
                <label
                    style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; display: block; margin-bottom: 4px; color: var(--text-color);">
                    Uraian Pekerjaan / Tugas <span style="color: #ef4444;">*</span>
                </label>
                <textarea id="manualTupoksiUraian" class="form-control" rows="3"
                    placeholder="Langkah atau deskripsi rincian tugas yang dilaksanakan..."
                    style="width: 100%; font-size: var(--text-base, 0.875rem); border-radius: var(--radius-md, 8px); background: var(--bg-hover); color: var(--text-color); border: 1px solid var(--border-color);"></textarea>
            </div>
            <div style="margin-bottom: 16px;">
                <label
                    style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; display: block; margin-bottom: 4px; color: var(--text-color);">
                    Hasil / Output Keluaran
                </label>
                <input type="text" id="manualTupoksiOutput" class="form-control"
                    placeholder="Contoh: Log pemeliharaan terarsip &amp; konektivitas stabil"
                    style="width: 100%; font-size: var(--text-base, 0.875rem); height: 38px; border-radius: var(--radius-md, 8px); background: var(--bg-hover); color: var(--text-color); border: 1px solid var(--border-color);">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-outline" onclick="closeSubModalTupoksi()"
                    style="padding: 6px 14px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px);">Batal</button>
                <button type="button" class="btn btn-primary" id="btnSimpanTupoksiManual"
                    onclick="simpanTupoksiManual()"
                    style="padding: 6px 16px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); background: #10b981; border: none; font-weight: 600;">
                    <i class="fas fa-check me-1"></i> Simpan &amp; Terapkan
                </button>
            </div>
        </div>
    </div>

    <!-- Data Template Tupoksi untuk JS -->
    <script type="application/json" id="tupoksiTemplatesData">
        {!! json_encode($tupoksiTemplates, JSON_UNESCAPED_UNICODE) !!}
    </script>
@endsection

@push('scripts')
    <script
        src="{{ asset('js/tendik-aktivitas.js') }}?v={{ file_exists(public_path('js/tendik-aktivitas.js')) ? filemtime(public_path('js/tendik-aktivitas.js')) : time() }}">
    </script>
@endpush
