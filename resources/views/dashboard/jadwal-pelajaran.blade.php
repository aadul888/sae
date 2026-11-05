@extends('layouts.dashboard')

@section('title', ($isViewingGuru ? 'Jadwal Mengajar' : 'Jadwal Pelajaran') . ' — SAE')
@section('dash_title', $isViewingGuru ? 'Jadwal Mengajar' : 'Jadwal Pelajaran')

@section('content')
    <!-- 1. Header Banner -->
    <div class="dash-banner" style="margin-bottom: 20px;">
        <div>
            <h2 style="font-size: var(--text-2xl, 1.5rem); font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-calendar-days text-primary me-2"></i>
                {{ $isViewingGuru ? 'Jadwal Mengajar Guru' : ($isViewingSiswa ? 'Jadwal Pelajaran Kelas' : 'Jadwal Pelajaran & Mengajar') }}
            </h2>
            <p style="color: var(--text-muted); font-size: var(--text-base, 0.875rem); margin: 0;">
                @if ($isViewingSiswa && $studentRombel)
                    Kelas: <strong style="color: var(--text-color);">{{ $studentRombel->nama }}</strong>
                    @if (!empty($studentRombel->ptk_id_str))
                        &bull; Wali Kelas: <strong style="color: var(--primary);">{{ $studentRombel->ptk_id_str }}</strong>
                    @endif
                @elseif ($isViewingGuru)
                    Jadwal tatap muka kegiatan belajar mengajar aktif untuk Anda.
                @else
                    Direktori jadwal pelajaran semester aktif sekolah.
                @endif
            </p>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            @if ($canManageJadwal && !$isViewingSiswa)
                <button type="button" id="btnTambahJadwalManual" class="btn btn-primary"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                    title="Input Jadwal Manual" aria-label="Input Jadwal Manual">
                    <i class="fas fa-plus"></i>
                </button>
            @endif
            @if ($isGuru || $isViewingGuru)
                <a href="{{ route('dashboard.presensi-mengajar.index') }}" class="btn btn-outline"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem); text-decoration: none;"
                    title="Presensi Mengajar Guru">
                    <i class="fas fa-calendar-check text-primary"></i>
                </a>
            @endif
        </div>
    </div>

    <!-- 2. Alert Status Jadwal Masih Draft (Jika Belum Diberlakukan) -->
    @if (!$isJadwalDiberlakukan)
        <div class="card"
            style="padding: 14px 18px; margin-bottom: 20px; background: rgba(245, 158, 11, 0.08); border: 1px dashed rgba(245, 158, 11, 0.35); border-radius: var(--radius-lg, 12px); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div
                    style="width: 40px; height: 40px; border-radius: var(--radius-lg, 12px); background: rgba(245, 158, 11, 0.18); color: var(--warning, #f59e0b); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <div style="font-weight: 700; color: var(--warning, #f59e0b); font-size: var(--text-base, 0.875rem);">Jadwal KBM Masih Berstatus Draft
                    </div>
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                        Jadwal pelajaran semester ini masih dalam tahap penyusunan / finalisasi kurikulum dan belum resmi
                        diberlakukan.
                    </div>
                </div>
            </div>
            <span class="badge badge-warning">DRAFT</span>
        </div>
    @endif

    <!-- 3. Stat Grid Baku SAE (4 Kartu Ringkasan) -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.12); color: var(--success, #10b981);">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: var(--success, #10b981);">{{ $stats['total_sesi_hari_ini'] }} <span
                        style="font-size: var(--text-xs, 0.75rem); font-weight: 500;">Sesi</span></div>
                <div class="dash-stat-label">Jadwal Hari Ini ({{ $hariIni }})</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.12); color: var(--primary);">
                <i class="fas fa-calendar-days"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['total_sesi_mingguan'] }} <span
                        style="font-size: var(--text-xs, 0.75rem); font-weight: 500;">Sesi</span></div>
                <div class="dash-stat-label">Total Sesi Mingguan</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(168, 85, 247, 0.12); color: #a855f7;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['total_jp_mingguan'] }} <span
                        style="font-size: var(--text-xs, 0.75rem); font-weight: 500;">JP</span></div>
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
                    style="padding: 8px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; border-radius: var(--radius-md, 8px);">
                    <i class="fas {{ $hariIni === $h ? 'fa-calendar-check text-success' : 'fa-calendar' }} me-1"></i>
                    {{ $h }}
                    @if ($hariIni === $h)
                        <span class="badge badge-success"
                            style="font-size: var(--text-2xs, 0.6875rem); padding: 2px 5px; margin-left: 4px;">Hari Ini</span>
                    @endif
                </a>
            @endforeach
            <a href="{{ request()->fullUrlWithQuery(['hari' => 'semua']) }}"
                class="btn {{ $selectedHari === 'semua' ? 'btn-primary' : 'btn-outline' }}"
                style="padding: 8px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; border-radius: var(--radius-md, 8px);">
                <i class="fas fa-list-ul me-1"></i> Semua Hari
            </a>
        </div>
    </div>

    <!-- Unified Datatable Jadwal Pelajaran (Toolbar + Table + Pagination dalam 1 Card Terpadu) -->
    <div class="card datatable-card-wrapper" id="tableDataContainer">
        <!-- 1. Toolbar Header Datatable Baku SAE (Entries, Filters & Live Search) -->
        <div class="datatable-toolbar">
            <form action="{{ route('dashboard.jadwal-pelajaran.index') }}" method="GET" style="margin: 0;">
                <input type="hidden" name="hari" value="{{ $selectedHari }}">
                @if (request('view'))
                    <input type="hidden" name="view" value="{{ request('view') }}">
                @endif

                <div
                    style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                        <!-- Tampilkan Entri Selector -->
                        <div class="toolbar-entries">
                            <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                            <select id="perPageSelect" name="per_page" class="per-page-select" style="height: 38px;">
                                @foreach ([10, 15, 25, 50, 'all'] as $n)
                                    <option value="{{ $n }}"
                                        {{ request('per_page', 15) == $n ? 'selected' : '' }}>
                                        {{ $n === 'all' ? 'Semua' : $n }}
                                    </option>
                                @endforeach
                            </select>
                            <span>entri</span>
                        </div>

                        <!-- Filter Rombel -->
                        @if ($rombelOptions->isNotEmpty())
                            <select name="rombel_id" id="filterRombel" class="toolbar-filter-select"
                                style="min-width: 150px; height: 38px;">
                                <option value="">Semua Kelas</option>
                                @foreach ($rombelOptions as $r)
                                    <option value="{{ $r->rombongan_belajar_id }}"
                                        {{ request('rombel_id') === $r->rombongan_belajar_id ? 'selected' : '' }}>
                                        Kelas {{ $r->nama }}
                                    </option>
                                @endforeach
                            </select>
                        @endif

                        @if (request('q') || request('rombel_id') || ($selectedHari !== 'semua' && $selectedHari !== $hariIni))
                            <a href="{{ route('dashboard.jadwal-pelajaran.index', array_filter(['view' => request('view')])) }}"
                                class="btn btn-outline btn-reset-filter"
                                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                title="Reset filter" data-live-reset>
                                <i class="fas fa-undo"></i>
                            </a>
                        @endif

                        <!-- Mode Switcher (Tabel Datatable / Kartu Grid) -->
                        <div
                            style="display: inline-flex; border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); overflow: hidden; height: 38px;">
                            <button type="button" id="btnJadwalViewTable" class="btn btn-primary"
                                style="height: 100%; border: none; border-radius: 0; padding: 0 12px; font-size: var(--text-sm, 0.8125rem); display: inline-flex; align-items: center; gap: 6px;"
                                title="Tampilan Tabel Datatable">
                                <i class="fas fa-table-list"></i> <span class="d-none-mobile">Tabel</span>
                            </button>
                            <button type="button" id="btnJadwalViewGrid" class="btn btn-outline"
                                style="height: 100%; border: none; border-radius: 0; padding: 0 12px; font-size: var(--text-sm, 0.8125rem); display: inline-flex; align-items: center; gap: 6px;"
                                title="Tampilan Kartu Grid">
                                <i class="fas fa-grip"></i> <span class="d-none-mobile">Kartu</span>
                            </button>
                        </div>
                    </div>

                    <!-- Live Search Box Baku SAE -->
                    <div class="live-search-wrap" style="height: 38px; min-width: 220px;">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="q" id="searchJadwalInput"
                            placeholder="Cari mapel, kelas, ruangan..." value="{{ request('q') }}" autocomplete="off">
                        <button type="button" class="clear-search {{ request('q') ? 'visible' : '' }}"
                            id="clearSearchJadwalBtn" title="Hapus pencarian">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- 2. Datatable Jadwal Pelajaran & Mengajar Baku SAE -->
        <div class="table-responsive-stack" id="tableDataContainerJadwal" style="overflow-x: auto; width: 100%;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <th
                            style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 55px; text-align: center;">
                            No</th>
                        <th
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px;">
                            <i class="fas fa-calendar-day me-1 text-primary"></i> Hari &amp; Jam
                        </th>
                        <th
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 130px;">
                            <i class="fas fa-chalkboard me-1 text-primary"></i> Kelas
                        </th>
                        <th
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            <i class="fas fa-book me-1 text-primary"></i> Mata Pelajaran
                        </th>
                        <th
                            style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 130px;">
                            <i class="fas fa-location-dot me-1 text-danger"></i> Ruangan
                        </th>
                        <th
                            style="padding: 12px 12px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 80px;">
                            <i class="fas fa-bolt me-1 text-primary"></i> JP
                        </th>
                        <th
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 130px;">
                            <i class="fas fa-signal me-1 text-primary"></i> Status
                        </th>
                        @if ($isGuru || $canManageJadwal)
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 100px;">
                                <i class="fas fa-sliders me-1 text-primary"></i> Aksi
                            </th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($schedules as $index => $item)
                        <tr class="jadwal-row-item"
                            style="border-bottom: 1px solid var(--border-color); transition: background 0.15s ease;">
                            <td data-label="No"
                                style="padding: 12px 14px; text-align: center; color: var(--text-muted); font-size: var(--text-sm, 0.8125rem);">
                                <span class="badge"
                                    style="background: rgba(99,102,241,0.08); color: var(--primary); font-weight: 700; font-size: var(--text-xs, 0.75rem);">
                                    #{{ $index + 1 }}
                                </span>
                            </td>
                            <td data-label="Hari &amp; Jam" style="padding: 12px 16px;">
                                <div class="cell-col-right">
                                    <div
                                        style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem); display: inline-flex; align-items: center; gap: 6px;">
                                        <span>{{ $item->hari }}</span>
                                        <span class="badge"
                                            style="background: var(--bg-hover); color: var(--text-color); font-weight: 700; font-size: var(--text-xs, 0.75rem); border: 1px solid var(--border-color); padding: 2px 6px;">
                                            Jam
                                            {{ $item->jam_ke_mulai }}{{ $item->jam_ke_mulai != $item->jam_ke_selesai ? '-' . $item->jam_ke_selesai : '' }}
                                        </span>
                                    </div>
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                        <i
                                            class="far fa-clock me-1 text-primary"></i>{{ $item->jam_waktu_range ?: (!empty($item->jam_mulai) ? substr($item->jam_mulai, 0, 5) . ' - ' . substr($item->jam_selesai, 0, 5) : '-') }}
                                    </div>
                                </div>
                            </td>
                            <td data-label="Kelas" style="padding: 12px 16px;">
                                <div class="cell-col-right">
                                    <div
                                        style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem); display: inline-flex; align-items: center; gap: 6px;">
                                        <i class="fas fa-chalkboard text-primary" style="font-size: var(--text-sm, 0.8125rem);"></i>
                                        <span>{{ $item->nama_rombel }}</span>
                                    </div>
                                    @if (!$isGuru && !empty($item->nama_guru))
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                            <i class="fas fa-chalkboard-user me-1"></i>{{ $item->nama_guru }}
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td data-label="Mapel" style="padding: 12px 16px;">
                                <div class="cell-col-right">
                                    <div
                                        style="font-weight: 600; color: var(--text-color); font-size: var(--text-base, 0.875rem); line-height: 1.35;">
                                        {{ $item->nama_mata_pelajaran }}
                                    </div>
                                </div>
                            </td>
                            <td data-label="Ruangan" style="padding: 12px 14px;">
                                <div class="cell-col-right">
                                    @if ($item->ruangan)
                                        <span
                                            style="font-size: var(--text-sm, 0.8125rem); color: var(--text-color); display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fas fa-location-dot text-danger" style="font-size: var(--text-xs, 0.75rem);"></i>
                                            <span>{{ $item->ruangan }}</span>
                                        </span>
                                    @else
                                        <span style="color: var(--text-muted); font-size: var(--text-sm, 0.8125rem);">-</span>
                                    @endif
                                </div>
                            </td>
                            <td data-label="Beban" style="padding: 12px 12px; text-align: center;">
                                <div class="cell-col-right" style="align-items: center;">
                                    <span class="badge"
                                        style="background: rgba(168,85,247,0.1); color: #a855f7; font-weight: 700; font-size: var(--text-sm, 0.8125rem); padding: 4px 8px;">
                                        <i
                                            class="fas fa-bolt me-1"></i>{{ $item->durasi_jp ?: max(1, $item->jam_ke_selesai - $item->jam_ke_mulai + 1) }}
                                        JP
                                    </span>
                                </div>
                            </td>
                            <td data-label="Status" style="padding: 12px 16px; text-align: center;">
                                <div class="cell-col-right" style="align-items: center;">
                                    @if ($item->status_kbm === 'Berlangsung')
                                        <span class="badge badge-success" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;">
                                            <i class="fas fa-spinner fa-spin me-1"></i> Berlangsung
                                        </span>
                                    @elseif ($item->status_kbm === 'Selesai')
                                        <span class="badge badge-outline"
                                            style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px; color: var(--text-muted);">
                                            <i class="fas fa-check me-1"></i> Selesai
                                        </span>
                                    @elseif ($item->status_kbm === 'Mendatang')
                                        <span class="badge badge-primary" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;">
                                            <i class="far fa-clock me-1"></i> Mendatang
                                        </span>
                                    @else
                                        <span class="badge badge-outline" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;">
                                            {{ $item->hari }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            @if ($isGuru || $canManageJadwal)
                                <td data-label="Aksi" style="padding: 12px 18px; text-align: center;">
                                    <div class="cell-col-right" style="align-items: center; display: inline-flex; gap: 6px; justify-content: center;">
                                        @if (!empty($item->can_manage))
                                            <button type="button" class="btn-icon btn-jadwal-edit"
                                                title="Edit Slot Jadwal"
                                                data-id="{{ $item->id }}"
                                                data-pembelajaran-id="{{ $item->pembelajaran_id }}"
                                                data-rombel-id="{{ $item->rombongan_belajar_id }}"
                                                data-rombel-name="{{ $item->nama_rombel }}"
                                                data-hari="{{ $item->hari }}"
                                                data-jam-ke-mulai="{{ $item->jam_ke_mulai }}"
                                                data-jam-ke-selesai="{{ $item->jam_ke_selesai }}"
                                                data-ruangan="{{ $item->ruangan }}"
                                                data-keterangan="{{ $item->keterangan }}"
                                                data-nama-mapel="{{ $item->nama_mata_pelajaran }}"
                                                style="width: 32px; height: 32px; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--primary); display: inline-flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <button type="button" class="btn-icon btn-jadwal-hapus"
                                                title="Hapus Slot Jadwal"
                                                data-id="{{ $item->id }}"
                                                data-nama-mapel="{{ $item->nama_mata_pelajaran }}"
                                                data-hari="{{ $item->hari }}"
                                                data-jam-ke="{{ $item->jam_ke_mulai }}"
                                                style="width: 32px; height: 32px; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--danger, #ef4444); display: inline-flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                        @if ($isGuru)
                                            <a href="{{ route('dashboard.presensi-mengajar.index', ['rombongan_belajar_id' => $item->rombongan_belajar_id]) }}"
                                                class="btn-icon" title="Presensi Mengajar di Kelas Ini"
                                                style="width: 32px; height: 32px; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--primary); display: inline-flex; align-items: center; justify-content: center; text-decoration: none;">
                                                <i class="fas fa-calendar-check"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr class="empty-row">
                            <td colspan="{{ ($isGuru || $canManageJadwal) ? 8 : 7 }}"
                                style="padding: 42px 16px; text-align: center; color: var(--text-muted);">
                                <div
                                    style="width: 52px; height: 52px; border-radius: 50%; background: rgba(99,102,241,0.08); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); margin: 0 auto 10px auto;">
                                    <i class="fas fa-calendar-xmark"></i>
                                </div>
                                <div
                                    style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem); margin-bottom: 3px;">
                                    Tidak Ada Jadwal Pelajaran</div>
                                <div style="font-size: var(--text-sm, 0.8125rem); max-width: 380px; margin: 0 auto;">Tidak ada jadwal KBM
                                    aktif
                                    untuk
                                    filter yang dipilih.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 3. Footer Paginasi Terpadu Datatable -->
        @if ($schedules->hasPages())
            <div class="datatable-footer">
                @include('partials.datatable-pagination', ['paginator' => $schedules])
            </div>
        @endif
    </div>

    <!-- 7. Schedule Card Grid (Alternatif Tampilan Kartu Grid) -->
    <div id="gridDataContainerJadwal" style="display: none;">
        @if ($schedules->isNotEmpty())
            <div class="jadwal-card-grid">
                @foreach ($schedules as $item)
                    @php
                        $isBerlangsung = $item->status_kbm === 'Berlangsung';
                    @endphp
                    <div class="jadwal-item-card {{ $isBerlangsung ? 'is-berlangsung' : '' }}">
                        <div>
                            <!-- Header Baris Atas: Jam Ke & Status Live KBM -->
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; gap: 8px;">
                                <span class="badge"
                                    style="background: rgba(99, 102, 241, 0.12); color: var(--primary); font-size: var(--text-sm, 0.8125rem); font-weight: 700; padding: 3px 8px; border-radius: var(--radius-sm, 6px);">
                                    Jam
                                    {{ $item->jam_ke_mulai }}{{ $item->jam_ke_mulai != $item->jam_ke_selesai ? '-' . $item->jam_ke_selesai : '' }}
                                    ({{ $item->durasi_jp ?: max(1, $item->jam_ke_selesai - $item->jam_ke_mulai + 1) }} JP)
                                </span>

                                @if ($item->status_kbm === 'Berlangsung')
                                    <span class="badge badge-success" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;">
                                        <i class="fas fa-spinner fa-spin me-1"></i> Berlangsung
                                    </span>
                                @elseif ($item->status_kbm === 'Selesai')
                                    <span class="badge badge-outline"
                                        style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px; color: var(--text-muted);">
                                        <i class="fas fa-check me-1"></i> Selesai
                                    </span>
                                @elseif ($item->status_kbm === 'Mendatang')
                                    <span class="badge badge-primary" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;">
                                        <i class="far fa-clock me-1"></i> Mendatang
                                    </span>
                                @elseif ($selectedHari === 'semua')
                                    <span class="badge"
                                        style="background: var(--bg-hover); color: var(--text-color); font-size: var(--text-xs, 0.75rem); padding: 2px 7px;">
                                        {{ $item->hari }}
                                    </span>
                                @endif
                            </div>

                            <!-- Nama Mata Pelajaran -->
                            <div
                                style="font-weight: 700; font-size: var(--text-md, 1rem); color: var(--text-color); line-height: 1.35; margin-bottom: 6px;">
                                {{ $item->nama_mata_pelajaran }}
                            </div>

                            <!-- Kelas (jika guru) / Guru Pengampu (jika siswa) -->
                            <div
                                style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); display: flex; align-items: center; gap: 8px; margin-bottom: 12px; flex-wrap: wrap;">
                                @if ($isGuru)
                                    <span
                                        style="color: var(--primary); font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fas fa-chalkboard"></i> {{ $item->nama_rombel }}
                                    </span>
                                @else
                                    <span
                                        style="color: var(--primary); font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
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
                        <div
                            style="border-top: 1px solid var(--border-color); padding-top: 10px; display: flex; justify-content: space-between; align-items: center; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); gap: 6px; flex-wrap: wrap;">
                            <span style="display: inline-flex; align-items: center; gap: 5px;">
                                <i class="far fa-clock text-primary"></i>
                                <strong>{{ $item->jam_waktu_range ?: (!empty($item->jam_mulai) ? substr($item->jam_mulai, 0, 5) . ' - ' . substr($item->jam_selesai, 0, 5) : 'Jam Sesuai KBM') }}</strong>
                            </span>
                            @if ($item->ruangan)
                                <span style="display: inline-flex; align-items: center; gap: 5px;"
                                    title="Ruang / Laboratorium">
                                    <i class="fas fa-location-dot text-danger"></i>
                                    <span>{{ $item->ruangan }}</span>
                                </span>
                            @endif
                        </div>

                        @if (!empty($item->can_manage) || $isGuru)
                            <div style="border-top: 1px solid var(--border-color); padding-top: 10px; margin-top: 10px; display: flex; justify-content: flex-end; align-items: center; gap: 6px;">
                                @if (!empty($item->can_manage))
                                    <button type="button" class="btn-icon btn-jadwal-edit" title="Edit Slot Jadwal"
                                        data-id="{{ $item->id }}"
                                        data-pembelajaran-id="{{ $item->pembelajaran_id }}"
                                        data-rombel-id="{{ $item->rombongan_belajar_id }}"
                                        data-rombel-name="{{ $item->nama_rombel }}"
                                        data-hari="{{ $item->hari }}"
                                        data-jam-ke-mulai="{{ $item->jam_ke_mulai }}"
                                        data-jam-ke-selesai="{{ $item->jam_ke_selesai }}"
                                        data-ruangan="{{ $item->ruangan }}"
                                        data-keterangan="{{ $item->keterangan }}"
                                        data-nama-mapel="{{ $item->nama_mata_pelajaran }}"
                                        style="width: 32px; height: 32px; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--primary); display: inline-flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button type="button" class="btn-icon btn-jadwal-hapus" title="Hapus Slot Jadwal"
                                        data-id="{{ $item->id }}"
                                        data-nama-mapel="{{ $item->nama_mata_pelajaran }}"
                                        data-hari="{{ $item->hari }}"
                                        data-jam-ke="{{ $item->jam_ke_mulai }}"
                                        style="width: 32px; height: 32px; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--danger, #ef4444); display: inline-flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @endif
                                @if ($isGuru)
                                    <a href="{{ route('dashboard.presensi-mengajar.index', ['rombongan_belajar_id' => $item->rombongan_belajar_id]) }}"
                                        class="btn-icon" title="Presensi Mengajar di Kelas Ini"
                                        style="width: 32px; height: 32px; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--primary); display: inline-flex; align-items: center; justify-content: center; text-decoration: none;">
                                        <i class="fas fa-calendar-check"></i>
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty State -->
            <div class="card" style="padding: 48px 20px; text-align: center; border-radius: var(--radius-xl, 16px); margin-bottom: 24px;">
                <div
                    style="width: 60px; height: 60px; border-radius: 50%; background: rgba(99, 102, 241, 0.08); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: var(--text-3xl, 1.875rem); margin: 0 auto 14px auto;">
                    <i class="fas fa-calendar-xmark"></i>
                </div>
                <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px;">
                    Tidak Ada Jadwal Pelajaran
                </h3>
                <p style="color: var(--text-muted); font-size: var(--text-base, 0.875rem); max-width: 420px; margin: 0 auto;">
                    @if ($selectedHari !== 'semua')
                        Tidak ada jadwal KBM tatap muka untuk hari <strong>{{ $selectedHari }}</strong>. Silakan pilih
                        hari
                        lain pada tab di atas.
                    @else
                        Belum ada rekaman jadwal tatap muka KBM yang terdaftar di sistem.
                    @endif
                </p>
            </div>
        @endif
    </div>

    @if ($canManageJadwal && !$isViewingSiswa)
        <!-- Modal Input / Edit Slot Jadwal Manual -->
        <div id="modalJadwalManual" class="sae-modal"
            data-route-store="{{ route('dashboard.jadwal-kbm.manual.store-slot') }}"
            data-route-update="{{ url('/dashboard/master-data/jadwal-kbm/manual/update-slot') }}"
            data-route-delete="{{ url('/dashboard/master-data/jadwal-kbm/manual/delete-slot') }}"
            data-route-pembelajaran="{{ route('dashboard.jadwal-kbm.manual.pembelajaran') }}"
            style="display: none; position: fixed; inset: 0; z-index: 99999 !important; background: rgba(0,0,0,0.6); align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card"
                style="width: 100%; max-width: 540px; max-height: 90vh; overflow-y: auto; margin: 16px; padding: 24px; border-radius: var(--radius-lg, 12px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.4);">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                    <h3 id="mjTitle"
                        style="margin: 0; font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color);">
                        <i class="fas fa-calendar-plus text-primary me-2"></i> Jadwalkan Pelajaran
                    </h3>
                    <button type="button" id="mjTutup"
                        style="background: transparent; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;"
                        aria-label="Tutup"><i class="fas fa-times"></i></button>
                </div>

                <form id="mjForm">
                    @csrf
                    <input type="hidden" id="mjId" value="">

                    <div class="form-group" style="margin-bottom: 16px;">
                        <label for="mjRombel"
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">Kelas
                            <span style="color: var(--danger, #ef4444);">*</span></label>
                        <select id="mjRombel" name="rombongan_belajar_id" required class="form-control"
                            style="width: 100%; padding: 9px 12px; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                            <option value="">-- Pilih Kelas --</option>
                            @foreach ($rombelOptions as $r)
                                <option value="{{ $r->rombongan_belajar_id }}">{{ $r->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 16px;">
                        <label for="mjPembelajaran"
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">Mata
                            Pelajaran &amp; Guru Pengampu <span style="color: var(--danger, #ef4444);">*</span></label>
                        <select id="mjPembelajaran" name="pembelajaran_id" required class="form-control"
                            style="width: 100%; padding: 9px 12px; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                            <option value="">-- Pilih Kelas Terlebih Dahulu --</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 16px;">
                        <label for="mjHari"
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">Hari
                            <span style="color: var(--danger, #ef4444);">*</span></label>
                        <select id="mjHari" name="hari" required class="form-control"
                            style="width: 100%; padding: 9px 12px; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                            @foreach ($hariList as $h)
                                <option value="{{ $h }}">{{ $h }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                        <div class="form-group">
                            <label for="mjJamKeMulai"
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">Jam
                                Ke-Mulai <span style="color: var(--danger, #ef4444);">*</span></label>
                            <select id="mjJamKeMulai" name="jam_ke_mulai" required class="form-control"
                                style="width: 100%; padding: 9px 12px; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                                @for ($i = 1; $i <= 16; $i++)
                                    <option value="{{ $i }}">Jam Ke-{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="mjJamKeSelesai"
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">Jam
                                Ke-Selesai <span style="color: var(--danger, #ef4444);">*</span></label>
                            <select id="mjJamKeSelesai" name="jam_ke_selesai" required class="form-control"
                                style="width: 100%; padding: 9px 12px; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                                @for ($i = 1; $i <= 16; $i++)
                                    <option value="{{ $i }}">Jam Ke-{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div id="mjAlert"
                        style="display: none; padding: 10px 14px; border-radius: var(--radius-md, 8px); margin-bottom: 16px; font-size: var(--text-sm, 0.8125rem);">
                    </div>

                    <div id="mjBoxOverwrite"
                        style="margin-bottom: 16px; padding: 10px 14px; background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.25); border-radius: var(--radius-md, 8px); display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" id="mjOverwrite" name="overwrite" value="1"
                            style="width: 17px; height: 17px; cursor: pointer; accent-color: var(--warning, #f59e0b);">
                        <label for="mjOverwrite"
                            style="margin: 0; font-size: var(--text-sm, 0.8125rem); color: var(--text-color); cursor: pointer; line-height: 1.4;">
                            <strong>Ganti / Timpa jadwal kelas ini</strong> jika jam yang dipilih telah terisi pelajaran
                            lain.
                        </label>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="mjKeterangan"
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">Keterangan
                            Tambahan (Opsional)</label>
                        <input type="text" id="mjKeterangan" name="keterangan"
                            placeholder="Contoh: Praktik Bengkel / Teori" class="form-control"
                            style="width: 100%; padding: 9px 12px; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                    </div>

                    <div
                        style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                        <button type="button" id="mjBatal" class="btn btn-outline" style="padding: 8px 16px;">Batal</button>
                        <button type="submit" id="mjSimpan" class="btn btn-primary" style="padding: 8px 20px;">
                            <i class="fas fa-save me-1"></i> Simpan Slot
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script
        src="{{ asset('js/jadwal-pelajaran.js') }}?v={{ file_exists(public_path('js/jadwal-pelajaran.js')) ? filemtime(public_path('js/jadwal-pelajaran.js')) : time() }}">
    </script>
@endpush
