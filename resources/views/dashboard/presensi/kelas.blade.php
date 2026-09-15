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
                <i class="fas fa-users-viewfinder text-primary me-2"></i> Presensi Rombongan Belajar
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Verifikasi manual kehadiran peserta didik, rekapitulasi harian kelas, serta pencatatan surat izin &amp; sakit oleh Guru dan Wali Kelas.
            </p>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 10px; flex-wrap: wrap;">
            @if ($selectedRombel)
                <button type="button" class="btn btn-danger" id="btnTandaiAlpha"
                    data-rombel="{{ $selectedRombelId }}"
                    data-rombel-nama="{{ $selectedRombel->nama }}"
                    style="padding: 9px 16px; font-size: 0.85rem; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fas fa-user-xmark"></i> Tandai Sisa sebagai Alpha
                </button>
            @endif
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="card" style="padding: 18px 20px; border-radius: 14px; margin-bottom: 20px;">
        <form action="{{ route('dashboard.presensi.kelas') }}" method="GET" style="display: flex; gap: 14px; flex-wrap: wrap; align-items: flex-end;">
            <div style="flex: 1; min-width: 220px;">
                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 5px;">
                    Pilih Rombongan Belajar:
                </label>
                <select name="rombel_id" class="form-control" style="width: 100%; padding: 9px 12px; font-size: 0.88rem;">
                    @foreach ($rombelList as $r)
                        <option value="{{ $r->rombongan_belajar_id }}" {{ $selectedRombelId == $r->rombongan_belajar_id ? 'selected' : '' }}>
                            {{ $r->nama }} {{ $waliRombel === $r->rombongan_belajar_id ? '(Kelas Binaan Anda)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 5px;">
                    Tanggal Presensi:
                </label>
                <input type="date" id="kelasTanggalInput" name="tanggal" value="{{ $tanggal }}" class="form-control" style="padding: 9px 12px; font-size: 0.88rem;">
            </div>

            <div>
                <button type="submit" class="btn btn-primary" style="padding: 9px 20px; font-size: 0.88rem; font-weight: 600;">
                    <i class="fas fa-filter me-1"></i> Tampilkan Kelas
                </button>
            </div>
        </form>
    </div>

    <!-- Rekap Status Kelas Mini-Stats -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; margin-bottom: 20px;">
        <div class="card" style="padding: 12px 16px; border-radius: 12px; text-align: center;">
            <div style="font-size: 1.25rem; font-weight: 800; color: var(--text-color);">{{ $rekap['total'] }}</div>
            <div style="font-size: 0.75rem; color: var(--text-muted);">Total Siswa</div>
        </div>
        <div class="card" style="padding: 12px 16px; border-radius: 12px; text-align: center; border-left: 3px solid var(--success);">
            <div style="font-size: 1.25rem; font-weight: 800; color: var(--success);">{{ $rekap['hadir'] }}</div>
            <div style="font-size: 0.75rem; color: var(--text-muted);">Hadir</div>
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
            <div style="font-size: 0.75rem; color: var(--text-muted);">Belum Absen</div>
        </div>
    </div>

    <!-- Table Daftar Siswa & Presensi Cepat -->
    <div class="card" style="padding: 20px; border-radius: 14px; margin-bottom: 24px;">
        <div class="table-responsive">
            <table class="table" style="width: 100%; font-size: 0.85rem;">
                <thead>
                    <tr>
                        <th style="width: 45px; text-align: center;">No</th>
                        <th style="width: 45px;">Foto</th>
                        <th>Nama Peserta Didik</th>
                        <th>NISN</th>
                        <th>Jam Masuk</th>
                        <th style="text-align: center;">Status Presensi</th>
                        <th style="text-align: center;">Ubah Status Cepat</th>
                        <th style="text-align: center; width: 90px;">Lampiran</th>
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
                            <td>
                                @if ($s->jam_masuk)
                                    <div style="font-weight: 700; font-family: monospace; color: var(--text-color);">
                                        {{ substr($s->jam_masuk, 0, 5) }} WIB
                                    </div>
                                    @if ($s->menit_terlambat > 0)
                                        <div style="font-size: 0.72rem; color: var(--warning);">+{{ $s->menit_terlambat }}m terlambat</div>
                                    @endif
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.8rem;">-</span>
                                @endif
                            </td>
                            <td style="text-align: center;" id="badge-status-{{ $s->peserta_didik_id }}">
                                @if ($s->status_presensi === 'H')
                                    <span class="badge badge-success">Hadir</span>
                                @elseif ($s->status_presensi === 'T')
                                    <span class="badge badge-warning">Terlambat</span>
                                @elseif ($s->status_presensi === 'I')
                                    <span class="badge badge-primary">Izin</span>
                                @elseif ($s->status_presensi === 'S')
                                    <span class="badge" style="background: rgba(139,92,246,0.15); color: #8b5cf6;">Sakit</span>
                                @elseif ($s->status_presensi === 'D')
                                    <span class="badge badge-accent">Dispen</span>
                                @elseif ($s->status_presensi === 'A')
                                    <span class="badge badge-danger">Alpha</span>
                                @else
                                    <span class="badge" style="background: rgba(148,163,184,0.15); color: var(--text-muted);">Belum</span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                <div style="display: inline-flex; gap: 4px;">
                                    <button type="button" class="btn-status-toggle {{ $s->status_presensi === 'H' ? 'active-H' : '' }}"
                                        data-id="{{ $s->peserta_didik_id }}" data-nama="{{ $s->nama }}" data-status="H" title="Hadir Tepat Waktu">
                                        H
                                    </button>
                                    <button type="button" class="btn-status-toggle {{ $s->status_presensi === 'T' ? 'active-T' : '' }}"
                                        data-id="{{ $s->peserta_didik_id }}" data-nama="{{ $s->nama }}" data-status="T" title="Terlambat">
                                        T
                                    </button>
                                    <button type="button" class="btn-status-toggle {{ $s->status_presensi === 'I' ? 'active-I' : '' }}"
                                        data-id="{{ $s->peserta_didik_id }}" data-nama="{{ $s->nama }}" data-status="I" title="Izin (Input Keterangan)">
                                        I
                                    </button>
                                    <button type="button" class="btn-status-toggle {{ $s->status_presensi === 'S' ? 'active-S' : '' }}"
                                        data-id="{{ $s->peserta_didik_id }}" data-nama="{{ $s->nama }}" data-status="S" title="Sakit (Upload Surat Dokter)">
                                        S
                                    </button>
                                    <button type="button" class="btn-status-toggle {{ $s->status_presensi === 'A' ? 'active-A' : '' }}"
                                        data-id="{{ $s->peserta_didik_id }}" data-nama="{{ $s->nama }}" data-status="A" title="Alpha (Tanpa Keterangan)">
                                        A
                                    </button>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                @if ($s->lampiran_url)
                                    <button type="button" class="btn btn-outline btn-preview-lampiran"
                                        data-url="{{ $s->lampiran_url }}" style="padding: 4px 8px; font-size: 0.75rem;" title="Lihat Berkas Surat">
                                        <i class="fas fa-file-lines text-primary"></i>
                                    </button>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.75rem;">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                Silakan pilih rombongan belajar terlebih dahulu untuk menampilkan daftar siswa.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form Pencatatan Izin / Sakit -->
    <div id="modalIzinSakit" class="modal-backdrop" style="display: none; z-index: 99999 !important;">
        <div class="card" style="max-width: 480px; width: 92%; margin: auto; padding: 24px; border-radius: 16px; box-shadow: 0 16px 40px rgba(0,0,0,0.5);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <h3 id="titleModalIzin" style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Pencatatan Izin / Sakit
                </h3>
                <button type="button" id="btnCloseIzinModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formIzinSakit" enctype="multipart/form-data">
                <input type="hidden" id="izinPdId">
                <input type="hidden" id="izinStatusVal">

                <div style="margin-bottom: 14px; font-size: 0.85rem;">
                    <span style="color: var(--text-muted);">Nama Siswa:</span>
                    <strong id="izinPdNama" style="display: block; font-size: 1rem; color: var(--text-color);"></strong>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 5px;">
                        Keterangan / Alasan:
                    </label>
                    <textarea name="keterangan" rows="3" required placeholder="Tuliskan keterangan izin atau sakit..." class="form-control" style="width: 100%; font-size: 0.85rem;"></textarea>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 5px;">
                        Unggah Surat Permohonan / Surat Dokter (Opsional):
                    </label>
                    <input type="file" name="lampiran" accept=".jpg,.jpeg,.png,.pdf" class="form-control" style="width: 100%; font-size: 0.82rem; padding: 7px;">
                    <small style="color: var(--text-muted); font-size: 0.72rem; display: block; margin-top: 4px;">
                        Format: JPG, PNG, atau PDF (Maksimal 4 MB).
                    </small>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" id="btnCancelIzinModal" class="btn btn-outline" style="padding: 8px 18px; font-size: 0.85rem;">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary" style="padding: 8px 22px; font-size: 0.85rem; font-weight: 700;">
                        Simpan Presensi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Preview Berkas Surat -->
    <div id="modalPreviewLampiran" class="modal-backdrop" style="display: none; z-index: 99999 !important;">
        <div class="card" style="max-width: 650px; width: 92%; margin: auto; padding: 20px; border-radius: 16px; box-shadow: 0 16px 40px rgba(0,0,0,0.5);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <h4 style="font-size: 0.95rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    <i class="fas fa-file-lines text-primary me-2"></i> Berkas Lampiran Surat
                </h4>
                <button type="button" id="btnCloseLampiranModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="margin: 10px 0; text-align: center;">
                <iframe id="frameLampiran" src="" style="width: 100%; height: 450px; border: none; border-radius: 10px; display: none;"></iframe>
                <img id="imgLampiran" src="" alt="Surat Lampiran" style="max-width: 100%; max-height: 450px; border-radius: 10px; object-fit: contain; display: none;">
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/presensi-kelas.js') }}?v={{ file_exists(public_path('js/presensi-kelas.js')) ? filemtime(public_path('js/presensi-kelas.js')) : time() }}"></script>
@endpush
