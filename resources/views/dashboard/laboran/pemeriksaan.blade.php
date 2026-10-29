@extends('layouts.dashboard')

@section('title', 'Pemeriksaan Ceklist Alat & Bahan — Laboratorium SAE')
@section('dash_title', 'Pemeriksaan Ceklist')

@section('content')
    @include('dashboard.laboran.partials.header-actions')
    <div class="dash-content-inner">
        {{-- 1. Banner Header --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap; margin-bottom: var(--space-9);">
            <div style="display: flex; align-items: center; gap: var(--space-7);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(16,185,129,0.12); color: var(--success, #10b981); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl); flex-shrink: 0;">
                    <i class="fas fa-list-check"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--text-color); margin: 0;">
                        Pemeriksaan Daftar Ceklist Alat &amp; Bahan
                    </h2>
                    <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-1);">
                        Pemeriksaan berkala kondisi alat &amp; bahan praktik dengan daftar ceklist terstruktur.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions dash-banner-access">
                <a href="{{ route('dashboard.laboran.pemeriksaan.cetak', request()->query()) }}" class="btn btn-outline"
                    title="Cetak Checklist Pemeriksaan Alat &amp; Bahan (A4)" aria-label="Cetak Checklist Pemeriksaan">
                    <i class="fas fa-print"></i>
                </a>
                @if ($canCreate)
                    <button type="button" class="btn btn-primary" id="btnOpenPemeriksaan"
                        title="Buat Pemeriksaan Baru" aria-label="Buat Pemeriksaan Baru">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>

        {{-- 2. Statistik --}}
        <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: var(--space-9); gap: var(--space-7);">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);"><i class="fas fa-list-check"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statTotal, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Total Pemeriksaan</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);"><i class="fas fa-pen-to-square"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statDraf, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Masih Draf</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);"><i class="fas fa-circle-check"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statFinal, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Final</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.15); color: var(--purple, #8b5cf6);"><i class="fas fa-calendar-day"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statBulan, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Bulan Ini</div>
                </div>
            </div>
        </div>

        {{-- 3. Datatable --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.laboran.pemeriksaan.index') }}" style="margin: 0;">
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

                            <select name="jenis" id="filterJenis" class="toolbar-filter-select" style="height: 38px;" title="Filter Jenis Pemeriksaan">
                                <option value="">Semua Jenis</option>
                                @foreach ($jenisList as $k => $v)
                                    <option value="{{ $k }}" {{ $jenisFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>

                            <select name="status" id="filterStatus" class="toolbar-filter-select" style="height: 38px;" title="Filter Status">
                                <option value="">Semua Status</option>
                                @foreach ($statusList as $k => $v)
                                    <option value="{{ $k }}" {{ $statusFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>

                            @if ($search || $jenisFilter || $statusFilter)
                                <a href="{{ route('dashboard.laboran.pemeriksaan.index') }}" class="btn btn-outline"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                                    title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="q" id="liveSearch" value="{{ $search }}" placeholder="Cari nomor, lokasi, petugas, atau kesimpulan..." autocomplete="off">
                            <button type="button" id="clearSearch" class="clear-search {{ $search ? 'visible' : '' }}" title="Hapus pencarian"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); ">
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Nomor / Tanggal</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Lokasi &amp; Petugas</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Jenis</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Hasil Ceklist</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Status</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $p)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td data-label="Nomor" style="padding: var(--space-6) var(--space-7);">
                                    <div style="font-family: var(--font-family-mono); font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--primary);">{{ $p->nomor_pemeriksaan }}</div>
                                    <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ \Carbon\Carbon::parse($p->tanggal_pemeriksaan)->translatedFormat('d M Y') }}</div>
                                    @if ($p->singkatan || $p->nama_jurusan)
                                        <span class="badge badge-outline" style="font-size: var(--text-2xs); margin-top: var(--space-2);">{{ $p->singkatan ?: $p->nama_jurusan }}</span>
                                    @endif
                                </td>
                                <td data-label="Lokasi" style="padding: var(--space-6) var(--space-7);">
                                    <div style="font-weight: var(--font-bold); color: var(--text-color); font-size: var(--text-base);">{{ $p->ruang_lab_nama ?: 'Semua Lokasi' }}</div>
                                    <div style="font-size: var(--text-xs); color: var(--text-muted);"><i class="fas fa-user me-1"></i>{{ $p->petugas_nama ?: '—' }}</div>
                                    @if ($p->kesimpulan)
                                        <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-2);">{{ \Illuminate\Support\Str::limit($p->kesimpulan, 70) }}</div>
                                    @endif
                                </td>
                                <td data-label="Jenis" style="padding: var(--space-6) var(--space-7); text-align: center;">
                                    <span class="badge badge-outline" style="font-size: var(--text-2xs);">{{ $jenisList[$p->jenis] ?? $p->jenis }}</span>
                                </td>
                                <td data-label="Hasil" style="padding: var(--space-6) var(--space-7); text-align: center; font-size: var(--text-sm);">
                                    <div style="display: flex; justify-content: center; gap: var(--space-3); flex-wrap: wrap;">
                                        <span class="badge badge-success" style="font-size: var(--text-2xs);" title="Sesuai">{{ $p->total_sesuai }}</span>
                                        <span class="badge badge-warning" style="font-size: var(--text-2xs);" title="Tidak Sesuai">{{ $p->total_tidak_sesuai }}</span>
                                        <span class="badge badge-danger" style="font-size: var(--text-2xs);" title="Tidak Ada">{{ $p->total_tidak_ada }}</span>
                                    </div>
                                    <div style="font-size: var(--text-2xs); color: var(--text-muted); margin-top: var(--space-2);">dari {{ $p->total_item }} item</div>
                                </td>
                                <td data-label="Status" style="padding: var(--space-6) var(--space-7); text-align: center;">
                                    <span class="badge {{ $p->status === 'final' ? 'badge-success' : 'badge-warning' }}" style="font-size: var(--text-2xs);">
                                        {{ $statusList[$p->status] ?? $p->status }}
                                    </span>
                                </td>
                                <td data-label="Aksi" style="padding: var(--space-6) var(--space-7); text-align: right;">
                                    <div class="table-actions" style="justify-content: flex-end; gap: var(--space-3);">
                                        <a href="{{ route('dashboard.laboran.pemeriksaan.show', $p->id) }}" class="btn-icon"
                                            title="Buka & Isi Ceklist" style="text-decoration: none;">
                                            <i class="fas fa-list-check" style="color: var(--primary);"></i>
                                        </a>
                                        @if ($canDelete)
                                            <button type="button" class="btn-icon btn-delete-pemeriksaan"
                                                data-id="{{ $p->id }}" data-nama="{{ $p->nomor_pemeriksaan }}"
                                                title="Hapus Pemeriksaan" style="color: var(--danger, #ef4444);">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: var(--space-11); text-align: center; color: var(--text-muted);">
                                    <i class="fas fa-list-check mb-2" style="font-size: var(--text-3xl); opacity: 0.5;"></i>
                                    <div>Belum ada pemeriksaan. Buat pemeriksaan untuk memulai ceklist alat.</div>
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

        {{-- Modal Buat Pemeriksaan --}}
        <div id="modalPemeriksaan" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 680px; max-height: 92vh; overflow-y: auto; padding: var(--space-10); border-radius: var(--radius-lg);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-9);">
                    <h3 style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-color);">Buat Pemeriksaan Baru</h3>
                    <button type="button" id="btnClosePemeriksaan" style="background: none; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;" title="Tutup">&times;</button>
                </div>

                <form id="formPemeriksaan" method="POST" action="{{ route('dashboard.laboran.pemeriksaan.store') }}">
                    @csrf

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Tanggal Pemeriksaan <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="date" name="tanggal_pemeriksaan" id="pmTanggal" class="form-control" required>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Jenis Pemeriksaan <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <select name="jenis" id="pmJenis" class="form-control" required>
                                @foreach ($jenisList as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Lokasi Praktik</label>
                            <select name="ruang_lab_nama" id="pmLokasi" class="form-control">
                                <option value="">-- Semua Lokasi --</option>
                                @foreach ($lokasiOptions as $lok)
                                    <option value="{{ $lok }}">{{ $lok }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Petugas</label>
                            <input type="text" name="petugas_nama" id="pmPetugas" class="form-control" placeholder="Nama petugas pemeriksa">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            Pilih Item yang Diperiksa
                            <span style="font-size: var(--text-xs); font-weight: var(--font-normal); color: var(--text-muted);">(kosongkan = semua item dalam cakupan)</span>
                        </label>
                        <div style="max-height: 220px; overflow-y: auto; padding: var(--space-5); border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                            @forelse ($itemOptions as $io)
                                <label style="display: flex; align-items: center; gap: var(--space-3); font-size: var(--text-sm); color: var(--text-color); cursor: pointer; margin-bottom: var(--space-3);">
                                    <input type="checkbox" name="item_ids[]" value="{{ $io->id }}" style="cursor: pointer;">
                                    <span>{{ $io->nama_item }} <span style="color: var(--text-muted); font-size: var(--text-xs);">({{ $io->kode_item }})</span></span>
                                </label>
                            @empty
                                <p style="font-size: var(--text-sm); color: var(--text-muted); margin: 0;">Belum ada item inventaris dalam cakupan Anda.</p>
                            @endforelse
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Kesimpulan</label>
                            <textarea name="kesimpulan" id="pmKesimpulan" class="form-control" rows="2" placeholder="Kesimpulan hasil pemeriksaan..."></textarea>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Rekomendasi</label>
                            <textarea name="rekomendasi" id="pmRekomendasi" class="form-control" rows="2" placeholder="Rekomendasi tindak lanjut..."></textarea>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-9);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Status Awal</label>
                        <select name="status" id="pmStatus" class="form-control">
                            <option value="draf">Draf (isi ceklist dulu)</option>
                            <option value="final">Final</option>
                        </select>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: var(--space-4);">
                        <button type="button" class="btn btn-secondary" id="btnCancelPemeriksaan">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Buat Pemeriksaan</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

        @push('scripts')
            <script src="{{ asset('js/laboran-common.js') }}?v={{ file_exists(public_path('js/laboran-common.js')) ? filemtime(public_path('js/laboran-common.js')) : time() }}"></script>
            <script src="{{ asset('js/laboran-pemeriksaan.js') }}?v={{ file_exists(public_path('js/laboran-pemeriksaan.js')) ? filemtime(public_path('js/laboran-pemeriksaan.js')) : time() }}"></script>
        @endpush
    @endsection
