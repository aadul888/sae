<?php

namespace App\Services;

use App\Models\JadwalPengaturan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\Table;

class JadwalPrintService
{
    /**
     * Data untuk Cetak Dokumen: Jadwal Induk Sekolah (Matriks Besar Seluruh Rombel).
     */
    public function getCetakIndukData(Request $request): array
    {
        $sekolah = DB::table('sekolah')->first();
        $pengaturan = JadwalPengaturan::getSettings();
        $slots = JadwalPengaturan::getSlots();
        $dailySlotCounts = JadwalPengaturan::getDailySlotCounts();
        $baseHariList = $request->get('hari') ? [$request->get('hari')] : ($pengaturan->hari_aktif ?? ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']);
        $hariList = array_values(array_filter($baseHariList, function ($h) use ($dailySlotCounts) {
            return ($dailySlotCounts[$h] ?? 0) > 0;
        }));
        if (empty($hariList)) {
            $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        }

        $rombels = DB::table('rombongan_belajar')
            ->where(function ($w) {
                $w->where('jenis_rombel', 1)
                    ->orWhere('jenis_rombel_str', 'Kelas')
                    ->orWhere('jenis_rombel_str', 'LIKE', '%reguler%');
            })
            ->select('rombongan_belajar_id', 'nama', 'tingkat_pendidikan_id')
            ->orderBy('tingkat_pendidikan_id', 'asc')
            ->orderBy('nama', 'asc')
            ->get();

        $sumber = $request->get('sumber') ?: (JadwalPengaturan::getModeAktif() ?: 'otomatis');
        $allSchedules = DB::table('jadwal_kbm as j')
            ->leftJoin('gtk as g', 'j.ptk_id', '=', 'g.ptk_id')
            ->where('j.sumber', $sumber)
            ->where('j.is_active', true)
            ->select('j.*', 'g.nama as nama_guru')
            ->get();

        $matrix = [];
        $occupied = [];
        foreach ($allSchedules as $s) {
            $rId = $s->rombongan_belajar_id;
            $h = $s->hari;
            $startK = (int) $s->jam_ke_mulai;
            $endK = (int) $s->jam_ke_selesai;

            $matrix[$rId][$h][$startK] = $s;
            for ($k = $startK + 1; $k <= $endK; $k++) {
                $occupied[$rId][$h][$k] = true;
            }
        }

        $kepalaSekolah = DB::table('gtk')
            ->where(function ($w) {
                $w->where('jenis_ptk_id_str', 'LIKE', '%Kepala Sekolah%')
                  ->orWhere('jabatan_ptk_id_str', 'LIKE', '%Kepala Sekolah%');
            })
            ->first();

        $wakaKurikulum = $this->resolveWakaKurikulum();

        return compact(
            'sekolah',
            'kepalaSekolah',
            'wakaKurikulum',
            'pengaturan',
            'slots',
            'hariList',
            'rombels',
            'matrix',
            'occupied'
        );
    }

    /**
     * Data untuk Cetak Dokumen: Jadwal Mengajar per Guru.
     */
    public function getCetakGuruData(Request $request): array
    {
        $sekolah = DB::table('sekolah')->first();
        $kepalaSekolah = DB::table('gtk')
            ->where(function ($w) {
                $w->where('jenis_ptk_id_str', 'LIKE', '%Kepala Sekolah%')
                  ->orWhere('jabatan_ptk_id_str', 'LIKE', '%Kepala Sekolah%');
            })
            ->first();
        $pengaturan = JadwalPengaturan::getSettings();
        $slots = JadwalPengaturan::getSlots();
        $dailySlotCounts = JadwalPengaturan::getDailySlotCounts();
        $baseHariList = $pengaturan->hari_aktif ?? ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $hariList = array_values(array_filter($baseHariList, function ($h) use ($dailySlotCounts) {
            return ($dailySlotCounts[$h] ?? 0) > 0;
        }));
        if (empty($hariList)) {
            $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        }
        $sumber = $request->get('sumber') ?: (JadwalPengaturan::getModeAktif() ?: 'otomatis');
        $ptkId = $request->get('ptk_id');

        $activePtkIds = DB::table('jadwal_kbm')
            ->where('sumber', $sumber)
            ->where('is_active', true)
            ->whereNotNull('ptk_id')
            ->distinct()
            ->pluck('ptk_id');

        $allGuruOptions = DB::table('gtk')
            ->whereIn('ptk_id', $activePtkIds)
            ->whereNotNull('nama')
            ->orderBy('nama', 'asc')
            ->get();

        $guruQuery = DB::table('gtk')
            ->whereNotNull('nama')
            ->orderBy('nama', 'asc');

        if ($ptkId) {
            $guruQuery->where('ptk_id', $ptkId);
        } else {
            $guruQuery->whereIn('ptk_id', $activePtkIds);
        }

        $guruList = $guruQuery->get();

        $allSchedules = DB::table('jadwal_kbm as j')
            ->leftJoin('rombongan_belajar as r', 'j.rombongan_belajar_id', '=', 'r.rombongan_belajar_id')
            ->where('j.sumber', $sumber)
            ->where('j.is_active', true)
            ->select('j.*', 'r.nama as nama_rombel')
            ->get();

        $matrix = [];
        $occupied = [];
        $totalJpPerGuru = [];
        $totalSesiPerGuru = [];

        foreach ($allSchedules as $s) {
            $pId = $s->ptk_id;
            $h = $s->hari;
            if (!in_array($h, $hariList, true)) continue;

            $startK = (int) $s->jam_ke_mulai;
            $endK = (int) $s->jam_ke_selesai;
            $jp = max(1, $endK - $startK + 1);

            $isRoutine = in_array($s->mata_pelajaran_id, ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT']);

            if (!$isRoutine && $pId) {
                $totalJpPerGuru[$pId] = ($totalJpPerGuru[$pId] ?? 0) + $jp;
                $totalSesiPerGuru[$pId] = ($totalSesiPerGuru[$pId] ?? 0) + 1;
            }

            if ($pId) {
                if (!empty($matrix[$pId][$h][$startK]) || !empty($occupied[$pId][$h][$startK])) {
                    continue;
                }
                $matrix[$pId][$h][$startK] = $s;
                for ($k = $startK + 1; $k <= $endK; $k++) {
                    $occupied[$pId][$h][$k] = true;
                }
            }
        }

        // Ambil dan Petakan Kegiatan Rutin Sekolah
        $routinesFromDb = DB::table('jadwal_kbm')
            ->whereIn('mata_pelajaran_id', ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'])
            ->where('is_active', true)
            ->select('hari', 'jam_ke_mulai', 'jam_ke_selesai', 'jam_mulai', 'jam_selesai', 'mata_pelajaran_id', 'nama_mata_pelajaran')
            ->distinct()
            ->get();

        $routinesMap = [];
        foreach ($routinesFromDb as $r) {
            $routinesMap[$r->hari . '_' . $r->jam_ke_mulai] = $r;
        }

        if (!empty($pengaturan->upacara['aktif'])) {
            $upHari = $pengaturan->upacara['hari'] ?? 'Senin';
            $upJam = (int) ($pengaturan->upacara['jam_ke'] ?? 1);
            $key = $upHari . '_' . $upJam;
            if (!isset($routinesMap[$key])) {
                $routinesMap[$key] = (object) [
                    'hari'                => $upHari,
                    'jam_ke_mulai'        => $upJam,
                    'jam_ke_selesai'      => $upJam,
                    'jam_mulai'           => null,
                    'jam_selesai'         => null,
                    'mata_pelajaran_id'   => 'UPACARA',
                    'nama_mata_pelajaran' => $pengaturan->upacara['nama'] ?? 'Upacara Bendera',
                ];
            }
        }

        if (!empty($pengaturan->pembiasaan['aktif'])) {
            $pemHari = $pengaturan->pembiasaan['hari'] ?? 'Jumat';
            $pemJam = (int) ($pengaturan->pembiasaan['jam_ke'] ?? 1);
            $key = $pemHari . '_' . $pemJam;
            if (!isset($routinesMap[$key])) {
                $routinesMap[$key] = (object) [
                    'hari'                => $pemHari,
                    'jam_ke_mulai'        => $pemJam,
                    'jam_ke_selesai'      => $pemJam,
                    'jam_mulai'           => null,
                    'jam_selesai'         => null,
                    'mata_pelajaran_id'   => 'PEMBIASAAN',
                    'nama_mata_pelajaran' => $pengaturan->pembiasaan['nama'] ?? 'Pembiasaan',
                ];
            }
        }

        if (!empty($pengaturan->istirahat) && is_array($pengaturan->istirahat)) {
            foreach ($pengaturan->istirahat as $ist) {
                if (!empty($ist['aktif']) && !empty($ist['jam_ke'])) {
                    $istJam = (int) $ist['jam_ke'];
                    $istNama = $ist['nama'] ?? 'Istirahat';
                    foreach ($hariList as $h) {
                        $maxSlotsHari = count(JadwalPengaturan::getSlots($h));
                        if ($istJam > $maxSlotsHari) {
                            continue;
                        }
                        $key = $h . '_' . $istJam;
                        if (!isset($routinesMap[$key])) {
                            $routinesMap[$key] = (object) [
                                'hari'                => $h,
                                'jam_ke_mulai'        => $istJam,
                                'jam_ke_selesai'      => $istJam,
                                'jam_mulai'           => null,
                                'jam_selesai'         => null,
                                'mata_pelajaran_id'   => 'ISTIRAHAT',
                                'nama_mata_pelajaran' => $istNama,
                            ];
                        }
                    }
                }
            }
        }

        foreach ($guruList as $guru) {
            $pId = $guru->ptk_id;
            foreach ($routinesMap as $r) {
                $h = $r->hari;
                $startK = (int) $r->jam_ke_mulai;
                $endK = (int) $r->jam_ke_selesai;

                $isSlotFree = empty($matrix[$pId][$h][$startK]) && empty($occupied[$pId][$h][$startK]);

                if ($isSlotFree) {
                    $descRombel = 'Jeda Istirahat';
                    if ($r->mata_pelajaran_id === 'UPACARA') {
                        $descRombel = 'Dewan Guru & Siswa';
                    } elseif ($r->mata_pelajaran_id === 'PEMBIASAAN') {
                        $descRombel = 'Wali Kelas & Guru';
                    }

                    $matrix[$pId][$h][$startK] = (object) [
                        'rombongan_belajar_id' => null,
                        'nama_rombel'          => $descRombel,
                        'ptk_id'               => $pId,
                        'mata_pelajaran_id'    => $r->mata_pelajaran_id,
                        'nama_mata_pelajaran'  => $r->nama_mata_pelajaran,
                        'hari'                 => $h,
                        'jam_ke_mulai'         => $startK,
                        'jam_ke_selesai'       => $endK,
                        'jam_mulai'            => $r->jam_mulai,
                        'jam_selesai'          => $r->jam_selesai,
                        'ruangan'              => $r->mata_pelajaran_id === 'UPACARA' ? 'Lapangan' : null,
                    ];

                    for ($k = $startK + 1; $k <= $endK; $k++) {
                        $occupied[$pId][$h][$k] = true;
                    }
                }
            }
        }

        return compact(
            'sekolah',
            'kepalaSekolah',
            'pengaturan',
            'slots',
            'hariList',
            'guruList',
            'matrix',
            'occupied',
            'totalJpPerGuru',
            'totalSesiPerGuru',
            'ptkId',
            'allGuruOptions'
        );
    }

    /**
     * Data untuk Cetak Dokumen: Jadwal Pelajaran per Rombel / Kelas.
     */
    public function getCetakRombelData(Request $request): array
    {
        $sekolah = DB::table('sekolah')->first();
        $kepalaSekolah = DB::table('gtk')
            ->where(function ($w) {
                $w->where('jenis_ptk_id_str', 'LIKE', '%Kepala Sekolah%')
                  ->orWhere('jabatan_ptk_id_str', 'LIKE', '%Kepala Sekolah%');
            })
            ->first();
        $pengaturan = JadwalPengaturan::getSettings();
        $slots = JadwalPengaturan::getSlots();
        $rombelId = $request->get('rombel_id');

        $rombelQuery = DB::table('rombongan_belajar as r')
            ->leftJoin('gtk as w', 'r.ptk_id', '=', 'w.ptk_id')
            ->where(function ($w) {
                $w->where('r.jenis_rombel', 1)
                    ->orWhere('r.jenis_rombel_str', 'Kelas')
                    ->orWhere('r.jenis_rombel_str', 'LIKE', '%reguler%');
            })
            ->select('r.*', 'w.nama as nama_wali_kelas', 'w.nip as nip_wali_kelas')
            ->orderBy('r.tingkat_pendidikan_id', 'asc')
            ->orderBy('r.nama', 'asc');

        $sumber = $request->get('sumber') ?: (JadwalPengaturan::getModeAktif() ?: 'otomatis');

        $activeRombelIds = DB::table('jadwal_kbm')
            ->where('sumber', $sumber)
            ->where('is_active', true)
            ->distinct()
            ->pluck('rombongan_belajar_id');

        $allRombelOptions = DB::table('rombongan_belajar')
            ->whereIn('rombongan_belajar_id', $activeRombelIds)
            ->orderBy('tingkat_pendidikan_id', 'asc')
            ->orderBy('nama', 'asc')
            ->get();

        if ($rombelId) {
            $rombelQuery->where('r.rombongan_belajar_id', $rombelId);
        } else {
            $rombelQuery->whereIn('r.rombongan_belajar_id', $activeRombelIds);
        }

        $rombelList = $rombelQuery->get();
        $dailySlotCounts = JadwalPengaturan::getDailySlotCounts();
        $baseHariList = $pengaturan->hari_aktif ?? ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $hariList = array_values(array_filter($baseHariList, function ($h) use ($dailySlotCounts) {
            return ($dailySlotCounts[$h] ?? 0) > 0;
        }));
        if (empty($hariList)) {
            $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        }

        $allSchedules = DB::table('jadwal_kbm as j')
            ->leftJoin('gtk as g', 'j.ptk_id', '=', 'g.ptk_id')
            ->where('j.sumber', $sumber)
            ->where('j.is_active', true)
            ->select('j.*', 'g.nama as nama_guru')
            ->get();

        $matrix = [];
        $occupied = [];
        foreach ($allSchedules as $s) {
            $rId = $s->rombongan_belajar_id;
            $h = $s->hari;
            $startK = (int) $s->jam_ke_mulai;
            $endK = (int) $s->jam_ke_selesai;

            $matrix[$rId][$h][$startK] = $s;
            for ($k = $startK + 1; $k <= $endK; $k++) {
                $occupied[$rId][$h][$k] = true;
            }
        }

        return compact(
            'sekolah',
            'kepalaSekolah',
            'pengaturan',
            'slots',
            'hariList',
            'rombelList',
            'matrix',
            'occupied',
            'rombelId',
            'allRombelOptions'
        );
    }

    /**
     * Resolusi Data Waka Kurikulum dari Penetapan Tugas Tambahan.
     */
    public function resolveWakaKurikulum(): ?object
    {
        $waka = null;
        if (Table::exists('ptk_tugas_tambahan') && Table::exists('ref_tugas_tambahan')) {
            $waka = DB::table('ptk_tugas_tambahan as ptt')
                ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                ->join('gtk', 'ptt.ptk_id', '=', 'gtk.ptk_id')
                ->where(function ($w) {
                    $w->where('rtt.kode', 'WAKA_KURIKULUM')
                      ->orWhere('rtt.nama', 'LIKE', '%Kurikulum%');
                })
                ->where('ptt.is_active', 1)
                ->select('gtk.nama', 'gtk.nip', 'gtk.nuptk')
                ->first();
        }

        if (!$waka && Table::exists('gtk')) {
            $waka = DB::table('gtk')
                ->where(function ($w) {
                    $w->where('jenis_ptk_id_str', 'LIKE', '%Kurikulum%')
                      ->orWhere('jabatan_ptk_id_str', 'LIKE', '%Kurikulum%');
                })
                ->select('nama', 'nip', 'nuptk')
                ->first();
        }

        return $waka;
    }
}
