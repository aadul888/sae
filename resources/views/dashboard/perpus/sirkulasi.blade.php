@extends('layouts.dashboard')

@section('title', 'Sirkulasi Peminjaman Buku - Perpustakaan')

@section('content')
<div class="dash-content-inner">
    <!-- 1. Header Banner & Actions -->
    <div class="dash-banner" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(59,130,246,0.12); color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0;">
                <i class="fas fa-exchange-alt"></i>
            </div>
            <div>
                <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Sirkulasi Peminjaman &amp; Pengembalian
                </h2>
                <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">
                    Manajemen peminjaman buku, pengembalian, perpanjangan, dan pencatatan denda keterlambatan.
                </div>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <button type="button" class="btn btn-primary" id="btnTambahPeminjaman" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 8px; font-weight: 600;" title="Transaksi Pinjam">
                <i class="fas fa-plus"></i>
                <span>Transaksi Pinjam</span>
            </button>
        </div>
    </div>

    <!-- 2. Stat Grid Baku SAE -->
    <div class="dash-stat-grid" style="margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: #3b82f6;">
                <i class="fas fa-book-reader"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_dipinjam'] ?? 0) }}</div>
                <div class="dash-stat-label">Sedang Dipinjam</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239,68,68,0.12); color: #ef4444;">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['terlambat'] ?? 0) }}</div>
                <div class="dash-stat-label">Jatuh Tempo / Terlambat</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                <i class="fas fa-circle-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_kembali'] ?? 0) }}</div>
                <div class="dash-stat-label">Total Sudah Kembali</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                <i class="fas fa-coins"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">Rp {{ number_format($stats['denda_terkumpul'] ?? 0, 0, ',', '.') }}</div>
                <div class="dash-stat-label">Denda Terkumpul</div>
            </div>
        </div>
    </div>

    <!-- Filter & Live Search Card -->
    <div class="card" style="padding: 16px; margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; flex: 1;">
                <div style="position: relative; min-width: 240px; flex: 1;">
                    <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.85rem;"></i>
                    <input type="text" id="searchSirkulasi" class="form-control" placeholder="Cari kode transaksi, peminjam, atau judul buku..." value="{{ $q ?? '' }}" style="padding-left: 36px;">
                </div>
                <div style="min-width: 180px;">
                    <select id="filterStatus" class="form-control">
                        <option value="">-- Semua Status --</option>
                        <option value="dipinjam" {{ ($status ?? '') === 'dipinjam' ? 'selected' : '' }}>Dipinjam</option>
                        <option value="kembali" {{ ($status ?? '') === 'kembali' ? 'selected' : '' }}>Kembali</option>
                        <option value="terlambat" {{ ($status ?? '') === 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                        <option value="hilang" {{ ($status ?? '') === 'hilang' ? 'selected' : '' }}>Hilang / Rusak</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Datatable Container Standard SAE -->
    <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="background-color: var(--table-header-bg, #f8fafc); border-bottom: 1px solid var(--border-color);">
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 60px;">No</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Kode & Buku</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Peminjam</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Tgl Pinjam / Tempo</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Status</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Denda</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sirkulasi as $idx => $row)
                    @php
                        $isOverdue = ($row->status === 'dipinjam' && \Carbon\Carbon::parse($row->tgl_jatuh_tempo)->isPast());
                    @endphp
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 14px 18px; font-size: 0.88rem; color: var(--text-muted);">
                            {{ ($sirkulasi->currentPage() - 1) * $sirkulasi->perPage() + $loop->iteration }}
                        </td>
                        <td style="padding: 14px 18px;">
                            <div style="font-weight: 600; color: var(--text-heading); font-size: 0.9rem;">
                                {{ $row->buku->judul ?? '-' }}
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); font-family: monospace;">
                                TR: {{ $row->kode_transaksi }} | Kode: {{ $row->buku->kode_buku ?? '-' }}
                            </div>
                        </td>
                        <td style="padding: 14px 18px;">
                            <div style="font-weight: 600; color: var(--text-heading); font-size: 0.88rem;">{{ $row->peminjam_nama }}</div>
                            <span class="badge" style="background-color: {{ $row->peminjam_tipe === 'siswa' ? '#e0e7ff' : '#fef3c7' }}; color: {{ $row->peminjam_tipe === 'siswa' ? '#3730a3' : '#92400e' }}; font-size: 0.72rem; padding: 2px 8px; border-radius: 4px;">
                                {{ strtoupper($row->peminjam_tipe) }}
                            </span>
                        </td>
                        <td style="padding: 14px 18px; font-size: 0.85rem;">
                            <div>Pinjam: {{ \Carbon\Carbon::parse($row->tgl_pinjam)->format('d M Y') }}</div>
                            <div style="color: {{ $isOverdue ? '#ef4444' : 'var(--text-muted)' }}; font-weight: {{ $isOverdue ? '600' : 'normal' }}; font-size: 0.8rem;">
                                Tempo: {{ \Carbon\Carbon::parse($row->tgl_jatuh_tempo)->format('d M Y') }}
                                @if ($isOverdue)
                                    <i class="fas fa-exclamation-circle" title="Terlambat"></i>
                                @endif
                            </div>
                            @if ($row->tgl_kembali)
                                <div style="color: #10b981; font-size: 0.78rem;">Kembali: {{ \Carbon\Carbon::parse($row->tgl_kembali)->format('d M Y') }}</div>
                            @endif
                        </td>
                        <td style="padding: 14px 18px;">
                            @if ($row->status === 'dipinjam')
                                @if ($isOverdue)
                                    <span class="badge" style="background-color: #fee2e2; color: #b91c1c; padding: 4px 10px; border-radius: 12px; font-size: 0.78rem; font-weight: 600;">
                                        Terlambat
                                    </span>
                                @else
                                    <span class="badge" style="background-color: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 12px; font-size: 0.78rem; font-weight: 600;">
                                        Dipinjam
                                    </span>
                                @endif
                            @elseif ($row->status === 'kembali')
                                <span class="badge" style="background-color: #dcfce7; color: #15803d; padding: 4px 10px; border-radius: 12px; font-size: 0.78rem; font-weight: 600;">
                                    Kembali
                                </span>
                            @else
                                <span class="badge" style="background-color: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 12px; font-size: 0.78rem; font-weight: 600;">
                                    {{ ucfirst($row->status) }}
                                </span>
                            @endif
                        </td>
                        <td style="padding: 14px 18px; font-size: 0.88rem;">
                            @if ($row->denda > 0)
                                <div style="font-weight: 700; color: #d97706;">Rp {{ number_format($row->denda, 0, ',', '.') }}</div>
                                <span class="badge" style="background-color: {{ $row->status_denda === 'lunas' ? '#dcfce7' : '#fee2e2' }}; color: {{ $row->status_denda === 'lunas' ? '#15803d' : '#b91c1c' }}; font-size: 0.7rem; padding: 2px 6px;">
                                    {{ strtoupper($row->status_denda) }}
                                </span>
                            @else
                                <span style="color: var(--text-muted); font-size: 0.8rem;">-</span>
                            @endif
                        </td>
                        <td style="padding: 14px 18px; text-align: center;">
                            <div class="table-actions" style="display: flex; gap: 6px; justify-content: center;">
                                @if ($row->status === 'dipinjam')
                                    <button type="button" class="btn-icon btn-kembalikan" data-id="{{ $row->id }}" data-kode="{{ $row->kode_transaksi }}" data-judul="{{ $row->buku->judul ?? 'Buku' }}" title="Proses Pengembalian" style="background-color: #10b981; color: #fff; width: 32px; height: 32px; border-radius: 6px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-undo-alt" style="font-size: 0.8rem;"></i>
                                    </button>
                                @endif
                                <form action="{{ route('dashboard.perpustakaan.sirkulasi.destroy', $row->id) }}" method="POST" style="display: inline;" data-confirm="delete">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-icon btn-delete-sirkulasi" data-kode="{{ $row->kode_transaksi }}" title="Hapus Transaksi" style="background-color: #fee2e2; color: #ef4444; width: 32px; height: 32px; border-radius: 6px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-trash-alt" style="font-size: 0.8rem;"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 36px; color: var(--text-muted);">
                            <i class="fas fa-book-open" style="font-size: 2.2rem; margin-bottom: 10px; opacity: 0.4;"></i>
                            <p style="margin: 0; font-size: 0.95rem;">Belum ada data sirkulasi peminjaman buku.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginasi Baku SAE -->
    @if ($sirkulasi->hasPages())
        <div class="custom-pagination">
            @if ($sirkulasi->onFirstPage())
                <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
            @else
                <a href="{{ $sirkulasi->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
            @endif
            @php
                $cur = $sirkulasi->currentPage();
                $last = $sirkulasi->lastPage();
                $from = max(1, $cur - 2);
                $to = min($last, $cur + 2);
            @endphp
            @if ($from > 1)
                <a href="{{ $sirkulasi->url(1) }}" class="page-btn">1</a>
                @if ($from > 2) <span class="page-info">&hellip;</span> @endif
            @endif
            @for ($i = $from; $i <= $to; $i++)
                <a href="{{ $sirkulasi->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
            @endfor
            @if ($to < $last)
                @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                <a href="{{ $sirkulasi->url($last) }}" class="page-btn">{{ $last }}</a>
            @endif
            @if ($sirkulasi->hasMorePages())
                <a href="{{ $sirkulasi->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
            @else
                <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
            @endif
        </div>
    @endif
</div>

<!-- Modal Tambah Transaksi Pinjam -->
<div id="modalPinjam" class="modal-backdrop" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center; padding: 20px;">
    <div class="modal-card" style="background: var(--card-bg, #fff); border-radius: 12px; max-width: 600px; width: 100%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-heading);">
                <i class="fas fa-plus-circle" style="color: var(--primary-color); margin-right: 8px;"></i>
                Tambah Transaksi Pinjam Buku
            </h3>
            <button type="button" onclick="closeModalPinjam()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>
        <form action="{{ route('dashboard.perpustakaan.sirkulasi.store') }}" method="POST" style="padding: 20px;">
            @csrf
            <div style="display: grid; grid-template-columns: 1fr; gap: 14px;">
                <div>
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Pilih Buku *</label>
                    <select name="buku_id" id="pinjam_buku_id" class="form-control" required>
                        <option value="">-- Cari Judul Buku / Kode --</option>
                        @foreach ($bukuList as $buku)
                            <option value="{{ $buku->id }}">
                                [{{ $buku->kode_buku }}] {{ $buku->judul }} (Tersedia: {{ $buku->eksemplar_tersedia }} eks)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px;">
                    <div>
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Tipe Peminjam *</label>
                        <select name="peminjam_tipe" id="pinjam_tipe" class="form-control" required>
                            <option value="siswa">Peserta Didik</option>
                            <option value="guru">Guru / Tendik</option>
                            <option value="umum">Umum</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Nama Peminjam *</label>
                        <input type="text" name="peminjam_nama" id="pinjam_nama" class="form-control" placeholder="Nama lengkap siswa/guru" required>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Tanggal Pinjam *</label>
                        <input type="date" name="tgl_pinjam" id="pinjam_tgl" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Jatuh Tempo (Kembali) *</label>
                        <input type="date" name="tgl_jatuh_tempo" id="pinjam_tempo" class="form-control" value="{{ date('Y-m-d', strtotime('+7 days')) }}" required>
                    </div>
                </div>
                <div>
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Catatan Tambahan</label>
                    <textarea name="catatan" class="form-control" rows="2" placeholder="Catatan kondisi buku atau kontak peminjam"></textarea>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                <button type="button" onclick="closeModalPinjam()" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Proses Pinjam</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Proses Pengembalian -->
<div id="modalKembalikan" class="modal-backdrop" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center; padding: 20px;">
    <div class="modal-card" style="background: var(--card-bg, #fff); border-radius: 12px; max-width: 500px; width: 100%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-heading);">
                <i class="fas fa-undo-alt" style="color: #10b981; margin-right: 8px;"></i>
                Pengembalian Buku
            </h3>
            <button type="button" onclick="closeModalKembalikan()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>
        <form id="formKembalikan" action="" method="POST" style="padding: 20px;">
            @csrf
            <div style="display: grid; grid-template-columns: 1fr; gap: 14px;">
                <div style="background: var(--table-header-bg, #f8fafc); padding: 12px; border-radius: 8px;">
                    <div style="font-size: 0.8rem; color: var(--text-muted);">Transaksi / Judul:</div>
                    <div id="kembaliDetailJudul" style="font-weight: 600; color: var(--text-heading); font-size: 0.95rem;">-</div>
                </div>
                <div>
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Tanggal Kembali *</label>
                    <input type="date" name="tgl_kembali" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div>
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Denda Keterlambatan / Kerusakan (Rp)</label>
                    <input type="number" name="denda" class="form-control" value="0" min="0" step="500">
                </div>
                <div>
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Status Denda</label>
                    <select name="status_denda" class="form-control">
                        <option value="lunas">Lunas</option>
                        <option value="belum">Belum Dibayar</option>
                    </select>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                <button type="button" onclick="closeModalKembalikan()" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary" style="background-color: #10b981; border-color: #10b981;"><i class="fas fa-check"></i> Simpan Pengembalian</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/perpus-sirkulasi.js') }}"></script>
@endpush
