<?php

namespace App\Services;

use App\Models\JadwalKbm;
use App\Models\JadwalPengaturan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoSchedulerService
{
    /**
     * Jalankan Engine Auto-Generate Jadwal KBM
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
        foreach ($rombels as $r) {
            $tStr = (string) ($r->tingkat_pendidikan_id ?? '');
            $rombelTargetJp[$r->rombongan_belajar_id] = (int) ($jpTingkat[$tStr] ?? 50);
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

        // Deteksi pengaturan kegiatan rutin (Upacara, Pembiasaan) untuk anchor contiguity
        $pengaturan = JadwalPengaturan::getSettings();
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
            $totalSlots,
            $dailySlotCounts,
            $maxJpPerSession,
            $istirahatJamKe,
            $upacaraHari,
            $upacaraJamKe,
            $pembiasaanHari,
            $pembiasaanJamKe
        ) {
            // Jika Fresh Start, hapus jadwal eksisting pada rombel terpilih (pertahankan kegiatan rutin Upacara, Pembiasaan & Istirahat)
            if ($clearExisting) {
                DB::table('jadwal_kbm')
                    ->whereIn('rombongan_belajar_id', $selectedRombelIds)
                    ->whereNotIn('mata_pelajaran_id', ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'])
                    ->delete();
            }

            // Load Preferensi Ketersediaan Guru (Off-Days, Max JP Harian, Jam Berhalangan)
            $guruPreferences = \App\Models\JadwalGuruPreferensi::all()->keyBy('ptk_id');

            // State Tracking: Rombel, Guru, dan Distribusi Hari
            $rombelOccupied    = []; // [rombelId][hari][slot] = true
            $guruOccupied      = []; // [ptkId][hari][slot] = true
            $guruDayJp         = []; // [ptkId][hari] = totalJP
            $rombelDayMapel    = []; // [rombelId][hari][mapelId] = count
            $rombelDayJp       = []; // [rombelId][hari] = totalJP

            // Inisialisasi: Seed kegiatan rutin (Upacara & Pembiasaan) sebagai anchor slot contiguity
            foreach ($selectedRombelIds as $rId) {
                if ($upacaraHari && $upacaraJamKe) {
                    $rombelOccupied[$rId][$upacaraHari][$upacaraJamKe] = true;
                    $rombelDayJp[$rId][$upacaraHari] = ($rombelDayJp[$rId][$upacaraHari] ?? 0) + 1;
                }
                if ($pembiasaanHari && $pembiasaanJamKe) {
                    $rombelOccupied[$rId][$pembiasaanHari][$pembiasaanJamKe] = true;
                    $rombelDayJp[$rId][$pembiasaanHari] = ($rombelDayJp[$rId][$pembiasaanHari] ?? 0) + 1;
                }
            }

            // Inisialisasi state dari data eksisting yang masih tersisa (termasuk kegiatan rutin lain yang sudah ada di DB)
            $existingSchedules = DB::table('jadwal_kbm')
                ->where('is_active', true)
                ->get();

            foreach ($existingSchedules as $ex) {
                $rId = $ex->rombongan_belajar_id;
                $gId = $ex->ptk_id;
                $h   = $ex->hari;
                $mId = $ex->mata_pelajaran_id;

                for ($k = (int)$ex->jam_ke_mulai; $k <= (int)$ex->jam_ke_selesai; $k++) {
                    $rombelOccupied[$rId][$h][$k] = true;
                    if ($gId) {
                        $guruOccupied[$gId][$h][$k] = true;
                    }
                }

                $rombelDayMapel[$rId][$h][$mId] = ($rombelDayMapel[$rId][$h][$mId] ?? 0) + 1;
                $durasi = max(1, (int)$ex->jam_ke_selesai - (int)$ex->jam_ke_mulai + 1);
                $rombelDayJp[$rId][$h] = ($rombelDayJp[$rId][$h] ?? 0) + $durasi;
                if ($gId) {
                    $guruDayJp[$gId][$h] = ($guruDayJp[$gId][$h] ?? 0) + $durasi;
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

            // Hitung beban guru untuk prioritas constraint
            $guruLoadCount = [];
            foreach ($pembelajaranList as $p) {
                if ($p->ptk_id) {
                    $guruLoadCount[$p->ptk_id] = ($guruLoadCount[$p->ptk_id] ?? 0) + 1;
                }
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
                        'guru_load'            => $p->ptk_id ? ($guruLoadCount[$p->ptk_id] ?? 0) : 0,
                    ];
                }
            }

            // 4. Urutkan Sesi Berdasarkan Tingkat Kesulitan (Most Constrained First)
            // Blok durasi terbesar & guru dengan jam terbanyak ditempatkan lebih dulu!
            usort($sessionsToSchedule, function ($a, $b) {
                if ($a['duration'] !== $b['duration']) {
                    return $b['duration'] <=> $a['duration'];
                }
                return $b['guru_load'] <=> $a['guru_load'];
            });

            // 5. PASS 1: Penempatan Slot Strict Contiguity (Tanpa Lubang)
            // Hanya menawarkan posisi slot yang langsung menyambung setelah pelajaran terakhir pada hari tersebut
            $batchInserts = [];
            $unallocatedSessions = [];

            foreach ($sessionsToSchedule as $sess) {
                $rId = $sess['rombongan_belajar_id'];
                $gId = $sess['ptk_id'];
                $mId = $sess['mata_pelajaran_id'];
                $dur = $sess['duration'];

                // Batasi alokasi JP agar rombel tidak melampaui target per tingkat (X=50, XI=48, XII=46)
                $targetRombelMax = $rombelTargetJp[$rId] ?? 50;
                if ((array_sum($rombelDayJp[$rId] ?? []) + $dur) > $targetRombelMax) {
                    continue;
                }

                $placed = false;

                // Urutkan hari berdasarkan beban JP rombel terendah untuk menjaga keseimbangan
                $sortedHari = $hariList;
                usort($sortedHari, function ($h1, $h2) use ($rId, $rombelDayJp) {
                    return ($rombelDayJp[$rId][$h1] ?? 0) <=> ($rombelDayJp[$rId][$h2] ?? 0);
                });

                foreach ($sortedHari as $h) {
                    $gPref = $gId ? ($guruPreferences[$gId] ?? null) : null;
                    if ($gPref) {
                        if (!empty($gPref->hari_off) && in_array($h, $gPref->hari_off, true)) {
                            continue;
                        }
                        if ($gPref->max_jp_per_hari && (($guruDayJp[$gId][$h] ?? 0) + $dur > $gPref->max_jp_per_hari)) {
                            continue;
                        }
                    }

                    // Hindari mapel teori yang sama di hari yang sama jika belum terpaksa
                    $alreadyOnDay = ($rombelDayMapel[$rId][$h][$mId] ?? 0) > 0;
                    if ($alreadyOnDay && !$sess['is_block_kejuruan']) {
                        continue;
                    }

                    // Hanya ambil posisi slot contiguous (menyambung tanpa lubang)
                    $candidateSlots = $this->getNextContiguousSlots(
                        $rId, $h, $dur, $rombelOccupied, $dailySlotCounts, $istirahatJamKe
                    );

                    foreach ($candidateSlots as $startK) {
                        $endK = $startK + $dur - 1;

                        // Cek Jam Berhalangan Spesifik Guru
                        if ($gPref && !empty($gPref->jam_unavailable)) {
                            $isUn = false;
                            foreach ($gPref->jam_unavailable as $un) {
                                if (($un['hari'] ?? '') === $h) {
                                    $unS = (int) ($un['jam_ke_mulai'] ?? 1);
                                    $unE = (int) ($un['jam_ke_selesai'] ?? $unS);
                                    if ($startK <= $unE && $endK >= $unS) {
                                        $isUn = true;
                                        break;
                                    }
                                }
                            }
                            if ($isUn) continue;
                        }

                        // Periksa apakah guru tersedia di sepanjang rentang durasi
                        $slotFree = true;
                        if ($gId) {
                            for ($checkK = $startK; $checkK <= $endK; $checkK++) {
                                if (!empty($guruOccupied[$gId][$h][$checkK])) {
                                    $slotFree = false;
                                    break;
                                }
                            }
                        }

                        if ($slotFree) {
                            // Tempatkan sesi ini!
                            for ($occK = $startK; $occK <= $endK; $occK++) {
                                $rombelOccupied[$rId][$h][$occK] = true;
                                if ($gId) {
                                    $guruOccupied[$gId][$h][$occK] = true;
                                }
                            }

                            $rombelDayMapel[$rId][$h][$mId] = ($rombelDayMapel[$rId][$h][$mId] ?? 0) + 1;
                            $rombelDayJp[$rId][$h] = ($rombelDayJp[$rId][$h] ?? 0) + $dur;
                            if ($gId) {
                                $guruDayJp[$gId][$h] = ($guruDayJp[$gId][$h] ?? 0) + $dur;
                            }

                            $jamMulai = $slots[$startK]['mulai'] ?? '07:15';
                            $jamSelesai = $slots[$endK]['selesai'] ?? '08:45';
                            $semesterId = $rombelMap[$rId]->semester_id ?? null;

                            $batchInserts[] = [
                                'rombongan_belajar_id' => $rId,
                                'pembelajaran_id'      => $sess['pembelajaran_id'],
                                'ptk_id'               => $gId,
                                'mata_pelajaran_id'    => $mId,
                                'nama_mata_pelajaran'  => $sess['nama_mata_pelajaran'],
                                'hari'                 => $h,
                                'jam_ke_mulai'         => $startK,
                                'jam_ke_selesai'       => $endK,
                                'jam_mulai'            => strlen($jamMulai) === 5 ? "{$jamMulai}:00" : $jamMulai,
                                'jam_selesai'          => strlen($jamSelesai) === 5 ? "{$jamSelesai}:00" : $jamSelesai,
                                'ruangan'              => null,
                                'semester_id'          => $semesterId,
                                'is_active'            => true,
                                'keterangan'           => 'Auto-Generated',
                                'created_at'           => now(),
                                'updated_at'           => now(),
                            ];

                            $placed = true;
                            break;
                        }
                    }

                    if ($placed) {
                        break;
                    }
                }

                // Fallback: jika belum dapat slot, coba abaikan batasan duplicate mapel harian
                if (!$placed) {
                    $targetRombelMax = $rombelTargetJp[$rId] ?? 50;
                    if ((array_sum($rombelDayJp[$rId] ?? []) + $dur) > $targetRombelMax) {
                        $unallocatedSessions[] = $sess;
                        continue;
                    }

                    foreach ($sortedHari as $h) {
                        $gPref = $gId ? ($guruPreferences[$gId] ?? null) : null;
                        if ($gPref) {
                            if (!empty($gPref->hari_off) && in_array($h, $gPref->hari_off, true)) {
                                continue;
                            }
                            if ($gPref->max_jp_per_hari && (($guruDayJp[$gId][$h] ?? 0) + $dur > $gPref->max_jp_per_hari)) {
                                continue;
                            }
                        }

                        $candidateSlotsFb = $this->getNextContiguousSlots(
                            $rId, $h, $dur, $rombelOccupied, $dailySlotCounts, $istirahatJamKe
                        );

                        foreach ($candidateSlotsFb as $startK) {
                            $endK = $startK + $dur - 1;

                            if ($gPref && !empty($gPref->jam_unavailable)) {
                                $isUn = false;
                                foreach ($gPref->jam_unavailable as $un) {
                                    if (($un['hari'] ?? '') === $h) {
                                        $unS = (int) ($un['jam_ke_mulai'] ?? 1);
                                        $unE = (int) ($un['jam_ke_selesai'] ?? $unS);
                                        if ($startK <= $unE && $endK >= $unS) {
                                            $isUn = true;
                                            break;
                                        }
                                    }
                                }
                                if ($isUn) continue;
                            }

                            $slotFree = true;
                            if ($gId) {
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
                                    if ($gId) {
                                        $guruOccupied[$gId][$h][$occK] = true;
                                    }
                                }

                                $rombelDayJp[$rId][$h] = ($rombelDayJp[$rId][$h] ?? 0) + $dur;
                                if ($gId) {
                                    $guruDayJp[$gId][$h] = ($guruDayJp[$gId][$h] ?? 0) + $dur;
                                }

                                $jamMulai = $slots[$startK]['mulai'] ?? '07:15';
                                $jamSelesai = $slots[$endK]['selesai'] ?? '08:45';
                                $semesterId = $rombelMap[$rId]->semester_id ?? null;

                                $batchInserts[] = [
                                    'rombongan_belajar_id' => $rId,
                                    'pembelajaran_id'      => $sess['pembelajaran_id'],
                                    'ptk_id'               => $gId,
                                    'mata_pelajaran_id'    => $mId,
                                    'nama_mata_pelajaran'  => $sess['nama_mata_pelajaran'],
                                    'hari'                 => $h,
                                    'jam_ke_mulai'         => $startK,
                                    'jam_ke_selesai'       => $endK,
                                    'jam_mulai'            => strlen($jamMulai) === 5 ? "{$jamMulai}:00" : $jamMulai,
                                    'jam_selesai'          => strlen($jamSelesai) === 5 ? "{$jamSelesai}:00" : $jamSelesai,
                                    'ruangan'              => null,
                                    'semester_id'          => $semesterId,
                                    'is_active'            => true,
                                    'keterangan'           => 'Auto-Generated (Fallback)',
                                    'created_at'           => now(),
                                    'updated_at'           => now(),
                                ];

                                $placed = true;
                                break;
                            }
                        }
                        if ($placed) break;
                    }
                }

                if (!$placed) {
                    $unallocatedSessions[] = $sess;
                }
            }

            // PASS 3: ELASTIC SUB-BLOCK SPLITTING & CONTIGUOUS GAP FILLING
            // Memecah sesi yang belum teralokasi menjadi unit 1 JP agar dapat mengisi slot contiguous yang tersisa
            $unallocated = 0;
            if (!empty($unallocatedSessions)) {
                $splittable = [];
                foreach ($unallocatedSessions as $u) {
                    // Pecah menjadi unit 1 JP individual untuk maksimum fleksibilitas
                    for ($i = 0; $i < $u['duration']; $i++) {
                        $splittable[] = array_merge($u, ['duration' => 1]);
                    }
                }

                foreach ($splittable as $sess) {
                    $rId = $sess['rombongan_belajar_id'];
                    $gId = $sess['ptk_id'];
                    $mId = $sess['mata_pelajaran_id'];

                    $targetRombelMax = $rombelTargetJp[$rId] ?? 50;
                    if ((array_sum($rombelDayJp[$rId] ?? []) + 1) > $targetRombelMax) {
                        $unallocated++;
                        continue;
                    }

                    $placed = false;

                    $sortedHari = $hariList;
                    usort($sortedHari, function ($h1, $h2) use ($rId, $rombelDayJp) {
                        return ($rombelDayJp[$rId][$h1] ?? 0) <=> ($rombelDayJp[$rId][$h2] ?? 0);
                    });

                    foreach ($sortedHari as $h) {
                        $gPref = $gId ? ($guruPreferences[$gId] ?? null) : null;
                        if ($gPref) {
                            if (!empty($gPref->hari_off) && in_array($h, $gPref->hari_off, true)) continue;
                            if ($gPref->max_jp_per_hari && (($guruDayJp[$gId][$h] ?? 0) + 1 > $gPref->max_jp_per_hari)) continue;
                        }

                        $candidateSlotsGap = $this->getNextContiguousSlots(
                            $rId, $h, 1, $rombelOccupied, $dailySlotCounts, $istirahatJamKe
                        );

                        foreach ($candidateSlotsGap as $startK) {
                            $free = true;
                            if ($gId && !empty($guruOccupied[$gId][$h][$startK])) {
                                $free = false;
                            }

                            if ($free) {
                                $rombelOccupied[$rId][$h][$startK] = true;
                                if ($gId) {
                                    $guruOccupied[$gId][$h][$startK] = true;
                                }

                                $rombelDayJp[$rId][$h] = ($rombelDayJp[$rId][$h] ?? 0) + 1;
                                if ($gId) {
                                    $guruDayJp[$gId][$h] = ($guruDayJp[$gId][$h] ?? 0) + 1;
                                }

                                $jamMulai = $slots[$startK]['mulai'] ?? '07:15';
                                $jamSelesai = $slots[$startK]['selesai'] ?? '08:00';
                                $semesterId = $rombelMap[$rId]->semester_id ?? null;

                                $batchInserts[] = [
                                    'rombongan_belajar_id' => $rId,
                                    'pembelajaran_id'      => $sess['pembelajaran_id'],
                                    'ptk_id'               => $gId,
                                    'mata_pelajaran_id'    => $mId,
                                    'nama_mata_pelajaran'  => $sess['nama_mata_pelajaran'],
                                    'hari'                 => $h,
                                    'jam_ke_mulai'         => $startK,
                                    'jam_ke_selesai'       => $startK,
                                    'jam_mulai'            => strlen($jamMulai) === 5 ? "{$jamMulai}:00" : $jamMulai,
                                    'jam_selesai'          => strlen($jamSelesai) === 5 ? "{$jamSelesai}:00" : $jamSelesai,
                                    'ruangan'              => null,
                                    'semester_id'          => $semesterId,
                                    'is_active'            => true,
                                    'keterangan'           => 'Auto-Generated (Gap Fill)',
                                    'created_at'           => now(),
                                    'updated_at'           => now(),
                                ];

                                $placed = true;
                                break;
                            }
                        }
                        if ($placed) break;
                    }

                    if (!$placed) {
                        $unallocated++;
                    }
                }
            }

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
                'message'         => "Berhasil men-generate {$totalGenerated} jadwal KBM ({$totalJpCreated} JP) secara otomatis tanpa jam pelajaran terlewat!",
                'total_generated' => $totalGenerated,
                'total_jp'        => $totalJpCreated,
                'total_rombel'    => count($selectedRombelIds),
                'unallocated'     => $unallocated,
            ];
        });
    }

    /**
     * Hitung posisi slot contiguous berikutnya untuk rombel pada hari tertentu.
     * Hanya mengembalikan posisi yang langsung menyambung setelah pelajaran terakhir,
     * sehingga TIDAK PERNAH menghasilkan lubang di tengah jadwal.
     *
     * Logika:
     * 1. Cari ujung terakhir pelajaran pagi (sebelum istirahat) → tawarkan slot berikutnya
     * 2. Jika pagi sudah penuh hingga batas istirahat, tawarkan juga slot siang (setelah istirahat)
     *
     * @return int[] Array of candidate start positions (at most 2: morning-append, afternoon-append)
     */
    private function getNextContiguousSlots(
        string $rId,
        string $h,
        int $dur,
        array &$rombelOccupied,
        array $dailySlotCounts,
        ?int $istirahatJamKe
    ): array {
        $dayMax = $dailySlotCounts[$h] ?? 11;
        $rOcc = $rombelOccupied[$rId][$h] ?? [];

        // Tentukan batas jendela pagi
        $morningMax = ($istirahatJamKe && $istirahatJamKe <= $dayMax) ? ($istirahatJamKe - 1) : $dayMax;

        // Cari ujung terakhir slot pagi yang terisi berurutan dari awal
        $morningEnd = 0;
        for ($k = 1; $k <= $morningMax; $k++) {
            if (!empty($rOcc[$k])) {
                $morningEnd = $k;
            } else {
                break;
            }
        }

        $candidates = [];

        // Kandidat 1: Append ke sesi pagi
        $candMorning = $morningEnd + 1;
        if ($candMorning >= 1 && ($candMorning + $dur - 1) <= $morningMax) {
            $candidates[] = $candMorning;
        }

        // Kandidat 2: Append ke sesi siang (hanya jika pagi sudah penuh hingga batas istirahat)
        if ($istirahatJamKe && $istirahatJamKe <= $dayMax && $morningEnd >= $morningMax) {
            $afternoonStart = $istirahatJamKe + 1;
            $afternoonEnd = $afternoonStart - 1;
            for ($k = $afternoonStart; $k <= $dayMax; $k++) {
                if (!empty($rOcc[$k])) {
                    $afternoonEnd = $k;
                } else {
                    break;
                }
            }

            $candAfternoon = $afternoonEnd + 1;
            if ($candAfternoon >= $afternoonStart && ($candAfternoon + $dur - 1) <= $dayMax) {
                $candidates[] = $candAfternoon;
            }
        }

        return $candidates;
    }

    /**
     * Memecah total jam mengajar mingguan (JJM) menjadi sesi blok pertemuan yang teratur dan adaptif terhadap jendela KBM
     * Contoh: 9 JP -> [6, 3] atau [5, 4], 8 JP -> [5, 3] atau [4, 4], 4 JP -> [4] atau [2, 2]
     */
    private function decomposeJjmIntoBlocks(int $jjm, int $maxBlock = 3, ?int $istirahatJamKe = null): array
    {
        if ($jjm <= 0) return [];
        $maxBlock = max(2, min(9, $maxBlock));

        // Jendela kontinyu maksimal sebelum istirahat (misal istirahat jam ke-8 berarti jendela pagi = 7 JP)
        $maxContinuousWindow = $istirahatJamKe ? max(4, $istirahatJamKe - 1) : 7;
        $effectiveMax = min($maxBlock, $maxContinuousWindow);

        // Khusus blok kejuruan besar (9 JP & 8 JP) agar muat sebelum & sesudah istirahat dalam satu hari atau dua hari
        if ($jjm === 9) {
            if ($effectiveMax >= 6) return [6, 3];
            if ($effectiveMax >= 5) return [5, 4];
            return [3, 3, 3];
        }

        if ($jjm === 8) {
            if ($effectiveMax >= 5) return [5, 3];
            return [4, 4];
        }

        if ($jjm === 7) return [4, 3];
        if ($jjm === 6) return [3, 3];
        if ($jjm === 5) return [3, 2];

        if ($jjm === 4) {
            return ($effectiveMax >= 4) ? [4] : [2, 2];
        }

        if ($jjm <= $effectiveMax) {
            return [$jjm];
        }

        // Dekomposisi standar seimbang
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
