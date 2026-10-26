@extends('layouts.dashboard')

@section('title', 'Bon Permintaan Alat & Bahan — Laboratorium SAE')
@section('dash_title', 'Bon Permintaan')

@section('content')
    <div class="dash-content-inner">
        {{-- 1. Banner Header  (Indikator Tupoksi #2) --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap; margin-bottom: var(--space-9);">
            <div style="display: flex; align-items: center; gap: var(--space-7);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(14,165,233,0.12); color: var(--info, #0ea5e9); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl); flex-shrink: 0;">
                    <i class="fas fa-file-signature"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--text-color); margin: 0;">
                        Bon / Form Permintaan Alat &amp; Bahan
                    </h2>
                    <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-1);">
                        Bukti fisik Tupoksi #2 — penyiapan alat, bahan, dan peralatan praktik pembelajaran.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions" style="display: flex; gap: var(--space-4); align-items: center; flex-wrap: wrap;">
                <span class="badge" style="background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); border: 1px solid rgba(59,130,246,0.25); padding: var(--space-3) var(--space-6); border-radius: var(--radius-md); font-size: var(--text-xs); font-weight: var(--font-bold); display: inline-flex; align-items: center; gap: var(--space-2);">
                    <i class="fas {{ $scopeBadge['icon'] }}"></i> {{ $scopeBadge['label'] }}
                </span>
                @if ($canCreate)
                    <button type="button" class="btn btn-primary" id="btnOpenPermintaan"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md); font-size: var(--text-md);"
                        title="Buat Bon Permintaan Baru">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>

        {{-- 2. Statistik --}}
        <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: var(--space-9); gap: var(--space-7);">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);"><i class="fas fa-file-signature"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statTotal, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Total Permintaan</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);"><i class="fas fa-hourglass-half"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statDiajukan, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Menunggu Penyiapan</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);"><i class="fas fa-hand-holding"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statDiserahkan, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Diserahkan</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.15); color: var(--purple, #8b5cf6);"><i class="fas fa-calendar-day"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statBulanIni, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Bulan Ini</div>
                </div>
            </div>
        </div>

        {{-- 3. Datatable --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.laboran.permintaan.index') }}" style="margin: 0;">
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

                            <select name="status" id="filterStatus" class="toolbar-filter-select" style="height: 38px;" title="Filter Status">
                                <option value="">Semua Status</option>
                                @foreach ($statusList as $k => $v)
                                    <option value="{{ $k }}" {{ $statusFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>

                            <input type="date" name="dari" value="{{ $tanggalFrom }}" class="form-control"
                                style="height: 38px; font-size: var(--text-sm); padding: 0 var(--space-5); border-radius: var(--radius-md);" title="Dari Tanggal">
                            <input type="date" name="sampai" value="{{ $tanggalTo }}" class="form-control"
                                style="height: 38px; font-size: var(--text-sm); padding: 0 var(--space-5); border-radius: var(--radius-md);" title="Sampai Tanggal">

                            @if ($search || $statusFilter || $tanggalFrom || $tanggalTo)
                                <a href="{{ route('dashboard.laboran.permintaan.index') }}" class="btn btn-outline"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                                    title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="q" id="liveSearch" value="{{ $search }}" placeholder="Cari nomor, pemohon, mapel, atau keperluan..." autocomplete="off">
                            <button type="button" id="clearSearch" class="clear-search {{ $search ? 'visible' : '' }}" title="Hapus pencarian"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255, 255, 255, 0.02);">
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Nomor / Tanggal</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Pemohon</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Keperluan</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Item</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Status</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $p)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td data-label="Nomor" style="padding: var(--space-6) var(--space-7);">
                                    <div style="font-family: var(--font-family-mono); font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--primary);">{{ $p->nomor_permintaan }}</div>
                                    <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ \Carbon\Carbon::parse($p->tanggal_permintaan)->translatedFormat('d M Y') }}</div>
                                    @if ($p->tanggal_dibutuhkan)
                                        <div style="font-size: var(--text-2xs); color: var(--warning, #f59e0b);">Dibutuhkan: {{ \Carbon\Carbon::parse($p->tanggal_dibutuhkan)->format('d/m/Y') }}</div>
                                    @endif
                                    @if ($p->singkatan || $p->nama_jurusan)
                                        <span class="badge badge-outline" style="font-size: var(--text-2xs); margin-top: var(--space-2);">{{ $p->singkatan ?: $p->nama_jurusan }}</span>
                                    @endif
                                </td>
                                <td data-label="Pemohon" style="padding: var(--space-6) var(--space-7);">
                                    <div style="font-weight: var(--font-bold); color: var(--text-color); font-size: var(--text-base);">{{ $p->pemohon_nama }}</div>
                                    @if ($p->rombel_nama || $p->mata_pelajaran)
                                        <div style="font-size: var(--text-xs); color: var(--text-muted);">
                                            {{ $p->rombel_nama ?: '' }}{{ $p->rombel_nama && $p->mata_pelajaran ? ' • ' : '' }}{{ $p->mata_pelajaran ?: '' }}
                                        </div>
                                    @endif
                                    @if ($p->ruang_lab_nama)
                                        <div style="font-size: var(--text-2xs); color: var(--text-muted);"><i class="fas fa-location-dot me-1"></i>{{ $p->ruang_lab_nama }}</div>
                                    @endif
                                </td>
                                <td data-label="Keperluan" style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); color: var(--text-color);">
                                    {{ \Illuminate\Support\Str::limit($p->keperluan, 80) ?: '—' }}
                                </td>
                                <td data-label="Item" style="padding: var(--space-6) var(--space-7); text-align: center; font-weight: var(--font-bold); font-size: var(--text-base);">{{ $p->total_item }}</td>
                                <td data-label="Status" style="padding: var(--space-6) var(--space-7); text-align: center;">
                                    @php
                                        $sc = match ($p->status) {
                                            'selesai', 'diserahkan' => 'badge-success',
                                            'disiapkan' => 'badge-info',
                                            'ditolak' => 'badge-danger',
                                            default => 'badge-warning',
                                        };
                                    @endphp
                                    <span class="badge {{ $sc }}" style="font-size: var(--text-2xs);">{{ $statusList[$p->status] ?? $p->status }}</span>
                                </td>
                                <td data-label="Aksi" style="padding: var(--space-6) var(--space-7); text-align: right;">
                                    <div class="table-actions" style="justify-content: flex-end; gap: var(--space-3);">
                                        <a href="{{ route('dashboard.laboran.permintaan.show', $p->id) }}" class="btn-icon" title="Buka Detail" style="text-decoration: none;">
                                            <i class="fas fa-eye" style="color: var(--primary);"></i>
                                        </a>
                                        <a href="{{ route('dashboard.laboran.permintaan.cetak', $p->id) }}" target="_blank" class="btn-icon" title="Cetak Bon Permintaan" style="text-decoration: none;">
                                            <i class="fas fa-print" style="color: var(--info, #0ea5e9);"></i>
                                        </a>
                                        @if ($canDelete)
                                            <button type="button" class="btn-icon btn-delete-permintaan"
                                                data-id="{{ $p->id }}" data-nama="{{ $p->nomor_permintaan }}"
                                                title="Hapus" style="color: var(--danger, #ef4444);">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: var(--space-11); text-align: center; color: var(--text-muted);">
                                    <i class="fas fa-file-signature mb-2" style="font-size: var(--text-3xl); opacity: 0.5;"></i>
                                    <div>Belum ada bon permintaan alat &amp; bahan.</div>
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

        {{-- Modal Buat Bon Permintaan --}}
        <div id="modalPermintaan" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 720px; max-height: 92vh; overflow-y: auto; padding: var(--space-10); border-radius: var(--radius-lg);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-9);">
                    <h3 style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-color);">Buat Bon Permintaan</h3>
                    <button type="button" data-close="modalPermintaan" style="background: none; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;" title="Tutup">&times;</button>
                </div>

                <form id="formPermintaan" method="POST" action="{{ route('dashboard.laboran.permintaan.store') }}">
                    @csrf

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Tanggal Permintaan <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="date" name="tanggal_permintaan" id="pmTanggal" class="form-control" required>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Tanggal Dibutuhkan</label>
                            <input type="date" name="tanggal_dibutuhkan" id="pmDibutuhkan" class="form-control">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Nama Pemohon <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="text" name="pemohon_nama" id="pmPemohon" class="form-control" required placeholder="Nama guru pengampu / pemohon">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Kelas / Rombel</label>
                            <select name="rombel_id" id="pmRombel" class="form-control">
                                <option value="">-- Pilih Kelas --</option>
                                @foreach (\App\Support\Table::exists('rombongan_belajar') ? \Illuminate\Support\Facades\DB::table('rombongan_belajar')->where('jenis_rombel', '1')->orderBy('nama')->get(['rombongan_belajar_id', 'nama']) : [] as $rb)
                                    <option value="{{ $rb->rombongan_belajar_id }}">{{ $rb->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Mata Pelajaran</label>
                            <input type="text" name="mata_pelajaran" id="pmMapel" class="form-control" placeholder="Contoh: Dasar Dasar Agribisnis Tanaman">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Ruang Lab / Bengkel</label>
                            <select name="ruang_lab_nama" id="pmLokasi" class="form-control">
                                <option value="">-- Pilih Lokasi --</option>
                                @foreach ($lokasiOptions as $lok)
                                    <option value="{{ $lok }}">{{ $lok }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Keperluan Praktik</label>
                        <input type="text" name="keperluan" id="pmKeperluan" class="form-control" placeholder="Contoh: Praktik pengukuran pH tanah">
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            Alat / Bahan yang Diminta <span style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <div style="max-height: 240px; overflow-y: auto; padding: var(--space-5); border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                            @forelse ($itemOptions as $io)
                                <div style="display: flex; align-items: center; gap: var(--space-4); padding: var(--space-3) 0; border-bottom: 1px dashed var(--border-color);">
                                    <input type="checkbox" name="item_ids[]" value="{{ $io->id }}" class="pm-item" style="cursor: pointer;">
                                    <div style="flex: 1; min-width: 0;">
                                        <div style="font-size: var(--text-sm); font-weight: var(--font-semibold); color: var(--text-color);">{{ $io->nama_item }}</div>
                                        <div style="font-size: var(--text-xs); color: var(--text-muted);">
                                            {{ $io->kode_item }} &bull; {{ $jenisList[$io->jenis] ?? $io->jenis }} &bull; Stok: {{ (float) $io->stok_tersedia }} {{ $io->satuan }}
                                        </div>
                                    </div>
                                    <input type="number" step="0.01" min="0.01" name="item_jumlah[{{ $io->id }}]" value="1"
                                        class="form-control" style="width: 90px; height: 34px; font-size: var(--text-sm);" title="Jumlah diminta">
                                </div>
                            @empty
                                <p style="font-size: var(--text-sm); color: var(--text-muted); margin: 0;">Belum ada alat/bahan dalam inventaris cakupan Anda.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-9);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Catatan Pemohon</label>
                        <textarea name="catatan_pemohon" id="pmCatatan" class="form-control" rows="2"></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: var(--space-4);">
                        <button type="button" class="btn btn-secondary" data-close="modalPermintaan">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan Bon</button>
                    </div>
                </form>
            </div>
        </div>

        @push('scripts')
            <script src="{{ asset('js/laboran-common.js') }}?v={{ file_exists(public_path('js/laboran-common.js')) ? filemtime(public_path('js/laboran-common.js')) : time() }}"></script>
            <script src="{{ asset('js/laboran-permintaan.js') }}?v={{ file_exists(public_path('js/laboran-permintaan.js')) ? filemtime(public_path('js/laboran-permintaan.js')) : time() }}"></script>
        @endpush
    </div>
@endsection
