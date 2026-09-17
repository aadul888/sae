<?php

namespace App\Services;

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
        $clearExisting = $options['clear_existing'] ?? true;
        $targetRombelIds = $options['rombongan_belajar_ids'] ?? [];
        $tingkat = $options['tingkat'] ?? null;
        $maxJpPerSession = (int) ($options['max_jp_per_sesi'] ?? 3);

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

        // 1. Tentukan Rombel yang Ditargetkan (Hanya Kelas Reguler)
        $rombelQuery = DB::table('rombongan_belajar')
            ->where(function ($w) {
                $w->where('jenis_rombel', 1)
                  ->orWhere('jenis_rombel_str', 'Kelas')
                  ->orWhere('jenis_rombel_str', 'LIKE', '%reguler%');
            })
            ->select('rombongan_belajar_id', 'nama', 'tingkat_pendidikan_id', 'semester_id');

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

        // Pastikan kapasitas slot harian di hari kerja reguler (Senin-Kamis) mendukung hingga 12 jam pelajaran
        // jika ada kelas dengan target belajar >= 48 JP agar seluruh jam tertampung
        $effectiveDailySlotCounts = $dailySlotCounts;
        if ($maxRequiredJp >= 48) {
            foreach (['Senin', 'Selasa', 'Rabu', 'Kamis'] as $h) {
                if (in_array($h, $hariList, true)) {
                    $effectiveDailySlotCounts[$h] = max(12, $effectiveDailySlotCounts[$h] ?? 11);
                }
            }
        }

        // Siapkan definisi slot waktu hingga slot maksimal yang dibutuhkan
        $maxNeededSlot = max(array_values($effectiveDailySlotCounts));
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

        // Petakan Rombel Pilihan yang terafiliasi dengan masing-masing Rombel Reguler
        $allPilihanRombels = DB::table('rombongan_belajar')
            ->where('jenis_rombel', '!=', 1)
            ->get(['rombongan_belajar_id', 'nama']);

        $pilihanToRegMap = [];
        $allFetchRombelIds = $selectedRombelIds;

        foreach ($rombels as $r) {
            $cleanName = trim($r->nama);
            $matchingPilIds = $allPilihanRombels->filter(function ($p) use ($cleanName) {
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

        return DB::transaction(function () use (
            $clearExisting,
            $selectedRombelIds,
            $rombelMap,
            $rombelTargetJp,
            $allFetchRombelIds,
            $pilihanToRegMap,
            $hariList,
            $slots,
            $effectiveDailySlotCounts,
            $maxJpPerSession,
            $istirahatJamKe,
            $upacaraHari,
            $upacaraJamKe,
            $pembiasaanHari,
            $pembiasaanJamKe
        ) {
            // Jika Fresh Start, hapus jadwal eksisting pada rombel terpilih (pertahankan kegiatan rutin)
            if ($clearExisting) {
                DB::table('jadwal_kbm')
                    ->whereIn('rombongan_belajar_id', $selectedRombelIds)
                    ->whereNotIn('mata_pelajaran_id', ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'])
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

            // Inisialisasi: Tandai kegiatan rutin pada grid fisik agar KBM tidak menabrak slot tersebut
            foreach ($selectedRombelIds as $rId) {
                if ($upacaraHari && $upacaraJamKe) {
                    $rombelOccupied[$rId][$upacaraHari][$upacaraJamKe] = true;
                }
                if ($pembiasaanHari && $pembiasaanJamKe) {
                    $rombelOccupied[$rId][$pembiasaanHari][$pembiasaanJamKe] = true;
                }
                if ($istirahatJamKe) {
                    foreach (['Senin', 'Selasa', 'Rabu', 'Kamis'] as $h) {
                        if (in_array($h, $hariList, true)) {
                            $rombelOccupied[$rId][$h][$istirahatJamKe] = true;
                        }
                    }
                }
            }

            // Inisialisasi state dari data eksisting yang masih tersisa
            $existingSchedules = DB::table('jadwal_kbm')
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
            // Prioritas: PKL -> Guru Multi-Rombel Paling Kritis -> Blok Durasi Besar
            usort($sessionsToSchedule, function ($a, $b) {
                if ($a['is_pkl'] !== $b['is_pkl']) {
                    return $a['is_pkl'] ? -1 : 1;
                }
                if ($a['constraint_score'] !== $b['constraint_score']) {
                    return $b['constraint_score'] <=> $a['constraint_score'];
                }
                return $b['duration'] <=> $a['duration'];
            });

            $batchInserts = [];
            $unallocatedPass1 = [];

            // ==========================================
            // PASS 1: PENEMPATAN BLOK UTAMA (CONTIGUOUS PREFERRED)
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

                // Urutkan hari berdasarkan beban slot terendah
                $sortedHari = $hariList;
                usort($sortedHari, function ($h1, $h2) use ($rId, $rombelOccupied) {
                    return count($rombelOccupied[$rId][$h1] ?? []) <=> count($rombelOccupied[$rId][$h2] ?? []);
                });

                foreach ($sortedHari as $h) {
                    $gPref = ($gId && !$isPkl) ? ($guruPreferences[$gId] ?? null) : null;
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
                        $rId, $h, $dur, $rombelOccupied, $effectiveDailySlotCounts, $istirahatJamKe, false, $isPkl
                    );

                    foreach ($candidateSlots as $startK) {
                        $endK = $startK + $dur - 1;

                        if ($this->isTeacherUnavailable($gPref, $h, $startK, $endK)) {
                            continue;
                        }

                        $slotFree = true;
                        if ($gId && !$isPkl) {
                            for ($checkK = $startK; $checkK <= $endK; $checkK++) {
                                if (!empty($guruOccupied[$gId][$h][$checkK])) {
                                    $slotFree = false;
                                    break;
                                }
                            }
                        }

                        if ($slotFree) {
                            for ($occK = $startK; $occK <= $endK; $occK++) {
                                $rombelOccupied[$rId][$h][$occK] = true;
                                if ($gId && !$isPkl) {
                                    $guruOccupied[$gId][$h][$occK] = true;
                                }
                            }

                            $rombelDayMapel[$rId][$h][$mId] = ($rombelDayMapel[$rId][$h][$mId] ?? 0) + 1;
                            $rombelPureKbmJp[$rId] = ($rombelPureKbmJp[$rId] ?? 0) + $dur;
                            if ($gId && !$isPkl) {
                                $guruDayJp[$gId][$h] = ($guruDayJp[$gId][$h] ?? 0) + $dur;
                            }

                            $batchInserts[] = $this->buildScheduleItem($sess, $rId, $h, $startK, $endK, $slots, $rombelMap, 'Auto-Generated');
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
            // PASS 2: ELASTIC DECOMPOSITION & GAP FILLING
            // ==========================================
            // Memecah blok >= 3 JP yang belum dapat slot menjadi sub-blok fleksibel (2+2 atau 2+1)
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
                $mId   = $sess['mata_pelajaran_id'];
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
                    $gPref = ($gId && !$isPkl) ? ($guruPreferences[$gId] ?? null) : null;
                    if ($gPref) {
                        if (!empty($gPref->hari_off) && in_array($h, $gPref->hari_off, true)) continue;
                        if ($gPref->max_jp_per_hari && (($guruDayJp[$gId][$h] ?? 0) + $dur > $gPref->max_jp_per_hari)) continue;
                    }

                    // Izinkan celah (allow gaps)
                    $candidates = $this->findCandidateSlots(
                        $rId, $h, $dur, $rombelOccupied, $effectiveDailySlotCounts, $istirahatJamKe, true, $isPkl
                    );

                    foreach ($candidates as $startK) {
                        $endK = $startK + $dur - 1;

                        if ($this->isTeacherUnavailable($gPref, $h, $startK, $endK)) {
                            continue;
                        }

                        $slotFree = true;
                        if ($gId && !$isPkl) {
                            for ($checkK = $startK; $checkK <= $endK; $checkK++) {
                                if (!empty($guruOccupied[$gId][$h][$checkK])) {
                                    $slotFree = false;
                                    break;
                                }
                            }
                        }

                        if ($slotFree) {
                            for ($occK = $startK; $occK <= $endK; $occK++) {
                                $rombelOccupied[$rId][$h][$occK] = true;
                                if ($gId && !$isPkl) {
                                    $guruOccupied[$gId][$h][$occK] = true;
                                }
                            }

                            $rombelDayMapel[$rId][$h][$mId] = ($rombelDayMapel[$rId][$h][$mId] ?? 0) + 1;
                            $rombelPureKbmJp[$rId] = ($rombelPureKbmJp[$rId] ?? 0) + $dur;
                            if ($gId && !$isPkl) {
                                $guruDayJp[$gId][$h] = ($guruDayJp[$gId][$h] ?? 0) + $dur;
                            }

                            $batchInserts[] = $this->buildScheduleItem($sess, $rId, $h, $startK, $endK, $slots, $rombelMap, 'Auto-Generated (Elastic)');
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
            // PASS 3: EXHAUSTIVE UNIT PLACEMENT (1 JP)
            // ==========================================
            // Sesi yang masih tersisa dipecah menjadi unit 1 JP individual dan ditempatkan ke slot kosong yang tersisa
            $splittablePass3 = [];
            foreach ($unallocatedPass2 as $u) {
                for ($i = 0; $i < $u['duration']; $i++) {
                    $splittablePass3[] = array_merge($u, ['duration' => 1]);
                }
            }

            $unallocatedFinal = 0;
            foreach ($splittablePass3 as $sess) {
                $rId   = $sess['rombongan_belajar_id'];
                $gId   = $sess['ptk_id'];
                $mId   = $sess['mata_pelajaran_id'];
                $isPkl = $sess['is_pkl'];

                $targetRombelMax = $rombelTargetJp[$rId] ?? 50;
                if ((($rombelPureKbmJp[$rId] ?? 0) + 1) > $targetRombelMax) {
                    $unallocatedFinal++;
                    continue;
                }

                $placed = false;
                $sortedHari = $hariList;
                usort($sortedHari, function ($h1, $h2) use ($rId, $rombelOccupied) {
                    return count($rombelOccupied[$rId][$h1] ?? []) <=> count($rombelOccupied[$rId][$h2] ?? []);
                });

                foreach ($sortedHari as $h) {
                    $gPref = ($gId && !$isPkl) ? ($guruPreferences[$gId] ?? null) : null;
                    if ($gPref) {
                        if (!empty($gPref->hari_off) && in_array($h, $gPref->hari_off, true)) continue;
                        if ($gPref->max_jp_per_hari && (($guruDayJp[$gId][$h] ?? 0) + 1 > $gPref->max_jp_per_hari)) continue;
                    }

                    $candidates = $this->findCandidateSlots(
                        $rId, $h, 1, $rombelOccupied, $effectiveDailySlotCounts, $istirahatJamKe, true, $isPkl
                    );

                    foreach ($candidates as $startK) {
                        if ($this->isTeacherUnavailable($gPref, $h, $startK, $startK)) {
                            continue;
                        }

                        $free = true;
                        if ($gId && !$isPkl && !empty($guruOccupied[$gId][$h][$startK])) {
                            $free = false;
                        }

                        if ($free) {
                            $rombelOccupied[$rId][$h][$startK] = true;
                            if ($gId && !$isPkl) {
                                $guruOccupied[$gId][$h][$startK] = true;
                            }

                            $rombelPureKbmJp[$rId] = ($rombelPureKbmJp[$rId] ?? 0) + 1;
                            if ($gId && !$isPkl) {
                                $guruDayJp[$gId][$h] = ($guruDayJp[$gId][$h] ?? 0) + 1;
                            }

                            $batchInserts[] = $this->buildScheduleItem($sess, $rId, $h, $startK, $startK, $slots, $rombelMap, 'Auto-Generated (Gap Fill)');
                            $placed = true;
                            break;
                        }
                    }

                    if ($placed) break;
                }

                if (!$placed) {
                    $unallocatedFinal++;
                }
            }

            // ==========================================
            // PASS 4: SMART COMPACTION (HOLE ELIMINATION)
            // ==========================================
            // Padatkan jadwal ke jam-jam awal (shift left) sehingga kelas yang jam Dapodik-nya kurang
            // otomatis memiliki jam kosong yang rapi di akhir hari (pulang lebih awal) tanpa lubang di tengah.
            $this->compactRombelSchedules($batchInserts, $rombelOccupied, $guruOccupied, $effectiveDailySlotCounts, $istirahatJamKe, $slots);

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

            return [
                'success'         => true,
                'message'         => "Berhasil men-generate {$totalGenerated} jadwal KBM ({$totalJpCreated} JP) secara optimal tanpa bentrok dan jam kosong tertata rapi!",
                'total_generated' => $totalGenerated,
                'total_jp'        => $totalJpCreated,
                'total_rombel'    => count($selectedRombelIds),
                'unallocated'     => $unallocatedFinal,
            ];
        });
    }

    /**
     * Cari seluruh kandidat slot waktu yang valid untuk rombel pada hari tertentu dengan scoring kedekatan
     *
     * @return int[] Array of start positions terurut berdasarkan skor prioritas
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
        $dayMax = $dailySlotCounts[$h] ?? 12;
        $rOcc = $rombelOccupied[$rId][$h] ?? [];

        $candidates = [];

        for ($startK = 1; $startK <= ($dayMax - $dur + 1); $startK++) {
            $endK = $startK + $dur - 1;

            // Pastikan seluruh rentang slot rombel belum terisi
            $free = true;
            for ($k = $startK; $k <= $endK; $k++) {
                if (!empty($rOcc[$k])) {
                    $free = false;
                    break;
                }
            }
            if (!$free) continue;

            // Pembelajaran non-PKL tidak boleh menyeberangi jam istirahat
            if (!$isPkl && $istirahatJamKe && $startK < $istirahatJamKe && $endK >= $istirahatJamKe) {
                continue;
            }

            // Hitung skor contiguity:
            // 1. Menempel setelah pelajaran sebelumnya (atau awal jam 1 / jam 2 setelah Upacara)
            $isTouchPrev = ($startK === 1) || !empty($rOcc[$startK - 1]) || ($istirahatJamKe && $startK === ($istirahatJamKe + 1));
            // 2. Menempel sebelum pelajaran berikutnya
            $isTouchNext = ($endK === $dayMax) || !empty($rOcc[$endK + 1]);

            $score = 0;
            if ($isTouchPrev && $isTouchNext) {
                $score = 120; // Sempurna: menutup celah di antara 2 pelajaran
            } elseif ($isTouchPrev) {
                $score = 100; // Sambungan alami ke bawah
            } elseif ($isTouchNext) {
                $score = 70;  // Nempel ke pelajaran berikutnya
            } else {
                $score = 40;  // Slot alternatif di tengah
            }

            // Berikan bonus preferensi sesi pagi agar jadwal terisi padat dari pagi
            if ($istirahatJamKe && $endK < $istirahatJamKe) {
                $score += 60;
            }

            // Jika mode strict contiguous, tolak slot yang tidak menyambung
            if (!$allowGaps && $score < 70) {
                continue;
            }

            $candidates[] = [
                'start' => $startK,
                'score' => $score,
            ];
        }

        // Urutkan kandidat dari skor tertinggi ke terendah
        usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_column($candidates, 'start');
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

        // Iterasi pergeseran hingga stabil
        for ($iter = 0; $iter < 10; $iter++) {
            $anyMoved = false;

            foreach ($batchInserts as &$item) {
                $rId   = $item['rombongan_belajar_id'];
                $h     = $item['hari'];
                $gId   = $item['ptk_id'];
                $start = (int) $item['jam_ke_mulai'];
                $end   = (int) $item['jam_ke_selesai'];
                $dur   = $end - $start + 1;

                // 1. Cross-Break Compaction: Coba lompat dari sesi siang ke sesi pagi jika pagi ada slot kosong yang muat
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

                // 2. Linear Step Compaction: Geser mundur 1 slot jika slot (start - 1) kosong
                $targetStart = $start - 1;
                $targetEnd   = $end - 1;

                if ($targetStart < 1) continue;

                // Jangan menyeberang istirahat mundur
                if ($istirahatJamKe && $start > $istirahatJamKe && $targetStart <= $istirahatJamKe) {
                    continue;
                }

                // Cek apakah slot targetStart kosong untuk rombel
                if (!empty($rombelOccupied[$rId][$h][$targetStart])) {
                    continue;
                }

                // Cek apakah guru bebas di targetStart
                if ($gId && !empty($guruOccupied[$gId][$h][$targetStart])) {
                    continue;
                }

                // Geser maju 1 jam!
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
            }
            unset($item);

            if (!$anyMoved) break;
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
     * Memecah total jam mengajar mingguan (JJM) menjadi sesi blok pertemuan yang teratur dan fleksibel
     */
    private function decomposeJjmIntoBlocks(int $jjm, int $maxBlock = 3, ?int $istirahatJamKe = null): array
    {
        if ($jjm <= 0) return [];
        $maxBlock = max(2, min(9, $maxBlock));

        // Jendela kontinyu maksimal sebelum istirahat
        $maxContinuousWindow = $istirahatJamKe ? max(4, $istirahatJamKe - 1) : 7;
        $effectiveMax = min($maxBlock, $maxContinuousWindow);

        // Dekomposisi spesifik untuk keserasian jadwal
        if ($jjm === 9) return [5, 4];
        if ($jjm === 8) return [4, 4];
        if ($jjm === 7) return [4, 3];
        if ($jjm === 6) return [3, 3];
        if ($jjm === 5) return [3, 2];
        if ($jjm === 4) return [2, 2]; // 2 JP + 2 JP sangat fleksibel

        if ($jjm <= $effectiveMax) {
            return [$jjm];
        }

        $blocks = [];
        $remaining = $jjm;
        while ($remaining > 0) {
            $take = min($effectiveMax, $remaining);
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
}
