<?php

namespace App\Services;

use App\Exceptions\SchedulerValidationException;
use App\Models\JadwalKbm;
use App\Models\JadwalPengaturan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoSchedulerService
{
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
        $rombelQuery = DB::table('rombongan_belajar')
            ->where(function ($w) {
                $w->where('jenis_rombel', 1)
                  ->orWhere('jenis_rombel_str', 'Kelas')
                  ->orWhere('jenis_rombel_str', 'LIKE', '%reguler%');
            })
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

    /**
     * Cari seluruh kandidat slot waktu yang valid untuk rombel pada hari tertentu secara STRICT ZERO-GAP
     *
     * @return array Array kandidat slot [ ['start' => int, 'end' => int, 'split' => bool] | ['start_pagi' => int, ...] ]
     */
    private function findCandidateSlots(
        string $rId,
        string $h,
        int $dur,
        array &$rombelOccupied,
        array $dailySlotCounts,
        ?int $istirahatJamKe,
        bool $allowGaps = false,
        bool $isPkl = false
    ): array {
        $dayMax = isset($dailySlotCounts[$rId][$h]) 
            ? $dailySlotCounts[$rId][$h] 
            : ($dailySlotCounts[$h] ?? 12);
        $rOcc = $rombelOccupied[$rId][$h] ?? [];

        $morningMax = ($h === 'Jumat' || !$istirahatJamKe) ? $dayMax : ($istirahatJamKe - 1);
        $candidates = [];

        // 1. Sesi Pagi: Scan semua window valid yang muat $dur (prioritas dari jam awal)
        for ($startK = 1; $startK <= ($morningMax - $dur + 1); $startK++) {
            $endK = $startK + $dur - 1;
            $free = true;
            for ($k = $startK; $k <= $endK; $k++) {
                if (!empty($rOcc[$k])) { $free = false; break; }
            }
            if ($free) {
                $candidates[] = [
                    'start' => $startK,
                    'end'   => $endK,
                    'split' => false,
                ];
            }
        }

        // 2. Bridge Across Break (Pagi -> Siang): Hanya jika durasi > 1 dan bukan Jumat
        if ($h !== 'Jumat' && $istirahatJamKe && $dur > 1) {
            for ($pagiFit = min($dur - 1, $morningMax); $pagiFit >= 1; $pagiFit--) {
                $siangFit = $dur - $pagiFit;
                $sp = $morningMax - $pagiFit + 1;
                $ep = $morningMax;
                $ss = $istirahatJamKe + 1;
                $es = $ss + $siangFit - 1;

                if ($es <= $dayMax && $sp >= 1) {
                    $free = true;
                    for ($k = $sp; $k <= $ep; $k++) {
                        if (!empty($rOcc[$k])) { $free = false; break; }
                    }
                    for ($k = $ss; $k <= $es; $k++) {
                        if (!empty($rOcc[$k])) { $free = false; break; }
                    }
                    if ($free) {
                        $candidates[] = [
                            'start_pagi'  => $sp,
                            'end_pagi'    => $ep,
                            'start_siang' => $ss,
                            'end_siang'   => $es,
                            'dur_pagi'    => $pagiFit,
                            'dur_siang'   => $siangFit,
                            'split'       => true,
                        ];
                    }
                }
            }
        }

        // 3. Sesi Siang: Scan semua window di siang hari
        if ($h !== 'Jumat' && $istirahatJamKe) {
            $afternoonStart = $istirahatJamKe + 1;
            for ($startK = $afternoonStart; $startK <= ($dayMax - $dur + 1); $startK++) {
                $endK = $startK + $dur - 1;
                $free = true;
                for ($k = $startK; $k <= $endK; $k++) {
                    if (!empty($rOcc[$k])) { $free = false; break; }
                }
                if ($free) {
                    $candidates[] = [
                        'start' => $startK,
                        'end'   => $endK,
                        'split' => false,
                    ];
                }
            }
        }

        // 4. Fallback Cadangan (Jika allowGaps aktif):
        if (empty($candidates) && $allowGaps) {
            for ($startK = 1; $startK <= ($dayMax - $dur + 1); $startK++) {
                $endK = $startK + $dur - 1;
                if ($istirahatJamKe && $startK <= $istirahatJamKe && $endK >= $istirahatJamKe) continue;

                $free = true;
                for ($k = $startK; $k <= $endK; $k++) {
                    if (!empty($rOcc[$k])) { $free = false; break; }
                }
                if ($free) {
                    $candidates[] = [
                        'start' => $startK,
                        'end'   => $endK,
                        'split' => false,
                    ];
                    break;
                }
            }
        }

        return $candidates;
    }

    /**
     * Eksekusi penempatan kandidat sesi ke matriks occupied dan batch inserts
     */
    private function tryPlaceCandidate(
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
        $rombelMap,
        string $keterangan
    ): bool {
        $gId   = $sess['ptk_id'];
        $mId   = $sess['mata_pelajaran_id'];
        $dur   = $sess['duration'];

        if (!$cand['split']) {
            $startK = $cand['start'];
            $endK   = $cand['end'];

            if ($this->isTeacherUnavailable($gPref, $h, $startK, $endK)) {
                return false;
            }

            if ($gId) {
                for ($k = $startK; $k <= $endK; $k++) {
                    if (!empty($guruOccupied[$gId][$h][$k])) {
                        return false;
                    }
                }
            }

            for ($k = $startK; $k <= $endK; $k++) {
                $rombelOccupied[$rId][$h][$k] = true;
                if ($gId) {
                    $guruOccupied[$gId][$h][$k] = true;
                }
            }

            $rombelDayMapel[$rId][$h][$mId] = ($rombelDayMapel[$rId][$h][$mId] ?? 0) + 1;
            $rombelPureKbmJp[$rId] = ($rombelPureKbmJp[$rId] ?? 0) + $dur;
            if ($gId) {
                $guruDayJp[$gId][$h] = ($guruDayJp[$gId][$h] ?? 0) + $dur;
            }

            $batchInserts[] = $this->buildScheduleItem($sess, $rId, $h, $startK, $endK, $slots, $rombelMap, $keterangan);
            return true;
        } else {
            $sp = $cand['start_pagi'];
            $ep = $cand['end_pagi'];
            $ss = $cand['start_siang'];
            $es = $cand['end_siang'];

            if ($this->isTeacherUnavailable($gPref, $h, $sp, $ep) || $this->isTeacherUnavailable($gPref, $h, $ss, $es)) {
                return false;
            }

            if ($gId) {
                for ($k = $sp; $k <= $ep; $k++) {
                    if (!empty($guruOccupied[$gId][$h][$k])) return false;
                }
                for ($k = $ss; $k <= $es; $k++) {
                    if (!empty($guruOccupied[$gId][$h][$k])) return false;
                }
            }

            for ($k = $sp; $k <= $ep; $k++) {
                $rombelOccupied[$rId][$h][$k] = true;
                if ($gId) $guruOccupied[$gId][$h][$k] = true;
            }
            for ($k = $ss; $k <= $es; $k++) {
                $rombelOccupied[$rId][$h][$k] = true;
                if ($gId) $guruOccupied[$gId][$h][$k] = true;
            }

            $rombelDayMapel[$rId][$h][$mId] = ($rombelDayMapel[$rId][$h][$mId] ?? 0) + 1;
            $rombelPureKbmJp[$rId] = ($rombelPureKbmJp[$rId] ?? 0) + $dur;
            if ($gId) {
                $guruDayJp[$gId][$h] = ($guruDayJp[$gId][$h] ?? 0) + $dur;
            }

            $batchInserts[] = $this->buildScheduleItem($sess, $rId, $h, $sp, $ep, $slots, $rombelMap, $keterangan . ' (Pagi)');
            $batchInserts[] = $this->buildScheduleItem($sess, $rId, $h, $ss, $es, $slots, $rombelMap, $keterangan . ' (Lanjutan Siang)');
            return true;
        }
    }

    /**
     * Padatkan jadwal rombel ke jam-jam awal (Bubble Compaction) agar jam kosong terkumpul di akhir hari
     */
    private function compactRombelSchedules(
        array &$batchInserts,
        array &$rombelOccupied,
        array &$guruOccupied,
        array $dailySlotCounts,
        ?int $istirahatJamKe,
        array $slots
    ): void {
        usort($batchInserts, function ($a, $b) {
            if ($a['rombongan_belajar_id'] !== $b['rombongan_belajar_id']) {
                return strcmp($a['rombongan_belajar_id'], $b['rombongan_belajar_id']);
            }
            if ($a['hari'] !== $b['hari']) {
                return strcmp($a['hari'], $b['hari']);
            }
            return $a['jam_ke_mulai'] <=> $b['jam_ke_mulai'];
        });

        // 1. Multi-Step Flush Left Compaction
        for ($iter = 0; $iter < 12; $iter++) {
            $anyMoved = false;

            foreach ($batchInserts as &$item) {
                if (in_array($item['mata_pelajaran_id'], ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'], true)) {
                    continue;
                }

                $rId   = $item['rombongan_belajar_id'];
                $h     = $item['hari'];
                $gId   = $item['ptk_id'];
                $start = (int) $item['jam_ke_mulai'];
                $end   = (int) $item['jam_ke_selesai'];
                $dur   = $end - $start + 1;

                // Cross-Break Compaction: Coba lompat dari sesi siang ke sesi pagi jika pagi ada slot kosong yang muat
                if ($istirahatJamKe && $start > $istirahatJamKe) {
                    $morningMax = $istirahatJamKe - 1;
                    for ($mStart = 1; $mStart <= ($morningMax - $dur + 1); $mStart++) {
                        $mEnd = $mStart + $dur - 1;
                        $mFree = true;
                        for ($mk = $mStart; $mk <= $mEnd; $mk++) {
                            if (!empty($rombelOccupied[$rId][$h][$mk])) {
                                $mFree = false;
                                break;
                            }
                            if ($gId && !empty($guruOccupied[$gId][$h][$mk])) {
                                $mFree = false;
                                break;
                            }
                        }

                        if ($mFree) {
                            for ($k = $start; $k <= $end; $k++) {
                                unset($rombelOccupied[$rId][$h][$k]);
                                if ($gId) unset($guruOccupied[$gId][$h][$k]);
                            }

                            for ($k = $mStart; $k <= $mEnd; $k++) {
                                $rombelOccupied[$rId][$h][$k] = true;
                                if ($gId) $guruOccupied[$gId][$h][$k] = true;
                            }

                            $item['jam_ke_mulai'] = $mStart;
                            $item['jam_ke_selesai'] = $mEnd;
                            $item['jam_mulai'] = $slots[$mStart]['mulai'] ? (strlen($slots[$mStart]['mulai']) === 5 ? $slots[$mStart]['mulai'] . ':00' : $slots[$mStart]['mulai']) : $item['jam_mulai'];
                            $item['jam_selesai'] = $slots[$mEnd]['selesai'] ? (strlen($slots[$mEnd]['selesai']) === 5 ? $slots[$mEnd]['selesai'] . ':00' : $slots[$mEnd]['selesai']) : $item['jam_selesai'];

                            $anyMoved = true;
                            break;
                        }
                    }
                    if ($anyMoved) continue;
                }

                // Multi-step shift left dalam segmen yang sama
                $minAllowed = ($istirahatJamKe && $start > $istirahatJamKe) ? ($istirahatJamKe + 1) : 1;
                for ($targetStart = $minAllowed; $targetStart < $start; $targetStart++) {
                    $targetEnd = $targetStart + $dur - 1;

                    if ($istirahatJamKe && $start <= $istirahatJamKe && $targetEnd >= $istirahatJamKe) {
                        continue;
                    }

                    $canFit = true;
                    for ($k = $targetStart; $k <= $targetEnd; $k++) {
                        if ($k < $start && !empty($rombelOccupied[$rId][$h][$k])) {
                            $canFit = false;
                            break;
                        }
                        if ($gId && $k < $start && !empty($guruOccupied[$gId][$h][$k])) {
                            $canFit = false;
                            break;
                        }
                    }

                    if ($canFit) {
                        for ($k = $start; $k <= $end; $k++) {
                            unset($rombelOccupied[$rId][$h][$k]);
                            if ($gId) unset($guruOccupied[$gId][$h][$k]);
                        }
                        for ($k = $targetStart; $k <= $targetEnd; $k++) {
                            $rombelOccupied[$rId][$h][$k] = true;
                            if ($gId) $guruOccupied[$gId][$h][$k] = true;
                        }

                        $item['jam_ke_mulai'] = $targetStart;
                        $item['jam_ke_selesai'] = $targetEnd;
                        $item['jam_mulai'] = $slots[$targetStart]['mulai'] ? (strlen($slots[$targetStart]['mulai']) === 5 ? $slots[$targetStart]['mulai'] . ':00' : $slots[$targetStart]['mulai']) : $item['jam_mulai'];
                        $item['jam_selesai'] = $slots[$targetEnd]['selesai'] ? (strlen($slots[$targetEnd]['selesai']) === 5 ? $slots[$targetEnd]['selesai'] . ':00' : $slots[$targetEnd]['selesai']) : $item['jam_selesai'];

                        $anyMoved = true;
                        break;
                    }
                }
            }
            unset($item);

            if (!$anyMoved) break;
        }
    }

    /**
     * Resolusi sesi unallocated via Augmenting Swap (1-Step Ejection Chain)
     */
    private function resolveUnallocatedBySwap(
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
        $rombelMap,
        array $hariList,
        ?object $pengaturan
    ): void {
        for ($round = 0; $round < 3; $round++) {
            $unallocatedAfterSwap = [];
            $anySwapped = false;

            foreach ($unallocatedSessions as $sess) {
                $rId = $sess['rombongan_belajar_id'];
                $gId = $sess['ptk_id'];
                $placed = false;

                foreach ($hariList as $h) {
                    $dayMax = isset($rombelDailySlotCounts[$rId][$h]) ? $rombelDailySlotCounts[$rId][$h] : ($rombelDailySlotCounts[$h] ?? 12);

                    for ($k = 1; $k <= $dayMax; $k++) {
                        if ($istirahatJamKe && $k === $istirahatJamKe) continue;
                        if ($k === 1 && $h === 'Senin' && !empty($pengaturan->upacara['aktif'])) continue;
                        if ($k === 1 && $h === 'Jumat' && !empty($pengaturan->pembiasaan['aktif'])) continue;

                        // Guru harus bebas pada slot (h, k)
                        if ($gId && !empty($guruOccupied[$gId][$h][$k])) continue;

                        // Cari item yang menempati slot (rId, h, k) di $batchInserts
                        $foundItemIdx = null;
                        foreach ($batchInserts as $idx => $itemCheck) {
                            if ($itemCheck['rombongan_belajar_id'] === $rId && $itemCheck['hari'] === $h) {
                                if ($k >= (int)$itemCheck['jam_ke_mulai'] && $k <= (int)$itemCheck['jam_ke_selesai']) {
                                    $foundItemIdx = $idx;
                                    break;
                                }
                            }
                        }

                        if ($foundItemIdx === null) continue;

                        $bItem = $batchInserts[$foundItemIdx];
                        $gB = $bItem['ptk_id'];
                        $bStart = (int) $bItem['jam_ke_mulai'];
                        $bEnd = (int) $bItem['jam_ke_selesai'];
                        $bDur = $bEnd - $bStart + 1;
                        $bMapelId = $bItem['mata_pelajaran_id'];

                        // Batasi swap hanya untuk durasi wajar (<= 3 JP) dan bukan mapel rutin
                        if ($bDur > 3 || in_array($bMapelId, ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'], true)) continue;

                        // 1. Evikasi sementara bItem dari rombel & guru
                        for ($x = $bStart; $x <= $bEnd; $x++) {
                            unset($rombelOccupied[$rId][$h][$x]);
                            if ($gB) unset($guruOccupied[$gB][$h][$x]);
                        }

                        // Kunci slot (h, k) agar bItem TIDAK BISA kembali ke slot ini (karena slot ini akan dipakai sess)
                        $rombelOccupied[$rId][$h][$k] = true;
                        if ($gId) $guruOccupied[$gId][$h][$k] = true;

                        // 2. Cari slot alternatif untuk bItem
                        $altPlaced = false;
                        foreach ($hariList as $altH) {
                            $altCands = $this->findCandidateSlots($rId, $altH, $bDur, $rombelOccupied, $rombelDailySlotCounts, $istirahatJamKe, false);
                            foreach ($altCands as $altCand) {
                                if (!$altCand['split']) {
                                    $as = $altCand['start'];
                                    $ae = $altCand['end'];
                                    // Slot alternatif tidak boleh menimpa posisi bItem itu sendiri
                                    if ($altH === $h && ($as <= $bEnd && $ae >= $bStart)) continue;

                                    $free = true;
                                    if ($gB) {
                                        for ($x = $as; $x <= $ae; $x++) {
                                            if (!empty($guruOccupied[$gB][$altH][$x])) { $free = false; break; }
                                        }
                                    }
                                    if ($free) {
                                        for ($x = $as; $x <= $ae; $x++) {
                                            $rombelOccupied[$rId][$altH][$x] = true;
                                            if ($gB) $guruOccupied[$gB][$altH][$x] = true;
                                        }

                                        // Mutasi langsung pada array $batchInserts tanpa referensi
                                        $batchInserts[$foundItemIdx]['hari'] = $altH;
                                        $batchInserts[$foundItemIdx]['jam_ke_mulai'] = $as;
                                        $batchInserts[$foundItemIdx]['jam_ke_selesai'] = $ae;
                                        $batchInserts[$foundItemIdx]['jam_mulai'] = $slots[$as]['mulai'] ? (strlen($slots[$as]['mulai']) === 5 ? $slots[$as]['mulai'] . ':00' : $slots[$as]['mulai']) : $batchInserts[$foundItemIdx]['jam_mulai'];
                                        $batchInserts[$foundItemIdx]['jam_selesai'] = $slots[$ae]['selesai'] ? (strlen($slots[$ae]['selesai']) === 5 ? $slots[$ae]['selesai'] . ':00' : $slots[$ae]['selesai']) : $batchInserts[$foundItemIdx]['jam_selesai'];

                                        // Update tracking mapel dan jam harian untuk bItem
                                        if ($altH !== $h) {
                                            if (isset($rombelDayMapel[$rId][$h][$bMapelId])) {
                                                $rombelDayMapel[$rId][$h][$bMapelId] = max(0, $rombelDayMapel[$rId][$h][$bMapelId] - 1);
                                            }
                                            $rombelDayMapel[$rId][$altH][$bMapelId] = ($rombelDayMapel[$rId][$altH][$bMapelId] ?? 0) + 1;

                                            if ($gB) {
                                                $guruDayJp[$gB][$h] = max(0, ($guruDayJp[$gB][$h] ?? 0) - $bDur);
                                                $guruDayJp[$gB][$altH] = ($guruDayJp[$gB][$altH] ?? 0) + $bDur;
                                            }
                                        }

                                        $altPlaced = true;
                                        break;
                                    }
                                }
                            }
                            if ($altPlaced) break;
                        }

                        if ($altPlaced) {
                            // Tempatkan sesi unallocated pada (h, k) yang sudah terkunci
                            $rombelDayMapel[$rId][$h][$sess['mata_pelajaran_id']] = ($rombelDayMapel[$rId][$h][$sess['mata_pelajaran_id']] ?? 0) + 1;
                            $rombelPureKbmJp[$rId] = ($rombelPureKbmJp[$rId] ?? 0) + 1;
                            if ($gId) $guruDayJp[$gId][$h] = ($guruDayJp[$gId][$h] ?? 0) + 1;

                            $batchInserts[] = $this->buildScheduleItem($sess, $rId, $h, $k, $k, $slots, $rombelMap, 'Auto-Generated (Swap Resolved)');

                            $placed = true;
                            $anySwapped = true;
                            break;
                        } else {
                            // Coba Slice Swap jika bDur >= 2 dan k berada di ujung (bStart atau bEnd)
                            $slicePlaced = false;
                            if ($bDur >= 2 && ($k === $bStart || $k === $bEnd)) {
                                foreach ($hariList as $altH) {
                                    $altDayMax = isset($rombelDailySlotCounts[$rId][$altH]) ? $rombelDailySlotCounts[$rId][$altH] : ($rombelDailySlotCounts[$altH] ?? 12);
                                    for ($altK = 1; $altK <= $altDayMax; $altK++) {
                                        if ($istirahatJamKe && $altK === $istirahatJamKe) continue;
                                        if ($altK === 1 && $altH === 'Senin' && !empty($pengaturan->upacara['aktif'])) continue;
                                        if ($altK === 1 && $altH === 'Jumat' && !empty($pengaturan->pembiasaan['aktif'])) continue;

                                        // Slot alternatif tidak boleh berada di dalam span bItem itu sendiri
                                        if ($altH === $h && $altK >= $bStart && $altK <= $bEnd) continue;

                                        // Rombel rId dan guru gB harus bebas pada (altH, altK)
                                        if (!empty($rombelOccupied[$rId][$altH][$altK])) continue;
                                        if ($gB && !empty($guruOccupied[$gB][$altH][$altK])) continue;

                                        // Ditemukan slot 1 JP untuk slice bItem!
                                        // 1. Potong bItem sebanyak 1 JP
                                        if ($k === $bEnd) {
                                            $batchInserts[$foundItemIdx]['jam_ke_selesai'] = $bEnd - 1;
                                            $batchInserts[$foundItemIdx]['jam_selesai'] = $slots[$bEnd - 1]['selesai'] ? (strlen($slots[$bEnd - 1]['selesai']) === 5 ? $slots[$bEnd - 1]['selesai'] . ':00' : $slots[$bEnd - 1]['selesai']) : $batchInserts[$foundItemIdx]['jam_selesai'];
                                            for ($x = $bStart; $x <= $bEnd - 1; $x++) {
                                                $rombelOccupied[$rId][$h][$x] = true;
                                                if ($gB) $guruOccupied[$gB][$h][$x] = true;
                                            }
                                        } else { // $k === $bStart
                                            $batchInserts[$foundItemIdx]['jam_ke_mulai'] = $bStart + 1;
                                            $batchInserts[$foundItemIdx]['jam_mulai'] = $slots[$bStart + 1]['mulai'] ? (strlen($slots[$bStart + 1]['mulai']) === 5 ? $slots[$bStart + 1]['mulai'] . ':00' : $slots[$bStart + 1]['mulai']) : $batchInserts[$foundItemIdx]['jam_mulai'];
                                            for ($x = $bStart + 1; $x <= $bEnd; $x++) {
                                                $rombelOccupied[$rId][$h][$x] = true;
                                                if ($gB) $guruOccupied[$gB][$h][$x] = true;
                                            }
                                        }

                                        // 2. Buat item slice 1 JP untuk bItem pada (altH, altK)
                                        $sliceItem = $bItem;
                                        $sliceItem['hari'] = $altH;
                                        $sliceItem['jam_ke_mulai'] = $altK;
                                        $sliceItem['jam_ke_selesai'] = $altK;
                                        $sliceItem['jam_mulai'] = $slots[$altK]['mulai'] ? (strlen($slots[$altK]['mulai']) === 5 ? $slots[$altK]['mulai'] . ':00' : $slots[$altK]['mulai']) : '07:00:00';
                                        $sliceItem['jam_selesai'] = $slots[$altK]['selesai'] ? (strlen($slots[$altK]['selesai']) === 5 ? $slots[$altK]['selesai'] . ':00' : $slots[$altK]['selesai']) : '07:45:00';
                                        $sliceItem['keterangan'] = 'Auto-Generated (Swap Slice)';
                                        $batchInserts[] = $sliceItem;

                                        $rombelOccupied[$rId][$altH][$altK] = true;
                                        if ($gB) $guruOccupied[$gB][$altH][$altK] = true;

                                        if ($altH !== $h) {
                                            $rombelDayMapel[$rId][$altH][$bMapelId] = ($rombelDayMapel[$rId][$altH][$bMapelId] ?? 0) + 1;
                                            if ($gB) {
                                                $guruDayJp[$gB][$h] = max(0, ($guruDayJp[$gB][$h] ?? 0) - 1);
                                                $guruDayJp[$gB][$altH] = ($guruDayJp[$gB][$altH] ?? 0) + 1;
                                            }
                                        }

                                        // 3. Tempatkan sess pada slot (h, k) yang sudah terkunci
                                        $rombelDayMapel[$rId][$h][$sess['mata_pelajaran_id']] = ($rombelDayMapel[$rId][$h][$sess['mata_pelajaran_id']] ?? 0) + 1;
                                        $rombelPureKbmJp[$rId] = ($rombelPureKbmJp[$rId] ?? 0) + 1;
                                        if ($gId) $guruDayJp[$gId][$h] = ($guruDayJp[$gId][$h] ?? 0) + 1;

                                        $batchInserts[] = $this->buildScheduleItem($sess, $rId, $h, $k, $k, $slots, $rombelMap, 'Auto-Generated (Swap Slice Resolved)');

                                        $placed = true;
                                        $anySwapped = true;
                                        $slicePlaced = true;
                                        break;
                                    }
                                    if ($slicePlaced) break;
                                }
                            }

                            if (!$slicePlaced) {
                                // Revert kunci slot (h, k) dan kembalikan bItem ke slot semula
                                unset($rombelOccupied[$rId][$h][$k]);
                                if ($gId) unset($guruOccupied[$gId][$h][$k]);

                                for ($x = $bStart; $x <= $bEnd; $x++) {
                                    $rombelOccupied[$rId][$h][$x] = true;
                                    if ($gB) $guruOccupied[$gB][$h][$x] = true;
                                }
                            } else {
                                break;
                            }
                        }
                    }
                    if ($placed) break;
                }

                // Strategi B: Inter-Rombel Teacher Shift
                // Jika sess belum dapat slot, cari slot (h, k) di mana rombel $rId KOSONG,
                // tetapi guru $gId sibuk mengajar di rombel lain ($otherRId). Pindahkan sesi di $otherRId ke slot alternatif di $otherRId.
                if (!$placed && $gId) {
                    foreach ($hariList as $h) {
                        $dayMax = isset($rombelDailySlotCounts[$rId][$h]) ? $rombelDailySlotCounts[$rId][$h] : ($rombelDailySlotCounts[$h] ?? 12);
                        for ($k = 1; $k <= $dayMax; $k++) {
                            if ($istirahatJamKe && $k === $istirahatJamKe) continue;
                            if ($k === 1 && $h === 'Senin' && !empty($pengaturan->upacara['aktif'])) continue;
                            if ($k === 1 && $h === 'Jumat' && !empty($pengaturan->pembiasaan['aktif'])) continue;

                            // Rombel $rId HARUS KOSONG pada (h, k)
                            if (!empty($rombelOccupied[$rId][$h][$k])) continue;

                            // Guru $gId harus sedang mengajar pada (h, k)
                            if (empty($guruOccupied[$gId][$h][$k])) continue;

                            // Cari item milik guru $gId pada (h, k) di $batchInserts
                            $otherItemIdx = null;
                            foreach ($batchInserts as $idx => $itemCheck) {
                                if ($itemCheck['ptk_id'] === $gId && $itemCheck['hari'] === $h) {
                                    if ($k >= (int)$itemCheck['jam_ke_mulai'] && $k <= (int)$itemCheck['jam_ke_selesai']) {
                                        $otherItemIdx = $idx;
                                        break;
                                    }
                                }
                            }

                            if ($otherItemIdx === null) continue;

                            $otherItem = $batchInserts[$otherItemIdx];
                            $otherRId = $otherItem['rombongan_belajar_id'];
                            if ($otherRId === $rId) continue;

                            $oStart = (int) $otherItem['jam_ke_mulai'];
                            $oEnd = (int) $otherItem['jam_ke_selesai'];
                            $oDur = $oEnd - $oStart + 1;
                            $oMapelId = $otherItem['mata_pelajaran_id'];

                            if ($oDur > 6 || in_array($oMapelId, ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'], true)) continue;

                            // 1. Evikasi sementara otherItem
                            for ($x = $oStart; $x <= $oEnd; $x++) {
                                unset($rombelOccupied[$otherRId][$h][$x]);
                                unset($guruOccupied[$gId][$h][$x]);
                            }

                            // Kunci slot (h, k) untuk rombel $rId dan guru $gId
                            $rombelOccupied[$rId][$h][$k] = true;
                            $guruOccupied[$gId][$h][$k] = true;

                            // 2. Cari slot alternatif untuk otherItem di otherRId
                            $altPlaced = false;
                            foreach ($hariList as $altH) {
                                $altCands = $this->findCandidateSlots($otherRId, $altH, $oDur, $rombelOccupied, $rombelDailySlotCounts, $istirahatJamKe, false);
                                foreach ($altCands as $altCand) {
                                    if (!$altCand['split']) {
                                        $as = $altCand['start'];
                                        $ae = $altCand['end'];
                                        // Slot alternatif tidak boleh menimpa posisi otherItem itu sendiri
                                        if ($altH === $h && ($as <= $oEnd && $ae >= $oStart)) continue;

                                        $free = true;
                                        for ($x = $as; $x <= $ae; $x++) {
                                            if (!empty($guruOccupied[$gId][$altH][$x])) { $free = false; break; }
                                        }
                                        if ($free) {
                                            for ($x = $as; $x <= $ae; $x++) {
                                                $rombelOccupied[$otherRId][$altH][$x] = true;
                                                $guruOccupied[$gId][$altH][$x] = true;
                                            }

                                            $batchInserts[$otherItemIdx]['hari'] = $altH;
                                            $batchInserts[$otherItemIdx]['jam_ke_mulai'] = $as;
                                            $batchInserts[$otherItemIdx]['jam_ke_selesai'] = $ae;
                                            $batchInserts[$otherItemIdx]['jam_mulai'] = $slots[$as]['mulai'] ? (strlen($slots[$as]['mulai']) === 5 ? $slots[$as]['mulai'] . ':00' : $slots[$as]['mulai']) : $batchInserts[$otherItemIdx]['jam_mulai'];
                                            $batchInserts[$otherItemIdx]['jam_selesai'] = $slots[$ae]['selesai'] ? (strlen($slots[$ae]['selesai']) === 5 ? $slots[$ae]['selesai'] . ':00' : $slots[$ae]['selesai']) : $batchInserts[$otherItemIdx]['jam_selesai'];

                                            if ($altH !== $h) {
                                                if (isset($rombelDayMapel[$otherRId][$h][$oMapelId])) {
                                                    $rombelDayMapel[$otherRId][$h][$oMapelId] = max(0, $rombelDayMapel[$otherRId][$h][$oMapelId] - 1);
                                                }
                                                $rombelDayMapel[$otherRId][$altH][$oMapelId] = ($rombelDayMapel[$otherRId][$altH][$oMapelId] ?? 0) + 1;

                                                $guruDayJp[$gId][$h] = max(0, ($guruDayJp[$gId][$h] ?? 0) - $oDur);
                                                $guruDayJp[$gId][$altH] = ($guruDayJp[$gId][$altH] ?? 0) + $oDur;
                                            }

                                            $altPlaced = true;
                                            break;
                                        }
                                    }
                                }
                                if ($altPlaced) break;
                            }

                            if ($altPlaced) {
                                $rombelDayMapel[$rId][$h][$sess['mata_pelajaran_id']] = ($rombelDayMapel[$rId][$h][$sess['mata_pelajaran_id']] ?? 0) + 1;
                                $rombelPureKbmJp[$rId] = ($rombelPureKbmJp[$rId] ?? 0) + 1;
                                $guruDayJp[$gId][$h] = ($guruDayJp[$gId][$h] ?? 0) + 1;

                                $batchInserts[] = $this->buildScheduleItem($sess, $rId, $h, $k, $k, $slots, $rombelMap, 'Auto-Generated (Teacher Shift Resolved)');

                                $placed = true;
                                $anySwapped = true;
                                break;
                            } else {
                                unset($rombelOccupied[$rId][$h][$k]);
                                unset($guruOccupied[$gId][$h][$k]);

                                for ($x = $oStart; $x <= $oEnd; $x++) {
                                    $rombelOccupied[$otherRId][$h][$x] = true;
                                    $guruOccupied[$gId][$h][$x] = true;
                                }
                            }
                        }
                        if ($placed) break;
                    }
                }

                if (!$placed) {
                    $unallocatedAfterSwap[] = $sess;
                }
            }
            $unallocatedSessions = $unallocatedAfterSwap;

            if (!$anySwapped || empty($unallocatedSessions)) {
                break;
            }
        }
    }

    /**
     * Resolusi sesi unallocated via Intra-Rombel Relocation (Menukar sesi internal dalam rombel yang sama)
     */
    private function resolveUnallocatedByIntraRombelSwap(
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
        $rombelMap,
        array $hariList,
        ?object $pengaturan
    ): void {
        for ($round = 0; $round < 5; $round++) {
            $stillUnallocated = [];
            $anySwapped = false;

            foreach ($unallocatedSessions as $sess) {
                $rId = $sess['rombongan_belajar_id'];
                $gId = $sess['ptk_id'];
                $placed = false;

                // 1. Cari semua slot kosong di rombel $rId
                $emptySlots = [];
                foreach ($hariList as $h) {
                    $dayMax = isset($rombelDailySlotCounts[$rId][$h]) ? $rombelDailySlotCounts[$rId][$h] : 12;
                    for ($k = 1; $k <= $dayMax; $k++) {
                        if ($istirahatJamKe && $k === $istirahatJamKe && $h !== 'Jumat') continue;
                        if ($k === 1 && $h === 'Senin' && !empty($pengaturan->upacara['aktif'])) continue;
                        if ($k === 1 && $h === 'Jumat' && !empty($pengaturan->pembiasaan['aktif'])) continue;
                        if (empty($rombelOccupied[$rId][$h][$k])) {
                            $emptySlots[] = ['hari' => $h, 'jam_ke' => $k];
                        }
                    }
                }

                // Coba tempatkan langsung jika guru $gId bebas pada salah satu slot kosong
                foreach ($emptySlots as $es) {
                    $eh = $es['hari'];
                    $ek = $es['jam_ke'];
                    if (!$gId || empty($guruOccupied[$gId][$eh][$ek])) {
                        $rombelOccupied[$rId][$eh][$ek] = true;
                        if ($gId) $guruOccupied[$gId][$eh][$ek] = true;
                        $batchInserts[] = $this->buildScheduleItem($sess, $rId, $eh, $ek, $ek, $slots, $rombelMap, 'Auto-Generated (Direct Empty Fill)');
                        $rombelPureKbmJp[$rId] = ($rombelPureKbmJp[$rId] ?? 0) + 1;
                        if ($gId) $guruDayJp[$gId][$eh] = ($guruDayJp[$gId][$eh] ?? 0) + 1;
                        $placed = true;
                        $anySwapped = true;
                        break;
                    }
                }

                if ($placed) continue;

                // 2. Intra-Rombel Swap: Pindahkan item lain di rombel $rId yang gurunya bebas di slot kosong ($eh, $ek)
                foreach ($emptySlots as $es) {
                    $eh = $es['hari'];
                    $ek = $es['jam_ke'];

                    foreach ($batchInserts as $idx => $bItem) {
                        if ($bItem['rombongan_belajar_id'] !== $rId) continue;
                        if (in_array($bItem['mata_pelajaran_id'], ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'], true)) continue;

                        $bH = $bItem['hari'];
                        $bStart = (int) $bItem['jam_ke_mulai'];
                        $bEnd = (int) $bItem['jam_ke_selesai'];
                        $bDur = $bEnd - $bStart + 1;
                        $gB = $bItem['ptk_id'];

                        // Cek apakah guru gB bebas di ($eh, $ek)
                        if ($gB && !empty($guruOccupied[$gB][$eh][$ek])) continue;

                        // Jika bDur === 1
                        if ($bDur === 1) {
                            if (!$gId || empty($guruOccupied[$gId][$bH][$bStart])) {
                                unset($rombelOccupied[$rId][$bH][$bStart]);
                                if ($gB) unset($guruOccupied[$gB][$bH][$bStart]);

                                $rombelOccupied[$rId][$eh][$ek] = true;
                                if ($gB) $guruOccupied[$gB][$eh][$ek] = true;

                                $batchInserts[$idx]['hari'] = $eh;
                                $batchInserts[$idx]['jam_ke_mulai'] = $ek;
                                $batchInserts[$idx]['jam_ke_selesai'] = $ek;
                                $batchInserts[$idx]['jam_mulai'] = $slots[$ek]['mulai'] ? (strlen($slots[$ek]['mulai']) === 5 ? $slots[$ek]['mulai'] . ':00' : $slots[$ek]['mulai']) : $batchInserts[$idx]['jam_mulai'];
                                $batchInserts[$idx]['jam_selesai'] = $slots[$ek]['selesai'] ? (strlen($slots[$ek]['selesai']) === 5 ? $slots[$ek]['selesai'] . ':00' : $slots[$ek]['selesai']) : $batchInserts[$idx]['jam_selesai'];

                                $rombelOccupied[$rId][$bH][$bStart] = true;
                                if ($gId) $guruOccupied[$gId][$bH][$bStart] = true;

                                $batchInserts[] = $this->buildScheduleItem($sess, $rId, $bH, $bStart, $bStart, $slots, $rombelMap, 'Auto-Generated (Intra-Rombel Swap Resolved)');
                                $rombelPureKbmJp[$rId] = ($rombelPureKbmJp[$rId] ?? 0) + 1;
                                if ($gId) $guruDayJp[$gId][$bH] = ($guruDayJp[$gId][$bH] ?? 0) + 1;
                                $placed = true;
                                $anySwapped = true;
                                break 2;
                            }
                        } elseif ($bDur >= 2) {
                            // Potong 1 JP dari ujung bItem (bStart atau bEnd)
                            foreach ([$bStart, $bEnd] as $targetK) {
                                if (!$gId || empty($guruOccupied[$gId][$bH][$targetK])) {
                                    unset($rombelOccupied[$rId][$bH][$targetK]);
                                    if ($gB) unset($guruOccupied[$gB][$bH][$targetK]);

                                    if ($targetK === $bStart) {
                                        $batchInserts[$idx]['jam_ke_mulai'] = $bStart + 1;
                                        $batchInserts[$idx]['jam_mulai'] = $slots[$bStart + 1]['mulai'] ? (strlen($slots[$bStart + 1]['mulai']) === 5 ? $slots[$bStart + 1]['mulai'] . ':00' : $slots[$bStart + 1]['mulai']) : $batchInserts[$idx]['jam_mulai'];
                                    } else {
                                        $batchInserts[$idx]['jam_ke_selesai'] = $bEnd - 1;
                                        $batchInserts[$idx]['jam_selesai'] = $slots[$bEnd - 1]['selesai'] ? (strlen($slots[$bEnd - 1]['selesai']) === 5 ? $slots[$bEnd - 1]['selesai'] . ':00' : $slots[$bEnd - 1]['selesai']) : $batchInserts[$idx]['jam_selesai'];
                                    }

                                    $rombelOccupied[$rId][$eh][$ek] = true;
                                    if ($gB) $guruOccupied[$gB][$eh][$ek] = true;

                                    $sliceItem = $bItem;
                                    $sliceItem['hari'] = $eh;
                                    $sliceItem['jam_ke_mulai'] = $ek;
                                    $sliceItem['jam_ke_selesai'] = $ek;
                                    $sliceItem['jam_mulai'] = $slots[$ek]['mulai'] ? (strlen($slots[$ek]['mulai']) === 5 ? $slots[$ek]['mulai'] . ':00' : $slots[$ek]['mulai']) : '07:15:00';
                                    $sliceItem['jam_selesai'] = $slots[$ek]['selesai'] ? (strlen($slots[$ek]['selesai']) === 5 ? $slots[$ek]['selesai'] . ':00' : $slots[$ek]['selesai']) : '08:00:00';
                                    $sliceItem['keterangan'] = ($bItem['keterangan'] ?? 'Auto-Generated') . ' (Slice Relocated)';
                                    $batchInserts[] = $sliceItem;

                                    $rombelOccupied[$rId][$bH][$targetK] = true;
                                    if ($gId) $guruOccupied[$gId][$bH][$targetK] = true;

                                    $batchInserts[] = $this->buildScheduleItem($sess, $rId, $bH, $targetK, $targetK, $slots, $rombelMap, 'Auto-Generated (Slice Swap Resolved)');
                                    $rombelPureKbmJp[$rId] = ($rombelPureKbmJp[$rId] ?? 0) + 1;
                                    if ($gId) $guruDayJp[$gId][$bH] = ($guruDayJp[$gId][$bH] ?? 0) + 1;
                                    $placed = true;
                                    $anySwapped = true;
                                    break 3;
                                }
                            }
                        }
                    }
                }

                if (!$placed) {
                    $stillUnallocated[] = $sess;
                }
            }

            $unallocatedSessions = $stillUnallocated;
            if (!$anySwapped || empty($unallocatedSessions)) {
                break;
            }
        }
    }

    /**
     * Format struktur baris jadwal KBM untuk batch insert
     */
    private function buildScheduleItem(
        array $sess,
        string $rId,
        string $h,
        int $startK,
        int $endK,
        array $slots,
        $rombelMap,
        string $keterangan
    ): array {
        $jamMulai = $slots[$startK]['mulai'] ?? '06:30';
        $jamSelesai = $slots[$endK]['selesai'] ?? '14:45';
        $semesterId = $rombelMap[$rId]->semester_id ?? null;

        return [
            'rombongan_belajar_id' => $rId,
            'pembelajaran_id'      => $sess['pembelajaran_id'],
            'ptk_id'               => $sess['ptk_id'],
            'mata_pelajaran_id'    => $sess['mata_pelajaran_id'],
            'nama_mata_pelajaran'  => $sess['nama_mata_pelajaran'],
            'hari'                 => $h,
            'jam_ke_mulai'         => $startK,
            'jam_ke_selesai'       => $endK,
            'jam_mulai'            => strlen($jamMulai) === 5 ? "{$jamMulai}:00" : $jamMulai,
            'jam_selesai'          => strlen($jamSelesai) === 5 ? "{$jamSelesai}:00" : $jamSelesai,
            'ruangan'              => null,
            'semester_id'          => $semesterId,
            'sumber'               => 'otomatis',
            'is_active'            => true,
            'keterangan'           => $keterangan,
            'created_at'           => now(),
            'updated_at'           => now(),
        ];
    }

    /**
     * Periksa ketersediaan jam berhalangan spesifik guru
     */
    private function isTeacherUnavailable(?object $gPref, string $h, int $startK, int $endK): bool
    {
        if (!$gPref || empty($gPref->jam_unavailable)) {
            return false;
        }

        foreach ($gPref->jam_unavailable as $un) {
            if (($un['hari'] ?? '') === $h) {
                $unS = (int) ($un['jam_ke_mulai'] ?? 1);
                $unE = (int) ($un['jam_ke_selesai'] ?? $unS);
                if ($startK <= $unE && $endK >= $unS) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Memecah total jam mengajar mingguan (JJM) menjadi sesi blok pertemuan yang teratur dan fleksibel (hingga 9 JP)
     */
    private function decomposeJjmIntoBlocks(int $jjm, int $maxBlock = 3, ?int $istirahatJamKe = null): array
    {
        if ($jjm <= 0) return [];
        $maxBlock = max(2, min(9, $maxBlock));

        // Penanganan dekomposisi seimbang untuk JJM SMK agar proporsional di seluruh hari
        if ($jjm === 18) {
            if ($maxBlock >= 6) return [6, 6, 6];
            return [3, 3, 3, 3, 3, 3];
        }
        if ($jjm === 12) {
            if ($maxBlock >= 6) return [6, 6];
            if ($maxBlock === 2) return [2, 2, 2, 2, 2, 2];
            return [3, 3, 3, 3];
        }
        if ($jjm === 10) {
            if ($maxBlock >= 5) return [5, 5];
            if ($maxBlock === 2) return [2, 2, 2, 2, 2];
            return [3, 3, 2, 2];
        }
        if ($jjm === 9) {
            if ($maxBlock >= 5) return [5, 4];
            if ($maxBlock >= 3) return [3, 3, 3];
            return [3, 2, 2, 2];
        }
        if ($jjm === 8) {
            if ($maxBlock >= 4) return [4, 4];
            if ($maxBlock === 3) return [3, 3, 2];
            return [2, 2, 2, 2];
        }
        if ($jjm === 7) {
            if ($maxBlock >= 4) return [4, 3];
            return [3, 2, 2];
        }
        if ($jjm === 6) {
            if ($maxBlock >= 3) return [3, 3];
            return [2, 2, 2];
        }
        if ($jjm === 5) {
            return [3, 2];
        }
        if ($jjm === 4) {
            return [2, 2];
        }
        if ($jjm <= 3) {
            return [$jjm];
        }

        $blocks = [];
        $remaining = $jjm;
        while ($remaining > 0) {
            $take = min($maxBlock, $remaining);
            if ($take === 1 && count($blocks) > 0) {
                $blocks[count($blocks) - 1] += 1;
                break;
            }
            if (($remaining - $take) === 1 && $take > 2) {
                $take -= 1;
            }
            $blocks[] = $take;
            $remaining -= $take;
        }

        return $blocks;
    }
    /**
     * Gabungkan sesi berdekatan dari rombel, hari, mapel, dan guru yang sama menjadi satu blok solid
     */
    private function mergeAdjacentSessions(array &$batchInserts, array $slots): void
    {
        usort($batchInserts, function ($a, $b) {
            if ($a["rombongan_belajar_id"] !== $b["rombongan_belajar_id"]) return strcmp($a["rombongan_belajar_id"], $b["rombongan_belajar_id"]);
            if ($a["hari"] !== $b["hari"]) return strcmp($a["hari"], $b["hari"]);
            return (int)$a["jam_ke_mulai"] <=> (int)$b["jam_ke_mulai"];
        });

        $merged = [];
        foreach ($batchInserts as $item) {
            $rId = $item["rombongan_belajar_id"];
            $h = $item["hari"];
            $mId = $item["mata_pelajaran_id"];
            $gId = $item["ptk_id"];
            $isRoutine = in_array($mId, ["UPACARA", "PEMBIASAAN", "ISTIRAHAT"], true);

            $lastIdx = count($merged) - 1;
            if (!$isRoutine && $lastIdx >= 0) {
                $prev = &$merged[$lastIdx];
                if ($prev["rombongan_belajar_id"] === $rId && $prev["hari"] === $h && $prev["mata_pelajaran_id"] === $mId && $prev["ptk_id"] === $gId) {
                    $pEnd = (int) $prev["jam_ke_selesai"];
                    $cStart = (int) $item["jam_ke_mulai"];
                    $isNext = ($cStart === $pEnd + 1);
                    if ($isNext) {
                        $cEnd = (int) $item["jam_ke_selesai"];
                        $prev["jam_ke_selesai"] = $cEnd;
                        $prev["jam_selesai"] = $slots[$cEnd]["selesai"] ? (strlen($slots[$cEnd]["selesai"]) === 5 ? $slots[$cEnd]["selesai"] . ":00" : $slots[$cEnd]["selesai"]) : $item["jam_selesai"];
                        continue;
                    }
                }
            }
            $merged[] = $item;
        }
        $batchInserts = $merged;
    }

    /**
     * Susun ulang seluruh sesi menjadi blok utuh (minimal 2 JP, satu mapel maksimal satu blok per hari,
     * tidak melewati jam istirahat). Hasil hanya dipakai jika benar-benar bebas bentrok guru.
     */
    private function blockifyLayout(
        array &$batchInserts,
        array $slots,
        array $rombelOccupied,
        array $guruOccupied,
        array $rombelDailySlotCounts,
        array $hariList,
        int $maxJpPerSession,
        ?int $istirahatJamKe,
        $guruPreferences,
        ?int $seed,
        float $budget
    ): bool {
        $templates = [];
        $jpOf = [];
        $ptkOf = [];
        foreach ($batchInserts as $row) {
            $rId = $row['rombongan_belajar_id'];
            for ($k = (int) $row['jam_ke_mulai']; $k <= (int) $row['jam_ke_selesai']; $k++) {
                unset($rombelOccupied[$rId][$row['hari']][$k]);
                if ($row['ptk_id']) unset($guruOccupied[$row['ptk_id']][$row['hari']][$k]);
            }
            $pid = $row['pembelajaran_id'];
            $templates[$pid] ??= $row;
            $ptkOf[$pid] = $row['ptk_id'];
            $jpOf[$rId][$pid] = ($jpOf[$rId][$pid] ?? 0) + ((int) $row['jam_ke_selesai'] - (int) $row['jam_ke_mulai'] + 1);
        }

        $segments = [];
        foreach (array_keys($jpOf) as $rId) {
            foreach ($hariList as $h) {
                $dayMax = $rombelDailySlotCounts[$rId][$h] ?? ($rombelDailySlotCounts[$h] ?? 12);
                $cur = null;
                for ($k = 1; $k <= $dayMax + 1; $k++) {
                    $free = $k <= $dayMax && empty($rombelOccupied[$rId][$h][$k]);
                    if ($free) {
                        $cur ??= ['day' => $h, 'start' => $k, 'size' => 0];
                        $cur['size']++;
                    } elseif ($cur) {
                        $segments[$rId][] = $cur;
                        $cur = null;
                    }
                }
            }
        }

        $blocks = [];
        $allowOf = [];
        $capOf = [];
        foreach ($jpOf as $rId => $pids) {
            $maxSeg = 1;
            $dayCount = [];
            foreach ($segments[$rId] ?? [] as $s) {
                $maxSeg = max($maxSeg, $s['size']);
                $dayCount[$s['day']] = true;
            }
            $cap = max(2, min($maxJpPerSession, $maxSeg, 6));
            foreach ($pids as $pid => $jp) {
                $parts = $this->decomposeJjmIntoBlocks($jp, $cap, $istirahatJamKe);
                $allowOf[$rId][$pid] = max(1, (int) ceil(count($parts) / max(1, count($dayCount))));
                $capOf[$rId][$pid] = max($maxJpPerSession, max($parts));
            }
        }
        foreach ($batchInserts as $row) {
            $rId = $row['rombongan_belajar_id'];
            $pid = $row['pembelajaran_id'];
            $blocks[] = [
                'rId' => $rId, 'pid' => $pid, 'ptk' => $row['ptk_id'],
                'len' => (int) $row['jam_ke_selesai'] - (int) $row['jam_ke_mulai'] + 1,
                'day' => $row['hari'], 'start' => (int) $row['jam_ke_mulai'],
                'allow' => $allowOf[$rId][$pid] ?? 1, 'cap' => $capOf[$rId][$pid] ?? $maxJpPerSession,
            ];
        }

        $prefs = [];
        foreach ($guruPreferences as $ptk => $p) {
            $un = [];
            foreach (($p->jam_unavailable ?? []) as $u) {
                $from = (int) ($u['jam_ke_mulai'] ?? 1);
                $un[] = [$u['hari'] ?? '', $from, (int) ($u['jam_ke_selesai'] ?? $from)];
            }
            $prefs[$ptk] = [
                'off' => $p->hari_off ?? [],
                'unavail' => $un,
                'max' => $p->max_jp_per_hari ?: null,
            ];
        }

        $solver = new BlockTimetableSolver();
        $result = $solver->solve($segments, $blocks, $guruOccupied, $prefs, ($seed ?? 1) + 101, $budget);
        if (!$result['ok']) return false;

        $fmt = fn($t) => strlen((string) $t) === 5 ? $t . ':00' : $t;
        $rebuilt = [];
        foreach ($result['items'] as $it) {
            $start = $it['start'];
            $end = $start + $it['len'] - 1;
            $row = $templates[$it['pid']];
            $row['rombongan_belajar_id'] = $it['rId'];
            $row['hari'] = $it['day'];
            $row['jam_ke_mulai'] = $start;
            $row['jam_ke_selesai'] = $end;
            $row['jam_mulai'] = $fmt($slots[$start]['mulai'] ?? $row['jam_mulai']);
            $row['jam_selesai'] = $fmt($slots[$end]['selesai'] ?? $row['jam_selesai']);
            $row['keterangan'] = 'Auto-Generated';
            $rebuilt[] = $row;
        }
        $batchInserts = $rebuilt;

        return true;
    }

    /**
     * Susun ulang slot per rombel (tukar antar slot, bebas bentrok guru) agar tidak ada sesi 1 JP
     * dan satu mapel tidak terpecah di hari yang sama. Jam istirahat tidak pernah disentuh.
     *
     * @return array{single_jp:int, split:int}
     */
    private function optimizeSlotLayout(
        array &$batchInserts,
        array $slots,
        array $rombelOccupied,
        array $guruOccupied,
        array $rombelDailySlotCounts,
        array $hariList,
        ?int $istirahatJamKe,
        $guruPreferences,
        ?int $seed,
        float $seconds = 6.0
    ): array {
        $cell = [];
        $templates = [];
        $teacherOcc = [];
        $teacherDay = [];
        $rombelIds = [];

        foreach ($batchInserts as $row) {
            for ($k = (int) $row['jam_ke_mulai']; $k <= (int) $row['jam_ke_selesai']; $k++) {
                unset($rombelOccupied[$row['rombongan_belajar_id']][$row['hari']][$k]);
                if ($row['ptk_id']) unset($guruOccupied[$row['ptk_id']][$row['hari']][$k]);
            }
        }

        $validK = [];
        foreach ($batchInserts as $row) {
            $rId = $row['rombongan_belajar_id'];
            $rombelIds[$rId] = true;
            $templates[$row['pembelajaran_id']] ??= $row;
        }
        $rombelIds = array_keys($rombelIds);

        foreach ($rombelIds as $rId) {
            foreach ($hariList as $h) {
                $dayMax = $rombelDailySlotCounts[$rId][$h] ?? ($rombelDailySlotCounts[$h] ?? 12);
                for ($k = 1; $k <= $dayMax; $k++) {
                    if (empty($rombelOccupied[$rId][$h][$k])) $validK[$rId][$h][] = $k;
                }
            }
        }

        foreach ($batchInserts as $row) {
            $rId = $row['rombongan_belajar_id'];
            $h = $row['hari'];
            $ptk = $row['ptk_id'];
            for ($k = (int) $row['jam_ke_mulai']; $k <= (int) $row['jam_ke_selesai']; $k++) {
                if (!in_array($k, $validK[$rId][$h] ?? [], true)) continue;
                $cell[$rId][$h][$k] = ['pid' => $row['pembelajaran_id'], 'ptk' => $ptk];
                if ($ptk) {
                    $teacherOcc[$ptk][$h][$k] = $rId;
                    $teacherDay[$ptk][$h] = ($teacherDay[$ptk][$h] ?? 0) + 1;
                }
            }
        }

        $breakAt = $istirahatJamKe;
        $buildRuns = function (string $rId, string $h) use (&$cell, &$validK, $breakAt): array {
            $runs = [];
            $cur = null;
            foreach ($validK[$rId][$h] ?? [] as $k) {
                $c = $cell[$rId][$h][$k] ?? null;
                if ($c === null) {
                    if ($cur) { $runs[] = $cur; $cur = null; }
                    continue;
                }
                if ($cur && $cur['pid'] === $c['pid'] && $k === $cur['end'] + 1) {
                    $cur['end'] = $k;
                    $cur['len']++;
                } else {
                    if ($cur) $runs[] = $cur;
                    $cur = ['pid' => $c['pid'], 'start' => $k, 'end' => $k, 'len' => 1, 'bridge' => false];
                }
            }
            if ($cur) $runs[] = $cur;

            $merged = [];
            foreach ($runs as $run) {
                $li = count($merged) - 1;
                if ($breakAt && $li >= 0 && $merged[$li]['pid'] === $run['pid']
                    && $merged[$li]['end'] === $breakAt - 1 && $run['start'] === $breakAt + 1) {
                    $merged[$li]['end'] = $run['end'];
                    $merged[$li]['len'] += $run['len'];
                    $merged[$li]['bridge'] = true;
                    continue;
                }
                $merged[] = $run;
            }
            return $merged;
        };
        $dayCost = function (string $rId, string $h) use ($buildRuns): int {
            $cost = 0;
            $seen = [];
            foreach ($buildRuns($rId, $h) as $run) {
                if ($run['len'] === 1) $cost += 4;
                if ($run['bridge']) $cost += 1;
                if (isset($seen[$run['pid']])) $cost += 4;
                $seen[$run['pid']] = true;
            }
            return $cost;
        };

        $costCache = [];
        $total = 0;
        foreach ($rombelIds as $rId) {
            foreach ($hariList as $h) {
                $costCache[$rId][$h] = $dayCost($rId, $h);
                $total += $costCache[$rId][$h];
            }
        }

        $canTeach = function (?string $ptk, string $h, int $k) use (&$teacherOcc, &$teacherDay, $guruOccupied, $guruPreferences): bool {
            if (!$ptk) return true;
            if (!empty($guruOccupied[$ptk][$h][$k]) || isset($teacherOcc[$ptk][$h][$k])) return false;
            $pref = $guruPreferences[$ptk] ?? null;
            if ($pref) {
                if (!empty($pref->hari_off) && in_array($h, $pref->hari_off, true)) return false;
                if ($this->isTeacherUnavailable($pref, $h, $k, $k)) return false;
                if ($pref->max_jp_per_hari && (($teacherDay[$ptk][$h] ?? 0) + 1 > $pref->max_jp_per_hari)) return false;
            }
            return true;
        };
        $tPlace = function (?string $ptk, string $rId, string $h, int $k) use (&$teacherOcc, &$teacherDay): void {
            if (!$ptk) return;
            $teacherOcc[$ptk][$h][$k] = $rId;
            $teacherDay[$ptk][$h] = ($teacherDay[$ptk][$h] ?? 0) + 1;
        };
        $tRemove = function (?string $ptk, string $h, int $k) use (&$teacherOcc, &$teacherDay): void {
            if (!$ptk) return;
            unset($teacherOcc[$ptk][$h][$k]);
            $teacherDay[$ptk][$h] = max(0, ($teacherDay[$ptk][$h] ?? 0) - 1);
        };

        mt_srand(($seed ?? 1) + 7);
        $deadline = microtime(true) + $seconds;
        $temp = 1.5;
        $badList = [];
        $iter = 0;

        while ($total > 0) {
            if ($iter % 400 === 0) {
                if (microtime(true) > $deadline) break;
                $temp = max(0.2, $temp * 0.985);
                $badList = [];
                foreach ($costCache as $rId => $days) {
                    foreach ($days as $h => $c) {
                        if ($c > 0) $badList[] = [$rId, $h];
                    }
                }
            }
            $iter++;

            if ($badList && mt_rand(1, 100) <= 80) {
                [$rId, $h] = $badList[array_rand($badList)];
            } else {
                $rId = $rombelIds[array_rand($rombelIds)];
                $h = $hariList[array_rand($hariList)];
            }
            $daySlots = $validK[$rId][$h] ?? [];
            if (!$daySlots) continue;

            $targeted = false;
            if (mt_rand(1, 100) <= 75) {
                $bad = [];
                $seenPid = [];
                foreach ($buildRuns($rId, $h) as $run) {
                    if ($run['len'] === 1 || isset($seenPid[$run['pid']])) $bad[] = $run;
                    $seenPid[$run['pid']] = true;
                }
                if ($bad) {
                    $run = $bad[array_rand($bad)];
                    $near = [];
                    foreach ([$run['start'] - 1, $run['end'] + 1] as $nk) {
                        if (in_array($nk, $daySlots, true)) $near[] = $nk;
                    }
                    $pieces = [];
                    foreach ($hariList as $hh) {
                        foreach ($validK[$rId][$hh] ?? [] as $kk) {
                            $c = $cell[$rId][$hh][$kk] ?? null;
                            if ($c && $c['pid'] === $run['pid'] && !($hh === $h && $kk >= $run['start'] && $kk <= $run['end'])) {
                                $pieces[] = [$hh, $kk];
                            }
                        }
                    }
                    if ($near && $pieces) {
                        $a = $near[array_rand($near)];
                        [$h2, $b] = $pieces[array_rand($pieces)];
                        $targeted = true;
                    }
                }
            }

            if (!$targeted) {
                $a = $daySlots[array_rand($daySlots)];
                $h2 = (mt_rand(1, 100) <= 60) ? $h : $hariList[array_rand($hariList)];
                $slots2 = $validK[$rId][$h2] ?? [];
                if (!$slots2) continue;
                $b = $slots2[array_rand($slots2)];
            }
            if ($h === $h2 && $a === $b) continue;

            $A = $cell[$rId][$h][$a] ?? null;
            $B = $cell[$rId][$h2][$b] ?? null;
            if ($A === null && $B === null) continue;
            if ($A !== null && $B !== null && $A['pid'] === $B['pid']) continue;

            if ($A) $tRemove($A['ptk'], $h, $a);
            if ($B) $tRemove($B['ptk'], $h2, $b);

            $ok = (!$A || $canTeach($A['ptk'], $h2, $b)) && (!$B || $canTeach($B['ptk'], $h, $a));
            if (!$ok) {
                if ($A) $tPlace($A['ptk'], $rId, $h, $a);
                if ($B) $tPlace($B['ptk'], $rId, $h2, $b);
                continue;
            }

            $cell[$rId][$h][$a] = $B;
            $cell[$rId][$h2][$b] = $A;
            $new1 = $dayCost($rId, $h);
            $new2 = ($h2 === $h) ? $new1 : $dayCost($rId, $h2);
            $old = $costCache[$rId][$h] + (($h2 === $h) ? 0 : $costCache[$rId][$h2]);
            $new = $new1 + (($h2 === $h) ? 0 : $new2);
            $delta = $new - $old;

            if ($delta <= 0 || (mt_rand() / mt_getrandmax()) < exp(-$delta / $temp)) {
                if ($A) $tPlace($A['ptk'], $rId, $h2, $b);
                if ($B) $tPlace($B['ptk'], $rId, $h, $a);
                $costCache[$rId][$h] = $new1;
                $costCache[$rId][$h2] = $new2;
                $total += $delta;
            } else {
                $cell[$rId][$h][$a] = $A;
                $cell[$rId][$h2][$b] = $B;
                if ($A) $tPlace($A['ptk'], $rId, $h, $a);
                if ($B) $tPlace($B['ptk'], $rId, $h2, $b);
            }
        }

        $fmt = fn($t) => strlen((string) $t) === 5 ? $t . ':00' : $t;
        $rebuilt = [];
        $singleJp = 0;
        $split = 0;
        foreach ($rombelIds as $rId) {
            foreach ($hariList as $h) {
                $cur = null;
                $seen = [];
                $flush = function () use (&$cur, &$rebuilt, &$singleJp, &$split, &$seen, $templates, $slots, $fmt, $rId, $h) {
                    if (!$cur) return;
                    $row = $templates[$cur['pid']];
                    $row['hari'] = $h;
                    $row['jam_ke_mulai'] = $cur['start'];
                    $row['jam_ke_selesai'] = $cur['end'];
                    $row['jam_mulai'] = $fmt($slots[$cur['start']]['mulai'] ?? $row['jam_mulai']);
                    $row['jam_selesai'] = $fmt($slots[$cur['end']]['selesai'] ?? $row['jam_selesai']);
                    $row['keterangan'] = 'Auto-Generated';
                    $rebuilt[] = $row;
                    if ($cur['start'] === $cur['end']) $singleJp++;
                    if (isset($seen[$cur['pid']])) $split++;
                    $seen[$cur['pid']] = true;
                    $cur = null;
                };
                foreach ($validK[$rId][$h] ?? [] as $k) {
                    $c = $cell[$rId][$h][$k] ?? null;
                    if ($c === null) { $flush(); continue; }
                    if ($cur && $cur['pid'] === $c['pid'] && $k === $cur['end'] + 1) {
                        $cur['end'] = $k;
                    } else {
                        $flush();
                        $cur = ['pid' => $c['pid'], 'start' => $k, 'end' => $k];
                    }
                }
                $flush();
            }
        }

        $batchInserts = $rebuilt;

        return ['single_jp' => $singleJp, 'split' => $split];
    }
}
