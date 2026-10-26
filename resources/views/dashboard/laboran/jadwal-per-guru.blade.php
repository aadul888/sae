@extends('layouts.dashboard')

@section('title', 'Jadwal Praktik per Guru — Laboratorium SAE')
@section('dash_title', 'Jadwal per Guru')

@section('content')
    <div class="dash-content-inner">
        {{-- 1. Banner Header --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap; margin-bottom: var(--space-9);">
            <div style="display: flex; align-items: center; gap: var(--space-7);">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl); flex-shrink: 0;">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--text-color); margin: 0;">
                        Jadwal Praktik per Guru
                    </h2>
                    <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-1);">
                        Rekap jadwal penggunaan lab / bengkel / lahan / lapangan menurut guru, mingguan atau bulanan.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions" style="display: flex; gap: var(--space-4); align-items: center; flex-wrap: wrap;">
                <a href="{{ route('dashboard.laboran.jadwal.index') }}" class="btn btn-outline"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md); font-size: var(--text-md); text-decoration: none;"
                    title="Kembali ke Jadwal Praktik">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <a href="{{ route('dashboard.laboran.jadwal-per-guru.cetak', request()->query()) }}" target="_blank"
                    class="btn btn-primary"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md); font-size: var(--text-md); text-decoration: none;"
                    title="Cetak Jadwal per Guru">
                    <i class="fas fa-print"></i>
                </a>
            </div>
        </div>

        {{-- 2. Filter --}}
        <div class="card" style="padding: var(--space-7); margin-bottom: var(--space-9);">
            <form method="GET" action="{{ route('dashboard.laboran.jadwal-per-guru.index') }}">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: var(--space-5); align-items: end;">
                    <div>
                        <label style="display: block; font-size: var(--text-sm); font-weight: var(--font-semibold); margin-bottom: var(--space-2); color: var(--text-color);">Mode</label>
                        <select name="mode" class="form-control" style="height: 38px;">
                            <option value="mingguan" {{ $mode === 'mingguan' ? 'selected' : '' }}>Mingguan</option>
                            <option value="bulanan" {{ $mode === 'bulanan' ? 'selected' : '' }}>Bulanan</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: var(--text-sm); font-weight: var(--font-semibold); margin-bottom: var(--space-2); color: var(--text-color);">Guru</label>
                        <select name="ptk_id" class="form-control" style="height: 38px;">
                            <option value="">Semua Guru</option>
                            @foreach ($daftarGuru as $g)
                                <option value="{{ $g->ptk_id }}" {{ $ptkId === $g->ptk_id ? 'selected' : '' }}>
                                    {{ $g->nama }}{{ !empty($g->nip) ? ' — ' . $g->nip : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: var(--text-sm); font-weight: var(--font-semibold); margin-bottom: var(--space-2); color: var(--text-color);">Acuan Tanggal (mingguan)</label>
                        <input type="date" name="tanggal" class="form-control" style="height: 38px;"
                            value="{{ request('tanggal', $mode === 'mingguan' ? $start : '') }}">
                    </div>
                    <div>
                        <label style="display: block; font-size: var(--text-sm); font-weight: var(--font-semibold); margin-bottom: var(--space-2); color: var(--text-color);">Bulan</label>
                        <select name="bulan" class="form-control" style="height: 38px;">
                            @foreach (range(1, 12) as $b)
                                <option value="{{ $b }}" {{ $bulan === $b ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::createFromDate(2026, $b, 1)->translatedFormat('F') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: var(--text-sm); font-weight: var(--font-semibold); margin-bottom: var(--space-2); color: var(--text-color);">Tahun</label>
                        <input type="number" name="tahun" class="form-control" style="height: 38px;"
                            value="{{ $tahun }}" min="2020" max="{{ date('Y') + 1 }}">
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary"
                            style="height: 38px; width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                            <i class="fas fa-filter"></i> Terapkan
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- 3. Ringkasan --}}
        <div class="dash-stat-grid"
            style="grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); margin-bottom: var(--space-9); gap: var(--space-7);">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59,130,246,0.15); color: var(--primary, #3b82f6);"><i class="fas fa-layer-group"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($totalSesi, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Total Sesi</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: var(--success, #10b981);"><i class="fas fa-clock"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ intdiv($totalMenit, 60) }}<span style="font-size: var(--text-sm);">j {{ $totalMenit % 60 }}m</span></div>
                    <div class="dash-stat-label">Total Durasi</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: var(--warning, #f59e0b);"><i class="fas fa-calendar-day"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $hariAktif }}</div>
                    <div class="dash-stat-label">Hari Aktif</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(139,92,246,0.15); color: var(--purple, #8b5cf6);"><i class="fas fa-location-dot"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $lokasiUnik }}</div>
                    <div class="dash-stat-label">Lokasi Dipakai</div>
                </div>
            </div>
        </div>

        {{-- 4. Isi Jadwal --}}
        <div class="card" style="padding: var(--space-8) var(--space-9); margin-bottom: var(--space-9);">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: var(--space-4); flex-wrap: wrap; margin-bottom: var(--space-7);">
                <div>
                    <h3 style="font-size: var(--text-md); font-weight: var(--font-bold); margin: 0; color: var(--text-color);">
                        <i class="fas fa-calendar-days text-primary me-2"></i>{{ $label }}
                    </h3>
                    <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-1);">
                        @if ($guru)
                            Guru: <strong>{{ $guru->nama }}</strong>{{ !empty($guru->nip) ? ' — NIP ' . $guru->nip : '' }}
                        @else
                            Seluruh guru dalam cakupan jurusan Anda
                        @endif
                    </div>
                </div>
                <span class="badge badge-primary" style="font-size: var(--text-xs);">
                    {{ $mode === 'bulanan' ? 'Bulanan' : 'Mingguan' }}
                </span>
            </div>

            @if ($totalSesi === 0)
                <div style="text-align: center; padding: var(--space-11); color: var(--text-muted);">
                    <i class="fas fa-calendar-xmark" style="font-size: var(--text-3xl); opacity: 0.4; display: block; margin-bottom: var(--space-4);"></i>
                    Tidak ada jadwal praktik pada periode ini.
                </div>
            @elseif ($mode === 'mingguan')
                @foreach ($perHari as $hari => $rows)
                    @continue($rows->isEmpty())
                    <div style="margin-bottom: var(--space-8);">
                        <div style="font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: var(--space-4); padding-bottom: var(--space-2); border-bottom: 2px solid rgba(59,130,246,0.25);">
                            {{ $hari }}
                        </div>
                        <div class="table-responsive-stack">
                            <table class="table" style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: rgba(255,255,255,0.02);">
                                        <th style="padding: 10px 14px; font-size: var(--text-xs); text-align: left; color: var(--text-muted); text-transform: uppercase;">Jam</th>
                                        <th style="padding: 10px 14px; font-size: var(--text-xs); text-align: left; color: var(--text-muted); text-transform: uppercase;">Lokasi</th>
                                        <th style="padding: 10px 14px; font-size: var(--text-xs); text-align: left; color: var(--text-muted); text-transform: uppercase;">Kelas / Mapel</th>
                                        <th style="padding: 10px 14px; font-size: var(--text-xs); text-align: left; color: var(--text-muted); text-transform: uppercase;">Guru</th>
                                        <th style="padding: 10px 14px; font-size: var(--text-xs); text-align: left; color: var(--text-muted); text-transform: uppercase;">Topik</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rows as $r)
                                        <tr style="border-bottom: 1px solid var(--border-color);">
                                            <td data-label="Jam" style="padding: 10px 14px; font-family: var(--font-family-mono); font-size: var(--text-sm); white-space: nowrap;">
                                                {{ substr((string) $r->jam_mulai, 0, 5) }}–{{ substr((string) $r->jam_selesai, 0, 5) }}
                                            </td>
                                            <td data-label="Lokasi" style="padding: 10px 14px; font-size: var(--text-sm);">
                                                {{ $r->ruang_lab_nama }}
                                                <div style="font-size: var(--text-xs); color: var(--text-muted);">
                                                    {{ $jenisLabels[$r->jenis_lokasi] ?? 'Lainnya' }}
                                                </div>
                                            </td>
                                            <td data-label="Kelas / Mapel" style="padding: 10px 14px; font-size: var(--text-sm);">
                                                {{ $r->rombel_nama ?: '-' }}
                                                <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ $r->mata_pelajaran }}</div>
                                            </td>
                                            <td data-label="Guru" style="padding: 10px 14px; font-size: var(--text-sm);">
                                                {{ $r->guru_nama ?: '-' }}
                                                @if (!empty($r->guru_pendamping))
                                                    <div style="font-size: var(--text-xs); color: var(--primary);">+ {{ $r->guru_pendamping }}</div>
                                                @endif
                                            </td>
                                            <td data-label="Topik" style="padding: 10px 14px; font-size: var(--text-sm); color: var(--text-muted);">
                                                {{ $r->topik_praktik ?: '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            @else
                @foreach ($perTanggal as $tgl => $rows)
                    <div style="margin-bottom: var(--space-8);">
                        <div style="font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--primary); margin-bottom: var(--space-4); padding-bottom: var(--space-2); border-bottom: 2px solid rgba(59,130,246,0.25);">
                            {{ \Carbon\Carbon::parse($tgl)->translatedFormat('l, d F Y') }}
                            <span style="font-weight: var(--font-normal); color: var(--text-muted); font-size: var(--text-xs);">
                                &bull; {{ count($rows) }} sesi
                            </span>
                        </div>
                        @foreach ($rows as $r)
                            <div style="display: flex; gap: var(--space-4); padding: var(--space-4) 0; border-bottom: 1px dashed var(--border-color); font-size: var(--text-sm); flex-wrap: wrap;">
                                <span style="font-family: var(--font-family-mono); white-space: nowrap; color: var(--primary); font-weight: var(--font-bold);">
                                    {{ substr((string) $r->jam_mulai, 0, 5) }}–{{ substr((string) $r->jam_selesai, 0, 5) }}
                                </span>
                                <span style="font-weight: var(--font-semibold);">{{ $r->ruang_lab_nama }}</span>
                                <span style="color: var(--text-muted);">{{ $r->rombel_nama ?: '-' }} &bull; {{ $r->mata_pelajaran }}</span>
                                <span style="color: var(--text-muted); margin-left: auto;">{{ $r->guru_nama ?: '-' }}</span>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/laboran-common.js') }}?v={{ file_exists(public_path('js/laboran-common.js')) ? filemtime(public_path('js/laboran-common.js')) : time() }}"></script>
    @endpush
@endsection
