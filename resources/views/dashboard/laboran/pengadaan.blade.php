@extends('layouts.dashboard')

@section('title', 'Usulan Pengadaan Alat & Bahan — Laboratorium SAE')
@section('dash_title', 'Usulan Pengadaan')

@section('content')
    <div class="dash-content-inner">
        {{-- 1. Banner Header --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap; margin-bottom: var(--space-9);">
            <div style="display: flex; align-items: center; gap: var(--space-7);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(139,92,246,0.12); color: var(--purple, #8b5cf6); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl); flex-shrink: 0;">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--text-color); margin: 0;">
                        Daftar Usulan Pengadaan Alat &amp; Bahan
                    </h2>
                    <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-1);">
                        Penyusunan usulan pengadaan alat &amp; bahan praktik beserta estimasi anggaran.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions" style="display: flex; gap: var(--space-4); align-items: center; flex-wrap: wrap;">
                <span class="badge" style="background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); border: 1px solid rgba(59,130,246,0.25); padding: var(--space-3) var(--space-6); border-radius: var(--radius-md); font-size: var(--text-xs); font-weight: var(--font-bold); display: inline-flex; align-items: center; gap: var(--space-2);">
                    <i class="fas {{ $scopeBadge['icon'] }}"></i> {{ $scopeBadge['label'] }}
                </span>
                @if ($canCreate)
                    <button type="button" class="btn btn-primary" id="btnOpenPengadaan"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md); font-size: var(--text-md);"
                        title="Buat Usulan Pengadaan">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>

        {{-- 2. Statistik --}}
        <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: var(--space-9); gap: var(--space-7);">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);"><i class="fas fa-file-invoice"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statTotal, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Total Usulan</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);"><i class="fas fa-hourglass-half"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statDiajukan, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Dalam Proses</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);"><i class="fas fa-circle-check"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statDisetujui, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Disetujui</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.15); color: var(--purple, #8b5cf6);"><i class="fas fa-money-bill-wave"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-md);">Rp {{ number_format($statBiaya, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Nilai Disetujui</div>
                </div>
            </div>
        </div>

        {{-- 3. Datatable --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.laboran.pengadaan.index') }}" style="margin: 0;">
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

                            <select name="jenis" id="filterJenis" class="toolbar-filter-select" style="height: 38px;" title="Filter Jenis">
                                <option value="">Semua Jenis</option>
                                @foreach ($jenisList as $k => $v)
                                    <option value="{{ $k }}" {{ ($jenisFilter ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>

                            <select name="urgensi" id="filterUrgensi" class="toolbar-filter-select" style="height: 38px;" title="Filter Urgensi">
                                <option value="">Semua Urgensi</option>
                                @foreach ($urgensiList as $k => $v)
                                    <option value="{{ $k }}" {{ $urgensiFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>

                            <select name="sumber_dana" id="filterSumberDana" class="toolbar-filter-select" style="height: 38px;" title="Filter Sumber Anggaran Pembelian">
                                <option value="">Semua Sumber Anggaran</option>
                                @foreach ($sumberDanaList as $k => $v)
                                    <option value="{{ $k }}" {{ ($sumberFilter ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>

                            @if ($search || $statusFilter || $urgensiFilter || !empty($jenisFilter) || !empty($sumberFilter))
                                <a href="{{ route('dashboard.laboran.pengadaan.index') }}" class="btn btn-outline"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                                    title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="q" id="liveSearch" value="{{ $search }}" placeholder="Cari nomor usulan, pemohon, atau justifikasi..." autocomplete="off">
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
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Jenis &amp; Justifikasi Usulan</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Item</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">Estimasi</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Urgensi</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Status</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $u)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td data-label="Nomor" style="padding: var(--space-6) var(--space-7);">
                                    <div style="font-family: var(--font-family-mono); font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--primary);">{{ $u->nomor_usulan }}</div>
                                    <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ \Carbon\Carbon::parse($u->tanggal_usulan)->translatedFormat('d M Y') }}</div>
                                    @if ($u->tahun_ajaran)
                                        <div style="font-size: var(--text-2xs); color: var(--text-muted);">TA {{ $u->tahun_ajaran }}{{ $u->semester ? ' • SMT ' . $u->semester : '' }}</div>
                                    @endif
                                    @if ($u->singkatan || $u->nama_jurusan)
                                        <span class="badge badge-outline" style="font-size: var(--text-2xs); margin-top: var(--space-2);">{{ $u->singkatan ?: $u->nama_jurusan }}</span>
                                    @endif
                                </td>
                                <td data-label="Jenis &amp; Justifikasi" style="padding: var(--space-6) var(--space-7);">
                                    <div style="display: flex; gap: var(--space-2); align-items: center; margin-bottom: var(--space-2); flex-wrap: wrap;">
                                        <span class="badge badge-primary" style="font-size: var(--text-2xs);">
                                            <i class="fas fa-boxes-stacked me-1"></i> {{ $jenisList[$u->jenis ?? 'semua'] ?? 'Semua' }}
                                        </span>
                                        @if ($u->sumber_dana)
                                            <span class="badge badge-outline" style="font-size: var(--text-2xs);">
                                                <i class="fas fa-wallet me-1"></i> {{ $sumberDanaList[$u->sumber_dana] ?? $u->sumber_dana }}
                                            </span>
                                        @endif
                                    </div>
                                    @if ($u->justifikasi)
                                        <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ \Illuminate\Support\Str::limit($u->justifikasi, 75) }}</div>
                                    @else
                                        <div style="font-size: var(--text-xs); color: var(--text-muted); font-style: italic;">Tidak ada catatan justifikasi.</div>
                                    @endif
                                </td>
                                <td data-label="Item" style="padding: var(--space-6) var(--space-7); text-align: center; font-weight: var(--font-bold); font-size: var(--text-base);">
                                    {{ $u->total_item }}
                                </td>
                                <td data-label="Estimasi" style="padding: var(--space-6) var(--space-7); text-align: right; font-size: var(--text-sm); font-weight: var(--font-bold); white-space: nowrap;">
                                    Rp {{ number_format((float) $u->total_estimasi_biaya, 0, ',', '.') }}
                                </td>
                                <td data-label="Urgensi" style="padding: var(--space-6) var(--space-7); text-align: center;">
                                    @php
                                        $uc = match ($u->urgensi) {
                                            'kritis' => 'badge-danger',
                                            'tinggi' => 'badge-warning',
                                            'sedang' => 'badge-info',
                                            default => 'badge-outline',
                                        };
                                    @endphp
                                    <span class="badge {{ $uc }}" style="font-size: var(--text-2xs);">{{ $urgensiList[$u->urgensi] ?? $u->urgensi }}</span>
                                </td>
                                <td data-label="Status" style="padding: var(--space-6) var(--space-7); text-align: center;">
                                    @php
                                        $sc = match ($u->status) {
                                            'disetujui', 'direalisasi' => 'badge-success',
                                            'ditolak' => 'badge-danger',
                                            'diverifikasi' => 'badge-info',
                                            'diajukan' => 'badge-warning',
                                            default => 'badge-outline',
                                        };
                                    @endphp
                                    <span class="badge {{ $sc }}" style="font-size: var(--text-2xs);">{{ $statusList[$u->status] ?? $u->status }}</span>
                                </td>
                                <td data-label="Aksi" style="padding: var(--space-6) var(--space-7); text-align: right;">
                                    <div class="table-actions" style="justify-content: flex-end; gap: var(--space-3);">
                                        <a href="{{ route('dashboard.laboran.pengadaan.show', $u->id) }}" class="btn-icon"
                                            title="Buka Detail Usulan" style="text-decoration: none;">
                                            <i class="fas fa-eye" style="color: var(--primary);"></i>
                                        </a>
                                        @if ($canUpdate)
                                            <button type="button" class="btn-icon btn-edit-pengadaan"
                                                data-id="{{ $u->id }}" data-ta="{{ $u->tahun_ajaran }}"
                                                data-semester="{{ $u->semester }}" data-tanggal="{{ $u->tanggal_usulan }}"
                                                data-jenis="{{ $u->jenis ?? 'semua' }}"
                                                data-dana="{{ $u->sumber_dana }}"
                                                data-urgensi="{{ $u->urgensi }}" data-status="{{ $u->status }}"
                                                data-justifikasi="{{ $u->justifikasi }}"
                                                data-catatan="{{ $u->catatan_verifikasi }}"
                                                title="Ubah Usulan">
                                                <i class="fas fa-pen-to-square" style="color: var(--primary);"></i>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <button type="button" class="btn-icon btn-delete-pengadaan"
                                                data-id="{{ $u->id }}" data-nama="{{ $u->nomor_usulan }}"
                                                title="Hapus Usulan" style="color: var(--danger, #ef4444);">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="padding: var(--space-11); text-align: center; color: var(--text-muted);">
                                    <i class="fas fa-file-invoice-dollar mb-2" style="font-size: var(--text-3xl); opacity: 0.5;"></i>
                                    <div>Belum ada usulan pengadaan.</div>
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

        {{-- Modal Form Usulan --}}
        <div id="modalPengadaan" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 700px; max-height: 92vh; overflow-y: auto; padding: var(--space-10); border-radius: var(--radius-lg);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-9);">
                    <h3 id="modalPengadaanTitle" style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-color);">Buat Usulan Pengadaan</h3>
                    <button type="button" data-close="modalPengadaan" style="background: none; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;" title="Tutup">&times;</button>
                </div>

                <form id="formPengadaan" method="POST" action="{{ route('dashboard.laboran.pengadaan.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="pengadaanMethod" value="POST">
                    <input type="hidden" name="jurusan_id" value="{{ $context['jurusan_id'] ?? '' }}">

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Tanggal Usulan <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="date" name="tanggal_usulan" id="pgTanggal" class="form-control" required>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Tahun Ajaran</label>
                            <input type="text" name="tahun_ajaran" id="pgTA" class="form-control" placeholder="2026/2027">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Semester</label>
                            <select name="semester" id="pgSemester" class="form-control">
                                <option value="">-- Pilih --</option>
                                <option value="1">Ganjil</option>
                                <option value="2">Genap</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Jenis Pengadaan <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <select name="jenis" id="pgJenis" class="form-control" required>
                                @foreach ($jenisList as $jk => $jv)
                                    <option value="{{ $jk }}">{{ $jv }}</option>
                                @endforeach
                            </select>
                            <small style="font-size: var(--text-xs); color: var(--text-muted); display: block; margin-top: 4px;">Pilihan jenis alat dan bahan</small>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Tingkat Urgensi <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <select name="urgensi" id="pgUrgensi" class="form-control" required>
                                @foreach ($urgensiList as $k => $v)
                                    <option value="{{ $k }}" {{ $k === 'sedang' ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Sumber Anggaran</label>
                            <select name="sumber_dana" id="pgDana" class="form-control">
                                <option value="">-- Pilih Sumber Anggaran --</option>
                                @foreach ($sumberDanaList as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Status Usulan</label>
                        <select name="status" id="pgStatus" class="form-control">
                            @foreach ($statusList as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Justifikasi / Catatan Pengadaan</label>
                        <textarea name="justifikasi" id="pgJustifikasi" class="form-control" rows="2" placeholder="Alasan kebutuhan pengadaan..."></textarea>
                    </div>

                    <div class="form-group" id="kebutuhanWrap" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            Tarik dari Analisis Kebutuhan
                            <span style="font-size: var(--text-xs); font-weight: var(--font-normal); color: var(--text-muted);">(pilih item untuk dimasukkan otomatis)</span>
                        </label>
                        <div style="max-height: 220px; overflow-y: auto; padding: var(--space-5); border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                            @forelse ($kebutuhanTersedia as $kb)
                                <label style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-3); font-size: var(--text-sm); color: var(--text-color); cursor: pointer; margin-bottom: var(--space-3); padding: var(--space-2) 0; border-bottom: 1px dashed var(--border-color);">
                                    <span style="display: flex; align-items: center; gap: var(--space-3);">
                                        <input type="checkbox" name="kebutuhan_ids[]" value="{{ $kb->id }}" style="cursor: pointer;">
                                        <span>
                                            <strong>{{ $kb->nama_item }}</strong>
                                            <span class="badge badge-outline" style="font-size: var(--text-2xs); margin-left: 6px;">{{ $kb->jenis === 'alat' ? 'Alat' : 'Bahan' }}</span>
                                        </span>
                                    </span>
                                    <span style="color: var(--danger, #ef4444); font-size: var(--text-xs); font-weight: var(--font-bold); white-space: nowrap;">
                                        Kekurangan: {{ (float) $kb->selisih }} {{ $kb->satuan }}
                                    </span>
                                </label>
                            @empty
                                <p style="font-size: var(--text-sm); color: var(--text-muted); margin: 0;">
                                    Tidak ada data kebutuhan terverifikasi yang belum masuk usulan.
                                </p>
                            @endforelse
                        </div>
                    </div>

                    <div class="form-group" id="catatanWrap" style="margin-bottom: var(--space-9); display: none;">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Catatan Verifikasi</label>
                        <textarea name="catatan_verifikasi" id="pgCatatan" class="form-control" rows="2"></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: var(--space-4);">
                        <button type="button" class="btn btn-secondary" data-close="modalPengadaan">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

        @push('scripts')
            <script src="{{ asset('js/laboran-common.js') }}?v={{ file_exists(public_path('js/laboran-common.js')) ? filemtime(public_path('js/laboran-common.js')) : time() }}"></script>
            <script src="{{ asset('js/laboran-pengadaan.js') }}?v={{ file_exists(public_path('js/laboran-pengadaan.js')) ? filemtime(public_path('js/laboran-pengadaan.js')) : time() }}"></script>
        @endpush
    @endsection
