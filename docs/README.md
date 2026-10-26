# Dokumentasi SAE

Panduan pengembangan aplikasi SAE (Sistem Aplikasi Edukasi).

---

## Mulai dari mana?

| Kalau Anda ingin… | Baca |
|---|---|
| **Melakukan upgrade di server aaPanel** | [UPGRADE_SERVER_AAPANEL.md](UPGRADE_SERVER_AAPANEL.md) |
| Memahami struktur folder & arsitektur | [PROJECT_STRUCTURE.md](PROJECT_STRUCTURE.md) |
| Membuat modul/fitur baru | [PANDUAN_PEMBUATAN_MODUL_DAN_FITUR.md](PANDUAN_PEMBUATAN_MODUL_DAN_FITUR.md) |
| Mengubah tampilan / antarmuka | [UI_DESIGN_STANDARDS.md](UI_DESIGN_STANDARDS.md) |
| Menulis kode sesuai gaya proyek | [code-style-guide.md](code-style-guide.md) |
| Membuat tombol, ikon, atau datatable | [STANDAR_TOMBOL_ICON_DAN_DATATABLE.md](STANDAR_TOMBOL_ICON_DAN_DATATABLE.md) |
| Mengetahui ketergantungan antar modul | [MODULE_DEPENDENCIES.md](MODULE_DEPENDENCIES.md) |
| Mengubah sistem secara efisien (hindari efek samping) | [PANDUAN_EFISIENSI_PERUBAHAN_SISTEM.md](PANDUAN_EFISIENSI_PERUBAHAN_SISTEM.md) |

---

## Urutan baca yang disarankan (developer baru)

1. **PROJECT_STRUCTURE.md** — peta keseluruhan: di mana controller, service, model, view.
2. **code-style-guide.md** — konvensi penamaan dan penulisan kode.
3. **PANDUAN_PEMBUATAN_MODUL_DAN_FITUR.md** — alur baku menambah modul (controller → service → route → view → hak akses).
4. **UI_DESIGN_STANDARDS.md** — sebelum menyentuh Blade atau CSS.
5. **MODULE_DEPENDENCIES.md** — agar tidak memutus modul lain saat mengubah sesuatu.

**Untuk operator/deployer:** mulai dari **UPGRADE_SERVER_AAPANEL.md**.

---

## Ringkasan Teknologi

| Komponen | Versi |
|---|---|
| PHP | ≥ 8.3 |
| Laravel | 13.x |
| Database | MySQL ≥ 8.0 / MariaDB ≥ 10.4 |
| Frontend | Blade + CSS custom (design token), Alpine.js, Vite |
| Ikon | FontAwesome 6 (lokal) |

---

## Perintah Penting

```bash
# Pengembangan
php artisan serve
npm run dev

# Build aset produksi
npm run build

# Test
php artisan config:clear      # WAJIB sebelum test
php artisan test --no-coverage

# Cek konsistensi desain saja
php artisan test --filter=DesignConsistencyTest

# Cache produksi
php artisan optimize:clear
php artisan optimize
```

---

## Aturan Wajib

### 1. Jangan menulis nilai desain mentah

```blade
{{-- ❌ SALAH --}}
<div style="border-radius: 10px; font-size: 0.82rem;">

{{-- ✅ BENAR --}}
<x-sae.card>...</x-sae.card>
```

Dijaga otomatis oleh `DesignConsistencyTest` — build akan gagal bila dilanggar.

### 2. Selalu jalankan `config:clear` sebelum test

`php artisan config:cache` mem-bake nilai `.env` sehingga mengalahkan
`CACHE_DRIVER=array` pada `phpunit.xml`. Gejalanya: puluhan test gagal
padahal kode tidak berubah.

### 3. Dokumentasi ikut di-commit

Folder `docs/` **wajib** ter-track git. Catatan kerja internal agen ada di
`.agents/` yang sengaja di-ignore; dokumen yang dibutuhkan developer harus
disalin ke `docs/`.

---

## Struktur Folder Inti

```
app/
  Http/Controllers/     # 74 controller (tipis, delegasi ke service)
  Http/Middleware/      # auth, permission, CSRF, realtime broadcast
  Models/               # 65 model Eloquent
  Services/             # 48 service (logika bisnis)
  Support/              # helper statis (kamus izin, penemuan modul, dll.)
  Console/Commands/     # perintah artisan kustom
config/                 # konfigurasi Laravel
database/
  migrations/           # 83 migrasi
  seeders/
docs/                   # dokumentasi pengembangan (file ini)
public/
  css/                  # tokens.css, components.css, sae.css, per-modul
  js/                   # skrip per-modul
  vendor/               # library pihak ketiga (FontAwesome, Chart.js, dll.)
resources/views/
  components/sae/       # 8 komponen Blade reusable
  layouts/              # app, dashboard, cetak
  partials/             # sidebar, header, footer, nav mobile
  dashboard/            # halaman per modul
routes/
  web.php               # 470+ rute web
  api.php               # API (Sanctum + feeder Dapodik)
tests/
  Feature/              # test fitur (termasuk guard konsistensi desain)
  Unit/
```

---

## Menghubungi

Dikembangkan oleh Abdul Azis. Badge kontak tampil di footer aplikasi
(di-resolve dari `App\Support\SystemSignature`).
