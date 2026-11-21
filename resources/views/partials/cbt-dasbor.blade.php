{{-- Partial: CBT — Tab Dasbor (interaktif per peran: admin/guru/peserta didik) --}}
@php
    $peran = $dasbor['role'] ?? 'guru';
    $peranLabel = ['admin' => 'Administrator', 'guru' => 'Guru', 'peserta_didik' => 'Peserta Didik', 'tendik' => 'Tendik'][$peran] ?? ucfirst($peran);
    $periodeAktif = $dasbor['periode_aktif'] ?? null;
    $tipeLabel = \App\Models\CbtPeriodeAsesmen::TIPE_LABEL;
@endphp

{{-- Sapaan + periode aktif --}}
<div class="card" style="padding: var(--space-8); margin-bottom: var(--space-8); display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap;">
    <div style="display: flex; align-items: center; gap: var(--space-6);">
        <div style="width: 52px; height: 52px; border-radius: var(--radius-lg); background: rgba(99, 102, 241, 0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl); flex-shrink: 0;">
            <i class="fas {{ $peran === 'peserta_didik' ? 'fa-user-graduate' : ($peran === 'admin' ? 'fa-user-shield' : 'fa-chalkboard-user') }}"></i>
        </div>
        <div>
            <h3 style="font-size: var(--text-lg); font-weight: 800; color: var(--text-main); margin: 0 0 2px 0;">
                Dasbor CBT &amp; Asesmen — {{ $peranLabel }}
            </h3>
            <p style="color: var(--text-muted); font-size: var(--text-sm); margin: 0;">
                @if ($peran === 'peserta_didik')
                    Jadwal ujian yang harus Anda ikuti.
                @elseif ($peran === 'admin')
                    Pantau bank soal, ujian, dan periode asesmen seluruh sekolah.
                @else
                    Ringkasan soal, ujian, dan periode asesmen pada pembelajaran yang Anda ampu.
                @endif
            </p>
        </div>
    </div>
    <div style="text-align: right;">
        @if ($periodeAktif)
            <div style="font-size: var(--text-2xs); color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.04em;">Asesmen Berjalan</div>
            <div style="font-size: var(--text-sm); font-weight: 700; color: var(--success);">
                <i class="fas fa-circle-play"></i> {{ $periodeAktif->nama }}
            </div>
            <div style="font-size: var(--text-xs); color: var(--text-muted);">
                {{ $tipeLabel[$periodeAktif->tipe] ?? $periodeAktif->tipe }}
                @if ($periodeAktif->tahun_ajaran) · {{ $periodeAktif->tahun_ajaran }} @endif
                @if ($periodeAktif->semester) · {{ ucfirst($periodeAktif->semester) }} @endif
            </div>
        @else
            <div style="font-size: var(--text-xs); color: var(--text-muted);">
                <i class="fas fa-circle-pause"></i> Belum ada periode asesmen aktif
            </div>
        @endif
    </div>
</div>

@if ($peran === 'peserta_didik')
    {{-- ======================= DASBOR PESERTA DIDIK ======================= --}}
    <div class="card" style="padding: var(--space-8);">
        <h4 style="font-size: var(--text-md); font-weight: 700; color: var(--text-main); margin: 0 0 var(--space-6) 0; display: flex; align-items: center; gap: var(--space-4);">
            <i class="fas fa-calendar-days" style="color: var(--primary);"></i> Ujian Terjadwal
        </h4>
        <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
            <table class="table" style="width: 100%; border-collapse: collapse; margin: 0;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); text-align: left;">
                        <th style="padding: 10px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Judul</th>
                        <th style="padding: 10px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Mata Pelajaran</th>
                        <th style="padding: 10px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 130px;">Jadwal</th>
                        <th style="padding: 10px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 90px;">Durasi</th>
                        <th style="padding: 10px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 100px;">Token</th>
                        <th style="padding: 10px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 130px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse (($dasbor['ujian_terjadwal'] ?? collect()) as $u)
                        @php $siap = $u->status === 'terbit'; @endphp
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 14px; font-size: var(--text-sm); font-weight: 600; color: var(--text-main);" data-label="Judul">{{ $u->judul }}</td>
                            <td style="padding: 12px 14px; font-size: var(--text-sm); color: var(--text-main);" data-label="Mata Pelajaran">
                                {{ $u->nama_mapel }}
                                @if ($u->nama_periode)<div style="font-size: var(--text-xs); color: var(--text-muted);">{{ $u->nama_periode }}</div>@endif
                            </td>
                            <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs); color: var(--text-muted);" data-label="Jadwal">
                                {{ $u->jadwal_mulai ? \Illuminate\Support\Carbon::parse($u->jadwal_mulai)->translatedFormat('d M Y H:i') : 'Belum dijadwalkan' }}
                            </td>
                            <td style="padding: 12px 14px; text-align: center; font-size: var(--text-sm); color: var(--text-main);" data-label="Durasi">{{ (int) $u->durasi_menit }}m</td>
                            <td style="padding: 12px 14px; text-align: center; font-size: var(--text-sm);" data-label="Token">
                                @if (!empty($u->token))
                                    <span class="copyable" data-copy="{{ $u->token }}" title="Klik untuk salin token" style="font-family: monospace; font-weight: 700; letter-spacing: 0.08em; color: var(--accent); cursor: pointer;">{{ $u->token }}</span>
                                @else
                                    <span style="color: var(--text-muted);">—</span>
                                @endif
                            </td>
                            <td style="padding: 12px 14px; text-align: center;" data-label="Status">
                                <span class="badge" style="background: {{ $siap ? 'rgba(16,185,129,0.15)' : 'rgba(148,163,184,0.15)' }}; color: {{ $siap ? 'var(--success)' : 'var(--text-muted)' }}; padding: 3px 8px; border-radius: var(--radius-pill);">
                                    {{ $siap ? 'Terbit' : 'Ditutup' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: var(--space-10); color: var(--text-muted); font-size: var(--text-sm);">
                                <i class="fas fa-calendar-xmark" style="font-size: var(--text-2xl); opacity: 0.4; display: block; margin-bottom: var(--space-4);"></i>
                                Belum ada ujian terjadwal.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-4);">
            <i class="fas fa-circle-info"></i> Pelaksanaan ujian (mengerjakan) diaktifkan setelah fondasi server siap.
        </div>
    </div>

    {{-- Jadwal sesi (slot) ujian --}}
    <div class="card" style="padding: var(--space-8); margin-top: var(--space-8);">
        <h4 style="font-size: var(--text-md); font-weight: 700; color: var(--text-main); margin: 0 0 var(--space-6) 0; display: flex; align-items: center; gap: var(--space-4);">
            <i class="fas fa-clock" style="color: var(--accent);"></i> Jadwal Sesi Ujian
        </h4>
        <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
            <table class="table" style="width: 100%; border-collapse: collapse; margin: 0;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); text-align: left;">
                        <th style="padding: 10px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Mata Pelajaran</th>
                        <th style="padding: 10px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Periode</th>
                        <th style="padding: 10px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 150px;">Tanggal</th>
                        <th style="padding: 10px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 120px;">Jam</th>
                        <th style="padding: 10px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 90px;">Ruang</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse (($dasbor['sesi_terjadwal'] ?? collect()) as $s)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 14px; font-size: var(--text-sm); color: var(--text-main);" data-label="Mata Pelajaran">{{ $s->nama_mapel }}</td>
                            <td style="padding: 12px 14px; font-size: var(--text-xs); color: var(--text-muted);" data-label="Periode">{{ $s->nama_periode ?: '—' }}</td>
                            <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs); color: var(--text-muted);" data-label="Tanggal">
                                {{ $s->tanggal ? \Illuminate\Support\Carbon::parse($s->tanggal)->translatedFormat('d M Y') : '—' }}
                            </td>
                            <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs); color: var(--text-main);" data-label="Jam">
                                @if ($s->jam_mulai || $s->jam_selesai)
                                    {{ $s->jam_mulai ? substr($s->jam_mulai, 0, 5) : '…' }}–{{ $s->jam_selesai ? substr($s->jam_selesai, 0, 5) : '…' }}
                                @else
                                    —
                                @endif
                            </td>
                            <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs); color: var(--text-muted);" data-label="Ruang">{{ $s->ruang ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; padding: var(--space-10); color: var(--text-muted); font-size: var(--text-sm);">
                                <i class="fas fa-clock" style="font-size: var(--text-2xl); opacity: 0.4; display: block; margin-bottom: var(--space-4);"></i>
                                Belum ada sesi ujian terjadwal.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@else
    {{-- ======================= DASBOR GURU / ADMIN ======================= --}}
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: var(--space-10);">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.12); color: var(--primary);"><i class="fas fa-circle-question"></i></div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['soal_total'] ?? 0) }}</div>
                <div class="dash-stat-label">Total Soal</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6, 182, 212, 0.12); color: var(--accent);"><i class="fas fa-file-pen"></i></div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['ujian_total'] ?? 0) }}</div>
                <div class="dash-stat-label">Total Ujian</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.12); color: var(--success);"><i class="fas fa-circle-check"></i></div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($dasbor['ujian_terbit'] ?? 0) }}</div>
                <div class="dash-stat-label">Ujian Terbit</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.12); color: var(--warning);"><i class="fas fa-calendar-check"></i></div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($dasbor['ujian_periode_aktif'] ?? 0) }}</div>
                <div class="dash-stat-label">Ujian di Periode Aktif</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.12); color: var(--primary);"><i class="fas fa-clock"></i></div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($dasbor['sesi_total'] ?? 0) }}</div>
                <div class="dash-stat-label">Sesi Ujian</div>
            </div>
        </div>
    </div>

    <div class="dash-admin-main-grid">
        <div class="card" style="padding: var(--space-8);">
            <h4 style="font-size: var(--text-md); font-weight: 700; color: var(--text-main); margin: 0 0 var(--space-6) 0; display: flex; align-items: center; gap: var(--space-4);">
                <i class="fas fa-chart-pie" style="color: var(--primary);"></i> Komposisi Jenis Soal
            </h4>
            <div style="height: 240px; position: relative;">
                <canvas id="chartCbtJenisSoal"></canvas>
            </div>
        </div>

        <div class="card" style="padding: var(--space-8);">
            <h4 style="font-size: var(--text-md); font-weight: 700; color: var(--text-main); margin: 0 0 var(--space-6) 0; display: flex; align-items: center; gap: var(--space-4);">
                <i class="fas fa-clock-rotate-left" style="color: var(--accent);"></i> Ujian Terbaru
            </h4>
            @forelse (($dasbor['ujian_terbaru'] ?? collect()) as $u)
                <div style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-4); padding: var(--space-5) 0; border-bottom: 1px solid var(--border-color);">
                    <div style="min-width: 0;">
                        <div class="sae-truncate" style="font-size: var(--text-sm); font-weight: 600; color: var(--text-main); max-width: 220px;" title="{{ $u->judul }}">{{ $u->judul }}</div>
                        <div class="sae-truncate" style="font-size: var(--text-xs); color: var(--text-muted); max-width: 220px;">{{ $u->nama_mapel }}{{ $u->nama_periode ? ' · '.$u->nama_periode : '' }}</div>
                    </div>
                    <span class="badge" style="flex-shrink: 0; background: {{ $u->status === 'terbit' ? 'rgba(16,185,129,0.15)' : ($u->status === 'draft' ? 'rgba(245,158,11,0.15)' : 'rgba(148,163,184,0.15)') }}; color: {{ $u->status === 'terbit' ? 'var(--success)' : ($u->status === 'draft' ? 'var(--warning)' : 'var(--text-muted)') }}; padding: 3px 8px; border-radius: var(--radius-pill);">
                        {{ ['terbit' => 'Terbit', 'draft' => 'Draf', 'ditutup' => 'Ditutup'][$u->status] ?? $u->status }}
                    </span>
                </div>
            @empty
                <div style="text-align: center; padding: var(--space-8); color: var(--text-muted); font-size: var(--text-sm);">Belum ada ujian.</div>
            @endforelse
        </div>
    </div>

    <script id="cbtJenisSoalPayload" type="application/json">{!! json_encode($dasbor['jenis_soal'] ?? ['labels' => [], 'values' => []], JSON_UNESCAPED_UNICODE) !!}</script>
@endif
