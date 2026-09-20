@extends('layouts.dashboard')

@section('title', 'Verifikasi e-Izin Gerbang - Keamanan')

@section('content')
<div class="content-wrapper">
    {{-- Header --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--text-heading); margin-bottom: 4px;">
                <i class="fas fa-shield-alt" style="color: var(--primary); margin-right: 8px;"></i>
                Verifikasi e-Izin Keluar-Masuk Siswa
            </h1>
            <p style="font-size: 0.875rem; color: var(--text-muted); margin: 0;">
                Validasi dan verifikasi siswa keluar-masuk gerbang sekolah berbasis tiket digital / QR code.
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="button" class="btn btn-primary" onclick="openModalScan()" style="display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-qrcode"></i>
                <span>Scan / Input Tiket</span>
            </button>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="card" style="padding: 16px; border-left: 4px solid var(--primary);">
            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Total Izin Hari Ini</div>
            <div style="font-size: 1.6rem; font-weight: 700; color: var(--text-heading); margin-top: 4px;">{{ $stats['total'] }}</div>
        </div>
        <div class="card" style="padding: 16px; border-left: 4px solid #f59e0b;">
            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Menunggu Gerbang</div>
            <div style="font-size: 1.6rem; font-weight: 700; color: #f59e0b; margin-top: 4px;">{{ $stats['menunggu'] }}</div>
        </div>
        <div class="card" style="padding: 16px; border-left: 4px solid #3b82f6;">
            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Sedang di Luar</div>
            <div style="font-size: 1.6rem; font-weight: 700; color: #3b82f6; margin-top: 4px;">{{ $stats['di_luar'] }}</div>
        </div>
        <div class="card" style="padding: 16px; border-left: 4px solid #10b981;">
            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Sudah Kembali</div>
            <div style="font-size: 1.6rem; font-weight: 700; color: #10b981; margin-top: 4px;">{{ $stats['kembali'] }}</div>
        </div>
        <div class="card" style="padding: 16px; border-left: 4px solid #6366f1;">
            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Pulang Selesai</div>
            <div style="font-size: 1.6rem; font-weight: 700; color: #6366f1; margin-top: 4px;">{{ $stats['pulang'] }}</div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="card" style="padding: 16px; margin-bottom: 20px;">
        <form method="GET" action="{{ route('dashboard.keamanan.izin.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <div style="flex: 1; min-width: 220px;">
                <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Cari nomor tiket, nama siswa, NISN..." style="width: 100%;">
            </div>
            <div style="min-width: 150px;">
                <input type="date" name="tanggal" value="{{ $tanggal }}" class="form-control" style="width: 100%;">
            </div>
            <div style="min-width: 160px;">
                <select name="status" class="form-control" style="width: 100%;">
                    <option value="">Semua Status</option>
                    <option value="menunggu_satpam" {{ $status === 'menunggu_satpam' ? 'selected' : '' }}>Menunggu Gerbang</option>
                    <option value="di_luar" {{ $status === 'di_luar' ? 'selected' : '' }}>Di Luar</option>
                    <option value="kembali" {{ $status === 'kembali' ? 'selected' : '' }}>Sudah Kembali</option>
                    <option value="pulang_selesai" {{ $status === 'pulang_selesai' ? 'selected' : '' }}>Pulang Selesai</option>
                    <option value="dibatalkan" {{ $status === 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> Filter</button>
            <a href="{{ route('dashboard.keamanan.izin.index') }}" class="btn btn-secondary" title="Reset"><i class="fas fa-sync"></i></a>
        </form>
    </div>

    {{-- Datatable Container --}}
    <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">No Tiket</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Siswa / Rombel</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Jenis Izin</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Alasan</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Waktu Izin</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Status Gerbang</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Aksi Gerbang</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $item)
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px 18px;">
                            <span style="font-weight: 700; font-family: monospace; font-size: 0.85rem; color: var(--primary);">{{ $item->nomor_tiket }}</span>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">{{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}</div>
                        </td>
                        <td style="padding: 12px 18px;">
                            <div style="font-weight: 600; color: var(--text-heading);">{{ $item->nama_siswa ?? '-' }}</div>
                            <div style="font-size: 0.78rem; color: var(--text-muted);">
                                NISN: {{ $item->nisn ?? '-' }} &bull; {{ $item->nama_rombel ?? '-' }}
                            </div>
                        </td>
                        <td style="padding: 12px 18px;">
                            @if ($item->jenis_izin === 'keluar_sebentar')
                                <span class="badge" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3);">Keluar Sebentar</span>
                            @elseif ($item->jenis_izin === 'pulang_cepat')
                                <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">Pulang Cepat</span>
                            @else
                                <span class="badge" style="background: rgba(107, 114, 128, 0.15); color: #6b7280; border: 1px solid rgba(107, 114, 128, 0.3);">{{ ucfirst($item->jenis_izin) }}</span>
                            @endif
                        </td>
                        <td style="padding: 12px 18px; max-width: 220px;">
                            <div style="font-size: 0.85rem; color: var(--text-heading); white-space: normal;">{{ $item->alasan }}</div>
                        </td>
                        <td style="padding: 12px 18px; font-size: 0.82rem;">
                            <div>Keluar: <strong>{{ substr($item->jam_izin_keluar, 0, 5) }}</strong></div>
                            @if ($item->jam_rencana_kembali)
                                <div style="color: var(--text-muted);">Rencana: {{ substr($item->jam_rencana_kembali, 0, 5) }}</div>
                            @endif
                            @if ($item->jam_kembali_aktual)
                                <div style="color: #10b981; font-weight: 600;">Aktual: {{ substr($item->jam_kembali_aktual, 0, 5) }}</div>
                            @endif
                        </td>
                        <td style="padding: 12px 18px;">
                            @if ($item->status === 'menunggu_satpam')
                                <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3);">Menunggu Gerbang</span>
                            @elseif ($item->status === 'di_luar')
                                <span class="badge" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3);">Di Luar Sekolah</span>
                                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">Oleh: {{ $item->satpam_checkout_by }}</div>
                            @elseif ($item->status === 'kembali')
                                <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);">Sudah Kembali</span>
                                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">Oleh: {{ $item->satpam_checkin_by }}</div>
                            @elseif ($item->status === 'pulang_selesai')
                                <span class="badge" style="background: rgba(99, 102, 241, 0.15); color: #6366f1; border: 1px solid rgba(99, 102, 241, 0.3);">Pulang Selesai</span>
                            @else
                                <span class="badge" style="background: rgba(107, 114, 128, 0.15); color: #6b7280; border: 1px solid rgba(107, 114, 128, 0.3);">{{ ucfirst($item->status) }}</span>
                            @endif
                        </td>
                        <td style="padding: 12px 18px; text-align: center;">
                            <div class="table-actions" style="display: flex; gap: 6px; justify-content: center;">
                                @if ($item->status === 'menunggu_satpam')
                                    <form action="{{ route('dashboard.keamanan.izin.checkout', $item->id) }}" method="POST" data-confirm-action="checkout" data-name="{{ $item->nama_siswa }}">
                                        @csrf
                                        <button type="submit" class="btn btn-warning btn-sm" title="Verifikasi Keluar Gerbang" style="display: inline-flex; align-items: center; gap: 4px; padding: 5px 10px; font-size: 0.78rem;">
                                            <i class="fas fa-sign-out-alt"></i> Out
                                        </button>
                                    </form>
                                @elseif ($item->status === 'di_luar' && $item->jenis_izin === 'keluar_sebentar')
                                    <form action="{{ route('dashboard.keamanan.izin.checkin', $item->id) }}" method="POST" data-confirm-action="checkin" data-name="{{ $item->nama_siswa }}">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm" title="Verifikasi Kembali Masuk Gerbang" style="display: inline-flex; align-items: center; gap: 4px; padding: 5px 10px; font-size: 0.78rem;">
                                            <i class="fas fa-sign-in-alt"></i> In
                                        </button>
                                    </form>
                                @else
                                    <span style="font-size: 0.8rem; color: var(--text-muted);">-</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 36px; color: var(--text-muted);">
                            <i class="fas fa-ticket-alt" style="font-size: 2rem; margin-bottom: 8px; opacity: 0.4;"></i>
                            <p style="margin: 0;">Tidak ada data izin keluar-masuk pada filter ini.</p>
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

{{-- Modal Scan / Input Tiket Cepat --}}
<div id="modalScanTiket" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center;">
    <div class="card" style="width: 100%; max-width: 520px; margin: 20px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
            <h3 style="font-size: 1.15rem; font-weight: 700; margin: 0; color: var(--text-heading);">
                <i class="fas fa-qrcode" style="color: var(--primary); margin-right: 8px;"></i>
                Scan / Masukkan Nomor Tiket e-Izin
            </h3>
            <button type="button" onclick="closeModalScan()" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>

        <div style="margin-bottom: 16px;">
            <label class="form-label" style="font-weight: 600;">Nomor Tiket e-Izin (Scan Barcode/Ketik):</label>
            <div style="display: flex; gap: 8px;">
                <input type="text" id="inputScanTiket" class="form-control" placeholder="Contoh: IZN-20260921-0001" style="font-family: monospace; font-size: 1.1rem; text-transform: uppercase;">
                <button type="button" class="btn btn-primary" id="btnCariTiket" onclick="cariTiketScan()"><i class="fas fa-search"></i> Cek</button>
            </div>
        </div>

        {{-- Hasil Pencarian Tiket --}}
        <div id="scanResultContainer" style="display: none; padding: 14px; border-radius: 8px; background: var(--bg-card); border: 1px solid var(--border-color); margin-bottom: 16px;">
            <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 4px;">Informasi Tiket:</div>
            <div id="resNomorTiket" style="font-weight: 700; font-family: monospace; color: var(--primary); font-size: 1.1rem;"></div>
            <div id="resNamaSiswa" style="font-weight: 600; font-size: 1rem; color: var(--text-heading); margin-top: 4px;"></div>
            <div id="resRombel" style="font-size: 0.82rem; color: var(--text-muted);"></div>
            <div style="margin-top: 8px; font-size: 0.85rem;" id="resAlasan"></div>
            <div style="margin-top: 8px; font-size: 0.85rem;" id="resStatus"></div>

            <div id="scanActionContainer" style="margin-top: 16px; display: flex; gap: 8px;"></div>
        </div>

        <div style="display: flex; justify-content: flex-end;">
            <button type="button" class="btn btn-secondary" onclick="closeModalScan()">Tutup</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.keamananRoutes = {
        verifikasiTiket: "{{ route('dashboard.keamanan.izin.verifikasi') }}",
        checkoutBase: "{{ url('/dashboard/keamanan/izin/checkout') }}",
        checkinBase: "{{ url('/dashboard/keamanan/izin/checkin') }}"
    };
</script>
<script src="{{ asset('js/keamanan-izin.js') }}"></script>
@endpush
