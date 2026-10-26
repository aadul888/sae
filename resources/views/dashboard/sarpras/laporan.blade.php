@extends('layouts.dashboard')

@section('title', 'Laporan & Buku Induk Inventaris — Sarpras SAE')
@section('dash_title', 'Laporan & Buku Induk Inventaris')

@section('content')
    <div class="dash-content-inner">
        {{-- 1. Header Banner & Action Button --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg, 12px); background: rgba(16,185,129,0.12); color: var(--success, #10b981); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); flex-shrink: 0;">
                    <i class="fas fa-book-bookmark"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                        Laporan &amp; Buku Induk Inventaris
                    </h2>
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        Rekapitulasi total aset, akumulasi nilai perolehan, komposisi sumber dana, dan cetak Buku Induk resmi.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <a href="{{ route('dashboard.sarpras.laporan.cetak', request()->all()) }}" target="_blank"
                    class="btn btn-primary"
                    style="height: 38px; padding: 0 16px; display: inline-flex; align-items: center; gap: 6px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem); text-decoration: none;"
                    title="Cetak Buku Induk Inventaris (PDF / Print)">
                    <i class="fas fa-print"></i> Cetak Buku Induk
                </a>
            </div>
        </div>

        {{-- 2. Stat Grid (4 Kartu Ringkasan) --}}
        <div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
                    <i class="fas fa-boxes-stacked"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($totalAsetUnit, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Total Unit Fisik Aset</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-lg, 1.125rem); color: var(--success, #10b981);">
                        Rp {{ number_format($totalAsetNilai, 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Total Nilai Perolehan</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);">
                    <i class="fas fa-cubes"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($totalAsetJenis, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Jenis Barang Terdaftar</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);">
                    <i class="fas fa-building"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($totalRuang, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Ruangan Penampung</div>
                </div>
            </div>
        </div>

        {{-- 3. Panel Ringkasan Rekapitulasi (Kategori, Sumber Dana, Kondisi) --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; margin-bottom: 24px;">
            {{-- Kategori --}}
            <div class="card" style="padding: 16px 18px; border-radius: var(--radius-xl, 16px); border: 1px solid var(--border-color); background: var(--card-bg);">
                <div style="font-weight: 800; font-size: var(--text-sm, 0.8125rem); color: var(--text-color); margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-shapes text-primary"></i> Rekapitulasi per Kategori
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    @foreach ($rekapKategori as $rk)
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: var(--text-xs, 0.75rem); padding-bottom: 6px; border-bottom: 1px dashed var(--border-color);">
                            <span style="font-weight: 600; color: var(--text-color);">{{ $rk->kategori }}</span>
                            <span><strong>{{ $rk->unit }} Unit</strong> (Rp {{ number_format($rk->nilai, 0, ',', '.') }})</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Sumber Dana --}}
            <div class="card" style="padding: 16px 18px; border-radius: var(--radius-xl, 16px); border: 1px solid var(--border-color); background: var(--card-bg);">
                <div style="font-weight: 800; font-size: var(--text-sm, 0.8125rem); color: var(--text-color); margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-hand-holding-dollar text-success"></i> Rekapitulasi Sumber Dana
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    @foreach ($rekapSumberDana as $rs)
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: var(--text-xs, 0.75rem); padding-bottom: 6px; border-bottom: 1px dashed var(--border-color);">
                            <span style="font-weight: 600; color: var(--text-color);">{{ $rs->sumber_dana }}</span>
                            <span><strong>{{ $rs->unit }} Unit</strong> (Rp {{ number_format($rs->nilai, 0, ',', '.') }})</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Kondisi Fisik --}}
            <div class="card" style="padding: 16px 18px; border-radius: var(--radius-xl, 16px); border: 1px solid var(--border-color); background: var(--card-bg);">
                <div style="font-weight: 800; font-size: var(--text-sm, 0.8125rem); color: var(--text-color); margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-heart-pulse text-warning"></i> Kelayakan Kondisi Fisik
                </div>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: var(--text-xs, 0.75rem);">
                        <span class="badge badge-success" style="font-size: var(--text-xs, 0.75rem); padding: 4px 8px;">Kondisi Baik</span>
                        <strong>{{ number_format($rekapKondisi['baik'], 0, ',', '.') }} Unit</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: var(--text-xs, 0.75rem);">
                        <span class="badge badge-warning" style="font-size: var(--text-xs, 0.75rem); padding: 4px 8px;">Rusak Ringan</span>
                        <strong>{{ number_format($rekapKondisi['rusak_ringan'], 0, ',', '.') }} Unit</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: var(--text-xs, 0.75rem);">
                        <span class="badge badge-danger" style="font-size: var(--text-xs, 0.75rem); padding: 4px 8px;">Rusak Berat</span>
                        <strong>{{ number_format($rekapKondisi['rusak_berat'], 0, ',', '.') }} Unit</strong>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. Datatable Buku Induk Inventaris --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.sarpras.laporan.index') }}" id="filterFormLaporan" style="margin: 0;">
                    <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                        <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                            <div class="toolbar-entries">
                                <label for="perPageSelectLaporan" style="margin: 0;">Tampilkan</label>
                                <select id="perPageSelectLaporan" name="perPage" class="per-page-select" style="height: 38px;">
                                    @foreach ([10, 20, 50, 100] as $n)
                                        <option value="{{ $n }}" {{ request('perPage', 20) == $n ? 'selected' : '' }}>
                                            {{ $n }}
                                        </option>
                                    @endforeach
                                </select>
                                <span>entri</span>
                            </div>

                            <select name="kategori" class="toolbar-filter-select" style="height: 38px;">
                                <option value="">Semua Kategori</option>
                                @foreach ($kategoriOptions as $ko)
                                    <option value="{{ $ko }}" {{ $kategoriFilter === $ko ? 'selected' : '' }}>{{ $ko }}</option>
                                @endforeach
                            </select>

                            <select name="sumber_dana" class="toolbar-filter-select" style="height: 38px;">
                                <option value="">Semua Sumber Dana</option>
                                @foreach ($sumberOptions as $so)
                                    <option value="{{ $so }}" {{ $sumberFilter === $so ? 'selected' : '' }}>{{ $so }}</option>
                                @endforeach
                            </select>

                            <select name="kondisi" class="toolbar-filter-select" style="height: 38px;">
                                <option value="">Semua Kondisi</option>
                                <option value="baik" {{ $kondisiFilter === 'baik' ? 'selected' : '' }}>Baik</option>
                                <option value="rusak_ringan" {{ $kondisiFilter === 'rusak_ringan' ? 'selected' : '' }}>Rusak Ringan</option>
                                <option value="rusak_berat" {{ $kondisiFilter === 'rusak_berat' ? 'selected' : '' }}>Rusak Berat</option>
                            </select>

                            <select name="tahun" class="toolbar-filter-select" style="height: 38px;">
                                <option value="">Semua Tahun</option>
                                @foreach ($tahunOptions as $to)
                                    <option value="{{ $to }}" {{ $tahunFilter == $to ? 'selected' : '' }}>{{ $to }}</option>
                                @endforeach
                            </select>

                            @if ($kategoriFilter || $sumberFilter || $kondisiFilter || $tahunFilter)
                                <a href="{{ route('dashboard.sarpras.laporan.index') }}"
                                    class="btn btn-outline btn-reset-filter"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                    title="Reset filter"><i class="fas fa-undo"></i></a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255, 255, 255, 0.02);">
                            <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">No</th>
                            <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Kode &amp; Nama Barang</th>
                            <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Kategori &amp; Merk</th>
                            <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Penempatan &amp; Alokasi</th>
                            <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Thn / Sumber</th>
                            <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">Harga Satuan</th>
                            <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Jumlah</th>
                            <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Kondisi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $idx => $it)
                            <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                                <td data-label="No" style="padding: 14px 18px; font-size: var(--text-base, 0.875rem); color: var(--text-muted);">
                                    {{ $list->firstItem() + $idx }}
                                </td>
                                <td data-label="Nama Barang" style="padding: 14px 18px;">
                                    <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                                        {{ $it->nama_barang }}
                                    </div>
                                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--primary); font-family: monospace;">
                                        {{ $it->kode_aset }}
                                    </div>
                                    @if ($it->no_seri_pabrik)
                                        <div style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted);">SN: {{ $it->no_seri_pabrik }}</div>
                                    @endif
                                </td>
                                <td data-label="Kategori" style="padding: 14px 18px; font-size: var(--text-base, 0.875rem);">
                                    <span class="badge badge-info" style="font-size: var(--text-2xs, 0.6875rem);">{{ $it->kategori }}</span>
                                    @if ($it->merk_tipe)
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 3px;">{{ $it->merk_tipe }}</div>
                                    @endif
                                </td>
                                <td data-label="Penempatan" style="padding: 14px 18px; font-size: var(--text-base, 0.875rem);">
                                    <div style="font-weight: 600; color: var(--text-color);">{{ $it->nama_ruang ?: 'Belum Ditentukan' }}</div>
                                    <div style="margin-top: 2px;">
                                        @if ($it->jurusan_singkatan)
                                            <span class="badge" style="font-size: var(--text-2xs, 0.6875rem); padding: 1px 5px; background: rgba(99,102,241,0.15); color: var(--primary-light, #6366f1);">{{ $it->jurusan_singkatan }}</span>
                                        @else
                                            <span class="badge" style="font-size: var(--text-2xs, 0.6875rem); padding: 1px 5px; background: rgba(148,163,184,0.15); color: var(--text-muted);">Umum</span>
                                        @endif
                                    </div>
                                </td>
                                <td data-label="Tahun & Sumber" style="padding: 14px 18px; text-align: center; font-size: var(--text-sm, 0.8125rem);">
                                    <strong>{{ $it->tahun_perolehan ?: '—' }}</strong>
                                    <div style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted);">{{ $it->sumber_dana }}</div>
                                </td>
                                <td data-label="Harga" style="padding: 14px 18px; text-align: right; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                    Rp {{ number_format((float) $it->harga_perolehan, 0, ',', '.') }}
                                </td>
                                <td data-label="Jumlah" style="padding: 14px 18px; text-align: center; font-weight: 700; font-size: var(--text-base, 0.875rem);">
                                    {{ $it->jumlah }} {{ $it->satuan }}
                                </td>
                                <td data-label="Kondisi" style="padding: 14px 18px; text-align: center;">
                                    @if ($it->kondisi === 'baik')
                                        <span class="badge badge-success">Baik</span>
                                    @elseif ($it->kondisi === 'rusak_ringan')
                                        <span class="badge badge-warning">Rusak Ringan</span>
                                    @else
                                        <span class="badge badge-danger">Rusak Berat</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                    <i class="fas fa-folder-open mb-2" style="font-size: var(--text-3xl, 1.875rem); opacity: 0.5;"></i>
                                    <div>Belum ada data barang pada Buku Induk Inventaris.</div>
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
    </div>
@endsection
