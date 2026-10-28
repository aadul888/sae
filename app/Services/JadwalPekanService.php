<?php

namespace App\Services;

use App\Models\JadwalKbmPekan;
use App\Models\JadwalPengaturan;
use App\Models\KalenderPendidikan;
use App\Support\Table;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Pengelola pemberlakuan jadwal per pekan (lapis 2).
 *
 * Pekan diturunkan otomatis dari rentang semester pada Kalender Pendidikan
 * (pekan 1 = minggu yang memuat tanggal awal semester, Senin–Sabtu), lalu
 * disimpan agar rentangnya bisa dikoreksi operator. Status awal selalu
 * `draft` supaya pekan baru tidak langsung terlihat oleh guru/siswa.
 */
class JadwalPekanService
{
    /** Format tanggal standar untuk query & validasi. */
    public const DATE_FORMAT = 'Y-m-d';

    /**
     * Rentang semester aktif yang dipakai sebagai dasar pembentukan pekan.
     *
     * Bila semester tidak diberikan, diturunkan dari bulan berjalan
     * (Juli–Desember = 1, Januari–Juni = 2) agar rentang pekan tidak
     * melebar menjadi satu tahun ajaran penuh.
     *
     * @return array{tahun_ajaran:string, semester:string, start:string, end:string, label:string}
     */
    public function resolvePeriode(?string $tahunAjaran = null, ?string $semester = null): array
    {
        $semester = $semester ?: ((int) now()->month >= 7 ? '1' : '2');

        if (!$tahunAjaran) {
            $tahun = (int) now()->year;
            $tahunAjaran = (int) now()->month >= 7 ? "{$tahun}/" . ($tahun + 1) : ($tahun - 1) . "/{$tahun}";
        }

        $periode = KalenderPendidikan::resolvePeriodeDates($tahunAjaran, $semester);

        return [
            'tahun_ajaran' => $periode['tahun_ajaran'],
            'semester'     => (string) ($periode['semester'] === 'semua' ? '1' : $periode['semester']),
            'start'        => $periode['start'],
            'end'          => $periode['end'],
            'label'        => $periode['label'],
        ];
    }

    /**
     * Daftar pekan tersimpan untuk satu periode, urut pekan_ke.
     *
     * @return \Illuminate\Support\Collection<int, JadwalKbmPekan>
     */
    public function daftarPekan(string $tahunAjaran, string $semester)
    {
        if (!Table::exists('jadwal_kbm_pekan')) {
            return collect();
        }

        return JadwalKbmPekan::periode($tahunAjaran, $semester)
            ->orderBy('pekan_ke')
            ->get();
    }

    /**
     * Bentuk rentang pekan dari tanggal awal & akhir semester.
     *
     * Aturan: pekan dihitung Senin–Sabtu (7 hari), dimulai dari Senin pada
     * minggu yang memuat tanggal awal semester. Sisa hari pada pekan terakhir
     * dipotong pada tanggal akhir semester.
     *
     * @return array<int, array{pekan_ke:int, tanggal_mulai:string, tanggal_selesai:string}>
     */
    public function buildRentangPekan(string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        // Geser ke Senin pada minggu yang sama dengan tanggal awal semester.
        $cursor = $start->copy()->startOfWeek(Carbon::MONDAY);

        $rentang = [];
        $pekanKe = 1;

        while ($cursor->lte($end)) {
            $pekanMulai = $cursor->copy();
            $pekanSelesai = $cursor->copy()->addDays(6);

            // Pekan pertama dipotong di tanggal awal semester, pekan terakhir di tanggal akhir.
            if ($pekanMulai->lt($start)) {
                $pekanMulai = $start->copy();
            }
            if ($pekanSelesai->gt($end)) {
                $pekanSelesai = $end->copy();
            }

            $rentang[] = [
                'pekan_ke'        => $pekanKe,
                'tanggal_mulai'   => $pekanMulai->toDateString(),
                'tanggal_selesai' => $pekanSelesai->toDateString(),
            ];

            $pekanKe++;
            $cursor->addWeek();
        }

        return $rentang;
    }

    /**
     * Sinkronkan pekan periode: buat yang belum ada, perbarui tanggal pekan
     * yang masih `draft`, dan biarkan pekan yang sudah diberlakukan apa adanya.
     *
     * @return array{created:int, updated:int, skipped:int, total:int}
     */
    public function generatePekan(string $tahunAjaran, string $semester, bool $perbaruiDraft = true): array
    {
        $periode = $this->resolvePeriode($tahunAjaran, $semester);
        $rentang = $this->buildRentangPekan($periode['start'], $periode['end']);

        if (empty($rentang)) {
            return ['created' => 0, 'updated' => 0, 'skipped' => 0, 'total' => 0];
        }

        $existing = $this->daftarPekan($periode['tahun_ajaran'], $periode['semester'])
            ->keyBy('pekan_ke');

        $created = 0;
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($periode, $rentang, $existing, $perbaruiDraft, &$created, &$updated, &$skipped) {
            foreach ($rentang as $row) {
                $pekan = $existing->get($row['pekan_ke']);

                if ($pekan) {
                    $bolehDiperbarui = $perbaruiDraft && $pekan->status === 'draft';
                    $tanggalBerubah = $pekan->tanggal_mulai->toDateString() !== $row['tanggal_mulai']
                        || $pekan->tanggal_selesai->toDateString() !== $row['tanggal_selesai'];

                    if ($bolehDiperbarui && $tanggalBerubah) {
                        $pekan->update([
                            'tanggal_mulai'   => $row['tanggal_mulai'],
                            'tanggal_selesai' => $row['tanggal_selesai'],
                            'is_efektif'      => $this->hitungEfektif($row['tanggal_mulai'], $row['tanggal_selesai']),
                        ]);
                        $updated++;
                    } else {
                        $skipped++;
                    }

                    continue;
                }

                JadwalKbmPekan::create([
                    'tahun_ajaran'    => $periode['tahun_ajaran'],
                    'semester'        => $periode['semester'],
                    'pekan_ke'        => $row['pekan_ke'],
                    'tanggal_mulai'   => $row['tanggal_mulai'],
                    'tanggal_selesai' => $row['tanggal_selesai'],
                    'sumber'          => null,
                    'status'          => 'draft',
                    'is_efektif'      => $this->hitungEfektif($row['tanggal_mulai'], $row['tanggal_selesai']),
                ]);
                $created++;
            }
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'total'   => count($rentang),
        ];
    }

    /**
     * Ubah satu pekan (rentang tanggal, sumber, status, catatan).
     * Melempar InvalidArgumentException bila data tidak valid.
     */
    public function updatePekan(int $id, array $data, ?string $userIdentifier = null): JadwalKbmPekan
    {
        $pekan = JadwalKbmPekan::find($id);

        if (!$pekan) {
            throw new \InvalidArgumentException('Pekan tidak ditemukan.');
        }

        $mulai = $data['tanggal_mulai'] ?? $pekan->tanggal_mulai->toDateString();
        $selesai = $data['tanggal_selesai'] ?? $pekan->tanggal_selesai->toDateString();

        if (Carbon::parse($mulai)->gt(Carbon::parse($selesai))) {
            throw new \InvalidArgumentException('Tanggal mulai pekan tidak boleh melewati tanggal selesai.');
        }

        if (isset($data['status']) && !in_array($data['status'], JadwalKbmPekan::STATUS_LIST, true)) {
            throw new \InvalidArgumentException('Status pekan tidak dikenal.');
        }

        if (isset($data['sumber']) && !in_array($data['sumber'], ['otomatis', 'manual', ''], true)) {
            throw new \InvalidArgumentException('Sumber pekan tidak dikenal.');
        }

        $this->pastikanTidakTumpangTindih($pekan, $mulai, $selesai);

        $payload = [
            'tanggal_mulai'   => $mulai,
            'tanggal_selesai' => $selesai,
            'status'          => $data['status'] ?? $pekan->status,
            'sumber'          => array_key_exists('sumber', $data)
                ? (trim((string) $data['sumber']) ?: null)
                : $pekan->sumber,
            'catatan'         => $data['catatan'] ?? $pekan->catatan,
            'is_efektif'      => array_key_exists('is_efektif', $data)
                ? filter_var($data['is_efektif'], FILTER_VALIDATE_BOOLEAN)
                : $pekan->is_efektif,
        ];

        $this->terapkanStatusPelaporan($payload, $pekan, $userIdentifier);

        $pekan->update($payload);

        return $pekan->refresh();
    }

    /**
     * Ubah status pemberlakuan satu pekan dengan validasi transisi.
     */
    public function ubahStatus(int $id, string $status, ?string $userIdentifier = null): JadwalKbmPekan
    {
        if (!in_array($status, JadwalKbmPekan::STATUS_LIST, true)) {
            throw new \InvalidArgumentException('Status pekan tidak dikenal.');
        }

        $pekan = JadwalKbmPekan::find($id);
        if (!$pekan) {
            throw new \InvalidArgumentException('Pekan tidak ditemukan.');
        }

        $payload = ['status' => $status];
        $this->terapkanStatusPelaporan($payload, $pekan, $userIdentifier);

        $pekan->update($payload);

        return $pekan->refresh();
    }

    /**
     * Pekan yang berlaku (aktif/libur/ujian) pada tanggal tertentu.
     */
    public function getPekanAktif(string $tanggal, ?string $tahunAjaran = null, ?string $semester = null): ?JadwalKbmPekan
    {
        if (!Table::exists('jadwal_kbm_pekan')) {
            return null;
        }

        $query = JadwalKbmPekan::query()->mencakupTanggal($tanggal);

        if ($tahunAjaran && $semester) {
            $query->periode($tahunAjaran, $semester);
        }

        return $query->orderBy('pekan_ke')->first();
    }

    /**
     * Peta pekan per tanggal untuk satu rentang (dipakai ekspansi bulanan).
     *
     * @return array<string, JadwalKbmPekan>
     */
    public function petaPekanPerTanggal(string $startDate, string $endDate, ?string $tahunAjaran = null, ?string $semester = null): array
    {
        if (!Table::exists('jadwal_kbm_pekan')) {
            return [];
        }

        $query = JadwalKbmPekan::query()
            ->where('tanggal_mulai', '<=', $endDate)
            ->where('tanggal_selesai', '>=', $startDate);

        if ($tahunAjaran && $semester) {
            $query->periode($tahunAjaran, $semester);
        }

        $map = [];
        foreach ($query->orderBy('pekan_ke')->get() as $pekan) {
            $cursor = Carbon::parse(max($pekan->tanggal_mulai->toDateString(), $startDate));
            $batas = Carbon::parse(min($pekan->tanggal_selesai->toDateString(), $endDate));

            while ($cursor->lte($batas)) {
                $map[$cursor->toDateString()] = $pekan;
                $cursor->addDay();
            }
        }

        return $map;
    }

    /**
     * Ringkasan status pekan untuk badge banner.
     *
     * @return array{total:int, draft:int, aktif:int, libur:int, ujian:int, aktif_ke:?int, aktif_rentang:?string}
     */
    public function ringkasan(string $tahunAjaran, string $semester, ?string $tanggalAcuan = null): array
    {
        $list = $this->daftarPekan($tahunAjaran, $semester);
        $tanggal = $tanggalAcuan ?: now()->toDateString();

        $ringkasan = [
            'total' => $list->count(),
            'draft' => 0,
            'aktif' => 0,
            'libur' => 0,
            'ujian' => 0,
            'aktif_ke' => null,
            'aktif_rentang' => null,
        ];

        foreach ($list as $pekan) {
            $status = (string) $pekan->status;
            if (array_key_exists($status, $ringkasan)) {
                $ringkasan[$status]++;
            }

            if ($pekan->mencakupTanggal($tanggal)) {
                $ringkasan['aktif_ke'] = $pekan->pekan_ke;
                $ringkasan['aktif_rentang'] = $pekan->rentang_label;
            }
        }

        return $ringkasan;
    }

    /**
     * Sumber jadwal yang berlaku pada tanggal tertentu dengan
     * mempertimbangkan override per pekan.
     */
    public function sumberBerlakuPada(string $tanggal, ?string $tahunAjaran = null, ?string $semester = null): string
    {
        $global = JadwalPengaturan::getModeAktif() ?: 'otomatis';
        $pekan = $this->getPekanAktif($tanggal, $tahunAjaran, $semester);

        return $pekan ? $pekan->resolveSumber($global) : $global;
    }

    /**
     * Hitung jumlah hari efektif satu rentang pekan memakai Kalender Pendidikan.
     */
    public function hitungEfektif(string $startDate, string $endDate): bool
    {
        return KalenderPendidikan::hitungHariEfektif($startDate, $endDate, 'pd') > 0;
    }

    /**
     * Lengkapi payload status: isi jejak pemberlakuan saat pekan aktif,
     * bersihkan saat dikembalikan ke draft.
     */
    protected function terapkanStatusPelaporan(array &$payload, JadwalKbmPekan $pekan, ?string $userIdentifier): void
    {
        $statusBaru = $payload['status'] ?? $pekan->status;

        if ($statusBaru === 'draft') {
            $payload['diberlakukan_pada'] = null;
            $payload['diberlakukan_oleh'] = null;

            return;
        }

        if ($pekan->status === 'draft' || !$pekan->diberlakukan_pada) {
            $payload['diberlakukan_pada'] = now();
            $payload['diberlakukan_oleh'] = $userIdentifier;
        }
    }

    /**
     * Cegah dua pekan dalam periode yang sama saling menimpa tanggal.
     */
    protected function pastikanTidakTumpangTindih(JadwalKbmPekan $pekan, string $mulai, string $selesai): void
    {
        $bentrok = JadwalKbmPekan::periode($pekan->tahun_ajaran, $pekan->semester)
            ->where('id', '!=', $pekan->id)
            ->where('tanggal_mulai', '<=', $selesai)
            ->where('tanggal_selesai', '>=', $mulai)
            ->orderBy('pekan_ke')
            ->first();

        if ($bentrok) {
            throw new \InvalidArgumentException(
                "Rentang tanggal bertabrakan dengan pekan ke-{$bentrok->pekan_ke} ({$bentrok->rentang_label})."
            );
        }
    }
}
