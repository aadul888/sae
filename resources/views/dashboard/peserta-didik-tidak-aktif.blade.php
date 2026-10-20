@extends('layouts.dashboard')

@section('title', 'Manajemen Data — Peserta Didik Tidak Aktif — SAE')
@section('dash_title', 'Peserta Didik Tidak Aktif')

@section('content')
    @php
        $isAdmin = $isAdmin ?? (session('user')['role'] ?? (session('user')->role ?? '')) === 'admin';
    @endphp
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-user-graduate text-primary me-2"></i> Manajemen Data — Peserta Didik Tidak Aktif &amp;
                Alumni
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Direktori data peserta didik yang telah lulus (alumni) maupun mutasi/keluar beserta riwayat akademik dan
                arsip foto.
            </p>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
            @if ($canCreate || $isAdmin)
                <button type="button" class="btn btn-outline" onclick="openImportExcelModal()"
                    style="display: inline-flex; align-items: center; gap: 8px; height: 38px; padding: 0 14px; border-radius: 8px; font-size: 0.88rem; border-color: #10b981; color: #10b981;"
                    title="Import Excel Peserta Didik Keluar / Tidak Aktif (Format Dapodik)">
                    <i class="fas fa-file-excel"></i>
                    <span>Import Excel</span>
                </button>
            @endif
            @if (($canCreate || $canUpdate) && $grade12ActiveCount > 0)
                <button type="button" id="btnArchiveGrade12" class="btn btn-primary" data-count="{{ $grade12ActiveCount }}"
                    data-url="{{ route('dashboard.peserta-didik-tidak-aktif.archive-grade12') }}"
                    style="padding: 8px 14px; font-size: 0.9rem; font-weight: 600; box-shadow: 0 2px 8px rgba(99,102,241,0.3);"
                    title="Arsipkan Peserta Didik Kelas XII ({{ $grade12ActiveCount }})">
                    <i class="fas fa-box-archive"></i>
                </button>
            @endif
            <a href="{{ route('dashboard.peserta-didik-tidak-aktif.export', ['status' => $status, 'tahun' => $tahun]) }}"
                class="btn btn-outline" style="padding: 8px 14px; font-size: 0.9rem;" title="Ekspor Excel (.CSV)">
                <i class="fas fa-file-excel text-success"></i>
            </a>
        </div>
    </div>

    <!-- Summary Stats Grid -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ number_format($summary['total'], 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Total Peserta Didik Diarsipkan</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ number_format($summary['alumni'], 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Alumni (Lulus)</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
                <i class="fas fa-right-from-bracket"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ number_format($summary['mutasi'], 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Mutasi / Keluar</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
                <i class="fas fa-camera"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ number_format($summary['berfoto'], 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Foto Tersimpan</div>
            </div>
        </div>
    </div>

    <!-- Unified Datatable Peserta Didik Tidak Aktif (Toolbar + Table + Pagination dalam 1 Card Terpadu) -->
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

                    <select id="filterStatus" name="status" class="toolbar-filter-select"
                        style="height: 38px; min-width: 140px;">
                        <option value="">Semua Status</option>
                        <option value="Alumni" {{ $status === 'Alumni' ? 'selected' : '' }}>Alumni (Lulus)</option>
                        <option value="Mutasi" {{ $status === 'Mutasi' ? 'selected' : '' }}>Mutasi / Keluar</option>
                    </select>

                    <select id="filterTahun" name="tahun" class="toolbar-filter-select"
                        style="height: 38px; min-width: 150px;">
                        <option value="">Semua Tahun Lulus</option>
                        @foreach ($filterTahun as $t)
                            <option value="{{ $t }}" {{ $tahun === $t ? 'selected' : '' }}>Tahun
                                {{ $t }}</option>
                        @endforeach
                    </select>

                    @if ($q || $status || $tahun)
                        <a href="{{ route('dashboard.peserta-didik-tidak-aktif.index') }}"
                            class="btn btn-outline btn-reset-filter"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;"
                            title="Reset filter" data-live-reset>
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif

                    @if ($canCreate || $isAdmin)
                        <button type="button" class="btn btn-outline"
                            onclick="openImportExcelModal()"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem; border-color: #10b981; color: #10b981;"
                            title="Import Excel Peserta Didik Keluar / Tidak Aktif (Format Dapodik)">
                            <i class="fas fa-file-excel"></i>
                        </button>
                    @endif
                </div>

                <div class="live-search-wrap" style="height: 38px;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="liveSearch" name="q" placeholder="Cari nama / NISN / NIK / rombel..."
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
                                ['nama', 'Nama Lengkap'],
                                ['nisn', 'NISN / NIPD'],
                                ['jenis_kelamin', 'L/P'],
                                ['nama_rombel_terakhir', 'Rombel Terakhir'],
                                ['tingkat_pendidikan_terakhir', 'Tingkat'],
                                ['tahun_lulus', 'Status & Tahun'],
                            ];
                        @endphp
                        @foreach ($cols as [$key, $label])
                            <th class="sortable-th {{ $sort === $key ? 'sorted' : '' }}"
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; {{ $key === 'jenis_kelamin' ? 'text-align: center;' : '' }}"
                                data-sort="{{ $key }}">
                                {{ $label }}
                                <span class="sort-icon">{!! $sort === $key ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                        @endforeach
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Kontak &amp; Alamat
                        </th>
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($list as $item)
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <td class="cell-pd-nama"
                                style="padding: 14px 18px; font-weight: 700; color: var(--text-color); font-size: 0.88rem;"
                                data-label="Nama">
                                <div class="pd-main-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                    @if (!empty($item->foto_url))
                                        <div class="pd-foto-thumb" id="pdFotoThumb_{{ $item->peserta_didik_id }}"
                                            onclick="openPhotoPreviewModal('{{ $item->foto_url }}', '{{ addslashes($item->nama) }}')"
                                            title="Klik untuk memperbesar pasfoto"
                                            style="width: 42px; height: 42px; border-radius: 10px; background: repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 8px 8px; border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; cursor: pointer; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.25); transition: transform 0.2s ease, border-color 0.2s ease;">
                                            <img src="{{ $item->foto_url }}" alt="Foto {{ $item->nama }}"
                                                style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                    @else
                                        <div class="pd-foto-thumb empty"
                                            style="width: 42px; height: 42px; border-radius: 10px; background: rgba(239,68,68,0.08); border: 1.5px dashed rgba(239,68,68,0.4); display: flex; align-items: center; justify-content: center; color: #ef4444; font-size: 1.05rem; flex-shrink: 0;">
                                            <i class="fas fa-user-slash" style="opacity: 0.7;"></i>
                                        </div>
                                    @endif
                                    <div class="pd-info">
                                        <div class="pd-title-row"
                                            style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                            <span class="pd-nama"
                                                style="font-weight: 700; color: var(--text-color);">{{ $item->nama }}</span>
                                            @if ($item->status_keluar === 'Alumni')
                                                <span class="badge"
                                                    style="font-size: 0.62rem; background: rgba(16,185,129,0.12); color: #10b981; padding: 2px 6px; white-space: nowrap;">
                                                    <i class="fas fa-graduation-cap me-1"></i>Alumni
                                                </span>
                                            @else
                                                <span class="badge"
                                                    style="font-size: 0.62rem; background: rgba(245,158,11,0.12); color: #f59e0b; padding: 2px 6px; white-space: nowrap;">
                                                    <i
                                                        class="fas fa-right-from-bracket me-1"></i>{{ $item->status_keluar }}
                                                </span>
                                            @endif
                                        </div>
                                        @if ($item->nik)
                                            <div
                                                style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace; margin-top: 2px;">
                                                <span class="copyable" data-copy="{{ $item->nik }}" data-label="NIK"
                                                    title="Klik untuk salin NIK">
                                                    NIK: {{ $item->nik }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="cell-pd-nisn"
                                style="padding: 14px 18px; font-family: monospace; font-size: 0.84rem; color: var(--primary);"
                                data-label="NISN/NIPD">
                                <div class="cell-col-right">
                                    <div>
                                        @if ($item->nisn)
                                            <span class="copyable" data-copy="{{ $item->nisn }}" data-label="NISN"
                                                title="Klik untuk salin NISN">{{ $item->nisn }}</span>
                                        @else
                                            -
                                        @endif
                                    </div>
                                    @if ($item->nipd)
                                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                                            <span class="copyable" data-copy="{{ $item->nipd }}" data-label="NIPD"
                                                title="Klik untuk salin NIPD">NIPD: {{ $item->nipd }}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="cell-pd-gender" style="padding: 14px 18px; text-align: center;" data-label="L/P">
                                <span class="badge {{ $item->jenis_kelamin === 'L' ? 'badge-primary' : 'badge-danger' }}"
                                    style="font-size: 0.72rem; padding: 2px 7px;">
                                    {{ $item->jenis_kelamin ?: '-' }}
                                </span>
                            </td>
                            <td class="cell-pd-rombel"
                                style="padding: 14px 18px; font-weight: 600; color: var(--text-color); font-size: 0.84rem;"
                                data-label="Rombel">
                                <span class="badge badge-outline" style="font-size: 0.76rem; padding: 3px 8px;">
                                    {{ $item->nama_rombel_terakhir ?: '-' }}
                                </span>
                            </td>
                            <td class="cell-pd-tingkat"
                                style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);"
                                data-label="Tingkat">
                                {{ $item->tingkat_pendidikan_terakhir ?: '-' }}
                            </td>
                            <td class="cell-pd-ttl"
                                style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);"
                                data-label="Status">
                                <div class="cell-col-right">
                                    <div style="font-weight: 600; color: var(--text-color);">
                                        {{ $item->status_keluar === 'Alumni' ? 'Lulus ' . ($item->tahun_lulus ?: '-') : $item->status_keluar }}
                                    </div>
                                    <div style="font-size: 0.72rem; color: var(--text-muted);">
                                        {{ $item->tanggal_keluar ? \Carbon\Carbon::parse($item->tanggal_keluar)->format('d/m/Y') : '-' }}
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted); max-width: 220px;"
                                data-label="Kontak">
                                <div class="cell-col-right">
                                    @if ($item->nomor_telepon_seluler)
                                        <div><i class="fas fa-phone-alt me-1 text-primary"
                                                style="font-size: 0.72rem;"></i>
                                            <span class="copyable" data-copy="{{ $item->nomor_telepon_seluler }}"
                                                data-label="No HP"
                                                title="Klik untuk salin No HP">{{ $item->nomor_telepon_seluler }}</span>
                                        </div>
                                    @else
                                        <div>-</div>
                                    @endif
                                    @if ($item->alamat_jalan)
                                        <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 0.74rem;"
                                            title="{{ $item->alamat_jalan }}">
                                            <i class="fas fa-location-dot me-1 text-muted"></i> {{ $item->alamat_jalan }}
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="cell-pd-aksi" style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                                <div class="table-actions">
                                    @if ($isAdmin)
                                        <button type="button" class="btn-icon" title="Lihat Biodata Arsip Lengkap"
                                            onclick="openBiodataModal('{{ $item->id }}')">
                                            <i class="fas fa-id-card"></i>
                                        </button>
                                    @else
                                        <span style="color: var(--text-muted); font-size: 0.82rem;">-</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8"
                                style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                                <i class="fas fa-user-slash mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                                <div>Belum ada data peserta didik tidak aktif / alumni yang cocok.</div>
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

    {{-- Modal Biodata Peserta Didik Tidak Aktif / Alumni --}}
    <div id="biodataModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 720px; width: 94%; max-height: 85vh; display: flex; flex-direction: column; margin: 0; border-radius: 14px; padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div id="bioFotoContainer"
                        style="width: 50px; height: 50px; border-radius: 12px; background: rgba(99,102,241,0.12); display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1.5px solid var(--border-color); flex-shrink: 0; box-shadow: 0 2px 8px rgba(0,0,0,0.25);">
                        <i class="fas fa-user-graduate text-primary" style="font-size: 1.3rem;"></i>
                    </div>
                    <div>
                        <h3 id="bioNama"
                            style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0;">
                            Biodata Arsip Peserta Didik</h3>
                        <div id="bioRombel" style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">-</div>
                    </div>
                </div>
                <button type="button" onclick="closeBiodataModal()"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="bioLoading" style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin me-2" style="font-size: 1.4rem;"></i>
                <div>Memuat rincian arsip peserta didik...</div>
            </div>

            <div id="bioContent" style="overflow-y: auto; flex: 1; display: none; font-size: 0.84rem;">
                {{-- 1. Data Pribadi --}}
                <div style="margin-bottom: 12px;">
                    <div
                        style="font-weight: 700; color: var(--primary); font-size: 0.82rem; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-user me-1"></i> Data Pribadi Peserta Didik
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 140px;">NISN / NIPD</td>
                            <td id="bioNisn" style="font-weight: 600; font-family: monospace; color: var(--primary);">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">NIK</td>
                            <td id="bioNik" style="font-family: monospace;">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Jenis Kelamin</td>
                            <td id="bioJk">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Tempat, Tgl Lahir</td>
                            <td id="bioTtl">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Agama</td>
                            <td id="bioAgama">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Kebutuhan Khusus</td>
                            <td id="bioKhusus">-</td>
                        </tr>
                    </table>
                </div>

                {{-- 2. Status Pengarsipan & Akademik --}}
                <div style="margin-bottom: 12px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                    <div
                        style="font-weight: 700; color: var(--primary); font-size: 0.82rem; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-graduation-cap me-1"></i> Status Pengarsipan &amp; Akademik
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 140px;">Status Keluar</td>
                            <td id="bioStatus">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Tahun Lulus</td>
                            <td id="bioTahunLulus">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Tanggal Keluar</td>
                            <td id="bioTglKeluar">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Rombel Terakhir</td>
                            <td id="bioRombelDetail">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Alasan / Keterangan</td>
                            <td id="bioAlasan">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">No. SKHUN</td>
                            <td id="bioSkhun">-</td>
                        </tr>
                    </table>
                </div>

                {{-- 3. Alamat & Domisili --}}
                <div style="margin-bottom: 12px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                    <div
                        style="font-weight: 700; color: var(--primary); font-size: 0.82rem; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-map-marker-alt me-1"></i> Alamat &amp; Tempat Tinggal
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 140px;">Alamat Jalan</td>
                            <td id="bioAlamat">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">RT / RW / Dusun</td>
                            <td id="bioRtRwDusun">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Kelurahan / Desa</td>
                            <td id="bioKelurahan">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Kecamatan / Pos</td>
                            <td id="bioKecamatanPos">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Jenis Tinggal</td>
                            <td id="bioJenisTinggal">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Alat Transportasi</td>
                            <td id="bioTransportasi">-</td>
                        </tr>
                    </table>
                </div>

                {{-- 4. Kontak & Bantuan Sosial --}}
                <div style="margin-bottom: 12px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                    <div
                        style="font-weight: 700; color: var(--primary); font-size: 0.82rem; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-address-book me-1"></i> Kontak &amp; Bantuan Sosial
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 140px;">No. HP</td>
                            <td id="bioHp">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Telepon Rumah</td>
                            <td id="bioTelepon">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Email</td>
                            <td id="bioEmail">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Penerima KPS / KPS</td>
                            <td id="bioKps">-</td>
                        </tr>
                    </table>
                </div>

                {{-- 5. Data Orang Tua & Wali --}}
                <div style="margin-bottom: 12px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                    <div
                        style="font-weight: 700; color: var(--primary); font-size: 0.82rem; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-users me-1"></i> Data Orang Tua &amp; Wali
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 140px;">Data Ayah</td>
                            <td id="bioAyah">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Data Ibu</td>
                            <td id="bioIbu">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Data Wali</td>
                            <td id="bioWali">-</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div
                style="padding-top: 14px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end;">
                <button type="button" onclick="closeBiodataModal()" class="btn btn-outline"
                    style="padding: 8px 18px; font-size: 0.85rem;">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Preview Pasfoto Besar --}}
    <div id="photoPreviewModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);"
        onclick="closePhotoPreviewModal()">
        <div style="position: relative; max-width: 400px; width: 90%; text-align: center;"
            onclick="event.stopPropagation()">
            <img id="imgFullPreview" src="" alt="Pasfoto"
                style="width: 100%; max-height: 70vh; object-fit: contain; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); border: 2px solid #fff;">
            <div id="txtFullPreviewName"
                style="margin-top: 10px; color: #fff; font-weight: 700; font-size: 1rem; text-shadow: 0 2px 4px rgba(0,0,0,0.8);">
            </div>
            <button type="button" onclick="closePhotoPreviewModal()"
                style="position: absolute; top: -12px; right: -12px; width: 32px; height: 32px; border-radius: 50%; background: #ef4444; color: #fff; border: 2px solid #fff; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    {{-- Modal Import Excel Peserta Didik Keluar / Tidak Aktif (Format Dapodik) --}}
    @if ($canCreate || $isAdmin)
        <div id="modalImportExcel" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card"
                style="max-width: 580px; width: 94%; max-height: 90vh; display: flex; flex-direction: column; margin: 0; border-radius: 14px; padding: 22px; background: var(--bg-card); border: 1px solid var(--border-color); box-shadow: 0 10px 30px rgba(0,0,0,0.4);">

                {{-- Header Modal --}}
                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div
                            style="width: 42px; height: 42px; border-radius: 10px; background: rgba(16,185,129,0.12); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                            <i class="fas fa-file-excel"></i>
                        </div>
                        <div>
                            <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0;">
                                Import Peserta Didik Keluar / Alumni
                            </h3>
                            <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                                Format Resmi Ekspor Dapodik (43 Kolom)
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="closeImportExcelModal()"
                        style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.15rem; padding: 4px;"
                        title="Tutup">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form id="formImportExcel"
                    action="{{ route('dashboard.peserta-didik-tidak-aktif.import-excel') }}" method="POST"
                    enctype="multipart/form-data" onsubmit="handleImportExcelSubmit(event)">
                    @csrf

                    {{-- Alert Box Info Petunjuk --}}
                    <div
                        style="margin-bottom: 16px; padding: 12px 14px; border-radius: 10px; background: rgba(99,102,241,0.08); border: 1px solid rgba(99,102,241,0.25); font-size: 0.8rem; line-height: 1.45; color: var(--text-color);">
                        <div
                            style="font-weight: 700; color: var(--primary); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-info-circle"></i> Kompatibel Format Dapodik
                        </div>
                        <div>
                            Unggah langsung berkas Excel/CSV hasil unduhan dari Dapodik menu <strong>Peserta Didik
                                Keluar</strong> tanpa perlu mengubah urutan kolom. Sistem otomatis mendeteksi status keluar
                            (Alumni/Mutasi/Keluar) serta tahun kelulusan.
                        </div>
                    </div>

                    {{-- Unduh Template Card --}}
                    <div
                        style="margin-bottom: 18px; padding: 12px 14px; border-radius: 10px; background: rgba(255,255,255,0.02); border: 1px dashed var(--border-color); display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                        <div>
                            <div style="font-size: 0.84rem; font-weight: 700; color: var(--text-color);">Format Excel
                                Baku Dapodik</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">Unduh contoh template jika ingin
                                menyusun data secara manual</div>
                        </div>
                        <a href="{{ route('dashboard.peserta-didik-tidak-aktif.download-template') }}"
                            class="btn btn-outline"
                            style="padding: 6px 12px; font-size: 0.78rem; display: inline-flex; align-items: center; gap: 6px; border-color: #10b981; color: #10b981; text-decoration: none; white-space: nowrap;">
                            <i class="fas fa-download"></i> Unduh Template
                        </a>
                    </div>

                    {{-- Berkas Input --}}
                    <div style="margin-bottom: 16px;">
                        <label for="inputExcelFile"
                            style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                            Pilih Berkas Excel / CSV <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="file" id="inputExcelFile" name="file_excel" accept=".xlsx,.xls,.csv,.txt"
                            style="width: 100%; padding: 8px 12px; font-size: 0.82rem; border-radius: 8px; border: 1px solid var(--border-color); background: rgba(255,255,255,0.03); color: var(--text-color);"
                            required>
                        <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 4px;">
                            Mendukung format .xlsx, .xls, atau .csv dari Dapodik (Maksimal 30 MB).
                        </div>
                    </div>

                    {{-- Loading / Status State --}}
                    <div id="importExcelProgress"
                        style="display: none; margin-bottom: 14px; padding: 12px; border-radius: 8px; background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.25); text-align: center; font-size: 0.82rem; color: #10b981;">
                        <i class="fas fa-spinner fa-spin me-2"></i> Sedang membaca dan memproses data peserta didik...
                    </div>

                    {{-- Footer Buttons --}}
                    <div
                        style="display: flex; justify-content: flex-end; align-items: center; gap: 10px; margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--border-color);">
                        <button type="button" onclick="closeImportExcelModal()" class="btn btn-outline"
                            style="padding: 8px 18px; font-size: 0.82rem;">
                            Batal
                        </button>
                        <button type="submit" id="btnSubmitImportExcel" class="btn btn-primary"
                            style="padding: 8px 22px; font-size: 0.84rem; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-cloud-arrow-up"></i>
                            <span id="btnSubmitImportExcelText">Import Sekarang</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script
        src="{{ asset('js/peserta-didik-tidak-aktif.js') }}?v={{ filemtime(public_path('js/peserta-didik-tidak-aktif.js')) }}">
    </script>
@endpush
