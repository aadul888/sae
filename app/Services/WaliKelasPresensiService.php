<?php

namespace App\Services;

use App\Models\JadwalPengaturan;
use App\Models\KalenderPendidikan;
use App\Models\NotifikasiTransaksi;
use App\Models\PresensiHarian;
use App\Models\PresensiIzin;
use App\Models\PresensiPengaturan;
use App\Models\SekolahMeta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use App\Support\Table;
/**
 * Service khusus rekapitulasi, pencatatan manual, approval izin, dan ekspor presensi kelas binaan.
 * ponytail: static-orchestrator; upgrade ke asynchronous job jika ekspor rekap matriks bulanan melibatkan > 10.000 log.
 */
class WaliKelasPresensiService
{
    /**
     * Kumpulkan seluruh dataset untuk tampilan halaman Presensi Kelas.
     */
    public static function getPresensiData(array $ctx, Request $request, bool $canUpdate): array
    {
        $activeRombel = $ctx['activeRombel'];

        $activeTab = $request->get('tab', 'harian');
        if (!in_array($activeTab, ['harian', 'izin'], true)) {
            $activeTab = 'harian';
        }

        $tanggal = $request->get('tanggal', now()->toDateString());
        $q = trim($request->get('q', ''));
        $statusFilter = trim($request->get('status_filter', ''));
        $perPage = (int) $request->get('perPage', 15);
        $sort    = in_array($request->get('sort'), ['nama', 'nisn', 'status', 'jam_masuk'], true) ? $request->get('sort') : 'nama';
        $sortDir = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        $statusHari = KalenderPendidikan::getStatusHari($tanggal, 'pd');
        $pengaturan = PresensiPengaturan::getPengaturan();
        $isLiburHari = ($statusHari['is_libur'] ?? false) || (class_exists(JadwalPengaturan::class) ? !JadwalPengaturan::isHariAktif(Carbon::parse($tanggal)) : false);

        if (!$activeRombel) {
            return [
                'hasRombel'        => false,
                'isAdmin'          => $ctx['isAdmin'],
                'rombelList'       => $ctx['rombelList'],
                'activeRombel'     => null,
                'waliNama'         => $ctx['waliNama'],
                'tanggal'          => $tanggal,
                'statusHari'       => $statusHari,
                'isLiburHari'      => $isLiburHari,
                'pengaturan'       => $pengaturan,
                'list'             => new LengthAwarePaginator([], 0, $perPage),
                'total'            => 0,
                'totalSiswa'       => 0,
                'summary'          => [
                    'total' => 0, 'hadir' => 0, 'terlambat' => 0, 'izin' => 0,
                    'sakit' => 0, 'alpha' => 0, 'pulang' => 0, 'belum' => 0, 'persen' => 0
                ],
                'q'                => $q,
                'statusFilter'     => $statusFilter,
                'perPage'          => $perPage,
                'sort'             => $sort,
                'sortDir'          => $sortDir,
                'canUpdate'        => $canUpdate,
                'activeTab'        => $activeTab,
                'pendingIzinCount' => 0,
                'totalIzinCount'   => 0,
                'izinSummary'      => ['total' => 0, 'menunggu' => 0, 'disetujui' => 0, 'ditolak' => 0],
                'izinList'         => new LengthAwarePaginator([], 0, $perPage, 1, ['path' => $request->url(), 'query' => $request->query()]),
                'qIzin'            => '',
                'statusIzinFilter' => '',
                'jenisIzinFilter'  => '',
            ];
        }

        $targetRombelId = $activeRombel->rombongan_belajar_id ?? null;

        $query = DB::table('peserta_didik as pd')
            ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
            ->leftJoin('presensi_harian as ph', function ($join) use ($tanggal) {
                $join->on('pd.peserta_didik_id', '=', 'ph.peserta_didik_id')
                    ->where('ph.tanggal', '=', $tanggal);
            })
            ->where('pd.rombongan_belajar_id', $targetRombelId)
            ->select(
                'pd.peserta_didik_id',
                'pd.nama',
                'pd.nisn',
                'pd.nipd',
                'pd.jenis_kelamin',
                'pdm.foto_path',
                'pdm.rfid_uid',
                'ph.id as presensi_id',
                'ph.status',
                'ph.jam_masuk',
                'ph.jam_pulang',
                'ph.menit_terlambat',
                'ph.status_ketepatan_masuk',
                'ph.status_ketepatan_pulang',
                'ph.metode_masuk',
                'ph.metode_pulang',
                'ph.keterangan',
                'ph.verified_by'
            );

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('pd.nama', 'like', "%{$q}%")
                    ->orWhere('pd.nisn', 'like', "%{$q}%")
                    ->orWhere('pd.nipd', 'like', "%{$q}%");
            });
        }

        if ($statusFilter !== '') {
            if ($statusFilter === 'pulang') {
                $query->whereNotNull('ph.jam_pulang');
            } elseif ($statusFilter === 'belum') {
                $query->whereNull('ph.id');
            } else {
                $query->where('ph.status', $statusFilter);
            }
        }

        if ($sort === 'status') {
            $query->orderBy('ph.status', $sortDir)->orderBy('pd.nama', 'asc');
        } elseif ($sort === 'jam_masuk') {
            $query->orderBy('ph.jam_masuk', $sortDir)->orderBy('pd.nama', 'asc');
        } elseif ($sort === 'nisn') {
            $query->orderBy('pd.nisn', $sortDir)->orderBy('pd.nama', 'asc');
        } else {
            $query->orderBy('pd.nama', $sortDir);
        }

        $list = $query->paginate($perPage)->appends($request->query());

        $totalSiswa = DB::table('peserta_didik')->where('rombongan_belajar_id', $targetRombelId)->count();
        $allPresensiHariIni = DB::table('presensi_harian')
            ->where('rombongan_belajar_id', $targetRombelId)
            ->where('tanggal', $tanggal)
            ->get();

        $countHadir     = $allPresensiHariIni->where('status', 'H')->count();
        $countTerlambat = $allPresensiHariIni->where('status', 'T')->count();
        $countIzin      = $allPresensiHariIni->where('status', 'I')->count();
        $countSakit     = $allPresensiHariIni->where('status', 'S')->count();
        $countDispen    = $allPresensiHariIni->where('status', 'D')->count();
        $countAlpha     = $allPresensiHariIni->where('status', 'A')->count();
        $countPulang    = $allPresensiHariIni->whereNotNull('jam_pulang')->count();
        $countSudahPresensi = $allPresensiHariIni->count();
        $countBelum     = max(0, $totalSiswa - $countSudahPresensi);

        $persenKehadiran = $totalSiswa > 0
            ? min(100.0, round((($countHadir + $countTerlambat + $countDispen) / $totalSiswa) * 100, 1))
            : 0;

        $summary = [
            'total'     => $totalSiswa,
            'hadir'     => $countHadir,
            'terlambat' => $countTerlambat,
            'izin'      => $countIzin,
            'sakit'     => $countSakit,
            'dispen'    => $countDispen,
            'alpha'     => $countAlpha,
            'pulang'    => $countPulang,
            'belum'     => $countBelum,
            'persen'    => $persenKehadiran,
        ];

        // Format Siswa & Evaluasi Kunci
        $list->getCollection()->transform(function ($item) {
            $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : null;
            $item->status_label = PresensiHarian::STATUS_LABELS[$item->status] ?? ($item->status ?: 'Belum');
            $item->status_badge = PresensiHarian::STATUS_BADGES[$item->status] ?? '<span class="badge badge-outline"><i class="fas fa-circle-question me-1"></i> Belum</span>';

            $item->is_mandiri = !empty($item->presensi_id) && in_array($item->metode_masuk, ['rfid', 'qr', 'kiosk'], true);
            $item->is_manual = !empty($item->presensi_id) && ($item->metode_masuk === 'manual');

            if ($item->is_mandiri) {
                $item->is_locked = true;
                $item->lock_reason = "Presensi Mandiri (" . strtoupper($item->metode_masuk) . ")";
                $item->can_pulang = false;
            } else {
                $item->is_locked = false;
                $item->lock_reason = null;
                $item->can_pulang = in_array($item->status, ['H', 'T'], true);
            }

            return $item;
        });

        $allSiswa = DB::table('peserta_didik')
            ->where('rombongan_belajar_id', $targetRombelId)
            ->orderBy('nama', 'asc')
            ->select('peserta_didik_id', 'nama', 'nisn')
            ->get();

        // Tab Izin Siswa
        $qIzin            = trim($request->get('q_izin', ''));
        $statusIzinFilter = trim($request->get('status_izin', ''));
        $jenisIzinFilter  = trim($request->get('jenis_izin', ''));

        $baseIzinQuery = DB::table('presensi_izin as pi')
            ->join('peserta_didik as pd', 'pi.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
            ->where(function ($w) use ($targetRombelId) {
                $w->where('pi.rombongan_belajar_id', $targetRombelId)
                  ->orWhere('pd.rombongan_belajar_id', $targetRombelId);
            });

        $pendingIzinCount = (clone $baseIzinQuery)->where('pi.status', 'menunggu')->count();
        $totalIzinCount   = (clone $baseIzinQuery)->count();

        $izinSummary = [
            'total'     => $totalIzinCount,
            'menunggu'  => $pendingIzinCount,
            'disetujui' => (clone $baseIzinQuery)->where('pi.status', 'disetujui')->count(),
            'ditolak'   => (clone $baseIzinQuery)->where('pi.status', 'ditolak')->count(),
        ];

        $izinListQuery = (clone $baseIzinQuery)
            ->select(
                'pi.*',
                'pd.nama as siswa_nama',
                'pd.nisn as siswa_nisn',
                'pd.nipd as siswa_nipd',
                'pd.jenis_kelamin as siswa_jk',
                'pdm.foto_path'
            );

        if ($qIzin !== '') {
            $izinListQuery->where(function ($w) use ($qIzin) {
                $w->where('pd.nama', 'like', "%{$qIzin}%")
                  ->orWhere('pd.nisn', 'like', "%{$qIzin}%")
                  ->orWhere('pi.alasan', 'like', "%{$qIzin}%");
            });
        }

        if ($statusIzinFilter !== '') {
            $izinListQuery->where('pi.status', $statusIzinFilter);
        }

        if ($jenisIzinFilter !== '') {
            $izinListQuery->where('pi.jenis', $jenisIzinFilter);
        }

        $izinList = $izinListQuery
            ->orderByRaw("FIELD(pi.status, 'menunggu', 'disetujui', 'ditolak')")
            ->orderBy('pi.created_at', 'desc')
            ->paginate($perPage, ['*'], 'izin_page')
            ->appends($request->query());

        return [
            'hasRombel'        => true,
            'isAdmin'          => $ctx['isAdmin'],
            'rombelList'       => $ctx['rombelList'],
            'activeRombel'     => $activeRombel,
            'waliNama'         => $ctx['waliNama'],
            'tanggal'          => $tanggal,
            'statusHari'       => $statusHari,
            'isLiburHari'      => $isLiburHari,
            'pengaturan'       => $pengaturan,
            'list'             => $list,
            'allSiswa'         => $allSiswa,
            'total'            => $list->total(),
            'totalSiswa'       => $totalSiswa,
            'summary'          => $summary,
            'q'                => $q,
            'statusFilter'     => $statusFilter,
            'perPage'          => $perPage,
            'sort'             => $sort,
            'sortDir'          => $sortDir,
            'canUpdate'        => $canUpdate,
            'activeTab'        => $activeTab,
            'pendingIzinCount' => $pendingIzinCount,
            'totalIzinCount'   => $totalIzinCount,
            'izinSummary'      => $izinSummary,
            'izinList'         => $izinList,
            'qIzin'            => $qIzin,
            'statusIzinFilter' => $statusIzinFilter,
            'jenisIzinFilter'  => $jenisIzinFilter,
        ];
    }

    /**
     * Pencatatan presensi manual siswa.
     */
    public static function simpanManual(array $ctx, array $data, mixed $user): array
    {
        $pdId    = $data['peserta_didik_id'];
        $tanggal = $data['tanggal'];
        $action  = strtolower(trim((string) $data['action']));
        $ket     = trim((string) ($data['keterangan'] ?? ''));

        $statusMap = [
            'masuk'     => 'H',
            'h'         => 'H',
            'terlambat' => 'T',
            't'         => 'T',
            'sakit'     => 'S',
            's'         => 'S',
            'izin'      => 'I',
            'i'         => 'I',
            'alpha'     => 'A',
            'a'         => 'A',
        ];

        $activeRombel = $ctx['activeRombel'];
        $siswa = DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->first();
        if (!$siswa) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Data peserta didik tidak ditemukan.'];
        }

        if ($siswa->rombongan_belajar_id !== $activeRombel->rombongan_belajar_id && !$ctx['isAdmin']) {
            return ['status' => 'error', 'code' => 403, 'message' => 'Peserta didik ini berada di luar rombel binaan Anda.'];
        }

        $statusHari = KalenderPendidikan::getStatusHari($tanggal, 'pd');
        $isHariAktif = class_exists(JadwalPengaturan::class) ? JadwalPengaturan::isHariAktif(Carbon::parse($tanggal)) : true;

        if (($statusHari['mode'] ?? '') === 'libur' || ($statusHari['is_libur'] ?? false) || !$isHariAktif) {
            $alasan = $statusHari['nama_agenda'] ?: 'Hari Libur Resmi / Libur Akhir Pekan';
            return [
                'status'  => 'error',
                'code'    => 422,
                'message' => "Presensi tidak dapat dicatat pada {$tanggal} ({$alasan}).",
            ];
        }

        $pengaturan = PresensiPengaturan::getPengaturan();
        $existing = PresensiHarian::where('peserta_didik_id', $pdId)
            ->where('tanggal', $tanggal)
            ->first();

        $userName = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? 'Wali Kelas')) : ($user->nama ?? ($user->name ?? 'Wali Kelas'));
        $verifiedSignature = "Wali Kelas: {$userName}";

        if ($action === 'pulang') {
            if (!$existing) {
                return ['status' => 'error', 'code' => 422, 'message' => 'Peserta didik belum tercatat presensi masuk hari ini.'];
            }
            if ($existing->jam_pulang) {
                return ['status' => 'error', 'code' => 422, 'message' => "Presensi pulang sudah tercatat pada pukul {$existing->jam_pulang}."];
            }

            $nowTime = now()->format('H:i:s');
            $statusKetepatanPulang = 'tepat_waktu';
            $jamPulangStd = $pengaturan->jam_pulang_normal ?? '15:30:00';
            if ($nowTime < $jamPulangStd) {
                $statusKetepatanPulang = 'pulang_cepat';
            }

            $existing->jam_pulang = $nowTime;
            $existing->metode_pulang = 'manual';
            $existing->status_ketepatan_pulang = $statusKetepatanPulang;
            $existing->keterangan = $ket ? ($existing->keterangan ? "{$existing->keterangan}; {$ket}" : $ket) : $existing->keterangan;
            $existing->save();

            return [
                'status'  => 'success',
                'message' => "Presensi pulang untuk {$siswa->nama} berhasil dicatat pada {$nowTime}.",
                'data'    => $existing,
            ];
        }

        if ($action === 'reset' || $action === 'batal') {
            if ($existing) {
                $existing->delete();
                return [
                    'status'  => 'success',
                    'message' => "Presensi {$siswa->nama} untuk tanggal {$tanggal} telah dibatalkan / dikosongkan.",
                ];
            }
            return ['status' => 'success', 'message' => 'Belum ada presensi yang tercatat untuk dibatalkan.'];
        }

        $targetStatus = $statusMap[$action] ?? 'H';
        $nowTime = now()->format('H:i:s');
        $jamMasukStd = $pengaturan->jam_masuk_mulai ?? '06:30:00';
        $jamBatasTerlambat = $pengaturan->jam_masuk_selesai ?? '07:15:00';

        $menitTerlambat = 0;
        $statusKetepatanMasuk = 'tepat_waktu';

        if ($targetStatus === 'H') {
            $jamRecord = ($nowTime >= $jamMasukStd && $nowTime <= $jamBatasTerlambat) ? $nowTime : ($jamMasukStd ?: '07:00:00');
        } elseif ($targetStatus === 'T') {
            $jamRecord = max($nowTime, '07:20:00');
            $statusKetepatanMasuk = 'terlambat';
            $menitTerlambat = 15;
        } else {
            $jamRecord = null;
            $statusKetepatanMasuk = null;
        }

        $presensi = PresensiHarian::updateOrCreate(
            ['peserta_didik_id' => $pdId, 'tanggal' => $tanggal],
            [
                'rombongan_belajar_id'   => $siswa->rombongan_belajar_id,
                'status'                 => $targetStatus,
                'jam_masuk'              => $jamRecord,
                'menit_terlambat'        => $menitTerlambat,
                'status_ketepatan_masuk' => $statusKetepatanMasuk,
                'metode_masuk'           => 'manual',
                'keterangan'             => $ket ?: null,
                'verified_by'            => $verifiedSignature,
            ]
        );

        $statusLabelMap = [
            'H' => 'Hadir',
            'T' => 'Terlambat',
            'S' => 'Sakit',
            'I' => 'Izin',
            'A' => 'Alpha',
        ];
        $label = $statusLabelMap[$targetStatus] ?? 'Tercatat';

        return [
            'status'  => 'success',
            'message' => "Presensi {$siswa->nama} berhasil diperbarui menjadi {$label}.",
            'data'    => $presensi,
        ];
    }

    /**
     * Reset / kosongkan presensi manual rombel untuk tanggal tertentu.
     */
    public static function resetPresensi(array $ctx, string $tanggal, string $rombelId): int
    {
        return PresensiHarian::where('rombongan_belajar_id', $rombelId)
            ->where('tanggal', $tanggal)
            ->where('metode_masuk', 'manual')
            ->delete();
    }

    /**
     * Verifikasi surat izin siswa.
     */
    public static function verifikasiIzin(array $ctx, int|string $id, array $data, mixed $user): array
    {
        $userId = is_array($user) ? ($user['pengguna_id'] ?? ($user['id'] ?? null)) : ($user->pengguna_id ?? ($user->id ?? null));
        $verifiedBy = is_array($user) ? ($user['nama'] ?? 'Wali Kelas') : ($user->nama ?? 'Wali Kelas');

        $activeRombel = $ctx['activeRombel'];
        $izin = PresensiIzin::findOrFail($id);

        if (!$ctx['isAdmin']) {
            $targetRombelId = $activeRombel?->rombongan_belajar_id;
            $siswaRombelId = DB::table('peserta_didik')->where('peserta_didik_id', $izin->peserta_didik_id)->value('rombongan_belajar_id');
            if ($izin->rombongan_belajar_id !== $targetRombelId && $siswaRombelId !== $targetRombelId) {
                return ['status' => 'error', 'code' => 403, 'message' => 'Peserta didik ini berada di luar rombel binaan Anda.'];
            }
        }

        $izin->status = $data['status'];
        $izin->disetujui_oleh = $verifiedBy;
        $izin->catatan_petugas = $data['catatan'] ?? null;
        $izin->save();

        if ($izin->status === 'disetujui') {
            $izin->applyToDailyAttendance($verifiedBy);
        }

        $statusStr = ($izin->status === 'disetujui') ? 'Disetujui' : 'Ditolak';
        $notifTipe = ($izin->status === 'disetujui') ? 'success' : 'danger';
        $icon = ($izin->status === 'disetujui') ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-xmark';

        $siswa = DB::table('peserta_didik')->where('peserta_didik_id', $izin->peserta_didik_id)->first();
        $namaSiswa = $siswa?->nama ?? 'Siswa';

        $judulMurid = "Surat {$izin->jenis_label}: {$statusStr}";
        $pesanMurid = "Surat {$izin->jenis_label} Anda {$izin->status} oleh {$verifiedBy}." . ($izin->catatan_petugas ? " ({$izin->catatan_petugas})" : '');

        $judulOrtu = "Surat {$izin->jenis_label}: {$namaSiswa} ({$statusStr})";
        $pesanOrtu = "Surat {$izin->jenis_label} {$namaSiswa} telah {$izin->status} oleh Wali Kelas.";

        NotifikasiTransaksi::kirimNotifikasiIzinVerifikasi(
            $izin->peserta_didik_id,
            $izin->rombongan_belajar_id ?? '',
            $judulMurid,
            $pesanMurid,
            $judulOrtu,
            $pesanOrtu,
            $notifTipe,
            $icon,
            $userId
        );

        return [
            'status'  => 'success',
            'message' => 'Permohonan surat ' . $izin->jenis_label . ' berhasil ' . ($izin->status === 'disetujui' ? 'disetujui' : 'ditolak') . '.',
        ];
    }

    /**
     * Siapkan view data untuk laporan presensi PDF multi-periode.
     */
    public static function getPdfData(array $ctx, string $tipe, Request $request): array
    {
        $activeRombel = $ctx['activeRombel'];
        $sekolah = PresensiPengaturan::getSekolah();
        $targetRombelId = $activeRombel->rombongan_belajar_id;

        $tipe = strtolower($tipe);
        if (!in_array($tipe, ['siswa', 'hari', 'bulan', 'semester', 'tahun'], true)) {
            $tipe = 'hari';
        }

        $kepalaSekolah = Table::exists('gtk')
            ? DB::table('gtk')->where(function ($w) {
                $w->where('jenis_ptk_id_str', 'LIKE', '%Kepala Sekolah%')
                  ->orWhere('jabatan_ptk_id_str', 'LIKE', '%Kepala Sekolah%');
            })->first()
            : null;

        $sekolahMeta = Table::exists('sekolah_meta') ? SekolahMeta::first() : null;

        $viewData = [
            'tipe'          => $tipe,
            'sekolah'       => $sekolah,
            'sekolahMeta'   => $sekolahMeta,
            'activeRombel'  => $activeRombel,
            'waliNama'      => $ctx['waliNama'],
            'waliNip'       => $ctx['waliNip'] ?? '—',
            'kepalaSekolah' => $kepalaSekolah,
            'generatedAt'   => now()->translatedFormat('d F Y, H:i') . ' WIB',
        ];

        $safeRombel = str_replace(['/', '\\', ' '], '_', $activeRombel->nama);

        switch ($tipe) {
            case 'siswa':
                $siswaId = $request->get('siswa_id');
                $siswa = null;
                if ($siswaId) {
                    $siswa = DB::table('peserta_didik')->where('peserta_didik_id', $siswaId)->first();
                }
                if (!$siswa) {
                    $siswa = DB::table('peserta_didik')->where('rombongan_belajar_id', $targetRombelId)->orderBy('nama', 'asc')->first();
                }
                if (!$siswa) abort(404, 'Data peserta didik tidak ditemukan.');

                $bulan = $request->get('bulan', now()->format('Y-m'));
                $logs = PresensiHarian::where('peserta_didik_id', $siswa->peserta_didik_id)
                    ->where('tanggal', 'like', "{$bulan}%")
                    ->orderBy('tanggal', 'asc')
                    ->get();

                $carbonBulanObj = Carbon::parse($bulan . '-01');
                $hebBulan = KalenderPendidikan::hitungHariEfektifBerjalan(
                    $carbonBulanObj->copy()->startOfMonth()->toDateString(),
                    $carbonBulanObj->copy()->endOfMonth()->toDateString(),
                    now()->toDateString(),
                    'pd'
                );

                $stats = [
                    'total'        => $logs->count(),
                    'hadir'        => $logs->where('status', 'H')->count(),
                    'terlambat'    => $logs->where('status', 'T')->count(),
                    'izin'         => $logs->where('status', 'I')->count(),
                    'sakit'        => $logs->where('status', 'S')->count(),
                    'dispen'       => $logs->where('status', 'D')->count(),
                    'alpha'        => $logs->where('status', 'A')->count(),
                    'hari_efektif' => $hebBulan,
                ];
                $denoSiswa = $hebBulan > 0 ? $hebBulan : $stats['total'];
                $stats['persen'] = $denoSiswa > 0 ? min(100.0, round((($stats['hadir'] + $stats['terlambat'] + $stats['dispen']) / $denoSiswa) * 100, 1)) : 0;

                $viewData['siswa'] = $siswa;
                $viewData['bulan'] = $bulan;
                $viewData['bulanLabel'] = Carbon::parse($bulan . '-01')->translatedFormat('F Y');
                $viewData['logs'] = $logs;
                $viewData['stats'] = $stats;
                $viewData['fileName'] = "Rekap_Presensi_Peserta_Didik_{$siswa->nisn}_{$bulan}.pdf";
                break;

            case 'hari':
                $tanggal = $request->get('tanggal', now()->toDateString());
                $statusHari = KalenderPendidikan::getStatusHari($tanggal, 'pd');

                $siswaList = DB::table('peserta_didik as pd')
                    ->leftJoin('presensi_harian as ph', function ($join) use ($tanggal) {
                        $join->on('pd.peserta_didik_id', '=', 'ph.peserta_didik_id')
                            ->where('ph.tanggal', '=', $tanggal);
                    })
                    ->where('pd.rombongan_belajar_id', $targetRombelId)
                    ->select(
                        'pd.peserta_didik_id', 'pd.nama', 'pd.nisn', 'pd.nipd', 'pd.jenis_kelamin',
                        'ph.status', 'ph.jam_masuk', 'ph.jam_pulang', 'ph.menit_terlambat', 'ph.metode_masuk', 'ph.keterangan'
                    )
                    ->orderBy('pd.nama', 'asc')
                    ->get();

                $viewData['tanggal'] = $tanggal;
                $viewData['tanggalLabel'] = Carbon::parse($tanggal)->translatedFormat('l, d F Y');
                $viewData['statusHari'] = $statusHari;
                $viewData['siswaList'] = $siswaList;
                $viewData['fileName'] = "Presensi_Harian_{$safeRombel}_{$tanggal}.pdf";
                break;

            case 'bulan':
                $bulan = $request->get('bulan', now()->format('Y-m'));
                $carbonBulan = Carbon::parse($bulan . '-01');
                $daysInMonth = $carbonBulan->daysInMonth;

                $siswaList = DB::table('peserta_didik')
                    ->where('rombongan_belajar_id', $targetRombelId)
                    ->orderBy('nama', 'asc')
                    ->get();

                $logsBulan = DB::table('presensi_harian')
                    ->where('rombongan_belajar_id', $targetRombelId)
                    ->where('tanggal', 'like', "{$bulan}%")
                    ->get()
                    ->groupBy('peserta_didik_id');

                $hebBulan = KalenderPendidikan::hitungHariEfektifBerjalan(
                    $carbonBulan->copy()->startOfMonth()->toDateString(),
                    $carbonBulan->copy()->endOfMonth()->toDateString(),
                    now()->toDateString(),
                    'pd'
                );

                $matrix = [];
                foreach ($siswaList as $s) {
                    $sLogs = $logsBulan->get($s->peserta_didik_id, collect());
                    $daysMap = [];
                    for ($d = 1; $d <= $daysInMonth; $d++) {
                        $tglStr = sprintf('%s-%02d', $bulan, $d);
                        $log = $sLogs->firstWhere('tanggal', $tglStr);
                        $daysMap[$d] = $log ? $log->status : '-';
                    }
                    $h = $sLogs->where('status', 'H')->count();
                    $t = $sLogs->where('status', 'T')->count();
                    $i = $sLogs->where('status', 'I')->count();
                    $sakit = $sLogs->where('status', 'S')->count();
                    $d = $sLogs->where('status', 'D')->count();
                    $a = $sLogs->where('status', 'A')->count();
                    $total = $sLogs->count();
                    $denoBulan = $hebBulan > 0 ? $hebBulan : $total;
                    $persen = $denoBulan > 0 ? min(100.0, round((($h + $t + $d) / $denoBulan) * 100, 1)) : 0;

                    $matrix[] = [
                        'siswa'   => $s,
                        'days'    => $daysMap,
                        'rekap'   => ['h' => $h, 't' => $t, 'i' => $i, 's' => $sakit, 'd' => $d, 'a' => $a, 'persen' => $persen],
                    ];
                }

                $viewData['bulan'] = $bulan;
                $viewData['bulanLabel'] = $carbonBulan->translatedFormat('F Y');
                $viewData['daysInMonth'] = $daysInMonth;
                $viewData['matrix'] = $matrix;
                $viewData['fileName'] = "Rekap_Bulanan_{$safeRombel}_{$bulan}.pdf";
                break;

            case 'semester':
                $semester = (int) $request->get('semester', (now()->month >= 7 ? 1 : 2));
                $tahun = (int) $request->get('tahun', now()->year);
                $bulanList = ($semester === 1) ? [7, 8, 9, 10, 11, 12] : [1, 2, 3, 4, 5, 6];
                $tahunAkademik = ($semester === 1) ? "{$tahun}/" . ($tahun + 1) : ($tahun - 1) . "/{$tahun}";

                $siswaList = DB::table('peserta_didik')
                    ->where('rombongan_belajar_id', $targetRombelId)
                    ->orderBy('nama', 'asc')
                    ->get();

                $logsSemester = DB::table('presensi_harian')
                    ->where('rombongan_belajar_id', $targetRombelId)
                    ->where(function ($q) use ($bulanList, $tahun, $semester) {
                        foreach ($bulanList as $b) {
                            $y = ($semester === 2 && $b >= 7) ? ($tahun - 1) : $tahun;
                            $ym = sprintf('%04d-%02d', $y, $b);
                            $q->orWhere('tanggal', 'like', "{$ym}%");
                        }
                    })
                    ->get()
                    ->groupBy('peserta_didik_id');

                $startDateSem = ($semester === 1) ? "{$tahun}-07-01" : "{$tahun}-01-01";
                $endDateSem = ($semester === 1) ? "{$tahun}-12-31" : "{$tahun}-06-30";
                $hebSem = KalenderPendidikan::hitungHariEfektifBerjalan($startDateSem, $endDateSem, now()->toDateString(), 'pd');

                $rekapSemester = [];
                foreach ($siswaList as $s) {
                    $sLogs = $logsSemester->get($s->peserta_didik_id, collect());
                    $h = $sLogs->where('status', 'H')->count();
                    $t = $sLogs->where('status', 'T')->count();
                    $i = $sLogs->where('status', 'I')->count();
                    $sakit = $sLogs->where('status', 'S')->count();
                    $d = $sLogs->where('status', 'D')->count();
                    $a = $sLogs->where('status', 'A')->count();
                    $total = $sLogs->count();
                    $denoSem = $hebSem > 0 ? $hebSem : $total;
                    $persen = $denoSem > 0 ? min(100.0, round((($h + $t + $d) / $denoSem) * 100, 1)) : 0;

                    $rekapSemester[] = [
                        'siswa' => $s,
                        'h'     => $h,
                        't'     => $t,
                        'i'     => $i,
                        's'     => $sakit,
                        'd'     => $d,
                        'a'     => $a,
                        'total' => $total,
                        'persen'=> $persen,
                    ];
                }

                $viewData['semester'] = $semester;
                $viewData['tahunAkademik'] = $tahunAkademik;
                $viewData['rekapSemester'] = $rekapSemester;
                $viewData['fileName'] = "Rekap_Semester_{$semester}_{$safeRombel}.pdf";
                break;

            case 'tahun':
                $tahun = (int) ($request->get('tahun_ajaran') ?: $request->get('tahun', (now()->month >= 7 ? now()->year : now()->year - 1)));
                $tahunAjaran = "{$tahun}/" . ($tahun + 1);

                $startDate = "{$tahun}-07-01";
                $endDate   = ($tahun + 1) . "-06-30";

                $siswaList = DB::table('peserta_didik')
                    ->where('rombongan_belajar_id', $targetRombelId)
                    ->orderBy('nama', 'asc')
                    ->get();

                $logsTahunQuery = DB::table('presensi_harian')->where('rombongan_belajar_id', $targetRombelId);
                if (DB::table('presensi_harian')->where('rombongan_belajar_id', $targetRombelId)->whereBetween('tanggal', [$startDate, $endDate])->exists()) {
                    $logsTahunQuery->whereBetween('tanggal', [$startDate, $endDate]);
                }
                $logsTahun = $logsTahunQuery->get()->groupBy('peserta_didik_id');

                $rekapTahun = [];
                foreach ($siswaList as $s) {
                    $sLogs = $logsTahun->get($s->peserta_didik_id, collect());
                    $h = $sLogs->where('status', 'H')->count();
                    $t = $sLogs->where('status', 'T')->count();
                    $i = $sLogs->where('status', 'I')->count();
                    $sakit = $sLogs->where('status', 'S')->count();
                    $a = $sLogs->where('status', 'A')->count();
                    $total = $sLogs->count();
                    $persen = $total > 0 ? round((($h + $t) / $total) * 100, 1) : 0;

                    $rekapTahun[] = [
                        'siswa' => $s,
                        'h'     => $h,
                        't'     => $t,
                        'i'     => $i,
                        's'     => $sakit,
                        'a'     => $a,
                        'total' => $total,
                        'persen'=> $persen,
                    ];
                }

                $viewData['tahunAjaran'] = $tahunAjaran;
                $viewData['rekapTahun'] = $rekapTahun;
                $viewData['fileName'] = "Rekap_Tahunan_{$safeRombel}.pdf";
                break;
        }

        return $viewData;
    }
}
