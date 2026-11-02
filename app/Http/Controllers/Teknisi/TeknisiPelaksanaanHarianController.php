<?php

namespace App\Http\Controllers\Teknisi;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Pelaksanaan Harian — jurnal konfirmasi rencana Jadwal Area Tugas.
 *
 * Setiap rencana (teknisi_area_tugas) yang jatuh pada suatu tanggal dapat
 * dikonfirmasi "sudah dikerjakan" atau belum. Cakupan data mengikuti ruang PJ
 * akun login (lihat TeknisiBaseController::scopeRuangPj).
 *
 * Tab bawaan menampilkan yang BELUM dikerjakan; tab kedua menampilkan yang
 * sudah selesai. Filter: hari ini, periode (pagi/siang/sore, mingguan, dst).
 */
class TeknisiPelaksanaanHarianController extends TeknisiBaseController
{
    public const STATUS = [
        'belum'   => 'Belum Dikerjakan',
        'selesai' => 'Selesai',
    ];

    public const SHIFT = [
        'pagi'  => 'Pagi',
        'siang' => 'Siang',
        'sore'  => 'Sore',
    ];

    /** Label periode rencana (mengikuti Jadwal Area Tugas). */
    public const PERIODE = [
        'harian'   => 'Harian',
        'mingguan' => 'Mingguan',
        'bulanan'  => 'Bulanan',
        'semester' => 'Semester',
        'tahunan'  => 'Tahunan',
        'insidental' => 'Insidental',
    ];

    public function index(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $tab    = $request->query('tab') === 'selesai' ? 'selesai' : 'belum';
        $search = trim((string) $request->query('q', ''));
        $shift  = trim((string) $request->query('shift', ''));
        $periodeFilter = trim((string) $request->query('periode', ''));
        $tanggal = trim((string) $request->query('tanggal', ''));
        $khususHariIni = $request->query('hari_ini') === '1';
        $perPage = (int) $request->query('perPage', $request->query('per_page', 15));
        $perPage = in_array($perPage, [10, 15, 25, 50, 100], true) ? $perPage : 15;

        if ($khususHariIni) {
            $tanggal = now()->toDateString();
        }

        // Pastikan baris pelaksanaan hari ini untuk rencana yang jatuh hari ini
        // sudah tersedia (auto-generate), agar teknisi tinggal mengonfirmasi.
        $this->generateHariIni($context);

        $query = DB::table('teknisi_pelaksanaan_harian as p')->select('p.*');
        $this->scopeRuangPj($query, $context, 'ruang_id', 'p', true);
        $query->where('p.status', $tab);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('p.area', 'like', "%{$search}%")
                  ->orWhere('p.petugas_nama', 'like', "%{$search}%")
                  ->orWhere('p.uraian', 'like', "%{$search}%");
            });
        }
        if ($shift !== '')          $query->where('p.shift', $shift);
        if ($periodeFilter !== '')  $query->where('p.periode_jadwal', $periodeFilter);
        if ($tanggal !== '')        $query->where('p.tanggal', $tanggal);

        $statBase = DB::table('teknisi_pelaksanaan_harian');
        $this->scopeRuangPj($statBase, $context, 'ruang_id', null, true);
        $statTotal   = (clone $statBase)->count();
        $statBelum   = (clone $statBase)->where('status', 'belum')->count();
        $statSelesai = (clone $statBase)->where('status', 'selesai')->count();
        $statHariIni = (clone $statBase)->where('tanggal', now()->toDateString())->count();

        $list = $query->orderBy('p.tanggal')->orderBy('p.id')
            ->paginate($perPage)->withQueryString();

        $pjRuang = $this->pjNamaPerRuang($list->pluck('ruang_id')->all());

        // Rencana (jadwal area tugas) yang bisa dikonfirmasi manual.
        $rencanaQuery = DB::table('teknisi_area_tugas as a')
            ->select('a.id', 'a.area', 'a.periode_jadwal', 'a.shift')
            ->whereIn('a.status', ['aktif', 'draf'])->orderBy('a.area');
        $this->scopeRuangPj($rencanaQuery, $context, 'ruang_id', 'a', true);
        $rencanaOptions = $rencanaQuery->get();

        return view('dashboard.teknisi.pelaksanaan-harian', array_merge(
            compact('list', 'search', 'tab', 'shift', 'periodeFilter', 'tanggal', 'khususHariIni',
                'perPage', 'pjRuang', 'statTotal', 'statBelum', 'statSelesai', 'statHariIni', 'rencanaOptions'),
            $this->abilities(),
            [
                'shiftList'   => self::SHIFT,
                'periodeList' => self::PERIODE,
                'statusList'  => self::STATUS,
            ]
        ));
    }

    /**
     * Buat baris pelaksanaan untuk setiap rencana (jadwal area tugas) hari ini
     * yang belum punya konfirmasi. Aman dipanggil berulang (idempoten).
     */
    protected function generateHariIni(array $context): void
    {
        $today = now()->toDateString();

        $rencana = DB::table('teknisi_area_tugas as a')
            ->select('a.id', 'a.ruang_id', 'a.area', 'a.periode_jadwal', 'a.shift',
                'a.petugas_ptk_id', 'a.petugas_nama')
            ->whereIn('a.status', ['aktif', 'draf']);
        $this->scopeRuangPj($rencana, $context, 'ruang_id', 'a', true);
        // Hanya rencana yang sudah mulai (atau belum dijadwalkan) per hari ini.
        $rencana->where(function ($q) use ($today) {
            $q->whereNull('tanggal_mulai')->orWhere('tanggal_mulai', '<=', $today);
        });
        $rencana->where(function ($q) use ($today) {
            $q->whereNull('tanggal_selesai')->orWhere('tanggal_selesai', '>=', $today);
        });

        foreach ($rencana->get() as $r) {
            $ada = DB::table('teknisi_pelaksanaan_harian')
                ->where('area_tugas_id', $r->id)->where('tanggal', $today)->exists();
            if ($ada) {
                continue;
            }

            $pj = $this->pjUntukRuang($r->ruang_id ? (int) $r->ruang_id : null, $context);

            DB::table('teknisi_pelaksanaan_harian')->insert([
                'nomor_pelaksanaan' => $this->generateNomor('teknisi_pelaksanaan_harian', 'nomor_pelaksanaan', 'PLK'),
                'area_tugas_id'     => $r->id,
                'ruang_id'          => $r->ruang_id,
                'tanggal'           => $today,
                'shift'             => $r->shift ?: 'pagi',
                'area'              => $r->area,
                'periode_jadwal'    => $r->periode_jadwal,
                'petugas_ptk_id'    => $pj['ptk_id'] ?: $r->petugas_ptk_id,
                'petugas_nama'      => $pj['nama'] ?: $r->petugas_nama,
                'status'            => 'belum',
                'user_id'           => $context['user_id'],
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }

    /** Buat baris manual untuk rencana + tanggal tertentu (tombol "Tambah"). */
    public function store(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $data = $request->validate([
            'area_tugas_id' => 'required|integer',
            'tanggal'       => 'required|date',
            'shift'         => 'nullable|in:pagi,siang,sore',
        ]);

        $rencana = DB::table('teknisi_area_tugas')->where('id', $data['area_tugas_id'])->first();
        if (!$rencana) {
            return $this->respond($request, false, 'Rencana jadwal tidak ditemukan.', 404);
        }
        if (!$this->ownsRowRuang($rencana, $context)) {
            return $this->respond($request, false, 'Anda hanya dapat mengubah data pada ruang tanggung jawab Anda.', 403);
        }

        $ada = DB::table('teknisi_pelaksanaan_harian')
            ->where('area_tugas_id', $rencana->id)->where('tanggal', $data['tanggal'])->exists();
        if ($ada) {
            return $this->respond($request, false, 'Pelaksanaan untuk rencana & tanggal ini sudah ada.', 422);
        }

        $pj = $this->pjUntukRuang($rencana->ruang_id ? (int) $rencana->ruang_id : null, $context);

        DB::table('teknisi_pelaksanaan_harian')->insert([
            'nomor_pelaksanaan' => $this->generateNomor('teknisi_pelaksanaan_harian', 'nomor_pelaksanaan', 'PLK'),
            'area_tugas_id'     => $rencana->id,
            'ruang_id'          => $rencana->ruang_id,
            'tanggal'           => $data['tanggal'],
            'shift'             => $data['shift'] ?? $rencana->shift ?? 'pagi',
            'area'              => $rencana->area,
            'periode_jadwal'    => $rencana->periode_jadwal,
            'petugas_ptk_id'    => $pj['ptk_id'] ?: $rencana->petugas_ptk_id,
            'petugas_nama'      => $pj['nama'] ?: $rencana->petugas_nama,
            'status'            => 'belum',
            'user_id'           => $context['user_id'],
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        return $this->respond($request, true, 'Baris pelaksanaan berhasil dibuat.');
    }

    /** Perbarui detail konfirmasi (uraian/temuan/catatan + status). */
    public function update(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findRow('teknisi_pelaksanaan_harian', $id);
        if (!$row) return $this->respond($request, false, 'Data tidak ditemukan.', 404);
        if (!$this->ownsRowRuang($row, $context)) {
            return $this->respond($request, false, 'Anda hanya dapat mengubah data pada ruang tanggung jawab Anda.', 403);
        }

        $data = $request->validate([
            'status'  => 'nullable|in:belum,selesai',
            'uraian'  => 'nullable|string|max:2000',
            'temuan'  => 'nullable|string|max:1000',
            'catatan' => 'nullable|string|max:1000',
        ]);

        DB::table('teknisi_pelaksanaan_harian')->where('id', $id)->update([
            'status'     => $data['status'] ?? $row->status,
            'uraian'     => $data['uraian'] ?? null,
            'temuan'     => $data['temuan'] ?? null,
            'catatan'    => $data['catatan'] ?? null,
            'updated_at' => now(),
        ]);

        return $this->respond($request, true, 'Pelaksanaan tugas berhasil diperbarui.');
    }

    /** Ubah status saja (tombol konfirmasi "Selesai" / "Belum"). */
    public function status(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $data = $request->validate(['status' => 'required|in:belum,selesai']);

        $row = $this->findRow('teknisi_pelaksanaan_harian', $id);
        if (!$row) return $this->respond($request, false, 'Data tidak ditemukan.', 404);
        if (!$this->ownsRowRuang($row, $context)) {
            return $this->respond($request, false, 'Anda hanya dapat mengubah data pada ruang tanggung jawab Anda.', 403);
        }

        DB::table('teknisi_pelaksanaan_harian')->where('id', $id)->update([
            'status'     => $data['status'],
            'updated_at' => now(),
        ]);

        return $this->respond($request, true, 'Status pelaksanaan diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findRow('teknisi_pelaksanaan_harian', $id);
        if (!$row) return $this->respond($request, false, 'Data tidak ditemukan.', 404);
        if (!$this->ownsRowRuang($row, $context)) {
            return $this->respond($request, false, 'Anda hanya dapat menghapus data pada ruang tanggung jawab Anda.', 403);
        }

        DB::table('teknisi_pelaksanaan_harian')->where('id', $id)->delete();

        return $this->respond($request, true, 'Baris pelaksanaan berhasil dihapus.');
    }
}
