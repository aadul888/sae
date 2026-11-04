<!-- SISI BELAKANG (BACK - PORTRAIT: 56mm x 87mm) -->
<div class="kp-card kp-card-back" id="card-back-{{ $card['identifier'] }}">
    <div class="kp-bg-decor kp-bg-decor-back"></div>

    <!-- Header Belakang -->
    <div class="kp-back-header">
        <div class="kp-back-title">KARTU TANDA PEGAWAI DIGITAL</div>
        <div class="kp-back-school">{{ $card['sekolah']['nama'] }}</div>
    </div>

    <div class="kp-divider kp-divider-back"></div>

    <!-- Ketentuan Penggunaan -->
    <div class="kp-back-content">
        <div class="kp-terms-title">KETENTUAN PENGGUNAAN:</div>
        <ol class="kp-terms-list">
            <li>Kartu ini adalah identitas resmi Pegawai di lingkungan {{ $card['sekolah']['nama'] }}.</li>
            <li>Wajib dibawa selama berdinas untuk presensi kehadiran, akses kerja, dan layanan administratif.</li>
            <li>QR Code pada sisi depan dapat dipindai untuk validasi keabsahan status kepegawaian secara online.</li>
            <li>Dilarang memindahtangankan atau menyalahgunakan kartu ini kepada pihak lain.</li>
            <li>Jika kartu hilang atau ditemukan, mohon hubungi Bagian Tata Usaha / Kepegawaian sekolah.</li>
        </ol>
    </div>

    <!-- Barcode NIP / NUPTK / Kode & Kontak Sekolah -->
    <div class="kp-back-bottom">
        <div class="kp-barcode-box">
            <div class="kp-barcode-svg">
                {!! $card['barcode_svg'] !!}
            </div>
            <div class="kp-barcode-text">* {{ $card['barcode_text'] }} *</div>
        </div>

        <div class="kp-back-contact">
            <div><i class="fas fa-map-marker-alt"></i> {{ Str::limit($card['sekolah']['alamat'], 58) }}</div>
            <div>
                @if (!empty($card['sekolah']['telepon']) && $card['sekolah']['telepon'] !== '-')
                    <span><i class="fas fa-phone"></i> {{ $card['sekolah']['telepon'] }}</span>
                @endif
                @if (!empty($card['sekolah']['website']))
                    <span style="margin-left: 6px;"><i class="fas fa-globe"></i>
                        {{ str_replace(['http://', 'https://'], '', $card['sekolah']['website']) }}</span>
                @endif
            </div>
        </div>
    </div>
</div>
