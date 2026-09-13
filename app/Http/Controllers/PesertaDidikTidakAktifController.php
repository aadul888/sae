<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\PesertaDidikTidakAktif;
use App\Models\RolePermission;

class PesertaDidikTidakAktifController extends Controller
{
    private const SORTABLE = ['nama', 'nisn', 'nipd', 'jenis_kelamin', 'nama_rombel_terakhir', 'tingkat_pendidikan_terakhir', 'tahun_lulus', 'status_keluar'];

    public function index(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if ($role === 'peserta_didik' || !RolePermission::canAccess($user, 'menu_peserta_didik_tidak_aktif')) {
            return redirect()->route('dashboard.' . ($role ?: 'peserta_didik'))->with('error', 'Akses ke menu Peserta Didik Tidak Aktif dinonaktifkan.');
        }

        $q       = trim($request->get('q', ''));
        $status  = trim($request->get('status', ''));
        $tahun   = trim($request->get('tahun', ''));
        $perPage = (int) $request->get('perPage', 15);
        $sort    = in_array($request->get('sort'), self::SORTABLE, true) ? $request->get('sort') : 'nama';
        $sortDir = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        if (!Schema::hasTable('peserta_didik_tidak_aktif')) {
            return view('dashboard.peserta-didik-tidak-aktif', [
                'list' => collect(),
                'total' => 0,
                'summary' => ['total' => 0, 'alumni' => 0, 'mutasi' => 0, 'berfoto' => 0],
                'filterTahun' => collect(),
                'q' => $q,
                'status' => $status,
                'tahun' => $tahun,
                'perPage' => $perPage,
                'sort' => $sort,
                'sortDir' => $sortDir,
                'grade12ActiveCount' => 0,
            ]);
        }

        // Jamin kepastian: Siswa yang saat ini aktif di tabel peserta_didik TIDAK BOLEH muncul ganda di tidak aktif
        $activePdIds = Schema::hasTable('peserta_didik')
            ? DB::table('peserta_didik')->pluck('peserta_didik_id')->filter()->all()
            : [];

        $baseTidakAktif = DB::table('peserta_didik_tidak_aktif');
        if (!empty($activePdIds)) {
            $baseTidakAktif->whereNotIn('peserta_didik_id', $activePdIds);
        }

        // Summary counts
        $totalAll   = (clone $baseTidakAktif)->count();
        $totalAlumni = (clone $baseTidakAktif)->where('status_keluar', 'Alumni')->count();
        $totalMutasi = (clone $baseTidakAktif)->where('status_keluar', '<>', 'Alumni')->count();
        $totalBerfoto = (clone $baseTidakAktif)->whereNotNull('foto_path')->where('foto_path', '<>', '')->count();

        $summary = [
            'total'   => $totalAll,
            'alumni'  => $totalAlumni,
            'mutasi'  => $totalMutasi,
            'berfoto' => $totalBerfoto,
        ];

        // Daftar filter tahun kelulusan
        $filterTahun = (clone $baseTidakAktif)
            ->whereNotNull('tahun_lulus')
            ->where('tahun_lulus', '<>', '')
            ->distinct()
            ->pluck('tahun_lulus')
            ->sortDesc()
            ->values();

        $grade12ActiveCount = 0;

        $query = clone $baseTidakAktif;

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('nama', 'LIKE', "%{$q}%")
                    ->orWhere('nisn', 'LIKE', "%{$q}%")
                    ->orWhere('nipd', 'LIKE', "%{$q}%")
                    ->orWhere('nik', 'LIKE', "%{$q}%")
                    ->orWhere('nama_rombel_terakhir', 'LIKE', "%{$q}%");
            });
        }

        if ($status !== '') {
            if ($status === 'Alumni') {
                $query->where('status_keluar', 'Alumni');
            } else {
                $query->where('status_keluar', '<>', 'Alumni');
            }
        }

        if ($tahun !== '') {
            $query->where('tahun_lulus', $tahun);
        }

        $totalFiltered = $query->count();
        $query->orderBy($sort, $sortDir);

        $currentPage = (int) $request->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        $items = $query->skip($offset)->take($perPage)->get();

        foreach ($items as $item) {
            $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : null;
        }

        $list = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $totalFiltered,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('dashboard.peserta-didik-tidak-aktif', compact(
            'list',
            'summary',
            'filterTahun',
            'q',
            'status',
            'tahun',
            'perPage',
            'sort',
            'sortDir',
            'grade12ActiveCount'
        ));
    }

    /**
     * Detail siswa tidak aktif untuk modal
     */
    public function show(Request $request, string|int $id)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        $item = DB::table('peserta_didik_tidak_aktif')
            ->where('id', $id)
            ->orWhere('peserta_didik_id', $id)
            ->first();

        if (!$item) {
            return response()->json(['status' => 'error', 'message' => 'Data arsip siswa tidak ditemukan.'], 404);
        }

        $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : null;

        return response()->json([
            'status' => 'success',
            'data' => $item,
        ]);
    }



    /**
     * Ekspor daftar siswa tidak aktif ke file Excel CSV
     */
    public function exportExcel(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');

        $status = trim($request->get('status', ''));
        $tahun  = trim($request->get('tahun', ''));

        $query = DB::table('peserta_didik_tidak_aktif');
        if (Schema::hasTable('peserta_didik')) {
            $activePdIds = DB::table('peserta_didik')->pluck('peserta_didik_id')->filter()->all();
            if (!empty($activePdIds)) {
                $query->whereNotIn('peserta_didik_id', $activePdIds);
            }
        }
        if ($status !== '') {
            if ($status === 'Alumni') {
                $query->where('status_keluar', 'Alumni');
            } else {
                $query->where('status_keluar', '<>', 'Alumni');
            }
        }
        if ($tahun !== '') {
            $query->where('tahun_lulus', $tahun);
        }

        $items = $query->orderByDesc('tahun_lulus')->orderBy('nama')->get();

        $filename = 'Data_Alumni_Siswa_Tidak_Aktif_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($items) {
            $output = fopen('php://output', 'w');
            // UTF-8 BOM
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, [
                'No',
                'Status',
                'Tahun Kelulusan / Keluar',
                'Tanggal Keluar',
                'Nama Lengkap',
                'NISN',
                'NIPD',
                'NIK',
                'Jenis Kelamin',
                'Rombel Terakhir',
                'Tingkat Terakhir',
                'Alasan Keluar',
                'No HP',
                'Alamat'
            ]);

            $no = 1;
            foreach ($items as $r) {
                fputcsv($output, [
                    $no++,
                    $r->status_keluar ?? 'Alumni',
                    $r->tahun_lulus ?? '-',
                    $r->tanggal_keluar ?? '-',
                    $r->nama ?? '-',
                    $r->nisn ? "'" . $r->nisn : '-',
                    $r->nipd ? "'" . $r->nipd : '-',
                    $r->nik ? "'" . $r->nik : '-',
                    $r->jenis_kelamin === 'L' ? 'Laki-laki' : ($r->jenis_kelamin === 'P' ? 'Perempuan' : ($r->jenis_kelamin ?? '-')),
                    $r->nama_rombel_terakhir ?? '-',
                    $r->tingkat_pendidikan_terakhir ?? '-',
                    $r->alasan_keluar ?? '-',
                    $r->nomor_telepon_seluler ?? '-',
                    $r->alamat_jalan ?? '-'
                ]);
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Arsipkan siswa kelas XII yang masih aktif
     */
    public function archiveGrade12(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');

        if (!RolePermission::canAccess($user, 'menu_peserta_didik_tidak_aktif')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $grade12ActiveCount = (int) $request->get('count', 0);

        if ($grade12ActiveCount === 0) {
            return response()->json(['status' => 'error', 'message' => 'Tidak ada siswa kelas XII yang perlu diarsipkan.'], 400);
        }

        // Ambil siswa kelas XII yang masih aktif
        $grade12Students = DB::table('peserta_didik')
            ->where('tingkat_pendidikan', 12)
            ->where('status', 'Aktif')
            ->pluck('peserta_didik_id')
            ->filter()
            ->all();

        if (empty($grade12Students)) {
            return response()->json(['status' => 'error', 'message' => 'Tidak ada siswa kelas XII yang ditemukan.'], 404);
        }

        // Pindahkan ke tabel tidak aktif
        $movedCount = 0;
        foreach ($grade12Students as $studentId) {
            $existing = DB::table('peserta_didik_tidak_aktif')
                ->where('peserta_didik_id', $studentId)
                ->first();

            if (!$existing) {
                $student = DB::table('peserta_didik')
                    ->where('peserta_didik_id', $studentId)
                    ->first();

                if ($student) {
                    DB::table('peserta_didik_tidak_aktif')->insert([
                        'peserta_didik_id' => $student->peserta_didik_id,
                        'nama' => $student->nama,
                        'nisn' => $student->nisn,
                        'nipd' => $student->nipd,
                        'nik' => $student->nik,
                        'jenis_kelamin' => $student->jenis_kelamin,
                        'nama_rombel_terakhir' => $student->nama_rombel,
                        'tingkat_pendidikan_terakhir' => $student->tingkat_pendidikan,
                        'tahun_lulus' => date('Y'),
                        'tanggal_keluar' => now(),
                        'status_keluar' => 'Alumni',
                        'alasan_keluar' => 'Lulus',
                        'nomor_telepon_seluler' => $student->no_hp,
                        'alamat_jalan' => $student->alamat,
                        'foto_path' => $student->foto_path,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $movedCount++;
                }
            }
        }

        // Hapus dari tabel aktif
        DB::table('peserta_didik')
            ->whereIn('peserta_didik_id', $grade12Students)
            ->update(['status' => 'Tidak Aktif']);

        return response()->json([
            'status' => 'success',
            'message' => "Berhasil mengarsipkan {$movedCount} siswa kelas XII.",
            'moved_count' => $movedCount,
        ]);
    }
}
