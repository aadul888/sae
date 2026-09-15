@extends('layouts.dashboard')

@section('title', 'Rekap Tanggapan: ' . $formulir->judul . ' — SAE')
@section('dash_title', 'Formulir & Survei')

@section('content')
    <!-- Banner Header -->
    <div class="dash-banner">
        <div>
            <div style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 4px;">
                <a href="{{ route('dashboard.formulir.index') }}"
                    style="color: var(--primary); text-decoration: none;">Formulir &amp; Survei</a>
                <i class="fas fa-chevron-right mx-1" style="font-size: 0.7rem;"></i> Rekap Tanggapan
            </div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-inbox text-primary me-2"></i> {{ $formulir->judul }}
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
                Total <strong>{{ $formulir->respon_count }} tanggapan</strong> terekam. Unduh data langsung ke format
                spreadsheet Excel.
            </p>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="{{ route('dashboard.formulir.export-csv', $formulir->id) }}" class="btn btn-primary"
                style="padding: 9px 16px; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-file-excel"></i> Ekspor CSV / Excel
            </a>
            <a href="{{ $formulir->public_url }}" target="_blank" class="btn btn-outline"
                style="padding: 9px 14px; font-size: 0.85rem;">
                <i class="fas fa-external-link-alt me-1"></i> Buka Formulir
            </a>
            <a href="{{ route('dashboard.formulir.index') }}" class="btn btn-outline"
                style="padding: 9px 14px; font-size: 0.85rem;">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Alert Notifikasi Flash -->
    @if (session('success'))
        <div class="alert alert-success"
            style="padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;">
            <i class="fas fa-circle-check me-1"></i> {{ session('success') }}
        </div>
    @endif

    <!-- Statistik & Analisis Pilihan/Rating -->
    @if (!empty($fieldStats))
        <h3
            style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-chart-pie text-primary"></i> Ringkasan Pola Tanggapan
        </h3>
        <div class="stat-bars-grid">
            @foreach ($fieldStats as $fieldId => $stat)
                <div class="stat-bar-card">
                    <div class="stat-bar-title">
                        <span>{{ $stat['label'] }}</span>
                        <span style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted);">{{ $stat['total'] }}
                            suara</span>
                    </div>
                    <div>
                        @foreach ($stat['counts'] as $choice => $cnt)
                            @php
                                $pct = $stat['total'] > 0 ? round(($cnt / $stat['total']) * 100) : 0;
                            @endphp
                            <div class="bar-row">
                                <div class="bar-meta">
                                    <span>{{ $choice }}</span>
                                    <strong>{{ $cnt }} ({{ $pct }}%)</strong>
                                </div>
                                <div class="bar-track">
                                    <div class="bar-fill" style="width: {{ $pct }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Tabel Lengkap Seluruh Tanggapan Masuk --}}
    <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">

        {{-- Toolbar: Entries + Search --}}
        <div class="table-toolbar"
            style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding: 14px 20px; border-bottom: 1px solid var(--border-color);">
            <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                <h3 style="font-size: 0.95rem; font-weight: 700; margin: 0; color: var(--text-color);">
                    <i class="fas fa-table-list me-1 text-primary"></i> Daftar Responden
                    <span style="color:var(--text-muted); font-weight:500;">({{ $responses->total() }})</span>
                </h3>
                <div class="toolbar-entries" style="display:flex;align-items:center;gap:6px;font-size:0.82rem;color:var(--text-muted);">
                    <label for="perPageSelect" style="margin:0;">Tampilkan</label>
                    <select id="perPageSelect" class="per-page-select"
                        style="padding:4px 8px;border-radius:6px;border:1px solid var(--border-color);background:var(--bg-card);color:var(--text-color);font-size:0.82rem;">
                        @foreach ([10, 15, 25, 50, 100] as $n)
                            <option value="{{ $n }}" {{ ($perPage ?? 15) == $n ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                    <span>entri</span>
                </div>
            </div>
            <div class="live-search-wrap"
                style="display:flex;align-items:center;gap:8px;background:var(--bg-body);border:1px solid var(--border-color);border-radius:8px;padding:6px 12px;min-width:220px;">
                <i class="fas fa-search" style="color:var(--text-muted);font-size:0.82rem;"></i>
                <input type="text" id="liveSearch" placeholder="Cari nama atau jawaban..."
                    value="{{ request('q') }}" autocomplete="off"
                    style="border:none;background:transparent;outline:none;width:100%;font-size:0.85rem;color:var(--text-color);">
                <button type="button" id="clearSearch"
                    class="clear-search {{ request('q') ? 'visible' : '' }}"
                    title="Hapus pencarian"
                    style="background:none;border:none;cursor:pointer;color:var(--text-muted);padding:0;display:{{ request('q') ? 'flex' : 'none' }};align-items:center;">
                    <i class="fas fa-times" style="font-size:0.78rem;"></i>
                </button>
            </div>
        </div>

        @if ($responses->count() > 0)
            {{-- Responsive Table --}}
            <div style="overflow-x: auto;">
                <table class="table mb-0" style="width:100%;border-collapse:collapse;font-size:0.85rem;">
                    <thead>
                        <tr style="background:var(--bg-hover);border-bottom:1px solid var(--border-color);">
                            <th style="padding:12px 18px;font-size:0.78rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;width:50px;text-align:center;">No</th>
                            <th style="padding:12px 18px;font-size:0.78rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Waktu Submit</th>
                            <th style="padding:12px 18px;font-size:0.78rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Nama Responden</th>
                            <th style="padding:12px 18px;font-size:0.78rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Identitas</th>
                            <th style="padding:12px 18px;font-size:0.78rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Ringkasan Jawaban</th>
                            <th style="padding:12px 18px;font-size:0.78rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;text-align:center;width:100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($responses as $idx => $r)
                            @php
                                $jawaban = is_array($r->jawaban) ? $r->jawaban : [];
                            @endphp
                            <tr style="border-bottom:1px solid var(--border-color);transition:background 0.2s;">
                                <td style="padding:14px 18px;text-align:center;color:var(--text-muted);" data-label="No">
                                    {{ $responses->firstItem() + $idx }}
                                </td>
                                <td style="padding:14px 18px;" data-label="Waktu Submit">
                                    <span style="font-weight:600;color:var(--text-color);">
                                        {{ $r->created_at ? $r->created_at->format('d/m/Y') : '-' }}
                                    </span>
                                    <span style="display:block;font-size:0.75rem;color:var(--text-muted);">
                                        {{ $r->created_at ? $r->created_at->format('H:i:s') . ' WIB' : '' }}
                                    </span>
                                </td>
                                <td style="padding:14px 18px;" data-label="Nama Responden">
                                    <strong style="color:var(--text-color);">{{ $r->nama_responden ?: 'Responden Tamu' }}</strong>
                                    @if ($r->pengguna_id)
                                        <span class="badge"
                                            style="font-size:0.65rem;background:rgba(99,102,241,0.12);color:var(--primary);padding:2px 6px;margin-left:4px;border-radius:4px;">
                                            <i class="fas fa-user-check me-1"></i>Akun SAE
                                        </span>
                                    @else
                                        <span class="badge badge-outline"
                                            style="font-size:0.65rem;padding:2px 5px;margin-left:4px;color:var(--text-muted);">Tamu</span>
                                    @endif
                                </td>
                                <td style="padding:14px 18px;font-size:0.8rem;color:var(--text-muted);" data-label="Identitas">
                                    {{ $r->identitas_responden ?: '-' }}
                                </td>
                                <td style="padding:14px 18px;max-width:280px;" data-label="Ringkasan Jawaban">
                                    <div style="display:flex;flex-wrap:wrap;gap:4px;">
                                        @foreach (array_slice($jawaban, 0, 2) as $k => $val)
                                            <span
                                                style="background:var(--bg-hover);padding:2px 7px;border-radius:5px;font-size:0.75rem;color:var(--text-muted);border:1px solid var(--border-color);white-space:nowrap;max-width:180px;overflow:hidden;text-overflow:ellipsis;">
                                                {{ is_array($val) ? implode(', ', $val) : \Illuminate\Support\Str::limit($val, 25) }}
                                            </span>
                                        @endforeach
                                        @if (count($jawaban) > 2)
                                            <span style="font-size:0.72rem;color:var(--text-muted);">+{{ count($jawaban) - 2 }} lainnya</span>
                                        @endif
                                    </div>
                                </td>
                                <td style="padding:14px 18px;text-align:center;" data-label="Aksi">
                                    <div class="table-actions" style="display:flex;gap:5px;justify-content:center;">
                                        <button type="button" class="btn-icon" title="Lihat Detail"
                                            onclick='openDetailModal(@json($r), @json($formulir->skema ?? []))'>
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <form action="{{ route('dashboard.formulir.delete-response', [$formulir->id, $r->id]) }}"
                                            method="POST" data-confirm="delete"
                                            data-name="tanggapan dari {{ $r->nama_responden ?? 'responden ini' }}"
                                            style="margin:0;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon text-danger" title="Hapus Tanggapan">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination Footer --}}
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;padding:14px 20px;border-top:1px solid var(--border-color);">
                <div style="font-size:0.82rem;color:var(--text-muted);">
                    Menampilkan <strong>{{ $responses->firstItem() ?? 0 }}</strong>–<strong>{{ $responses->lastItem() ?? 0 }}</strong>
                    dari <strong>{{ $responses->total() }}</strong> entri
                </div>
                @if ($responses->hasPages())
                    <div class="custom-pagination" style="margin:0;padding:0;">
                        @if ($responses->onFirstPage())
                            <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                        @else
                            <a href="{{ $responses->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
                        @endif
                        @php
                            $cur  = $responses->currentPage();
                            $last = $responses->lastPage();
                            $from = max(1, $cur - 2);
                            $to   = min($last, $cur + 2);
                        @endphp
                        @if ($from > 1)
                            <a href="{{ $responses->url(1) }}" class="page-btn">1</a>
                            @if ($from > 2)<span class="page-info">&hellip;</span>@endif
                        @endif
                        @for ($i = $from; $i <= $to; $i++)
                            <a href="{{ $responses->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                        @endfor
                        @if ($to < $last)
                            @if ($to < $last - 1)<span class="page-info">&hellip;</span>@endif
                            <a href="{{ $responses->url($last) }}" class="page-btn">{{ $last }}</a>
                        @endif
                        @if ($responses->hasMorePages())
                            <a href="{{ $responses->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
                        @else
                            <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                        @endif
                    </div>
                @endif
            </div>
        @else
            <div style="text-align:center;padding:60px 20px;color:var(--text-muted);">
                <i class="fas fa-inbox" style="font-size:2.5rem;opacity:0.3;margin-bottom:12px;display:block;"></i>
                <p style="margin:0;font-size:0.9rem;">Belum ada data tanggapan yang masuk.</p>
            </div>
        @endif
    </div>


    <!-- Modal Detail Tanggapan Standar Dashboard SAE -->
    <div id="responseModal" class="modal-backdrop">
        <div class="card" style="max-width: 640px; width: 95%; padding: 0; overflow: hidden;">
            <div style="padding: 18px 24px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <h4 style="font-size: 1.05rem; font-weight: 700; margin: 0; color: var(--text-color);">Rincian Tanggapan Responden</h4>
                    <span id="modalMeta" style="font-size: 0.78rem; color: var(--text-muted);">-</span>
                </div>
                <button type="button" onclick="closeDetail()" class="btn-theme-toggle" style="width: 32px; height: 32px;" title="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="padding: 24px; overflow-y: auto; max-height: 60vh;" id="modalContent"></div>
            <div style="padding: 14px 24px; border-top: 1px solid var(--border-color); text-align: right; background: var(--bg-hover);">
                <button type="button" class="btn btn-outline" onclick="closeDetail()"
                    style="font-size: 0.85rem; padding: 6px 16px;">Tutup</button>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/formulir.js') }}?v={{ file_exists(public_path('js/formulir.js')) ? filemtime(public_path('js/formulir.js')) : time() }}"></script>
    @endpush
@endsection
