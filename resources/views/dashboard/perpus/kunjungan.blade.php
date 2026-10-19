@extends('layouts.dashboard')

@section('title', 'Buku Kunjungan - Perpustakaan')

@section('content')
    <div class="dash-content-inner">
        <!-- 1. Header Banner & Actions -->
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div
                    style="width: 48px; height: 48px; border-radius: 12px; background: rgba(59,130,246,0.12); color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0;">
                    <i class="fas fa-user-check"></i>
                </div>
                <div>
                    <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--text-color); margin: 0;">
                        Buku Kunjungan Perpustakaan
                    </h2>
                    <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">
                        Pencatatan presensi dan log pengunjung harian perpustakaan sekolah.
                    </div>
                </div>
            </div>

            <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <button type="button" class="btn btn-primary" id="btnTambahKunjungan" onclick="openModalKunjungan()"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem;"
                    title="Catat Pengunjung Perpustakaan">
                    <i class="fas fa-pen-fancy"></i>
                </button>
            </div>
        </div>

        <!-- 2. Stat Grid Baku SAE -->
        <div class="dash-stat-grid" style="margin-bottom: 20px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: #3b82f6;">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($stats['hari_ini'] ?? 0) }}</div>
                    <div class="dash-stat-label">Pengunjung Hari Ini</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($stats['bulan_ini'] ?? 0) }}</div>
                    <div class="dash-stat-label">Pengunjung Bulan Ini</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(139,92,246,0.12); color: #8b5cf6;">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($stats['siswa'] ?? 0) }}</div>
                    <div class="dash-stat-label">Peserta Didik</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($stats['guru_staf'] ?? 0) }}</div>
                    <div class="dash-stat-label">Guru &amp; Staf</div>
                </div>
            </div>
        </div>

        <!-- Unified Datatable Buku Kunjungan (Toolbar + Table + Pagination dalam 1 Card Terpadu) -->
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <!-- 1. Toolbar Header (Entries, Filters & Live Search) -->
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.perpustakaan.kunjungan') }}" id="filterForm"
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

                            <input type="date" name="tanggal" id="filterTanggal" class="form-control"
                                value="{{ $tanggal ?? '' }}"
                                style="height: 38px; padding: 0 10px; font-size: 0.82rem; border-radius: 8px;"
                                title="Filter Tanggal">

                            <select name="tipe" id="filterTipe" class="toolbar-filter-select"
                                style="height: 38px; min-width: 150px;">
                                <option value="">Semua Tipe</option>
                                <option value="siswa" {{ ($tipe ?? '') === 'siswa' ? 'selected' : '' }}>Peserta Didik
                                </option>
                                <option value="guru" {{ ($tipe ?? '') === 'guru' ? 'selected' : '' }}>Guru</option>
                                <option value="tendik" {{ ($tipe ?? '') === 'tendik' ? 'selected' : '' }}>Tendik</option>
                                <option value="tamu" {{ ($tipe ?? '') === 'tamu' ? 'selected' : '' }}>Tamu / Umum
                                </option>
                            </select>

                            @if ($q || $tanggal || $tipe)
                                <a href="{{ route('dashboard.perpustakaan.kunjungan') }}"
                                    class="btn btn-outline btn-reset-filter"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;"
                                    title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="q" id="searchKunjungan" value="{{ $q ?? '' }}"
                                placeholder="Cari nama, kelas, atau keperluan..." autocomplete="off">
                            <button type="button" id="clearSearch" class="clear-search {{ !empty($q) ? 'visible' : '' }}"
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
                        <tr
                            style="background-color: var(--table-header-bg, #f8fafc); border-bottom: 1px solid var(--border-color);">
                            <th
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 60px;">
                                No</th>
                            <th
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Waktu</th>
                            <th
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Nama Pengunjung</th>
                            <th
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Tipe & Kelas / Unit</th>
                            <th
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Keperluan</th>
                            <th
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 80px;">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($kunjungan as $idx => $row)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 14px 18px; font-size: 0.88rem; color: var(--text-muted);">
                                    {{ ($kunjungan->currentPage() - 1) * $kunjungan->perPage() + $loop->iteration }}
                                </td>
                                <td style="padding: 14px 18px; font-size: 0.85rem;">
                                    <div style="font-weight: 600; color: var(--text-heading);">
                                        {{ \Carbon\Carbon::parse($row->tanggal)->format('d M Y') }}
                                    </div>
                                    <div style="color: var(--text-muted); font-size: 0.78rem; font-family: monospace;">
                                        <i class="far fa-clock"></i> {{ substr($row->jam_kunjung ?? '00:00', 0, 5) }} WIB
                                    </div>
                                </td>
                                <td style="padding: 14px 18px;">
                                    <div style="font-weight: 600; color: var(--text-heading); font-size: 0.9rem;">
                                        {{ $row->nama }}
                                    </div>
                                </td>
                                <td style="padding: 14px 18px;">
                                    <span class="badge"
                                        style="background-color: {{ $row->pengunjung_tipe === 'siswa' ? '#e0e7ff' : '#fef3c7' }}; color: {{ $row->pengunjung_tipe === 'siswa' ? '#3730a3' : '#92400e' }}; font-size: 0.75rem; padding: 2px 8px; border-radius: 4px;">
                                        {{ strtoupper($row->pengunjung_tipe) }}
                                    </span>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                                        {{ $row->rombel_atau_unit ?: '-' }}
                                    </div>
                                </td>
                                <td style="padding: 14px 18px; font-size: 0.88rem; color: var(--text-heading);">
                                    {{ $row->keperluan }}
                                </td>
                                <td style="padding: 14px 18px; text-align: center;">
                                    <div class="table-actions" style="display: flex; gap: 6px; justify-content: center;">
                                        <form action="{{ route('dashboard.perpustakaan.kunjungan.destroy', $row->id) }}"
                                            method="POST" style="display: inline;" data-confirm="delete">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon btn-delete-kunjungan"
                                                data-nama="{{ $row->nama }}" title="Hapus Log"
                                                style="background-color: #fee2e2; color: #ef4444; width: 32px; height: 32px; border-radius: 6px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-trash-alt" style="font-size: 0.8rem;"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 36px; color: var(--text-muted);">
                                    <i class="fas fa-user-friends"
                                        style="font-size: 2.2rem; margin-bottom: 10px; opacity: 0.4;"></i>
                                    <p style="margin: 0; font-size: 0.95rem;">Belum ada data kunjungan perpustakaan.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 3. Footer Paginasi Terpadu Datatable -->
            @if ($kunjungan->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $kunjungan])
                </div>
            @endif
        </div>

        <!-- Modal Catat Kunjungan -->
        <div id="modalKunjungan" class="modal-backdrop"
            style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center; padding: 20px;">
            <div class="modal-card"
                style="background: var(--card-bg, #fff); border-radius: 12px; max-width: 500px; width: 100%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); overflow: hidden;">
                <div
                    style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-heading);">
                        <i class="fas fa-pen-alt" style="color: var(--primary-color); margin-right: 8px;"></i>
                        Catat Kunjungan Baru
                    </h3>
                    <button type="button" onclick="closeModalKunjungan()"
                        style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--text-muted);">&times;</button>
                </div>
                <form action="{{ route('dashboard.perpustakaan.kunjungan.store') }}" method="POST"
                    style="padding: 20px;">
                    @csrf
                    <div style="display: grid; grid-template-columns: 1fr; gap: 14px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div>
                                <label class="form-label"
                                    style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Tanggal
                                    *</label>
                                <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}"
                                    required>
                            </div>
                            <div>
                                <label class="form-label"
                                    style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Jam
                                    Kunjung *</label>
                                <input type="time" name="jam_kunjung" class="form-control"
                                    value="{{ date('H:i') }}" required>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px;">
                            <div>
                                <label class="form-label"
                                    style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Tipe
                                    Pengunjung *</label>
                                <select name="pengunjung_tipe" id="tipeKunjunganSelect" class="form-control" required>
                                    <option value="siswa">Peserta Didik</option>
                                    <option value="guru">Guru</option>
                                    <option value="tendik">Tendik</option>
                                    <option value="tamu">Tamu / Umum</option>
                                </select>
                            </div>
                            <div>
                                <div
                                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                    <label class="form-label"
                                        style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin: 0;">Nama
                                        Lengkap *</label>
                                    <span style="font-size: 0.74rem; color: var(--text-muted);"><i
                                            class="fas fa-search me-1"></i>Relasi Data</span>
                                </div>
                                <input type="hidden" name="pengunjung_id" id="pengunjungIdInput">
                                <input type="search" name="nama" id="namaPengunjungInput" class="form-control"
                                    placeholder="Cari nama, NISN, NIPD, atau NIP..." required autocomplete="off"
                                    data-people-search="{{ route('dashboard.people.search') }}" data-people-kind="all"
                                    data-people-target="#pengunjungIdInput" data-people-type-target="#tipeKunjunganSelect"
                                    data-people-unit-target="#unitPengunjungInput" data-people-clear-unit-on-input="true">
                            </div>
                        </div>
                        <div>
                            <label class="form-label"
                                style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Kelas
                                / Unit Kerja</label>
                            <input type="text" name="rombel_atau_unit" id="unitPengunjungInput" class="form-control"
                                placeholder="Contoh: XII TKJ 1 / Guru Matematika">
                        </div>
                        <div>
                            <label class="form-label"
                                style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Keperluan
                                Kunjungan *</label>
                            <select name="keperluan" class="form-control" required>
                                <option value="Membaca Buku">Membaca Buku</option>
                                <option value="Meminjam / Mengembalikan Buku">Meminjam / Mengembalikan Buku</option>
                                <option value="Mengerjakan Tugas / Diskusi">Mengerjakan Tugas / Diskusi</option>
                                <option value="Menggunakan Komputer / Internet">Menggunakan Komputer / Internet</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                    </div>
                    <div
                        style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                        <button type="button" onclick="closeModalKunjungan()" class="btn btn-secondary">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan
                            Kunjungan</button>
                    </div>
                </form>
            </div>
        </div>
    @endsection

    @push('scripts')
        <script
            src="{{ asset('js/perpus-kunjungan.js') }}?v={{ file_exists(public_path('js/perpus-kunjungan.js')) ? filemtime(public_path('js/perpus-kunjungan.js')) : time() }}">
        </script>
    @endpush
