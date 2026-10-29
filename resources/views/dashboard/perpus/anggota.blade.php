@extends('layouts.dashboard')

@section('title', 'Anggota Perpustakaan')

@section('content')
    <div class="dash-content-inner">
        <div class="dash-banner" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:20px;">
            <div style="display:flex;align-items:center;gap:14px;">
                <div style="width:48px;height:48px;border-radius:var(--radius-lg,12px);background:rgba(139,92,246,0.12);color:#8b5cf6;display:flex;align-items:center;justify-content:center;font-size:var(--text-2xl,1.5rem);flex-shrink:0;">
                    <i class="fas fa-id-card"></i>
                </div>
                <div>
                    <h2 style="font-size:var(--text-xl,1.25rem);font-weight:800;color:var(--text-color);margin:0;">Registrasi Anggota &amp; Kartu Digital</h2>
                    <div style="font-size:var(--text-sm,0.8125rem);color:var(--text-muted);margin-top:2px;">
                        Kelola anggota perpustakaan (siswa, GTK, umum) beserta kartu anggota digital ber-QR.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions" style="display:flex;gap:8px;flex-wrap:wrap;">
                <a href="{{ route('dashboard.perpustakaan.anggota.kartu.cetak') }}" target="_blank" class="btn btn-outline"
                    style="display:inline-flex;align-items:center;gap:6px;height:38px;padding:0 14px;border-radius:var(--radius-md,8px);">
                    <i class="fas fa-print"></i> Cetak Kartu Massal
                </a>
                <button type="button" class="btn btn-primary" onclick="openModalAnggota()"
                    style="display:inline-flex;align-items:center;gap:6px;height:38px;padding:0 14px;border-radius:var(--radius-md,8px);">
                    <i class="fas fa-plus"></i> Daftarkan Anggota
                </button>
            </div>
        </div>

        @include('dashboard.perpus.partials.tab-nav')

        @if (session('error'))
            <div class="alert alert-danger" style="margin-bottom:16px;"><i class="fas fa-circle-exclamation"></i> {{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="alert alert-success" style="margin-bottom:16px;"><i class="fas fa-circle-check"></i> {{ session('success') }}</div>
        @endif

        <div class="dash-stat-grid" style="margin-bottom:20px;">
            <div class="dash-stat-card"><div class="dash-stat-icon" style="background:rgba(139,92,246,0.12);color:#8b5cf6;"><i class="fas fa-users"></i></div>
                <div class="dash-stat-info"><div class="dash-stat-value">{{ number_format($stats['total'], 0, ',', '.') }}</div><div class="dash-stat-label">Total Anggota</div></div></div>
            <div class="dash-stat-card"><div class="dash-stat-icon" style="background:rgba(59,130,246,0.12);color:#3b82f6;"><i class="fas fa-user-graduate"></i></div>
                <div class="dash-stat-info"><div class="dash-stat-value">{{ number_format($stats['siswa'], 0, ',', '.') }}</div><div class="dash-stat-label">Peserta Didik</div></div></div>
            <div class="dash-stat-card"><div class="dash-stat-icon" style="background:rgba(16,185,129,0.12);color:#10b981;"><i class="fas fa-chalkboard-teacher"></i></div>
                <div class="dash-stat-info"><div class="dash-stat-value">{{ number_format($stats['gtk'], 0, ',', '.') }}</div><div class="dash-stat-label">GTK</div></div></div>
            <div class="dash-stat-card"><div class="dash-stat-icon" style="background:rgba(245,158,11,0.12);color:#f59e0b;"><i class="fas fa-user"></i></div>
                <div class="dash-stat-info"><div class="dash-stat-value">{{ number_format($stats['umum'], 0, ',', '.') }}</div><div class="dash-stat-label">Umum</div></div></div>
        </div>

        <div class="card" style="padding:0;overflow:hidden;">
            <form method="GET" action="{{ route('dashboard.perpustakaan.anggota.index') }}"
                style="padding:14px 16px;border-bottom:1px solid var(--border-color);display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Cari nama / NISN / nomor anggota..."
                    style="height:38px;max-width:280px;">
                <select name="tipe" class="form-control" style="height:38px;max-width:170px;">
                    <option value="">Semua Tipe</option>
                    <option value="siswa" {{ $tipe === 'siswa' ? 'selected' : '' }}>Peserta Didik</option>
                    <option value="gtk" {{ $tipe === 'gtk' ? 'selected' : '' }}>GTK</option>
                    <option value="umum" {{ $tipe === 'umum' ? 'selected' : '' }}>Umum</option>
                </select>
                <button type="submit" class="btn btn-primary" style="height:38px;padding:0 16px;"><i class="fas fa-filter"></i> Filter</button>
            </form>

            <div style="overflow-x:auto;">
                <table class="table-minimal-compact" style="width:100%;">
                    <thead>
                        <tr style="border-bottom:1px solid var(--border-color);">
                            <th style="padding:10px;">No. Anggota</th>
                            <th style="padding:10px;">Nama</th>
                            <th style="padding:10px;">Tipe</th>
                            <th style="padding:10px;">NISN/NIP</th>
                            <th style="padding:10px;">Rombel/Unit</th>
                            <th style="padding:10px;">Status</th>
                            <th style="padding:10px;text-align:center;width:170px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $a)
                            <tr style="border-bottom:1px solid var(--border-color);">
                                <td style="padding:10px;" class="pp-mono"><span style="font-family:monospace;font-weight:700;">{{ $a->nomor_anggota }}</span></td>
                                <td style="padding:10px;font-weight:600;">{{ $a->nama }}</td>
                                <td style="padding:10px;">{{ ucfirst($a->tipe) }}</td>
                                <td style="padding:10px;font-family:monospace;">{{ $a->nisn_nip ?: '-' }}</td>
                                <td style="padding:10px;">{{ $a->rombel_atau_unit ?: '-' }}</td>
                                <td style="padding:10px;">
                                    <span class="badge {{ $a->is_active ? 'badge-success' : 'badge-danger' }}">{{ $a->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                </td>
                                <td style="padding:10px;text-align:center;">
                                    <a href="{{ route('dashboard.perpustakaan.anggota.kartu', $a->id) }}" target="_blank" class="btn btn-sm btn-outline" title="Kartu Digital" style="padding:4px 8px;">
                                        <i class="fas fa-id-card"></i>
                                    </a>
                                    <form method="POST" action="{{ route('dashboard.perpustakaan.anggota.toggle', $a->id) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline" title="{{ $a->is_active ? 'Nonaktifkan' : 'Aktifkan' }}" style="padding:4px 8px;">
                                            <i class="fas fa-power-off"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('dashboard.perpustakaan.anggota.destroy', $a->id) }}" style="display:inline;" onsubmit="return confirm('Hapus anggota ini?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline" title="Hapus" style="padding:4px 8px;color:#ef4444;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" style="padding:28px;text-align:center;color:var(--text-muted);">Belum ada anggota terdaftar.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div style="padding:14px 16px;">{{ $list->links() }}</div>
        </div>
    </div>

    {{-- Modal Daftar Anggota --}}
    <div id="modalAnggota" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.55);z-index:9999;align-items:center;justify-content:center;padding:16px;">
        <div class="card" style="max-width:520px;width:100%;padding:22px;max-height:90vh;overflow:auto;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                <h3 style="margin:0;font-size:1.1rem;font-weight:800;"><i class="fas fa-user-plus"></i> Daftarkan Anggota Baru</h3>
                <button type="button" class="btn btn-sm btn-outline" onclick="closeModalAnggota()" style="padding:4px 10px;"><i class="fas fa-xmark"></i></button>
            </div>
            <form method="POST" action="{{ route('dashboard.perpustakaan.anggota.store') }}">
                @csrf
                <div style="display:grid;gap:12px;">
                    <div>
                        <label style="font-size:0.8rem;font-weight:700;">Tipe Anggota</label>
                        <select name="tipe" class="form-control" required>
                            <option value="siswa">Peserta Didik</option>
                            <option value="gtk">GTK</option>
                            <option value="umum">Umum</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:0.8rem;font-weight:700;">NISN / NIP <small style="color:var(--text-muted);">(opsional; otomatis tarik data sekolah)</small></label>
                        <input type="text" name="nisn_nip" class="form-control" placeholder="Contoh: 0071234567">
                    </div>
                    <div>
                        <label style="font-size:0.8rem;font-weight:700;">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                    <div>
                        <label style="font-size:0.8rem;font-weight:700;">Rombel / Unit <small style="color:var(--text-muted);">(opsional)</small></label>
                        <input type="text" name="rombel_atau_unit" class="form-control">
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div>
                            <label style="font-size:0.8rem;font-weight:700;">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-control">
                                <option value="">-</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:0.8rem;font-weight:700;">Telepon</label>
                            <input type="text" name="telepon" class="form-control">
                        </div>
                    </div>
                    <div>
                        <label style="font-size:0.8rem;font-weight:700;">Email <small style="color:var(--text-muted);">(opsional)</small></label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div>
                        <label style="font-size:0.8rem;font-weight:700;">Password <small style="color:var(--text-muted);">(kosong = default NISN/NIP)</small></label>
                        <input type="text" name="password" class="form-control" placeholder="Biarkan kosong untuk default">
                    </div>
                </div>
                <div style="margin-top:16px;display:flex;gap:10px;justify-content:flex-end;">
                    <button type="button" class="btn btn-outline" onclick="closeModalAnggota()">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Daftarkan</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            function openModalAnggota() { document.getElementById('modalAnggota').style.display = 'flex'; }
            function closeModalAnggota() { document.getElementById('modalAnggota').style.display = 'none'; }
            @if ($errors->any()) document.addEventListener('DOMContentLoaded', openModalAnggota); @endif
        </script>
    @endpush
@endsection
