@php
    $info = $pipStatusInfo ?? \App\Models\PipPengaturan::getStatusInfo($activeTahun ?? null);
    $isClosed = $info['is_closed'] ?? false;
    $isManual = $info['is_locked'] ?? false;
    $isExp = $info['is_expired'] ?? false;
    $cd = $info['countdown'] ?? ['days' => 0, 'hours' => 0, 'minutes' => 0, 'seconds' => 0];
    $judulUsulan =
        $info['judul_usulan'] ??
        'Usulan PIP Tahun ' .
            ($info['tahun'] ?? date('Y')) .
            ' — ' .
            ($info['fase_label'] ?? 'Fase 1 (Januari - Juni)');
@endphp

<style>
    .pip-countdown-card {
        margin-bottom: 22px;
        padding: 16px 20px;
        border-radius: var(--radius-xl, 16px);
        transition: all 0.25s ease;
        position: relative;
        overflow: hidden;
    }

    .pip-countdown-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: {{ $isClosed ? '#ef4444' : 'var(--primary, #6366f1)' }};
    }

    .pip-countdown-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .pip-countdown-left {
        display: flex;
        align-items: center;
        gap: 14px;
        flex: 1 1 320px;
        min-width: 0;
    }

    .pip-countdown-right {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .pip-countdown-boxes {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .pip-cd-box {
        text-align: center;
        min-width: 54px;
        padding: 7px 8px;
        border-radius: var(--radius-lg, 12px);
        background: var(--card-bg, #1e293b);
        border: 1.5px solid rgba(99, 102, 241, 0.25);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        transition: transform 0.2s ease, border-color 0.2s ease;
    }

    .pip-cd-num {
        font-size: var(--text-xl, 1.25rem);
        font-weight: 800;
        color: var(--primary, #6366f1);
        font-family: 'Plus Jakarta Sans', monospace;
        line-height: 1;
    }

    .pip-cd-lbl {
        font-size: var(--text-2xs, 0.6875rem);
        font-weight: 700;
        color: var(--text-muted, #64748b);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 3px;
    }

    .pip-cd-sep {
        font-size: var(--text-xl, 1.25rem);
        font-weight: 800;
        color: var(--primary, #6366f1);
        opacity: 0.6;
        user-select: none;
    }

    @media (max-width: 768px) {
        .pip-countdown-card {
            padding: 14px 16px;
        }

        .pip-countdown-inner {
            flex-direction: column;
            align-items: stretch;
            gap: 14px;
        }

        .pip-countdown-left {
            flex: 1 1 100%;
        }

        .pip-countdown-right {
            justify-content: space-between;
            width: 100%;
            border-top: 1px dashed rgba(255, 255, 255, 0.08);
            padding-top: 12px;
        }

        .pip-cd-box {
            min-width: 48px;
            padding: 6px 6px;
        }

        .pip-cd-num {
            font-size: var(--text-xl, 1.25rem);
        }
    }

    @media (max-width: 480px) {
        .pip-countdown-right {
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
        }

        .pip-countdown-boxes {
            justify-content: center;
            width: 100%;
        }

        .pip-cd-box {
            min-width: 42px;
            padding: 5px 4px;
        }

        .pip-cd-num {
            font-size: var(--text-lg, 1.125rem);
        }

        .pip-cd-lbl {
            font-size: var(--text-2xs, 0.6875rem);
        }
    }
</style>

<div class="card pip-countdown-card" id="pipCountdownWrap" data-deadline="{{ $info['deadline_iso'] ?? '' }}"
    data-locked="{{ $isManual ? '1' : '0' }}" data-autolock="{{ $info['kunci_otomatis'] ?? true ? '1' : '0' }}"
    data-closed="{{ $isClosed ? '1' : '0' }}"
    style="border: 1.5px solid {{ $isClosed ? 'rgba(239,68,68,0.35)' : 'rgba(99,102,241,0.3)' }}; background: {{ $isClosed ? 'linear-gradient(135deg, rgba(239,68,68,0.06) 0%, rgba(245,158,11,0.04) 100%)' : 'linear-gradient(135deg, rgba(99,102,241,0.08) 0%, rgba(16,185,129,0.05) 100%)' }};">
    <div class="pip-countdown-inner">
        <!-- Info Kiri: Judul Usulan, Fase, Status & Batas Waktu -->
        <div class="pip-countdown-left">
            <div
                style="width: 44px; height: 44px; border-radius: var(--radius-lg, 12px); background: {{ $isClosed ? 'rgba(239,68,68,0.14)' : 'rgba(99,102,241,0.14)' }}; color: {{ $isClosed ? '#ef4444' : 'var(--primary, #6366f1)' }}; display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                <i class="fas {{ $isClosed ? 'fa-lock' : 'fa-hourglass-half fa-spin-pulse' }}"></i>
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px;">
                    <span style="font-size: var(--text-md, 1rem); font-weight: 800; color: var(--text-color); line-height: 1.3;">
                        {{ $judulUsulan }}
                    </span>
                    <span class="badge badge-primary"
                        style="font-size: var(--text-xs, 0.75rem); padding: 2px 8px; font-weight: 700; border-radius: var(--radius-2xl, 20px);">
                        <i class="fas fa-layer-group me-1"></i> {{ $info['fase_label'] ?? 'Fase 1' }}
                    </span>
                    @if (!empty($info['tahun_pelajaran']))
                        <span class="badge badge-outline" style="font-size: var(--text-xs, 0.75rem); padding: 2px 7px;">
                            <i class="fas fa-graduation-cap me-1"></i> TP {{ $info['tahun_pelajaran'] }}
                        </span>
                    @endif
                    <span id="pipCdBadge" class="badge {{ $isClosed ? 'badge-danger' : 'badge-success' }}"
                        style="font-size: var(--text-xs, 0.75rem); padding: 2px 8px; font-weight: 700; border-radius: var(--radius-2xl, 20px);">
                        @if ($isManual)
                            <i class="fas fa-lock me-1"></i> Terkunci Manual
                        @elseif ($isExp)
                            <i class="fas fa-clock-rotate-left me-1"></i> Batas Waktu Berakhir
                        @else
                            <i class="fas fa-lock-open me-1"></i> Pengusulan Dibuka
                        @endif
                    </span>
                </div>

                <div
                    style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <span>
                        <i class="fas fa-calendar-day text-primary me-1"></i>
                        Batas Akhir: <strong>{{ $info['deadline_formatted'] }}</strong>
                    </span>
                    @if ($isClosed)
                        <span style="color: #ef4444; font-weight: 700;">
                            <i class="fas fa-circle-exclamation me-1"></i>
                            Wali Kelas tidak dapat mengubah / menambah usulan
                        </span>
                    @else
                        <span style="color: #10b981; font-weight: 600;">
                            <i class="fas fa-circle-check me-1"></i>
                            Wali Kelas dapat mengusulkan siswa sesuai kuota rombel
                        </span>
                    @endif
                </div>

                @if (!empty($info['pesan_pengumuman']))
                    <div
                        style="margin-top: 6px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); background: rgba(0,0,0,0.03); border-left: 3px solid {{ $isClosed ? '#ef4444' : 'var(--primary, #6366f1)' }}; padding: 4px 10px; border-radius: 0 6px 6px 0;">
                        {{ $info['pesan_pengumuman'] }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Timer Kanan: Hitung Mundur Realtime & Tombol Pengaturan -->
        <div class="pip-countdown-right">
            @if ($isClosed)
                <div
                    style="display: flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: var(--radius-lg, 12px); background: rgba(239,68,68,0.12); border: 1.5px solid rgba(239,68,68,0.3); color: #ef4444; font-weight: 800; font-size: var(--text-base, 0.875rem);">
                    <i class="fas fa-lock" style="font-size: var(--text-xl, 1.25rem);"></i>
                    <span>PENGUSULAN DITUTUP</span>
                </div>
            @else
                <div class="pip-countdown-boxes">
                    <div class="pip-cd-box">
                        <div id="pipCdDays" class="pip-cd-num">
                            {{ sprintf('%02d', $cd['days'] ?? 0) }}
                        </div>
                        <div class="pip-cd-lbl">Hari</div>
                    </div>
                    <span class="pip-cd-sep">:</span>
                    <div class="pip-cd-box">
                        <div id="pipCdHours" class="pip-cd-num">
                            {{ sprintf('%02d', $cd['hours'] ?? 0) }}
                        </div>
                        <div class="pip-cd-lbl">Jam</div>
                    </div>
                    <span class="pip-cd-sep">:</span>
                    <div class="pip-cd-box">
                        <div id="pipCdMinutes" class="pip-cd-num">
                            {{ sprintf('%02d', $cd['minutes'] ?? 0) }}
                        </div>
                        <div class="pip-cd-lbl">Menit</div>
                    </div>
                    <span class="pip-cd-sep">:</span>
                    <div class="pip-cd-box" style="border-color: rgba(16,185,129,0.35);">
                        <div id="pipCdSeconds" class="pip-cd-num" style="color: #10b981;">
                            {{ sprintf('%02d', $cd['seconds'] ?? 0) }}
                        </div>
                        <div class="pip-cd-lbl">Detik</div>
                    </div>
                </div>
            @endif

            @if (!empty($isAdmin))
                <a href="{{ route('dashboard.pip.index', array_filter(['tab' => 'pengaturan', 'tahun' => $activeTahun ?? null])) }}"
                    class="btn btn-outline btn-sm"
                    style="height: 38px; padding: 0 12px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; display: inline-flex; align-items: center; gap: 6px; border-radius: var(--radius-md, 8px); border-color: rgba(99,102,241,0.35); text-decoration: none;"
                    title="Buka Pengaturan Kunci, Fase &amp; Batas Waktu">
                    <i class="fas fa-gear text-primary"></i>
                    <span>Atur Batas &amp; Fase</span>
                </a>
            @endif
        </div>
    </div>
</div>
