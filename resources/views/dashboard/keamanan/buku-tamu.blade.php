@extends('layouts.dashboard')

@section('title', 'Buku Tamu Pos Keamanan')

@section('content')
<div class="content-wrapper">
    {{-- Header --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--text-heading); margin-bottom: 4px;">
                <i class="fas fa-book-open" style="color: var(--primary); margin-right: 8px;"></i>
                Buku Tamu Pos Keamanan
            </h1>
            <p style="font-size: 0.875rem; color: var(--text-muted); margin: 0;">
                Pencatatan identitas, tujuan kunjungan, dan pemantauan tamu yang berada di lingkungan sekolah.
            </p>
        </div>
        @if ($canCreate)
            <div>
                <button type="button" class="btn btn-primary" onclick="openModalTamu()" style="display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-plus"></i>
                    <span>Catat Tamu Masuk</span>
                </button>
            </div>
        @endif
    </div>

    {{-- Stats Cards --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="card" style="padding: 16px; border-left: 4px solid var(--primary);">
            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Total Kunjungan</div>
            <div style="font-size: 1.6rem; font-weight: 700; color: var(--text-heading); margin-top: 4px;">{{ $stats['total'] }}</div>
        </div>
        <div class="card" style="padding: 16px; border-left: 4px solid #3b82f6;">
            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Tamu Hari Ini</div>
            <div style="font-size: 1.6rem; font-weight: 700; color: #3b82f6; margin-top: 4px;">{{ $stats['hari_ini'] }}</div>
        </div>
        <div class="card" style="padding: 16px; border-left: 4px solid #f59e0b;">
            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Sedang di Lokasi</div>
            <div style="font-size: 1.6rem; font-weight: 700; color: #f59e0b; margin-top: 4px;">{{ $stats['di_lokasi'] }}</div>
        </div>
        <div class="card" style="padding: 16px; border-left: 4px solid #10b981;">
            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Sudah Keluar</div>
            <div style="font-size: 1.6rem; font-weight: 700; color: #10b981; margin-top: 4px;">{{ $stats['sudah_keluar'] }}</div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="card" style="padding: 16px; margin-bottom: 20px;">
        <form method="GET" action="{{ route('dashboard.keamanan.buku-tamu.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <div style="flex: 1; min-width: 220px;">
                <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Cari nama tamu, instansi, keperluan..." style="width: 100%;">
            </div>
            <div style="min-width: 150px;">
                <input type="date" name="tanggal" value="{{ $tanggal }}" class="form-control" style="width: 100%;">
            </div>
            <div style="min-width: 160px;">
                <select name="status" class="form-control" style="width: 100%;">
                    <option value="">Semua Status</option>
                    <option value="berada_di_lokasi" {{ $status === 'berada_di_lokasi' ? 'selected' : '' }}>Di Lokasi</option>
                    <option value="sudah_keluar" {{ $status === 'sudah_keluar' ? 'selected' : '' }}>Sudah Keluar</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> Filter</button>
            <a href="{{ route('dashboard.keamanan.buku-tamu.index') }}" class="btn btn-secondary" title="Reset"><i class="fas fa-sync"></i></a>
        </form>
    </div>

    {{-- Datatable Container --}}
    <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Tanggal & Waktu</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Tamu & Instansi</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Tujuan & Keperluan</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Visitor / Kendaraan</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Status</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $item)
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px 18px;">
                            <div style="font-weight: 600; color: var(--text-heading);">{{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}</div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">
                                In: <strong>{{ substr($item->jam_masuk, 0, 5) }}</strong>
                                @if ($item->jam_keluar)
                                    &bull; Out: <strong>{{ substr($item->jam_keluar, 0, 5) }}</strong>
                                @endif
                            </div>
                        </td>
                        <td style="padding: 12px 18px;">
                            <div style="font-weight: 600; color: var(--text-heading);">{{ $item->nama_tamu }}</div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">
                                {{ $item->instansi_asal ?: 'Umum / Pribadi' }}
                                @if ($item->nomor_kontak)
                                    &bull; Telp: {{ $item->nomor_kontak }}
                                @endif
                            </div>
                        </td>
                        <td style="padding: 12px 18px;">
                            <div style="font-weight: 600; color: var(--text-heading); font-size: 0.85rem;">Bertemu: {{ $item->tujuan_bertemu }}</div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">Hal: {{ $item->keperluan }}</div>
                        </td>
                        <td style="padding: 12px 18px; font-size: 0.82rem;">
                            @if ($item->nomor_kartu_visitor)
                                <div><span class="badge" style="background: rgba(99, 102, 241, 0.15); color: #6366f1;">Kartu: {{ $item->nomor_kartu_visitor }}</span></div>
                            @endif
                            @if ($item->nomor_polisi_kendaraan)
                                <div style="color: var(--text-muted); margin-top: 2px;">Nopol: {{ $item->nomor_polisi_kendaraan }}</div>
                            @endif
                        </td>
                        <td style="padding: 12px 18px;">
                            @if ($item->status === 'berada_di_lokasi')
                                <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3);">Di Lokasi</span>
                            @else
                                <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);">Sudah Keluar</span>
                            @endif
                        </td>
                        <td style="padding: 12px 18px; text-align: center;">
                            <div class="table-actions" style="display: flex; gap: 6px; justify-content: center;">
                                @if ($item->status === 'berada_di_lokasi')
                                    <form action="{{ route('dashboard.keamanan.buku-tamu.checkout', $item->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-warning btn-sm" title="Checkout Tamu Keluar" style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; font-size: 0.78rem;">
                                            <i class="fas fa-sign-out-alt"></i> Out
                                        </button>
                                    </form>
                                @endif
                                @if ($canUpdate)
                                    <button type="button" class="btn-icon" title="Edit" onclick="editTamu({{ json_encode($item) }})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                @endif
                                @if ($canDelete)
                                    <form action="{{ route('dashboard.keamanan.buku-tamu.destroy', $item->id) }}" method="POST" data-confirm="delete" data-name="{{ $item->nama_tamu }}">
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
                            <i class="fas fa-user-friends" style="font-size: 2rem; margin-bottom: 8px; opacity: 0.4;"></i>
                            <p style="margin: 0;">Belum ada catatan tamu.</p>
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

{{-- Modal Tambah / Edit Tamu --}}
<div id="modalTamu" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center;">
    <div class="card" style="width: 100%; max-width: 600px; margin: 20px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
            <h3 id="modalTamuTitle" style="font-size: 1.15rem; font-weight: 700; margin: 0; color: var(--text-heading);">
                Catat Tamu Masuk
            </h3>
            <button type="button" onclick="closeModalTamu()" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>

        <form id="formTamu" method="POST" action="{{ route('dashboard.keamanan.buku-tamu.store') }}"
              data-store-url="{{ route('dashboard.keamanan.buku-tamu.store') }}"
              data-update-url="{{ url('/dashboard/keamanan/buku-tamu') }}/:id">
            @csrf
            <input type="hidden" name="_method" id="tamuMethod" value="POST">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label class="form-label">Tanggal <span style="color:red;">*</span></label>
                    <input type="date" name="tanggal" id="tamu_tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div>
                    <label class="form-label">Jam Masuk <span style="color:red;">*</span></label>
                    <input type="time" name="jam_masuk" id="tamu_jam_masuk" class="form-control" value="{{ date('H:i') }}" required>
                </div>
            </div>

            <div style="margin-bottom: 12px;">
                <label class="form-label">Nama Tamu <span style="color:red;">*</span></label>
                <input type="text" name="nama_tamu" id="tamu_nama" class="form-control" placeholder="Nama lengkap tamu" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label class="form-label">Instansi / Lembaga Asal</label>
                    <input type="text" name="instansi_asal" id="tamu_instansi" class="form-control" placeholder="Contoh: Dinas Pendidikan, Orang Tua">
                </div>
                <div>
                    <label class="form-label">Nomor Kontak / HP</label>
                    <input type="text" name="nomor_kontak" id="tamu_kontak" class="form-control" placeholder="08xxxxxxxx">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label class="form-label">Tujuan Bertemu <span style="color:red;">*</span></label>
                    <input type="text" name="tujuan_bertemu" id="tamu_tujuan" class="form-control" placeholder="Kepala Sekolah, Wakasek, Guru, TU..." required>
                </div>
                <div>
                    <label class="form-label">Keperluan <span style="color:red;">*</span></label>
                    <input type="text" name="keperluan" id="tamu_keperluan" class="form-control" placeholder="Urusan dinas, konsultasi nilai..." required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label class="form-label">Nomor Kartu Visitor</label>
                    <input type="text" name="nomor_kartu_visitor" id="tamu_kartu" class="form-control" placeholder="Contoh: V-01">
                </div>
                <div>
                    <label class="form-label">Nomor Polisi Kendaraan</label>
                    <input type="text" name="nomor_polisi_kendaraan" id="tamu_nopol" class="form-control" placeholder="Contoh: B 1234 ABC">
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
<script src="{{ asset('js/keamanan-buku-tamu.js') }}"></script>
@endpush
