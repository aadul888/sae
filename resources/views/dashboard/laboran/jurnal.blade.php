@extends('layouts.dashboard')

@section('title', 'Jurnal Kegiatan Laboran — SAE')
@section('dash_title', 'Jurnal Kegiatan')

@section('content')
    @include('dashboard.laboran.partials.header-actions')
    <div class="dash-content-inner">
        {{-- Banner (Tupoksi #8) --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap; margin-bottom: var(--space-9);">
            <div style="display: flex; align-items: center; gap: var(--space-7);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(16,185,129,0.12); color: var(--success, #10b981); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl); flex-shrink: 0;">
                    <i class="fas fa-book"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--text-color); margin: 0;">
                        Jurnal Kegiatan Harian Laboran
                    </h2>
                    <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-1);">
                        Bukti fisik Tupoksi #8 — kategori kegiatan dipetakan ke 9 indikator tupoksi.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions dash-banner-access">
                <a href="{{ route('dashboard.laboran.jurnal.cetak', ['dari' => $dari, 'sampai' => $sampai]) }}" target="_blank" class="btn btn-outline"
                    title="Cetak Jurnal Kegiatan" aria-label="Cetak Jurnal Kegiatan">
                    <i class="fas fa-print"></i>
                </a>
                @if ($canCreate)
                    <button type="button" class="btn btn-primary" id="btnOpenJurnal"
                        title="Catat Kegiatan Baru" aria-label="Catat Kegiatan Baru">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>

        {{-- Statistik --}}
        <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: var(--space-9); gap: var(--space-7);">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);"><i class="fas fa-list"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statTotal, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Kegiatan Tercatat</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);"><i class="fas fa-clock"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ intdiv($statMenit, 60) }}j {{ $statMenit % 60 }}m</div>
                    <div class="dash-stat-label">Akumulasi Durasi</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);"><i class="fas fa-calendar-day"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statHari, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Hari Aktif</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.15); color: var(--purple, #8b5cf6);"><i class="fas fa-tags"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statKategori, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Kategori Terpakai</div>
                </div>
            </div>
        </div>

        {{-- Rekap per kategori (peta bukti tupoksi) --}}
        @if ($rekapKategori->count() > 0)
            <div class="card" style="padding: var(--space-9); margin-bottom: var(--space-9);">
                <h3 style="font-size: var(--text-md); font-weight: var(--font-bold); margin: 0 0 var(--space-2) 0; color: var(--text-color); display: flex; align-items: center; gap: var(--space-3);">
                    <i class="fas fa-chart-pie text-primary"></i> Sebaran Kegiatan per Indikator Tupoksi
                </h3>
                <p style="font-size: var(--text-xs); color: var(--text-muted); margin: 0 0 var(--space-7) 0;">
                    Setiap kategori kegiatan terhubung ke nomor indikator supervisi — memudahkan pembuktian saat penilaian.
                </p>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: var(--space-5);">
                    @foreach ($rekapKategori as $rk)
                        @php
                            $meta = $kategoriList[$rk->kategori] ?? [ucfirst($rk->kategori), 0];
                            $nomor = $meta[1];
                        @endphp
                        <div style="padding: var(--space-5) var(--space-6); border-radius: var(--radius-md); background: var(--bg-hover); border: 1px solid var(--border-color);">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-3); margin-bottom: var(--space-2);">
                                <span style="font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-color);">{{ $meta[0] }}</span>
                                @if ($nomor > 0)
                                    <span class="badge badge-outline" style="font-size: var(--text-2xs);" title="Indikator Tupoksi">#{{ $nomor }}</span>
                                @endif
                            </div>
                            <div style="display: flex; gap: var(--space-5); font-size: var(--text-xs); color: var(--text-muted);">
                                <span><strong style="color: var(--text-color);">{{ $rk->jumlah }}</strong> kegiatan</span>
                                <span><strong style="color: var(--text-color);">{{ intdiv((int) $rk->menit, 60) }}j</strong> durasi</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Datatable --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.laboran.jurnal.index') }}" style="margin: 0;">
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

                            <select name="kategori" id="filterKategori" class="toolbar-filter-select" style="height: 38px;" title="Filter Kategori">
                                <option value="">Semua Kategori</option>
                                @foreach ($kategoriList as $k => $v)
                                    <option value="{{ $k }}" {{ $kategoriFilter === $k ? 'selected' : '' }}>{{ $v[0] }}</option>
                                @endforeach
                            </select>

                            <x-sae.toolbar-date-range :from-value="$dari" :to-value="$sampai" />

                            @if ($search || $kategoriFilter)
                <a href="{{ route('dashboard.laboran.jurnal.index') }}" class="btn btn-outline"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                                    title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="q" id="liveSearch" value="{{ $search }}" placeholder="Cari kegiatan, uraian, hasil, atau guru..." autocomplete="off">
                            <button type="button" id="clearSearch" class="clear-search {{ $search ? 'visible' : '' }}" title="Hapus pencarian"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255, 255, 255, 0.02);">
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Tanggal / Jam</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Kegiatan</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Kategori</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Durasi</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $j)
                            @php $meta = $kategoriList[$j->kategori] ?? [ucfirst($j->kategori), 0]; @endphp
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td data-label="Tanggal" style="padding: var(--space-6) var(--space-7);">
                                    <div style="font-weight: var(--font-bold); color: var(--text-color); font-size: var(--text-sm);">
                                        {{ \Carbon\Carbon::parse($j->tanggal)->translatedFormat('d M Y') }}
                                    </div>
                                    @if ($j->jam_mulai)
                                        <div style="font-size: var(--text-xs); color: var(--text-muted); font-family: var(--font-family-mono);">
                                            {{ substr($j->jam_mulai, 0, 5) }}{{ $j->jam_selesai ? '–' . substr($j->jam_selesai, 0, 5) : '' }}
                                        </div>
                                    @endif
                                    <div style="font-size: var(--text-2xs); color: var(--text-muted);">{{ $shiftList[$j->shift] ?? $j->shift }}</div>
                                    @if ($j->singkatan || $j->nama_jurusan)
                                        <span class="badge badge-outline" style="font-size: var(--text-2xs); margin-top: var(--space-2);">{{ $j->singkatan ?: $j->nama_jurusan }}</span>
                                    @endif
                                </td>
                                <td data-label="Kegiatan" style="padding: var(--space-6) var(--space-7);">
                                    <div style="font-weight: var(--font-bold); color: var(--text-color); font-size: var(--text-base);">{{ $j->kegiatan }}</div>
                                    @if ($j->uraian)
                                        <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ \Illuminate\Support\Str::limit($j->uraian, 90) }}</div>
                                    @endif
                                    @if ($j->hasil)
                                        <div style="font-size: var(--text-xs); color: var(--success, #10b981); margin-top: var(--space-1);"><i class="fas fa-check me-1"></i>{{ \Illuminate\Support\Str::limit($j->hasil, 70) }}</div>
                                    @endif
                                    @if ($j->ruang_lab_nama)
                                        <div style="font-size: var(--text-2xs); color: var(--text-muted);"><i class="fas fa-location-dot me-1"></i>{{ $j->ruang_lab_nama }}</div>
                                    @endif
                                </td>
                                <td data-label="Kategori" style="padding: var(--space-6) var(--space-7); text-align: center;">
                                    <span class="badge badge-outline" style="font-size: var(--text-2xs);">{{ $meta[0] }}</span>
                                    @if (($meta[1] ?? 0) > 0)
                                        <div style="font-size: var(--text-2xs); color: var(--primary); margin-top: var(--space-1);">Indikator #{{ $meta[1] }}</div>
                                    @endif
                                </td>
                                <td data-label="Durasi" style="padding: var(--space-6) var(--space-7); text-align: center; font-size: var(--text-sm); font-family: var(--font-family-mono);">
                                    {{ intdiv((int) $j->durasi_menit, 60) }}j {{ (int) $j->durasi_menit % 60 }}m
                                </td>
                                <td data-label="Aksi" style="padding: var(--space-6) var(--space-7); text-align: right;">
                                    <div class="table-actions" style="justify-content: flex-end; gap: var(--space-3);">
                                        @if ($canUpdate)
                                            <button type="button" class="btn-icon btn-edit-jurnal"
                                                data-id="{{ $j->id }}" data-tanggal="{{ $j->tanggal }}"
                                                data-lokasi="{{ $j->ruang_lab_nama }}" data-shift="{{ $j->shift }}"
                                                data-kegiatan="{{ $j->kegiatan }}" data-kategori="{{ $j->kategori }}"
                                                data-mulai="{{ $j->jam_mulai ? substr($j->jam_mulai, 0, 5) : '' }}"
                                                data-selesai="{{ $j->jam_selesai ? substr($j->jam_selesai, 0, 5) : '' }}"
                                                data-uraian="{{ $j->uraian }}" data-hasil="{{ $j->hasil }}"
                                                data-kendala="{{ $j->kendala }}" data-tindak="{{ $j->tindak_lanjut }}"
                                                data-siswa="{{ $j->jumlah_siswa }}" data-guru="{{ $j->guru_terkait }}"
                                                data-catatan="{{ $j->catatan }}" data-status="{{ $j->status }}"
                                                title="Ubah Jurnal">
                                                <i class="fas fa-pen-to-square" style="color: var(--primary);"></i>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <button type="button" class="btn-icon btn-delete-jurnal"
                                                data-id="{{ $j->id }}" data-nama="{{ $j->kegiatan }}"
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
                                    <i class="fas fa-book mb-2" style="font-size: var(--text-3xl); opacity: 0.5;"></i>
                                    <div>Belum ada jurnal kegiatan pada rentang ini.</div>
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

        {{-- Modal Jurnal --}}
        <div id="modalJurnal" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 720px; max-height: 92vh; overflow-y: auto; padding: var(--space-10); border-radius: var(--radius-lg);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-9);">
                    <h3 id="modalJurnalTitle" style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-color);">Catat Jurnal Kegiatan</h3>
                    <button type="button" data-close="modalJurnal" style="background: none; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;" title="Tutup">&times;</button>
                </div>

                <form id="formJurnal" method="POST" action="{{ route('dashboard.laboran.jurnal.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="jurnalMethod" value="POST">

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Tanggal <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="date" name="tanggal" id="jrTanggal" class="form-control" required>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Shift</label>
                            <select name="shift" id="jrShift" class="form-control">
                                @foreach ($shiftList as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Lokasi</label>
                            <select name="ruang_lab_nama" id="jrLokasi" class="form-control">
                                <option value="">-- Pilih --</option>
                                @foreach ($lokasiOptions as $lok)
                                    <option value="{{ $lok }}">{{ $lok }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 2fr 1.2fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Nama Kegiatan <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="text" name="kegiatan" id="jrKegiatan" class="form-control" required placeholder="Contoh: Menyiapkan alat praktik Kimia">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Kategori Tupoksi <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <select name="kategori" id="jrKategori" class="form-control" required>
                                @foreach ($kategoriList as $k => $v)
                                    <option value="{{ $k }}">{{ $v[0] }}{{ $v[1] > 0 ? ' (#' . $v[1] . ')' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Jam Mulai</label>
                            <input type="time" name="jam_mulai" id="jrMulai" class="form-control">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Jam Selesai</label>
                            <input type="time" name="jam_selesai" id="jrSelesai" class="form-control">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Jml Siswa</label>
                            <input type="number" min="0" max="1000" name="jumlah_siswa" id="jrSiswa" class="form-control">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Status</label>
                            <select name="status" id="jrStatus" class="form-control">
                                @foreach ($statusList as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Uraian Kegiatan</label>
                        <textarea name="uraian" id="jrUraian" class="form-control" rows="2" placeholder="Rincian pekerjaan yang dilakukan..."></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Hasil</label>
                            <textarea name="hasil" id="jrHasil" class="form-control" rows="2" placeholder="Hasil yang dicapai..."></textarea>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Kendala</label>
                            <textarea name="kendala" id="jrKendala" class="form-control" rows="2" placeholder="Kendala yang dihadapi..."></textarea>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Tindak Lanjut</label>
                            <textarea name="tindak_lanjut" id="jrTindak" class="form-control" rows="2" placeholder="Rencana tindak lanjut..."></textarea>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Guru Terkait</label>
                            <input type="text" name="guru_terkait" id="jrGuru" class="form-control" placeholder="Nama guru yang terlibat">
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin: var(--space-5) 0 var(--space-3) 0; color: var(--text-color);">Catatan</label>
                            <input type="text" name="catatan" id="jrCatatan" class="form-control" placeholder="Catatan tambahan">
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: var(--space-4);">
                        <button type="button" class="btn btn-secondary" data-close="modalJurnal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        @push('scripts')
            <script src="{{ asset('js/laboran-common.js') }}?v={{ file_exists(public_path('js/laboran-common.js')) ? filemtime(public_path('js/laboran-common.js')) : time() }}"></script>
            <script src="{{ asset('js/laboran-jurnal.js') }}?v={{ file_exists(public_path('js/laboran-jurnal.js')) ? filemtime(public_path('js/laboran-jurnal.js')) : time() }}"></script>
        @endpush
    </div>
@endsection
