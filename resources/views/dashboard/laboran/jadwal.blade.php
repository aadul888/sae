@extends('layouts.dashboard')

@section('title', 'Jadwal Praktik & Penggunaan Lokasi - Laboratorium SAE')
@section('dash_title', 'Jadwal Praktik')

@push('styles')
    <style>
        /* Kartu sesi: LEBAR TETAP (mengikuti lebar kolom kelas) dan tinggi
           MENGISI PENUH SEL sesuai jumlah JP: sesi 1 JP setinggi satu baris jam,
           sesi 3 JP setinggi tiga baris jam (rowspan). Teks panjang membungkus
           ke bawah sehingga tidak terpotong. Lebar kolom dikunci agar kartu
           tidak melebar atau menyempit saat isinya berbeda-beda. */
        .jadwal-grid-kelas {
            --jadwal-col-w: 150px;
            --jadwal-card-gap: 3px;
            /* Tinggi minimum satu baris jam (min-height isi 52px + padding sel). */
            --jadwal-row-h: 58px;
        }

        .jadwal-grid-kelas thead th:not(:first-child),
        .jadwal-grid-kelas tbody td:not(:first-child) {
            width: var(--jadwal-col-w);
            min-width: var(--jadwal-col-w);
            max-width: var(--jadwal-col-w);
        }

        .jadwal-sesi-stack {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            gap: var(--jadwal-card-gap);
            min-width: 0;
            /* Sel matrix height:auto, jadi padding-bottom dihitung dari tinggi
               sel hasil rowspan dan mendorong isi (kartu + tombol) melar penuh. */
            height: 100%;
            box-sizing: border-box;
        }

        /* Kartu mengisi penuh tinggi sel: satu kartu tunggal melar mengikuti
           rowspan sesinya, beberapa kartu saling berbagi ruang secara merata.
           Isi kartu (mapel + guru + lokasi) berada di TENGAH kartu secara
           vertikal, sehingga seimbang di sel 1 JP maupun sel rowspan. */
        .jadwal-sesi-stack>.jadwal-grid-card {
            flex: 1 1 auto;
            width: 100%;
            justify-content: center !important;
        }

        /* Tombol aksi dalam sel (Isi / rutinitas / kosong) juga mengisi sel. */
        .td-sesi>button,
        .td-sesi>div {
            height: 100%;
            box-sizing: border-box;
        }

        /* TEKS PANJANG: dibungkus (wrap) agar tetap terbaca utuh. Nama mapel
           dibatasi maksimum 2 baris dengan elipsis supaya kartu tidak melar
           berlebihan; guru & lokasi dipotong 1 baris. Teks utuh selalu tersedia
           lewat tooltip (title). */
        .jadwal-grid-card-mapel {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            overflow-wrap: anywhere;
            font-weight: 700;
            font-size: var(--text-xs, 0.75rem);
            color: var(--text-color);
            line-height: 1.2;
            min-width: 0;
        }

        .jadwal-grid-card-1line {
            display: block;
            min-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
@endpush

@section('content')
    @include('dashboard.laboran.partials.header-actions')
    <div class="dash-content-inner">
        {{-- 1. Header Banner & Action Buttons --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap; margin-bottom: var(--space-9);">
            <div style="display: flex; align-items: center; gap: var(--space-7);">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl); flex-shrink: 0;">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--text-color); margin: 0;">
                        Jadwal Praktik &amp; Penggunaan Lokasi
                    </h2>
                    <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-1);">
                        Penjadwalan guru untuk praktik di laboratorium, bengkel, lahan/kebun, dan lapangan sekolah.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions dash-banner-access">
                @if ($canRead)
                    <div class="jadwal-view-switcher"
                        style="display: inline-flex; gap: 4px; background: var(--bg-hover); padding: 3px; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color);">
                        <a href="{{ route('dashboard.laboran.jadwal.index', array_merge(request()->query(), ['view_mode' => 'grid'])) }}"
                            class="btn {{ ($viewMode ?? 'grid') === 'grid' ? 'btn-primary' : 'btn-outline' }}"
                            style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border: none; border-radius: var(--radius-sm, 6px); box-shadow: none; font-size: var(--text-md, 1rem);"
                            title="Tampilan Matriks Grid" aria-label="Tampilan Matriks Grid">
                            <i class="fas fa-table-cells"></i>
                        </a>
                        <a href="{{ route('dashboard.laboran.jadwal.index', array_merge(request()->query(), ['view_mode' => 'table'])) }}"
                            class="btn {{ ($viewMode ?? 'grid') === 'table' ? 'btn-primary' : 'btn-outline' }}"
                            style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border: none; border-radius: var(--radius-sm, 6px); box-shadow: none; font-size: var(--text-md, 1rem);"
                            title="Tampilan Rekap Tabel" aria-label="Tampilan Rekap Tabel">
                            <i class="fas fa-list"></i>
                        </a>
                    </div>

                    {{-- Dropdown Cetak Jadwal Multi-Mode --}}
                    <div style="position: relative; display: inline-block;">
                        <button type="button" class="btn btn-outline dash-banner-access__trigger" id="btnDropdownCetakJadwal"
                            title="Pilihan Cetak Jadwal Praktik" aria-label="Pilihan Cetak Jadwal Praktik"
                            aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-print"></i>
                        </button>
                        <div id="dropdownMenuCetakJadwal"
                            style="display: none; position: absolute; right: 0; top: calc(100% + 6px); background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); box-shadow: 0 10px 25px rgba(0,0,0,0.25); min-width: 220px; z-index: 1000; overflow: hidden;">
                            <a href="{{ route('dashboard.laboran.jadwal.cetak', ['mode_jadwal' => 'mingguan']) }}" target="_blank"
                                style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; color: var(--text-color); text-decoration: none; font-size: var(--text-sm); border-bottom: 1px solid var(--border-color);">
                                <i class="fas fa-calendar-week text-primary" style="width: 16px;"></i>
                                <div>
                                    <strong>Jadwal Mingguan</strong>
                                    <div style="font-size: var(--text-2xs); color: var(--text-muted);">Sesi berulang per minggu</div>
                                </div>
                            </a>
                            <a href="{{ route('dashboard.laboran.jadwal.cetak', ['mode_jadwal' => 'semester']) }}" target="_blank"
                                style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; color: var(--text-color); text-decoration: none; font-size: var(--text-sm); border-bottom: 1px solid var(--border-color);">
                                <i class="fas fa-calendar-range text-success" style="width: 16px;"></i>
                                <div>
                                    <strong>Jadwal Semester</strong>
                                    <div style="font-size: var(--text-2xs); color: var(--text-muted);">Rekap per bulan satu semester</div>
                                </div>
                            </a>
                            <a href="{{ route('dashboard.laboran.laporan.index') }}"
                                style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; color: var(--text-color); text-decoration: none; font-size: var(--text-sm);">
                                <i class="fas fa-file-lines text-warning" style="width: 16px;"></i>
                                <div>
                                    <strong>Laporan Lokasi</strong>
                                    <div style="font-size: var(--text-2xs); color: var(--text-muted);">Bulan, triwulan, tahunan</div>
                                </div>
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- 2. Stat Grid (4 Kartu Ringkasan) --}}
        <div class="dash-stat-grid"
            style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: var(--space-10); gap: var(--space-7);">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statTotal, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Total Sesi Terjadwal</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statHariIni, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Praktik Hari Ini</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statBerlangsung, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Sedang Berlangsung</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statSelesai, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Praktik Selesai</div>
                </div>
            </div>
        </div>

        {{-- 2b. Tab Pemberlakuan Jadwal (Semester / Mingguan) � Default Semester, Ketat 1 Mode --}}
        @if ($canRead)
            @php
                $activeMode = $pengaturanJadwal->mode_pemberlakuan ?? 'semester';
                $modeDiberlakukan = $activeMode;
            @endphp
            <div class="periode-nav-wrapper" style="margin-bottom: var(--space-8);">
                <div class="periode-nav-desktop">
                    @foreach ($modePemberlakuanLabels as $mKey => $mLabel)
                        @php
                            $isThisActive = ($activeMode === $mKey);
                        @endphp
                        <a href="{{ route('dashboard.laboran.jadwal.index', array_merge(request()->query(), ['mode_pemberlakuan' => $mKey])) }}"
                            class="periode-nav-tab btn-tab-pemberlakuan {{ $isThisActive ? 'active' : '' }}"
                            data-mode="{{ $mKey }}"
                            data-label="{{ $mLabel }}"
                            data-is-active="{{ $isThisActive ? '1' : '0' }}"
                            title="Klik untuk memberlakukan Jadwal {{ $mLabel }}">
                            <i class="fas {{ $mKey === 'mingguan' ? 'fa-calendar-week' : 'fa-calendar-range' }}"></i>
                            {{ $mLabel }}
                            @if ($isThisActive)
                                <span class="badge badge-diberlakukan" style="font-size: var(--text-2xs); margin-left: 6px;">
                                    <i class="fas fa-check-circle me-1"></i> Diberlakukan
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- 3a. Matriks Grid (pola Jadwal KBM) --}}
        @if (($viewMode ?? 'grid') === 'grid')
            <!-- Bar Informasi Grid + pemilih hari -->
            <div class="card" id="jadwalTabBarContainer" style="padding: 12px 16px; margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <span class="badge badge-primary" style="font-size: var(--text-sm, 0.8125rem); padding: 4px 8px;">
                            <i class="fas fa-table-cells me-1"></i> Matriks Jam Pelajaran &amp; Kelas
                        </span>
                        <span style="font-size: var(--text-sm, 0.8125rem);">
                            {{ $gridKelas->count() }} kelas dalam cakupan Anda
                        </span>
                    </div>
                    <div style="display: flex; gap: 14px; align-items: center; flex-wrap: wrap;">
                        <span><span style="display: inline-block; width: 10px; height: 10px; border-radius: var(--radius-xs, 4px); background: rgba(99,102,241,0.2); border: 1px solid var(--primary); margin-right: 4px;"></span> Terjadwal</span>
                        <span><span style="display: inline-block; width: 10px; height: 10px; border-radius: var(--radius-xs, 4px); background: var(--bg-hover); border: 1px dashed var(--border-color); margin-right: 4px;"></span> Kosong (Bisa Diisi)</span>
                    </div>
                </div>

                <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border-color); display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                    <span style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-right: 4px; white-space: nowrap;">
                        <i class="fas fa-calendar-day me-1"></i> Hari:
                    </span>
                    @foreach ($gridLokasi as $hari)
                        <a href="{{ route('dashboard.laboran.jadwal.index', array_merge(request()->query(), ['view_mode' => 'grid', 'hari_grid' => $hari])) }}"
                            class="btn {{ $selectedHari === $hari ? 'btn-primary' : 'btn-outline' }}"
                            style="padding: 4px 10px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-sm, 6px);">
                            {{ $hari }}
                            <span class="badge" style="background: {{ $selectedHari === $hari ? 'rgba(255,255,255,0.25)' : 'rgba(99,102,241,0.15)' }}; color: {{ $selectedHari === $hari ? 'inherit' : 'var(--primary)' }}; font-size: var(--text-2xs, 0.6875rem); padding: 1px 5px; margin-left: 3px;">
                                {{ $countPerHari[$hari] ?? 0 }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>

            @if ($gridKelas->isEmpty())
                <div class="card" style="padding: 32px 20px; text-align: center; margin-bottom: 24px;">
                    <i class="fas fa-users-slash" style="font-size: var(--text-2xl, 1.5rem); color: var(--text-muted); opacity: 0.5;"></i>
                    <div style="margin-top: 10px; font-weight: 700; color: var(--text-color);">Belum ada kelas dalam cakupan Anda</div>
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 4px;">
                        Hubungi admin untuk menetapkan jurusan penugasan laboran.
                    </div>
                </div>
            @else
                <!-- Matriks: baris = Jam ke-1..13, kolom = KELAS (dikelompokkan per jurusan) -->
                <div class="card table-responsive-stack" id="gridMatrixContainer"
                    style="padding: 0; margin-bottom: 24px; overflow-x: auto;">
                    <table class="table jadwal-grid-kelas"
                        style="width: max-content; min-width: 100%; border-collapse: separate; border-spacing: 0;">
                        <thead>
                            <tr>
                                <th style="position: sticky; left: 0; z-index: 20; background: var(--bg-card); width: 120px; min-width: 120px; padding: 10px 12px; border-bottom: 2px solid var(--border-color); border-right: 2px solid var(--border-color); font-size: var(--text-sm, 0.8125rem); font-weight: 800; color: var(--text-color); text-transform: uppercase; text-align: left;">
                                    <div style="display: flex; align-items: center; gap: 6px;">
                                        <i class="fas fa-clock text-primary"></i> Jam
                                    </div>
                                </th>
                                @foreach ($gridGroups as $grup)
                                    @foreach ($grup['kelas'] as $kelas)
                                        <th style="padding: 8px 6px; border-bottom: 2px solid var(--border-color); border-right: 1px solid var(--border-color); text-align: center; min-width: 130px; background: var(--bg-card);">
                                            <div style="font-weight: 800; font-size: var(--text-sm, 0.8125rem); color: var(--text-color); line-height: 1.2;">
                                                {{ $kelas->nama }}
                                            </div>
                                            <div style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted); font-weight: 600; margin-top: 2px;">
                                                {{ $grup['label'] }}
                                            </div>
                                        </th>
                                    @endforeach
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @for ($jam = 1; $jam <= $maxSlots; $jam++)
                                @php $slot = $slotsPerHari[$selectedHari][$jam] ?? null; @endphp
                                <tr>
                                    <td style="position: sticky; left: 0; z-index: 10; background: var(--bg-card); padding: 6px 10px; border-bottom: 1px solid var(--border-color); border-right: 2px solid var(--border-color); vertical-align: middle;">
                                        <div style="font-weight: 800; font-size: var(--text-sm, 0.8125rem); color: var(--text-color);">
                                            Jam ke-{{ $jam }}
                                        </div>
                                        <div style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted);">
                                            @if ($slot)
                                                {{ $slot['mulai'] }}-{{ $slot['selesai'] }}
                                            @else
                                                &mdash;
                                            @endif
                                        </div>
                                    </td>

                                    @foreach ($gridGroups as $grup)
                                        @foreach ($grup['kelas'] as $kelas)
                                            @php
                                                $rid = (string) $kelas->rombongan_belajar_id;

                                                // Rutinitas KBM pada jam ini (tidak boleh dipakai praktik).
                                                $rutinLabel = null;
                                                if ($slot) {
                                                    if ($rutinitas['upacara']['aktif'] && $rutinitas['upacara']['hari'] === $selectedHari && $jam === $rutinitas['upacara']['jam_ke']) {
                                                        $rutinLabel = $rutinitas['upacara']['nama'];
                                                    } elseif ($rutinitas['pembiasaan']['aktif'] && $rutinitas['pembiasaan']['hari'] === $selectedHari && $jam === $rutinitas['pembiasaan']['jam_ke']) {
                                                        $rutinLabel = $rutinitas['pembiasaan']['nama'];
                                                    } elseif (!empty($slot['is_break'])) {
                                                        $rutinLabel = 'Istirahat';
                                                    }
                                                }
                                            @endphp

                                            @if (!empty($gridOccupied[$rid][$jam]))
                                                {{-- Baris ini sudah tercakup kartu multi-JP (rowspan) di atasnya. --}}
                                                @elseif (isset($gridClusters[$rid][$jam]))
                                                    @php $cluster = $gridClusters[$rid][$jam]; @endphp
                                                    <td rowspan="{{ $cluster['span'] }}" class="td-sesi"
                                                        style="padding: 2px; border-bottom: 1px solid var(--border-color); border-right: 1px solid var(--border-color); vertical-align: top; background: rgba(99,102,241,0.03); box-sizing: border-box; min-height: calc(var(--jadwal-row-h) * {{ $cluster['span'] }});">
                                                        @php $jumlahSesi = count($cluster['sesi']); @endphp
                                                        {{-- Tinggi sel tabel bersifat auto, sehingga `height: 100%` pada
                                                             anak <td> tidak resolve. Tinggi stack dihitung eksplisit dari
                                                             jumlah JP (rowspan) agar kartu benar-benar mengisi penuh sel. --}}
                                                        <div class="jadwal-sesi-stack" data-jumlah-sesi="{{ $jumlahSesi }}"
                                                            style="min-height: calc(var(--jadwal-row-h) * {{ $cluster['span'] }} - {{ 4 + ($jumlahSesi - 1) * 3 }}px);">
                                                        @foreach ($cluster['sesi'] as $sesi)
                                                            @include('dashboard.laboran.partials.kartu-sesi-sel', [
                                                                'item' => $sesi['item'],
                                                                'jamKe' => $sesi['jamKe'],
                                                                'jamMulai' => $sesi['jamMulai'],
                                                                'jamSelesai' => $sesi['jamSelesai'],
                                                                'mapelClamp' => $jumlahSesi > 1 ? 1 : 2,
                                                                'canUpdate' => $canUpdate,
                                                                'canDelete' => $canDelete,
                                                            ])
                                                        @endforeach
                                                    </div>
                                                </td>
                                            @elseif (!$slot)
                                                <td class="td-sesi" style="padding: 3px; border-bottom: 1px solid var(--border-color); border-right: 1px solid var(--border-color);">
                                                    <div style="min-height: 52px; display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: var(--text-2xs, 0.6875rem);">-</div>
                                                </td>
                                            @elseif ($rutinLabel)
                                                <td class="td-sesi" style="padding: 3px; border-bottom: 1px solid var(--border-color); border-right: 1px solid var(--border-color);">
                                                    <div style="min-height: 52px; display: flex; align-items: center; justify-content: center; text-align: center; padding: 2px; border-radius: var(--radius-sm, 6px); background: {{ $rutinLabel === 'Istirahat' ? 'rgba(245,158,11,0.06)' : 'rgba(239,68,68,0.06)' }}; border: 1px dashed {{ $rutinLabel === 'Istirahat' ? 'rgba(245,158,11,0.35)' : 'rgba(239,68,68,0.35)' }}; color: var(--text-muted); font-size: var(--text-2xs, 0.6875rem); font-weight: 700;">
                                                        {{ $rutinLabel }}
                                                    </div>
                                                </td>
                                            @elseif ($canCreate)
                                                <td class="td-sesi" style="padding: 3px; border-bottom: 1px solid var(--border-color); border-right: 1px solid var(--border-color);">
                                                    <button type="button" class="btn-quick-slot"
                                                        title="Isi jadwal {{ $kelas->nama }} pada {{ $selectedHari }} Jam ke-{{ $jam }}"
                                                        data-hari="{{ $selectedHari }}" data-jam-ke="{{ $jam }}"
                                                        data-rombel-id="{{ $rid }}" data-rombel-nama="{{ $kelas->nama }}"
                                                        data-jam-mulai="{{ $slot['mulai'] }}" data-jam-selesai="{{ $slot['selesai'] }}"
                                                        style="width: 100%; min-height: 52px; border: 1px dashed var(--border-color); border-radius: var(--radius-sm, 6px); background: transparent; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 3px; color: var(--text-muted); transition: all 0.2s ease;">
                                                        <i class="fas fa-plus" style="font-size: var(--text-2xs, 0.6875rem);"></i>
                                                        <span style="font-size: var(--text-2xs, 0.6875rem); font-weight: 600;">Isi</span>
                                                    </button>
                                                </td>
                                            @else
                                                <td class="td-sesi" style="padding: 3px; border-bottom: 1px solid var(--border-color); border-right: 1px solid var(--border-color);">
                                                    <div style="min-height: 52px; display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: var(--text-2xs, 0.6875rem);">-</div>
                                                </td>
                                            @endif
                                        @endforeach
                                    @endforeach
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            @endif
        @else
        {{-- 3b. Datatable Terpadu --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.laboran.jadwal.index') }}" id="filterForm" style="margin: 0;">
                    <div style="display: flex; flex-wrap: wrap; gap: var(--space-6); justify-content: space-between; align-items: center;">
                        <div style="display: flex; flex-wrap: wrap; gap: var(--space-5); align-items: center;">
                            <div class="toolbar-entries">
                                <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                                <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                                    @foreach ([10, 15, 25, 50, 100] as $n)
                                        <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>
                                            {{ $n }}</option>
                                    @endforeach
                                </select>
                                <span>entri</span>
                            </div>

                            <select name="jenis" id="filterJenis" class="toolbar-filter-select" style="height: 38px;"
                                title="Filter Jenis Lokasi">
                                <option value="">Semua Jenis Lokasi</option>
                                @foreach ($jenisLokasiLabels ?? \App\Http\Controllers\LaboranJadwalController::JENIS_LOKASI as $jKey => $jLabel)
                                    <option value="{{ $jKey }}" {{ $jenisFilter === $jKey ? 'selected' : '' }}>
                                        {{ $jLabel }}</option>
                                @endforeach
                            </select>

                            <select name="lab" id="filterLab" class="toolbar-filter-select" style="height: 38px;"
                                title="Filter Lokasi Praktik">
                                <option value="">Semua Lokasi</option>
                                @foreach ($daftarLab as $lab)
                                    <option value="{{ $lab }}" {{ $labFilter === $lab ? 'selected' : '' }}>
                                        {{ $lab }}</option>
                                @endforeach
                            </select>

                            <select name="status" id="filterStatus" class="toolbar-filter-select" style="height: 38px;"
                                title="Filter Status Sesi">
                                <option value="">Semua Status</option>
                                @foreach (\App\Http\Controllers\LaboranJadwalController::STATUS_LIST as $sKey => $sLabel)
                                    <option value="{{ $sKey }}" {{ $statusFilter === $sKey ? 'selected' : '' }}>
                                        {{ $sLabel }}</option>
                                @endforeach
                            </select>

                            <input type="date" name="tanggal" id="filterTanggal" value="{{ $tanggalFilter }}"
                                class="form-control"
                                style="height: 38px; font-size: var(--text-sm); padding: 0 var(--space-5); border-radius: var(--radius-md);"
                                title="Filter Tanggal Praktik">

                            @if ($search || $labFilter || $jenisFilter || $statusFilter || $tanggalFilter)
                                <a href="{{ route('dashboard.laboran.jadwal.index') }}" class="btn btn-outline btn-reset-filter"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                                    title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="q" id="liveSearch" value="{{ $search }}"
                                placeholder="Cari mapel, topik, guru, kelas, atau lokasi..." autocomplete="off">
                            <button type="button" id="clearSearch" class="clear-search {{ $search ? 'visible' : '' }}"
                                title="Hapus pencarian">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); ">
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">
                                No</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">
                                Lokasi &amp; Tanggal</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">
                                Mapel &amp; Topik</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">
                                Guru &amp; Kelas</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                Waktu Sesi</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                Status</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $idx => $item)
                            @php
                                $jenisKey = $item->jenis_lokasi ?: 'lainnya';
                                $jenisLabel = \App\Http\Controllers\LaboranJadwalController::JENIS_LOKASI[$jenisKey] ?? 'Lokasi Lainnya';
                                $jenisIcon = match ($jenisKey) {
                                    'lab' => 'fa-flask',
                                    'bengkel' => 'fa-screwdriver-wrench',
                                    'lahan' => 'fa-seedling',
                                    'lapangan' => 'fa-futbol',
                                    default => 'fa-location-dot',
                                };
                                $jenisColor = match ($jenisKey) {
                                    'lab' => 'var(--primary, #3b82f6)',
                                    'bengkel' => 'var(--warning, #f59e0b)',
                                    'lahan' => 'var(--success, #10b981)',
                                    'lapangan' => 'var(--purple, #8b5cf6)',
                                    default => 'var(--text-muted)',
                                };
                            @endphp
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td data-label="No" style="padding: var(--space-7) var(--space-8); font-size: var(--text-base); color: var(--text-muted);">
                                    {{ $list->firstItem() + $idx }}
                                </td>
                                <td data-label="Lokasi & Tanggal" style="padding: var(--space-7) var(--space-8);">
                                    <div style="font-weight: var(--font-bold); color: var(--text-color); font-size: var(--text-base);">
                                        {{ $item->ruang_lab_nama }}
                                    </div>
                                    <div style="font-size: var(--text-xs); color: {{ $jenisColor }}; font-weight: var(--font-semibold); margin-top: var(--space-1);">
                                        <i class="fas {{ $jenisIcon }} me-1"></i>{{ $jenisLabel }}
                                    </div>
                                    <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-1);">
                                        <i class="fas fa-calendar-day text-primary me-1"></i>
                                        {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d F Y') }}
                                    </div>
                                </td>
                                <td data-label="Mapel & Topik" style="padding: var(--space-7) var(--space-8);">
                                    <div style="font-weight: var(--font-semibold); color: var(--text-color); font-size: var(--text-base);">
                                        {{ $item->mata_pelajaran }}
                                    </div>
                                    @if (!empty($item->topik_praktik))
                                        <div style="font-size: var(--text-sm); color: var(--primary); font-weight: var(--font-medium);">
                                            {{ $item->topik_praktik }}
                                        </div>
                                    @else
                                        <div style="font-size: var(--text-xs); color: var(--text-muted); font-style: italic;">
                                            Topik belum diisi
                                        </div>
                                    @endif
                                    @if (!empty($item->alat_bahan_digunakan))
                                        <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-1);">
                                            <i class="fas fa-toolbox me-1"></i>{{ \Illuminate\Support\Str::limit($item->alat_bahan_digunakan, 60) }}
                                        </div>
                                    @endif
                                    @if (!empty($item->laporan_kerusakan))
                                        <div style="font-size: var(--text-xs); color: var(--danger, #ef4444); margin-top: var(--space-1);">
                                            <i class="fas fa-circle-exclamation me-1"></i>
                                            {{ \Illuminate\Support\Str::limit($item->laporan_kerusakan, 60) }}
                                        </div>
                                    @endif
                                </td>
                                <td data-label="Guru & Kelas" style="padding: var(--space-7) var(--space-8); font-size: var(--text-base);">
                                    <div style="font-weight: var(--font-semibold); color: var(--text-color);">
                                        {{ $item->guru_nama ?: '-' }}
                                    </div>
                                    @if (!empty($item->guru_pendamping))
                                        <div style="font-size: var(--text-xs); color: var(--primary); margin-top: 2px;" title="Guru Pendamping: {{ $item->guru_pendamping }}">
                                            <i class="fas fa-users me-1"></i>+ {{ $item->guru_pendamping }}
                                        </div>
                                    @endif
                                    <div style="margin-top: var(--space-2); display: flex; gap: var(--space-2); flex-wrap: wrap;">
                                        <span class="badge badge-outline" style="font-size: var(--text-xs);">
                                            {{ $item->rombel_nama ?: 'Seluruh Siswa' }}
                                        </span>
                                        @if (!empty($item->pemberlakuan) && $item->pemberlakuan !== 'sekali')
                                            <span class="badge badge-primary" style="font-size: var(--text-2xs);" title="Penjadwalan Berkala">
                                                <i class="fas fa-repeat me-1"></i>{{ ucfirst($item->pemberlakuan) }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td data-label="Waktu" style="padding: var(--space-7) var(--space-8); text-align: center; font-size: var(--text-base); font-family: var(--font-family-mono);">
                                    {{ substr((string) $item->jam_mulai, 0, 5) }} - {{ substr((string) $item->jam_selesai, 0, 5) }}
                                </td>
                                <td data-label="Status" style="padding: var(--space-7) var(--space-8); text-align: center;">
                                    @if ($item->status === 'dijadwalkan')
                                        <span class="badge badge-info">Dijadwalkan</span>
                                    @elseif ($item->status === 'berlangsung')
                                        <span class="badge badge-warning">Berlangsung</span>
                                    @elseif ($item->status === 'selesai')
                                        <span class="badge badge-success">Selesai</span>
                                    @else
                                        <span class="badge badge-danger">Batal</span>
                                    @endif
                                </td>
                                <td data-label="Aksi" style="padding: var(--space-7) var(--space-8); text-align: right;">
                                    <div class="table-actions" style="justify-content: flex-end; gap: var(--space-3);">
                                        @if ($canUpdate)
                                            <button type="button" class="btn-icon btn-edit-jadwal"
                                                data-id="{{ $item->id }}"
                                                data-lokasi="{{ $item->ruang_lab_nama }}"
                                                data-jenis="{{ $jenisKey }}"
                                                data-rombel-id="{{ $item->rombel_id }}"
                                                data-rombel-nama="{{ $item->rombel_nama }}"
                                                data-pembelajaran-id="{{ $item->pembelajaran_id }}"
                                                data-mapel="{{ $item->mata_pelajaran }}"
                                                data-ptk-id="{{ $item->ptk_id }}"
                                                data-ptk-ids="{{ $item->ptk_ids }}"
                                                data-pendamping="{{ $item->guru_pendamping }}"
                                                data-pemberlakuan="{{ $item->pemberlakuan ?? 'sekali' }}"
                                                data-guru="{{ $item->guru_nama }}"
                                                data-topik="{{ $item->topik_praktik }}"
                                                data-tanggal="{{ $item->tanggal }}"
                                                data-jam-mulai="{{ substr((string) $item->jam_mulai, 0, 5) }}"
                                                data-jam-selesai="{{ substr((string) $item->jam_selesai, 0, 5) }}"
                                                data-alat="{{ $item->alat_bahan_digunakan }}"
                                                data-status="{{ $item->status }}"
                                                title="Ubah Detail Jadwal">
                                                <i class="fas fa-pen-to-square" style="color: var(--primary);"></i>
                                            </button>
                                            <button type="button" class="btn-icon btn-status-lab"
                                                data-id="{{ $item->id }}" data-mapel="{{ $item->mata_pelajaran }}"
                                                data-status="{{ $item->status }}"
                                                data-laporan="{{ $item->laporan_kerusakan }}" title="Update Status Sesi">
                                                <i class="fas fa-sliders" style="color: var(--success, #10b981);"></i>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <button type="button" class="btn-icon btn-delete-jadwal"
                                                data-id="{{ $item->id }}" data-mapel="{{ $item->mata_pelajaran }}"
                                                title="Hapus Jadwal" style="color: var(--danger, #ef4444);">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="padding: var(--space-11); text-align: center; color: var(--text-muted);">
                                    <i class="fas fa-folder-open mb-2" style="font-size: var(--text-3xl); opacity: 0.5;"></i>
                                    <div>Belum ada jadwal praktik yang cocok dengan filter.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($list->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $list])
                </div>
            @endif
        </div>
        @endif

        @include('dashboard.laboran.partials.jadwal-modal-mingguan')
        @include('dashboard.laboran.partials.jadwal-modal-semester')
        @include('dashboard.laboran.partials.jadwal-modal-status')
    </div>

    @push('scripts')
            <script src="{{ asset('js/laboran-common.js') }}?v={{ file_exists(public_path('js/laboran-common.js')) ? filemtime(public_path('js/laboran-common.js')) : time() }}"></script>
            <script
                src="{{ asset('js/laboran-jadwal-jam.js') }}?v={{ file_exists(public_path('js/laboran-jadwal-jam.js')) ? filemtime(public_path('js/laboran-jadwal-jam.js')) : time() }}">
            </script>
            <script
                src="{{ asset('js/laboran-jadwal-pengajar.js') }}?v={{ file_exists(public_path('js/laboran-jadwal-pengajar.js')) ? filemtime(public_path('js/laboran-jadwal-pengajar.js')) : time() }}">
            </script>
            <script
                src="{{ asset('js/laboran-jadwal.js') }}?v={{ file_exists(public_path('js/laboran-jadwal.js')) ? filemtime(public_path('js/laboran-jadwal.js')) : time() }}">
            </script>
        @endpush
    @endsection
