@extends('layouts.dashboard')

@section('title', 'Kebersihan Lingkungan Produktif — SAE')
@section('dash_title', 'Kebersihan Jurusan')

@section('content')
    @include('dashboard.laboran.partials.header-actions')
    <div class="dash-content-inner">
        {{-- Banner (Tupoksi #9) --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap; margin-bottom: var(--space-9);">
            <div style="display: flex; align-items: center; gap: var(--space-7);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(16,185,129,0.12); color: var(--success, #10b981); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl); flex-shrink: 0;">
                    <i class="fas fa-broom"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--text-color); margin: 0;">
                        Kebersihan Lingkungan Produktif Jurusan
                    </h2>
                    <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-1);">
                        Bukti fisik Tupoksi #9 — dokumentasi kegiatan kebersihan area praktik.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions dash-banner-access">
                <a href="{{ route('dashboard.laboran.kebersihan.cetak', ['dari' => $dari, 'sampai' => $sampai]) }}" target="_blank" class="btn btn-outline"
                    title="Cetak Dokumentasi" aria-label="Cetak Dokumentasi">
                    <i class="fas fa-print"></i>
                </a>
                @if ($canCreate)
                    <button type="button" class="btn btn-outline" id="btnOpenImportKebersihan"
                        title="Import Kegiatan via Excel" aria-label="Import Kegiatan via Excel">
                        <i class="fas fa-file-excel text-success"></i>
                    </button>
                    <button type="button" class="btn btn-primary" id="btnOpenKebersihan"
                        title="Catat Kegiatan Kebersihan" aria-label="Catat Kegiatan Kebersihan">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>

        {{-- Statistik --}}
        <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: var(--space-9); gap: var(--space-7);">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);"><i class="fas fa-broom"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statTotal, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Total Kegiatan</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);"><i class="fas fa-circle-check"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statBersih, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Kondisi Bersih</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239, 68, 68, 0.15); color: var(--danger, #ef4444);"><i class="fas fa-triangle-exclamation"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statTindakan, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Perlu Tindakan</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.15); color: var(--purple, #8b5cf6);"><i class="fas fa-camera"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statDokumentasi, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Ber-dokumentasi</div>
                </div>
            </div>
        </div>

        {{-- Datatable --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.laboran.kebersihan.index') }}" style="margin: 0;">
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

                            <select name="jenis" id="filterJenis" class="toolbar-filter-select" style="height: 38px;" title="Filter Jenis">
                                <option value="">Semua Jenis</option>
                                @foreach ($jenisList as $k => $v)
                                    <option value="{{ $k }}" {{ $jenisFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>

                            <select name="kondisi" id="filterKondisi" class="toolbar-filter-select" style="height: 38px;" title="Filter Kondisi">
                                <option value="">Semua Kondisi</option>
                                @foreach ($kondisiList as $k => $v)
                                    <option value="{{ $k }}" {{ $kondisiFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>

                            <x-sae.toolbar-date-range :from-value="$dari" :to-value="$sampai" />

                            @if ($search || $jenisFilter || $kondisiFilter)
                <a href="{{ route('dashboard.laboran.kebersihan.index') }}" class="btn btn-outline"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                                    title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="q" id="liveSearch" value="{{ $search }}" placeholder="Cari area, kegiatan, petugas, atau temuan..." autocomplete="off">
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
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Area &amp; Kegiatan</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Jenis</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Kondisi</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $k)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td data-label="Nomor" style="padding: var(--space-6) var(--space-7);">
                                    <div style="font-family: var(--font-family-mono); font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--primary);">{{ $k->nomor_kegiatan }}</div>
                                    <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ \Carbon\Carbon::parse($k->tanggal_kegiatan)->translatedFormat('d M Y') }}</div>
                                    @if ($k->singkatan || $k->nama_jurusan)
                                        <span class="badge badge-outline" style="font-size: var(--text-2xs); margin-top: var(--space-2);">{{ $k->singkatan ?: $k->nama_jurusan }}</span>
                                    @endif
                                </td>
                                <td data-label="Area" style="padding: var(--space-6) var(--space-7);">
                                    <div style="font-weight: var(--font-bold); color: var(--text-color); font-size: var(--text-base);">{{ $k->area }}</div>
                                    @if ($k->uraian_kegiatan)
                                        <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ \Illuminate\Support\Str::limit($k->uraian_kegiatan, 90) }}</div>
                                    @endif
                                    <div style="font-size: var(--text-2xs); color: var(--text-muted); margin-top: var(--space-1);">
                                        <i class="fas fa-users me-1"></i>{{ $k->jumlah_petugas }} petugas
                                        @if ($k->ruang_lab_nama) &bull; {{ $k->ruang_lab_nama }} @endif
                                    </div>
                                    @if ($k->temuan)
                                        <div style="font-size: var(--text-2xs); color: var(--danger, #ef4444); margin-top: var(--space-1);"><i class="fas fa-circle-exclamation me-1"></i>{{ \Illuminate\Support\Str::limit($k->temuan, 60) }}</div>
                                    @endif
                                </td>
                                <td data-label="Jenis" style="padding: var(--space-6) var(--space-7); text-align: center;">
                                    <span class="badge badge-outline" style="font-size: var(--text-2xs);">{{ $jenisList[$k->jenis_kegiatan] ?? $k->jenis_kegiatan }}</span>
                                </td>
                                <td data-label="Kondisi" style="padding: var(--space-6) var(--space-7); text-align: center;">
                                    @php
                                        $kc = match ($k->kondisi_akhir) {
                                            'bersih' => 'badge-success',
                                            'cukup' => 'badge-info',
                                            'kotor' => 'badge-warning',
                                            'perlu_tindakan' => 'badge-danger',
                                            default => 'badge-outline',
                                        };
                                    @endphp
                                    <span class="badge {{ $kc }}" style="font-size: var(--text-2xs);">{{ $kondisiList[$k->kondisi_akhir] ?? $k->kondisi_akhir }}</span>
                                    @if ($k->dokumentasi_paths)
                                        <div style="font-size: var(--text-2xs); color: var(--success, #10b981); margin-top: var(--space-2);"><i class="fas fa-camera me-1"></i>Ada dokumentasi</div>
                                    @endif
                                </td>
                                <td data-label="Aksi" style="padding: var(--space-6) var(--space-7); text-align: right;">
                                    <div class="table-actions" style="justify-content: flex-end; gap: var(--space-3);">
                                        @if ($canUpdate)
                                            <button type="button" class="btn-icon btn-edit-kebersihan"
                                                data-id="{{ $k->id }}" data-tanggal="{{ $k->tanggal_kegiatan }}"
                                                data-area="{{ $k->area }}" data-lokasi="{{ $k->ruang_lab_nama }}"
                                                data-jenis="{{ $k->jenis_kegiatan }}"
                                                data-uraian="{{ $k->uraian_kegiatan }}"
                                                data-peralatan="{{ $k->peralatan_digunakan }}"
                                                data-bahan="{{ $k->bahan_digunakan }}"
                                                data-petugas="{{ $k->jumlah_petugas }}"
                                                data-nama-petugas="{{ $k->nama_petugas }}"
                                                data-kondisi="{{ $k->kondisi_akhir }}"
                                                data-temuan="{{ $k->temuan }}"
                                                data-tindak="{{ $k->tindak_lanjut }}"
                                                data-dokumentasi="{{ $k->dokumentasi_paths }}"
                                                data-status="{{ $k->status }}"
                                                title="Ubah Catatan">
                                                <i class="fas fa-pen-to-square" style="color: var(--primary);"></i>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <button type="button" class="btn-icon btn-delete-kebersihan"
                                                data-id="{{ $k->id }}" data-nama="{{ $k->nomor_kegiatan }}"
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
                                    <i class="fas fa-broom mb-2" style="font-size: var(--text-3xl); opacity: 0.5;"></i>
                                    <div>Belum ada catatan kegiatan kebersihan pada rentang ini.</div>
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
        <div id="modalKebersihan" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 700px; max-height: 92vh; overflow-y: auto; padding: var(--space-10); border-radius: var(--radius-lg);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-9);">
                    <h3 id="modalKebersihanTitle" style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-color);">Catat Kegiatan Kebersihan</h3>
                    <button type="button" data-close="modalKebersihan" style="background: none; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;" title="Tutup">&times;</button>
                </div>

                <form id="formKebersihan" method="POST" action="{{ route('dashboard.laboran.kebersihan.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="kebersihanMethod" value="POST">

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Tanggal <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="date" name="tanggal_kegiatan" id="kbTanggal" class="form-control" required>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Jenis</label>
                            <select name="jenis_kegiatan" id="kbJenis" class="form-control">
                                @foreach ($jenisList as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Status</label>
                            <select name="status" id="kbStatus" class="form-control">
                                @foreach ($statusList as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1.4fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Area / Lokasi <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="text" name="area" id="kbArea" class="form-control" required placeholder="Contoh: Lahan Praktik ATPH / Bengkel Otomotif">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Ruang Lab</label>
                            <select name="ruang_lab_nama" id="kbLokasi" class="form-control">
                                <option value="">-- Pilih --</option>
                                @foreach ($lokasiOptions as $lok)
                                    <option value="{{ $lok }}">{{ $lok }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Uraian Kegiatan</label>
                        <textarea name="uraian_kegiatan" id="kbUraian" class="form-control" rows="2" placeholder="Pekerjaan kebersihan yang dilakukan..."></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Peralatan</label>
                            <input type="text" name="peralatan_digunakan" id="kbPeralatan" class="form-control" placeholder="Sapu, pel, sekop, dll.">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Bahan</label>
                            <input type="text" name="bahan_digunakan" id="kbBahan" class="form-control" placeholder="Sabun, pembersih lantai, dll.">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Jml Petugas</label>
                            <input type="number" min="1" max="500" name="jumlah_petugas" id="kbPetugas" class="form-control" value="1">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Kondisi Akhir</label>
                            <select name="kondisi_akhir" id="kbKondisi" class="form-control">
                                @foreach ($kondisiList as $k => $v)
                                    <option value="{{ $k }}" {{ $k === 'bersih' ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Nama Petugas</label>
                            <input type="text" name="nama_petugas" id="kbNamaPetugas" class="form-control" placeholder="Nama-nama petugas">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Temuan</label>
                            <textarea name="temuan" id="kbTemuan" class="form-control" rows="2" placeholder="Temuan saat kegiatan..."></textarea>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Tindak Lanjut</label>
                            <textarea name="tindak_lanjut" id="kbTindak" class="form-control" rows="2" placeholder="Rencana tindak lanjut..."></textarea>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-9);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            <i class="fas fa-camera me-1 text-primary"></i> Dokumentasi (path foto)
                        </label>
                        <textarea name="dokumentasi_paths[]" id="kbDokumentasi" class="form-control" rows="2"
                            placeholder="Satu path per baris, mis: storage/laboran/kebersihan/foto1.jpg"></textarea>
                        <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-2);">
                            <i class="fas fa-info-circle me-1"></i> Bukti fisik Tupoksi #9 — isi path foto dokumentasi kegiatan.
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: var(--space-4);">
                        <button type="button" class="btn btn-secondary" data-close="modalKebersihan">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        @include('dashboard.laboran.partials.kebersihan-modal-import')

        @push('scripts')
            <script src="{{ asset('js/laboran-common.js') }}?v={{ file_exists(public_path('js/laboran-common.js')) ? filemtime(public_path('js/laboran-common.js')) : time() }}"></script>
            <script src="{{ asset('js/laboran-kebersihan.js') }}?v={{ file_exists(public_path('js/laboran-kebersihan.js')) ? filemtime(public_path('js/laboran-kebersihan.js')) : time() }}"></script>
        @endpush
    </div>
@endsection
