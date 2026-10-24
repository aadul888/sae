<?php

namespace App\Services;

use App\Models\JadwalGuruPreferensi;
use App\Models\JadwalPengaturan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JadwalPengaturanService
{
    /**
     * Mengubah mode dan status pemberlakuan jadwal (otomatis / manual / draft).
     */
    public function togglePemberlakuan(Request $request, mixed $user): array
    {
        $pengaturan = JadwalPengaturan::getSettings();

        $targetMode = $request->input('mode', 'otomatis');
        if (!in_array($targetMode, ['otomatis', 'manual'], true)) {
            $targetMode = 'otomatis';
        }

        $reqStatus = $request->input('status');
        if (!in_array($reqStatus, ['aktif', 'draft'], true)) {
            $currentActiveMode = JadwalPengaturan::getModeAktif();
            $reqStatus = ($currentActiveMode === $targetMode) ? 'draft' : 'aktif';
        }

        $adminName = is_array($user) ? ($user['nama'] ?? ($user['username'] ?? 'Administrator')) : ($user->nama ?? ($user->username ?? 'Administrator'));

        if ($reqStatus === 'aktif') {
            $newMode = $targetMode;
            $newStatus = 'aktif';
            $isAktif = true;
            $modeLabel = ($targetMode === 'otomatis') ? 'Otomatis (Generate)' : 'Manual (Wali Kelas/Koordinator)';
            $otherLabel = ($targetMode === 'otomatis') ? 'Manual' : 'Otomatis';
            $message = "Jadwal KBM {$modeLabel} RESMI DIBERLAKUKAN! Jadwal {$otherLabel} otomatis dialihkan ke status Draft. Jadwal aktif kini ditampilkan pada dashboard Guru, Siswa, dan Presensi Mengajar.";
            $catatan = $request->input('catatan') ?: "Jadwal {$modeLabel} diberlakukan secara resmi oleh {$adminName}.";
        } else {
            $newMode = 'draft';
            $newStatus = 'draft';
            $isAktif = false;
            $message = "Status Jadwal KBM dialihkan ke DRAFT. Seluruh jadwal dinonaktifkan sementara dan guru/siswa akan melihat info jadwal dalam tahap penyusunan.";
            $catatan = $request->input('catatan') ?: "Status jadwal dialihkan ke Draft oleh {$adminName}.";
        }

        $pengaturan->update([
            'mode_pemberlakuan'    => $newMode,
            'status_jadwal'        => $newStatus,
            'is_diberlakukan'      => $isAktif,
            'diberlakukan_pada'    => $isAktif ? now() : null,
            'diberlakukan_oleh'    => $isAktif ? $adminName : null,
            'catatan_pemberlakuan' => $catatan,
        ]);

        if ($isAktif) {
            try {
                JadwalPengaturan::syncRoutineActivities($newMode);
            } catch (\Throwable $e) {}
        }

        RealtimeService::trigger('jadwal.changed', [
            'action'            => 'pemberlakuan_updated',
            'mode_pemberlakuan' => $newMode,
            'status'            => $newStatus,
            'is_diberlakukan'   => $isAktif,
        ]);

        return [
            'success'           => true,
            'message'           => $message,
            'mode_pemberlakuan' => $newMode,
            'status'            => $newStatus,
            'is_diberlakukan'   => $isAktif,
            'is_otomatis_aktif' => ($isAktif && $newMode === 'otomatis'),
            'is_manual_aktif'   => ($isAktif && $newMode === 'manual'),
            'diberlakukan_pada' => $pengaturan->diberlakukan_pada ? $pengaturan->diberlakukan_pada->format('d/m/Y H:i') : null,
        ];
    }

    /**
     * Simpan Pengaturan Slot Jam KBM & Durasi per JP.
     */
    public function simpanPengaturan(Request $request): array
    {
        $validated = $request->validate([
            'jam_mulai_kbm'          => 'required|string',
            'durasi_per_jp'          => 'required|integer|min:20|max:90',
            'total_slot_jp'          => 'nullable|integer|min:0|max:16',
            'max_jp_per_sesi'        => 'nullable|integer|min:2|max:9',
            'slot_harian'            => 'nullable|array',
            'slot_harian.*.total_jp' => 'nullable|integer|min:0|max:16',
            'slot_tingkat_harian'    => 'nullable|array',
            'jp_tingkat'             => 'nullable|array',
            'hari_aktif'             => 'nullable|array',
            'istirahat'              => 'nullable|array',
            'upacara'                => 'nullable|array',
            'pembiasaan'             => 'nullable|array',
        ]);

        $skemaHari = $request->input('skema_hari', '5_hari');
        if (!in_array($skemaHari, ['5_hari', '6_hari'], true)) {
            $skemaHari = '5_hari';
        }

        $jamMulai = strlen($validated['jam_mulai_kbm']) === 5 ? "{$validated['jam_mulai_kbm']}:00" : $validated['jam_mulai_kbm'];

        $presetTingkatSlots = JadwalPengaturan::getDefaultTingkatDailySlots($skemaHari);
        $slotTingkatHarian = [];
        if ($request->has('slot_tingkat_harian') && is_array($request->input('slot_tingkat_harian'))) {
            foreach (['10', '11', '12'] as $t) {
                $tInput = $request->input("slot_tingkat_harian.{$t}", []);
                foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $dh) {
                    $rawVal = $tInput[$dh] ?? null;
                    $slotTingkatHarian[$t][$dh] = ($rawVal === '' || $rawVal === null)
                        ? ($presetTingkatSlots[$t][$dh] ?? 0)
                        : max(0, min(16, (int) $rawVal));
                }
                if ($skemaHari === '5_hari') {
                    $slotTingkatHarian[$t]['Sabtu'] = 0;
                }
            }
        } else {
            $slotTingkatHarian = $presetTingkatSlots;
        }

        $slotHarian = [];
        $maxDailySlot = 0;
        $presetSlots = JadwalPengaturan::getPresetSlotHarian($skemaHari);

        if ($request->has('slot_harian') && is_array($request->input('slot_harian'))) {
            foreach ($request->input('slot_harian') as $dh => $val) {
                $rawJp = $val['total_jp'] ?? null;
                $jpVal = ($rawJp === '' || $rawJp === null) ? ($presetSlots[$dh]['total_jp'] ?? 0) : max(0, min(16, (int) $rawJp));
                $maxTingkatJp = max($slotTingkatHarian['10'][$dh] ?? 0, $slotTingkatHarian['11'][$dh] ?? 0, $slotTingkatHarian['12'][$dh] ?? 0);
                $jpVal = max($jpVal, $maxTingkatJp);
                $slotHarian[$dh] = ['total_jp' => $jpVal];
                if ($jpVal > $maxDailySlot) {
                    $maxDailySlot = $jpVal;
                }
            }
        } else {
            foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $dh) {
                $maxTingkatJp = max($slotTingkatHarian['10'][$dh] ?? 0, $slotTingkatHarian['11'][$dh] ?? 0, $slotTingkatHarian['12'][$dh] ?? 0);
                $slotHarian[$dh] = ['total_jp' => $maxTingkatJp];
                if ($maxTingkatJp > $maxDailySlot) {
                    $maxDailySlot = $maxTingkatJp;
                }
            }
        }

        if ($skemaHari === '5_hari') {
            $slotHarian['Sabtu']['total_jp'] = 0;
            $hariAktif = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        } else {
            if (($slotHarian['Sabtu']['total_jp'] ?? 0) <= 0) {
                $slotHarian['Sabtu']['total_jp'] = 6;
            }
            $hariAktif = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        }

        $totalSlotJp = (int) ($validated['total_slot_jp'] ?? ($maxDailySlot > 0 ? $maxDailySlot : 13));

        $jpTingkat = [
            '10' => max(20, min(80, (int) ($request->input('jp_tingkat.10', 50)))),
            '11' => max(20, min(80, (int) ($request->input('jp_tingkat.11', 48)))),
            '12' => max(20, min(80, (int) ($request->input('jp_tingkat.12', 46)))),
        ];

        $upacaraConfig = [
            'aktif'        => !empty($request->input('upacara.aktif')),
            'hari'         => $request->input('upacara.hari', 'Senin'),
            'jam_ke'       => (int) $request->input('upacara.jam_ke', 1),
            'durasi_menit' => (int) $request->input('upacara.durasi_menit', 45),
            'nama'         => 'Upacara Bendera',
        ];

        $pembiasaanConfig = [
            'aktif'        => !empty($request->input('pembiasaan.aktif')),
            'hari'         => $request->input('pembiasaan.hari', 'Jumat'),
            'jam_ke'       => (int) $request->input('pembiasaan.jam_ke', 1),
            'durasi_menit' => (int) $request->input('pembiasaan.durasi_menit', 40),
            'nama'         => $request->input('pembiasaan.nama') ?: 'Pembiasaan',
        ];

        $istirahatConfig = [];
        if ($request->has('istirahat') && is_array($request->input('istirahat'))) {
            foreach ($request->input('istirahat') as $ist) {
                $istirahatConfig[] = [
                    'aktif'        => !empty($ist['aktif']),
                    'jam_ke'       => max(1, (int) ($ist['jam_ke'] ?? 5)),
                    'durasi_menit' => max(5, min(90, (int) ($ist['durasi_menit'] ?? 30))),
                    'nama'         => !empty($ist['nama']) ? $ist['nama'] : 'Istirahat',
                ];
            }
        }

        $pengaturan = JadwalPengaturan::getSettings();
        $pengaturan->update([
            'jam_mulai_kbm'       => $jamMulai,
            'durasi_per_jp'       => $validated['durasi_per_jp'],
            'total_slot_jp'       => $totalSlotJp,
            'max_jp_per_sesi'     => $request->filled('max_jp_per_sesi') ? max(2, min(9, (int) $request->input('max_jp_per_sesi'))) : ($pengaturan->max_jp_per_sesi ?? 3),
            'skema_hari'          => $skemaHari,
            'slot_harian'         => $slotHarian,
            'slot_tingkat_harian' => $slotTingkatHarian,
            'jp_tingkat'          => $jpTingkat,
            'hari_aktif'          => $hariAktif,
            'istirahat'           => $istirahatConfig,
            'upacara'             => $upacaraConfig,
            'pembiasaan'          => $pembiasaanConfig,
        ]);

        JadwalPengaturan::syncRoutineActivities();
        JadwalPengaturan::syncToPresensiPengaturan();

        RealtimeService::trigger('jadwal.changed', [
            'action' => 'pengaturan_updated',
        ]);
        RealtimeService::trigger('kalender.changed', [
            'action' => 'skema_updated',
        ]);

        return [
            'success' => true,
            'message' => 'Pengaturan jam pelajaran & skema ' . ($skemaHari === '6_hari' ? '6 Hari' : '5 Hari') . ' berhasil disimpan!',
            'data'    => $pengaturan,
        ];
    }

    /**
     * Ambil daftar pembelajaran untuk rombel target (termasuk kelas pilihan).
     */
    public function getPembelajaranByRombel(Request $request): array
    {
        $rombelId = $request->get('rombel_id');
        if (!$rombelId) {
            return ['pembelajaran' => []];
        }

        $rombel = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $rombelId)->first();
        $targetRombelIds = [$rombelId];

        if ($rombel) {
            $pilihanRombelQuery = DB::table('rombongan_belajar')
                ->where('jenis_rombel', '!=', 1);

            if (!empty($rombel->id_ruang)) {
                $pilihanRombelQuery->where(function ($q) use ($rombel) {
                    $q->where('id_ruang', $rombel->id_ruang)
                      ->orWhere('nama', trim($rombel->nama))
                      ->orWhere('nama', trim($rombel->nama) . ' 1')
                      ->orWhere('nama', 'LIKE', trim($rombel->nama) . '%');
                });
            } else {
                $cleanName = trim($rombel->nama);
                $pilihanRombelQuery->where(function ($q) use ($cleanName) {
                    $q->where('nama', $cleanName)
                        ->orWhere('nama', $cleanName . ' 1')
                        ->orWhere('nama', 'LIKE', $cleanName . '%');
                });
            }

            $pilihanRombelIds = $pilihanRombelQuery->pluck('rombongan_belajar_id')->toArray();
            $targetRombelIds = array_unique(array_merge($targetRombelIds, $pilihanRombelIds));
        }

        $items = DB::table('pembelajaran as p')
            ->leftJoin('gtk as g', 'p.ptk_id', '=', 'g.ptk_id')
            ->leftJoin('rombongan_belajar as r', 'p.rombongan_belajar_id', '=', 'r.rombongan_belajar_id')
            ->whereIn('p.rombongan_belajar_id', $targetRombelIds)
            ->select(
                'p.pembelajaran_id',
                'p.mata_pelajaran_id',
                'p.rombongan_belajar_id',
                DB::raw('COALESCE(p.nama_mata_pelajaran, p.mata_pelajaran_id_str, "-") as nama_mata_pelajaran'),
                'p.jam_mengajar_per_minggu',
                'p.ptk_id',
                'g.nama as nama_guru',
                'r.jenis_rombel'
            )
            ->orderBy('r.jenis_rombel', 'asc')
            ->orderBy('p.nama_mata_pelajaran', 'asc')
            ->get()
            ->map(function ($item) use ($rombelId) {
                $isPilihan = ($item->rombongan_belajar_id !== $rombelId);
                if ($isPilihan) {
                    $item->nama_mata_pelajaran .= ' [Pilihan]';
                }
                return $item;
            });

        return ['pembelajaran' => $items];
    }

    /**
     * Ambil Preferensi Ketersediaan Guru.
     */
    public function getGuruPreferensi(Request $request): array
    {
        $ptkId = $request->get('ptk_id');
        if (!$ptkId) {
            return ['success' => false, 'message' => 'PTK ID diperlukan.', 'status_code' => 422];
        }

        $guru = DB::table('gtk')->where('ptk_id', $ptkId)->first();
        if (!$guru) {
            return ['success' => false, 'message' => 'Guru tidak ditemukan.', 'status_code' => 404];
        }

        $preferensi = JadwalGuruPreferensi::where('ptk_id', $ptkId)->first();

        return [
            'success' => true,
            'guru' => [
                'ptk_id' => $guru->ptk_id,
                'nama'   => $guru->nama,
                'nip'    => $guru->nip,
            ],
            'preferensi' => $preferensi ? [
                'hari_off'        => $preferensi->hari_off ?? [],
                'jam_unavailable' => $preferensi->jam_unavailable ?? [],
                'max_jp_per_hari' => $preferensi->max_jp_per_hari,
                'keterangan'      => $preferensi->keterangan,
            ] : [
                'hari_off'        => [],
                'jam_unavailable' => [],
                'max_jp_per_hari' => null,
                'keterangan'      => '',
            ],
        ];
    }

    /**
     * Simpan Preferensi Ketersediaan Guru.
     */
    public function simpanGuruPreferensi(Request $request): array
    {
        $validated = $request->validate([
            'ptk_id'          => 'required|string',
            'hari_off'        => 'nullable|array',
            'jam_unavailable' => 'nullable|array',
            'max_jp_per_hari' => 'nullable|integer|min:1|max:16',
            'keterangan'      => 'nullable|string|max:255',
        ]);

        $pref = JadwalGuruPreferensi::updateOrCreate(
            ['ptk_id' => $validated['ptk_id']],
            [
                'hari_off'        => $validated['hari_off'] ?? [],
                'jam_unavailable' => $validated['jam_unavailable'] ?? [],
                'max_jp_per_hari' => $validated['max_jp_per_hari'] ?? null,
                'keterangan'      => $validated['keterangan'] ?? null,
            ]
        );

        RealtimeService::trigger('jadwal.changed', [
            'action' => 'preferensi_updated',
            'ptk_id' => $validated['ptk_id'],
        ]);

        return [
            'success' => true,
            'message' => 'Preferensi ketersediaan guru berhasil disimpan!',
            'data'    => $pref,
        ];
    }
}
