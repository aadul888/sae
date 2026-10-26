@extends('layouts.dashboard')

@section('title', 'Perawatan & K3 Alat — Laboratorium SAE')
@section('dash_title', 'Perawatan & K3')

@section('content')
    <div class="dash-content-inner">
        {{-- 1. Banner Header --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap; margin-bottom: var(--space-9);">
            <div style="display: flex; align-items: center; gap: var(--space-7);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl); flex-shrink: 0;">
                    <i class="fas fa-screwdriver-wrench"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--text-color); margin: 0;">
                        Perawatan, Pemeliharaan &amp; Penataan Alat
                    </h2>
                    <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-1);">
                        Pengelolaan perawatan alat sesuai standar K3 beserta kartu perawatan berkala.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions" style="display: flex; gap: var(--space-4); align-items: center; flex-wrap: wrap;">
                <span class="badge" style="background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); border: 1px solid rgba(59,130,246,0.25); padding: var(--space-3) var(--space-6); border-radius: var(--radius-md); font-size: var(--text-xs); font-weight: var(--font-bold); display: inline-flex; align-items: center; gap: var(--space-2);">
                    <i class="fas {{ $scopeBadge['icon'] }}"></i> {{ $scopeBadge['label'] }}
                </span>
                @if ($canCreate)
                    <button type="button" class="btn btn-primary" id="btnOpenPerawatan"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md); font-size: var(--text-md);"
                        title="Tambah Catatan Perawatan">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>

        {{-- 2. Statistik --}}
        <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: var(--space-9); gap: var(--space-7);">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);"><i class="fas fa-screwdriver-wrench"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statTotal, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Total Catatan Perawatan</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);"><i class="fas fa-calendar-day"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statDijadwalkan, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Dijadwalkan</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.15); color: var(--purple, #8b5cf6);"><i class="fas fa-spinner"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statProses, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Dalam Proses</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);"><i class="fas fa-circle-check"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statSelesai, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Selesai</div>
                </div>
            </div>
        </div>

        {{-- 3. Navigasi Tab --}}
        <div class="periode-nav-wrapper" style="margin-bottom: var(--space-7);">
            <div class="periode-nav-desktop">
                <a href="{{ route('dashboard.laboran.perawatan.index', array_merge(request()->except(['tab', 'page']), ['tab' => 'perawatan'])) }}"
                    class="periode-nav-tab {{ $tab === 'perawatan' ? 'active' : '' }}">
                    <i class="fas fa-screwdriver-wrench"></i> Catatan Perawatan ({{ $statTotal }})
                </a>
                <a href="{{ route('dashboard.laboran.perawatan.index', array_merge(request()->except(['tab', 'page']), ['tab' => 'kartu'])) }}"
                    class="periode-nav-tab {{ $tab === 'kartu' ? 'active' : '' }}">
                    <i class="fas fa-id-card"></i> Kartu Perawatan ({{ $statKartuAktif }})
                </a>
            </div>
            <div class="periode-nav-mobile">
                <div class="periode-mobile-select-box" style="margin-bottom: 0;">
                    <label><i class="fas fa-sliders text-primary me-1"></i> Pilih Modul:</label>
                    <select class="periode-mobile-select" onchange="if(this.value) window.location.href=this.value;">
                        <option value="{{ route('dashboard.laboran.perawatan.index', array_merge(request()->except(['tab', 'page']), ['tab' => 'perawatan'])) }}" {{ $tab === 'perawatan' ? 'selected' : '' }}>
                            Catatan Perawatan ({{ $statTotal }})
                        </option>
                        <option value="{{ route('dashboard.laboran.perawatan.index', array_merge(request()->except(['tab', 'page']), ['tab' => 'kartu'])) }}" {{ $tab === 'kartu' ? 'selected' : '' }}>
                            Kartu Perawatan ({{ $statKartuAktif }})
                        </option>
                    </select>
                </div>
            </div>
        </div>

        @if ($tab === 'kartu')
            {{-- ================= TAB KARTU PERAWATAN ================= --}}
            <div class="card datatable-card-wrapper" id="tableDataContainer">
                <div class="datatable-toolbar">
                    <form method="GET" action="{{ route('dashboard.laboran.perawatan.index') }}" style="margin: 0;">
                        <input type="hidden" name="tab" value="kartu">
                        <div style="display: flex; flex-wrap: wrap; gap: var(--space-6); justify-content: space-between; align-items: center;">
                            <div style="display: flex; flex-wrap: wrap; gap: var(--space-5); align-items: center;">
                                <div class="toolbar-entries">
                                    <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                                    <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                                        @foreach ([10, 15, 25, 50, 100] as $n)
                                            <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                                        @endforeach
                                    </select>
                                    <span>entri</span>
                                </div>
                                @if ($canCreate)
                                    <button type="button" class="btn btn-outline" id="btnOpenKartu"
                                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                                        title="Buat Kartu Perawatan Baru">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                @endif
                            </div>
                            <div class="live-search-wrap" style="height: 38px;">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" name="q" id="liveSearch" value="{{ $search }}" placeholder="Cari nama alat, kode kartu, atau lokasi..." autocomplete="off">
                                <button type="button" id="clearSearch" class="clear-search {{ $search ? 'visible' : '' }}" title="Hapus pencarian"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255, 255, 255, 0.02);">
                                <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Kode Kartu</th>
                                <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Alat</th>
                                <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Interval</th>
                                <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Jadwal Berikutnya</th>
                                <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Status</th>
                                <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($list as $k)
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td data-label="Kode" style="padding: var(--space-6) var(--space-7);">
                                        <div style="font-family: var(--font-family-mono); font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--primary);">{{ $k->kode_kartu }}</div>
                                        @if ($k->singkatan || $k->nama_jurusan)
                                            <span class="badge badge-outline" style="font-size: var(--text-2xs); margin-top: var(--space-2);">{{ $k->singkatan ?: $k->nama_jurusan }}</span>
                                        @endif
                                    </td>
                                    <td data-label="Alat" style="padding: var(--space-6) var(--space-7);">
                                        <div style="font-weight: var(--font-bold); color: var(--text-color); font-size: var(--text-base);">{{ $k->nama_alat }}</div>
                                        <div style="font-size: var(--text-xs); color: var(--text-muted);">
                                            {{ $k->kode_alat ?: '—' }}{{ $k->ruang_lab_nama ? ' • ' . $k->ruang_lab_nama : '' }}
                                        </div>
                                        @if ($k->instruksi_perawatan)
                                            <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-2);">
                                                <i class="fas fa-info-circle me-1"></i>{{ \Illuminate\Support\Str::limit($k->instruksi_perawatan, 60) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td data-label="Interval" style="padding: var(--space-6) var(--space-7); text-align: center; font-size: var(--text-sm);">
                                        {{ $k->interval_hari }} hari
                                    </td>
                                    <td data-label="Jadwal" style="padding: var(--space-6) var(--space-7); text-align: center; font-size: var(--text-sm);">
                                        @if ($k->jadwal_berikutnya)
                                            @php
                                                $jd = \Carbon\Carbon::parse($k->jadwal_berikutnya);
                                                $lewat = $jd->isPast();
                                                $dekat = !$lewat && $jd->diffInDays(now()) <= 7;
                                            @endphp
                                            <span style="font-weight: var(--font-bold); color: {{ $lewat ? 'var(--danger, #ef4444)' : ($dekat ? 'var(--warning, #f59e0b)' : 'var(--text-color)') }};">
                                                {{ $jd->translatedFormat('d M Y') }}
                                            </span>
                                            @if ($lewat)
                                                <div style="font-size: var(--text-2xs); color: var(--danger, #ef4444);">Lewat {{ $jd->diffInDays(now()) }} hari</div>
                                            @elseif ($dekat)
                                                <div style="font-size: var(--text-2xs); color: var(--warning, #f59e0b);">{{ $jd->diffInDays(now()) }} hari lagi</div>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td data-label="Status" style="padding: var(--space-6) var(--space-7); text-align: center;">
                                        <span class="badge {{ $k->status === 'aktif' ? 'badge-success' : 'badge-outline' }}" style="font-size: var(--text-2xs);">
                                            {{ $k->status === 'aktif' ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td data-label="Aksi" style="padding: var(--space-6) var(--space-7); text-align: right;">
                                        <div class="table-actions" style="justify-content: flex-end; gap: var(--space-3);">
                                            @if ($canUpdate)
                                                <button type="button" class="btn-icon btn-selesai-kartu" data-id="{{ $k->id }}"
                                                    data-nama="{{ $k->nama_alat }}" title="Tandai Sudah Dirawat">
                                                    <i class="fas fa-check" style="color: var(--success, #10b981);"></i>
                                                </button>
                                                <button type="button" class="btn-icon btn-edit-kartu"
                                                    data-id="{{ $k->id }}" data-nama="{{ $k->nama_alat }}"
                                                    data-kode="{{ $k->kode_alat }}" data-lokasi="{{ $k->ruang_lab_nama }}"
                                                    data-merk="{{ $k->merk_tipe }}" data-mulai="{{ $k->tanggal_mulai }}"
                                                    data-interval="{{ $k->interval_hari }}"
                                                    data-pj="{{ $k->penanggung_jawab_nama }}"
                                                    data-instruksi="{{ $k->instruksi_perawatan }}"
                                                    data-status="{{ $k->status }}" data-catatan="{{ $k->catatan }}"
                                                    data-item="{{ $k->item_id }}"
                                                    title="Ubah Kartu Perawatan">
                                                    <i class="fas fa-pen-to-square" style="color: var(--primary);"></i>
                                                </button>
                                            @endif
                                            @if ($canDelete)
                                                <button type="button" class="btn-icon btn-delete-kartu"
                                                    data-id="{{ $k->id }}" data-nama="{{ $k->nama_alat }}"
                                                    title="Hapus Kartu" style="color: var(--danger, #ef4444);">
                                                    <i class="fas fa-trash-can"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="padding: var(--space-11); text-align: center; color: var(--text-muted);">
                                        <i class="fas fa-id-card mb-2" style="font-size: var(--text-3xl); opacity: 0.5;"></i>
                                        <div>Belum ada kartu perawatan. Buat kartu untuk memantau jadwal perawatan berkala.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($list->hasPages())
                    <div class="datatable-footer">
                        @include('partials.datatable-pagination', ['paginator' => $list])
                    </div>
                @endif
            </div>
        @else
            {{-- ================= TAB CATATAN PERAWATAN ================= --}}
            <div class="card datatable-card-wrapper" id="tableDataContainer">
                <div class="datatable-toolbar">
                    <form method="GET" action="{{ route('dashboard.laboran.perawatan.index') }}" style="margin: 0;">
                        <input type="hidden" name="tab" value="perawatan">
                        <div style="display: flex; flex-wrap: wrap; gap: var(--space-6); justify-content: space-between; align-items: center;">
                            <div style="display: flex; flex-wrap: wrap; gap: var(--space-5); align-items: center;">
                                <div class="toolbar-entries">
                                    <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                                    <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                                        @foreach ([10, 15, 25, 50, 100] as $n)
                                            <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                                        @endforeach
                                    </select>
                                    <span>entri</span>
                                </div>

                                <select name="jenis" id="filterJenis" class="toolbar-filter-select" style="height: 38px;" title="Filter Jenis Perawatan">
                                    <option value="">Semua Jenis</option>
                                    @foreach ($jenisList as $k => $v)
                                        <option value="{{ $k }}" {{ $jenisFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
                                    @endforeach
                                </select>

                                <select name="k3" id="filterK3" class="toolbar-filter-select" style="height: 38px;" title="Filter Kategori K3">
                                    <option value="">Semua K3</option>
                                    @foreach ($k3List as $k => $v)
                                        <option value="{{ $k }}" {{ $k3Filter === $k ? 'selected' : '' }}>{{ $v }}</option>
                                    @endforeach
                                </select>

                                <select name="status" id="filterStatus" class="toolbar-filter-select" style="height: 38px;" title="Filter Status">
                                    <option value="">Semua Status</option>
                                    @foreach ($statusList as $k => $v)
                                        <option value="{{ $k }}" {{ $statusFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
                                    @endforeach
                                </select>

                                @if ($search || $jenisFilter || $k3Filter || $statusFilter)
                                    <a href="{{ route('dashboard.laboran.perawatan.index', ['tab' => 'perawatan']) }}" class="btn btn-outline"
                                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                                        title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                                @endif
                            </div>

                            <div class="live-search-wrap" style="height: 38px;">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" name="q" id="liveSearch" value="{{ $search }}" placeholder="Cari alat, kode, pekerjaan, atau pelaksana..." autocomplete="off">
                                <button type="button" id="clearSearch" class="clear-search {{ $search ? 'visible' : '' }}" title="Hapus pencarian"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255, 255, 255, 0.02);">
                                <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Kode / Tanggal</th>
                                <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Alat &amp; Pekerjaan</th>
                                <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Jenis</th>
                                <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">K3</th>
                                <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Status</th>
                                <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($list as $p)
                                @php $apd = json_decode($p->apd_digunakan ?? '[]', true) ?: []; @endphp
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td data-label="Kode" style="padding: var(--space-6) var(--space-7);">
                                        <div style="font-family: var(--font-family-mono); font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--primary);">{{ $p->kode_perawatan }}</div>
                                        <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ \Carbon\Carbon::parse($p->tanggal_perawatan)->translatedFormat('d M Y') }}</div>
                                        @if ($p->periode_berikutnya)
                                            <div style="font-size: var(--text-2xs); color: var(--purple, #8b5cf6);">Berikut: {{ \Carbon\Carbon::parse($p->periode_berikutnya)->format('d/m/Y') }}</div>
                                        @endif
                                    </td>
                                    <td data-label="Alat" style="padding: var(--space-6) var(--space-7);">
                                        <div style="font-weight: var(--font-bold); color: var(--text-color); font-size: var(--text-base);">{{ $p->nama_alat ?: '—' }}</div>
                                        @if ($p->uraian_pekerjaan)
                                            <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ \Illuminate\Support\Str::limit($p->uraian_pekerjaan, 80) }}</div>
                                        @endif
                                        <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-2);">
                                            <i class="fas fa-user me-1"></i>{{ $p->pelaksana_nama ?: '—' }}
                                            @if ($p->ruang_lab_nama) <span> • {{ $p->ruang_lab_nama }}</span>@endif
                                        </div>
                                        @if (!empty($apd))
                                            <div style="display: flex; flex-wrap: wrap; gap: var(--space-2); margin-top: var(--space-2);">
                                                @foreach (array_slice($apd, 0, 4) as $a)
                                                    <span class="badge badge-outline" style="font-size: var(--text-2xs);"><i class="fas fa-shield-halved me-1"></i>{{ $a }}</span>
                                                @endforeach
                                                @if (count($apd) > 4)
                                                    <span class="badge badge-outline" style="font-size: var(--text-2xs);">+{{ count($apd) - 4 }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td data-label="Jenis" style="padding: var(--space-6) var(--space-7); text-align: center;">
                                        <span class="badge badge-outline" style="font-size: var(--text-2xs);">{{ $jenisList[$p->jenis_perawatan] ?? $p->jenis_perawatan }}</span>
                                    </td>
                                    <td data-label="K3" style="padding: var(--space-6) var(--space-7); text-align: center;">
                                        <span class="badge badge-outline" style="font-size: var(--text-2xs);">{{ $k3List[$p->kategori_k3] ?? ($p->kategori_k3 ?: '—') }}</span>
                                    </td>
                                    <td data-label="Status" style="padding: var(--space-6) var(--space-7); text-align: center;">
                                        @php
                                            $sc = match ($p->status) {
                                                'selesai' => 'badge-success',
                                                'proses' => 'badge-warning',
                                                'ditunda' => 'badge-danger',
                                                default => 'badge-info',
                                            };
                                        @endphp
                                        <span class="badge {{ $sc }}" style="font-size: var(--text-2xs);">{{ $statusList[$p->status] ?? $p->status }}</span>
                                        @if ($p->kondisi_akhir)
                                            <div style="font-size: var(--text-2xs); color: var(--text-muted); margin-top: var(--space-2);">{{ $kondisiList[$p->kondisi_akhir] ?? $p->kondisi_akhir }}</div>
                                        @endif
                                    </td>
                                    <td data-label="Aksi" style="padding: var(--space-6) var(--space-7); text-align: right;">
                                        <div class="table-actions" style="justify-content: flex-end; gap: var(--space-3);">
                                            @if ($canUpdate)
                                                <button type="button" class="btn-icon btn-edit-perawatan"
                                                    data-id="{{ $p->id }}" data-item="{{ $p->item_id }}"
                                                    data-nama="{{ $p->nama_alat }}" data-kode="{{ $p->kode_alat }}"
                                                    data-lokasi="{{ $p->ruang_lab_nama }}"
                                                    data-jenis="{{ $p->jenis_perawatan }}" data-k3="{{ $p->kategori_k3 }}"
                                                    data-tanggal="{{ $p->tanggal_perawatan }}"
                                                    data-berikut="{{ $p->periode_berikutnya }}"
                                                    data-pelaksana="{{ $p->pelaksana_nama }}"
                                                    data-uraian="{{ $p->uraian_pekerjaan }}"
                                                    data-prosedur="{{ $p->prosedur_k3 }}"
                                                    data-apd="{{ $p->apd_digunakan }}"
                                                    data-hasil="{{ $p->hasil }}" data-kondisi="{{ $p->kondisi_akhir }}"
                                                    data-biaya="{{ $p->biaya }}" data-status="{{ $p->status }}"
                                                    data-catatan="{{ $p->catatan }}"
                                                    title="Ubah Catatan Perawatan">
                                                    <i class="fas fa-pen-to-square" style="color: var(--primary);"></i>
                                                </button>
                                            @endif
                                            @if ($canDelete)
                                                <button type="button" class="btn-icon btn-delete-perawatan"
                                                    data-id="{{ $p->id }}" data-nama="{{ $p->nama_alat ?: $p->kode_perawatan }}"
                                                    title="Hapus Catatan" style="color: var(--danger, #ef4444);">
                                                    <i class="fas fa-trash-can"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="padding: var(--space-11); text-align: center; color: var(--text-muted);">
                                        <i class="fas fa-screwdriver-wrench mb-2" style="font-size: var(--text-3xl); opacity: 0.5;"></i>
                                        <div>Belum ada catatan perawatan alat.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($list->hasPages())
                    <div class="datatable-footer">
                        @include('partials.datatable-pagination', ['paginator' => $list])
                    </div>
                @endif
            </div>
        @endif

        {{-- ================= MODAL PERAWATAN ================= --}}
        <div id="modalPerawatan" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 760px; max-height: 92vh; overflow-y: auto; padding: var(--space-10); border-radius: var(--radius-lg);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-9);">
                    <h3 id="modalPerawatanTitle" style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-color);">Catatan Perawatan Alat</h3>
                    <button type="button" id="btnClosePerawatan" style="background: none; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;" title="Tutup">&times;</button>
                </div>

                <form id="formPerawatan" method="POST" action="{{ route('dashboard.laboran.perawatan.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="perawatanMethod" value="POST">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Pilih Alat dari Inventaris</label>
                            <select name="item_id" id="prwItem" class="form-control">
                                <option value="">-- Atau isi nama alat manual --</option>
                                @foreach ($itemOptions as $io)
                                    <option value="{{ $io->id }}">{{ $io->nama_item }} ({{ $io->kode_item }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Nama Alat</label>
                            <input type="text" name="nama_alat" id="prwNama" class="form-control" placeholder="Nama alat jika tidak dipilih dari inventaris">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Jenis Perawatan <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <select name="jenis_perawatan" id="prwJenis" class="form-control" required>
                                @foreach ($jenisList as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Kategori Risiko K3</label>
                            <select name="kategori_k3" id="prwK3" class="form-control">
                                @foreach ($k3List as $k => $v)
                                    <option value="{{ $k }}" {{ $k === 'umum' ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Tanggal Perawatan <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="date" name="tanggal_perawatan" id="prwTanggal" class="form-control" required>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Periode Berikutnya</label>
                            <input type="date" name="periode_berikutnya" id="prwBerikut" class="form-control">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Lokasi</label>
                            <select name="ruang_lab_nama" id="prwLokasi" class="form-control">
                                <option value="">-- Pilih --</option>
                                @foreach ($lokasiOptions as $lok)
                                    <option value="{{ $lok }}">{{ $lok }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Pelaksana</label>
                            <input type="text" name="pelaksana_nama" id="prwPelaksana" class="form-control" placeholder="Nama petugas pelaksana">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Status</label>
                            <select name="status" id="prwStatus" class="form-control">
                                @foreach ($statusList as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Uraian Pekerjaan</label>
                        <textarea name="uraian_pekerjaan" id="prwUraian" class="form-control" rows="2" placeholder="Contoh: Pembersihan lensa, pengecekan kalibrasi, pelumasan bagian mekanik..."></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Prosedur K3 yang Diterapkan</label>
                        <textarea name="prosedur_k3" id="prwProsedur" class="form-control" rows="2" placeholder="Contoh: Alat dimatikan & dicabut dari listrik sebelum dibersihkan, area kerja diberi tanda..."></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            <i class="fas fa-shield-halved me-1 text-primary"></i> APD yang Digunakan
                        </label>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: var(--space-3); padding: var(--space-5); border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                            @foreach ($apdList as $i => $apd)
                                <label style="display: flex; align-items: center; gap: var(--space-3); font-size: var(--text-sm); color: var(--text-color); cursor: pointer; margin: 0;">
                                    <input type="checkbox" name="apd_digunakan[]" value="{{ $apd }}" class="prw-apd" style="cursor: pointer;">
                                    {{ $apd }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Kondisi Akhir</label>
                            <select name="kondisi_akhir" id="prwKondisi" class="form-control">
                                <option value="">-- Pilih --</option>
                                @foreach ($kondisiList as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Biaya (Rp)</label>
                            <input type="number" step="0.01" min="0" name="biaya" id="prwBiaya" class="form-control" placeholder="0">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Kode Alat</label>
                            <input type="text" name="kode_alat" id="prwKode" class="form-control" placeholder="Otomatis dari inventaris">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Hasil Perawatan</label>
                        <textarea name="hasil" id="prwHasil" class="form-control" rows="2" placeholder="Hasil pengecekan / perbaikan yang dilakukan..."></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-9);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Catatan</label>
                        <textarea name="catatan" id="prwCatatan" class="form-control" rows="2" placeholder="Catatan tambahan..."></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: var(--space-4);">
                        <button type="button" class="btn btn-secondary" id="btnCancelPerawatan">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ================= MODAL KARTU PERAWATAN ================= --}}
        <div id="modalKartu" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 660px; max-height: 92vh; overflow-y: auto; padding: var(--space-10); border-radius: var(--radius-lg);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-9);">
                    <h3 id="modalKartuTitle" style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-color);">Kartu Perawatan</h3>
                    <button type="button" id="btnCloseKartu" style="background: none; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;" title="Tutup">&times;</button>
                </div>

                <form id="formKartu" method="POST" action="{{ route('dashboard.laboran.perawatan.kartu.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="kartuMethod" value="POST">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Alat dari Inventaris</label>
                            <select name="item_id" id="krtItem" class="form-control">
                                <option value="">-- Atau isi manual --</option>
                                @foreach ($itemOptions as $io)
                                    <option value="{{ $io->id }}">{{ $io->nama_item }} ({{ $io->kode_item }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Nama Alat <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="text" name="nama_alat" id="krtNama" class="form-control" placeholder="Nama alat">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Merk / Tipe</label>
                            <input type="text" name="merk_tipe" id="krtMerk" class="form-control" placeholder="Contoh: Olympus CX23">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Kode Alat</label>
                            <input type="text" name="kode_alat" id="krtKode" class="form-control" placeholder="Otomatis dari inventaris">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Lokasi</label>
                            <select name="ruang_lab_nama" id="krtLokasi" class="form-control">
                                <option value="">-- Pilih --</option>
                                @foreach ($lokasiOptions as $lok)
                                    <option value="{{ $lok }}">{{ $lok }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Penanggung Jawab</label>
                            <input type="text" name="penanggung_jawab_nama" id="krtPj" class="form-control" placeholder="Nama penanggung jawab">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" id="krtMulai" class="form-control">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Interval (hari) <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="number" min="1" max="3650" name="interval_hari" id="krtInterval" class="form-control" value="30" required>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Status</label>
                            <select name="status" id="krtStatus" class="form-control">
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Instruksi Perawatan</label>
                        <textarea name="instruksi_perawatan" id="krtInstruksi" class="form-control" rows="3" placeholder="Langkah-langkah perawatan rutin yang harus dilakukan..."></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            <i class="fas fa-shield-halved me-1 text-primary"></i> APD Wajib
                        </label>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: var(--space-3); padding: var(--space-5); border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                            @foreach ($apdList as $apd)
                                <label style="display: flex; align-items: center; gap: var(--space-3); font-size: var(--text-sm); color: var(--text-color); cursor: pointer; margin: 0;">
                                    <input type="checkbox" name="apd_wajib[]" value="{{ $apd }}" class="krt-apd" style="cursor: pointer;">
                                    {{ $apd }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-9);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Catatan</label>
                        <textarea name="catatan" id="krtCatatan" class="form-control" rows="2"></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: var(--space-4);">
                        <button type="button" class="btn btn-secondary" id="btnCancelKartu">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan Kartu</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

        @push('scripts')
            <script src="{{ asset('js/laboran-common.js') }}?v={{ file_exists(public_path('js/laboran-common.js')) ? filemtime(public_path('js/laboran-common.js')) : time() }}"></script>
            <script src="{{ asset('js/laboran-perawatan.js') }}?v={{ file_exists(public_path('js/laboran-perawatan.js')) ? filemtime(public_path('js/laboran-perawatan.js')) : time() }}"></script>
        @endpush
    @endsection
