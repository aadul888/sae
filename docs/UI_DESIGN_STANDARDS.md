# Standar Desain & UI — SAE

Panduan wajib untuk setiap perubahan antarmuka. Tujuannya menjaga **konsistensi**
agar pengguna tidak melihat tampilan yang berbeda-beda antar halaman.

---

## 1. Sumber Kebenaran Tunggal

| Berkas | Isi | Boleh diubah? |
|---|---|---|
| `public/css/tokens.css` | Skala spacing, radius, tipografi, elevasi, durasi, z-index | ✅ Ya — di sinilah nilai baru ditambahkan |
| `public/css/components.css` | Kelas komponen (`.sae-btn`, `.sae-card`, `.sae-table`, dll.) | ✅ Ya |
| `resources/views/components/sae/*` | Komponen Blade (`<x-sae.button>`, `<x-sae.card>`, …) | ✅ Ya |
| `public/css/*.css` lainnya | Stylesheet lama per modul | ⚠️ Hanya untuk perbaikan; jangan tambah nilai mentah |

**Urutan pemuatan wajib:** `tokens.css` → `components.css` → `sae.css` → CSS modul.
Dijaga otomatis oleh `DesignConsistencyTest`.

---

## 2. Aturan Keras

### ❌ JANGAN menulis nilai mentah

```blade
{{-- SALAH --}}
<div style="border-radius: 10px; font-size: 0.82rem; padding: 12px;">
```

```blade
{{-- BENAR — pakai komponen --}}
<x-sae.card>...</x-sae.card>

{{-- BENAR — pakai token bila perlu inline --}}
<div style="border-radius: var(--radius-lg); font-size: var(--text-sm); padding: var(--space-6);">
```

### ❌ JANGAN membuat ukuran font baru

Hanya ada **12 ukuran sah**: `--text-2xs` (11px), `--text-xs` (12px), `--text-sm` (13px),
`--text-base` (14px), `--text-md` (16px), `--text-lg` (18px), `--text-xl` (20px),
`--text-2xl` (24px), `--text-3xl` (30px), `--text-4xl` (36px), `--text-5xl` (48px), `--text-6xl` (64px).

> **Kenapa ini penting:** `rem` dihitung dari `font-size` induk. Nilai seperti `0.82rem`
> di dalam elemen ber-`font-size` lain menghasilkan `13.28px` — bukan `13.12px` seperti dugaan.
> Inilah penyebab tampilan terasa "tidak konsisten" meski kelihatannya mirip.

### ❌ JANGAN membuat radius baru

Hanya ada **7 radius sah**: `--radius-xs` (4px), `--radius-sm` (6px), `--radius-md` (8px),
`--radius-lg` (12px), `--radius-xl` (16px), `--radius-2xl` (20px), `--radius-pill` (999px).

Sebelumnya ada 20 nilai berbeda (2px sampai 9999px) — sudah dinormalisasi.

### ✅ SELALU pakai komponen untuk elemen berulang

| Kebutuhan | Komponen |
|---|---|
| Tombol | `<x-sae.button variant="primary\|secondary\|outline\|ghost\|danger\|success" size="sm\|lg" icon="fas fa-plus">` |
| Kartu | `<x-sae.card title="..." icon="fas fa-chart-pie">` |
| Judul halaman | `<x-sae.page-header title="..." subtitle="..." icon="...">` + slot `actions` |
| Label status | `<x-sae.badge variant="neutral\|primary\|success\|warning\|danger">` |
| Input form | `<x-sae.field name="..." label="..." type="text\|textarea" required hint="..." />` |
| Pesan sistem | `<x-sae.alert variant="info\|success\|warning\|danger" dismissible>` |
| Keadaan kosong | `<x-sae.empty title="..." text="..." icon="fa-inbox" />` |
| Angka ringkasan | `<x-sae.stat :value="42" label="Total" icon="fas fa-users" color="accent" />` |

---

## 3. Contoh Lengkap

```blade
<x-sae.page-header
    title="Data Peserta Didik"
    subtitle="Direktori peserta didik aktif dari Dapodik."
    icon="fas fa-user-graduate">

    <x-slot:actions>
        <x-sae.button variant="outline" icon="fas fa-file-excel" onclick="openImport()">
            Import Excel
        </x-sae.button>
        <x-sae.button variant="primary" icon="fas fa-plus" href="{{ route('peserta-didik.create') }}">
            Tambah
        </x-sae.button>
    </x-slot:actions>
</x-sae.page-header>

<div class="sae-stat-grid">
    <x-sae.stat :value="number_format($total)" label="Total Aktif" icon="fas fa-users" />
    <x-sae.stat :value="number_format($laki)" label="Laki-laki" icon="fas fa-mars" color="accent" />
</div>

<x-sae.card title="Daftar Peserta Didik" icon="fas fa-table">
    @if ($siswa->isEmpty())
        <x-sae.empty title="Belum ada data" text="Tambahkan peserta didik terlebih dahulu." />
    @else
        <div class="sae-table-wrap">
            <table class="sae-table">
                <thead>
                    <tr><th>Nama</th><th>NISN</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @foreach ($siswa as $s)
                        <tr>
                            <td>{{ $s->nama }}</td>
                            <td class="sae-mono">{{ $s->nisn }}</td>
                            <td><x-sae.badge variant="success">Aktif</x-sae.badge></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-sae.card>
```

---

## 4. Spacing

Skala kelipatan 4px: `--space-1` (2px), `--space-2` (4px), `--space-3` (6px),
`--space-4` (8px), `--space-5` (10px), `--space-6` (12px), `--space-7` (14px),
`--space-8` (16px), `--space-9` (20px), `--space-10` (24px), `--space-11` (32px),
`--space-12` (40px), `--space-13` (48px).

Alias semantik: `--space-xs` … `--space-2xl`.

---

## 5. Aksesibilitas

- Tinggi kontrol minimum: `--control-height-md` (38px) untuk target sentuh.
- Semua tombol ikon wajib punya `aria-label`.
- Gunakan `.sae-sr-only` untuk teks yang hanya dibaca pembaca layar.
- `prefers-reduced-motion` sudah ditangani otomatis oleh `tokens.css`.
- Fokus keyboard memakai `:focus-visible` dengan outline `--primary`.

---

## 6. Menambah Token Baru

Bila benar-benar butuh nilai baru:

1. Tambahkan ke `tokens.css` **dengan komentar alasan pemakaian**.
2. Jangan menambah varian yang hanya beda 1–2px dari token yang sudah ada.
3. Perbarui `DesignConsistencyTest` bila perlu.

---

## 7. Verifikasi

```bash
php artisan config:clear          # WAJIB sebelum test (lihat catatan di bawah)
php artisan view:clear
php artisan test --filter=DesignConsistencyTest
php artisan test --no-coverage
```

`DesignConsistencyTest` akan **gagal** bila ada:
- `font-size: <angka>rem` atau `border-radius: <angka>px` di CSS aplikasi
- `font-size` mentah di view
- lebih dari 3 `border-radius` mentah di view (sisa shorthand)
- sintaks `var()` rusak
- token wajib belum didefinisikan
- urutan pemuatan CSS salah

---

## 8. Catatan Penting

> **`php artisan config:cache` merusak test suite.**
> Perintah itu mem-bake nilai `.env` (mis. `CACHE_DRIVER=file`) sehingga mengalahkan
> `<env name="CACHE_DRIVER" value="array"/>` pada `phpunit.xml`. Gejalanya: 34 test gagal
> meski kode tidak berubah. Selalu jalankan `php artisan config:clear` sebelum test.
