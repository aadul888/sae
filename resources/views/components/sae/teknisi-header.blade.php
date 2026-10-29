@props([
    'bannerTitle' => 'Modul Teknisi',
    'bannerDesc' => '',
    'bannerIcon' => 'fas fa-tools',
    'bannerColor' => 'var(--primary, #3b82f6)',
    'createRoute' => null,
    'cetakRoute' => null,
    'importModalId' => null,
    'filterRoute' => '#',
    'modalId' => 'modalCreate',
    'statCards' => [],
    'perPage' => 15,
    'search' => '',
    'showSearch' => true,
    'canCreate' => false,
    'canImport' => false,
])

@php
    $perPage = $perPage ?: 15;
@endphp

<div class="dash-content-inner">
    <!-- 1. Header Banner & Actions -->
    <div class="dash-banner"
        style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div
                style="width: 48px; height: 48px; border-radius: var(--radius-lg, 12px); background: rgba(59,130,246,0.12); color: {{ $bannerColor }}; display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); flex-shrink: 0;">
                <i class="{{ $bannerIcon }}"></i>
            </div>
            <div>
                <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                    {{ $bannerTitle }}
                </h2>
                <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                    {{ $bannerDesc }}
                </div>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            @if ($cetakRoute)
                <a href="{{ $cetakRoute }}" target="_blank" class="btn btn-outline"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                    title="Cetak Dokumen" aria-label="Cetak Dokumen">
                    <i class="fas fa-print"></i>
                </a>
            @endif
            @if ($createRoute && $canCreate)
                <button type="button" class="btn btn-primary" data-modal-open="{{ $modalId }}"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                    title="Tambah Data Baru" aria-label="Tambah Data Baru">
                    <i class="fas fa-plus"></i>
                </button>
            @endif
            @if ($importModalId && $canImport)
                <button type="button" class="btn btn-outline" data-modal-open="{{ $importModalId }}"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                    title="Import Excel" aria-label="Import Excel">
                    <i class="fas fa-file-excel text-success"></i>
                </button>
            @endif
        </div>
    </div>

    <!-- 2. Stat Grid -->
    @if (!empty($statCards))
        <div class="dash-stat-grid" style="margin-bottom: 20px;">
            @foreach ($statCards as $card)
                <div class="dash-stat-card">
                    <div class="dash-stat-icon"
                        style="background: {{ $card['bg'] ?? 'rgba(59,130,246,0.12)' }}; color: {{ $card['color'] ?? 'var(--primary, #3b82f6)' }};">
                        <i class="{{ $card['icon'] ?? 'fas fa-chart-simple' }}"></i>
                    </div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value">{{ $card['value'] ?? 0 }}</div>
                        <div class="dash-stat-label">{{ $card['label'] ?? '' }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- 3. Datatable -->
    <div class="card datatable-card-wrapper" id="tableDataContainer">
        <div class="datatable-toolbar">
            <form method="GET" action="{{ $filterRoute }}" id="filterForm" style="margin: 0;">
                <div
                    style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                        <div class="toolbar-entries">
                            <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                            <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                                @foreach ([10, 15, 25, 50, 100] as $n)
                                    <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>
                                        {{ $n }}</option>
                                @endforeach
                            </select>
                            <span>entri</span>
                        </div>

                        {{ $filter ?? '' }}

                        @if (($search ?? '') !== '')
                            <a href="{{ $filterRoute }}" class="btn btn-outline btn-reset-filter"
                                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                        @endif
                    </div>

                    @if ($showSearch)
                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="q" id="liveSearch" value="{{ $search }}"
                                placeholder="Cari data..." autocomplete="off">
                            <button type="button" id="clearSearch"
                                class="clear-search {{ !empty($search) ? 'visible' : '' }}" title="Hapus pencarian">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    @endif
                </div>
            </form>
        </div>

        <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                {{ $slot }}
            </table>
        </div>

        @isset($pagination)
            <div class="datatable-footer">
                {{ $pagination }}
            </div>
        @endisset
    </div>
</div>
