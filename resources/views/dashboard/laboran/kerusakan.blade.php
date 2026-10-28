@extends('layouts.dashboard')

@section('title', 'Berita Acara Kerusakan Alat — Laboratorium SAE')
@section('dash_title', 'Berita Acara Kerusakan')

@section('content')
    @include('dashboard.laboran.partials.header-actions')
    <div class="dash-content-inner">
        {{-- 1. Banner Header --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap; margin-bottom: var(--space-9);">
            <div style="display: flex; align-items: center; gap: var(--space-7);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(239,68,68,0.12); color: var(--danger, #ef4444); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl); flex-shrink: 0;">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--text-color); margin: 0;">
                        Pengelolaan Berita Acara Kerusakan Alat
                    </h2>
                    <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-1);">
                        Pencatatan resmi kejadian kerusakan alat beserta kronologi, saksi, dan tindak lanjut.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions dash-banner-access">
                @if ($canCreate)
                    <button type="button" class="btn btn-primary" id="btnOpenKerusakan"
                        title="Buat Berita Acara Kerusakan" aria-label="Buat Berita Acara Kerusakan">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>

        {{-- 2. Statistik --}}
        <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: var(--space-9); gap: var(--space-7);">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);"><i class="fas fa-file-contract"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statTotal, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Total Berita Acara</div>
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
                <div class="dash-stat-icon" style="background: rgba(239, 68, 68, 0.15); color: var(--danger, #ef4444);"><i class="fas fa-circle-exclamation"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statBerat, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Rusak Berat / Total</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.15); color: var(--purple, #8b5cf6);"><i class="fas fa-money-bill-wave"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-md);">Rp {{ number_format($statBiaya, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Estimasi Biaya</div>
                </div>
            </div>
        </div>

        {{-- 3. Datatable --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.laboran.kerusakan.index') }}" style="margin: 0;">
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

                            <select name="tingkat" id="filterTingkat" class="toolbar-filter-select" style="height: 38px;" title="Filter Tingkat Kerusakan">
                                <option value="">Semua Tingkat</option>
                                @foreach ($tingkatList as $k => $v)
                                    <option value="{{ $k }}" {{ $tingkatFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>

                            <select name="status" id="filterStatus" class="toolbar-filter-select" style="height: 38px;" title="Filter Status">
                                <option value="">Semua Status</option>
                                @foreach ($statusList as $k => $v)
                                    <option value="{{ $k }}" {{ $statusFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>

                            @if ($search || $tingkatFilter || $statusFilter)
                                <a href="{{ route('dashboard.laboran.kerusakan.index') }}" class="btn btn-outline"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                                    title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="q" id="liveSearch" value="{{ $search }}" placeholder="Cari nomor BA, alat, kronologi, atau pelaku..." autocomplete="off">
                            <button type="button" id="clearSearch" class="clear-search {{ $search ? 'visible' : '' }}" title="Hapus pencarian"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255, 255, 255, 0.02);">
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Nomor / Kejadian</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Alat &amp; Kronologi</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Tingkat</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Status</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $ba)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td data-label="Nomor" style="padding: var(--space-6) var(--space-7);">
                                    <div style="font-family: var(--font-family-mono); font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--primary);">{{ $ba->nomor_ba }}</div>
                                    <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ \Carbon\Carbon::parse($ba->tanggal_kejadian)->translatedFormat('d M Y') }}</div>
                                    @if ($ba->singkatan || $ba->nama_jurusan)
                                        <span class="badge badge-outline" style="font-size: var(--text-2xs); margin-top: var(--space-2);">{{ $ba->singkatan ?: $ba->nama_jurusan }}</span>
                                    @endif
                                </td>
                                <td data-label="Alat" style="padding: var(--space-6) var(--space-7);">
                                    @php
                                        $rincian = collect($ba->alat ?? []);
                                        if ($rincian->isEmpty()) {
                                            $rincian = collect([(object) [
                                                'nama_alat'   => $ba->nama_alat,
                                                'jumlah_rusak' => $ba->jumlah_rusak,
                                                'satuan'      => $ba->satuan,
                                                'kode_alat'   => $ba->kode_alat,
                                                'ruang_lab_nama' => $ba->ruang_lab_nama,
                                                'tingkat_kerusakan' => $ba->tingkat_kerusakan,
                                                'keterangan'  => null,
                                            ]]);
                                        }
                                        $alatPertama = $rincian->first();
                                        $alatJson = json_encode($rincian->map(fn ($ra) => [
                                            'item_id'           => $ra->item_id ?? null,
                                            'nama_alat'         => $ra->nama_alat,
                                            'kode_alat'         => $ra->kode_alat ?? null,
                                            'ruang_lab_nama'    => $ra->ruang_lab_nama ?? null,
                                            'jumlah_rusak'      => (float) $ra->jumlah_rusak,
                                            'satuan'            => $ra->satuan,
                                            'tingkat_kerusakan' => $ra->tingkat_kerusakan ?? $ba->tingkat_kerusakan,
                                            'keterangan'        => $ra->keterangan ?? null,
                                        ])->values());
                                    @endphp
                                    <div style="font-weight: var(--font-bold); color: var(--text-color); font-size: var(--text-base);">
                                        {{ $alatPertama->nama_alat }}
                                        @if ($rincian->count() > 1)
                                            <span class="badge badge-outline" style="font-size: var(--text-2xs); margin-left: var(--space-2);">+{{ $rincian->count() - 1 }} alat lain</span>
                                        @endif
                                    </div>
                                    <div style="font-size: var(--text-xs); color: var(--text-muted);">
                                        {{ (float) $alatPertama->jumlah_rusak }} {{ $alatPertama->satuan }}
                                        @if ($alatPertama->ruang_lab_nama ?? null) &bull; {{ $alatPertama->ruang_lab_nama }} @endif
                                    </div>
                                    @if ($rincian->count() > 1)
                                        <ul style="margin: var(--space-2) 0 0 var(--space-6); padding: 0; font-size: var(--text-xs); color: var(--text-muted);">
                                            @foreach ($rincian->skip(1) as $ra)
                                                <li>{{ $ra->nama_alat }} — {{ (float) $ra->jumlah_rusak }} {{ $ra->satuan }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    @if ($ba->kronologi)
                                        <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-2);">{{ \Illuminate\Support\Str::limit($ba->kronologi, 80) }}</div>
                                    @endif
                                    @if ($ba->pelaku_nama)
                                        <div style="font-size: var(--text-2xs); color: var(--text-muted); margin-top: var(--space-1);">
                                            <i class="fas fa-user me-1"></i>{{ $ba->pelaku_nama }}
                                        </div>
                                    @endif
                                    @if ($ba->perlu_usulan_penggantian)
                                        <span class="badge badge-warning" style="font-size: var(--text-2xs); margin-top: var(--space-2);">Perlu Penggantian</span>
                                    @endif
                                </td>
                                <td data-label="Tingkat" style="padding: var(--space-6) var(--space-7); text-align: center;">
                                    @php
                                        $tc = match ($ba->tingkat_kerusakan) {
                                            'total' => 'badge-danger',
                                            'berat' => 'badge-danger',
                                            'sedang' => 'badge-warning',
                                            default => 'badge-info',
                                        };
                                    @endphp
                                    <span class="badge {{ $tc }}" style="font-size: var(--text-2xs);">{{ $tingkatList[$ba->tingkat_kerusakan] ?? $ba->tingkat_kerusakan }}</span>
                                    @if ($ba->estimasi_biaya)
                                        <div style="font-size: var(--text-2xs); color: var(--text-muted); margin-top: var(--space-2);">Rp {{ number_format((float) $ba->estimasi_biaya, 0, ',', '.') }}</div>
                                    @endif
                                </td>
                                <td data-label="Status" style="padding: var(--space-6) var(--space-7); text-align: center;">
                                    @php
                                        $sc = match ($ba->status) {
                                            'selesai', 'disetujui' => 'badge-success',
                                            'diverifikasi' => 'badge-info',
                                            'diajukan' => 'badge-warning',
                                            default => 'badge-outline',
                                        };
                                    @endphp
                                    <span class="badge {{ $sc }}" style="font-size: var(--text-2xs);">{{ $statusList[$ba->status] ?? $ba->status }}</span>
                                </td>
                                <td data-label="Aksi" style="padding: var(--space-6) var(--space-7); text-align: right;">
                                    <div class="table-actions" style="justify-content: flex-end; gap: var(--space-3);">
                                        <a href="{{ route('dashboard.laboran.kerusakan.cetak', $ba->id) }}" target="_blank" class="btn-icon"
                                            title="Cetak Berita Acara" style="text-decoration: none;">
                                            <i class="fas fa-print" style="color: var(--primary);"></i>
                                        </a>
                                        @if ($canUpdate)
                                            <button type="button" class="btn-icon btn-edit-kerusakan"
                                                data-id="{{ $ba->id }}"
                                                data-alat="{{ $alatJson }}"
                                                data-lokasi="{{ $ba->ruang_lab_nama }}"
                                                data-kejadian="{{ $ba->tanggal_kejadian }}" data-ba="{{ $ba->tanggal_ba }}"
                                                data-tingkat="{{ $ba->tingkat_kerusakan }}"
                                                data-kronologi="{{ $ba->kronologi }}" data-penyebab="{{ $ba->penyebab }}"
                                                data-pelaku-tipe="{{ $ba->pelaku_tipe }}" data-pelaku-nama="{{ $ba->pelaku_nama }}"
                                                data-saksi1="{{ $ba->saksi_1_nama }}" data-saksi2="{{ $ba->saksi_2_nama }}"
                                                data-tindakan="{{ $ba->tindakan_lanjut }}"
                                                data-biaya="{{ $ba->estimasi_biaya }}"
                                                data-perlu="{{ $ba->perlu_usulan_penggantian ? 1 : 0 }}"
                                                data-status="{{ $ba->status }}"
                                                data-catatan="{{ $ba->catatan_verifikasi }}"
                                                title="Ubah Berita Acara">
                                                <i class="fas fa-pen-to-square" style="color: var(--primary);"></i>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <button type="button" class="btn-icon btn-delete-kerusakan"
                                                data-id="{{ $ba->id }}" data-nama="{{ $ba->nomor_ba }}"
                                                title="Hapus Berita Acara" style="color: var(--danger, #ef4444);">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="padding: var(--space-11); text-align: center; color: var(--text-muted);">
                                    <i class="fas fa-file-contract mb-2" style="font-size: var(--text-3xl); opacity: 0.5;"></i>
                                    <div>Belum ada berita acara kerusakan alat.</div>
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

        {{-- Modal Form Berita Acara --}}
        <div id="modalKerusakan" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 780px; max-height: 92vh; overflow-y: auto; padding: var(--space-10); border-radius: var(--radius-lg);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-9);">
                    <h3 id="modalKerusakanTitle" style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-color);">Berita Acara Kerusakan Alat</h3>
                    <button type="button" data-close="modalKerusakan" style="background: none; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;" title="Tutup">&times;</button>
                </div>

                <form id="formKerusakan" method="POST" action="{{ route('dashboard.laboran.kerusakan.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="kerusakanMethod" value="POST">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Lokasi / Ruang Praktik</label>
                            <select name="ruang_lab_nama" id="krLokasi" class="form-control">
                                <option value="">-- Pilih --</option>
                                @foreach ($lokasiOptions as $lok)
                                    <option value="{{ $lok }}">{{ $lok }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Tingkat Kerusakan <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <select name="tingkat_kerusakan" id="krTingkat" class="form-control" required>
                                @foreach ($tingkatList as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Daftar alat yang dilaporkan (bisa lebih dari satu) --}}
                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-4); margin-bottom: var(--space-3); flex-wrap: wrap;">
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin: 0; color: var(--text-color);">
                                Daftar Alat yang Dilaporkan <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <button type="button" class="btn btn-outline" id="krTambahAlat"
                                style="height: 34px; padding: 0 var(--space-5); font-size: var(--text-sm);">
                                <i class="fas fa-plus me-1"></i> Tambah Alat
                            </button>
                        </div>

                        <div style="overflow-x: auto; border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                            <table class="table" style="width: 100%; margin: 0; min-width: 680px;">
                                <thead>
                                    <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255, 255, 255, 0.02);">
                                        <th style="padding: var(--space-4) var(--space-5); font-size: var(--text-xs); text-transform: uppercase; color: var(--text-muted); text-align: left; min-width: 200px;">Alat Inventaris</th>
                                        <th style="padding: var(--space-4) var(--space-5); font-size: var(--text-xs); text-transform: uppercase; color: var(--text-muted); text-align: left; min-width: 160px;">Nama Alat</th>
                                        <th style="padding: var(--space-4) var(--space-5); font-size: var(--text-xs); text-transform: uppercase; color: var(--text-muted); text-align: left; width: 90px;">Jumlah</th>
                                        <th style="padding: var(--space-4) var(--space-5); font-size: var(--text-xs); text-transform: uppercase; color: var(--text-muted); text-align: left; width: 100px;">Satuan</th>
                                        <th style="padding: var(--space-4) var(--space-5); font-size: var(--text-xs); text-transform: uppercase; color: var(--text-muted); text-align: left; width: 150px;">Tingkat</th>
                                        <th style="padding: var(--space-4) var(--space-5); width: 44px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="krAlatBody"></tbody>
                            </table>
                        </div>

                        <div id="krAlatEmpty"
                            style="padding: var(--space-6); text-align: center; font-size: var(--text-sm); color: var(--text-muted); border: 1px dashed var(--border-color); border-radius: var(--radius-md); margin-top: var(--space-4);">
                            Belum ada alat. Klik <strong>Tambah Alat</strong> untuk memilih alat dari inventaris.
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Tanggal Kejadian <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="date" name="tanggal_kejadian" id="krKejadian" class="form-control" required>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Tanggal BA</label>
                            <input type="date" name="tanggal_ba" id="krBa" class="form-control">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Kronologi Kejadian</label>
                        <textarea name="kronologi" id="krKronologi" class="form-control" rows="3" placeholder="Uraian kronologis kejadian kerusakan secara berurutan..."></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Penyebab Kerusakan</label>
                        <textarea name="penyebab" id="krPenyebab" class="form-control" rows="2" placeholder="Analisis penyebab: kelalaian, keausan, kesalahan prosedur, dsb..."></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Pelaku / Penanggung Jawab</label>
                            <select name="pelaku_tipe" id="krPelakuTipe" class="form-control" style="margin-bottom: var(--space-3);">
                                @foreach ($pelakuList as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="pelaku_nama" id="krPelakuNama" class="form-control" placeholder="Nama pelaku (jika diketahui)">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Saksi</label>
                            <input type="text" name="saksi_1_nama" id="krSaksi1" class="form-control" placeholder="Saksi 1" style="margin-bottom: var(--space-3);">
                            <input type="text" name="saksi_2_nama" id="krSaksi2" class="form-control" placeholder="Saksi 2">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Tindakan Lanjut</label>
                        <textarea name="tindakan_lanjut" id="krTindakan" class="form-control" rows="2" placeholder="Perbaikan, penggantian, atau tindakan lain..."></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Estimasi Biaya (Rp)</label>
                            <input type="number" step="0.01" min="0" name="estimasi_biaya" id="krBiaya" class="form-control" placeholder="0">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Status</label>
                            <select name="status" id="krStatus" class="form-control">
                                @foreach ($statusList as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div style="display: flex; align-items: flex-end;">
                            <label style="display: flex; align-items: center; gap: var(--space-3); font-size: var(--text-sm); color: var(--text-color); cursor: pointer; margin: 0; padding-bottom: var(--space-3);">
                                <input type="checkbox" name="perlu_usulan_penggantian" id="krPerlu" value="1" style="cursor: pointer;">
                                Perlu Usulan Penggantian
                            </label>
                        </div>
                    </div>

                    <div class="form-group" id="krCatatanWrap" style="margin-bottom: var(--space-9); display: none;">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Catatan Verifikasi</label>
                        <textarea name="catatan_verifikasi" id="krCatatan" class="form-control" rows="2"></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: var(--space-4);">
                        <button type="button" class="btn btn-secondary" data-close="modalKerusakan">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan Berita Acara</button>
                    </div>

                    {{-- Cetakan baris alat (diisi oleh laboran-kerusakan.js) --}}
                    <template id="krAlatTemplate">
                        <tr class="kr-alat-row" style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: var(--space-4) var(--space-5);">
                                <select name="alat[__IDX__][item_id]" class="form-control kr-alat-item">
                                    <option value="">-- Pilih dari inventaris --</option>
                                    @foreach ($itemOptions as $io)
                                        <option value="{{ $io->id }}" data-nama="{{ $io->nama_item }}"
                                            data-kode="{{ $io->kode_item }}" data-satuan="{{ $io->satuan }}"
                                            data-ruang="{{ $io->ruang_lab_nama }}">{{ $io->nama_item }} ({{ $io->kode_item }})</option>
                                    @endforeach
                                </select>
                            </td>
                            <td style="padding: var(--space-4) var(--space-5);">
                                <input type="text" name="alat[__IDX__][nama_alat]" class="form-control kr-alat-nama" placeholder="Nama alat">
                            </td>
                            <td style="padding: var(--space-4) var(--space-5);">
                                <input type="number" step="0.01" min="0.01" name="alat[__IDX__][jumlah_rusak]" class="form-control kr-alat-jumlah" value="1">
                            </td>
                            <td style="padding: var(--space-4) var(--space-5);">
                                <input type="text" name="alat[__IDX__][satuan]" class="form-control kr-alat-satuan" value="unit">
                            </td>
                            <td style="padding: var(--space-4) var(--space-5);">
                                <select name="alat[__IDX__][tingkat_kerusakan]" class="form-control kr-alat-tingkat">
                                    @foreach ($tingkatList as $k => $v)
                                        <option value="{{ $k }}">{{ $v }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td style="padding: var(--space-4) var(--space-5); text-align: center;">
                                <button type="button" class="btn-icon kr-alat-hapus" title="Hapus alat"
                                    style="color: var(--danger, #ef4444);"><i class="fas fa-trash-can"></i></button>
                            </td>
                        </tr>
                    </template>
                </form>
            </div>
        </div>

    </div>

        @push('scripts')
            <script src="{{ asset('js/laboran-common.js') }}?v={{ file_exists(public_path('js/laboran-common.js')) ? filemtime(public_path('js/laboran-common.js')) : time() }}"></script>
            <script src="{{ asset('js/laboran-kerusakan.js') }}?v={{ file_exists(public_path('js/laboran-kerusakan.js')) ? filemtime(public_path('js/laboran-kerusakan.js')) : time() }}"></script>
        @endpush
    @endsection
