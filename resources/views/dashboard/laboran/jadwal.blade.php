@extends('layouts.app')

@section('title', 'Jadwal Penggunaan Lab — Laboratorium SAE')

@section('content')
<div class="dash-container">
    {{-- 1. Header Banner & Action Button --}}
    <div class="dash-header-block" style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h1 style="font-size: 1.4rem; font-weight: 700; color: var(--text-color); margin: 0;">
                    <i class="fas fa-calendar-check" style="color: var(--primary); margin-right: 8px;"></i>
                    Jadwal &amp; Penggunaan Lab
                </h1>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin: 4px 0 0 0;">
                    Penjadwalan sesi praktikum, pemakaian ruang lab komputer/IPA/bengkel, dan log pasca-praktik.
                </p>
            </div>
            @if ($canCreate)
            <div>
                <button type="button" class="btn btn-primary" id="btnOpenModalJadwal">
                    <i class="fas fa-plus me-1"></i> Jadwalkan Praktik
                </button>
            </div>
            @endif
        </div>
    </div>

    {{-- 2. Stat Grid (4 Kartu Ringkasan) --}}
    <div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
                <i class="fas fa-layer-group"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $statTotal }}</div>
                <div class="dash-stat-label">Total Sesi Terjadwal</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $statHariIni }}</div>
                <div class="dash-stat-label">Praktik Hari Ini</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $statBerlangsung }}</div>
                <div class="dash-stat-label">Sedang Berlangsung</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                <i class="fas fa-circle-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $statSelesai }}</div>
                <div class="dash-stat-label">Praktik Selesai</div>
            </div>
        </div>
    </div>

    {{-- 3. Toolbar Tabel (LiveSearch & Filter) --}}
    <div class="card" style="padding: 14px 18px; margin-bottom: 20px;">
        <form method="GET" action="{{ route('dashboard.laboran.jadwal.index') }}" id="filterForm" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            <div style="flex: 1; min-width: 200px; position: relative;">
                <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Cari mapel, topik praktik, guru, atau kelas..." style="padding-left: 36px;">
                <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.85rem;"></i>
            </div>
            <div style="min-width: 150px;">
                <select name="lab" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Semua Lab --</option>
                    @foreach ($daftarLab as $lab)
                    <option value="{{ $lab }}" {{ $labFilter === $lab ? 'selected' : '' }}>{{ $lab }}</option>
                    @endforeach
                </select>
            </div>
            <div style="min-width: 140px;">
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Semua Status --</option>
                    <option value="dijadwalkan" {{ $statusFilter === 'dijadwalkan' ? 'selected' : '' }}>Dijadwalkan</option>
                    <option value="berlangsung" {{ $statusFilter === 'berlangsung' ? 'selected' : '' }}>Berlangsung</option>
                    <option value="selesai" {{ $statusFilter === 'selesai' ? 'selected' : '' }}>Selesai</option>
                    <option value="batal" {{ $statusFilter === 'batal' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>
            <div style="min-width: 140px;">
                <input type="date" name="tanggal" value="{{ $tanggalFilter }}" class="form-control" onchange="this.form.submit()" title="Filter Tanggal">
            </div>
            <button type="submit" class="btn btn-secondary" title="Cari"><i class="fas fa-filter"></i></button>
            @if ($search || $labFilter || $statusFilter || $tanggalFilter)
            <a href="{{ route('dashboard.laboran.jadwal.index') }}" class="btn btn-outline" title="Reset"><i class="fas fa-undo"></i></a>
            @endif
        </form>
    </div>

    {{-- 4. Datatable Responsif --}}
    <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255, 255, 255, 0.02);">
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">No</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Lab &amp; Tanggal</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Mapel &amp; Topik</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Guru &amp; Rombel</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Waktu Sesi</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Status</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $idx => $item)
                <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                    <td data-label="No" style="padding: 14px 18px; font-size: 0.88rem; color: var(--text-muted);">
                        {{ $list->firstItem() + $idx }}
                    </td>
                    <td data-label="Lab & Tanggal" style="padding: 14px 18px;">
                        <div style="font-weight: 700; color: var(--text-color); font-size: 0.92rem;">{{ $item->ruang_lab_nama }}</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);"><i class="fas fa-calendar-day text-primary me-1"></i> {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d F Y') }}</div>
                    </td>
                    <td data-label="Mapel" style="padding: 14px 18px;">
                        <div style="font-weight: 600; color: var(--text-color); font-size: 0.88rem;">{{ $item->mata_pelajaran }}</div>
                        <div style="font-size: 0.78rem; color: var(--primary); font-weight: 500;">{{ $item->topik_praktik }}</div>
                        @if ($item->laporan_kerusakan)
                        <div style="font-size: 0.74rem; color: #ef4444; margin-top: 3px;"><i class="fas fa-circle-exclamation me-1"></i> Catatan: {{ $item->laporan_kerusakan }}</div>
                        @endif
                    </td>
                    <td data-label="Guru" style="padding: 14px 18px; font-size: 0.88rem;">
                        <div style="font-weight: 600; color: var(--text-color);">{{ $item->guru_nama ?: '-' }}</div>
                        <span class="badge badge-outline" style="font-size: 0.72rem;">{{ $item->rombel_nama ?: 'Seluruh Siswa' }}</span>
                    </td>
                    <td data-label="Waktu" style="padding: 14px 18px; text-align: center; font-size: 0.85rem; font-family: monospace;">
                        {{ substr($item->jam_mulai, 0, 5) }} - {{ substr($item->jam_selesai, 0, 5) }}
                    </td>
                    <td data-label="Status" style="padding: 14px 18px; text-align: center;">
                        @if ($item->status === 'dijadwalkan')
                            <span class="badge badge-info">Dijadwalkan</span>
                        @elseif ($item->status === 'berlangsung')
                            <span class="badge badge-warning">Berlangsung</span>
                        @elseif ($item->status === 'selesai')
                            <span class="badge badge-success">Selesai</span>
                        @else
                            <span class="badge badge-danger">Batal</span>
                        @endif
                    </td>
                    <td data-label="Aksi" style="padding: 14px 18px; text-align: right;">
                        <div class="table-actions" style="justify-content: flex-end; gap: 6px;">
                            @if ($canUpdate)
                            <button type="button" class="btn btn-sm btn-outline btn-status-lab"
                                data-id="{{ $item->id }}"
                                data-mapel="{{ $item->mata_pelajaran }}"
                                data-status="{{ $item->status }}"
                                data-laporan="{{ $item->laporan_kerusakan }}"
                                title="Update Status Sesi">
                                <i class="fas fa-sliders me-1"></i> Status
                            </button>
                            @endif
                            @if ($canDelete)
                            <button type="button" class="btn-icon btn-delete-jadwal"
                                data-id="{{ $item->id }}"
                                data-mapel="{{ $item->mata_pelajaran }}"
                                title="Hapus Jadwal" style="color: #ef4444;">
                                <i class="fas fa-trash-can"></i>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="padding: 30px; text-align: center; color: var(--text-muted);">
                        <i class="fas fa-folder-open mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                        <div>Belum ada jadwal penggunaan lab yang cocok.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- 5. Pagination Footer Resmi SAE --}}
    @if ($list->hasPages())
        <div class="custom-pagination">
            @if ($list->onFirstPage())
                <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
            @else
                <a href="{{ $list->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
            @endif
            @php
                $cur = $list->currentPage();
                $last = $list->lastPage();
                $from = max(1, $cur - 2);
                $to = min($last, $cur + 2);
            @endphp
            @if ($from > 1)
                <a href="{{ $list->url(1) }}" class="page-btn">1</a>
                @if ($from > 2) <span class="page-info">&hellip;</span> @endif
            @endif
            @for ($i = $from; $i <= $to; $i++)
                <a href="{{ $list->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
            @endfor
            @if ($to < $last)
                @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                <a href="{{ $list->url($last) }}" class="page-btn">{{ $last }}</a>
            @endif
            @if ($list->hasMorePages())
                <a href="{{ $list->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
            @else
                <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
            @endif
        </div>
    @endif
</div>

{{-- Modal Tambah Jadwal Praktik --}}
<div id="modalJadwal" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="width: 100%; max-width: 580px; max-height: 90vh; overflow-y: auto; padding: 24px; border-radius: 12px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: var(--text-color);">
                Jadwalkan Penggunaan Lab
            </h3>
            <button type="button" id="btnCloseModalJadwal" style="background: none; border: none; font-size: 1.25rem; color: var(--text-muted); cursor: pointer;">&times;</button>
        </div>

        <form id="formJadwal" method="POST" action="{{ route('dashboard.laboran.jadwal.store') }}">
            @csrf
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Ruang Lab <span style="color: #ef4444;">*</span></label>
                    <select name="ruang_lab_nama" class="form-control" required>
                        @foreach ($daftarLab as $l)
                        <option value="{{ $l }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Tanggal Praktik <span style="color: #ef4444;">*</span></label>
                    <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" class="form-control" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Guru Pengampu <span style="color: #ef4444;">*</span></label>
                    <select name="ptk_id" class="form-control" required>
                        <option value="">-- Pilih Guru --</option>
                        @foreach ($allGtk as $g)
                        <option value="{{ $g->ptk_id }}">{{ $g->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Kelas / Rombel</label>
                    <select name="rombel_id" class="form-control">
                        <option value="">-- Semua Siswa / Umum --</option>
                        @foreach ($allRombel as $rb)
                        <option value="{{ $rb->rombongan_belajar_id }}">{{ $rb->nama }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Mata Pelajaran <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="mata_pelajaran" class="form-control" placeholder="Contoh: Pemrograman Web / Kimia Dasar" required>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Topik / Materi Praktik <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="topik_praktik" class="form-control" placeholder="Contoh: Reaksi Redoks / Desain Database" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Jam Mulai <span style="color: #ef4444;">*</span></label>
                    <input type="time" name="jam_mulai" class="form-control" value="08:00" required>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Jam Selesai <span style="color: #ef4444;">*</span></label>
                    <input type="time" name="jam_selesai" class="form-control" value="09:30" required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Kebutuhan Alat / Bahan Praktik</label>
                <textarea name="alat_bahan_digunakan" class="form-control" rows="2" placeholder="Sebutkan alat, komputer, cairan kimia, atau bahan yang diperlukan..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="btn btn-secondary" id="btnCancelModalJadwal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan Jadwal</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Update Status & Laporan Kerusakan --}}
<div id="modalStatus" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="width: 100%; max-width: 480px; padding: 24px; border-radius: 12px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: var(--text-color);">
                Update Status Praktikum
            </h3>
            <button type="button" id="btnCloseModalStatus" style="background: none; border: none; font-size: 1.25rem; color: var(--text-muted); cursor: pointer;">&times;</button>
        </div>

        <form id="formStatus" method="POST" action="">
            @csrf
            @method('PUT')
            <p id="statusDesc" style="font-size: 0.88rem; color: var(--text-color); margin-bottom: 16px;"></p>

            <div class="form-group" style="margin-bottom: 14px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Pilih Status <span style="color: #ef4444;">*</span></label>
                <select name="status" id="selectStatusLab" class="form-control" required>
                    <option value="dijadwalkan">Dijadwalkan</option>
                    <option value="berlangsung">Sedang Berlangsung</option>
                    <option value="selesai">Praktik Selesai</option>
                    <option value="batal">Dibatalkan</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Laporan Kerusakan / Insiden Pasca-Praktik</label>
                <textarea name="laporan_kerusakan" id="inputLaporanKerusakan" class="form-control" rows="2" placeholder="Catat jika ada alat pecah, mouse/keyboard rusak, atau insiden..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="btn btn-secondary" id="btnCancelModalStatus">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i> Simpan Status</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/laboran-jadwal.js') }}"></script>
@endpush
@endsection
