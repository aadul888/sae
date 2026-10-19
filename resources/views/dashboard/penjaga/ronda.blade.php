@extends('layouts.dashboard')

@section('title', 'Buku Jaga Malam & Ronda - Fasilitas')

@section('content')
    <div class="content-wrapper">
        <!-- 1. Header Banner & Actions -->
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div
                    style="width: 48px; height: 48px; border-radius: 12px; background: rgba(59,130,246,0.12); color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0;">
                    <i class="fas fa-moon"></i>
                </div>
                <div>
                    <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--text-color); margin: 0;">
                        Buku Jaga Malam &amp; Ronda Penjaga Sekolah
                    </h2>
                    <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">
                        Pencatatan kontrol keliling malam hari, pengecekan kunci pintu/jendela, lampu penerangan, dan
                        keamanan aset.
                    </div>
                </div>
            </div>

            <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                @if ($canCreate)
                    <button type="button" class="btn btn-primary" onclick="openModalRonda()"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem;"
                        title="Catat Kontrol Ronda Malam">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>

        <!-- 2. Stat Grid Baku SAE -->
        <div class="dash-stat-grid" style="margin-bottom: 20px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: #3b82f6;">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['total'] }}</div>
                    <div class="dash-stat-label">Total Kontrol</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: #6366f1;">
                    <i class="fas fa-cloud-moon"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['malam_ini'] }}</div>
                    <div class="dash-stat-label">Ronda Malam Ini</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['kondusif'] }}</div>
                    <div class="dash-stat-label">Kondusif</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239,68,68,0.12); color: #ef4444;">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['insiden_keamanan'] }}</div>
                    <div class="dash-stat-label">Perhatian Khusus</div>
                </div>
            </div>
        </div>

        {{-- Unified Datatable Ronda (Toolbar + Table + Pagination dalam 1 Card Terpadu) --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            {{-- 1. Toolbar Header (Entries, Filters & Live Search) --}}
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.penjaga.ronda.index') }}" id="filterForm"
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

                            <select name="situasi_keamanan" class="toolbar-filter-select"
                                style="height: 38px; min-width: 150px;">
                                <option value="">Semua Situasi</option>
                                <option value="kondusif" {{ $situasi === 'kondusif' ? 'selected' : '' }}>Kondusif</option>
                                <option value="orang_mencurigakan"
                                    {{ $situasi === 'orang_mencurigakan' ? 'selected' : '' }}>Orang
                                    Mencurigakan</option>
                                <option value="kebocoran_air" {{ $situasi === 'kebocoran_air' ? 'selected' : '' }}>
                                    Kebocoran
                                    Air/Lainnya</option>
                            </select>

                            @if ($search || $situasi || $tanggal)
                                <a href="{{ route('dashboard.penjaga.ronda.index') }}"
                                    class="btn btn-outline btn-reset-filter"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;"
                                    title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="search" id="searchRonda" value="{{ $search }}"
                                placeholder="Cari zona kontrol, catatan, petugas..." autocomplete="off">
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
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Waktu Kontrol</th>
                            <th
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Zona Kontrol</th>
                            <th
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Pintu & Jendela</th>
                            <th
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Penerangan Lampu</th>
                            <th
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Situasi Keamanan</th>
                            <th
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Catatan & Petugas</th>
                            <th
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $item)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 18px;">
                                    <div style="font-weight: 600; color: var(--text-heading);">
                                        {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}</div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">Pukul
                                        {{ substr($item->jam_kontrol, 0, 5) }} WIB</div>
                                </td>
                                <td style="padding: 12px 18px;">
                                    <span
                                        style="font-weight: 600; color: var(--text-heading);">{{ $item->zona_kontrol }}</span>
                                </td>
                                <td style="padding: 12px 18px;">
                                    @if ($item->status_pintu_jendela === 'terkunci_rapi')
                                        <span class="badge"
                                            style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);">Terkunci
                                            Rapi</span>
                                    @elseif ($item->status_pintu_jendela === 'ditemukan_terbuka')
                                        <span class="badge"
                                            style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">Ditemukan
                                            Terbuka</span>
                                    @else
                                        <span class="badge"
                                            style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3);">Kunci
                                            Rusak</span>
                                    @endif
                                </td>
                                <td style="padding: 12px 18px;">
                                    @if ($item->status_lampu === 'menyala_sesuai' || $item->status_lampu === 'normal')
                                        <span class="badge"
                                            style="background: rgba(16, 185, 129, 0.15); color: #10b981;">Normal /
                                            Sesuai</span>
                                    @elseif ($item->status_lampu === 'mati_sebagian')
                                        <span class="badge"
                                            style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">Mati
                                            Sebagian</span>
                                    @else
                                        <span class="badge"
                                            style="background: rgba(239, 68, 68, 0.15); color: #ef4444;">Konsleting /
                                            Masalah</span>
                                    @endif
                                </td>
                                <td style="padding: 12px 18px;">
                                    @if ($item->situasi_keamanan === 'kondusif')
                                        <span class="badge"
                                            style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);">Kondusif</span>
                                    @else
                                        <span class="badge"
                                            style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">{{ ucwords(str_replace('_', ' ', $item->situasi_keamanan)) }}</span>
                                    @endif
                                </td>
                                <td style="padding: 12px 18px; font-size: 0.82rem;">
                                    <div style="color: var(--text-heading);">{{ $item->catatan_penjaga ?: '-' }}</div>
                                    <div style="color: var(--text-muted); margin-top: 2px;">Petugas:
                                        {{ $item->nama_petugas ?: 'Penjaga Sekolah' }}</div>
                                </td>
                                <td style="padding: 12px 18px; text-align: center;">
                                    <div class="table-actions" style="display: flex; gap: 6px; justify-content: center;">
                                        @if ($canUpdate)
                                            <button type="button" class="btn-icon" title="Edit"
                                                onclick="editRonda({{ json_encode($item) }})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <form action="{{ route('dashboard.penjaga.ronda.destroy', $item->id) }}"
                                                method="POST" data-confirm="delete"
                                                data-name="Log {{ $item->zona_kontrol }}">
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
                                    <i class="fas fa-shield-alt"
                                        style="font-size: 2rem; margin-bottom: 8px; opacity: 0.4;"></i>
                                    <p style="margin: 0;">Belum ada data kontrol ronda malam.</p>
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
                    style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center;">
                    <div class="card"
                        style="width: 100%; max-width: 550px; margin: 20px; max-height: 90vh; overflow-y: auto;">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
                            <h3 id="modalRondaTitle"
                                style="font-size: 1.15rem; font-weight: 700; margin: 0; color: var(--text-heading);">Catat
                                Kontrol
                                Ronda</h3>
                            <button type="button" onclick="closeModalRonda()"
                                style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: var(--text-muted);">&times;</button>
                        </div>

                        <form id="formRonda" method="POST" action="{{ route('dashboard.penjaga.ronda.store') }}"
                            data-store-url="{{ route('dashboard.penjaga.ronda.store') }}"
                            data-update-url="{{ url('/dashboard/penjaga/ronda') }}/:id">
                            @csrf
                            <input type="hidden" name="_method" id="rondaMethod" value="POST">

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                                <div>
                                    <label class="form-label">Tanggal <span style="color:red;">*</span></label>
                                    <input type="date" name="tanggal" id="ronda_tanggal" class="form-control"
                                        value="{{ date('Y-m-d') }}" required>
                                </div>
                                <div>
                                    <label class="form-label">Jam Kontrol <span style="color:red;">*</span></label>
                                    <input type="time" name="jam_kontrol" id="ronda_jam" class="form-control"
                                        value="{{ date('H:i') }}" required>
                                </div>
                            </div>

                            <div style="margin-bottom: 12px;">
                                <div
                                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                    <label class="form-label" style="margin: 0;">Zona / Titik Kontrol <span
                                            style="color:red;">*</span></label>
                                    <span style="font-size: 0.74rem; color: var(--text-muted);"><i
                                            class="fas fa-building me-1"></i>Master Ruang & Sarpras</span>
                                </div>
                                <input list="listZonaRonda" name="zona_kontrol" id="ronda_zona" class="form-control"
                                    placeholder="Pilih dari daftar Ruang Sarpras atau ketik titik kontrol..." required>
                                <datalist id="listZonaRonda">
                                    @foreach ($daftarRuang as $rng)
                                        <option value="{{ $rng->nama_ruang }} ({{ $rng->gedung }})">
                                            {{ $rng->nama_ruang }} -
                                            Lantai
                                            {{ $rng->lantai }}</option>
                                    @endforeach
                                </datalist>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                                <div>
                                    <label class="form-label">Pintu & Jendela <span style="color:red;">*</span></label>
                                    <select name="status_pintu_jendela" id="ronda_pintu" class="form-control" required>
                                        <option value="terkunci_rapi">Terkunci Rapi</option>
                                        <option value="ditemukan_terbuka">Ditemukan Terbuka</option>
                                        <option value="kunci_rusak">Kunci Rusak</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Penerangan Lampu <span style="color:red;">*</span></label>
                                    <select name="status_lampu" id="ronda_lampu" class="form-control" required>
                                        <option value="menyala_sesuai">Menyala Sesuai / Normal</option>
                                        <option value="mati_sebagian">Mati Sebagian</option>
                                        <option value="konsleting">Konsleting / Rusak</option>
                                    </select>
                                </div>
                            </div>

                            <div style="margin-bottom: 12px;">
                                <label class="form-label">Situasi Keamanan <span style="color:red;">*</span></label>
                                <select name="situasi_keamanan" id="ronda_situasi" class="form-control" required>
                                    <option value="kondusif">Kondusif & Aman</option>
                                    <option value="orang_mencurigakan">Orang Mencurigakan</option>
                                    <option value="kebocoran_air">Kebocoran Air / Masalah Bangunan</option>
                                </select>
                            </div>

                            <div style="margin-bottom: 16px;">
                                <label class="form-label">Catatan Penjaga</label>
                                <textarea name="catatan_penjaga" id="ronda_catatan" class="form-control" rows="3"
                                    placeholder="Catatan temuan selama kontrol ronda..."></textarea>
                            </div>

                            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                                <button type="button" class="btn btn-secondary"
                                    onclick="closeModalRonda()">Batal</button>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i>
                                    Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endsection

            @push('scripts')
                <script
                    src="{{ asset('js/penjaga-ronda.js') }}?v={{ file_exists(public_path('js/penjaga-ronda.js')) ? filemtime(public_path('js/penjaga-ronda.js')) : time() }}">
                </script>
            @endpush
