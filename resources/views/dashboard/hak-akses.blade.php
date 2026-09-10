@extends('layouts.dashboard')

@section('title', 'Hak Akses & Peran — SAE')
@section('dash_title', 'Hak Akses & Peran')

@section('content')
    <!-- Banner Header -->
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-shield-halved text-primary me-2"></i> Pengaturan Hak Akses &amp; CRUD
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Kelola hak akses menu navigasi, visibilitas, serta izin operasi <strong>CRUD (Create, Read, Update,
                    Delete)</strong> untuk setiap peran.
            </p>
        </div>
        <div class="dash-banner-actions">
            <button type="button" id="btnResetDefault" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.82rem;">
                <i class="fas fa-rotate-left me-1"></i> Reset Bawaan
            </button>
        </div>
    </div>

    <!-- Role Switcher Tabs (Desktop) -->
    <div class="dash-tabs-nav dash-desktop-tabs">
        <a href="{{ route('dashboard.hak-akses.index', ['role' => 'admin']) }}"
            class="btn {{ $activeRole === 'admin' ? 'btn-primary' : 'btn-outline' }}">
            <i class="fas fa-user-shield me-1"></i> Administrator ({{ $counts['admin'] }})
        </a>
        <a href="{{ route('dashboard.hak-akses.index', ['role' => 'guru']) }}"
            class="btn {{ $activeRole === 'guru' ? 'btn-primary' : 'btn-outline' }}">
            <i class="fas fa-chalkboard-user me-1"></i> Guru &amp; Tendik ({{ $counts['guru'] }})
        </a>
        <a href="{{ route('dashboard.hak-akses.index', ['role' => 'peserta_didik']) }}"
            class="btn {{ $activeRole === 'peserta_didik' ? 'btn-primary' : 'btn-outline' }}">
            <i class="fas fa-user-graduate me-1"></i> Peserta Didik ({{ $counts['peserta_didik'] ?? 0 }})
        </a>
    </div>

    <!-- Role Switcher (Mobile Custom Dropdown) -->
    <div class="dash-mobile-tab-select-wrap">
        <div class="dash-custom-dropdown">
            <button type="button" class="custom-dropdown-trigger" aria-haspopup="listbox" aria-expanded="false">
                <div class="custom-dropdown-trigger-label">
                    <i
                        class="fas {{ $activeRole === 'admin' ? 'fa-user-shield' : ($activeRole === 'guru' ? 'fa-chalkboard-user' : 'fa-user-graduate') }} text-primary me-2"></i>
                    <span>{{ $activeRole === 'admin' ? 'Administrator' : ($activeRole === 'guru' ? 'Guru & Tendik' : 'Peserta Didik') }}</span>
                    <span class="badge badge-primary badge-sm ms-2">{{ $counts[$activeRole] ?? 0 }}</span>
                </div>
                <i class="fas fa-chevron-down custom-dropdown-arrow"></i>
            </button>
            <div class="custom-dropdown-menu" role="listbox">
                <a href="{{ route('dashboard.hak-akses.index', ['role' => 'admin']) }}"
                    class="custom-dropdown-item {{ $activeRole === 'admin' ? 'active' : '' }}">
                    <div class="dropdown-item-left">
                        <i class="fas fa-user-shield text-primary me-2"></i>
                        <span>Administrator</span>
                    </div>
                    <span
                        class="badge {{ $activeRole === 'admin' ? 'badge-primary' : 'badge-outline' }} badge-sm">{{ $counts['admin'] }}</span>
                </a>
                <a href="{{ route('dashboard.hak-akses.index', ['role' => 'guru']) }}"
                    class="custom-dropdown-item {{ $activeRole === 'guru' ? 'active' : '' }}">
                    <div class="dropdown-item-left">
                        <i class="fas fa-chalkboard-user text-primary me-2"></i>
                        <span>Guru &amp; Tendik</span>
                    </div>
                    <span
                        class="badge {{ $activeRole === 'guru' ? 'badge-primary' : 'badge-outline' }} badge-sm">{{ $counts['guru'] }}</span>
                </a>
                <a href="{{ route('dashboard.hak-akses.index', ['role' => 'peserta_didik']) }}"
                    class="custom-dropdown-item {{ $activeRole === 'peserta_didik' ? 'active' : '' }}">
                    <div class="dropdown-item-left">
                        <i class="fas fa-user-graduate text-primary me-2"></i>
                        <span>Peserta Didik</span>
                    </div>
                    <span
                        class="badge {{ $activeRole === 'peserta_didik' ? 'badge-primary' : 'badge-outline' }} badge-sm">{{ $counts['peserta_didik'] ?? 0 }}</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Permission Groups -->
    @foreach ($permissionsConfig as $groupName => $items)
        <div class="card"
            style="padding: 0; margin-bottom: 20px; border-radius: 12px; overflow: hidden; border: 1px solid var(--border-color);">
            <div
                style="padding: 14px 20px; background: rgba(99, 102, 241, 0.05); border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between;">
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color);">
                    <i
                        class="fas {{ $groupName === 'Menu Navigasi' ? 'fa-bars-staggered' : 'fa-gear' }} text-primary me-2"></i>
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
                        $permRecord = $savedPermissions->get($permKey);
                        $isAllowed = $permRecord ? (bool) $permRecord->is_allowed : $activeRole === 'admin';
                        $canC = $permRecord ? (bool) $permRecord->can_create : $activeRole === 'admin';
                        $canR = $permRecord ? (bool) $permRecord->can_read : true;
                        $canU = $permRecord ? (bool) $permRecord->can_update : $activeRole === 'admin';
                        $canD = $permRecord ? (bool) $permRecord->can_delete : $activeRole === 'admin';
                        $isLocked = $activeRole === 'admin' && $permKey === 'menu_hak_akses';
                    @endphp

                    <div
                        style="padding: 14px 20px; border-bottom: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 10px; {{ !$isRelevant ? 'opacity: 0.45; background: rgba(0,0,0,0.02);' : '' }}">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                            <div style="display: flex; align-items: center; gap: 14px; flex: 1; min-width: 0;">
                                <div
                                    style="width: 38px; height: 38px; border-radius: 8px; background: rgba(99, 102, 241, 0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.05rem; flex-shrink: 0;">
                                    <i class="fas {{ $perm['icon'] }}"></i>
                                </div>
                                <div style="min-width: 0;">
                                    <div
                                        style="font-size: 0.88rem; font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                                        <span>{{ $perm['label'] }}</span>
                                        @if ($isLocked)
                                            <span class="badge badge-warning"
                                                style="font-size: 0.65rem; padding: 2px 6px;">Kunci Sistem</span>
                                        @endif
                                        @if (!$isRelevant)
                                            <span class="badge"
                                                style="font-size: 0.65rem; padding: 2px 6px; background: rgba(148, 163, 184, 0.2); color: var(--text-muted);">Di
                                                luar peran ini</span>
                                        @endif
                                    </div>
                                    <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 2px;">
                                        {{ $perm['desc'] }} &bull; <code
                                            style="font-size: 0.72rem; color: var(--primary);">{{ $permKey }}</code>
                                    </div>
                                </div>
                            </div>

                            <!-- Master Radio Switch -->
                            <div style="display: flex; align-items: center; gap: 12px; flex-shrink: 0; background: rgba(0,0,0,0.15); padding: 6px 12px; border-radius: 8px;">
                                <label style="display: flex; align-items: center; gap: 6px; font-size: 0.78rem; cursor: {{ $isLocked ? 'not-allowed' : 'pointer' }}; color: {{ $isAllowed ? '#10b981' : 'var(--text-muted)' }}; font-weight: {{ $isAllowed ? '700' : '500' }}; margin: 0;">
                                    <input type="radio" class="permission-radio" name="menu_{{ $permKey }}" data-role="{{ $activeRole }}" data-key="{{ $permKey }}" value="1" {{ $isAllowed ? 'checked' : '' }} {{ $isLocked ? 'disabled' : '' }}>
                                    Aktif
                                </label>
                                <label style="display: flex; align-items: center; gap: 6px; font-size: 0.78rem; cursor: {{ $isLocked ? 'not-allowed' : 'pointer' }}; color: {{ !$isAllowed ? '#ef4444' : 'var(--text-muted)' }}; font-weight: {{ !$isAllowed ? '700' : '500' }}; margin: 0;">
                                    <input type="radio" class="permission-radio" name="menu_{{ $permKey }}" data-role="{{ $activeRole }}" data-key="{{ $permKey }}" value="0" {{ !$isAllowed ? 'checked' : '' }} {{ $isLocked ? 'disabled' : '' }}>
                                    Nonaktif
                                </label>
                            </div>
                        </div>

                        <!-- CRUD Matrix Radios -->
                        <div class="crud-controls" style="margin-left: 52px; padding-top: 10px; border-top: 1px dashed rgba(255,255,255,0.05);">
                            <span style="display: block; font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Izin Operasi:</span>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px;">
                                @foreach([
                                    ['action' => 'create', 'label' => 'Create', 'icon' => 'fa-plus-circle', 'color' => '#3b82f6', 'val' => $canC],
                                    ['action' => 'read', 'label' => 'Read', 'icon' => 'fa-eye', 'color' => '#10b981', 'val' => $canR],
                                    ['action' => 'update', 'label' => 'Update', 'icon' => 'fa-pen-to-square', 'color' => '#f59e0b', 'val' => $canU],
                                    ['action' => 'delete', 'label' => 'Delete', 'icon' => 'fa-trash', 'color' => '#ef4444', 'val' => $canD],
                                ] as $crud)
                                    <div style="display: flex; flex-direction: column; gap: 6px; background: rgba(0,0,0,0.15); padding: 8px 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.02);">
                                        <span style="font-size: 0.72rem; font-weight: 700; color: {{ $crud['color'] }};">
                                            <i class="fas {{ $crud['icon'] }} me-1"></i> {{ $crud['label'] }}
                                        </span>
                                        <div style="display: flex; gap: 12px;">
                                            <label style="display: flex; align-items: center; gap: 4px; font-size: 0.75rem; color: {{ $crud['val'] ? '#10b981' : 'var(--text-muted)' }}; cursor: {{ $isLocked ? 'not-allowed' : 'pointer' }}; margin: 0; font-weight: {{ $crud['val'] ? '700' : '500' }};">
                                                <input type="radio" class="crud-radio" name="{{ $crud['action'] }}_{{ $permKey }}" data-role="{{ $activeRole }}" data-key="{{ $permKey }}" data-action="{{ $crud['action'] }}" value="1" {{ $crud['val'] ? 'checked' : '' }} {{ $isLocked ? 'disabled' : '' }}>
                                                Ya
                                            </label>
                                            <label style="display: flex; align-items: center; gap: 4px; font-size: 0.75rem; color: {{ !$crud['val'] ? '#ef4444' : 'var(--text-muted)' }}; cursor: {{ $isLocked ? 'not-allowed' : 'pointer' }}; margin: 0; font-weight: {{ !$crud['val'] ? '700' : '500' }};">
                                                <input type="radio" class="crud-radio" name="{{ $crud['action'] }}_{{ $permKey }}" data-role="{{ $activeRole }}" data-key="{{ $permKey }}" data-action="{{ $crud['action'] }}" value="0" {{ !$crud['val'] ? 'checked' : '' }} {{ $isLocked ? 'disabled' : '' }}>
                                                Tidak
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                const showToast = (title, icon = 'success') => {
                    if (typeof Swal !== 'undefined') {
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 1600,
                            timerProgressBar: true
                        });
                        Toast.fire({
                            icon,
                            title
                        });
                    }
                };

                // 1. Toggle Akses Menu Keseluruhan (Master Switch Radios)
                document.querySelectorAll('.permission-radio').forEach(radio => {
                    radio.addEventListener('change', async (e) => {
                        const target = e.target;
                        const role = target.dataset.role;
                        const key = target.dataset.key;
                        const isAllowed = target.value === '1';

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
                                // Update colors
                                const container = target.closest('div');
                                const labelAktif = container.querySelector('label:first-child');
                                const labelMati = container.querySelector('label:last-child');
                                
                                labelAktif.style.color = isAllowed ? '#10b981' : 'var(--text-muted)';
                                labelAktif.style.fontWeight = isAllowed ? '700' : '500';
                                
                                labelMati.style.color = !isAllowed ? '#ef4444' : 'var(--text-muted)';
                                labelMati.style.fontWeight = !isAllowed ? '700' : '500';
                                
                                showToast(data.message);
                            } else {
                                throw new Error(data.message || 'Gagal mengubah izin');
                            }
                        } catch (err) {
                            // Revert radio selection
                            const revertedValue = isAllowed ? '0' : '1';
                            target.closest('div').querySelector(`input[value="${revertedValue}"]`).checked = true;
                            
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

                // 2. Toggle CRUD Spesifik (Create, Read, Update, Delete)
                document.querySelectorAll('.crud-radio').forEach(radio => {
                    radio.addEventListener('change', async (e) => {
                        const target = e.target;
                        const role = target.dataset.role;
                        const key = target.dataset.key;
                        const action = target.dataset.action;
                        const isAllowed = target.value === '1';

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
                                    action: action,
                                    is_allowed: isAllowed
                                })
                            });

                            const data = await res.json();
                            if (data.status === 'success') {
                                // Update colors
                                const container = target.closest('div');
                                const labelYa = container.querySelector('label:first-child');
                                const labelTidak = container.querySelector('label:last-child');
                                
                                labelYa.style.color = isAllowed ? '#10b981' : 'var(--text-muted)';
                                labelYa.style.fontWeight = isAllowed ? '700' : '500';
                                
                                labelTidak.style.color = !isAllowed ? '#ef4444' : 'var(--text-muted)';
                                labelTidak.style.fontWeight = !isAllowed ? '700' : '500';

                                showToast(data.message);
                            } else {
                                throw new Error(data.message || 'Gagal mengubah izin ' + action);
                            }
                        } catch (err) {
                            // Revert radio selection
                            const revertedValue = isAllowed ? '0' : '1';
                            target.closest('div').querySelector(`input[value="${revertedValue}"]`).checked = true;

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
                        const confirmed = typeof Swal !== 'undefined' ?
                            (await Swal.fire({
                                title: 'Reset ke Bawaan?',
                                text: 'Seluruh izin peran {{ ucfirst($activeRole) }} akan dikembalikan ke pengaturan default sistem.',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Ya, Reset',
                                cancelButtonText: 'Batal',
                                confirmButtonColor: '#ef4444',
                                cancelButtonColor: '#64748b'
                            })).isConfirmed :
                            confirm('Reset izin peran {{ $activeRole }} ke pengaturan default?');

                        if (!confirmed) return;

                        try {
                            const res = await fetch('{{ route('dashboard.hak-akses.reset') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken
                                },
                                body: JSON.stringify({
                                    role: '{{ $activeRole }}'
                                })
                            });
                            const data = await res.json();
                            if (data.status === 'success') {
                                window.location.reload();
                            } else {
                                throw new Error(data.message);
                            }
                        } catch (err) {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Gagal',
                                    text: err.message
                                });
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
