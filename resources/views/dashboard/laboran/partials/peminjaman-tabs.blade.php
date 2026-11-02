{{--
    Tab halaman PEMINJAMAN: satu halaman untuk semua peminjaman.
      • Peminjaman            → laboran_peminjaman (ruang/bangunan, alat, fasilitas, aset)
      • Sarana Kegiatan Rapat → ceklist persiapan rapat (Tupoksi #4), terhubung Jurnal Pelaksanaan

    Dipakai oleh `dashboard/laboran/peminjaman.blade.php` (langsung) dan
    `dashboard/teknisi/sarana-rapat.blade.php` (via slot `tabnav`).
--}}
@php
    $pjTab = request()->routeIs('dashboard.teknisi.sarana-rapat*') ? 'rapat' : 'peminjaman';
@endphp
<div class="periode-nav-wrapper">
    <div class="periode-nav-desktop">
        <a href="{{ route('dashboard.laboran.peminjaman-alat.index') }}"
            class="periode-nav-tab {{ $pjTab === 'peminjaman' ? 'active' : '' }}">
            <i class="fas fa-hand-holding"></i> Peminjaman
        </a>
        <a href="{{ route('dashboard.teknisi.sarana-rapat.index') }}"
            class="periode-nav-tab {{ $pjTab === 'rapat' ? 'active' : '' }}">
            <i class="fas fa-people-roof"></i> Sarana Kegiatan Rapat
        </a>
    </div>
</div>
