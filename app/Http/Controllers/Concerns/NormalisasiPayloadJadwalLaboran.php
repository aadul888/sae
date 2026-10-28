<?php

namespace App\Http\Controllers\Concerns;

use App\Models\JadwalPengaturan;
use App\Services\LaboranJadwalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Normalisasi payload penjadwalan praktik Laboran.
 *
 * Diekstrak dari `LaboranJadwalController` agar controller tetap di bawah batas
 * 1.000 baris. Isi method dipindahkan APA ADANYA (termasuk `abort()` 422 dan
 * fallback slot KBM) sehingga perilaku validasi tidak berubah.
 *
 * Pemakai wajib menyediakan properti `$service` bertipe `LaboranJadwalService`.
 */
trait NormalisasiPayloadJadwalLaboran
{
    protected function validatedPayload(Request $request): array
    {
        $validated = $request->validate([
            'ruang_lab_nama'       => 'required|string|max:100',
            'jenis_lokasi'         => 'nullable|in:' . implode(',', array_keys(LaboranJadwalService::JENIS_LOKASI)),
            'rombel_id'            => 'nullable|string|max:100',
            'pembelajaran_id'      => 'nullable|string|max:50',
            'mata_pelajaran_id'    => 'nullable|string|max:50',
            'mata_pelajaran'       => 'nullable|string|max:255',
            'ptk_id'               => 'nullable|string|max:100',
            'ptk_ids'              => 'nullable|array',
            'ptk_ids.*'            => 'nullable|string|max:100',
            'guru_pendamping'      => 'nullable|string|max:500',
            'topik_praktik'        => 'nullable|string|max:255',
            'tanggal'              => 'nullable|date',
            'pemberlakuan'         => 'nullable|in:sekali,mingguan,bulanan,semester',
            'hari'                 => 'nullable|array',
            'hari.*'               => 'nullable|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'tanggal_akhir'        => 'nullable|date|after_or_equal:tanggal',
            'jam_mulai'            => 'nullable',
            'jam_selesai'          => 'nullable',
            'jam_ke_mulai'         => 'nullable|integer|min:1|max:20',
            'jam_ke_selesai'       => 'nullable|integer|min:1|max:20',
            'alat_bahan_digunakan' => 'nullable|string|max:1000',
            'status'               => 'nullable|in:' . implode(',', array_keys(LaboranJadwalService::STATUS_LIST)),
            'laporan_kerusakan'    => 'nullable|string|max:1000',
        ], [
            'ruang_lab_nama.required' => 'Lokasi praktik wajib dipilih.',
        ]);

        $rombelId       = trim((string) ($validated['rombel_id'] ?? ''));
        $pembelajaranId = trim((string) ($validated['pembelajaran_id'] ?? ''));
        $mapelNama      = trim((string) ($validated['mata_pelajaran'] ?? ''));
        $mapelId        = trim((string) ($validated['mata_pelajaran_id'] ?? ''));
        $ptkId          = trim((string) ($validated['ptk_id'] ?? ''));

        // Modal simpel: jika pembelajaran_id diberikan, ambil data dari baris pembelajaran
        $pembelajaran = null;
        if ($pembelajaranId !== '') {
            $pembelajaran = DB::table('pembelajaran as p')
                ->leftJoin('gtk as g', 'p.ptk_id', '=', 'g.ptk_id')
                ->where('p.pembelajaran_id', $pembelajaranId)
                ->select('p.*', 'g.nama as nama_guru')
                ->first();
        }

        if (!$pembelajaran && $rombelId !== '') {
            $q = DB::table('pembelajaran')->where('rombongan_belajar_id', $rombelId);
            if ($mapelNama !== '') {
                $q->where(function ($w) use ($mapelNama) {
                    $w->where('nama_mata_pelajaran', $mapelNama)
                      ->orWhere('mata_pelajaran_id_str', $mapelNama);
                });
            }
            if ($ptkId !== '') {
                $q->where('ptk_id', $ptkId);
            }
            $pembelajaran = $q->first();
        }

        if ($pembelajaran) {
            $pembelajaranId = $pembelajaran->pembelajaran_id;
            $mapelNama      = $mapelNama !== ''
                ? $mapelNama
                : trim((string) ($pembelajaran->nama_mata_pelajaran ?: $pembelajaran->mata_pelajaran_id_str));
            $mapelId        = $mapelId !== '' ? $mapelId : (string) ($pembelajaran->mata_pelajaran_id ?? '');
            $ptkId          = $ptkId !== '' ? $ptkId : (string) ($pembelajaran->ptk_id ?? '');
            if ($rombelId === '') {
                $rombelId = (string) ($pembelajaran->rombongan_belajar_id ?? '');
            }
        }

        if ($mapelNama === '' && $rombelId !== '') {
            $fallback = DB::table('pembelajaran')
                ->where('rombongan_belajar_id', $rombelId)
                ->orderBy('nama_mata_pelajaran')
                ->first();
            if ($fallback) {
                $mapelNama      = trim((string) ($fallback->nama_mata_pelajaran ?: $fallback->mata_pelajaran_id_str));
                $mapelId        = (string) ($fallback->mata_pelajaran_id ?? '');
                $ptkId          = $ptkId !== '' ? $ptkId : (string) ($fallback->ptk_id ?? '');
                $pembelajaranId = $pembelajaranId !== '' ? $pembelajaranId : $fallback->pembelajaran_id;
            }
        }

        if ($ptkId === '' && $rombelId !== '') {
            $ptkId = (string) (DB::table('pembelajaran as p')
                ->where('p.rombongan_belajar_id', $rombelId)
                ->whereNotNull('p.ptk_id')
                ->value('p.ptk_id') ?? '');
        }

        // Modal simpel: guru & mapel wajib berasal dari data pembelajaran.
        // Tanpa pembelajaran yang dikenali, penyimpanan ditolak (bukan menebak guru).
        if ($ptkId === '' || $mapelNama === '') {
            abort(response()->json([
                'status'  => 'error',
                'message' => 'Guru pengampu & mata pelajaran wajib dipilih dari daftar pembelajaran.',
            ], 422));
        }

        $tanggal = !empty($validated['tanggal']) ? $validated['tanggal'] : now()->toDateString();

        $jenis = $validated['jenis_lokasi'] ?? null;
        if (!$jenis) {
            $jenis = $this->service->detectJenisLokasi($validated['ruang_lab_nama'], null);
        }

        // Alokasi waktu via jam pelajaran (Jam ke-N): konversi ke jam mulai/selesai
        // berdasarkan slot Pengaturan KBM hari tanggal terpilih.
        $jamMulai   = trim((string) ($validated['jam_mulai'] ?? ''));
        $jamSelesai = trim((string) ($validated['jam_selesai'] ?? ''));
        // Satu pengisian boleh mencakup BEBERAPA JP sekaligus (mis. Jam ke-1 s/d
        // Jam ke-4), sesuai rentang yang dipilih laboran pada modal.
        $jamKeMulai   = (int) ($validated['jam_ke_mulai'] ?? 0);
        $jamKeSelesai = (int) ($validated['jam_ke_selesai'] ?? 0);

        if ($jamKeMulai > 0) {
            $hariValid = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            $hariBase = null;

            // Prioritaskan hari dari slot yang dipilih (kiriman `hari[]`/`hari_semester`),
            // agar konversi Jam Ke-N -> jam tetap mengikuti hari slot, bukan tanggal acuan.
            $hariSubmit = (array) $request->input('hari', []);
            $hariSubmitName = trim((string) ($hariSubmit[0] ?? ''));
            if ($hariSubmitName === '' && $request->filled('hari_semester')) {
                $hariSubmitName = (string) $request->input('hari_semester');
            }
            if (in_array($hariSubmitName, $hariValid, true)) {
                $hariBase = $hariSubmitName;
            }

            if (!$hariBase && !empty($validated['tanggal'])) {
                $dayMap = ['Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
                    'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'];
                $hariBase = $dayMap[date('l', strtotime($validated['tanggal']))] ?? null;
            }
            if (!$hariBase || !in_array($hariBase, $hariValid, true)) {
                $hariBase = 'Senin';
            }
            $slots = JadwalPengaturan::getSlots($hariBase);
            if ($jamKeSelesai < $jamKeMulai) {
                $jamKeSelesai = $jamKeMulai;
            }

            $slotMulai   = $slots[$jamKeMulai] ?? null;
            $slotSelesai = $slots[$jamKeSelesai] ?? null;

            if ($slotMulai) {
                $jamMulai = $slotMulai['jam_mulai'];
            }
            if ($slotSelesai) {
                $jamSelesai = $slotSelesai['jam_selesai'];
            }

            // Fallback durasi 45 menit bila slot tidak tersedia
            if ($jamMulai === '' || $jamSelesai === '') {
                $base = $jamMulai !== '' ? Carbon::createFromFormat('H:i', $jamMulai) : Carbon::createFromTime(7, 15);
                if ($jamMulai === '') {
                    $jamMulai = $base->format('H:i');
                }
                $jamSelesai = $base->copy()->addMinutes(45 * max(1, $jamKeSelesai - $jamKeMulai + 1))->format('H:i');
            }
        }

        if ($jamMulai === '') {
            $jamMulai = '08:00';
        }
        if ($jamSelesai === '') {
            $jamSelesai = Carbon::parse($jamMulai)->addMinutes(45)->format('H:i');
        } elseif (strlen($jamSelesai) === 5 && $jamSelesai <= $jamMulai) {
            $jamSelesai = Carbon::parse($jamMulai)->addMinutes(45)->format('H:i');
        }

        $allTeacherIds = $this->service->extractAllPtkIds([
            'ptk_id'  => $ptkId,
            'ptk_ids' => $validated['ptk_ids'] ?? [],
        ]);

        $primaryPtkId = $allTeacherIds[0] ?? ($ptkId !== '' ? $ptkId : null);
        $companionNames = $this->service->formatGuruPendamping($allTeacherIds, $primaryPtkId)
            ?: (trim((string) ($validated['guru_pendamping'] ?? '')) ?: null);

        $topik = trim((string) ($validated['topik_praktik'] ?? ''));

        return [
            'ruang_lab_nama'       => trim((string) $validated['ruang_lab_nama']),
            'jenis_lokasi'         => $jenis,
            'rombel_id'            => $rombelId,
            'pembelajaran_id'      => $pembelajaranId !== '' ? $pembelajaranId : null,
            'mata_pelajaran_id'    => $mapelId !== '' ? $mapelId : null,
            'mata_pelajaran'       => $mapelNama,
            'ptk_id'               => $primaryPtkId,
            'ptk_ids'              => !empty($allTeacherIds) ? json_encode($allTeacherIds) : null,
            'guru_pendamping'      => $companionNames,
            'topik_praktik'        => $topik !== '' ? $topik : null,
            'tanggal'              => $tanggal,
            'pemberlakuan'         => $validated['pemberlakuan'] ?? 'mingguan',
            'jam_mulai'            => $jamMulai,
            'jam_selesai'          => $jamSelesai,
            'alat_bahan_digunakan' => $validated['alat_bahan_digunakan'] ?? null,
            'status'               => $validated['status'] ?? 'dijadwalkan',
            'laporan_kerusakan'    => $validated['laporan_kerusakan'] ?? null,
        ];
    }
}
