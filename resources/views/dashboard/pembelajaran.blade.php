@extends('layouts.dashboard')

@section('title', 'Master Data — Pembelajaran — SAE')
@section('dash_title', 'Pembelajaran & Mata Pelajaran')

@section('content')
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-book-bookmark text-primary me-2"></i> Master Data — Pembelajaran
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Struktur kurikulum, alokasi jam mengajar, dan penugasan guru pengampu per rombel dari Dapodik.
            </p>
        </div>
        <div class="dash-banner-actions">
            <a href="{{ route('dashboard.dapodik') }}" class="btn btn-outline" style="padding: 9px 16px; font-size: 0.85rem;">
                <i class="fas fa-cloud-arrow-down me-1"></i> Tarik Data Dapodik
            </a>
        </div>
    </div>

    <!-- Summary Stats Grid -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                <i class="fas fa-book-open"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ number_format($summary['total'], 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Total Pembelajaran</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                <i class="fas fa-school"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ $summary['rombel'] }}
                </div>
                <div class="dash-stat-label">Rombel Terjadwal</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
                <i class="fas fa-chalkboard-user"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ $summary['guru'] }}
                </div>
                <div class="dash-stat-label">Guru Pengampu</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ number_format($summary['jam'], 0, ',', '.') }} JP
                </div>
                <div class="dash-stat-label">Total Jam / Minggu</div>
            </div>
        </div>
    </div>

    <!-- Toolbar & Filter -->
    <div class="card" style="padding: 16px; margin-bottom: 20px;">
        <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
            <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                <div class="toolbar-entries">
                    <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                    <select id="perPageSelect" class="per-page-select">
                        @foreach ([10, 15, 25, 50, 100] as $n)
                            <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}
                            </option>
                        @endforeach
                    </select>
                    <span>entri</span>
                </div>

                <select id="filterRombel" class="toolbar-filter-select">
                    <option value="">Semua Rombel</option>
                    @foreach ($filterRombel as $r)
                        <option value="{{ $r }}" {{ $rombel === $r ? 'selected' : '' }}>{{ $r }}
                        </option>
                    @endforeach
                </select>

                <select id="filterGuru" class="toolbar-filter-select">
                    <option value="">Semua Guru Pengampu</option>
                    @foreach ($filterGuru as $g)
                        <option value="{{ $g }}" {{ $guru === $g ? 'selected' : '' }}>{{ $g }}
                        </option>
                    @endforeach
                </select>

                <select id="filterStatus" class="toolbar-filter-select">
                    <option value="">Semua Status Kurikulum</option>
                    @foreach ($filterStatus as $st)
                        <option value="{{ $st }}" {{ $status === $st ? 'selected' : '' }}>{{ $st }}
                        </option>
                    @endforeach
                </select>

                @if ($q || $rombel || $guru || $status)
                    <a href="{{ route('dashboard.pembelajaran.index') }}" class="btn btn-outline"
                        style="padding: 7px 12px; font-size: 0.8rem;" title="Reset filter">
                        <i class="fas fa-undo me-1"></i> Reset
                    </a>
                @endif
            </div>

            <div class="live-search-wrap">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="liveSearch" placeholder="Cari mapel / guru / rombel..."
                    value="{{ $q }}" autocomplete="off">
                <button type="button" id="clearSearch" class="clear-search {{ $q ? 'visible' : '' }}"
                    title="Hapus pencarian">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Datatable Pembelajaran -->
    <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
        <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                    @php
                        $cols = [
                            ['nama_mata_pelajaran', 'Mata Pelajaran'],
                            ['nama_rombel', 'Rombel / Kelas'],
                            ['nama_guru', 'Guru Pengampu'],
                            ['jam_mengajar_per_minggu', 'Jam / Mg'],
                            ['status_di_kurikulum_str', 'Status Kurikulum'],
                        ];
                    @endphp
                    @foreach ($cols as [$key, $label])
                        <th class="sortable-th {{ $sort === $key ? 'sorted' : '' }}"
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; {{ $key === 'jam_mengajar_per_minggu' ? 'text-align: center;' : '' }}"
                            data-sort="{{ $key }}">
                            {{ $label }}
                            <span class="sort-icon">{!! $sort === $key ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                    @endforeach
                    <th
                        style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                        Aksi
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $item)
                    <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                        <td style="padding: 14px 18px; font-weight: 700; color: var(--text-color); font-size: 0.88rem;"
                            data-label="Mata Pelajaran">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div
                                    style="width: 32px; height: 32px; border-radius: 8px; background: rgba(99,102,241,0.12); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 0.82rem; flex-shrink: 0;">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 700; color: var(--text-color);">
                                        {{ $item->nama_mata_pelajaran }}</div>
                                    @if ($item->mata_pelajaran_id)
                                        <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace;">
                                            ID: {{ $item->mata_pelajaran_id }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td style="padding: 14px 18px; font-size: 0.84rem;" data-label="Rombel / Kelas">
                            <div style="font-weight: 600; color: var(--text-color);">{{ $item->nama_rombel ?: '-' }}</div>
                            @if ($item->tingkat)
                                <span class="badge badge-outline"
                                    style="font-size: 0.70rem; padding: 2px 6px; margin-top: 2px;">
                                    {{ $item->tingkat }}
                                </span>
                            @endif
                        </td>
                        <td style="padding: 14px 18px; font-size: 0.84rem;" data-label="Guru Pengampu">
                            @if ($item->nama_guru)
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span
                                        class="badge {{ $item->guru_gender === 'P' ? 'badge-danger' : 'badge-primary' }}"
                                        style="font-size: 0.68rem; padding: 2px 6px;">
                                        {{ $item->guru_gender ?: 'PTK' }}
                                    </span>
                                    <span
                                        style="font-weight: 600; color: var(--text-color);">{{ $item->nama_guru }}</span>
                                </div>
                                @if ($item->nuptk || $item->nip)
                                    <div
                                        style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace; margin-top: 2px;">
                                        {{ $item->nuptk ? 'NUPTK: ' . $item->nuptk : 'NIP: ' . $item->nip }}
                                    </div>
                                @endif
                            @else
                                <span style="color: var(--text-muted); font-style: italic;">Belum Ditugaskan</span>
                            @endif
                        </td>
                        <td style="padding: 14px 18px; text-align: center;" data-label="Jam / Mg">
                            <span class="badge"
                                style="background: rgba(245,158,11,0.12); color: #f59e0b; font-size: 0.78rem; padding: 3px 10px; font-weight: 700;">
                                {{ $item->jam_mengajar_per_minggu ?: 0 }} JP
                            </span>
                        </td>
                        <td style="padding: 14px 18px; font-size: 0.82rem;" data-label="Status Kurikulum">
                            <span class="badge badge-outline" style="font-size: 0.72rem; padding: 3px 8px;">
                                {{ $item->status_di_kurikulum_str ?: 'Wajib' }}
                            </span>
                        </td>
                        <td style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                            <div class="table-actions">
                                <button type="button" class="btn-icon" title="Lihat Detail Pembelajaran"
                                    onclick="openPembelajaranModal('{{ $item->pembelajaran_id }}')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6"
                            style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                            <i class="fas fa-folder-open mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                            <div>Belum ada data pembelajaran yang cocok.</div>
                            <div style="font-size: 0.78rem; margin-top: 6px;">
                                Sinkronisasi data Dapodik untuk mengisi tabel pembelajaran.
                            </div>
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

    {{-- Modal Detail Pembelajaran --}}
    <div id="pembelajaranModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 600px; width: 92%; max-height: 85vh; display: flex; flex-direction: column; margin: 0; border-radius: 14px; padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div>
                    <h3 id="pemModalTitle"
                        style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0;">Detail
                        Pembelajaran</h3>
                    <div id="pemModalSubtitle" style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">-
                    </div>
                </div>
                <button type="button" onclick="closePembelajaranModal()"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="pemModalLoading" style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin me-2" style="font-size: 1.4rem;"></i>
                <div>Memuat detail pembelajaran...</div>
            </div>

            <div id="pemModalContent" style="overflow-y: auto; flex: 1; display: none; font-size: 0.84rem;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted); width: 150px;">Mata Pelajaran</td>
                        <td id="pemMapel" style="font-weight: 700;">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">ID Mapel / Pembelajaran</td>
                        <td id="pemIdMapel" style="font-family: monospace; font-size: 0.78rem; color: var(--text-muted);">
                            -</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Rombongan Belajar</td>
                        <td id="pemRombel" style="font-weight: 600; color: var(--primary);">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Tingkat / Jurusan</td>
                        <td id="pemTingkat">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Kurikulum</td>
                        <td id="pemKurikulum">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Guru Pengampu</td>
                        <td id="pemGuru" style="font-weight: 600;">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">NUPTK / NIP Guru</td>
                        <td id="pemGuruNip" style="font-family: monospace;">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Kontak / Status Guru</td>
                        <td id="pemGuruKontak">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Alokasi Jam Mengajar</td>
                        <td id="pemJam">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Status di Kurikulum</td>
                        <td id="pemStatusKur">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Wali Kelas</td>
                        <td id="pemWali">-</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: var(--text-muted);">Ruang Belajar</td>
                        <td id="pemRuang">-</td>
                    </tr>
                </table>
            </div>

            <div
                style="margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color); text-align: right;">
                <button type="button" class="btn btn-outline" style="padding: 7px 16px; font-size: 0.82rem;"
                    onclick="closePembelajaranModal()">
                    Tutup
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function applyFilter(paramName, paramValue) {
            const url = new URL(window.location.href);
            if (paramValue) {
                url.searchParams.set(paramName, paramValue);
            } else {
                url.searchParams.delete(paramName);
            }
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }

        document.getElementById('perPageSelect').addEventListener('change', function() {
            applyFilter('perPage', this.value);
        });

        document.getElementById('filterRombel').addEventListener('change', function() {
            applyFilter('rombel', this.value);
        });

        document.getElementById('filterGuru').addEventListener('change', function() {
            applyFilter('guru', this.value);
        });

        document.getElementById('filterStatus').addEventListener('change', function() {
            applyFilter('status', this.value);
        });

        const liveSearchInput = document.getElementById('liveSearch');
        const clearSearchBtn = document.getElementById('clearSearch');
        let debounceTimer;

        liveSearchInput.addEventListener('input', function() {
            const val = this.value;
            clearSearchBtn.classList.toggle('visible', val.length > 0);
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                applyFilter('q', val.trim());
            }, 500);
        });

        clearSearchBtn.addEventListener('click', function() {
            liveSearchInput.value = '';
            this.classList.remove('visible');
            applyFilter('q', '');
        });

        document.querySelectorAll('.sortable-th').forEach(th => {
            th.addEventListener('click', function() {
                const sortKey = this.dataset.sort;
                const url = new URL(window.location.href);
                const currentSort = url.searchParams.get('sort');
                const currentDir = url.searchParams.get('sort_dir') || 'asc';
                let nextDir = 'asc';
                if (currentSort === sortKey) {
                    nextDir = currentDir === 'asc' ? 'desc' : 'asc';
                }
                url.searchParams.set('sort', sortKey);
                url.searchParams.set('sort_dir', nextDir);
                url.searchParams.delete('page');
                window.location.href = url.toString();
            });
        });

        function openPembelajaranModal(id) {
            const modal = document.getElementById('pembelajaranModal');
            const loading = document.getElementById('pemModalLoading');
            const content = document.getElementById('pemModalContent');
            modal.style.display = 'flex';
            loading.style.display = 'block';
            content.style.display = 'none';

            fetch(`{{ url('dashboard/master-data/pembelajaran') }}/${id}`)
                .then(r => r.json())
                .then(res => {
                    loading.style.display = 'none';
                    if (res.status === 'success' && res.data) {
                        const d = res.data;
                        document.getElementById('pemModalTitle').textContent = d.nama_mata_pelajaran || d
                            .mata_pelajaran_id_str || 'Pembelajaran';
                        document.getElementById('pemModalSubtitle').textContent = (d.nama_rombel ? 'Kelas ' + d
                            .nama_rombel : '') + (d.tingkat ? ' • ' + d.tingkat : '');
                        document.getElementById('pemMapel').textContent = d.nama_mata_pelajaran || d
                            .mata_pelajaran_id_str || '-';
                        const idArr = [
                            d.mata_pelajaran_id ? 'Mapel ID: ' + d.mata_pelajaran_id : null,
                            d.pembelajaran_id ? 'Pembelajaran ID: ' + d.pembelajaran_id : null
                        ].filter(Boolean);
                        document.getElementById('pemIdMapel').textContent = idArr.length > 0 ? idArr.join(' • ') : '-';
                        document.getElementById('pemRombel').textContent = d.nama_rombel || '-';
                        document.getElementById('pemTingkat').textContent = (d.tingkat || '-') + (d.jurusan ? ' / ' + d
                            .jurusan : '');
                        document.getElementById('pemKurikulum').textContent = d.kurikulum || '-';
                        document.getElementById('pemGuru').textContent = d.nama_guru || 'Belum Ditugaskan';
                        document.getElementById('pemGuruNip').textContent = (d.nuptk ? 'NUPTK: ' + d.nuptk : '') + (d
                            .nip ? ' | NIP: ' + d.nip : (!d.nuptk ? '-' : ''));
                        const guruKontakArr = [
                            d.guru_status ? d.guru_status : null,
                            d.guru_hp ? 'HP: ' + d.guru_hp : null,
                            d.guru_email ? 'Email: ' + d.guru_email : null
                        ].filter(Boolean);
                        document.getElementById('pemGuruKontak').textContent = guruKontakArr.length > 0 ? guruKontakArr
                            .join(' • ') : '-';
                        document.getElementById('pemJam').textContent = (d.jam_mengajar_per_minggu || 0) +
                            ' Jam Pelajaran (JP) / Minggu';
                        document.getElementById('pemStatusKur').textContent = d.status_di_kurikulum_str || 'Wajib';
                        document.getElementById('pemWali').textContent = d.wali_kelas || '-';
                        document.getElementById('pemRuang').textContent = d.ruang || '-';
                        content.style.display = 'block';
                    }
                })
                .catch(() => {
                    loading.innerHTML =
                        '<div style="color: #ef4444;"><i class="fas fa-exclamation-triangle me-2"></i>Gagal memuat detail pembelajaran.</div>';
                });
        }

        function closePembelajaranModal() {
            document.getElementById('pembelajaranModal').style.display = 'none';
        }

        document.getElementById('pembelajaranModal').addEventListener('click', function(e) {
            if (e.target === this) closePembelajaranModal();
        });
    </script>
@endpush
