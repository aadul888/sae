<?php

namespace App\Services;

use App\Models\JadwalKbm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JadwalMutasiService
{
    /**
     * Simpan Jadwal KBM Baru.
     */
    public function store(array $validated, bool $overwrite): array
    {
        $pembelajaran = DB::table('pembelajaran')->where('pembelajaran_id', $validated['pembelajaran_id'])->first();
        if (!$pembelajaran) {
            return ['success' => false, 'message' => 'Data pembelajaran tidak valid.', 'status_code' => 422];
        }

        $ptkId = $pembelajaran->ptk_id;
        $mapelId = $pembelajaran->mata_pelajaran_id;
        $namaMapel = $pembelajaran->nama_mata_pelajaran ?: $pembelajaran->mata_pelajaran_id_str;

        $jamMulaiFormatted = strlen($validated['jam_mulai']) === 5 ? "{$validated['jam_mulai']}:00" : $validated['jam_mulai'];
        $jamSelesaiFormatted = strlen($validated['jam_selesai']) === 5 ? "{$validated['jam_selesai']}:00" : $validated['jam_selesai'];

        $conflict = JadwalKbm::checkConflict(
            $ptkId,
            $validated['rombongan_belajar_id'],
            $validated['hari'],
            $validated['jam_mulai'],
            $validated['jam_selesai'],
            null,
            $validated['ruangan'] ?? null,
            (int) $validated['jam_ke_mulai'],
            (int) $validated['jam_ke_selesai'],
            'otomatis'
        );

        if ($conflict['has_conflict']) {
            if ($conflict['type'] === 'rombel') {
                if ($overwrite) {
                    JadwalKbm::where('rombongan_belajar_id', $validated['rombongan_belajar_id'])
                        ->where('hari', $validated['hari'])
                        ->where('sumber', 'otomatis')
                        ->where('is_active', true)
                        ->where(function ($q) use ($jamMulaiFormatted, $jamSelesaiFormatted) {
                            $q->where('jam_mulai', '<', $jamSelesaiFormatted)
                              ->where('jam_selesai', '>', $jamMulaiFormatted);
                        })
                        ->delete();
                } else {
                    return [
                        'success'           => false,
                        'can_overwrite'     => true,
                        'conflict_type'     => 'rombel',
                        'conflicting_mapel' => $conflict['conflict_with']->nama_mata_pelajaran ?? 'Pelajaran Lain',
                        'message'           => $conflict['message'],
                        'conflict'          => $conflict,
                        'status_code'       => 422,
                    ];
                }
            } else {
                return [
                    'success'       => false,
                    'can_overwrite' => false,
                    'conflict_type' => $conflict['type'] ?? 'unknown',
                    'message'       => $conflict['message'],
                    'conflict'      => $conflict,
                    'status_code'   => 422,
                ];
            }
        }

        $rombel = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $validated['rombongan_belajar_id'])->first();

        $jadwal = JadwalKbm::create([
            'rombongan_belajar_id' => $validated['rombongan_belajar_id'],
            'pembelajaran_id'      => $validated['pembelajaran_id'],
            'ptk_id'               => $ptkId,
            'mata_pelajaran_id'    => $mapelId,
            'nama_mata_pelajaran'  => $namaMapel,
            'hari'                 => $validated['hari'],
            'jam_ke_mulai'         => $validated['jam_ke_mulai'],
            'jam_ke_selesai'       => $validated['jam_ke_selesai'],
            'jam_mulai'            => $jamMulaiFormatted,
            'jam_selesai'          => $jamSelesaiFormatted,
            'ruangan'              => $validated['ruangan'] ?? null,
            'semester_id'          => $rombel->semester_id ?? null,
            'sumber'               => 'otomatis',
            'is_active'            => true,
            'keterangan'           => $validated['keterangan'] ?? null,
        ]);

        RealtimeService::trigger('jadwal.changed', [
            'action' => 'created',
            'id'     => $jadwal->id,
            'rombel' => $jadwal->rombongan_belajar_id,
            'hari'   => $jadwal->hari,
        ]);

        return [
            'success' => true,
            'message' => 'Jadwal KBM berhasil ditambahkan!',
            'data'    => $jadwal,
        ];
    }

    /**
     * Perbarui Jadwal KBM.
     */
    public function update(int|string $id, array $validated, bool $overwrite): array
    {
        $jadwal = JadwalKbm::findOrFail($id);

        if (in_array($jadwal->mata_pelajaran_id, ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'], true) || str_starts_with($jadwal->pembelajaran_id ?? '', 'ROUTINE_')) {
            return [
                'success' => false,
                'message' => "Kegiatan rutin '{$jadwal->nama_mata_pelajaran}' tidak dapat diubah secara manual karena ditentukan dari Pengaturan Jam & Bel KBM.",
                'status_code' => 422,
            ];
        }

        $pembelajaran = DB::table('pembelajaran')->where('pembelajaran_id', $validated['pembelajaran_id'])->first();
        if (!$pembelajaran) {
            return ['success' => false, 'message' => 'Data pembelajaran tidak valid.', 'status_code' => 422];
        }

        $ptkId = $pembelajaran->ptk_id;
        $mapelId = $pembelajaran->mata_pelajaran_id;
        $namaMapel = $pembelajaran->nama_mata_pelajaran ?: $pembelajaran->mata_pelajaran_id_str;

        $jamMulaiFormatted = strlen($validated['jam_mulai']) === 5 ? "{$validated['jam_mulai']}:00" : $validated['jam_mulai'];
        $jamSelesaiFormatted = strlen($validated['jam_selesai']) === 5 ? "{$validated['jam_selesai']}:00" : $validated['jam_selesai'];

        $conflict = JadwalKbm::checkConflict(
            $ptkId,
            $validated['rombongan_belajar_id'],
            $validated['hari'],
            $validated['jam_mulai'],
            $validated['jam_selesai'],
            $id,
            $validated['ruangan'] ?? null,
            (int) $validated['jam_ke_mulai'],
            (int) $validated['jam_ke_selesai'],
            'otomatis'
        );

        if ($conflict['has_conflict']) {
            if ($conflict['type'] === 'rombel') {
                if ($overwrite) {
                    JadwalKbm::where('rombongan_belajar_id', $validated['rombongan_belajar_id'])
                        ->where('id', '!=', $id)
                        ->where('hari', $validated['hari'])
                        ->where('sumber', 'otomatis')
                        ->where('is_active', true)
                        ->where(function ($q) use ($jamMulaiFormatted, $jamSelesaiFormatted) {
                            $q->where('jam_mulai', '<', $jamSelesaiFormatted)
                              ->where('jam_selesai', '>', $jamMulaiFormatted);
                        })
                        ->delete();
                } else {
                    return [
                        'success'           => false,
                        'can_overwrite'     => true,
                        'conflict_type'     => 'rombel',
                        'conflicting_mapel' => $conflict['conflict_with']->nama_mata_pelajaran ?? 'Pelajaran Lain',
                        'message'           => $conflict['message'],
                        'conflict'          => $conflict,
                        'status_code'       => 422,
                    ];
                }
            } else {
                return [
                    'success'       => false,
                    'can_overwrite' => false,
                    'conflict_type' => $conflict['type'] ?? 'unknown',
                    'message'       => $conflict['message'],
                    'conflict'      => $conflict,
                    'status_code'   => 422,
                ];
            }
        }

        $jadwal->update([
            'rombongan_belajar_id' => $validated['rombongan_belajar_id'],
            'pembelajaran_id'      => $validated['pembelajaran_id'],
            'ptk_id'               => $ptkId,
            'mata_pelajaran_id'    => $mapelId,
            'nama_mata_pelajaran'  => $namaMapel,
            'hari'                 => $validated['hari'],
            'jam_ke_mulai'         => $validated['jam_ke_mulai'],
            'jam_ke_selesai'       => $validated['jam_ke_selesai'],
            'jam_mulai'            => $jamMulaiFormatted,
            'jam_selesai'          => $jamSelesaiFormatted,
            'ruangan'              => $validated['ruangan'] ?? null,
            'keterangan'           => $validated['keterangan'] ?? null,
        ]);

        RealtimeService::trigger('jadwal.changed', [
            'action' => 'updated',
            'id'     => $jadwal->id,
            'rombel' => $jadwal->rombongan_belajar_id,
            'hari'   => $jadwal->hari,
        ]);

        return [
            'success' => true,
            'message' => 'Jadwal KBM berhasil diperbarui!',
            'data'    => $jadwal,
        ];
    }

    /**
     * Hapus Jadwal KBM.
     */
    public function destroy(int|string $id): array
    {
        $jadwal = JadwalKbm::findOrFail($id);

        if (in_array($jadwal->mata_pelajaran_id, ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'], true) || str_starts_with($jadwal->pembelajaran_id ?? '', 'ROUTINE_')) {
            return [
                'success' => false,
                'message' => "Kegiatan rutin '{$jadwal->nama_mata_pelajaran}' tidak dapat dihapus karena ditentukan dari Pengaturan Jam & Bel KBM.",
                'status_code' => 422,
            ];
        }

        $jadwal->delete();

        RealtimeService::trigger('jadwal.changed', [
            'action' => 'deleted',
            'id'     => $id,
        ]);

        return [
            'success' => true,
            'message' => 'Jadwal KBM berhasil dihapus.',
        ];
    }

    /**
     * API: Cek Potensi Bentrok Jadwal secara Live.
     */
    public function checkConflict(Request $request): array
    {
        $ptkId = $request->get('ptk_id');
        $rombelId = $request->get('rombongan_belajar_id');
        $hari = $request->get('hari');
        $jamMulai = $request->get('jam_mulai');
        $jamSelesai = $request->get('jam_selesai');
        $ignoreId = $request->get('ignore_id');
        $ruangan = $request->get('ruangan');
        $jamKeMulai = $request->get('jam_ke_mulai') ? (int) $request->get('jam_ke_mulai') : null;
        $jamKeSelesai = $request->get('jam_ke_selesai') ? (int) $request->get('jam_ke_selesai') : null;
        $sumber = $request->get('sumber', 'otomatis');

        if (!$hari || !$jamMulai || !$jamSelesai) {
            return [
                'has_conflict' => false,
                'message'      => 'Parameter waktu tidak lengkap.',
            ];
        }

        return JadwalKbm::checkConflict(
            $ptkId,
            $rombelId,
            $hari,
            $jamMulai,
            $jamSelesai,
            $ignoreId,
            $ruangan,
            $jamKeMulai,
            $jamKeSelesai,
            $sumber
        );
    }
}
