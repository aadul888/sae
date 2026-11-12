@extends('layouts.dashboard')

@section('title', 'Input Nilai — SAE')
@section('dash_title', 'Buku Induk Siswa')

@section('content')
    {{-- 1. Header Banner --}}
    <div class="dash-banner"
        style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div
                style="width: 44px; height: 44px; border-radius: var(--radius-lg, 12px); background: rgba(67,56,202,0.12); color: #4338ca; display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                <i class="fas fa-pen-to-square"></i>
            </div>
            <div>
                <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                    Input Nilai per Kelas
                </h2>
                <p style="margin: 2px 0 0 0; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                    Isi nilai <strong>manual per kelas akhir (XII)</strong> atau impor berkas nilai kelas ini
                    (siap menerima ekspor e-Rapor). Semester dapat dipilih mundur mulai 1 sampai 6.
                </p>
            </div>
        </div>
    </div>

    {{-- 2. Tab Navigasi --}}
    @include('dashboard.kesiswaan.buku-induk.partials.tabs', ['activeTab' => 'input_nilai'])

    {{-- 3. Unified Datatable Card (Mengacu Datatable Baku SAE & Tab Buku Induk) --}}
    <div class="card datatable-card-wrapper" id="tableDataContainer">
        {{-- Datatable Toolbar --}}
        <div class="datatable-toolbar">
            <form method="GET" action="{{ route('dashboard.kesiswaan.buku-induk.input-nilai') }}" class="datatable-filter-form"
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

                    <select name="semester" id="filterSemesterNilai" class="toolbar-filter-select"
                        onchange="this.form.submit()"
                        style="height: 38px; min-width: 140px;">
                        @foreach ($semesterList as $sem)
                            <option value="{{ $sem }}" {{ (int) $semester === $sem ? 'selected' : '' }}>
                                Semester {{ $sem }}
                            </option>
                        @endforeach
                    </select>

                    <select name="kelas" id="filterKelasNilai" class="toolbar-filter-select"
                        onchange="this.form.submit()"
                        style="height: 38px; min-width: 220px;">
                        @forelse ($kelasList as $k)
                            <option value="{{ $k['id'] }}" {{ (string) $kelasId === (string) $k['id'] ? 'selected' : '' }}>
                                {{ $k['nama'] }}
                            </option>
                        @empty
                            <option value="">&mdash; Tidak ada kelas tingkat ini &mdash;</option>
                        @endforelse
                    </select>

                    @if (!empty($q) || (int) ($perPage ?? 25) !== 25)
                        <a href="{{ route('dashboard.kesiswaan.buku-induk.input-nilai', ['tahun_lulus' => $tahunLulus, 'semester' => $semester, 'kelas' => $kelasId]) }}"
                            class="btn btn-outline btn-reset-filter"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                            title="Reset filter" aria-label="Reset filter">
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif

                    @if ($kelasId !== '' && $kelas)
                        @if ($can['create'])
                            <button type="button" class="btn btn-primary" onclick="openModalImportNilai()"
                                title="Import Berkas Nilai"
                                aria-label="Import Berkas Nilai"
                                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);">
                                <i class="fas fa-file-import"></i>
                            </button>
                        @endif

                        @if ($can['update'] && $siswa->count() > 0 && !empty($mapel))
                            <button type="button" class="btn btn-primary" onclick="submitFormNilai()" id="btnSimpanToolbar"
                                title="Simpan Semua Nilai Halaman Ini"
                                aria-label="Simpan Semua Nilai Halaman Ini"
                                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); background: #10b981; border-color: #10b981;">
                                <i class="fas fa-floppy-disk"></i>
                            </button>
                        @endif
                    @endif

                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); display: flex; align-items: center; gap: 8px; margin-left: 4px;">
                        @if (!empty($mapel))
                            <span class="badge badge-outline" style="font-weight: 700;">{{ count($mapel) }} mapel aktif</span>
                            @if (in_array((int) $semester, $pklSemester ?? [], true))
                                <span class="badge" style="background: rgba(245,158,11,0.15); color: #d97706; font-weight: 700;">
                                    <i class="fas fa-lock"></i> Terkunci PKL
                                </span>
                            @endif
                        @endif
                    </div>
                </div>

                <div class="live-search-wrap" style="height: 38px; min-width: 260px;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="liveSearch" name="q"
                        placeholder="Cari nama / NISN / no. induk..." value="{{ $q ?? '' }}"
                        autocomplete="off">
                    <button type="button" id="clearSearch" class="clear-search {{ !empty($q) ? 'visible' : '' }}"
                        onclick="this.previousElementSibling.value=''; this.form.submit();"
                        title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </form>
        </div>

        @if ($kelasId === '' || !$kelas)
            <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                <i class="fas fa-chalkboard-user" style="font-size: var(--text-3xl, 2rem); opacity: 0.3; margin-bottom: 12px; display: block;\"></i>
                <div style="font-weight: 700; font-size: var(--text-md, 1rem);">Belum ada kelas pada kriteria ini.</div>
            </div>
        @else
            {{-- Form Tabel Nilai Manual --}}
            <form id="formInputNilai" action="{{ route('dashboard.kesiswaan.buku-induk.input-nilai.save') }}" method="POST">
                @csrf
                <input type="hidden" name="kelas" value="{{ $kelasId }}">
                <input type="hidden" name="semester" value="{{ $semester }}">

                <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                    <table class="table table-pd bi-nilai-table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr style="background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color);">
                                <th style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 45px; text-align: center;">No</th>
                                <th class="sortable-th {{ in_array(($sort ?? ''), ['nipd', 'nomor_induk', 'nisn']) ? 'sorted' : '' }}" data-sort="nipd"
                                    style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 150px;">
                                    No. Induk / NISN
                                    <span class="sort-icon">{!! in_array(($sort ?? ''), ['nipd', 'nomor_induk', 'nisn']) ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                                </th>
                                <th class="sortable-th {{ ($sort ?? '') === 'nama' ? 'sorted' : '' }}" data-sort="nama"
                                    style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; min-width: 200px;">
                                    Nama Peserta Didik
                                    <span class="sort-icon">{!! ($sort ?? '') === 'nama' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                                </th>
                                @foreach ($mapel as $nama)
                                    @php $sing = $singkatan[$nama] ?? ''; @endphp
                                    <th title="{{ $nama }}" style="padding: 12px 8px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; min-width: 90px;">
                                        <span class="bi-nilai-dot" style="background: {{ ($kelompok[$nama] ?? 'umum') === 'kejuruan' ? '#f59e0b' : '#10b981' }};"></span>
                                        {{ $sing !== '' ? $sing : $nama }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($siswa as $i => $s)
                                @php
                                    $rowNumber = ($siswa instanceof \Illuminate\Pagination\LengthAwarePaginator) ? $siswa->firstItem() + $i : $i + 1;
                                @endphp
                                <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                                    <td data-label="No" style="text-align: center; color: var(--text-muted); padding: 10px 14px; font-weight: 700; font-size: 0.82rem;">{{ $rowNumber }}</td>
                                    <td data-label="No. Induk / NISN" style="padding: 10px 16px;">
                                        <div style="font-family: monospace; font-weight: 700; color: var(--primary, #3b82f6);">
                                            {{ ($s->nipd ?? '') !== '' ? $s->nipd : '—' }}
                                        </div>
                                        <div style="font-family: monospace; font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                            {{ ($s->nisn ?? '') !== '' ? $s->nisn : '—' }}
                                        </div>
                                    </td>
                                    <td data-label="Nama" class=\"bi-nilai-nama\" style="font-weight: 700; color: var(--text-color); padding: 10px 16px;">{{ $s->nama }}</td>
                                    @foreach ($mapel as $nama)
                                        <td style="padding: 6px 8px; text-align: center;">
                                            <input type="text" inputmode="decimal" class="bi-nilai-input"
                                                name="nilai[{{ $s->peserta_didik_id }}][{{ $nama }}]"
                                                data-min="10" data-max="100" maxlength="6" placeholder="—"
                                                title="Wajib angka {{ \App\Services\BukuIndukNilaiService::rentangNilai() }}"
                                                value="{{ $tersimpan[$s->peserta_didik_id][$nama] ?? '' }}">
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 3 + count($mapel) }}" style="text-align: center; padding: 40px 16px; color: var(--text-muted);">
                                        <i class="fas fa-users-slash" style="font-size: var(--text-3xl, 2rem); opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                                        <div style="font-weight: 600;">Belum ada siswa di kelas {{ $kelas['nama'] }}.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Datatable Footer Pagination & Simpan --}}
                <div class="datatable-footer" style="display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; border-top: 1px solid var(--border-color); background: rgba(0,0,0,0.01); flex-wrap: wrap; gap: 10px;">
                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                        Isi nilai angka wajib <strong>{{ \App\Services\BukuIndukNilaiService::rentangNilai() }}</strong>. Kosongkan untuk menghapus nilai.
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span id="inputNilaiInfo" style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);"></span>
                        @if ($can['update'] && $siswa->count() > 0 && !empty($mapel))
                            <button type="submit" class="btn btn-primary"
                                style="padding: 8px 20px; font-size: var(--text-sm, 0.8125rem); display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fas fa-floppy-disk"></i> Simpan Nilai
                            </button>
                        @endif
                    </div>
                </div>
            </form>

            @if ($siswa instanceof \Illuminate\Pagination\LengthAwarePaginator && $siswa->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $siswa])
                </div>
            @endif
        @endif
    </div>

    {{-- Modal Import Nilai --}}
    @if ($can['create'] && $kelas)
        <div id="modalImportNilai" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card"
                style="max-width: 520px; width: 94%; margin: 0; border-radius: var(--radius-xl, 16px); padding: 22px; background: var(--bg-card); border: 1px solid var(--border-color); box-shadow: 0 10px 30px rgba(0,0,0,0.4);">
                
                {{-- Header Modal --}}
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div
                            style="width: 42px; height: 42px; border-radius: var(--radius-lg, 12px); background: rgba(67,56,202,0.12); color: #4338ca; display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem);">
                            <i class="fas fa-file-import"></i>
                        </div>
                        <div>
                            <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-color); margin: 0;">
                                Import Nilai Siswa
                            </h3>
                            <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                                {{ $kelas['nama'] }} &mdash; Semester {{ $semester }}
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="closeModalImportNilai()"
                        style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-xl, 1.25rem); padding: 4px; width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center;"
                        title="Tutup" aria-label="Tutup">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div style="margin-bottom: 16px;">
                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 12px; line-height: 1.5;">
                        Kolom nilai akan dikenali otomatis berdasarkan nama mapel yang terdaftar. Baris NISN wajib terdaftar pada kelas <strong>{{ $kelas['nama'] }}</strong>.
                    </div>
                    <a href="{{ route('dashboard.kesiswaan.buku-induk.input-nilai.template', ['kelas' => $kelasId, 'semester' => $semester, 'tahun_lulus' => $tahunLulus]) }}"
                        class="btn btn-outline" style="width: 100%; justify-content: center; font-size: var(--text-xs, 0.75rem); border-color: var(--success, #10b981); color: var(--success, #10b981);">
                        <i class="fas fa-download"></i> Unduh Format Template Excel (.xlsx)
                    </a>
                </div>

                <form id="formInputImport" action="{{ route('dashboard.kesiswaan.buku-induk.input-nilai.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="kelas" value="{{ $kelasId }}">
                    <input type="hidden" name="semester" value="{{ $semester }}">
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
                        <button type="button" class="btn btn-outline" onclick="closeModalImportNilai()">Batal</button>
                        <button type="submit" id="btnInputImport" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px;">
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
function openModalImportNilai() {
    const m = document.getElementById('modalImportNilai');
    if (m) m.style.display = 'flex';
}

function closeModalImportNilai() {
    const m = document.getElementById('modalImportNilai');
    if (m) m.style.display = 'none';
}

function submitFormNilai() {
    const form = document.getElementById('formInputNilai');
    if (form) form.requestSubmit();
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

    const formNilai = document.getElementById('formInputNilai');
    if (formNilai) {
        formNilai.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = formNilai.querySelector('button[type="submit"]');
            const btnToolbar = document.getElementById('btnSimpanToolbar');
            const info = document.getElementById('inputNilaiInfo');
            if (btn) btn.disabled = true;
            if (btnToolbar) btnToolbar.disabled = true;
            if (info) info.textContent = 'Menyimpan...';

            try {
                const fd = new FormData(formNilai);
                const res = await fetch(formNilai.action, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: fd,
                });
                const data = await res.json();
                if (btn) btn.disabled = false;
                if (btnToolbar) btnToolbar.disabled = false;
                if (res.ok && data.status === 'success') {
                    if (window.Swal) Swal.fire({ icon: 'success', title: 'Tersimpan', text: data.message || 'Nilai berhasil disimpan.' });
                    if (info) info.textContent = 'Tersimpan pada ' + new Date().toLocaleTimeString();
                } else {
                    if (window.Swal) Swal.fire({ icon: 'error', title: 'Gagal', text: data.message || 'Terjadi kesalahan.' });
                    if (info) info.textContent = 'Gagal menyimpan.';
                }
            } catch (err) {
                if (btn) btn.disabled = false;
                if (btnToolbar) btnToolbar.disabled = false;
                if (info) info.textContent = 'Kesalahan jaringan.';
                if (window.Swal) Swal.fire({ icon: 'error', title: 'Kesalahan', text: 'Gagal menghubungi server.' });
            }
        });
    }

    const formImport = document.getElementById('formInputImport');
    if (formImport) {
        formImport.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('btnInputImport');
            const orig = btn ? btn.innerHTML : '';
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengimpor…'; }

            try {
                const fd = new FormData(formImport);
                const res = await fetch(formImport.action, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: fd,
                });
                const data = await res.json();
                if (btn) { btn.disabled = false; btn.innerHTML = orig; }

                if (res.ok && data.status === 'success') {
                    closeModalImportNilai();
                    if (window.Swal) {
                        Swal.fire({ icon: 'success', title: 'Impor Selesai', text: data.message })
                            .then(() => location.reload());
                    } else {
                        location.reload();
                    }
                } else {
                    if (window.Swal) Swal.fire({ icon: 'error', title: 'Gagal Impor', text: data.message || 'Format tidak sesuai.' });
                }
            } catch (err) {
                if (btn) { btn.disabled = false; btn.innerHTML = orig; }
                if (window.Swal) Swal.fire({ icon: 'error', title: 'Kesalahan', text: 'Gagal memproses berkas impor.' });
            }
        });
    }
});
</script>
@endpush
