@extends('layouts.dashboard')

@section('title', 'Master Data — Kompetensi Keahlian — SAE')
@section('dash_title', 'Kompetensi Keahlian')

@section('content')
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-laptop-code text-primary me-2"></i> Master Data — Kompetensi Keahlian
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Kelola data kode dan nama kompetensi keahlian yang tersedia di sekolah.
            </p>
        </div>
        <div class="dash-banner-actions">
            <button type="button" class="btn btn-primary" onclick="openAddModal()"
                style="padding: 9px 16px; font-size: 0.85rem;">
                <i class="fas fa-plus me-1"></i> Tambah Data
            </button>
        </div>
    </div>

    <div class="toolbar-row">
        <div style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; color: var(--text-muted);">
            <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
            <select id="perPageSelect" class="per-page-select">
                @foreach ([10, 15, 25, 50, 100] as $n)
                    <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                @endforeach
            </select>
            <span>entri</span>
            <span class="badge badge-outline" style="margin-left: 8px; font-size: 0.73rem;">Total:
                {{ $total }}</span>
        </div>
        <div class="live-search-wrap">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="liveSearch" placeholder="Cari kode / nama / bidang..." value="{{ $q }}"
                autocomplete="off">
            <button type="button" id="clearSearch" class="clear-search {{ $q ? 'visible' : '' }}" title="Hapus pencarian">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
        <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                    @php $cols = [['kode','Kode'],['nama','Nama Kompetensi'],['bidang_keahlian','Bidang Keahlian'],['program_keahlian','Program Keahlian'],['tahun_berlaku','Tahun']]; @endphp
                    @foreach ($cols as [$key, $label])
                        <th class="sortable-th {{ $sort === $key ? 'sorted' : '' }}"
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;"
                            data-sort="{{ $key }}">
                            {{ $label }}
                            <span class="sort-icon">{!! $sort === $key ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                    @endforeach
                    <th
                        style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 80px;">
                        Status</th>
                    <th
                        style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                        Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $item)
                    <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                        <td style="padding: 14px 18px; font-weight: 700; color: var(--primary); font-size: 0.86rem; font-family: monospace;"
                            data-label="Kode">
                            {{ $item->kode }}
                        </td>
                        <td style="padding: 14px 18px; font-weight: 600; color: var(--text-color); font-size: 0.86rem;"
                            data-label="Nama Kompetensi">
                            {{ $item->nama }}
                        </td>
                        <td style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);"
                            data-label="Bidang Keahlian">
                            {{ $item->bidang_keahlian ?: '-' }}
                        </td>
                        <td style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);"
                            data-label="Program Keahlian">
                            {{ $item->program_keahlian ?: '-' }}
                        </td>
                        <td style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted); text-align: center;"
                            data-label="Tahun">
                            {{ $item->tahun_berlaku ?: '-' }}
                        </td>
                        <td style="padding: 14px 18px; text-align: center;" data-label="Status">
                            <span class="badge {{ $item->is_active ? 'badge-primary' : 'badge-outline' }}"
                                style="font-size: 0.7rem; padding: 3px 8px; border-radius: 6px;">
                                {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                            <div class="table-actions">
                                <button type="button" class="btn-icon" title="Edit"
                                    onclick="openEditModal({{ json_encode($item) }})">
                                    <i class="fas fa-pen-to-square"></i>
                                </button>
                                <form action="{{ route('dashboard.kompetensi-keahlian.destroy', $item->id) }}"
                                    method="POST" style="display: inline; margin: 0;" data-action-type="delete"
                                    data-name="{{ $item->nama }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-icon danger" title="Hapus">
                                        <i class="fas fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7"
                            style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                            <i class="fas fa-folder-open mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                            <div>Belum ada data kompetensi keahlian.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($list->hasPages())
        <div class="custom-pagination">
            @if ($list->onFirstPage())
                <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
            @else
                <a href="{{ $list->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i
                        class="fas fa-chevron-left"></i></a>
            @endif
            @php
                $cur = $list->currentPage();
                $last = $list->lastPage();
                $from = max(1, $cur - 2);
                $to = min($last, $cur + 2);
            @endphp
            @if ($from > 1)
                <a href="{{ $list->url(1) }}" class="page-btn">1</a>
                @if ($from > 2)
                    <span class="page-info">&hellip;</span>
                @endif
            @endif
            @for ($i = $from; $i <= $to; $i++)
                <a href="{{ $list->url($i) }}"
                    class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
            @endfor
            @if ($to < $last)
                @if ($to < $last - 1)
                    <span class="page-info">&hellip;</span>
                @endif
                <a href="{{ $list->url($last) }}" class="page-btn">{{ $last }}</a>
            @endif
            @if ($list->hasMorePages())
                <a href="{{ $list->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i
                        class="fas fa-chevron-right"></i></a>
            @else
                <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
            @endif
        </div>
    @endif

    {{-- Add/Edit Modal --}}
    <div id="kkModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="max-width: 520px; width: 90%; margin: 0; border-radius: 14px; padding: 22px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                <h3 id="kkModalTitle" style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0;">
                    Tambah Kompetensi Keahlian</h3>
                <button type="button" onclick="closeKKModal()"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="kkForm" method="POST" action="">
                @csrf
                <div id="kkMethodField"></div>

                <div class="modal-form-grid">
                    <div class="form-group-compact">
                        <label>Kode <span style="color: #ef4444;">*</span></label>
                        <input type="text" name="kode" id="inputKode" required placeholder="Cth: C2.01"
                            style="font-family: monospace;">
                    </div>
                    <div class="form-group-compact">
                        <label>Tahun Berlaku</label>
                        <input type="number" name="tahun_berlaku" id="inputTahun" min="2000" max="2099"
                            placeholder="Cth: 2022">
                    </div>
                    <div class="form-group-compact full-width">
                        <label>Nama Kompetensi Keahlian <span style="color: #ef4444;">*</span></label>
                        <input type="text" name="nama" id="inputNama" required
                            placeholder="Cth: Rekayasa Perangkat Lunak">
                    </div>
                    <div class="form-group-compact">
                        <label>Bidang Keahlian</label>
                        <input type="text" name="bidang_keahlian" id="inputBidang"
                            placeholder="Cth: Teknologi Informasi">
                    </div>
                    <div class="form-group-compact">
                        <label>Program Keahlian</label>
                        <input type="text" name="program_keahlian" id="inputProgram"
                            placeholder="Cth: Pengembangan Perangkat Lunak">
                    </div>
                    <div class="form-group-compact full-width">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="is_active" id="inputActive" value="1" checked
                                style="width: 16px; height: 16px; accent-color: var(--primary);">
                            Status Aktif
                        </label>
                    </div>
                </div>

                <div
                    style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" class="btn btn-outline" onclick="closeKKModal()"
                        style="padding: 8px 14px; font-size: 0.82rem;">Batal</button>
                    <button type="submit" class="btn btn-primary"
                        style="padding: 8px 16px; font-size: 0.82rem;">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/kompetensi-keahlian.js') }}"></script>
    @endpush
@endsection
