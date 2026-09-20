@extends('layouts.dashboard')

@section('title', 'e-Izin Keluar-Masuk Siswa - Piket Sekolah')

@section('content')
<div class="content-wrapper">
    {{-- Header --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--text-heading); margin-bottom: 4px;">
                <i class="fas fa-ticket-alt" style="color: var(--primary); margin-right: 8px;"></i>
                e-Izin Keluar-Masuk Siswa (Piket Sekolah)
            </h1>
            <p style="font-size: 0.875rem; color: var(--text-muted); margin: 0;">
                Penerbitan surat izin keluar sebentar, pulang cepat, dan validasi slip barcode untuk satpam gerbang.
            </p>
        </div>
        @if ($canCreate)
            <div>
                <button type="button" class="btn btn-primary" onclick="openModalIzin()" style="display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-plus"></i>
                    <span>Terbitkan Izin Baru</span>
                </button>
            </div>
        @endif
    </div>

    {{-- Stats Cards --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="card" style="padding: 16px; border-left: 4px solid var(--primary);">
            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Total Izin Hari Ini</div>
            <div style="font-size: 1.6rem; font-weight: 700; color: var(--text-heading); margin-top: 4px;">{{ $stats['total'] }}</div>
        </div>
        <div class="card" style="padding: 16px; border-left: 4px solid #3b82f6;">
            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Keluar Sebentar</div>
            <div style="font-size: 1.6rem; font-weight: 700; color: #3b82f6; margin-top: 4px;">{{ $stats['keluar_sebentar'] }}</div>
        </div>
        <div class="card" style="padding: 16px; border-left: 4px solid #ef4444;">
            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Pulang Cepat</div>
            <div style="font-size: 1.6rem; font-weight: 700; color: #ef4444; margin-top: 4px;">{{ $stats['pulang_cepat'] }}</div>
        </div>
        <div class="card" style="padding: 16px; border-left: 4px solid #f59e0b;">
            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Menunggu Gerbang</div>
            <div style="font-size: 1.6rem; font-weight: 700; color: #f59e0b; margin-top: 4px;">{{ $stats['menunggu_gerbang'] }}</div>
        </div>
        <div class="card" style="padding: 16px; border-left: 4px solid #10b981;">
            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Sedang di Luar</div>
            <div style="font-size: 1.6rem; font-weight: 700; color: #10b981; margin-top: 4px;">{{ $stats['di_luar'] }}</div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="card" style="padding: 16px; margin-bottom: 20px;">
        <form method="GET" action="{{ route('dashboard.piket.izin.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <div style="flex: 1; min-width: 220px;">
                <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Cari nomor tiket, nama siswa, NISN..." style="width: 100%;">
            </div>
            <div style="min-width: 150px;">
                <input type="date" name="tanggal" value="{{ $tanggal }}" class="form-control" style="width: 100%;">
            </div>
            <div style="min-width: 160px;">
                <select name="jenis_izin" class="form-control" style="width: 100%;">
                    <option value="">Semua Jenis Izin</option>
                    <option value="keluar_sebentar" {{ $jenis === 'keluar_sebentar' ? 'selected' : '' }}>Keluar Sebentar</option>
                    <option value="pulang_cepat" {{ $jenis === 'pulang_cepat' ? 'selected' : '' }}>Pulang Cepat</option>
                    <option value="terlambat_masuk" {{ $jenis === 'terlambat_masuk' ? 'selected' : '' }}>Terlambat Masuk</option>
                </select>
            </div>
            <div style="min-width: 160px;">
                <select name="status" class="form-control" style="width: 100%;">
                    <option value="">Semua Status</option>
                    <option value="menunggu_satpam" {{ $status === 'menunggu_satpam' ? 'selected' : '' }}>Menunggu Gerbang</option>
                    <option value="di_luar" {{ $status === 'di_luar' ? 'selected' : '' }}>Di Luar Sekolah</option>
                    <option value="kembali" {{ $status === 'kembali' ? 'selected' : '' }}>Sudah Kembali</option>
                    <option value="pulang_selesai" {{ $status === 'pulang_selesai' ? 'selected' : '' }}>Pulang Selesai</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> Filter</button>
            <a href="{{ route('dashboard.piket.izin.index') }}" class="btn btn-secondary" title="Reset"><i class="fas fa-sync"></i></a>
        </form>
    </div>

    {{-- Datatable Container --}}
    <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">No Tiket</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Siswa & Rombel</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Jenis & Alasan</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Waktu</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Piket / Pembuat</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Status Gerbang</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $item)
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px 18px;">
                            <span style="font-weight: 700; font-family: monospace; color: var(--primary); font-size: 0.85rem;">{{ $item->nomor_tiket }}</span>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">{{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}</div>
                        </td>
                        <td style="padding: 12px 18px;">
                            <div style="font-weight: 600; color: var(--text-heading);">{{ $item->nama_siswa ?? '-' }}</div>
                            <div style="font-size: 0.78rem; color: var(--text-muted);">
                                NISN: {{ $item->nisn ?? '-' }} &bull; {{ $item->nama_rombel ?? '-' }}
                            </div>
                        </td>
                        <td style="padding: 12px 18px;">
                            <div>
                                @if ($item->jenis_izin === 'keluar_sebentar')
                                    <span class="badge" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3);">Keluar Sebentar</span>
                                @elseif ($item->jenis_izin === 'pulang_cepat')
                                    <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">Pulang Cepat</span>
                                @else
                                    <span class="badge" style="background: rgba(107, 114, 128, 0.15); color: #6b7280;">{{ ucfirst($item->jenis_izin) }}</span>
                                @endif
                            </div>
                            <div style="font-size: 0.82rem; color: var(--text-heading); margin-top: 4px; max-width: 220px; white-space: normal;">
                                {{ $item->alasan }}
                            </div>
                        </td>
                        <td style="padding: 12px 18px; font-size: 0.82rem;">
                            <div>Keluar: <strong>{{ substr($item->jam_izin_keluar, 0, 5) }}</strong></div>
                            @if ($item->jam_rencana_kembali)
                                <div style="color: var(--text-muted);">Rencana: {{ substr($item->jam_rencana_kembali, 0, 5) }}</div>
                            @endif
                            @if ($item->jam_kembali_aktual)
                                <div style="color: #10b981; font-weight: 600;">Kembali: {{ substr($item->jam_kembali_aktual, 0, 5) }}</div>
                            @endif
                        </td>
                        <td style="padding: 12px 18px; font-size: 0.82rem;">
                            <div style="color: var(--text-heading); font-weight: 500;">{{ $item->nama_piket ?: $item->created_by ?: 'Guru Piket' }}</div>
                        </td>
                        <td style="padding: 12px 18px;">
                            @if ($item->status === 'menunggu_satpam')
                                <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3);">Menunggu Gerbang</span>
                            @elseif ($item->status === 'di_luar')
                                <span class="badge" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3);">Di Luar</span>
                            @elseif ($item->status === 'kembali')
                                <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);">Sudah Kembali</span>
                            @elseif ($item->status === 'pulang_selesai')
                                <span class="badge" style="background: rgba(99, 102, 241, 0.15); color: #6366f1; border: 1px solid rgba(99, 102, 241, 0.3);">Pulang Selesai</span>
                            @else
                                <span class="badge" style="background: rgba(107, 114, 128, 0.15); color: #6b7280;">{{ ucfirst($item->status) }}</span>
                            @endif
                        </td>
                        <td style="padding: 12px 18px; text-align: center;">
                            <div class="table-actions" style="display: flex; gap: 6px; justify-content: center;">
                                <a href="{{ route('dashboard.piket.izin.cetak', $item->id) }}" target="_blank" class="btn btn-secondary btn-sm" title="Cetak Slip Izin" style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; font-size: 0.78rem;">
                                    <i class="fas fa-print"></i> Slip
                                </a>
                                @if ($canUpdate)
                                    <button type="button" class="btn-icon" title="Edit" onclick="editIzin({{ json_encode($item) }})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                @endif
                                @if ($canDelete)
                                    <form action="{{ route('dashboard.piket.izin.destroy', $item->id) }}" method="POST" data-confirm="delete" data-name="Tiket {{ $item->nomor_tiket }}">
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
                            <i class="fas fa-ticket-alt" style="font-size: 2rem; margin-bottom: 8px; opacity: 0.4;"></i>
                            <p style="margin: 0;">Belum ada data izin keluar-masuk.</p>
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

{{-- Modal Tambah / Edit e-Izin --}}
<div id="modalIzin" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center;">
    <div class="card" style="width: 100%; max-width: 600px; margin: 20px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
            <h3 id="modalIzinTitle" style="font-size: 1.15rem; font-weight: 700; margin: 0; color: var(--text-heading);">Terbitkan e-Izin Siswa</h3>
            <button type="button" onclick="closeModalIzin()" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>

        <form id="formIzin" method="POST" action="{{ route('dashboard.piket.izin.store') }}"
              data-store-url="{{ route('dashboard.piket.izin.store') }}"
              data-update-url="{{ url('/dashboard/piket/izin') }}/:id">
            @csrf
            <input type="hidden" name="_method" id="izinMethod" value="POST">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label class="form-label">Tanggal <span style="color:red;">*</span></label>
                    <input type="date" name="tanggal" id="izin_tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div>
                    <label class="form-label">Jenis Izin <span style="color:red;">*</span></label>
                    <select name="jenis_izin" id="izin_jenis" class="form-control" required>
                        <option value="keluar_sebentar">Keluar Sebentar (Kembali Lagi)</option>
                        <option value="pulang_cepat">Pulang Cepat / Sakit</option>
                        <option value="terlambat_masuk">Dispensasi Terlambat Masuk</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;" id="rowSiswaSelect">
                <div>
                    <label class="form-label">Pilih Kelas / Rombel</label>
                    <select id="filter_rombel_modal" class="form-control" onchange="filterSiswaByRombel()">
                        <option value="">Semua Rombel</option>
                        @foreach ($rombelList as $r)
                            <option value="{{ $r->rombongan_belajar_id }}">{{ $r->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Pilih Peserta Didik <span style="color:red;">*</span></label>
                    <select name="peserta_didik_id" id="izin_siswa_id" class="form-control" required>
                        <option value="">-- Pilih Siswa --</option>
                        @foreach ($siswaList as $s)
                            <option value="{{ $s->peserta_didik_id }}" data-rombel="{{ $s->rombongan_belajar_id }}">
                                {{ $s->nama }} ({{ $s->nisn ?? 'No NISN' }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label class="form-label">Jam Izin Keluar <span style="color:red;">*</span></label>
                    <input type="time" name="jam_izin_keluar" id="izin_jam_keluar" class="form-control" value="{{ date('H:i') }}" required>
                </div>
                <div>
                    <label class="form-label">Jam Rencana Kembali</label>
                    <input type="time" name="jam_rencana_kembali" id="izin_jam_kembali" class="form-control">
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label class="form-label">Alasan Izin <span style="color:red;">*</span></label>
                <textarea name="alasan" id="izin_alasan" class="form-control" rows="3" placeholder="Contoh: Mengambil berkas di rumah / Sakit demam di UKS & dijemput orang tua..." required></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-secondary" onclick="closeModalIzin()">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Terbitkan Izin</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.piketRoutes = {
        searchSiswa: "{{ route('dashboard.piket.izin.search-siswa') }}"
    };
</script>
<script src="{{ asset('js/piket-izin.js') }}"></script>
@endpush
