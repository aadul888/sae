@extends('layouts.dashboard')

@section('title', 'Import Nilai & Pengaturan Buku Induk — SAE')
@section('dash_title', 'Buku Induk Siswa')

@section('content')
    {{-- 1. Header Banner --}}
    <div class="dash-banner"
        style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div
                style="width: 44px; height: 44px; border-radius: var(--radius-lg, 12px); background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                <i class="fas fa-file-import"></i>
            </div>
            <div>
                <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                    Import Nilai &amp; Pengaturan Buku Induk
                </h2>
                <p style="margin: 2px 0 0 0; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                    Unggah nilai (satu nilai per mata pelajaran per semester) dan atur penandatangan lembar buku induk.
                </p>
            </div>
        </div>
    </div>

    {{-- 2. Statistik Ringkasan --}}
    <div class="dash-stat-grid" style="margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(139,92,246,0.12); color: var(--purple, #8b5cf6);">
                <i class="fas fa-book"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_mapel'] ?? 0, 0, ',', '.') }}</div>
                <div class="dash-stat-label">Total Mapel Tersusun</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: var(--success, #10b981);">
                <i class="fas fa-layer-group"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['mapel_umum'] ?? 0, 0, ',', '.') }}</div>
                <div class="dash-stat-label">Mapel Umum</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b);">
                <i class="fas fa-screwdriver-wrench"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['mapel_kejuruan'] ?? 0, 0, ',', '.') }}</div>
                <div class="dash-stat-label">Mapel Kejuruan</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6);">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_referensi'] ?? 0, 0, ',', '.') }}</div>
                <div class="dash-stat-label">Referensi Pembelajaran</div>
            </div>
        </div>
    </div>

    {{-- 3. Tab Navigasi --}}
    @include('dashboard.kesiswaan.buku-induk.partials.tabs', ['activeTab' => 'nilai'])

    {{-- 4. Kartu Import Nilai --}}
    <div class="card" style="padding: 18px; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 12px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div
                    style="width: 38px; height: 38px; border-radius: var(--radius-md, 8px); background: rgba(16,185,129,0.12); color: var(--success, #10b981); display: flex; align-items: center; justify-content: center; font-size: var(--text-lg, 1.125rem); flex-shrink: 0;">
                    <i class="fas fa-file-excel"></i>
                </div>
                <div>
                    <div style="font-weight: 800; color: var(--text-color); font-size: var(--text-md, 1rem);">Import Nilai</div>
                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">Format: NISN, Nama, Kelas, Nama Mata Pelajaran, Semester 1&ndash;6</div>
                </div>
            </div>
            <a href="{{ route('dashboard.kesiswaan.buku-induk.nilai.template') }}" class="btn btn-outline"
                style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; font-size: var(--text-sm, 0.8125rem); text-decoration: none; border-color: var(--success, #10b981); color: var(--success, #10b981);">
                <i class="fas fa-download"></i> Unduh Template Excel
            </a>
        </div>

        <div
            style="margin-bottom: 14px; padding: 10px 12px; border-radius: var(--radius-md, 8px); background: rgba(99,102,241,0.08); border: 1px solid rgba(99,102,241,0.2); font-size: var(--text-xs, 0.75rem); line-height: 1.5; color: var(--text-color);">
            <i class="fas fa-info-circle" style="color: var(--primary, #3b82f6);"></i>
            Satu baris per mata pelajaran; isi nilai untuk <strong>6 semester</strong> (kosongkan yang belum ada). Kolom <strong>Nama</strong> &amp; <strong>Kelas</strong> hanya untuk memudahkan pengisian (acuan tetap NISN). Nilai yang sama untuk mapel &amp; semester akan diperbarui otomatis.
        </div>

        @if ($can['create'])
            <form id="formImportNilai" action="{{ route('dashboard.kesiswaan.buku-induk.nilai.import') }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <label for="fileNilai"
                    style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                    Pilih Berkas Excel / CSV <span style="color: var(--danger, #ef4444);">*</span>
                </label>
                <div class="bi-import-row">
                    <input type="file" id="fileNilai" name="file_excel" accept=".xlsx,.xls,.csv,.txt" class="bi-file-input" required>
                    <button type="submit" id="btnImportNilai" class="btn btn-primary"
                        style="padding: 9px 22px; font-size: var(--text-sm, 0.8125rem); display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;">
                        <i class="fas fa-cloud-arrow-up"></i> Import Nilai
                    </button>
                </div>
                <div style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted); margin-top: 6px;">Mendukung .xlsx, .xls, .csv (maks 20 MB).</div>
            </form>
        @else
            <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">Anda tidak memiliki hak untuk mengimpor nilai.</div>
        @endif
    </div>

    {{-- 5. Kartu Pengaturan (penandatangan lembar cetak) --}}
    <div class="card" style="padding: 18px;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px;">
            <div
                style="width: 38px; height: 38px; border-radius: var(--radius-md, 8px); background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); display: flex; align-items: center; justify-content: center; font-size: var(--text-lg, 1.125rem); flex-shrink: 0;">
                <i class="fas fa-sliders"></i>
            </div>
            <div>
                <div style="font-weight: 800; color: var(--text-color); font-size: var(--text-md, 1rem);">Pengaturan Buku Induk</div>
                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">Tahun ajaran &amp; penandatangan lembar cetak. Daftar mapel diatur di tab <strong>Maping Mata Pelajaran</strong>.</div>
            </div>
        </div>

        <form id="formPengaturan" action="{{ route('dashboard.kesiswaan.buku-induk.pengaturan.save') }}" method="POST">
            @csrf

            {{-- Penandatangan & Tahun Ajaran --}}
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px; margin-bottom: 18px;">
                <div>
                    <label style="display: block; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">Tahun Ajaran</label>
                    <input type="text" name="tahun_ajaran" class="bi-input" value="{{ $form['tahun_ajaran'] ?? '' }}"
                        placeholder="2025/2026">
                </div>
                <div>
                    <label style="display: block; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">Kepala Sekolah</label>
                    <input type="text" name="nama_kepala_sekolah" class="bi-input" value="{{ $form['nama_kepala_sekolah'] ?? '' }}">
                </div>
                <div>
                    <label style="display: block; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">NIP Kepala Sekolah</label>
                    <input type="text" name="nip_kepala_sekolah" class="bi-input" value="{{ $form['nip_kepala_sekolah'] ?? '' }}">
                </div>
                <div>
                    <label style="display: block; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">Petugas Buku Induk</label>
                    <input type="text" name="nama_petugas" class="bi-input" value="{{ $form['nama_petugas'] ?? '' }}">
                </div>
                <div>
                    <label style="display: block; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">NIP Petugas</label>
                    <input type="text" name="nip_petugas" class="bi-input" value="{{ $form['nip_petugas'] ?? '' }}">
                </div>
            </div>

            @if ($can['update'])
                <button type="submit" class="btn btn-primary"
                    style="margin-top: 16px; padding: 8px 20px; font-size: var(--text-sm, 0.8125rem); display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-save"></i> Simpan Pengaturan
                </button>
            @endif
        </form>
    </div>
@endsection

@include('dashboard.kesiswaan.buku-induk.partials.buku-induk-style')

@push('scripts')
    <script src="{{ asset('js/buku-induk-nilai.js') }}?v={{ file_exists(public_path('js/buku-induk-nilai.js')) ? filemtime(public_path('js/buku-induk-nilai.js')) : time() }}"></script>
@endpush
