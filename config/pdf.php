<?php

/*
|--------------------------------------------------------------------------
| Kompresi PDF (Ghostscript)
|--------------------------------------------------------------------------
| PHP tidak bisa mengompres PDF tanpa alat eksternal. Bila Ghostscript
| terpasang, berkas PDF yang diunggah diperkecil otomatis (downsample gambar
| di dalamnya). Bila tidak ada, PDF disimpan apa adanya — tidak error.
|
| Butuh binari: gswin64c.exe (Windows), gs (Linux/macOS).
*/
return [
    // Path binari eksplisit; kosong = cari otomatis di PATH & lokasi umum.
    'binary' => env('PDF_GHOSTSCRIPT_BINARY', ''),

    // Nonaktifkan kompresi PDF (mis. server tanpa Ghostscript & tidak ingin probing).
    'enabled' => env('PDF_COMPRESS_ENABLED', true),

    // Profil kualitas Ghostscript:
    //   /screen  72dpi  — kecil, teks bisa kabur
    //   /ebook   150dpi — seimbang (default, cocok scan dokumen siswa)
    //   /printer 300dpi — besar, kualitas cetak
    'settings' => env('PDF_COMPRESS_SETTINGS', '/ebook'),

    // Batas waktu proses (detik) agar unggahan tak menggantung.
    'timeout' => (int) env('PDF_COMPRESS_TIMEOUT', 60),

    // Hanya simpan hasil kompres bila setidaknya sebesar ini lebih kecil (persen).
    // Menghindari penggantian berkas yang untungnya tipis.
    'min_savings_percent' => 5,
];
