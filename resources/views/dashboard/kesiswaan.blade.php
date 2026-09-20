@extends('layouts.dashboard')

@section('title', 'Administrasi Kesiswaan (Buku Induk & Klaper) — SAE')
@section('dash_title', 'Administrasi Kesiswaan')

@section('content')
    <!-- 1. Header Banner & Actions -->
    <div class="dash-banner" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(59,130,246,0.12); color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                <i class="fas fa-address-card"></i>
            </div>
            <div>
                <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Administrasi Kesiswaan
                </h2>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            @if ($canCreate)
                <button type="button" class="btn btn-outline" id="btnSyncKlaper" title="Sinkronkan Buku Klaper" style="padding: 8px 14px; font-size: 0.9rem; border-radius: 8px;">
                    <i class="fas fa-rotate text-primary"></i>
                </button>
                <button type="button" class="btn btn-primary" id="btnOpenMutasiModal" title="Catat Mutasi Siswa" style="padding: 8px 14px; font-size: 0.9rem; border-radius: 8px;">
                    <i class="fas fa-plus"></i>
                </button>
            @endif
        </div>
    </div>

    <!-- 2. Flash Messages -->
    @if (session('success'))
        <div style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: #10b981; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 0.86rem; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-check-circle"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <!-- 3. Stat Grid Baku SAE -->
    <div class="dash-stat-grid" style="margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: #2563eb;">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_aktif'] ?? 0) }}</div>
                <div class="dash-stat-label">Siswa Aktif</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                <i class="fas fa-book-bookmark"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_klaper'] ?? 0) }}</div>
                <div class="dash-stat-label">Buku Klaper</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239,68,68,0.12); color: #ef4444;">
                <i class="fas fa-person-walking-dashed-line-arrow-right"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_mutasi'] ?? 0) }}</div>
                <div class="dash-stat-label">Mutasi</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                <i class="fas fa-file-circle-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['berkas_lengkap'] ?? 0) }}</div>
                <div class="dash-stat-label">Berkas Lengkap</div>
            </div>
        </div>
    </div>

    <!-- 4. Navigation Tabs Wrapper -->
    <div class="periode-nav-wrapper" style="margin-bottom: 20px;">
        <div class="periode-nav-desktop">
            <a href="{{ route('dashboard.kesiswaan.index', ['tab' => 'klaper']) }}"
                class="periode-nav-tab {{ $activeTab === 'klaper' ? 'active' : '' }}">
                <i class="fas fa-address-book"></i> Klaper &amp; Induk
            </a>
            <a href="{{ route('dashboard.kesiswaan.index', ['tab' => 'mutasi']) }}"
                class="periode-nav-tab {{ $activeTab === 'mutasi' ? 'active' : '' }}">
                <i class="fas fa-right-left"></i> Mutasi
            </a>
            <a href="{{ route('dashboard.kesiswaan.index', ['tab' => 'berkas']) }}"
                class="periode-nav-tab {{ $activeTab === 'berkas' ? 'active' : '' }}">
                <i class="fas fa-folder-open"></i> Berkas Fisik
            </a>
        </div>
    </div>

    <!-- 5. Content Sesuai Tab Aktif -->
    @if ($activeTab === 'klaper')
        <!-- TAB 1: BUKU KLAPER & BUKU INDUK SISWA -->
        <div class="card" style="padding: 20px; border-radius: 14px; border: 1px solid var(--border-color); margin-bottom: 20px;">
            <!-- Filter & Search Toolbar -->
            <form method="GET" action="{{ route('dashboard.kesiswaan.index') }}" class="dash-toolbar" style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 16px; flex-wrap: wrap;">
                <input type="hidden" name="tab" value="klaper">
                <div class="live-search-wrap" style="flex: 1; min-width: 260px;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Cari nama siswa, NISN, nomor klaper..." autocomplete="off">
                </div>

                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <select name="abjad" onchange="this.form.submit()" style="height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem;">
                        <option value="">Semua Abjad (A-Z)</option>
                        @foreach (range('A', 'Z') as $char)
                            <option value="{{ $char }}" {{ ($abjad ?? '') === $char ? 'selected' : '' }}>Huruf {{ $char }}</option>
                        @endforeach
                    </select>

                    <select name="tahun_masuk" onchange="this.form.submit()" style="height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem;">
                        <option value="">Semua Tahun Masuk</option>
                        @for ($y = date('Y'); $y >= date('Y') - 5; $y--)
                            <option value="{{ $y }}" {{ ($tahunMasuk ?? '') == $y ? 'selected' : '' }}>Tahun {{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </form>

            <!-- Tabel Buku Klaper -->
            <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 0; border: none; box-shadow: none;">
                <table class="table table-pd dash-table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border-color); text-align: left; background: var(--bg-hover);">
                            <th style="padding: 12px 14px; width: 45px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">No</th>
                            <th style="padding: 12px 14px; width: 110px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">No. Klaper</th>
                            <th style="padding: 12px 14px; width: 110px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">No. Induk</th>
                            <th style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Lengkap Siswa</th>
                            <th style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">NISN / NIPD</th>
                            <th style="padding: 12px 14px; width: 120px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Rombel / Kelas</th>
                            <th style="padding: 12px 14px; width: 100px; text-align: center; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Status</th>
                            <th style="padding: 12px 14px; width: 80px; text-align: center; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($klaperList as $index => $item)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td data-label="No" style="padding: 12px 14px; font-size: 0.84rem; color: var(--text-muted);">
                                    {{ $klaperList->firstItem() + $index }}
                                </td>
                                <td data-label="No Klaper" style="padding: 12px 14px; font-family: monospace; font-weight: 700; color: var(--primary);">
                                    {{ $item->nomor_klaper ?: '—' }}
                                </td>
                                <td data-label="Buku Induk" style="padding: 12px 14px; font-family: monospace; font-weight: 600; color: var(--text-color);">
                                    {{ $item->nomor_induk ?: ($item->nipd ?: '—') }}
                                </td>
                                <td data-label="Nama Siswa" style="padding: 12px 14px;">
                                    <div style="font-weight: 700; color: var(--text-color); font-size: 0.88rem;">{{ $item->nama }}</div>
                                    <div style="font-size: 0.78rem; color: var(--text-muted);">
                                        {{ $item->tempat_lahir }}, {{ $item->tanggal_lahir ? \Carbon\Carbon::parse($item->tanggal_lahir)->translatedFormat('d M Y') : '-' }} &bull; {{ $item->jenis_kelamin === 'L' ? 'L' : 'P' }}
                                    </div>
                                </td>
                                <td data-label="NISN / NIPD" style="padding: 12px 14px; font-size: 0.82rem; color: var(--text-muted);">
                                    NISN: <strong>{{ $item->nisn ?: '-' }}</strong><br>
                                    NIPD: {{ $item->nipd ?: '-' }}
                                </td>
                                <td data-label="Rombel" style="padding: 12px 14px; font-weight: 600; color: var(--text-color); font-size: 0.84rem;">
                                    {{ $item->rombel_nama ?: '—' }}
                                </td>
                                <td data-label="Status" style="padding: 12px 14px; text-align: center;">
                                    @if ($item->status_klaper === 'lulus')
                                        <span class="badge" style="background: rgba(16,185,129,0.12); color: #10b981; padding: 4px 8px; border-radius: 6px; font-weight: 700; font-size: 0.72rem;">Lulus</span>
                                    @elseif ($item->status_klaper === 'mutasi_keluar' || $item->status_klaper === 'do')
                                        <span class="badge" style="background: rgba(239,68,68,0.12); color: #ef4444; padding: 4px 8px; border-radius: 6px; font-weight: 700; font-size: 0.72rem;">Keluar/Mutasi</span>
                                    @else
                                        <span class="badge" style="background: rgba(59,130,246,0.12); color: #2563eb; padding: 4px 8px; border-radius: 6px; font-weight: 700; font-size: 0.72rem;">Aktif</span>
                                    @endif
                                </td>
                                <td data-label="Aksi" style="padding: 12px 14px; text-align: center;">
                                    @if ($item->klaper_id && $canUpdate)
                                        <button type="button" class="btn btn-outline btn-sm btn-edit-klaper" data-id="{{ $item->klaper_id }}"
                                            data-nama="{{ $item->nama }}" data-klaper="{{ $item->nomor_klaper }}" data-induk="{{ $item->nomor_induk }}"
                                            data-status="{{ $item->status_klaper }}" data-tahun="{{ $item->tahun_masuk }}"
                                            style="padding: 4px 8px; font-size: 0.76rem;" title="Edit Nomor Klaper">
                                            <i class="fas fa-pen-to-square"></i>
                                        </button>
                                    @else
                                        <span style="color: var(--text-muted); font-size: 0.76rem;">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px 14px; color: var(--text-muted);">
                                    <i class="fas fa-address-book" style="font-size: 2.4rem; margin-bottom: 12px; display: block; opacity: 0.35;"></i>
                                    <span style="font-weight: 600; font-size: 0.92rem;">Belum ada rekaman data Buku Klaper.</span>
                                    <p style="font-size: 0.8rem; margin: 4px 0 0 0; color: var(--text-muted);">Gunakan tombol "Sinkronkan Buku Klaper" di atas untuk men-generate nomor klaper otomatis dari Dapodik.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Custom Pagination -->
            @if ($klaperList->hasPages())
                <div class="custom-pagination" style="margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    <div class="page-info" style="font-size: 0.82rem; color: var(--text-muted);">
                        Menampilkan <strong>{{ $klaperList->firstItem() ?? 0 }}</strong> - <strong>{{ $klaperList->lastItem() ?? 0 }}</strong> dari <strong>{{ $klaperList->total() }}</strong> siswa
                    </div>
                    <div style="display: flex; gap: 4px; align-items: center;">
                        @if ($klaperList->onFirstPage())
                            <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                        @else
                            <a href="{{ $klaperList->previousPageUrl() }}" class="page-btn"><i class="fas fa-chevron-left"></i></a>
                        @endif
                        <span class="page-btn current">{{ $klaperList->currentPage() }}</span>
                        @if ($klaperList->hasMorePages())
                            <a href="{{ $klaperList->nextPageUrl() }}" class="page-btn"><i class="fas fa-chevron-right"></i></a>
                        @else
                            <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                        @endif
                    </div>
                </div>
            @endif
        </div>

    @elseif ($activeTab === 'mutasi')
        <!-- TAB 2: MANAJEMEN MUTASI SISWA -->
        <div class="card" style="padding: 20px; border-radius: 14px; border: 1px solid var(--border-color); margin-bottom: 20px;">
            <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 0; border: none; box-shadow: none;">
                <table class="table table-pd dash-table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border-color); text-align: left; background: var(--bg-hover);">
                            <th style="padding: 12px 14px; width: 45px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">No</th>
                            <th style="padding: 12px 14px; width: 130px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Jenis &amp; Tanggal</th>
                            <th style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Siswa</th>
                            <th style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">No. Surat &amp; Asal/Tujuan</th>
                            <th style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Alasan Kepindahan</th>
                            <th style="padding: 12px 14px; width: 100px; text-align: center; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($mutasiList as $index => $mt)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td data-label="No" style="padding: 12px 14px; font-size: 0.84rem; color: var(--text-muted);">
                                    {{ $mutasiList->firstItem() + $index }}
                                </td>
                                <td data-label="Jenis & Tanggal" style="padding: 12px 14px;">
                                    @if ($mt->jenis_mutasi === 'masuk')
                                        <span class="badge" style="background: rgba(16,185,129,0.12); color: #10b981; padding: 3px 8px; border-radius: 6px; font-weight: 700; font-size: 0.72rem;">Mutasi Masuk</span>
                                    @elseif ($mt->jenis_mutasi === 'keluar')
                                        <span class="badge" style="background: rgba(239,68,68,0.12); color: #ef4444; padding: 3px 8px; border-radius: 6px; font-weight: 700; font-size: 0.72rem;">Mutasi Keluar</span>
                                    @else
                                        <span class="badge" style="background: rgba(245,158,11,0.12); color: #f59e0b; padding: 3px 8px; border-radius: 6px; font-weight: 700; font-size: 0.72rem;">Drop Out / Berhenti</span>
                                    @endif
                                    <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
                                        {{ \Carbon\Carbon::parse($mt->tanggal_mutasi)->translatedFormat('d M Y') }}
                                    </div>
                                </td>
                                <td data-label="Nama Siswa" style="padding: 12px 14px;">
                                    <div style="font-weight: 700; color: var(--text-color); font-size: 0.88rem;">{{ $mt->siswa->nama ?? 'Siswa' }}</div>
                                    <div style="font-size: 0.78rem; color: var(--text-muted);">NISN: {{ $mt->siswa->nisn ?? '-' }}</div>
                                </td>
                                <td data-label="No Surat & Tujuan" style="padding: 12px 14px;">
                                    <div style="font-weight: 600; font-size: 0.84rem; color: var(--primary);">{{ $mt->nomor_surat_mutasi ?: '—' }}</div>
                                    <div style="font-size: 0.78rem; color: var(--text-muted);">Sekolah: {{ $mt->sekolah_tujuan_asal ?: '—' }}</div>
                                </td>
                                <td data-label="Alasan" style="padding: 12px 14px; font-size: 0.84rem; color: var(--text-color);">
                                    {{ $mt->alasan }}
                                </td>
                                <td data-label="Aksi" style="padding: 12px 14px; text-align: center;">
                                    <a href="{{ route('dashboard.kesiswaan.mutasi.cetak', $mt->id) }}" target="_blank" class="btn btn-outline btn-sm" style="padding: 5px 10px; font-size: 0.8rem;" title="Cetak Surat Mutasi">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px 14px; color: var(--text-muted);">
                                    <i class="fas fa-person-walking-arrow-right" style="font-size: 2.4rem; margin-bottom: 12px; display: block; opacity: 0.35;"></i>
                                    <span style="font-weight: 600; font-size: 0.92rem;">Belum ada riwayat mutasi siswa yang dicatat.</span>
                                    <p style="font-size: 0.8rem; margin: 4px 0 0 0; color: var(--text-muted);">Gunakan tombol "Catat Mutasi Siswa" di atas untuk meregistrasi mutasi.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    @else
        <!-- TAB 3: VERIFIKASI BERKAS FISIK SISWA BARU -->
        <div class="card" style="padding: 20px; border-radius: 14px; border: 1px solid var(--border-color); margin-bottom: 20px;">
            <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 0; border: none; box-shadow: none;">
                <table class="table table-pd dash-table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border-color); text-align: left; background: var(--bg-hover);">
                            <th style="padding: 12px 14px; width: 45px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">No</th>
                            <th style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Lengkap Siswa</th>
                            <th style="padding: 12px 14px; width: 110px; text-align: center; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Akta Lahir</th>
                            <th style="padding: 12px 14px; width: 110px; text-align: center; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Kartu Keluarga</th>
                            <th style="padding: 12px 14px; width: 110px; text-align: center; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Ijazah SMP</th>
                            <th style="padding: 12px 14px; width: 110px; text-align: center; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">KTP Ortu</th>
                            <th style="padding: 12px 14px; width: 110px; text-align: center; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">KIP / PIP</th>
                            <th style="padding: 12px 14px; width: 90px; text-align: center; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($berkasList as $index => $bk)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 14px; font-size: 0.84rem; color: var(--text-muted);">
                                    {{ $berkasList->firstItem() + $index }}
                                </td>
                                <td style="padding: 12px 14px;">
                                    <div style="font-weight: 700; color: var(--text-color); font-size: 0.88rem;">{{ $bk->nama }}</div>
                                    <div style="font-size: 0.78rem; color: var(--text-muted);">NISN: {{ $bk->nisn ?: '-' }} &bull; Rombel: {{ $bk->rombel_nama ?: '-' }}</div>
                                </td>
                                <td style="padding: 12px 14px; text-align: center;">
                                    {!! $bk->akta_kelahiran ? '<i class="fas fa-circle-check text-success" style="font-size: 1.1rem;"></i>' : '<i class="fas fa-circle-xmark text-danger" style="font-size: 1.1rem; opacity: 0.5;"></i>' !!}
                                </td>
                                <td style="padding: 12px 14px; text-align: center;">
                                    {!! $bk->kartu_keluarga ? '<i class="fas fa-circle-check text-success" style="font-size: 1.1rem;"></i>' : '<i class="fas fa-circle-xmark text-danger" style="font-size: 1.1rem; opacity: 0.5;"></i>' !!}
                                </td>
                                <td style="padding: 12px 14px; text-align: center;">
                                    {!! $bk->ijazah_smp ? '<i class="fas fa-circle-check text-success" style="font-size: 1.1rem;"></i>' : '<i class="fas fa-circle-xmark text-danger" style="font-size: 1.1rem; opacity: 0.5;"></i>' !!}
                                </td>
                                <td style="padding: 12px 14px; text-align: center;">
                                    {!! $bk->ktp_orang_tua ? '<i class="fas fa-circle-check text-success" style="font-size: 1.1rem;"></i>' : '<i class="fas fa-circle-xmark text-danger" style="font-size: 1.1rem; opacity: 0.5;"></i>' !!}
                                </td>
                                <td style="padding: 12px 14px; text-align: center;">
                                    {!! $bk->kip_pip ? '<i class="fas fa-circle-check text-success" style="font-size: 1.1rem;"></i>' : '<i class="fas fa-circle-xmark text-danger" style="font-size: 1.1rem; opacity: 0.5;"></i>' !!}
                                </td>
                                <td style="padding: 12px 14px; text-align: center;">
                                    @if ($canUpdate)
                                        <button type="button" class="btn btn-outline btn-sm btn-edit-berkas"
                                            data-id="{{ $bk->peserta_didik_id }}"
                                            data-nama="{{ $bk->nama }}"
                                            data-akta="{{ $bk->akta_kelahiran ? 1 : 0 }}"
                                            data-kk="{{ $bk->kartu_keluarga ? 1 : 0 }}"
                                            data-ijazah="{{ $bk->ijazah_smp ? 1 : 0 }}"
                                            data-ktp="{{ $bk->ktp_orang_tua ? 1 : 0 }}"
                                            data-kip="{{ $bk->kip_pip ? 1 : 0 }}"
                                            style="padding: 4px 8px; font-size: 0.76rem;" title="Verifikasi Berkas">
                                            <i class="fas fa-pen-to-square"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px 14px; color: var(--text-muted);">
                                    <i class="fas fa-folder-open" style="font-size: 2.4rem; margin-bottom: 12px; display: block; opacity: 0.35;"></i>
                                    <span style="font-weight: 600; font-size: 0.92rem;">Belum ada rekaman verifikasi berkas siswa.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- 6. Modal Form Catat Mutasi Siswa (z-index: 99999 !important) -->
    <div id="modalMutasiItem" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="max-width: 580px; width: 92%; margin: 0; border-radius: 14px; padding: 24px; box-shadow: 0 16px 40px rgba(0,0,0,0.45); border: 1px solid var(--border-color); background: var(--bg-card, #1e293b);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-right-left text-primary"></i> Catat Mutasi Siswa
                </h3>
                <button type="button" id="btnCloseMutasiModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.15rem; padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formMutasiItem">
                @csrf
                <div style="margin-bottom: 14px;">
                    <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                        Pilih Peserta Didik <span style="color: #ef4444;">*</span>
                    </label>
                    <select id="mutasiPesertaDidikId" required
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.85rem; box-sizing: border-box;">
                        <option value="">-- Cari / Pilih Peserta Didik --</option>
                        @foreach ($siswaList as $sw)
                            <option value="{{ $sw->peserta_didik_id }}">
                                {{ $sw->nama }} (NISN: {{ $sw->nisn ?: '-' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                            Jenis Mutasi <span style="color: #ef4444;">*</span>
                        </label>
                        <select id="mutasiJenis" required
                            style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.85rem; box-sizing: border-box;">
                            <option value="keluar">Mutasi Keluar (Pindah Sekolah)</option>
                            <option value="masuk">Mutasi Masuk</option>
                            <option value="do">Drop Out / Putus Sekolah</option>
                            <option value="meninggal">Meninggal Dunia</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                            Tanggal Mutasi <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="date" id="mutasiTanggal" required value="{{ date('Y-m-d') }}"
                            style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.85rem; box-sizing: border-box;">
                    </div>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                        Sekolah Tujuan / Sekolah Asal
                    </label>
                    <input type="text" id="mutasiSekolah" placeholder="Contoh: SMKN 1 Serang..."
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.85rem; box-sizing: border-box;">
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); display: block; margin-bottom: 4px;">
                        Alasan Kepindahan / Mutasi <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" id="mutasiAlasan" required placeholder="Contoh: Mengikuti domisili orang tua..."
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.85rem; box-sizing: border-box;">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" id="btnCancelMutasiModal" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.84rem; border-radius: 8px;">
                        Batal
                    </button>
                    <button type="submit" id="btnSaveMutasiModal" class="btn btn-primary" style="padding: 8px 18px; font-size: 0.84rem; border-radius: 8px; font-weight: 600;">
                        <i class="fas fa-check me-1"></i> Simpan &amp; Cetak Surat
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 7. Modal Edit Verifikasi Berkas (z-index: 99999 !important) -->
    <div id="modalBerkasItem" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="max-width: 500px; width: 92%; margin: 0; border-radius: 14px; padding: 24px; box-shadow: 0 16px 40px rgba(0,0,0,0.45); border: 1px solid var(--border-color); background: var(--bg-card, #1e293b);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-folder-check text-primary"></i> Checklist Berkas Siswa
                </h3>
                <button type="button" id="btnCloseBerkasModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.15rem; padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formBerkasItem">
                @csrf
                <input type="hidden" id="berkasPdId">

                <div style="background: var(--bg-hover); padding: 10px 14px; border-radius: 8px; margin-bottom: 16px;">
                    <div id="berkasNamaSiswa" style="font-weight: 700; color: var(--text-color);"></div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px;">
                    <label style="display: flex; align-items: center; gap: 10px; font-size: 0.88rem; cursor: pointer;">
                        <input type="checkbox" id="chkAkta" style="width: 18px; height: 18px;">
                        <span>Akta Kelahiran (Asli / Fotokopi Terlegalisir)</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; font-size: 0.88rem; cursor: pointer;">
                        <input type="checkbox" id="chkKk" style="width: 18px; height: 18px;">
                        <span>Kartu Keluarga (KK)</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; font-size: 0.88rem; cursor: pointer;">
                        <input type="checkbox" id="chkIjazah" style="width: 18px; height: 18px;">
                        <span>Ijazah / SKL SMP / MTs</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; font-size: 0.88rem; cursor: pointer;">
                        <input type="checkbox" id="chkKtp" style="width: 18px; height: 18px;">
                        <span>KTP Kedua Orang Tua / Wali</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; font-size: 0.88rem; cursor: pointer;">
                        <input type="checkbox" id="chkKip" style="width: 18px; height: 18px;">
                        <span>Kartu Indonesia Pintar (KIP / PIP / PKH)</span>
                    </label>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" id="btnCancelBerkasModal" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.84rem; border-radius: 8px;">
                        Batal
                    </button>
                    <button type="submit" id="btnSaveBerkasModal" class="btn btn-primary" style="padding: 8px 18px; font-size: 0.84rem; border-radius: 8px; font-weight: 600;">
                        <i class="fas fa-check me-1"></i> Simpan Status Berkas
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/kesiswaan.js') }}"></script>
@endpush
