@extends('layouts.dashboard')

@section('title', 'Peminjaman Alat — Laboratorium SAE')
@section('dash_title', 'Peminjaman Alat')

@section('content')
    <div class="dash-content-inner">
        {{-- 1. Banner Header --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap; margin-bottom: var(--space-9);">
            <div style="display: flex; align-items: center; gap: var(--space-7);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(14,165,233,0.12); color: var(--info, #0ea5e9); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl); flex-shrink: 0;">
                    <i class="fas fa-hand-holding"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--text-color); margin: 0;">
                        Peminjaman Alat Laboratorium
                    </h2>
                    <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-1);">
                        Sirkulasi peminjaman alat praktik khusus jurusan beserta pengembalian &amp; cek kondisi.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions" style="display: flex; gap: var(--space-4); align-items: center; flex-wrap: wrap;">
                <span class="badge" style="background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); border: 1px solid rgba(59,130,246,0.25); padding: var(--space-3) var(--space-6); border-radius: var(--radius-md); font-size: var(--text-xs); font-weight: var(--font-bold); display: inline-flex; align-items: center; gap: var(--space-2);">
                    <i class="fas {{ $scopeBadge['icon'] }}"></i> {{ $scopeBadge['label'] }}
                </span>
                @if ($canUpdate && $statTerlambat > 0)
                    <button type="button" class="btn btn-outline" id="btnTandaiTerlambat"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md); font-size: var(--text-md);"
                        title="Tandai Semua Peminjaman Terlambat ({{ $statTerlambat }})">
                        <i class="fas fa-clock-rotate-left" style="color: var(--danger, #ef4444);"></i>
                    </button>
                @endif
                @if ($canCreate)
                    <button type="button" class="btn btn-primary" id="btnOpenPeminjaman"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md); font-size: var(--text-md);"
                        title="Ajukan Peminjaman Baru">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>

        {{-- 2. Statistik --}}
        <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: var(--space-9); gap: var(--space-7);">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);"><i class="fas fa-clipboard-list"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statTotal, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Total Peminjaman</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);"><i class="fas fa-hourglass-half"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statDiajukan, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Menunggu Persetujuan</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(14, 165, 233, 0.15); color: var(--info, #0ea5e9);"><i class="fas fa-hand-holding"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statDipinjam, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Sedang Dipinjam</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239, 68, 68, 0.15); color: var(--danger, #ef4444);"><i class="fas fa-clock"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statTerlambat, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Terlambat</div>
                </div>
            </div>
        </div>

        {{-- 3. Datatable --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.laboran.peminjaman-alat.index') }}" style="margin: 0;">
                    <div style="display: flex; flex-wrap: wrap; gap: var(--space-6); justify-content: space-between; align-items: center;">
                        <div style="display: flex; flex-wrap: wrap; gap: var(--space-5); align-items: center;">
                            <div class="toolbar-entries">
                                <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                                <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                                    @foreach ([10, 15, 25, 50, 100] as $n)
                                        <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                                    @endforeach
                                </select>
                                <span>entri</span>
                            </div>

                            <select name="status" id="filterStatus" class="toolbar-filter-select" style="height: 38px;" title="Filter Status">
                                <option value="">Semua Status</option>
                                @foreach ($statusList as $k => $v)
                                    <option value="{{ $k }}" {{ $statusFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>

                            <select name="tipe" id="filterTipe" class="toolbar-filter-select" style="height: 38px;" title="Filter Tipe Peminjam">
                                <option value="">Semua Peminjam</option>
                                @foreach ($tipeList as $k => $v)
                                    <option value="{{ $k }}" {{ $tipeFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>

                            @if ($search || $statusFilter || $tipeFilter || $overdueOnly)
                                <a href="{{ route('dashboard.laboran.peminjaman-alat.index') }}" class="btn btn-outline"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                                    title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="q" id="liveSearch" value="{{ $search }}" placeholder="Cari nomor pinjam, peminjam, atau keperluan..." autocomplete="off">
                            <button type="button" id="clearSearch" class="clear-search {{ $search ? 'visible' : '' }}" title="Hapus pencarian"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255, 255, 255, 0.02);">
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Nomor / Tanggal</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Peminjam</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Keperluan</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Tenggat</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Status</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $p)
                            @php
                                $terlambat = $p->tanggal_kembali_rencana
                                    && in_array($p->status, ['dipinjam', 'terlambat'], true)
                                    && \Carbon\Carbon::parse($p->tanggal_kembali_rencana)->isPast();
                            @endphp
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td data-label="Nomor" style="padding: var(--space-6) var(--space-7);">
                                    <div style="font-family: var(--font-family-mono); font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--primary);">{{ $p->nomor_pinjam }}</div>
                                    <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ \Carbon\Carbon::parse($p->tanggal_pinjam)->translatedFormat('d M Y') }}</div>
                                    @if ($p->singkatan || $p->nama_jurusan)
                                        <span class="badge badge-outline" style="font-size: var(--text-2xs); margin-top: var(--space-2);">{{ $p->singkatan ?: $p->nama_jurusan }}</span>
                                    @endif
                                </td>
                                <td data-label="Peminjam" style="padding: var(--space-6) var(--space-7);">
                                    <div style="font-weight: var(--font-bold); color: var(--text-color); font-size: var(--text-base);">{{ $p->peminjam_nama }}</div>
                                    <div style="font-size: var(--text-xs); color: var(--text-muted);">
                                        {{ $tipeList[$p->peminjam_tipe] ?? $p->peminjam_tipe }}
                                        @if ($p->peminjam_kelas) &bull; {{ $p->peminjam_kelas }} @endif
                                    </div>
                                    <div style="font-size: var(--text-2xs); color: var(--text-muted); margin-top: var(--space-1);">
                                        <i class="fas fa-boxes-stacked me-1"></i>{{ $p->jumlah_item }} item
                                    </div>
                                </td>
                                <td data-label="Keperluan" style="padding: var(--space-6) var(--space-7);">
                                    <div style="font-size: var(--text-sm); color: var(--text-color);">{{ \Illuminate\Support\Str::limit($p->keperluan, 80) }}</div>
                                    @if ($p->ruang_lab_nama)
                                        <div style="font-size: var(--text-xs); color: var(--text-muted);"><i class="fas fa-location-dot me-1"></i>{{ $p->ruang_lab_nama }}</div>
                                    @endif
                                </td>
                                <td data-label="Tenggat" style="padding: var(--space-6) var(--space-7); text-align: center; font-size: var(--text-sm);">
                                    @if ($p->tanggal_kembali_rencana)
                                        <span style="font-weight: var(--font-bold); color: {{ $terlambat ? 'var(--danger, #ef4444)' : 'var(--text-color)' }};">
                                            {{ \Carbon\Carbon::parse($p->tanggal_kembali_rencana)->translatedFormat('d M Y') }}
                                        </span>
                                        @if ($p->tanggal_kembali_aktual)
                                            <div style="font-size: var(--text-2xs); color: var(--success, #10b981);">
                                                Kembali: {{ \Carbon\Carbon::parse($p->tanggal_kembali_aktual)->format('d/m/Y') }}
                                            </div>
                                        @elseif ($terlambat)
                                            <div style="font-size: var(--text-2xs); color: var(--danger, #ef4444);">
                                                Lewat {{ \Carbon\Carbon::parse($p->tanggal_kembali_rencana)->diffInDays(now()) }} hari
                                            </div>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td data-label="Status" style="padding: var(--space-6) var(--space-7); text-align: center;">
                                    @php
                                        $sc = match ($p->status) {
                                            'dikembalikan' => 'badge-success',
                                            'dipinjam' => 'badge-info',
                                            'disetujui' => 'badge-primary',
                                            'terlambat' => 'badge-danger',
                                            'ditolak' => 'badge-danger',
                                            default => 'badge-warning',
                                        };
                                    @endphp
                                    <span class="badge {{ $sc }}" style="font-size: var(--text-2xs);">{{ $statusList[$p->status] ?? $p->status }}</span>
                                    @if ($p->kondisi_kembali)
                                        <div style="font-size: var(--text-2xs); color: var(--text-muted); margin-top: var(--space-2);">{{ $kondisiList[$p->kondisi_kembali] ?? $p->kondisi_kembali }}</div>
                                    @endif
                                </td>
                                <td data-label="Aksi" style="padding: var(--space-6) var(--space-7); text-align: right;">
                                    <div class="table-actions" style="justify-content: flex-end; gap: var(--space-3);">
                                        <a href="{{ route('dashboard.laboran.peminjaman-alat.show', $p->id) }}" class="btn-icon"
                                            title="Buka Detail" style="text-decoration: none;">
                                            <i class="fas fa-eye" style="color: var(--primary);"></i>
                                        </a>
                                        @if ($canUpdate && $p->status === 'diajukan')
                                            <button type="button" class="btn-icon btn-setujui-pinjam"
                                                data-id="{{ $p->id }}" data-nama="{{ $p->peminjam_nama }}"
                                                title="Setujui Peminjaman">
                                                <i class="fas fa-check" style="color: var(--success, #10b981);"></i>
                                            </button>
                                        @endif
                                        @if ($canUpdate && in_array($p->status, ['disetujui'], true))
                                            <button type="button" class="btn-icon btn-serah-terima"
                                                data-id="{{ $p->id }}" data-nama="{{ $p->peminjam_nama }}"
                                                title="Serah Terima (Alat Dipinjam)">
                                                <i class="fas fa-hand-holding" style="color: var(--info, #0ea5e9);"></i>
                                            </button>
                                        @endif
                                        @if ($canUpdate && in_array($p->status, ['dipinjam', 'terlambat'], true))
                                            <button type="button" class="btn-icon btn-kembalikan-pinjam"
                                                data-id="{{ $p->id }}" data-nama="{{ $p->peminjam_nama }}"
                                                title="Proses Pengembalian">
                                                <i class="fas fa-rotate-left" style="color: var(--success, #10b981);"></i>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <button type="button" class="btn-icon btn-delete-pinjam"
                                                data-id="{{ $p->id }}" data-nama="{{ $p->nomor_pinjam }}"
                                                title="Hapus Peminjaman" style="color: var(--danger, #ef4444);">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: var(--space-11); text-align: center; color: var(--text-muted);">
                                    <i class="fas fa-hand-holding mb-2" style="font-size: var(--text-3xl); opacity: 0.5;"></i>
                                    <div>Belum ada data peminjaman alat.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($list->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $list])
                </div>
            @endif
        </div>

        {{-- Modal Ajukan Peminjaman --}}
        <div id="modalPeminjaman" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 720px; max-height: 92vh; overflow-y: auto; padding: var(--space-10); border-radius: var(--radius-lg);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-9);">
                    <h3 style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-color);">Ajukan Peminjaman Alat</h3>
                    <button type="button" data-close="modalPeminjaman" style="background: none; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;" title="Tutup">&times;</button>
                </div>

                <form id="formPeminjaman" method="POST" action="{{ route('dashboard.laboran.peminjaman-alat.store') }}">
                    @csrf

                    <div style="display: grid; grid-template-columns: 1fr 1.4fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Tipe Peminjam <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <select name="peminjam_tipe" id="pjTipe" class="form-control" required>
                                @foreach ($tipeList as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Nama Peminjam <span style="color: var(--danger, #ef4444);">*</span>
                            </label>

                            {{-- Pilihan Otomatis: Siswa --}}
                            <div id="wrapPeminjamSiswa">
                                <select id="selectPeminjamSiswa" class="form-control">
                                    <option value="">-- Pilih Peserta Didik (Otomatis) --</option>
                                    @foreach (($siswaList ?? []) as $s)
                                        <option value="{{ $s->peserta_didik_id }}" data-nama="{{ $s->nama }}" data-kelas="{{ $s->rombel_nama }}">
                                            {{ $s->nama }}{{ !empty($s->rombel_nama) ? ' • ' . $s->rombel_nama : '' }}{{ !empty($s->nisn) ? ' (' . $s->nisn . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Pilihan Otomatis: Guru / Tendik --}}
                            <div id="wrapPeminjamGtk" style="display: none;">
                                <select id="selectPeminjamGtk" class="form-control">
                                    <option value="">-- Pilih Guru / Tenaga Kependidikan --</option>
                                    @foreach (($guruList ?? []) as $g)
                                        <option value="{{ $g->ptk_id }}" data-nama="{{ $g->nama }}" data-kelas="{{ $g->nip ? 'NIP. ' . $g->nip : ($g->jenis_ptk_id_str ?: 'GTK') }}">
                                            {{ $g->nama }}{{ !empty($g->nip) ? ' — ' . $g->nip : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Input Manual untuk Umum --}}
                            <input type="text" name="peminjam_nama" id="pjNama" class="form-control" required placeholder="Nama lengkap peminjam" style="margin-top: 4px;">
                            <input type="hidden" name="peminjam_id" id="pjId">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Kelas / Unit Kerja</label>
                            <input type="text" name="peminjam_kelas" id="pjKelas" class="form-control" placeholder="Contoh: XI TKJ 1 / Guru Kejuruan">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Lokasi Pengambilan</label>
                            <select name="ruang_lab_nama" id="pjLokasi" class="form-control">
                                <option value="">-- Pilih --</option>
                                @foreach ($lokasiOptions as $lok)
                                    <option value="{{ $lok }}">{{ $lok }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            Keperluan <span style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <input type="text" name="keperluan" id="pjKeperluan" class="form-control" required placeholder="Contoh: Praktik konfigurasi jaringan">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Tanggal Pinjam <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="date" name="tanggal_pinjam" id="pjTanggal" class="form-control" required>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Rencana Kembali <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="date" name="tanggal_kembali_rencana" id="pjKembali" class="form-control" required>
                        </div>
                    </div>

                    {{-- Pilih Alat Terpadu (Laboratorium & Sarpras) --}}
                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            Pilih Alat yang Dipinjam <span style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <div style="max-height: 260px; overflow-y: auto; padding: var(--space-5); border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                            {{-- Seksi Alat Lab --}}
                            <div style="font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--primary); text-transform: uppercase; margin-bottom: var(--space-3);">
                                <i class="fas fa-flask me-1"></i> Alat Praktik Laboratorium / Bengkel
                            </div>
                            @forelse ($itemOptions as $io)
                                <div style="display: flex; align-items: center; gap: var(--space-4); padding: var(--space-3) 0; border-bottom: 1px dashed var(--border-color);">
                                    <input type="checkbox" name="item_ids[]" value="{{ $io->id }}" class="pj-item" style="cursor: pointer;">
                                    <div style="flex: 1; min-width: 0;">
                                        <div style="font-size: var(--text-sm); font-weight: var(--font-semibold); color: var(--text-color);">{{ $io->nama_item }}</div>
                                        <div style="font-size: var(--text-xs); color: var(--text-muted);">
                                            {{ $io->kode_item }} &bull; Stok: {{ (float) $io->stok_tersedia }} {{ $io->satuan }}
                                            @if ($io->ruang_lab_nama) &bull; {{ $io->ruang_lab_nama }} @endif
                                        </div>
                                    </div>
                                    <input type="number" step="0.01" min="0.01" max="{{ (float) $io->stok_tersedia }}"
                                        name="item_jumlah[{{ $io->id }}]" value="1" class="form-control"
                                        style="width: 90px; height: 34px; font-size: var(--text-sm);" title="Jumlah pinjam">
                                </div>
                            @empty
                                <p style="font-size: var(--text-xs); color: var(--text-muted); margin: 0 0 var(--space-4) 0;">
                                    Belum ada alat dalam inventaris laboratorium.
                                </p>
                            @endforelse

                            {{-- Seksi Aset Sarpras --}}
                            @if (!empty($sarprasAsetOptions) && $sarprasAsetOptions->isNotEmpty())
                                <div style="font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--purple, #8b5cf6); text-transform: uppercase; margin: var(--space-6) 0 var(--space-3) 0;">
                                    <i class="fas fa-boxes-stacked me-1"></i> Peralatan &amp; Aset Sarpras Sekolah
                                </div>
                                @foreach ($sarprasAsetOptions as $ao)
                                    <div style="display: flex; align-items: center; gap: var(--space-4); padding: var(--space-3) 0; border-bottom: 1px dashed var(--border-color);">
                                        <input type="checkbox" name="aset_ids[]" value="{{ $ao->id }}" class="pj-item" style="cursor: pointer;">
                                        <div style="flex: 1; min-width: 0;">
                                            <div style="font-size: var(--text-sm); font-weight: var(--font-semibold); color: var(--text-color);">{{ $ao->nama_item }}</div>
                                            <div style="font-size: var(--text-xs); color: var(--text-muted);">
                                                {{ $ao->kode_aset }} &bull; Tersedia: {{ (float) $ao->stok_tersedia }} {{ $ao->satuan }}
                                                @if ($ao->nama_ruang) &bull; {{ $ao->nama_ruang }} @endif
                                            </div>
                                        </div>
                                        <input type="number" step="0.01" min="0.01" max="{{ (float) $ao->stok_tersedia }}"
                                            name="aset_jumlah[{{ $ao->id }}]" value="1" class="form-control"
                                            style="width: 90px; height: 34px; font-size: var(--text-sm);" title="Jumlah pinjam">
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-9);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Catatan</label>
                        <textarea name="catatan" id="pjCatatan" class="form-control" rows="2" placeholder="Catatan tambahan..."></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: var(--space-4);">
                        <button type="button" class="btn btn-secondary" data-close="modalPeminjaman">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i> Ajukan Peminjaman</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Pengembalian --}}
        <div id="modalKembali" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 520px; padding: var(--space-10); border-radius: var(--radius-lg);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-9);">
                    <h3 style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-color);">Proses Pengembalian</h3>
                    <button type="button" data-close="modalKembali" style="background: none; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;" title="Tutup">&times;</button>
                </div>

                <form id="formKembali" method="POST" action="">
                    @csrf
                    <p id="kembaliDesc" style="font-size: var(--text-base); color: var(--text-color); margin-bottom: var(--space-7);"></p>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            Kondisi Alat Saat Kembali <span style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <select name="kondisi_kembali" id="kembaliKondisi" class="form-control" required>
                            @foreach ($kondisiList as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                        <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-2);">
                            <i class="fas fa-info-circle me-1"></i> Jika rusak, kondisi inventaris akan otomatis diperbarui.
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-9);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Catatan Pengembalian</label>
                        <textarea name="catatan_pengembalian" id="kembaliCatatan" class="form-control" rows="2" placeholder="Catatan kondisi atau keterlambatan..."></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: var(--space-4);">
                        <button type="button" class="btn btn-secondary" data-close="modalKembali">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i> Konfirmasi Pengembalian</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

        @push('scripts')
            <script src="{{ asset('js/laboran-common.js') }}?v={{ file_exists(public_path('js/laboran-common.js')) ? filemtime(public_path('js/laboran-common.js')) : time() }}"></script>
            <script src="{{ asset('js/laboran-peminjaman.js') }}?v={{ file_exists(public_path('js/laboran-peminjaman.js')) ? filemtime(public_path('js/laboran-peminjaman.js')) : time() }}"></script>
        @endpush
    @endsection
