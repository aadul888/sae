@extends('layouts.dashboard')

@section('title', 'Jurnal Harian Guru Piket')

@section('content')
    <div class="content-wrapper">
        <!-- 1. Header Banner & Actions -->
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg, 12px); background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); flex-shrink: 0;">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                        Jurnal Harian Guru Piket
                    </h2>
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        Rekapitulasi pelaksanaan piket harian sekolah, pemantauan ketertiban siswa, dan kejadian khusus.
                    </div>
                </div>
            </div>

            <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                @if ($canCreate)
                    <button type="button" class="btn btn-primary" onclick="openModalJurnal()"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                        title="Tulis Jurnal Piket Harian">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>

        <!-- 2. Stat Grid Baku SAE -->
        <div class="dash-stat-grid" style="margin-bottom: 20px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6);">
                    <i class="fas fa-book-bookmark"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['total'] }}</div>
                    <div class="dash-stat-label">Total Catatan Jurnal</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary-light, #6366f1);">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['bulan_ini'] }}</div>
                    <div class="dash-stat-label">Jurnal Bulan Ini</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: var(--success, #10b981);">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['hari_ini'] }}</div>
                    <div class="dash-stat-label">Jurnal Hari Ini</div>
                </div>
            </div>
        </div>

        {{-- Unified Datatable Piket Jurnal (Toolbar + Table + Pagination dalam 1 Card Terpadu) --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            {{-- 1. Toolbar Header (Entries, Filters & Live Search) --}}
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.piket.jurnal.index') }}" id="filterForm"
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

                            <input type="date" name="tanggal" value="{{ $tanggal }}" class="form-control"
                                style="height: 38px; width: auto; min-width: 140px; padding: 4px 10px;">

                            <select name="shift" class="toolbar-filter-select" style="height: 38px; min-width: 140px;">
                                <option value="">Semua Shift</option>
                                <option value="pagi" {{ $shift === 'pagi' ? 'selected' : '' }}>Pagi</option>
                                <option value="siang" {{ $shift === 'siang' ? 'selected' : '' }}>Siang</option>
                                <option value="full_day" {{ $shift === 'full_day' ? 'selected' : '' }}>Full Day</option>
                            </select>

                            @if ($search || $shift || $tanggal)
                                <a href="{{ route('dashboard.piket.jurnal.index') }}"
                                    class="btn btn-outline btn-reset-filter"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                    title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="search" id="searchPiketJurnal" value="{{ $search }}"
                                placeholder="Cari catatan, guru piket..." autocomplete="off">
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
                                Tanggal & Shift</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Guru Piket</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                Siswa Terlambat</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                Siswa Izin</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Catatan Kejadian</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Status</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $item)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 18px;">
                                    <div style="font-weight: 600; color: var(--text-heading);">
                                        {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}</div>
                                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); text-transform: uppercase;">
                                        {{ str_replace('_', ' ', $item->shift_jam) }}</div>
                                </td>
                                <td style="padding: 12px 18px;">
                                    <span
                                        style="font-weight: 600; color: var(--text-heading);">{{ $item->nama_guru_piket ?: 'Guru Piket' }}</span>
                                </td>
                                <td style="padding: 12px 18px; text-align: center;">
                                    <span class="badge"
                                        style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b); font-size: var(--text-base, 0.875rem);">
                                        {{ $item->jumlah_siswa_terlambat }} Siswa
                                    </span>
                                </td>
                                <td style="padding: 12px 18px; text-align: center;">
                                    <span class="badge"
                                        style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6); font-size: var(--text-base, 0.875rem);">
                                        {{ $item->jumlah_siswa_izin }} Siswa
                                    </span>
                                </td>
                                <td
                                    style="padding: 12px 18px; font-size: var(--text-base, 0.875rem); color: var(--text-heading); max-width: 250px;">
                                    <div style="white-space: normal;">
                                        {{ Str::limit($item->catatan_kejadian, 100) ?: '-' }}
                                    </div>
                                </td>
                                <td style="padding: 12px 18px;">
                                    @if ($item->status === 'selesai')
                                        <span class="badge"
                                            style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981); border: 1px solid rgba(16, 185, 129, 0.3);">Selesai</span>
                                    @else
                                        <span class="badge"
                                            style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b); border: 1px solid rgba(245, 158, 11, 0.3);">Berjalan</span>
                                    @endif
                                </td>
                                <td style="padding: 12px 18px; text-align: center;">
                                    <div class="table-actions" style="display: flex; gap: 6px; justify-content: center;">
                                        @if ($canUpdate)
                                            <button type="button" class="btn-icon" title="Edit"
                                                onclick="editJurnal({{ json_encode($item) }})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <form action="{{ route('dashboard.piket.jurnal.destroy', $item->id) }}"
                                                method="POST" data-confirm="delete"
                                                data-name="Jurnal {{ $item->tanggal }}">
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
                                    <i class="fas fa-clipboard-list"
                                        style="font-size: var(--text-3xl, 1.875rem); margin-bottom: 8px; opacity: 0.4;"></i>
                                    <p style="margin: 0;">Belum ada catatan jurnal guru piket.</p>
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

        {{-- Modal Form Jurnal Guru Piket --}}
        <div id="modalJurnal"
            style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center;">
            <div class="card" style="width: 100%; max-width: 580px; margin: 20px; max-height: 90vh; overflow-y: auto;">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
                    <h3 id="modalJurnalTitle"
                        style="font-size: var(--text-xl, 1.25rem); font-weight: 700; margin: 0; color: var(--text-heading);">Tulis Jurnal
                        Piket
                    </h3>
                    <button type="button" onclick="closeModalJurnal()"
                        style="background: none; border: none; font-size: var(--text-xl, 1.25rem); cursor: pointer; color: var(--text-muted);">&times;</button>
                </div>

                <form id="formJurnal" method="POST" action="{{ route('dashboard.piket.jurnal.store') }}"
                    data-store-url="{{ route('dashboard.piket.jurnal.store') }}"
                    data-update-url="{{ url('/dashboard/piket/jurnal') }}/:id">
                    @csrf
                    <input type="hidden" name="_method" id="jurnalMethod" value="POST">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label class="form-label">Tanggal <span style="color:red;">*</span></label>
                            <input type="date" name="tanggal" id="jurnal_tanggal" class="form-control"
                                value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div>
                            <label class="form-label">Shift Piket <span style="color:red;">*</span></label>
                            <select name="shift_jam" id="jurnal_shift" class="form-control" required>
                                <option value="pagi">Pagi</option>
                                <option value="siang">Siang</option>
                                <option value="full_day">Full Day</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label class="form-label">Jumlah Siswa Terlambat</label>
                            <input type="number" name="jumlah_siswa_terlambat" id="jurnal_terlambat"
                                class="form-control" min="0" placeholder="Auto hitung jika kosong">
                        </div>
                        <div>
                            <label class="form-label">Jumlah Siswa Izin</label>
                            <input type="number" name="jumlah_siswa_izin" id="jurnal_izin" class="form-control"
                                min="0" placeholder="Auto hitung jika kosong">
                        </div>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <label class="form-label">Catatan Kejadian / Laporan Piket</label>
                        <textarea name="catatan_kejadian" id="jurnal_catatan" class="form-control" rows="4"
                            placeholder="Keadaan KBM umum, guru tidak hadir/berhalangan, penertiban seragam, dan catatan khusus piket hari ini..."></textarea>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label class="form-label">Status Pelaksanaan</label>
                        <select name="status" id="jurnal_status" class="form-control">
                            <option value="berjalan">Berjalan</option>
                            <option value="selesai">Selesai</option>
                        </select>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="btn btn-secondary" onclick="closeModalJurnal()">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Jurnal</button>
                    </div>
                </form>
            </div>
        </div>
    @endsection

    @push('scripts')
        <script
            src="{{ asset('js/piket-jurnal.js') }}?v={{ file_exists(public_path('js/piket-jurnal.js')) ? filemtime(public_path('js/piket-jurnal.js')) : time() }}">
        </script>
    @endpush
