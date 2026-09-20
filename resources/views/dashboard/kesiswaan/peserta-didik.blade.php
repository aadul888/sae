@extends('layouts.dashboard')

@section('title', 'Peserta Didik (Aktif, Tidak Aktif, Berkas, Usulan & Alumni) — SAE')
@section('dash_title', 'Peserta Didik')

@section('content')
    <style>
        .pd-nama[data-biodata-id]:hover {
            color: var(--primary) !important;
            text-decoration: underline;
        }
        .pd-foto-thumb[data-biodata-id]:hover {
            transform: scale(1.08);
            border-color: var(--primary) !important;
            box-shadow: 0 3px 10px rgba(99,102,241,0.35) !important;
        }
    </style>

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
                    Direktori lengkap siswa aktif, riwayat nonaktif, verifikasi berkas fisik, usulan revisi data, dan arsip alumni.
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
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: #2563eb;">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_alumni'] ?? 0) }}</div>
                <div class="dash-stat-label">Total Alumni</div>
            </div>
        </div>
    </div>

    <!-- 3. Navigation Tabs Wrapper (Urutan: Aktif, Tidak Aktif, Berkas Fisik, Usulan Perubahan, Alumni) -->
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
            <a href="{{ route('dashboard.kesiswaan.peserta-didik.index', ['tab' => 'berkas']) }}"
                class="periode-nav-tab {{ $activeTab === 'berkas' ? 'active' : '' }}">
                <i class="fas fa-folder-open"></i> Berkas Fisik
            </a>
            <a href="{{ route('dashboard.kesiswaan.peserta-didik.index', ['tab' => 'usulan']) }}"
                class="periode-nav-tab {{ $activeTab === 'usulan' ? 'active' : '' }}">
                <i class="fas fa-file-pen"></i> Usulan Perubahan
            </a>
            <a href="{{ route('dashboard.kesiswaan.peserta-didik.index', ['tab' => 'alumni']) }}"
                class="periode-nav-tab {{ $activeTab === 'alumni' ? 'active' : '' }}">
                <i class="fas fa-graduation-cap"></i> Alumni
            </a>
        </div>
    </div>

    <!-- 4. Toolbar Filter Global per Tab -->
    <div class="card" style="padding: 16px 20px; margin-bottom: 18px;">
        <form method="GET" action="{{ route('dashboard.kesiswaan.peserta-didik.index') }}" class="table-toolbar" style="margin-bottom: 0; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
            <div class="live-search-wrap" style="flex: 1; min-width: 240px; position: relative;">
                <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama, NISN, NIPD, atau NIK siswa..."
                    class="form-control" style="padding-left: 38px; width: 100%; border-radius: 8px;">
            </div>

            @if ($activeTab === 'aktif')
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
            @elseif ($activeTab === 'alumni')
                <input type="text" name="tahun_lulus" value="{{ $tahunLulus }}" placeholder="Tahun Lulus (Contoh: 2026)"
                    class="form-control" style="width: 180px; border-radius: 8px;">
            @endif

            <button type="submit" class="btn btn-outline" style="border-radius: 8px;"><i class="fas fa-filter"></i> Filter</button>
            @if ($q || $rombel || $gender || $tahunLulus)
                <a href="{{ route('dashboard.kesiswaan.peserta-didik.index', ['tab' => $activeTab]) }}"
                    class="btn btn-outline" style="border-radius: 8px;" title="Reset filter">
                    <i class="fas fa-undo"></i>
                </a>
            @endif
        </form>
    </div>

    <!-- 5. Content Sesuai Tab -->
    @if ($activeTab === 'aktif')
        <!-- TAB 1: SISWA AKTIF -->
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Lengkap</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">NISN / NIPD</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">L/P</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Rombel</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Tempat, Tanggal Lahir</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Orang Tua</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($aktifList as $item)
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <td class="cell-pd-nama" style="padding: 14px 18px; font-weight: 700; color: var(--text-color); font-size: 0.88rem;" data-label="Nama Lengkap">
                                <div class="pd-main-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                    @if (!empty($item->foto_url))
                                        <div class="pd-foto-thumb" data-biodata-id="{{ $item->peserta_didik_id }}" role="button" tabindex="0" title="Klik untuk melihat biodata lengkap {{ $item->nama }}"
                                            style="width: 42px; height: 42px; border-radius: 10px; background: repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 8px 8px; border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.25); cursor: pointer; transition: all 0.2s ease;">
                                            <img src="{{ $item->foto_url }}" alt="Foto {{ $item->nama }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                    @else
                                        <div class="pd-foto-thumb empty" data-biodata-id="{{ $item->peserta_didik_id }}" role="button" tabindex="0" title="Klik untuk melihat biodata lengkap {{ $item->nama }}"
                                            style="width: 42px; height: 42px; border-radius: 10px; background: rgba(99,102,241,0.08); border: 1.5px dashed rgba(99,102,241,0.4); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.05rem; flex-shrink: 0; cursor: pointer; transition: all 0.2s ease;">
                                            <i class="fas fa-user-graduate" style="opacity: 0.7;"></i>
                                        </div>
                                    @endif
                                    <div class="pd-info">
                                        <div class="pd-title-row" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                            <span class="pd-nama" data-biodata-id="{{ $item->peserta_didik_id }}" role="button" tabindex="0" title="Klik untuk melihat biodata lengkap {{ $item->nama }}"
                                                style="font-weight: 700; color: var(--text-color); cursor: pointer; transition: color 0.2s ease;">{{ $item->nama }}</span>
                                        </div>
                                        @if (!empty($item->nik))
                                            <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace; margin-top: 2px;">
                                                <span class="copyable" data-copy="{{ $item->nik }}" data-label="NIK" title="Klik untuk salin NIK">
                                                    NIK: {{ $item->nik }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="cell-pd-nisn" style="padding: 14px 18px; font-family: monospace; font-size: 0.84rem; color: var(--primary);" data-label="NISN/NIPD">
                                <div class="cell-col-right">
                                    <div>
                                        @if ($item->nisn)
                                            <span class="copyable" data-copy="{{ $item->nisn }}" data-label="NISN" title="Klik untuk salin NISN">{{ $item->nisn }}</span>
                                        @else
                                            -
                                        @endif
                                    </div>
                                    @if (!empty($item->nipd))
                                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                                            <span class="copyable" data-copy="{{ $item->nipd }}" data-label="NIPD" title="Klik untuk salin NIPD">NIPD: {{ $item->nipd }}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="cell-pd-gender" style="padding: 14px 18px; text-align: center;" data-label="L/P">
                                <span class="badge {{ $item->jenis_kelamin === 'L' ? 'badge-primary' : 'badge-danger' }}" style="font-size: 0.72rem; padding: 2px 7px;">
                                    {{ $item->jenis_kelamin ?: '-' }}
                                </span>
                            </td>
                            <td class="cell-pd-rombel" style="padding: 14px 18px; font-weight: 600; color: var(--text-color); font-size: 0.84rem;" data-label="Rombel">
                                <span class="badge badge-outline" style="font-size: 0.76rem; padding: 3px 8px;">
                                    {{ $item->nama_rombel ?: '-' }}
                                </span>
                            </td>
                            <td class="cell-pd-ttl" style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);" data-label="TTL">
                                {{ $item->tempat_lahir ? $item->tempat_lahir . ', ' : '' }}{{ $item->tanggal_lahir ? date('d/m/Y', strtotime($item->tanggal_lahir)) : '-' }}
                            </td>
                            <td style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);" data-label="Orang Tua">
                                {{ $item->nama_ayah ?: ($item->nama_ibu ?: '-') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                                <i class="fas fa-user-slash mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                                <div>Belum ada data peserta didik aktif yang cocok.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($aktifList->hasPages())
            <div class="custom-pagination">
                @if ($aktifList->onFirstPage())
                    <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                @else
                    <a href="{{ $aktifList->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
                @endif
                @php
                    $cur = $aktifList->currentPage();
                    $last = $aktifList->lastPage();
                    $from = max(1, $cur - 2);
                    $to = min($last, $cur + 2);
                @endphp
                @if ($from > 1)
                    <a href="{{ $aktifList->url(1) }}" class="page-btn">1</a>
                    @if ($from > 2) <span class="page-info">&hellip;</span> @endif
                @endif
                @for ($i = $from; $i <= $to; $i++)
                    <a href="{{ $aktifList->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                @endfor
                @if ($to < $last)
                    @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                    <a href="{{ $aktifList->url($last) }}" class="page-btn">{{ $last }}</a>
                @endif
                @if ($aktifList->hasMorePages())
                    <a href="{{ $aktifList->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
                @else
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        @endif

    @elseif ($activeTab === 'tidak_aktif')
        <!-- TAB 2: SISWA TIDAK AKTIF -->
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Lengkap</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">NISN / NIPD</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">L/P</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Rombel Terakhir</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Alasan Keluar</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Tanggal Keluar</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tidakAktifList as $item)
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <td class="cell-pd-nama" style="padding: 14px 18px; font-weight: 700; color: var(--text-color); font-size: 0.88rem;" data-label="Nama Lengkap">
                                <div class="pd-main-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                    @if (!empty($item->foto_url))
                                        <div class="pd-foto-thumb" data-biodata-id="{{ $item->peserta_didik_id }}" role="button" tabindex="0" title="Klik untuk melihat biodata lengkap {{ $item->nama }}"
                                            style="width: 42px; height: 42px; border-radius: 10px; background: repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 8px 8px; border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.25); cursor: pointer; transition: all 0.2s ease;">
                                            <img src="{{ $item->foto_url }}" alt="Foto {{ $item->nama }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                    @else
                                        <div class="pd-foto-thumb empty" data-biodata-id="{{ $item->peserta_didik_id }}" role="button" tabindex="0" title="Klik untuk melihat biodata lengkap {{ $item->nama }}"
                                            style="width: 42px; height: 42px; border-radius: 10px; background: rgba(99,102,241,0.08); border: 1.5px dashed rgba(99,102,241,0.4); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.05rem; flex-shrink: 0; cursor: pointer; transition: all 0.2s ease;">
                                            <i class="fas fa-user-graduate" style="opacity: 0.7;"></i>
                                        </div>
                                    @endif
                                    <div class="pd-info">
                                        <div class="pd-title-row" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                            <span class="pd-nama" data-biodata-id="{{ $item->peserta_didik_id }}" role="button" tabindex="0" title="Klik untuk melihat biodata lengkap {{ $item->nama }}"
                                                style="font-weight: 700; color: var(--text-color); cursor: pointer; transition: color 0.2s ease;">{{ $item->nama }}</span>
                                        </div>
                                        @if (!empty($item->nik))
                                            <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace; margin-top: 2px;">
                                                <span class="copyable" data-copy="{{ $item->nik }}" data-label="NIK" title="Klik untuk salin NIK">
                                                    NIK: {{ $item->nik }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="cell-pd-nisn" style="padding: 14px 18px; font-family: monospace; font-size: 0.84rem; color: var(--primary);" data-label="NISN/NIPD">
                                <div class="cell-col-right">
                                    <div>
                                        @if ($item->nisn)
                                            <span class="copyable" data-copy="{{ $item->nisn }}" data-label="NISN" title="Klik untuk salin NISN">{{ $item->nisn }}</span>
                                        @else
                                            -
                                        @endif
                                    </div>
                                    @if (!empty($item->nipd))
                                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                                            <span class="copyable" data-copy="{{ $item->nipd }}" data-label="NIPD" title="Klik untuk salin NIPD">NIPD: {{ $item->nipd }}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="cell-pd-gender" style="padding: 14px 18px; text-align: center;" data-label="L/P">
                                <span class="badge {{ $item->jenis_kelamin === 'L' ? 'badge-primary' : 'badge-danger' }}" style="font-size: 0.72rem; padding: 2px 7px;">
                                    {{ $item->jenis_kelamin ?: '-' }}
                                </span>
                            </td>
                            <td class="cell-pd-rombel" style="padding: 14px 18px; font-weight: 600; color: var(--text-color); font-size: 0.84rem;" data-label="Rombel Terakhir">
                                <span class="badge badge-outline" style="font-size: 0.76rem; padding: 3px 8px;">
                                    {{ $item->rombel_terakhir ?: '-' }}
                                </span>
                            </td>
                            <td style="padding: 14px 18px;" data-label="Alasan Keluar">
                                <span class="badge badge-danger" style="font-size: 0.74rem;">{{ $item->alasan_keluar ?: 'Keluar' }}</span>
                            </td>
                            <td style="padding: 14px 18px; font-size: 0.85rem; color: var(--text-muted);" data-label="Tanggal Keluar">
                                {{ $item->tanggal_keluar ? date('d/m/Y', strtotime($item->tanggal_keluar)) : '-' }}
                            </td>
                            <td style="padding: 14px 18px;" data-label="Status">
                                <span class="badge badge-outline" style="font-size: 0.74rem;">{{ $item->status_keluar ?: 'Nonaktif' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                                <i class="fas fa-user-slash mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                                <div>Belum ada data siswa tidak aktif.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($tidakAktifList->hasPages())
            <div class="custom-pagination">
                @if ($tidakAktifList->onFirstPage())
                    <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                @else
                    <a href="{{ $tidakAktifList->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
                @endif
                @php
                    $cur = $tidakAktifList->currentPage();
                    $last = $tidakAktifList->lastPage();
                    $from = max(1, $cur - 2);
                    $to = min($last, $cur + 2);
                @endphp
                @if ($from > 1)
                    <a href="{{ $tidakAktifList->url(1) }}" class="page-btn">1</a>
                    @if ($from > 2) <span class="page-info">&hellip;</span> @endif
                @endif
                @for ($i = $from; $i <= $to; $i++)
                    <a href="{{ $tidakAktifList->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                @endfor
                @if ($to < $last)
                    @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                    <a href="{{ $tidakAktifList->url($last) }}" class="page-btn">{{ $last }}</a>
                @endif
                @if ($tidakAktifList->hasMorePages())
                    <a href="{{ $tidakAktifList->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
                @else
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        @endif

    @elseif ($activeTab === 'berkas')
        <!-- TAB 3: VERIFIKASI BERKAS FISIK -->
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Siswa</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">NISN / NIPD</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Rombel</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Akta Lahir</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Kartu Keluarga</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Ijazah SMP</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">KTP Ortu</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">KIP / PIP</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($berkasList as $item)
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <td class="cell-pd-nama" style="padding: 14px 18px; font-weight: 700; color: var(--text-color); font-size: 0.88rem;" data-label="Nama Siswa">
                                <div class="pd-main-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                    @if (!empty($item->foto_url))
                                        <div class="pd-foto-thumb" data-biodata-id="{{ $item->peserta_didik_id }}" role="button" tabindex="0" title="Klik untuk melihat biodata lengkap {{ $item->nama }}"
                                            style="width: 42px; height: 42px; border-radius: 10px; background: repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 8px 8px; border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.25); cursor: pointer; transition: all 0.2s ease;">
                                            <img src="{{ $item->foto_url }}" alt="Foto {{ $item->nama }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                    @else
                                        <div class="pd-foto-thumb empty" data-biodata-id="{{ $item->peserta_didik_id }}" role="button" tabindex="0" title="Klik untuk melihat biodata lengkap {{ $item->nama }}"
                                            style="width: 42px; height: 42px; border-radius: 10px; background: rgba(99,102,241,0.08); border: 1.5px dashed rgba(99,102,241,0.4); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.05rem; flex-shrink: 0; cursor: pointer; transition: all 0.2s ease;">
                                            <i class="fas fa-user-graduate" style="opacity: 0.7;"></i>
                                        </div>
                                    @endif
                                    <div class="pd-info">
                                        <div class="pd-title-row" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                            <span class="pd-nama" data-biodata-id="{{ $item->peserta_didik_id }}" role="button" tabindex="0" title="Klik untuk melihat biodata lengkap {{ $item->nama }}"
                                                style="font-weight: 700; color: var(--text-color); cursor: pointer; transition: color 0.2s ease;">{{ $item->nama }}</span>
                                        </div>
                                        @if (!empty($item->nik))
                                            <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace; margin-top: 2px;">
                                                <span class="copyable" data-copy="{{ $item->nik }}" data-label="NIK" title="Klik untuk salin NIK">
                                                    NIK: {{ $item->nik }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="cell-pd-nisn" style="padding: 14px 18px; font-family: monospace; font-size: 0.84rem; color: var(--primary);" data-label="NISN/NIPD">
                                <div class="cell-col-right">
                                    <div>
                                        @if ($item->nisn)
                                            <span class="copyable" data-copy="{{ $item->nisn }}" data-label="NISN" title="Klik untuk salin NISN">{{ $item->nisn }}</span>
                                        @else
                                            -
                                        @endif
                                    </div>
                                    @if (!empty($item->nipd))
                                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                                            <span class="copyable" data-copy="{{ $item->nipd }}" data-label="NIPD" title="Klik untuk salin NIPD">NIPD: {{ $item->nipd }}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="cell-pd-rombel" style="padding: 14px 18px; font-weight: 600; color: var(--text-color); font-size: 0.84rem;" data-label="Rombel">
                                <span class="badge badge-outline" style="font-size: 0.76rem; padding: 3px 8px;">
                                    {{ $item->rombel_nama ?: '-' }}
                                </span>
                            </td>
                            <td style="padding: 14px 18px; text-align: center;" data-label="Akta Lahir">
                                <i class="fas {{ $item->akta_kelahiran ? 'fa-circle-check text-success' : 'fa-circle-xmark text-muted' }}" style="font-size: 1.1rem;"></i>
                            </td>
                            <td style="padding: 14px 18px; text-align: center;" data-label="Kartu Keluarga">
                                <i class="fas {{ $item->kartu_keluarga ? 'fa-circle-check text-success' : 'fa-circle-xmark text-muted' }}" style="font-size: 1.1rem;"></i>
                            </td>
                            <td style="padding: 14px 18px; text-align: center;" data-label="Ijazah SMP">
                                <i class="fas {{ $item->ijazah_smp ? 'fa-circle-check text-success' : 'fa-circle-xmark text-muted' }}" style="font-size: 1.1rem;"></i>
                            </td>
                            <td style="padding: 14px 18px; text-align: center;" data-label="KTP Ortu">
                                <i class="fas {{ $item->ktp_orang_tua ? 'fa-circle-check text-success' : 'fa-circle-xmark text-muted' }}" style="font-size: 1.1rem;"></i>
                            </td>
                            <td style="padding: 14px 18px; text-align: center;" data-label="KIP / PIP">
                                <i class="fas {{ $item->kip_pip ? 'fa-circle-check text-success' : 'fa-circle-xmark text-muted' }}" style="font-size: 1.1rem;"></i>
                            </td>
                            <td class="cell-pd-aksi" style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                                <div class="table-actions">
                                    <button type="button" class="btn-icon btn-edit-berkas"
                                        data-id="{{ $item->peserta_didik_id }}"
                                        data-nama="{{ $item->nama }}"
                                        data-akta="{{ $item->akta_kelahiran ? '1' : '0' }}"
                                        data-kk="{{ $item->kartu_keluarga ? '1' : '0' }}"
                                        data-ijazah="{{ $item->ijazah_smp ? '1' : '0' }}"
                                        data-ktp="{{ $item->ktp_orang_tua ? '1' : '0' }}"
                                        data-kip="{{ $item->kip_pip ? '1' : '0' }}"
                                        title="Verifikasi &amp; Perbarui Berkas">
                                        <i class="fas fa-pen-to-square"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                                <i class="fas fa-folder-open mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                                <div>Belum ada data berkas siswa.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($berkasList->hasPages())
            <div class="custom-pagination">
                @if ($berkasList->onFirstPage())
                    <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                @else
                    <a href="{{ $berkasList->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
                @endif
                @php
                    $cur = $berkasList->currentPage();
                    $last = $berkasList->lastPage();
                    $from = max(1, $cur - 2);
                    $to = min($last, $cur + 2);
                @endphp
                @if ($from > 1)
                    <a href="{{ $berkasList->url(1) }}" class="page-btn">1</a>
                    @if ($from > 2) <span class="page-info">&hellip;</span> @endif
                @endif
                @for ($i = $from; $i <= $to; $i++)
                    <a href="{{ $berkasList->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                @endfor
                @if ($to < $last)
                    @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                    <a href="{{ $berkasList->url($last) }}" class="page-btn">{{ $last }}</a>
                @endif
                @if ($berkasList->hasMorePages())
                    <a href="{{ $berkasList->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
                @else
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        @endif

    @elseif ($activeTab === 'usulan')
        <!-- TAB 4: USULAN PERUBAHAN DATA SISWA -->
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Siswa</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Kolom Diusulkan</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nilai Baru</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Alasan Perubahan</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Berkas Bukti</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Status</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($usulanList as $item)
                        @php
                            $usulanPdId = $item->peserta_didik_id ?: ($item->siswa?->peserta_didik_id ?? '');
                        @endphp
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <td class="cell-pd-nama" style="padding: 14px 18px; font-weight: 700; color: var(--text-color); font-size: 0.88rem;" data-label="Nama Siswa">
                                <div class="pd-main-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                    @if (!empty($item->foto_url))
                                        <div class="pd-foto-thumb" data-biodata-id="{{ $usulanPdId }}" role="button" tabindex="0" title="Klik untuk melihat biodata lengkap {{ $item->siswa?->nama }}"
                                            style="width: 42px; height: 42px; border-radius: 10px; background: repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 8px 8px; border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.25); cursor: pointer; transition: all 0.2s ease;">
                                            <img src="{{ $item->foto_url }}" alt="Foto {{ $item->siswa?->nama }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                    @else
                                        <div class="pd-foto-thumb empty" data-biodata-id="{{ $usulanPdId }}" role="button" tabindex="0" title="Klik untuk melihat biodata lengkap {{ $item->siswa?->nama }}"
                                            style="width: 42px; height: 42px; border-radius: 10px; background: rgba(99,102,241,0.08); border: 1.5px dashed rgba(99,102,241,0.4); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.05rem; flex-shrink: 0; cursor: pointer; transition: all 0.2s ease;">
                                            <i class="fas fa-user-graduate" style="opacity: 0.7;"></i>
                                        </div>
                                    @endif
                                    <div class="pd-info">
                                        <div class="pd-title-row" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                            <span class="pd-nama" data-biodata-id="{{ $usulanPdId }}" role="button" tabindex="0" title="Klik untuk melihat biodata lengkap {{ $item->siswa?->nama }}"
                                                style="font-weight: 700; color: var(--text-color); cursor: pointer; transition: color 0.2s ease;">{{ $item->siswa?->nama ?: 'Siswa #' . $item->peserta_didik_id }}</span>
                                        </div>
                                        <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace; margin-top: 2px;">
                                            @if ($item->siswa?->nisn)
                                                <span class="copyable" data-copy="{{ $item->siswa->nisn }}" data-label="NISN" title="Klik untuk salin NISN">
                                                    NISN: {{ $item->siswa->nisn }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 14px 18px;" data-label="Kolom Diusulkan">
                                <span class="badge badge-outline" style="font-family: monospace; font-size: 0.76rem; text-transform: uppercase;">
                                    {{ str_replace('_', ' ', $item->kolom_perubahan) }}
                                </span>
                            </td>
                            <td style="padding: 14px 18px; font-size: 0.85rem; font-weight: 700; color: var(--text-color);" data-label="Nilai Baru">
                                {{ $item->nilai_baru }}
                            </td>
                            <td style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);" data-label="Alasan">
                                {{ $item->alasan }}
                            </td>
                            <td style="padding: 14px 18px; text-align: center;" data-label="Berkas Bukti">
                                @if (!empty($item->berkas_bukti_path))
                                    <a href="{{ asset('storage/' . ltrim($item->berkas_bukti_path, '/')) }}" target="_blank" class="btn-icon" title="Lihat Bukti Berkas">
                                        <i class="fas fa-file-arrow-down text-primary"></i>
                                    </a>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.8rem;">-</span>
                                @endif
                            </td>
                            <td style="padding: 14px 18px;" data-label="Status">
                                @php
                                    $bColor = match ($item->status) {
                                        'disetujui' => 'badge-success',
                                        'ditolak' => 'badge-danger',
                                        default => 'badge-warning',
                                    };
                                @endphp
                                <span class="badge {{ $bColor }}" style="font-size: 0.72rem; padding: 2px 8px;">{{ strtoupper($item->status) }}</span>
                            </td>
                            <td class="cell-pd-aksi" style="padding: 14px 18px; text-align: right;" data-label="Aksi">
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
                                    @else
                                        <span style="color: var(--text-muted); font-size: 0.82rem;">-</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                                <i class="fas fa-file-circle-question mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                                <div>Belum ada usulan perubahan data siswa.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($usulanList->hasPages())
            <div class="custom-pagination">
                @if ($usulanList->onFirstPage())
                    <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                @else
                    <a href="{{ $usulanList->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
                @endif
                @php
                    $cur = $usulanList->currentPage();
                    $last = $usulanList->lastPage();
                    $from = max(1, $cur - 2);
                    $to = min($last, $cur + 2);
                @endphp
                @if ($from > 1)
                    <a href="{{ $usulanList->url(1) }}" class="page-btn">1</a>
                    @if ($from > 2) <span class="page-info">&hellip;</span> @endif
                @endif
                @for ($i = $from; $i <= $to; $i++)
                    <a href="{{ $usulanList->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                @endfor
                @if ($to < $last)
                    @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                    <a href="{{ $usulanList->url($last) }}" class="page-btn">{{ $last }}</a>
                @endif
                @if ($usulanList->hasMorePages())
                    <a href="{{ $usulanList->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
                @else
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        @endif

    @elseif ($activeTab === 'alumni')
        <!-- TAB 5: ALUMNI (DIALIHKAN SETELAH USULAN PERUBAHAN) -->
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Alumni</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">NISN / NIPD</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">L/P</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Rombel Terakhir</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Tahun Lulus</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($alumniList as $item)
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <td class="cell-pd-nama" style="padding: 14px 18px; font-weight: 700; color: var(--text-color); font-size: 0.88rem;" data-label="Nama Alumni">
                                <div class="pd-main-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                    @if (!empty($item->foto_url))
                                        <div class="pd-foto-thumb" data-biodata-id="{{ $item->peserta_didik_id }}" role="button" tabindex="0" title="Klik untuk melihat biodata lengkap {{ $item->nama }}"
                                            style="width: 42px; height: 42px; border-radius: 10px; background: repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 8px 8px; border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.25); cursor: pointer; transition: all 0.2s ease;">
                                            <img src="{{ $item->foto_url }}" alt="Foto {{ $item->nama }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                    @else
                                        <div class="pd-foto-thumb empty" data-biodata-id="{{ $item->peserta_didik_id }}" role="button" tabindex="0" title="Klik untuk melihat biodata lengkap {{ $item->nama }}"
                                            style="width: 42px; height: 42px; border-radius: 10px; background: rgba(99,102,241,0.08); border: 1.5px dashed rgba(99,102,241,0.4); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.05rem; flex-shrink: 0; cursor: pointer; transition: all 0.2s ease;">
                                            <i class="fas fa-user-graduate" style="opacity: 0.7;"></i>
                                        </div>
                                    @endif
                                    <div class="pd-info">
                                        <div class="pd-title-row" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                            <span class="pd-nama" data-biodata-id="{{ $item->peserta_didik_id }}" role="button" tabindex="0" title="Klik untuk melihat biodata lengkap {{ $item->nama }}"
                                                style="font-weight: 700; color: var(--text-color); cursor: pointer; transition: color 0.2s ease;">{{ $item->nama }}</span>
                                        </div>
                                        @if (!empty($item->nik))
                                            <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace; margin-top: 2px;">
                                                <span class="copyable" data-copy="{{ $item->nik }}" data-label="NIK" title="Klik untuk salin NIK">
                                                    NIK: {{ $item->nik }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="cell-pd-nisn" style="padding: 14px 18px; font-family: monospace; font-size: 0.84rem; color: var(--primary);" data-label="NISN/NIPD">
                                <div class="cell-col-right">
                                    <div>
                                        @if ($item->nisn)
                                            <span class="copyable" data-copy="{{ $item->nisn }}" data-label="NISN" title="Klik untuk salin NISN">{{ $item->nisn }}</span>
                                        @else
                                            -
                                        @endif
                                    </div>
                                    @if (!empty($item->nipd))
                                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                                            <span class="copyable" data-copy="{{ $item->nipd }}" data-label="NIPD" title="Klik untuk salin NIPD">NIPD: {{ $item->nipd }}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="cell-pd-gender" style="padding: 14px 18px; text-align: center;" data-label="L/P">
                                <span class="badge {{ $item->jenis_kelamin === 'L' ? 'badge-primary' : 'badge-danger' }}" style="font-size: 0.72rem; padding: 2px 7px;">
                                    {{ $item->jenis_kelamin ?: '-' }}
                                </span>
                            </td>
                            <td class="cell-pd-rombel" style="padding: 14px 18px; font-weight: 600; color: var(--text-color); font-size: 0.84rem;" data-label="Rombel Terakhir">
                                <span class="badge badge-outline" style="font-size: 0.76rem; padding: 3px 8px;">
                                    {{ $item->rombel_terakhir ?: '-' }}
                                </span>
                            </td>
                            <td style="padding: 14px 18px; text-align: center;" data-label="Tahun Lulus">
                                <span class="badge badge-primary" style="font-weight: 700; font-size: 0.8rem; padding: 3px 8px;">
                                    {{ $item->tahun_lulus ?: ($item->tanggal_keluar ? date('Y', strtotime($item->tanggal_keluar)) : '-') }}
                                </span>
                            </td>
                            <td style="padding: 14px 18px; font-size: 0.85rem; color: var(--text-muted);" data-label="Keterangan">
                                {{ $item->alasan_keluar ?: 'Alumni / Tamat Belajar' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                                <i class="fas fa-graduation-cap mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                                <div>Belum ada data arsip alumni.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($alumniList->hasPages())
            <div class="custom-pagination">
                @if ($alumniList->onFirstPage())
                    <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                @else
                    <a href="{{ $alumniList->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
                @endif
                @php
                    $cur = $alumniList->currentPage();
                    $last = $alumniList->lastPage();
                    $from = max(1, $cur - 2);
                    $to = min($last, $cur + 2);
                @endphp
                @if ($from > 1)
                    <a href="{{ $alumniList->url(1) }}" class="page-btn">1</a>
                    @if ($from > 2) <span class="page-info">&hellip;</span> @endif
                @endif
                @for ($i = $from; $i <= $to; $i++)
                    <a href="{{ $alumniList->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                @endfor
                @if ($to < $last)
                    @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                    <a href="{{ $alumniList->url($last) }}" class="page-btn">{{ $last }}</a>
                @endif
                @if ($alumniList->hasMorePages())
                    <a href="{{ $alumniList->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
                @else
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        @endif
    @endif

    <!-- MODAL 1: DETAIL BIODATA LENGKAP PESERTA DIDIK (Persis seperti Manajemen Data) -->
    <div id="biodataModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 720px; width: 94%; max-height: 85vh; display: flex; flex-direction: column; margin: 0; border-radius: 14px; padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div id="bioFotoContainer"
                        style="width: 50px; height: 50px; border-radius: 12px; background: rgba(99,102,241,0.12); display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1.5px solid var(--border-color); flex-shrink: 0; box-shadow: 0 2px 8px rgba(0,0,0,0.25);">
                        <i class="fas fa-user-graduate text-primary" style="font-size: 1.3rem;"></i>
                    </div>
                    <div>
                        <h3 id="bioNama"
                            style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0;">
                            Biodata Peserta Didik</h3>
                        <div id="bioRombel" style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">-</div>
                    </div>
                </div>
                <button type="button" class="close-modal" data-target="#biodataModal"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="bioLoading" style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin me-2" style="font-size: 1.4rem;"></i>
                <div>Memuat biodata peserta didik...</div>
            </div>

            <div id="bioContent" style="overflow-y: auto; flex: 1; display: none; font-size: 0.84rem;">
                <div style="margin-bottom: 12px;">
                    <div
                        style="font-weight: 700; color: var(--primary); font-size: 0.82rem; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-user me-1"></i> Data Pribadi &amp; Fisik
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 140px;">NISN / NIPD</td>
                            <td id="bioNisn" style="font-weight: 600; font-family: monospace; color: var(--primary);">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">NIK</td>
                            <td id="bioNik" style="font-family: monospace;">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Jenis Kelamin</td>
                            <td id="bioJk">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Tempat, Tgl Lahir</td>
                            <td id="bioTtl">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Agama</td>
                            <td id="bioAgama">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Anak Keberapa</td>
                            <td id="bioAnak">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Tinggi / Berat Badan</td>
                            <td id="bioFisik" style="font-weight: 600; color: #10b981;">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Kebutuhan Khusus</td>
                            <td id="bioKhusus">-</td>
                        </tr>
                    </table>
                </div>

                <div style="margin-bottom: 12px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                    <div
                        style="font-weight: 700; color: var(--primary); font-size: 0.82rem; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-graduation-cap me-1"></i> Data Akademik &amp; Pendaftaran
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 140px;">Pendaftaran / Asal</td>
                            <td id="bioPendaftaran">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Tanggal Masuk</td>
                            <td id="bioTglMasuk">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">ID Registrasi / Anggota</td>
                            <td id="bioRegId"
                                style="font-family: monospace; font-size: 0.78rem; color: var(--text-muted);">-</td>
                        </tr>
                    </table>
                </div>

                <div style="margin-bottom: 12px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                    <div
                        style="font-weight: 700; color: var(--primary); font-size: 0.82rem; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-users me-1"></i> Data Orang Tua &amp; Wali
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 140px;">Nama Ayah</td>
                            <td id="bioAyah">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Nama Ibu</td>
                            <td id="bioIbu">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Nama Wali</td>
                            <td id="bioWali">-</td>
                        </tr>
                    </table>
                </div>

                <div style="margin-bottom: 12px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                    <div
                        style="font-weight: 700; color: var(--primary); font-size: 0.82rem; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-address-book me-1"></i> Kontak &amp; Domisili
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 140px;">Kontak (HP/Email)</td>
                            <td id="bioHp">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Alamat Jalan</td>
                            <td id="bioAlamat">-</td>
                        </tr>
                    </table>
                </div>

                <div id="bioMapelSection"
                    style="margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color); display: none;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-weight: 700; color: var(--text-color); font-size: 0.85rem;"><i
                                class="fas fa-book-open text-primary me-1"></i> Mata Pelajaran di Kelas</span>
                        <span id="bioJmlMapel" class="badge"
                            style="background: rgba(99,102,241,0.15); color: var(--primary); font-size: 0.72rem; padding: 2px 7px;">0
                            Mapel</span>
                    </div>
                    <div style="overflow-x: auto; max-height: 180px;">
                        <table class="table"
                            style="width: 100%; border-collapse: collapse; font-size: 0.80rem; margin-bottom: 0;">
                            <thead>
                                <tr
                                    style="background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--border-color);">
                                    <th style="padding: 6px 10px; color: var(--text-muted);">Mata Pelajaran</th>
                                    <th style="padding: 6px 10px; color: var(--text-muted);">Guru Pengampu</th>
                                    <th style="padding: 6px 10px; color: var(--text-muted); text-align: center;">Jam</th>
                                </tr>
                            </thead>
                            <tbody id="bioMapelList"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div
                style="display: flex; justify-content: flex-end; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color);">
                <button type="button" class="btn btn-outline close-modal" data-target="#biodataModal"
                    style="padding: 8px 18px; font-size: 0.82rem;">Tutup</button>
            </div>
        </div>
    </div>

    <!-- MODAL 2: AJUKAN USULAN PERUBAHAN DATA (Clean Global SAE Modal Style) -->
    <div id="modalUsulan" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="width: 100%; max-width: 520px; padding: 24px; border-radius: 12px; margin: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
                <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-color); margin: 0;">Ajukan Usulan Perubahan Data</h3>
                <button type="button" class="close-modal" data-target="#modalUsulan" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;"><i class="fas fa-times"></i></button>
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
