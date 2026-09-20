@extends('layouts.dashboard')

@section('title', 'Buku Kunjungan - Perpustakaan')

@section('content')
<div class="dash-content-inner">
    <!-- Header Page -->
    <div class="dash-header-section" style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--text-heading); margin-bottom: 4px;">
                    <i class="fas fa-user-check" style="color: var(--primary-color); margin-right: 8px;"></i>
                    Buku Kunjungan Perpustakaan
                </h1>
                <p style="color: var(--text-muted); font-size: 0.88rem; margin: 0;">
                    Pencatatan presensi dan log pengunjung harian perpustakaan sekolah.
                </p>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn btn-primary" id="btnTambahKunjungan" style="display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fas fa-pen-fancy"></i> Catat Pengunjung
                </button>
            </div>
        </div>
    </div>

    <!-- Statistik Cards -->
    <div class="grid-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="card stat-card" style="padding: 16px; border-left: 4px solid #3b82f6;">
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">Pengunjung Hari Ini</div>
            <div style="font-size: 1.6rem; font-weight: 700; color: #3b82f6; margin-top: 4px;">{{ number_format($stats['hari_ini'] ?? 0) }}</div>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;"><i class="fas fa-calendar-day"></i> {{ date('d M Y') }}</div>
        </div>
        <div class="card stat-card" style="padding: 16px; border-left: 4px solid #10b981;">
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">Pengunjung Bulan Ini</div>
            <div style="font-size: 1.6rem; font-weight: 700; color: #10b981; margin-top: 4px;">{{ number_format($stats['bulan_ini'] ?? 0) }}</div>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;"><i class="fas fa-calendar-alt"></i> Bulan {{ date('F Y') }}</div>
        </div>
        <div class="card stat-card" style="padding: 16px; border-left: 4px solid #8b5cf6;">
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">Peserta Didik</div>
            <div style="font-size: 1.6rem; font-weight: 700; color: #8b5cf6; margin-top: 4px;">{{ number_format($stats['siswa'] ?? 0) }}</div>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;"><i class="fas fa-user-graduate"></i> Kunjungan siswa</div>
        </div>
        <div class="card stat-card" style="padding: 16px; border-left: 4px solid #f59e0b;">
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">Guru & Staf</div>
            <div style="font-size: 1.6rem; font-weight: 700; color: #f59e0b; margin-top: 4px;">{{ number_format($stats['guru_staf'] ?? 0) }}</div>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;"><i class="fas fa-chalkboard-teacher"></i> Kunjungan GTK</div>
        </div>
    </div>

    <!-- Filter & Live Search Card -->
    <div class="card" style="padding: 16px; margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; flex: 1;">
                <div style="position: relative; min-width: 240px; flex: 1;">
                    <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.85rem;"></i>
                    <input type="text" id="searchKunjungan" class="form-control" placeholder="Cari nama, kelas, atau keperluan..." value="{{ $q ?? '' }}" style="padding-left: 36px;">
                </div>
                <div style="min-width: 160px;">
                    <input type="date" id="filterTanggal" class="form-control" value="{{ $tanggal ?? '' }}" title="Filter Tanggal">
                </div>
                <div style="min-width: 160px;">
                    <select id="filterTipe" class="form-control">
                        <option value="">-- Semua Tipe --</option>
                        <option value="siswa" {{ ($tipe ?? '') === 'siswa' ? 'selected' : '' }}>Peserta Didik</option>
                        <option value="guru" {{ ($tipe ?? '') === 'guru' ? 'selected' : '' }}>Guru</option>
                        <option value="tendik" {{ ($tipe ?? '') === 'tendik' ? 'selected' : '' }}>Tendik</option>
                        <option value="tamu" {{ ($tipe ?? '') === 'tamu' ? 'selected' : '' }}>Tamu / Umum</option>
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
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Waktu</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Pengunjung</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Tipe & Kelas / Unit</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Keperluan</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 80px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($kunjungan as $idx => $row)
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 14px 18px; font-size: 0.88rem; color: var(--text-muted);">
                            {{ ($kunjungan->currentPage() - 1) * $kunjungan->perPage() + $loop->iteration }}
                        </td>
                        <td style="padding: 14px 18px; font-size: 0.85rem;">
                            <div style="font-weight: 600; color: var(--text-heading);">
                                {{ \Carbon\Carbon::parse($row->tanggal)->format('d M Y') }}
                            </div>
                            <div style="color: var(--text-muted); font-size: 0.78rem; font-family: monospace;">
                                <i class="far fa-clock"></i> {{ substr($row->jam_kunjung ?? '00:00', 0, 5) }} WIB
                            </div>
                        </td>
                        <td style="padding: 14px 18px;">
                            <div style="font-weight: 600; color: var(--text-heading); font-size: 0.9rem;">
                                {{ $row->nama }}
                            </div>
                        </td>
                        <td style="padding: 14px 18px;">
                            <span class="badge" style="background-color: {{ $row->pengunjung_tipe === 'siswa' ? '#e0e7ff' : '#fef3c7' }}; color: {{ $row->pengunjung_tipe === 'siswa' ? '#3730a3' : '#92400e' }}; font-size: 0.75rem; padding: 2px 8px; border-radius: 4px;">
                                {{ strtoupper($row->pengunjung_tipe) }}
                            </span>
                            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                                {{ $row->rombel_atau_unit ?: '-' }}
                            </div>
                        </td>
                        <td style="padding: 14px 18px; font-size: 0.88rem; color: var(--text-heading);">
                            {{ $row->keperluan }}
                        </td>
                        <td style="padding: 14px 18px; text-align: center;">
                            <div class="table-actions" style="display: flex; gap: 6px; justify-content: center;">
                                <form action="{{ route('dashboard.perpustakaan.kunjungan.destroy', $row->id) }}" method="POST" style="display: inline;" data-confirm="delete">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-icon btn-delete-kunjungan" data-nama="{{ $row->nama }}" title="Hapus Log" style="background-color: #fee2e2; color: #ef4444; width: 32px; height: 32px; border-radius: 6px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-trash-alt" style="font-size: 0.8rem;"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 36px; color: var(--text-muted);">
                            <i class="fas fa-user-friends" style="font-size: 2.2rem; margin-bottom: 10px; opacity: 0.4;"></i>
                            <p style="margin: 0; font-size: 0.95rem;">Belum ada data kunjungan perpustakaan.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginasi Baku SAE -->
    @if ($kunjungan->hasPages())
        <div class="custom-pagination">
            @if ($kunjungan->onFirstPage())
                <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
            @else
                <a href="{{ $kunjungan->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
            @endif
            @php
                $cur = $kunjungan->currentPage();
                $last = $kunjungan->lastPage();
                $from = max(1, $cur - 2);
                $to = min($last, $cur + 2);
            @endphp
            @if ($from > 1)
                <a href="{{ $kunjungan->url(1) }}" class="page-btn">1</a>
                @if ($from > 2) <span class="page-info">&hellip;</span> @endif
            @endif
            @for ($i = $from; $i <= $to; $i++)
                <a href="{{ $kunjungan->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
            @endfor
            @if ($to < $last)
                @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                <a href="{{ $kunjungan->url($last) }}" class="page-btn">{{ $last }}</a>
            @endif
            @if ($kunjungan->hasMorePages())
                <a href="{{ $kunjungan->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
            @else
                <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
            @endif
        </div>
    @endif
</div>

<!-- Modal Catat Kunjungan -->
<div id="modalKunjungan" class="modal-backdrop" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center; padding: 20px;">
    <div class="modal-card" style="background: var(--card-bg, #fff); border-radius: 12px; max-width: 500px; width: 100%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-heading);">
                <i class="fas fa-pen-alt" style="color: var(--primary-color); margin-right: 8px;"></i>
                Catat Kunjungan Baru
            </h3>
            <button type="button" onclick="closeModalKunjungan()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>
        <form action="{{ route('dashboard.perpustakaan.kunjungan.store') }}" method="POST" style="padding: 20px;">
            @csrf
            <div style="display: grid; grid-template-columns: 1fr; gap: 14px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Tanggal *</label>
                        <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Jam Kunjung *</label>
                        <input type="time" name="jam_kunjung" class="form-control" value="{{ date('H:i') }}" required>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px;">
                    <div>
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Tipe *</label>
                        <select name="pengunjung_tipe" class="form-control" required>
                            <option value="siswa">Siswa</option>
                            <option value="guru">Guru</option>
                            <option value="tendik">Tendik</option>
                            <option value="tamu">Tamu</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Nama Lengkap *</label>
                        <input type="text" name="nama" class="form-control" placeholder="Nama pengunjung" required>
                    </div>
                </div>
                <div>
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Kelas / Unit Kerja</label>
                    <input type="text" name="rombel_atau_unit" class="form-control" placeholder="Contoh: XII TKJ 1 atau Tata Usaha">
                </div>
                <div>
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Keperluan Kunjungan *</label>
                    <select name="keperluan" class="form-control" required>
                        <option value="Membaca Buku">Membaca Buku</option>
                        <option value="Meminjam / Mengembalikan Buku">Meminjam / Mengembalikan Buku</option>
                        <option value="Mengerjakan Tugas / Diskusi">Mengerjakan Tugas / Diskusi</option>
                        <option value="Menggunakan Komputer / Internet">Menggunakan Komputer / Internet</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                <button type="button" onclick="closeModalKunjungan()" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Kunjungan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/perpus-kunjungan.js') }}"></script>
@endpush
