@extends('layouts.dashboard')

@section('title', 'Manajemen Data — Peserta Didik Aktif — SAE')
@section('dash_title', 'Peserta Didik Aktif')

@section('content')
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-user-graduate text-primary me-2"></i> Manajemen Data — Peserta Didik Aktif
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Direktori data peserta didik aktif bersumber langsung dari tabel <strong>Peserta Didik</strong> (Dapodik).
            </p>
        </div>
        <div class="dash-banner-actions">
            <a href="{{ route('dashboard.dapodik') }}" class="btn btn-outline" style="padding: 9px 16px; font-size: 0.85rem;">
                <i class="fas fa-cloud-arrow-down me-1"></i> Tarik Data Dapodik
            </a>
        </div>
    </div>

    <!-- Summary Stats Grid -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ number_format($summary['total'], 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Total Peserta Didik Aktif</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
                <i class="fas fa-mars"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ number_format($summary['laki'], 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Laki-Laki</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(236,72,153,0.15); color: #ec4899;">
                <i class="fas fa-venus"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ number_format($summary['perempuan'], 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Perempuan</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                <i class="fas fa-chalkboard-user"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ $summary['rombel'] }}
                </div>
                <div class="dash-stat-label">Total Rombel</div>
            </div>
        </div>
    </div>

    <!-- Toolbar & Filter -->
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

                <select id="filterRombel" class="form-control"
                    style="width: auto; min-width: 150px; padding: 7px 12px; font-size: 0.82rem; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg, rgba(255,255,255,0.05)); color: var(--text-color);">
                    <option value="">Semua Rombel</option>
                    @foreach ($filterRombel as $r)
                        <option value="{{ $r }}" {{ $rombel === $r ? 'selected' : '' }}>{{ $r }}
                        </option>
                    @endforeach
                </select>

                <select id="filterGender" class="form-control"
                    style="width: auto; min-width: 130px; padding: 7px 12px; font-size: 0.82rem; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg, rgba(255,255,255,0.05)); color: var(--text-color);">
                    <option value="">Semua Gender</option>
                    <option value="L" {{ $gender === 'L' ? 'selected' : '' }}>Laki-Laki (L)</option>
                    <option value="P" {{ $gender === 'P' ? 'selected' : '' }}>Perempuan (P)</option>
                </select>

                @if ($q || $rombel || $gender)
                    <a href="{{ route('dashboard.peserta-didik-aktif.index') }}" class="btn btn-outline"
                        style="padding: 7px 12px; font-size: 0.8rem;" title="Reset filter">
                        <i class="fas fa-undo me-1"></i> Reset
                    </a>
                @endif
            </div>

            <div class="live-search-wrap">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="liveSearch" placeholder="Cari nama / NISN / NIK..." value="{{ $q }}"
                    autocomplete="off">
                <button type="button" id="clearSearch" class="clear-search {{ $q ? 'visible' : '' }}"
                    title="Hapus pencarian">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Datatable Peserta Didik -->
    <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
        <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                    @php
                        $cols = [
                            ['nama', 'Nama Lengkap'],
                            ['nisn', 'NISN / NIPD'],
                            ['jenis_kelamin', 'L/P'],
                            ['nama_rombel', 'Rombel Kelas'],
                            ['tingkat_pendidikan_id', 'Tingkat'],
                        ];
                    @endphp
                    @foreach ($cols as [$key, $label])
                        <th class="sortable-th {{ $sort === $key ? 'sorted' : '' }}"
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; {{ $key === 'jenis_kelamin' ? 'text-align: center;' : '' }}"
                            data-sort="{{ $key }}">
                            {{ $label }}
                            <span class="sort-icon">{!! $sort === $key ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                    @endforeach
                    <th
                        style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Tempat, Tanggal Lahir
                    </th>
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
                            data-label="Nama Lengkap">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div
                                    style="width: 32px; height: 32px; border-radius: 8px; background: rgba(99,102,241,0.12); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 0.82rem; flex-shrink: 0;">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 700; color: var(--text-color);">{{ $item->nama }}</div>
                                    @if ($item->nik)
                                        <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace;">
                                            NIK: {{ $item->nik }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td style="padding: 14px 18px; font-family: monospace; font-size: 0.84rem; color: var(--primary);"
                            data-label="NISN / NIPD">
                            <div>{{ $item->nisn ?: '-' }}</div>
                            @if ($item->nipd)
                                <div style="font-size: 0.72rem; color: var(--text-muted);">NIPD: {{ $item->nipd }}</div>
                            @endif
                        </td>
                        <td style="padding: 14px 18px; text-align: center;" data-label="L/P">
                            <span class="badge {{ $item->jenis_kelamin === 'L' ? 'badge-primary' : 'badge-danger' }}"
                                style="font-size: 0.72rem; padding: 2px 7px;">
                                {{ $item->jenis_kelamin ?: '-' }}
                            </span>
                        </td>
                        <td style="padding: 14px 18px; font-weight: 600; color: var(--text-color); font-size: 0.84rem;"
                            data-label="Rombel Kelas">
                            <span class="badge badge-outline" style="font-size: 0.76rem; padding: 3px 8px;">
                                {{ $item->nama_rombel ?: '-' }}
                            </span>
                        </td>
                        <td style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);"
                            data-label="Tingkat">
                            {{ $item->tingkat_pendidikan_id ?: '-' }}
                        </td>
                        <td style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);"
                            data-label="Tempat, Tanggal Lahir">
                            {{ implode(', ', array_filter([$item->tempat_lahir, $item->tanggal_lahir])) ?: '-' }}
                        </td>
                        <td style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                            <div class="table-actions">
                                <button type="button" class="btn-icon" title="Lihat Biodata Lengkap"
                                    onclick="openBiodataPesertaDidikModal('{{ $item->peserta_didik_id }}')">
                                    <i class="fas fa-id-card"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7"
                            style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                            <i class="fas fa-user-slash mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                            <div>Belum ada data peserta didik yang cocok.</div>
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

    {{-- Modal Biodata Peserta Didik --}}
    <div id="biodataModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 640px; width: 92%; max-height: 85vh; display: flex; flex-direction: column; margin: 0; border-radius: 14px; padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div>
                    <h3 id="bioNama" style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0;">
                        Biodata Peserta Didik</h3>
                    <div id="bioRombel" style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">-</div>
                </div>
                <button type="button" onclick="closeBiodataModal()"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="bioLoading" style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin me-2" style="font-size: 1.4rem;"></i>
                <div>Memuat biodata peserta didik...</div>
            </div>

            <div id="bioContent" style="overflow-y: auto; flex: 1; display: none; font-size: 0.84rem;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted); width: 140px;">NISN / NIPD</td>
                        <td id="bioNisn" style="font-weight: 600; font-family: monospace; color: var(--primary);">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">NIK</td>
                        <td id="bioNik" style="font-family: monospace;">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Jenis Kelamin</td>
                        <td id="bioJk">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Tempat, Tgl Lahir</td>
                        <td id="bioTtl">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Agama</td>
                        <td id="bioAgama">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Nama Ayah</td>
                        <td id="bioAyah">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Nama Ibu</td>
                        <td id="bioIbu">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">No. HP / WA</td>
                        <td id="bioHp">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Alamat</td>
                        <td id="bioAlamat">-</td>
                    </tr>
                </table>
            </div>

            <div
                style="display: flex; justify-content: flex-end; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color);">
                <button type="button" class="btn btn-outline" onclick="closeBiodataModal()"
                    style="padding: 8px 18px; font-size: 0.82rem;">Tutup</button>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/peserta-didik-aktif.js') }}?v={{ filemtime(public_path('js/peserta-didik-aktif.js')) }}">
        </script>
    @endpush
@endsection
