{{-- 
    Template Kartu Pelajar Digital SAE — FORMAT PORTRAIT (ISO CR-80: 54mm x 85.6mm)
    Halaman Depan:
    - Logo sekolah (kiri) & Logo SAE berwarna (kanan)
    - Logo Jurusan transparan sebagai latar belakang badan kartu pelajar
    - Pasfoto peserta didik terpusat
    - Nama, NISN, Rombel/Jurusan, Status Aktif & Tahun Pelajaran
    - QR Code scan direct verifikasi online di bagian bawah
    Halaman Belakang:
    - Ketentuan pemegang kartu, Barcode NISN, Kontak resmi sekolah
    - Bersih tanpa TTD dan stempel
--}}

<div class="kp-card-pair {{ $wrapperClass ?? '' }}">
    <!-- SISI DEPAN (FRONT - PORTRAIT) -->
    <div class="kp-card kp-card-front" id="card-front-{{ $card['nisn'] }}">
        <div class="kp-bg-decor"></div>

        <!-- Logo Jurusan sebagai Latar Belakang Transparan Badan Kartu -->
        @if(!empty($card['jurusan_logo_url']))
            <div class="kp-card-watermark" title="Jurusan: {{ $card['jurusan'] }}">
                <img src="{{ $card['jurusan_logo_url'] }}" alt="Watermark Logo Jurusan">
            </div>
        @endif

        <!-- Header Kartu -->
        <div class="kp-header">
            <div class="kp-header-left">
                @if(!empty($card['sekolah']['logo_url']))
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
                <img src="{{ asset('img/logo-icon.png') }}" alt="Logo SAE" class="kp-sae-logo" title="Sistem Administrasi Edukasi">
            </div>
        </div>

        <div class="kp-divider"></div>

        <!-- Body Kartu (Portrait) -->
        <div class="kp-body-portrait">
            <!-- Pasfoto Peserta Didik -->
            <div class="kp-photo-box">
                @if(!empty($card['foto_url']))
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

                <div class="kp-rombel-jurusan">
                    <span class="kp-rombel-name">{{ $card['rombel'] }}</span>
                    <span class="kp-bullet">&bull;</span>
                    <span class="kp-tp-label">{{ $card['tahun_pelajaran'] }}</span>
                </div>
            </div>

            <!-- QR Code Direct Scan (Bisa di-klik untuk Zoom Fullscreen saat Transaksi) -->
            <div class="kp-qrcode-box" 
                 onclick="zoomKpQrCode(event, this)" 
                 data-student-name="{{ $card['nama'] }}" 
                 data-student-nisn="{{ $card['nisn'] }}" 
                 data-student-rombel="{{ $card['rombel'] }}"
                 title="Klik untuk memperbesar QR Code (Transaksi / Presensi)">
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

    <!-- SISI BELAKANG (BACK - PORTRAIT) -->
    <div class="kp-card kp-card-back" id="card-back-{{ $card['nisn'] }}">
        <div class="kp-bg-decor kp-bg-decor-back"></div>

        <!-- Header Belakang -->
        <div class="kp-back-header">
            <div class="kp-back-title">KARTU TANDA PELAJAR DIGITAL</div>
            <div class="kp-back-school">{{ $card['sekolah']['nama'] }}</div>
        </div>

        <div class="kp-divider kp-divider-back"></div>

        <!-- Ketentuan Penggunaan -->
        <div class="kp-back-content">
            <div class="kp-terms-title">KETENTUAN PENGGUNAAN:</div>
            <ol class="kp-terms-list">
                <li>Kartu ini adalah identitas sah Peserta Didik di lingkungan {{ $card['sekolah']['nama'] }}.</li>
                <li>Wajib dibawa saat di sekolah untuk presensi, perizinan gerbang, dan transaksi digital.</li>
                <li>QR Code pada sisi depan dapat dipindai langsung untuk validasi keabsahan data resmi online.</li>
                <li>Dilarang memindahtangankan atau menyalahgunakan kartu ini.</li>
                <li>Jika kartu hilang atau ditemukan, mohon segera hubungi pihak sekolah.</li>
            </ol>
        </div>

        <!-- Barcode NISN & Kontak Sekolah -->
        <div class="kp-back-bottom">
            <div class="kp-barcode-box">
                <div class="kp-barcode-svg">
                    {!! $card['barcode_svg'] !!}
                </div>
                <div class="kp-barcode-text">* {{ $card['nisn'] }} *</div>
            </div>

            <div class="kp-back-contact">
                <div><i class="fas fa-map-marker-alt"></i> {{ Str::limit($card['sekolah']['alamat'], 58) }}</div>
                <div>
                    @if(!empty($card['sekolah']['telepon']) && $card['sekolah']['telepon'] !== '-')
                        <span><i class="fas fa-phone"></i> {{ $card['sekolah']['telepon'] }}</span>
                    @endif
                    @if(!empty($card['sekolah']['website']))
                        <span style="margin-left: 6px;"><i class="fas fa-globe"></i> {{ str_replace(['http://', 'https://'], '', $card['sekolah']['website']) }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
