@extends('layouts.dashboard')

@section('title', 'Buku Administrasi Jurusan — SAE')
@section('dash_title', 'Administrasi Jurusan')

@section('content')
    <div class="dash-content-inner">
        {{-- Banner (Tupoksi #1) --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap; margin-bottom: var(--space-9);">
            <div style="display: flex; align-items: center; gap: var(--space-7);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(99,102,241,0.12); color: var(--primary, #6366f1); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl); flex-shrink: 0;">
                    <i class="fas fa-folder-open"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--text-color); margin: 0;">
                        Buku Administrasi Jurusan
                    </h2>
                    <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-1);">
                        Bukti fisik Tupoksi #1 — administrasi program keahlian/jurusan &amp; jadwal penggunaan lab/bengkel.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions" style="display: flex; gap: var(--space-4); align-items: center; flex-wrap: wrap;">
                <span class="badge" style="background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); border: 1px solid rgba(59,130,246,0.25); padding: var(--space-3) var(--space-6); border-radius: var(--radius-md); font-size: var(--text-xs); font-weight: var(--font-bold); display: inline-flex; align-items: center; gap: var(--space-2);">
                    <i class="fas {{ $scopeBadge['icon'] }}"></i> {{ $scopeBadge['label'] }}
                </span>
                <a href="{{ route('dashboard.laboran.administrasi.cetak') }}" target="_blank" class="btn btn-outline"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md); font-size: var(--text-md); text-decoration: none;"
                    title="Cetak Buku Administrasi">
                    <i class="fas fa-print"></i>
                </a>
                @if ($canCreate)
                    <button type="button" class="btn btn-primary" id="btnOpenAdministrasi"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md); font-size: var(--text-md);"
                        title="Catat Dokumen Administrasi">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>

        {{-- Statistik --}}
        <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: var(--space-9); gap: var(--space-7);">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);"><i class="fas fa-folder-open"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statTotal, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Total Dokumen</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);"><i class="fas fa-circle-check"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statAktif, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Aktif</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.15); color: var(--purple, #8b5cf6);"><i class="fas fa-box-archive"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statArsip, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Diarsipkan</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);"><i class="fas fa-calendar-day"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statBulanIni, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Bulan Ini</div>
                </div>
            </div>
        </div>

        {{-- Datatable --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.laboran.administrasi.index') }}" style="margin: 0;">
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

                            <select name="jenis" id="filterJenis" class="toolbar-filter-select" style="height: 38px;" title="Filter Jenis Dokumen">
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

                            <select name="tahun_ajaran" id="filterTA" class="toolbar-filter-select" style="height: 38px;" title="Filter Tahun Ajaran">
                                <option value="">Semua TA</option>
                                @foreach ($tahunAjaranList as $ta)
                                    <option value="{{ $ta }}" {{ $taFilter === $ta ? 'selected' : '' }}>{{ $ta }}</option>
                                @endforeach
                            </select>

                            @if ($search || $jenisFilter || $statusFilter || $taFilter)
                                <a href="{{ route('dashboard.laboran.administrasi.index') }}" class="btn btn-outline"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                                    title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="q" id="liveSearch" value="{{ $search }}" placeholder="Cari perihal, nomor, atau asal/tujuan..." autocomplete="off">
                            <button type="button" id="clearSearch" class="clear-search {{ $search ? 'visible' : '' }}" title="Hapus pencarian"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255, 255, 255, 0.02);">
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Tanggal / Nomor</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Perihal</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Jenis</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Status</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $a)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td data-label="Tanggal" style="padding: var(--space-6) var(--space-7);">
                                    <div style="font-weight: var(--font-bold); color: var(--text-color); font-size: var(--text-sm);">
                                        {{ \Carbon\Carbon::parse($a->tanggal)->translatedFormat('d M Y') }}
                                    </div>
                                    @if ($a->nomor_dokumen)
                                        <div style="font-family: var(--font-family-mono); font-size: var(--text-xs); color: var(--primary);">{{ $a->nomor_dokumen }}</div>
                                    @endif
                                    @if ($a->nomor_urut)
                                        <div style="font-size: var(--text-2xs); color: var(--text-muted);">Urut: {{ $a->nomor_urut }}</div>
                                    @endif
                                    @if ($a->singkatan || $a->nama_jurusan)
                                        <span class="badge badge-outline" style="font-size: var(--text-2xs); margin-top: var(--space-2);">{{ $a->singkatan ?: $a->nama_jurusan }}</span>
                                    @endif
                                </td>
                                <td data-label="Perihal" style="padding: var(--space-6) var(--space-7);">
                                    <div style="font-weight: var(--font-bold); color: var(--text-color); font-size: var(--text-base);">{{ $a->perihal }}</div>
                                    @if ($a->asal_tujuan)
                                        <div style="font-size: var(--text-xs); color: var(--text-muted);"><i class="fas fa-arrow-right-arrow-left me-1"></i>{{ $a->asal_tujuan }}</div>
                                    @endif
                                    @if ($a->ringkasan)
                                        <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-1);">{{ \Illuminate\Support\Str::limit($a->ringkasan, 80) }}</div>
                                    @endif
                                    @if ($a->tahun_ajaran)
                                        <div style="font-size: var(--text-2xs); color: var(--text-muted);">TA {{ $a->tahun_ajaran }}{{ $a->semester ? ' • SMT ' . $a->semester : '' }}</div>
                                    @endif
                                </td>
                                <td data-label="Jenis" style="padding: var(--space-6) var(--space-7); text-align: center;">
                                    <span class="badge badge-outline" style="font-size: var(--text-2xs);">{{ $jenisList[$a->jenis_dokumen] ?? $a->jenis_dokumen }}</span>
                                </td>
                                <td data-label="Status" style="padding: var(--space-6) var(--space-7); text-align: center;">
                                    @php
                                        $sc = match ($a->status) {
                                            'aktif' => 'badge-success',
                                            'arsip' => 'badge-info',
                                            default => 'badge-danger',
                                        };
                                    @endphp
                                    <span class="badge {{ $sc }}" style="font-size: var(--text-2xs);">{{ $statusList[$a->status] ?? $a->status }}</span>
                                </td>
                                <td data-label="Aksi" style="padding: var(--space-6) var(--space-7); text-align: right;">
                                    <div class="table-actions" style="justify-content: flex-end; gap: var(--space-3);">
                                        @if ($canUpdate)
                                            <button type="button" class="btn-icon btn-edit-administrasi"
                                                data-id="{{ $a->id }}" data-tanggal="{{ $a->tanggal }}"
                                                data-urut="{{ $a->nomor_urut }}" data-ta="{{ $a->tahun_ajaran }}"
                                                data-semester="{{ $a->semester }}" data-jenis="{{ $a->jenis_dokumen }}"
                                                data-nomor="{{ $a->nomor_dokumen }}" data-perihal="{{ $a->perihal }}"
                                                data-asal="{{ $a->asal_tujuan }}"
                                                data-tgl-dok="{{ $a->tanggal_dokumen }}"
                                                data-ringkasan="{{ $a->ringkasan }}"
                                                data-keterangan="{{ $a->keterangan }}"
                                                data-status="{{ $a->status }}"
                                                title="Ubah Dokumen">
                                                <i class="fas fa-pen-to-square" style="color: var(--primary);"></i>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <button type="button" class="btn-icon btn-delete-administrasi"
                                                data-id="{{ $a->id }}" data-nama="{{ $a->perihal }}"
                                                title="Hapus" style="color: var(--danger, #ef4444);">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="padding: var(--space-11); text-align: center; color: var(--text-muted);">
                                    <i class="fas fa-folder-open mb-2" style="font-size: var(--text-3xl); opacity: 0.5;"></i>
                                    <div>Belum ada dokumen administrasi jurusan.</div>
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

        {{-- Modal --}}
        <div id="modalAdministrasi" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 720px; max-height: 92vh; overflow-y: auto; padding: var(--space-10); border-radius: var(--radius-lg);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-9);">
                    <h3 id="modalAdministrasiTitle" style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-color);">Catat Dokumen Administrasi</h3>
                    <button type="button" data-close="modalAdministrasi" style="background: none; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;" title="Tutup">&times;</button>
                </div>

                <form id="formAdministrasi" method="POST" action="{{ route('dashboard.laboran.administrasi.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="administrasiMethod" value="POST">

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Tanggal Catat <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="date" name="tanggal" id="adTanggal" class="form-control" required>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Nomor Urut</label>
                            <input type="text" name="nomor_urut" id="adUrut" class="form-control" placeholder="001">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Jenis Dokumen <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <select name="jenis_dokumen" id="adJenis" class="form-control" required>
                                @foreach ($jenisList as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Tahun Ajaran</label>
                            <input type="text" name="tahun_ajaran" id="adTA" class="form-control" placeholder="2026/2027">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Semester</label>
                            <select name="semester" id="adSemester" class="form-control">
                                <option value="">-- Pilih --</option>
                                <option value="1">Ganjil</option>
                                <option value="2">Genap</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Nomor Dokumen</label>
                            <input type="text" name="nomor_dokumen" id="adNomor" class="form-control" placeholder="421.3/SK-01/2026">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            Perihal <span style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <input type="text" name="perihal" id="adPerihal" class="form-control" required placeholder="Pokok isi dokumen">
                    </div>

                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Asal / Tujuan</label>
                            <input type="text" name="asal_tujuan" id="adAsal" class="form-control" placeholder="Instansi / pihak">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Tanggal Dokumen</label>
                            <input type="date" name="tanggal_dokumen" id="adTglDok" class="form-control">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Ringkasan</label>
                        <textarea name="ringkasan" id="adRingkasan" class="form-control" rows="2" placeholder="Ringkasan isi dokumen..."></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--space-6); margin-bottom: var(--space-9);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Keterangan</label>
                            <input type="text" name="keterangan" id="adKeterangan" class="form-control" placeholder="Catatan tambahan">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Status</label>
                            <select name="status" id="adStatus" class="form-control">
                                @foreach ($statusList as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: var(--space-4);">
                        <button type="button" class="btn btn-secondary" data-close="modalAdministrasi">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        @push('scripts')
            <script src="{{ asset('js/laboran-common.js') }}?v={{ file_exists(public_path('js/laboran-common.js')) ? filemtime(public_path('js/laboran-common.js')) : time() }}"></script>
            <script src="{{ asset('js/laboran-administrasi.js') }}?v={{ file_exists(public_path('js/laboran-administrasi.js')) ? filemtime(public_path('js/laboran-administrasi.js')) : time() }}"></script>
        @endpush
    </div>
@endsection
