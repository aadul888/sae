<?php

namespace App\Services\Concerns;

use App\Models\JadwalPengaturan;
use Illuminate\Support\Facades\DB;

/**
 * Logika Jadwal KBM & kuota JP untuk penjadwalan praktik Laboran.
 *
 * Dipisahkan dari `LaboranJadwalService` agar berkas tetap ringkas (<1.000 baris)
 * dan tanggung jawabnya jelas: sumber KBM yang berlaku, peta jam per mapel,
 * serta perhitungan kuota/sisa JP.
 */
trait LaboranJadwalKbmKuota
{
    /**
     * Sumber Jadwal KBM yang sedang DIBERLAKUKAN ('otomatis' | 'manual').
     *
     * Jadwal KBM dapat dibuat otomatis oleh penjadwal maupun diinput manual.
     * Yang menjadi acuan hanya salah satu sumber yang sedang diberlakukan
     * (lihat `JadwalPengaturan::getModeAktif()`); bila belum ada yang
     * diberlakukan, default-nya 'otomatis' — sama seperti modul KBM lain.
     */
    public function sumberKbmAktif(): string
    {
        $sumber = JadwalPengaturan::getModeAktif();

        return in_array($sumber, ['otomatis', 'manual'], true) ? $sumber : 'otomatis';
    }

    /**
     * Daftar mata pelajaran yang TERJADWAL di Jadwal KBM untuk satu rombel
     * pada satu hari, dikembalikan sebagai himpunan kunci pembanding
     * (`id:{pembelajaran_id}` atau `nama:{nama mapel}` bila id tak tersedia).
     *
     * Dipakai untuk membatasi pilihan guru & mapel di modal penjadwalan
     * laboran supaya hanya mapel yang memang ada di KBM yang bisa dipilih.
     * Hanya sumber KBM yang sedang diberlakukan yang dihitung, dan slot
     * rutinitas (ROUTINE_*) selalu dikecualikan.
     *
     * @return array<string, bool> himpunan kunci mapel (untuk isset())
     */
    public function mapelTerjadwalKbm(string $rombelId, string $hari): array
    {
        $rombelId = trim($rombelId);
        $hari = trim($hari);
        if ($rombelId === '' || $hari === '') {
            return [];
        }

        $rows = DB::table('jadwal_kbm')
            ->where('rombongan_belajar_id', $rombelId)
            ->where('hari', $hari)
            ->where('sumber', $this->sumberKbmAktif())
            ->where('is_active', true)
            ->where('pembelajaran_id', 'not like', 'ROUTINE_%')
            ->select('pembelajaran_id', 'nama_mata_pelajaran')
            ->get();

        $keys = [];
        foreach ($rows as $r) {
            foreach ($this->kunciPencarianMapelKbm(
                $r->pembelajaran_id ?? '',
                $r->nama_mata_pelajaran ?? ''
            ) as $kunci) {
                $keys[$kunci] = true;
            }
        }

        return $keys;
    }

    /**
     * Kunci pembanding antara baris `pembelajaran` dan `jadwal_kbm`.
     *
     * `jadwal_kbm` bisa menyimpan mapel sebagai `pembelajaran_id` maupun hanya
     * nama mapel, tergantung sumbernya. Karena itu KEDUA bentuk kunci
     * dikembalikan agar pencocokan tetap berhasil walaupun salah satu sisi
     * tidak lengkap.
     *
     * @return array<int,string>
     */
    public function kunciPencarianMapelKbm(?string $pembelajaranId, ?string $namaMapel): array
    {
        $kunci = [];

        $pid = trim((string) $pembelajaranId);
        if ($pid !== '' && !str_starts_with($pid, 'ROUTINE_')) {
            $kunci[] = 'id:' . $pid;
        }

        $nama = strtolower(trim((string) $namaMapel));
        if ($nama !== '') {
            $kunci[] = 'nama:' . preg_replace('/\s+/', ' ', $nama);
        }

        return $kunci;
    }

    /**
     * Rentang Jam ke-N tempat setiap mata pelajaran terjadwal di Jadwal KBM
     * untuk satu rombel pada satu hari.
     *
     * Dipakai modal penjadwalan laboran agar pilihan "Jam ke-N s/d Jam ke-M"
     * mengikuti jam mapel pada Jadwal KBM (bukan seluruh slot hari itu) —
     * termasuk bila satu mapel dipecah menjadi beberapa blok jam.
     *
     * Hanya sumber KBM yang sedang diberlakukan yang dihitung (`sumberKbmAktif()`),
     * dan slot rutinitas (ROUTINE_*) selalu dikecualikan.
     *
     * @return array<string, array{jam_ke: array<int,int>, blok: array<int, array{mulai:int, selesai:int}>}>
     *         Dipetakan dengan kunci `id:{pembelajaran_id}` dan `nama:{nama mapel}`.
     */
    public function jamKeMapelKbm(string $rombelId, string $hari): array
    {
        $rombelId = trim($rombelId);
        $hari = trim($hari);
        if ($rombelId === '' || $hari === '') {
            return [];
        }

        $rows = DB::table('jadwal_kbm')
            ->where('rombongan_belajar_id', $rombelId)
            ->where('hari', $hari)
            ->where('sumber', $this->sumberKbmAktif())
            ->where('is_active', true)
            ->where('pembelajaran_id', 'not like', 'ROUTINE_%')
            ->select('pembelajaran_id', 'nama_mata_pelajaran', 'jam_ke_mulai', 'jam_ke_selesai')
            ->orderBy('jam_ke_mulai')
            ->get();

        $blokPerMapel = [];
        foreach ($rows as $r) {
            $awal = (int) ($r->jam_ke_mulai ?? 0);
            if ($awal <= 0) {
                continue;
            }
            $akhir = (int) ($r->jam_ke_selesai ?? 0);
            if ($akhir < $awal) {
                $akhir = $awal;
            }

            // Dedupe blok yang sama (bisa muncul dari beberapa baris KBM).
            foreach ($this->kunciPencarianMapelKbm(
                $r->pembelajaran_id ?? '',
                $r->nama_mata_pelajaran ?? ''
            ) as $kunci) {
                $blokPerMapel[$kunci][$awal . '-' . $akhir] = [$awal, $akhir];
            }
        }

        $hasil = [];
        foreach ($blokPerMapel as $kunci => $blok) {
            $blok = array_values($blok);
            usort($blok, fn ($a, $b) => $a[0] <=> $b[0]);

            $jamKe = [];
            foreach ($blok as $b) {
                for ($i = $b[0]; $i <= $b[1]; $i++) {
                    $jamKe[$i] = $i;
                }
            }

            $hasil[$kunci] = [
                'jam_ke' => array_values($jamKe),
                'blok'   => array_map(
                    fn ($b) => ['mulai' => $b[0], 'selesai' => $b[1]],
                    $blok
                ),
            ];
        }

        return $hasil;
    }

    /**
     * Pastikan total JP praktik untuk satu pembelajaran pada tanggal tertentu
     * tidak melebihi jumlah JP mapel tersebut di Jadwal KBM pada hari yang sama.
     *
     * @return string|null Pesan kesalahan, atau null bila aman.
     */
    public function findKuotaOverflow(array $data, string $hari, ?int $ignoreId = null, ?string $tanggal = null): ?string
    {
        $pembelajaranId = trim((string) ($data['pembelajaran_id'] ?? ''));
        $rombelId       = trim((string) ($data['rombel_id'] ?? ''));
        if ($pembelajaranId === '' || $rombelId === '') {
            return null;
        }

        $tanggal = $tanggal ?? (string) ($data['tanggal'] ?? '');

        $kuotaJp = $this->kuotaJpMapelKbm($rombelId, $hari, $pembelajaranId);

        if ($kuotaJp <= 0) {
            // Wajib hari KBM: mapel harus punya jadwal KBM pada hari tersebut,
            // jika tidak, praktik tidak sah dijadwalkan di hari tanpa jadwal mapel.
            return 'Mapel ini tidak memiliki jadwal KBM pada hari ' . $hari
                . ', sehingga praktik tidak dapat dijadwalkan pada hari tersebut.';
        }

        $diminta = $this->durasiJp($data['jam_mulai'] ?? null, $data['jam_selesai'] ?? null, $hari);
        if ($diminta <= 0) {
            return null;
        }

        // JP praktik yang sudah ada pada tanggal yang sama.
        $terpakai = $this->jpTerpakaiPraktik($rombelId, $pembelajaranId, $tanggal, $ignoreId);

        if ($terpakai + $diminta <= $kuotaJp) {
            return null;
        }

        return 'Alokasi JP melebihi jadwal KBM: mapel ini hanya ' . $kuotaJp . ' JP pada hari ' . $hari
            . ', sudah terpakai ' . $terpakai . ' JP, permintaan ' . $diminta . ' JP.';
    }

    /**
     * Kuota JP satu mapel pada Jadwal KBM (kelas + hari, sumber yang berlaku).
     */
    public function kuotaJpMapelKbm(string $rombelId, string $hari, string $pembelajaranId): int
    {
        if ($rombelId === '' || $hari === '' || $pembelajaranId === '') {
            return 0;
        }

        return (int) DB::table('jadwal_kbm')
            ->where('rombongan_belajar_id', $rombelId)
            ->where('pembelajaran_id', $pembelajaranId)
            ->where('hari', $hari)
            ->where('sumber', $this->sumberKbmAktif())
            ->where('is_active', true)
            ->sum(DB::raw('GREATEST(1, jam_ke_selesai - jam_ke_mulai + 1)'));
    }

    /**
     * JP praktik yang sudah terpakai untuk satu mapel pada satu tanggal.
     */
    public function jpTerpakaiPraktik(string $rombelId, string $pembelajaranId, string $tanggal, ?int $ignoreId = null): int
    {
        if ($rombelId === '' || $pembelajaranId === '' || $tanggal === '') {
            return 0;
        }

        $hari = $this->hariDariTanggal($tanggal);

        $query = DB::table('laboran_jadwal_penggunaan')
            ->where('rombel_id', $rombelId)
            ->where('pembelajaran_id', $pembelajaranId)
            ->where('tanggal', $tanggal)
            ->where('status', '<>', 'batal');
        if ($ignoreId) {
            $query->where('id', '<>', $ignoreId);
        }

        $terpakai = 0;
        foreach ($query->get(['id', 'jam_mulai', 'jam_selesai']) as $row) {
            if ($hari) {
                $terpakai += $this->durasiJp($row->jam_mulai, $row->jam_selesai, $hari);
            }
        }

        return $terpakai;
    }

    /**
     * Ringkasan ketersediaan JP satu mapel untuk dropdown guru & mapel di modal.
     *
     * `jam_tersedia` = JP milik mapel pada Jadwal KBM yang BELUM terpakai praktik
     * pada tanggal tersebut — inilah jam yang masih bisa dipilih laboran.
     *
     * @return array{kuota:int, terpakai:int, sisa:int, jam_tersedia:array<int,int>}
     */
    public function ringkasanKuotaMapelKbm(
        string $rombelId,
        string $hari,
        string $pembelajaranId,
        ?string $tanggal = null,
        ?int $ignoreId = null
    ): array {
        $kuota = $this->kuotaJpMapelKbm($rombelId, $hari, $pembelajaranId);
        $terpakai = 0;

        if ($kuota > 0 && $tanggal) {
            $terpakai = $this->jpTerpakaiPraktik($rombelId, $pembelajaranId, $tanggal, $ignoreId);
        }

        $jamKbm = $this->jamKeMapelKbmByPembelajaran($rombelId, $hari, $pembelajaranId);
        $jamTerpakai = [];
        if ($tanggal && $jamKbm) {
            $jamTerpakai = $this->jamKeTerpakaiPraktik(
                $rombelId,
                $pembelajaranId,
                $tanggal,
                $hari,
                $ignoreId
            );
        }

        $jamTersedia = array_values(array_diff($jamKbm, $jamTerpakai));

        return [
            'kuota'        => $kuota,
            'terpakai'     => $terpakai,
            'sisa'         => max(0, $kuota - $terpakai),
            'jam_tersedia' => $jamTersedia,
        ];
    }

    /**
     * Ringkasan ketersediaan JP untuk BANYAK mapel sekaligus (satu query per tabel).
     *
     * Dipakai dropdown guru & mapel agar bisa menampilkan jam tersedia / sisa JP
     * tanpa N+1 query.
     *
     * @param array<int,string> $pembelajaranIds
     * @return array<string, array{kuota:int, terpakai:int, sisa:int, jam_tersedia:array<int,int>}>
     */
    public function ringkasanKuotaPerMapelKbm(
        string $rombelId,
        string $hari,
        array $pembelajaranIds,
        ?string $tanggal = null,
        ?int $ignoreId = null
    ): array {
        $ids = array_values(array_unique(array_filter(array_map(
            fn ($v) => trim((string) $v),
            $pembelajaranIds
        ), fn ($v) => $v !== '')));

        $hasil = [];
        foreach ($ids as $id) {
            $hasil[$id] = ['kuota' => 0, 'terpakai' => 0, 'sisa' => 0, 'jam_tersedia' => []];
        }
        if (!$ids || $rombelId === '' || $hari === '') {
            return $hasil;
        }

        // 1) Kuota & jam per mapel dari Jadwal KBM (satu query).
        $kbmRows = DB::table('jadwal_kbm')
            ->where('rombongan_belajar_id', $rombelId)
            ->where('hari', $hari)
            ->where('sumber', $this->sumberKbmAktif())
            ->where('is_active', true)
            ->whereIn('pembelajaran_id', $ids)
            ->orderBy('jam_ke_mulai')
            ->get(['pembelajaran_id', 'jam_ke_mulai', 'jam_ke_selesai']);

        $jamKbm = [];
        foreach ($kbmRows as $r) {
            $awal = (int) ($r->jam_ke_mulai ?? 0);
            if ($awal <= 0) {
                continue;
            }
            $akhir = max($awal, (int) ($r->jam_ke_selesai ?? $awal));
            $pid = (string) $r->pembelajaran_id;

            for ($i = $awal; $i <= $akhir; $i++) {
                $jamKbm[$pid][$i] = $i;
            }
        }

        // 2) JP praktik yang sudah terpakai pada tanggal tersebut (satu query).
        $jamTerpakai = [];
        $menitTerpakai = [];
        if ($tanggal) {
            $query = DB::table('laboran_jadwal_penggunaan')
                ->where('rombel_id', $rombelId)
                ->where('tanggal', $tanggal)
                ->whereIn('pembelajaran_id', $ids)
                ->where('status', '<>', 'batal');
            if ($ignoreId) {
                $query->where('id', '<>', $ignoreId);
            }

            foreach ($query->get(['pembelajaran_id', 'jam_mulai', 'jam_selesai']) as $row) {
                $pid = (string) $row->pembelajaran_id;
                foreach ($this->jamKeDariRentang($row->jam_mulai, $row->jam_selesai, $hari) as $k) {
                    $jamTerpakai[$pid][$k] = $k;
                }
                $menitTerpakai[$pid][] = [$row->jam_mulai, $row->jam_selesai];
            }
        }

        foreach ($ids as $id) {
            $jam = $jamKbm[$id] ?? [];
            ksort($jam);
            $jam = array_values($jam);

            $kuota = count($jam);

            $terpakai = 0;
            foreach ($menitTerpakai[$id] ?? [] as $rentang) {
                $terpakai += $this->durasiJp($rentang[0], $rentang[1], $hari);
            }

            $terpakaiJam = array_values($jamTerpakai[$id] ?? []);
            $hasil[$id] = [
                'kuota'        => $kuota,
                'terpakai'     => $terpakai,
                'sisa'         => max(0, $kuota - $terpakai),
                'jam_tersedia' => array_values(array_diff($jam, $terpakaiJam)),
            ];
        }

        return $hasil;
    }

    /**
     * JP milik satu pembelajaran pada Jadwal KBM (kelas + hari, sumber berlaku).
     *
     * @return array<int,int>
     */
    public function jamKeMapelKbmByPembelajaran(string $rombelId, string $hari, string $pembelajaranId): array
    {
        if ($rombelId === '' || $hari === '' || $pembelajaranId === '') {
            return [];
        }

        $rows = DB::table('jadwal_kbm')
            ->where('rombongan_belajar_id', $rombelId)
            ->where('pembelajaran_id', $pembelajaranId)
            ->where('hari', $hari)
            ->where('sumber', $this->sumberKbmAktif())
            ->where('is_active', true)
            ->orderBy('jam_ke_mulai')
            ->get(['jam_ke_mulai', 'jam_ke_selesai']);

        $jam = [];
        foreach ($rows as $r) {
            $awal = (int) ($r->jam_ke_mulai ?? 0);
            if ($awal <= 0) {
                continue;
            }
            $akhir = max($awal, (int) ($r->jam_ke_selesai ?? $awal));
            for ($i = $awal; $i <= $akhir; $i++) {
                $jam[$i] = $i;
            }
        }
        ksort($jam);

        return array_values($jam);
    }

    /**
     * JP yang sudah terpakai praktik untuk satu mapel pada satu tanggal.
     *
     * @return array<int,int>
     */
    public function jamKeTerpakaiPraktik(
        string $rombelId,
        string $pembelajaranId,
        string $tanggal,
        string $hari,
        ?int $ignoreId = null
    ): array {
        if ($rombelId === '' || $pembelajaranId === '' || $tanggal === '') {
            return [];
        }

        $query = DB::table('laboran_jadwal_penggunaan')
            ->where('rombel_id', $rombelId)
            ->where('pembelajaran_id', $pembelajaranId)
            ->where('tanggal', $tanggal)
            ->where('status', '<>', 'batal');
        if ($ignoreId) {
            $query->where('id', '<>', $ignoreId);
        }

        $jam = [];
        foreach ($query->get(['jam_mulai', 'jam_selesai']) as $row) {
            foreach ($this->jamKeDariRentang($row->jam_mulai, $row->jam_selesai, $hari) as $k) {
                $jam[$k] = $k;
            }
        }
        ksort($jam);

        return array_values($jam);
    }

    /**
     * Daftar JP (nomor jam pelajaran) yang dicakup sebuah rentang jam.
     *
     * @return array<int,int>
     */
    public function jamKeDariRentang($jamMulai, $jamSelesai, string $hari): array
    {
        $mulai = substr((string) $jamMulai, 0, 5);
        $selesai = substr((string) $jamSelesai, 0, 5);
        if ($mulai === '' || $selesai === '' || $selesai <= $mulai) {
            return [];
        }

        $jam = [];
        foreach (JadwalPengaturan::getSlots($hari) as $ke => $slot) {
            if (empty($slot['schedulable'])) {
                continue;
            }
            if ($mulai < $slot['selesai'] && $selesai > $slot['mulai']) {
                $jam[(int) $ke] = (int) $ke;
            }
        }
        ksort($jam);

        return array_values($jam);
    }

    /**
     * Hitung jumlah JP yang dicakup rentang jam tertentu pada satu hari.
     *
     * Hanya slot yang dapat dijadwalkan (schedulable) yang dihitung, karena
     * JP pada Jadwal KBM tidak pernah memuat jam rutinitas (Upacara,
     * Pembiasaan, Istirahat). Bila rentang seluruhnya berada di dalam slot
     * rutinitas, hitungan jatuh ke jumlah slot yang beririsan agar tidak nol.
     */
    protected function durasiJp($jamMulai, $jamSelesai, string $hari): int
    {
        $mulai = substr((string) $jamMulai, 0, 5);
        $selesai = substr((string) $jamSelesai, 0, 5);
        if ($mulai === '' || $selesai === '' || $selesai <= $mulai) {
            return 0;
        }

        $jp = 0;
        $beririsan = 0;
        foreach (JadwalPengaturan::getSlots($hari) as $slot) {
            if ($mulai < $slot['selesai'] && $selesai > $slot['mulai']) {
                $beririsan++;
                if (!empty($slot['schedulable'])) {
                    $jp++;
                }
            }
        }

        return $jp > 0 ? $jp : $beririsan;
    }
}
