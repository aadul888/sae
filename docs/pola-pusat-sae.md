# Pola Pusat SAE — Kontrak Aplikasi ke sae-core

Dokumen ini menjelaskan **pola integrasi semua aplikasi SAE ke pusat (sae-core)**.
Pola ini dipakai ulang untuk aplikasi apa pun (sae-smk, sae-feeder, dan aplikasi
berikutnya) **tanpa mengganggu sistem yang sudah berjalan**.

---

## 1. Prinsip

> **Pusat = sumber informasi, bukan gerbang.**

Pusat mengirim **status & pengumuman**, bukan perintah yang menutup aplikasi.
Aplikasi di sekolah **selalu berjalan penuh**, apa pun kondisi koneksi ke pusat.

Konsekuensi:
- **Tidak ada pemblokiran akses.** Tidak ada penutupan fitur, tidak ada
  pembatasan peran, tidak ada penghentian aplikasi.
- Aplikasi harus **tetap jalan walau pusat mati / tak terjangkau** (fail-open).
- Pusat hanya menambah **satu hal**: apakah **iklan donasi** perlu ditampilkan.

Yang **di**kontrol pusat hari ini:
1. **Donasi & iklan** — sekolah yang belum donasi melihat banner iklan; yang
   sudah donasi tidak melihatnya. Admin pusat yang menandai donatur.
2. **Notifikasi broadcast** — pesan/pengumuman dari pusat.
3. **Info versi** — versi terbaru yang tersedia (sekadar info, bukan paksaan).

Bukan lagi wewenang pusat: menutup fitur, mengunci lisensi, membatasi peran.

## 2. Kenapa bukan kontrol akses / lisensi bertanda-tangan

Percobaan sebelumnya (push aturan peran, lalu lisensi bertanda-tangan + masa
berlaku + pembekuan) **tidak ditegakkan** dan hanya menambah kerumitan:

- Repo klien publik + admin sekolah pegang server → apa pun gate di klien bisa
  dilucuti.
- Lisensi blokir **tak berpengaruh** bila klien belum pernah menerima lisensi
  (fail-open), sehingga pembekuan di pusat terasa sia-sia.
- Memblokir akses SMA *setelah* mereka memakai = kehilangan kepercayaan.

Kesimpulan: **buang pemblokiran.** Sisa yang berguna dan tulus hanyalah
**donasi/iklan** + pengumuman. Itu saja yang pusat kirim.

## 3. Kontrak API (dipakai semua aplikasi)

Tiga panggilan, semua **opsional** dan **aman-gagal**:

| Panggilan | Arah | Guna |
|---|---|---|
| `POST /api/enroll` | app → pusat | Daftar sekali saat instalasi pertama, kirim hash kunci sendiri |
| `POST /api/sync/checkin` | app → pusat | Lapor status + **terima** `donation`, `notifications`, `latest_version` |
| `POST /api/sync/telemetry` | app → pusat | Lapor agregat angka (jumlah siswa/guru/dll) |

**Auth**: kunci instalasi per sekolah, dibuat otomatis oleh app saat pertama
(enroll), disimpan di tabel `settings` (bukan di repo). Pusat menyimpan
hash-nya. Tidak ada rahasia yang ditanam di repo publik.

### Balasan `checkin` (inti pola)

```json
{
  "success": true,
  "latest_version": "1.0.7",
  "update_available": true,
  "notifications": [ { "id": 1, "title": "...", "body": "...", "action_url": "..." } ],
  "notif_count": 1,
  "donation": {
    "is_donor": false,
    "show_ads": true
  }
}
```

Makna:
- `donation.show_ads = true` → sekolah **belum donasi** dan admin pusat
  menyalakan iklan → **tampilkan banner donasi**.
- `donation.show_ads = false` → sudah donasi atau iklan dimatikan →
  **sembunyikan banner**.
- `notifications` → tampilkan sebagai pengumuman. Tidak wajib.
- `latest_version` → hanya info. **Tidak** memaksa update.

Itu **seluruhnya**. Tidak ada field `access`, `blocked`, `entitlements`,
`suspended`, `exp`, atau apa pun yang menutup aplikasi.

## 4. Di sisi aplikasi (klien)

Satu service kecil, pola sama untuk tiap app:

```
1. Saat pertama: enroll (kirim hash kunci sendiri) → simpan kunci di settings.
2. Ambil status dari pusat (checkin), simpan hasilnya.
   - Bila pusat tak terjangkau → pakai nilai bawaan aman (fail-open).
3. Tampilkan banner donasi bila donation.show_ads = true.
4. Tampilkan notifikasi.
5. (opsional) cron harian: kirim telemetri + segarkan status.
```

Aturan wajib:
- **Selalu fail-open.** Gagal hubungi pusat = aplikasi jalan normal, iklan ikut
  nilai terakhir / bawaan.
- **Jangan pernah menutup fitur** berdasar balasan pusat.
- **Jangan taruh rahasia di repo.** Kunci dibuat di server sekolah & disimpan
  di DB.

## 5. Di sisi pusat (sae-core)

- Tabel `sync_sekolah`: baris per instalasi (npsn, nama, versi, agregat, dan
  dua bendera donasi: `is_donor`, `ad_enabled`).
- Endpoint `enroll` / `checkin` / `telemetry` (sudah ada di `routes/api.php`).
- Halaman admin **Donasi & Iklan**: tandai sekolah donatur / atur tampil-tidaknya
  iklan. Ini satu-satunya "kontrol" yang tersisa — dan sifatnya **bantuan
  finansial sukarela**, bukan hukuman.
- Notifikasi broadcast (opsional).

`show_ads` dihitung pusat sederhana:

```
show_ads = ad_enabled AND NOT is_donor
```

## 6. Menerapkan ke aplikasi baru

Untuk app SAE berikutnya (cukup tambah `app_code`, mis. `SAE-FEEDER`):

1. **Daftarkan kode app** di pusat (otomatis saat enroll pertama).
2. Di app: buat satu `CoreStatusService` kecil dengan pola bab 4:
   - `enroll()` — sekali, simpan kunci di settings.
   - `status()` — panggil `checkin`, simpan & kembalikan `donation` +
     `notifications`. Fail-open.
   - `showAds()` — baca `donation.show_ads`.
3. Tampilkan banner donasi memakai partial yang sama bila `showAds()` true.
4. **Tidak perlu** membuat gate, middleware, lisensi, atau pemeriksaan apa pun
   yang menghambat aplikasi.

Dengan begitu aplikasi baru langsung ikut pola **tanpa menyentuh** aplikasi
yang sedang jalan.

## 7. Yang dibuang (agar tidak jadi beban)

Agar pola ini bersih, komponen blokir dari percobaan lalu **tidak dipakai lagi**:

- **sae-core**: `LicenseService`, model `License`, tabel `licenses`, endpoint
  `POST /api/license/issue`, halaman `/admin/lisensi`.
- **sae-smk**: `App\Support\AppLicense` (verifikasi + gerbang), kolom
  `settings.license_*`, gerbang `AppLicense::allows()` di `UpdateService`,
  kartu Lisensi di halaman Update.

Diganti oleh apa yang **sudah ada**: `/api/sync/checkin` yang mengembalikan
`donation` + `notifications`, dan `CoreControlService::showAds()` di klien.

## 8. Ringkas

| Aspek | Nilai dalam pola ini |
|---|---|
| Pengaruh pusat ke app | Menampilkan iklan donasi + kirim pengumuman |
| Pemblokiran akses | **Tidak ada** |
| Ketersediaan app saat pusat mati | **Tetap jalan** (fail-open) |
| Rahasia di repo publik | **Tidak ada** |
| Kontrol admin pusat | Tandai donatur & simpan iklan |
| Reusable app baru | Ya — cukup `app_code` + service status kecil |

---

### Berkas acuan
- Klien: `app/Services/CoreControlService.php` (`showAds()`),
  `app/Support/CoreApiKey.php` (enroll + kunci), `resources/views/partials/donation-banner.blade.php`.
- Pusat: `app/Http/Controllers/Api/ClientApiController.php` (`checkin()` →
  `donation`), `routes/api.php`, halaman admin Donasi & Iklan.
