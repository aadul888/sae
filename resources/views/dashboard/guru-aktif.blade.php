@extends('layouts.dashboard')

@section('title', 'Manajemen Data — Guru Aktif — SAE')
@section('dash_title', 'Guru & Tendik Aktif')

@section('content')
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-chalkboard-user text-primary me-2"></i> Manajemen Data — Guru Aktif
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Direktori Guru &amp; Tenaga Pendidik aktif bersumber dari tabel <strong>GTK</strong> (Dapodik).
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
                <i class="fas fa-users"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ number_format($summary['total'], 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Total Guru Aktif</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                <i class="fas fa-id-badge"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ $summary['pns'] }}
                </div>
                <div class="dash-stat-label">PNS / ASN</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ $summary['non_pns'] }}
                </div>
                <div class="dash-stat-label">Non-PNS / Honorer</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
                <i class="fas fa-venus-mars"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ $summary['laki'] }} / {{ $summary['perempuan'] }}
                </div>
                <div class="dash-stat-label">Laki / Perempuan</div>
            </div>
        </div>
    </div>

    <!-- Toolbar & Filter -->
    <div class="card" style="padding: 16px; margin-bottom: 20px;">
        <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
            <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                <div style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; color: var(--text-muted);">
                    <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                    <select id="perPageSelect" class="per-page-select">
                        @foreach ([10, 15, 25, 50, 100] as $n)
                            <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}
                            </option>
                        @endforeach
                    </select>
                    <span>entri</span>
                </div>

                <select id="filterJenis" class="form-control"
                    style="width: auto; min-width: 150px; padding: 7px 12px; font-size: 0.82rem; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg, rgba(255,255,255,0.05)); color: var(--text-color);">
                    <option value="">Semua Jenis PTK</option>
                    @foreach ($filterJenis as $j)
                        <option value="{{ $j }}" {{ $jenis === $j ? 'selected' : '' }}>{{ $j }}
                        </option>
                    @endforeach
                </select>

                <select id="filterStatus" class="form-control"
                    style="width: auto; min-width: 160px; padding: 7px 12px; font-size: 0.82rem; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg, rgba(255,255,255,0.05)); color: var(--text-color);">
                    <option value="">Semua Status Kepegawaian</option>
                    @foreach ($filterStatus as $s)
                        <option value="{{ $s }}" {{ $status === $s ? 'selected' : '' }}>{{ $s }}
                        </option>
                    @endforeach
                </select>

                <select id="filterGender" class="form-control"
                    style="width: auto; min-width: 120px; padding: 7px 12px; font-size: 0.82rem; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg, rgba(255,255,255,0.05)); color: var(--text-color);">
                    <option value="">Semua Gender</option>
                    <option value="L" {{ $gender === 'L' ? 'selected' : '' }}>Laki-Laki (L)</option>
                    <option value="P" {{ $gender === 'P' ? 'selected' : '' }}>Perempuan (P)</option>
                </select>

                @if ($q || $jenis || $status || $gender)
                    <a href="{{ route('dashboard.guru-aktif.index') }}" class="btn btn-outline"
                        style="padding: 7px 12px; font-size: 0.8rem;" title="Reset filter">
                        <i class="fas fa-undo me-1"></i> Reset
                    </a>
                @endif
            </div>

            <div class="live-search-wrap">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="liveSearch" placeholder="Cari nama / NUPTK / NIP..." value="{{ $q }}"
                    autocomplete="off">
                <button type="button" id="clearSearch" class="clear-search {{ $q ? 'visible' : '' }}"
                    title="Hapus pencarian">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Datatable Guru & Tendik -->
    <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
        <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                    @php
                        $cols = [
                            ['nama', 'Nama GTK'],
                            ['nuptk', 'NUPTK / NIP'],
                            ['jenis_kelamin', 'L/P'],
                            ['jenis_ptk_id_str', 'Jenis PTK'],
                            ['status_kepegawaian_id_str', 'Status'],
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
                        Pendidikan / Mapel
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
                            data-label="Nama GTK">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div
                                    style="width: 32px; height: 32px; border-radius: 8px; background: rgba(99,102,241,0.12); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 0.82rem; flex-shrink: 0;">
                                    <i class="fas fa-chalkboard-user"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 700; color: var(--text-color);">{{ $item->nama }}</div>
                                    @if ($item->email)
                                        <div style="font-size: 0.72rem; color: var(--text-muted);">
                                            {{ $item->email }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td style="padding: 14px 18px; font-family: monospace; font-size: 0.84rem; color: var(--primary);"
                            data-label="NUPTK / NIP">
                            <div>{{ $item->nuptk ?: '-' }}</div>
                            @if ($item->nip)
                                <div style="font-size: 0.72rem; color: var(--text-muted);">NIP: {{ $item->nip }}</div>
                            @endif
                        </td>
                        <td style="padding: 14px 18px; text-align: center;" data-label="L/P">
                            <span class="badge {{ $item->jenis_kelamin === 'L' ? 'badge-primary' : 'badge-outline' }}"
                                style="font-size: 0.72rem; padding: 2px 7px;">
                                {{ $item->jenis_kelamin ?: '-' }}
                            </span>
                        </td>
                        <td style="padding: 14px 18px; font-size: 0.84rem; color: var(--text-color);"
                            data-label="Jenis PTK">
                            {{ $item->jenis_ptk ?: '-' }}
                        </td>
                        <td style="padding: 14px 18px; font-size: 0.82rem;" data-label="Status">
                            <span
                                class="badge {{ str_contains(strtoupper($item->status_kepegawaian ?? ''), 'PNS') ? 'badge-primary' : 'badge-outline' }}"
                                style="font-size: 0.72rem; padding: 3px 8px;">
                                {{ $item->status_kepegawaian ?: '-' }}
                            </span>
                        </td>
                        <td style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);"
                            data-label="Pendidikan / Mapel">
                            <div>{{ $item->pendidikan_terakhir ?: '-' }}</div>
                            @if ($item->bidang_studi_terakhir)
                                <div style="font-size: 0.72rem; color: var(--text-muted);">
                                    {{ $item->bidang_studi_terakhir }}</div>
                            @endif
                        </td>
                        <td style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                            <div class="table-actions">
                                <button type="button" class="btn-icon" title="Lihat Profil Lengkap"
                                    onclick="openBiodataGuruModal('{{ $item->ptk_id }}')">
                                    <i class="fas fa-id-card"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7"
                            style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                            <i class="fas fa-folder-open mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                            <div>Belum ada data Guru &amp; Tendik yang cocok.</div>
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

    {{-- Modal Profil GTK --}}
    <div id="gtkModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 640px; width: 92%; max-height: 85vh; display: flex; flex-direction: column; margin: 0; border-radius: 14px; padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div>
                    <h3 id="gtkJabatan"
                        style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0;">Profil Guru
                        &amp; Tendik</h3>
                    <div id="gtkSubtitle" style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">-</div>
                </div>
                <button type="button" onclick="closeBiodataGuruModal()"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="gtkLoading" style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin me-2" style="font-size: 1.4rem;"></i>
                <div>Memuat profil GTK...</div>
            </div>

            <div id="gtkContent" style="overflow-y: auto; flex: 1; display: none; font-size: 0.84rem;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted); width: 140px;">Nama Lengkap</td>
                        <td id="gtkJkNama" style="font-weight: 700;">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">NUPTK / NIP</td>
                        <td id="gtkJkNuptk" style="font-family: monospace; color: var(--primary);">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">NIK</td>
                        <td id="gtkJkNik" style="font-family: monospace;">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Jenis Kelamin</td>
                        <td id="gtkJkGender">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Tempat, Tgl Lahir</td>
                        <td id="gtkJkTtl">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Status Kepegawaian</td>
                        <td id="gtkJkStatus">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Pendidikan Terakhir</td>
                        <td id="gtkJkPend">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Bidang Studi / Mapel</td>
                        <td id="gtkJkMapel">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">No. HP / WA</td>
                        <td id="gtkJkHp">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Alamat</td>
                        <td id="gtkJkAlamat">-</td>
                    </tr>
                </table>
            </div>

            <div
                style="display: flex; justify-content: flex-end; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color);">
                <button type="button" class="btn btn-outline" onclick="closeBiodataGuruModal()"
                    style="padding: 8px 18px; font-size: 0.82rem;">Tutup</button>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/guru-aktif.js') }}?v={{ filemtime(public_path('js/guru-aktif.js')) }}"></script>
    @endpush
@endsection
