@extends('layouts.dashboard')

@section('title', 'Buku Agenda Surat Masuk — SAE')
@section('dash_title', 'Surat Masuk')

@section('content')
    <div id="suratMasukContainer" data-store-url="{{ route('dashboard.persuratan.masuk.store') }}"
        data-base-url="{{ url('/dashboard/persuratan/masuk') }}">

        <!-- 1. Header Banner & Actions -->
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div
                    style="width: 48px; height: 48px; border-radius: 12px; background: rgba(59,130,246,0.12); color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0;">
                    <i class="fas fa-inbox"></i>
                </div>
                <div>
                    <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--text-color); margin: 0;">
                        Buku Agenda Surat Masuk
                    </h2>
                    <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">
                        Pencatatan surat masuk, lembar disposisi pimpinan, dan pengarsipan berkas scan ke harddisk.
                    </div>
                </div>
            </div>

            <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <a href="{{ route('dashboard.persuratan.keluar.index') }}" class="btn btn-outline"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem;"
                    title="Buku Agenda Surat Keluar">
                    <i class="fas fa-paper-plane text-success"></i>
                </a>
                <a href="{{ route('dashboard.persuratan.pengaturan.index') }}" class="btn btn-outline"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem;"
                    title="Pengaturan Persuratan &amp; Harddisk">
                    <i class="fas fa-sliders text-muted"></i>
                </a>
                <button type="button" class="btn btn-outline" id="btnOpenCetakAgendaMasuk"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem;"
                    title="Cetak Buku Agenda Surat Masuk">
                    <i class="fas fa-print text-primary"></i>
                </button>
                @if ($canCreate)
                    <button type="button" class="btn btn-primary" id="btnOpenCreateMasuk"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem;"
                        title="Catat Surat Masuk Baru">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>

        <!-- 2. Flash Messages -->
        @if (session('success'))
            <div
                style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: #10b981; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 0.86rem; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-check-circle"></i>
                <div>{{ session('success') }}</div>
            </div>
        @endif
        @if (session('error'))
            <div
                style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #ef4444; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 0.86rem; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-circle-exclamation"></i>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        <!-- 3. Stat Grid Baku SAE -->
        <div class="dash-stat-grid" style="margin-bottom: 20px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: #3b82f6;">
                    <i class="fas fa-inbox"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($stats['total'] ?? 0) }}</div>
                    <div class="dash-stat-label">Total Surat Masuk</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                    <i class="fas fa-clock-rotate-left"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($stats['menunggu'] ?? 0) }}</div>
                    <div class="dash-stat-label">Menunggu Disposisi</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($stats['proses'] ?? 0) }}</div>
                    <div class="dash-stat-label">Sedang Diproses</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($stats['selesai'] ?? 0) }}</div>
                    <div class="dash-stat-label">Selesai / Tindak Lanjut</div>
                </div>
            </div>
        </div>

        <!-- Card Interaktif: Nomor Surat Masuk Terakhir (Diatas Datatable) -->
        <div class="card card-nomor-terakhir"
            style="padding: 16px 20px; margin-bottom: 20px; border-radius: 14px; border: 1px solid rgba(59,130,246,0.25); background: linear-gradient(135deg, rgba(59,130,246,0.06) 0%, rgba(99,102,241,0.03) 100%); display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
            <div style="display: flex; align-items: center; gap: 16px; min-width: 260px; flex: 1;">
                <div
                    style="width: 48px; height: 48px; border-radius: 12px; background: rgba(59,130,246,0.15); color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0;">
                    <i class="fas fa-inbox"></i>
                </div>
                <div style="min-width: 0;">
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <span
                            style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">
                            Nomor Surat Masuk Terakhir
                        </span>
                        <span class="badge badge-info"
                            style="font-size: 0.68rem; padding: 2px 8px; border-radius: 12px;">Agenda Masuk</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-top: 4px; flex-wrap: wrap;">
                        <span id="labelNomorSuratMasuk"
                            style="font-family: 'JetBrains Mono', 'Fira Code', 'Courier New', monospace; font-size: 1.15rem; font-weight: 800; color: #2563eb; background: rgba(59,130,246,0.1); padding: 4px 12px; border-radius: 8px; border: 1px solid rgba(59,130,246,0.25); letter-spacing: 0.5px;">
                            {{ $stats['nomor_terakhir'] ?? '-' }}
                        </span>
                    </div>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <button type="button" class="btn btn-outline"
                    onclick="copyNomorSurat(this, '{{ $stats['nomor_terakhir'] ?? '' }}')"
                    style="padding: 8px 14px; font-size: 0.84rem; border-radius: 8px; display: inline-flex; align-items: center; gap: 7px; background: var(--card-bg);"
                    title="Salin nomor surat masuk terakhir ke clipboard">
                    <i class="fas fa-copy text-primary"></i> <span>Salin Nomor</span>
                </button>

                @if (($stats['nomor_terakhir'] ?? '-') !== '-')
                    <button type="button" class="btn btn-primary"
                        onclick="filterByNomorSurat('{{ $stats['nomor_terakhir'] }}')"
                        style="padding: 8px 14px; font-size: 0.84rem; border-radius: 8px; display: inline-flex; align-items: center; gap: 7px;"
                        title="Filter / temukan surat ini pada datatable">
                        <i class="fas fa-search"></i> <span>Cari di Datatable</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- Unified Datatable Surat Masuk (Toolbar + Table + Pagination dalam 1 Card Terpadu) -->
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <!-- 1. Toolbar Header (Entries, Filters & Live Search) -->
            <div class="datatable-toolbar">
                <div
                    style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                        <div class="toolbar-entries">
                            <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                            <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                                @foreach ([10, 15, 25, 50, 100] as $n)
                                    <option value="{{ $n }}" {{ ($perPage ?? 25) == $n ? 'selected' : '' }}>
                                        {{ $n }}</option>
                                @endforeach
                            </select>
                            <span>entri</span>
                        </div>

                        <select id="filterStatus" name="status" class="toolbar-filter-select" style="height: 38px;">
                            <option value="">Semua Status</option>
                            <option value="menunggu_disposisi"
                                {{ ($status ?? '') === 'menunggu_disposisi' ? 'selected' : '' }}>Menunggu Disposisi
                            </option>
                            <option value="diproses" {{ ($status ?? '') === 'diproses' ? 'selected' : '' }}>Sedang
                                Diproses
                            </option>
                            <option value="selesai" {{ ($status ?? '') === 'selesai' ? 'selected' : '' }}>Selesai</option>
                            <option value="diarsipkan" {{ ($status ?? '') === 'diarsipkan' ? 'selected' : '' }}>Diarsipkan
                            </option>
                        </select>

                        <select id="filterPtk" name="ptk_id" class="toolbar-filter-select" style="height: 38px;">
                            <option value="">Semua Tujuan Disposisi</option>
                            @foreach ($ptkList as $ptk)
                                <option value="{{ $ptk->ptk_id }}"
                                    {{ ($filterPtk ?? '') === $ptk->ptk_id ? 'selected' : '' }}>
                                    {{ $ptk->nama }}
                                </option>
                            @endforeach
                        </select>

                        @if (!empty($q) || !empty($status) || !empty($filterPtk))
                            <a href="{{ route('dashboard.persuratan.masuk.index') }}"
                                class="btn btn-outline btn-reset-filter"
                                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;"
                                title="Reset filter" data-live-reset>
                                <i class="fas fa-undo"></i>
                            </a>
                        @endif
                    </div>

                    <div class="live-search-wrap" style="height: 38px;">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="liveSearch" name="q"
                            placeholder="Cari nomor surat / perihal / pengirim..." value="{{ $q ?? '' }}"
                            autocomplete="off">
                        <button type="button" id="clearSearch" class="clear-search {{ !empty($q) ? 'visible' : '' }}"
                            title="Hapus pencarian">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. Konten Datatable Responsive Stack -->
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                            <th class="sortable-th {{ ($sort ?? '') === 'nomor_surat' ? 'sorted' : '' }}"
                                data-sort="nomor_surat"
                                style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                No. Surat
                                <span class="sort-icon">{!! ($sort ?? '') === 'nomor_surat' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th
                                style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Pengirim &amp; Perihal
                            </th>
                            <th class="sortable-th {{ ($sort ?? '') === 'tanggal_surat' ? 'sorted' : '' }}"
                                data-sort="tanggal_surat"
                                style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px;">
                                Tgl Surat / Masuk
                                <span class="sort-icon">{!! ($sort ?? '') === 'tanggal_surat' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th
                                style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 100px; text-align: center;">
                                Arsip HDD
                            </th>
                            <th
                                style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 120px; text-align: center;">
                                Status
                            </th>
                            <th
                                style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 120px; text-align: center;">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 16px;">
                                    <div
                                        style="font-family: monospace; font-weight: 700; font-size: 0.88rem; color: var(--text-color);">
                                        {{ $item->nomor_surat }}
                                    </div>
                                </td>

                                <td style="padding: 12px 16px;">
                                    <div
                                        style="font-weight: 600; font-size: 0.84rem; color: var(--text-color); margin-bottom: 2px;">
                                        {{ $item->perihal }}
                                    </div>
                                    <div style="font-size: 0.76rem; color: var(--text-muted);">
                                        <i class="fas fa-building text-primary me-1"></i> Dari:
                                        <strong>{{ $item->pengirim_asal ?: '-' }}</strong>
                                    </div>
                                    @if ($item->latestDisposisi)
                                        <div style="margin-top: 5px;">
                                            <span class="badge-compact"
                                                style="background: rgba(37,99,235,0.08); color: #2563eb; font-size: 0.72rem; border: 1px solid rgba(37,99,235,0.2);">
                                                <i class="fas fa-share-nodes me-1"></i> Disp:
                                                <strong>{{ $item->latestDisposisi->disposisi_ke }}</strong>
                                                @if ($item->latestDisposisi->ptk)
                                                    <small>({{ $item->latestDisposisi->ptk->jabatan_ptk_id_str ?: $item->latestDisposisi->ptk->jenis_ptk_id_str }})</small>
                                                @endif
                                                &bull; {{ $item->latestDisposisi->instruksi }}
                                            </span>
                                        </div>
                                    @endif
                                </td>

                                <td style="padding: 12px 16px;">
                                    <div style="font-size: 0.8rem; color: var(--text-color);">
                                        <i class="far fa-calendar-alt text-muted me-1"></i>
                                        {{ date('d/m/Y', strtotime($item->tanggal_surat)) }}
                                    </div>
                                    @if ($item->tanggal_diterima)
                                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                                            Terima: {{ date('d/m/Y', strtotime($item->tanggal_diterima)) }}
                                        </div>
                                    @endif
                                </td>

                                <td style="padding: 12px 16px; text-align: center;">
                                    @if (!empty($item->file_path))
                                        <button type="button" class="btn-icon text-primary btn-view-dokumen"
                                            title="Pratinjau Berkas dari HDD"
                                            data-view-url="{{ route('dashboard.persuratan.dokumen.view', $item->id) }}"
                                            data-download-url="{{ route('dashboard.persuratan.dokumen.download', $item->id) }}"
                                            data-title="Surat Masuk: {{ $item->nomor_surat }} - {{ $item->perihal }}"
                                            data-mime="{{ str_contains($item->file_path, '.pdf') ? 'application/pdf' : 'image/jpeg' }}">
                                            <i class="fas fa-file-pdf" style="font-size: 1.1rem; color: #ef4444;"></i>
                                        </button>
                                    @else
                                        <span style="font-size: 0.72rem; color: var(--text-muted); font-style: italic;">
                                            Tanpa Berkas
                                        </span>
                                    @endif
                                </td>

                                <td style="padding: 12px 16px; text-align: center;">
                                    @php
                                        $badgeClass = match ($item->status) {
                                            'menunggu_disposisi' => 'badge-warning',
                                            'diproses' => 'badge-info',
                                            'selesai' => 'badge-success',
                                            default => 'badge-secondary',
                                        };
                                        $badgeLabel = match ($item->status) {
                                            'menunggu_disposisi' => 'Menunggu Disp.',
                                            'diproses' => 'Diproses',
                                            'selesai' => 'Selesai',
                                            'diarsipkan' => 'Diarsipkan',
                                            default => ucfirst($item->status),
                                        };
                                    @endphp
                                    <span class="badge-compact {{ $badgeClass }}">
                                        {{ $badgeLabel }}
                                    </span>
                                </td>

                                <td style="padding: 12px 16px; text-align: center;">
                                    <div class="table-actions" style="display: flex; gap: 4px; justify-content: center;">
                                        <!-- Tombol Disposisi -->
                                        <button type="button" class="btn-icon text-warning btn-disposisi-masuk"
                                            title="Buat Lembar Disposisi" data-id="{{ $item->id }}"
                                            data-nomor="{{ $item->nomor_surat }}" data-perihal="{{ $item->perihal }}">
                                            <i class="fas fa-share-nodes"></i>
                                        </button>

                                        <!-- Tombol Cetak Lembar Disposisi -->
                                        <a href="{{ route('dashboard.persuratan.masuk.disposisi.cetak', $item->id) }}"
                                            target="_blank" class="btn-icon text-info"
                                            title="Cetak Lembar Disposisi Resmi">
                                            <i class="fas fa-print"></i>
                                        </a>

                                        @if ($canUpdate)
                                            <button type="button" class="btn-icon btn-edit-masuk"
                                                title="Edit Surat Masuk" data-id="{{ $item->id }}"
                                                data-nomor="{{ $item->nomor_surat }}"
                                                data-perihal="{{ $item->perihal }}"
                                                data-pengirim="{{ $item->pengirim_asal }}"
                                                data-tgl-surat="{{ $item->tanggal_surat }}"
                                                data-tgl-diterima="{{ $item->tanggal_diterima }}"
                                                data-status="{{ $item->status }}"
                                                data-keterangan="{{ $item->keterangan }}"
                                                data-file-path="{{ $item->file_path }}"
                                                data-file-name="{{ $item->file_name_original }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        @endif

                                        @if ($canDelete)
                                            <form action="{{ route('dashboard.persuratan.masuk.destroy', $item->id) }}"
                                                method="POST" data-confirm="delete"
                                                data-name="Surat Masuk No. {{ $item->nomor_surat }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon text-danger"
                                                    title="Hapus Surat Masuk">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6"
                                    style="text-align: center; padding: 40px 16px; color: var(--text-muted);">
                                    <i class="fas fa-inbox"
                                        style="font-size: 2.2rem; opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                                    Tidak ada data surat masuk yang sesuai kriteria pencarian.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 3. Footer Paginasi Terpadu Datatable -->
            @if ($items->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $items])
                </div>
            @endif
        </div>

        @include('dashboard.persuratan.partials.modal-surat-masuk')
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modalAgenda = document.getElementById('modalAgendaMasuk');
            const btnOpenAgenda = document.getElementById('btnOpenCetakAgendaMasuk');
            const btnCloseAgenda = document.getElementById('btnCloseModalAgendaMasuk');
            const btnCancelAgenda = document.getElementById('btnCancelModalAgendaMasuk');

            if (btnOpenAgenda && modalAgenda) {
                btnOpenAgenda.addEventListener('click', () => {
                    modalAgenda.style.display = 'flex';
                    document.body.classList.add('modal-open');
                });
            }

            function closeAgendaModal() {
                if (!modalAgenda) return;
                modalAgenda.style.display = 'none';
                document.body.classList.remove('modal-open');
            }

            if (btnCloseAgenda) btnCloseAgenda.addEventListener('click', closeAgendaModal);
            if (btnCancelAgenda) btnCancelAgenda.addEventListener('click', closeAgendaModal);
            if (modalAgenda) {
                modalAgenda.addEventListener('click', (e) => {
                    if (e.target === modalAgenda) closeAgendaModal();
                });
            }
        });

        function copyNomorSurat(btn, text) {
            if (!text || text === '-') return;
            const oldHtml = btn.innerHTML;
            const updateBtn = () => {
                btn.innerHTML =
                    '<i class="fas fa-check text-success"></i> <span style="color:#10b981;">Tersalin!</span>';
                setTimeout(() => {
                    btn.innerHTML = oldHtml;
                }, 2000);
            };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(updateBtn).catch(() => fallbackCopy(text, updateBtn));
            } else {
                fallbackCopy(text, updateBtn);
            }
        }

        function fallbackCopy(text, cb) {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy');
                if (cb) cb();
            } catch (e) {}
            document.body.removeChild(ta);
        }

        function filterByNomorSurat(text) {
            if (!text || text === '-') return;
            const input = document.getElementById('liveSearch');
            if (input) {
                input.value = text;
                input.focus();
                input.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
                const clearBtn = document.getElementById('clearSearch');
                if (clearBtn) clearBtn.classList.add('visible');
            } else {
                const url = new URL(window.location.href);
                url.searchParams.set('q', text);
                url.searchParams.set('page', '1');
                window.location.href = url.toString();
            }
        }
    </script>
    <script
        src="{{ asset('js/persuratan-masuk.js') }}?v={{ file_exists(public_path('js/persuratan-masuk.js')) ? filemtime(public_path('js/persuratan-masuk.js')) : time() }}">
    </script>
@endpush
