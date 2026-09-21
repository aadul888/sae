@extends('layouts.dashboard')

@section('title', 'Checklist Kebersihan & Sanitasi - Fasilitas')

@section('content')
<div class="content-wrapper">
    <!-- 1. Header Banner & Actions -->
    <div class="dash-banner" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(59,130,246,0.12); color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0;">
                <i class="fas fa-broom"></i>
            </div>
            <div>
                <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Checklist Kebersihan &amp; Sanitasi Harian
                </h2>
                <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">
                    Pencatatan kontrol kebersihan toilet, ruang belajar, selasar, ketersediaan air &amp; sabun.
                </div>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            @if ($canCreate)
                <button type="button" class="btn btn-primary" onclick="openModalKebersihan()" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 8px; font-weight: 600;" title="Tambah Checklist">
                    <i class="fas fa-plus"></i>
                    <span>Tambah Checklist</span>
                </button>
            @endif
        </div>
    </div>

    <!-- 2. Stat Grid Baku SAE -->
    <div class="dash-stat-grid" style="margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: #3b82f6;">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['total'] }}</div>
                <div class="dash-stat-label">Total Pemeriksaan</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: #3b82f6;">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['hari_ini'] }}</div>
                <div class="dash-stat-label">Checklist Hari Ini</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['perlu_tindakan'] }}</div>
                <div class="dash-stat-label">Perlu Tindakan</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239,68,68,0.12); color: #ef4444;">
                <i class="fas fa-faucet-drip"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['air_mati'] }}</div>
                <div class="dash-stat-label">Masalah Air / Sanitasi</div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="card" style="padding: 16px; margin-bottom: 20px;">
        <form method="GET" action="{{ route('dashboard.penjaga.kebersihan.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <div style="flex: 1; min-width: 220px;">
                <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Cari zona/area, catatan temuan, nama petugas..." style="width: 100%;">
            </div>
            <div style="min-width: 150px;">
                <input type="date" name="tanggal" value="{{ $tanggal }}" class="form-control" style="width: 100%;">
            </div>
            <div style="min-width: 150px;">
                <select name="shift" class="form-control" style="width: 100%;">
                    <option value="">Semua Shift</option>
                    <option value="pagi" {{ $shift === 'pagi' ? 'selected' : '' }}>Pagi</option>
                    <option value="siang" {{ $shift === 'siang' ? 'selected' : '' }}>Siang</option>
                    <option value="sore" {{ $shift === 'sore' ? 'selected' : '' }}>Sore</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> Filter</button>
            <a href="{{ route('dashboard.penjaga.kebersihan.index') }}" class="btn btn-secondary" title="Reset"><i class="fas fa-sync"></i></a>
        </form>
    </div>

    {{-- Datatable Container --}}
    <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Tanggal & Shift</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Area / Zona</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Kondisi Kebersihan</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Air & Sabun</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Catatan Temuan</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Petugas</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $item)
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px 18px;">
                            <div style="font-weight: 600; color: var(--text-heading);">{{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}</div>
                            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: capitalize;">Shift {{ $item->shift }}</div>
                        </td>
                        <td style="padding: 12px 18px;">
                            <span style="font-weight: 600; color: var(--text-heading);">{{ $item->area_zona }}</span>
                        </td>
                        <td style="padding: 12px 18px;">
                            @if ($item->kondisi_kebersihan === 'sangat_bersih')
                                <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);">Sangat Bersih</span>
                            @elseif ($item->kondisi_kebersihan === 'bersih')
                                <span class="badge" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3);">Bersih</span>
                            @elseif ($item->kondisi_kebersihan === 'kotor')
                                <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3);">Kotor</span>
                            @else
                                <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">Perlu Tindakan</span>
                            @endif
                        </td>
                        <td style="padding: 12px 18px;">
                            @if ($item->ketersediaan_air_sabun === 'lengkap')
                                <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">Lengkap</span>
                            @elseif ($item->ketersediaan_air_sabun === 'habis')
                                <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">Sabun Habis</span>
                            @else
                                <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444;">Air Mati / Masalah</span>
                            @endif
                        </td>
                        <td style="padding: 12px 18px; font-size: 0.85rem; color: var(--text-muted); max-width: 250px;">
                            {{ $item->catatan_temuan ?: '-' }}
                        </td>
                        <td style="padding: 12px 18px; font-size: 0.82rem; color: var(--text-heading);">
                            {{ $item->nama_petugas ?: 'Petugas Fasilitas' }}
                        </td>
                        <td style="padding: 12px 18px; text-align: center;">
                            <div class="table-actions" style="display: flex; gap: 6px; justify-content: center;">
                                @if ($canUpdate)
                                    <button type="button" class="btn-icon" title="Edit" onclick="editKebersihan({{ json_encode($item) }})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                @endif
                                @if ($canDelete)
                                    <form action="{{ route('dashboard.penjaga.kebersihan.destroy', $item->id) }}" method="POST" data-confirm="delete" data-name="Checklist {{ $item->area_zona }}">
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
                            <i class="fas fa-clipboard-check" style="font-size: 2rem; margin-bottom: 8px; opacity: 0.4;"></i>
                            <p style="margin: 0;">Belum ada data checklist kebersihan.</p>
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

{{-- Modal Form Checklist Kebersihan --}}
<div id="modalKebersihan" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center;">
    <div class="card" style="width: 100%; max-width: 550px; margin: 20px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
            <h3 id="modalKebersihanTitle" style="font-size: 1.15rem; font-weight: 700; margin: 0; color: var(--text-heading);">Checklist Kebersihan</h3>
            <button type="button" onclick="closeModalKebersihan()" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>

        <form id="formKebersihan" method="POST" action="{{ route('dashboard.penjaga.kebersihan.store') }}"
              data-store-url="{{ route('dashboard.penjaga.kebersihan.store') }}"
              data-update-url="{{ url('/dashboard/penjaga/kebersihan') }}/:id">
            @csrf
            <input type="hidden" name="_method" id="kebersihanMethod" value="POST">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label class="form-label">Tanggal <span style="color:red;">*</span></label>
                    <input type="date" name="tanggal" id="kebersihan_tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div>
                    <label class="form-label">Shift <span style="color:red;">*</span></label>
                    <select name="shift" id="kebersihan_shift" class="form-control" required>
                        <option value="pagi">Pagi</option>
                        <option value="siang">Siang</option>
                        <option value="sore">Sore</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                    <label class="form-label" style="margin: 0;">Area / Zona Kebersihan <span style="color:red;">*</span></label>
                    <span style="font-size: 0.74rem; color: var(--text-muted);"><i class="fas fa-building me-1"></i>Master Ruang & Sarpras</span>
                </div>
                <input list="listAreaKebersihan" name="area_zona" id="kebersihan_area" class="form-control" placeholder="Pilih dari daftar Ruang Sarpras atau ketik zona..." required>
                <datalist id="listAreaKebersihan">
                    @foreach ($daftarRuang as $rng)
                    <option value="{{ $rng->nama_ruang }} ({{ $rng->gedung }})">{{ $rng->nama_ruang }} - Lantai {{ $rng->lantai }}</option>
                    @endforeach
                </datalist>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label class="form-label">Kondisi Kebersihan <span style="color:red;">*</span></label>
                    <select name="kondisi_kebersihan" id="kebersihan_kondisi" class="form-control" required>
                        <option value="sangat_bersih">Sangat Bersih</option>
                        <option value="bersih" selected>Bersih</option>
                        <option value="kotor">Kotor</option>
                        <option value="perlu_tindakan">Perlu Tindakan</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Ketersediaan Air & Sabun <span style="color:red;">*</span></label>
                    <select name="ketersediaan_air_sabun" id="kebersihan_air" class="form-control" required>
                        <option value="lengkap" selected>Lengkap & Tersedia</option>
                        <option value="habis">Sabun Habis</option>
                        <option value="air_mati">Air Mati / Kran Rusak</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label class="form-label">Catatan Temuan</label>
                <textarea name="catatan_temuan" id="kebersihan_catatan" class="form-control" rows="3" placeholder="Catatan khusus atau kebutuhan perlengkapan..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-secondary" onclick="closeModalKebersihan()">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/penjaga-kebersihan.js') }}"></script>
@endpush
