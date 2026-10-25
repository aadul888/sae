@extends('layouts.dashboard')

@section('title', 'Wali Kelas — Agenda Kelas — SAE')
@section('dash_title', 'Wali Kelas — Agenda Kelas')

@push('styles')
    <link rel="stylesheet"
        href="{{ asset('css/wali-kelas.css') }}?v={{ file_exists(public_path('css/wali-kelas.css')) ? filemtime(public_path('css/wali-kelas.css')) : time() }}">
@endpush

@section('content')
    <div id="waliAgendaApp" data-tanggal="{{ $tanggal }}" data-rombel="{{ $activeRombel?->rombongan_belajar_id }}"
        data-route-store="{{ route('dashboard.wali-kelas.agenda.store') }}"
        data-route-next="{{ route('dashboard.wali-kelas.agenda.next-pertemuan') }}"
        data-route-detail="{{ url('/dashboard/wali-kelas/agenda/detail') }}"
        data-route-update="{{ url('/dashboard/wali-kelas/agenda') }}"
        data-route-destroy="{{ url('/dashboard/wali-kelas/agenda') }}">
        <!-- 1. Header Banner & Actions -->
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div
                    style="width: 44px; height: 44px; border-radius: var(--radius-lg, 12px); background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                    <i class="fas fa-book-open-reader"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0 0 2px 0;">
                        Agenda Kelas Binaan
                    </h2>
                    <p style="color: var(--text-muted); font-size: var(--text-sm, 0.8125rem); margin: 0;">
                        @if ($hasRombel && $activeRombel)
                            Kelas: <strong style="color: var(--text-color);">{{ $activeRombel->nama }}</strong>
                            &bull; Wali Kelas: <strong style="color: var(--primary);">{{ $waliNama }}</strong>
                            &bull; Pengisian agenda sesuai jadwal harian dibantu guru mapel, wali kelas, koordinator, dan
                            petugas piket.
                        @else
                            Monitoring kegiatan belajar mengajar dan pencatatan agenda harian kelas binaan.
                        @endif
                    </p>
                </div>
            </div>

            <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                @if ($canSwitchRombel && $rombelList->isNotEmpty())
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <label for="adminRombelSelect"
                            style="font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-muted); margin: 0; white-space: nowrap;">
                            <i class="fas fa-chalkboard me-1"></i> Rombel:
                        </label>
                        <select id="adminRombelSelect" class="form-control"
                            style="font-size: var(--text-base, 0.875rem); padding: 6px 12px; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color); min-width: 170px;">
                            @foreach ($rombelList as $r)
                                <option value="{{ $r->rombongan_belajar_id }}"
                                    {{ $activeRombel?->rombongan_belajar_id === $r->rombongan_belajar_id ? 'selected' : '' }}>
                                    {{ $r->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if ($hasRombel)
                    <a href="{{ route('dashboard.wali-kelas.agenda.cetak', array_filter(['rombel_id' => $canSwitchRombel ? $activeRombel?->rombongan_belajar_id : null, 'tanggal' => $tab === 'jadwal' ? $tanggal : null])) }}"
                        target="_blank" class="btn btn-outline" title="Cetak Rekap Jurnal Agenda Kelas"
                        style="height: 38px; padding: 0 14px; display: inline-flex; align-items: center; gap: 6px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem); text-decoration: none;">
                        <i class="fas fa-print text-primary"></i>
                        <span>Cetak Jurnal</span>
                    </a>

                    @if ($canCreate)
                        <button type="button" class="btn btn-primary" id="btnOpenCreateAgenda"
                            style="height: 38px; padding: 0 14px; display: inline-flex; align-items: center; gap: 6px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem); font-weight: 700;">
                            <i class="fas fa-plus"></i>
                            <span>Input Agenda Tambahan</span>
                        </button>
                    @endif
                @endif
            </div>
        </div>

        @if (!$hasRombel)
            <!-- Empty State jika belum terikat rombel -->
            <div class="card" style="padding: 40px 20px; text-align: center; margin-top: 20px;">
                <div
                    style="width: 70px; height: 70px; border-radius: 50%; background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b); display: inline-flex; align-items: center; justify-content: center; font-size: var(--text-3xl, 1.875rem); margin-bottom: 16px;">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <h3 style="font-size: var(--text-xl, 1.25rem); font-weight: 700; color: var(--text-color); margin-bottom: 8px;">
                    Belum Ada Rombel Terpilih
                </h3>
                <p style="color: var(--text-muted); font-size: var(--text-base, 0.875rem); max-width: 540px; margin: 0 auto 20px;">
                    Akun Anda belum terikat dengan rombongan belajar aktif sebagai Wali Kelas, Koordinator Kelas, atau
                    Petugas
                    Piket. Silakan hubungi Administrator sistem atau Operator Dapodik sekolah.
                </p>
            </div>
        @else
            <!-- 2. Navigasi Tab Segmented: Agenda Sesuai Jadwal vs Semua Riwayat -->
            <div
                style="display: flex; gap: 8px; margin-bottom: 18px; flex-wrap: wrap; align-items: center; justify-content: space-between;">
                <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                    <a href="{{ route('dashboard.wali-kelas.agenda.index', array_filter(['rombel_id' => $canSwitchRombel ? $activeRombel?->rombongan_belajar_id : null, 'tab' => 'jadwal', 'tanggal' => $tanggal])) }}"
                        class="btn btn-sm {{ $tab === 'jadwal' ? 'btn-primary' : 'btn-outline' }}"
                        style="border-radius: var(--radius-2xl, 20px); padding: 7px 16px; font-size: var(--text-base, 0.875rem); font-weight: 700; display: inline-flex; align-items: center; gap: 7px;">
                        <i class="fas fa-calendar-check"></i>
                        <span>Agenda Sesuai Jadwal ({{ $hari }})</span>
                    </a>
                    <a href="{{ route('dashboard.wali-kelas.agenda.index', array_filter(['rombel_id' => $canSwitchRombel ? $activeRombel?->rombongan_belajar_id : null, 'tab' => 'riwayat'])) }}"
                        class="btn btn-sm {{ $tab === 'riwayat' ? 'btn-primary' : 'btn-outline' }}"
                        style="border-radius: var(--radius-2xl, 20px); padding: 7px 16px; font-size: var(--text-base, 0.875rem); font-weight: 700; display: inline-flex; align-items: center; gap: 7px;">
                        <i class="fas fa-clock-rotate-left"></i>
                        <span>Semua Riwayat Agenda Kelas ({{ number_format($summary['total'] ?? 0) }})</span>
                    </a>
                </div>

                <!-- Identitas Peran Pengisi Aktif -->
                <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
                    <span>Peran Anda:</span>
                    @if ($isAdmin)
                        <span class="badge badge-primary"><i class="fas fa-shield me-1"></i> Administrator</span>
                    @elseif ($isPiket)
                        <span class="badge badge-outline"
                            style="border-color: var(--accent, #0284c7); color: var(--accent, #0284c7); font-weight: 700;"><i
                                class="fas fa-user-shield me-1"></i> Petugas Piket KBM</span>
                    @elseif ($isKoordinator)
                        <span class="badge badge-warning text-dark"><i class="fas fa-crown me-1"></i> Koordinator
                            Kelas</span>
                    @else
                        <span class="badge badge-success"><i class="fas fa-user-tie me-1"></i> Wali Kelas</span>
                    @endif
                </div>
            </div>

            @if ($tab === 'jadwal')
                @include('dashboard.wali-kelas.partials.tab-agenda-jadwal')
            @else
                @include('dashboard.wali-kelas.partials.tab-agenda-riwayat')
            @endif
        @endif

        @include('dashboard.wali-kelas.partials.modal-agenda-kelas')
    </div>
@endsection

@push('scripts')
    <script
        src="{{ asset('js/wali-kelas-agenda.js') }}?v={{ file_exists(public_path('js/wali-kelas-agenda.js')) ? filemtime(public_path('js/wali-kelas-agenda.js')) : time() }}">
    </script>
@endpush
