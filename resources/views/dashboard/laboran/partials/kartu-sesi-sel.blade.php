{{--
    Kartu satu sesi praktik di dalam sel matriks grid Jadwal Laboran.

    Baris matriks = jam ke-N, kolom = kelas. Karena posisi kartu sudah menunjukkan
    jam dan kelasnya, isi kartu dibatasi pada tiga informasi yang dibutuhkan
    laboran: NAMA MAPEL, NAMA GURU, dan TEMPAT/LOKASI PRAKTIK. Jam & pukul tetap
    tersedia sebagai tooltip (`title`) agar tidak memakan ruang.

    UKURAN KARTU: lebar tetap (kolom kelas) dan tinggi MENGISI PENUH sel sesuai
    jumlah JP yang di-rowspan. Isi kartu (mapel, guru, lokasi) dipusatkan secara
    vertikal di tengah kartu agar seimbang pada sesi 1 JP maupun 4 JP.

    TEKS PANJANG: nama mapel maksimum 2 baris (elipsis), guru & lokasi 1 baris
    (elipsis). Teks utuh selalu tersedia lewat atribut `title` (tooltip).

    Parameter:
    - $item        : baris laboran_jadwal_penggunaan (plus guru_nama, rombel_nama)
    - $jamKe       : label jam, mis. "Jam ke-3" atau "Jam ke-3-4" (untuk tooltip)
    - $jamMulai    : pukul mulai (HH:MM)
    - $jamSelesai  : pukul selesai (HH:MM)
    - $jumlahSesi  : jumlah sesi pada sel yang sama (kelas + jam) — dipakai uji/QA
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
@endphp
<div class="jadwal-grid-card" title="{{ $tooltip }}"
    style="background: var(--bg-card); border: 1px solid rgba(99,102,241,0.35); border-left: 3px solid {{ $borderCol }}; border-radius: var(--radius-md, 8px); padding: 4px 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); box-sizing: border-box; display: flex; flex-direction: column; justify-content: center; gap: 2px; overflow: hidden;">

    {{-- Nama mata pelajaran (maks. 2 baris) + aksi ubah/hapus --}}
    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 4px; min-width: 0;">
        <span class="jadwal-grid-card-mapel" title="{{ $item->mata_pelajaran }}">{{ $item->mata_pelajaran }}</span>
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

    {{-- Nama guru (1 baris, elipsis) --}}
    <div style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted); display: flex; align-items: center; gap: 4px; min-width: 0;"
        title="{{ $item->guru_nama ?: '-' }}">
        <i class="fas fa-chalkboard-user" style="flex-shrink: 0;"></i>
        <span class="jadwal-grid-card-1line">{{ $item->guru_nama ?: '-' }}</span>
    </div>

    {{-- Tempat / lokasi praktik (1 baris, elipsis) --}}
    <div style="font-size: var(--text-2xs, 0.6875rem); color: {{ $borderCol }}; font-weight: 700; display: flex; align-items: center; gap: 4px; min-width: 0;"
        title="{{ $item->ruang_lab_nama ?: '-' }}">
        <i class="fas {{ $jenisIconG }}" style="flex-shrink: 0;"></i>
        <span class="jadwal-grid-card-1line">{{ $item->ruang_lab_nama ?: '-' }}</span>
    </div>
</div>
