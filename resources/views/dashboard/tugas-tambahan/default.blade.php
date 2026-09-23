@extends('layouts.dashboard')

@section('title', 'Dashboard ' . $duty->nama . ' — SAE')
@section('dash_title', $duty->nama)

@section('content')
    <!-- Banner Header Tugas Tambahan -->
    <div class="dash-banner" style="margin-bottom: 24px; padding: 22px 26px; border-radius: 16px; background: linear-gradient(135deg, rgba(99, 102, 241, 0.12) 0%, rgba(16, 185, 129, 0.08) 100%); border: 1px solid rgba(99, 102, 241, 0.25);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
            <div style="display: flex; align-items: flex-start; gap: 16px; flex: 1; min-width: 280px;">
                <div style="width: 52px; height: 52px; border-radius: 14px; background: rgba(99, 102, 241, 0.2); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; box-shadow: 0 4px 14px rgba(99, 102, 241, 0.2);">
                    <i class="fas {{ $duty->icon ?? 'fa-briefcase' }}"></i>
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px;">
                        <span class="badge badge-primary" style="font-size: 0.72rem; padding: 3px 8px; font-weight: 700;">
                            TUGAS TAMBAHAN
                        </span>
                        <span class="badge badge-accent" style="font-size: 0.72rem; padding: 3px 8px; font-weight: 600;">
                            {{ $duty->bidang }}
                        </span>
                        <span class="badge badge-outline" style="font-size: 0.72rem; padding: 3px 8px;">
                            {{ $duty->kode }}
                        </span>
                    </div>
                    <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--text-color); margin: 0 0 6px 0;">
                        {{ $duty->nama }}
                    </h2>
                    <p style="color: var(--text-muted); font-size: 0.84rem; margin: 0; line-height: 1.5;">
                        Portal kerja &amp; pemantauan operasional tugas tambahan. Seluruh modul di bawah ini otomatis terhubung sesuai kewenangan tugas Anda.
                    </p>
                </div>
            </div>

            <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                @php
                    $baseRole = is_array($sessionUser) ? ($sessionUser['role'] ?? 'guru') : ($sessionUser->role ?? 'guru');
                @endphp
                <a href="{{ route('dashboard.' . ($baseRole === 'peserta_didik' ? 'peserta-didik' : $baseRole)) }}" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.82rem; border-radius: 8px;">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Dashboard Pokok
                </a>
            </div>
        </div>

        <!-- Meta Summary Badges -->
        <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-top: 18px; padding-top: 14px; border-top: 1px solid rgba(255, 255, 255, 0.08); font-size: 0.82rem;">
            <span class="badge badge-outline" style="font-size: 0.74rem; padding: 4px 10px;">
                <i class="fas fa-clock me-1 text-primary"></i> Beban: {{ $duty->ekuivalensi_jam > 0 ? $duty->ekuivalensi_jam . ' Jam / Minggu' : 'Reguler' }}
            </span>
            <span class="badge badge-outline" style="font-size: 0.74rem; padding: 4px 10px;">
                <i class="fas fa-users-gear me-1 text-primary"></i> Kelompok: {{ ucfirst($duty->kelompok) }}
            </span>
            @if ($userAssignment)
                <span class="badge badge-success" style="font-size: 0.74rem; padding: 4px 10px;">
                    <i class="fas fa-check-circle me-1"></i> SK: {{ $userAssignment->nomor_sk ?: 'Aktif' }}
                </span>
            @endif
            <span class="badge badge-outline" style="font-size: 0.74rem; padding: 4px 10px; margin-left: auto;">
                <i class="fas fa-layer-group me-1 text-primary"></i> {{ count($grantedModules) }} Modul Berwenang
            </span>
        </div>
    </div>

    <!-- Modul-Modul Kewenangan Tugas Tambahan Ini -->
    <div style="margin-bottom: 20px;">
        <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0 0 6px 0; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-cubes-stacked text-primary"></i> Akses Cepat Modul &amp; Fitur Tugas
        </h3>
        <p style="color: var(--text-muted); font-size: 0.82rem; margin: 0;">
            Pintasan langsung ke seluruh fitur operasional yang diberikan khusus untuk pemegang tugas {{ $duty->nama }}.
        </p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin-bottom: 30px;">
        @forelse ($grantedModules as $mod)
            @php
                $modRoute = $mod['route'] ?? null;
                $hasRoute = $modRoute && \Illuminate\Support\Facades\Route::has($modRoute);
                $url = $hasRoute ? route($modRoute) : '#';
            @endphp
            <a href="{{ $url }}" class="card" style="padding: 18px 20px; border-radius: 12px; margin: 0; text-decoration: none; color: inherit; transition: all 0.25s ease; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 14px; position: relative; overflow: hidden;"
               onmouseover="this.style.borderColor='rgba(99,102,241,0.5)'; this.style.transform='translateY(-2px)';"
               onmouseout="this.style.borderColor='var(--border-color)'; this.style.transform='translateY(0)';">
                <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(99, 102, 241, 0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                    <i class="fas {{ $mod['icon'] ?? 'fa-cube' }}"></i>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-size: 0.92rem; font-weight: 700; color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        {{ $mod['label'] }}
                    </div>
                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                        {{ $mod['group'] ?? 'Modul Sistem' }}
                    </div>
                </div>
                <div style="color: var(--text-muted); font-size: 0.85rem;">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>
        @empty
            <div class="card" style="grid-column: 1 / -1; padding: 30px; text-align: center; color: var(--text-muted);">
                <i class="fas fa-key mb-2" style="font-size: 2rem; opacity: 0.4;"></i>
                <div>Belum ada modul spesifik yang dikonfigurasi untuk tugas tambahan ini. Admin dapat mengaturnya pada menu Hak Akses &gt; Tab Tugas Tambahan.</div>
            </div>
        @endforelse
    </div>

    <!-- Rekan Personel Pemegang Tugas Serupa -->
    @if ($teamMembers->isNotEmpty())
        <div class="card" style="padding: 20px 24px; border-radius: 12px; margin-bottom: 24px;">
            <h3 style="font-size: 0.98rem; font-weight: 700; color: var(--text-color); margin: 0 0 14px 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-users text-primary"></i> Personel Pemegang Tugas {{ $duty->nama }} ({{ $teamMembers->count() }} Orang)
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 12px;">
                @foreach ($teamMembers as $tm)
                    <div style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 8px; background: var(--bg-hover); border: 1px solid var(--border-color);">
                        <div style="width: 34px; height: 34px; border-radius: 50%; background: rgba(99, 102, 241, 0.15); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 700;">
                            {{ strtoupper(substr($tm->nama, 0, 1)) }}
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-size: 0.84rem; font-weight: 700; color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $tm->nama }}
                            </div>
                            <div style="font-size: 0.7rem; color: var(--text-muted);">
                                {{ $tm->rombel_nama ? 'Kelas: ' . $tm->rombel_nama : ($tm->keterangan ?: 'NIP: ' . $tm->nip) }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endsection
