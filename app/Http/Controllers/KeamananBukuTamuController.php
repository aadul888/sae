<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RolePermission;
use App\Services\KeamananImportService;
use App\Services\QrCodeService;

class KeamananBukuTamuController extends Controller
{
    use \App\Http\Controllers\Concerns\CetakKeamanan;

    /** Unduh template Excel import Buku Tamu. */
    public function templateExcel(KeamananImportService $service)
    {
        $path = $service->generateTemplateFile('tamu');

        return response()->download($path, 'Format_Import_Buku_Tamu.xlsx')->deleteFileAfterSend(true);
    }

    /** Import Buku Tamu via Excel (.xlsx, .xls, .csv). */
    public function importExcel(Request $request, KeamananImportService $service)
    {
        $request->validate(['file' => 'required|file|max:10240']);

        try {
            $hasil = $service->import('tamu', $request->file('file'));
            $pesan = "{$hasil['imported']} baris buku tamu berhasil diimpor" . ($hasil['skipped'] > 0 ? ", {$hasil['skipped']} baris dilewati." : '.');

            return response()->json(['status' => 'success', 'message' => $pesan]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function index(Request $request)
    {
        $user = session('user');
        $role = $user['role'] ?? 'tendik';

        $canCreate = RolePermission::canAccess($user, 'menu_buku_tamu', 'create') || RolePermission::canAccess($user, 'menu_keamanan', 'create');
        $canRead = RolePermission::canAccess($user, 'menu_buku_tamu', 'read') || RolePermission::canAccess($user, 'menu_keamanan', 'read');
        $canUpdate = RolePermission::canAccess($user, 'menu_buku_tamu', 'update') || RolePermission::canAccess($user, 'menu_keamanan', 'update');
        $canDelete = RolePermission::canAccess($user, 'menu_buku_tamu', 'delete') || RolePermission::canAccess($user, 'menu_keamanan', 'delete');

        if (!$canRead && !in_array($role, ['admin', 'tendik', 'satpam'])) {
            return redirect()->route('dashboard.' . $role)->with('error', 'Anda tidak memiliki hak akses ke Buku Tamu.');
        }

        $search = $request->get('q', $request->get('search', ''));
        $status = $request->get('status', '');
        $tanggal = $request->get('tanggal', '');
        $jenisTamu = $request->get('jenis_tamu', '');
        $perPage = (int) $request->get('perPage', 15);
        $sort = $request->get('sort', 'id');
        $sortDir = $request->get('sortDir', 'desc');

        $query = DB::table('keamanan_buku_tamu');

        if ($tanggal) {
            $query->where('tanggal', $tanggal);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($jenisTamu) {
            $query->where('jenis_tamu', $jenisTamu);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_tamu', 'like', "%{$search}%")
                    ->orWhere('instansi_asal', 'like', "%{$search}%")
                    ->orWhere('tujuan_bertemu', 'like', "%{$search}%")
                    ->orWhere('keperluan', 'like', "%{$search}%")
                    ->orWhere('nomor_kartu_visitor', 'like', "%{$search}%")
                    ->orWhere('nama_siswa', 'like', "%{$search}%")
                    ->orWhere('rombel_siswa', 'like', "%{$search}%")
                    ->orWhere('nik_tamu', 'like', "%{$search}%")
                    ->orWhere('nisn_siswa', 'like', "%{$search}%");
            });
        }

        $list = $query->orderBy($sort, $sortDir)->paginate($perPage)->withQueryString();

        $stats = [
            'total' => DB::table('keamanan_buku_tamu')->count(),
            'hari_ini' => DB::table('keamanan_buku_tamu')->where('tanggal', date('Y-m-d'))->count(),
            'di_lokasi' => DB::table('keamanan_buku_tamu')->where('status', 'berada_di_lokasi')->count(),
            'sudah_keluar' => DB::table('keamanan_buku_tamu')->where('status', 'sudah_keluar')->count(),
            'mandiri' => DB::table('keamanan_buku_tamu')->where('is_mandiri', 1)->count(),
        ];

        $daftarRuang = DB::table('sarpras_ruang')->select('id', 'kode_ruang', 'nama_ruang', 'gedung')->orderBy('nama_ruang')->get();
        $publicFormUrl = route('tamu.form');
        $qrSvg = QrCodeService::generateSvg($publicFormUrl, 200, 1);

        return view('dashboard.keamanan.buku-tamu', compact(
            'list', 'search', 'status', 'tanggal', 'jenisTamu', 'perPage', 'sort', 'sortDir',
            'daftarRuang', 'publicFormUrl', 'qrSvg',
            'canCreate', 'canRead', 'canUpdate', 'canDelete', 'stats'
        ));
    }

    /** Halaman cetak Buku Tamu (ikut filter aktif halaman). */
    public function cetak(Request $request)
    {
        $search = $request->get('q', $request->get('search', ''));
        $status = $request->get('status', '');
        $tanggal = $request->get('tanggal', '');
        $jenisTamu = $request->get('jenis_tamu', '');
        $sort = $request->get('sort', 'id');
        $sortDir = $request->get('sortDir', 'desc');

        $periode = $this->periodeDariRequest($request);

        $query = DB::table('keamanan_buku_tamu');
        if ($periode) {
            $query->whereBetween('tanggal', [$periode['start'], $periode['end']]);
        } elseif ($tanggal) {
            $query->where('tanggal', $tanggal);
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($jenisTamu) {
            $query->where('jenis_tamu', $jenisTamu);
        }
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_tamu', 'like', "%{$search}%")
                    ->orWhere('instansi_asal', 'like', "%{$search}%")
                    ->orWhere('tujuan_bertemu', 'like', "%{$search}%")
                    ->orWhere('keperluan', 'like', "%{$search}%")
                    ->orWhere('nomor_kartu_visitor', 'like', "%{$search}%")
                    ->orWhere('nama_siswa', 'like', "%{$search}%")
                    ->orWhere('rombel_siswa', 'like', "%{$search}%")
                    ->orWhere('nik_tamu', 'like', "%{$search}%")
                    ->orWhere('nisn_siswa', 'like', "%{$search}%");
            });
        }

        $rows = $query->orderBy($sort, $sortDir)->limit(1000)->get();

        $filterInfo = ($periode ? 'Periode: ' . $periode['label'] : 'Tanggal: ' . ($tanggal ?: 'Semua'))
            . ' • Status: ' . ($status ? ucfirst(str_replace('_', ' ', $status)) : 'Semua')
            . ' • Kategori: ' . ($jenisTamu ? ucfirst(str_replace('_', ' ', $jenisTamu)) : 'Semua')
            . ($search ? " • Pencarian: \"{$search}\"" : '');

        return $this->renderCetak('tamu', 'Laporan Buku Tamu / Kunjungan', $filterInfo, $rows);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_tamu' => 'required|string|max:255',
            'tujuan_bertemu' => 'required|string|max:255',
            'keperluan' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'jam_masuk' => 'required',
            'jenis_tamu' => 'nullable|string|max:50',
            'nomor_kontak' => 'nullable|string|max:30',
            'nip_tamu' => 'nullable|string|max:50',
            'jabatan_tamu' => 'nullable|string|max:150',
            'ada_surat_tugas' => 'nullable|in:0,1',
            'nomor_surat_tugas' => 'nullable|string|max:150',
            'tanggal_surat_tugas' => 'nullable|date',
        ]);

        $user = session('user');
        $ptkId = $user['ptk_id'] ?? null;
        $adaSurat = (bool) $request->input('ada_surat_tugas', 0);

        DB::table('keamanan_buku_tamu')->insert([
            'tanggal' => $request->tanggal,
            'jam_masuk' => $request->jam_masuk,
            'jam_keluar' => $request->jam_keluar ?: null,
            'nama_tamu' => $request->nama_tamu,
            'jenis_tamu' => $request->jenis_tamu ?: 'umum',
            'nik_tamu' => $request->nik_tamu ?: null,
            'nip_tamu' => $request->nip_tamu ?: null,
            'jabatan_tamu' => $request->jabatan_tamu ?: null,
            'peserta_didik_id' => $request->peserta_didik_id ?: null,
            'nisn_siswa' => $request->nisn_siswa ?: null,
            'nama_siswa' => $request->nama_siswa ?: null,
            'rombel_siswa' => $request->rombel_siswa ?: null,
            'instansi_asal' => $request->instansi_asal ?: null,
            'ada_surat_tugas' => $adaSurat ? 1 : 0,
            'nomor_surat_tugas' => $adaSurat ? ($request->nomor_surat_tugas ?: null) : null,
            'tanggal_surat_tugas' => $adaSurat ? ($request->tanggal_surat_tugas ?: null) : null,
            'nomor_kontak' => $request->nomor_kontak ?: null,
            'tujuan_bertemu' => $request->tujuan_bertemu,
            'keperluan' => $request->keperluan,
            'nomor_kartu_visitor' => $request->nomor_kartu_visitor ?: null,
            'nomor_polisi_kendaraan' => $request->nomor_polisi_kendaraan ?: null,
            'petugas_satpam_ptk_id' => $ptkId,
            'is_mandiri' => 0,
            'status' => $request->status ?? 'berada_di_lokasi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('dashboard.keamanan.buku-tamu.index')->with('success', 'Buku tamu berhasil dicatat.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_tamu' => 'required|string|max:255',
            'tujuan_bertemu' => 'required|string|max:255',
            'keperluan' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'jam_masuk' => 'required',
            'jenis_tamu' => 'nullable|string|max:50',
            'nip_tamu' => 'nullable|string|max:50',
            'jabatan_tamu' => 'nullable|string|max:150',
            'ada_surat_tugas' => 'nullable|in:0,1',
            'nomor_surat_tugas' => 'nullable|string|max:150',
            'tanggal_surat_tugas' => 'nullable|date',
        ]);

        $adaSurat = (bool) $request->input('ada_surat_tugas', 0);

        DB::table('keamanan_buku_tamu')->where('id', $id)->update([
            'tanggal' => $request->tanggal,
            'jam_masuk' => $request->jam_masuk,
            'jam_keluar' => $request->jam_keluar ?: null,
            'nama_tamu' => $request->nama_tamu,
            'jenis_tamu' => $request->jenis_tamu ?: 'umum',
            'nik_tamu' => $request->nik_tamu ?: null,
            'nip_tamu' => $request->nip_tamu ?: null,
            'jabatan_tamu' => $request->jabatan_tamu ?: null,
            'peserta_didik_id' => $request->peserta_didik_id ?: null,
            'nisn_siswa' => $request->nisn_siswa ?: null,
            'nama_siswa' => $request->nama_siswa ?: null,
            'rombel_siswa' => $request->rombel_siswa ?: null,
            'instansi_asal' => $request->instansi_asal ?: null,
            'ada_surat_tugas' => $adaSurat ? 1 : 0,
            'nomor_surat_tugas' => $adaSurat ? ($request->nomor_surat_tugas ?: null) : null,
            'tanggal_surat_tugas' => $adaSurat ? ($request->tanggal_surat_tugas ?: null) : null,
            'nomor_kontak' => $request->nomor_kontak ?: null,
            'tujuan_bertemu' => $request->tujuan_bertemu,
            'keperluan' => $request->keperluan,
            'nomor_kartu_visitor' => $request->nomor_kartu_visitor ?: null,
            'nomor_polisi_kendaraan' => $request->nomor_polisi_kendaraan ?: null,
            'status' => $request->status ?? 'berada_di_lokasi',
            'updated_at' => now(),
        ]);

        return redirect()->route('dashboard.keamanan.buku-tamu.index')->with('success', 'Data buku tamu berhasil diperbarui.');
    }

    public function checkout($id)
    {
        DB::table('keamanan_buku_tamu')->where('id', $id)->update([
            'jam_keluar' => now()->format('H:i:s'),
            'status' => 'sudah_keluar',
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Tamu berhasil di-checkout.');
    }

    public function destroy($id)
    {
        DB::table('keamanan_buku_tamu')->where('id', $id)->delete();
        return redirect()->route('dashboard.keamanan.buku-tamu.index')->with('success', 'Data tamu berhasil dihapus.');
    }

    /**
     * Cetak Lembar / Standing Poster QR Code e-Tamu
     */
    public function cetakQr()
    {
        $sekolah = DB::table('sekolah')->first();
        $sekolahMeta = DB::table('sekolah_meta')->first();
        $targetUrl = route('tamu.form');
        $qrSvg = QrCodeService::generateSvg($targetUrl, 280, 1);

        return view('dashboard.keamanan.buku-tamu-cetak-qr', compact('sekolah', 'sekolahMeta', 'targetUrl', 'qrSvg'));
    }

    /**
     * Halaman Publik e-Tamu (Monitoring Statistik & Daftar Kunjungan Hari Ini + QR Code Mandiri)
     */
    public function publicIndex()
    {
        $sekolah = DB::table('sekolah')->first();
        $sekolahMeta = DB::table('sekolah_meta')->first();
        $today = date('Y-m-d');

        $stats = [
            'hari_ini' => DB::table('keamanan_buku_tamu')->where('tanggal', $today)->count(),
            'di_lokasi' => DB::table('keamanan_buku_tamu')->where('tanggal', $today)->where('status', 'berada_di_lokasi')->count(),
            'orang_tua' => DB::table('keamanan_buku_tamu')->where('tanggal', $today)->where('jenis_tamu', 'orang_tua')->count(),
            'selesai' => DB::table('keamanan_buku_tamu')->where('tanggal', $today)->where('status', 'sudah_keluar')->count(),
        ];

        $running_info = [
            'Portal e-Tamu Pos Gerbang: Tamu umum, instansi, & orang tua murid wajib registrasi mandiri via scan QR Code.',
            'Layanan buku tamu pos keamanan terhubung langsung dengan sistem otorisasi kartu visitor sekolah.',
            'Pengunjung diharapkan melapor kembali ke Pos Satpam sebelum meninggalkan lingkungan sekolah.',
        ];

        $daftarTamu = DB::table('keamanan_buku_tamu')
            ->where('tanggal', $today)
            ->orderBy('jam_masuk', 'desc')
            ->limit(20)
            ->get();

        $formUrl = route('tamu.form');
        $qrSvg = QrCodeService::generateSvg($formUrl, 200, 1);

        return view('keamanan.tamu-publik', compact('sekolah', 'sekolahMeta', 'stats', 'running_info', 'daftarTamu', 'formUrl', 'qrSvg'));
    }

    /**
     * Formulir Publik Tamu Mandiri (Akses Scan QR Code / Direct Link)
     */
    public function publicForm()
    {
        $sekolah = DB::table('sekolah')->first();
        $sekolahMeta = DB::table('sekolah_meta')->first();
        $daftarRuang = DB::table('sarpras_ruang')->select('nama_ruang')->orderBy('nama_ruang')->get();

        return view('keamanan.tamu-mandiri', compact('sekolah', 'sekolahMeta', 'daftarRuang'));
    }

    /**
     * Simpan Pengisian Mandiri Tamu
     */
    public function publicSubmit(Request $request)
    {
        $request->validate([
            'nama_tamu' => 'required|string|max:255',
            'jenis_tamu' => 'required|string|max:50',
            'nomor_kontak' => 'nullable|string|max:30',
            'tujuan_bertemu' => 'required|string|max:255',
            'keperluan' => 'required|string|max:255',
            'instansi_asal' => 'nullable|string|max:255',
            'nomor_polisi_kendaraan' => 'nullable|string|max:30',
            'nik_tamu' => 'nullable|string|max:30',
            'nisn_siswa' => 'nullable|string|max:30',
            'nama_siswa' => 'nullable|string|max:200',
            'rombel_siswa' => 'nullable|string|max:100',
            'peserta_didik_id' => 'nullable|string|max:50',
            'nip_tamu' => 'nullable|string|max:50',
            'jabatan_tamu' => 'nullable|string|max:150',
            'ada_surat_tugas' => 'nullable|in:0,1',
            'nomor_surat_tugas' => 'nullable|required_if:ada_surat_tugas,1|string|max:150',
            'tanggal_surat_tugas' => 'nullable|required_if:ada_surat_tugas,1|date',
        ]);

        $adaSurat = (bool) $request->input('ada_surat_tugas', 0);

        $id = DB::table('keamanan_buku_tamu')->insertGetId([
            'tanggal' => date('Y-m-d'),
            'jam_masuk' => date('H:i:s'),
            'jam_keluar' => null,
            'nama_tamu' => $request->nama_tamu,
            'jenis_tamu' => $request->jenis_tamu,
            'nik_tamu' => $request->nik_tamu ?: null,
            'nip_tamu' => $request->nip_tamu ?: null,
            'jabatan_tamu' => $request->jabatan_tamu ?: null,
            'peserta_didik_id' => $request->peserta_didik_id ?: null,
            'nisn_siswa' => $request->nisn_siswa ?: null,
            'nama_siswa' => $request->nama_siswa ?: null,
            'rombel_siswa' => $request->rombel_siswa ?: null,
            'instansi_asal' => $request->instansi_asal ?: ($request->jenis_tamu === 'orang_tua' ? 'Orang Tua / Wali Siswa' : null),
            'ada_surat_tugas' => $adaSurat ? 1 : 0,
            'nomor_surat_tugas' => $adaSurat ? ($request->nomor_surat_tugas ?: null) : null,
            'tanggal_surat_tugas' => $adaSurat ? ($request->tanggal_surat_tugas ?: null) : null,
            'nomor_kontak' => $request->nomor_kontak ?: null,
            'tujuan_bertemu' => $request->tujuan_bertemu,
            'keperluan' => $request->keperluan,
            'nomor_kartu_visitor' => null,
            'nomor_polisi_kendaraan' => $request->nomor_polisi_kendaraan ?: null,
            'petugas_satpam_ptk_id' => null,
            'is_mandiri' => 1,
            'status' => 'berada_di_lokasi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('tamu.success', $id);
    }

    /**
     * Halaman Sukses & Bukti Tiket Tamu Digital
     */
    public function publicSuccess($id)
    {
        $tamu = DB::table('keamanan_buku_tamu')->where('id', $id)->first();
        if (!$tamu) {
            return redirect()->route('tamu.public')->with('error', 'Catatan kunjungan tidak ditemukan.');
        }

        $sekolah = DB::table('sekolah')->first();
        $sekolahMeta = DB::table('sekolah_meta')->first();

        return view('keamanan.tamu-sukses', compact('tamu', 'sekolah', 'sekolahMeta'));
    }

    /**
     * API Deteksi Otomatis Anak / Siswa via NIK Ortu atau NISN/NIK Siswa
     */
    public function checkStudentApi(Request $request)
    {
        $rawQuery = trim((string) ($request->get('q') ?? $request->get('nisn_siswa', '')));
        $nikOrtu = preg_replace('/[^0-9]/', '', (string) ($request->get('nik_ortu') ?? ''));
        if ($nikOrtu === '' && is_numeric($rawQuery) && strlen($rawQuery) >= 12) {
            $nikOrtu = $rawQuery;
        }
        $numericQuery = preg_replace('/[^0-9]/', '', $rawQuery);

        $students = collect();
        $suggestedParentName = null;
        $matchedRole = null;

        // 1. Deteksi melalui NIK Orang Tua
        if ($nikOrtu !== '') {
            $identitasRows = DB::table('peserta_didik_identitas')
                ->where('nik_ayah', $nikOrtu)
                ->orWhere('nik_ibu', $nikOrtu)
                ->orWhere('nik_wali', $nikOrtu)
                ->get();

            if ($identitasRows->isNotEmpty()) {
                $first = $identitasRows->first();
                if ($first->nik_ayah === $nikOrtu) {
                    $suggestedParentName = $first->nama_ayah;
                    $matchedRole = 'Ayah Kandung';
                } elseif ($first->nik_ibu === $nikOrtu) {
                    $suggestedParentName = $first->nama_ibu;
                    $matchedRole = 'Ibu Kandung';
                } elseif ($first->nik_wali === $nikOrtu) {
                    $suggestedParentName = $first->nama_wali;
                    $matchedRole = 'Wali Siswa';
                }

                $pdIds = $identitasRows->pluck('peserta_didik_id');
                $matchedStudents = DB::table('peserta_didik')
                    ->whereIn('peserta_didik_id', $pdIds)
                    ->select('peserta_didik_id', 'nama', 'nisn', 'nik', 'nama_rombel', 'nama_ayah', 'nama_ibu', 'nama_wali')
                    ->get();

                $students = $students->merge($matchedStudents);
            }
        }

        // 2. Deteksi melalui NISN, NIK, NIPD, atau Nama Siswa
        if ($rawQuery !== '') {
            $siswaQuery = DB::table('peserta_didik')
                ->select('peserta_didik_id', 'nama', 'nisn', 'nik', 'nama_rombel', 'nama_ayah', 'nama_ibu', 'nama_wali');

            if ($numericQuery !== '' && strlen($numericQuery) >= 3) {
                $siswaQuery->where(function ($q) use ($numericQuery, $rawQuery) {
                    $q->where('nisn', 'like', "%{$numericQuery}%")
                        ->orWhere('nik', 'like', "%{$numericQuery}%")
                        ->orWhere('nipd', 'like', "%{$numericQuery}%");
                    if (strlen($rawQuery) >= 2) {
                        $q->orWhere('nama', 'like', '%' . $rawQuery . '%');
                    }
                });
            } elseif (strlen($rawQuery) >= 2) {
                // Pencarian berdasarkan nama siswa (minimal 2 karakter)
                $siswaQuery->where('nama', 'like', '%' . $rawQuery . '%');
            } else {
                $siswaQuery = null;
            }

            if ($siswaQuery) {
                $siswaResult = $siswaQuery->limit(12)->get();
                if ($siswaResult->isNotEmpty()) {
                    $students = $students->merge($siswaResult);
                    if (!$suggestedParentName) {
                        $firstPd = $siswaResult->first();
                        $suggestedParentName = $firstPd->nama_ayah ?: ($firstPd->nama_ibu ?: $firstPd->nama_wali);
                        $matchedRole = $firstPd->nama_ayah ? 'Orang Tua (Ayah)' : ($firstPd->nama_ibu ? 'Orang Tua (Ibu)' : 'Orang Tua / Wali');
                    }
                }
            }
        }

        $uniqueStudents = $students->unique('peserta_didik_id')->values();

        return response()->json([
            'success' => true,
            'found' => $uniqueStudents->isNotEmpty(),
            'count' => $uniqueStudents->count(),
            'suggested_parent_name' => $suggestedParentName,
            'matched_role' => $matchedRole,
            'students' => $uniqueStudents,
        ]);
    }
}
