@extends('layouts.dashboard')

@section('title', 'Pemeliharaan Preventif Sarpras - Teknisi')

@section('content')
    @include('dashboard.laboran.partials.header-actions')

    <div class="dash-content-inner">
        <!-- 1. Header Banner & Actions -->
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg, 12px); background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); flex-shrink: 0;">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                        Jadwal Pemeliharaan Preventif (Maintenance)
                    </h2>
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        Program perawatan berkala gedung, kelistrikan, perairan, sanitasi/drainase, pendingin AC, dan
                        infrastruktur IT.
                    </div>
                </div>
            </div>

            <div class="dash-banner-actions dash-banner-access">
                <button type="button" class="btn btn-outline" data-modal-open="modalImportPM"
                    title="Import Excel">
                    <i class="fas fa-file-excel text-success"></i>
                </button>
                <button type="button" class="btn btn-primary" id="btnTambahJadwalPM"
                    title="Tambah Jadwal Preventive Maintenance">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        </div>

        <!-- 2. Stat Grid Baku SAE -->
        <div class="dash-stat-grid" style="margin-bottom: 20px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6);">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($stats['terjadwal'] ?? 0) }}</div>
                    <div class="dash-stat-label">Terjadwal Datang</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b);">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($stats['proses'] ?? 0) }}</div>
                    <div class="dash-stat-label">Sedang Berjalan</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: var(--success, #10b981);">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($stats['selesai'] ?? 0) }}</div>
                    <div class="dash-stat-label">Tuntas / Selesai</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239,68,68,0.12); color: var(--danger, #ef4444);">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($stats['tertunda'] ?? 0) }}</div>
                    <div class="dash-stat-label">Tertunda / Lewat Jadwal</div>
                </div>
            </div>
        </div>

        <!-- Unified Datatable Pemeliharaan (Toolbar + Table + Pagination dalam 1 Card Terpadu) -->
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <!-- 1. Toolbar Header (Entries, Filters & Live Search) -->
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.teknisi.pemeliharaan') }}" id="filterForm"
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

                            <select name="kategori" id="filterKategoriPM" class="toolbar-filter-select"
                                style="height: 38px; min-width: 160px;">
                                <option value="">Semua Kategori</option>
                                <option value="bangunan" {{ ($kategori ?? '') === 'bangunan' ? 'selected' : '' }}>Bangunan &
                                    Fisik</option>
                                <option value="kelistrikan" {{ ($kategori ?? '') === 'kelistrikan' ? 'selected' : '' }}>
                                    Kelistrikan & Panel</option>
                                <option value="perairan" {{ ($kategori ?? '') === 'perairan' ? 'selected' : '' }}>Perairan
                                    &
                                    Plumbing</option>
                                <option value="kebersihan" {{ ($kategori ?? '') === 'kebersihan' ? 'selected' : '' }}>
                                    Kebersihan
                                    Lingkungan</option>
                                <option value="ac_pendingin" {{ ($kategori ?? '') === 'ac_pendingin' ? 'selected' : '' }}>
                                    AC &
                                    Pendingin</option>
                                <option value="it_jaringan" {{ ($kategori ?? '') === 'it_jaringan' ? 'selected' : '' }}>IT
                                    &
                                    Jaringan</option>
                            </select>

                            <select name="frekuensi" id="filterFrekuensi" class="toolbar-filter-select"
                                style="height: 38px; min-width: 140px;">
                                <option value="">Semua Frekuensi</option>
                                <option value="mingguan" {{ ($frekuensi ?? '') === 'mingguan' ? 'selected' : '' }}>Mingguan
                                </option>
                                <option value="bulanan" {{ ($frekuensi ?? '') === 'bulanan' ? 'selected' : '' }}>Bulanan
                                </option>
                                <option value="triwulan" {{ ($frekuensi ?? '') === 'triwulan' ? 'selected' : '' }}>Triwulan
                                </option>
                                <option value="semesteran" {{ ($frekuensi ?? '') === 'semesteran' ? 'selected' : '' }}>
                                    Semesteran</option>
                                <option value="tahunan" {{ ($frekuensi ?? '') === 'tahunan' ? 'selected' : '' }}>Tahunan
                                </option>
                            </select>

                            <select name="status" id="filterStatusPM" class="toolbar-filter-select"
                                style="height: 38px; min-width: 140px;">
                                <option value="">Semua Status</option>
                                <option value="terjadwal" {{ ($status ?? '') === 'terjadwal' ? 'selected' : '' }}>Terjadwal
                                </option>
                                <option value="proses" {{ ($status ?? '') === 'proses' ? 'selected' : '' }}>Dalam Proses
                                </option>
                                <option value="selesai" {{ ($status ?? '') === 'selesai' ? 'selected' : '' }}>Selesai
                                </option>
                                <option value="tertunda" {{ ($status ?? '') === 'tertunda' ? 'selected' : '' }}>Tertunda
                                </option>
                            </select>

                            @if ($q || $kategori || $frekuensi || $status)
                                <a href="{{ route('dashboard.teknisi.pemeliharaan') }}"
                                    class="btn btn-outline btn-reset-filter"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                    title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif

                            <button type="button" class="btn btn-outline" data-teknisi-bulk-delete
                                data-modul="pemeliharaan" data-endpoint="{{ route('dashboard.teknisi.bulk-delete') }}"
                                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                title="Hapus data terpilih" aria-label="Hapus data terpilih">
                                <i class="fas fa-trash-alt" style="color: var(--danger, #ef4444);"></i>
                            </button>
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="q" id="searchPM" value="{{ $q ?? '' }}"
                                placeholder="Cari kode PM, kegiatan, lokasi..." autocomplete="off">
                            <button type="button" id="clearSearch"
                                class="clear-search {{ !empty($q) ? 'visible' : '' }}" title="Hapus pencarian">
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
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 60px;">
                                No</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Kode & Kegiatan</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Bidang & Lokasi</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Frekuensi & Jadwal</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                PJ & Biaya</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Status</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 130px;">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pemeliharaan as $idx => $row)
                            @php
                                $kategoriLabel =
                                    [
                                        'bangunan' => 'Bangunan Fisik',
                                        'kelistrikan' => 'Kelistrikan',
                                        'perairan' => 'Perairan & Pipa',
                                        'kebersihan' => 'Kebersihan Lingkungan',
                                        'ac_pendingin' => 'AC & Pendingin',
                                        'it_jaringan' => 'IT & Jaringan',
                                    ][$row->kategori] ?? ucfirst($row->kategori);

                                $statusColor = [
                                    'terjadwal' => ['bg' => '#e0f2fe', 'color' => '#0369a1', 'label' => 'Terjadwal'],
                                    'proses' => ['bg' => '#fef3c7', 'color' => '#92400e', 'label' => 'Dalam Proses'],
                                    'selesai' => ['bg' => '#dcfce7', 'color' => '#15803d', 'label' => 'Selesai'],
                                    'tertunda' => ['bg' => '#fee2e2', 'color' => '#b91c1c', 'label' => 'Tertunda'],
                                ][$row->status] ?? [
                                    'bg' => '#f1f5f9',
                                    'color' => '#475569',
                                    'label' => ucfirst($row->status),
                                ];
                            @endphp
                            <tr style="border-bottom: 1px solid var(--border-color);" data-bulk-id="{{ $row->id }}">
                                <td style="padding: 14px 18px; font-size: var(--text-base, 0.875rem); color: var(--text-muted);">
                                    {{ ($pemeliharaan->currentPage() - 1) * $pemeliharaan->perPage() + $loop->iteration }}
                                </td>
                                <td style="padding: 14px 18px;">
                                    <div style="font-weight: 700; color: var(--text-heading); font-size: var(--text-base, 0.875rem);">
                                        {{ $row->nama_kegiatan }}
                                    </div>
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-family: monospace;">
                                        {{ $row->kode_pemeliharaan }}
                                    </div>
                                    @if ($row->catatan_hasil)
                                        <div style="font-size: var(--text-sm, 0.8125rem); color: var(--success, #10b981); margin-top: 4px;">
                                            <i class="fas fa-check-circle"></i> {{ Str::limit($row->catatan_hasil, 60) }}
                                        </div>
                                    @endif
                                </td>
                                <td style="padding: 14px 18px;">
                                    <span class="badge"
                                        style="background-color: var(--text-main, #f1f5f9); color: var(--text-heading); font-size: var(--text-xs, 0.75rem); padding: 3px 8px; border-radius: var(--radius-xs, 4px); font-weight: 600;">
                                        {{ $kategoriLabel }}
                                    </span>
                                    <div
                                        style="font-weight: 600; color: var(--text-heading); font-size: var(--text-base, 0.875rem); margin-top: 4px;">
                                        <i class="fas fa-map-marker-alt" style="color: var(--danger, #ef4444); font-size: var(--text-sm, 0.8125rem);"></i>
                                        {{ $row->lokasi_aset }}
                                    </div>
                                </td>
                                <td style="padding: 14px 18px; font-size: var(--text-base, 0.875rem);">
                                    <span class="badge"
                                        style="background-color: #ede9fe; color: #6d28d9; font-size: var(--text-xs, 0.75rem); padding: 2px 6px; border-radius: var(--radius-xs, 4px);">
                                        {{ strtoupper($row->frekuensi) }}
                                    </span>
                                    <div style="color: var(--text-heading); font-weight: 600; margin-top: 4px;">
                                        Jadwal: {{ \Carbon\Carbon::parse($row->tgl_jadwal)->format('d M Y') }}
                                    </div>
                                    @if ($row->tgl_realisasi)
                                        <div style="color: var(--success, #10b981); font-size: var(--text-sm, 0.8125rem);">
                                            Realisasi: {{ \Carbon\Carbon::parse($row->tgl_realisasi)->format('d M Y') }}
                                        </div>
                                    @endif
                                </td>
                                <td style="padding: 14px 18px; font-size: var(--text-base, 0.875rem);">
                                    <div style="font-weight: 600; color: var(--text-heading);">
                                        {{ $row->penanggung_jawab ?: '-' }}</div>
                                    @if ($row->biaya > 0)
                                        <div style="font-size: var(--text-sm, 0.8125rem); color: var(--warning, #d97706); font-weight: 600; margin-top: 2px;">
                                            Rp {{ number_format($row->biaya, 0, ',', '.') }}
                                        </div>
                                    @else
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">Biaya: Rp 0</div>
                                    @endif
                                </td>
                                <td style="padding: 14px 18px;">
                                    <span class="badge"
                                        style="background-color: {{ $statusColor['bg'] }}; color: {{ $statusColor['color'] }}; font-size: var(--text-sm, 0.8125rem); padding: 4px 10px; border-radius: var(--radius-lg, 12px); font-weight: 600;">
                                        {{ $statusColor['label'] }}
                                    </span>
                                </td>
                                <td style="padding: 14px 18px; text-align: center;">
                                    <div class="table-actions" style="display: flex; gap: 6px; justify-content: center;">
                                        <button type="button" class="btn-icon btn-update-pm"
                                            data-item="{{ json_encode($row) }}" title="Update Status & Realisasi"
                                            style="background-color: var(--primary, #3b82f6); color: #fff; width: 32px; height: 32px; border-radius: var(--radius-sm, 6px); border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-check-square" style="font-size: var(--text-sm, 0.8125rem);"></i>
                                        </button>
                                        <form action="{{ route('dashboard.teknisi.pemeliharaan.destroy', $row->id) }}"
                                            method="POST" style="display: inline;" data-confirm="delete">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon btn-delete-pm"
                                                data-nama="{{ $row->nama_kegiatan }}" title="Hapus Jadwal"
                                                style="background-color: #fee2e2; color: var(--danger, #ef4444); width: 32px; height: 32px; border-radius: var(--radius-sm, 6px); border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-trash-alt" style="font-size: var(--text-sm, 0.8125rem);"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 36px; color: var(--text-muted);">
                                    <i class="fas fa-clipboard-list"
                                        style="font-size: var(--text-3xl, 1.875rem); margin-bottom: 10px; opacity: 0.4;"></i>
                                    <p style="margin: 0; font-size: var(--text-md, 1rem);">Belum ada jadwal pemeliharaan preventif.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 3. Footer Paginasi Terpadu Datatable -->
            @if ($pemeliharaan->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $pemeliharaan])
                </div>
            @endif
        </div>

        <!-- Modal Tambah Jadwal PM Baru -->
        <div id="modalCreatePM" class="modal-backdrop"
            style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center; padding: 20px;">
            <div class="modal-card"
                style="background: var(--card-bg, #fff); border-radius: var(--radius-lg, 12px); max-width: 600px; width: 100%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); overflow: hidden;">
                <div
                    style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0; font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-heading);">
                        <i class="fas fa-plus-circle" style="color: var(--primary-color); margin-right: 8px;"></i>
                        Tambah Jadwal Pemeliharaan Preventif
                    </h3>
                    <button type="button" onclick="closeModalCreatePM()"
                        style="background: none; border: none; font-size: var(--text-xl, 1.25rem); cursor: pointer; color: var(--text-muted);">&times;</button>
                </div>
                <form action="{{ route('dashboard.teknisi.pemeliharaan.store') }}" method="POST"
                    style="padding: 20px;">
                    @csrf
                    <div style="display: grid; grid-template-columns: 1fr; gap: 14px;">
                        <div>
                            <label class="form-label"
                                style="font-size: var(--text-base, 0.875rem); font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Nama
                                Kegiatan / Program PM *</label>
                            <input type="text" name="nama_kegiatan" class="form-control"
                                placeholder="Contoh: Kuras Toren Air & Pengecekan Pompa Distribusi" required>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div>
                                <label class="form-label"
                                    style="font-size: var(--text-base, 0.875rem); font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Bidang
                                    Sarpras *</label>
                                <select name="kategori" class="form-control" required>
                                    <option value="bangunan">Bangunan & Fisik</option>
                                    <option value="kelistrikan">Kelistrikan & Panel</option>
                                    <option value="perairan">Perairan & Plumbing</option>
                                    <option value="kebersihan">Kebersihan Lingkungan</option>
                                    <option value="ac_pendingin">AC & Pendingin Ruangan</option>
                                    <option value="it_jaringan">IT, Server & Jaringan</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label"
                                    style="font-size: var(--text-base, 0.875rem); font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Frekuensi
                                    Servis *</label>
                                <select name="frekuensi" class="form-control" required>
                                    <option value="mingguan">Mingguan</option>
                                    <option value="bulanan" selected>Bulanan</option>
                                    <option value="triwulan">Triwulan (3 Bulan)</option>
                                    <option value="semesteran">Semesteran (6 Bulan)</option>
                                    <option value="tahunan">Tahunan</option>
                                </select>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div>
                                <div
                                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                    <label class="form-label"
                                        style="font-size: var(--text-base, 0.875rem); font-weight: 600; color: var(--text-heading); margin: 0;">Lokasi
                                        / Aset Sasaran *</label>
                                    <span style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);"><i
                                            class="fas fa-building me-1"></i>Sarpras</span>
                                </div>
                                <input list="listLokasiAsetPm" name="lokasi_aset" class="form-control"
                                    placeholder="Pilih Ruang/Aset Sarpras atau ketik..." required>
                                <datalist id="listLokasiAsetPm">
                                    @foreach ($daftarRuang as $rng)
                                        <option value="{{ $rng->nama_ruang }} ({{ $rng->gedung }})">Ruang:
                                            {{ $rng->nama_ruang }} [Lantai {{ $rng->lantai }}]</option>
                                    @endforeach
                                    @foreach ($daftarAset as $ast)
                                        <option value="{{ $ast->nama_barang }} ({{ $ast->kode_aset }})">Aset:
                                            {{ $ast->nama_barang }} [{{ $ast->kategori }}]</option>
                                    @endforeach
                                </datalist>
                            </div>
                            <div>
                                <label class="form-label"
                                    style="font-size: var(--text-base, 0.875rem); font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Tanggal
                                    Rencana Jadwal *</label>
                                <input type="date" name="tgl_jadwal" class="form-control"
                                    value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div>
                                <div
                                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                    <label class="form-label"
                                        style="font-size: var(--text-base, 0.875rem); font-weight: 600; color: var(--text-heading); margin: 0;">Penanggung
                                        Jawab</label>
                                    <span style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);"><i
                                            class="fas fa-user-check me-1"></i>GTK</span>
                                </div>
                                <input type="search" name="penanggung_jawab" class="form-control"
                                    placeholder="Cari GTK atau ketik nama..." autocomplete="off"
                                    data-people-search="{{ route('dashboard.people.search') }}" data-people-kind="gtk">
                            </div>
                            <div>
                                <label class="form-label"
                                    style="font-size: var(--text-base, 0.875rem); font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Estimasi
                                    Anggaran (Rp)</label>
                                <input type="number" name="biaya" class="form-control" value="0" min="0"
                                    step="5000">
                            </div>
                        </div>
                    </div>
                    <div
                        style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                        <button type="button" onclick="closeModalCreatePM()" class="btn btn-secondary">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Jadwal</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Update Realisasi PM -->
        <div id="modalUpdatePM" class="modal-backdrop"
            style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center; padding: 20px;">
            <div class="modal-card"
                style="background: var(--card-bg, #fff); border-radius: var(--radius-lg, 12px); max-width: 540px; width: 100%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); overflow: hidden;">
                <div
                    style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0; font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-heading);">
                        <i class="fas fa-tasks" style="color: var(--primary, #3b82f6); margin-right: 8px;"></i>
                        Update Realisasi Pemeliharaan
                    </h3>
                    <button type="button" onclick="closeModalUpdatePM()"
                        style="background: none; border: none; font-size: var(--text-xl, 1.25rem); cursor: pointer; color: var(--text-muted);">&times;</button>
                </div>
                <form id="formUpdatePM" action="" method="POST" style="padding: 20px;">
                    @csrf
                    <div style="display: grid; grid-template-columns: 1fr; gap: 14px;">
                        <div style="background: var(--table-header-bg, #f8fafc); padding: 12px; border-radius: var(--radius-md, 8px);">
                            <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">Kegiatan:</div>
                            <div id="updateNamaPM" style="font-weight: 700; color: var(--text-heading); font-size: var(--text-md, 1rem);">
                                -
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div>
                                <label class="form-label"
                                    style="font-size: var(--text-base, 0.875rem); font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Status
                                    *</label>
                                <select name="status" id="updateStatusPMVal" class="form-control" required>
                                    <option value="terjadwal">Terjadwal</option>
                                    <option value="proses">Sedang Dikerjakan</option>
                                    <option value="selesai">Selesai</option>
                                    <option value="tertunda">Tertunda</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label"
                                    style="font-size: var(--text-base, 0.875rem); font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Tanggal
                                    Realisasi</label>
                                <input type="date" name="tgl_realisasi" id="updateTglRealisasiVal"
                                    class="form-control" value="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                        <div>
                            <label class="form-label"
                                style="font-size: var(--text-base, 0.875rem); font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Catatan
                                Hasil & Rekomendasi</label>
                            <textarea name="catatan_hasil" id="updateCatatanHasilVal" class="form-control" rows="3"
                                placeholder="Jelaskan kondisi aset setelah diservis atau catatan komponen yang perlu diganti..."></textarea>
                        </div>
                        <div>
                            <label class="form-label"
                                style="font-size: var(--text-base, 0.875rem); font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Realisasi
                                Biaya Aktual (Rp)</label>
                            <input type="number" name="biaya" id="updateBiayaPMVal" class="form-control"
                                value="0" min="0" step="1000">
                        </div>
                    </div>
                    <div
                        style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                        <button type="button" onclick="closeModalUpdatePM()" class="btn btn-secondary">Batal</button>
                        <button type="submit" class="btn btn-primary"
                            style="background-color: var(--primary, #3b82f6); border-color: var(--primary, #3b82f6);"><i class="fas fa-save"></i> Simpan
                            Realisasi</button>
                    </div>
                </form>
            </div>
        </div>

        @include('dashboard.teknisi._partials.modal-import', [
            'modalId' => 'modalImportPM',
            'title' => 'Import Jadwal Pemeliharaan',
            'templateRoute' => route('dashboard.teknisi.pemeliharaan.template'),
            'actionRoute' => route('dashboard.teknisi.pemeliharaan.import'),
            'hint' => 'Satu baris = satu jadwal. Data hasil impor menjadi milik akun Anda.',
        ])
    @endsection

    @push('scripts')
        <script
            src="{{ asset('js/teknisi-pemeliharaan.js') }}?v={{ file_exists(public_path('js/teknisi-pemeliharaan.js')) ? filemtime(public_path('js/teknisi-pemeliharaan.js')) : time() }}">
        </script>
        <script
            src="{{ asset('js/teknisi-bulk.js') }}?v={{ file_exists(public_path('js/teknisi-bulk.js')) ? filemtime(public_path('js/teknisi-bulk.js')) : time() }}">
        </script>
        @include('dashboard.teknisi._partials.scripts')
    @endpush
