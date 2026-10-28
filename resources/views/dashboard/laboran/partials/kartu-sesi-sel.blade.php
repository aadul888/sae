{{--
    Kartu satu sesi praktik di dalam sel matriks grid Jadwal Laboran.

    Baris matriks = jam ke-N, kolom = kelas. Karena posisi kartu sudah menunjukkan
    jam dan kelasnya, isi kartu dibatasi pada tiga informasi yang dibutuhkan
    laboran: NAMA MAPEL, NAMA GURU, dan TEMPAT/LOKASI PRAKTIK. Jam & pukul tetap
    tersedia sebagai tooltip (`title`) agar tidak memakan ruang.

    Kartu mengisi penuh tinggi sel (`flex: 1 1 0` di dalam `.jadwal-sesi-stack`)
    supaya sel tidak terlihat kosong, dan tetap dibagi rata bila satu sel memuat
    lebih dari satu sesi (team teaching / lahan & lapangan). Bila sesi lebih dari
    satu, nama mapel dipangkas satu baris agar tidak ada kartu yang terpotong.

    Parameter:
    - $item        : baris laboran_jadwal_penggunaan (plus guru_nama, rombel_nama)
    - $jamKe       : label jam, mis. "Jam ke-3" atau "Jam ke-3-4" (untuk tooltip)
    - $jamMulai    : pukul mulai (HH:MM)
    - $jamSelesai  : pukul selesai (HH:MM)
    - $jumlahSesi  : jumlah sesi pada sel yang sama (kelas + jam) — dipakai uji/QA
    - $mapelClamp  : maksimum baris nama mapel (1 bila sel memuat banyak sesi)
    - $canUpdate   : izin ubah
    - $canDelete   : izin hapus
--}}
@php
    $jenisKeyG = $item->jenis_lokasi ?: 'lainnya';
    $borderCol = match ($jenisKeyG) {
        'lab' => 'var(--primary)',
        'bengkel' => 'var(--warning, #f59e0b)',
        'lahan' => 'var(--success, #10b981)',
        'lapangan' => 'var(--purple, #8b5cf6)',
        default => 'var(--text-muted)',
    };
    $jenisIconG = match ($jenisKeyG) {
        'lab' => 'fa-flask',
        'bengkel' => 'fa-screwdriver-wrench',
        'lahan' => 'fa-seedling',
        'lapangan' => 'fa-futbol',
        default => 'fa-location-dot',
    };
    $jamKe = $jamKe ?? '';
    $jamMulai = $jamMulai ?? substr((string) $item->jam_mulai, 0, 5);
    $jamSelesai = $jamSelesai ?? substr((string) $item->jam_selesai, 0, 5);
    $tooltip = trim($jamKe . ' · ' . $jamMulai . '-' . $jamSelesai, ' ·');

    // Satu kartu per sesi; bila sel memuat beberapa sesi, mapel dibatasi satu baris
    // supaya ruang tetap cukup untuk guru + lokasi (tidak terpotong).
    $mapelClamp = (int) ($mapelClamp ?? ((int) ($jumlahSesi ?? 1) > 1 ? 1 : 2));
@endphp
<div class="jadwal-grid-card" title="{{ $tooltip }}"
    style="background: var(--bg-card); border: 1px solid rgba(99,102,241,0.35); border-left: 3px solid {{ $borderCol }}; border-radius: var(--radius-md, 8px); padding: 4px 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); box-sizing: border-box; display: flex; flex-direction: column; justify-content: center; gap: 3px; overflow: hidden;">

    {{-- Nama mata pelajaran (+ aksi ubah/hapus) --}}
    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 4px; min-width: 0;">
        <span style="font-weight: 700; font-size: var(--text-xs, 0.75rem); color: var(--text-color); line-height: 1.2; display: -webkit-box; -webkit-line-clamp: {{ $mapelClamp }}; -webkit-box-orient: vertical; overflow: hidden;"
            title="{{ $item->mata_pelajaran }}">{{ $item->mata_pelajaran }}</span>
        @if ($canUpdate || $canDelete)
            <div class="jadwal-grid-aksi" style="display: flex; align-items: center; gap: 1px; flex-shrink: 0;">
                @if ($canUpdate)
                    <button type="button" class="btn-icon btn-edit-jadwal" data-id="{{ $item->id }}"
                        data-lokasi="{{ $item->ruang_lab_nama }}" data-jenis="{{ $jenisKeyG }}"
                        data-rombel-id="{{ $item->rombel_id }}" data-rombel-nama="{{ $item->rombel_nama }}"
                        data-pembelajaran-id="{{ $item->pembelajaran_id }}" data-mapel="{{ $item->mata_pelajaran }}"
                        data-ptk-id="{{ $item->ptk_id }}" data-tanggal="{{ $item->tanggal }}"
                        data-pemberlakuan="{{ $item->pemberlakuan ?? 'mingguan' }}"
                        data-jam-mulai="{{ substr((string) $item->jam_mulai, 0, 5) }}"
                        data-jam-selesai="{{ substr((string) $item->jam_selesai, 0, 5) }}"
                        data-status="{{ $item->status }}" title="Edit Jadwal"
                        style="width: 18px; height: 18px; font-size: var(--text-2xs, 0.6875rem); padding: 0; line-height: 1;">
                        <i class="fas fa-pencil text-primary"></i>
                    </button>
                @endif
                @if ($canDelete)
                    <button type="button" class="btn-icon btn-delete-jadwal" data-id="{{ $item->id }}"
                        data-mapel="{{ $item->mata_pelajaran }}" title="Hapus Jadwal"
                        style="width: 18px; height: 18px; font-size: var(--text-2xs, 0.6875rem); padding: 0; line-height: 1; color: var(--danger, #ef4444);">
                        <i class="fas fa-trash"></i>
                    </button>
                @endif
            </div>
        @endif
    </div>

    {{-- Nama guru --}}
    <div style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted); display: flex; align-items: center; gap: 4px; min-width: 0;"
        title="{{ $item->guru_nama ?: '-' }}">
        <i class="fas fa-chalkboard-user" style="flex-shrink: 0;"></i>
        <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $item->guru_nama ?: '-' }}</span>
    </div>

    {{-- Tempat / lokasi praktik --}}
    <div style="font-size: var(--text-2xs, 0.6875rem); color: {{ $borderCol }}; font-weight: 700; display: flex; align-items: center; gap: 4px; min-width: 0;"
        title="{{ $item->ruang_lab_nama ?: '-' }}">
        <i class="fas {{ $jenisIconG }}" style="flex-shrink: 0;"></i>
        <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $item->ruang_lab_nama ?: '-' }}</span>
    </div>
</div>
