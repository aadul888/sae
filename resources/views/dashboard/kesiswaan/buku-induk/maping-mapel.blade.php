@extends('layouts.dashboard')

@section('title', 'Maping Mata Pelajaran — SAE')
@section('dash_title', 'Buku Induk Siswa')

@section('content')
    {{-- 1. Header Banner --}}
    <div class="dash-banner"
        style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div
                style="width: 44px; height: 44px; border-radius: var(--radius-lg, 12px); background: rgba(13,148,136,0.12); color: #0d9488; display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                <i class="fas fa-diagram-project"></i>
            </div>
            <div>
                <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                    Maping Mata Pelajaran
                </h2>
                <p style="margin: 2px 0 0 0; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                    Tetapkan mata pelajaran <strong>per semester</strong> — semester tanpa mapel boleh dikosongkan.
                </p>
            </div>
        </div>
    </div>

    {{-- 2. Tab Navigasi --}}
    @include('dashboard.kesiswaan.buku-induk.partials.tabs', ['activeTab' => 'mapel'])

    {{-- 3. Import Pemetaan per Semester --}}
    @if ($can['create'])
        <div class="card" style="padding: 18px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 12px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div
                        style="width: 38px; height: 38px; border-radius: var(--radius-md, 8px); background: rgba(16,185,129,0.12); color: var(--success, #10b981); display: flex; align-items: center; justify-content: center; font-size: var(--text-lg, 1.125rem); flex-shrink: 0;">
                        <i class="fas fa-file-excel"></i>
                    </div>
                    <div>
                        <div style="font-weight: 800; color: var(--text-color); font-size: var(--text-md, 1rem);">Import Mapel per Semester</div>
                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">Format: Semester, Kelompok (umum/kejuruan), Nama Mata Pelajaran</div>
                    </div>
                </div>
                <a href="{{ route('dashboard.kesiswaan.buku-induk.mapel.template') }}" class="btn btn-outline"
                    style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; font-size: var(--text-sm, 0.8125rem); text-decoration: none; border-color: var(--success, #10b981); color: var(--success, #10b981);">
                    <i class="fas fa-download"></i> Unduh Template Excel
                </a>
            </div>

            <div
                style="margin-bottom: 14px; padding: 10px 12px; border-radius: var(--radius-md, 8px); background: rgba(99,102,241,0.08); border: 1px solid rgba(99,102,241,0.2); font-size: var(--text-xs, 0.75rem); line-height: 1.5; color: var(--text-color);">
                <i class="fas fa-info-circle" style="color: var(--primary, #3b82f6);"></i>
                Satu berkas untuk <strong>satu semester</strong>. Daftar mapel semester itu akan <strong>diganti</strong> dengan isi berkas — cocok untuk semester yang hanya mengajarkan sebagian mapel.
            </div>

            <form id="formMapelImport" action="{{ route('dashboard.kesiswaan.buku-induk.mapel.import') }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div class="bi-import-row">
                    <select name="semester" class="bi-input bi-file-sm" required>
                        @foreach (range(1, 6) as $sem)
                            <option value="{{ $sem }}">Semester {{ $sem }}</option>
                        @endforeach
                    </select>
                    <input type="file" name="file_excel" accept=".xlsx,.xls,.csv,.txt" class="bi-file-input" required>
                    <button type="submit" id="btnImportMapel" class="btn btn-primary"
                        style="padding: 9px 22px; font-size: var(--text-sm, 0.8125rem); display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;">
                        <i class="fas fa-cloud-arrow-up"></i> Import Mapel
                    </button>
                </div>
                <div style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted); margin-top: 6px;">Mendukung .xlsx, .xls, .csv (maks 20 MB).</div>
            </form>
        </div>
    @endif

    {{-- 4. Pemetaan per Semester --}}
    <div class="card" style="padding: 18px;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px;">
            <div
                style="width: 38px; height: 38px; border-radius: var(--radius-md, 8px); background: rgba(13,148,136,0.12); color: #0d9488; display: flex; align-items: center; justify-content: center; font-size: var(--text-lg, 1.125rem); flex-shrink: 0;">
                <i class="fas fa-list-check"></i>
            </div>
            <div>
                <div style="font-weight: 800; color: var(--text-color); font-size: var(--text-md, 1rem);">Pemetaan Mata Pelajaran per Semester</div>
                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                    Ambil dari referensi <strong>pembelajaran</strong> Dapodik atau tambah manual. Gunakan panah untuk mengatur urutan.
                </div>
            </div>
        </div>

        <form id="formPengaturan" action="{{ route('dashboard.kesiswaan.buku-induk.mapel.save') }}" method="POST">
            @csrf

            <div class="bi-sem-tabs">
                @foreach (range(1, 6) as $sem)
                    <button type="button" class="bi-sem-tab {{ $sem === 1 ? 'active' : '' }}" data-sem="{{ $sem }}">
                        Semester {{ $sem }}
                        <span class="bi-sem-badge">{{ count($perSemester[$sem]['umum']) + count($perSemester[$sem]['kejuruan']) }}</span>
                    </button>
                @endforeach
            </div>

            @foreach (range(1, 6) as $sem)
                <div class="bi-sem-panel {{ $sem === 1 ? 'active' : '' }}" data-sem="{{ $sem }}">
                    @if (!($terpetakan[$sem] ?? false))
                        <div class="bi-sem-note" style="margin-bottom: 12px;">
                            <i class="fas fa-circle-info"></i> Semester {{ $sem }} belum dipetakan — masih memakai daftar mapel global. Simpan untuk menandai semester ini.
                        </div>
                    @endif

                    <div class="bi-mapel-grid">
                        @foreach (['umum' => ['label' => 'Mapel Umum', 'color' => '#10b981'], 'kejuruan' => ['label' => 'Mapel Kejuruan', 'color' => '#f59e0b']] as $key => $meta)
                            <div class="bi-mapel-col" data-kelompok="{{ $key }}" data-semester="{{ $sem }}">
                                <div class="bi-col-head">
                                    <span><i class="fas fa-layer-group" style="color: {{ $meta['color'] }};"></i> {{ $meta['label'] }} — Sem {{ $sem }}</span>
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

                                <textarea name="mapel[{{ $sem }}][{{ $key }}]" class="bi-hidden-mapel" hidden>{{ implode("\n", $perSemester[$sem][$key]) }}</textarea>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            @if ($can['update'])
                <button type="submit" class="btn btn-primary"
                    style="margin-top: 16px; padding: 8px 20px; font-size: var(--text-sm, 0.8125rem); display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-save"></i> Simpan Pemetaan Mapel
                </button>
            @else
                <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">Anda tidak memiliki hak untuk mengubah pemetaan mapel.</div>
            @endif
        </form>
    </div>

    {{-- Data referensi mapel dari tabel pembelajaran (untuk JS) --}}
    <script type="application/json" id="biRefData">@json($referensi)</script>
@endsection

@include('dashboard.kesiswaan.buku-induk.partials.buku-induk-style')

@push('scripts')
    <script src="{{ asset('js/buku-induk-nilai.js') }}?v={{ file_exists(public_path('js/buku-induk-nilai.js')) ? filemtime(public_path('js/buku-induk-nilai.js')) : time() }}"></script>
@endpush
