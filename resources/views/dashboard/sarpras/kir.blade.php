@extends('layouts.dashboard')

@section('title', 'Kartu Inventaris Ruangan (KIR) — Sarpras SAE')
@section('dash_title', 'Kartu Inventaris Ruangan')

@section('content')
    <div class="dash-content-inner">
        {{-- 1. Header Banner & Action Button --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg, 12px); background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); flex-shrink: 0;">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                        Kartu Inventaris Ruangan (KIR)
                    </h2>
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        Pemantauan penempatan aset per ruangan, penanggung jawab, serta cetak lembar KIR resmi siap tempel.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <a href="{{ route('dashboard.sarpras.aset.index') }}" class="btn btn-outline"
                    style="height: 38px; padding: 0 14px; display: inline-flex; align-items: center; gap: 6px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem); text-decoration: none;">
                    <i class="fas fa-boxes-stacked text-primary"></i> Kelola Aset &amp; Inventaris
                </a>
                <a href="{{ route('dashboard.sarpras.ruang.index') }}" class="btn btn-primary"
                    style="height: 38px; padding: 0 14px; display: inline-flex; align-items: center; gap: 6px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem); text-decoration: none;">
                    <i class="fas fa-door-open"></i> Master Ruang
                </a>
            </div>
        </div>

        {{-- 2. Stat Grid (4 Kartu Ringkasan) --}}
        <div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
                    <i class="fas fa-building"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statTotalRuang, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Total Ruangan</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);">
                    <i class="fas fa-file-circle-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statRuangTerisiKIR, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Ruang Terisi Aset (KIR)</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);">
                    <i class="fas fa-boxes-stacked"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statTotalAsetDitempatkan, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Total Unit Ditempatkan</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);">
                    <i class="fas fa-user-slash"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statRuangTanpaPJ, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Ruang Belum Ada PJ</div>
                </div>
            </div>
        </div>

        {{-- 3. Toolbar Pencarian & Filter Gedung --}}
        <div class="card" style="padding: 16px 20px; margin-bottom: 20px; border-radius: var(--radius-xl, 16px); border: 1px solid var(--border-color); background: var(--card-bg);">
            <form method="GET" action="{{ route('dashboard.sarpras.kir.index') }}" id="filterKirForm" style="margin: 0;">
                <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                        <select name="gedung" id="filterGedungKIR" class="toolbar-filter-select" style="height: 38px;">
                            <option value="">Semua Gedung</option>
                            @foreach ($gedungList as $g)
                                <option value="{{ $g }}" {{ $gedungFilter === $g ? 'selected' : '' }}>{{ $g }}</option>
                            @endforeach
                        </select>

                        @if ($search || $gedungFilter)
                            <a href="{{ route('dashboard.sarpras.kir.index') }}" class="btn btn-outline btn-reset-filter"
                                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                title="Reset filter"><i class="fas fa-undo"></i></a>
                        @endif
                    </div>

                    <div class="live-search-wrap" style="height: 38px;">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="q" id="searchKIR" value="{{ $search }}"
                            placeholder="Cari ruang, kode, gedung, atau PJ..." autocomplete="off">
                        <button type="button" class="clear-search {{ $search ? 'visible' : '' }}"
                            onclick="document.getElementById('searchKIR').value=''; document.getElementById('filterKirForm').submit();"
                            title="Hapus pencarian">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- 4. Grid Kartu Inventaris Ruangan (KIR) --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; margin-bottom: 24px;">
            @forelse ($list as $ruang)
                @php
                    $rekap = $asetRekap[$ruang->id] ?? ['jenis' => 0, 'unit' => 0, 'baik' => 0, 'rr' => 0, 'rb' => 0];
                    $hasAset = $rekap['unit'] > 0;
                @endphp
                <div class="card" style="padding: 18px 20px; border-radius: var(--radius-xl, 16px); border: 1px solid var(--border-color); background: var(--card-bg); display: flex; flex-direction: column; justify-content: space-between; transition: transform 0.2s ease, box-shadow 0.2s ease;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 8px;">
                            <div>
                                <span class="badge" style="font-size: var(--text-2xs, 0.6875rem); padding: 2px 7px; background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); font-family: monospace; font-weight: 700;">
                                    {{ $ruang->kode_ruang }}
                                </span>
                                <h3 style="margin: 6px 0 0 0; font-size: var(--text-md, 1rem); font-weight: 800; color: var(--text-color);">
                                    {{ $ruang->nama_ruang }}
                                </h3>
                            </div>
                            <span class="badge {{ $ruang->kondisi === 'baik' ? 'badge-success' : ($ruang->kondisi === 'rusak_ringan' ? 'badge-warning' : 'badge-danger') }}"
                                style="font-size: var(--text-2xs, 0.6875rem); padding: 3px 8px;">
                                {{ ucfirst(str_replace('_', ' ', $ruang->kondisi)) }}
                            </span>
                        </div>

                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
                            <span><i class="fas fa-landmark me-1"></i> {{ $ruang->gedung ?: 'Gedung —' }} (Lt. {{ $ruang->lantai ?: '1' }})</span>
                            @if ($ruang->panjang && $ruang->lebar)
                                <span><i class="fas fa-ruler-combined me-1"></i> {{ (float) $ruang->panjang }}m × {{ (float) $ruang->lebar }}m ({{ (float) $ruang->luas }} m²)</span>
                            @elseif ($ruang->luas)
                                <span><i class="fas fa-chart-area me-1"></i> {{ (float) $ruang->luas }} m²</span>
                            @endif
                        </div>

                        <div style="padding: 8px 10px; background: var(--bg-hover); border-radius: var(--radius-md, 8px); margin-bottom: 14px; font-size: var(--text-xs, 0.75rem);">
                            <div style="color: var(--text-muted); margin-bottom: 2px;">Penanggung Jawab:</div>
                            <div style="font-weight: 700; color: var(--text-color);">
                                <i class="fas fa-user-shield me-1 text-primary"></i> {{ $ruang->pj_nama ?: 'Belum Ditetapkan' }}
                            </div>
                            @if ($ruang->pj_nip)
                                <div style="color: var(--text-muted); font-size: var(--text-2xs, 0.6875rem);">NIP: {{ $ruang->pj_nip }}</div>
                            @endif
                        </div>

                        <div style="margin-bottom: 16px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: var(--text-xs, 0.75rem); margin-bottom: 6px;">
                                <span style="color: var(--text-muted);">Aset Ruangan:</span>
                                <strong>{{ $rekap['unit'] }} Unit ({{ $rekap['jenis'] }} Jenis)</strong>
                            </div>
                            <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                <span class="badge badge-success" style="font-size: var(--text-2xs, 0.6875rem); padding: 2px 6px;">
                                    {{ $rekap['baik'] }} Baik
                                </span>
                                <span class="badge badge-warning" style="font-size: var(--text-2xs, 0.6875rem); padding: 2px 6px;">
                                    {{ $rekap['rr'] }} Rusak Ringan
                                </span>
                                <span class="badge badge-danger" style="font-size: var(--text-2xs, 0.6875rem); padding: 2px 6px;">
                                    {{ $rekap['rb'] }} Rusak Berat
                                </span>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 12px;">
                        <button type="button" class="btn btn-outline btn-detail-kir" data-id="{{ $ruang->id }}"
                            style="flex: 1; height: 36px; padding: 0 10px; font-size: var(--text-xs, 0.75rem); display: inline-flex; align-items: center; justify-content: center; gap: 5px;">
                            <i class="fas fa-list"></i> Rincian Aset
                        </button>
                        <a href="{{ route('dashboard.sarpras.kir.cetak', $ruang->id) }}" target="_blank"
                            class="btn btn-primary"
                            style="height: 36px; padding: 0 14px; font-size: var(--text-xs, 0.75rem); display: inline-flex; align-items: center; justify-content: center; gap: 5px; text-decoration: none;"
                            title="Cetak Lembar KIR Ruangan">
                            <i class="fas fa-print"></i> Cetak KIR
                        </a>
                    </div>
                </div>
            @empty
                <div class="card" style="grid-column: 1 / -1; padding: 40px; text-align: center; color: var(--text-muted); border-radius: var(--radius-xl, 16px);">
                    <i class="fas fa-folder-open mb-2" style="font-size: var(--text-4xl, 2.25rem); opacity: 0.5;"></i>
                    <div style="font-size: var(--text-base, 0.875rem); font-weight: 600;">Belum ada data ruangan yang cocok.</div>
                </div>
            @endforelse
        </div>

        {{-- 5. Pagination --}}
        @if ($list->hasPages())
            <div class="datatable-footer">
                @include('partials.datatable-pagination', ['paginator' => $list])
            </div>
        @endif

        {{-- Modal Rincian Aset Ruangan --}}
        <div id="modalDetailKir" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 780px; max-height: 90vh; overflow-y: auto; padding: 24px; border-radius: var(--radius-lg, 12px);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <div>
                        <h3 id="kirModalTitle" style="margin: 0; font-size: var(--text-xl, 1.25rem); font-weight: 700; color: var(--text-color);">
                            Rincian Aset Ruang
                        </h3>
                        <div id="kirModalSub" style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;"></div>
                    </div>
                    <button type="button" id="btnCloseModalKir"
                        style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;"
                        aria-label="Tutup Modal">&times;</button>
                </div>

                <div class="table-responsive-stack" style="overflow-x: auto; margin-bottom: 20px;">
                    <table class="table-minimal-compact" style="width: 100%;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th style="width: 40px; text-align: center;">No</th>
                                <th>Kode &amp; Nama Barang</th>
                                <th>Kategori / Merk</th>
                                <th style="width: 90px; text-align: center;">Jumlah</th>
                                <th style="width: 100px; text-align: center;">Kondisi</th>
                                <th style="width: 100px; text-align: center;">Status</th>
                            </tr>
                        </thead>
                        <tbody id="kirModalBody">
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 20px; color: var(--text-muted);">
                                    Memuat data aset...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-secondary" id="btnCancelModalKir">Tutup</button>
                    <a id="btnCetakKirModal" href="#" target="_blank" class="btn btn-primary" style="text-decoration: none;">
                        <i class="fas fa-print me-1"></i> Cetak Lembar KIR
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('modalDetailKir');
            const btnClose = document.getElementById('btnCloseModalKir');
            const btnCancel = document.getElementById('btnCancelModalKir');
            const modalTitle = document.getElementById('kirModalTitle');
            const modalSub = document.getElementById('kirModalSub');
            const modalBody = document.getElementById('kirModalBody');
            const btnCetak = document.getElementById('btnCetakKirModal');

            function closeModal() {
                if (modal) modal.style.display = 'none';
            }

            if (btnClose) btnClose.addEventListener('click', closeModal);
            if (btnCancel) btnCancel.addEventListener('click', closeModal);
            if (modal) {
                modal.addEventListener('click', function (e) {
                    if (e.target === modal) closeModal();
                });
            }

            document.addEventListener('click', function (e) {
                const btn = e.target.closest('.btn-detail-kir');
                if (!btn) return;
                const id = btn.dataset.id;
                modal.style.display = 'flex';
                modalBody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px; color: var(--text-muted);"><i class="fas fa-spinner fa-spin me-1"></i> Memuat data...</td></tr>';
                btnCetak.href = `/dashboard/sarpras/kir/${id}/cetak`;

                fetch(`/dashboard/sarpras/kir/${id}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        const r = res.ruang;
                        modalTitle.textContent = `${r.nama_ruang} (${r.kode_ruang})`;
                        modalSub.textContent = `${r.gedung || 'Gedung'} • PJ: ${r.pj_nama || 'Belum ada'} • ${res.items.length} Barang Terdata`;

                        if (res.items.length === 0) {
                            modalBody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 24px; color: var(--text-muted);">Belum ada aset/barang yang ditempatkan di ruangan ini.</td></tr>';
                            return;
                        }

                        let html = '';
                        res.items.forEach((it, idx) => {
                            const badgeKondisi = it.kondisi === 'baik'
                                ? '<span class="badge badge-success">Baik</span>'
                                : (it.kondisi === 'rusak_ringan' ? '<span class="badge badge-warning">Rusak Ringan</span>' : '<span class="badge badge-danger">Rusak Berat</span>');
                            html += `<tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="text-align: center; color: var(--text-muted); font-size: var(--text-xs, 0.75rem);">${idx + 1}</td>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-sm, 0.8125rem);">${it.nama_barang}</div>
                                    <div style="font-size: var(--text-2xs, 0.6875rem); color: var(--primary); font-family: monospace;">${it.kode_aset}</div>
                                </td>
                                <td style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                    ${it.kategori || '—'}<br><small>${it.merk_tipe || ''}</small>
                                </td>
                                <td style="text-align: center; font-weight: 700; font-size: var(--text-sm, 0.8125rem);">${it.jumlah} ${it.satuan}</td>
                                <td style="text-align: center;">${badgeKondisi}</td>
                                <td style="text-align: center;"><span class="badge badge-outline" style="font-size: var(--text-2xs, 0.6875rem);">${it.status_ketersediaan}</span></td>
                            </tr>`;
                        });
                        modalBody.innerHTML = html;
                    } else {
                        modalBody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--danger, #ef4444); padding: 20px;">${res.message || 'Gagal memuat data.'}</td></tr>`;
                    }
                })
                .catch(() => {
                    modalBody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--danger, #ef4444); padding: 20px;">Terjadi kesalahan jaringan.</td></tr>';
                });
            });
        });
    </script>
@endpush
