@extends('layouts.dashboard')

@section('title', 'Presensi Kelas — SAE')
@section('dash_title', 'Presensi Kelas')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/presensi.css') }}?v={{ file_exists(public_path('css/presensi.css')) ? filemtime(public_path('css/presensi.css')) : time() }}">
@endpush

@section('content')
    <!-- Dash Banner -->
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-users-viewfinder text-primary me-2"></i> Presensi Kelas &amp; Mata Pelajaran
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Pencatatan kehadiran peserta didik per mata pelajaran oleh Guru Mapel &amp; Wali Kelas. Terisolasi mandiri dari presensi gerbang sekolah dan disiapkan untuk integrasi Agenda Kelas.
            </p>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 10px; flex-wrap: wrap;">
            @if ($selectedRombel)
                <button type="button" class="btn btn-danger" id="btnTandaiAlpha"
                    data-rombel="{{ $selectedRombelId }}"
                    data-rombel-nama="{{ $selectedRombel->nama }}"
                    data-pembelajaran="{{ $selectedPembelajaranId }}"
                    style="padding: 9px 16px; font-size: 0.85rem; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fas fa-user-xmark"></i> Tandai Sisa Mapel sebagai Alpha
                </button>
            @endif
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="card" style="padding: 18px 20px; border-radius: 14px; margin-bottom: 20px;">
        <form action="{{ route('dashboard.presensi.kelas') }}" method="GET" style="display: flex; gap: 14px; flex-wrap: wrap; align-items: flex-end;">
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 5px;">
                    Pilih Rombongan Belajar:
                </label>
                <select name="rombel_id" class="form-control" style="width: 100%; padding: 9px 12px; font-size: 0.88rem;" onchange="this.form.submit()">
                    @foreach ($rombelList as $r)
                        <option value="{{ $r->rombongan_belajar_id }}" {{ $selectedRombelId == $r->rombongan_belajar_id ? 'selected' : '' }}>
                            {{ $r->nama }} {{ $waliRombel === $r->rombongan_belajar_id ? '(Kelas Binaan Anda)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="flex: 1; min-width: 220px;">
                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 5px;">
                    Mata Pelajaran (KBM):
                </label>
                <select name="pembelajaran_id" id="kelasPembelajaranSelect" class="form-control" style="width: 100%; padding: 9px 12px; font-size: 0.88rem;">
                    @if ($pembelajaranList->isEmpty())
                        <option value="">-- Tidak ada mapel terdaftar di rombel ini --</option>
                    @else
                        @foreach ($pembelajaranList as $p)
                            <option value="{{ $p->pembelajaran_id }}" {{ $selectedPembelajaranId == $p->pembelajaran_id ? 'selected' : '' }}>
                                {{ $p->nama_mata_pelajaran }} {{ $p->nama_guru ? '— ' . $p->nama_guru : '' }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>

            <div style="min-width: 150px;">
                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 5px;">
                    Tanggal Presensi:
                </label>
                <input type="date" id="kelasTanggalInput" name="tanggal" value="{{ $tanggal }}" class="form-control" style="padding: 9px 12px; font-size: 0.88rem;">
            </div>

            <div style="width: 95px;">
                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 5px;">
                    Jam Ke:
                </label>
                <input type="text" id="kelasJamKeInput" name="jam_ke" value="{{ $jamKe }}" placeholder="1-2" class="form-control" style="padding: 9px 12px; font-size: 0.88rem;">
            </div>

            <div>
                <button type="submit" class="btn btn-primary" style="padding: 9px 20px; font-size: 0.88rem; font-weight: 600;">
                    <i class="fas fa-filter me-1"></i> Tampilkan
                </button>
            </div>
        </form>
    </div>

    <!-- Rekap Status Kelas Mini-Stats -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; margin-bottom: 20px;">
        <div class="card" style="padding: 12px 16px; border-radius: 12px; text-align: center;">
            <div style="font-size: 1.25rem; font-weight: 800; color: var(--text-color);">{{ $rekap['total'] }}</div>
            <div style="font-size: 0.75rem; color: var(--text-muted);">Total Peserta Didik</div>
        </div>
        <div class="card" style="padding: 12px 16px; border-radius: 12px; text-align: center; border-left: 3px solid var(--success);">
            <div style="font-size: 1.25rem; font-weight: 800; color: var(--success);">{{ $rekap['hadir'] }}</div>
            <div style="font-size: 0.75rem; color: var(--text-muted);">Hadir Mapel</div>
        </div>
        <div class="card" style="padding: 12px 16px; border-radius: 12px; text-align: center; border-left: 3px solid var(--warning);">
            <div style="font-size: 1.25rem; font-weight: 800; color: var(--warning);">{{ $rekap['terlambat'] }}</div>
            <div style="font-size: 0.75rem; color: var(--text-muted);">Terlambat</div>
        </div>
        <div class="card" style="padding: 12px 16px; border-radius: 12px; text-align: center; border-left: 3px solid var(--primary);">
            <div style="font-size: 1.25rem; font-weight: 800; color: var(--primary);">{{ $rekap['izin'] }}</div>
            <div style="font-size: 0.75rem; color: var(--text-muted);">Izin</div>
        </div>
        <div class="card" style="padding: 12px 16px; border-radius: 12px; text-align: center; border-left: 3px solid #8b5cf6;">
            <div style="font-size: 1.25rem; font-weight: 800; color: #8b5cf6;">{{ $rekap['sakit'] }}</div>
            <div style="font-size: 0.75rem; color: var(--text-muted);">Sakit</div>
        </div>
        <div class="card" style="padding: 12px 16px; border-radius: 12px; text-align: center; border-left: 3px solid var(--danger);">
            <div style="font-size: 1.25rem; font-weight: 800; color: var(--danger);">{{ $rekap['alpha'] }}</div>
            <div style="font-size: 0.75rem; color: var(--text-muted);">Alpha</div>
        </div>
        <div class="card" style="padding: 12px 16px; border-radius: 12px; text-align: center; border-left: 3px solid var(--text-muted);">
            <div style="font-size: 1.25rem; font-weight: 800; color: var(--text-muted);">{{ $rekap['belum'] }}</div>
            <div style="font-size: 0.75rem; color: var(--text-muted);">Belum Dicatat</div>
        </div>
    </div>

    <!-- Table Daftar Siswa & Presensi Mapel -->
    <div class="card" style="padding: 20px; border-radius: 14px; margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
            <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color);">
                <i class="fas fa-book-bookmark text-primary me-2"></i>
                Mapel: <span class="text-primary">{{ $selectedPembelajaran ? $selectedPembelajaran->nama_mata_pelajaran : 'Semua Mapel' }}</span>
                @if ($selectedPembelajaran && $selectedPembelajaran->nama_guru)
                    <span style="font-weight: 400; font-size: 0.8rem; color: var(--text-muted);">({{ $selectedPembelajaran->nama_guru }})</span>
                @endif
            </div>
            <div style="font-size: 0.78rem; color: var(--text-muted);">
                <span class="badge" style="background: rgba(16,185,129,0.12); color: var(--success);"><i class="fas fa-circle-check me-1"></i> Presensi Mandiri Mapel</span>
                <span class="badge" style="background: rgba(59,130,246,0.12); color: var(--primary); margin-left: 4px;"><i class="fas fa-shield me-1"></i> Data Gerbang Terlindungi</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table" style="width: 100%; font-size: 0.85rem;">
                <thead>
                    <tr>
                        <th style="width: 45px; text-align: center;">No</th>
                        <th style="width: 45px;">Foto</th>
                        <th>Nama Peserta Didik</th>
                        <th>NISN</th>
                        <th style="text-align: center;" title="Status kedatangan di gerbang sekolah (RFID / Kiosk)">Presensi Gerbang</th>
                        <th style="text-align: center;">Status Mapel</th>
                        <th style="text-align: center;">Ubah Status Mapel</th>
                        <th>Keterangan Mapel</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($siswaList as $idx => $s)
                        <tr id="row-siswa-{{ $s->peserta_didik_id }}">
                            <td style="text-align: center; color: var(--text-muted);">{{ $idx + 1 }}</td>
                            <td>
                                <img src="{{ $s->foto_url ?: asset('img/logo-dark.png') }}" alt="{{ $s->nama }}"
                                    style="width: 34px; height: 34px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border-color);"
                                    onerror="this.src='/img/logo-dark.png';">
                            </td>
                            <td>
                                <div style="font-weight: 700; color: var(--text-color);">{{ $s->nama }}</div>
                                <div style="font-size: 0.72rem; color: var(--text-muted);">
                                    {{ $s->jenis_kelamin === 'L' ? 'Laki-laki' : ($s->jenis_kelamin === 'P' ? 'Perempuan' : '-') }}
                                </div>
                            </td>
                            <td>
                                <span style="font-family: monospace;">{{ $s->nisn ?: '-' }}</span>
                            </td>
                            <td style="text-align: center;">
                                @if ($s->gerbang_status === 'H')
                                    <span class="badge badge-success" style="font-size: 0.75rem;"><i class="fas fa-school me-1"></i> Hadir</span>
                                    @if ($s->gerbang_jam_masuk)
                                        <div style="font-size: 0.7rem; font-family: monospace; color: var(--text-muted); margin-top: 2px;">
                                            {{ substr($s->gerbang_jam_masuk, 0, 5) }} WIB
                                        </div>
                                    @endif
                                @elseif ($s->gerbang_status === 'T')
                                    <span class="badge badge-warning" style="font-size: 0.75rem;"><i class="fas fa-clock me-1"></i> Terlambat</span>
                                    @if ($s->gerbang_jam_masuk)
                                        <div style="font-size: 0.7rem; font-family: monospace; color: var(--warning); margin-top: 2px;">
                                            {{ substr($s->gerbang_jam_masuk, 0, 5) }} (+{{ $s->gerbang_menit_terlambat }}m)
                                        </div>
                                    @endif
                                @elseif ($s->gerbang_status === 'I')
                                    <span class="badge badge-primary" style="font-size: 0.75rem;"><i class="fas fa-envelope me-1"></i> Izin</span>
                                @elseif ($s->gerbang_status === 'S')
                                    <span class="badge" style="background: rgba(139,92,246,0.15); color: #8b5cf6; font-size: 0.75rem;"><i class="fas fa-stethoscope me-1"></i> Sakit</span>
                                @elseif ($s->gerbang_status === 'A')
                                    <span class="badge badge-danger" style="font-size: 0.75rem;"><i class="fas fa-xmark me-1"></i> Alpha</span>
                                @else
                                    <span class="badge" style="background: rgba(148,163,184,0.12); color: var(--text-muted); font-size: 0.75rem;"><i class="fas fa-minus me-1"></i> Belum Tap</span>
                                @endif

                                @if ($s->gerbang_status_pulang === 'pulang_cepat')
                                    <div style="font-size: 0.68rem; color: #f59e0b; margin-top: 2px;" title="Pulang Cepat / Tidak Tap Pulang">
                                        <i class="fas fa-person-walking-arrow-right"></i> Pulang Cepat
                                    </div>
                                @endif
                            </td>
                            <td style="text-align: center;" id="badge-status-{{ $s->peserta_didik_id }}">
                                {!! \App\Models\PresensiMapel::STATUS_BADGES[$s->mapel_status] ?? '<span class="badge" style="background: rgba(148,163,184,0.15); color: var(--text-muted);">Belum</span>' !!}
                            </td>
                            <td style="text-align: center;">
                                <div style="display: inline-flex; gap: 4px;">
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
                            <td>
                                <div id="ket-status-{{ $s->peserta_didik_id }}" style="font-size: 0.78rem; color: var(--text-muted); max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    {{ $s->mapel_keterangan ?: '-' }}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                Silakan pilih rombongan belajar terlebih dahulu untuk menampilkan daftar peserta didik.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form Pencatatan Izin / Sakit Mapel -->
    <div id="modalIzinSakit" class="modal-backdrop" style="display: none; z-index: 99999 !important;">
        <div class="card" style="max-width: 480px; width: 92%; margin: auto; padding: 24px; border-radius: 16px; box-shadow: 0 16px 40px rgba(0,0,0,0.5);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <h3 id="titleModalIzin" style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Pencatatan Izin / Sakit Mapel
                </h3>
                <button type="button" id="btnCloseIzinModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formIzinSakit">
                <input type="hidden" id="izinPdId">
                <input type="hidden" id="izinStatusVal">

                <div style="margin-bottom: 14px; font-size: 0.85rem;">
                    <span style="color: var(--text-muted);">Nama Peserta Didik:</span>
                    <strong id="izinPdNama" style="display: block; font-size: 1rem; color: var(--text-color);"></strong>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 5px;">
                        Keterangan / Alasan KBM:
                    </label>
                    <textarea name="keterangan" id="izinKeteranganInput" rows="3" required placeholder="Tuliskan alasan izin/sakit pada jam mapel ini..." class="form-control" style="width: 100%; font-size: 0.85rem;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" id="btnCancelIzinModal" class="btn btn-outline" style="padding: 8px 18px; font-size: 0.85rem;">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary" style="padding: 8px 22px; font-size: 0.85rem; font-weight: 700;">
                        Simpan Presensi Mapel
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/presensi-kelas.js') }}?v={{ file_exists(public_path('js/presensi-kelas.js')) ? filemtime(public_path('js/presensi-kelas.js')) : time() }}"></script>
@endpush
