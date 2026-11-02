@extends('layouts.dashboard')

@section('title', 'Pencatatan Pelanggaran Siswa - Keamanan')

@section('content')
    <div class="content-wrapper">
        <!-- 1. Header Banner & Actions Standar SAE -->
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg, 12px); background: rgba(239,68,68,0.12); color: var(--danger, #ef4444); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); flex-shrink: 0;">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                        Pencatatan Pelanggaran Siswa Pos Keamanan
                    </h2>
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        Pencatatan pelanggaran disiplin dan tata tertib siswa di gerbang &amp; patroli &bull; Terhubung realtime ke Kesiswaan.
                    </div>
                </div>
            </div>

            <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <button type="button" class="btn btn-outline" onclick="openModalCetakKeamanan()"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                    title="Cetak Pelanggaran (pilih periode)" aria-label="Cetak Pelanggaran">
                    <i class="fas fa-print"></i>
                </button>
                @if ($canCreate)
                    <button type="button" class="btn btn-outline" id="btnOpenImportKeamanan"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                        title="Import Pelanggaran Siswa via Excel" aria-label="Import Pelanggaran Siswa via Excel">
                        <i class="fas fa-file-excel text-success"></i>
                    </button>
                    <button type="button" class="btn btn-primary" onclick="openModalPelanggaran()"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                        title="Catat Pelanggaran Siswa">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>

        <!-- 2. Stat Grid Baku SAE -->
        <div class="dash-stat-grid" style="margin-bottom: 20px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239,68,68,0.12); color: var(--danger, #ef4444);">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($totalHariIni) }}</div>
                    <div class="dash-stat-label">Pelanggaran Hari Ini</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6);">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($totalBulanIni) }}</div>
                    <div class="dash-stat-label">Total Bulan Ini</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: var(--success, #10b981);">
                    <i class="fas fa-scale-balanced"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format(count($tataTertibList)) }}</div>
                    <div class="dash-stat-label">Aturan Tata Tertib Aktif</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(139,92,246,0.12); color: var(--purple, #8b5cf6);">
                    <i class="fas fa-list-ol"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($totalSemua) }}</div>
                    <div class="dash-stat-label">Total Pelanggaran</div>
                </div>
            </div>
        </div>

        {{-- Unified Datatable Keamanan Pelanggaran --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            {{-- 1. Toolbar Header (Entries, Filters & Live Search) --}}
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.keamanan.pelanggaran.index') }}" id="filterForm"
                    style="margin: 0;">
                    <div
                        style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                        <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                            <div class="toolbar-entries">
                                <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                                <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                                    @foreach ([10, 15, 25, 50, 100] as $n)
                                        <option value="{{ $n }}"
                                            {{ request('perPage', 15) == $n ? 'selected' : '' }}>
                                            {{ $n }}</option>
                                    @endforeach
                                </select>
                                <span>entri</span>
                            </div>

                            <input type="date" name="tanggal" value="{{ $tanggal }}" class="form-control"
                                style="height: 38px; width: auto; min-width: 140px; padding: 4px 10px;">

                            <select name="kategori" class="toolbar-filter-select" style="height: 38px; min-width: 170px;">
                                <option value="">Semua Kategori</option>
                                <option value="kehadiran" {{ $kategori === 'kehadiran' ? 'selected' : '' }}>Kehadiran / Jam Belajar</option>
                                <option value="kerapian" {{ $kategori === 'kerapian' ? 'selected' : '' }}>Kerapian Seragam</option>
                                <option value="perilaku" {{ $kategori === 'perilaku' ? 'selected' : '' }}>Perilaku &amp; Ketertiban</option>
                                <option value="larangan_berat" {{ $kategori === 'larangan_berat' ? 'selected' : '' }}>Larangan Berat</option>
                            </select>

                            @if ($q || $kategori || $tanggal)
                                <a href="{{ route('dashboard.keamanan.pelanggaran.index') }}"
                                    class="btn btn-outline btn-reset-filter"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                    title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="q" id="searchPelanggaran" value="{{ $q }}"
                                placeholder="Cari nama siswa, NISN, aturan..." autocomplete="off">
                            <button type="button" id="clearSearch"
                                class="clear-search {{ !empty($q) ? 'visible' : '' }}" title="Hapus pencarian">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- 2. Data Table --}}
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color);">
                            <th style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 110px;">Tanggal</th>
                            <th style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Siswa</th>
                            <th style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Aturan Dilanggar</th>
                            <th style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 180px;">Lokasi Kejadian</th>
                            <th style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 90px;">Poin</th>
                            <th style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px; text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pelanggaranList as $item)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem);">
                                    {{ date('d/m/Y', strtotime($item->tanggal_kejadian)) }}
                                </td>
                                <td style="padding: 12px 16px;">
                                    <div style="font-weight: 700; color: var(--text-color);">
                                        {{ $item->siswa?->nama ?: 'Siswa #' . $item->peserta_didik_id }}
                                    </div>
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                        NISN: {{ $item->siswa?->nisn ?: '-' }}
                                    </div>
                                </td>
                                <td style="padding: 12px 16px;">
                                    <strong>{{ $item->aturan?->nama_aturan ?: 'Pelanggaran' }}</strong>
                                    @if ($item->aturan?->tingkat)
                                        <span class="badge {{ $item->aturan->tingkat === 'berat' ? 'badge-danger' : ($item->aturan->tingkat === 'sedang' ? 'badge-warning' : 'badge-success') }}" style="font-size: 0.65rem; margin-left: 4px;">
                                            {{ strtoupper($item->aturan->tingkat) }}
                                        </span>
                                    @endif
                                    @if ($item->keterangan)
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                            Ket: {{ $item->keterangan }}
                                        </div>
                                    @endif
                                </td>
                                <td style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                    <i class="fas fa-location-dot me-1"></i> {{ $item->tempat_kejadian }}
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    @if ($item->sanggah_status)
                                        <span style="text-decoration: line-through; font-size: 0.75rem; color: var(--text-muted);">+{{ $item->poin }}</span>
                                        <div style="font-weight: 800; color: #059669; font-size: 0.875rem;">+{{ $item->poin_netto }}</div>
                                    @else
                                        <span style="font-weight: 800; color: var(--danger, #ef4444); font-size: 1rem;">+{{ $item->poin }}</span>
                                    @endif
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    @if ($item->sanggah_status)
                                        <span class="badge badge-success" style="font-size: 0.7rem;"><i class="fas fa-check"></i> DISANGGAH</span>
                                    @else
                                        @php
                                            $badgeSt = match ($item->status_tindak_lanjut) {
                                                'selesai' => 'badge-success',
                                                'proses'  => 'badge-warning',
                                                default   => 'badge-danger',
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeSt }}" style="font-size: 0.7rem;">{{ strtoupper($item->status_tindak_lanjut) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px 16px; color: var(--text-muted);">
                                    <i class="fas fa-folder-open" style="font-size: 2rem; opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                                    Belum ada catatan pelanggaran siswa dari pos satpam.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 3. Footer Paginasi Terpadu Datatable --}}
            @if ($pelanggaranList->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $pelanggaranList])
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Form Pencatatan Pelanggaran Siswa -->
    <div id="modalPelanggaranSatpam"
        style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center;">
        <div class="card" style="width: 100%; max-width: 580px; margin: 20px; max-height: 90vh; overflow-y: auto;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
                <h3 style="font-size: var(--text-xl, 1.25rem); font-weight: 700; margin: 0; color: var(--text-heading); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-triangle-exclamation text-danger"></i> Catat Pelanggaran Siswa
                </h3>
                <button type="button" onclick="closeModalPelanggaran()"
                    style="background: none; border: none; font-size: var(--text-xl, 1.25rem); cursor: pointer; color: var(--text-muted);">&times;</button>
            </div>

            <form method="POST" action="{{ route('dashboard.keamanan.pelanggaran.store') }}" enctype="multipart/form-data">
                @csrf

                <!-- Cari Siswa (People Autocomplete Support) -->
                <div style="margin-bottom: 14px;">
                    <label class="form-label">Pilih Siswa Pelanggar <span style="color:red;">*</span></label>
                    <input type="search" id="pelanggaran_cari_siswa" class="form-control"
                        placeholder="Ketik nama siswa atau NISN..." autocomplete="off"
                        data-people-search="{{ route('dashboard.people.search') }}" data-people-kind="siswa"
                        data-people-target="#pelanggaran_peserta_didik_id" required>
                    <input type="hidden" name="peserta_didik_id" id="pelanggaran_peserta_didik_id" required>
                </div>

                <!-- Aturan Tata Tertib (Bisa Pilih Multi Aturan) -->
                <div style="margin-bottom: 14px; position: relative;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <label class="form-label" style="margin: 0;">Aturan Tata Tertib (Bisa > 1 Aturan) <span style="color:red;">*</span></label>
                        <span id="pelanggaran_total_poin_label" style="font-size: 0.75rem; font-weight: 700; color: var(--danger);">0 Poin Terpilih</span>
                    </div>
                    <input type="search" id="pelanggaran_tatib_search" class="form-control"
                        placeholder="Ketik kode atau nama aturan untuk menambahkan..." autocomplete="off">

                    <!-- Dropdown Hasil Pencarian Aturan -->
                    <div id="pelanggaran_tatib_results"
                        style="display: none; position: absolute; left: 0; right: 0; top: 100%; background: var(--bg-card, #fff); border: 1px solid var(--border-color); border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.12); max-height: 220px; overflow-y: auto; z-index: 1000; margin-top: 4px;">
                    </div>

                    <!-- Container Daftar Aturan Terpilih (Chips / Tags Multi Aturan) -->
                    <div id="pelanggaran_tatib_selected_container"
                        style="display: flex; flex-direction: column; gap: 6px; margin-top: 8px;">
                    </div>
                    <!-- Hidden input penampung array ID -->
                    <div id="pelanggaran_tatib_hidden_inputs"></div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div>
                        <label class="form-label">Tanggal Kejadian <span style="color:red;">*</span></label>
                        <input type="date" name="tanggal_kejadian" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div>
                        <label class="form-label">Lokasi / Pos Kejadian <span style="color:red;">*</span></label>
                        <input list="listLokasiKejadian" name="tempat_kejadian" class="form-control"
                            value="Pos Gerbang Utama" placeholder="Pilih atau ketik lokasi..." required>
                        <datalist id="listLokasiKejadian">
                            <option value="Pos Gerbang Utama">Pos Gerbang Utama</option>
                            <option value="Gerbang Samping / Belakang">Gerbang Samping / Belakang</option>
                            <option value="Area Parkir Siswa">Area Parkir Siswa</option>
                            <option value="Luar Pagar Sekolah">Luar Pagar Sekolah</option>
                            <option value="Kantin / Koridor">Kantin / Koridor</option>
                        </datalist>
                    </div>
                </div>

                <div style="margin-bottom: 14px;">
                    <label class="form-label">Kronologi / Catatan Temuan Satpam</label>
                    <textarea name="keterangan" rows="3" class="form-control"
                        placeholder="Contoh: Siswa keluar gerbang saat jam belajar tanpa surat izin..."></textarea>
                </div>

                <div style="margin-bottom: 18px;">
                    <label class="form-label">Foto Bukti <small class="text-muted">(Opsional, maks 5 MB)</small></label>
                    <input type="file" name="foto_bukti" class="form-control" accept="image/*">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModalPelanggaran()">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-save"></i> Simpan Pelanggaran</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Import Pelanggaran Siswa via Excel --}}
    @include('dashboard.keamanan.partials.modal-import', [
        'modulKode' => 'pelanggaran',
        'modulJudul' => 'Pelanggaran Siswa',
        'templateRoute' => route('dashboard.keamanan.pelanggaran.template'),
        'importRoute' => route('dashboard.keamanan.pelanggaran.import'),
    ])

    {{-- Modal Cetak periode Pelanggaran --}}
    @include('dashboard.keamanan.partials.modal-cetak', [
        'cetakRoute' => route('dashboard.keamanan.pelanggaran.cetak'),
        'modulJudul' => 'Pelanggaran Siswa',
    ])
@endsection

@push('scripts')
<script>
    const modalPel = document.getElementById('modalPelanggaranSatpam');

    function openModalPelanggaran() {
        if (modalPel) modalPel.style.display = 'flex';
    }

    function closeModalPelanggaran() {
        if (modalPel) modalPel.style.display = 'none';
    }

    if (modalPel) {
        modalPel.addEventListener('click', function(e) {
            if (e.target === modalPel) closeModalPelanggaran();
        });
    }

    // Interaktif Search & Select Aturan Tata Tertib (Sama seperti pencarian nama)
    document.addEventListener('DOMContentLoaded', function () {
        const inputTatib = document.getElementById('pelanggaran_tatib_search');
        const resultsTatib = document.getElementById('pelanggaran_tatib_results');
        const containerSelected = document.getElementById('pelanggaran_tatib_selected_container');
        const hiddenInputsWrap = document.getElementById('pelanggaran_tatib_hidden_inputs');
        const totalPoinLabel = document.getElementById('pelanggaran_total_poin_label');

        const selectedTatibMap = new Map(); // id -> item

        function renderSelectedTatib() {
            containerSelected.innerHTML = '';
            hiddenInputsWrap.innerHTML = '';
            let sumPoin = 0;

            selectedTatibMap.forEach((item, id) => {
                sumPoin += parseInt(item.bobot_poin || 0, 10);

                // Hidden input
                const hInput = document.createElement('input');
                hInput.type = 'hidden';
                hInput.name = 'tata_tertib_ids[]';
                hInput.value = id;
                hiddenInputsWrap.appendChild(hInput);

                // Chip Card
                const chip = document.createElement('div');
                chip.style.display = 'flex';
                chip.style.alignItems = 'center';
                chip.style.justifyContent = 'space-between';
                chip.style.padding = '8px 12px';
                chip.style.background = 'rgba(239,68,68,0.06)';
                chip.style.border = '1px solid rgba(239,68,68,0.2)';
                chip.style.borderRadius = '6px';
                chip.style.fontSize = '0.8125rem';
                chip.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-weight: 800; color: var(--primary);">[${item.kode}]</span>
                        <span style="font-weight: 600; color: var(--text-color);">${item.nama_aturan}</span>
                        <span class="badge ${item.tingkat === 'berat' ? 'badge-danger' : (item.tingkat === 'sedang' ? 'badge-warning' : 'badge-success')}" style="font-size: 0.65rem;">
                            ${(item.tingkat || 'ringan').toUpperCase()}
                        </span>
                        <span style="font-weight: 800; color: var(--danger);">+${item.bobot_poin} Poin</span>
                    </div>
                    <button type="button" class="btn-remove-tatib" data-id="${id}"
                        style="background: none; border: none; color: var(--danger); cursor: pointer; padding: 2px 6px; font-size: 0.875rem;">
                        <i class="fas fa-trash-can"></i>
                    </button>
                `;
                containerSelected.appendChild(chip);
            });

            if (totalPoinLabel) {
                totalPoinLabel.textContent = `${selectedTatibMap.size} Aturan (+${sumPoin} Poin Terpilih)`;
            }

            if (selectedTatibMap.size > 0) {
                inputTatib.removeAttribute('required');
                inputTatib.setCustomValidity('');
            } else {
                inputTatib.setAttribute('required', 'required');
                inputTatib.setCustomValidity('Pilih minimal satu aturan tata tertib.');
            }
        }

        containerSelected.addEventListener('click', function(e) {
            const btnRemove = e.target.closest('.btn-remove-tatib');
            if (btnRemove) {
                const id = parseInt(btnRemove.getAttribute('data-id'), 10);
                selectedTatibMap.delete(id);
                renderSelectedTatib();
            }
        });

        let tatibDebounce;
        if (inputTatib) {
            inputTatib.addEventListener('input', function () {
                clearTimeout(tatibDebounce);
                const query = this.value.trim();
                if (query.length < 2) {
                    resultsTatib.style.display = 'none';
                    return;
                }

                tatibDebounce = setTimeout(() => {
                    fetch(`{{ route('dashboard.keamanan.pelanggaran.search-tatib') }}?q=${encodeURIComponent(query)}`)
                        .then(res => res.json())
                        .then(data => {
                            resultsTatib.innerHTML = '';
                            if (!data.length) {
                                resultsTatib.innerHTML = '<div style="padding: 10px 14px; font-size: 0.8125rem; color: var(--text-muted);">Aturan tidak ditemukan.</div>';
                            } else {
                                data.forEach(item => {
                                    const isSelected = selectedTatibMap.has(item.id);
                                    const opt = document.createElement('div');
                                    opt.style.padding = '10px 14px';
                                    opt.style.cursor = isSelected ? 'default' : 'pointer';
                                    opt.style.opacity = isSelected ? '0.5' : '1';
                                    opt.style.borderBottom = '1px solid var(--border-color)';
                                    opt.style.fontSize = '0.8125rem';
                                    opt.innerHTML = `
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <div>
                                                <strong style="color: var(--primary);">[${item.kode}]</strong>
                                                <span style="font-weight: 600; color: var(--text-color); margin-left: 4px;">${item.nama_aturan}</span>
                                                ${isSelected ? '<span class="badge badge-success" style="font-size: 0.65rem; margin-left: 6px;">Sudah Dipilih</span>' : ''}
                                            </div>
                                            <span style="font-weight: 800; color: var(--danger); font-size: 0.75rem;">+${item.bobot_poin} Poin</span>
                                        </div>
                                        <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">
                                            Kategori: ${item.kategori} &bull; Tingkat: ${(item.tingkat || 'ringan').toUpperCase()}
                                        </div>
                                    `;
                                    if (!isSelected) {
                                        opt.addEventListener('mouseover', () => opt.style.background = 'rgba(59,130,246,0.06)');
                                        opt.addEventListener('mouseout', () => opt.style.background = 'transparent');
                                        opt.addEventListener('click', () => {
                                            selectedTatibMap.set(item.id, item);
                                            renderSelectedTatib();
                                            inputTatib.value = '';
                                            resultsTatib.style.display = 'none';
                                            inputTatib.focus();
                                        });
                                    }
                                    resultsTatib.appendChild(opt);
                                });
                            }
                            resultsTatib.style.display = 'block';
                        })
                        .catch(() => {
                            resultsTatib.style.display = 'none';
                        });
                }, 250);
            });
        }

        renderSelectedTatib();

        document.addEventListener('click', function (e) {
            if (resultsTatib && !resultsTatib.contains(e.target) && e.target !== inputTatib) {
                resultsTatib.style.display = 'none';
            }
        });

        // Live Search Filter Reset
        const searchInput = document.getElementById('searchPelanggaran');
        const clearBtn = document.getElementById('clearSearch');
        if (searchInput && clearBtn) {
            searchInput.addEventListener('input', function() {
                if (this.value) {
                    clearBtn.classList.add('visible');
                } else {
                    clearBtn.classList.remove('visible');
                }
            });
            clearBtn.addEventListener('click', function() {
                searchInput.value = '';
                clearBtn.classList.remove('visible');
                document.getElementById('filterForm').submit();
            });
        }
    });
</script>
@endpush
