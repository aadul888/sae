@extends('layouts.dashboard')

@section('title', 'Riwayat Presensi Saya — SAE')
@section('dash_title', 'Riwayat Presensi Siswa')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/presensi.css') }}?v={{ file_exists(public_path('css/presensi.css')) ? filemtime(public_path('css/presensi.css')) : time() }}">
@endpush

@section('content')
    <!-- Dash Banner -->
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-id-card-clip text-primary me-2"></i> Portal Presensi Peserta Didik
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Pantau catatan kehadiran harian, waktu ketepatan masuk/pulang, foto verifikasi snapshot, serta pengajuan e-izin secara mandiri.
            </p>
        </div>
        <div class="dash-banner-actions">
            <button type="button" class="btn btn-primary" id="btnBukaModalIzin"
                style="padding: 9px 18px; font-size: 0.85rem; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fas fa-file-signature"></i> Ajukan Surat Izin / Sakit
            </button>
        </div>
    </div>

    <!-- Student Info & Dynamic QR Code Grid -->
    <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 20px; margin-bottom: 24px;">
        <!-- Left: Student Biodata & Monthly Summary Stats -->
        <div class="card" style="padding: 22px; border-radius: 16px; display: flex; flex-direction: column; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 18px; margin-bottom: 20px;">
                <img src="{{ $siswa->foto_url ?: asset('img/logo-dark.png') }}" alt="{{ $siswa->nama }}"
                    style="width: 72px; height: 72px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary); box-shadow: 0 0 16px var(--primary-glow);"
                    onerror="this.src='/img/logo-dark.png';">
                <div>
                    <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--text-color); margin: 0 0 4px 0;">
                        {{ $siswa->nama }}
                    </h3>
                    <div style="font-size: 0.85rem; color: var(--primary); font-weight: 700; margin-bottom: 4px;">
                        {{ $siswa->nama_rombel ?: 'Rombel Belum Ditentukan' }}
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                        <span style="font-family: monospace; font-size: 0.8rem; color: var(--text-muted);">NISN: {{ $siswa->nisn }}</span>
                        @if ($siswa->rfid_uid)
                            <span class="badge badge-success" style="font-size: 0.68rem;"><i class="fas fa-wifi me-1"></i> RFID Aktif</span>
                        @else
                            <span class="badge" style="font-size: 0.68rem; background: rgba(148,163,184,0.15); color: var(--text-muted);">RFID Belum Terdaftar</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Mini Summary Stats -->
            <div>
                <div style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 10px;">
                    Statistik Kehadiran Bulan Ini ({{ now()->translatedFormat('F Y') }})
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(85px, 1fr)); gap: 8px; text-align: center;">
                    <div style="background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.25); padding: 10px 6px; border-radius: 10px;">
                        <div style="font-weight: 800; font-size: 1.2rem; color: var(--success);">{{ $stats['hadir'] }}</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Hadir</div>
                    </div>
                    <div style="background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.25); padding: 10px 6px; border-radius: 10px;">
                        <div style="font-weight: 800; font-size: 1.2rem; color: var(--warning);">{{ $stats['terlambat'] }}</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Terlambat</div>
                    </div>
                    <div style="background: rgba(59,130,246,0.08); border: 1px solid rgba(59,130,246,0.25); padding: 10px 6px; border-radius: 10px;">
                        <div style="font-weight: 800; font-size: 1.2rem; color: var(--primary);">{{ $stats['izin'] }}</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Izin</div>
                    </div>
                    <div style="background: rgba(139,92,246,0.08); border: 1px solid rgba(139,92,246,0.25); padding: 10px 6px; border-radius: 10px;">
                        <div style="font-weight: 800; font-size: 1.2rem; color: #8b5cf6;">{{ $stats['sakit'] }}</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Sakit</div>
                    </div>
                    <div style="background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.25); padding: 10px 6px; border-radius: 10px;">
                        <div style="font-weight: 800; font-size: 1.2rem; color: var(--danger);">{{ $stats['alpha'] }}</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Alpha</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Dynamic QR Code Scanner Card (Anti-Screenshot) -->
        <div class="card" style="padding: 22px; border-radius: 16px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <div style="font-size: 0.88rem; font-weight: 800; color: var(--text-color); margin-bottom: 2px;">
                <i class="fas fa-qrcode text-primary me-1"></i> QR Code Presensi Dinamis
            </div>
            <div style="font-size: 0.74rem; color: var(--text-muted); margin-bottom: 14px;">
                Scan langsung di kamera terminal sekolah (Anti-Screenshot)
            </div>

            <div style="background: #fff; padding: 10px; border-radius: 12px; display: inline-block; box-shadow: 0 4px 16px rgba(0,0,0,0.25); margin-bottom: 12px;">
                <img src="{{ $dynamicQrUri }}" alt="Dynamic QR" style="width: 150px; height: 150px; display: block;">
            </div>

            <!-- Countdown Timer Progress Bar -->
            <div style="width: 100%; max-width: 220px; margin-bottom: 6px;">
                <div style="height: 4px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden;">
                    <div id="qrProgressBar" style="height: 100%; width: 0%; background: var(--primary); transition: width 1s linear;"></div>
                </div>
            </div>

            <div style="font-size: 0.75rem; color: var(--text-muted);">
                Kode diperbarui dalam: <strong id="qrCountdownText" style="color: var(--primary);">60d</strong>
            </div>
        </div>
    </div>

    <!-- Log Riwayat Presensi Tabel -->
    <div class="card" style="padding: 20px; border-radius: 14px; margin-bottom: 24px;">
        <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-color); margin-bottom: 6px;">
            <i class="fas fa-calendar-check text-primary me-2"></i> Log Presensi Harian Anda
        </h3>
        <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 16px;">
            Daftar riwayat tap masuk, pulang, dan foto snapshot verifikasi kamera live terminal.
        </p>

        <div class="table-responsive">
            <table class="table" style="width: 100%; font-size: 0.85rem;">
                <thead>
                    <tr>
                        <th style="width: 120px;">Tanggal</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Status Kehadiran</th>
                        <th>Keterangan</th>
                        <th style="text-align: center; width: 110px;">Foto Bukti</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $l)
                        <tr>
                            <td style="font-weight: 700; color: var(--text-color);">
                                {{ \Carbon\Carbon::parse($l->tanggal)->translatedFormat('d F Y') }}
                            </td>
                            <td>
                                @if ($l->jam_masuk)
                                    <div style="font-weight: 700; font-family: monospace; color: var(--text-color);">
                                        {{ substr($l->jam_masuk, 0, 5) }} WIB
                                    </div>
                                    <div style="font-size: 0.72rem; color: var(--text-muted);">
                                        Metode: {{ strtoupper($l->metode_masuk ?: 'manual') }}
                                    </div>
                                @else
                                    <span style="color: var(--text-muted);">-</span>
                                @endif
                            </td>
                            <td>
                                @if ($l->jam_pulang)
                                    <div style="font-weight: 700; font-family: monospace; color: var(--text-color);">
                                        {{ substr($l->jam_pulang, 0, 5) }} WIB
                                    </div>
                                    <div style="font-size: 0.72rem; color: var(--text-muted);">
                                        Metode: {{ strtoupper($l->metode_pulang ?: 'manual') }}
                                    </div>
                                @else
                                    <span style="color: var(--text-muted);">-</span>
                                @endif
                            </td>
                            <td>
                                @if ($l->status === 'H')
                                    <span class="badge badge-success"><i class="fas fa-check-circle me-1"></i> Tepat Waktu</span>
                                @elseif ($l->status === 'T')
                                    <span class="badge badge-warning"><i class="fas fa-clock me-1"></i> Terlambat +{{ $l->menit_terlambat }}m</span>
                                @elseif ($l->status === 'I')
                                    <span class="badge badge-primary"><i class="fas fa-file-signature me-1"></i> Izin</span>
                                @elseif ($l->status === 'S')
                                    <span class="badge" style="background: rgba(139,92,246,0.15); color: #8b5cf6;"><i class="fas fa-notes-medical me-1"></i> Sakit</span>
                                @elseif ($l->status === 'D')
                                    <span class="badge badge-accent"><i class="fas fa-award me-1"></i> Dispen</span>
                                @else
                                    <span class="badge badge-danger"><i class="fas fa-times-circle me-1"></i> Alpha</span>
                                @endif
                            </td>
                            <td>{{ $l->keterangan ?: '-' }}</td>
                            <td style="text-align: center;">
                                @if ($l->foto_masuk_url)
                                    <button type="button" class="btn btn-outline btn-view-snapshot"
                                        data-url="{{ $l->foto_masuk_url }}"
                                        data-caption="Snapshot Masuk — {{ \Carbon\Carbon::parse($l->tanggal)->translatedFormat('d M Y') }}"
                                        style="padding: 4px 8px; font-size: 0.75rem;" title="Foto Masuk">
                                        <i class="fas fa-camera text-primary"></i>
                                    </button>
                                @endif
                                @if ($l->foto_pulang_url)
                                    <button type="button" class="btn btn-outline btn-view-snapshot"
                                        data-url="{{ $l->foto_pulang_url }}"
                                        data-caption="Snapshot Pulang — {{ \Carbon\Carbon::parse($l->tanggal)->translatedFormat('d M Y') }}"
                                        style="padding: 4px 8px; font-size: 0.75rem; margin-left: 4px;" title="Foto Pulang">
                                        <i class="fas fa-camera text-success"></i>
                                    </button>
                                @endif
                                @if (!$l->foto_masuk_url && !$l->foto_pulang_url)
                                    <span style="color: var(--text-muted); font-size: 0.75rem;">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                Belum ada rekaman data presensi untuk akun Anda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Custom Pagination --}}
        @if ($logs->hasPages())
            <div class="custom-pagination">
                @if ($logs->onFirstPage())
                    <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                @else
                    <a href="{{ $logs->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
                @endif
                @php
                    $cur = $logs->currentPage();
                    $last = $logs->lastPage();
                    $from = max(1, $cur - 2);
                    $to = min($last, $cur + 2);
                @endphp
                @if ($from > 1)
                    <a href="{{ $logs->url(1) }}" class="page-btn">1</a>
                    @if ($from > 2)
                        <span class="page-info">&hellip;</span>
                    @endif
                @endif
                @for ($i = $from; $i <= $to; $i++)
                    <a href="{{ $logs->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                @endfor
                @if ($to < $last)
                    @if ($to < $last - 1)
                        <span class="page-info">&hellip;</span>
                    @endif
                    <a href="{{ $logs->url($last) }}" class="page-btn">{{ $last }}</a>
                @endif
                @if ($logs->hasMorePages())
                    <a href="{{ $logs->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
                @else
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        @endif
    </div>

    <!-- Riwayat Pengajuan E-Izin -->
    <div class="card" style="padding: 20px; border-radius: 14px; margin-bottom: 24px;">
        <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
            <i class="fas fa-envelope-open-text text-primary me-2"></i> Riwayat Pengajuan Surat Izin / Sakit
        </h3>
        <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 16px;">
            Status verifikasi permohonan surat keterangan tidak masuk sekolah yang telah Anda ajukan.
        </p>

        <div class="table-responsive">
            <table class="table" style="width: 100%; font-size: 0.85rem;">
                <thead>
                    <tr>
                        <th>Tgl Pengajuan</th>
                        <th>Jenis</th>
                        <th>Rentang Tanggal</th>
                        <th>Alasan</th>
                        <th>Surat Lampiran</th>
                        <th>Status Verifikasi</th>
                        <th>Catatan Petugas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftarIzin as $iz)
                        <tr>
                            <td>{{ $iz->created_at->translatedFormat('d M Y') }}</td>
                            <td><span class="badge {{ $iz->jenis === 'sakit' ? 'badge-purple' : 'badge-primary' }}">{{ $iz->jenis_label }}</span></td>
                            <td>{{ \Carbon\Carbon::parse($iz->tanggal_mulai)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($iz->tanggal_selesai)->format('d/m/Y') }}</td>
                            <td>{{ $iz->alasan }}</td>
                            <td>
                                @if ($iz->lampiran_url)
                                    <a href="{{ $iz->lampiran_url }}" target="_blank" class="btn btn-outline" style="padding: 4px 8px; font-size: 0.75rem;">
                                        <i class="fas fa-paperclip me-1"></i> Buka File
                                    </a>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.75rem;">-</span>
                                @endif
                            </td>
                            <td>{!! $iz->status_badge !!}</td>
                            <td>{{ $iz->catatan_petugas ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 25px; color: var(--text-muted);">
                                Anda belum pernah mengajukan surat izin atau sakit.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form Pengajuan E-Izin -->
    <div id="modalPengajuanIzin" class="modal-backdrop" style="display: none; z-index: 99999 !important;">
        <div class="card" style="max-width: 500px; width: 92%; margin: auto; padding: 24px; border-radius: 16px; box-shadow: 0 16px 40px rgba(0,0,0,0.5);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    <i class="fas fa-file-signature text-primary me-2"></i> Pengajuan Surat Izin / Sakit
                </h3>
                <button type="button" id="btnCloseModalIzin" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formPengajuanIzin" enctype="multipart/form-data">
                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 5px;">
                        Jenis Permohonan:
                    </label>
                    <select name="jenis" required class="form-control" style="width: 100%; padding: 8px 12px; font-size: 0.85rem;">
                        <option value="izin">Izin (Keperluan Keluarga / Penting)</option>
                        <option value="sakit">Sakit (Wajib Lampirkan Surat Dokter)</option>
                        <option value="dispen">Dispensasi (Lomba / Tugas Sekolah)</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 5px;">
                            Mulai Tanggal:
                        </label>
                        <input type="date" name="tanggal_mulai" value="{{ now()->toDateString() }}" required class="form-control" style="width: 100%; font-size: 0.85rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 5px;">
                            Sampai Tanggal:
                        </label>
                        <input type="date" name="tanggal_selesai" value="{{ now()->toDateString() }}" required class="form-control" style="width: 100%; font-size: 0.85rem;">
                    </div>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 5px;">
                        Alasan &amp; Keterangan Lengkap:
                    </label>
                    <textarea name="alasan" rows="3" required placeholder="Jelaskan alasan izin atau kondisi sakit Anda..." class="form-control" style="width: 100%; font-size: 0.85rem;"></textarea>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 5px;">
                        Unggah Bukti Surat / Foto Surat Dokter:
                    </label>
                    <input type="file" name="lampiran" accept=".jpg,.jpeg,.png,.pdf" class="form-control" style="width: 100%; font-size: 0.82rem; padding: 7px;">
                    <small style="color: var(--text-muted); font-size: 0.72rem; display: block; margin-top: 4px;">
                        Format: JPG, PNG, atau PDF (Maksimal 4 MB).
                    </small>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" id="btnCancelModalIzin" class="btn btn-outline" style="padding: 8px 18px; font-size: 0.85rem;">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary" style="padding: 8px 22px; font-size: 0.85rem; font-weight: 700;">
                        Kirim Permohonan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal View Snapshot -->
    <div id="modalSnapshotSaya" class="modal-backdrop" style="display: none; z-index: 99999 !important;">
        <div class="card" style="max-width: 440px; width: 92%; margin: auto; padding: 20px; border-radius: 16px; text-align: center;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <h4 id="snapshotSayaCaption" style="font-size: 0.95rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Foto Bukti Presensi
                </h4>
                <button type="button" id="btnCloseSnapshotSaya" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="margin: 10px 0;">
                <img id="imgSnapshotSaya" src="" alt="Snapshot" style="width: 100%; border-radius: 12px; object-fit: cover; max-height: 380px; border: 1px solid var(--border-color);">
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/presensi-saya.js') }}?v={{ file_exists(public_path('js/presensi-saya.js')) ? filemtime(public_path('js/presensi-saya.js')) : time() }}"></script>
@endpush
