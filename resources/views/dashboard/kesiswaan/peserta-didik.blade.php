@extends('layouts.dashboard')

@section('title', 'Peserta Didik (Aktif, Tidak Aktif, Alumni, Berkas & Usulan) — SAE')
@section('dash_title', 'Peserta Didik')

@section('content')
    <!-- 1. Header Banner & Actions -->
    <div class="dash-banner" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                <i class="fas fa-users-viewfinder"></i>
            </div>
            <div>
                <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Peserta Didik
                </h2>
                <p style="margin: 2px 0 0 0; font-size: 0.82rem; color: var(--text-muted);">
                    Direktori lengkap siswa aktif, riwayat nonaktif, data alumni, verifikasi berkas fisik, dan usulan revisi data.
                </p>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            @if ($canCreate)
                <button type="button" class="btn btn-primary" id="btnOpenUsulanModal" title="Ajukan Usulan Perubahan Data Siswa" style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;">
                    <i class="fas fa-file-pen"></i>
                </button>
            @endif
        </div>
    </div>

    <!-- 2. Stat Grid Baku SAE -->
    <div class="dash-stat-grid" style="margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_aktif'] ?? 0) }}</div>
                <div class="dash-stat-label">Siswa Aktif</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239,68,68,0.12); color: #ef4444;">
                <i class="fas fa-user-xmark"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_tidak_aktif'] ?? 0) }}</div>
                <div class="dash-stat-label">Tidak Aktif</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: #2563eb;">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_alumni'] ?? 0) }}</div>
                <div class="dash-stat-label">Total Alumni</div>
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
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(168,85,247,0.12); color: #a855f7;">
                <i class="fas fa-clock-rotate-left"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['usulan_menunggu'] ?? 0) }}</div>
                <div class="dash-stat-label">Usulan Menunggu</div>
            </div>
        </div>
    </div>

    <!-- 3. Navigation Tabs Wrapper -->
    <div class="periode-nav-wrapper" style="margin-bottom: 20px;">
        <div class="periode-nav-desktop">
            <a href="{{ route('dashboard.kesiswaan.peserta-didik.index', ['tab' => 'aktif']) }}"
                class="periode-nav-tab {{ $activeTab === 'aktif' ? 'active' : '' }}">
                <i class="fas fa-user-check"></i> Aktif
            </a>
            <a href="{{ route('dashboard.kesiswaan.peserta-didik.index', ['tab' => 'tidak_aktif']) }}"
                class="periode-nav-tab {{ $activeTab === 'tidak_aktif' ? 'active' : '' }}">
                <i class="fas fa-user-xmark"></i> Tidak Aktif
            </a>
            <a href="{{ route('dashboard.kesiswaan.peserta-didik.index', ['tab' => 'alumni']) }}"
                class="periode-nav-tab {{ $activeTab === 'alumni' ? 'active' : '' }}">
                <i class="fas fa-graduation-cap"></i> Alumni
            </a>
            <a href="{{ route('dashboard.kesiswaan.peserta-didik.index', ['tab' => 'berkas']) }}"
                class="periode-nav-tab {{ $activeTab === 'berkas' ? 'active' : '' }}">
                <i class="fas fa-folder-open"></i> Berkas Fisik
            </a>
            <a href="{{ route('dashboard.kesiswaan.peserta-didik.index', ['tab' => 'usulan']) }}"
                class="periode-nav-tab {{ $activeTab === 'usulan' ? 'active' : '' }}">
                <i class="fas fa-file-pen"></i> Usulan Perubahan
            </a>
        </div>
    </div>

    <!-- 4. Content Sesuai Tab -->
    @if ($activeTab === 'aktif')
        <!-- TAB 1: SISWA AKTIF -->
        <div class="card" style="padding: 16px 20px; margin-bottom: 18px;">
            <form method="GET" action="{{ route('dashboard.kesiswaan.peserta-didik.index') }}" class="table-toolbar" style="margin-bottom: 0;">
                <input type="hidden" name="tab" value="aktif">
                <div class="live-search-wrap" style="flex: 1; min-width: 240px; position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                    <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama, NISN, atau NIPD siswa..."
                        class="form-control" style="padding-left: 38px; width: 100%; border-radius: 8px;">
                </div>
                <select name="rombel" class="form-control" style="width: 200px; border-radius: 8px;">
                    <option value="">-- Semua Rombel --</option>
                    @foreach ($filterRombel as $r)
                        <option value="{{ $r }}" {{ $rombel === $r ? 'selected' : '' }}>{{ $r }}</option>
                    @endforeach
                </select>
                <select name="gender" class="form-control" style="width: 130px; border-radius: 8px;">
                    <option value="">-- L/P --</option>
                    <option value="L" {{ $gender === 'L' ? 'selected' : '' }}>Laki-laki</option>
                    <option value="P" {{ $gender === 'P' ? 'selected' : '' }}>Perempuan</option>
                </select>
                <button type="submit" class="btn btn-outline" style="border-radius: 8px;"><i class="fas fa-filter"></i> Filter</button>
            </form>
        </div>

        <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Lengkap</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">NISN / NIPD</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">L/P</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Rombel</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">TTL</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Orang Tua</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($aktifList as $item)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 18px; font-weight: 700; color: var(--text-color);">{{ $item->nama }}</td>
                            <td style="padding: 12px 18px; font-family: monospace; font-size: 0.82rem;">{{ $item->nisn ?: '-' }} / {{ $item->nipd ?: '-' }}</td>
                            <td style="padding: 12px 18px; text-align: center;"><span class="badge {{ $item->jenis_kelamin === 'L' ? 'badge-primary' : 'badge-accent' }}">{{ $item->jenis_kelamin }}</span></td>
                            <td style="padding: 12px 18px; font-size: 0.85rem;">{{ $item->nama_rombel ?: '-' }}</td>
                            <td style="padding: 12px 18px; font-size: 0.82rem;">{{ $item->tempat_lahir ?: '-' }}, {{ $item->tanggal_lahir ? date('d/m/Y', strtotime($item->tanggal_lahir)) : '-' }}</td>
                            <td style="padding: 12px 18px; font-size: 0.82rem; color: var(--text-muted);">{{ $item->nama_ayah ?: ($item->nama_ibu ?: '-') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 30px; text-align: center; color: var(--text-muted);">Belum ada data siswa aktif yang cocok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    @elseif ($activeTab === 'tidak_aktif')
        <!-- TAB 2: SISWA TIDAK AKTIF -->
        <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Lengkap</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">NISN</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Rombel Terakhir</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Alasan Keluar</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Tanggal Keluar</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Sekolah Tujuan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tidakAktifList as $item)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 18px; font-weight: 700; color: var(--text-color);">{{ $item->nama }}</td>
                            <td style="padding: 12px 18px; font-family: monospace; font-size: 0.82rem;">{{ $item->nisn ?: '-' }}</td>
                            <td style="padding: 12px 18px; font-size: 0.85rem;">{{ $item->rombel_terakhir ?: '-' }}</td>
                            <td style="padding: 12px 18px;"><span class="badge badge-danger">{{ $item->alasan_keluar ?: 'Keluar' }}</span></td>
                            <td style="padding: 12px 18px; font-size: 0.85rem;">{{ $item->tanggal_keluar ? date('d/m/Y', strtotime($item->tanggal_keluar)) : '-' }}</td>
                            <td style="padding: 12px 18px; font-size: 0.85rem;">{{ $item->sekolah_tujuan ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 30px; text-align: center; color: var(--text-muted);">Belum ada data siswa tidak aktif.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    @elseif ($activeTab === 'alumni')
        <!-- TAB 3: ALUMNI -->
        <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Alumni</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">NISN</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">L/P</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Rombel Terakhir</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Tahun Lulus</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($alumniList as $item)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 18px; font-weight: 700; color: var(--text-color);">{{ $item->nama }}</td>
                            <td style="padding: 12px 18px; font-family: monospace; font-size: 0.82rem;">{{ $item->nisn ?: '-' }}</td>
                            <td style="padding: 12px 18px; text-align: center;"><span class="badge {{ $item->jenis_kelamin === 'L' ? 'badge-primary' : 'badge-accent' }}">{{ $item->jenis_kelamin }}</span></td>
                            <td style="padding: 12px 18px; font-size: 0.85rem;">{{ $item->rombel_terakhir ?: '-' }}</td>
                            <td style="padding: 12px 18px; font-weight: 700; color: var(--primary);">{{ $item->tanggal_keluar ? date('Y', strtotime($item->tanggal_keluar)) : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding: 30px; text-align: center; color: var(--text-muted);">Belum ada data arsip alumni.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    @elseif ($activeTab === 'berkas')
        <!-- TAB 4: VERIFIKASI BERKAS FISIK -->
        <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Siswa</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Rombel</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Akta Lahir</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Kartu Keluarga</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Ijazah SMP</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">KTP Ortu</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($berkasList as $item)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 18px; font-weight: 700; color: var(--text-color);">
                                {{ $item->nama }}
                                <div style="font-size: 0.75rem; color: var(--text-muted);">NISN: {{ $item->nisn ?: '-' }}</div>
                            </td>
                            <td style="padding: 12px 18px; font-size: 0.85rem;">{{ $item->rombel_nama ?: '-' }}</td>
                            <td style="padding: 12px 18px; text-align: center;"><i class="fas {{ $item->akta_kelahiran ? 'fa-check text-success' : 'fa-xmark text-muted' }}"></i></td>
                            <td style="padding: 12px 18px; text-align: center;"><i class="fas {{ $item->kartu_keluarga ? 'fa-check text-success' : 'fa-xmark text-muted' }}"></i></td>
                            <td style="padding: 12px 18px; text-align: center;"><i class="fas {{ $item->ijazah_smp ? 'fa-check text-success' : 'fa-xmark text-muted' }}"></i></td>
                            <td style="padding: 12px 18px; text-align: center;"><i class="fas {{ $item->ktp_orang_tua ? 'fa-check text-success' : 'fa-xmark text-muted' }}"></i></td>
                            <td style="padding: 12px 18px; text-align: right;">
                                <button type="button" class="btn-icon btn-edit-berkas"
                                    data-id="{{ $item->peserta_didik_id }}"
                                    data-nama="{{ $item->nama }}"
                                    data-akta="{{ $item->akta_kelahiran ? '1' : '0' }}"
                                    data-kk="{{ $item->kartu_keluarga ? '1' : '0' }}"
                                    data-ijazah="{{ $item->ijazah_smp ? '1' : '0' }}"
                                    data-ktp="{{ $item->ktp_orang_tua ? '1' : '0' }}"
                                    data-kip="{{ $item->kip_pip ? '1' : '0' }}"
                                    title="Update Berkas">
                                    <i class="fas fa-pen-to-square"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 30px; text-align: center; color: var(--text-muted);">Belum ada data berkas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    @elseif ($activeTab === 'usulan')
        <!-- TAB 5: USULAN PERUBAHAN DATA SISWA -->
        <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Siswa</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Kolom Diusulkan</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nilai Baru</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Alasan</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Status</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($usulanList as $item)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 18px; font-weight: 700; color: var(--text-color);">
                                {{ $item->siswa?->nama ?: 'Siswa #' . $item->peserta_didik_id }}
                                <div style="font-size: 0.75rem; color: var(--text-muted);">NISN: {{ $item->siswa?->nisn ?: '-' }}</div>
                            </td>
                            <td style="padding: 12px 18px; font-family: monospace; font-weight: 700; color: var(--primary);">
                                {{ $item->kolom_perubahan }}
                            </td>
                            <td style="padding: 12px 18px; font-size: 0.85rem;">
                                {{ $item->nilai_baru }}
                            </td>
                            <td style="padding: 12px 18px; font-size: 0.85rem; color: var(--text-muted);">
                                {{ $item->alasan }}
                            </td>
                            <td style="padding: 12px 18px;">
                                @php
                                    $bColor = match ($item->status) {
                                        'disetujui' => 'badge-success',
                                        'ditolak' => 'badge-danger',
                                        default => 'badge-warning',
                                    };
                                @endphp
                                <span class="badge {{ $bColor }}">{{ strtoupper($item->status) }}</span>
                            </td>
                            <td style="padding: 12px 18px; text-align: right;">
                                <div class="table-actions">
                                    @if ($item->status === 'menunggu' && $canUpdate)
                                        <button type="button" class="btn-icon btn-verif-usulan"
                                            data-id="{{ $item->id }}"
                                            data-nama="{{ $item->siswa?->nama }}"
                                            data-kolom="{{ $item->kolom_perubahan }}"
                                            data-nilai="{{ $item->nilai_baru }}"
                                            title="Verifikasi Usulan">
                                            <i class="fas fa-check-to-slot text-primary"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 30px; text-align: center; color: var(--text-muted);">Belum ada usulan perubahan data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    <!-- MODAL: AJUKAN USULAN PERUBAHAN DATA -->
    <div id="modalUsulan" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="width: 100%; max-width: 520px; padding: 24px; border-radius: 12px; margin: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
                <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-color); margin: 0;">Ajukan Usulan Perubahan Data</h3>
                <button type="button" class="btn-icon close-modal" data-target="#modalUsulan"><i class="fas fa-xmark"></i></button>
            </div>
            <form id="formUsulan" method="POST" action="{{ route('dashboard.kesiswaan.peserta-didik.usulan.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Pilih Siswa <span class="text-danger">*</span></label>
                    <select name="peserta_didik_id" class="form-control" required style="width: 100%; border-radius: 8px;">
                        <option value="">-- Pilih Peserta Didik --</option>
                        @foreach ($siswaList as $sw)
                            <option value="{{ $sw->peserta_didik_id }}">{{ $sw->nama }} (NISN: {{ $sw->nisn ?: '-' }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Kolom yang Ingin Diubah <span class="text-danger">*</span></label>
                    <select name="kolom_perubahan" class="form-control" required style="width: 100%; border-radius: 8px;">
                        <option value="nama">Nama Lengkap</option>
                        <option value="nisn">NISN</option>
                        <option value="nik">NIK</option>
                        <option value="tempat_lahir">Tempat Lahir</option>
                        <option value="tanggal_lahir">Tanggal Lahir</option>
                        <option value="nama_ibu">Nama Ibu Kandung</option>
                        <option value="nama_ayah">Nama Ayah</option>
                        <option value="alamat_jalan">Alamat</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Nilai Baru yang Benar <span class="text-danger">*</span></label>
                    <input type="text" name="nilai_baru" class="form-control" placeholder="Tuliskan data yang benar sesuai dokumen resmi" required style="width: 100%; border-radius: 8px;">
                </div>
                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Alasan Perubahan <span class="text-danger">*</span></label>
                    <input type="text" name="alasan" class="form-control" placeholder="Contoh: Menyesuaikan Akta Kelahiran asli" required style="width: 100%; border-radius: 8px;">
                </div>
                <div class="form-group" style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Unggah Berkas Bukti (PDF / Foto Akta / KK)</label>
                    <input type="file" name="berkas_bukti" class="form-control" accept=".pdf,.jpg,.jpeg,.png" style="width: 100%; border-radius: 8px;">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-outline close-modal" data-target="#modalUsulan" style="border-radius: 8px;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="border-radius: 8px;"><i class="fas fa-paper-plane"></i> Kirim Usulan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/kesiswaan-peserta-didik.js') }}"></script>
@endpush
