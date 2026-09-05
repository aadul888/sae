@extends('layouts.dashboard')

@section('title', 'Update Sistem — Sistem Aplikasi Edukasi (SAE)')
@section('dash_title', 'Update Sistem')

@section('content')
    <!-- Welcome / Header Banner -->
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-arrows-rotate text-primary me-2"></i> Update Sistem
            </h2>
            <p style="color: var(--text-muted); font-size: 0.88rem;">
                Deteksi dan instalasi otomatis pembaruan file kode, assets, dan migrasi skema database SAE.
            </p>
        </div>
        <div class="dash-banner-actions">
            <button type="button" id="btnCheckUpdate" class="btn btn-outline" style="padding: 9px 16px; font-size: 0.85rem;">
                <i class="fas fa-sync-alt me-1" id="checkIcon"></i> Periksa Pembaruan
            </button>
        </div>
    </div>

    <!-- Status Stats Grid -->
    <div class="dash-stat-grid">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                <i class="fas fa-code-branch"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" id="currentVer" style="font-size: 1.25rem;">
                    v{{ $status['current_version'] }}
                </div>
                <div class="dash-stat-label">Versi Sistem Terpasang</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
                <i class="fas fa-code-commit"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" id="gitBranch" style="font-size: 1.15rem;">
                    {{ $status['has_git'] ? $status['git_branch'] : 'Standalone' }}
                </div>
                <div class="dash-stat-label">Branch Repositori</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon"
                style="background: {{ count($status['pending_migrations']) > 0 ? 'rgba(245,158,11,0.15)' : 'rgba(16,185,129,0.15)' }}; color: {{ count($status['pending_migrations']) > 0 ? '#f59e0b' : '#10b981' }};">
                <i class="fas fa-database"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" id="pendingMigCount">
                    {{ count($status['pending_migrations']) }}
                </div>
                <div class="dash-stat-label">Database Migrasi Pending</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(147, 51, 234, 0.15); color: #9333ea;">
                <i class="fas fa-clock-rotate-left"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" id="lastUpdateText"
                    style="font-size: 0.95rem; font-weight: 700; word-break: break-all;">
                    {{ $status['last_update_at'] ?? 'Belum Tercatat' }}
                </div>
                <div class="dash-stat-label">Riwayat Update Terakhir</div>
            </div>
        </div>
    </div>

    <!-- Main Section: Grid 2 Column -->
    <div class="dash-grid-2">
        <!-- Status & Action Card -->
        <div class="card" style="margin-bottom: 0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-color);">
                    <i class="fas fa-shield-halved text-primary me-2"></i> Status Integritas Sistem
                </h3>
                <span class="badge {{ $status['updates_available'] ? 'badge-warning' : 'badge-primary' }}" id="statusBadge">
                    {{ $status['updates_available'] ? 'Pembaruan Siap' : 'Versi Terkini' }}
                </span>
            </div>

            <!-- Update Alert Banner -->
            <div id="updateBanner"
                style="padding: 16px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 14px; background: {{ $status['updates_available'] ? 'rgba(245, 158, 11, 0.1)' : 'rgba(16, 185, 129, 0.1)' }}; border: 1px solid {{ $status['updates_available'] ? 'rgba(245, 158, 11, 0.3)' : 'rgba(16, 185, 129, 0.3)' }};">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div
                        style="width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: {{ $status['updates_available'] ? 'rgba(245, 158, 11, 0.2)' : 'rgba(16, 185, 129, 0.2)' }}; color: {{ $status['updates_available'] ? '#f59e0b' : '#10b981' }};">
                        <i class="fas {{ $status['updates_available'] ? 'fa-triangle-exclamation' : 'fa-check' }}"></i>
                    </div>
                    <div>
                        <div style="font-weight: 700; color: var(--text-color);" id="bannerTitle">
                            {{ $status['updates_available'] ? 'Pembaruan Tersedia!' : 'Sistem Sudah yang Terbaru' }}
                        </div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);" id="bannerDesc">
                            {{ $status['updates_available'] ? 'Ditemukan file kode atau migrasi database baru yang siap dipasang.' : 'Semua file kode dan struktur database sudah dalam kondisi prima.' }}
                        </div>
                    </div>
                </div>
                <button type="button" id="btnRunUpdate"
                    class="btn btn-primary {{ $status['updates_available'] ? '' : 'hidden' }}"
                    style="padding: 9px 16px; font-size: 0.85rem; white-space: nowrap; display: {{ $status['updates_available'] ? 'inline-flex' : 'none' }};">
                    <i class="fas fa-download me-1"></i> Pasang Sekarang
                </button>
            </div>

            <!-- Migration Details if any -->
            <div id="migrationList" class="{{ empty($status['pending_migrations']) ? 'hidden' : '' }}"
                style="margin-top: 15px;">
                <div style="font-weight: 700; font-size: 0.85rem; color: var(--text-color); margin-bottom: 8px;">
                    <i class="fas fa-database text-warning me-1"></i> Migrasi Database yang Belum Dipasang:
                </div>
                <div
                    style="background: var(--bg-hover); border-radius: 8px; padding: 10px; max-height: 140px; overflow-y: auto;">
                    <ul style="font-size: 0.8rem; color: var(--text-muted); margin: 0; padding-left: 18px;" id="migUl">
                        @foreach ($status['pending_migrations'] as $mig)
                            <li><code>{{ $mig }}</code></li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div style="margin-top: 20px; font-size: 0.82rem; color: var(--text-muted); line-height: 1.5;">
                <i class="fas fa-circle-info text-primary me-1"></i> <b>Catatan Pembaruan:</b>
                Proses update akan melakukan migrasi database (jika ada skema baru), sinkronisasi file kode, dan
                membersihkan cache aplikasi secara otomatis.
            </div>
        </div>

        <!-- Terminal Execution Log Card -->
        <div class="card" style="margin-bottom: 0; display: flex; flex-direction: column;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-color);">
                    <i class="fas fa-terminal text-primary me-2"></i> Log Eksekusi Pembaruan
                </h3>
                <span class="badge" style="background: rgba(148, 163, 184, 0.15); color: var(--text-muted);">Console</span>
            </div>

            <div id="updateLogBox"
                style="flex: 1; min-height: 250px; max-height: 320px; background: #0f172a; color: #38bdf8; font-family: monospace; font-size: 0.8rem; padding: 14px; border-radius: 10px; overflow-y: auto; border: 1px solid rgba(255,255,255,0.08);">
                <div style="color: #64748b;">// Siap menerima proses update sistem...</div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btnCheck = document.getElementById('btnCheckUpdate');
            const btnRun = document.getElementById('btnRunUpdate');
            const checkIcon = document.getElementById('checkIcon');
            const logBox = document.getElementById('updateLogBox');
            const updateBanner = document.getElementById('updateBanner');
            const bannerTitle = document.getElementById('bannerTitle');
            const bannerDesc = document.getElementById('bannerDesc');
            const pendingMigCount = document.getElementById('pendingMigCount');
            const statusBadge = document.getElementById('statusBadge');
            const lastUpdateText = document.getElementById('lastUpdateText');
            const migrationList = document.getElementById('migrationList');
            const migUl = document.getElementById('migUl');

            function appendLog(msg, color = '#38bdf8') {
                const line = document.createElement('div');
                line.style.color = color;
                line.style.marginBottom = '4px';
                line.textContent = `[${new Date().toLocaleTimeString()}] ${msg}`;
                logBox.appendChild(line);
                logBox.scrollTop = logBox.scrollHeight;
            }

            btnCheck.addEventListener('click', async () => {
                checkIcon.classList.add('fa-spin');
                appendLog('Memeriksa update sistem...', '#94a3b8');
                try {
                    const res = await fetch('{{ route('dashboard.update.check') }}');
                    const data = await res.json();
                    if (data.status === 'success') {
                        const s = data.data;
                        pendingMigCount.textContent = s.pending_migrations.length;
                        if (s.last_update_at) {
                            lastUpdateText.textContent = s.last_update_at;
                        }

                        if (s.pending_migrations.length > 0) {
                            migUl.innerHTML = s.pending_migrations.map(m =>
                                `<li><code>${m}</code></li>`).join('');
                            migrationList.classList.remove('hidden');
                        } else {
                            migrationList.classList.add('hidden');
                        }

                        if (s.updates_available) {
                            bannerTitle.textContent = 'Pembaruan Tersedia!';
                            bannerDesc.textContent =
                                'Ditemukan file kode atau migrasi database baru yang siap dipasang.';
                            updateBanner.style.background = 'rgba(245, 158, 11, 0.1)';
                            updateBanner.style.borderColor = 'rgba(245, 158, 11, 0.3)';
                            statusBadge.className = 'badge badge-warning';
                            statusBadge.textContent = 'Pembaruan Siap';
                            btnRun.classList.remove('hidden');
                            btnRun.style.display = 'inline-flex';
                            appendLog('Pembaruan terdeteksi!', '#fbbf24');
                        } else {
                            bannerTitle.textContent = 'Sistem Sudah yang Terbaru';
                            bannerDesc.textContent =
                                'Semua file kode dan struktur database sudah dalam kondisi prima.';
                            updateBanner.style.background = 'rgba(16, 185, 129, 0.1)';
                            updateBanner.style.borderColor = 'rgba(16, 185, 129, 0.3)';
                            statusBadge.className = 'badge badge-primary';
                            statusBadge.textContent = 'Versi Terkini';
                            btnRun.classList.add('hidden');
                            btnRun.style.display = 'none';
                            appendLog('Sistem dalam versi terbaru.', '#34d399');
                        }
                    }
                } catch (err) {
                    appendLog('Gagal memeriksa update: ' + err.message, '#f87171');
                } finally {
                    checkIcon.classList.remove('fa-spin');
                }
            });

            btnRun.addEventListener('click', async () => {
                const confirmed = typeof Swal !== 'undefined' ?
                    (await Swal.fire({
                        title: 'Jalankan Pembaruan Sistem?',
                        text: 'Sistem akan memperbarui file kode dan migrasi database secara otomatis.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-download me-1"></i> Ya, Pasang Sekarang',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#6366f1',
                        cancelButtonColor: '#64748b',
                        background: document.documentElement.getAttribute('data-theme') ===
                            'light' ? '#ffffff' : '#0f172a',
                        color: document.documentElement.getAttribute('data-theme') === 'light' ?
                            '#0f172a' : '#f8fafc'
                    })).isConfirmed : confirm('Jalankan pembaruan sistem sekarang?');

                if (!confirmed) return;

                btnRun.disabled = true;
                btnRun.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memproses...';
                appendLog('Memulai pembaruan sistem...', '#38bdf8');

                try {
                    const res = await fetch('{{ route('dashboard.update.execute') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json'
                        }
                    });
                    const data = await res.json();
                    if (data.status === 'success') {
                        data.data.logs.forEach(l => appendLog(l, '#34d399'));
                        appendLog('Update sistem selesai dengan sukses!', '#4ade80');
                        btnRun.classList.add('hidden');
                        btnRun.style.display = 'none';
                        bannerTitle.textContent = 'Sistem Berhasil Diperbarui';
                        bannerDesc.textContent = 'Semua file kode dan database telah disinkronkan.';
                        updateBanner.style.background = 'rgba(16, 185, 129, 0.1)';
                        updateBanner.style.borderColor = 'rgba(16, 185, 129, 0.3)';
                        statusBadge.className = 'badge badge-primary';
                        statusBadge.textContent = 'Versi Terkini';
                        pendingMigCount.textContent = '0';
                        migrationList.classList.add('hidden');
                        if (data.data.timestamp) {
                            lastUpdateText.textContent = data.data.timestamp;
                        }

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Berhasil!',
                                text: 'Sistem berhasil diperbarui.',
                                icon: 'success',
                                confirmButtonColor: '#10b981',
                                background: document.documentElement.getAttribute(
                                    'data-theme') === 'light' ? '#ffffff' : '#0f172a',
                                color: document.documentElement.getAttribute('data-theme') ===
                                    'light' ? '#0f172a' : '#f8fafc'
                            });
                        }
                    } else {
                        data.data.logs.forEach(l => appendLog(l, '#f87171'));
                        appendLog('Pembaruan selesai dengan catatan / error.', '#fbbf24');
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Perhatian',
                                text: 'Pembaruan selesai dengan beberapa catatan.',
                                icon: 'warning',
                                confirmButtonColor: '#f59e0b',
                                background: document.documentElement.getAttribute(
                                    'data-theme') === 'light' ? '#ffffff' : '#0f172a',
                                color: document.documentElement.getAttribute('data-theme') ===
                                    'light' ? '#0f172a' : '#f8fafc'
                            });
                        }
                    }
                } catch (err) {
                    appendLog('Error eksekusi update: ' + err.message, '#f87171');
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Gagal!',
                            text: 'Terjadi kesalahan: ' + err.message,
                            icon: 'error',
                            confirmButtonColor: '#ef4444',
                            background: document.documentElement.getAttribute('data-theme') ===
                                'light' ? '#ffffff' : '#0f172a',
                            color: document.documentElement.getAttribute('data-theme') ===
                                'light' ? '#0f172a' : '#f8fafc'
                        });
                    }
                } finally {
                    btnRun.disabled = false;
                    btnRun.innerHTML = '<i class="fas fa-download me-1"></i> Pasang Sekarang';
                }
            });
        });
    </script>
@endsection
