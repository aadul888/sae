<?php

// Rute modul administrasi tendik. Dipanggil dari dalam grup dashboard-core.php.
return function () {
    // =========================================================================
    // MODUL ADMINISTRASI BARU: SARPRAS, LABORAN, KEAMANAN, PENJAGA, PIKET
    // =========================================================================

    // 1. Sarpras & Aset
    Route::prefix('sarpras')->name('sarpras.')->group(function () {
        // Ruang & Gedung
        Route::get('/ruang', [\App\Http\Controllers\SarprasRuangController::class, 'index'])->name('ruang.index')->middleware('permission:menu_sarpras|menu_sarpras_ruang,read');
        Route::get('/ruang/export', [\App\Http\Controllers\SarprasRuangController::class, 'exportExcel'])->name('ruang.export')->middleware('permission:menu_sarpras|menu_sarpras_ruang,read');
        // Ruang & Gedung: tambah/ubah/hapus boleh pengelola Sarpras (menu_sarpras),
        // Teknisi, dan tenaga Sarpras lain lewat menu_sarpras_ruang.
        Route::post('/ruang', [\App\Http\Controllers\SarprasRuangController::class, 'store'])->name('ruang.store')->middleware('permission:menu_sarpras|menu_sarpras_ruang,create');
        Route::put('/ruang/{id}', [\App\Http\Controllers\SarprasRuangController::class, 'update'])->name('ruang.update')->middleware('permission:menu_sarpras|menu_sarpras_ruang,update');
        Route::delete('/ruang/{id}', [\App\Http\Controllers\SarprasRuangController::class, 'destroy'])->name('ruang.destroy')->middleware('permission:menu_sarpras|menu_sarpras_ruang,delete');

        // Inventaris & Aset — Teknisi & tenaga Sarpras lain memakai menu_inventaris
        // dengan hak penuh (CRUD) seperti pengelola Sarpras (menu_sarpras) dan Laboran.
        Route::get('/aset', [\App\Http\Controllers\SarprasAsetController::class, 'index'])->name('aset.index')->middleware('permission:menu_sarpras|menu_inventaris|menu_laboran,read');
        Route::get('/aset/export', [\App\Http\Controllers\SarprasAsetController::class, 'exportExcel'])->name('aset.export')->middleware('permission:menu_sarpras|menu_inventaris|menu_laboran,read');
        Route::get('/aset/template', [\App\Http\Controllers\SarprasAsetController::class, 'templateExcel'])->name('aset.template')->middleware('permission:menu_sarpras|menu_inventaris|menu_laboran,read');
        Route::post('/aset/import', [\App\Http\Controllers\SarprasAsetController::class, 'importExcel'])->name('aset.import')->middleware('permission:menu_sarpras|menu_inventaris|menu_laboran,create');
        Route::post('/aset', [\App\Http\Controllers\SarprasAsetController::class, 'store'])->name('aset.store')->middleware('permission:menu_sarpras|menu_inventaris|menu_laboran,create');
        Route::put('/aset/{id}', [\App\Http\Controllers\SarprasAsetController::class, 'update'])->name('aset.update')->middleware('permission:menu_sarpras|menu_inventaris|menu_laboran,update');
        Route::delete('/aset/{id}', [\App\Http\Controllers\SarprasAsetController::class, 'destroy'])->name('aset.destroy')->middleware('permission:menu_sarpras|menu_inventaris|menu_laboran,delete');

        // Peminjaman Sarpras (dialihkan ke modul peminjaman alat terpadu)
        Route::get('/peminjaman', function () {
            return redirect()->route('dashboard.laboran.peminjaman-alat.index');
        })->name('peminjaman.index');
        Route::post('/peminjaman', [\App\Http\Controllers\Laboran\LaboranPeminjamanController::class, 'store'])->name('peminjaman.store')->middleware('permission:menu_sarpras|menu_laboran,create');
        Route::put('/peminjaman/{id}', [\App\Http\Controllers\Laboran\LaboranPeminjamanController::class, 'updateStatus'])->name('peminjaman.update')->middleware('permission:menu_sarpras|menu_laboran,update');
        Route::post('/peminjaman/{id}/kembali', [\App\Http\Controllers\Laboran\LaboranPeminjamanController::class, 'kembalikan'])->name('peminjaman.kembali')->middleware('permission:menu_sarpras|menu_laboran,update');
        Route::post('/peminjaman/{id}/kembalikan', [\App\Http\Controllers\Laboran\LaboranPeminjamanController::class, 'kembalikan'])->middleware('permission:menu_sarpras|menu_laboran,update');
        Route::delete('/peminjaman/{id}', [\App\Http\Controllers\Laboran\LaboranPeminjamanController::class, 'destroy'])->name('peminjaman.destroy')->middleware('permission:menu_sarpras|menu_laboran,delete');

        // Kartu Inventaris Ruangan (KIR)
        Route::get('/kir', [\App\Http\Controllers\SarprasKirController::class, 'index'])->name('kir.index')->middleware('permission:menu_sarpras|menu_sarpras_ruang,read');
        Route::get('/kir/{id}/cetak', [\App\Http\Controllers\SarprasKirController::class, 'cetak'])->name('kir.cetak')->middleware('permission:menu_sarpras|menu_sarpras_ruang,read');
        Route::get('/kir/{id}', [\App\Http\Controllers\SarprasKirController::class, 'show'])->name('kir.show')->middleware('permission:menu_sarpras|menu_sarpras_ruang,read');

        // Barang Habis Pakai & ATK (Persediaan & Mutasi)
        Route::get('/persediaan', [\App\Http\Controllers\SarprasPersediaanController::class, 'index'])->name('persediaan.index')->middleware('permission:menu_sarpras|menu_inventaris,read');
        Route::post('/persediaan', [\App\Http\Controllers\SarprasPersediaanController::class, 'store'])->name('persediaan.store')->middleware('permission:menu_sarpras|menu_inventaris,create');
        Route::put('/persediaan/{id}', [\App\Http\Controllers\SarprasPersediaanController::class, 'update'])->name('persediaan.update')->middleware('permission:menu_sarpras|menu_inventaris,update');
        Route::delete('/persediaan/{id}', [\App\Http\Controllers\SarprasPersediaanController::class, 'destroy'])->name('persediaan.destroy')->middleware('permission:menu_sarpras|menu_inventaris,delete');
        Route::post('/persediaan/{id}/mutasi', [\App\Http\Controllers\SarprasPersediaanController::class, 'mutasi'])->name('persediaan.mutasi')->middleware('permission:menu_sarpras|menu_inventaris,create');

        // Buku Induk Inventaris & Laporan Sarpras
        Route::get('/laporan', [\App\Http\Controllers\SarprasLaporanController::class, 'index'])->name('laporan.index')->middleware('permission:menu_sarpras|menu_inventaris,read');
        Route::get('/laporan/cetak', [\App\Http\Controllers\SarprasLaporanController::class, 'cetak'])->name('laporan.cetak')->middleware('permission:menu_sarpras|menu_inventaris,read');
    });

    // 2. Laboratorium & Laboran
    Route::prefix('laboran')->name('laboran.')->group(function () {
        // Alat & Bahan Lab
        Route::get('/inventaris', [\App\Http\Controllers\LaboranInventarisController::class, 'index'])->name('inventaris.index')->middleware('permission:menu_laboran|menu_laboran_inventaris,read');
        Route::get('/inventaris/cetak', [\App\Http\Controllers\LaboranInventarisController::class, 'cetak'])->name('inventaris.cetak')->middleware('permission:menu_laboran|menu_laboran_inventaris,read');
        Route::get('/inventaris/template', [\App\Http\Controllers\LaboranInventarisController::class, 'templateExcel'])->name('inventaris.template')->middleware('permission:menu_laboran|menu_laboran_inventaris,read');
        Route::post('/inventaris/import', [\App\Http\Controllers\LaboranInventarisController::class, 'importExcel'])->name('inventaris.import')->middleware('permission:menu_laboran|menu_laboran_inventaris,create');
        Route::post('/inventaris', [\App\Http\Controllers\LaboranInventarisController::class, 'store'])->name('inventaris.store')->middleware('permission:menu_laboran|menu_laboran_inventaris,create');
        Route::put('/inventaris/{id}', [\App\Http\Controllers\LaboranInventarisController::class, 'update'])->name('inventaris.update')->middleware('permission:menu_laboran|menu_laboran_inventaris,update');
        Route::delete('/inventaris/{id}', [\App\Http\Controllers\LaboranInventarisController::class, 'destroy'])->name('inventaris.destroy')->middleware('permission:menu_laboran|menu_laboran_inventaris,delete');
        Route::post('/inventaris/bulk-delete', [\App\Http\Controllers\LaboranInventarisController::class, 'destroyBulk'])->name('inventaris.bulk-delete')->middleware('permission:menu_laboran|menu_laboran_inventaris,delete');

        // Bahan Habis Pakai (BHP) — modul terpisah, jenis=bahan_habis_pakai
        Route::get('/bhp', [\App\Http\Controllers\Laboran\LaboranBhpController::class, 'index'])->name('bhp.index')->middleware('permission:menu_laboran|menu_laboran_inventaris,read');
        Route::get('/bhp/cetak', [\App\Http\Controllers\Laboran\LaboranBhpController::class, 'cetak'])->name('bhp.cetak')->middleware('permission:menu_laboran|menu_laboran_inventaris,read');
        Route::get('/bhp/template', [\App\Http\Controllers\Laboran\LaboranBhpController::class, 'templateExcel'])->name('bhp.template')->middleware('permission:menu_laboran|menu_laboran_inventaris,read');
        Route::post('/bhp/import', [\App\Http\Controllers\Laboran\LaboranBhpController::class, 'importExcel'])->name('bhp.import')->middleware('permission:menu_laboran|menu_laboran_inventaris,create');
        Route::post('/bhp', [\App\Http\Controllers\Laboran\LaboranBhpController::class, 'store'])->name('bhp.store')->middleware('permission:menu_laboran|menu_laboran_inventaris,create');
        Route::put('/bhp/{id}', [\App\Http\Controllers\Laboran\LaboranBhpController::class, 'update'])->name('bhp.update')->middleware('permission:menu_laboran|menu_laboran_inventaris,update');
        Route::delete('/bhp/{id}', [\App\Http\Controllers\Laboran\LaboranBhpController::class, 'destroy'])->name('bhp.destroy')->middleware('permission:menu_laboran|menu_laboran_inventaris,delete');
        Route::post('/bhp/bulk-delete', [\App\Http\Controllers\Laboran\LaboranBhpController::class, 'destroyBulk'])->name('bhp.bulk-delete')->middleware('permission:menu_laboran|menu_laboran_inventaris,delete');

        // Jadwal & Penggunaan Lab / Bengkel / Lahan / Lapangan Praktik
        Route::get('/jadwal', [\App\Http\Controllers\LaboranJadwalController::class, 'index'])->name('jadwal.index')->middleware('permission:menu_laboran|menu_laboran_inventaris,read');
        Route::post('/jadwal', [\App\Http\Controllers\LaboranJadwalController::class, 'store'])->name('jadwal.store')->middleware('permission:menu_laboran|menu_laboran_inventaris,create');
        Route::put('/jadwal/{id}', [\App\Http\Controllers\LaboranJadwalController::class, 'update'])->name('jadwal.update')->middleware('permission:menu_laboran|menu_laboran_inventaris,update');
        Route::delete('/jadwal/{id}', [\App\Http\Controllers\LaboranJadwalController::class, 'destroy'])->name('jadwal.destroy')->middleware('permission:menu_laboran|menu_laboran_inventaris,delete');
        // Mata pelajaran otomatis dari kelas (data pembelajaran Dapodik)
        Route::get('/jadwal/pembelajaran', [\App\Http\Controllers\LaboranJadwalController::class, 'pembelajaranByRombel'])->name('jadwal.pembelajaran')->middleware('permission:menu_laboran|menu_laboran_inventaris,read');
        // Slot jadwal KBM aktif kelas (auto-fill mapel/jam/guru)
        Route::get('/jadwal/kbm', [\App\Http\Controllers\LaboranJadwalController::class, 'kbmByRombel'])->name('jadwal.kbm')->middleware('permission:menu_laboran|menu_laboran_inventaris,read');

        // Laporan Penggunaan Lokasi Praktik (Bulan, Triwulan, Semester, Tahun)
        Route::get('/laporan', [\App\Http\Controllers\LaboranJadwalController::class, 'laporan'])->name('laporan.index')->middleware('permission:menu_laboran|menu_laboran_inventaris,read');
        Route::get('/laporan/cetak', [\App\Http\Controllers\LaboranJadwalController::class, 'cetakLaporan'])->name('laporan.cetak')->middleware('permission:menu_laboran|menu_laboran_inventaris,read');
        // Cetak jadwal praktik (mingguan / semester)
        Route::get('/jadwal/cetak', [\App\Http\Controllers\LaboranJadwalController::class, 'cetakJadwalPerGuru'])->name('jadwal.cetak')->middleware('permission:menu_laboran|menu_laboran_inventaris,read');
        // Pemberlakuan jadwal (aktifkan mode mingguan / semester)
        Route::post('/jadwal/pemberlakuan', [\App\Http\Controllers\LaboranJadwalController::class, 'togglePemberlakuan'])->name('jadwal.toggle-pemberlakuan')->middleware('permission:menu_laboran|menu_laboran_inventaris,update');

        // Daftar gabungan guru + mapel + kelas (dari pembelajaran semua rombel)
        Route::get('/jadwal/guru-mapel', [\App\Http\Controllers\LaboranJadwalController::class, 'guruMapelList'])->name('jadwal.guru-mapel')->middleware('permission:menu_laboran|menu_laboran_inventaris,read');

        // Daftar guru (GTK aktif) untuk memilih guru pendamping / tim pengajar
        Route::get('/jadwal/daftar-guru', [\App\Http\Controllers\LaboranJadwalController::class, 'daftarGuru'])->name('jadwal.daftar-guru')->middleware('permission:menu_laboran|menu_laboran_inventaris,read');

        // Slot jam pelajaran (JP) untuk alokasi waktu modal jadwal
        Route::get('/jadwal/slots', [\App\Http\Controllers\LaboranJadwalController::class, 'slotList'])->name('jadwal.slots')->middleware('permission:menu_laboran|menu_laboran_inventaris,read');

        // =====================================================================
        // MODUL LABORATORIUM PER JURUSAN (Kebutuhan, Perawatan, Ceklist,
        // Pengadaan, Berita Acara Kerusakan, Peminjaman, Laporan Lengkap)
        // Seluruh data dibatasi cakupan jurusan via App\Support\LaboranScope.
        // =====================================================================

        // Dashboard Laboratorium (informatif, per jurusan)
        Route::get('/dashboard', [\App\Http\Controllers\Laboran\LaboranDashboardController::class, 'index'])
            ->name('dashboard')->middleware('permission:menu_laboran,read');
        Route::get('/dashboard/cetak', [\App\Http\Controllers\Laboran\LaboranDashboardController::class, 'cetak'])
            ->name('dashboard.cetak')->middleware('permission:menu_laboran,read');

        // 0. Data Peserta Didik (baca-saja, dibatasi jurusan Laboran)
        Route::prefix('peserta-didik')->name('peserta-didik.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Laboran\LaboranPesertaDidikController::class, 'index'])
                ->name('index')->middleware('permission:menu_laboran,read');
            Route::get('/export', [\App\Http\Controllers\Laboran\LaboranPesertaDidikController::class, 'exportExcel'])
                ->name('export')->middleware('permission:menu_laboran,read');
            Route::get('/cetak', [\App\Http\Controllers\Laboran\LaboranPesertaDidikController::class, 'cetak'])
                ->name('cetak')->middleware('permission:menu_laboran,read');
            Route::get('/{id}', [\App\Http\Controllers\Laboran\LaboranPesertaDidikController::class, 'show'])
                ->name('show')->middleware('permission:menu_laboran,read');
        });

        // 1. Analisis & Input Kebutuhan Alat/Bahan
        Route::prefix('kebutuhan')->name('kebutuhan.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Laboran\LaboranKebutuhanController::class, 'index'])->name('index')->middleware('permission:menu_laboran,read');
            Route::post('/', [\App\Http\Controllers\Laboran\LaboranKebutuhanController::class, 'store'])->name('store')->middleware('permission:menu_laboran,create');
            // Analisis otomatis dari kondisi stok & kerusakan
            Route::post('/jalankan-analisis', [\App\Http\Controllers\Laboran\LaboranKebutuhanController::class, 'jalankanAnalisis'])->name('jalankan-analisis')->middleware('permission:menu_laboran,create');
            Route::get('/cetak', [\App\Http\Controllers\Laboran\LaboranKebutuhanController::class, 'cetak'])->name('cetak')->middleware('permission:menu_laboran,read');
            Route::put('/{id}', [\App\Http\Controllers\Laboran\LaboranKebutuhanController::class, 'update'])->name('update')->middleware('permission:menu_laboran,update');
            Route::delete('/{id}', [\App\Http\Controllers\Laboran\LaboranKebutuhanController::class, 'destroy'])->name('destroy')->middleware('permission:menu_laboran,delete');
        });

        // 2. Perawatan / Pemeliharaan / Penataan Alat (K3) + Kartu Perawatan
        Route::prefix('perawatan')->name('perawatan.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Laboran\LaboranPerawatanController::class, 'index'])->name('index')->middleware('permission:menu_laboran,read');
            Route::post('/', [\App\Http\Controllers\Laboran\LaboranPerawatanController::class, 'store'])->name('store')->middleware('permission:menu_laboran,create');
            Route::get('/cetak', [\App\Http\Controllers\Laboran\LaboranPerawatanController::class, 'cetak'])->name('cetak')->middleware('permission:menu_laboran,read');
            Route::put('/{id}', [\App\Http\Controllers\Laboran\LaboranPerawatanController::class, 'update'])->name('update')->middleware('permission:menu_laboran,update');
            Route::delete('/{id}', [\App\Http\Controllers\Laboran\LaboranPerawatanController::class, 'destroy'])->name('destroy')->middleware('permission:menu_laboran,delete');

            // Kartu perawatan
            Route::post('/kartu', [\App\Http\Controllers\Laboran\LaboranPerawatanController::class, 'storeKartu'])->name('kartu.store')->middleware('permission:menu_laboran,create');
            Route::put('/kartu/{id}', [\App\Http\Controllers\Laboran\LaboranPerawatanController::class, 'updateKartu'])->name('kartu.update')->middleware('permission:menu_laboran,update');
            Route::delete('/kartu/{id}', [\App\Http\Controllers\Laboran\LaboranPerawatanController::class, 'destroyKartu'])->name('kartu.destroy')->middleware('permission:menu_laboran,delete');
            Route::post('/kartu/{id}/selesai', [\App\Http\Controllers\Laboran\LaboranPerawatanController::class, 'tandaiKartuSelesai'])->name('kartu.selesai')->middleware('permission:menu_laboran,update');
        });

        // 4. Pemeriksaan / Daftar Ceklist Alat & Bahan
        Route::prefix('pemeriksaan')->name('pemeriksaan.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Laboran\LaboranPemeriksaanController::class, 'index'])->name('index')->middleware('permission:menu_laboran,read');
            Route::post('/', [\App\Http\Controllers\Laboran\LaboranPemeriksaanController::class, 'store'])->name('store')->middleware('permission:menu_laboran,create');
            Route::get('/cetak', [\App\Http\Controllers\Laboran\LaboranPemeriksaanController::class, 'cetak'])->name('cetak')->middleware('permission:menu_laboran,read');
            Route::get('/{id}', [\App\Http\Controllers\Laboran\LaboranPemeriksaanController::class, 'show'])->name('show')->middleware('permission:menu_laboran,read');
            Route::put('/{id}', [\App\Http\Controllers\Laboran\LaboranPemeriksaanController::class, 'update'])->name('update')->middleware('permission:menu_laboran,update');
            Route::delete('/{id}', [\App\Http\Controllers\Laboran\LaboranPemeriksaanController::class, 'destroy'])->name('destroy')->middleware('permission:menu_laboran,delete');
            Route::post('/{id}/simpan-item', [\App\Http\Controllers\Laboran\LaboranPemeriksaanController::class, 'simpanItem'])->name('simpan-item')->middleware('permission:menu_laboran,update');
            Route::post('/{id}/tambah-item', [\App\Http\Controllers\Laboran\LaboranPemeriksaanController::class, 'tambahItem'])->name('tambah-item')->middleware('permission:menu_laboran,update');
            Route::delete('/{id}/item/{itemId}', [\App\Http\Controllers\Laboran\LaboranPemeriksaanController::class, 'hapusItem'])->name('hapus-item')->middleware('permission:menu_laboran,update');
        });

        // 5. Daftar Usulan Pengadaan Alat & Bahan Praktik
        Route::prefix('pengadaan')->name('pengadaan.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Laboran\LaboranPengadaanController::class, 'index'])->name('index')->middleware('permission:menu_laboran,read');
            Route::post('/', [\App\Http\Controllers\Laboran\LaboranPengadaanController::class, 'store'])->name('store')->middleware('permission:menu_laboran,create');
            Route::get('/cetak', [\App\Http\Controllers\Laboran\LaboranPengadaanController::class, 'cetak'])->name('cetak')->middleware('permission:menu_laboran,read');
            Route::get('/{id}', [\App\Http\Controllers\Laboran\LaboranPengadaanController::class, 'show'])->name('show')->middleware('permission:menu_laboran,read');
            Route::put('/{id}', [\App\Http\Controllers\Laboran\LaboranPengadaanController::class, 'update'])->name('update')->middleware('permission:menu_laboran,update');
            Route::delete('/{id}', [\App\Http\Controllers\Laboran\LaboranPengadaanController::class, 'destroy'])->name('destroy')->middleware('permission:menu_laboran,delete');
            Route::post('/{id}/item', [\App\Http\Controllers\Laboran\LaboranPengadaanController::class, 'tambahItem'])->name('item.store')->middleware('permission:menu_laboran,update');
            Route::put('/{id}/item/{itemId}', [\App\Http\Controllers\Laboran\LaboranPengadaanController::class, 'updateItem'])->name('item.update')->middleware('permission:menu_laboran,update');
            Route::delete('/{id}/item/{itemId}', [\App\Http\Controllers\Laboran\LaboranPengadaanController::class, 'hapusItem'])->name('item.destroy')->middleware('permission:menu_laboran,update');
        });

        // 6. Berita Acara Kerusakan Alat
        Route::prefix('kerusakan')->name('kerusakan.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Laboran\LaboranKerusakanController::class, 'index'])->name('index')->middleware('permission:menu_laboran,read');
            Route::post('/', [\App\Http\Controllers\Laboran\LaboranKerusakanController::class, 'store'])->name('store')->middleware('permission:menu_laboran,create');
            Route::put('/{id}', [\App\Http\Controllers\Laboran\LaboranKerusakanController::class, 'update'])->name('update')->middleware('permission:menu_laboran,update');
            Route::delete('/{id}', [\App\Http\Controllers\Laboran\LaboranKerusakanController::class, 'destroy'])->name('destroy')->middleware('permission:menu_laboran,delete');
            Route::get('/{id}/cetak', [\App\Http\Controllers\Laboran\LaboranKerusakanController::class, 'cetak'])->name('cetak')->middleware('permission:menu_laboran,read');
        });

        // 7. Peminjaman Alat Khusus Jurusan / Laboran
        Route::prefix('peminjaman-alat')->name('peminjaman-alat.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Laboran\LaboranPeminjamanController::class, 'index'])->name('index')->middleware('permission:menu_laboran,read');
            Route::get('/template', [\App\Http\Controllers\Laboran\LaboranPeminjamanController::class, 'templateExcel'])->name('template')->middleware('permission:menu_laboran|menu_laboran_peminjaman,read');
            Route::post('/import', [\App\Http\Controllers\Laboran\LaboranPeminjamanController::class, 'importExcel'])->name('import')->middleware('permission:menu_laboran|menu_laboran_peminjaman,create');
            Route::post('/', [\App\Http\Controllers\Laboran\LaboranPeminjamanController::class, 'store'])->name('store')->middleware('permission:menu_laboran,create');
            Route::get('/cetak', [\App\Http\Controllers\Laboran\LaboranPeminjamanController::class, 'cetak'])->name('cetak')->middleware('permission:menu_laboran,read');
            Route::get('/{id}', [\App\Http\Controllers\Laboran\LaboranPeminjamanController::class, 'show'])->name('show')->middleware('permission:menu_laboran,read');
            Route::put('/{id}/status', [\App\Http\Controllers\Laboran\LaboranPeminjamanController::class, 'updateStatus'])->name('status')->middleware('permission:menu_laboran,update');
            Route::post('/{id}/kembalikan', [\App\Http\Controllers\Laboran\LaboranPeminjamanController::class, 'kembalikan'])->name('kembalikan')->middleware('permission:menu_laboran,update');
            Route::delete('/{id}', [\App\Http\Controllers\Laboran\LaboranPeminjamanController::class, 'destroy'])->name('destroy')->middleware('permission:menu_laboran,delete');
            Route::post('/tandai-terlambat', [\App\Http\Controllers\Laboran\LaboranPeminjamanController::class, 'tandaiTerlambat'])->name('tandai-terlambat')->middleware('permission:menu_laboran,update');
        });

        // 8. Laporan Lengkap Penggunaan & Kerusakan Alat
        Route::prefix('laporan-lengkap')->name('laporan-lengkap.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Laboran\LaboranLaporanController::class, 'index'])->name('index')->middleware('permission:menu_laboran,read');
            Route::get('/cetak', [\App\Http\Controllers\Laboran\LaboranLaporanController::class, 'cetak'])->name('cetak')->middleware('permission:menu_laboran,read');
        });

        // =====================================================================
        // KELENGKAPAN TUPOKSI LABORAN (9 Indikator Supervisi)
        // =====================================================================


        // Indikator 7 — Arsip Dokumen (Ordner Digital)
        Route::prefix('arsip')->name('arsip.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Laboran\LaboranArsipController::class, 'index'])->name('index')->middleware('permission:menu_laboran,read');
            Route::post('/', [\App\Http\Controllers\Laboran\LaboranArsipController::class, 'store'])->name('store')->middleware('permission:menu_laboran,create');
            Route::post('/arsipkan-massal', [\App\Http\Controllers\Laboran\LaboranArsipController::class, 'arsipkanMassal'])->name('arsipkan-massal')->middleware('permission:menu_laboran,create');
            Route::get('/cetak', [\App\Http\Controllers\Laboran\LaboranArsipController::class, 'cetak'])->name('cetak')->middleware('permission:menu_laboran,read');
            Route::get('/riwayat/{id}/cetak', [\App\Http\Controllers\Laboran\LaboranArsipController::class, 'cetakUlang'])->name('cetak-ulang')->middleware('permission:menu_laboran,read');
            Route::put('/{id}', [\App\Http\Controllers\Laboran\LaboranArsipController::class, 'update'])->name('update')->middleware('permission:menu_laboran,update');
            Route::delete('/{id}', [\App\Http\Controllers\Laboran\LaboranArsipController::class, 'destroy'])->name('destroy')->middleware('permission:menu_laboran,delete');
        });

        // Indikator 8 — Jurnal Kegiatan Harian
        Route::prefix('jurnal')->name('jurnal.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Laboran\LaboranJurnalController::class, 'index'])->name('index')->middleware('permission:menu_laboran,read');
            Route::post('/', [\App\Http\Controllers\Laboran\LaboranJurnalController::class, 'store'])->name('store')->middleware('permission:menu_laboran,create');
            Route::put('/{id}', [\App\Http\Controllers\Laboran\LaboranJurnalController::class, 'update'])->name('update')->middleware('permission:menu_laboran,update');
            Route::delete('/{id}', [\App\Http\Controllers\Laboran\LaboranJurnalController::class, 'destroy'])->name('destroy')->middleware('permission:menu_laboran,delete');
            Route::get('/cetak', [\App\Http\Controllers\Laboran\LaboranJurnalController::class, 'cetak'])->name('cetak')->middleware('permission:menu_laboran,read');
        });

        // Indikator 9 — Kebersihan Lingkungan Produktif
        Route::prefix('kebersihan')->name('kebersihan.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Laboran\LaboranKebersihanController::class, 'index'])->name('index')->middleware('permission:menu_laboran,read');
            Route::get('/template', [\App\Http\Controllers\Laboran\LaboranKebersihanController::class, 'templateExcel'])->name('template')->middleware('permission:menu_laboran|menu_laboran_kebersihan,read');
            Route::post('/import', [\App\Http\Controllers\Laboran\LaboranKebersihanController::class, 'importExcel'])->name('import')->middleware('permission:menu_laboran|menu_laboran_kebersihan,create');
            Route::post('/', [\App\Http\Controllers\Laboran\LaboranKebersihanController::class, 'store'])->name('store')->middleware('permission:menu_laboran,create');
            Route::put('/{id}', [\App\Http\Controllers\Laboran\LaboranKebersihanController::class, 'update'])->name('update')->middleware('permission:menu_laboran,update');
            Route::delete('/{id}', [\App\Http\Controllers\Laboran\LaboranKebersihanController::class, 'destroy'])->name('destroy')->middleware('permission:menu_laboran,delete');
            Route::get('/cetak', [\App\Http\Controllers\Laboran\LaboranKebersihanController::class, 'cetak'])->name('cetak')->middleware('permission:menu_laboran,read');
        });
    });

    // 3. Keamanan / Satpam Pos Gerbang
    Route::prefix('keamanan')->name('keamanan.')->group(function () {
        // Verifikasi e-Izin Gerbang Siswa
        Route::get('/izin', [\App\Http\Controllers\KeamananIzinController::class, 'index'])->name('izin.index')->middleware('permission:menu_keamanan|menu_keamanan_izin,read');
        Route::get('/izin/verifikasi', [\App\Http\Controllers\KeamananIzinController::class, 'verifikasiTiket'])->name('izin.verifikasi');
        Route::post('/izin/checkout/{id}', [\App\Http\Controllers\KeamananIzinController::class, 'checkout'])->name('izin.checkout')->middleware('permission:menu_keamanan|menu_keamanan_izin,update');
        Route::post('/izin/checkin/{id}', [\App\Http\Controllers\KeamananIzinController::class, 'checkin'])->name('izin.checkin')->middleware('permission:menu_keamanan|menu_keamanan_izin,update');

        // Monitoring Presensi Siswa Gerbang
        Route::get('/presensi', [\App\Http\Controllers\KeamananPresensiController::class, 'index'])->name('presensi.index')->middleware('permission:menu_keamanan|menu_keamanan_presensi,read');

        // Buku Tamu Pos Keamanan
        Route::get('/buku-tamu', [\App\Http\Controllers\KeamananBukuTamuController::class, 'index'])->name('buku-tamu.index')->middleware('permission:menu_buku_tamu,read');
        Route::post('/buku-tamu', [\App\Http\Controllers\KeamananBukuTamuController::class, 'store'])->name('buku-tamu.store')->middleware('permission:menu_buku_tamu,create');
        Route::put('/buku-tamu/{id}', [\App\Http\Controllers\KeamananBukuTamuController::class, 'update'])->name('buku-tamu.update')->middleware('permission:menu_buku_tamu,update');
        Route::post('/buku-tamu/{id}/checkout', [\App\Http\Controllers\KeamananBukuTamuController::class, 'checkout'])->name('buku-tamu.checkout')->middleware('permission:menu_buku_tamu,update');
        Route::delete('/buku-tamu/{id}', [\App\Http\Controllers\KeamananBukuTamuController::class, 'destroy'])->name('buku-tamu.destroy')->middleware('permission:menu_buku_tamu,delete');

        // Patroli & Insiden Keamanan
        Route::get('/patroli', [\App\Http\Controllers\KeamananPatroliController::class, 'index'])->name('patroli.index')->middleware('permission:menu_keamanan|menu_keamanan_patroli,read');
        Route::post('/patroli', [\App\Http\Controllers\KeamananPatroliController::class, 'storePatroli'])->name('patroli.store')->middleware('permission:menu_keamanan|menu_keamanan_patroli,create');
        Route::put('/patroli/{id}', [\App\Http\Controllers\KeamananPatroliController::class, 'updatePatroli'])->name('patroli.update')->middleware('permission:menu_keamanan|menu_keamanan_patroli,update');
        Route::delete('/patroli/{id}', [\App\Http\Controllers\KeamananPatroliController::class, 'destroyPatroli'])->name('patroli.destroy')->middleware('permission:menu_keamanan|menu_keamanan_patroli,delete');
        Route::post('/insiden', [\App\Http\Controllers\KeamananPatroliController::class, 'storeInsiden'])->name('insiden.store')->middleware('permission:menu_keamanan,create');
        Route::put('/insiden/{id}', [\App\Http\Controllers\KeamananPatroliController::class, 'updateInsiden'])->name('insiden.update')->middleware('permission:menu_keamanan,update');
        Route::delete('/insiden/{id}', [\App\Http\Controllers\KeamananPatroliController::class, 'destroyInsiden'])->name('insiden.destroy')->middleware('permission:menu_keamanan,delete');
    });

    // 4. Fasilitas & Penjaga Sekolah
    Route::prefix('penjaga')->name('penjaga.')->group(function () {
        // Checklist Kebersihan & Sanitasi
        Route::get('/kebersihan', [\App\Http\Controllers\PenjagaKebersihanController::class, 'index'])->name('kebersihan.index')->middleware('permission:menu_penjaga|menu_penjaga_kebersihan,read');
        Route::post('/kebersihan', [\App\Http\Controllers\PenjagaKebersihanController::class, 'store'])->name('kebersihan.store')->middleware('permission:menu_penjaga|menu_penjaga_kebersihan,create');
        Route::put('/kebersihan/{id}', [\App\Http\Controllers\PenjagaKebersihanController::class, 'update'])->name('kebersihan.update')->middleware('permission:menu_penjaga|menu_penjaga_kebersihan,update');
        Route::delete('/kebersihan/{id}', [\App\Http\Controllers\PenjagaKebersihanController::class, 'destroy'])->name('kebersihan.destroy')->middleware('permission:menu_penjaga|menu_penjaga_kebersihan,delete');

        // Buku Jaga Malam & Ronda
        Route::get('/ronda', [\App\Http\Controllers\PenjagaRondaController::class, 'index'])->name('ronda.index')->middleware('permission:menu_penjaga|menu_penjaga_ronda,read');
        Route::post('/ronda', [\App\Http\Controllers\PenjagaRondaController::class, 'store'])->name('ronda.store')->middleware('permission:menu_penjaga|menu_penjaga_ronda,create');
        Route::put('/ronda/{id}', [\App\Http\Controllers\PenjagaRondaController::class, 'update'])->name('ronda.update')->middleware('permission:menu_penjaga|menu_penjaga_ronda,update');
        Route::delete('/ronda/{id}', [\App\Http\Controllers\PenjagaRondaController::class, 'destroy'])->name('ronda.destroy')->middleware('permission:menu_penjaga|menu_penjaga_ronda,delete');
    });

    // 5. Piket Sekolah & e-Izin Keluar
    Route::prefix('piket')->name('piket.')->group(function () {
        // e-Izin Keluar-Masuk Siswa
        Route::get('/izin', [\App\Http\Controllers\PiketIzinController::class, 'index'])->name('izin.index')->middleware('permission:menu_piket,read');
        Route::get('/izin/search-siswa', [\App\Http\Controllers\PiketIzinController::class, 'searchSiswa'])->name('izin.search-siswa');
        Route::post('/izin', [\App\Http\Controllers\PiketIzinController::class, 'store'])->name('izin.store')->middleware('permission:menu_piket,create');
        Route::put('/izin/{id}', [\App\Http\Controllers\PiketIzinController::class, 'update'])->name('izin.update')->middleware('permission:menu_piket,update');
        Route::delete('/izin/{id}', [\App\Http\Controllers\PiketIzinController::class, 'destroy'])->name('izin.destroy')->middleware('permission:menu_piket,delete');
        Route::get('/izin/{id}/cetak', [\App\Http\Controllers\PiketIzinController::class, 'cetakSlip'])->name('izin.cetak')->middleware('permission:menu_piket,read');

        // Jurnal Guru Piket
        Route::get('/jurnal', [\App\Http\Controllers\PiketJurnalController::class, 'index'])->name('jurnal.index')->middleware('permission:menu_piket,read');
        Route::post('/jurnal', [\App\Http\Controllers\PiketJurnalController::class, 'store'])->name('jurnal.store')->middleware('permission:menu_piket,create');
        Route::put('/jurnal/{id}', [\App\Http\Controllers\PiketJurnalController::class, 'update'])->name('jurnal.update')->middleware('permission:menu_piket,update');
        Route::delete('/jurnal/{id}', [\App\Http\Controllers\PiketJurnalController::class, 'destroy'])->name('jurnal.destroy')->middleware('permission:menu_piket,delete');
    });

    // Pengajuan e-Izin Keluar Siswa Mandiri
    Route::post('/peserta-didik/izin/keluar', [\App\Http\Controllers\PesertaDidikIzinController::class, 'storeIzinKeluar'])->name('peserta-didik.izin.keluar.store');

    // 6. Perpustakaan (Koleksi, Sirkulasi, Kunjungan)
    Route::prefix('perpustakaan')->name('perpustakaan.')->group(function () {
        // Koleksi & Katalog Buku
        Route::get('/koleksi', [\App\Http\Controllers\PerpusKoleksiController::class, 'index'])->name('koleksi.index')->middleware('permission:menu_perpustakaan|menu_perpustakaan_koleksi,read');
        Route::post('/koleksi', [\App\Http\Controllers\PerpusKoleksiController::class, 'store'])->name('koleksi.store')->middleware('permission:menu_perpustakaan|menu_perpustakaan_koleksi,create');
        Route::put('/koleksi/{id}', [\App\Http\Controllers\PerpusKoleksiController::class, 'update'])->name('koleksi.update')->middleware('permission:menu_perpustakaan|menu_perpustakaan_koleksi,update');
        Route::delete('/koleksi/{id}', [\App\Http\Controllers\PerpusKoleksiController::class, 'destroy'])->name('koleksi.destroy')->middleware('permission:menu_perpustakaan|menu_perpustakaan_koleksi,delete');

        // Sirkulasi Peminjaman & Pengembalian
        Route::get('/sirkulasi', [\App\Http\Controllers\PerpusSirkulasiController::class, 'index'])->name('sirkulasi')->middleware('permission:menu_perpustakaan|menu_perpustakaan_sirkulasi,read');
        Route::post('/sirkulasi', [\App\Http\Controllers\PerpusSirkulasiController::class, 'store'])->name('sirkulasi.store')->middleware('permission:menu_perpustakaan|menu_perpustakaan_sirkulasi,create');
        Route::post('/sirkulasi/{id}/kembalikan', [\App\Http\Controllers\PerpusSirkulasiController::class, 'kembalikan'])->name('sirkulasi.kembalikan')->middleware('permission:menu_perpustakaan|menu_perpustakaan_sirkulasi,update');
        Route::delete('/sirkulasi/{id}', [\App\Http\Controllers\PerpusSirkulasiController::class, 'destroy'])->name('sirkulasi.destroy')->middleware('permission:menu_perpustakaan|menu_perpustakaan_sirkulasi,delete');

        // Buku Kunjungan
        Route::get('/kunjungan', [\App\Http\Controllers\PerpusKunjunganController::class, 'index'])->name('kunjungan')->middleware('permission:menu_perpustakaan|menu_perpustakaan_kunjungan,read');
        Route::post('/kunjungan', [\App\Http\Controllers\PerpusKunjunganController::class, 'store'])->name('kunjungan.store')->middleware('permission:menu_perpustakaan|menu_perpustakaan_kunjungan,create');
        Route::delete('/kunjungan/{id}', [\App\Http\Controllers\PerpusKunjunganController::class, 'destroy'])->name('kunjungan.destroy')->middleware('permission:menu_perpustakaan|menu_perpustakaan_kunjungan,delete');

        // Laporan & Rekapitulasi Perpustakaan
        Route::get('/laporan', [\App\Http\Controllers\PerpusLaporanController::class, 'index'])->name('laporan.index')->middleware('permission:menu_perpustakaan|menu_perpustakaan_laporan,read');
        Route::get('/laporan/cetak', [\App\Http\Controllers\PerpusLaporanController::class, 'cetak'])->name('laporan.cetak')->middleware('permission:menu_perpustakaan|menu_perpustakaan_laporan,read');
        Route::get('/laporan/tahun-ajaran', [\App\Http\Controllers\PerpusLaporanController::class, 'tahunAjaran'])->name('laporan.tahunan')->middleware('permission:menu_perpustakaan|menu_perpustakaan_laporan,read');
        Route::get('/laporan/tahun-ajaran/cetak', [\App\Http\Controllers\PerpusLaporanController::class, 'cetakTahunAjaran'])->name('laporan.tahunan.cetak')->middleware('permission:menu_perpustakaan|menu_perpustakaan_laporan,read');

        // Registrasi Anggota + ID Card Digital
        Route::get('/anggota', [\App\Http\Controllers\PerpusAnggotaController::class, 'index'])->name('anggota.index')->middleware('permission:menu_perpustakaan|menu_perpustakaan_anggota,read');
        Route::post('/anggota', [\App\Http\Controllers\PerpusAnggotaController::class, 'store'])->name('anggota.store')->middleware('permission:menu_perpustakaan|menu_perpustakaan_anggota,create');
        Route::put('/anggota/{id}', [\App\Http\Controllers\PerpusAnggotaController::class, 'update'])->name('anggota.update')->middleware('permission:menu_perpustakaan|menu_perpustakaan_anggota,update');
        Route::post('/anggota/{id}/toggle', [\App\Http\Controllers\PerpusAnggotaController::class, 'toggle'])->name('anggota.toggle')->middleware('permission:menu_perpustakaan|menu_perpustakaan_anggota,update');
        Route::delete('/anggota/{id}', [\App\Http\Controllers\PerpusAnggotaController::class, 'destroy'])->name('anggota.destroy')->middleware('permission:menu_perpustakaan|menu_perpustakaan_anggota,delete');
        Route::get('/anggota/cek-master', [\App\Http\Controllers\PerpusAnggotaController::class, 'cekMasterData'])->name('anggota.cek-master')->middleware('permission:menu_perpustakaan|menu_perpustakaan_anggota,read');
        Route::get('/anggota/kartu/cetak', [\App\Http\Controllers\PerpusAnggotaController::class, 'cetakKartu'])->name('anggota.kartu.cetak')->middleware('permission:menu_perpustakaan|menu_perpustakaan_anggota,read');
        Route::get('/anggota/{id}/kartu', [\App\Http\Controllers\PerpusAnggotaController::class, 'kartu'])->name('anggota.kartu')->middleware('permission:menu_perpustakaan|menu_perpustakaan_anggota,read');

        // Import Katalog & Input Cepat Scan Barcode
        Route::get('/import', [\App\Http\Controllers\PerpusImportController::class, 'form'])->name('import.form')->middleware('permission:menu_perpustakaan|menu_perpustakaan_koleksi,read');
        Route::get('/import/template', [\App\Http\Controllers\PerpusImportController::class, 'template'])->name('import.template')->middleware('permission:menu_perpustakaan|menu_perpustakaan_koleksi,read');
        Route::post('/import', [\App\Http\Controllers\PerpusImportController::class, 'import'])->name('import.process')->middleware('permission:menu_perpustakaan|menu_perpustakaan_koleksi,create');
        Route::post('/koleksi-scan', [\App\Http\Controllers\PerpusImportController::class, 'storeScan'])->name('koleksi.scan')->middleware('permission:menu_perpustakaan|menu_perpustakaan_koleksi,create');
    });

    // 7. Teknisi & Maintenance (Work Order & Pemeliharaan Preventif)
    Route::prefix('teknisi')->name('teknisi.')->group(function () {
        // Work Order & Tiket Perbaikan (Bangunan, Listrik, Air, AC, IT, Kebersihan)
        Route::get('/work-order', [\App\Http\Controllers\TeknisiWorkOrderController::class, 'index'])->name('work-order')->middleware('permission:menu_teknisi|menu_teknisi_wo,read');
        Route::post('/work-order', [\App\Http\Controllers\TeknisiWorkOrderController::class, 'store'])->name('work-order.store')->middleware('permission:menu_teknisi|menu_teknisi_wo,create');
        Route::post('/work-order/{id}/status', [\App\Http\Controllers\TeknisiWorkOrderController::class, 'updateStatus'])->name('work-order.status')->middleware('permission:menu_teknisi|menu_teknisi_wo,update');
        Route::delete('/work-order/{id}', [\App\Http\Controllers\TeknisiWorkOrderController::class, 'destroy'])->name('work-order.destroy')->middleware('permission:menu_teknisi|menu_teknisi_wo,delete');
        Route::get('/work-order/template', [\App\Http\Controllers\TeknisiWorkOrderController::class, 'template'])->name('work-order.template')->middleware('permission:menu_teknisi|menu_teknisi_wo,read');
        Route::post('/work-order/import', [\App\Http\Controllers\TeknisiWorkOrderController::class, 'import'])->name('work-order.import')->middleware('permission:menu_teknisi|menu_teknisi_wo,create');
        Route::get('/work-order/cetak', [\App\Http\Controllers\TeknisiWorkOrderController::class, 'cetak'])->name('work-order.cetak')->middleware('permission:menu_teknisi|menu_teknisi_wo,read');

        // Pemeliharaan Preventif (Maintenance Routine)
        Route::get('/pemeliharaan', [\App\Http\Controllers\TeknisiPemeliharaanController::class, 'index'])->name('pemeliharaan')->middleware('permission:menu_teknisi|menu_teknisi_pemeliharaan,read');
        Route::post('/pemeliharaan', [\App\Http\Controllers\TeknisiPemeliharaanController::class, 'store'])->name('pemeliharaan.store')->middleware('permission:menu_teknisi|menu_teknisi_pemeliharaan,create');
        Route::post('/pemeliharaan/{id}/status', [\App\Http\Controllers\TeknisiPemeliharaanController::class, 'updateStatus'])->name('pemeliharaan.status')->middleware('permission:menu_teknisi|menu_teknisi_pemeliharaan,update');
        Route::delete('/pemeliharaan/{id}', [\App\Http\Controllers\TeknisiPemeliharaanController::class, 'destroy'])->name('pemeliharaan.destroy')->middleware('permission:menu_teknisi|menu_teknisi_pemeliharaan,delete');
        Route::get('/pemeliharaan/template', [\App\Http\Controllers\TeknisiPemeliharaanController::class, 'template'])->name('pemeliharaan.template')->middleware('permission:menu_teknisi|menu_teknisi_pemeliharaan,read');
        Route::post('/pemeliharaan/import', [\App\Http\Controllers\TeknisiPemeliharaanController::class, 'import'])->name('pemeliharaan.import')->middleware('permission:menu_teknisi|menu_teknisi_pemeliharaan,create');

        // Hapus massal (bulk) seluruh datatable modul Teknisi
        Route::post('/bulk-delete', [\App\Http\Controllers\Teknisi\TeknisiBulkDeleteController::class, 'destroy'])
            ->name('bulk-delete')
            ->middleware('permission:menu_teknisi|menu_teknisi_wo|menu_teknisi_pemeliharaan,delete');

        // =====================================================================
        // KELENGKAPAN TUPOKSI TEKNISI (5 Indikator Supervisi)
        // =====================================================================

        // Indikator 1 — Administrasi & Jadwal Area Tugas Kebersihan
        Route::prefix('area-tugas')->name('area-tugas.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Teknisi\TeknisiAreaTugasController::class, 'index'])->name('index')->middleware('permission:menu_teknisi,read');
            Route::post('/', [\App\Http\Controllers\Teknisi\TeknisiAreaTugasController::class, 'store'])->name('store')->middleware('permission:menu_teknisi,create');
            Route::put('/{id}', [\App\Http\Controllers\Teknisi\TeknisiAreaTugasController::class, 'update'])->name('update')->middleware('permission:menu_teknisi,update');
            Route::delete('/{id}', [\App\Http\Controllers\Teknisi\TeknisiAreaTugasController::class, 'destroy'])->name('destroy')->middleware('permission:menu_teknisi,delete');
        });

        // Indikator 2 — Kebersihan Lingkungan & Ruang Kerja
        Route::prefix('kebersihan-ruang')->name('kebersihan-ruang.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Teknisi\TeknisiKebersihanRuangController::class, 'index'])->name('index')->middleware('permission:menu_teknisi,read');
            Route::post('/', [\App\Http\Controllers\Teknisi\TeknisiKebersihanRuangController::class, 'store'])->name('store')->middleware('permission:menu_teknisi,create');
            Route::put('/{id}', [\App\Http\Controllers\Teknisi\TeknisiKebersihanRuangController::class, 'update'])->name('update')->middleware('permission:menu_teknisi,update');
            Route::delete('/{id}', [\App\Http\Controllers\Teknisi\TeknisiKebersihanRuangController::class, 'destroy'])->name('destroy')->middleware('permission:menu_teknisi,delete');
        });

        // Indikator 3 — Kebersihan Lingkungan Ruang Rapat
        Route::prefix('kebersihan-rapat')->name('kebersihan-rapat.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Teknisi\TeknisiKebersihanRapatController::class, 'index'])->name('index')->middleware('permission:menu_teknisi,read');
            Route::post('/', [\App\Http\Controllers\Teknisi\TeknisiKebersihanRapatController::class, 'store'])->name('store')->middleware('permission:menu_teknisi,create');
            Route::put('/{id}', [\App\Http\Controllers\Teknisi\TeknisiKebersihanRapatController::class, 'update'])->name('update')->middleware('permission:menu_teknisi,update');
            Route::delete('/{id}', [\App\Http\Controllers\Teknisi\TeknisiKebersihanRapatController::class, 'destroy'])->name('destroy')->middleware('permission:menu_teknisi,delete');
        });

        // Indikator 4 — Laporan Sarana Kegiatan Rapat
        Route::prefix('sarana-rapat')->name('sarana-rapat.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Teknisi\TeknisiSaranaRapatController::class, 'index'])->name('index')->middleware('permission:menu_teknisi,read');
            Route::post('/', [\App\Http\Controllers\Teknisi\TeknisiSaranaRapatController::class, 'store'])->name('store')->middleware('permission:menu_teknisi,create');
            Route::put('/{id}', [\App\Http\Controllers\Teknisi\TeknisiSaranaRapatController::class, 'update'])->name('update')->middleware('permission:menu_teknisi,update');
            Route::delete('/{id}', [\App\Http\Controllers\Teknisi\TeknisiSaranaRapatController::class, 'destroy'])->name('destroy')->middleware('permission:menu_teknisi,delete');
        });

        // Indikator 5 — Pengarsipan & Laporan Perbaikan Sarana Prasarana
        Route::prefix('jurnal-perbaikan')->name('jurnal-perbaikan.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Teknisi\TeknisiJurnalPerbaikanController::class, 'index'])->name('index')->middleware('permission:menu_teknisi,read');
            Route::post('/', [\App\Http\Controllers\Teknisi\TeknisiJurnalPerbaikanController::class, 'store'])->name('store')->middleware('permission:menu_teknisi,create');
            Route::put('/{id}', [\App\Http\Controllers\Teknisi\TeknisiJurnalPerbaikanController::class, 'update'])->name('update')->middleware('permission:menu_teknisi,update');
            Route::delete('/{id}', [\App\Http\Controllers\Teknisi\TeknisiJurnalPerbaikanController::class, 'destroy'])->name('destroy')->middleware('permission:menu_teknisi,delete');
        });

        // =====================================================================
        // MODUL TEKNISI LAPANGAN (generik, per-akun petugas)
        // =====================================================================
        $modulLapangan = 'area-master|inspeksi-utilitas|apar-k3|pest-control|higiene-kantin|pemakaian-bahan'
            . '|usulan-pengadaan|aset-mutasi|peminjaman-ruang';

        Route::prefix('modul/{modul}')->name('modul.')->where(['modul' => $modulLapangan])->group(function () {
            Route::get('/', [\App\Http\Controllers\Teknisi\TeknisiModuleController::class, 'index'])->name('index')->middleware('permission:menu_teknisi,read');
            Route::post('/', [\App\Http\Controllers\Teknisi\TeknisiModuleController::class, 'store'])->name('store')->middleware('permission:menu_teknisi,create');
            Route::get('/template', [\App\Http\Controllers\Teknisi\TeknisiModuleController::class, 'template'])->name('template')->middleware('permission:menu_teknisi,read');
            Route::post('/import', [\App\Http\Controllers\Teknisi\TeknisiModuleController::class, 'import'])->name('import')->middleware('permission:menu_teknisi,create');
            Route::get('/cetak', [\App\Http\Controllers\Teknisi\TeknisiModuleController::class, 'cetak'])->name('cetak')->middleware('permission:menu_teknisi,read');
            Route::put('/{id}', [\App\Http\Controllers\Teknisi\TeknisiModuleController::class, 'update'])->name('update')->middleware('permission:menu_teknisi,update');
            Route::delete('/{id}', [\App\Http\Controllers\Teknisi\TeknisiModuleController::class, 'destroy'])->name('destroy')->middleware('permission:menu_teknisi,delete');
        });

        // =====================================================================
        // LAPORAN (CETAK PER HALAMAN) & IMPORT EXCEL — 5 modul TUPOKSI
        // =====================================================================
        $modulRegex = 'area-tugas|kebersihan-ruang|kebersihan-rapat|sarana-rapat|jurnal-perbaikan';
        Route::get('/laporan/{modul}/template', [\App\Http\Controllers\Teknisi\TeknisiTupoksiReportController::class, 'template'])
            ->name('laporan.template')->where('modul', $modulRegex)->middleware('permission:menu_teknisi,read');
        Route::post('/laporan/{modul}/import', [\App\Http\Controllers\Teknisi\TeknisiTupoksiReportController::class, 'import'])
            ->name('laporan.import')->where('modul', $modulRegex)->middleware('permission:menu_teknisi,create');
        Route::get('/laporan/{modul}/cetak', [\App\Http\Controllers\Teknisi\TeknisiTupoksiReportController::class, 'cetak'])
            ->name('laporan.cetak')->where('modul', $modulRegex)->middleware('permission:menu_teknisi,read');
    });
};
