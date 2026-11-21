{{-- Partial: E-Learning — Tab Dasbor (interaktif per peran: admin/guru/peserta didik) --}}
@php
    $peran = $dasbor['role'] ?? 'guru';
    $peranLabel = ['admin' => 'Administrator', 'guru' => 'Guru', 'peserta_didik' => 'Peserta Didik', 'tendik' => 'Tendik'][$peran] ?? ucfirst($peran);
    $trend = $dasbor['trend'] ?? ['labels' => [], 'values' => []];
@endphp

{{-- Sapaan peran --}}
<div class="card" style="padding: var(--space-8); margin-bottom: var(--space-8); display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap;">
    <div style="display: flex; align-items: center; gap: var(--space-6);">
        <div style="width: 52px; height: 52px; border-radius: var(--radius-lg); background: rgba(99, 102, 241, 0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl); flex-shrink: 0;">
            <i class="fas {{ $peran === 'peserta_didik' ? 'fa-user-graduate' : ($peran === 'admin' ? 'fa-user-shield' : 'fa-chalkboard-user') }}"></i>
        </div>
        <div>
            <h3 style="font-size: var(--text-lg); font-weight: 800; color: var(--text-main); margin: 0 0 2px 0;">
                Dasbor {{ $peranLabel }}
            </h3>
            <p style="color: var(--text-muted); font-size: var(--text-sm); margin: 0;">
                @if ($peran === 'peserta_didik')
                    Ringkasan materi, kuis, dan tugas milik Anda.
                @elseif ($peran === 'admin')
                    Pantau seluruh aktivitas pembelajaran E-Learning sekolah.
                @else
                    Ringkasan materi, tugas, dan penilaian pada pembelajaran yang Anda ampu.
                @endif
            </p>
        </div>
    </div>
    <div style="font-size: var(--text-xs); color: var(--text-muted);">
        <i class="fas fa-calendar-day"></i> {{ now()->translatedFormat('l, d F Y') }}
    </div>
</div>

@if ($peran === 'peserta_didik')
    {{-- ======================= DASBOR PESERTA DIDIK ======================= --}}
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: var(--space-10);">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.12); color: var(--success);"><i class="fas fa-square-check"></i></div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($dasbor['my_materi_selesai'] ?? 0) }}</div>
                <div class="dash-stat-label">Kuis Dikerjakan</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6, 182, 212, 0.12); color: var(--accent);"><i class="fas fa-upload"></i></div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($dasbor['my_tugas_kumpul'] ?? 0) }}</div>
                <div class="dash-stat-label">Tugas Dikumpulkan</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.12); color: var(--warning);"><i class="fas fa-star"></i></div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ rtrim(rtrim(number_format((float) ($dasbor['my_nilai_rata'] ?? 0), 2, '.', ''), '0'), '.') }}</div>
                <div class="dash-stat-label">Rata-rata Nilai Tugas</div>
            </div>
        </div>
    </div>

    <div class="card" style="padding: var(--space-8); margin-bottom: var(--space-8);">
        <h4 style="font-size: var(--text-md); font-weight: 700; color: var(--text-main); margin: 0 0 var(--space-6) 0; display: flex; align-items: center; gap: var(--space-4);">
            <i class="fas fa-chart-column" style="color: var(--primary);"></i> Nilai Kuis per Mata Pelajaran
        </h4>
        <div style="height: 260px; position: relative;">
            <canvas id="chartNilaiMapelPd"></canvas>
        </div>
    </div>

    <div class="card" style="padding: var(--space-8);">
        <h4 style="font-size: var(--text-md); font-weight: 700; color: var(--text-main); margin: 0 0 var(--space-6) 0; display: flex; align-items: center; gap: var(--space-4);">
            <i class="fas fa-clock-rotate-left" style="color: var(--primary);"></i> Kuis Terakhir Dikerjakan
        </h4>
        @forelse (($dasbor['kuis_terakhir'] ?? collect()) as $k)
            <div style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-6); padding: var(--space-6) 0; border-bottom: 1px solid var(--border-color);">
                <div style="min-width: 0;">
                    <div class="sae-truncate" style="font-size: var(--text-sm); font-weight: 600; color: var(--text-main); max-width: 420px;" title="{{ $k->judul }}">{{ $k->judul }}</div>
                    <div style="font-size: var(--text-xs); color: var(--text-muted);">
                        {{ $k->submitted_at ? \Illuminate\Support\Carbon::parse($k->submitted_at)->translatedFormat('d M Y H:i') : '' }}
                    </div>
                </div>
                <div style="text-align: right; flex-shrink: 0;">
                    <div style="font-size: var(--text-lg); font-weight: 800; color: {{ ($k->skor ?? 0) >= 70 ? 'var(--success)' : 'var(--danger)' }};">
                        {{ rtrim(rtrim(number_format((float) $k->skor, 2, '.', ''), '0'), '.') }}
                    </div>
                    <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ $k->benar }}/{{ $k->total_soal }} benar</div>
                </div>
            </div>
        @empty
            <div style="text-align: center; padding: var(--space-10); color: var(--text-muted); font-size: var(--text-sm);">
                <i class="fas fa-clipboard-question" style="font-size: var(--text-2xl); opacity: 0.4; display: block; margin-bottom: var(--space-4);"></i>
                Belum ada kuis yang dikerjakan.
            </div>
        @endforelse
    </div>

    <script id="nilaiMapelPayload" type="application/json">{!! json_encode($dasbor['nilai_per_mapel'] ?? ['labels' => [], 'values' => []], JSON_UNESCAPED_UNICODE) !!}</script>
@else
    {{-- ======================= DASBOR GURU / ADMIN ======================= --}}
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: var(--space-10);">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.12); color: var(--success);"><i class="fas fa-list-check"></i></div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($dasbor['materi_berkuis'] ?? 0) }}</div>
                <div class="dash-stat-label">Materi Berkuis</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.12); color: var(--warning);"><i class="fas fa-hourglass-half"></i></div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($dasbor['butuh_penilaian'] ?? 0) }}</div>
                <div class="dash-stat-label">Menunggu Penilaian</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239, 68, 68, 0.12); color: var(--danger);"><i class="fas fa-file-pen"></i></div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['draft'] ?? 0) }}</div>
                <div class="dash-stat-label">Materi Draf</div>
            </div>
        </div>
    </div>

    <div class="dash-admin-main-grid">
        <div class="card" style="padding: var(--space-8);">
            <h4 style="font-size: var(--text-md); font-weight: 700; color: var(--text-main); margin: 0 0 var(--space-6) 0; display: flex; align-items: center; gap: var(--space-4);">
                <i class="fas fa-chart-line" style="color: var(--primary);"></i> Materi Ditambahkan (7 Hari)
            </h4>
            <div style="height: 220px; position: relative;">
                <canvas id="chartElearningTrend"></canvas>
            </div>
        </div>

        <div class="card" style="padding: var(--space-8);">
            <h4 style="font-size: var(--text-md); font-weight: 700; color: var(--text-main); margin: 0 0 var(--space-6) 0; display: flex; align-items: center; gap: var(--space-4);">
                <i class="fas fa-inbox" style="color: var(--accent);"></i> Pengumpulan Terbaru
            </h4>
            @forelse (($dasbor['pengumpulan_terbaru'] ?? collect()) as $p)
                <div style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-4); padding: var(--space-5) 0; border-bottom: 1px solid var(--border-color);">
                    <div style="min-width: 0;">
                        <div class="sae-truncate" style="font-size: var(--text-sm); font-weight: 600; color: var(--text-main); max-width: 200px;" title="{{ $p->tugas }}">{{ $p->tugas }}</div>
                        <div class="sae-truncate" style="font-size: var(--text-xs); color: var(--text-muted); max-width: 200px;">{{ $p->nama ?? '—' }}</div>
                    </div>
                    <span class="badge" style="flex-shrink: 0; background: {{ $p->nilai !== null ? 'rgba(16,185,129,0.15)' : 'rgba(245,158,11,0.15)' }}; color: {{ $p->nilai !== null ? 'var(--success)' : 'var(--warning)' }}; padding: 3px 8px; border-radius: var(--radius-pill);">
                        {{ $p->nilai !== null ? rtrim(rtrim(number_format((float) $p->nilai, 2, '.', ''), '0'), '.') : 'Belum' }}
                    </span>
                </div>
            @empty
                <div style="text-align: center; padding: var(--space-8); color: var(--text-muted); font-size: var(--text-sm);">Belum ada pengumpulan.</div>
            @endforelse
        </div>
    </div>

    <div class="card" style="padding: var(--space-8); margin-top: var(--space-8);">
        <h4 style="font-size: var(--text-md); font-weight: 700; color: var(--text-main); margin: 0 0 var(--space-6) 0; display: flex; align-items: center; gap: var(--space-4);">
            <i class="fas fa-book-open" style="color: var(--primary);"></i> Materi Terbaru
        </h4>
        <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
            <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <tbody>
                    @forelse (($dasbor['materi_terbaru'] ?? collect()) as $m)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 10px 14px; font-size: var(--text-sm); font-weight: 600; color: var(--text-main);" data-label="Judul">{{ $m->judul }}</td>
                            <td style="padding: 10px 14px; text-align: right; width: 140px;" data-label="Status">
                                @if ($m->status === 'terbit')
                                    <span class="badge" style="background: rgba(16,185,129,0.15); color: var(--success); padding: 3px 8px; border-radius: var(--radius-pill);">Terbit</span>
                                @else
                                    <span class="badge" style="background: rgba(245,158,11,0.15); color: var(--warning); padding: 3px 8px; border-radius: var(--radius-pill);">Draf</span>
                                @endif
                            </td>
                            <td style="padding: 10px 14px; text-align: right; width: 130px; font-size: var(--text-xs); color: var(--text-muted);" data-label="Dibuat">
                                {{ $m->created_at ? \Illuminate\Support\Carbon::parse($m->created_at)->translatedFormat('d M Y') : '' }}
                            </td>
                        </tr>
                    @empty
                        <tr><td style="text-align: center; padding: var(--space-8); color: var(--text-muted); font-size: var(--text-sm);">Belum ada materi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script id="elearningTrendPayload" type="application/json">{!! json_encode($trend, JSON_UNESCAPED_UNICODE) !!}</script>
@endif
