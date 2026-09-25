@extends('layouts.dashboard')

@section('title', 'Presensi Kelas & Mapel — SAE')
@section('dash_title', 'Presensi Kelas')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/presensi.css') }}?v={{ file_exists(public_path('css/presensi.css')) ? filemtime(public_path('css/presensi.css')) : time() }}">
@endpush

@section('content')
    <!-- Header Banner Baku SAE -->
    <div class="dash-banner" style="margin-bottom: 20px;">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-users-viewfinder text-primary me-2"></i> Presensi Kelas &amp; Mata Pelajaran
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
                @if ($selectedRombel)
                    Kelas: <strong style="color: var(--text-color);">{{ $selectedRombel->nama }}</strong>
                    @if ($selectedPembelajaran)
                        &bull; Mapel: <strong style="color: var(--primary);">{{ $selectedPembelajaran->nama_mata_pelajaran }}</strong>
                    @endif
                    @if ($jamKe)
                        &bull; Jam ke: <strong style="color: var(--text-color);">{{ $jamKe }}</strong>
                    @endif
                @else
                    Pencatatan presensi peserta didik per mata pelajaran KBM oleh Guru Pengampu.
                @endif
            </p>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="{{ route('dashboard.presensi-mengajar.index') }}" class="btn btn-outline btn-responsive-icon"
                style="padding: 9px 14px; font-size: 0.85rem;" title="Presensi Mengajar Guru">
                <i class="fas fa-calendar-check me-1"></i> <span class="btn-responsive-text">Presensi Guru</span>
            </a>
            @if ($selectedRombel && $siswaList->isNotEmpty())
                <button type="button" class="btn btn-primary btn-responsive-icon" id="btnHadirSemua"
                    data-rombel="{{ $selectedRombelId }}"
                    data-rombel-nama="{{ $selectedRombel->nama }}"
                    data-pembelajaran="{{ $selectedPembelajaranId }}"
                    data-jam-ke="{{ $jamKe }}"
                    style="padding: 9px 16px; font-size: 0.85rem; font-weight: 700;" title="Tandai Hadir Seluruh Peserta Didik">
                    <i class="fas fa-check-double me-1"></i> <span class="btn-responsive-text">Hadir Semua</span>
                </button>
                <button type="button" class="btn btn-outline btn-responsive-icon" id="btnResetPresensiKelas"
                    data-rombel="{{ $selectedRombelId }}"
                    data-rombel-nama="{{ $selectedRombel->nama }}"
                    data-pembelajaran="{{ $selectedPembelajaranId }}"
                    style="padding: 9px 16px; font-size: 0.85rem; font-weight: 700; color: #ef4444; border-color: rgba(239,68,68,0.4);" title="Reset / Kosongkan Presensi Mapel Kelas">
                    <i class="fas fa-rotate-left me-1"></i> <span class="btn-responsive-text">Reset Presensi</span>
                </button>
            @endif
        </div>
    </div>

    @if ($rombelList->isEmpty())
        <!-- Empty State jika Guru belum ada tugas rombel / jadwal KBM -->
        <div class="card" style="padding: 40px 20px; text-align: center; margin-top: 20px;">
            <div style="width: 70px; height: 70px; border-radius: 50%; background: rgba(99,102,241,0.12); color: var(--primary); display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 16px;">
                <i class="fas fa-chalkboard-user"></i>
            </div>
            <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--text-color); margin-bottom: 8px;">Belum Ada Kelas Mengajar Terdaftar</h3>
            <p style="color: var(--text-muted); font-size: 0.9rem; max-width: 540px; margin: 0 auto;">
                Akun Anda belum terdaftar mengampu rombongan belajar pada data Pembelajaran atau Jadwal KBM. Silakan hubungi Waka Kurikulum atau Operator Dapodik sekolah untuk sinkronisasi pembagian jam mengajar Anda.
            </p>
        </div>
    @else
        <!-- Filter Bar Card Baku SAE -->
        <div class="card" style="padding: 16px; margin-bottom: 20px;">
            <form action="{{ route('dashboard.presensi.kelas') }}" method="GET" id="formFilterPresensiKelas" style="display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; justify-content: space-between;">
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; flex: 1;">
                        <div style="min-width: 170px; flex: 1;">
                            <label style="display: block; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">
                                <i class="fas fa-chalkboard me-1 text-primary"></i> Rombongan Belajar:
                            </label>
                            <select name="rombel_id" class="form-control" style="width: 100%; padding: 8px 12px; font-size: 0.85rem; border-radius: 8px;" onchange="this.form.submit()">
                                @foreach ($rombelList as $r)
                                    <option value="{{ $r->rombongan_belajar_id }}" {{ $selectedRombelId == $r->rombongan_belajar_id ? 'selected' : '' }}>
                                        {{ $r->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div style="min-width: 200px; flex: 1;">
                            <label style="display: block; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">
                                <i class="fas fa-book me-1 text-primary"></i> Mata Pelajaran (KBM):
                            </label>
                            <select name="pembelajaran_id" id="kelasPembelajaranSelect" class="form-control" style="width: 100%; padding: 8px 12px; font-size: 0.85rem; border-radius: 8px;" onchange="this.form.submit()">
                                @if ($pembelajaranList->isEmpty())
                                    <option value="">-- Tidak ada mapel yang diampu di kelas ini --</option>
                                @else
                                    @foreach ($pembelajaranList as $p)
                                        <option value="{{ $p->pembelajaran_id }}" {{ $selectedPembelajaranId == $p->pembelajaran_id ? 'selected' : '' }}>
                                            {{ $p->nama_mata_pelajaran }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div style="min-width: 140px;">
                            <label style="display: block; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">
                                <i class="fas fa-calendar-day me-1 text-primary"></i> Tanggal:
                            </label>
                            <input type="date" id="kelasTanggalInput" name="tanggal" value="{{ $tanggal }}" class="form-control" style="padding: 8px 10px; font-size: 0.85rem; border-radius: 8px;" onchange="this.form.submit()">
                        </div>

                        <div style="width: 90px;">
                            <label style="display: block; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">
                                <i class="fas fa-clock me-1 text-primary"></i> Jam Ke:
                            </label>
                            <input type="text" id="kelasJamKeInput" name="jam_ke" value="{{ $jamKe }}" placeholder="1-2" class="form-control" style="padding: 8px 10px; font-size: 0.85rem; border-radius: 8px;">
                        </div>

                        <div>
                            <button type="submit" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.85rem; font-weight: 600; border-radius: 8px;">
                                <i class="fas fa-filter me-1"></i> Terapkan
                            </button>
                        </div>
                    </div>

                    <div class="live-search-wrap" style="min-width: 200px;">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="liveSearchKelasInput" placeholder="Cari nama / NISN..." autocomplete="off">
                        <button type="button" id="clearSearchKelasBtn" class="clear-search" title="Hapus pencarian">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Rekap Status Kelas Mini-Stats Baku SAE -->
        <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; margin-bottom: 20px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem;">{{ $rekap['total'] }}</div>
                    <div class="dash-stat-label">Total Peserta Didik</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                    <i class="fas fa-check-double"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #10b981;">{{ $rekap['hadir'] }}</div>
                    <div class="dash-stat-label">Hadir Mapel</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #f59e0b;">{{ $rekap['terlambat'] }}</div>
                    <div class="dash-stat-label">Terlambat</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59,130,246,0.15); color: #3b82f6;">
                    <i class="fas fa-envelope-open-text"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #3b82f6;">{{ $rekap['izin'] }}</div>
                    <div class="dash-stat-label">Izin Mapel</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(236,72,153,0.15); color: #ec4899;">
                    <i class="fas fa-notes-medical"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #ec4899;">{{ $rekap['sakit'] }}</div>
                    <div class="dash-stat-label">Sakit</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239,68,68,0.15); color: #ef4444;">
                    <i class="fas fa-circle-xmark"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #ef4444;">{{ $rekap['alpha'] }}</div>
                    <div class="dash-stat-label">Alpha Mapel</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(100,116,139,0.15); color: #64748b;">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #64748b;">{{ $rekap['belum'] }}</div>
                    <div class="dash-stat-label">Belum Dicatat</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: #06b6d4;">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #06b6d4;">{{ $rekap['persen'] }}%</div>
                    <div class="dash-stat-label">% Kehadiran</div>
                </div>
            </div>
        </div>

        <!-- Datatable Presensi Kelas Baku SAE -->
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Peserta Didik
                        </th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            NISN
                        </th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;" title="Status kedatangan di gerbang sekolah (RFID / Kiosk)">
                            Presensi Gerbang
                        </th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                            Status Mapel
                        </th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Catatan Mapel
                        </th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                            Aksi Presensi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($siswaList as $s)
                        <tr id="row-siswa-{{ $s->peserta_didik_id }}" class="row-siswa-item" style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <!-- Kolom Peserta Didik -->
                            <td class="cell-pd-nama" style="padding: 14px 18px;" data-label="Siswa">
                                <div class="pd-main-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                    @if (!empty($s->foto_path))
                                        <div class="pd-foto-thumb" style="width: 42px; height: 42px; border-radius: 10px; border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                                            <img src="{{ asset('storage/' . ltrim($s->foto_path, '/')) }}" alt="{{ $s->nama }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                    @else
                                        <div class="pd-foto-thumb empty" style="width: 42px; height: 42px; border-radius: 10px; background: rgba(99,102,241,0.08); border: 1.5px dashed rgba(99,102,241,0.4); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 0.95rem; flex-shrink: 0;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    @endif
                                    <div class="pd-info">
                                        <div class="pd-title-row" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                            <span class="pd-nama text-nama-siswa" style="font-weight: 700; color: var(--text-color);">{{ $s->nama }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Kolom NISN -->
                            <td class="cell-pd-nisn" style="padding: 14px 18px;" data-label="NISN">
                                <span class="text-nisn-siswa" style="font-family: monospace; font-size: 0.85rem; color: var(--text-color);">{{ $s->nisn ?: '-' }}</span>
                            </td>

                            <!-- Kolom Presensi Gerbang (Sekolah) -->
                            <td class="cell-pd-gerbang" style="padding: 14px 18px; text-align: center;" data-label="Gerbang">
                                @if ($s->gerbang_status === 'H')
                                    <span class="badge badge-success" style="font-size: 0.74rem;"><i class="fas fa-school me-1"></i> Hadir</span>
                                    @if ($s->gerbang_jam_masuk)
                                        <div style="font-size: 0.7rem; font-family: monospace; color: var(--text-muted); margin-top: 2px;">
                                            {{ substr($s->gerbang_jam_masuk, 0, 5) }} WIB
                                        </div>
                                    @endif
                                @elseif ($s->gerbang_status === 'T')
                                    <span class="badge badge-warning" style="font-size: 0.74rem;"><i class="fas fa-clock me-1"></i> Terlambat</span>
                                    @if ($s->gerbang_jam_masuk)
                                        <div style="font-size: 0.7rem; font-family: monospace; color: var(--warning); margin-top: 2px;">
                                            {{ substr($s->gerbang_jam_masuk, 0, 5) }} (+{{ $s->gerbang_menit_terlambat }}m)
                                        </div>
                                    @endif
                                @elseif ($s->gerbang_status === 'I')
                                    <span class="badge badge-primary" style="font-size: 0.74rem;"><i class="fas fa-envelope me-1"></i> Izin</span>
                                @elseif ($s->gerbang_status === 'S')
                                    <span class="badge" style="background: rgba(139,92,246,0.15); color: #8b5cf6; font-size: 0.74rem;"><i class="fas fa-stethoscope me-1"></i> Sakit</span>
                                @elseif ($s->gerbang_status === 'A')
                                    <span class="badge badge-danger" style="font-size: 0.74rem;"><i class="fas fa-xmark me-1"></i> Alpha</span>
                                @else
                                    <span class="badge" style="background: rgba(148,163,184,0.12); color: var(--text-muted); font-size: 0.74rem;"><i class="fas fa-minus me-1"></i> Belum Tap</span>
                                @endif

                                @if ($s->gerbang_status_pulang === 'pulang_cepat')
                                    <div style="font-size: 0.68rem; color: #f59e0b; margin-top: 2px;" title="Pulang Cepat / Tidak Tap Pulang">
                                        <i class="fas fa-person-walking-arrow-right"></i> Pulang Cepat
                                    </div>
                                @endif
                            </td>

                            <!-- Kolom Status Mapel -->
                            <td class="cell-pd-status" id="badge-status-{{ $s->peserta_didik_id }}" style="padding: 14px 18px; text-align: center;" data-label="Status Mapel">
                                {!! \App\Models\PresensiMapel::STATUS_BADGES[$s->mapel_status] ?? '<span class="badge badge-outline" style="color: var(--text-muted); font-size: 0.74rem;">Belum</span>' !!}
                            </td>

                            <!-- Kolom Catatan Mapel -->
                            <td class="cell-pd-ket" id="ket-status-{{ $s->peserta_didik_id }}" style="padding: 14px 18px;" data-label="Catatan">
                                <span style="font-size: 0.8rem; color: var(--text-muted);">
                                    {{ $s->mapel_keterangan ?: '—' }}
                                </span>
                            </td>

                            <!-- Kolom Aksi Presensi -->
                            <td class="cell-pd-aksi" style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                                <div class="table-actions" style="justify-content: flex-end; gap: 4px; display: inline-flex;">
                                    <button type="button" class="btn-status-toggle {{ $s->mapel_status === 'H' ? 'active-H' : '' }}"
                                        data-id="{{ $s->peserta_didik_id }}" data-nama="{{ $s->nama }}" data-status="H" title="Hadir pada KBM">
                                        H
                                    </button>
                                    <button type="button" class="btn-status-toggle {{ $s->mapel_status === 'T' ? 'active-T' : '' }}"
                                        data-id="{{ $s->peserta_didik_id }}" data-nama="{{ $s->nama }}" data-status="T" title="Terlambat Masuk Kelas">
                                        T
                                    </button>
                                    <button type="button" class="btn-status-toggle {{ $s->mapel_status === 'I' ? 'active-I' : '' }}"
                                        data-id="{{ $s->peserta_didik_id }}" data-nama="{{ $s->nama }}" data-status="I" title="Izin Mapel">
                                        I
                                    </button>
                                    <button type="button" class="btn-status-toggle {{ $s->mapel_status === 'S' ? 'active-S' : '' }}"
                                        data-id="{{ $s->peserta_didik_id }}" data-nama="{{ $s->nama }}" data-status="S" title="Sakit di Jam Mapel">
                                        S
                                    </button>
                                    <button type="button" class="btn-status-toggle {{ $s->mapel_status === 'A' ? 'active-A' : '' }}"
                                        data-id="{{ $s->peserta_didik_id }}" data-nama="{{ $s->nama }}" data-status="A" title="Alpha pada Jam Mapel">
                                        A
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 40px 20px; text-align: center; color: var(--text-muted);">
                                <div style="font-size: 2rem; opacity: 0.35; margin-bottom: 8px;">
                                    <i class="fas fa-folder-open"></i>
                                </div>
                                <div style="font-weight: 700; color: var(--text-color); margin-bottom: 4px;">Tidak Ada Data Peserta Didik</div>
                                <div style="font-size: 0.82rem;">Silakan pilih rombongan belajar di atas untuk menampilkan daftar siswa.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    <!-- Modal Form Pencatatan Izin / Sakit Mapel -->
    <div id="modalIzinSakit" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center; padding: 12px; box-sizing: border-box;">
        <div class="card" style="max-width: 480px; width: 92%; margin: auto; padding: 22px; border-radius: 16px; box-shadow: 0 16px 40px rgba(0,0,0,0.5); border: 1px solid var(--border-color); background: var(--bg-card);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <h3 id="titleModalIzin" style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-notes-medical text-primary"></i> Pencatatan Izin / Sakit Mapel
                </h3>
                <button type="button" id="btnCloseIzinModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formIzinSakit">
                <input type="hidden" id="izinPdId">
                <input type="hidden" id="izinStatusVal">

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 4px;">Peserta Didik:</label>
                    <div id="izinPdNama" style="font-size: 0.95rem; font-weight: 700; color: var(--text-color);">-</div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label for="izinKeteranganInput" style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-color); margin-bottom: 6px;">
                        Keterangan / Alasan KBM <span style="color: var(--danger);">*</span>:
                    </label>
                    <textarea name="keterangan" id="izinKeteranganInput" rows="3" required placeholder="Tuliskan alasan izin/sakit pada jam mapel ini..." class="form-control" style="width: 100%; font-size: 0.85rem; border-radius: 8px; resize: vertical;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" id="btnCancelIzinModal" class="btn btn-outline" style="padding: 7px 14px; font-size: 0.82rem; border-radius: 8px;">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary" style="padding: 7px 18px; font-size: 0.82rem; border-radius: 8px; font-weight: 600;">
                        <i class="fas fa-check me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/presensi-kelas.js') }}?v={{ file_exists(public_path('js/presensi-kelas.js')) ? filemtime(public_path('js/presensi-kelas.js')) : time() }}"></script>
@endpush
