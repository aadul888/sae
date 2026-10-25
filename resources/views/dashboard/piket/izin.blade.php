@extends('layouts.dashboard')

@section('title', 'e-Izin Keluar-Masuk Siswa - Piket Sekolah')

@section('content')
    <div class="content-wrapper">
        <!-- 1. Header Banner & Actions -->
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg, 12px); background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); flex-shrink: 0;">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                        e-Izin Keluar-Masuk Siswa (Piket Sekolah)
                    </h2>
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        Penerbitan surat izin keluar sebentar, pulang cepat, dan validasi slip barcode untuk satpam gerbang.
                    </div>
                </div>
            </div>

            <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                @if ($canCreate)
                    <button type="button" class="btn btn-primary" onclick="openModalIzin()"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                        title="Terbitkan Izin Baru">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>

        <!-- 2. Stat Grid Baku SAE -->
        <div class="dash-stat-grid" style="margin-bottom: 20px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6);">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['total'] }}</div>
                    <div class="dash-stat-label">Total Izin Hari Ini</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6);">
                    <i class="fas fa-person-walking"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['keluar_sebentar'] }}</div>
                    <div class="dash-stat-label">Keluar Sebentar</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239,68,68,0.12); color: var(--danger, #ef4444);">
                    <i class="fas fa-house-user"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['pulang_cepat'] }}</div>
                    <div class="dash-stat-label">Pulang Cepat</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b);">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['menunggu_gerbang'] }}</div>
                    <div class="dash-stat-label">Menunggu Gerbang</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: var(--success, #10b981);">
                    <i class="fas fa-route"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['di_luar'] }}</div>
                    <div class="dash-stat-label">Sedang di Luar</div>
                </div>
            </div>
        </div>

        {{-- Unified Datatable Piket Izin (Toolbar + Table + Pagination dalam 1 Card Terpadu) --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            {{-- 1. Toolbar Header (Entries, Filters & Live Search) --}}
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.piket.izin.index') }}" id="filterForm" style="margin: 0;">
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

                            <select name="jenis_izin" class="toolbar-filter-select" style="height: 38px; min-width: 150px;">
                                <option value="">Semua Jenis Izin</option>
                                <option value="keluar_sebentar" {{ $jenis === 'keluar_sebentar' ? 'selected' : '' }}>Keluar
                                    Sebentar
                                </option>
                                <option value="pulang_cepat" {{ $jenis === 'pulang_cepat' ? 'selected' : '' }}>Pulang Cepat
                                </option>
                                <option value="terlambat_masuk" {{ $jenis === 'terlambat_masuk' ? 'selected' : '' }}>
                                    Terlambat
                                    Masuk</option>
                            </select>

                            <select name="status" class="toolbar-filter-select" style="height: 38px; min-width: 150px;">
                                <option value="">Semua Status</option>
                                <option value="menunggu_satpam" {{ $status === 'menunggu_satpam' ? 'selected' : '' }}>
                                    Menunggu
                                    Gerbang</option>
                                <option value="di_luar" {{ $status === 'di_luar' ? 'selected' : '' }}>Di Luar Sekolah
                                </option>
                                <option value="kembali" {{ $status === 'kembali' ? 'selected' : '' }}>Sudah Kembali
                                </option>
                                <option value="pulang_selesai" {{ $status === 'pulang_selesai' ? 'selected' : '' }}>Pulang
                                    Selesai
                                </option>
                            </select>

                            @if ($search || $jenis || $status || ($tanggal && $tanggal !== date('Y-m-d')))
                                <a href="{{ route('dashboard.piket.izin.index') }}"
                                    class="btn btn-outline btn-reset-filter"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                    title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="search" id="searchPiketIzin" value="{{ $search }}"
                                placeholder="Cari nomor tiket, siswa, NISN..." autocomplete="off">
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
                                No Tiket</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Siswa & Rombel</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Jenis & Alasan</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Waktu</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Piket / Pembuat</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Status Gerbang</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $item)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 18px;">
                                    <span
                                        style="font-weight: 700; font-family: monospace; color: var(--primary); font-size: var(--text-base, 0.875rem);">{{ $item->nomor_tiket }}</span>
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                        {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}</div>
                                </td>
                                <td style="padding: 12px 18px;">
                                    <div style="font-weight: 600; color: var(--text-heading);">
                                        {{ $item->nama_siswa ?? '-' }}
                                    </div>
                                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                        NISN: {{ $item->nisn ?? '-' }} &bull; {{ $item->nama_rombel ?? '-' }}
                                    </div>
                                </td>
                                <td style="padding: 12px 18px;">
                                    <div>
                                        @if ($item->jenis_izin === 'keluar_sebentar')
                                            <span class="badge"
                                                style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6); border: 1px solid rgba(59, 130, 246, 0.3);">Keluar
                                                Sebentar</span>
                                        @elseif ($item->jenis_izin === 'pulang_cepat')
                                            <span class="badge"
                                                style="background: rgba(239, 68, 68, 0.15); color: var(--danger, #ef4444); border: 1px solid rgba(239, 68, 68, 0.3);">Pulang
                                                Cepat</span>
                                        @else
                                            <span class="badge"
                                                style="background: rgba(107, 114, 128, 0.15); color: #6b7280;">{{ ucfirst($item->jenis_izin) }}</span>
                                        @endif
                                    </div>
                                    <div
                                        style="font-size: var(--text-sm, 0.8125rem); color: var(--text-heading); margin-top: 4px; max-width: 220px; white-space: normal;">
                                        {{ $item->alasan }}
                                    </div>
                                </td>
                                <td style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem);">
                                    <div>Keluar: <strong>{{ substr($item->jam_izin_keluar, 0, 5) }}</strong></div>
                                    @if ($item->jam_rencana_kembali)
                                        <div style="color: var(--text-muted);">Rencana:
                                            {{ substr($item->jam_rencana_kembali, 0, 5) }}</div>
                                    @endif
                                    @if ($item->jam_kembali_aktual)
                                        <div style="color: var(--success, #10b981); font-weight: 600;">Kembali:
                                            {{ substr($item->jam_kembali_aktual, 0, 5) }}</div>
                                    @endif
                                </td>
                                <td style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem);">
                                    <div style="color: var(--text-heading); font-weight: 500;">
                                        {{ $item->nama_piket ?: $item->created_by ?: 'Guru Piket' }}</div>
                                </td>
                                <td style="padding: 12px 18px;">
                                    @if ($item->status === 'menunggu_satpam')
                                        <span class="badge"
                                            style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b); border: 1px solid rgba(245, 158, 11, 0.3);">Menunggu
                                            Gerbang</span>
                                    @elseif ($item->status === 'di_luar')
                                        <span class="badge"
                                            style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6); border: 1px solid rgba(59, 130, 246, 0.3);">Di
                                            Luar</span>
                                    @elseif ($item->status === 'kembali')
                                        <span class="badge"
                                            style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981); border: 1px solid rgba(16, 185, 129, 0.3);">Sudah
                                            Kembali</span>
                                    @elseif ($item->status === 'pulang_selesai')
                                        <span class="badge"
                                            style="background: rgba(99, 102, 241, 0.15); color: var(--primary-light, #6366f1); border: 1px solid rgba(99, 102, 241, 0.3);">Pulang
                                            Selesai</span>
                                    @else
                                        <span class="badge"
                                            style="background: rgba(107, 114, 128, 0.15); color: #6b7280;">{{ ucfirst($item->status) }}</span>
                                    @endif
                                </td>
                                <td style="padding: 12px 18px; text-align: center;">
                                    <div class="table-actions" style="display: flex; gap: 6px; justify-content: center;">
                                        <a href="{{ route('dashboard.piket.izin.cetak', $item->id) }}" target="_blank"
                                            class="btn-icon" title="Cetak Slip Izin">
                                            <i class="fas fa-print" style="color: var(--primary-hover, #2563eb);"></i>
                                        </a>
                                        @if ($canUpdate)
                                            <button type="button" class="btn-icon" title="Edit"
                                                onclick="editIzin({{ json_encode($item) }})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <form action="{{ route('dashboard.piket.izin.destroy', $item->id) }}"
                                                method="POST" data-confirm="delete"
                                                data-name="Tiket {{ $item->nomor_tiket }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon btn-icon-danger" title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 36px; color: var(--text-muted);">
                                    <i class="fas fa-ticket-alt"
                                        style="font-size: var(--text-3xl, 1.875rem); margin-bottom: 8px; opacity: 0.4;"></i>
                                    <p style="margin: 0;">Belum ada data izin keluar-masuk.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 3. Footer Paginasi Terpadu Datatable --}}
            @if ($list->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $list])
                </div>
            @endif
        </div>

        {{-- Modal Tambah / Edit e-Izin --}}
        <div id="modalIzin"
            style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center;">
            <div class="card" style="width: 100%; max-width: 600px; margin: 20px; max-height: 90vh; overflow-y: auto;">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
                    <h3 id="modalIzinTitle"
                        style="font-size: var(--text-xl, 1.25rem); font-weight: 700; margin: 0; color: var(--text-heading);">Terbitkan
                        e-Izin
                        Siswa</h3>
                    <button type="button" onclick="closeModalIzin()"
                        style="background: none; border: none; font-size: var(--text-xl, 1.25rem); cursor: pointer; color: var(--text-muted);">&times;</button>
                </div>

                <form id="formIzin" method="POST" action="{{ route('dashboard.piket.izin.store') }}"
                    data-store-url="{{ route('dashboard.piket.izin.store') }}"
                    data-update-url="{{ url('/dashboard/piket/izin') }}/:id">
                    @csrf
                    <input type="hidden" name="_method" id="izinMethod" value="POST">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label class="form-label">Tanggal <span style="color:red;">*</span></label>
                            <input type="date" name="tanggal" id="izin_tanggal" class="form-control"
                                value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div>
                            <label class="form-label">Jenis Izin <span style="color:red;">*</span></label>
                            <select name="jenis_izin" id="izin_jenis" class="form-control" required>
                                <option value="keluar_sebentar">Keluar Sebentar (Kembali Lagi)</option>
                                <option value="pulang_cepat">Pulang Cepat / Sakit</option>
                                <option value="terlambat_masuk">Dispensasi Terlambat Masuk</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;"
                        id="rowSiswaSelect">
                        <div>
                            <label class="form-label">Pilih Kelas / Rombel</label>
                            <select id="filter_rombel_modal" class="form-control" onchange="filterSiswaByRombel()">
                                <option value="">Semua Rombel</option>
                                @foreach ($rombelList as $r)
                                    <option value="{{ $r->rombongan_belajar_id }}">{{ $r->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Pilih Peserta Didik <span style="color:red;">*</span></label>
                            <input type="hidden" name="peserta_didik_id" id="izin_siswa_id">
                            <div style="position: relative;">
                                <input type="search" id="izin_siswa_search" class="form-control"
                                    placeholder="Ketik nama, NISN, atau NIPD..." autocomplete="off"
                                    data-people-search="{{ route('dashboard.piket.izin.search-siswa') }}"
                                    data-people-kind="siswa" data-people-target="#izin_siswa_id"
                                    data-people-filter="#filter_rombel_modal" data-people-require-selection="true"
                                    required>
                            </div>
                            <small id="izin_siswa_status" class="text-muted" aria-live="polite"></small>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label class="form-label">Jam Izin Keluar <span style="color:red;">*</span></label>
                            <input type="time" name="jam_izin_keluar" id="izin_jam_keluar" class="form-control"
                                value="{{ date('H:i') }}" required>
                        </div>
                        <div>
                            <label class="form-label">Jam Rencana Kembali</label>
                            <input type="time" name="jam_rencana_kembali" id="izin_jam_kembali" class="form-control">
                        </div>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label class="form-label">Alasan Izin <span style="color:red;">*</span></label>
                        <textarea name="alasan" id="izin_alasan" class="form-control" rows="3"
                            placeholder="Contoh: Mengambil berkas di rumah / Sakit demam di UKS & dijemput orang tua..." required></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="btn btn-secondary" onclick="closeModalIzin()">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Terbitkan
                            Izin</button>
                    </div>
                </form>
            </div>
        </div>
    @endsection

    @push('scripts')
        <script
            src="{{ asset('js/piket-izin.js') }}?v={{ file_exists(public_path('js/piket-izin.js')) ? filemtime(public_path('js/piket-izin.js')) : time() }}">
        </script>
    @endpush
