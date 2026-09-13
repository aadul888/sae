@extends('layouts.dashboard')

@section('title', 'Manajemen Data — Peserta Didik Tidak Aktif — SAE')
@section('dash_title', 'Peserta Didik Tidak Aktif')

@section('content')
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
            @if ($grade12ActiveCount > 0)
                <button type="button" id="btnArchiveGrade12" class="btn btn-primary" data-count="{{ $grade12ActiveCount }}"
                    style="padding: 9px 16px; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 8px rgba(99,102,241,0.3);">
                    <i class="fas fa-box-archive"></i>
                    <span>Arsipkan Siswa Kelas XII ({{ $grade12ActiveCount }})</span>
                </button>
            @endif
            <a href="{{ route('dashboard.peserta-didik-tidak-aktif.export', ['status' => $status, 'tahun' => $tahun]) }}"
                class="btn btn-outline"
                style="padding: 9px 16px; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-file-excel text-success"></i>
                <span>Ekspor Excel (.CSV)</span>
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
                <div class="dash-stat-label">Total Siswa Diarsipkan</div>
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

    <!-- Toolbar & Filter -->
    <div class="card" style="padding: 16px; margin-bottom: 20px;">
        <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
            <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                <div class="toolbar-entries">
                    <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                    <select id="perPageSelect" class="per-page-select">
                        @foreach ([10, 15, 25, 50, 100] as $n)
                            <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}
                            </option>
                        @endforeach
                    </select>
                    <span>entri</span>
                </div>

                <select id="filterStatus" class="toolbar-filter-select" style="min-width: 140px;">
                    <option value="">Semua Status</option>
                    <option value="Alumni" {{ $status === 'Alumni' ? 'selected' : '' }}>Alumni (Lulus)</option>
                    <option value="Mutasi" {{ $status === 'Mutasi' ? 'selected' : '' }}>Mutasi / Keluar</option>
                </select>

                <select id="filterTahun" class="toolbar-filter-select" style="min-width: 150px;">
                    <option value="">Semua Tahun Lulus</option>
                    @foreach ($filterTahun as $t)
                        <option value="{{ $t }}" {{ $tahun === $t ? 'selected' : '' }}>Tahun
                            {{ $t }}</option>
                    @endforeach
                </select>

                @if ($q || $status || $tahun)
                    <a href="{{ route('dashboard.peserta-didik-tidak-aktif.index') }}" class="btn btn-outline"
                        style="padding: 7px 12px; font-size: 0.8rem;" title="Reset filter">
                        <i class="fas fa-undo me-1"></i> Reset
                    </a>
                @endif
            </div>

            <div class="live-search-wrap">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="liveSearch" placeholder="Cari nama / NISN / NIK / rombel..."
                    value="{{ $q }}" autocomplete="off">
                <button type="button" id="clearSearch" class="clear-search {{ $q ? 'visible' : '' }}"
                    title="Hapus pencarian">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Datatable Peserta Didik Tidak Aktif -->
    <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
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
                            data-label="Nama Lengkap">
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
                                    <div class="pd-foto-thumb empty" id="pdFotoThumb_{{ $item->peserta_didik_id }}"
                                        style="width: 42px; height: 42px; border-radius: 10px; background: rgba(99,102,241,0.08); border: 1.5px dashed rgba(99,102,241,0.4); display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--primary); font-size: 0.8rem; flex-shrink: 0;">
                                        <i class="fas fa-user" style="font-size: 0.82rem;"></i>
                                        <span
                                            style="font-size: 0.52rem; font-weight: 800; letter-spacing: 0.5px; margin-top: 2px;">ARSIP</span>
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
                                                <i class="fas fa-right-from-bracket me-1"></i>{{ $item->status_keluar }}
                                            </span>
                                        @endif
                                    </div>
                                    @if ($item->nik)
                                        <div
                                            style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace; margin-top: 2px;">
                                            NIK: {{ $item->nik }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="cell-pd-nisn"
                            style="padding: 14px 18px; font-family: monospace; font-size: 0.84rem; color: var(--primary);"
                            data-label="NISN / NIPD">
                            <div>{{ $item->nisn ?: '-' }}</div>
                            @if ($item->nipd)
                                <div style="font-size: 0.72rem; color: var(--text-muted);">NIPD: {{ $item->nipd }}</div>
                            @endif
                        </td>
                        <td class="cell-pd-gender" style="padding: 14px 18px; text-align: center;" data-label="L/P">
                            <span class="badge {{ $item->jenis_kelamin === 'L' ? 'badge-primary' : 'badge-danger' }}"
                                style="font-size: 0.72rem; padding: 2px 7px;">
                                {{ $item->jenis_kelamin ?: '-' }}
                            </span>
                        </td>
                        <td class="cell-pd-rombel"
                            style="padding: 14px 18px; font-weight: 600; color: var(--text-color); font-size: 0.84rem;"
                            data-label="Rombel Terakhir">
                            <span class="badge badge-outline" style="font-size: 0.76rem; padding: 3px 8px;">
                                {{ $item->nama_rombel_terakhir ?: '-' }}
                            </span>
                        </td>
                        <td class="cell-pd-tingkat"
                            style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);"
                            data-label="Tingkat">
                            {{ $item->tingkat_pendidikan_terakhir ?: '-' }}
                        </td>
                        <td class="cell-pd-ttl" style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);"
                            data-label="Status &amp; Tahun">
                            <div style="font-weight: 600; color: var(--text-color);">
                                {{ $item->status_keluar === 'Alumni' ? 'Lulus ' . ($item->tahun_lulus ?: '-') : $item->status_keluar }}
                            </div>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">
                                {{ $item->tanggal_keluar ? date('d/m/Y', strtotime($item->tanggal_keluar)) : '-' }}
                            </div>
                        </td>
                        <td style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted); max-width: 220px;"
                            data-label="Kontak &amp; Alamat">
                            <div><i class="fas fa-phone me-1 text-primary" style="font-size: 0.75rem;"></i>
                                {{ $item->nomor_telepon_seluler ?: '-' }}</div>
                            <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 0.74rem;"
                                title="{{ $item->alamat_jalan }}">
                                <i class="fas fa-location-dot me-1 text-muted"></i> {{ $item->alamat_jalan ?: '-' }}
                            </div>
                        </td>
                        <td class="cell-pd-aksi" style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                            <div class="table-actions">
                                <button type="button" class="btn-icon" title="Lihat Biodata Arsip Lengkap"
                                    onclick="openBiodataModal('{{ $item->id }}')">
                                    <i class="fas fa-id-card"></i>
                                </button>
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

    <!-- Pagination -->
    @if ($list->hasPages())
        <div class="custom-pagination">
            @if ($list->onFirstPage())
                <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
            @else
                <a href="{{ $list->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i
                        class="fas fa-chevron-left"></i></a>
            @endif
            @php
                $cur = $list->currentPage();
                $last = $list->lastPage();
                $from = max(1, $cur - 2);
                $to = min($last, $cur + 2);
            @endphp
            @if ($from > 1)
                <a href="{{ $list->url(1) }}" class="page-btn">1</a>
                @if ($from > 2)
                    <span class="page-info">&hellip;</span>
                @endif
            @endif
            @for ($i = $from; $i <= $to; $i++)
                <a href="{{ $list->url($i) }}"
                    class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
            @endfor
            @if ($to < $last)
                @if ($to < $last - 1)
                    <span class="page-info">&hellip;</span>
                @endif
                <a href="{{ $list->url($last) }}" class="page-btn">{{ $last }}</a>
            @endif
            @if ($list->hasMorePages())
                <a href="{{ $list->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i
                        class="fas fa-chevron-right"></i></a>
            @else
                <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
            @endif
        </div>
    @endif

    {{-- Modal Biodata Peserta Didik Tidak Aktif / Alumni --}}
    <div id="biodataModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
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
                            Biodata Arsip Siswa</h3>
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
                <div>Memuat rincian arsip siswa...</div>
            </div>

            <div id="bioContent" style="overflow-y: auto; flex: 1; display: none; font-size: 0.84rem;">
                <div style="margin-bottom: 12px;">
                    <div
                        style="font-weight: 700; color: var(--primary); font-size: 0.82rem; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-user me-1"></i> Data Pribadi Siswa
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 140px;">NISN / NIPD</td>
                            <td id="bioNisn" style="font-weight: 600; font-family: monospace; color: var(--primary);">-
                            </td>
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
                    </table>
                </div>

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
                            <td style="padding: 6px 0; color: var(--text-muted);">Tahun Lulus / Keluar</td>
                            <td id="bioTahunLulus">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Rombel Terakhir</td>
                            <td id="bioRombelDetail">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Keterangan / Alasan</td>
                            <td id="bioAlasan">-</td>
                        </tr>
                    </table>
                </div>

                <div style="margin-bottom: 12px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                    <div
                        style="font-weight: 700; color: var(--primary); font-size: 0.82rem; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-address-book me-1"></i> Kontak &amp; Domisili
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 140px;">No. HP / Telepon</td>
                            <td id="bioHp">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Email</td>
                            <td id="bioEmail">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Alamat Jalan</td>
                            <td id="bioAlamat">-</td>
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
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 10000; align-items: center; justify-content: center; backdrop-filter: blur(4px);"
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
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const liveSearch = document.getElementById('liveSearch');
            const clearSearch = document.getElementById('clearSearch');
            const perPageSelect = document.getElementById('perPageSelect');
            const filterStatus = document.getElementById('filterStatus');
            const filterTahun = document.getElementById('filterTahun');
            const btnArchiveGrade12 = document.getElementById('btnArchiveGrade12');

            let debounceTimer;

            function applyFilter() {
                const params = new URLSearchParams(window.location.search);

                if (liveSearch.value.trim()) {
                    params.set('q', liveSearch.value.trim());
                } else {
                    params.delete('q');
                }

                if (filterStatus.value) {
                    params.set('status', filterStatus.value);
                } else {
                    params.delete('status');
                }

                if (filterTahun.value) {
                    params.set('tahun', filterTahun.value);
                } else {
                    params.delete('tahun');
                }

                if (perPageSelect.value) {
                    params.set('perPage', perPageSelect.value);
                }

                params.set('page', '1');
                window.location.search = params.toString();
            }

            if (perPageSelect) perPageSelect.addEventListener('change', applyFilter);
            if (filterStatus) filterStatus.addEventListener('change', applyFilter);
            if (filterTahun) filterTahun.addEventListener('change', applyFilter);

            if (liveSearch) {
                liveSearch.addEventListener('input', function() {
                    if (this.value.trim()) {
                        clearSearch.classList.add('visible');
                    } else {
                        clearSearch.classList.remove('visible');
                    }
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(applyFilter, 500);
                });

                liveSearch.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        clearTimeout(debounceTimer);
                        applyFilter();
                    }
                });
            }

            if (clearSearch) {
                clearSearch.addEventListener('click', function() {
                    liveSearch.value = '';
                    clearSearch.classList.remove('visible');
                    applyFilter();
                });
            }

            // Sortable column headers
            document.querySelectorAll('.sortable-th').forEach(function(th) {
                th.style.cursor = 'pointer';
                th.addEventListener('click', function() {
                    const sortKey = this.dataset.sort;
                    if (!sortKey) return;
                    const params = new URLSearchParams(window.location.search);
                    const currentSort = params.get('sort') || 'nama';
                    const currentDir = params.get('sort_dir') || 'asc';
                    let newDir = 'asc';
                    if (currentSort === sortKey) {
                        newDir = currentDir === 'asc' ? 'desc' : 'asc';
                    }
                    params.set('sort', sortKey);
                    params.set('sort_dir', newDir);
                    params.set('page', '1');
                    window.location.search = params.toString();
                });
            });

            // Aksi Pengarsipan Siswa Tingkat XII
            if (btnArchiveGrade12) {
                btnArchiveGrade12.addEventListener('click', async function() {
                    const count = this.getAttribute('data-count');
                    if (!count || count <= 0) {
                        alert('Tidak ada siswa kelas XII yang perlu diarsipkan.');
                        return;
                    }

                    if (!confirm(`Apakah Anda yakin ingin mengarsipkan ${count} siswa kelas XII?`)) {
                        return;
                    }

                    try {
                        const response = await fetch(
                            '{{ route('peserta-didik-tidak-aktif.archive-grade12') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    count: parseInt(count)
                                })
                            });

                        const result = await response.json();

                        if (result.status === 'success') {
                            alert(result.message);
                            window.location.reload();
                        } else {
                            alert('Gagal: ' + result.message);
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat mengarsipkan data.');
                    }
                });
            }
        });

        function openBiodataModal(id) {
            const modal = document.getElementById('biodataModal');
            const loading = document.getElementById('bioLoading');
            const content = document.getElementById('bioContent');

            modal.style.display = 'flex';
            loading.style.display = 'block';
            content.style.display = 'none';

            fetch(`{{ url('dashboard/manajemen-data/peserta-didik-tidak-aktif') }}/${id}`)
                .then(res => res.json())
                .then(res => {
                    loading.style.display = 'none';
                    if (res.status === 'success' && res.data) {
                        const d = res.data;
                        content.style.display = 'block';

                        document.getElementById('bioNama').textContent = d.nama || '-';
                        document.getElementById('bioRombel').textContent = (d.nama_rombel_terakhir ? d
                            .nama_rombel_terakhir + ' • Tingkat ' + (d.tingkat_pendidikan_terakhir || '-') : '-');
                        document.getElementById('bioNisn').textContent = (d.nisn || '-') + (d.nipd ? ' / ' + d.nipd :
                            '');
                        document.getElementById('bioNik').textContent = d.nik || '-';
                        document.getElementById('bioJk').textContent = d.jenis_kelamin === 'L' ? 'Laki-Laki' : (d
                            .jenis_kelamin === 'P' ? 'Perempuan' : '-');
                        document.getElementById('bioTtl').textContent = [d.tempat_lahir, d.tanggal_lahir].filter(
                            Boolean).join(', ') || '-';
                        document.getElementById('bioAgama').textContent = d.agama_id_str || '-';

                        const statusBadge = d.status_keluar === 'Alumni' ?
                            '<span class="badge" style="background: rgba(16,185,129,0.15); color: #10b981; font-weight: 700; padding: 3px 8px;"><i class="fas fa-graduation-cap me-1"></i>Alumni (Lulus)</span>' :
                            '<span class="badge" style="background: rgba(245,158,11,0.15); color: #f59e0b; font-weight: 700; padding: 3px 8px;"><i class="fas fa-right-from-bracket me-1"></i>' +
                            (d.status_keluar || 'Mutasi') + '</span>';

                        document.getElementById('bioStatus').innerHTML = statusBadge;
                        document.getElementById('bioTahunLulus').textContent = (d.tahun_lulus ? 'Tahun ' + d
                            .tahun_lulus : '-') + (d.tanggal_keluar ? ' (Tgl: ' + d.tanggal_keluar + ')' : '');
                        document.getElementById('bioRombelDetail').textContent = (d.nama_rombel_terakhir || '-') + (d
                            .kurikulum_id_str ? ' (' + d.kurikulum_id_str + ')' : '');
                        document.getElementById('bioAlasan').textContent = d.alasan_keluar || '-';

                        document.getElementById('bioHp').textContent = d.nomor_telepon_seluler || '-';
                        document.getElementById('bioEmail').textContent = d.email || '-';
                        document.getElementById('bioAlamat').textContent = d.alamat_jalan || '-';

                        const fotoContainer = document.getElementById('bioFotoContainer');
                        if (d.foto_url) {
                            fotoContainer.innerHTML =
                                `<img src="${d.foto_url}" alt="${d.nama}" style="width: 100%; height: 100%; object-fit: cover;">`;
                        } else {
                            fotoContainer.innerHTML =
                                `<i class="fas fa-user-graduate text-primary" style="font-size: 1.3rem;"></i>`;
                        }
                    } else {
                        loading.style.display = 'block';
                        loading.innerHTML =
                            '<div style="color: var(--danger);"><i class="fas fa-exclamation-circle me-1"></i> Gagal memuat rincian arsip siswa.</div>';
                    }
                })
                .catch(err => {
                    loading.style.display = 'block';
                    loading.innerHTML =
                        '<div style="color: var(--danger);"><i class="fas fa-exclamation-circle me-1"></i> Kesalahan jaringan: ' +
                        err.message + '</div>';
                });
        }

        function closeBiodataModal() {
            document.getElementById('biodataModal').style.display = 'none';
        }

        function openPhotoPreviewModal(url, name) {
            document.getElementById('imgFullPreview').src = url;
            document.getElementById('txtFullPreviewName').innerText = name;
            document.getElementById('photoPreviewModal').style.display = 'flex';
        }

        function closePhotoPreviewModal() {
            document.getElementById('photoPreviewModal').style.display = 'none';
        }
    </script>
@endpush
