@extends('layouts.dashboard')

@section('title', 'Arsip & Maintenance — SAE')
@section('dash_title', 'Arsip & Maintenance')

@section('content')
    <!-- Header Banner -->
    <div class="dash-banner"
        style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.08), rgba(239, 68, 68, 0.05)); border: 1px solid rgba(99, 102, 241, 0.2);">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-server text-primary me-2"></i> Arsip &amp; Maintenance Data
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Fasilitas pencadangan berkas arsip sebelum pergantian tahun pelajaran serta pembersihan residu sistem.
            </p>
        </div>
        <div class="dash-banner-actions">
            @if($syncAllowed)
                <span class="badge" style="background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); padding: 8px 16px; font-size: 0.85rem;">
                    <i class="fas fa-unlock me-1"></i> Gerbang Feeder: Terbuka
                </span>
            @else
                <span class="badge" style="background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); padding: 8px 16px; font-size: 0.85rem;">
                    <i class="fas fa-lock me-1"></i> Gerbang Feeder: Terkunci (Perlu Arsip)
                </span>
            @endif
        </div>
    </div>

    <!-- Alert Status Proteksi Sinkronisasi -->
    @if($isArchiveRequired)
        <div style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.25); border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 14px;">
            <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(239, 68, 68, 0.15); color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;">
                <i class="fas fa-shield-halved"></i>
            </div>
            <div>
                <div style="font-weight: 700; color: #ef4444; font-size: 0.95rem; margin-bottom: 4px;">
                    Proteksi Data Aktif Diaktifkan — Sinkronisasi Feeder Ditolak
                </div>
                <p style="color: var(--text-color); font-size: 0.85rem; margin: 0; line-height: 1.5;">
                    Terdapat data aktif di sistem SAE (<b>{{ number_format($counts['peserta_didik']) }} Peserta Didik</b>, <b>{{ number_format($counts['gtk']) }} Guru/Tendik</b>, dan <b>{{ number_format($counts['rombongan_belajar']) }} Rombel</b>).
                    Untuk mencegah hilangnya riwayat semester sebelumnya, proses sinkronisasi dari <b>SAE Feeder</b> akan ditolak sampai Anda mengklik <b>"Unduh Arsip (.ZIP)"</b> di bawah ini.
                </p>
            </div>
        </div>
    @else
        <div style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.25); border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 14px;">
            <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(16, 185, 129, 0.15); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;">
                <i class="fas fa-circle-check"></i>
            </div>
            <div>
                <div style="font-weight: 700; color: #10b981; font-size: 0.95rem; margin-bottom: 2px;">
                    Data Lama Telah Terarsip &amp; Feeder Siap Sinkron
                </div>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
                    Arsip data aktif terakhir diunduh pada <b>{{ $archiveDownloadedAt ? date('d M Y, H:i', strtotime($archiveDownloadedAt)) . ' WIB' : '-' }}</b>.
                    Aplikasi SAE Feeder sekarang diizinkan mengirimkan data baru.
                </p>
            </div>
        </div>
    @endif

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 24px;">

        <!-- Card Unduh Arsip -->
        <div class="card"
            style="padding: 24px; display: flex; flex-direction: column; justify-content: space-between; border-top: 3px solid var(--primary);">
            <div>
                <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 16px;">
                    <div
                        style="width: 54px; height: 54px; background: rgba(99,102,241,0.12); color: var(--primary); font-size: 24px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-file-zipper"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 2px; color: var(--text-color);">
                            Cadangkan &amp; Unduh Arsip
                        </h3>
                        <span style="font-size: 0.78rem; color: var(--text-muted);">
                            Format <code>.ZIP</code> lengkap (Semua Tabel Data Pokok)
                        </span>
                    </div>
                </div>

                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 16px; line-height: 1.5;">
                    Mengekspor seluruh data aktif satuan pendidikan (Sekolah, Rombel, Anggota Rombel, Pembelajaran, GTK, Peserta Didik, Akun, dan Tugas Tambahan) ke dalam satu berkas arsip mandiri.
                </p>

                <div style="background: rgba(0,0,0,0.03); border-radius: 8px; padding: 12px; margin-bottom: 20px; font-size: 0.8rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span style="color: var(--text-muted);">Total Peserta Didik:</span>
                        <b>{{ number_format($counts['peserta_didik']) }} siswa</b>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span style="color: var(--text-muted);">Total Guru &amp; Tendik:</span>
                        <b>{{ number_format($counts['gtk']) }} orang</b>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span style="color: var(--text-muted);">Total Rombongan Belajar:</span>
                        <b>{{ number_format($counts['rombongan_belajar']) }} kelas</b>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Arsip Terakhir:</span>
                        <b>{{ $archiveDownloadedAt ? date('d/m/Y H:i', strtotime($archiveDownloadedAt)) : 'Belum Pernah' }}</b>
                    </div>
                </div>
            </div>

            <a href="{{ route('dashboard.maintenance.download') }}" class="btn btn-primary"
                style="padding: 12px 24px; font-weight: 700; border-radius: 8px; text-align: center; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <i class="fas fa-download"></i> Unduh Arsip (.ZIP) &amp; Buka Kunci Feeder
            </a>
        </div>

        <!-- Card Bersihkan Data -->
        <div class="card"
            style="padding: 24px; display: flex; flex-direction: column; justify-content: space-between; border-top: 3px solid #ef4444;">
            <div>
                <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 16px;">
                    <div
                        style="width: 54px; height: 54px; background: rgba(239,68,68,0.12); color: #ef4444; font-size: 24px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-broom"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 2px; color: var(--text-color);">
                            Pembersihan Sistem
                        </h3>
                        <span style="font-size: 0.78rem; color: var(--text-muted);">
                            Hapus Cache &amp; Residu Session
                        </span>
                    </div>
                </div>

                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 16px; line-height: 1.5;">
                    Membersihkan session usang yang kadaluarsa (> 7 hari), berkas dump sementara, dan cache aplikasi Laravel untuk mempercepat performa server.
                </p>

                <div style="background: rgba(239, 68, 68, 0.05); border: 1px dashed rgba(239, 68, 68, 0.2); border-radius: 8px; padding: 12px; margin-bottom: 20px; font-size: 0.8rem; color: var(--text-muted);">
                    <i class="fas fa-info-circle text-primary me-1"></i>
                    Aksi pembersihan ini aman dan <b>TIDAK</b> akan menghapus data pokok Dapodik maupun foto peserta didik.
                </div>
            </div>

            <form action="{{ route('dashboard.maintenance.clean') }}" method="POST" id="formCleanData">
                @csrf
                <button type="submit" class="btn"
                    style="width: 100%; background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); padding: 12px 24px; font-weight: 700; border-radius: 8px; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fas fa-trash-can"></i> Bersihkan Residu Sistem
                </button>
            </form>
        </div>

    </div>

    @push('scripts')
        <script src="{{ asset('js/maintenance.js') }}?v={{ file_exists(public_path('js/maintenance.js')) ? filemtime(public_path('js/maintenance.js')) : time() }}"></script>
    @endpush
@endsection
