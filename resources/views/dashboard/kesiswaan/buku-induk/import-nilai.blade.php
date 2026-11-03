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
                    Unggah nilai (satu nilai per mata pelajaran per semester), susun daftar mata pelajaran, dan atur penandatangan lembar buku induk.
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

    {{-- 5. Kartu Pengaturan + Susun Mata Pelajaran --}}
    <div class="card" style="padding: 18px;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px;">
            <div
                style="width: 38px; height: 38px; border-radius: var(--radius-md, 8px); background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); display: flex; align-items: center; justify-content: center; font-size: var(--text-lg, 1.125rem); flex-shrink: 0;">
                <i class="fas fa-sliders"></i>
            </div>
            <div>
                <div style="font-weight: 800; color: var(--text-color); font-size: var(--text-md, 1rem);">Pengaturan Buku Induk</div>
                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">Susun mata pelajaran &amp; penandatangan lembar cetak</div>
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

            {{-- Susunan Mata Pelajaran --}}
            <div class="bi-mapel-section">
                <div style="margin-bottom: 12px;">
                    <div style="font-weight: 800; color: var(--text-color); font-size: var(--text-md, 1rem);">Susunan Mata Pelajaran</div>
                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                        Ambil dari referensi <strong>pembelajaran</strong> Dapodik atau tambah manual. Gunakan panah untuk mengatur urutan.
                    </div>
                </div>

                <div class="bi-mapel-grid">
                    @foreach (['umum' => ['label' => 'Mapel Umum', 'color' => '#10b981'], 'kejuruan' => ['label' => 'Mapel Kejuruan', 'color' => '#f59e0b']] as $key => $meta)
                        <div class="bi-mapel-col" data-kelompok="{{ $key }}">
                            <div class="bi-col-head">
                                <span><i class="fas fa-layer-group" style="color: {{ $meta['color'] }};"></i> {{ $meta['label'] }}</span>
                                <span class="bi-count" data-count>0</span>
                            </div>

                            <div class="bi-ref-wrap">
                                <div class="bi-ref-search-wrap">
                                    <i class="fas fa-search"></i>
                                    <input type="text" class="bi-input bi-ref-search" placeholder="Cari mapel referensi untuk ditambahkan...">
                                </div>
                                <div class="bi-ref-list" data-ref></div>
                            </div>

                            <div class="bi-selected" data-selected></div>

                            <div class="bi-add-custom">
                                <input type="text" class="bi-input bi-custom-input" placeholder="Tambah mapel manual...">
                                <button type="button" class="btn btn-outline bi-add-btn" title="Tambah mapel manual">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>

                            <textarea name="mapel_{{ $key }}" class="bi-hidden-mapel" hidden>{{ implode("\n", $mapel[$key]) }}</textarea>
                        </div>
                    @endforeach
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

    {{-- Data referensi mapel dari tabel pembelajaran (untuk JS) --}}
    <script type="application/json" id="biRefData">@json($referensi)</script>
@endsection

@push('styles')
    <style>
        .bi-input {
            width: 100%;
            min-height: 34px;
            padding: 6px 10px;
            font-size: var(--text-sm, 0.8125rem);
            border: 1px solid var(--border-color, #e5e7eb);
            border-radius: var(--radius-md, 8px);
            background: var(--bg-hover, #f9fafb);
            color: var(--text-color, #111827);
            box-sizing: border-box;
        }
        .bi-mapel-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; }
        .bi-mapel-col {
            border: 1px solid var(--border-color, #e5e7eb);
            border-radius: var(--radius-lg, 12px);
            padding: 12px;
            background: var(--bg-card, #fff);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .bi-col-head { display: flex; align-items: center; justify-content: space-between; font-weight: 800; color: var(--text-color); font-size: var(--text-sm, 0.8125rem); }
        .bi-count {
            font-size: var(--text-2xs, 0.6875rem); font-weight: 700; color: var(--text-muted);
            background: rgba(0,0,0,0.05); border-radius: 999px; padding: 2px 8px;
        }
        .bi-ref-search-wrap { position: relative; }
        .bi-ref-search-wrap i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: var(--text-xs, 0.75rem); }
        .bi-ref-search-wrap .bi-ref-search { padding-left: 28px; }
        .bi-ref-list { max-height: 150px; overflow-y: auto; border: 1px dashed var(--border-color, #e5e7eb); border-radius: var(--radius-md, 8px); margin-top: 6px; }
        .bi-ref-item { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 5px 8px; font-size: var(--text-xs, 0.75rem); border-bottom: 1px solid var(--border-color, #f1f2f4); }
        .bi-ref-item:last-child { border-bottom: none; }
        .bi-ref-item span { color: var(--text-color); }
        .bi-ref-item button { border: none; background: rgba(16,185,129,0.12); color: #10b981; width: 22px; height: 22px; border-radius: var(--radius-sm, 6px); cursor: pointer; flex-shrink: 0; }
        .bi-ref-empty { padding: 10px; text-align: center; color: var(--text-muted); font-size: var(--text-xs, 0.75rem); }
        .bi-selected { display: flex; flex-direction: column; gap: 6px; min-height: 40px; }
        .bi-sel-empty { text-align: center; color: var(--text-muted); font-size: var(--text-xs, 0.75rem); padding: 10px; border: 1px dashed var(--border-color, #e5e7eb); border-radius: var(--radius-md, 8px); }
        .bi-sel-item { display: flex; align-items: center; gap: 8px; padding: 6px 8px; border-radius: var(--radius-md, 8px); background: rgba(99,102,241,0.06); border: 1px solid rgba(99,102,241,0.18); }
        .bi-sel-idx { font-size: var(--text-2xs, 0.6875rem); font-weight: 700; color: var(--text-muted); min-width: 18px; }
        .bi-sel-name { flex: 1; font-size: var(--text-xs, 0.75rem); font-weight: 600; color: var(--text-color); }
        .bi-sel-actions { display: flex; gap: 3px; }
        .bi-sel-actions button { border: 1px solid var(--border-color, #e5e7eb); background: var(--bg-card, #fff); color: var(--text-muted); width: 24px; height: 24px; border-radius: var(--radius-sm, 6px); cursor: pointer; font-size: var(--text-2xs, 0.6875rem); }
        .bi-sel-actions button:hover { color: var(--text-color); }
        .bi-sel-actions .bi-remove { color: #ef4444; border-color: rgba(239,68,68,0.3); }
        .bi-add-custom { display: flex; gap: 6px; }
        .bi-add-custom .bi-add-btn { padding: 0 12px; height: 34px; flex-shrink: 0; }
        .bi-import-row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .bi-file-input {
            flex: 1;
            min-width: 240px;
            padding: 8px 12px;
            font-size: var(--text-sm, 0.8125rem);
            border-radius: var(--radius-md, 8px);
            border: 1px solid var(--border-color, #e5e7eb);
            background: var(--bg-hover, #f9fafb);
            color: var(--text-color, #111827);
            box-sizing: border-box;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('js/buku-induk-nilai.js') }}?v={{ file_exists(public_path('js/buku-induk-nilai.js')) ? filemtime(public_path('js/buku-induk-nilai.js')) : time() }}"></script>
@endpush
