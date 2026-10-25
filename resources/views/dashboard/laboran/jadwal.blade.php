@extends('layouts.dashboard')

@section('title', 'Jadwal Penggunaan Lab — Laboratorium SAE')
@section('dash_title', 'Jadwal Lab')

@section('content')
    <div class="dash-content-inner">
        {{-- 1. Header Banner & Action Button --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg, 12px); background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); flex-shrink: 0;">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                        Jadwal &amp; Penggunaan Lab
                    </h2>
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        Penjadwalan sesi praktikum, pemakaian ruang lab komputer/IPA/bengkel, dan log pasca-praktik.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                @if ($canCreate)
                    <button type="button" class="btn btn-primary" id="btnOpenModalJadwal"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                        title="Jadwalkan Praktik Baru">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>

        {{-- 2. Stat Grid (4 Kartu Ringkasan) --}}
        <div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $statTotal }}</div>
                    <div class="dash-stat-label">Total Sesi Terjadwal</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $statHariIni }}</div>
                    <div class="dash-stat-label">Praktik Hari Ini</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $statBerlangsung }}</div>
                    <div class="dash-stat-label">Sedang Berlangsung</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $statSelesai }}</div>
                    <div class="dash-stat-label">Praktik Selesai</div>
                </div>
            </div>
        </div>

        {{-- Unified Datatable Jadwal Pemakaian Lab (Toolbar + Table + Pagination dalam 1 Card Terpadu) --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <!-- 1. Toolbar Header (Entries, Filters & Live Search) -->
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.laboran.jadwal.index') }}" id="filterForm"
                    style="margin: 0;">
                    <div
                        style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                        <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                            <div class="toolbar-entries">
                                <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                                <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                                    @foreach ([10, 15, 25, 50, 100] as $n)
                                        <option value="{{ $n }}"
                                            {{ request('perPage', 15) == $n ? 'selected' : '' }}>
                                            {{ $n }}</option>
                                    @endforeach
                                </select>
                                <span>entri</span>
                            </div>

                            <select name="lab" id="filterLab" class="toolbar-filter-select" style="height: 38px;">
                                <option value="">Semua Ruang</option>
                                @if (isset($daftarRuangSarpras) && $daftarRuangSarpras->isNotEmpty())
                                    @foreach ($daftarRuangSarpras as $r)
                                        <option value="{{ $r->nama_ruang }}"
                                            {{ $labFilter === $r->nama_ruang ? 'selected' : '' }}>
                                            {{ $r->nama_ruang }} {{ $r->gedung ? "({$r->gedung})" : '' }}
                                        </option>
                                    @endforeach
                                @else
                                    @foreach ($daftarLab as $lab)
                                        <option value="{{ $lab }}" {{ $labFilter === $lab ? 'selected' : '' }}>
                                            {{ $lab }}</option>
                                    @endforeach
                                @endif
                            </select>

                            <select name="status" id="filterStatus" class="toolbar-filter-select" style="height: 38px;">
                                <option value="">Semua Status</option>
                                <option value="dijadwalkan" {{ $statusFilter === 'dijadwalkan' ? 'selected' : '' }}>
                                    Dijadwalkan
                                </option>
                                <option value="berlangsung" {{ $statusFilter === 'berlangsung' ? 'selected' : '' }}>
                                    Berlangsung
                                </option>
                                <option value="selesai" {{ $statusFilter === 'selesai' ? 'selected' : '' }}>Selesai
                                </option>
                                <option value="batal" {{ $statusFilter === 'batal' ? 'selected' : '' }}>Dibatalkan
                                </option>
                            </select>

                            <input type="date" name="tanggal" id="filterTanggal" value="{{ $tanggalFilter }}"
                                class="form-control"
                                style="height: 38px; font-size: var(--text-sm, 0.8125rem); padding: 0 10px; border-radius: var(--radius-md, 8px);"
                                title="Filter Tanggal">

                            @if ($search || $labFilter || $statusFilter || $tanggalFilter)
                                <a href="{{ route('dashboard.laboran.jadwal.index') }}"
                                    class="btn btn-outline btn-reset-filter"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                    title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="q" id="liveSearch" value="{{ $search }}"
                                placeholder="Cari mapel, topik praktik, guru, atau kelas..." autocomplete="off">
                            <button type="button" id="clearSearch" class="clear-search {{ $search ? 'visible' : '' }}"
                                title="Hapus pencarian">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- 2. Konten Datatable Responsive Stack -->
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255, 255, 255, 0.02);">
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                No</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Lab &amp; Tanggal</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Mapel &amp; Topik</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Guru &amp; Rombel</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                Waktu Sesi</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                Status</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $idx => $item)
                            <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                                <td data-label="No"
                                    style="padding: 14px 18px; font-size: var(--text-base, 0.875rem); color: var(--text-muted);">
                                    {{ $list->firstItem() + $idx }}
                                </td>
                                <td data-label="Lab & Tanggal" style="padding: 14px 18px;">
                                    <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                                        {{ $item->ruang_lab_nama }}</div>
                                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);"><i
                                            class="fas fa-calendar-day text-primary me-1"></i>
                                        {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d F Y') }}</div>
                                </td>
                                <td data-label="Mapel" style="padding: 14px 18px;">
                                    <div style="font-weight: 600; color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                                        {{ $item->mata_pelajaran }}</div>
                                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--primary); font-weight: 500;">
                                        {{ $item->topik_praktik }}</div>
                                    @if ($item->laporan_kerusakan)
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--danger, #ef4444); margin-top: 3px;"><i
                                                class="fas fa-circle-exclamation me-1"></i> Catatan:
                                            {{ $item->laporan_kerusakan }}</div>
                                    @endif
                                </td>
                                <td data-label="Guru" style="padding: 14px 18px; font-size: var(--text-base, 0.875rem);">
                                    <div style="font-weight: 600; color: var(--text-color);">{{ $item->guru_nama ?: '-' }}
                                    </div>
                                    <span class="badge badge-outline"
                                        style="font-size: var(--text-xs, 0.75rem);">{{ $item->rombel_nama ?: 'Seluruh Siswa' }}</span>
                                </td>
                                <td data-label="Waktu"
                                    style="padding: 14px 18px; text-align: center; font-size: var(--text-base, 0.875rem); font-family: monospace;">
                                    {{ substr($item->jam_mulai, 0, 5) }} - {{ substr($item->jam_selesai, 0, 5) }}
                                </td>
                                <td data-label="Status" style="padding: 14px 18px; text-align: center;">
                                    @if ($item->status === 'dijadwalkan')
                                        <span class="badge badge-info">Dijadwalkan</span>
                                    @elseif ($item->status === 'berlangsung')
                                        <span class="badge badge-warning">Berlangsung</span>
                                    @elseif ($item->status === 'selesai')
                                        <span class="badge badge-success">Selesai</span>
                                    @else
                                        <span class="badge badge-danger">Batal</span>
                                    @endif
                                </td>
                                <td data-label="Aksi" style="padding: 14px 18px; text-align: right;">
                                    <div class="table-actions" style="justify-content: flex-end; gap: 6px;">
                                        @if ($canUpdate)
                                            <button type="button" class="btn-icon btn-status-lab"
                                                data-id="{{ $item->id }}" data-mapel="{{ $item->mata_pelajaran }}"
                                                data-status="{{ $item->status }}"
                                                data-laporan="{{ $item->laporan_kerusakan }}" title="Update Status Sesi">
                                                <i class="fas fa-sliders" style="color: var(--primary);"></i>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <button type="button" class="btn-icon btn-delete-jadwal"
                                                data-id="{{ $item->id }}" data-mapel="{{ $item->mata_pelajaran }}"
                                                title="Hapus Jadwal" style="color: var(--danger, #ef4444);">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                    <i class="fas fa-folder-open mb-2" style="font-size: var(--text-3xl, 1.875rem); opacity: 0.5;"></i>
                                    <div>Belum ada jadwal penggunaan lab yang cocok.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 3. Pagination Footer Resmi SAE --}}
            @if ($list->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $list])
                </div>
            @endif
        </div>

        {{-- Modal Tambah Jadwal Praktik --}}
        <div id="modalJadwal" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card"
                style="width: 100%; max-width: 580px; max-height: 90vh; overflow-y: auto; padding: 24px; border-radius: var(--radius-lg, 12px);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 id="modalJadwalTitle"
                        style="margin: 0; font-size: var(--text-xl, 1.25rem); font-weight: 700; color: var(--text-color);">
                        Jadwalkan Penggunaan Lab
                    </h3>
                    <button type="button" id="btnCloseModalJadwal"
                        style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;">&times;</button>
                </div>

                <form id="formJadwal" method="POST" action="{{ route('dashboard.laboran.jadwal.store') }}">
                    @csrf
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div>
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <label style="font-size: var(--text-base, 0.875rem); font-weight: 600; color: var(--text-color); margin: 0;">
                                    Ruang Lab / Bengkel <span style="color: var(--danger, #ef4444);">*</span>
                                </label>
                                <a href="{{ route('dashboard.sarpras.ruang.index') }}" target="_blank"
                                    style="font-size: var(--text-xs, 0.75rem); color: var(--primary); text-decoration: none;"
                                    title="Kelola Ruang di Sarpras & Aset">
                                    <i class="fas fa-arrow-up-right-from-square me-1"></i> Data Sarpras
                                </a>
                            </div>
                            <select name="ruang_lab_nama" class="form-control" required>
                                <option value="">-- Pilih Ruang dari Sarpras &amp; Aset --</option>
                                @if (isset($daftarRuangSarpras) && $daftarRuangSarpras->isNotEmpty())
                                    @foreach ($daftarRuangSarpras as $r)
                                        <option value="{{ $r->nama_ruang }}">
                                            {{ $r->nama_ruang }} &bull; {{ $r->gedung ?: 'Sarpras' }}
                                            {{ $r->lantai ? '(Lt. ' . $r->lantai . ')' : '' }}
                                        </option>
                                    @endforeach
                                @else
                                    @foreach ($daftarLab as $l)
                                        <option value="{{ $l }}">{{ $l }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 4px;">
                                <i class="fas fa-link me-1 text-primary"></i> Data ruang terintegrasi dari <strong>Sarpras
                                    &amp; Aset</strong>.
                            </div>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Tanggal
                                Praktik <span style="color: var(--danger, #ef4444);">*</span></label>
                            <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" class="form-control"
                                required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Guru
                                Pengampu <span style="color: var(--danger, #ef4444);">*</span></label>
                            <select name="ptk_id" class="form-control" required>
                                <option value="">-- Pilih Guru --</option>
                                @foreach ($allGtk as $g)
                                    <option value="{{ $g->ptk_id }}">{{ $g->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Kelas
                                / Rombel</label>
                            <select name="rombel_id" class="form-control">
                                <option value="">-- Semua Siswa / Umum --</option>
                                @foreach ($allRombel as $rb)
                                    <option value="{{ $rb->rombongan_belajar_id }}">{{ $rb->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Mata
                                Pelajaran <span style="color: var(--danger, #ef4444);">*</span></label>
                            <input type="text" name="mata_pelajaran" class="form-control"
                                placeholder="Contoh: Pemrograman Web / Kimia Dasar" required>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Topik
                                / Materi Praktik <span style="color: var(--danger, #ef4444);">*</span></label>
                            <input type="text" name="topik_praktik" class="form-control"
                                placeholder="Contoh: Reaksi Redoks / Desain Database" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Jam
                                Mulai <span style="color: var(--danger, #ef4444);">*</span></label>
                            <input type="time" name="jam_mulai" class="form-control" value="08:00" required>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Jam
                                Selesai <span style="color: var(--danger, #ef4444);">*</span></label>
                            <input type="time" name="jam_selesai" class="form-control" value="09:30" required>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Kebutuhan
                            Alat / Bahan Praktik</label>
                        <textarea name="alat_bahan_digunakan" class="form-control" rows="2"
                            placeholder="Sebutkan alat, komputer, cairan kimia, atau bahan yang diperlukan..."></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                        <button type="button" class="btn btn-secondary" id="btnCancelModalJadwal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan
                            Jadwal</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Update Status & Laporan Kerusakan --}}
        <div id="modalStatus" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 480px; padding: 24px; border-radius: var(--radius-lg, 12px);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h3 style="margin: 0; font-size: var(--text-xl, 1.25rem); font-weight: 700; color: var(--text-color);">
                        Update Status Praktikum
                    </h3>
                    <button type="button" id="btnCloseModalStatus"
                        style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;">&times;</button>
                </div>

                <form id="formStatus" method="POST" action="">
                    @csrf
                    @method('PUT')
                    <p id="statusDesc" style="font-size: var(--text-base, 0.875rem); color: var(--text-color); margin-bottom: 16px;"></p>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Pilih
                            Status <span style="color: var(--danger, #ef4444);">*</span></label>
                        <select name="status" id="selectStatusLab" class="form-control" required>
                            <option value="dijadwalkan">Dijadwalkan</option>
                            <option value="berlangsung">Sedang Berlangsung</option>
                            <option value="selesai">Praktik Selesai</option>
                            <option value="batal">Dibatalkan</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Laporan
                            Kerusakan / Insiden Pasca-Praktik</label>
                        <textarea name="laporan_kerusakan" id="inputLaporanKerusakan" class="form-control" rows="2"
                            placeholder="Catat jika ada alat pecah, mouse/keyboard rusak, atau insiden..."></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                        <button type="button" class="btn btn-secondary" id="btnCancelModalStatus">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i> Simpan
                            Status</button>
                    </div>
                </form>
            </div>
        </div>

        @push('scripts')
            <script
                src="{{ asset('js/laboran-jadwal.js') }}?v={{ file_exists(public_path('js/laboran-jadwal.js')) ? filemtime(public_path('js/laboran-jadwal.js')) : time() }}">
            </script>
        @endpush
    @endsection
