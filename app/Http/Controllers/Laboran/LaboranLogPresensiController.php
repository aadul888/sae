<?php

namespace App\Http\Controllers\Laboran;

use App\Models\PresensiHarian;
use App\Support\LaboranScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Log Presensi Harian — modul baca-saja untuk Laboran.
 *
 * Menampilkan log presensi harian (terminal RFID / QR / manual) yang
 * DIBATASI pada jurusan tempat Laboran ditugaskan. Cakupan memakai relasi
 * `presensi_harian.rombongan_belajar_id` → `rombongan_belajar.jurusan_id`,
 * karena tabel presensi tidak menyimpan `jurusan_id` langsung.
 *
 * Tidak ada aksi CRUD: halaman hanya menampilkan data, filter kelas
 * (rombel), status, pencarian, dan pratinjau snapshot foto.
 */
class LaboranLogPresensiController extends LaboranBaseController
{
    /** Kolom yang boleh dipakai sebagai kunci pengurutan. */
    public const SORTABLE = ['nama_siswa', 'nama_rombel', 'jam_masuk', 'jam_pulang', 'status'];

    /** Status presensi yang dikenali. */
    public const STATUSES = ['H', 'T', 'I', 'S', 'D', 'A'];

    public function index(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) {
            return $res;
        }

        // Selaraskan dengan halaman presensi pusat: tandai pulang cepat untuk
        // siswa yang tidak tap pulang pada hari sebelumnya.
        PresensiHarian::autoCloseUncheckedOut();

        $tanggal = $this->resolveTanggal($request);
        $q       = trim((string) $request->query('q', ''));
        $status  = strtoupper(trim((string) $request->query('status', '')));
        $perPage = $this->resolvePerPage($request);

        if (!in_array($status, self::STATUSES, true)) {
            $status = '';
        }

        $sort    = in_array($request->query('sort'), self::SORTABLE, true) ? (string) $request->query('sort') : 'jam_masuk';
        $sortDir = $request->query('sort_dir') === 'asc' ? 'asc' : 'desc';

        // --- Pilihan kelas (rombel) sesuai cakupan jurusan ----------------
        $rombelList = $this->rombelOptions($context);

        // Filter rombel di luar cakupan diabaikan (bukan error) agar URL
        // hasil salin-tempel tidak membocorkan data jurusan lain.
        $rombelId = trim((string) $request->query('rombel_id', ''));
        if ($rombelId !== '' && !$rombelList->contains('rombongan_belajar_id', $rombelId)) {
            $rombelId = '';
        }

        // --- Statistik tanggal terpilih ----------------------------------
        $stats = $this->stats($context, $tanggal);

        // --- Data log ----------------------------------------------------
        $logs = $this->logQuery($context, $tanggal, $rombelId, $status, $q)
            ->orderBy($this->sortColumn($sort), $sortDir)
            ->paginate($perPage)
            ->withQueryString();

        $logs->getCollection()->transform(function ($item) {
            $item->foto_profil_url = $this->fotoUrl($item->foto_path);
            $item->foto_masuk_url  = $this->fotoUrl($item->foto_masuk);
            $item->foto_pulang_url = $this->fotoUrl($item->foto_pulang);

            return $item;
        });

        return view('dashboard.laboran.log-presensi', array_merge(
            compact('tanggal', 'logs', 'stats', 'rombelList', 'rombelId', 'status', 'q', 'perPage', 'sort', 'sortDir'),
            $this->abilities(),
            [
                'context'    => $context,
                'scopeBadge' => LaboranScope::badge($context),
            ]
        ));
    }

    // =========================================================================
    // QUERY
    // =========================================================================

    /**
     * Basis query log presensi yang sudah dibatasi jurusan Laboran.
     *
     * @param  array  $context
     * @return \Illuminate\Database\Query\Builder
     */
    private function logBase(array $context)
    {
        $query = DB::table('presensi_harian as ph')
            ->join('peserta_didik as pd', 'ph.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'ph.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id');

        LaboranScope::apply($query, 'rb.jurusan_id', $context);

        return $query;
    }

    private function logQuery(array $context, string $tanggal, string $rombelId, string $status, string $q)
    {
        $query = $this->logBase($context)
            ->where('ph.tanggal', $tanggal)
            ->select(
                'ph.*',
                'pd.nama as nama_siswa',
                'pd.nisn as nisn_siswa',
                'pd.jenis_kelamin',
                'rb.nama as nama_rombel',
                'pdm.foto_path'
            );

        if ($rombelId !== '') {
            $query->where('ph.rombongan_belajar_id', $rombelId);
        }

        if ($status !== '') {
            $query->where('ph.status', $status);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('pd.nama', 'like', "%{$q}%")
                    ->orWhere('pd.nisn', 'like', "%{$q}%");
            });
        }

        return $query;
    }

    private function sortColumn(string $sort): string
    {
        return match ($sort) {
            'nama_siswa'  => 'pd.nama',
            'nama_rombel' => 'rb.nama',
            'jam_pulang'  => 'ph.jam_pulang',
            'status'      => 'ph.status',
            default       => 'ph.jam_masuk',
        };
    }

    /**
     * Ringkasan presensi pada tanggal terpilih, dibatasi jurusan.
     *
     * @return array{total:int,hadir:int,terlambat:int,izin:int,sakit:int,dispen:int,alpha:int,belum:int,persen:float}
     */
    private function stats(array $context, string $tanggal): array
    {
        $siswaQuery = DB::table('peserta_didik as pd')
            ->join('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id');

        LaboranScope::apply($siswaQuery, 'rb.jurusan_id', $context);
        $total = (int) $siswaQuery->count('pd.peserta_didik_id');

        $byStatus = $this->logBase($context)
            ->where('ph.tanggal', $tanggal)
            ->select('ph.status', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('ph.status')
            ->pluck('jumlah', 'status');

        $count = fn (string $key) => (int) ($byStatus[$key] ?? 0);

        $hadir      = $count('H');
        $terlambat  = $count('T');
        $izin       = $count('I');
        $sakit      = $count('S');
        $dispen     = $count('D');
        $alpha      = $count('A');
        $tercatat   = $hadir + $terlambat + $izin + $sakit + $dispen + $alpha;

        return [
            'total'     => $total,
            'hadir'     => $hadir,
            'terlambat' => $terlambat,
            'izin'      => $izin,
            'sakit'     => $sakit,
            'dispen'    => $dispen,
            'alpha'     => $alpha,
            'belum'     => max(0, $total - $tercatat),
            'persen'    => $total > 0 ? round((($hadir + $terlambat) / $total) * 100, 1) : 0.0,
        ];
    }

    /**
     * Daftar rombel (kelas reguler) pada jurusan Laboran.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function rombelOptions(array $context)
    {
        $query = DB::table('rombongan_belajar')
            ->where('jenis_rombel', '1')
            ->select('rombongan_belajar_id', 'nama')
            ->orderBy('tingkat_pendidikan_id')
            ->orderBy('nama');

        LaboranScope::apply($query, 'jurusan_id', $context);

        return $query->get();
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function resolveTanggal(Request $request): string
    {
        $raw = trim((string) $request->query('tanggal', ''));

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) === 1) {
            [$y, $m, $d] = array_map('intval', explode('-', $raw));
            if (checkdate($m, $d, $y)) {
                return $raw;
            }
        }

        return now()->toDateString();
    }

    private function resolvePerPage(Request $request): int
    {
        $perPage = (int) $request->query('perPage', 15);

        return in_array($perPage, [10, 15, 25, 50, 100], true) ? $perPage : 15;
    }

    private function fotoUrl(?string $path): ?string
    {
        return !empty($path) ? asset('storage/' . ltrim($path, '/')) : null;
    }
}
