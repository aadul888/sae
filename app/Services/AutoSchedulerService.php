<?php

namespace App\Services;

use App\Exceptions\SchedulerValidationException;
use App\Models\JadwalKbm;
use App\Models\JadwalPengaturan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoSchedulerService
{
    public function __construct(
        protected ?AutoSchedulerPlacementService $placementService = null,
        protected ?AutoSchedulerSwapResolver $swapResolver = null,
        protected ?AutoSchedulerLayoutOptimizer $layoutOptimizer = null
    ) {
        $this->placementService ??= new AutoSchedulerPlacementService();
        $this->swapResolver ??= new AutoSchedulerSwapResolver($this->placementService);
        $this->layoutOptimizer ??= new AutoSchedulerLayoutOptimizer();
    }

    /**
     * Jalankan Engine Auto-Generate Jadwal KBM (Anti-Bentrok & Pemetaan Jam Maksimal)
     */
    public function generate(array $options = []): array
    {
        @set_time_limit(300);
        $strictValidation = $options['strict_validation'] ?? true;
        // Seed 70 & 60 terbukti 100% optimal 1.676 JP tanpa bentrok dan tanpa jam kosong
        $candidateSeeds = [101, 65, 70, 60, 26, 42, 52, 4, 1, 15, 30, 36, null];
        $bestResult = null;
        $lastException = null;

        foreach ($candidateSeeds as $seed) {
            try {
                $res = $this->executeGenerationAttempt($options, $seed);
                if (!empty($res['success']) && empty($res['unallocated']) && empty($res['unfilled_rombels'])) {
                    return $res;
                }
                if ($bestResult === null || ($res['total_jp'] ?? 0) > ($bestResult['total_jp'] ?? 0)) {
                    $bestResult = $res;
                }
            } catch (SchedulerValidationException $e) {
                $lastException = $e;
                continue;
            }
        }

        // Adaptive Fallback: Jika ada unallocated karena batas blok konfigurasi tertentu,
        // retry otomatis dengan max_jp_per_sesi seimbang agar 100% seluruh jadwal berhasil terpetakan
        if ($bestResult === null || !empty($bestResult['unallocated']) || !empty($bestResult['unfilled_rombels']) || empty($bestResult['success'])) {
            $fallbackOptions = $options;
            $fallbackOptions['max_jp_per_sesi'] = 3;
            foreach ([101, 65, 70, 60, 26, 42, 4, 1, null] as $seed) {
                try {
                    $res = $this->executeGenerationAttempt($fallbackOptions, $seed);
                    if (!empty($res['success']) && empty($res['unallocated']) && empty($res['unfilled_rombels'])) {
                        $res['message'] .= ' (Disesuaikan otomatis ke blok pertemuan seimbang agar seluruh rombel 100% terpetakan tanpa bentrok).';
                        return $res;
                    }
                } catch (SchedulerValidationException $e) {
                    $lastException = $e;
                    continue;
                }
            }
        }

        if ($bestResult !== null && !$strictValidation) {
            return $bestResult;
        }

        if ($lastException !== null) {
            throw $lastException;
        }

        return $bestResult ?? [
            'success' => false,
            'message' => 'Gagal men-generate jadwal.',
            'total_generated' => 0,
        ];
    }

    /**
     * Eksekusi satu kali percobaan generate jadwal dengan seed tertentu
     */
    private function executeGenerationAttempt(array $options, ?int $seed = null): array
    {
        $clearExisting = $options['clear_existing'] ?? true;
        $targetRombelIds = $options['rombongan_belajar_ids'] ?? [];
        $tingkat = $options['tingkat'] ?? null;
        $maxJpPerSession = (int) ($options['max_jp_per_sesi'] ?? 3);
        $strictValidation = $options['strict_validation'] ?? true;

        $pengaturan = JadwalPengaturan::getSettings();
        $slots = JadwalPengaturan::getSlots();
        $totalSlots = count($slots);
        $dailySlotCounts = JadwalPengaturan::getDailySlotCounts();
        $jpTingkat = $options['jp_tingkat'] ?? JadwalPengaturan::getJpTingkat();

        $istirahatJamKe = null;
        if (!empty($pengaturan->istirahat) && is_array($pengaturan->istirahat)) {
            foreach ($pengaturan->istirahat as $ist) {
                if (!isset($ist['aktif']) || !empty($ist['aktif'])) {
                    $istirahatJamKe = (int) ($ist['jam_ke'] ?? null);
                    break;
                }
            }
        }

        $hariList = $options['hari_aktif'] ?? $pengaturan->hari_aktif;
        if (empty($hariList) || !is_array($hariList)) {
            $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        }

        // Filter hari aktif: HANYA gunakan hari yang memiliki alokasi slot harian > 0 (Sabtu 0 JP mutlak diabaikan)
        $hariList = array_values(array_filter($hariList, function ($h) use ($dailySlotCounts) {
            return ($dailySlotCounts[$h] ?? 0) > 0;
        }));
        if (empty($hariList)) {
            $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        }

        // 1. Tentukan Rombel yang Ditargetkan (Hanya Kelas Reguler)
        $rombelQuery = \App\Support\RombelScope::query()
            ->select('rombongan_belajar_id', 'nama', 'tingkat_pendidikan_id', 'semester_id', 'id_ruang');

        if (!empty($targetRombelIds)) {
            $rombelQuery->whereIn('rombongan_belajar_id', $targetRombelIds);
        } elseif (!empty($tingkat)) {
            $rombelQuery->where('tingkat_pendidikan_id', $tingkat);
        }

        $rombels = $rombelQuery->get();
        if ($rombels->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Tidak ada rombongan belajar reguler yang dipilih.',
                'total_generated' => 0,
            ];
        }

        $selectedRombelIds = $rombels->pluck('rombongan_belajar_id')->toArray();
        $rombelMap = $rombels->keyBy('rombongan_belajar_id');

        // Petakan batas target JP per rombel berdasarkan tingkat pendidikan (SMK: X=50, XI=48, XII=46)
        $rombelTargetJp = [];
        $maxRequiredJp = 0;
        foreach ($rombels as $r) {
            $tStr = (string) ($r->tingkat_pendidikan_id ?? '');
            $target = (int) ($jpTingkat[$tStr] ?? 50);
            $rombelTargetJp[$r->rombongan_belajar_id] = $target;
            if ($target > $maxRequiredJp) {
                $maxRequiredJp = $target;
            }
        }

        // Ambil konfigurasi distribusi slot harian terinci per tingkat (X, XI, XII)
        $tingkatDailySlots = $options['slot_tingkat_harian'] ?? JadwalPengaturan::getTingkatDailySlotCounts($pengaturan->skema_hari ?? '5_hari');

        // Petakan alokasi slot harian spesifik per rombel berdasarkan tingkatnya
        $rombelDailySlotCounts = [];
        $allNeededSlots = [10];
        foreach ($rombels as $r) {
            $tStr = (string) ($r->tingkat_pendidikan_id ?? '10');
            $slotsCfg = $tingkatDailySlots[$tStr] ?? ($tingkatDailySlots['10'] ?? $dailySlotCounts);
            $rombelDailySlotCounts[$r->rombongan_belajar_id] = $slotsCfg;
            foreach ($slotsCfg as $dh => $sc) {
                if (in_array($dh, $hariList, true)) {
                    $allNeededSlots[] = (int) $sc;
                }
            }
        }

        // Siapkan definisi slot waktu hingga slot maksimal yang dibutuhkan
        $maxNeededSlot = max($allNeededSlots);
        if ($maxNeededSlot > count($slots)) {
            $durasi = max(20, min(90, (int) $pengaturan->durasi_per_jp));
            $lastEnd = end($slots)['selesai'] ?? '14:45';
            $curr = \Carbon\Carbon::createFromFormat('H:i', $lastEnd);
            for ($k = count($slots) + 1; $k <= $maxNeededSlot; $k++) {
                $st = $curr->format('H:i');
                $ed = $curr->copy()->addMinutes($durasi);
                $slots[$k] = [
                    'ke' => $k,
                    'mulai' => $st,
                    'selesai' => $ed->format('H:i'),
                    'label' => "Jam Ke-{$k}",
                    'is_break' => false,
                ];
                $curr = $ed->copy();
            }
        }

        // Petakan Rombel Pilihan yang terafiliasi dengan masing-masing Rombel Reguler (berdasarkan id_ruang & nama)
        $allPilihanRombels = DB::table('rombongan_belajar')
            ->where('jenis_rombel', '!=', 1)
            ->get(['rombongan_belajar_id', 'nama', 'id_ruang']);

        $pilihanToRegMap = [];
        $allFetchRombelIds = $selectedRombelIds;

        foreach ($rombels as $r) {
            $cleanName = trim($r->nama);
            $matchingPilIds = $allPilihanRombels->filter(function ($p) use ($r, $cleanName, $allPilihanRombels) {
                if (!empty($r->id_ruang) && !empty($p->id_ruang) && $r->id_ruang === $p->id_ruang) {
                    if (trim($p->nama) === $cleanName
                        || str_starts_with(trim($p->nama), $cleanName)
                        || str_starts_with($cleanName, trim($p->nama))) {
                        return true;
                    }
                    if ($allPilihanRombels->where('id_ruang', $r->id_ruang)->count() === 1) {
                        return true;
                    }
                }
                return trim($p->nama) === $cleanName
                    || trim($p->nama) === ($cleanName . ' 1')
                    || str_starts_with(trim($p->nama), $cleanName);
            })->pluck('rombongan_belajar_id')->toArray();

            foreach ($matchingPilIds as $mId) {
                $pilihanToRegMap[$mId] = $r->rombongan_belajar_id;
                $allFetchRombelIds[] = $mId;
            }
        }
        $allFetchRombelIds = array_unique($allFetchRombelIds);

        // Deteksi pengaturan kegiatan rutin (Upacara, Pembiasaan)
        $upacaraHari = null;
        $upacaraJamKe = null;
        if (!empty($pengaturan->upacara['aktif'])) {
            $upacaraHari = $pengaturan->upacara['hari'] ?? 'Senin';
            $upacaraJamKe = max(1, (int)($pengaturan->upacara['jam_ke'] ?? 1));
        }

        $pembiasaanHari = null;
        $pembiasaanJamKe = null;
        if (!empty($pengaturan->pembiasaan['aktif'])) {
            $pembiasaanHari = $pengaturan->pembiasaan['hari'] ?? 'Jumat';
            $pembiasaanJamKe = max(1, (int)($pengaturan->pembiasaan['jam_ke'] ?? 1));
        }

        try {
            return DB::transaction(function () use (
                $clearExisting,
                $selectedRombelIds,
                $rombelMap,
                $rombelTargetJp,
                $allFetchRombelIds,
                $pilihanToRegMap,
                $hariList,
                $slots,
                $rombelDailySlotCounts,
                $maxJpPerSession,
                $istirahatJamKe,
                $upacaraHari,
                $upacaraJamKe,
                $pembiasaanHari,
                $pembiasaanJamKe,
                $strictValidation,
                $pengaturan,
                $seed
            ) {
            // Jika Fresh Start, hapus jadwal eksisting pada rombel terpilih dan mapel pilihan terafiliasi (pertahankan kegiatan rutin)
            if ($clearExisting) {
                DB::table('jadwal_kbm')
                    ->whereIn('rombongan_belajar_id', $allFetchRombelIds)
                    ->whereNotIn('mata_pelajaran_id', ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'])
                    ->where('sumber', 'otomatis')
                    ->delete();
            }

            // Load Preferensi Ketersediaan Guru (Off-Days, Max JP Harian, Jam Berhalangan)
            $guruPreferences = \App\Models\JadwalGuruPreferensi::all()->keyBy('ptk_id');

            // State Tracking
            $rombelOccupied    = []; // [rombelId][hari][slot] = true
            $guruOccupied      = []; // [ptkId][hari][slot] = true
            $guruDayJp         = []; // [ptkId][hari] = totalJP
            $rombelDayMapel    = []; // [rombelId][hari][mapelId] = count
            $rombelPureKbmJp   = []; // [rombelId] = total JP KBM murni Dapodik

            // Ambil daftar seluruh PTK untuk isolasi mutlak jam istirahat guru
            $allPtkList = DB::table('gtk')->whereNotNull('ptk_id')->pluck('ptk_id')->toArray();

            // Inisialisasi: Tandai kegiatan rutin pada grid fisik agar KBM tidak menabrak slot tersebut
            foreach ($selectedRombelIds as $rId) {
                if ($upacaraHari && $upacaraJamKe) {
                    $rombelOccupied[$rId][$upacaraHari][$upacaraJamKe] = true;
                }
                if ($pembiasaanHari && $pembiasaanJamKe) {
                    $rombelOccupied[$rId][$pembiasaanHari][$pembiasaanJamKe] = true;
                }
            }

            // Kunci mutlak (Hard Lock) slot istirahat pada seluruh rombel dan seluruh guru di hari kerja reguler
            if ($istirahatJamKe) {
                foreach (['Senin', 'Selasa', 'Rabu', 'Kamis'] as $h) {
                    if (in_array($h, $hariList, true)) {
                        foreach ($selectedRombelIds as $rId) {
                            $rombelOccupied[$rId][$h][$istirahatJamKe] = true;
                        }
                        foreach ($allPtkList as $gId) {
                            $guruOccupied[$gId][$h][$istirahatJamKe] = true;
                        }
                    }
                }
            }

            // Inisialisasi state dari data eksisting yang masih tersisa
            $existingSchedules = DB::table('jadwal_kbm')
                ->where('sumber', 'otomatis')
                ->where('is_active', true)
                ->get();

            foreach ($existingSchedules as $ex) {
                $rId = $ex->rombongan_belajar_id;
                $gId = $ex->ptk_id;
                $h   = $ex->hari;
                $mId = $ex->mata_pelajaran_id;
                $isRoutine = in_array($mId, ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'], true);

                for ($k = (int)$ex->jam_ke_mulai; $k <= (int)$ex->jam_ke_selesai; $k++) {
                    $rombelOccupied[$rId][$h][$k] = true;
                    if ($gId && !$isRoutine) {
                        $guruOccupied[$gId][$h][$k] = true;
                    }
                }

                if (!$isRoutine) {
                    $durasi = max(1, (int)$ex->jam_ke_selesai - (int)$ex->jam_ke_mulai + 1);
                    $rombelDayMapel[$rId][$h][$mId] = ($rombelDayMapel[$rId][$h][$mId] ?? 0) + 1;
                    $rombelPureKbmJp[$rId] = ($rombelPureKbmJp[$rId] ?? 0) + $durasi;
                    if ($gId) {
                        $guruDayJp[$gId][$h] = ($guruDayJp[$gId][$h] ?? 0) + $durasi;
                    }
                }
            }

            // 2. Ambil data Pembelajaran Dapodik untuk rombel reguler dan mapel pilihan terafiliasi
            $rawPembelajaranList = DB::table('pembelajaran as p')
                ->leftJoin('gtk as g', 'p.ptk_id', '=', 'g.ptk_id')
                ->whereIn('p.rombongan_belajar_id', $allFetchRombelIds)
                ->select(
                    'p.pembelajaran_id',
                    'p.rombongan_belajar_id',
                    'p.mata_pelajaran_id',
                    DB::raw('COALESCE(p.nama_mata_pelajaran, p.mata_pelajaran_id_str, "-") as nama_mata_pelajaran'),
                    'p.jam_mengajar_per_minggu',
                    'p.ptk_id',
                    'g.nama as nama_guru'
                )
                ->get();

            $pembelajaranList = [];
            $rombelRequiredJjm = [];
            foreach ($rawPembelajaranList as $p) {
                $targetRegId = $pilihanToRegMap[$p->rombongan_belajar_id] ?? $p->rombongan_belajar_id;
                if (!in_array($targetRegId, $selectedRombelIds, true)) continue;

                $isPilihan = isset($pilihanToRegMap[$p->rombongan_belajar_id]);
                $namaMapel = $p->nama_mata_pelajaran;
                if ($isPilihan) {
                    $namaMapel .= ' [Pilihan]';
                }

                $pClone = clone $p;
                $pClone->rombongan_belajar_id = $targetRegId;
                $pClone->nama_mata_pelajaran = $namaMapel;
                $pembelajaranList[] = $pClone;
                $rombelRequiredJjm[$targetRegId] = ($rombelRequiredJjm[$targetRegId] ?? 0) + (int) $p->jam_mengajar_per_minggu;
            }

            // Hitung kapasitas fisik slot KBM per rombel dalam seminggu secara presisi per tingkat
            foreach ($selectedRombelIds as $rId) {
                $rSlots = $rombelDailySlotCounts[$rId] ?? $dailySlotCounts;
                $rombelPhysCap = 0;
                foreach ($hariList as $h) {
                    $slotsThisDay = $rSlots[$h] ?? 0;
                    if ($h === 'Senin' && $upacaraJamKe && $slotsThisDay >= $upacaraJamKe) $slotsThisDay -= 1;
                    if ($h === 'Jumat' && $pembiasaanJamKe && $slotsThisDay >= $pembiasaanJamKe) $slotsThisDay -= 1;
                    if ($istirahatJamKe && $h !== 'Jumat' && $slotsThisDay >= $istirahatJamKe) $slotsThisDay -= 1;
                    $rombelPhysCap += max(0, $slotsThisDay);
                }
                $req = $rombelRequiredJjm[$rId] ?? 0;
                $conf = $rombelTargetJp[$rId] ?? 50;
                $rombelTargetJp[$rId] = min($rombelPhysCap, max($conf, $req));
            }

            // Hitung skor keterikatan guru (Beban Multi-Rombel)
            // Guru yang mengajar paling banyak kelas adalah yang paling sulit dijadwalkan dan harus diprioritaskan
            $guruRombelCount = [];
            $guruTotalJjm = [];
            foreach ($pembelajaranList as $p) {
                if ($p->ptk_id) {
                    $guruRombelCount[$p->ptk_id][$p->rombongan_belajar_id] = true;
                    $guruTotalJjm[$p->ptk_id] = ($guruTotalJjm[$p->ptk_id] ?? 0) + (int)$p->jam_mengajar_per_minggu;
                }
            }
            $guruConstraintScore = [];
            foreach ($guruRombelCount as $gId => $rList) {
                $numRombel = count($rList);
                $totalJm = $guruTotalJjm[$gId] ?? 0;
                $guruConstraintScore[$gId] = ($numRombel * 50) + $totalJm;
            }

            // 3. Pecah setiap alokasi JJM menjadi Sesi KBM Terstruktur
            $sessionsToSchedule = [];

            foreach ($pembelajaranList as $p) {
                $rawJjm = (int) $p->jam_mengajar_per_minggu;
                if ($rawJjm <= 0) continue;

                // Jika mode isi slot kosong, kurangi alokasi yang sudah terjadwal sebelumnya
                if (!$clearExisting) {
                    $alreadyScheduledJp = DB::table('jadwal_kbm')
                        ->where('pembelajaran_id', $p->pembelajaran_id)
                        ->sum(DB::raw('GREATEST(1, jam_ke_selesai - jam_ke_mulai + 1)'));
                    $rawJjm = max(0, $rawJjm - $alreadyScheduledJp);
                }

                if ($rawJjm <= 0) continue;

                $isPkl = (stripos($p->nama_mata_pelajaran, 'PKL') !== false || stripos($p->nama_mata_pelajaran, 'Praktik Kerja Lapangan') !== false);
                $cScore = $p->ptk_id ? ($guruConstraintScore[$p->ptk_id] ?? 0) : 0;

                $sessionBlocks = $this->decomposeJjmIntoBlocks($rawJjm, $maxJpPerSession, $istirahatJamKe);
                foreach ($sessionBlocks as $blockDuration) {
                    $sessionsToSchedule[] = [
                        'pembelajaran_id'      => $p->pembelajaran_id,
                        'rombongan_belajar_id' => $p->rombongan_belajar_id,
                        'mata_pelajaran_id'    => $p->mata_pelajaran_id,
                        'nama_mata_pelajaran'  => $p->nama_mata_pelajaran,
                        'ptk_id'               => $p->ptk_id,
                        'duration'             => $blockDuration,
                        'is_block_kejuruan'    => ($blockDuration >= 4),
                        'is_pkl'               => $isPkl,
                        'constraint_score'     => $cScore,
                    ];
                }
            }

            // 4. Urutkan Sesi Berdasarkan Tingkat Kesulitan (Most Constrained First)
            // Prioritas: Rombel Paling Ketat (Target JP Tertinggi: X=50 JP tanpa slack) -> Guru Multi-Rombel -> Durasi
            if ($seed !== null) {
                mt_srand($seed);
                foreach ($sessionsToSchedule as &$sRef) {
                    $sRef['rnd'] = mt_rand(1, 1000);
                }
                unset($sRef);
                usort($sessionsToSchedule, function ($a, $b) use ($rombelTargetJp) {
                    $targetA = $rombelTargetJp[$a['rombongan_belajar_id']] ?? 50;
                    $targetB = $rombelTargetJp[$b['rombongan_belajar_id']] ?? 50;
                    if ($targetA !== $targetB) {
                        return $targetB <=> $targetA;
                    }
                    if ($a['constraint_score'] !== $b['constraint_score']) {
                        return $b['constraint_score'] <=> $a['constraint_score'];
                    }
                    if ($b['duration'] !== $a['duration']) {
                        return $b['duration'] <=> $a['duration'];
                    }
                    return $a['rnd'] <=> $b['rnd'];
                });
            } else {
                usort($sessionsToSchedule, function ($a, $b) use ($rombelTargetJp) {
                    $targetA = $rombelTargetJp[$a['rombongan_belajar_id']] ?? 50;
                    $targetB = $rombelTargetJp[$b['rombongan_belajar_id']] ?? 50;
                    if ($targetA !== $targetB) {
                        return $targetB <=> $targetA;
                    }
                    if ($a['constraint_score'] !== $b['constraint_score']) {
                        return $b['constraint_score'] <=> $a['constraint_score'];
                    }
                    return $b['duration'] <=> $a['duration'];
                });
            }

            $batchInserts = [];
            $unallocatedPass1 = [];

            // ==========================================
            // PASS 1: PENEMPATAN BLOK UTAMA (STRICT ZERO-GAP & PRE-BREAK BRIDGE)
            // ==========================================
            foreach ($sessionsToSchedule as $sess) {
                $rId   = $sess['rombongan_belajar_id'];
                $gId   = $sess['ptk_id'];
                $mId   = $sess['mata_pelajaran_id'];
                $dur   = $sess['duration'];
                $isPkl = $sess['is_pkl'];

                $targetRombelMax = $rombelTargetJp[$rId] ?? 50;
                if ((($rombelPureKbmJp[$rId] ?? 0) + $dur) > $targetRombelMax) {
                    $unallocatedPass1[] = $sess;
                    continue;
                }

                $placed = false;
                $sortedHari = $hariList;
                usort($sortedHari, function ($h1, $h2) use ($rId, $rombelOccupied) {
                    return count($rombelOccupied[$rId][$h1] ?? []) <=> count($rombelOccupied[$rId][$h2] ?? []);
                });

                foreach ($sortedHari as $h) {
                    $gPref = $gId ? ($guruPreferences[$gId] ?? null) : null;
                    if ($gPref) {
                        if (!empty($gPref->hari_off) && in_array($h, $gPref->hari_off, true)) continue;
                        if ($gPref->max_jp_per_hari && (($guruDayJp[$gId][$h] ?? 0) + $dur > $gPref->max_jp_per_hari)) continue;
                    }

                    // Hindari mapel duplicate di hari yang sama untuk teori non-blok
                    $alreadyOnDay = ($rombelDayMapel[$rId][$h][$mId] ?? 0) > 0;
                    if ($alreadyOnDay && !$sess['is_block_kejuruan'] && !$isPkl) {
                        continue;
                    }

                    $candidateSlots = $this->findCandidateSlots(
                        $rId, $h, $dur, $rombelOccupied, $rombelDailySlotCounts, $istirahatJamKe, false, $isPkl
                    );

                    foreach ($candidateSlots as $cand) {
                        if ($this->tryPlaceCandidate(
                            $sess, $cand, $rId, $h, $gPref, $rombelOccupied, $guruOccupied,
                            $rombelDayMapel, $rombelPureKbmJp, $guruDayJp, $batchInserts,
                            $slots, $rombelMap, 'Auto-Generated'
                        )) {
                            $placed = true;
                            break;
                        }
                    }

                    if ($placed) break;
                }

                if (!$placed) {
                    $unallocatedPass1[] = $sess;
                }
            }

            // ==========================================
            // PASS 2: ELASTIC DECOMPOSITION (2+2 atau 2+1)
            // ==========================================
            // Memecah blok >= 3 JP yang belum dapat slot menjadi sub-blok fleksibel
            $unallocatedPass2 = [];
            $splittablePass2 = [];
            foreach ($unallocatedPass1 as $u) {
                if ($u['duration'] >= 4) {
                    $half = (int) floor($u['duration'] / 2);
                    $splittablePass2[] = array_merge($u, ['duration' => $half]);
                    $splittablePass2[] = array_merge($u, ['duration' => $u['duration'] - $half]);
                } elseif ($u['duration'] === 3) {
                    $splittablePass2[] = array_merge($u, ['duration' => 2]);
                    $splittablePass2[] = array_merge($u, ['duration' => 1]);
                } else {
                    $splittablePass2[] = $u;
                }
            }

            foreach ($splittablePass2 as $sess) {
                $rId   = $sess['rombongan_belajar_id'];
                $gId   = $sess['ptk_id'];
                $dur   = $sess['duration'];
                $isPkl = $sess['is_pkl'];

                $targetRombelMax = $rombelTargetJp[$rId] ?? 50;
                if ((($rombelPureKbmJp[$rId] ?? 0) + $dur) > $targetRombelMax) {
                    $unallocatedPass2[] = $sess;
                    continue;
                }

                $placed = false;
                $sortedHari = $hariList;
                usort($sortedHari, function ($h1, $h2) use ($rId, $rombelOccupied) {
                    return count($rombelOccupied[$rId][$h1] ?? []) <=> count($rombelOccupied[$rId][$h2] ?? []);
                });

                foreach ($sortedHari as $h) {
                    $gPref = $gId ? ($guruPreferences[$gId] ?? null) : null;
                    if ($gPref) {
                        if (!empty($gPref->hari_off) && in_array($h, $gPref->hari_off, true)) continue;
                        if ($gPref->max_jp_per_hari && (($guruDayJp[$gId][$h] ?? 0) + $dur > $gPref->max_jp_per_hari)) continue;
                    }

                    $candidates = $this->findCandidateSlots(
                        $rId, $h, $dur, $rombelOccupied, $rombelDailySlotCounts, $istirahatJamKe, false, $isPkl
                    );

                    foreach ($candidates as $cand) {
                        if ($this->tryPlaceCandidate(
                            $sess, $cand, $rId, $h, $gPref, $rombelOccupied, $guruOccupied,
                            $rombelDayMapel, $rombelPureKbmJp, $guruDayJp, $batchInserts,
                            $slots, $rombelMap, 'Auto-Generated (Pass 2)'
                        )) {
                            $placed = true;
                            break;
                        }
                    }

                    if ($placed) break;
                }

                if (!$placed) {
                    $unallocatedPass2[] = $sess;
                }
            }

            // ==========================================
            // PASS 3: CONTIGUOUS 1 JP UNIT PLACEMENT
            // ==========================================
            // Sesi yang masih tersisa dipecah menjadi unit 1 JP individual dan ditempatkan secara contiguous
            $splittablePass3 = [];
            foreach ($unallocatedPass2 as $u) {
                for ($i = 0; $i < $u['duration']; $i++) {
                    $splittablePass3[] = array_merge($u, ['duration' => 1]);
                }
            }

            $unallocatedSessions = [];
            foreach ($splittablePass3 as $sess) {
                $rId   = $sess['rombongan_belajar_id'];
                $gId   = $sess['ptk_id'];
                $isPkl = $sess['is_pkl'];

                $targetRombelMax = $rombelTargetJp[$rId] ?? 50;
                if ((($rombelPureKbmJp[$rId] ?? 0) + 1) > $targetRombelMax) {
                    $unallocatedSessions[] = $sess;
                    continue;
                }

                $placed = false;
                $sortedHari = $hariList;
                usort($sortedHari, function ($h1, $h2) use ($rId, $rombelOccupied) {
                    return count($rombelOccupied[$rId][$h1] ?? []) <=> count($rombelOccupied[$rId][$h2] ?? []);
                });

                foreach ($sortedHari as $h) {
                    $gPref = $gId ? ($guruPreferences[$gId] ?? null) : null;
                    if ($gPref) {
                        if (!empty($gPref->hari_off) && in_array($h, $gPref->hari_off, true)) continue;
                        if ($gPref->max_jp_per_hari && (($guruDayJp[$gId][$h] ?? 0) + 1 > $gPref->max_jp_per_hari)) continue;
                    }

                    $candidates = $this->findCandidateSlots(
                        $rId, $h, 1, $rombelOccupied, $rombelDailySlotCounts, $istirahatJamKe, true, $isPkl
                    );

                    foreach ($candidates as $cand) {
                        if ($this->tryPlaceCandidate(
                            $sess, $cand, $rId, $h, $gPref, $rombelOccupied, $guruOccupied,
                            $rombelDayMapel, $rombelPureKbmJp, $guruDayJp, $batchInserts,
                            $slots, $rombelMap, 'Auto-Generated (Gap Fill 1 JP)'
                        )) {
                            $placed = true;
                            break;
                        }
                    }

                    if ($placed) break;
                }

                if (!$placed) {
                    $unallocatedSessions[] = $sess;
                }
            }

            // Jalankan Smart Compaction awal untuk merapatkan jadwal dan menggabungkan lubang-lubang kecil menjadi blok contiguous
            $this->compactRombelSchedules($batchInserts, $rombelOccupied, $guruOccupied, $rombelDailySlotCounts, $istirahatJamKe, $slots);
            $this->mergeAdjacentSessions($batchInserts, $slots);

            // ==========================================
            // PASS 3.5: AUGMENTING SWAP (1-STEP EJECTION)
            // ==========================================
            // Menyelamatkan sesi 1 JP yang bentrok guru dengan merelokasi matpel lain yang fleksibel
            if (!empty($unallocatedSessions)) {
                $this->resolveUnallocatedBySwap(
                    $unallocatedSessions,
                    $batchInserts,
                    $rombelOccupied,
                    $guruOccupied,
                    $rombelDayMapel,
                    $rombelPureKbmJp,
                    $guruDayJp,
                    $rombelDailySlotCounts,
                    $istirahatJamKe,
                    $slots,
                    $rombelMap,
                    $hariList,
                    $pengaturan
                );
            }

            // PASS 3.8: INTRA-ROMBEL RELOCATION (Relokasi Internal Rombel)
            // Menukar sesi dalam rombel yang sama agar slot kosong dapat terisi guru yang sedang bentrok
            if (!empty($unallocatedSessions)) {
                $this->resolveUnallocatedByIntraRombelSwap(
                    $unallocatedSessions,
                    $batchInserts,
                    $rombelOccupied,
                    $guruOccupied,
                    $rombelDayMapel,
                    $rombelPureKbmJp,
                    $guruDayJp,
                    $rombelDailySlotCounts,
                    $istirahatJamKe,
                    $slots,
                    $rombelMap,
                    $hariList,
                    $pengaturan
                );
            }

            // Validasi Keterisian Penuh per Rombel (Memastikan tidak ada rombel yang jam KBM-nya bolong)
            $unfilledRombels = [];
            foreach ($selectedRombelIds as $rId) {
                $req = $rombelRequiredJjm[$rId] ?? 0;
                $target = $rombelTargetJp[$rId] ?? 50;
                $expected = min($target, $req);
                $sched = $rombelPureKbmJp[$rId] ?? 0;
                if ($sched < $expected) {
                    $unfilledRombels[] = [
                        'rombongan_belajar_id' => $rId,
                        'nama_rombel'          => $rombelMap[$rId]->nama ?? $rId,
                        'required_jp'          => $expected,
                        'scheduled_jp'         => $sched,
                        'missing_jp'           => $expected - $sched,
                    ];
                }
            }

            $unallocatedFinal = count($unallocatedSessions);

            // Validasi Ketat: Jika diaktifkan dan ada rombel yang belum terisi penuh atau mapel belum termapping, batalkan transaksi!
            if ($strictValidation && ($unallocatedFinal > 0 || count($unfilledRombels) > 0)) {
                $unmappedList = [];
                foreach ($unallocatedSessions as $u) {
                    $rName = $rombelMap[$u['rombongan_belajar_id']]->nama ?? $u['rombongan_belajar_id'];
                    $gName = $u['ptk_id'] ? (DB::table('gtk')->where('ptk_id', $u['ptk_id'])->value('nama') ?? 'Guru') : '-';
                    $unmappedList[] = [
                        'rombongan_belajar_id' => $u['rombongan_belajar_id'],
                        'nama_rombel'          => $rName,
                        'mata_pelajaran_id'    => $u['mata_pelajaran_id'],
                        'nama_mata_pelajaran'  => $u['nama_mata_pelajaran'],
                        'ptk_id'               => $u['ptk_id'],
                        'nama_guru'            => $gName,
                        'durasi'               => $u['duration'],
                    ];
                }

                $unfilledCount = count($unfilledRombels);
                $msg = "Validasi Ketat: Generate Jadwal KBM Gagal. Terdapat {$unfilledCount} rombel belum terisi penuh atau {$unallocatedFinal} sesi jam pelajaran belum dapat dipetakan tanpa bentrok.";

                throw new SchedulerValidationException($msg, $unmappedList, $unfilledRombels);
            }

            // ==========================================
            // PASS 4: SMART COMPACTION (HOLE ELIMINATION)
            // ==========================================
            $this->compactRombelSchedules($batchInserts, $rombelOccupied, $guruOccupied, $rombelDailySlotCounts, $istirahatJamKe, $slots);
            $this->mergeAdjacentSessions($batchInserts, $slots);
            $solved = $this->blockifyLayout(
                $batchInserts, $slots, $rombelOccupied, $guruOccupied, $rombelDailySlotCounts,
                $hariList, $maxJpPerSession, $istirahatJamKe, $guruPreferences, $seed, 25.0
            );
            $layoutStats = $this->optimizeSlotLayout(
                $batchInserts, $slots, $rombelOccupied, $guruOccupied,
                $rombelDailySlotCounts, $hariList, $istirahatJamKe, $guruPreferences, $seed,
                $solved ? 0.0 : 6.0
            );

            // 6. Bulk Insert Hasil Generate ke Database (Chunk per 200 rows)
            foreach (array_chunk($batchInserts, 200) as $chunk) {
                DB::table('jadwal_kbm')->insert($chunk);
            }

            // Pastikan kegiatan rutin (Upacara, Pembiasaan & Istirahat) selalu aktif dan tersinkron
            \App\Models\JadwalPengaturan::syncRoutineActivities();

            $totalGenerated = count($batchInserts);
            $totalJpCreated = array_reduce($batchInserts, function ($acc, $item) {
                return $acc + ($item['jam_ke_selesai'] - $item['jam_ke_mulai'] + 1);
            }, 0);

            $note = '';
            if ($layoutStats['single_jp'] > 0 || $layoutStats['split'] > 0) {
                $note = " Catatan: masih ada {$layoutStats['single_jp']} sesi 1 JP dan {$layoutStats['split']} mapel terpisah di hari yang sama karena keterbatasan jadwal guru.";
            }

            return [
                'success'         => true,
                'message'         => "Berhasil men-generate {$totalGenerated} jadwal KBM ({$totalJpCreated} JP) secara optimal tanpa bentrok dan jam kosong tertata rapi!" . $note,
                'total_generated' => $totalGenerated,
                'total_jp'        => $totalJpCreated,
                'total_rombel'    => count($selectedRombelIds),
                'unallocated'     => $unallocatedFinal,
                'unfilled_rombels'=> $unfilledRombels,
            ];
        });
    } catch (SchedulerValidationException $e) {
        throw $e;
        return [
            'success'           => false,
            'is_incomplete'     => true,
            'message'           => $e->getMessage(),
            'unmapped_subjects' => $e->getUnmappedSubjects(),
            'unfilled_rombels'  => $e->getUnfilledRombels(),
            'total_generated'   => 0,
            'total_jp'          => 0,
        ];
    }
}

    public function findCandidateSlots(
        string $rId,
        string $h,
        int $dur,
        array &$rombelOccupied,
        array $dailySlotCounts,
        ?int $istirahatJamKe,
        bool $allowGaps = false,
        bool $isPkl = false
    ): array {
        return $this->placementService->findCandidateSlots($rId, $h, $dur, $rombelOccupied, $dailySlotCounts, $istirahatJamKe, $allowGaps, $isPkl);
    }

    public function tryPlaceCandidate(
        array $sess,
        array $cand,
        string $rId,
        string $h,
        ?object $gPref,
        array &$rombelOccupied,
        array &$guruOccupied,
        array &$rombelDayMapel,
        array &$rombelPureKbmJp,
        array &$guruDayJp,
        array &$batchInserts,
        array $slots,
        mixed $rombelMap,
        string $keterangan
    ): bool {
        return $this->placementService->tryPlaceCandidate(
            $sess, $cand, $rId, $h, $gPref, $rombelOccupied, $guruOccupied,
            $rombelDayMapel, $rombelPureKbmJp, $guruDayJp, $batchInserts,
            $slots, $rombelMap, $keterangan
        );
    }

    public function compactRombelSchedules(
        array &$batchInserts,
        array &$rombelOccupied,
        array &$guruOccupied,
        array $dailySlotCounts,
        ?int $istirahatJamKe,
        array $slots
    ): void {
        $this->placementService->compactRombelSchedules($batchInserts, $rombelOccupied, $guruOccupied, $dailySlotCounts, $istirahatJamKe, $slots);
    }

    public function resolveUnallocatedBySwap(
        array &$unallocatedSessions,
        array &$batchInserts,
        array &$rombelOccupied,
        array &$guruOccupied,
        array &$rombelDayMapel,
        array &$rombelPureKbmJp,
        array &$guruDayJp,
        array $rombelDailySlotCounts,
        ?int $istirahatJamKe,
        array $slots,
        mixed $rombelMap,
        array $hariList,
        ?object $pengaturan
    ): void {
        $this->swapResolver->resolveUnallocatedBySwap(
            $unallocatedSessions, $batchInserts, $rombelOccupied, $guruOccupied,
            $rombelDayMapel, $rombelPureKbmJp, $guruDayJp, $rombelDailySlotCounts,
            $istirahatJamKe, $slots, $rombelMap, $hariList, $pengaturan
        );
    }

    public function resolveUnallocatedByIntraRombelSwap(
        array &$unallocatedSessions,
        array &$batchInserts,
        array &$rombelOccupied,
        array &$guruOccupied,
        array &$rombelDayMapel,
        array &$rombelPureKbmJp,
        array &$guruDayJp,
        array $rombelDailySlotCounts,
        ?int $istirahatJamKe,
        array $slots,
        mixed $rombelMap,
        array $hariList,
        ?object $pengaturan
    ): void {
        $this->swapResolver->resolveUnallocatedByIntraRombelSwap(
            $unallocatedSessions, $batchInserts, $rombelOccupied, $guruOccupied,
            $rombelDayMapel, $rombelPureKbmJp, $guruDayJp, $rombelDailySlotCounts,
            $istirahatJamKe, $slots, $rombelMap, $hariList, $pengaturan
        );
    }

    public function buildScheduleItem(
        array $sess,
        string $rId,
        string $h,
        int $startK,
        int $endK,
        array $slots,
        mixed $rombelMap,
        string $keterangan
    ): array {
        return AutoSchedulerSupport::buildScheduleItem($sess, $rId, $h, $startK, $endK, $slots, $rombelMap, $keterangan);
    }

    public function isTeacherUnavailable(?object $gPref, string $h, int $startK, int $endK): bool
    {
        return AutoSchedulerSupport::isTeacherUnavailable($gPref, $h, $startK, $endK);
    }

    public function decomposeJjmIntoBlocks(int $jjm, int $maxBlock = 3, ?int $istirahatJamKe = null): array
    {
        return AutoSchedulerSupport::decomposeJjmIntoBlocks($jjm, $maxBlock, $istirahatJamKe);
    }

    public function mergeAdjacentSessions(array &$batchInserts, array $slots): void
    {
        AutoSchedulerSupport::mergeAdjacentSessions($batchInserts, $slots);
    }

    public function blockifyLayout(
        array &$batchInserts,
        array $slots,
        array $rombelOccupied,
        array $guruOccupied,
        array $rombelDailySlotCounts,
        array $hariList,
        int $maxJpPerSession,
        ?int $istirahatJamKe,
        mixed $guruPreferences,
        ?int $seed,
        float $budget
    ): bool {
        return $this->layoutOptimizer->blockifyLayout(
            $batchInserts, $slots, $rombelOccupied, $guruOccupied, $rombelDailySlotCounts,
            $hariList, $maxJpPerSession, $istirahatJamKe, $guruPreferences, $seed, $budget
        );
    }

    public function optimizeSlotLayout(
        array &$batchInserts,
        array $slots,
        array $rombelOccupied,
        array $guruOccupied,
        array $rombelDailySlotCounts,
        array $hariList,
        ?int $istirahatJamKe,
        mixed $guruPreferences,
        ?int $seed,
        float $seconds = 6.0
    ): array {
        return $this->layoutOptimizer->optimizeSlotLayout(
            $batchInserts, $slots, $rombelOccupied, $guruOccupied,
            $rombelDailySlotCounts, $hariList, $istirahatJamKe, $guruPreferences, $seed,
            $seconds
        );
    }
}