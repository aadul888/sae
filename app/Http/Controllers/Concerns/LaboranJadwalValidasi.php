<?php

namespace App\Http\Controllers\Concerns;

use App\Services\LaboranJadwalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Konstanta & tombol sakelar validasi penjadwalan praktik laboran.
 *
 * Dipisah dari controller agar berkas controller tetap di bawah batas 1.000
 * baris, dan agar pelonggaran sementara terlihat jelas di satu tempat.
 */
trait LaboranJadwalValidasi
{
    public const JENIS_LOKASI = LaboranJadwalService::JENIS_LOKASI;
    public const STATUS_LIST  = LaboranJadwalService::STATUS_LIST;

    /**
     * Ikatan jam praktik ke Jadwal KBM (kuota JP per mapel per hari).
     *
     * SEMENTARA DIMATIKAN atas permintaan pemilik modul: input jadwal praktik
     * tidak boleh ditolak karena mapel tidak ada di Jadwal KBM atau JP melebihi
     * kuota mapel. Ubah ke `true` untuk mengaktifkan lagi.
     */
    public const PAKSA_VALIDASI_KBM = false;

    /**
     * Pemeriksaan bentrok GURU pada jam beririsan antar kelas.
     *
     * SEMENTARA DIMATIKAN atas permintaan pemilik modul ("validasi ini juga di
     * hapus") karena kombinasi guru multi-kelas sering sah. Ubah ke `true`
     * untuk mengaktifkan lagi; kode pemeriksaannya sendiri tetap utuh di
     * `LaboranJadwalService::findConflictSingleDate()`.
     */
    public const PAKSA_VALIDASI_BENTROK = false;
    protected function mapelCocokKbm(object $row, array $kbmMapelKeys): bool
    {
        foreach ($this->service->kunciPencarianMapelKbm(
            (string) ($row->pembelajaran_id ?? ''),
            (string) ($row->nama_mata_pelajaran ?? '')
        ) as $kunci) {
            if (isset($kbmMapelKeys[$kunci])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Label jumlah JP pada dropdown guru & mapel: "(teralokasi JP / total JP)".
     *
     * Contoh: "(0 JP / 24 JP)" — 0 JP sudah dialokasikan praktik dari 24 JP
     * milik mapel tersebut di Jadwal KBM. Detail jam tersedia ditampilkan
     * terpisah sebagai tooltip agar daftar tetap ringkas.
     *
     * @param array{kuota:int,terpakai:int,sisa:int,jam_tersedia:array<int,int>} $kuota
     */
    protected function labelKetersediaanJam(array $kuota): string
    {
        if (empty($kuota['kuota'])) {
            return '';
        }

        return ' (' . (int) $kuota['terpakai'] . ' JP / ' . (int) $kuota['kuota'] . ' JP)';
    }

    /**
     * Tooltip dropdown guru & mapel: jam KBM dan jam yang masih tersedia.
     *
     * Contoh: "Jam KBM: ke-2–ke-4 • Jam tersedia: ke-2–ke-4".
     *
     * @param array{kuota:int,terpakai:int,sisa:int,jam_tersedia:array<int,int>} $kuota
     * @param array<int,array{mulai:int,selesai:int}> $blok
     */
    protected function tooltipKetersediaanJam(array $kuota, array $blok = []): string
    {
        $bagian = [];

        if ($blok) {
            $bagian[] = 'Jam KBM: ' . implode(', ', array_map(
                fn ($b) => 'ke-' . $b['mulai'] . ' s/d ke-' . $b['selesai'],
                $blok
            ));
        }

        $jam = array_values(array_filter(array_map('intval', $kuota['jam_tersedia'] ?? [])));
        $bagian[] = $jam
            ? 'Jam tersedia: ' . $this->ringkasJamKe($jam)
            : 'Jam tersedia: tidak ada jam tersisa';

        if (!empty($kuota['kuota'])) {
            $bagian[] = 'Terpakai ' . (int) $kuota['terpakai'] . ' dari ' . (int) $kuota['kuota'] . ' JP';
        }

        return implode(' • ', $bagian);
    }

    /**
     * Ringkas daftar jam berurutan: [2,3,4,9] => "ke-2–ke-4, ke-9".
     *
     * @param array<int,int> $jam
     */
    protected function ringkasJamKe(array $jam): string
    {
        sort($jam);
        $rentang = [];
        $awal = null;
        $sebelum = null;

        foreach ($jam as $k) {
            if ($awal === null) {
                $awal = $sebelum = $k;
                continue;
            }
            if ($k === $sebelum + 1) {
                $sebelum = $k;
                continue;
            }
            $rentang[] = $awal === $sebelum ? 'ke-' . $awal : 'ke-' . $awal . '–ke-' . $sebelum;
            $awal = $sebelum = $k;
        }
        if ($awal !== null) {
            $rentang[] = $awal === $sebelum ? 'ke-' . $awal : 'ke-' . $awal . '–ke-' . $sebelum;
        }

        return implode(', ', $rentang);
    }

    /**
     * AJAX: daftar guru (GTK) untuk memilih guru pendamping / tim pengajar.
     *
     * Dipakai modal jadwal agar satu sesi praktik bisa berisi lebih dari satu
     * guru pada lokasi dan jam yang sama.
     */
    public function daftarGuru(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $cari = trim((string) $request->query('q', ''));

        $rows = DB::table('gtk')
            ->select('ptk_id', 'nama')
            ->whereNotNull('nama')
            ->where('nama', '<>', '')
            ->when($cari !== '', fn ($q) => $q->where('nama', 'like', '%' . $cari . '%'))
            ->orderBy('nama')
            ->limit(1000)
            ->get();

        return response()->json([
            'status' => 'success',
            'guru'   => $rows->map(fn ($r) => [
                'ptk_id' => (string) $r->ptk_id,
                'nama'   => (string) $r->nama,
            ])->all(),
        ]);
    }

    /**
     * AJAX: slot jadwal KBM aktif untuk satu rombel (Point 1).
     */
    public function kbmByRombel(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $rombelId = trim((string) $request->query('rombel_id', ''));
        if ($rombelId === '') {
            return response()->json(['status' => 'success', 'kbm' => []]);
        }

        return response()->json([
            'status' => 'success',
            'kbm'    => $this->service->resolveKbmSlots($rombelId),
        ]);
    }

}
