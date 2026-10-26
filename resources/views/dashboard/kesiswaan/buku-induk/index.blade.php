@extends('layouts.dashboard')

@section('title', 'Pengelolaan Buku Induk Siswa — SAE')
@section('dash_title', 'Buku Induk Siswa')

@section('content')
    {{-- 1. Header Banner & Actions --}}
    <div class="dash-banner"
        style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div
                style="width: 44px; height: 44px; border-radius: var(--radius-lg, 12px); background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                <i class="fas fa-address-book"></i>
            </div>
            <div>
                <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                    Buku Induk Peserta Didik
                </h2>
                <p style="margin: 2px 0 0 0; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                    Register induk siswa: identitas pokok, data orang tua/wali, domisili, dan status kependidikan.
                </p>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('dashboard.kesiswaan.buku-induk.cetak-rekap', request()->only(['rombel_id', 'tahun_masuk', 'status'])) }}"
                target="_blank" class="btn btn-outline" title="Cetak Register Rekap Buku Induk"
                style="padding: 8px 14px; font-size: var(--text-base, 0.875rem); border-radius: var(--radius-md, 8px); font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-print"></i> <span class="d-none d-sm-inline">Cetak Register</span>
            </a>
            @if ($canCreate)
                <button type="button" class="btn btn-primary" id="btnSyncBukuInduk"
                    title="Sinkronkan Seluruh Siswa ke Buku Induk & Buku Klaper"
                    style="padding: 8px 14px; font-size: var(--text-base, 0.875rem); border-radius: var(--radius-md, 8px); font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-rotate"></i> <span>Sinkronisasi</span>
                </button>
            @endif
        </div>
    </div>

    {{-- 2. Flash Messages --}}
    @if (session('success'))
        <div
            style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: #10b981; padding: 12px 16px; border-radius: var(--radius-lg, 12px); margin-bottom: 18px; font-size: var(--text-base, 0.875rem); display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-check-circle"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    {{-- 3. Statistik Ringkasan --}}
    <div class="dash-stat-grid" style="margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6);">
                <i class="fas fa-users"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_siswa'] ?? 0, 0, ',', '.') }}</div>
                <div class="dash-stat-label">Total Siswa Terdaftar</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: var(--success, #10b981);">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_aktif'] ?? 0, 0, ',', '.') }}</div>
                <div class="dash-stat-label">Siswa Aktif</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary, #6366f1);">
                <i class="fas fa-id-card"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_lengkap'] ?? 0, 0, ',', '.') }}</div>
                <div class="dash-stat-label">Identitas (NIK) Terisi</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b);">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_lulus'] ?? 0, 0, ',', '.') }}</div>
                <div class="dash-stat-label">Lulus / Alumni</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239,68,68,0.12); color: var(--danger, #ef4444);">
                <i class="fas fa-person-walking-dashed-line-arrow-right"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_mutasi'] ?? 0, 0, ',', '.') }}</div>
                <div class="dash-stat-label">Mutasi / Keluar</div>
            </div>
        </div>
    </div>

    {{-- 4. Unified Datatable Card (mengacu datatable global SAE) --}}
    <div class="card datatable-card-wrapper" id="tableDataContainer">
        <div class="datatable-toolbar">
            <form method="GET" action="{{ route('dashboard.kesiswaan.buku-induk.index') }}" class="datatable-filter-form"
                style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center; width: 100%;">
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <div class="toolbar-entries">
                        <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                        <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                            @foreach ([10, 15, 25, 50, 100] as $n)
                                <option value="{{ $n }}" {{ (int) ($perPage ?? 25) === $n ? 'selected' : '' }}>
                                    {{ $n }}</option>
                            @endforeach
                        </select>
                        <span>entri</span>
                    </div>

                    <select name="status" id="filterStatusKlaper" class="toolbar-filter-select"
                        style="height: 38px; min-width: 150px;">
                        <option value="aktif" {{ ($status ?? 'aktif') === 'aktif' ? 'selected' : '' }}>Siswa Aktif
                        </option>
                        <option value="lulus" {{ ($status ?? '') === 'lulus' ? 'selected' : '' }}>Lulus</option>
                        <option value="mutasi_keluar" {{ ($status ?? '') === 'mutasi_keluar' ? 'selected' : '' }}>Mutasi
                            Keluar</option>
                        <option value="do" {{ ($status ?? '') === 'do' ? 'selected' : '' }}>Dikeluarkan (DO)</option>
                        <option value="all" {{ ($status ?? '') === 'all' ? 'selected' : '' }}>Semua Status</option>
                    </select>

                    <select name="rombel_id" id="filterRombelBukuInduk" class="toolbar-filter-select"
                        style="height: 38px; min-width: 170px;">
                        <option value="">Semua Rombel</option>
                        @foreach ($rombelList as $r)
                            <option value="{{ $r->rombongan_belajar_id }}"
                                {{ (string) ($rombelId ?? '') === (string) $r->rombongan_belajar_id ? 'selected' : '' }}>
                                {{ $r->nama }}</option>
                        @endforeach
                    </select>

                    <select name="tahun_masuk" id="filterTahunMasuk" class="toolbar-filter-select"
                        style="height: 38px; min-width: 140px;">
                        <option value="">Semua Angkatan</option>
                        @foreach ($tahunMasukList as $th)
                            <option value="{{ $th }}"
                                {{ (string) ($tahunMasuk ?? '') === (string) $th ? 'selected' : '' }}>
                                Angkatan {{ $th }}</option>
                        @endforeach
                    </select>

                    <select name="kelengkapan" id="filterKelengkapan" class="toolbar-filter-select"
                        style="height: 38px; min-width: 160px;">
                        <option value="all" {{ ($kelengkapan ?? 'all') === 'all' ? 'selected' : '' }}>Semua Kelengkapan
                        </option>
                        <option value="lengkap" {{ ($kelengkapan ?? '') === 'lengkap' ? 'selected' : '' }}>NIK & KK
                            Lengkap
                        </option>
                        <option value="belum_lengkap" {{ ($kelengkapan ?? '') === 'belum_lengkap' ? 'selected' : '' }}>
                            Belum
                            Lengkap</option>
                    </select>

                    @if (
                        !empty($q) ||
                            !empty($rombelId) ||
                            !empty($tahunMasuk) ||
                            ($kelengkapan ?? 'all') !== 'all' ||
                            ($status ?? 'aktif') !== 'aktif')
                        <a href="{{ route('dashboard.kesiswaan.buku-induk.index') }}"
                            class="btn btn-outline btn-reset-filter"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                            title="Reset filter">
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>

                <div class="live-search-wrap" style="height: 38px; min-width: 260px;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="liveSearch" name="q"
                        placeholder="Cari nama / NISN / NIK / no. induk..." value="{{ $q ?? '' }}"
                        autocomplete="off">
                    <button type="button" id="clearSearch" class="clear-search {{ !empty($q) ? 'visible' : '' }}"
                        title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </form>
        </div>

        <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color);">
                        <th class="sortable-th {{ ($sort ?? '') === 'nipd' ? 'sorted' : '' }}" data-sort="nipd"
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 150px;">
                            No. Induk
                            <span class="sort-icon">{!! ($sort ?? '') === 'nipd' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                        <th class="sortable-th {{ ($sort ?? '') === 'nama' ? 'sorted' : '' }}" data-sort="nama"
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Nama Peserta Didik
                            <span class="sort-icon">{!! ($sort ?? '') === 'nama' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                        <th class="sortable-th {{ ($sort ?? '') === 'nisn' ? 'sorted' : '' }}" data-sort="nisn"
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 130px;">
                            NISN
                            <span class="sort-icon">{!! ($sort ?? '') === 'nisn' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                        <th class="sortable-th {{ ($sort ?? '') === 'rombel_nama' ? 'sorted' : '' }}"
                            data-sort="rombel_nama"
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px;">
                            Rombel
                            <span class="sort-icon">{!! ($sort ?? '') === 'rombel_nama' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                        <th
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 160px;">
                            NIK / No. KK
                        </th>
                        <th
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 170px;">
                            Orang Tua / Wali
                        </th>
                        <th class="sortable-th {{ ($sort ?? '') === 'status_klaper' ? 'sorted' : '' }}"
                            data-sort="status_klaper"
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 120px; text-align: center;">
                            Status
                            <span class="sort-icon">{!! ($sort ?? '') === 'status_klaper' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                        <th
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 130px; text-align: center;">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        @php
                            $st = $item->status_klaper ?: 'aktif';
                            $badgeColor = match ($st) {
                                'aktif' => 'badge-success',
                                'lulus' => 'badge-primary',
                                'mutasi_keluar' => 'badge-danger',
                                'do' => 'badge-outline',
                                default => 'badge-outline',
                            };
                            $lengkap = !empty($item->nik) && !empty($item->no_kk);
                        @endphp
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td data-label="No. Induk"
                                style="padding: 12px 16px; font-family: monospace; font-weight: 700; color: var(--primary, #3b82f6);">
                                {{ $item->nomor_induk ?: ($item->nipd ?: '-') }}
                            </td>
                            <td data-label="Nama Peserta Didik"
                                style="padding: 12px 16px; font-weight: 700; color: var(--text-color);">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    @if (!empty($item->foto_url))
                                        <img src="{{ $item->foto_url }}" alt="Foto {{ $item->nama }}"
                                            style="width: 34px; height: 34px; border-radius: var(--radius-md, 8px); object-fit: cover; border: 1px solid var(--border-color); flex-shrink: 0;">
                                    @else
                                        <div
                                            style="width: 34px; height: 34px; border-radius: var(--radius-md, 8px); background: rgba(99,102,241,0.1); color: var(--primary, #3b82f6); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                            <i class="fas fa-user-graduate"
                                                style="font-size: var(--text-base, 0.875rem);"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <div>{{ $item->nama }}</div>
                                        <div
                                            style="font-size: var(--text-xs, 0.75rem); font-weight: 500; color: var(--text-muted);">
                                            {{ $item->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}
                                            @if (!empty($item->tempat_lahir))
                                                &bull; {{ $item->tempat_lahir }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="NISN"
                                style="padding: 12px 16px; font-family: monospace; font-size: var(--text-sm, 0.8125rem);">
                                {{ $item->nisn ?: '-' }}
                            </td>
                            <td data-label="Rombel" style="padding: 12px 16px; font-size: var(--text-base, 0.875rem);">
                                @if (!empty($item->rombel_nama))
                                    <span class="badge badge-outline"
                                        style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;">
                                        {{ $item->rombel_nama }}
                                    </span>
                                @else
                                    <span style="color: var(--text-muted);">-</span>
                                @endif
                            </td>
                            <td data-label="NIK / No. KK" style="padding: 12px 16px; font-size: var(--text-xs, 0.75rem);">
                                <div style="font-family: monospace; color: var(--text-color);">
                                    {{ $item->nik ?: '—' }}
                                </div>
                                <div
                                    style="font-family: monospace; color: var(--text-muted); font-size: var(--text-xs, 0.75rem);">
                                    KK: {{ $item->no_kk ?: '—' }}
                                </div>
                                @if ($lengkap)
                                    <span class="badge badge-success"
                                        style="font-size: var(--text-2xs, 0.6875rem); padding: 1px 6px;">
                                        <i class="fas fa-check"></i> Lengkap
                                    </span>
                                @else
                                    <span class="badge badge-warning"
                                        style="font-size: var(--text-2xs, 0.6875rem); padding: 1px 6px;">
                                        <i class="fas fa-circle-exclamation"></i> Belum Lengkap
                                    </span>
                                @endif
                            </td>
                            <td data-label="Orang Tua / Wali"
                                style="padding: 12px 16px; font-size: var(--text-xs, 0.75rem);">
                                <div style="color: var(--text-color);">
                                    <i class="fas fa-mars"
                                        style="font-size: var(--text-xs, 0.75rem); color: #3b82f6;"></i>
                                    {{ $item->nama_ayah ?: '—' }}
                                </div>
                                <div style="color: var(--text-color);">
                                    <i class="fas fa-venus"
                                        style="font-size: var(--text-xs, 0.75rem); color: #ec4899;"></i>
                                    {{ $item->nama_ibu ?: '—' }}
                                </div>
                                @if (!empty($item->nama_wali))
                                    <div style="color: var(--text-muted); font-size: var(--text-xs, 0.75rem);">
                                        <i class="fas fa-user-shield" style="font-size: var(--text-xs, 0.75rem);"></i>
                                        {{ $item->nama_wali }}
                                    </div>
                                @endif
                            </td>
                            <td data-label="Status" style="padding: 12px 16px; text-align: center;">
                                <span class="badge {{ $badgeColor }}"
                                    style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;">{{ strtoupper(str_replace('_', ' ', $st)) }}</span>
                                @if (!empty($item->tahun_masuk))
                                    <div
                                        style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 3px;">
                                        Angkatan {{ $item->tahun_masuk }}
                                    </div>
                                @endif
                            </td>
                            <td data-label="Aksi" style="padding: 12px 16px; text-align: center;">
                                <div class="table-actions"
                                    style="display: flex; gap: 6px; justify-content: center; align-items: center;">
                                    <button type="button" class="btn-icon btn-detail-buku-induk"
                                        data-id="{{ $item->peserta_didik_id }}" data-nama="{{ $item->nama }}"
                                        title="Buka Formulir Buku Induk"
                                        style="width: 34px; height: 34px; border-radius: var(--radius-md, 8px); display: inline-flex; align-items: center; justify-content: center; background: rgba(99,102,241,0.12); color: var(--primary, #6366f1); border: 1px solid rgba(99,102,241,0.25); cursor: pointer;">
                                        <i class="fas fa-pen-to-square"></i>
                                    </button>
                                    <a href="{{ route('dashboard.kesiswaan.buku-induk.cetak', $item->peserta_didik_id) }}"
                                        target="_blank" class="btn-icon" title="Cetak Lembar Buku Induk"
                                        style="width: 34px; height: 34px; border-radius: var(--radius-md, 8px); display: inline-flex; align-items: center; justify-content: center; background: rgba(16,185,129,0.12); color: var(--success, #10b981); border: 1px solid rgba(16,185,129,0.25);">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px 16px; color: var(--text-muted);">
                                <i class="fas fa-address-book"
                                    style="font-size: var(--text-3xl, 2rem); opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                                <div style="font-weight: 600;">Belum ada data Buku Induk yang cocok dengan filter.</div>
                                @if ($canCreate)
                                    <div style="font-size: var(--text-sm, 0.8125rem); margin-top: 6px;">
                                        Klik tombol <strong>Sinkronisasi</strong> untuk menginisialisasi nomor induk seluruh
                                        siswa.
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($items->hasPages())
            <div class="datatable-footer">
                @include('partials.datatable-pagination', ['paginator' => $items])
            </div>
        @endif
    </div>

    {{-- 5. Modal Formulir Buku Induk --}}
    <div id="modalBukuInduk" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 900px; width: 95%; max-height: 90vh; display: flex; flex-direction: column; margin: 0; border-radius: var(--radius-xl, 16px); padding: 20px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color); flex-shrink: 0;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div
                        style="width: 42px; height: 42px; border-radius: var(--radius-lg, 12px); background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                        <i class="fas fa-address-book"></i>
                    </div>
                    <div>
                        <h3 id="modalBukuIndukTitle"
                            style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin: 0;">
                            Formulir Buku Induk
                        </h3>
                        <div id="modalBukuIndukSubtitle"
                            style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                            Memuat data siswa...
                        </div>
                    </div>
                </div>
                <button type="button" class="close-modal" data-target="#modalBukuInduk" aria-label="Tutup"
                    title="Tutup"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.125rem); padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="modalBukuIndukLoading" style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin me-2" style="font-size: var(--text-2xl, 1.5rem);"></i>
                <div>Memuat lembar buku induk...</div>
            </div>

            <form id="formBukuInduk"
                style="display: none; flex-direction: column; flex: 1; min-height: 0; overflow: hidden;">
                @csrf
                <input type="hidden" id="biPesertaDidikId" name="peserta_didik_id">
                <input type="hidden" id="biMethod" name="_method" value="PUT">

                <div class="modal-body-scroll"
                    style="overflow-y: auto; flex: 1; min-height: 0; padding-right: 6px; -webkit-overflow-scrolling: touch;">

                    {{-- A. Identitas Pokok --}}
                    <div class="bi-section">
                        <div class="bi-section-title"><i class="fas fa-id-card"></i> A. Keterangan Identitas Pokok</div>
                        <div class="bi-grid-3">
                            <div class="bi-field">
                                <label>Nomor Induk <span class="bi-req">*</span></label>
                                <input type="text" name="nipd" id="biNipd" class="bi-input"
                                    placeholder="Nomor induk siswa">
                            </div>
                            <div class="bi-field">
                                <label>Nomor Klaper</label>
                                <input type="text" name="nomor_klaper" id="biNomorKlaper" class="bi-input"
                                    placeholder="Nomor klaper abjad">
                            </div>
                            <div class="bi-field">
                                <label>NISN</label>
                                <input type="text" name="nisn" id="biNisn" class="bi-input"
                                    placeholder="10 digit NISN">
                            </div>
                            <div class="bi-field bi-span-2">
                                <label>Nama Lengkap Peserta Didik <span class="bi-req">*</span></label>
                                <input type="text" name="nama" id="biNama" class="bi-input" required>
                            </div>
                            <div class="bi-field">
                                <label>Jenis Kelamin <span class="bi-req">*</span></label>
                                <select name="jenis_kelamin" id="biJenisKelamin" class="bi-input" required>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                            <div class="bi-field">
                                <label>Tempat Lahir</label>
                                <input type="text" name="tempat_lahir" id="biTempatLahir" class="bi-input">
                            </div>
                            <div class="bi-field">
                                <label>Tanggal Lahir</label>
                                <input type="date" name="tanggal_lahir" id="biTanggalLahir" class="bi-input">
                            </div>
                            <div class="bi-field">
                                <label>Agama</label>
                                <input type="text" name="agama_id_str" id="biAgama" class="bi-input">
                            </div>
                            <div class="bi-field">
                                <label>NIK</label>
                                <input type="text" name="nik" id="biNik" class="bi-input" maxlength="20">
                            </div>
                            <div class="bi-field">
                                <label>No. Kartu Keluarga (KK)</label>
                                <input type="text" name="no_kk" id="biNoKk" class="bi-input" maxlength="20">
                            </div>
                            <div class="bi-field">
                                <label>No. Registrasi Akta Lahir</label>
                                <input type="text" name="no_registrasi_akta_lahir" id="biNoAkta" class="bi-input">
                            </div>
                            <div class="bi-field">
                                <label>Kewarganegaraan</label>
                                <input type="text" name="kewarganegaraan" id="biKewarganegaraan" class="bi-input">
                            </div>
                            <div class="bi-field">
                                <label>Anak Keberapa</label>
                                <input type="number" name="anak_keberapa" id="biAnakKe" class="bi-input"
                                    min="1">
                            </div>
                            <div class="bi-field">
                                <label>Jumlah Saudara Kandung</label>
                                <input type="number" name="jumlah_saudara_kandung" id="biJumlahSaudara"
                                    class="bi-input" min="0">
                            </div>
                            <div class="bi-field">
                                <label>Golongan Darah</label>
                                <input type="text" name="golongan_darah" id="biGolonganDarah" class="bi-input"
                                    maxlength="5" placeholder="A / B / AB / O">
                            </div>
                            <div class="bi-field">
                                <label>Bahasa Sehari-hari</label>
                                <input type="text" name="bahasa_sehari_hari" id="biBahasa" class="bi-input">
                            </div>
                            <div class="bi-field">
                                <label>Kelainan Jasmani</label>
                                <input type="text" name="kelainan_jasmani" id="biKelainanJasmani" class="bi-input"
                                    placeholder="Tidak ada">
                            </div>
                            <div class="bi-field bi-span-2">
                                <label>Penyakit yang Pernah Diderita</label>
                                <input type="text" name="penyakit_pernah_diderita" id="biPenyakit" class="bi-input">
                            </div>
                        </div>
                    </div>

                    {{-- B. Alamat & Domisili --}}
                    <div class="bi-section">
                        <div class="bi-section-title"><i class="fas fa-house"></i> B. Keterangan Tempat Tinggal</div>
                        <div class="bi-grid-3">
                            <div class="bi-field bi-span-3">
                                <label>Alamat Jalan / Dusun</label>
                                <input type="text" name="alamat_jalan" id="biAlamat" class="bi-input">
                            </div>
                            <div class="bi-field">
                                <label>RT</label>
                                <input type="text" name="rt" id="biRt" class="bi-input" maxlength="5">
                            </div>
                            <div class="bi-field">
                                <label>RW</label>
                                <input type="text" name="rw" id="biRw" class="bi-input" maxlength="5">
                            </div>
                            <div class="bi-field">
                                <label>Kode Pos</label>
                                <input type="text" name="kode_pos" id="biKodePos" class="bi-input" maxlength="10">
                            </div>
                            <div class="bi-field">
                                <label>Desa / Kelurahan</label>
                                <input type="text" name="desa_kelurahan" id="biDesa" class="bi-input">
                            </div>
                            <div class="bi-field">
                                <label>Kecamatan</label>
                                <input type="text" name="kecamatan" id="biKecamatan" class="bi-input">
                            </div>
                            <div class="bi-field">
                                <label>Kabupaten / Kota</label>
                                <input type="text" name="kabupaten_kota" id="biKabupaten" class="bi-input">
                            </div>
                        </div>
                    </div>

                    {{-- C. Data Ayah --}}
                    <div class="bi-section">
                        <div class="bi-section-title"><i class="fas fa-mars"></i> C. Keterangan Data Ayah Kandung</div>
                        <div class="bi-grid-3">
                            <div class="bi-field bi-span-2">
                                <label>Nama Ayah</label>
                                <input type="text" name="nama_ayah" id="biNamaAyah" class="bi-input">
                            </div>
                            <div class="bi-field">
                                <label>NIK Ayah</label>
                                <input type="text" name="nik_ayah" id="biNikAyah" class="bi-input" maxlength="20">
                            </div>
                            <div class="bi-field">
                                <label>Pekerjaan Ayah</label>
                                <input type="text" name="pekerjaan_ayah_str" id="biPekerjaanAyah" class="bi-input">
                            </div>
                        </div>
                    </div>

                    {{-- D. Data Ibu --}}
                    <div class="bi-section">
                        <div class="bi-section-title"><i class="fas fa-venus"></i> D. Keterangan Data Ibu Kandung</div>
                        <div class="bi-grid-3">
                            <div class="bi-field bi-span-2">
                                <label>Nama Ibu</label>
                                <input type="text" name="nama_ibu" id="biNamaIbu" class="bi-input">
                            </div>
                            <div class="bi-field">
                                <label>NIK Ibu</label>
                                <input type="text" name="nik_ibu" id="biNikIbu" class="bi-input" maxlength="20">
                            </div>
                            <div class="bi-field">
                                <label>Pekerjaan Ibu</label>
                                <input type="text" name="pekerjaan_ibu_str" id="biPekerjaanIbu" class="bi-input">
                            </div>
                        </div>
                    </div>

                    {{-- E. Data Wali --}}
                    <div class="bi-section">
                        <div class="bi-section-title"><i class="fas fa-user-shield"></i> E. Keterangan Data Wali</div>
                        <div class="bi-grid-3">
                            <div class="bi-field bi-span-2">
                                <label>Nama Wali</label>
                                <input type="text" name="nama_wali" id="biNamaWali" class="bi-input">
                            </div>
                            <div class="bi-field">
                                <label>NIK Wali</label>
                                <input type="text" name="nik_wali" id="biNikWali" class="bi-input" maxlength="20">
                            </div>
                            <div class="bi-field">
                                <label>Hubungan dengan Siswa</label>
                                <input type="text" name="hubungan_wali" id="biHubunganWali" class="bi-input"
                                    placeholder="Paman / Kakek / dsb.">
                            </div>
                        </div>
                    </div>

                    {{-- F. Registrasi & Status --}}
                    <div class="bi-section">
                        <div class="bi-section-title"><i class="fas fa-clipboard-check"></i> F. Registrasi Masuk &amp;
                            Status
                            Kependidikan</div>
                        <div class="bi-grid-3">
                            <div class="bi-field bi-span-2">
                                <label>Sekolah Asal</label>
                                <input type="text" name="sekolah_asal" id="biSekolahAsal" class="bi-input">
                            </div>
                            <div class="bi-field">
                                <label>Tahun Masuk / Angkatan</label>
                                <input type="number" name="tahun_masuk" id="biTahunMasuk" class="bi-input"
                                    min="1990" max="2100">
                            </div>
                            <div class="bi-field">
                                <label>Status Kependidikan <span class="bi-req">*</span></label>
                                <select name="status_klaper" id="biStatusKlaper" class="bi-input" required>
                                    <option value="aktif">Aktif</option>
                                    <option value="lulus">Lulus</option>
                                    <option value="mutasi_keluar">Mutasi Keluar</option>
                                    <option value="do">Dikeluarkan (DO)</option>
                                </select>
                            </div>
                            <div class="bi-field">
                                <label>No. HP / WhatsApp</label>
                                <input type="text" name="nomor_telepon_seluler" id="biNoHp" class="bi-input">
                            </div>
                            <div class="bi-field bi-span-3">
                                <label>Catatan Kesiswaan</label>
                                <textarea name="catatan_kesiswaan" id="biCatatan" class="bi-input" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    style="display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-color); flex-shrink: 0; flex-wrap: wrap;">
                    <a href="#" id="btnCetakBukuInduk" target="_blank" class="btn btn-outline"
                        style="border-radius: var(--radius-md, 8px); padding: 8px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fas fa-print"></i> Cetak Lembar
                    </a>
                    <div style="display: flex; gap: 8px;">
                        <button type="button" class="btn btn-outline close-modal" data-target="#modalBukuInduk"
                            style="border-radius: var(--radius-md, 8px); padding: 8px 16px; font-size: var(--text-sm, 0.8125rem);">
                            Batal
                        </button>
                        <button type="submit" id="btnSimpanBukuInduk" class="btn btn-primary"
                            style="border-radius: var(--radius-md, 8px); padding: 8px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-save"></i> Simpan Buku Induk
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .bi-section {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e5e7eb);
            border-radius: var(--radius-lg, 12px);
            padding: 14px 16px;
            margin-bottom: 14px;
        }

        .bi-section-title {
            font-size: var(--text-sm, 0.8125rem);
            font-weight: 800;
            color: var(--text-color, #111827);
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .bi-grid-3 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }

        .bi-span-2 {
            grid-column: span 2;
        }

        .bi-span-3 {
            grid-column: 1 / -1;
        }

        .bi-field label {
            display: block;
            font-size: var(--text-xs, 0.75rem);
            font-weight: 700;
            color: var(--text-muted, #6b7280);
            margin-bottom: 4px;
        }

        .bi-req {
            color: #ef4444;
        }

        .bi-input {
            width: 100%;
            min-height: 36px;
            padding: 6px 10px;
            font-size: var(--text-sm, 0.8125rem);
            border: 1px solid var(--border-color, #e5e7eb);
            border-radius: var(--radius-md, 8px);
            background: var(--bg-hover, #f9fafb);
            color: var(--text-color, #111827);
            box-sizing: border-box;
        }

        @media (max-width: 640px) {
            .bi-span-2 {
                grid-column: 1 / -1;
            }
        }
    </style>
@endpush

@push('scripts')
    <script
        src="{{ asset('js/buku-induk.js') }}?v={{ file_exists(public_path('js/buku-induk.js')) ? filemtime(public_path('js/buku-induk.js')) : time() }}">
    </script>
@endpush
