{{--
    Kartu satu jadwal di dalam sel matriks grid Jadwal KBM.
    Dipakai untuk satu atau beberapa jadwal pada sel yang sama (team teaching /
    dua guru pada jam yang sama) agar tidak saling menimpa.

    Parameter:
    - $item       : baris jadwal_kbm (memuat nama_mata_pelajaran, nama_guru, dst.)
    - $durasi     : jumlah JP blok jadwal ini
    - $isBerderet : true bila sel memuat lebih dari satu jadwal
    - $canUpdate  : izin ubah
    - $canDelete  : izin hapus
--}}
@php
    $isUpacara = $item->mata_pelajaran_id === 'UPACARA';
    $isPembiasaan = $item->mata_pelajaran_id === 'PEMBIASAAN';
    $isIstirahat = $item->mata_pelajaran_id === 'ISTIRAHAT';
    $isRoutine = $isUpacara || $isPembiasaan || $isIstirahat;
    $borderCol = $isUpacara
        ? '#ef4444'
        : ($isPembiasaan
            ? '#10b981'
            : ($isIstirahat
                ? '#f59e0b'
                : 'var(--primary)'));
    $bgCard = $isUpacara
        ? 'rgba(239,68,68,0.03)'
        : ($isPembiasaan
            ? 'rgba(16,185,129,0.03)'
            : ($isIstirahat
                ? 'rgba(245,158,11,0.04)'
                : 'var(--bg-card)'));
    $minHeight = $isBerderet ? max(46, $durasi * 64 - 14) : max(54, $durasi * 64 - 10);
@endphp
<div class="jadwal-grid-card"
    style="background: {{ $bgCard }}; border: 1px solid {{ $isRoutine ? $borderCol : 'rgba(99,102,241,0.35)' }}; border-left: 4px solid {{ $borderCol }}; border-radius: var(--radius-md, 8px); padding: 6px 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); position: relative; {{ $isBerderet ? '' : 'height: calc(100% - 2px);' }} min-height: {{ $minHeight }}px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; overflow: hidden;">
    <div>
        <!-- Mapel Title -->
        <div style="font-weight: 700; font-size: var(--text-sm, 0.8125rem); color: var(--text-color); line-height: 1.25; margin-bottom: 3px; display: -webkit-box; -webkit-line-clamp: {{ $durasi > 1 && !$isBerderet ? '3' : '1' }}; -webkit-box-orient: vertical; overflow: hidden;"
            title="{{ $item->nama_mata_pelajaran }}">
            @if ($isUpacara)
                <i class="fas fa-flag text-danger me-1"></i>
            @elseif ($isPembiasaan)
                <i class="fas fa-hands-praying text-success me-1"></i>
            @elseif ($isIstirahat)
                <i class="fas fa-mug-hot text-warning me-1"></i>
            @endif
            {{ $item->nama_mata_pelajaran }}
        </div>

        <!-- Guru / Subtitle -->
        <div
            style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); display: flex; align-items: center; gap: 4px; margin-bottom: 4px;">
            @if ($isUpacara)
                <i class="fas fa-users text-danger" style="font-size: var(--text-xs, 0.75rem); flex-shrink: 0;"></i>
                <span
                    style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px;">Dewan
                    Guru &amp; Siswa</span>
            @elseif ($isPembiasaan)
                <i class="fas fa-users text-success" style="font-size: var(--text-xs, 0.75rem); flex-shrink: 0;"></i>
                <span
                    style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px;">Wali
                    Kelas &amp; Guru</span>
            @elseif ($isIstirahat)
                <i class="fas fa-mug-hot text-warning" style="font-size: var(--text-xs, 0.75rem); flex-shrink: 0;"></i>
                <span
                    style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px;">Siswa
                    &amp; Dewan Guru</span>
            @else
                <i class="fas fa-chalkboard-user text-primary"
                    style="font-size: var(--text-xs, 0.75rem); flex-shrink: 0;"></i>
                <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px;"
                    title="{{ $item->nama_guru }}">
                    {{ $item->nama_guru ?: 'Belum Ada Guru' }}
                </span>
            @endif
        </div>

        @if ($durasi >= 4 && !$isBerderet)
            <div
                style="font-size: var(--text-2xs, 0.6875rem); color: var(--primary); font-weight: 600; margin-bottom: 4px; display: flex; align-items: center; gap: 4px;">
                <i class="fas fa-cubes"></i> Blok Praktik ({{ $durasi }} JP)
            </div>
        @endif
    </div>

    <!-- Badges & Action Buttons Footer -->
    <div
        style="margin-top: 4px; padding-top: 4px; border-top: 1px dashed var(--border-color); display: flex; align-items: center; justify-content: space-between; gap: 4px;">
        <div style="display: flex; gap: 3px; flex-wrap: wrap; align-items: center;">
            @if ($isUpacara)
                <span class="badge"
                    style="background: rgba(239,68,68,0.12); color: var(--danger, #ef4444); font-size: var(--text-2xs, 0.6875rem); padding: 2px 5px; font-weight: 700;">
                    Upacara
                </span>
            @elseif ($isPembiasaan)
                <span class="badge"
                    style="background: rgba(16,185,129,0.12); color: var(--success, #10b981); font-size: var(--text-2xs, 0.6875rem); padding: 2px 5px; font-weight: 700;">
                    Pembiasaan
                </span>
            @elseif ($isIstirahat)
                <span class="badge"
                    style="background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b); font-size: var(--text-2xs, 0.6875rem); padding: 2px 5px; font-weight: 700;">
                    Istirahat
                </span>
            @else
                <span class="badge badge-primary"
                    style="font-size: var(--text-2xs, 0.6875rem); padding: 2px 5px; font-weight: 700;">
                    {{ $durasi }} JP
                </span>
            @endif

            @if (!empty($item->ruangan))
                <span class="badge"
                    style="background: var(--bg-hover); color: var(--text-color); font-size: var(--text-2xs, 0.6875rem); padding: 2px 5px; border: 1px solid var(--border-color);"
                    title="Ruangan: {{ $item->ruangan }}">
                    <i class="fas fa-door-open"></i> {{ $item->ruangan }}
                </span>
            @endif
        </div>

        <!-- Action Buttons (Quick Edit & Delete) -->
        @if ($isRoutine)
            <span class="badge"
                style="background: var(--bg-hover); color: var(--text-muted); font-size: var(--text-2xs, 0.6875rem); padding: 2px 5px;"
                title="Disinkronkan otomatis dari Pengaturan KBM">
                <i class="fas fa-sync-alt me-1"></i> Rutin
            </span>
        @elseif ($canUpdate || $canDelete)
            <div style="display: flex; align-items: center; gap: 2px; flex-shrink: 0;">
                @if ($canUpdate)
                    <button type="button" class="btn-icon btn-edit-jadwal" title="Edit Jadwal"
                        data-id="{{ $item->id }}" data-rombel="{{ $item->rombongan_belajar_id }}"
                        data-pembelajaran="{{ $item->pembelajaran_id }}" data-hari="{{ $item->hari }}"
                        data-jam-ke-mulai="{{ $item->jam_ke_mulai }}"
                        data-jam-ke-selesai="{{ $item->jam_ke_selesai }}"
                        data-jam-mulai="{{ substr($item->jam_mulai, 0, 5) }}"
                        data-jam-selesai="{{ substr($item->jam_selesai, 0, 5) }}"
                        data-ruangan="{{ $item->ruangan }}" data-keterangan="{{ $item->keterangan }}"
                        style="width: 22px; height: 22px; font-size: var(--text-2xs, 0.6875rem); padding: 0;">
                        <i class="fas fa-pencil text-primary"></i>
                    </button>
                @endif
                @if ($canDelete)
                    <form action="{{ route('dashboard.jadwal-kbm.destroy', $item->id) }}"
                        method="POST" class="form-hapus-jadwal" data-confirm="delete"
                        data-name="{{ $item->nama_mata_pelajaran }} ({{ $namaRombel ?? '' }})"
                        style="display: inline; margin: 0;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-icon" title="Hapus Jadwal"
                            style="width: 22px; height: 22px; font-size: var(--text-2xs, 0.6875rem); padding: 0;">
                            <i class="fas fa-trash text-danger"></i>
                        </button>
                    </form>
                @endif
            </div>
        @endif
    </div>
</div>
