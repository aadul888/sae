@extends('layouts.dashboard')

@section('title', 'Hak Akses & Peran — SAE')
@section('dash_title', 'Hak Akses & Peran')

@section('content')
    <!-- Banner Header -->
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-shield-halved text-primary me-2"></i> Pengaturan Hak Akses
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Kelola hak akses menu navigasi dan batasan fitur operasional secara dinamis untuk setiap peran (Administrator, Guru, Siswa).
            </p>
        </div>
        <div class="dash-banner-actions">
            <button type="button" id="btnResetDefault" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.82rem;">
                <i class="fas fa-rotate-left me-1"></i> Reset Bawaan
            </button>
        </div>
    </div>

    <!-- Role Switcher Tabs -->
    <div style="display: flex; gap: 8px; background: var(--card-bg); padding: 5px; border-radius: 12px; border: 1px solid var(--border-color); margin-bottom: 20px; width: fit-content; flex-wrap: wrap;">
        <a href="{{ route('dashboard.hak-akses.index', ['role' => 'admin']) }}"
            class="btn {{ $activeRole === 'admin' ? 'btn-primary' : 'btn-outline' }}"
            style="border: none; padding: 7px 16px; font-size: 0.82rem; border-radius: 8px;">
            <i class="fas fa-user-shield me-1"></i> Administrator ({{ $counts['admin'] }})
        </a>
        <a href="{{ route('dashboard.hak-akses.index', ['role' => 'guru']) }}"
            class="btn {{ $activeRole === 'guru' ? 'btn-primary' : 'btn-outline' }}"
            style="border: none; padding: 7px 16px; font-size: 0.82rem; border-radius: 8px;">
            <i class="fas fa-chalkboard-user me-1"></i> Guru &amp; Tendik ({{ $counts['guru'] }})
        </a>
        <a href="{{ route('dashboard.hak-akses.index', ['role' => 'siswa']) }}"
            class="btn {{ $activeRole === 'siswa' ? 'btn-primary' : 'btn-outline' }}"
            style="border: none; padding: 7px 16px; font-size: 0.82rem; border-radius: 8px;">
            <i class="fas fa-user-graduate me-1"></i> Siswa ({{ $counts['siswa'] }})
        </a>
    </div>

    <!-- Permission Groups -->
    @foreach ($permissionsConfig as $groupName => $items)
        <div class="card" style="padding: 0; margin-bottom: 20px; border-radius: 12px; overflow: hidden; border: 1px solid var(--border-color);">
            <div style="padding: 14px 20px; background: rgba(99, 102, 241, 0.05); border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between;">
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color);">
                    <i class="fas {{ $groupName === 'Menu Navigasi' ? 'fa-bars-staggered' : 'fa-gear' }} text-primary me-2"></i>
                    {{ $groupName }}
                </div>
                <span class="badge badge-primary" style="font-size: 0.72rem; padding: 2px 8px;">
                    {{ count($items) }} Item
                </span>
            </div>

            <div style="display: flex; flex-direction: column;">
                @foreach ($items as $permKey => $perm)
                    @php
                        $isRelevant = in_array($activeRole, $perm['roles'] ?? []);
                        $isAllowed = isset($savedPermissions[$permKey])
                            ? (bool) $savedPermissions[$permKey]
                            : ($activeRole === 'admin' ? true : false);
                        $isLocked = ($activeRole === 'admin' && $permKey === 'menu_hak_akses');
                    @endphp

                    <div style="padding: 14px 20px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; gap: 16px; {{ !$isRelevant ? 'opacity: 0.45; background: rgba(0,0,0,0.02);' : '' }}">
                        <div style="display: flex; align-items: center; gap: 14px; flex: 1; min-width: 0;">
                            <div style="width: 38px; height: 38px; border-radius: 8px; background: rgba(99, 102, 241, 0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.05rem; flex-shrink: 0;">
                                <i class="fas {{ $perm['icon'] }}"></i>
                            </div>
                            <div style="min-width: 0;">
                                <div style="font-size: 0.88rem; font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                                    <span>{{ $perm['label'] }}</span>
                                    @if ($isLocked)
                                        <span class="badge badge-warning" style="font-size: 0.65rem; padding: 2px 6px;">Kunci Sistem</span>
                                    @endif
                                    @if (!$isRelevant)
                                        <span class="badge" style="font-size: 0.65rem; padding: 2px 6px; background: rgba(148, 163, 184, 0.2); color: var(--text-muted);">Di luar peran ini</span>
                                    @endif
                                </div>
                                <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 2px;">
                                    {{ $perm['desc'] }} &bull; <code style="font-size: 0.72rem; color: var(--primary);">{{ $permKey }}</code>
                                </div>
                            </div>
                        </div>

                        <!-- Toggle Switch -->
                        <div style="display: flex; align-items: center; gap: 10px; flex-shrink: 0;">
                            <label class="switch-container" style="position: relative; display: inline-block; width: 44px; height: 24px; margin: 0; cursor: {{ $isLocked ? 'not-allowed' : 'pointer' }};">
                                <input type="checkbox"
                                       class="permission-toggle"
                                       data-role="{{ $activeRole }}"
                                       data-key="{{ $permKey }}"
                                       {{ $isAllowed ? 'checked' : '' }}
                                       {{ $isLocked ? 'disabled' : '' }}
                                       style="opacity: 0; width: 0; height: 0;">
                                <span class="slider-toggle" style="position: absolute; cursor: {{ $isLocked ? 'not-allowed' : 'pointer' }}; inset: 0; background-color: {{ $isAllowed ? '#10b981' : '#64748b' }}; transition: .3s; border-radius: 24px;"></span>
                            </label>
                            <span class="status-label" style="font-size: 0.75rem; font-weight: 600; min-width: 50px; color: {{ $isAllowed ? '#10b981' : '#64748b' }};">
                                {{ $isAllowed ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    <style>
        .slider-toggle:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
        }
        input:checked + .slider-toggle:before {
            transform: translateX(20px);
        }
    </style>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const toggles = document.querySelectorAll('.permission-toggle');
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                toggles.forEach(toggle => {
                    toggle.addEventListener('change', async (e) => {
                        const cb = e.target;
                        const role = cb.dataset.role;
                        const key = cb.dataset.key;
                        const isAllowed = cb.checked;
                        const label = cb.closest('div').querySelector('.status-label');
                        const slider = cb.closest('label').querySelector('.slider-toggle');

                        // Temporary visual feedback
                        label.textContent = 'Menyimpan...';
                        label.style.color = '#eab308';

                        try {
                            const res = await fetch('{{ route('dashboard.hak-akses.toggle') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken
                                },
                                body: JSON.stringify({
                                    role: role,
                                    permission_key: key,
                                    is_allowed: isAllowed
                                })
                            });

                            const data = await res.json();
                            if (data.status === 'success') {
                                label.textContent = isAllowed ? 'Aktif' : 'Nonaktif';
                                label.style.color = isAllowed ? '#10b981' : '#64748b';
                                slider.style.backgroundColor = isAllowed ? '#10b981' : '#64748b';

                                if (typeof Swal !== 'undefined') {
                                    const Toast = Swal.mixin({
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 1800,
                                        timerProgressBar: true
                                    });
                                    Toast.fire({
                                        icon: 'success',
                                        title: data.message
                                    });
                                }
                            } else {
                                throw new Error(data.message || 'Gagal mengubah izin');
                            }
                        } catch (err) {
                            cb.checked = !isAllowed;
                            label.textContent = !isAllowed ? 'Aktif' : 'Nonaktif';
                            label.style.color = !isAllowed ? '#10b981' : '#64748b';
                            slider.style.backgroundColor = !isAllowed ? '#10b981' : '#64748b';

                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Gagal',
                                    text: err.message,
                                    confirmButtonColor: '#ef4444'
                                });
                            } else {
                                alert(err.message);
                            }
                        }
                    });
                });

                // Reset Button
                const btnReset = document.getElementById('btnResetDefault');
                if (btnReset) {
                    btnReset.addEventListener('click', async () => {
                        const confirmed = typeof Swal !== 'undefined'
                            ? (await Swal.fire({
                                title: 'Reset ke Bawaan?',
                                text: 'Seluruh izin peran {{ ucfirst($activeRole) }} akan dikembalikan ke pengaturan default sistem.',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Ya, Reset',
                                cancelButtonText: 'Batal',
                                confirmButtonColor: '#ef4444',
                                cancelButtonColor: '#64748b'
                            })).isConfirmed
                            : confirm('Reset izin peran {{ $activeRole }} ke pengaturan default?');

                        if (!confirmed) return;

                        try {
                            const res = await fetch('{{ route('dashboard.hak-akses.reset') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken
                                },
                                body: JSON.stringify({ role: '{{ $activeRole }}' })
                            });
                            const data = await res.json();
                            if (data.status === 'success') {
                                window.location.reload();
                            } else {
                                throw new Error(data.message);
                            }
                        } catch (err) {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({ icon: 'error', title: 'Gagal', text: err.message });
                            } else {
                                alert(err.message);
                            }
                        }
                    });
                }
            });
        </script>
    @endpush
@endsection
