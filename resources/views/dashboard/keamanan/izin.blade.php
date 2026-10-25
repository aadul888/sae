@extends('layouts.dashboard')

@section('title', 'Verifikasi e-Izin Gerbang - Keamanan')

@section('content')
    <div class="content-wrapper">
        <!-- 1. Header Banner & Actions -->
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg, 12px); background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); flex-shrink: 0;">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                        Verifikasi e-Izin Keluar-Masuk Siswa
                    </h2>
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        Validasi dan verifikasi siswa keluar-masuk gerbang sekolah berbasis tiket digital / QR code.
                    </div>
                </div>
            </div>

            <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <button type="button" class="btn btn-primary" onclick="openModalScan()"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                    title="Scan / Input Tiket Izin Keluar">
                    <i class="fas fa-qrcode"></i>
                </button>
            </div>
        </div>

        <!-- 2. Stat Grid Baku SAE -->
        <div class="dash-stat-grid" style="margin-bottom: 20px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6);">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['total'] }}</div>
                    <div class="dash-stat-label">Total Izin Hari Ini</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b);">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['menunggu'] }}</div>
                    <div class="dash-stat-label">Menunggu Gerbang</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6);">
                    <i class="fas fa-person-walking-arrow-right"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['di_luar'] }}</div>
                    <div class="dash-stat-label">Sedang di Luar</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: var(--success, #10b981);">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['kembali'] }}</div>
                    <div class="dash-stat-label">Sudah Kembali</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary-light, #6366f1);">
                    <i class="fas fa-house-user"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['pulang'] }}</div>
                    <div class="dash-stat-label">Pulang Selesai</div>
                </div>
            </div>
        </div>

        {{-- Unified Datatable Keamanan Izin (Toolbar + Table + Pagination dalam 1 Card Terpadu) --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            {{-- 1. Toolbar Header (Entries, Filters & Live Search) --}}
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.keamanan.izin.index') }}" id="filterForm"
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
                                <option value="menunggu_satpam" {{ $status === 'menunggu_satpam' ? 'selected' : '' }}>
                                    Menunggu
                                    Gerbang</option>
                                <option value="di_luar" {{ $status === 'di_luar' ? 'selected' : '' }}>Di Luar</option>
                                <option value="kembali" {{ $status === 'kembali' ? 'selected' : '' }}>Sudah Kembali
                                </option>
                                <option value="pulang_selesai" {{ $status === 'pulang_selesai' ? 'selected' : '' }}>Pulang
                                    Selesai
                                </option>
                                <option value="dibatalkan" {{ $status === 'dibatalkan' ? 'selected' : '' }}>Dibatalkan
                                </option>
                            </select>

                            @if ($search || $status || ($tanggal && $tanggal !== date('Y-m-d')))
                                <a href="{{ route('dashboard.keamanan.izin.index') }}"
                                    class="btn btn-outline btn-reset-filter"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                    title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="search" id="searchIzin" value="{{ $search }}"
                                placeholder="Cari nomor tiket, siswa, NISN..." autocomplete="off">
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
                                No Tiket</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Nama Siswa / Rombel</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Jenis Izin</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Alasan</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Waktu Izin</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Status Gerbang</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                Aksi Gerbang</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $item)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 18px;">
                                    <span
                                        style="font-weight: 700; font-family: monospace; font-size: var(--text-base, 0.875rem); color: var(--primary);">{{ $item->nomor_tiket }}</span>
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                        {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}</div>
                                </td>
                                <td style="padding: 12px 18px;">
                                    <div style="font-weight: 600; color: var(--text-heading);">
                                        {{ $item->nama_siswa ?? '-' }}
                                    </div>
                                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                        NISN: {{ $item->nisn ?? '-' }} &bull; {{ $item->nama_rombel ?? '-' }}
                                    </div>
                                </td>
                                <td style="padding: 12px 18px;">
                                    @if ($item->jenis_izin === 'keluar_sebentar')
                                        <span class="badge"
                                            style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6); border: 1px solid rgba(59, 130, 246, 0.3);">Keluar
                                            Sebentar</span>
                                    @elseif ($item->jenis_izin === 'pulang_cepat')
                                        <span class="badge"
                                            style="background: rgba(239, 68, 68, 0.15); color: var(--danger, #ef4444); border: 1px solid rgba(239, 68, 68, 0.3);">Pulang
                                            Cepat</span>
                                    @else
                                        <span class="badge"
                                            style="background: rgba(107, 114, 128, 0.15); color: #6b7280; border: 1px solid rgba(107, 114, 128, 0.3);">{{ ucfirst($item->jenis_izin) }}</span>
                                    @endif
                                </td>
                                <td style="padding: 12px 18px; max-width: 220px;">
                                    <div style="font-size: var(--text-base, 0.875rem); color: var(--text-heading); white-space: normal;">
                                        {{ $item->alasan }}</div>
                                </td>
                                <td style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem);">
                                    <div>Keluar: <strong>{{ substr($item->jam_izin_keluar, 0, 5) }}</strong></div>
                                    @if ($item->jam_rencana_kembali)
                                        <div style="color: var(--text-muted);">Rencana:
                                            {{ substr($item->jam_rencana_kembali, 0, 5) }}</div>
                                    @endif
                                    @if ($item->jam_kembali_aktual)
                                        <div style="color: var(--success, #10b981); font-weight: 600;">Aktual:
                                            {{ substr($item->jam_kembali_aktual, 0, 5) }}</div>
                                    @endif
                                </td>
                                <td style="padding: 12px 18px;">
                                    @if ($item->status === 'menunggu_satpam')
                                        <span class="badge"
                                            style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b); border: 1px solid rgba(245, 158, 11, 0.3);">Menunggu
                                            Gerbang</span>
                                    @elseif ($item->status === 'di_luar')
                                        <span class="badge"
                                            style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6); border: 1px solid rgba(59, 130, 246, 0.3);">Di
                                            Luar Sekolah</span>
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">Oleh:
                                            {{ $item->satpam_checkout_by }}</div>
                                    @elseif ($item->status === 'kembali')
                                        <span class="badge"
                                            style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981); border: 1px solid rgba(16, 185, 129, 0.3);">Sudah
                                            Kembali</span>
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">Oleh:
                                            {{ $item->satpam_checkin_by }}</div>
                                    @elseif ($item->status === 'pulang_selesai')
                                        <span class="badge"
                                            style="background: rgba(99, 102, 241, 0.15); color: var(--primary-light, #6366f1); border: 1px solid rgba(99, 102, 241, 0.3);">Pulang
                                            Selesai</span>
                                    @else
                                        <span class="badge"
                                            style="background: rgba(107, 114, 128, 0.15); color: #6b7280; border: 1px solid rgba(107, 114, 128, 0.3);">{{ ucfirst($item->status) }}</span>
                                    @endif
                                </td>
                                <td style="padding: 12px 18px; text-align: center;">
                                    <div class="table-actions" style="display: flex; gap: 6px; justify-content: center;">
                                        @if ($item->status === 'menunggu_satpam')
                                            <form action="{{ route('dashboard.keamanan.izin.checkout', $item->id) }}"
                                                method="POST" data-confirm-action="checkout"
                                                data-name="{{ $item->nama_siswa }}" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn-icon" title="Verifikasi Keluar Gerbang"
                                                    style="color: var(--warning, #f59e0b);">
                                                    <i class="fas fa-arrow-right-from-bracket"></i>
                                                </button>
                                            </form>
                                        @elseif ($item->status === 'di_luar' && $item->jenis_izin === 'keluar_sebentar')
                                            <form action="{{ route('dashboard.keamanan.izin.checkin', $item->id) }}"
                                                method="POST" data-confirm-action="checkin"
                                                data-name="{{ $item->nama_siswa }}" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn-icon"
                                                    title="Verifikasi Kembali Masuk Gerbang" style="color: var(--success, #10b981);">
                                                    <i class="fas fa-arrow-right-to-bracket"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">-</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 36px; color: var(--text-muted);">
                                    <i class="fas fa-ticket-alt"
                                        style="font-size: var(--text-3xl, 1.875rem); margin-bottom: 8px; opacity: 0.4;"></i>
                                    <p style="margin: 0;">Tidak ada data izin keluar-masuk pada filter ini.</p>
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

        {{-- Modal Scan / Input Tiket Cepat --}}
        <div id="modalScanTiket"
            style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center;">
            <div class="card" style="width: 100%; max-width: 520px; margin: 20px; max-height: 90vh; overflow-y: auto;">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
                    <h3 style="font-size: var(--text-xl, 1.25rem); font-weight: 700; margin: 0; color: var(--text-heading);">
                        <i class="fas fa-qrcode" style="color: var(--primary); margin-right: 8px;"></i>
                        Scan / Masukkan Nomor Tiket e-Izin
                    </h3>
                    <button type="button" onclick="closeModalScan()"
                        style="background: none; border: none; font-size: var(--text-xl, 1.25rem); cursor: pointer; color: var(--text-muted);">&times;</button>
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="form-label" style="font-weight: 600;">Nomor Tiket e-Izin (Scan Barcode/Ketik):</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" id="inputScanTiket" class="form-control"
                            placeholder="Contoh: IZN-20260921-0001"
                            style="font-family: monospace; font-size: var(--text-lg, 1.125rem); text-transform: uppercase;">
                        <button type="button" class="btn btn-primary" id="btnCariTiket" onclick="cariTiketScan()"><i
                                class="fas fa-search"></i> Cek</button>
                    </div>
                </div>

                {{-- Hasil Pencarian Tiket --}}
                <div id="scanResultContainer"
                    style="display: none; padding: 14px; border-radius: var(--radius-md, 8px); background: var(--bg-card); border: 1px solid var(--border-color); margin-bottom: 16px;">
                    <div style="font-size: var(--text-base, 0.875rem); color: var(--text-muted); margin-bottom: 4px;">Informasi Tiket:</div>
                    <div id="resNomorTiket"
                        style="font-weight: 700; font-family: monospace; color: var(--primary); font-size: var(--text-lg, 1.125rem);"></div>
                    <div id="resNamaSiswa"
                        style="font-weight: 600; font-size: var(--text-md, 1rem); color: var(--text-heading); margin-top: 4px;"></div>
                    <div id="resRombel" style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);"></div>
                    <div style="margin-top: 8px; font-size: var(--text-base, 0.875rem);" id="resAlasan"></div>
                    <div style="margin-top: 8px; font-size: var(--text-base, 0.875rem);" id="resStatus"></div>

                    <div id="scanActionContainer" style="margin-top: 16px; display: flex; gap: 8px;"></div>
                </div>

                <div style="display: flex; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeModalScan()">Tutup</button>
                </div>
            </div>
        </div>
    @endsection

    @push('scripts')
        <script
            src="{{ asset('js/keamanan-izin.js') }}?v={{ file_exists(public_path('js/keamanan-izin.js')) ? filemtime(public_path('js/keamanan-izin.js')) : time() }}">
        </script>
    @endpush
