@extends('layouts.dashboard')

@section('title', 'Patroli & Insiden - Keamanan')

@section('content')
    <div class="content-wrapper">
        <!-- 1. Header Banner & Actions -->
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg, 12px); background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); flex-shrink: 0;">
                    <i class="fas fa-shield-virus"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                        Log Patroli & Laporan Insiden
                    </h2>
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        Pencatatan rute patroli keamanan lingkungan sekolah dan pelaporan insiden/kejadian khusus.
                    </div>
                </div>
            </div>

            <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                @if ($canCreate)
                    @if ($tab === 'patroli')
                        <button type="button" class="btn btn-primary" onclick="openModalPatroli()"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                            title="Tambah Log Patroli">
                            <i class="fas fa-plus"></i>
                        </button>
                    @else
                        <button type="button" class="btn btn-danger" onclick="openModalInsiden()"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                            title="Laporkan Insiden Khusus Keamanan">
                            <i class="fas fa-exclamation-triangle"></i>
                        </button>
                    @endif
                @endif
            </div>
        </div>

        <!-- 2. Stat Grid Baku SAE -->
        <div class="dash-stat-grid" style="margin-bottom: 20px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6);">
                    <i class="fas fa-walking"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['total_patroli'] }}</div>
                    <div class="dash-stat-label">Total Patroli</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6);">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['patroli_hari_ini'] }}</div>
                    <div class="dash-stat-label">Patroli Hari Ini</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239,68,68,0.12); color: var(--danger, #ef4444);">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['total_insiden'] }}</div>
                    <div class="dash-stat-label">Total Insiden Dicatat</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b);">
                    <i class="fas fa-fire-flame-curved"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['insiden_aktif'] }}</div>
                    <div class="dash-stat-label">Insiden Aktif</div>
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        <div style="display: flex; gap: 8px; border-bottom: 2px solid var(--border-color); margin-bottom: 20px;">
            <a href="{{ route('dashboard.keamanan.patroli.index', ['tab' => 'patroli']) }}"
                style="padding: 10px 18px; font-weight: 600; font-size: var(--text-base, 0.875rem); text-decoration: none; border-bottom: 2px solid {{ $tab === 'patroli' ? 'var(--primary)' : 'transparent' }}; color: {{ $tab === 'patroli' ? 'var(--primary)' : 'var(--text-muted)' }}; margin-bottom: -2px;">
                <i class="fas fa-walking"></i> Log Patroli Keliling
            </a>
            <a href="{{ route('dashboard.keamanan.patroli.index', ['tab' => 'insiden']) }}"
                style="padding: 10px 18px; font-weight: 600; font-size: var(--text-base, 0.875rem); text-decoration: none; border-bottom: 2px solid {{ $tab === 'insiden' ? 'var(--primary)' : 'transparent' }}; color: {{ $tab === 'insiden' ? 'var(--primary)' : 'var(--text-muted)' }}; margin-bottom: -2px;">
                <i class="fas fa-exclamation-circle"></i> Laporan Insiden & Kejadian
            </a>
        </div>

        @if ($tab === 'patroli')
            {{-- TAB 1: PATROLI --}}
            <div class="card datatable-card-wrapper" id="tableDataContainer">
                {{-- 1. Toolbar Header (Entries, Filters & Live Search) --}}
                <div class="datatable-toolbar">
                    <form method="GET" action="{{ route('dashboard.keamanan.patroli.index') }}" id="filterForm"
                        style="margin: 0;">
                        <input type="hidden" name="tab" value="patroli">
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

                                <input type="date" name="tanggal" value="{{ $tanggal }}" class="form-control"
                                    style="height: 38px; width: auto; min-width: 140px; padding: 4px 10px;">

                                @if ($search || $tanggal)
                                    <a href="{{ route('dashboard.keamanan.patroli.index', ['tab' => 'patroli']) }}"
                                        class="btn btn-outline btn-reset-filter"
                                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                        title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                                @endif
                            </div>

                            <div class="live-search-wrap" style="height: 38px;">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" name="search" id="searchPatroli" value="{{ $search }}"
                                    placeholder="Cari rute, temuan, petugas..." autocomplete="off">
                                <button type="button" id="clearSearch"
                                    class="clear-search {{ !empty($search) ? 'visible' : '' }}" title="Hapus pencarian">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- 2. Konten Datatable Responsive Stack --}}
                <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr>
                                <th
                                    style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Waktu Patroli</th>
                                <th
                                    style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Rute / Zona</th>
                                <th
                                    style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Kondisi Lingkungan</th>
                                <th
                                    style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Catatan Temuan</th>
                                <th
                                    style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Petugas</th>
                                <th
                                    style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($patroliList as $item)
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 12px 18px;">
                                        <div style="font-weight: 600; color: var(--text-heading);">
                                            {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}</div>
                                        <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">Pukul
                                            {{ substr($item->jam_patroli, 0, 5) }} WIB</div>
                                    </td>
                                    <td style="padding: 12px 18px;">
                                        <span
                                            style="font-weight: 600; color: var(--text-heading);">{{ $item->rute_zona }}</span>
                                    </td>
                                    <td style="padding: 12px 18px;">
                                        @if ($item->kondisi_lingkungan === 'aman_terkendali')
                                            <span class="badge"
                                                style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981); border: 1px solid rgba(16, 185, 129, 0.3);">Aman
                                                & Terkendali</span>
                                        @elseif ($item->kondisi_lingkungan === 'mencurigakan')
                                            <span class="badge"
                                                style="background: rgba(239, 68, 68, 0.15); color: var(--danger, #ef4444); border: 1px solid rgba(239, 68, 68, 0.3);">Mencurigakan</span>
                                        @else
                                            <span class="badge"
                                                style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b); border: 1px solid rgba(245, 158, 11, 0.3);">{{ ucwords(str_replace('_', ' ', $item->kondisi_lingkungan)) }}</span>
                                        @endif
                                    </td>
                                    <td
                                        style="padding: 12px 18px; font-size: var(--text-base, 0.875rem); color: var(--text-muted); max-width: 250px;">
                                        {{ $item->catatan_temuan ?: '-' }}
                                    </td>
                                    <td style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); color: var(--text-heading);">
                                        {{ $item->nama_petugas ?: 'Satpam' }}
                                    </td>
                                    <td style="padding: 12px 18px; text-align: center;">
                                        <div class="table-actions"
                                            style="display: flex; gap: 6px; justify-content: center;">
                                            @if ($canUpdate)
                                                <button type="button" class="btn-icon" title="Edit"
                                                    onclick="editPatroli({{ json_encode($item) }})">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            @endif
                                            @if ($canDelete)
                                                <form
                                                    action="{{ route('dashboard.keamanan.patroli.destroy', $item->id) }}"
                                                    method="POST" data-confirm="delete"
                                                    data-name="Patroli {{ $item->rute_zona }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-icon btn-icon-danger"
                                                        title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6"
                                        style="text-align: center; padding: 36px; color: var(--text-muted);">
                                        <i class="fas fa-walking"
                                            style="font-size: var(--text-3xl, 1.875rem); margin-bottom: 8px; opacity: 0.4;"></i>
                                        <p style="margin: 0;">Belum ada log patroli.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- 3. Footer Paginasi Terpadu Datatable Tab Patroli --}}
                @if ($patroliList->hasPages())
                    <div class="datatable-footer">
                        @include('partials.datatable-pagination', ['paginator' => $patroliList])
                    </div>
                @endif
            </div>
        @else
            {{-- TAB 2: INSIDEN --}}
            <div class="card datatable-card-wrapper" id="tableDataContainer">
                {{-- 1. Toolbar Header (Entries, Filters & Live Search) --}}
                <div class="datatable-toolbar">
                    <form method="GET" action="{{ route('dashboard.keamanan.patroli.index') }}" id="filterForm"
                        style="margin: 0;">
                        <input type="hidden" name="tab" value="insiden">
                        <div
                            style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                            <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                                <div class="toolbar-entries">
                                    <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                                    <select id="perPageSelect" name="perPage" class="per-page-select"
                                        style="height: 38px;">
                                        @foreach ([10, 15, 25, 50, 100] as $n)
                                            <option value="{{ $n }}"
                                                {{ request('perPage', 15) == $n ? 'selected' : '' }}>
                                                {{ $n }}</option>
                                        @endforeach
                                    </select>
                                    <span>entri</span>
                                </div>

                                <input type="date" name="tanggal" value="{{ $tanggal }}" class="form-control"
                                    style="height: 38px; width: auto; min-width: 140px; padding: 4px 10px;">

                                @if ($search || $tanggal)
                                    <a href="{{ route('dashboard.keamanan.patroli.index', ['tab' => 'insiden']) }}"
                                        class="btn btn-outline btn-reset-filter"
                                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                        title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                                @endif
                            </div>

                            <div class="live-search-wrap" style="height: 38px;">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" name="search" id="searchInsiden" value="{{ $search }}"
                                    placeholder="Cari nomor laporan, insiden, lokasi..." autocomplete="off">
                                <button type="button" id="clearSearch"
                                    class="clear-search {{ !empty($search) ? 'visible' : '' }}" title="Hapus pencarian">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- 2. Konten Datatable Responsive Stack --}}
                <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr>
                                <th
                                    style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    No Laporan & Waktu</th>
                                <th
                                    style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Judul Insiden & Lokasi</th>
                                <th
                                    style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Urgensi</th>
                                <th
                                    style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Kronologi / Tindakan</th>
                                <th
                                    style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Status Penyelesaian</th>
                                <th
                                    style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($insidenList as $ins)
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 12px 18px;">
                                        <div
                                            style="font-weight: 700; font-family: monospace; color: var(--primary); font-size: var(--text-base, 0.875rem);">
                                            {{ $ins->nomor_laporan }}</div>
                                        <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                            {{ \Carbon\Carbon::parse($ins->tanggal)->translatedFormat('d M Y') }} &bull;
                                            {{ substr($ins->jam_kejadian, 0, 5) }}
                                        </div>
                                    </td>
                                    <td style="padding: 12px 18px;">
                                        <div style="font-weight: 600; color: var(--text-heading);">
                                            {{ $ins->judul_insiden }}
                                        </div>
                                        <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);"><i
                                                class="fas fa-map-marker-alt"></i> {{ $ins->lokasi_kejadian }}</div>
                                    </td>
                                    <td style="padding: 12px 18px;">
                                        @if ($ins->tingkat_urgensi === 'darurat')
                                            <span class="badge"
                                                style="background: rgba(239, 68, 68, 0.2); color: var(--danger, #ef4444); border: 1px solid rgba(239, 68, 68, 0.4);">Darurat</span>
                                        @elseif ($ins->tingkat_urgensi === 'tinggi')
                                            <span class="badge"
                                                style="background: rgba(249, 115, 22, 0.2); color: #f97316; border: 1px solid rgba(249, 115, 22, 0.4);">Tinggi</span>
                                        @elseif ($ins->tingkat_urgensi === 'sedang')
                                            <span class="badge"
                                                style="background: rgba(245, 158, 11, 0.2); color: var(--warning, #f59e0b); border: 1px solid rgba(245, 158, 11, 0.4);">Sedang</span>
                                        @else
                                            <span class="badge"
                                                style="background: rgba(107, 114, 128, 0.2); color: #6b7280;">Rendah</span>
                                        @endif
                                    </td>
                                    <td style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); max-width: 250px;">
                                        <div style="color: var(--text-heading); white-space: normal;">
                                            {{ Str::limit($ins->kronologi, 80) }}</div>
                                        @if ($ins->tindakan_diambil)
                                            <div style="color: var(--text-muted); margin-top: 2px;"><em>Tindakan:
                                                    {{ Str::limit($ins->tindakan_diambil, 60) }}</em></div>
                                        @endif
                                    </td>
                                    <td style="padding: 12px 18px;">
                                        @if ($ins->status_penyelesaian === 'selesai')
                                            <span class="badge"
                                                style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981); border: 1px solid rgba(16, 185, 129, 0.3);">Selesai</span>
                                        @else
                                            <span class="badge"
                                                style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b); border: 1px solid rgba(245, 158, 11, 0.3);">Dalam
                                                Penanganan</span>
                                        @endif
                                    </td>
                                    <td style="padding: 12px 18px; text-align: center;">
                                        <div class="table-actions"
                                            style="display: flex; gap: 6px; justify-content: center;">
                                            @if ($canUpdate)
                                                <button type="button" class="btn-icon" title="Edit"
                                                    onclick="editInsiden({{ json_encode($ins) }})">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            @endif
                                            @if ($canDelete)
                                                <form action="{{ route('dashboard.keamanan.insiden.destroy', $ins->id) }}"
                                                    method="POST" data-confirm="delete"
                                                    data-name="Insiden {{ $ins->nomor_laporan }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-icon btn-icon-danger"
                                                        title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6"
                                        style="text-align: center; padding: 36px; color: var(--text-muted);">
                                        <i class="fas fa-check-circle"
                                            style="font-size: var(--text-3xl, 1.875rem); margin-bottom: 8px; color: var(--success, #10b981); opacity: 0.6;"></i>
                                        <p style="margin: 0;">Tidak ada laporan insiden.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- 3. Footer Paginasi Terpadu Datatable Tab Insiden --}}
                @if ($insidenList->hasPages())
                    <div class="datatable-footer">
                        @include('partials.datatable-pagination', ['paginator' => $insidenList])
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Modal Log Patroli --}}
    <div id="modalPatroli"
        style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center;">
        <div class="card" style="width: 100%; max-width: 550px; margin: 20px; max-height: 90vh; overflow-y: auto;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
                <h3 id="modalPatroliTitle"
                    style="font-size: var(--text-xl, 1.25rem); font-weight: 700; margin: 0; color: var(--text-heading);">Tambah Log Patroli
                </h3>
                <button type="button" onclick="closeModalPatroli()"
                    style="background: none; border: none; font-size: var(--text-xl, 1.25rem); cursor: pointer; color: var(--text-muted);">&times;</button>
            </div>

            <form id="formPatroli" method="POST" action="{{ route('dashboard.keamanan.patroli.store') }}"
                data-store-url="{{ route('dashboard.keamanan.patroli.store') }}"
                data-update-url="{{ url('/dashboard/keamanan/patroli') }}/:id">
                @csrf
                <input type="hidden" name="_method" id="patroliMethod" value="POST">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div>
                        <label class="form-label">Tanggal <span style="color:red;">*</span></label>
                        <input type="date" name="tanggal" id="patroli_tanggal" class="form-control"
                            value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div>
                        <label class="form-label">Jam Patroli <span style="color:red;">*</span></label>
                        <input type="time" name="jam_patroli" id="patroli_jam" class="form-control"
                            value="{{ date('H:i') }}" required>
                    </div>
                </div>

                <div style="margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <label class="form-label" style="margin: 0;">Rute / Zona Patroli <span
                                style="color:red;">*</span></label>
                        <span style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);"><i
                                class="fas fa-building me-1"></i>Master Ruang & Sarpras</span>
                    </div>
                    <input list="listRutePatroli" name="rute_zona" id="patroli_rute" class="form-control"
                        placeholder="Pilih dari daftar Ruang Sarpras atau ketik rute..." required>
                    <datalist id="listRutePatroli">
                        @foreach ($daftarRuang as $rng)
                            <option value="{{ $rng->nama_ruang }} ({{ $rng->gedung }})">{{ $rng->nama_ruang }} - Lantai
                                {{ $rng->lantai }}</option>
                        @endforeach
                    </datalist>
                </div>

                <div style="margin-bottom: 12px;">
                    <label class="form-label">Kondisi Lingkungan <span style="color:red;">*</span></label>
                    <select name="kondisi_lingkungan" id="patroli_kondisi" class="form-control" required>
                        <option value="aman_terkendali">Aman & Terkendali</option>
                        <option value="pintu_terbuka">Pintu / Jendela Ditemukan Terbuka</option>
                        <option value="lampu_mati">Lampu Padam / Rusak</option>
                        <option value="mencurigakan">Aktivitas Mencurigakan</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="form-label">Catatan Temuan</label>
                    <textarea name="catatan_temuan" id="patroli_catatan" class="form-control" rows="3"
                        placeholder="Catatan detail temuan saat patroli..."></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModalPatroli()">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Laporan Insiden --}}
    <div id="modalInsiden"
        style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center;">
        <div class="card" style="width: 100%; max-width: 650px; margin: 20px; max-height: 90vh; overflow-y: auto;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
                <h3 id="modalInsidenTitle"
                    style="font-size: var(--text-xl, 1.25rem); font-weight: 700; margin: 0; color: var(--text-heading);">Laporkan Insiden
                    Keamanan</h3>
                <button type="button" onclick="closeModalInsiden()"
                    style="background: none; border: none; font-size: var(--text-xl, 1.25rem); cursor: pointer; color: var(--text-muted);">&times;</button>
            </div>

            <form id="formInsiden" method="POST" action="{{ route('dashboard.keamanan.insiden.store') }}"
                data-store-url="{{ route('dashboard.keamanan.insiden.store') }}"
                data-update-url="{{ url('/dashboard/keamanan/insiden') }}/:id">
                @csrf
                <input type="hidden" name="_method" id="insidenMethod" value="POST">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div>
                        <label class="form-label">Tanggal Kejadian <span style="color:red;">*</span></label>
                        <input type="date" name="tanggal" id="insiden_tanggal" class="form-control"
                            value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div>
                        <label class="form-label">Jam Kejadian <span style="color:red;">*</span></label>
                        <input type="time" name="jam_kejadian" id="insiden_jam" class="form-control"
                            value="{{ date('H:i') }}" required>
                    </div>
                </div>

                <div style="margin-bottom: 12px;">
                    <label class="form-label">Judul Insiden <span style="color:red;">*</span></label>
                    <input type="text" name="judul_insiden" id="insiden_judul" class="form-control"
                        placeholder="Contoh: Kehilangan helm di parkir barat" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div>
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <label class="form-label" style="margin: 0;">Lokasi Kejadian <span
                                    style="color:red;">*</span></label>
                            <span style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);"><i
                                    class="fas fa-building me-1"></i>Sarpras</span>
                        </div>
                        <input list="listLokasiInsiden" name="lokasi_kejadian" id="insiden_lokasi" class="form-control"
                            placeholder="Pilih Ruang/Lokasi atau ketik..." required>
                        <datalist id="listLokasiInsiden">
                            @foreach ($daftarRuang as $rng)
                                <option value="{{ $rng->nama_ruang }} ({{ $rng->gedung }})">{{ $rng->nama_ruang }} -
                                    Lantai {{ $rng->lantai }}</option>
                            @endforeach
                        </datalist>
                    </div>
                    <div>
                        <label class="form-label">Tingkat Urgensi</label>
                        <select name="tingkat_urgensi" id="insiden_urgensi" class="form-control">
                            <option value="rendah">Rendah</option>
                            <option value="sedang" selected>Sedang</option>
                            <option value="tinggi">Tinggi</option>
                            <option value="darurat">Darurat</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <label class="form-label" style="margin: 0;">Pihak Terlibat / Saksi</label>
                        <span style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);"><i class="fas fa-users me-1"></i>Siswa
                            / GTK</span>
                    </div>
                    <input type="search" name="pihak_terlibat" id="insiden_pihak" class="form-control"
                        placeholder="Cari siswa/GTK atau ketik nama pihak terkait..." autocomplete="off"
                        data-people-search="{{ route('dashboard.people.search') }}" data-people-kind="all">
                </div>

                <div style="margin-bottom: 12px;">
                    <label class="form-label">Kronologi Kejadian <span style="color:red;">*</span></label>
                    <textarea name="kronologi" id="insiden_kronologi" class="form-control" rows="3"
                        placeholder="Jelaskan alur peristiwa secara runtut..." required></textarea>
                </div>

                <div style="margin-bottom: 12px;">
                    <label class="form-label">Tindakan Diambil</label>
                    <textarea name="tindakan_diambil" id="insiden_tindakan" class="form-control" rows="2"
                        placeholder="Tindakan awal petugas satpam / sekolah..."></textarea>
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="form-label">Status Penyelesaian</label>
                    <select name="status_penyelesaian" id="insiden_status" class="form-control">
                        <option value="dalam_penanganan">Dalam Penanganan</option>
                        <option value="selesai">Selesai</option>
                        <option value="diserahkan_ke_polsek">Diserahkan ke Pihak Berwajib</option>
                    </select>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModalInsiden()">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-save"></i> Simpan Laporan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script
        src="{{ asset('js/keamanan-patroli.js') }}?v={{ file_exists(public_path('js/keamanan-patroli.js')) ? filemtime(public_path('js/keamanan-patroli.js')) : time() }}">
    </script>
@endpush
