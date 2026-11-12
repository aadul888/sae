@extends('layouts.dashboard')

@section('title', 'Input Data PKL — SAE')
@section('dash_title', 'Buku Induk Siswa')

@section('content')
    {{-- 1. Header Banner --}}
    <div class="dash-banner"
        style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div
                style="width: 44px; height: 44px; border-radius: var(--radius-lg, 12px); background: rgba(13,148,136,0.12); color: #0d9488; display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                <i class="fas fa-building-user"></i>
            </div>
            <div>
                <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                    Input Data PKL / Prakerin
                </h2>
                <p style="margin: 2px 0 0 0; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                    Lengkapi <strong>Nama DU/DI</strong> dan <strong>Alamat DU/DI</strong> tempat Praktik Kerja Lapangan per siswa kelas akhir (XII) untuk dicetak pada lembar resmi Buku Induk.
                </p>
            </div>
        </div>
    </div>

    {{-- 2. Tab Navigasi --}}
    @include('dashboard.kesiswaan.buku-induk.partials.tabs', ['activeTab' => 'input_pkl'])

    @php
        $activeRombelObj = $rombelList->firstWhere('rombongan_belajar_id', $rombelId);
        $judulKelas = $activeRombelObj ? $activeRombelObj->nama : 'Semua Rombel (Kelas XII)';
    @endphp

    {{-- 3. Unified Datatable Card (Mengacu Datatable Baku SAE & Tab Buku Induk) --}}
    <div class="card datatable-card-wrapper" id="tableDataContainer">
        {{-- Datatable Toolbar --}}
        <div class="datatable-toolbar">
            <form method="GET" action="{{ route('dashboard.kesiswaan.buku-induk.input-pkl') }}" class="datatable-filter-form"
                style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center; width: 100%;">
                <div class="toolbar-controls" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <div class="toolbar-entries">
                        <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                        <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                            @foreach ([10, 15, 25, 50, 100] as $n)
                                <option value="{{ $n }}" {{ (int) ($perPage ?? 25) === $n ? 'selected' : '' }}>
                                    {{ $n }}</option>
                            @endforeach
                        </select>
                        <span>entri</span>
                    </div>

                    <select name="tahun_lulus" id="filterTahunLulus" class="toolbar-filter-select"
                        onchange="this.form.submit()"
                        style="height: 38px; min-width: 130px;">
                        @foreach ($filterTahunList as $th)
                            <option value="{{ $th }}" {{ (string) ($tahunLulus ?? '') === (string) $th ? 'selected' : '' }}>
                                Tahun {{ $th }}
                            </option>
                        @endforeach
                    </select>

                    <select name="rombel_id" id="filterRombelPkl" class="toolbar-filter-select"
                        onchange="this.form.submit()"
                        style="height: 38px; min-width: 220px;">
                        <option value="">Semua Rombel (Kelas XII)</option>
                        @foreach ($rombelList as $r)
                            <option value="{{ $r->rombongan_belajar_id }}" {{ (string) $rombelId === (string) $r->rombongan_belajar_id ? 'selected' : '' }}>
                                {{ $r->nama }}
                            </option>
                        @endforeach
                    </select>

                    @if (!empty($q) || !empty($rombelId) || (int) ($perPage ?? 25) !== 25)
                        <a href="{{ route('dashboard.kesiswaan.buku-induk.input-pkl', ['tahun_lulus' => $tahunLulus]) }}"
                            class="btn btn-outline btn-reset-filter"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                            title="Reset filter" aria-label="Reset filter">
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif

                    @if ($can['create'])
                        <button type="button" class="btn btn-primary" onclick="openModalImportPkl()"
                            title="Import Data PKL"
                            aria-label="Import Data PKL"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); background: #0d9488; border-color: #0d9488;">
                            <i class="fas fa-file-import"></i>
                        </button>
                    @endif

                    @if ($can['update'])
                        <button type="button" class="btn btn-primary" onclick="submitBatchPkl()" id="btnSimpanToolbar"
                            title="Simpan Semua Baris Terlihat"
                            aria-label="Simpan Semua Baris Terlihat"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); background: #0d9488; border-color: #0d9488;">
                            <i class="fas fa-floppy-disk"></i>
                        </button>
                    @endif

                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); display: flex; gap: 12px; align-items: center; margin-left: 4px;">
                        <span>Total: <strong style="color: var(--text-color);">{{ $totalSiswa ?? count($siswa) }}</strong></span>
                        <span>Terisi: <strong style="color: #0d9488;">{{ $terisi }}</strong></span>
                        <span>Belum: <strong style="color: #f59e0b;">{{ ($totalSiswa ?? count($siswa)) - $terisi }}</strong></span>
                    </div>
                </div>

                <div class="live-search-wrap" style="height: 38px; min-width: 260px;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="liveSearch" name="q"
                        placeholder="Cari nama / NISN / DU/DI..." value="{{ $q ?? '' }}"
                        autocomplete="off">
                    <button type="button" id="clearSearch" class="clear-search {{ !empty($q) ? 'visible' : '' }}"
                        onclick="this.previousElementSibling.value=''; this.form.submit();"
                        title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </form>
        </div>

        {{-- Tabel Form Data PKL --}}
        <form id="formBatchPkl" action="{{ route('dashboard.kesiswaan.buku-induk.input-pkl.save') }}" method="POST">
            @csrf
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color);">
                            <th style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">No</th>
                            <th class="sortable-th {{ in_array(($sort ?? ''), ['nipd', 'nomor_induk', 'nisn']) ? 'sorted' : '' }}" data-sort="nipd"
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 160px;">
                                No. Induk / NISN
                                <span class="sort-icon">{!! in_array(($sort ?? ''), ['nipd', 'nomor_induk', 'nisn']) ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ ($sort ?? '') === 'nama' ? 'sorted' : '' }}" data-sort="nama"
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Nama Peserta Didik
                                <span class="sort-icon">{!! ($sort ?? '') === 'nama' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ ($sort ?? '') === 'rombel_nama' ? 'sorted' : '' }}" data-sort="rombel_nama"
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 130px;">
                                Rombel
                                <span class="sort-icon">{!! ($sort ?? '') === 'rombel_nama' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ ($sort ?? '') === 'jenis_kelamin' ? 'sorted' : '' }}" data-sort="jenis_kelamin"
                                style="padding: 12px 10px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 60px; text-align: center;">
                                L/P
                                <span class="sort-icon">{!! ($sort ?? '') === 'jenis_kelamin' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ ($sort ?? '') === 'nama_dudi' ? 'sorted' : '' }}" data-sort="nama_dudi"
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; min-width: 240px;">
                                Nama DU/DI (Tempat PKL)
                                <span class="sort-icon">{!! ($sort ?? '') === 'nama_dudi' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; min-width: 300px;">
                                Alamat Lengkap DU/DI
                            </th>
                            @if ($can['update'])
                                <th style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 80px; text-align: center;">
                                    Aksi
                                </th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($siswa as $i => $s)
                            @php
                                $pdId = (string) $s->peserta_didik_id;
                                $namaDudi = $s->nama_dudi ?? '';
                                $alamatDudi = $s->alamat_dudi ?? '';
                                $rowNumber = ($siswa instanceof \Illuminate\Pagination\LengthAwarePaginator) ? $siswa->firstItem() + $i : $i + 1;
                            @endphp
                            <tr id="row-{{ $pdId }}" style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                                <td data-label="No" style="text-align: center; color: var(--text-muted); padding: 12px 14px; font-weight: 700; font-size: 0.82rem;">
                                    {{ $rowNumber }}
                                </td>
                                <td data-label="No. Induk / NISN" style="padding: 12px 16px;">
                                    <div style="font-family: monospace; font-weight: 700; color: var(--primary, #3b82f6);">
                                        {{ ($s->nipd ?? '') !== '' ? $s->nipd : '—' }}
                                    </div>
                                    <div style="font-family: monospace; font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                        {{ ($s->nisn ?? '') !== '' ? $s->nisn : '—' }}
                                    </div>
                                </td>
                                <td data-label="Nama Siswa" style="font-weight: 700; color: var(--text-color); padding: 12px 16px;">
                                    {{ $s->nama }}
                                </td>
                                <td data-label="Rombel" style="padding: 12px 16px; font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                    <span class="badge badge-outline">{{ $s->rombel_nama ?: ($s->nama_rombel ?: '—') }}</span>
                                </td>
                                <td data-label="L/P" style="text-align: center; padding: 12px 10px;">
                                    <span class="badge" style="background: {{ $s->jenis_kelamin === 'L' ? 'rgba(59,130,246,0.1)' : 'rgba(236,72,153,0.1)' }}; color: {{ $s->jenis_kelamin === 'L' ? '#2563eb' : '#db2777' }}; text-transform: uppercase;">
                                        {{ $s->jenis_kelamin }}
                                    </span>
                                </td>
                                <td data-label="Nama DU/DI" style="padding: 8px 14px;">
                                    <input type="text"
                                        name="pkl[{{ $pdId }}][nama_dudi]"
                                        id="nama_dudi_{{ $pdId }}"
                                        value="{{ $namaDudi }}"
                                        placeholder="Contoh: PT Industri Maju Bersama"
                                        class="bi-input input-dudi"
                                        style="width: 100%; font-size: var(--text-sm, 0.8125rem);"
                                        {{ $can['update'] ? '' : 'disabled' }}>
                                </td>
                                <td data-label="Alamat DU/DI" style="padding: 8px 14px;">
                                    <input type="text"
                                        name="pkl[{{ $pdId }}][alamat_dudi]"
                                        id="alamat_dudi_{{ $pdId }}"
                                        value="{{ $alamatDudi }}"
                                        placeholder="Contoh: Jl. Industri Raya No. 45, Kawasan Industri"
                                        class="bi-input input-alamat"
                                        style="width: 100%; font-size: var(--text-sm, 0.8125rem);"
                                        {{ $can['update'] ? '' : 'disabled' }}>
                                </td>
                                @if ($can['update'])
                                    <td data-label="Aksi" style="text-align: center; padding: 8px 10px;">
                                        <button type="button" class="btn btn-outline btn-save-row"
                                            onclick="saveRowPkl('{{ $pdId }}')"
                                            title="Simpan baris ini"
                                            style="width: 34px; height: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: var(--text-xs, 0.75rem); border-color: #0d9488; color: #0d9488; border-radius: var(--radius-md, 8px);">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px 16px; color: var(--text-muted);">
                                    <i class="fas fa-building-user" style="font-size: var(--text-3xl, 2rem); opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                                    <div style="font-weight: 600;">Belum ada data peserta didik yang cocok dengan filter.</div>
                                    <div style="font-size: var(--text-xs, 0.75rem); margin-top: 4px;">Coba ubah kata kunci pencarian atau pilihan rombel kelas di atas.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        {{-- Datatable Footer Pagination --}}
        @if ($siswa instanceof \Illuminate\Pagination\LengthAwarePaginator && $siswa->hasPages())
            <div class="datatable-footer">
                @include('partials.datatable-pagination', ['paginator' => $siswa])
            </div>
        @endif
    </div>

    {{-- Modal Import Data PKL --}}
    @if ($can['create'])
        <div id="modalImportPkl" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card"
                style="max-width: 520px; width: 94%; margin: 0; border-radius: var(--radius-xl, 16px); padding: 22px; background: var(--bg-card); border: 1px solid var(--border-color); box-shadow: 0 10px 30px rgba(0,0,0,0.4);">

                {{-- Header Modal --}}
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div
                            style="width: 42px; height: 42px; border-radius: var(--radius-lg, 12px); background: rgba(13,148,136,0.12); color: #0d9488; display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem);">
                            <i class="fas fa-file-import"></i>
                        </div>
                        <div>
                            <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-color); margin: 0;">
                                Import Data PKL / Prakerin
                            </h3>
                            <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                                {{ $judulKelas }} &mdash; Tahun {{ $tahunLulus }}
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="closeModalImportPkl()"
                        style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-xl, 1.25rem); padding: 4px; width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center;"
                        title="Tutup" aria-label="Tutup">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div style="margin-bottom: 16px;">
                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 12px; line-height: 1.5;">
                        Kolom dikenali: <strong>NISN, Nama DU/DI, Alamat DU/DI</strong>. Hanya baris dengan NISN siswa yang akan diperbarui.
                    </div>
                    <a href="{{ route('dashboard.kesiswaan.buku-induk.input-pkl.template', ['rombel_id' => $rombelId, 'tahun_lulus' => $tahunLulus]) }}"
                        class="btn btn-outline" style="width: 100%; justify-content: center; font-size: var(--text-xs, 0.75rem); border-color: var(--success, #10b981); color: var(--success, #10b981);">
                        <i class="fas fa-download"></i> Unduh Format Template Excel (.xlsx)
                    </a>
                </div>

                <form id="formInputImportPkl" action="{{ route('dashboard.kesiswaan.buku-induk.input-pkl.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="rombel_id" value="{{ $rombelId }}">
                    <input type="hidden" name="tahun_lulus" value="{{ $tahunLulus }}">

                    <div style="margin-bottom: 18px;">
                        <label style="display: block; font-size: var(--text-xs, 0.75rem); font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                            Pilih Berkas Excel / CSV <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="file" name="file_excel" accept=".xlsx,.xls,.csv,.txt" class="bi-input" required style="width: 100%;">
                        <div style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted); margin-top: 4px;">
                            Mendukung berkas format .xlsx, .xls, .csv (maksimal 20 MB).
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="btn btn-outline" onclick="closeModalImportPkl()">Batal</button>
                        <button type="submit" id="btnInputImportPkl" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px; background: #0d9488; border-color: #0d9488;">
                            <i class="fas fa-cloud-arrow-up"></i> Upload &amp; Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @include('dashboard.kesiswaan.buku-induk.partials.buku-induk-style')
@endsection

@push('scripts')
<script>
function openModalImportPkl() {
    const m = document.getElementById('modalImportPkl');
    if (m) m.style.display = 'flex';
}

function closeModalImportPkl() {
    const m = document.getElementById('modalImportPkl');
    if (m) m.style.display = 'none';
}

function submitBatchPkl() {
    const form = document.getElementById('formBatchPkl');
    if (form) form.requestSubmit();
}

// Simpan per baris (row inline)
async function saveRowPkl(pdId) {
    const btn = document.querySelector('#row-' + pdId + ' .btn-save-row');
    const namaDudi = document.getElementById('nama_dudi_' + pdId)?.value || '';
    const alamatDudi = document.getElementById('alamat_dudi_' + pdId)?.value || '';

    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; }

    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const fd = new FormData();
        fd.append('_token', token);
        fd.append('peserta_didik_id', pdId);
        fd.append('nama_dudi', namaDudi);
        fd.append('alamat_dudi', alamatDudi);

        const res = await fetch("{{ route('dashboard.kesiswaan.buku-induk.input-pkl.save-row') }}", {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: fd,
        });
        const json = await res.json();
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check" style="color: #10b981;"></i>'; }

        if (res.ok && json.status === 'success') {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1500,
                timerProgressBar: true,
            });
            Toast.fire({ icon: 'success', title: 'Data PKL tersimpan' });
        } else {
            Swal.fire('Gagal', json.message || 'Gagal menyimpan.', 'error');
        }
    } catch (err) {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check"></i>'; }
        Swal.fire('Kesalahan', 'Gagal menghubungi server.', 'error');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // Per page select auto-submit
    const perPageSel = document.getElementById('perPageSelect');
    if (perPageSel) {
        perPageSel.addEventListener('change', function () {
            this.form.submit();
        });
    }

    // Header sorting click
    document.querySelectorAll('#tableDataContainer .sortable-th').forEach(th => {
        th.style.cursor = 'pointer';
        th.addEventListener('click', function () {
            const sortKey = this.getAttribute('data-sort');
            if (!sortKey) return;
            const url = new URL(window.location.href);
            const curSort = url.searchParams.get('sort');
            const curDir = url.searchParams.get('sort_dir') || 'asc';
            let nextDir = 'asc';
            if (curSort === sortKey) {
                nextDir = curDir === 'asc' ? 'desc' : 'asc';
            }
            url.searchParams.set('sort', sortKey);
            url.searchParams.set('sort_dir', nextDir);
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        });
    });

    // Simpan semua batch
    const formBatch = document.getElementById('formBatchPkl');
    if (formBatch) {
        formBatch.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('btnSimpanToolbar');
            const orig = btn ? btn.innerHTML : '';
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; }

            try {
                const fd = new FormData(formBatch);
                const res = await fetch(formBatch.action, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: fd,
                });
                const json = await res.json();
                if (btn) { btn.disabled = false; btn.innerHTML = orig; }

                if (res.ok && json.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Tersimpan', text: json.message, timer: 1500, showConfirmButton: false })
                        .then(() => location.reload());
                } else {
                    Swal.fire('Gagal', json.message || 'Gagal menyimpan batch.', 'error');
                }
            } catch (err) {
                if (btn) { btn.disabled = false; btn.innerHTML = orig; }
                Swal.fire('Kesalahan', 'Gagal menghubungi server.', 'error');
            }
        });
    }

    // Submit import modal
    const formImport = document.getElementById('formInputImportPkl');
    if (formImport) {
        formImport.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('btnInputImportPkl');
            const orig = btn ? btn.innerHTML : '';
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengimpor…'; }

            try {
                const fd = new FormData(formImport);
                const res = await fetch(formImport.action, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: fd,
                });
                const json = await res.json();
                if (btn) { btn.disabled = false; btn.innerHTML = orig; }

                if (res.ok && json.status === 'success') {
                    closeModalImportPkl();
                    Swal.fire({ icon: 'success', title: 'Impor Selesai', text: json.message })
                        .then(() => location.reload());
                } else {
                    Swal.fire('Gagal Impor', json.message || 'Format berkas tidak sesuai.', 'error');
                }
            } catch (err) {
                if (btn) { btn.disabled = false; btn.innerHTML = orig; }
                Swal.fire('Kesalahan', 'Gagal memproses berkas impor.', 'error');
            }
        });
    }
});
</script>
@endpush
