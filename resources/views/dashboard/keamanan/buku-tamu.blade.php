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
                        Buku Tamu Pos Keamanan (e-Tamu)
                    </h2>
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        Pencatatan tamu mandiri via scan QR Code, deteksi otomatis orang tua siswa, dan input manual satpam.
                    </div>
                </div>
            </div>

            <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <a href="{{ route('dashboard.keamanan.buku-tamu.cetak-qr') }}" target="_blank" class="btn btn-outline"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                    title="Cetak Standing Poster QR Code e-Tamu" aria-label="Cetak Standing Poster QR Code e-Tamu">
                    <i class="fas fa-print"></i>
                </a>
                <button type="button" class="btn btn-outline" onclick="openModalQr()"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                    title="Tampilkan QR Code di Layar" aria-label="Tampilkan QR Code di Layar">
                    <i class="fas fa-qrcode"></i>
                </button>
                @if ($canCreate)
                    <button type="button" class="btn btn-primary" onclick="openModalTamu()"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                        title="Catat Tamu Masuk Manual" aria-label="Catat Tamu Masuk Manual">
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

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(139,92,246,0.12); color: var(--purple, #8b5cf6);">
                    <i class="fas fa-qrcode"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['mandiri'] }}</div>
                    <div class="dash-stat-label">e-Tamu Mandiri</div>
                </div>
            </div>
        </div>

        {{-- Unified Datatable Keamanan Buku Tamu --}}
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

                            <select name="status" class="toolbar-filter-select" style="height: 38px; min-width: 130px;">
                                <option value="">Semua Status</option>
                                <option value="berada_di_lokasi" {{ $status === 'berada_di_lokasi' ? 'selected' : '' }}>Di
                                    Lokasi
                                </option>
                                <option value="sudah_keluar" {{ $status === 'sudah_keluar' ? 'selected' : '' }}>Sudah
                                    Keluar
                                </option>
                            </select>

                            <select name="jenis_tamu" class="toolbar-filter-select" style="height: 38px; min-width: 140px;">
                                <option value="">Semua Kategori</option>
                                <option value="orang_tua" {{ $jenisTamu === 'orang_tua' ? 'selected' : '' }}>Orang Tua Siswa</option>
                                <option value="kedinasan" {{ $jenisTamu === 'kedinasan' ? 'selected' : '' }}>Kedinasan</option>
                                <option value="umum" {{ $jenisTamu === 'umum' ? 'selected' : '' }}>Umum</option>
                                <option value="alumni" {{ $jenisTamu === 'alumni' ? 'selected' : '' }}>Alumni</option>
                            </select>

                            @if ($search || $status || $tanggal || $jenisTamu)
                                <a href="{{ route('dashboard.keamanan.buku-tamu.index') }}"
                                    class="btn btn-outline btn-reset-filter"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                    title="Reset filter" aria-label="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="search" id="searchBukuTamu" value="{{ $search }}"
                                placeholder="Cari nama tamu, siswa, nopol..." autocomplete="off">
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
                                Nama Tamu & Kategori</th>
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
                                    <div style="font-weight: 600; color: var(--text-heading); display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                        <span>{{ $item->nama_tamu }}</span>
                                        @if ($item->is_mandiri)
                                            <span class="badge" style="background: rgba(139, 92, 246, 0.12); color: var(--purple, #8b5cf6); font-size: 10px; padding: 2px 6px;">
                                                <i class="fas fa-qrcode me-1"></i>Mandiri
                                            </span>
                                        @endif
                                        @if ($item->jenis_tamu === 'orang_tua')
                                            <span class="badge" style="background: rgba(2, 132, 199, 0.12); color: var(--primary, #0284c7); font-size: 10px; padding: 2px 6px;">
                                                <i class="fas fa-user-graduate me-1"></i>Orang Tua
                                            </span>
                                        @elseif ($item->jenis_tamu === 'kedinasan')
                                            <span class="badge" style="background: rgba(99, 102, 241, 0.12); color: #6366f1; font-size: 10px; padding: 2px 6px;">
                                                <i class="fas fa-building me-1"></i>Dinas
                                            </span>
                                        @endif
                                    </div>
                                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                        {{ $item->instansi_asal ?: 'Umum / Pribadi' }}
                                        @if ($item->nomor_kontak)
                                            &bull; Telp: {{ $item->nomor_kontak }}
                                        @endif
                                    </div>
                                    @if ($item->nama_siswa)
                                        <div style="font-size: 11px; color: var(--primary, #0284c7); margin-top: 3px;">
                                            <i class="fas fa-child me-1"></i> Siswa: <strong>{{ $item->nama_siswa }}</strong>
                                            @if ($item->rombel_siswa) ({{ $item->rombel_siswa }}) @endif
                                        </div>
                                    @endif
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

        {{-- Modal Tambah / Edit Tamu (Input Manual Satpam) --}}
        <div id="modalTamu"
            style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center;">
            <div class="card" style="width: 100%; max-width: 620px; margin: 20px; max-height: 90vh; overflow-y: auto;">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
                    <h3 id="modalTamuTitle"
                        style="font-size: var(--text-xl, 1.25rem); font-weight: 700; margin: 0; color: var(--text-heading);">
                        Catat Tamu Masuk (Manual)
                    </h3>
                    <button type="button" onclick="closeModalTamu()"
                        style="background: none; border: none; font-size: var(--text-xl, 1.25rem); cursor: pointer; color: var(--text-muted);">&times;</button>
                </div>

                <form id="formTamu" method="POST" action="{{ route('dashboard.keamanan.buku-tamu.store') }}"
                    data-store-url="{{ route('dashboard.keamanan.buku-tamu.store') }}"
                    data-update-url="{{ url('/dashboard/keamanan/buku-tamu') }}/:id">
                    @csrf
                    <input type="hidden" name="_method" id="tamuMethod" value="POST">
                    <input type="hidden" name="peserta_didik_id" id="tamu_pd_id" value="">

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
                        <label class="form-label">Kategori Tamu <span style="color:red;">*</span></label>
                        <select name="jenis_tamu" id="tamu_jenis" class="form-control" onchange="toggleManualOrtuSection()">
                            <option value="umum">Umum / Masyarakat</option>
                            <option value="orang_tua">Orang Tua / Wali Siswa</option>
                            <option value="kedinasan">Kedinasan / Instansi</option>
                            <option value="alumni">Alumni</option>
                        </select>
                    </div>

                    {{-- Section Pencarian Cepat Data Siswa untuk Tamu Orang Tua --}}
                    <div id="manualSectionOrtu" style="display: none; background: rgba(2, 132, 199, 0.05); border: 1px dashed var(--primary, #0284c7); border-radius: var(--radius-md, 8px); padding: 12px; margin-bottom: 12px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <span style="font-weight: 700; font-size: 11px; color: var(--primary, #0284c7);">
                                <i class="fas fa-search me-1"></i> Deteksi Siswa (Nama, NISN, atau NIK)
                            </span>
                            <span style="font-size: 10px; color: var(--text-muted);">Ketik nama/NISN & klik Cari</span>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 8px; align-items: flex-end;">
                            <div>
                                <label style="font-size: 10px; font-weight: 600;">Nama Lengkap / NISN Siswa</label>
                                <input type="text" id="manual_nisn_siswa" name="nisn_siswa" class="form-control" style="font-size: 12px; height: 34px;" placeholder="Ketik nama atau NISN...">
                            </div>
                            <div>
                                <label style="font-size: 10px; font-weight: 600;">Atau NIK Orang Tua</label>
                                <input type="text" id="manual_nik_ortu" name="nik_tamu" class="form-control" style="font-size: 12px; height: 34px;" placeholder="16 Digit NIK Ortu">
                            </div>
                            <button type="button" class="btn btn-outline" id="btnManualCekSiswa" onclick="lookupManualStudent()" aria-label="Cari siswa" title="Cari siswa" style="height: 34px; padding: 0 12px; font-size: 12px;">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <div id="manualLookupFeedback" style="font-size: 11px; margin-top: 6px;"></div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 8px;">
                            <div>
                                <label style="font-size: 10px; font-weight: 600;">Nama Siswa</label>
                                <input type="text" name="nama_siswa" id="tamu_nama_siswa" class="form-control" style="font-size: 12px; height: 34px;" placeholder="Nama Putra/Putri">
                            </div>
                            <div>
                                <label style="font-size: 10px; font-weight: 600;">Kelas / Rombel</label>
                                <input type="text" name="rombel_siswa" id="tamu_rombel_siswa" class="form-control" style="font-size: 12px; height: 34px;" placeholder="Kelas Siswa">
                            </div>
                        </div>
                    </div>

                    {{-- Section Surat Tugas untuk Tamu Kedinasan --}}
                    <div id="manualSectionKedinasan" style="display: none; background: rgba(6, 182, 212, 0.05); border: 1px dashed var(--accent, #06b6d4); border-radius: var(--radius-md, 8px); padding: 12px; margin-bottom: 12px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <span style="font-weight: 700; font-size: 11px; color: var(--accent, #06b6d4);">
                                <i class="fas fa-file-lines me-1"></i> Surat Tugas Kedinasan
                            </span>
                            <span style="font-size: 10px; color: var(--text-muted);">Pilih jika membawa surat</span>
                        </div>
                        <div style="margin-bottom: 8px;">
                            <label style="font-size: 11px; font-weight: 600; display: block; margin-bottom: 4px;">Apakah Membawa Surat Tugas?</label>
                            <div style="display: flex; gap: 14px; font-size: 12px;">
                                <label style="display: inline-flex; align-items: center; gap: 5px; cursor: pointer;">
                                    <input type="radio" name="ada_surat_tugas" value="1" id="manualRadioSuratYa" onchange="toggleManualSuratTugas(true)">
                                    <span><i class="fas fa-check text-success me-1"></i> Ya, Ada</span>
                                </label>
                                <label style="display: inline-flex; align-items: center; gap: 5px; cursor: pointer;">
                                    <input type="radio" name="ada_surat_tugas" value="0" id="manualRadioSuratTidak" checked onchange="toggleManualSuratTugas(false)">
                                    <span><i class="fas fa-times text-muted me-1"></i> Tidak Ada</span>
                                </label>
                            </div>
                        </div>
                        <div id="manualBoxSuratDetail" style="display: none; padding-top: 8px; border-top: 1px dashed rgba(6, 182, 212, 0.3);">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                <div>
                                    <label style="font-size: 10px; font-weight: 600;">Nomor Surat Tugas</label>
                                    <input type="text" name="nomor_surat_tugas" id="manual_nomor_surat" class="form-control" style="font-size: 12px; height: 34px;" placeholder="Contoh: 800/123/Disdik/2026">
                                </div>
                                <div>
                                    <label style="font-size: 10px; font-weight: 600;">Tanggal Surat</label>
                                    <input type="date" name="tanggal_surat_tugas" id="manual_tanggal_surat" class="form-control" style="font-size: 12px; height: 34px;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <label class="form-label">Nama Tamu <span style="color:red;">*</span></label>
                        <input type="text" name="nama_tamu" id="tamu_nama" class="form-control"
                            placeholder="Nama lengkap tamu" required>
                    </div>

                    {{-- NIP & Jabatan Tamu Kedinasan Satpam --}}
                    <div id="manualKedinasanIdentitas" style="display: none; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label class="form-label">NIP Tamu (Opsional)</label>
                            <input type="text" name="nip_tamu" id="manual_nip_tamu" class="form-control"
                                placeholder="18 digit NIP pegawai">
                        </div>
                        <div>
                            <label class="form-label">Jabatan (Opsional)</label>
                            <input type="text" name="jabatan_tamu" id="manual_jabatan_tamu" class="form-control"
                                placeholder="Contoh: Pengawas, Kasi Kurikulum">
                        </div>
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
                                <option value="Kepala Sekolah">Kepala Sekolah</option>
                                <option value="Wakil Kepala Sekolah">Wakil Kepala Sekolah</option>
                                <option value="Wali Kelas">Wali Kelas</option>
                                <option value="Guru Bimbingan Konseling (BK)">Guru Bimbingan Konseling (BK)</option>
                                <option value="Guru Mata Pelajaran">Guru Mata Pelajaran</option>
                                <option value="Bagian Tata Usaha (TU)">Bagian Tata Usaha (TU)</option>
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

        {{-- Modal Popup QR Code (Untuk di-scan langsung di layar pos satpam) --}}
        <div id="modalQrCode"
            style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center;">
            <div class="card" style="width: 100%; max-width: 440px; margin: 20px; text-align: center; padding: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
                    <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 700; margin: 0; color: var(--text-heading);">
                        <i class="fas fa-qrcode text-primary me-1"></i> Scan QR e-Tamu Mandiri
                    </h3>
                    <button type="button" onclick="closeModalQr()"
                        style="background: none; border: none; font-size: var(--text-xl, 1.25rem); cursor: pointer; color: var(--text-muted);">&times;</button>
                </div>
                <div style="background: #ffffff; border: 2px solid var(--primary, #0284c7); border-radius: 16px; padding: 14px; display: inline-block; margin-bottom: 12px;">
                    {!! $qrSvg !!}
                </div>
                <div style="font-size: 11px; font-family: monospace; color: var(--primary, #0284c7); margin-bottom: 8px; word-break: break-all;">
                    {{ $publicFormUrl }}
                </div>
                <p style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin: 0 0 16px;">
                    Arahkan kamera ponsel tamu ke kode QR di atas untuk mengisi buku tamu mandiri tanpa perlu antre di meja pos.
                </p>
                <div style="display: flex; gap: 8px; justify-content: center;">
                    <a href="{{ $publicFormUrl }}" target="_blank" class="btn btn-outline" style="font-size: var(--text-xs, 0.75rem);">
                        <i class="fas fa-external-link-alt me-1"></i> Buka Link
                    </a>
                    <a href="{{ route('dashboard.keamanan.buku-tamu.cetak-qr') }}" target="_blank" class="btn btn-primary" style="font-size: var(--text-xs, 0.75rem);">
                        <i class="fas fa-print me-1"></i> Cetak Poster
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.BUKU_TAMU_CONFIG = {
            checkStudentUrl: "{{ route('tamu.check-student') }}"
        };
    </script>
    <script
        src="{{ asset('js/keamanan-buku-tamu.js') }}?v={{ file_exists(public_path('js/keamanan-buku-tamu.js')) ? filemtime(public_path('js/keamanan-buku-tamu.js')) : time() }}">
    </script>
@endpush
