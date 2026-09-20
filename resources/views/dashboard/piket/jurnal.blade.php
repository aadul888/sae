@extends('layouts.dashboard')

@section('title', 'Jurnal Harian Guru Piket')

@section('content')
<div class="content-wrapper">
    {{-- Header --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--text-heading); margin-bottom: 4px;">
                <i class="fas fa-clipboard-list" style="color: var(--primary); margin-right: 8px;"></i>
                Jurnal Harian Guru Piket
            </h1>
            <p style="font-size: 0.875rem; color: var(--text-muted); margin: 0;">
                Rekapitulasi pelaksanaan piket harian sekolah, pemantauan ketertiban siswa, dan kejadian khusus.
            </p>
        </div>
        @if ($canCreate)
            <div>
                <button type="button" class="btn btn-primary" onclick="openModalJurnal()" style="display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-plus"></i>
                    <span>Tulis Jurnal Piket</span>
                </button>
            </div>
        @endif
    </div>

    {{-- Stats Cards --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="card" style="padding: 16px; border-left: 4px solid var(--primary);">
            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Total Catatan Jurnal</div>
            <div style="font-size: 1.6rem; font-weight: 700; color: var(--text-heading); margin-top: 4px;">{{ $stats['total'] }}</div>
        </div>
        <div class="card" style="padding: 16px; border-left: 4px solid #3b82f6;">
            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Jurnal Bulan Ini</div>
            <div style="font-size: 1.6rem; font-weight: 700; color: #3b82f6; margin-top: 4px;">{{ $stats['bulan_ini'] }}</div>
        </div>
        <div class="card" style="padding: 16px; border-left: 4px solid #10b981;">
            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Jurnal Hari Ini</div>
            <div style="font-size: 1.6rem; font-weight: 700; color: #10b981; margin-top: 4px;">{{ $stats['hari_ini'] }}</div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="card" style="padding: 16px; margin-bottom: 20px;">
        <form method="GET" action="{{ route('dashboard.piket.jurnal.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <div style="flex: 1; min-width: 220px;">
                <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Cari catatan kejadian, nama guru piket..." style="width: 100%;">
            </div>
            <div style="min-width: 150px;">
                <input type="date" name="tanggal" value="{{ $tanggal }}" class="form-control" style="width: 100%;">
            </div>
            <div style="min-width: 150px;">
                <select name="shift" class="form-control" style="width: 100%;">
                    <option value="">Semua Shift</option>
                    <option value="pagi" {{ $shift === 'pagi' ? 'selected' : '' }}>Pagi</option>
                    <option value="siang" {{ $shift === 'siang' ? 'selected' : '' }}>Siang</option>
                    <option value="full_day" {{ $shift === 'full_day' ? 'selected' : '' }}>Full Day</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> Filter</button>
            <a href="{{ route('dashboard.piket.jurnal.index') }}" class="btn btn-secondary" title="Reset"><i class="fas fa-sync"></i></a>
        </form>
    </div>

    {{-- Datatable Container --}}
    <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Tanggal & Shift</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Guru Piket</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Siswa Terlambat</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Siswa Izin</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Catatan Kejadian</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Status</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $item)
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px 18px;">
                            <div style="font-weight: 600; color: var(--text-heading);">{{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}</div>
                            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">{{ str_replace('_', ' ', $item->shift_jam) }}</div>
                        </td>
                        <td style="padding: 12px 18px;">
                            <span style="font-weight: 600; color: var(--text-heading);">{{ $item->nama_guru_piket ?: 'Guru Piket' }}</span>
                        </td>
                        <td style="padding: 12px 18px; text-align: center;">
                            <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; font-size: 0.85rem;">
                                {{ $item->jumlah_siswa_terlambat }} Siswa
                            </span>
                        </td>
                        <td style="padding: 12px 18px; text-align: center;">
                            <span class="badge" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6; font-size: 0.85rem;">
                                {{ $item->jumlah_siswa_izin }} Siswa
                            </span>
                        </td>
                        <td style="padding: 12px 18px; font-size: 0.85rem; color: var(--text-heading); max-width: 250px;">
                            <div style="white-space: normal;">{{ Str::limit($item->catatan_kejadian, 100) ?: '-' }}</div>
                        </td>
                        <td style="padding: 12px 18px;">
                            @if ($item->status === 'selesai')
                                <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);">Selesai</span>
                            @else
                                <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3);">Berjalan</span>
                            @endif
                        </td>
                        <td style="padding: 12px 18px; text-align: center;">
                            <div class="table-actions" style="display: flex; gap: 6px; justify-content: center;">
                                @if ($canUpdate)
                                    <button type="button" class="btn-icon" title="Edit" onclick="editJurnal({{ json_encode($item) }})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                @endif
                                @if ($canDelete)
                                    <form action="{{ route('dashboard.piket.jurnal.destroy', $item->id) }}" method="POST" data-confirm="delete" data-name="Jurnal {{ $item->tanggal }}">
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
                            <i class="fas fa-clipboard-list" style="font-size: 2rem; margin-bottom: 8px; opacity: 0.4;"></i>
                            <p style="margin: 0;">Belum ada catatan jurnal guru piket.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Baku Pagination --}}
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

{{-- Modal Form Jurnal Guru Piket --}}
<div id="modalJurnal" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center;">
    <div class="card" style="width: 100%; max-width: 580px; margin: 20px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
            <h3 id="modalJurnalTitle" style="font-size: 1.15rem; font-weight: 700; margin: 0; color: var(--text-heading);">Tulis Jurnal Piket</h3>
            <button type="button" onclick="closeModalJurnal()" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>

        <form id="formJurnal" method="POST" action="{{ route('dashboard.piket.jurnal.store') }}"
              data-store-url="{{ route('dashboard.piket.jurnal.store') }}"
              data-update-url="{{ url('/dashboard/piket/jurnal') }}/:id">
            @csrf
            <input type="hidden" name="_method" id="jurnalMethod" value="POST">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label class="form-label">Tanggal <span style="color:red;">*</span></label>
                    <input type="date" name="tanggal" id="jurnal_tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div>
                    <label class="form-label">Shift Piket <span style="color:red;">*</span></label>
                    <select name="shift_jam" id="jurnal_shift" class="form-control" required>
                        <option value="pagi">Pagi</option>
                        <option value="siang">Siang</option>
                        <option value="full_day">Full Day</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label class="form-label">Jumlah Siswa Terlambat</label>
                    <input type="number" name="jumlah_siswa_terlambat" id="jurnal_terlambat" class="form-control" min="0" placeholder="Auto hitung jika kosong">
                </div>
                <div>
                    <label class="form-label">Jumlah Siswa Izin</label>
                    <input type="number" name="jumlah_siswa_izin" id="jurnal_izin" class="form-control" min="0" placeholder="Auto hitung jika kosong">
                </div>
            </div>

            <div style="margin-bottom: 12px;">
                <label class="form-label">Catatan Kejadian / Laporan Piket</label>
                <textarea name="catatan_kejadian" id="jurnal_catatan" class="form-control" rows="4" placeholder="Keadaan KBM umum, guru tidak hadir/berhalangan, penertiban seragam, dan catatan khusus piket hari ini..."></textarea>
            </div>

            <div style="margin-bottom: 16px;">
                <label class="form-label">Status Pelaksanaan</label>
                <select name="status" id="jurnal_status" class="form-control">
                    <option value="berjalan">Berjalan</option>
                    <option value="selesai">Selesai</option>
                </select>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-secondary" onclick="closeModalJurnal()">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Jurnal</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/piket-jurnal.js') }}"></script>
@endpush
