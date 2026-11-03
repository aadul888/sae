@extends('layouts.dashboard')

@section('title', 'Arsip & Maintenance — SAE')
@section('dash_title', 'Arsip & Maintenance')

@section('content')
    <!-- Header Banner -->
    <div class="dash-banner"
        style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.08), rgba(239, 68, 68, 0.05)); border: 1px solid rgba(99, 102, 241, 0.2);">
        <div>
            <h2 class="dash-banner-title" style="font-size: var(--text-2xl, 1.5rem); font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-server text-primary me-2"></i> Arsip Data Terpadu &amp; Maintenance Sistem
            </h2>
            <p class="dash-banner-subtitle" style="color: var(--text-muted); font-size: var(--text-base, 0.875rem);">
                Fasilitas pencadangan berkas arsip mandiri (SQL, Excel CSV, Pasfoto &amp; Media) sebelum pergantian tahun ajaran serta pembersihan residu sistem.
            </p>
        </div>
        <div class="dash-banner-actions">
            @if($syncAllowed)
                <span class="badge" style="background: rgba(16,185,129,0.15); color: var(--success, #10b981); border: 1px solid rgba(16,185,129,0.3); padding: 8px 16px; font-size: var(--text-base, 0.875rem);">
                    <i class="fas fa-unlock me-1"></i> Gerbang Feeder: Terbuka
                </span>
            @else
                <span class="badge" style="background: rgba(239,68,68,0.15); color: var(--danger, #ef4444); border: 1px solid rgba(239,68,68,0.3); padding: 8px 16px; font-size: var(--text-base, 0.875rem);">
                    <i class="fas fa-lock me-1"></i> Gerbang Feeder: Terkunci (Perlu Arsip)
                </span>
            @endif
        </div>
    </div>

    <!-- Alert Status Proteksi Sinkronisasi -->
    @if($isArchiveRequired)
        <div style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.25); border-radius: var(--radius-lg, 12px); padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 14px;">
            <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(239, 68, 68, 0.15); color: var(--danger, #ef4444); display: flex; align-items: center; justify-content: center; font-size: var(--text-lg, 1.125rem); flex-shrink: 0;">
                <i class="fas fa-shield-halved"></i>
            </div>
            <div>
                <div style="font-weight: 700; color: var(--danger, #ef4444); font-size: var(--text-md, 1rem); margin-bottom: 4px;">
                    Proteksi Data Aktif Diaktifkan — Sinkronisasi Feeder Ditolak
                </div>
                <p style="color: var(--text-color); font-size: var(--text-base, 0.875rem); margin: 0; line-height: 1.5;">
                    Terdapat data aktif di sistem SAE (<b>{{ number_format($counts['peserta_didik']) }} Peserta Didik Aktif</b>, <b>{{ number_format($counts['gtk']) }} Guru/Tendik</b>, dan <b>{{ number_format($counts['rombongan_belajar']) }} Rombel</b>).
                    Untuk mencegah hilangnya riwayat semester sebelumnya, proses sinkronisasi dari <b>SAE Feeder</b> ditolak sampai Anda mengklik <b>"Unduh Paket Arsip Data (.ZIP)"</b> di bawah ini.
                </p>
            </div>
        </div>
    @else
        <div style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.25); border-radius: var(--radius-lg, 12px); padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 14px;">
            <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981); display: flex; align-items: center; justify-content: center; font-size: var(--text-lg, 1.125rem); flex-shrink: 0;">
                <i class="fas fa-circle-check"></i>
            </div>
            <div>
                <div style="font-weight: 700; color: var(--success, #10b981); font-size: var(--text-md, 1rem); margin-bottom: 2px;">
                    Data Telah Terarsip Lengkap &amp; Feeder Siap Sinkron
                </div>
                <p style="color: var(--text-muted); font-size: var(--text-base, 0.875rem); margin: 0;">
                    Paket arsip terakhir diunduh pada <b>{{ $archiveDownloadedAt ? \Carbon\Carbon::parse($archiveDownloadedAt)->format('d M Y, H:i') . ' WIB' : '-' }}</b>.
                    Aplikasi SAE Feeder saat ini diizinkan mengirimkan data baru ke sistem.
                </p>
            </div>
        </div>
    @endif

    @php $activeTab = $tab ?? 'backup'; @endphp

    <!-- Navigasi 2 Tab: Cadangkan (Backup) & Pemulihan (Restore) -->
    <div class="dash-tabs" style="display: flex; gap: 8px; border-bottom: 2px solid var(--border-color); margin-bottom: 22px; flex-wrap: wrap;">
        <button type="button" class="dash-tab-btn {{ $activeTab === 'backup' ? 'active' : '' }}" data-target="#tab-backup"
            style="padding: 12px 20px; font-weight: 700; font-size: var(--text-base, 0.875rem); border: none; background: none; color: {{ $activeTab === 'backup' ? 'var(--primary)' : 'var(--text-muted)' }}; border-bottom: 2px solid {{ $activeTab === 'backup' ? 'var(--primary)' : 'transparent' }}; margin-bottom: -2px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s ease;">
            <i class="fas fa-download"></i><span>Cadangkan Data (Backup)</span>
        </button>
        <button type="button" class="dash-tab-btn {{ $activeTab === 'restore' ? 'active' : '' }}" data-target="#tab-restore"
            style="padding: 12px 20px; font-weight: 700; font-size: var(--text-base, 0.875rem); border: none; background: none; color: {{ $activeTab === 'restore' ? 'var(--primary)' : 'var(--text-muted)' }}; border-bottom: 2px solid {{ $activeTab === 'restore' ? 'var(--primary)' : 'transparent' }}; margin-bottom: -2px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s ease;">
            <i class="fas fa-rotate-left"></i><span>Pemulihan Data (Restore)</span>
        </button>
    </div>

    <!-- ============================ TAB 1: BACKUP ============================ -->
    <div id="tab-backup" class="dash-tab-pane" style="{{ $activeTab === 'backup' ? 'display: block;' : 'display: none;' }}">

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 24px;">

        <!-- Card Unduh Arsip -->
        <div class="card"
            style="padding: 24px; display: flex; flex-direction: column; justify-content: space-between; border-top: 3px solid var(--primary);">
            <div>
                <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 16px;">
                    <div
                        style="width: 54px; height: 54px; background: rgba(99,102,241,0.12); color: var(--primary); font-size: 24px; border-radius: var(--radius-lg, 12px); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-file-zipper"></i>
                    </div>
                    <div>
                        <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 700; margin-bottom: 2px; color: var(--text-color);">
                            Cadangkan &amp; Unduh Paket Arsip
                        </h3>
                        <span style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                            Paket Arsip Mandiri (SQL Dump, Excel CSV Siap Buka, &amp; Media Foto)
                        </span>
                    </div>
                </div>

                <p style="font-size: var(--text-base, 0.875rem); color: var(--text-muted); margin-bottom: 16px; line-height: 1.5;">
                    Menghasilkan paket berkas arsip terpadu yang <strong>dapat dibuka langsung di masa depan</strong> tanpa bergantung pada format JSON mentah semata:
                </p>

                <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 18px; font-size: var(--text-sm, 0.8125rem);">
                    <div style="display: flex; align-items: flex-start; gap: 8px;">
                        <i class="fas fa-database text-primary" style="margin-top: 2px;"></i>
                        <div><strong>01_DATABASE_SQL:</strong> Dump SQL lengkap (semua tabel) siap di-import ke phpMyAdmin / MySQL.</div>
                    </div>
                    <div style="display: flex; align-items: flex-start; gap: 8px;">
                        <i class="fas fa-file-excel text-success" style="margin-top: 2px;"></i>
                        <div><strong>02_DATA_EXCEL_CSV:</strong> Format CSV UTF-8 BOM siap double-click di Microsoft Excel (Peserta Didik, Alumni, GTK, Rombel, Jadwal).</div>
                    </div>
                    <div style="display: flex; align-items: flex-start; gap: 8px;">
                        <i class="fas fa-images text-info" style="margin-top: 2px;"></i>
                        <div><strong>03_BERKAS_MEDIA:</strong> Seluruh pasfoto peserta didik, logo jurusan, dan berkas/kop sekolah.</div>
                    </div>
                </div>

                <div style="background: var(--bg-body); border-radius: var(--radius-md, 8px); padding: 14px; margin-bottom: 20px; font-size: var(--text-sm, 0.8125rem); border: 1px solid var(--border-color);">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span style="color: var(--text-muted);">Peserta Didik Aktif:</span>
                        <b>{{ number_format($counts['peserta_didik']) }} peserta didik</b>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span style="color: var(--text-muted);">Alumni &amp; Tidak Aktif:</span>
                        <b>{{ number_format($counts['peserta_didik_tidak_aktif'] ?? 0) }} peserta didik</b>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span style="color: var(--text-muted);">Guru &amp; Tenaga Kependidikan:</span>
                        <b>{{ number_format($counts['gtk']) }} orang</b>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span style="color: var(--text-muted);">Pasfoto Peserta Didik Tersimpan:</span>
                        <b>{{ number_format($counts['foto_count'] ?? 0) }} foto</b>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-top: 1px solid var(--border-color); padding-top: 6px; margin-top: 4px;">
                        <span style="color: var(--text-muted);">Unduhan Terakhir:</span>
                        <b>{{ $archiveDownloadedAt ? \Carbon\Carbon::parse($archiveDownloadedAt)->format('d/m/Y H:i') : 'Belum Pernah' }}</b>
                    </div>
                </div>
            </div>

            <a href="{{ route('dashboard.maintenance.download') }}" class="btn btn-primary"
                style="padding: 12px 24px; font-weight: 700; border-radius: var(--radius-md, 8px); text-align: center; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <i class="fas fa-file-zipper"></i> Unduh Paket Arsip Lengkap (.ZIP) &amp; Buka Kunci Feeder
            </a>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 8px; margin-top: 10px;">
                <a href="{{ route('dashboard.maintenance.download.sql') }}" class="btn btn-outline"
                    style="padding: 10px 14px; font-weight: 700; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); text-align: center; display: flex; align-items: center; justify-content: center; gap: 8px; border: 1px solid var(--border-color);"
                    title="Unduh berkas .sql berisi seluruh tabel database">
                    <i class="fas fa-database text-primary"></i> Unduh SQL Saja (.sql)
                </a>
                <a href="{{ route('dashboard.maintenance.download.assets') }}" class="btn btn-outline"
                    style="padding: 10px 14px; font-weight: 700; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); text-align: center; display: flex; align-items: center; justify-content: center; gap: 8px; border: 1px solid var(--border-color);"
                    title="Unduh berkas aset/media (foto, logo, stempel, dokumen)">
                    <i class="fas fa-images text-info"></i> Unduh Berkas Aset (.zip)
                </a>
            </div>
        </div>

        <!-- Card Bersihkan Data -->
        <div class="card"
            style="padding: 24px; display: flex; flex-direction: column; justify-content: space-between; border-top: 3px solid var(--danger, #ef4444);">
            <div>
                <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 16px;">
                    <div
                        style="width: 54px; height: 54px; background: rgba(239,68,68,0.12); color: var(--danger, #ef4444); font-size: 24px; border-radius: var(--radius-lg, 12px); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-broom"></i>
                    </div>
                    <div>
                        <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 700; margin-bottom: 2px; color: var(--text-color);">
                            Pembersihan Sistem
                        </h3>
                        <span style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                            Hapus Cache &amp; Residu Session
                        </span>
                    </div>
                </div>

                <p style="font-size: var(--text-base, 0.875rem); color: var(--text-muted); margin-bottom: 16px; line-height: 1.5;">
                    Membersihkan session usang yang kadaluarsa (> 7 hari), berkas dump sementara, dan cache framework Laravel untuk mempercepat performa server.
                </p>

                <div style="background: rgba(239, 68, 68, 0.05); border: 1px dashed rgba(239, 68, 68, 0.2); border-radius: var(--radius-md, 8px); padding: 12px; margin-bottom: 20px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                    <i class="fas fa-info-circle text-primary me-1"></i>
                    Aksi pembersihan ini aman dan <b>TIDAK</b> akan menghapus data pokok Dapodik maupun foto peserta didik.
                </div>
            </div>

            <form action="{{ route('dashboard.maintenance.clean') }}" method="POST" id="formCleanData">
                @csrf
                <button type="submit" class="btn"
                    style="width: 100%; background: rgba(239,68,68,0.15); color: var(--danger, #ef4444); border: 1px solid rgba(239,68,68,0.3); padding: 12px 24px; font-weight: 700; border-radius: var(--radius-md, 8px); transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fas fa-trash-can"></i> Bersihkan Residu Sistem
                </button>
            </form>
        </div>

    </div>

    </div>
    <!-- ========================== END TAB 1: BACKUP ========================== -->

    <!-- =========================== TAB 2: RESTORE ============================ -->
    <div id="tab-restore" class="dash-tab-pane" style="{{ $activeTab === 'restore' ? 'display: block;' : 'display: none;' }}">
        <div class="card"
            style="padding: 24px; border-top: 3px solid var(--warning, #f59e0b); max-width: 760px;">
            <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 16px;">
                <div
                    style="width: 54px; height: 54px; background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b); font-size: 24px; border-radius: var(--radius-lg, 12px); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-rotate-left"></i>
                </div>
                <div>
                    <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 700; margin-bottom: 2px; color: var(--text-color);">
                        Pulihkan Database dari Berkas Arsip
                    </h3>
                    <span style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                        Unggah berkas .sql atau .zip paket arsip SAE untuk memulihkan data
                    </span>
                </div>
            </div>

            <p style="font-size: var(--text-base, 0.875rem); color: var(--text-muted); margin-bottom: 16px; line-height: 1.5;">
                Fitur ini memulihkan seluruh tabel database dari berkas
                <strong>database_sae_lengkap.sql</strong> atau paket arsip <strong>.zip</strong> yang pernah
                diunduh dari halaman ini. Proses menimpa tabel yang ada dengan isi arsip.
            </p>

            <div style="background: rgba(239,68,68,0.06); border: 1px dashed rgba(239,68,68,0.3); border-radius: var(--radius-md, 8px); padding: 12px 14px; margin-bottom: 18px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); line-height: 1.5;">
                <i class="fas fa-triangle-exclamation" style="color: var(--danger, #ef4444);"></i>
                <b>Perhatian:</b> Restore akan <b>menimpa seluruh data</b> aplikasi saat ini. Salinan pengaman
                otomatis dibuat sebelum proses dan disimpan di <code>storage/app/archives</code>. Berkas aset
                (foto/dokumen) dipulihkan terpisah dengan mengekstrak ZIP aset ke folder <code>storage/app/public/assets</code>.
            </div>

            <form action="{{ route('dashboard.maintenance.restore') }}" method="POST" enctype="multipart/form-data" id="formRestoreData">
                @csrf
                @error('restore_file')
                    <div style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: var(--danger, #ef4444); padding: 10px 14px; border-radius: var(--radius-md, 8px); margin-bottom: 14px; font-size: var(--text-sm, 0.8125rem);">
                        <i class="fas fa-circle-exclamation"></i> {{ $message }}
                    </div>
                @enderror
                <label for="restoreFileInput"
                    style="display: flex; align-items: center; gap: 10px; border: 2px dashed var(--border-color); border-radius: var(--radius-md, 8px); padding: 16px; cursor: pointer; margin-bottom: 16px; background: var(--bg-body);">
                    <i class="fas fa-file-arrow-up text-primary" style="font-size: var(--text-lg, 1.125rem);"></i>
                    <span id="restoreFileName" style="font-size: var(--text-base, 0.875rem); color: var(--text-muted);">
                        Pilih berkas .sql atau .zip (maks. 500 MB)
                    </span>
                </label>
                <input type="file" name="restore_file" id="restoreFileInput" accept=".sql,.zip,.txt" style="display: none;">

                <button type="submit" class="btn"
                    style="width: 100%; background: rgba(245,158,11,0.15); color: var(--warning, #f59e0b); border: 1px solid rgba(245,158,11,0.35); padding: 12px 24px; font-weight: 700; border-radius: var(--radius-md, 8px); display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fas fa-rotate-left"></i> Pulihkan Database Sekarang
                </button>
            </form>
        </div>
    </div>
    <!-- ========================= END TAB 2: RESTORE ========================= -->

    @push('scripts')
        <script src="{{ asset('js/maintenance.js') }}?v={{ file_exists(public_path('js/maintenance.js')) ? filemtime(public_path('js/maintenance.js')) : time() }}"></script>
    @endpush
@endsection
