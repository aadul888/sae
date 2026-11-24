@extends('layouts.dashboard')

@section('title', 'Peta Sekolah — SAE')
@section('dash_title', 'Peta Sekolah')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/sarpras-peta.css') }}?v={{ @filemtime(public_path('css/sarpras-peta.css')) ?: '1' }}">
@endpush

@section('content')
    <div class="dash-content-inner" id="saePetaPage"
        data-can-update="{{ $canUpdate ? '1' : '0' }}"
        data-save-layout="{{ route('dashboard.sarpras.peta.layout') }}"
        data-upload-latar="{{ route('dashboard.sarpras.peta.latar') }}"
        data-latar-opacity-url="{{ route('dashboard.sarpras.peta.latar-opacity') }}"
        data-ruang-tersedia="{{ route('dashboard.sarpras.peta.ruang-tersedia') }}"
        data-attach-ruang="{{ route('dashboard.sarpras.peta.area.attach') }}"
        data-status-url="{{ url('dashboard/sarpras/peta/area') }}"
        data-kir-url="{{ route('dashboard.sarpras.kir.index') }}"
        data-import-url="{{ route('dashboard.sarpras.peta.import-vision') }}"
        data-import-commit-url="{{ route('dashboard.sarpras.peta.import-commit') }}"
        data-statuses="{{ json_encode($statuses) }}"
        data-csrf="{{ csrf_token() }}">

        {{-- 1. Header Banner --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(59,130,246,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl); flex-shrink: 0;">
                    <i class="fas fa-map-location-dot"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl); font-weight: 800; color: var(--text-color); margin: 0;">
                        Peta &amp; Denah Sekolah
                    </h2>
                    <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: 2px;">
                        {{ $peta->nama ?? 'Denah tapak sekolah' }} — Interactive Site-Plan Builder (Konva).
                    </div>
                </div>
            </div>

            <div class="dash-banner-actions dash-banner-access">
                @if ($canUpdate && $peta)
                    <button type="button" class="btn btn-outline" id="saePetaToggleEdit"
                        title="Mode Edit Layout (geser/ubah ukuran/putar blok)" aria-label="Mode Edit Layout">
                        <i class="fas fa-pen-ruler"></i>
                    </button>
                    <button type="button" class="btn btn-outline" id="saePetaSave" style="display:none;"
                        title="Simpan tata letak" aria-label="Simpan tata letak" disabled>
                        <i class="fas fa-floppy-disk"></i>
                    </button>
                    <button type="button" class="btn btn-outline" id="saePetaOpenLatar"
                        title="Upload gambar referensi / citra latar" aria-label="Upload citra latar">
                        <i class="fas fa-image"></i>
                    </button>
                    <button type="button" class="btn btn-outline" id="saePetaOpenImport"
                        title="Import / Update dari Gambar Denah (AI Vision)" aria-label="Import dari gambar">
                        <i class="fas fa-wand-magic-sparkles"></i>
                    </button>
                @endif
                @if ($peta)
                    <button type="button" class="btn btn-outline" id="saePetaDownloadPng"
                        title="Download Gambar Denah (PNG resolusi tinggi)" aria-label="Download PNG">
                        <i class="fas fa-file-image"></i>
                    </button>
                    <button type="button" class="btn btn-outline" id="saePetaDownloadPdf"
                        title="Cetak PDF (dengan legenda &amp; arah mata angin)" aria-label="Cetak PDF">
                        <i class="fas fa-file-pdf"></i>
                    </button>
                    <a href="{{ route('dashboard.sarpras.peta.export') }}" class="btn btn-outline"
                        title="Export Excel rekapitulasi ruangan &amp; kondisi" aria-label="Export Excel">
                        <i class="fas fa-file-excel text-success"></i>
                    </a>
                @endif
            </div>
        </div>

        {{-- 2. Statistik (rincian 5 status) --}}
        <div class="dash-stat-grid" style="margin-bottom: 16px; gap: 14px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
                    <i class="fas fa-map"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stat['total'] }}</div>
                    <div class="dash-stat-label">Total Area</div>
                </div>
            </div>
            @foreach ($statuses as $key => $meta)
                <div class="dash-stat-card" data-stat-key="{{ $key }}">
                    <div class="dash-stat-icon" style="background: {{ $meta['warna'] }}22; color: {{ $meta['warna'] }};">
                        <i class="fas fa-square"></i>
                    </div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value">{{ $stat[$key] ?? 0 }}</div>
                        <div class="dash-stat-label">{{ $meta['label'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- 3. Bar aksi tampilan + legenda interaktif --}}
        @if ($peta)
            <div class="sae-peta-toolbar">
                <div class="sae-peta-toolbar-group">
                    <span class="sae-peta-mode-badge" id="saePetaModeBadge">Mode Lihat</span>
                </div>

                <div class="sae-peta-toolbar-group">
                    @if ($peta->latar_url)
                        <label class="sae-peta-control" title="Tampilkan/sembunyikan citra latar">
                            <input type="checkbox" id="saePetaLatarToggle" checked>
                            <span>Latar</span>
                        </label>
                        <label class="sae-peta-control" title="Transparansi citra latar">
                            <span>Opacity</span>
                            <input type="range" id="saePetaLatarOpacity" min="0" max="100"
                                value="{{ $peta->latar_opacity ?? 70 }}">
                            <output id="saePetaLatarOpacityVal">{{ $peta->latar_opacity ?? 70 }}%</output>
                        </label>
                    @endif
                    <button type="button" class="sae-peta-ctrl-btn" id="saePetaZoomOut" title="Perkecil"
                        aria-label="Perkecil"><i class="fas fa-magnifying-glass-minus"></i></button>
                    <button type="button" class="sae-peta-ctrl-btn" id="saePetaZoomReset" title="Reset tampilan"
                        aria-label="Reset tampilan"><i class="fas fa-expand"></i></button>
                    <button type="button" class="sae-peta-ctrl-btn" id="saePetaZoomIn" title="Perbesar"
                        aria-label="Perbesar"><i class="fas fa-magnifying-glass-plus"></i></button>
                </div>

                <div class="sae-peta-legend" id="saePetaLegend">
                    @foreach ($statuses as $key => $meta)
                        <button type="button" class="sae-peta-legend-item" data-legend-key="{{ $key }}"
                            title="Klik untuk sorot blok {{ $meta['label'] }}">
                            <span class="sae-peta-swatch" style="background: {{ $meta['warna'] }};"></span>
                            {{ $meta['label'] }}
                            <span class="sae-peta-legend-count">{{ $stat[$key] ?? 0 }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- 4. Kanvas Denah (Konva) --}}
        <div class="card datatable-card-wrapper">
            @if (!$peta)
                <div class="sae-peta-empty">
                    <i class="fas fa-map-location-dot"></i>
                    <div>
                        <h3 style="margin: 0 0 6px; color: var(--text-main);">Denah belum dibuat</h3>
                        <p style="margin: 0;">Belum ada data denah tapak. Jalankan seeder peta atau buat denah baru.</p>
                    </div>
                </div>
            @else
                <div class="sae-peta-builder" id="saePetaBuilder">
                    <div class="sae-peta-stage-wrap">
                        <div id="saePetaStage"></div>

                        {{-- Toolbar gambar (tampil di edit mode) --}}
                        @if ($canUpdate)
                            <div class="sae-peta-tools" id="saePetaTools">
                                <button type="button" class="sae-peta-tool" data-tool="add-ruang"
                                    title="Tambah blok ruangan dari master" aria-label="Tambah ruangan">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <button type="button" class="sae-peta-tool" data-tool="line"
                                    title="Alat gambar jalan" aria-label="Gambar jalan">
                                    <i class="fas fa-road"></i>
                                </button>
                                <button type="button" class="sae-peta-tool" data-tool="batas"
                                    title="Gambar batas tanah" aria-label="Gambar batas tanah">
                                    <i class="fas fa-draw-polygon"></i>
                                </button>
                                <button type="button" class="sae-peta-tool" data-tool="delete"
                                    title="Hapus blok terpilih" aria-label="Hapus blok">
                                    <i class="fas fa-trash-can"></i>
                                </button>
                            </div>
                        @endif
                    </div>

                    {{-- Panel detail: latar solid, z-index tinggi --}}
                    <div class="sae-peta-detail" id="saePetaDetail" role="dialog" aria-modal="false"
                        aria-labelledby="saePetaDetailTitle">
                        <button type="button" class="sae-peta-detail-close" id="saePetaDetailClose"
                            title="Tutup" aria-label="Tutup detail">
                            <i class="fas fa-times"></i>
                        </button>
                        <h4 id="saePetaDetailTitle">Detail Area</h4>
                        <div id="saePetaDetailBody"></div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if ($canUpdate && $peta)
        {{-- Modal: atur citra latar / tracing --}}
        <div id="saePetaLatarModal" class="modal-backdrop" style="display: none;">
            <div class="sae-modal-card" role="dialog" aria-modal="true" aria-labelledby="saePetaLatarTitle">
                <div class="sae-modal-head">
                    <h3 id="saePetaLatarTitle">Upload Gambar Referensi (Tracing Layer)</h3>
                    <button type="button" class="sae-peta-detail-close" data-close-modal="1" title="Tutup"
                        aria-label="Tutup"><i class="fas fa-times"></i></button>
                </div>
                <form id="saePetaLatarForm" enctype="multipart/form-data">
                    <div class="sae-field">
                        <label for="saePetaLatarFile">Berkas gambar denah (JPG/PNG/WebP, maks 20 MB)</label>
                        <input type="file" id="saePetaLatarFile" name="latar" accept="image/jpeg,image/png,image/webp" required>
                    </div>
                    <div class="sae-field">
                        <label for="saePetaLatarAtribusi">Atribusi / sumber citra (opsional)</label>
                        <input type="text" id="saePetaLatarAtribusi" name="atribusi" maxlength="255"
                            placeholder="mis. Peta Pemetaan Sarpras 2026/2027 rev1">
                    </div>
                    <div class="sae-field">
                        <label for="saePetaLatarOpacityInput">Transparansi: <output id="saePetaLatarOpacityOut">70</output>%</label>
                        <input type="range" id="saePetaLatarOpacityInput" name="opacity" min="0" max="100" value="{{ $peta->latar_opacity ?? 70 }}">
                    </div>
                    <div class="sae-modal-actions">
                        <button type="button" class="btn btn-outline" data-close-modal="1">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Unggah
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal: import dari gambar (AI Vision) --}}
        <div id="saePetaImportModal" class="modal-backdrop" style="display: none;">
            <div class="sae-modal-card sae-modal-card--wide" role="dialog" aria-modal="true"
                aria-labelledby="saePetaImportTitle">
                <div class="sae-modal-head">
                    <h3 id="saePetaImportTitle">Import / Update dari Gambar Denah (AI Vision)</h3>
                    <button type="button" class="sae-peta-detail-close" data-close-modal="1" title="Tutup"
                        aria-label="Tutup"><i class="fas fa-times"></i></button>
                </div>
                <div class="sae-field">
                    <label for="saePetaImportFile">Gambar denah revisi baru (JPG/PNG/WebP)</label>
                    <input type="file" id="saePetaImportFile" accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="sae-modal-actions" style="justify-content: flex-start;">
                    <button type="button" class="btn btn-outline" id="saePetaImportScan">
                        <i class="fas fa-magnifying-glass"></i> Pindai Gambar
                    </button>
                    <button type="button" class="btn btn-primary" id="saePetaImportApply" disabled>
                        <i class="fas fa-check"></i> Terapkan
                    </button>
                </div>
                <div id="saePetaImportStatus" class="sae-text-muted" style="font-size: var(--text-sm);"></div>
                <div class="sae-peta-import-preview" id="saePetaImportPreview"></div>
            </div>
        </div>

        {{-- Modal: pilih ruang master belum ditempatkan --}}
        <div id="saePetaRuangPicker" class="modal-backdrop" style="display: none;">
            <div class="sae-modal-card" role="dialog" aria-modal="true" aria-labelledby="saePetaRuangTitle">
                <div class="sae-modal-head">
                    <h3 id="saePetaRuangTitle">Tambah Ruangan ke Denah</h3>
                    <button type="button" class="sae-peta-detail-close" data-close-modal="1" title="Tutup"
                        aria-label="Tutup"><i class="fas fa-times"></i></button>
                </div>
                <div class="sae-peta-ruang-list" id="saePetaRuangList"></div>
            </div>
        </div>
    @endif

    {{-- Data island untuk builder Konva --}}
    @if ($peta && $mapData)
        <script type="application/json" id="saePetaData">{!! json_encode(array_merge($mapData, [
            'nama' => $peta->nama,
            'skala' => $peta->skala,
            'periode' => \App\Support\SemesterHelper::getActiveSemesterLabel(),
        ]), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    @endif
@endsection

@push('scripts')
    {{-- Konva + builder dimuat sebagai aset biasa (bukan Vite) agar ikut ter-deploy
         lewat git pull / fallback ZIP tanpa langkah 'npm run build' di server. --}}
    <script src="{{ asset('vendor/konva/konva.min.js') }}?v={{ @filemtime(public_path('vendor/konva/konva.min.js')) ?: '1' }}"></script>
    <script src="{{ asset('js/sarpras-peta.js') }}?v={{ @filemtime(public_path('js/sarpras-peta.js')) ?: '1' }}"></script>
@endpush
