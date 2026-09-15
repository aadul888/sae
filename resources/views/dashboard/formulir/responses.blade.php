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

    <!-- Tabel Lengkap Seluruh Tanggapan Masuk -->
    <div class="response-table-container">
        <div
            style="padding: 16px 20px; border-bottom: 1px solid var(--border-color, #e2e8f0); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <h3 style="font-size: 0.95rem; font-weight: 700; margin: 0; color: var(--text-color);">
                <i class="fas fa-table-list me-1 text-primary"></i> Daftar Responden ({{ $responses->total() }})
            </h3>
            <form action="{{ route('dashboard.formulir.responses', $formulir->id) }}" method="GET"
                style="display: flex; gap: 8px;">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama atau jawaban..."
                    class="form-control form-control-sm" style="width: 220px;">
                <button type="submit" class="btn btn-outline btn-sm">Cari</button>
            </form>
        </div>

        @if ($responses->count() > 0)
            <div class="table-responsive">
                <table class="table mb-0" style="font-size: 0.85rem; width: 100%;">
                    <thead style="background: var(--bg-hover, #f8fafc);">
                        <tr>
                            <th style="width: 50px; text-align: center;">No</th>
                            <th>Waktu Submit</th>
                            <th>Nama Responden</th>
                            <th>Identitas (NISN / Rombel)</th>
                            <th>Ringkasan Jawaban</th>
                            <th style="width: 110px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($responses as $idx => $r)
                            @php
                                $jawaban = is_array($r->jawaban) ? $r->jawaban : [];
                            @endphp
                            <tr style="vertical-align: middle;">
                                <td style="text-align: center; color: var(--text-muted);">
                                    {{ $responses->firstItem() + $idx }}
                                </td>
                                <td>
                                    <span
                                        style="font-weight: 600; color: var(--text-color);">{{ $r->created_at ? $r->created_at->format('d/m/Y') : '-' }}</span>
                                    <span
                                        style="display: block; font-size: 0.75rem; color: var(--text-muted);">{{ $r->created_at ? $r->created_at->format('H:i:s') : '' }}
                                        WIB</span>
                                </td>
                                <td>
                                    <strong>{{ $r->nama_responden ?: 'Responden Tamu' }}</strong>
                                    @if ($r->pengguna_id)
                                        <span class="badge bg-primary-subtle text-primary"
                                            style="font-size: 0.68rem; margin-left: 4px;">Akun SAE</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary"
                                            style="font-size: 0.68rem; margin-left: 4px;">Tamu</span>
                                    @endif
                                </td>
                                <td style="color: var(--text-muted); font-size: 0.8rem;">
                                    {{ $r->identitas_responden ?: '-' }}
                                </td>
                                <td
                                    style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--text-muted);">
                                    @foreach (array_slice($jawaban, 0, 2) as $k => $val)
                                        <span
                                            style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; margin-right: 4px;">
                                            {{ is_array($val) ? implode(', ', $val) : Str::limit($val, 25) }}
                                        </span>
                                    @endforeach
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: flex; gap: 4px; justify-content: center;">
                                        <button type="button" class="btn btn-outline btn-sm" style="padding: 4px 8px;"
                                            title="Lihat Lengkap"
                                            onclick='openDetailModal(@json($r), @json($formulir->skema ?? []))'>
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <form
                                            action="{{ route('dashboard.formulir.delete-response', [$formulir->id, $r->id]) }}"
                                            method="POST" data-confirm="delete"
                                            data-name="tanggapan dari {{ $r->nama_responden ?? 'responden ini' }}"
                                            style="margin: 0;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm"
                                                style="padding: 4px 8px;" title="Hapus">
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

            <div style="padding: 16px 20px;">
                {{ $responses->links() }}
            </div>
        @else
            <div style="text-align: center; padding: 50px 20px; color: var(--text-muted);">
                <i class="fas fa-inbox" style="font-size: 2rem; color: var(--text-muted); opacity: 0.5; margin-bottom: 10px;"></i>
                <p style="margin: 0; font-size: 0.9rem;">Belum ada data tanggapan yang masuk.</p>
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
        <script src="{{ asset('js/formulir.js') }}"></script>
    @endpush
@endsection
