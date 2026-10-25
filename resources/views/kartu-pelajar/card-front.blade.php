<!-- SISI DEPAN (FRONT - PORTRAIT: 56mm x 87mm) -->
<div class="kp-card kp-card-front" id="card-front-{{ $card['nisn'] }}">
    <div class="kp-bg-decor"></div>

    <!-- Logo Jurusan sebagai Latar Belakang Transparan Badan Kartu -->
    @if (!empty($card['jurusan_logo_url']))
        <div class="kp-card-watermark" title="Jurusan: {{ $card['jurusan'] }}">
            <img src="{{ $card['jurusan_logo_url'] }}" alt="Watermark Logo Jurusan">
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
                <span class="kp-card-type">KARTU TANDA PELAJAR</span>
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
    <div class="kp-body-portrait">
        <!-- Pasfoto Peserta Didik -->
        <div class="kp-photo-box">
            @if (!empty($card['foto_url']))
                <img src="{{ $card['foto_url'] }}" alt="Pasfoto {{ $card['nama'] }}" class="kp-student-photo">
            @else
                <div class="kp-photo-placeholder">
                    <i class="fas fa-user"></i>
                    <span>FOTO</span>
                </div>
            @endif
        </div>

        <!-- Biodata Singkat -->
        <div class="kp-student-info">
            <div class="kp-student-name" title="{{ $card['nama'] }}">{{ $card['nama'] }}</div>

            <div class="kp-nisn-pill" title="Status: {{ $card['status'] ?? 'AKTIF' }}">
                <i class="fas fa-circle-check" style="color: #10b981; font-size: 5.5pt;"></i>
                <span class="kp-nisn-value">{{ $card['nisn'] }}</span>
            </div>
        </div>

        <!-- QR Code Direct Scan (Bisa di-klik untuk Zoom Fullscreen saat Transaksi) -->
        <div class="kp-qrcode-box" onclick="zoomKpQrCode(event, this)" ontouchstart="event.stopPropagation()"
            ontouchend="event.stopPropagation(); zoomKpQrCode(event, this);" data-student-name="{{ $card['nama'] }}"
            data-student-nisn="{{ $card['nisn'] }}" title="Klik untuk memperbesar QR Code (Transaksi / Presensi)">
            <div class="kp-qr-wrapper">
                {!! $card['qr_code_svg'] !!}
            </div>
        </div>
    </div>

    <!-- Footer Strip -->
    <div class="kp-footer-strip">
        <span>NPSN: {{ $card['sekolah']['npsn'] }}</span>
        <span>SAE DIGITAL ID</span>
    </div>
</div>
