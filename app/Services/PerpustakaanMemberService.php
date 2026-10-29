<?php

namespace App\Services;

use App\Models\PerpusAnggota;
use App\Support\Table;
use Illuminate\Support\Facades\DB;

/**
 * PerpustakaanMemberService
 * -------------------------------------------------------------------------
 * Layanan inti area member Perpustakaan Digital:
 *  - Poin literasi & level anggota
 *  - Notifikasi in-app anggota
 *  - Reservasi / antrean buku
 *  - Perhitungan statistik dashboard member
 */
class PerpustakaanMemberService
{
    /** Tabel poin per aktivitas. */
    public const POIN = [
        'pinjam'          => 10,
        'kembali_tepat'   => 15,
        'kembali_telat'   => 3,
        'kunjungan'       => 2,
        'ulasan'          => 5,
        'reservasi'       => 2,
    ];

    /** Ambang level literasi (poin minimum => label). */
    public const LEVEL = [
        ['min' => 0,    'label' => 'Pemula',        'icon' => 'fa-seedling',      'warna' => '#94a3b8'],
        ['min' => 50,   'label' => 'Pembaca',       'icon' => 'fa-book-open',     'warna' => '#3b82f6'],
        ['min' => 150,  'label' => 'Kutu Buku',     'icon' => 'fa-book-bookmark', 'warna' => '#8b5cf6'],
        ['min' => 350,  'label' => 'Pecinta Literasi', 'icon' => 'fa-fire',       'warna' => '#f59e0b'],
        ['min' => 700,  'label' => 'Duta Literasi', 'icon' => 'fa-crown',         'warna' => '#ec4899'],
    ];

    // ---------------------------------------------------------------------
    // POIN & LEVEL
    // ---------------------------------------------------------------------

    public static function tambahPoin(PerpusAnggota $anggota, string $aktivitas, int $qty = 1): int
    {
        $perAktivitas = self::POIN[$aktivitas] ?? 0;
        if ($perAktivitas <= 0 || !Table::hasColumn('perpus_anggota', 'poin')) {
            return 0;
        }

        $tambahan = $perAktivitas * max(1, $qty);
        DB::table('perpus_anggota')->where('id', $anggota->id)->increment('poin', $tambahan);
        $anggota->refresh();

        return $tambahan;
    }

    /**
     * Level literasi + progres ke level berikutnya.
     *
     * @return array{label:string, icon:string, warna:string, poin:int, next:?int, progress:int, sisa:int}
     */
    public static function level(PerpusAnggota $anggota): array
    {
        $poin = (int) ($anggota->poin ?? 0);
        $current = self::LEVEL[0];
        $next = null;

        foreach (self::LEVEL as $i => $lv) {
            if ($poin >= $lv['min']) {
                $current = $lv;
                $next = self::LEVEL[$i + 1] ?? null;
            }
        }

        $progress = 100;
        $sisa = 0;
        if ($next) {
            $span = max(1, $next['min'] - $current['min']);
            $progress = (int) round((($poin - $current['min']) / $span) * 100);
            $progress = max(0, min(100, $progress));
            $sisa = max(0, $next['min'] - $poin);

            // Jangan tampilkan 100% selama level berikutnya belum tercapai.
            if ($sisa > 0) {
                $progress = min(99, $progress);
            }
        }

        return [
            'label'    => $current['label'],
            'icon'     => $current['icon'],
            'warna'    => $current['warna'],
            'poin'     => $poin,
            'next'     => $next['min'] ?? null,
            'next_label' => $next['label'] ?? null,
            'progress' => $progress,
            'sisa'     => $sisa,
        ];
    }

    // ---------------------------------------------------------------------
    // NOTIFIKASI
    // ---------------------------------------------------------------------

    public static function notifikasi(PerpusAnggota $anggota, string $judul, ?string $pesan = null, array $opsi = []): void
    {
        if (!Table::exists('perpus_notifikasi')) {
            return;
        }

        try {
            DB::table('perpus_notifikasi')->insert([
                'anggota_id' => $anggota->id,
                'kategori'   => $opsi['kategori'] ?? 'umum',
                'judul'      => $judul,
                'pesan'      => $pesan,
                'tipe'       => $opsi['tipe'] ?? 'info',
                'icon'       => $opsi['icon'] ?? 'fa-solid fa-bell',
                'url'        => $opsi['url'] ?? null,
                'is_read'    => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal membuat notifikasi perpus: ' . $e->getMessage());
        }
    }

    public static function notifikasiBelumDibaca(PerpusAnggota $anggota): int
    {
        if (!Table::exists('perpus_notifikasi')) {
            return 0;
        }
        return (int) DB::table('perpus_notifikasi')
            ->where('anggota_id', $anggota->id)
            ->where('is_read', false)
            ->count();
    }

    public static function daftarNotifikasi(PerpusAnggota $anggota, int $limit = 20)
    {
        if (!Table::exists('perpus_notifikasi')) {
            return collect();
        }
        return DB::table('perpus_notifikasi')
            ->where('anggota_id', $anggota->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public static function tandaiDibaca(PerpusAnggota $anggota, ?int $id = null): void
    {
        if (!Table::exists('perpus_notifikasi')) {
            return;
        }
        $q = DB::table('perpus_notifikasi')->where('anggota_id', $anggota->id);
        if ($id) {
            $q->where('id', $id);
        }
        $q->update(['is_read' => true, 'updated_at' => now()]);
    }

    // ---------------------------------------------------------------------
    // RESERVASI
    // ---------------------------------------------------------------------

    public static function bisaReservasi(PerpusAnggota $anggota, int $bukuId): array
    {
        if (!Table::exists('perpus_reservasi')) {
            return [false, 'Fitur reservasi belum tersedia.'];
        }

        $buku = DB::table('perpus_koleksi_buku')->where('id', $bukuId)->first();
        if (!$buku) {
            return [false, 'Buku tidak ditemukan.'];
        }
        if ((int) $buku->eksemplar_tersedia > 0) {
            return [false, 'Buku masih tersedia â€” silakan pinjam langsung di meja layanan.'];
        }

        $sudah = DB::table('perpus_reservasi')
            ->where('anggota_id', $anggota->id)
            ->where('buku_id', $bukuId)
            ->whereIn('status', ['menunggu', 'siap'])
            ->exists();
        if ($sudah) {
            return [false, 'Anda sudah mengantre untuk buku ini.'];
        }

        // Batasi maksimal 3 reservasi aktif per anggota.
        $aktif = DB::table('perpus_reservasi')
            ->where('anggota_id', $anggota->id)
            ->whereIn('status', ['menunggu', 'siap'])
            ->count();
        if ($aktif >= 3) {
            return [false, 'Maksimal 3 reservasi aktif. Selesaikan salah satu dahulu.'];
        }

        return [true, null];
    }

    public static function buatReservasi(PerpusAnggota $anggota, int $bukuId): array
    {
        [$ok, $alasan] = self::bisaReservasi($anggota, $bukuId);
        if (!$ok) {
            return [false, $alasan];
        }

        $antrean = (int) DB::table('perpus_reservasi')
            ->where('buku_id', $bukuId)
            ->whereIn('status', ['menunggu', 'siap'])
            ->max('antrean') + 1;

        DB::table('perpus_reservasi')->insert([
            'anggota_id' => $anggota->id,
            'buku_id'    => $bukuId,
            'antrean'    => $antrean,
            'status'     => 'menunggu',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        self::tambahPoin($anggota, 'reservasi');
        self::notifikasi(
            $anggota,
            'Reservasi buku tercatat',
            'Anda berada di antrean ke-' . $antrean . '. Kami akan memberi tahu saat buku tersedia.',
            ['kategori' => 'reservasi', 'tipe' => 'info', 'icon' => 'fa-solid fa-clock']
        );

        return [true, 'Reservasi berhasil. Anda berada di antrean ke-' . $antrean . '.'];
    }

    public static function reservasiAnggota(PerpusAnggota $anggota)
    {
        if (!Table::exists('perpus_reservasi')) {
            return collect();
        }
        return DB::table('perpus_reservasi as r')
            ->leftJoin('perpus_koleksi_buku as b', 'r.buku_id', '=', 'b.id')
            ->select('r.*', 'b.judul as judul_buku', 'b.penulis as penulis_buku', 'b.cover_buku')
            ->where('r.anggota_id', $anggota->id)
            ->orderByDesc('r.created_at')
            ->limit(20)
            ->get();
    }

    public static function batalkanReservasi(PerpusAnggota $anggota, int $id): bool
    {
        if (!Table::exists('perpus_reservasi')) {
            return false;
        }
        $row = DB::table('perpus_reservasi')
            ->where('id', $id)
            ->where('anggota_id', $anggota->id)
            ->whereIn('status', ['menunggu', 'siap'])
            ->first();

        if (!$row) {
            return false;
        }

        DB::table('perpus_reservasi')->where('id', $row->id)->update([
            'status'     => 'batal',
            'updated_at' => now(),
        ]);

        return true;
    }

    // ---------------------------------------------------------------------
    // STATISTIK DASHBOARD
    // ---------------------------------------------------------------------

    /**
     * Rekomendasi buku: berdasarkan kategori yang paling sering dipinjam anggota.
     * Fallback ke buku terbaru bila belum ada riwayat.
     */
    public static function rekomendasi(PerpusAnggota $anggota, int $limit = 6)
    {
        if (!Table::exists('perpus_koleksi_buku')) {
            return collect();
        }

        $kategoriFavorit = [];
        try {
            $riwayatBuku = PerpustakaanIdentitasService::riwayatQuery($anggota)
                ->whereNotNull('b.kategori')
                ->pluck('b.kategori')
                ->filter()
                ->countBy()
                ->sortDesc()
                ->take(3)
                ->keys()
                ->all();
            $kategoriFavorit = $riwayatBuku;
        } catch (\Throwable $e) {
            $kategoriFavorit = [];
        }

        $sudahDipinjam = [];
        try {
            $sudahDipinjam = PerpustakaanIdentitasService::riwayatQuery($anggota)->pluck('s.buku_id')->all();
        } catch (\Throwable $e) {
            $sudahDipinjam = [];
        }

        $q = DB::table('perpus_koleksi_buku')->where('eksemplar_tersedia', '>', 0);

        if (!empty($kategoriFavorit)) {
            $q->whereIn('kategori', $kategoriFavorit);
        }
        if (!empty($sudahDipinjam)) {
            $q->whereNotIn('id', $sudahDipinjam);
        }

        $hasil = $q->orderByDesc('id')->limit($limit)->get();

        // Fallback bila filter terlalu ketat.
        if ($hasil->isEmpty()) {
            $hasil = DB::table('perpus_koleksi_buku')
                ->where('eksemplar_tersedia', '>', 0)
                ->orderByDesc('id')
                ->limit($limit)
                ->get();
        }

        return $hasil;
    }

    /**
     * Statistik literasi untuk dashboard member.
     */
    public static function statistik(PerpusAnggota $anggota): array
    {
        $ringkasan = PerpustakaanIdentitasService::ringkasanPinjaman($anggota);

        $kunjungan = 0;
        if (Table::exists('perpus_kunjungan')) {
            $q = DB::table('perpus_kunjungan');
            if (Table::hasColumn('perpus_kunjungan', 'anggota_id')) {
                $q->where('anggota_id', $anggota->id);
            } else {
                $q->where('pengunjung_id', $anggota->siswa_id ?? $anggota->nisn_nip);
            }
            $kunjungan = (int) $q->count();
        }

        $reservasiAktif = 0;
        if (Table::exists('perpus_reservasi')) {
            $reservasiAktif = (int) DB::table('perpus_reservasi')
                ->where('anggota_id', $anggota->id)
                ->whereIn('status', ['menunggu', 'siap'])
                ->count();
        }

        return [
            'pinjam_aktif'     => $ringkasan['jumlah_aktif'],
            'terlambat'        => $ringkasan['jumlah_terlambat'],
            'jatuh_tempo'      => $ringkasan['jatuh_tempo_terdekat'],
            'denda'            => $ringkasan['denda_belum_lunas'],
            'total_riwayat'    => $ringkasan['total_riwayat'],
            'total_kembali'    => $ringkasan['total_kembali'],
            'kunjungan'        => $kunjungan,
            'reservasi_aktif'  => $reservasiAktif,
            'notifikasi_unread' => self::notifikasiBelumDibaca($anggota),
            'poin'             => (int) ($anggota->poin ?? 0),
        ];
    }
}
