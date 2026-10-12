@extends('layouts.app')

@section('title', 'Instalasi Sistem — SAE (Sistem Aplikasi Edukasi)')

@section('content')
    <div class="container"
        style="min-height: 85vh; display: flex; align-items: center; justify-content: center; padding: 40px 16px;">
        <div
            style="width: 100%; max-width: 480px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 20px; padding: 36px 30px; box-shadow: var(--card-shadow); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); position: relative; overflow: hidden;">
            <div
                style="position: absolute; top: -50px; right: -50px; width: 140px; height: 140px; background: radial-gradient(circle, rgba(99,102,241,0.25) 0%, transparent 70%); border-radius: 50%; pointer-events: none;">
            </div>

            <div style="text-align: center; margin-bottom: 24px;">
                @php
                    $installLogoDark = asset('img/logo-dark.png') . '?v=' . (@filemtime(public_path('img/logo-dark.png')) ?: '1');
                    $installLogoLight = asset('img/logo-light.png') . '?v=' . (@filemtime(public_path('img/logo-light.png')) ?: '1');
                @endphp
                <img id="installLogo" src="{{ $installLogoDark }}" data-dark="{{ $installLogoDark }}"
                    data-light="{{ $installLogoLight }}" alt="SAE Logo"
                    style="height: 48px; max-width: 180px; width: auto; object-fit: contain; margin-bottom: 12px;">
                <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--text-color); margin-bottom: 6px;">Installer
                    Sistem SAE</h2>
                <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0;">Konfigurasi database &amp; inisialisasi
                    sistem</p>
            </div>

            {{-- Kartu Deteksi Jika Database & .env Sudah Terisi Sebelumnya --}}
            @if (!empty($status['has_tables']) && $status['has_tables'])
                <div
                    style="background: rgba(16,185,129,0.12); border: 1px solid #10b981; border-radius: 14px; padding: 16px; margin-bottom: 22px;">
                    <div
                        style="font-weight: 700; color: #10b981; margin-bottom: 6px; display: flex; align-items: center; gap: 8px; font-size: 0.92rem;">
                        <i class="fas fa-circle-check"></i> Database Terisi &amp; Terdeteksi!
                    </div>
                    <p style="font-size: 0.8rem; color: var(--text-color); margin-bottom: 12px; line-height: 1.5;">
                        Database <code>{{ $status['env_config']['database'] ?: 'MySQL' }}</code> sudah berisi
                        <b>{{ $status['table_count'] }} tabel</b> dan konfigurasi <code>.env</code> sudah ada. Anda dapat
                        langsung menyelesaikan langkah instalasi dan menuju halaman login.
                    </p>
                    <a href="{{ route('login') }}" class="btn btn-primary"
                        style="width: 100%; padding: 11px; font-weight: 700; font-size: 0.88rem; justify-content: center; text-decoration: none; display: flex; align-items: center; gap: 8px; background: #10b981; border-color: #10b981; box-shadow: 0 4px 14px rgba(16,185,129,0.35);">
                        <i class="fas fa-arrow-right-to-bracket"></i> Selesaikan &amp; Buka Halaman Login
                    </a>
                </div>
            @endif

            {{-- Peringatan Jika Koneksi Database dari .env Gagal --}}
            @if (!empty($status['error']))
                <div
                    style="background: rgba(239,68,68,0.1); border: 1px solid #ef4444; color: #ef4444; padding: 12px 14px; border-radius: 12px; font-size: 0.8rem; margin-bottom: 20px; line-height: 1.5;">
                    <div style="font-weight: 700; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-triangle-exclamation"></i> Peringatan Koneksi Database
                    </div>
                    {{ $status['error'] }}
                </div>
            @endif

            @if (session('error'))
                <div
                    style="background: rgba(239,68,68,0.1); border: 1px solid #ef4444; color: #ef4444; padding: 10px 14px; border-radius: 10px; font-size: 0.82rem; margin-bottom: 20px;">
                    <i class="fas fa-triangle-exclamation"></i> {{ session('error') }}
                </div>
            @endif

            @if (session('success'))
                <div
                    style="background: rgba(16,185,129,0.12); border: 1px solid #10b981; color: #10b981; padding: 10px 14px; border-radius: 10px; font-size: 0.82rem; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-circle-check"></i> {{ session('success') }}
                </div>
            @endif

            <form id="installForm" action="{{ route('install.process') }}" method="POST">
                @csrf
                <div style="margin-bottom: 18px;">
                    <label
                        style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">
                        <i class="fas fa-database text-primary"></i> Nama Database
                    </label>
                    <div class="input-group"
                        style="background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 4px; display: flex; align-items: center;">
                        <input type="text" name="db_name" required
                            value="{{ old('db_name', $status['env_config']['database'] ?? 'db_sae') }}"
                            placeholder="contoh: db_sae"
                            style="flex: 1; border: none; background: transparent; padding: 10px 14px; color: var(--text-color); font-size: 0.9rem; outline: none;">
                    </div>
                </div>

                <div style="margin-bottom: 18px;">
                    <label
                        style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">
                        <i class="fas fa-user text-primary"></i> Username Database
                    </label>
                    <div class="input-group"
                        style="background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 4px; display: flex; align-items: center;">
                        <input type="text" name="db_user" required
                            value="{{ old('db_user', $status['env_config']['username'] ?? 'root') }}"
                            placeholder="contoh: root"
                            style="flex: 1; border: none; background: transparent; padding: 10px 14px; color: var(--text-color); font-size: 0.9rem; outline: none;">
                    </div>
                </div>

                <div style="margin-bottom: 18px;">
                    <label
                        style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">
                        <i class="fas fa-lock text-primary"></i> Password Database
                    </label>
                    <div class="input-group"
                        style="background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 4px; display: flex; align-items: center;">
                        <input type="password" name="db_pass" placeholder="Kosongkan jika tanpa password"
                            style="flex: 1; border: none; background: transparent; padding: 10px 14px; color: var(--text-color); font-size: 0.9rem; outline: none;">
                    </div>
                </div>

                <!-- Pengaturan Lanjutan (Host & Port) -->
                <details style="margin-bottom: 20px; font-size: 0.82rem; color: var(--text-muted);">
                    <summary
                        style="cursor: pointer; font-weight: 600; color: var(--primary); margin-bottom: 10px; user-select: none;">
                        <i class="fas fa-sliders me-1"></i> Pengaturan Host &amp; Port Lanjutan
                    </summary>
                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 10px; margin-top: 8px;">
                        <div>
                            <label style="display: block; font-size: 0.75rem; margin-bottom: 4px;">Host Database</label>
                            <input type="text" name="db_host"
                                value="{{ old('db_host', $status['env_config']['host'] ?? '127.0.0.1') }}"
                                style="width: 100%; border: 1px solid var(--border-color); background: var(--input-bg); border-radius: 8px; padding: 8px 10px; color: var(--text-color); font-size: 0.85rem; outline: none;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.75rem; margin-bottom: 4px;">Port</label>
                            <input type="text" name="db_port"
                                value="{{ old('db_port', $status['env_config']['port'] ?? '3306') }}"
                                style="width: 100%; border: 1px solid var(--border-color); background: var(--input-bg); border-radius: 8px; padding: 8px 10px; color: var(--text-color); font-size: 0.85rem; outline: none;">
                        </div>
                    </div>
                </details>

                <div
                    style="background: rgba(99,102,241,0.08); border: 1px solid rgba(99,102,241,0.25); border-radius: 12px; padding: 14px; margin-bottom: 22px; font-size: 0.8rem; color: var(--text-muted); line-height: 1.6;">
                    <div style="font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                        <i class="fas fa-info-circle text-primary me-1"></i> Informasi Akun Bawaan:
                    </div>
                    <div>• <b>Admin:</b> admin@sae.id / <code style="color: var(--primary);">Admin543!</code></div>
                    <div>• <b>Guru:</b> gtk@sae.id / <code style="color: var(--primary);">Geteka543!</code></div>
                    <div>• <b>Peserta Didik:</b> pesertadidik@sae.id / <code
                            style="color: var(--primary);">PesertaDidik543!</code></div>
                </div>

                <button type="submit" id="btnSubmitInstall" class="btn btn-primary"
                    style="width: 100%; padding: 13px; font-weight: 700; font-size: 0.95rem; justify-content: center; box-shadow: 0 4px 16px rgba(99,102,241,0.35);">
                    <i class="fas fa-rocket me-2"></i>
                    {{ !empty($status['has_tables']) && $status['has_tables'] ? 'Perbarui & Terapkan Konfigurasi' : 'Mulai Instalasi Sistem' }}
                </button>

                {{-- Status Progres Interaktif --}}
                <div id="installProgressBox"
                    style="display: none; margin-top: 18px; padding: 16px; background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: 12px; font-size: 0.82rem;">
                    <div
                        style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px; font-weight: 700; color: var(--text-color);">
                        <i class="fas fa-spinner fa-spin text-primary" style="font-size: 1.1rem;"></i>
                        <span id="installProgressTitle">Memproses Instalasi Sistem SAE...</span>
                    </div>
                    <div id="installStepList"
                        style="display: flex; flex-direction: column; gap: 6px; color: var(--text-muted); font-size: 0.78rem;">
                        <div id="step-connect"><i class="fas fa-circle-notch fa-spin text-primary me-2"></i> Memvalidasi
                            koneksi MySQL Server...</div>
                        <div id="step-env" style="opacity: 0.5;"><i class="fas fa-clock me-2"></i> Menulis file konfigurasi
                            .env...</div>
                        <div id="step-db" style="opacity: 0.5;"><i class="fas fa-clock me-2"></i> Memeriksa &amp;
                            menyiapkan skema database...</div>
                        <div id="step-finish" style="opacity: 0.5;"><i class="fas fa-clock me-2"></i> Finalisasi &amp;
                            pengalihan ke login...</div>
                    </div>
                </div>
            </form>

            <div style="margin-top: 20px; text-align: center;">
                <a href="{{ route('home') }}"
                    style="color: var(--text-muted); font-size: 0.8rem; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('installForm');
            const submitBtn = document.getElementById('btnSubmitInstall');
            const progressBox = document.getElementById('installProgressBox');

            if (!form || !submitBtn) return;

            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                // Nonaktifkan tombol & tampilkan status progres
                submitBtn.disabled = true;
                const originalBtnHtml = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Sedang Memproses...';

                if (progressBox) {
                    progressBox.style.display = 'block';
                }

                const stepConnect = document.getElementById('step-connect');
                const stepEnv = document.getElementById('step-env');
                const stepDb = document.getElementById('step-db');
                const stepFinish = document.getElementById('step-finish');

                const advanceStep = (activeEl, nextEl, textDone) => {
                    if (activeEl) {
                        activeEl.innerHTML =
                            '<i class="fas fa-check-circle text-success me-2"></i> ' + textDone;
                        activeEl.style.opacity = '1';
                        activeEl.style.color = '#10b981';
                    }
                    if (nextEl) {
                        nextEl.style.opacity = '1';
                        nextEl.style.color = 'var(--text-color)';
                        const icon = nextEl.querySelector('i');
                        if (icon) {
                            icon.className = 'fas fa-circle-notch fa-spin text-primary me-2';
                        }
                    }
                };

                // Simulasi progres visual sembari server mengeksekusi
                setTimeout(() => advanceStep(stepConnect, stepEnv, 'Koneksi MySQL terverifikasi'),
                1000);
                setTimeout(() => advanceStep(stepEnv, stepDb, 'File konfigurasi .env tersimpan'), 2200);

                const formData = new FormData(form);

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });

                    const data = await response.json().catch(() => null);

                    if (response.ok && data && data.success) {
                        advanceStep(stepDb, stepFinish, 'Skema database berhasil disiapkan');
                        if (stepFinish) {
                            stepFinish.innerHTML =
                                '<i class="fas fa-check-circle text-success me-2"></i> Instalasi tuntas!';
                            stepFinish.style.opacity = '1';
                            stepFinish.style.color = '#10b981';
                        }

                        if (window.Swal) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Instalasi SAE Berhasil! 🎉',
                                text: data.message ||
                                    'Sistem siap digunakan. Mengalihkan ke login...',
                                confirmButtonText: 'Buka Halaman Login',
                                confirmButtonColor: '#6366f1',
                                timer: 2500,
                                timerProgressBar: true
                            }).then(() => {
                                window.location.href = data.redirect || "{{ route('login') }}";
                            });
                        } else {
                            window.location.href = data.redirect || "{{ route('login') }}";
                        }
                    } else {
                        const errorMsg = (data && data.message) ? data.message :
                            'Terjadi kegagalan saat instalasi (Kode HTTP ' + response.status + ').';
                        if (window.Swal) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Instalasi Gagal',
                                text: errorMsg,
                                confirmButtonColor: '#ef4444'
                            });
                        } else {
                            alert('Instalasi Gagal: ' + errorMsg);
                        }
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnHtml;
                        if (progressBox) progressBox.style.display = 'none';
                    }
                } catch (err) {
                    console.error('Install error:', err);
                    // Kemungkinan timeout atau jeda jaringan
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Memeriksa Status Server',
                            text: 'Respon server tertunda atau waktu tunggu habis. Halaman akan dimuat ulang untuk mengecek status database yang sudah masuk.',
                            confirmButtonText: 'Periksa Sekarang',
                            confirmButtonColor: '#6366f1'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        alert('Koneksi tertunda. Memuat ulang halaman...');
                        window.location.reload();
                    }
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }
            });
        });
    </script>
@endsection
