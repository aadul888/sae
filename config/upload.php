<?php

/*
|--------------------------------------------------------------------------
| Batas Ukuran Unggahan (KB)
|--------------------------------------------------------------------------
| Pagar pengaman global untuk SEMUA berkas unggahan. Tanpa alat eksternal
| (Ghostscript/qpdf) PDF tidak bisa dikompres di PHP, jadi batas ukuran adalah
| jaring terakhir agar penyimpanan server tidak digenangi berkas raksasa —
| terutama bila ada endpoint baru yang lupa menulis aturan `max:` sendiri.
|
| `absolute_max_kb` sengaja lebih longgar daripada aturan `max:` milik tiap
| controller (yang lebih spesifik), supaya pagar ini TIDAK PERNAH menolak
| unggahan yang sebenarnya sah. `exempt_fields` untuk berkas yang memang
| boleh besar (mis. arsip restore database, admin-only).
*/
return [
    'absolute_max_kb' => 32768, // 32 MB

    'exempt_fields' => [
        'restore_file', // arsip .sql/.zip restore database (admin, sampai 500 MB)
    ],
];
