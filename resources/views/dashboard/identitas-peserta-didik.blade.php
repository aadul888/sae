@extends('layouts.dashboard')

@section('title', 'Identitas Lengkap Peserta Didik — SAE')
@section('dash_title', 'Identitas Peserta Didik')

@section('content')
    @php
        $photoUrl = $studentPhoto ?? ($pd->foto_url ?? null);
        $statusKonfirmasi = $identitas->status_konfirmasi ?? 'belum_konfirmasi';
    @endphp

    {{-- Switcher Siswa Khusus Administrator & Tim Kesiswaan --}}
    @if (in_array($userRole, ['admin', 'tendik'], true) && !empty($allPdList) && $allPdList->isNotEmpty())
        <div class="card"
            style="margin-bottom: 20px; padding: 12px 18px; border-radius: 12px; background: rgba(99,102,241,0.06); border: 1px dashed rgba(99,102,241,0.3);">
            <form method="GET" action="{{ route('dashboard.identitas.index') }}"
                style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div
                    style="font-size: 0.82rem; font-weight: 700; color: var(--primary); display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-user-shield"></i> Mode Pengelola: Pilih Siswa untuk Meninjau Formulir Lengkap
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <select name="peserta_didik_id" onchange="this.form.submit()" class="form-select"
                        style="font-size: 0.82rem; height: 36px; border-radius: 8px; min-width: 260px;">
                        @foreach ($allPdList as $item)
                            <option value="{{ $item->peserta_didik_id }}"
                                {{ $pd && $pd->peserta_didik_id === $item->peserta_didik_id ? 'selected' : '' }}>
                                {{ $item->nama }} ({{ $item->nama_rombel ?: 'Tanpa Kelas' }}) - NISN: {{ $item->nisn }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    @endif

    <!-- Alert Notifikasi Flash -->
    @if (session('success'))
        <div class="alert alert-success"
            style="padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-circle-check" style="font-size: 1.1rem;"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger"
            style="padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-circle-exclamation" style="font-size: 1.1rem;"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if ($pd)
        <!-- 1. Header Banner Profil Siswa -->
        <div class="dash-banner"
            style="margin-bottom: 24px; background: linear-gradient(135deg, rgba(99, 102, 241, 0.12) 0%, rgba(6, 182, 212, 0.08) 100%); border: 1px solid rgba(99, 102, 241, 0.25); display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; border-radius: 16px; padding: 20px 24px;">
            <div style="display: flex; align-items: center; gap: 18px; flex: 1; min-width: 0;">
                @if ($photoUrl)
                    <div
                        style="flex-shrink: 0; width: 84px; height: 110px; display: flex; align-items: center; justify-content: center; background: transparent; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 14px rgba(0,0,0,0.18);">
                        <img src="{{ $photoUrl }}" alt="{{ $pd->nama }}"
                            style="width: 100%; height: 100%; object-fit: cover;"
                            onerror="this.style.display='none'; this.parentElement.style.display='none';">
                    </div>
                @else
                    <div
                        style="flex-shrink: 0; width: 70px; height: 70px; border-radius: 16px; background: rgba(99,102,241,0.15); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.8rem;">
                        <i class="fas fa-id-card"></i>
                    </div>
                @endif

                <div style="flex: 1; min-width: 0;">
                    <div style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 2px;">
                        Formulir Identitas Resmi Siswa &bull; Dapodik 2027 Rev. 1
                    </div>
                    <h2
                        style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin: 0 0 6px 0; line-height: 1.25;">
                        {{ $identitas->nama ?: $pd->nama }}
                    </h2>
                    <div
                        style="display: flex; align-items: center; gap: 12px; font-size: 0.8rem; color: var(--text-muted); flex-wrap: wrap; margin-bottom: 8px;">
                        <span><i class="fas fa-id-card text-primary me-1"></i> NISN:
                            <strong>{{ $identitas->nisn ?: ($pd->nisn ?: '-') }}</strong></span>
                        <span><i class="fas fa-fingerprint text-info me-1"></i> NIK:
                            <strong>{{ $identitas->nik ?: ($pd->nik ?: '-') }}</strong></span>
                        <span><i class="fas fa-school text-warning me-1"></i> Rombel:
                            <strong>{{ $pd->nama_rombel ?: '-' }}</strong></span>
                        @if ($waliKelas)
                            <span><i class="fas fa-chalkboard-user text-success me-1"></i> Wali Kelas:
                                <strong>{{ $waliKelas['nama'] }}</strong></span>
                        @endif
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <div
                            style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.25); border-radius: 20px; font-size: 0.74rem; font-weight: 700; color: #10b981;">
                            <i class="fas fa-circle-check"></i> Peserta Didik Aktif
                        </div>
                        <div
                            style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.2); border-radius: 20px; font-size: 0.74rem; font-weight: 700; color: var(--primary);">
                            <i class="fas fa-shield-halved"></i> Data Tersimpan Aman (Tidak Tertimpa Dapodik)
                        </div>
                    </div>
                </div>
            </div>

            <div class="dash-banner-actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
                @if ($userRole === 'orang_tua')
                    <a href="{{ route('dashboard.orang-tua') }}" class="btn btn-outline"
                        style="padding: 8px 14px; font-size: 0.85rem; border-radius: 8px;">
                        <i class="fas fa-house me-1"></i> Beranda Orang Tua
                    </a>
                @elseif ($userRole === 'peserta_didik')
                    <a href="{{ route('dashboard.peserta-didik') }}" class="btn btn-outline"
                        style="padding: 8px 14px; font-size: 0.85rem; border-radius: 8px;">
                        <i class="fas fa-house me-1"></i> Beranda Siswa
                    </a>
                @else
                    <a href="{{ route('dashboard.kesiswaan.peserta-didik.index', ['tab' => 'usulan']) }}"
                        class="btn btn-outline" style="padding: 8px 14px; font-size: 0.85rem; border-radius: 8px;">
                        <i class="fas fa-file-pen me-1"></i> Review Kesiswaan
                    </a>
                @endif

                @if (!empty($pd->nisn))
                    <button type="button" class="btn btn-primary" onclick="openKartuPelajarModal('{{ $pd->nisn }}')"
                        style="padding: 8px 16px; font-size: 0.85rem; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fas fa-qrcode"></i> Kartu Pelajar Digital
                    </button>
                @endif
            </div>
        </div>

        <!-- 2. Progress Stepper Progres Usulan Perubahan Data -->
        @php
            $step1Done = $statusKonfirmasi !== 'belum_konfirmasi';
            $step2Done = $berkasPrereq['is_valid'];
            $pendingUsulanCount = $usulanList->where('status', 'menunggu')->count();
            $approvedUsulanCount = $usulanList->where('status', 'disetujui')->count();
            $dapodikUsulanCount = $usulanList->where('status', 'sudah_ke_dapodik')->count();
            $step3Done = $usulanList->isNotEmpty() || $statusKonfirmasi === 'sesuai';
            $step4Done =
                ($usulanList->isNotEmpty() && $pendingUsulanCount === 0) ||
                in_array($statusKonfirmasi, ['sesuai', 'diverifikasi', 'disinkronkan_dapodik'], true);
            $step5Done = $statusKonfirmasi === 'disinkronkan_dapodik' || $dapodikUsulanCount > 0;
        @endphp

        <div class="card" style="margin-bottom: 24px; padding: 20px; border-radius: 14px;">
            <div
                style="font-size: 0.9rem; font-weight: 800; color: var(--text-color); margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-diagram-project text-primary"></i> Progres Usulan &amp; Verifikasi Data Siswa
                </div>
                <span style="font-size: 0.74rem; color: var(--text-muted); font-weight: normal;">
                    Pantau alur verifikasi data dan status update ke aplikasi Dapodik
                </span>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px;">
                <!-- Step 1 -->
                <div
                    style="background: var(--bg-hover); padding: 12px 14px; border-radius: 10px; border: 1.5px solid {{ $step1Done ? '#10b981' : '#f59e0b' }}; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div
                            style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Tahap 1</div>
                        <div style="font-size: 0.85rem; font-weight: 800; color: var(--text-color); margin: 3px 0;">
                            Konfirmasi Data</div>
                    </div>
                    <div style="margin-top: 8px;">
                        @if ($statusKonfirmasi === 'sesuai')
                            <span class="badge badge-success" style="font-size: 0.7rem; padding: 3px 8px;"><i
                                    class="fas fa-check me-1"></i> Data Sesuai</span>
                        @elseif ($statusKonfirmasi === 'perlu_perbaikan')
                            <span class="badge badge-warning" style="font-size: 0.7rem; padding: 3px 8px;"><i
                                    class="fas fa-check me-1"></i> Ada Perubahan</span>
                        @else
                            <span class="badge badge-outline"
                                style="font-size: 0.7rem; padding: 3px 8px; color: #f59e0b;"><i
                                    class="fas fa-clock me-1"></i> Belum Konfirmasi</span>
                        @endif
                    </div>
                </div>

                <!-- Step 2 -->
                <div
                    style="background: var(--bg-hover); padding: 12px 14px; border-radius: 10px; border: 1.5px solid {{ $step2Done ? '#10b981' : ($statusKonfirmasi === 'sesuai' ? 'var(--border-color)' : '#ef4444') }}; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div
                            style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Tahap 2</div>
                        <div style="font-size: 0.85rem; font-weight: 800; color: var(--text-color); margin: 3px 0;">Berkas
                            KK &amp; Ijazah</div>
                    </div>
                    <div style="margin-top: 8px;">
                        @if ($step2Done)
                            <span class="badge badge-success" style="font-size: 0.7rem; padding: 3px 8px;"><i
                                    class="fas fa-shield-halved me-1"></i> Lengkap &amp; Valid</span>
                        @elseif ($statusKonfirmasi === 'sesuai')
                            <span class="badge badge-outline" style="font-size: 0.7rem; padding: 3px 8px;"><i
                                    class="fas fa-minus me-1"></i> Tidak Diperlukan</span>
                        @else
                            <span class="badge badge-danger" style="font-size: 0.7rem; padding: 3px 8px;"><i
                                    class="fas fa-triangle-exclamation me-1"></i> Wajib Valid</span>
                        @endif
                    </div>
                </div>

                <!-- Step 3 -->
                <div
                    style="background: var(--bg-hover); padding: 12px 14px; border-radius: 10px; border: 1.5px solid {{ $step3Done ? '#10b981' : ($isFormEditable ? '#f59e0b' : 'var(--border-color)') }}; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div
                            style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Tahap 3</div>
                        <div style="font-size: 0.85rem; font-weight: 800; color: var(--text-color); margin: 3px 0;">Usulan
                            Perubahan</div>
                    </div>
                    <div style="margin-top: 8px;">
                        @if ($statusKonfirmasi === 'sesuai')
                            <span class="badge badge-success" style="font-size: 0.7rem; padding: 3px 8px;"><i
                                    class="fas fa-check me-1"></i> Data Sah Sesuai</span>
                        @elseif ($usulanList->isNotEmpty())
                            <span class="badge badge-primary" style="font-size: 0.7rem; padding: 3px 8px;"><i
                                    class="fas fa-paper-plane me-1"></i> {{ $usulanList->count() }} Terkirim</span>
                        @elseif ($isFormEditable)
                            <span class="badge badge-warning" style="font-size: 0.7rem; padding: 3px 8px;"><i
                                    class="fas fa-lock-open me-1"></i> Form Terbuka</span>
                        @else
                            <span class="badge badge-outline" style="font-size: 0.7rem; padding: 3px 8px;"><i
                                    class="fas fa-lock me-1"></i> Form Terkunci</span>
                        @endif
                    </div>
                </div>

                <!-- Step 4 -->
                <div
                    style="background: var(--bg-hover); padding: 12px 14px; border-radius: 10px; border: 1.5px solid {{ $step4Done ? '#10b981' : ($pendingUsulanCount > 0 ? '#f59e0b' : 'var(--border-color)') }}; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div
                            style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Tahap 4</div>
                        <div style="font-size: 0.85rem; font-weight: 800; color: var(--text-color); margin: 3px 0;">Review
                            Kesiswaan</div>
                    </div>
                    <div style="margin-top: 8px;">
                        @if ($statusKonfirmasi === 'sesuai')
                            <span class="badge badge-success" style="font-size: 0.7rem; padding: 3px 8px;"><i
                                    class="fas fa-check me-1"></i> Terverifikasi</span>
                        @elseif ($pendingUsulanCount > 0)
                            <span class="badge badge-warning" style="font-size: 0.7rem; padding: 3px 8px;"><i
                                    class="fas fa-clock me-1"></i> Menunggu ({{ $pendingUsulanCount }})</span>
                        @elseif ($approvedUsulanCount > 0)
                            <span class="badge badge-success" style="font-size: 0.7rem; padding: 3px 8px;"><i
                                    class="fas fa-check me-1"></i> Disetujui</span>
                        @else
                            <span class="badge badge-outline" style="font-size: 0.7rem; padding: 3px 8px;"><i
                                    class="fas fa-minus me-1"></i> Menunggu Usulan</span>
                        @endif
                    </div>
                </div>

                <!-- Step 5 -->
                <div
                    style="background: var(--bg-hover); padding: 12px 14px; border-radius: 10px; border: 1.5px solid {{ $step5Done ? '#10b981' : ($approvedUsulanCount > 0 ? 'var(--primary)' : 'var(--border-color)') }}; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div
                            style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Tahap 5</div>
                        <div style="font-size: 0.85rem; font-weight: 800; color: var(--text-color); margin: 3px 0;">Update
                            ke Dapodik</div>
                    </div>
                    <div style="margin-top: 8px;">
                        @if ($step5Done)
                            <span class="badge badge-success" style="font-size: 0.7rem; padding: 3px 8px;"><i
                                    class="fas fa-cloud-arrow-up me-1"></i> Selesai Di-update</span>
                        @elseif ($approvedUsulanCount > 0)
                            <span class="badge badge-primary" style="font-size: 0.7rem; padding: 3px 8px;"><i
                                    class="fas fa-hourglass-half me-1"></i> Siap Input Dapodik</span>
                        @elseif ($statusKonfirmasi === 'sesuai')
                            <span class="badge badge-success" style="font-size: 0.7rem; padding: 3px 8px;"><i
                                    class="fas fa-circle-check me-1"></i> Sah di Dapodik</span>
                        @else
                            <span class="badge badge-outline" style="font-size: 0.7rem; padding: 3px 8px;"><i
                                    class="fas fa-cloud me-1"></i> Belum Di-update</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Status Konfirmasi Siswa & Quick Action Card -->
        <div class="card"
            style="margin-bottom: 24px; padding: 20px 24px; border-radius: 14px; border: 1.5px solid {{ $statusKonfirmasi === 'sesuai' ? '#10b981' : ($statusKonfirmasi === 'perlu_perbaikan' ? ($berkasPrereq['is_valid'] ? 'var(--primary)' : '#ef4444') : '#f59e0b') }}; background: var(--card-bg);">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
                <div style="flex: 1; min-width: 280px;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px; flex-wrap: wrap;">
                        <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0;">
                            Status Verifikasi &amp; Konfirmasi Identitas
                        </h3>
                        @if ($statusKonfirmasi === 'sesuai')
                            <span class="badge badge-success" style="font-size: 0.75rem; padding: 4px 10px;">
                                <i class="fas fa-circle-check me-1"></i> Data Telah Sesuai (Terkonfirmasi &amp; Terkunci)
                            </span>
                        @elseif ($statusKonfirmasi === 'diverifikasi')
                            <span class="badge"
                                style="background: rgba(99,102,241,0.15); color: var(--primary); font-size: 0.75rem; padding: 4px 10px; border: 1px solid rgba(99,102,241,0.3);">
                                <i class="fas fa-badge-check me-1"></i> Terverifikasi oleh Tim Kesiswaan
                            </span>
                        @elseif ($statusKonfirmasi === 'disinkronkan_dapodik')
                            <span class="badge badge-success" style="font-size: 0.75rem; padding: 4px 10px;">
                                <i class="fas fa-cloud-arrow-up me-1"></i> Selesai Di-update ke Dapodik
                            </span>
                        @elseif ($statusKonfirmasi === 'perlu_perbaikan')
                            @if ($berkasPrereq['is_valid'])
                                <span class="badge badge-warning" style="font-size: 0.75rem; padding: 4px 10px;">
                                    <i class="fas fa-lock-open me-1"></i> Formulir Terbuka: Mode Usulan Perubahan
                                </span>
                            @else
                                <span class="badge badge-danger" style="font-size: 0.75rem; padding: 4px 10px;">
                                    <i class="fas fa-lock me-1"></i> Formulir Terkunci: Wajib KK &amp; Ijazah Valid
                                </span>
                            @endif
                        @else
                            <span class="badge"
                                style="background: rgba(245,158,11,0.15); color: #f59e0b; font-size: 0.75rem; padding: 4px 10px; border: 1px solid rgba(245,158,11,0.3);">
                                <i class="fas fa-lock me-1"></i> Belum Dikonfirmasi (Formulir Terkunci)
                            </span>
                        @endif
                    </div>
                    <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0; line-height: 1.5;">
                        @if ($statusKonfirmasi === 'sesuai')
                            Dikonfirmasi pada
                            {{ $identitas->dikonfirmasi_pada ? $identitas->dikonfirmasi_pada->translatedFormat('d F Y, H:i') : '-' }}
                            WIB oleh {{ $identitas->dikonfirmasi_oleh ?: 'Siswa' }}. Seluruh data terkunci aman.
                        @elseif ($statusKonfirmasi === 'perlu_perbaikan')
                            @if ($berkasPrereq['is_valid'])
                                Berkas Kartu Keluarga dan Ijazah SMP telah berstatus <strong>Valid / Sesuai</strong>.
                                Formulir sekarang <strong>terbuka</strong>. Silakan periksa dan perbaiki kolom data di
                                bawah, lalu klik <strong>"Simpan &amp; Ajukan Usulan Perubahan Data"</strong>.
                            @else
                                Anda menyatakan terdapat ketidaksesuaian data identitas. Namun pengisian usulan perubahan
                                <strong>hanya terbuka jika dokumen Kartu Keluarga dan Ijazah SMP telah divalidasi
                                    Valid</strong> oleh Tim Kesiswaan.
                            @endif
                        @elseif ($statusKonfirmasi === 'disinkronkan_dapodik')
                            Seluruh usulan perubahan data Anda telah diverifikasi oleh tim kesiswaan dan berhasil
                            disinkronkan ke aplikasi Dapodik Kemendikbudristek.
                        @else
                            Secara default, formulir identitas ini <strong>terkunci</strong> untuk mencegah manipulasi data.
                            Peserta didik <strong>diwajibkan melakukan konfirmasi terlebih dahulu</strong> dengan memilih
                            apakah data saat ini sudah sesuai atau memerlukan perbaikan.
                        @endif
                    </p>
                </div>

                <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                    @if (!$berkasPrereq['is_valid'] && $statusKonfirmasi === 'perlu_perbaikan')
                        <a href="{{ route('dashboard.berkas.index') }}" class="btn btn-primary"
                            style="padding: 9px 18px; font-size: 0.85rem; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-folder-open"></i> Upload Berkas (KK &amp; Ijazah)
                        </a>
                    @endif

                    <button type="button"
                        class="btn {{ $statusKonfirmasi === 'belum_konfirmasi' ? 'btn-primary' : 'btn-outline' }} btn-konfirmasi-dialog"
                        style="padding: 9px 18px; font-size: 0.85rem; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fas fa-clipboard-check"></i>
                        {{ $statusKonfirmasi === 'belum_konfirmasi' ? 'Konfirmasi Data Siswa' : 'Ubah Pilihan Konfirmasi' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Form Konfirmasi Tersembunyi (Disubmit via SweetAlert Dialog) -->
        <form id="formActionKonfirmasi" action="{{ route('dashboard.identitas.konfirmasi') }}" method="POST"
            style="display: none;">
            @csrf
            <input type="hidden" name="peserta_didik_id" value="{{ $pd->peserta_didik_id }}">
            <input type="hidden" name="status" id="inputStatusKonfirmasi" value="sesuai">
            <input type="hidden" name="catatan_siswa" id="inputCatatanKonfirmasi" value="">
        </form>

        <!-- 4. Navigation Tabs Filter Seksi Formulir -->
        <div class="periode-nav-wrapper" style="margin-bottom: 20px;">
            <div class="periode-nav-desktop" style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button type="button" class="btn btn-primary identitas-tab-btn active" data-target="all"
                    style="padding: 8px 14px; font-size: 0.82rem; font-weight: 700; border-radius: 8px;">
                    <i class="fas fa-list me-1"></i> Tampilkan Semua Bagian
                </button>
                <button type="button" class="btn btn-outline identitas-tab-btn" data-target="pribadi_alamat"
                    style="padding: 8px 14px; font-size: 0.82rem; font-weight: 700; border-radius: 8px;">
                    <i class="fas fa-user me-1"></i> Data Pribadi &amp; Domisili
                </button>
                <button type="button" class="btn btn-outline identitas-tab-btn" data-target="orang_tua"
                    style="padding: 8px 14px; font-size: 0.82rem; font-weight: 700; border-radius: 8px;">
                    <i class="fas fa-people-roof me-1"></i> Orang Tua &amp; Wali
                </button>
                <button type="button" class="btn btn-outline identitas-tab-btn" data-target="registrasi_kesejahteraan"
                    style="padding: 8px 14px; font-size: 0.82rem; font-weight: 700; border-radius: 8px;">
                    <i class="fas fa-address-book me-1"></i> Kontak, Pendaftaran &amp; Kesejahteraan
                </button>
                <button type="button" class="btn btn-outline identitas-tab-btn" data-target="prestasi_minat"
                    style="padding: 8px 14px; font-size: 0.82rem; font-weight: 700; border-radius: 8px;">
                    <i class="fas fa-trophy me-1"></i> Prestasi &amp; Minat
                </button>
                <button type="button" class="btn btn-outline identitas-tab-btn" data-target="periodik"
                    style="padding: 8px 14px; font-size: 0.82rem; font-weight: 700; border-radius: 8px;">
                    <i class="fas fa-ruler-combined me-1"></i> Data Periodik
                </button>
            </div>
        </div>

        <!-- 5. FORMULIR IDENTITAS LENGKAP PESERTA DIDIK -->
        <form action="{{ route('dashboard.identitas.update') }}" method="POST" id="formIdentitasPesertaDidik">
            @csrf
            <input type="hidden" name="peserta_didik_id" value="{{ $pd->peserta_didik_id }}">

            <fieldset {{ $isFormLocked ? 'disabled' : '' }}
                style="border: none; padding: 0; margin: 0; min-inline-size: auto;">

                <!-- ========================================== -->
                <!-- SEKSI 1: DATA PRIBADI PESERTA DIDIK -->
                <!-- ========================================== -->
                <div class="card identitas-form-section" data-section="pribadi_alamat"
                    style="margin-bottom: 24px; padding: 22px 24px; border-radius: 14px;">
                    <div
                        style="font-weight: 800; font-size: 1.05rem; color: var(--primary); margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-id-card"></i> Bagian 1: Data Pribadi Peserta Didik
                        </div>
                        <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">Sesuai Akta
                            Kelahiran
                            &amp; Kartu Keluarga</span>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px;">
                        <!-- Nama Lengkap -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Nama Lengkap Siswa
                                <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control"
                                value="{{ old('nama', $identitas->nama ?: $pd->nama) }}" required
                                style="font-size: 0.85rem;">
                        </div>

                        <!-- Jenis Kelamin -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Jenis Kelamin <span
                                    class="text-danger">*</span></label>
                            <div style="display: flex; gap: 18px; align-items: center; height: 38px;">
                                <label
                                    style="font-size: 0.85rem; display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                    <input type="radio" name="jenis_kelamin" value="L"
                                        {{ old('jenis_kelamin', $identitas->jenis_kelamin ?: $pd->jenis_kelamin) === 'L' ? 'checked' : '' }}>
                                    Laki-laki
                                </label>
                                <label
                                    style="font-size: 0.85rem; display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                    <input type="radio" name="jenis_kelamin" value="P"
                                        {{ old('jenis_kelamin', $identitas->jenis_kelamin ?: $pd->jenis_kelamin) === 'P' ? 'checked' : '' }}>
                                    Perempuan
                                </label>
                            </div>
                        </div>

                        <!-- NISN (Permanen - Readonly) -->
                        <div>
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                <label class="form-label"
                                    style="font-size: 0.82rem; font-weight: 700; margin: 0;">NISN</label>
                                <span class="badge"
                                    style="background: rgba(148,163,184,0.15); color: var(--text-muted); font-size: 0.68rem; padding: 2px 6px;">
                                    <i class="fas fa-lock me-1"></i>Permanen Dapodik
                                </span>
                            </div>
                            <input type="text" value="{{ $identitas->nisn ?: $pd->nisn }}" class="form-control"
                                readonly disabled
                                style="font-size: 0.85rem; background: var(--bg-hover); color: var(--text-muted); font-family: monospace;"
                                title="Nomor Induk Siswa Nasional bersifat unik & permanen dari Pusdatin Kemdikbud.">
                        </div>

                        <!-- NIPD (Permanen - Readonly) -->
                        <div>
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                <label class="form-label" style="font-size: 0.82rem; font-weight: 700; margin: 0;">Nomor
                                    Induk
                                    Siswa (NIPD)</label>
                                <span class="badge"
                                    style="background: rgba(148,163,184,0.15); color: var(--text-muted); font-size: 0.68rem; padding: 2px 6px;">
                                    <i class="fas fa-lock me-1"></i>Permanen Sekolah
                                </span>
                            </div>
                            <input type="text" value="{{ $identitas->nipd ?: ($pd->nipd ?: '-') }}"
                                class="form-control" readonly disabled
                                style="font-size: 0.85rem; background: var(--bg-hover); color: var(--text-muted); font-family: monospace;"
                                title="Nomor Induk Peserta Didik lokal sekolah bersifat permanen.">
                        </div>

                        <!-- NIK Siswa -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">NIK Siswa (16 Digit)
                                <span class="text-danger">*</span></label>
                            <input type="text" name="nik" maxlength="16" class="form-control"
                                value="{{ old('nik', $identitas->nik ?: $pd->nik) }}" placeholder="3203xxxxxxxxxxxx"
                                style="font-size: 0.85rem; font-family: monospace;">
                        </div>

                        <!-- No. Kartu Keluarga (KK) -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Nomor Kartu Keluarga
                                (No.
                                KK)</label>
                            <input type="text" name="no_kk" maxlength="16" class="form-control"
                                value="{{ old('no_kk', $identitas->no_kk) }}" placeholder="3203xxxxxxxxxxxx"
                                style="font-size: 0.85rem; font-family: monospace;">
                        </div>

                        <!-- No. Registrasi Akta Lahir -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">No. Registrasi Akta
                                Lahir</label>
                            <input type="text" name="no_registrasi_akta_lahir" class="form-control"
                                value="{{ old('no_registrasi_akta_lahir', $identitas->no_registrasi_akta_lahir) }}"
                                placeholder="Contoh: 3203-LT-xxxx" style="font-size: 0.85rem;">
                        </div>

                        <!-- Kewarganegaraan -->
                        <div>
                            <label class="form-label"
                                style="font-size: 0.82rem; font-weight: 700;">Kewarganegaraan</label>
                            <select name="kewarganegaraan" class="form-select" style="font-size: 0.85rem;">
                                <option value="WNI"
                                    {{ old('kewarganegaraan', $identitas->kewarganegaraan) === 'WNI' ? 'selected' : '' }}>
                                    Warga
                                    Negara Indonesia (WNI)</option>
                                <option value="WNA"
                                    {{ old('kewarganegaraan', $identitas->kewarganegaraan) === 'WNA' ? 'selected' : '' }}>
                                    Warga
                                    Negara Asing (WNA)</option>
                            </select>
                        </div>

                        <!-- Tempat Lahir -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" class="form-control"
                                value="{{ old('tempat_lahir', $identitas->tempat_lahir ?: $pd->tempat_lahir) }}"
                                style="font-size: 0.85rem;">
                        </div>

                        <!-- Tanggal Lahir -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" class="form-control"
                                value="{{ old('tanggal_lahir', $identitas->tanggal_lahir ? $identitas->tanggal_lahir->format('Y-m-d') : ($pd->tanggal_lahir ? \Carbon\Carbon::parse($pd->tanggal_lahir)->format('Y-m-d') : '')) }}"
                                style="font-size: 0.85rem;">
                        </div>

                        <!-- Agama & Kepercayaan -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Agama &amp;
                                Kepercayaan</label>
                            <select name="agama_id" class="form-select" required style="font-size: 0.85rem;">
                                @foreach ($ref['agama'] as $aId => $aName)
                                    <option value="{{ $aId }}"
                                        {{ old('agama_id', $identitas->agama_id ?: $pd->agama_id) == $aId ? 'selected' : '' }}>
                                        {{ $aName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Kebutuhan Khusus -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Berkebutuhan
                                Khusus</label>
                            <select name="kebutuhan_khusus_id" class="form-select" style="font-size: 0.85rem;">
                                @foreach ($ref['kebutuhan_khusus'] as $kId => $kName)
                                    <option value="{{ $kId }}"
                                        {{ old('kebutuhan_khusus_id', $identitas->kebutuhan_khusus_id) == $kId ? 'selected' : '' }}>
                                        {{ $kName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Anak ke-berapa -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Anak Ke-berapa (di
                                KK)</label>
                            <input type="number" name="anak_keberapa" class="form-control" min="1"
                                max="25" required
                                value="{{ old('anak_keberapa', $identitas->anak_keberapa ?: ($pd->anak_keberapa ?: 1)) }}"
                                style="font-size: 0.85rem;">
                        </div>

                    </div>
                </div>

                <!-- ========================================== -->
                <!-- SEKSI 2: ALAMAT & DOMISILI -->
                <!-- ========================================== -->
                <div class="card identitas-form-section" data-section="pribadi_alamat"
                    style="margin-bottom: 24px; padding: 22px 24px; border-radius: 14px;">
                    <div
                        style="font-weight: 800; font-size: 1.05rem; color: #0284c7; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-location-dot"></i> Bagian 1: Data Pribadi &amp; Domisili
                        </div>
                        <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">Sesuai Kartu
                            Keluarga
                            / Domisili Aktual</span>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px;">
                        <!-- Alamat Jalan -->
                        <div style="grid-column: 1 / -1;">
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Alamat Jalan / Kampung
                                /
                                Dusun <span class="text-danger">*</span></label>
                            <input type="text" name="alamat_jalan" class="form-control"
                                value="{{ old('alamat_jalan', $identitas->alamat_jalan ?: $pd->alamat_jalan) }}"
                                placeholder="Nama jalan, nomor rumah, blok/gang" required style="font-size: 0.85rem;">
                        </div>

                        <!-- RT -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">RT</label>
                            <input type="text" name="rt" maxlength="4" class="form-control" placeholder="001"
                                value="{{ old('rt', $identitas->rt) }}" style="font-size: 0.85rem;">
                        </div>

                        <!-- RW -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">RW</label>
                            <input type="text" name="rw" maxlength="4" class="form-control" placeholder="002"
                                value="{{ old('rw', $identitas->rw) }}" style="font-size: 0.85rem;">
                        </div>

                        <!-- Nama Dusun -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Nama Dusun /
                                Wilayah</label>
                            <input type="text" name="nama_dusun" class="form-control"
                                value="{{ old('nama_dusun', $identitas->nama_dusun) }}" style="font-size: 0.85rem;">
                        </div>

                        <!-- Desa / Kelurahan -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Desa /
                                Kelurahan</label>
                            <input type="text" name="desa_kelurahan" class="form-control"
                                value="{{ old('desa_kelurahan', $identitas->desa_kelurahan) }}"
                                style="font-size: 0.85rem;">
                        </div>

                        <!-- Kecamatan -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Kecamatan</label>
                            <input type="text" name="kecamatan" class="form-control"
                                value="{{ old('kecamatan', $identitas->kecamatan) }}" style="font-size: 0.85rem;">
                        </div>

                        <!-- Kabupaten / Kota -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Kabupaten /
                                Kota</label>
                            <input type="text" name="kabupaten_kota" class="form-control"
                                value="{{ old('kabupaten_kota', $identitas->kabupaten_kota) }}"
                                style="font-size: 0.85rem;">
                        </div>

                        <!-- Provinsi -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Provinsi</label>
                            <input type="text" name="provinsi" class="form-control"
                                value="{{ old('provinsi', $identitas->provinsi) }}" style="font-size: 0.85rem;">
                        </div>

                        <!-- Kode Pos -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Kode Pos</label>
                            <input type="text" name="kode_pos" maxlength="5" class="form-control"
                                placeholder="43266" value="{{ old('kode_pos', $identitas->kode_pos) }}"
                                style="font-size: 0.85rem; font-family: monospace;">
                        </div>

                        <!-- Tempat Tinggal -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Status Tempat
                                Tinggal</label>
                            <select name="tempat_tinggal_id" class="form-select" required style="font-size: 0.85rem;">
                                @foreach ($ref['tempat_tinggal'] as $tId => $tName)
                                    <option value="{{ $tId }}"
                                        {{ old('tempat_tinggal_id', $identitas->tempat_tinggal_id) == $tId ? 'selected' : '' }}>
                                        {{ $tName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Moda Transportasi -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Moda Transportasi ke
                                Sekolah</label>
                            <select name="transportasi_id" class="form-select" required style="font-size: 0.85rem;">
                                @foreach ($ref['transportasi'] as $trId => $trName)
                                    <option value="{{ $trId }}"
                                        {{ old('transportasi_id', $identitas->transportasi_id) == $trId ? 'selected' : '' }}>
                                        {{ $trName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Titik Lintang & Bujur (Geotagging) -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Titik Lintang
                                (Latitude)</label>
                            <input type="text" name="lintang" class="form-control" placeholder="-7.1234567"
                                value="{{ old('lintang', $identitas->lintang) }}"
                                style="font-size: 0.85rem; font-family: monospace;">
                        </div>

                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Titik Bujur
                                (Longitude)</label>
                            <input type="text" name="bujur" class="form-control" placeholder="107.1234567"
                                value="{{ old('bujur', $identitas->bujur) }}"
                                style="font-size: 0.85rem; font-family: monospace;">
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- SEKSI 3: DATA ORANG TUA (AYAH & IBU) -->
                <!-- ========================================== -->
                <div class="card identitas-form-section" data-section="orang_tua"
                    style="margin-bottom: 24px; padding: 22px 24px; border-radius: 14px;">
                    <div
                        style="font-weight: 800; font-size: 1.05rem; color: #16a34a; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-people-roof"></i> Bagian 2 &amp; 3: Data Ayah dan Ibu Kandung
                        </div>
                        <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">Ayah Kandung &amp;
                            Ibu
                            Kandung</span>
                    </div>

                    <!-- Sub-Kolom 1: Data Ayah Kandung -->
                    <div
                        style="margin-bottom: 24px; background: var(--bg-hover); padding: 18px; border-radius: 12px; border: 1px solid var(--border-color);">
                        <div
                            style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between;">
                            <span><i class="fas fa-person me-2 text-primary"></i> Data Ayah Kandung</span>
                            <div style="display: flex; gap: 14px; font-size: 0.82rem;">
                                <label style="cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                    <input type="radio" name="status_hidup_ayah" value="1"
                                        {{ old('status_hidup_ayah', $identitas->status_hidup_ayah) !== '0' ? 'checked' : '' }}>
                                    Masih Hidup
                                </label>
                                <label style="cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                    <input type="radio" name="status_hidup_ayah" value="0"
                                        {{ old('status_hidup_ayah', $identitas->status_hidup_ayah) === '0' ? 'checked' : '' }}>
                                    Sudah Meninggal
                                </label>
                            </div>
                        </div>

                        <div
                            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px;">
                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Nama Ayah
                                    Kandung</label>
                                <input type="text" name="nama_ayah" class="form-control form-control-sm"
                                    value="{{ old('nama_ayah', $identitas->nama_ayah ?: $pd->nama_ayah) }}"
                                    style="font-size: 0.85rem;">
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">NIK Ayah (16
                                    Digit)</label>
                                <input type="text" name="nik_ayah" maxlength="16"
                                    class="form-control form-control-sm"
                                    value="{{ old('nik_ayah', $identitas->nik_ayah) }}" placeholder="3203xxxxxxxxxxxx"
                                    style="font-size: 0.85rem; font-family: monospace;">
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Tahun Lahir
                                    Ayah</label>
                                <input type="number" name="tahun_lahir_ayah" min="1940" max="{{ date('Y') }}"
                                    class="form-control form-control-sm" placeholder="1975"
                                    value="{{ old('tahun_lahir_ayah', $identitas->tahun_lahir_ayah) }}"
                                    style="font-size: 0.85rem;">
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Pendidikan Terakhir
                                    Ayah</label>
                                <select name="pendidikan_ayah_id" class="form-select form-select-sm"
                                    style="font-size: 0.85rem;">
                                    <option value="">Pilih Jenjang</option>
                                    @foreach ($ref['jenjang_pendidikan'] as $jpId => $jpName)
                                        <option value="{{ $jpId }}"
                                            {{ old('pendidikan_ayah_id', $identitas->pendidikan_ayah_id) == $jpId ? 'selected' : '' }}>
                                            {{ $jpName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Pekerjaan Utama
                                    Ayah</label>
                                <select name="pekerjaan_ayah_id" class="form-select form-select-sm" required
                                    style="font-size: 0.85rem;">
                                    <option value="">Pilih Pekerjaan</option>
                                    @foreach ($ref['pekerjaan'] as $pkId => $pkName)
                                        <option value="{{ $pkId }}"
                                            {{ old('pekerjaan_ayah_id', $identitas->pekerjaan_ayah_id ?: $pd->pekerjaan_ayah_id) == $pkId ? 'selected' : '' }}>
                                            {{ $pkName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Penghasilan Bulanan
                                    Ayah</label>
                                <select name="penghasilan_ayah_id" class="form-select form-select-sm" required
                                    style="font-size: 0.85rem;">
                                    <option value="">Pilih Rentang</option>
                                    @foreach ($ref['penghasilan'] as $phId => $phName)
                                        <option value="{{ $phId }}"
                                            {{ old('penghasilan_ayah_id', $identitas->penghasilan_ayah_id) == $phId ? 'selected' : '' }}>
                                            {{ $phName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Berkebutuhan Khusus
                                    Ayah</label>
                                <select name="kebutuhan_khusus_ayah_id" class="form-select form-select-sm"
                                    style="font-size: 0.85rem;">
                                    @foreach ($ref['kebutuhan_khusus'] as $kbId => $kbName)
                                        <option value="{{ $kbId }}"
                                            {{ old('kebutuhan_khusus_ayah_id', $identitas->kebutuhan_khusus_ayah_id) == $kbId ? 'selected' : '' }}>
                                            {{ $kbName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Sub-Kolom 2: Data Ibu Kandung -->
                    <div
                        style="background: var(--bg-hover); padding: 18px; border-radius: 12px; border: 1px solid var(--border-color);">
                        <div
                            style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between;">
                            <span><i class="fas fa-person-dress me-2 text-primary"></i> Data Ibu Kandung</span>
                            <div style="display: flex; gap: 14px; font-size: 0.82rem;">
                                <label style="cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                    <input type="radio" name="status_hidup_ibu" value="1"
                                        {{ old('status_hidup_ibu', $identitas->status_hidup_ibu) !== '0' ? 'checked' : '' }}>
                                    Masih Hidup
                                </label>
                                <label style="cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                    <input type="radio" name="status_hidup_ibu" value="0"
                                        {{ old('status_hidup_ibu', $identitas->status_hidup_ibu) === '0' ? 'checked' : '' }}>
                                    Sudah Meninggal
                                </label>
                            </div>
                        </div>

                        <div
                            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px;">
                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Nama Ibu
                                    Kandung</label>
                                <input type="text" name="nama_ibu" class="form-control form-control-sm" required
                                    value="{{ old('nama_ibu', $identitas->nama_ibu ?: $pd->nama_ibu) }}"
                                    style="font-size: 0.85rem;">
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">NIK Ibu (16
                                    Digit)</label>
                                <input type="text" name="nik_ibu" maxlength="16" class="form-control form-control-sm"
                                    value="{{ old('nik_ibu', $identitas->nik_ibu) }}" placeholder="3203xxxxxxxxxxxx"
                                    style="font-size: 0.85rem; font-family: monospace;">
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Tahun Lahir
                                    Ibu</label>
                                <input type="number" name="tahun_lahir_ibu" min="1940" max="{{ date('Y') }}"
                                    class="form-control form-control-sm" placeholder="1978"
                                    value="{{ old('tahun_lahir_ibu', $identitas->tahun_lahir_ibu) }}"
                                    style="font-size: 0.85rem;">
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Pendidikan Terakhir
                                    Ibu</label>
                                <select name="pendidikan_ibu_id" class="form-select form-select-sm" required
                                    style="font-size: 0.85rem;">
                                    <option value="">Pilih Jenjang</option>
                                    @foreach ($ref['jenjang_pendidikan'] as $jpId => $jpName)
                                        <option value="{{ $jpId }}"
                                            {{ old('pendidikan_ibu_id', $identitas->pendidikan_ibu_id) == $jpId ? 'selected' : '' }}>
                                            {{ $jpName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Pekerjaan Utama
                                    Ibu</label>
                                <select name="pekerjaan_ibu_id" class="form-select form-select-sm" required
                                    style="font-size: 0.85rem;">
                                    <option value="">Pilih Pekerjaan</option>
                                    @foreach ($ref['pekerjaan'] as $pkId => $pkName)
                                        <option value="{{ $pkId }}"
                                            {{ old('pekerjaan_ibu_id', $identitas->pekerjaan_ibu_id ?: $pd->pekerjaan_ibu_id) == $pkId ? 'selected' : '' }}>
                                            {{ $pkName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Penghasilan Bulanan
                                    Ibu</label>
                                <select name="penghasilan_ibu_id" class="form-select form-select-sm" required
                                    style="font-size: 0.85rem;">
                                    <option value="">Pilih Rentang</option>
                                    @foreach ($ref['penghasilan'] as $phId => $phName)
                                        <option value="{{ $phId }}"
                                            {{ old('penghasilan_ibu_id', $identitas->penghasilan_ibu_id) == $phId ? 'selected' : '' }}>
                                            {{ $phName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Berkebutuhan Khusus
                                    Ibu</label>
                                <select name="kebutuhan_khusus_ibu_id" class="form-select form-select-sm"
                                    style="font-size: 0.85rem;">
                                    @foreach ($ref['kebutuhan_khusus'] as $kbId => $kbName)
                                        <option value="{{ $kbId }}"
                                            {{ old('kebutuhan_khusus_ibu_id', $identitas->kebutuhan_khusus_ibu_id) == $kbId ? 'selected' : '' }}>
                                            {{ $kbName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- SEKSI 4: DATA WALI MURID (OPSIONAL) -->
                <!-- ========================================== -->
                <div class="card identitas-form-section" data-section="orang_tua"
                    style="margin-bottom: 24px; padding: 22px 24px; border-radius: 14px;">
                    <div
                        style="font-weight: 800; font-size: 1.05rem; color: #d97706; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-handshake-angle"></i> Bagian 4: Data Wali
                        </div>
                        <label
                            style="font-size: 0.85rem; font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="mempunyai_wali" id="checkMempunyaiWali" value="1"
                                {{ old('mempunyai_wali', $identitas->mempunyai_wali) ? 'checked' : '' }}>
                            Mempunyai Wali (Tinggal bersama wali)
                        </label>
                    </div>

                    <div id="containerDataWali"
                        style="display: {{ old('mempunyai_wali', $identitas->mempunyai_wali) ? 'block' : 'none' }};">
                        <div
                            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px;">
                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Nama Lengkap
                                    Wali</label>
                                <input type="text" name="nama_wali" class="form-control form-control-sm"
                                    value="{{ old('nama_wali', $identitas->nama_wali ?: $pd->nama_wali) }}"
                                    style="font-size: 0.85rem;">
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">NIK Wali (16
                                    Digit)</label>
                                <input type="text" name="nik_wali" maxlength="16"
                                    class="form-control form-control-sm"
                                    value="{{ old('nik_wali', $identitas->nik_wali) }}" placeholder="3203xxxxxxxxxxxx"
                                    style="font-size: 0.85rem; font-family: monospace;">
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Tahun Lahir
                                    Wali</label>
                                <input type="number" name="tahun_lahir_wali" min="1930" max="{{ date('Y') }}"
                                    class="form-control form-control-sm" placeholder="1970"
                                    value="{{ old('tahun_lahir_wali', $identitas->tahun_lahir_wali) }}"
                                    style="font-size: 0.85rem;">
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Pendidikan Terakhir
                                    Wali</label>
                                <select name="pendidikan_wali_id" class="form-select form-select-sm"
                                    style="font-size: 0.85rem;">
                                    <option value="">Pilih Jenjang</option>
                                    @foreach ($ref['jenjang_pendidikan'] as $jpId => $jpName)
                                        <option value="{{ $jpId }}"
                                            {{ old('pendidikan_wali_id', $identitas->pendidikan_wali_id) == $jpId ? 'selected' : '' }}>
                                            {{ $jpName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Pekerjaan Utama
                                    Wali</label>
                                <select name="pekerjaan_wali_id" class="form-select form-select-sm"
                                    style="font-size: 0.85rem;">
                                    <option value="">Pilih Pekerjaan</option>
                                    @foreach ($ref['pekerjaan'] as $pkId => $pkName)
                                        <option value="{{ $pkId }}"
                                            {{ old('pekerjaan_wali_id', $identitas->pekerjaan_wali_id ?: $pd->pekerjaan_wali_id) == $pkId ? 'selected' : '' }}>
                                            {{ $pkName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Penghasilan Bulanan
                                    Wali</label>
                                <select name="penghasilan_wali_id" class="form-select form-select-sm"
                                    style="font-size: 0.85rem;">
                                    <option value="">Pilih Rentang</option>
                                    @foreach ($ref['penghasilan'] as $phId => $phName)
                                        <option value="{{ $phId }}"
                                            {{ old('penghasilan_wali_id', $identitas->penghasilan_wali_id) == $phId ? 'selected' : '' }}>
                                            {{ $phName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Berkebutuhan Khusus
                                    Wali</label>
                                <select name="kebutuhan_khusus_wali_id" class="form-select form-select-sm"
                                    style="font-size: 0.85rem;">
                                    @foreach ($ref['kebutuhan_khusus'] as $kbId => $kbName)
                                        <option value="{{ $kbId }}"
                                            {{ old('kebutuhan_khusus_wali_id', $identitas->kebutuhan_khusus_wali_id) == $kbId ? 'selected' : '' }}>
                                            {{ $kbName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- SEKSI 5: KONTAK DAN PENDAFTARAN MASUK -->
                <!-- ========================================== -->
                <div class="card identitas-form-section" data-section="registrasi_kesejahteraan"
                    style="margin-bottom: 24px; padding: 22px 24px; border-radius: 14px;">
                    <div
                        style="font-weight: 800; font-size: 1.05rem; color: #9333ea; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-address-book"></i> Bagian 5 &amp; 8: Kontak dan Pendaftaran Masuk
                            Masuk
                        </div>
                    </div>

                    <div
                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin-bottom: 20px;">
                        <!-- Kontak -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">No. HP / WhatsApp
                                Siswa
                                <span class="text-danger">*</span></label>
                            <input type="text" name="nomor_telepon_seluler" class="form-control"
                                placeholder="08xxxxxxxxxx"
                                value="{{ old('nomor_telepon_seluler', $identitas->nomor_telepon_seluler ?: $pd->nomor_telepon_seluler) }}"
                                required style="font-size: 0.85rem;">
                        </div>

                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">No. Telepon Rumah
                                (Kabel)</label>
                            <input type="text" name="nomor_telepon_rumah" class="form-control"
                                placeholder="0263xxxxxx"
                                value="{{ old('nomor_telepon_rumah', $identitas->nomor_telepon_rumah ?: $pd->nomor_telepon_rumah) }}"
                                style="font-size: 0.85rem;">
                        </div>

                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Email Siswa
                                Aktif</label>
                            <input type="email" name="email" class="form-control" placeholder="siswa@belajar.id"
                                value="{{ old('email', $identitas->email ?: $pd->email) }}" style="font-size: 0.85rem;">
                        </div>

                        <!-- Registrasi Masuk -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Jenis
                                Pendaftaran</label>
                            <select name="jenis_pendaftaran_id" class="form-select" required style="font-size: 0.85rem;">
                                @foreach ($ref['jenis_pendaftaran'] as $jpId => $jpLabel)
                                    <option value="{{ $jpId }}"
                                        {{ old('jenis_pendaftaran_id', $identitas->jenis_pendaftaran_id ?: $pd->jenis_pendaftaran_id) == $jpId ? 'selected' : '' }}>
                                        {{ $jpLabel }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Tanggal Masuk
                                Sekolah</label>
                            <input type="date" name="tanggal_masuk_sekolah" class="form-control"
                                value="{{ old('tanggal_masuk_sekolah', $identitas->tanggal_masuk_sekolah ? $identitas->tanggal_masuk_sekolah->format('Y-m-d') : '') }}"
                                style="font-size: 0.85rem;">
                        </div>

                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Program / Kompetensi
                                Keahlian</label>
                            <input type="text" name="program_keahlian" class="form-control"
                                value="{{ old('program_keahlian', $identitas->program_keahlian) }}"
                                placeholder="Contoh: Teknik Komputer dan Jaringan" style="font-size: 0.85rem;">
                        </div>

                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Sekolah Asal (SMP /
                                MTs)</label>
                            <input type="text" name="sekolah_asal" class="form-control"
                                value="{{ old('sekolah_asal', $identitas->sekolah_asal ?: $pd->sekolah_asal) }}"
                                style="font-size: 0.85rem;">
                        </div>
                    </div>

                    <div
                        style="display: flex; gap: 24px; flex-wrap: wrap; padding-top: 10px; border-top: 1px dashed var(--border-color);">
                        <div style="font-size: 0.85rem;">
                            <strong>PAUD Formal (TK / RA)</strong>
                            <label style="margin-left: 12px; cursor: pointer;"><input type="radio"
                                    name="pernah_paud_formal" value="1" required
                                    {{ old('pernah_paud_formal', $identitas->pernah_paud_formal) ? 'checked' : '' }}>
                                Ya</label>
                            <label style="margin-left: 8px; cursor: pointer;"><input type="radio"
                                    name="pernah_paud_formal" value="0" required
                                    {{ old('pernah_paud_formal', $identitas->pernah_paud_formal) == 0 ? 'checked' : '' }}>
                                Tidak</label>
                        </div>

                        <div style="font-size: 0.85rem;">
                            <strong>PAUD Non Formal (KB / TPA / SPS)</strong>
                            <label style="margin-left: 12px; cursor: pointer;"><input type="radio"
                                    name="pernah_paud_non_formal" value="1" required
                                    {{ old('pernah_paud_non_formal', $identitas->pernah_paud_non_formal) ? 'checked' : '' }}>
                                Ya</label>
                            <label style="margin-left: 8px; cursor: pointer;"><input type="radio"
                                    name="pernah_paud_non_formal" value="0" required
                                    {{ old('pernah_paud_non_formal', $identitas->pernah_paud_non_formal) == 0 ? 'checked' : '' }}>
                                Tidak</label>
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- SEKSI 6: PERLINDUNGAN SOSIAL & KESEJAHTERAAN -->
                <!-- ========================================== -->
                <div class="card identitas-form-section" data-section="registrasi_kesejahteraan"
                    style="margin-bottom: 24px; padding: 22px 24px; border-radius: 14px;">
                    <div
                        style="font-weight: 800; font-size: 1.05rem; color: #2563eb; margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-hand-holding-heart"></i> Bagian 7: Kesejahteraan (KKS / KIP / KIS /
                            KIS / PIP)
                        </div>
                        <button type="button" class="btn btn-outline" id="btnAddSosial"
                            style="font-size: 0.78rem; padding: 5px 12px; border-radius: 6px;">
                            <i class="fas fa-plus me-1"></i> Tambah Bantuan
                        </button>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="table mb-0" style="width: 100%; border-collapse: collapse; font-size: 0.84rem;">
                            <thead>
                                <tr style="background: var(--bg-hover); border-bottom: 1px solid var(--border-color);">
                                    <th
                                        style="padding: 10px 8px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                        Jenis Bantuan</th>
                                    <th
                                        style="padding: 10px 8px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                        Nomor Kartu</th>
                                    <th
                                        style="padding: 10px 8px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                        Nama Tertera di Kartu</th>
                                    <th
                                        style="padding: 10px 8px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 95px;">
                                        Tahun Mulai</th>
                                    <th
                                        style="padding: 10px 8px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 95px;">
                                        Tahun Selesai</th>
                                    <th
                                        style="padding: 10px 8px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 45px;">
                                        Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tbodySosial">
                                @php
                                    $sosialRows = old('perlindungan_sosial', $identitas->perlindungan_sosial ?? []);
                                @endphp
                                @if (!empty($sosialRows) && is_array($sosialRows))
                                    @foreach ($sosialRows as $idx => $s)
                                        <tr class="row-sosial" style="border-bottom: 1px solid var(--border-color);">
                                            <td style="padding: 10px 8px;">
                                                <select name="perlindungan_sosial[{{ $idx }}][jenis]"
                                                    class="form-select form-select-sm" style="font-size: 0.82rem;">
                                                    @foreach ($ref['kesejahteraan'] as $kj)
                                                        <option value="{{ $kj }}"
                                                            {{ ($s['jenis'] ?? '') === $kj ? 'selected' : '' }}>
                                                            {{ $kj }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td style="padding: 10px 8px;">
                                                <input type="text"
                                                    name="perlindungan_sosial[{{ $idx }}][no_kartu]"
                                                    class="form-control form-control-sm" placeholder="Nomor Fisik Kartu"
                                                    value="{{ $s['no_kartu'] ?? '' }}" style="font-size: 0.82rem;"
                                                    required>
                                            </td>
                                            <td style="padding: 10px 8px;">
                                                <input type="text"
                                                    name="perlindungan_sosial[{{ $idx }}][nama_di_kartu]"
                                                    class="form-control form-control-sm"
                                                    placeholder="Nama Tertera di Kartu"
                                                    value="{{ $s['nama_di_kartu'] ?? '' }}" style="font-size: 0.82rem;">
                                            </td>
                                            <td style="padding: 10px 8px; width: 95px;">
                                                <input type="number"
                                                    name="perlindungan_sosial[{{ $idx }}][tahun_mulai]"
                                                    class="form-control form-control-sm" placeholder="Mulai"
                                                    value="{{ $s['tahun_mulai'] ?? '' }}" style="font-size: 0.82rem;">
                                            </td>
                                            <td style="padding: 10px 8px; width: 95px;">
                                                <input type="number"
                                                    name="perlindungan_sosial[{{ $idx }}][tahun_selesai]"
                                                    class="form-control form-control-sm" placeholder="Selesai"
                                                    value="{{ $s['tahun_selesai'] ?? '' }}" style="font-size: 0.82rem;">
                                            </td>
                                            <td style="padding: 10px 8px; text-align: center; width: 45px;">
                                                <button type="button" class="btn-icon text-danger btn-remove-row"
                                                    title="Hapus Baris">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                                <tr id="emptySosial"
                                    style="{{ !empty($sosialRows) && count($sosialRows) > 0 ? 'display: none;' : '' }}">
                                    <td colspan="6"
                                        style="padding: 24px; text-align: center; color: var(--text-muted); font-size: 0.82rem;">
                                        Belum ada data kartu bantuan yang ditambahkan. Klik tombol <strong>+ Tambah
                                            Bantuan</strong> jika menerima KIP, KKS, KIS, atau bansos lainnya.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- SEKSI 7: MINAT, BAKAT & RIWAYAT PRESTASI -->
                <!-- ========================================== -->
                <div class="card identitas-form-section" data-section="prestasi_minat"
                    style="margin-bottom: 24px; padding: 22px 24px; border-radius: 14px;">
                    <div
                        style="font-weight: 800; font-size: 1.05rem; color: #ea580c; margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-trophy"></i> Bagian 6 &amp; 9: Prestasi serta Minat &amp; Bakat
                        </div>
                        <button type="button" class="btn btn-outline" id="btnAddPrestasi"
                            style="font-size: 0.78rem; padding: 5px 12px; border-radius: 6px;">
                            <i class="fas fa-plus me-1"></i> Tambah Prestasi
                        </button>
                    </div>

                    <div
                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 20px;">
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Hobi / Kegemaran
                                Siswa</label>
                            <select name="hobi_id" class="form-select" style="font-size: 0.85rem;">
                                @foreach ($ref['hobi'] as $hbId => $hbName)
                                    <option value="{{ $hbId }}"
                                        {{ old('hobi_id', $identitas->hobi_id) == $hbId ? 'selected' : '' }}>
                                        {{ $hbName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Cita-cita / Impian
                                Profesi</label>
                            <select name="cita_cita_id" class="form-select" style="font-size: 0.85rem;">
                                @foreach ($ref['cita_cita'] as $ctId => $ctName)
                                    <option value="{{ $ctId }}"
                                        {{ old('cita_cita_id', $identitas->cita_cita_id) == $ctId ? 'selected' : '' }}>
                                        {{ $ctName }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="table mb-0" style="width: 100%; border-collapse: collapse; font-size: 0.84rem;">
                            <thead>
                                <tr style="background: var(--bg-hover); border-bottom: 1px solid var(--border-color);">
                                    <th
                                        style="padding: 10px 8px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                        Bidang</th>
                                    <th
                                        style="padding: 10px 8px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                        Tingkat</th>
                                    <th
                                        style="padding: 10px 8px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                        Nama Kejuaraan / Lomba</th>
                                    <th
                                        style="padding: 10px 8px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 90px;">
                                        Tahun</th>
                                    <th
                                        style="padding: 10px 8px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                        Penyelenggara</th>
                                    <th
                                        style="padding: 10px 8px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 100px;">
                                        Peringkat</th>
                                    <th
                                        style="padding: 10px 8px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 45px;">
                                        Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyPrestasi">
                                @php
                                    $prestasiRows = old('riwayat_prestasi', $identitas->riwayat_prestasi ?? []);
                                @endphp
                                @if (!empty($prestasiRows) && is_array($prestasiRows))
                                    @foreach ($prestasiRows as $idx => $p)
                                        <tr class="row-prestasi" style="border-bottom: 1px solid var(--border-color);">
                                            <td style="padding: 10px 8px;">
                                                <select name="riwayat_prestasi[{{ $idx }}][jenis]"
                                                    class="form-select form-select-sm" style="font-size: 0.82rem;">
                                                    @foreach ($ref['jenis_prestasi'] as $jp)
                                                        <option value="{{ $jp }}"
                                                            {{ ($p['jenis'] ?? '') === $jp ? 'selected' : '' }}>
                                                            {{ $jp }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td style="padding: 10px 8px;">
                                                <select name="riwayat_prestasi[{{ $idx }}][tingkat]"
                                                    class="form-select form-select-sm" style="font-size: 0.82rem;">
                                                    @foreach ($ref['tingkat_prestasi'] as $tp)
                                                        <option value="{{ $tp }}"
                                                            {{ ($p['tingkat'] ?? '') === $tp ? 'selected' : '' }}>
                                                            {{ $tp }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td style="padding: 10px 8px;">
                                                <input type="text" name="riwayat_prestasi[{{ $idx }}][nama]"
                                                    class="form-control form-control-sm" placeholder="Nama Lomba"
                                                    value="{{ $p['nama'] ?? '' }}" style="font-size: 0.82rem;" required>
                                            </td>
                                            <td style="padding: 10px 8px; width: 90px;">
                                                <input type="number"
                                                    name="riwayat_prestasi[{{ $idx }}][tahun]"
                                                    class="form-control form-control-sm" placeholder="Tahun"
                                                    value="{{ $p['tahun'] ?? '' }}" style="font-size: 0.82rem;">
                                            </td>
                                            <td style="padding: 10px 8px;">
                                                <input type="text"
                                                    name="riwayat_prestasi[{{ $idx }}][penyelenggara]"
                                                    class="form-control form-control-sm"
                                                    placeholder="Instansi Penyelenggara"
                                                    value="{{ $p['penyelenggara'] ?? '' }}" style="font-size: 0.82rem;">
                                            </td>
                                            <td style="padding: 10px 8px; width: 100px;">
                                                <input type="text"
                                                    name="riwayat_prestasi[{{ $idx }}][peringkat]"
                                                    class="form-control form-control-sm" placeholder="Juara 1 / dll"
                                                    value="{{ $p['peringkat'] ?? '' }}" style="font-size: 0.82rem;">
                                            </td>
                                            <td style="padding: 10px 8px; text-align: center; width: 45px;">
                                                <button type="button" class="btn-icon text-danger btn-remove-row"
                                                    title="Hapus Baris">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                                <tr id="emptyPrestasi"
                                    style="{{ !empty($prestasiRows) && count($prestasiRows) > 0 ? 'display: none;' : '' }}">
                                    <td colspan="7"
                                        style="padding: 24px; text-align: center; color: var(--text-muted); font-size: 0.82rem;">
                                        Belum ada catatan riwayat prestasi. Klik tombol <strong>+ Tambah Prestasi</strong>
                                        jika
                                        pernah menjuarai lomba atau kejuaraan.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- SEKSI 10: DATA PERIODIK PESERTA DIDIK -->
                <!-- ========================================== -->
                <div class="card identitas-form-section" data-section="periodik"
                    style="margin-bottom: 24px; padding: 22px 24px; border-radius: 14px;">
                    <div
                        style="font-weight: 800; font-size: 1.05rem; color: #0f766e; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-ruler-combined"></i> Bagian 10: Data Periodik Peserta Didik
                        </div>
                        <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">Kondisi fisik dan
                            jarak ke sekolah</span>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Tinggi Badan (cm)
                                <span class="text-danger">*</span></label>
                            <input type="number" name="tinggi_badan" class="form-control" min="1"
                                max="250" required
                                value="{{ old('tinggi_badan', $identitas->tinggi_badan ?: $pd->tinggi_badan) }}">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Berat Badan (kg) <span
                                    class="text-danger">*</span></label>
                            <input type="number" name="berat_badan" class="form-control" min="1" max="300"
                                required value="{{ old('berat_badan', $identitas->berat_badan ?: $pd->berat_badan) }}">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Lingkar Kepala
                                (cm)</label>
                            <input type="number" name="lingkar_kepala" class="form-control" min="1"
                                max="150" value="{{ old('lingkar_kepala', $identitas->lingkar_kepala) }}">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Jarak Rumah ke Sekolah
                                <span class="text-danger">*</span></label>
                            <select name="jarak_rumah_sekolah" class="form-select" required>
                                <option value="kurang_dari_1_km"
                                    {{ old('jarak_rumah_sekolah', $identitas->jarak_rumah_sekolah) === 'kurang_dari_1_km' ? 'selected' : '' }}>
                                    Kurang dari 1 km</option>
                                <option value="lebih_dari_1_km"
                                    {{ old('jarak_rumah_sekolah', $identitas->jarak_rumah_sekolah) === 'lebih_dari_1_km' ? 'selected' : '' }}>
                                    Lebih dari 1 km</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Jarak Rumah
                                (km)</label>
                            <input type="number" name="jarak_rumah_sekolah_km" class="form-control" min="0"
                                max="999.99" step="0.01"
                                value="{{ old('jarak_rumah_sekolah_km', $identitas->jarak_rumah_sekolah_km) }}">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Waktu Tempuh
                                (Jam)</label>
                            <input type="number" name="waktu_tempuh_jam" class="form-control" min="0"
                                max="99" value="{{ old('waktu_tempuh_jam', $identitas->waktu_tempuh_jam) }}">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Waktu Tempuh
                                (Menit)</label>
                            <input type="number" name="waktu_tempuh_menit" class="form-control" min="0"
                                max="59" value="{{ old('waktu_tempuh_menit', $identitas->waktu_tempuh_menit) }}">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Jumlah Saudara Kandung
                                <span class="text-danger">*</span></label>
                            <input type="number" name="jumlah_saudara_kandung" class="form-control" min="0"
                                max="99" required
                                value="{{ old('jumlah_saudara_kandung', $identitas->jumlah_saudara_kandung) }}">
                        </div>
                    </div>
                </div>
            </fieldset>

            <!-- ========================================== -->
            <!-- CATATAN USULAN & SUBMIT BUTTON -->
            <!-- ========================================== -->
            <div class="card"
                style="margin-bottom: 24px; padding: 22px 24px; border-radius: 14px; background: var(--card-bg); border: 1.5px solid var(--border-color);">
                <div style="margin-bottom: 16px;">
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 700; color: var(--text-color);">
                        <i class="fas fa-message me-1 text-primary"></i> Catatan Tambahan atau Alasan Perubahan (Opsional)
                    </label>
                    <textarea name="catatan_siswa" class="form-control" rows="2" {{ $isFormLocked ? 'disabled' : '' }}
                        placeholder="Tuliskan keterangan singkat jika ada data yang diperbaiki..." style="font-size: 0.85rem;">{{ old('catatan_siswa', $identitas->catatan_siswa) }}</textarea>
                </div>

                <div
                    style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                    <div style="font-size: 0.8rem; color: var(--text-muted);">
                        @if ($isFormLocked)
                            <i class="fas fa-lock text-warning me-1"></i> Formulir dalam kondisi terkunci. Klik tombol
                            konfirmasi untuk membuka pengajuan usulan.
                        @else
                            <i class="fas fa-lock-open text-success me-1"></i> Formulir terbuka. Perubahan data akan
                            diteruskan ke Tim Kesiswaan untuk diverifikasi.
                        @endif
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        @if ($isFormLocked)
                            <button type="button" class="btn btn-outline btn-konfirmasi-dialog"
                                style="padding: 10px 20px; font-weight: 700; font-size: 0.88rem; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px; color: #f59e0b; border-color: rgba(245,158,11,0.4);">
                                <i class="fas fa-lock"></i> Formulir Terkunci (Buka / Konfirmasi)
                            </button>
                        @else
                            <button type="submit" class="btn btn-primary"
                                style="padding: 10px 22px; font-weight: 700; font-size: 0.88rem; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px;">
                                <i class="fas fa-floppy-disk"></i> Simpan &amp; Ajukan Usulan Perubahan Data
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </form>

        <!-- 5. Riwayat Usulan Perubahan Siswa -->
        @if ($usulanList->isNotEmpty())
            <div class="card table-responsive-stack" style="margin-bottom: 24px; padding: 0;">
                <div
                    style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                    <h3
                        style="font-size: 0.98rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-clock-rotate-left text-primary"></i> Riwayat Pengajuan Usulan Revisi Data
                    </h3>
                    <span class="badge badge-outline" style="font-size: 0.72rem;">{{ $usulanList->count() }}
                        Pengajuan</span>
                </div>

                <div style="overflow-x: auto;">
                    <table class="table table-pd mb-0"
                        style="width: 100%; border-collapse: collapse; font-size: 0.82rem;">
                        <thead>
                            <tr style="background: var(--bg-hover); border-bottom: 1px solid var(--border-color);">
                                <th
                                    style="padding: 10px 14px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Waktu</th>
                                <th
                                    style="padding: 10px 14px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Kolom Data</th>
                                <th
                                    style="padding: 10px 14px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Nilai Lama</th>
                                <th
                                    style="padding: 10px 14px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Usulan Nilai Baru</th>
                                <th
                                    style="padding: 10px 14px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Status Review Kesiswaan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($usulanList as $u)
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 10px 14px;">
                                        {{ $u->created_at ? $u->created_at->format('d/m/Y H:i') : '-' }}</td>
                                    <td style="padding: 10px 14px;"><strong
                                            style="text-transform: uppercase; font-family: monospace;">{{ str_replace('_', ' ', $u->kolom_perubahan) }}</strong>
                                    </td>
                                    <td style="padding: 10px 14px; color: var(--text-muted);">
                                        {{ $u->nilai_lama ?: '-' }}
                                    </td>
                                    <td style="padding: 10px 14px; font-weight: 700; color: var(--primary);">
                                        {{ $u->nilai_baru }}</td>
                                    <td style="padding: 10px 14px;">
                                        @if ($u->status === 'disetujui')
                                            <span class="badge badge-success"
                                                style="font-size: 0.7rem; padding: 2px 7px;"><i
                                                    class="fas fa-check me-1"></i>DISETUJUI</span>
                                        @elseif ($u->status === 'ditolak')
                                            <span class="badge badge-danger"
                                                style="font-size: 0.7rem; padding: 2px 7px;"><i
                                                    class="fas fa-times me-1"></i>DITOLAK</span>
                                        @else
                                            <span class="badge badge-warning"
                                                style="font-size: 0.7rem; padding: 2px 7px;"><i
                                                    class="fas fa-clock me-1"></i>MENUNGGU REVIEW</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @else
        <div style="padding: 40px 16px; text-align: center; color: var(--text-muted);">
            <i class="fas fa-user-xmark mb-2" style="font-size: 2rem; opacity: 0.4; display: block;"></i>
            <div>Data peserta didik tidak ditemukan atau Anda belum login sebagai peserta didik / wali murid.</div>
        </div>
    @endif

    <!-- MODAL KONFIRMASI KEBENARAN DATA IDENTITAS (THEME MATCHED & ICON DRIVEN) -->
    <div id="modalKonfirmasiData" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.72); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
        <div class="card"
            style="max-width: 440px; width: 92%; border-radius: 16px; padding: 22px; margin: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.4); border: 1.5px solid var(--border-color); background: var(--card-bg);">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div
                        style="width: 36px; height: 36px; border-radius: 10px; background: rgba(99,102,241,0.14); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.15rem;">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 1rem; font-weight: 800; color: var(--text-color); margin: 0;">Konfirmasi
                            Identitas</h3>
                        <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">Pilih status kebenaran
                            data saat ini</div>
                    </div>
                </div>
                <button type="button" class="close-modal" data-target="#modalKonfirmasiData"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.15rem; padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Pilihan 2 Tombol Tile Berukuran Besar dengan Icon Utama -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <!-- Opsi 1: Data Sesuai -->
                <button type="button" class="btn btn-tile-konfirmasi" data-status="sesuai"
                    style="background: rgba(16, 185, 129, 0.08); border: 2px solid rgba(16, 185, 129, 0.35); border-radius: 12px; padding: 18px 12px; text-align: center; cursor: pointer; transition: all 0.2s ease; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div
                        style="width: 46px; height: 46px; border-radius: 50%; background: rgba(16, 185, 129, 0.15); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                        <i class="fas fa-circle-check"></i>
                    </div>
                    <div style="font-size: 0.88rem; font-weight: 800; color: #10b981;">Data Sesuai</div>
                    <div style="font-size: 0.7rem; color: var(--text-muted); line-height: 1.3;">Data benar &amp; terkunci
                    </div>
                </button>

                <!-- Opsi 2: Belum Sesuai -->
                <button type="button" class="btn btn-tile-konfirmasi" data-status="perlu_perbaikan"
                    style="background: rgba(245, 158, 11, 0.08); border: 2px solid rgba(245, 158, 11, 0.35); border-radius: 12px; padding: 18px 12px; text-align: center; cursor: pointer; transition: all 0.2s ease; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div
                        style="width: 46px; height: 46px; border-radius: 50%; background: rgba(245, 158, 11, 0.15); color: #f59e0b; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                        <i class="fas fa-pen-to-square"></i>
                    </div>
                    <div style="font-size: 0.88rem; font-weight: 800; color: #f59e0b;">Perlu Perbaikan</div>
                    <div style="font-size: 0.7rem; color: var(--text-muted); line-height: 1.3;">Buka usulan perubahan
                    </div>
                </button>
            </div>

            <!-- Input Catatan Ringkas -->
            <div style="margin-bottom: 16px;">
                <label
                    style="font-size: 0.76rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 4px;">
                    <i class="fas fa-message me-1"></i> Catatan (Opsional):
                </label>
                <input type="text" id="inputCatatanKonfirmasiModal" class="form-control form-control-sm"
                    placeholder="Tuliskan keterangan jika ada..."
                    style="font-size: 0.82rem; height: 36px; border-radius: 8px; width: 100%;">
            </div>

            <div style="display: flex; justify-content: flex-end;">
                <button type="button" class="btn btn-outline close-modal" data-target="#modalKonfirmasiData"
                    style="padding: 6px 16px; font-size: 0.82rem; border-radius: 8px;">
                    <i class="fas fa-times me-1"></i> Batal
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Pratinjau Kartu Pelajar Digital (Layar Penuh, Bisa Digeser) -->
    @include('kartu-pelajar.modal-fullscreen')
@endsection

@push('styles')
    <link rel="stylesheet"
        href="{{ asset('css/kartu-pelajar.css') }}?v={{ file_exists(public_path('css/kartu-pelajar.css')) ? filemtime(public_path('css/kartu-pelajar.css')) : '1' }}">
@endpush

@push('scripts')
    <script
        src="{{ asset('js/identitas-peserta-didik.js') }}?v={{ file_exists(public_path('js/identitas-peserta-didik.js')) ? filemtime(public_path('js/identitas-peserta-didik.js')) : time() }}">
    </script>
@endpush
