<!-- SISI DEPAN (FRONT - PORTRAIT: 56mm x 87mm) -->
<div class="kp-card kp-card-front" id="card-front-{{ $card['identifier'] }}">
    <div class="kp-bg-decor kp-bg-decor-pegawai"></div>

    <!-- Watermark Logo Sekolah Latar Belakang Kartu -->
    @if (!empty($card['sekolah']['logo_url']))
        <div class="kp-card-watermark" style="opacity: 0.12;" title="{{ $card['sekolah']['nama'] }}">
            <img src="{{ $card['sekolah']['logo_url'] }}" alt="Watermark Logo Sekolah">
        </div>
    @endif

    <!-- Header Kartu -->
    <div class="kp-header">
        <div class="kp-header-left">
            @if (!empty($card['sekolah']['logo_url']))
                <img src="{{ $card['sekolah']['logo_url'] }}" alt="Logo Sekolah" class="kp-school-logo">
            @else
                <img src="{{ asset('img/logo-icon.png') }}" alt="Logo Sekolah" class="kp-school-logo">
            @endif
            <div class="kp-school-title">
                <span class="kp-card-type">KARTU TANDA PEGAWAI</span>
                <span class="kp-school-name">{{ $card['sekolah']['nama'] }}</span>
            </div>
        </div>
        <div class="kp-header-right">
            <img src="{{ asset('img/logo-icon.png') }}" alt="Logo SAE" class="kp-sae-logo"
                title="Sistem Aplikasi Edukasi">
        </div>
    </div>

    <div class="kp-divider"></div>

    <!-- Body Kartu (Portrait) -->
    <div class="kp-body-portrait" style="justify-content: flex-start; gap: 0;">
        <!-- Pasfoto Pegawai -->
        <div class="kp-photo-box">
            @php
                $rawFotoUrl = $card['foto_url'] ?? null;
                $fotoSrc = null;
                if (!empty($rawFotoUrl)) {
                    $p = parse_url($rawFotoUrl);
                    if (!empty($p['path'])) {
                        $fotoSrc = $p['path'] . (!empty($p['query']) ? '?' . $p['query'] : '');
                    } else {
                        $fotoSrc = $rawFotoUrl;
                    }
                }
            @endphp
            @if (!empty($fotoSrc))
                <img src="{{ $fotoSrc }}" data-fallback-url="{{ $rawFotoUrl }}" alt="Pasfoto {{ $card['nama'] }}" class="kp-student-photo"
                    onerror="if(this.dataset.fallbackUrl && this.src !== this.dataset.fallbackUrl){ this.src = this.dataset.fallbackUrl; } else { this.onerror=null; this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex'; }">
                <div class="kp-photo-placeholder" style="display: none; background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);">
                    <i class="fas fa-user-tie" style="color: #64748b; font-size: 16pt;"></i>
                    <span style="font-size: 5pt; font-weight: 700; color: #64748b; margin-top: 2px;">PEGAWAI</span>
                </div>
            @else
                <div class="kp-photo-placeholder" style="background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);">
                    <i class="fas fa-user-tie" style="color: #64748b; font-size: 16pt;"></i>
                    <span style="font-size: 5pt; font-weight: 700; color: #64748b; margin-top: 2px;">PEGAWAI</span>
                </div>
            @endif
        </div>

        <!-- Biodata Singkat Pegawai -->
        <div class="kp-student-info" style="margin-top: 1.5mm; flex-shrink: 0;">
            <div class="kp-student-name" title="{{ $card['nama'] }}">
                {{ $card['nama'] }}
            </div>

            <!-- Pill NIP -->
            <div class="kp-nisn-pill" title="NIP: {{ !empty($card['nip']) && $card['nip'] !== '-' ? $card['nip'] : '-' }}">
                <i class="fas fa-circle-check" style="color: var(--success, #10b981); font-size: 5.5pt;"></i>
                <span class="kp-nisn-value">
                    NIP. {{ !empty($card['nip']) && $card['nip'] !== '-' ? $card['nip'] : '-' }}
                </span>
            </div>
        </div>

        <!-- Area Tengah Vertikal: QR Code Berada Persis di Tengah Antara Pill NIP dan Garis Bawah Footer -->
        <div class="kp-qr-center-area" style="flex: 1; width: 100%; display: flex; align-items: center; justify-content: center; min-height: 0; margin: 0;">
            <div class="kp-qrcode-box" onclick="zoomKpQrCode(event, this)" ontouchstart="event.stopPropagation()"
                ontouchend="event.stopPropagation(); zoomKpQrCode(event, this);" data-pegawai-name="{{ $card['nama'] }}"
                data-pegawai-code="{{ !empty($card['nip']) && $card['nip'] !== '-' ? $card['nip'] : '-' }}"
                title="Klik untuk memperbesar QR Code (Presensi / Verifikasi)" style="margin: 0;">
                <div class="kp-qr-wrapper" style="width: 21.5mm; height: 21.5mm; padding: 1mm; border-radius: 2mm;">
                    {!! $card['qr_code_svg'] !!}
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Strip -->
    <div class="kp-footer-strip">
        <span>NPSN: {{ $card['sekolah']['npsn'] }}</span>
        <span>SAE DIGITAL ID</span>
    </div>
</div>
