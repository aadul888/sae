@extends('layouts.dashboard')

@section('title', 'Buku Tamu Pos Keamanan')

@section('content')
    <div class="content-wrapper">
        <!-- 1. Header Banner & Actions -->
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg, 12px); background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); flex-shrink: 0;">
                    <i class="fas fa-book-open"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                        Buku Tamu Pos Keamanan
                    </h2>
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        Pencatatan identitas, tujuan kunjungan, dan pemantauan tamu yang berada di lingkungan sekolah.
                    </div>
                </div>
            </div>

            <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                @if ($canCreate)
                    <button type="button" class="btn btn-primary" onclick="openModalTamu()"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                        title="Catat Tamu Masuk">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>

        <!-- 2. Stat Grid Baku SAE -->
        <div class="dash-stat-grid" style="margin-bottom: 20px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6);">
                    <i class="fas fa-address-book"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['total'] }}</div>
                    <div class="dash-stat-label">Total Kunjungan</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6);">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['hari_ini'] }}</div>
                    <div class="dash-stat-label">Tamu Hari Ini</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b);">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['di_lokasi'] }}</div>
                    <div class="dash-stat-label">Sedang di Lokasi</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: var(--success, #10b981);">
                    <i class="fas fa-door-open"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['sudah_keluar'] }}</div>
                    <div class="dash-stat-label">Sudah Keluar</div>
                </div>
            </div>
        </div>

        {{-- Unified Datatable Keamanan Buku Tamu (Toolbar + Table + Pagination dalam 1 Card Terpadu) --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            {{-- 1. Toolbar Header (Entries, Filters & Live Search) --}}
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.keamanan.buku-tamu.index') }}" id="filterForm"
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

                            <select name="status" class="toolbar-filter-select" style="height: 38px; min-width: 150px;">
                                <option value="">Semua Status</option>
                                <option value="berada_di_lokasi" {{ $status === 'berada_di_lokasi' ? 'selected' : '' }}>Di
                                    Lokasi
                                </option>
                                <option value="sudah_keluar" {{ $status === 'sudah_keluar' ? 'selected' : '' }}>Sudah
                                    Keluar
                                </option>
                            </select>

                            @if ($search || $status || $tanggal)
                                <a href="{{ route('dashboard.keamanan.buku-tamu.index') }}"
                                    class="btn btn-outline btn-reset-filter"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                    title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="search" id="searchBukuTamu" value="{{ $search }}"
                                placeholder="Cari nama tamu, instansi..." autocomplete="off">
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
                                Tanggal & Waktu</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Nama Tamu & Instansi</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Tujuan & Keperluan</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Visitor / Kendaraan</th>
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
                                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                        In: <strong>{{ substr($item->jam_masuk, 0, 5) }}</strong>
                                        @if ($item->jam_keluar)
                                            &bull; Out: <strong>{{ substr($item->jam_keluar, 0, 5) }}</strong>
                                        @endif
                                    </div>
                                </td>
                                <td style="padding: 12px 18px;">
                                    <div style="font-weight: 600; color: var(--text-heading);">{{ $item->nama_tamu }}
                                    </div>
                                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                        {{ $item->instansi_asal ?: 'Umum / Pribadi' }}
                                        @if ($item->nomor_kontak)
                                            &bull; Telp: {{ $item->nomor_kontak }}
                                        @endif
                                    </div>
                                </td>
                                <td style="padding: 12px 18px;">
                                    <div style="font-weight: 600; color: var(--text-heading); font-size: var(--text-base, 0.875rem);">Bertemu:
                                        {{ $item->tujuan_bertemu }}</div>
                                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">Hal: {{ $item->keperluan }}
                                    </div>
                                </td>
                                <td style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem);">
                                    @if ($item->nomor_kartu_visitor)
                                        <div><span class="badge"
                                                style="background: rgba(99, 102, 241, 0.15); color: var(--primary-light, #6366f1);">Kartu:
                                                {{ $item->nomor_kartu_visitor }}</span></div>
                                    @endif
                                    @if ($item->nomor_polisi_kendaraan)
                                        <div style="color: var(--text-muted); margin-top: 2px;">Nopol:
                                            {{ $item->nomor_polisi_kendaraan }}</div>
                                    @endif
                                </td>
                                <td style="padding: 12px 18px;">
                                    @if ($item->status === 'berada_di_lokasi')
                                        <span class="badge"
                                            style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b); border: 1px solid rgba(245, 158, 11, 0.3);">Di
                                            Lokasi</span>
                                    @else
                                        <span class="badge"
                                            style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981); border: 1px solid rgba(16, 185, 129, 0.3);">Sudah
                                            Keluar</span>
                                    @endif
                                </td>
                                <td style="padding: 12px 18px; text-align: center;">
                                    <div class="table-actions" style="display: flex; gap: 6px; justify-content: center;">
                                        @if ($item->status === 'berada_di_lokasi')
                                            <form action="{{ route('dashboard.keamanan.buku-tamu.checkout', $item->id) }}"
                                                method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn-icon" title="Checkout Tamu Keluar"
                                                    style="color: var(--warning, #f59e0b);">
                                                    <i class="fas fa-arrow-right-from-bracket"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if ($canUpdate)
                                            <button type="button" class="btn-icon" title="Edit"
                                                onclick="editTamu({{ json_encode($item) }})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <form action="{{ route('dashboard.keamanan.buku-tamu.destroy', $item->id) }}"
                                                method="POST" data-confirm="delete" data-name="{{ $item->nama_tamu }}">
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
                                <td colspan="6" style="text-align: center; padding: 36px; color: var(--text-muted);">
                                    <i class="fas fa-user-friends"
                                        style="font-size: var(--text-3xl, 1.875rem); margin-bottom: 8px; opacity: 0.4;"></i>
                                    <p style="margin: 0;">Belum ada catatan tamu.</p>
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

        {{-- Modal Tambah / Edit Tamu --}}
        <div id="modalTamu"
            style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center;">
            <div class="card" style="width: 100%; max-width: 600px; margin: 20px; max-height: 90vh; overflow-y: auto;">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
                    <h3 id="modalTamuTitle"
                        style="font-size: var(--text-xl, 1.25rem); font-weight: 700; margin: 0; color: var(--text-heading);">
                        Catat Tamu Masuk
                    </h3>
                    <button type="button" onclick="closeModalTamu()"
                        style="background: none; border: none; font-size: var(--text-xl, 1.25rem); cursor: pointer; color: var(--text-muted);">&times;</button>
                </div>

                <form id="formTamu" method="POST" action="{{ route('dashboard.keamanan.buku-tamu.store') }}"
                    data-store-url="{{ route('dashboard.keamanan.buku-tamu.store') }}"
                    data-update-url="{{ url('/dashboard/keamanan/buku-tamu') }}/:id">
                    @csrf
                    <input type="hidden" name="_method" id="tamuMethod" value="POST">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label class="form-label">Tanggal <span style="color:red;">*</span></label>
                            <input type="date" name="tanggal" id="tamu_tanggal" class="form-control"
                                value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div>
                            <label class="form-label">Jam Masuk <span style="color:red;">*</span></label>
                            <input type="time" name="jam_masuk" id="tamu_jam_masuk" class="form-control"
                                value="{{ date('H:i') }}" required>
                        </div>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <label class="form-label">Nama Tamu <span style="color:red;">*</span></label>
                        <input type="text" name="nama_tamu" id="tamu_nama" class="form-control"
                            placeholder="Nama lengkap tamu" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label class="form-label">Instansi / Lembaga Asal</label>
                            <input type="text" name="instansi_asal" id="tamu_instansi" class="form-control"
                                placeholder="Contoh: Dinas Pendidikan, Orang Tua">
                        </div>
                        <div>
                            <label class="form-label">Nomor Kontak / HP</label>
                            <input type="text" name="nomor_kontak" id="tamu_kontak" class="form-control"
                                placeholder="08xxxxxxxx">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                <label class="form-label" style="margin: 0;">Tujuan Bertemu <span
                                        style="color:red;">*</span></label>
                                <span style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);"><i
                                        class="fas fa-user-tie me-1"></i>GTK / Ruang</span>
                            </div>
                            <input type="search" list="listTujuanBertemu" name="tujuan_bertemu" id="tamu_tujuan"
                                class="form-control" placeholder="Cari GTK/ruang atau ketik..." autocomplete="off"
                                required data-people-search="{{ route('dashboard.people.search') }}"
                                data-people-kind="gtk">
                            <datalist id="listTujuanBertemu">
                                @foreach ($daftarRuang as $rng)
                                    <option value="{{ $rng->nama_ruang }}">{{ $rng->nama_ruang }}</option>
                                @endforeach
                            </datalist>
                        </div>
                        <div>
                            <label class="form-label">Keperluan <span style="color:red;">*</span></label>
                            <input type="text" name="keperluan" id="tamu_keperluan" class="form-control"
                                placeholder="Urusan dinas, konsultasi nilai..." required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label class="form-label">Nomor Kartu Visitor</label>
                            <input type="text" name="nomor_kartu_visitor" id="tamu_kartu" class="form-control"
                                placeholder="Contoh: V-01">
                        </div>
                        <div>
                            <label class="form-label">Nomor Polisi Kendaraan</label>
                            <input type="text" name="nomor_polisi_kendaraan" id="tamu_nopol" class="form-control"
                                placeholder="Contoh: B 1234 ABC">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                        <div>
                            <label class="form-label">Status Kunjungan</label>
                            <select name="status" id="tamu_status" class="form-control">
                                <option value="berada_di_lokasi">Berada di Lokasi</option>
                                <option value="sudah_keluar">Sudah Keluar</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Jam Keluar</label>
                            <input type="time" name="jam_keluar" id="tamu_jam_keluar" class="form-control">
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="btn btn-secondary" onclick="closeModalTamu()">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endsection

    @push('scripts')
        <script
            src="{{ asset('js/keamanan-buku-tamu.js') }}?v={{ file_exists(public_path('js/keamanan-buku-tamu.js')) ? filemtime(public_path('js/keamanan-buku-tamu.js')) : time() }}">
        </script>
    @endpush
