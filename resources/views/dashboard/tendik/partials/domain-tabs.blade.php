{{--
    Navigasi bidang tugas Portal Tendik.
    Tampil sebagai tab pada layar lebar dan berubah menjadi dropdown
    pada mode responsif (mengikuti pola `periode-nav-*` / `tab-nav` Perpustakaan).

    Parameter:
      $allDomains    => daftar bidang (key => label/icon/color) dari dashboard/tendik.blade.php
      $activeSection => bidang yang sedang aktif
--}}
@php
    $allDomains = $allDomains ?? [];
    $activeSection = $activeSection ?? 'umum';
    $penggunaTendik = session('user');
    $peranTendik = is_array($penggunaTendik)
        ? $penggunaTendik['role'] ?? null
        : $penggunaTendik->role ?? null;

    // Kunci izin menu tiap bidang (mengikuti TendikDutyResolver::DUTY_PERMISSION_MAP).
    $izinBidang = [
        'umum' => null, // selalu terbuka
        'kepala-tas' => 'menu_kepala_tas',
        'piket' => 'menu_piket',
        'kesiswaan' => 'menu_kesiswaan',
        'kepegawaian' => 'menu_kepegawaian',
        'sarpras' => 'menu_sarpras',
        'laboran' => 'menu_laboran',
        'perpustakaan' => 'menu_perpustakaan',
        'teknisi' => 'menu_teknisi',
        'keamanan' => 'menu_keamanan',
        'persuratan' => 'menu_persuratan',
        'penjaga' => 'menu_penjaga',
    ];

    $bidangTersedia = [];
    foreach ($allDomains as $key => $meta) {
        $izin = $izinBidang[$key] ?? 'menu_' . str_replace('-', '_', $key);
        $boleh = $izin === null || $peranTendik === 'admin' || \App\Models\RolePermission::canAccess($penggunaTendik ?: 'tendik', $izin);
        if (!$boleh) {
            continue;
        }
        $bidangTersedia[$key] = $meta;
    }
@endphp

@if (count($bidangTersedia) > 1)
    <div class="periode-nav-wrapper tendik-domain-nav">
        <div class="periode-nav-desktop">
            @foreach ($bidangTersedia as $key => $meta)
                <a href="{{ request()->fullUrlWithQuery(['bidang' => $key]) }}"
                    class="periode-nav-tab {{ $activeSection === $key ? 'active' : '' }}"
                    @if ($activeSection === $key) aria-current="page" @endif
                    title="{{ $meta['label'] }}">
                    <i class="{{ $meta['icon'] }}" style="color: {{ $meta['color'] }};"></i>
                    <span>{{ $meta['label'] }}</span>
                </a>
            @endforeach
        </div>

        <div class="periode-nav-mobile">
            <div class="periode-mobile-select-box" style="margin-bottom: 0;">
                <label><i class="fas fa-briefcase text-primary me-1"></i> Pilih Bidang Tugas:</label>
                <select class="periode-mobile-select" onchange="if(this.value) window.location.href=this.value;"
                    aria-label="Pilih bidang tugas">
                    @foreach ($bidangTersedia as $key => $meta)
                        <option value="{{ request()->fullUrlWithQuery(['bidang' => $key]) }}"
                            {{ $activeSection === $key ? 'selected' : '' }}>
                            {{ $meta['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
@endif
