<?php

namespace App\Services;

use App\Support\Table;
use Illuminate\Support\Facades\DB;

/**
 * Sumber data GTK gabungan: Dapodik (`gtk`) + Non-ASN lokal (`gtk_non_asn`).
 *
 * Keduanya dipisah tabel agar tarik Dapodik (delete + insert pada `gtk`)
 * tidak menghapus data Non-ASN. Semua modul yang ingin menghitung
 * "guru + tendik + non-asn" cukup memakai service ini.
 */
class GtkGabunganService
{
    /** Status penanda baris yang berasal dari input lokal. */
    public const SUMBER_NON_ASN = 'non_asn';

    public function nonAsnTersedia(): bool
    {
        return Table::exists('gtk_non_asn');
    }

    /**
     * Ambil daftar Non-ASN sebagai objek dengan bentuk kolom setara `gtk`,
     * supaya bisa langsung digabung ke koleksi dari `gtk`.
     *
     * @param  bool  $aktifSaja  true = hanya status 'aktif'
     */
    public function nonAsn(bool $aktifSaja = true): \Illuminate\Support\Collection
    {
        if (!$this->nonAsnTersedia()) {
            return collect();
        }

        $query = DB::table('gtk_non_asn');

        if ($aktifSaja) {
            $query->where('status', 'aktif');
        }

        return $query->orderBy('nama')->get()->map(fn ($row) => $this->bentukSepertiGtk($row));
    }

    /**
     * Normalisasi satu baris gtk_non_asn agar kolomnya setara dengan `gtk`.
     */
    public function bentukSepertiGtk(object $row): object
    {
        return (object) [
            // ptk_id semu — tidak akan pernah bertabrakan dengan ptk_id Dapodik
            'ptk_id' => 'nonasn:' . $row->id,
            'nama' => $row->nama,
            'gelar_depan' => $row->gelar_depan,
            'gelar_belakang' => $row->gelar_belakang,
            'nuptk' => $row->nuptk,
            'nip' => $row->nip,
            'nik' => $row->nik,
            'jenis_kelamin' => $row->jenis_kelamin,
            'tempat_lahir' => $row->tempat_lahir,
            'tanggal_lahir' => $row->tanggal_lahir,
            'agama' => $row->agama,
            'jenis_ptk' => $row->jenis_ptk,
            'jabatan_ptk' => $row->jabatan,
            'status_kepegawaian' => $row->status_kepegawaian ?: 'Non-ASN',
            'pendidikan_terakhir' => $row->pendidikan_terakhir,
            'bidang_studi_terakhir' => $row->bidang_studi,
            'pangkat_golongan_terakhir' => null,
            'email' => $row->email,
            'no_hp' => $row->no_hp,
            'alamat_jalan' => $row->alamat,
            'sumber_data' => self::SUMBER_NON_ASN,
        ];
    }

    /**
     * Tandai baris `gtk` sebagai Dapodik (supaya seragam dengan hasil gabungan).
     */
    public function tandaiDapodik(iterable $rows): \Illuminate\Support\Collection
    {
        return collect($rows)->map(function ($row) {
            $row->sumber_data = 'dapodik';
            return $row;
        });
    }

    /**
     * Gabungkan hasil query `gtk` (sudah difilter pemanggil) dengan Non-ASN
     * yang lolos filter yang sama.
     *
     * @param  callable(object):bool|null  $filter  Penyaring seragam untuk kedua sumber
     */
    public function gabung(iterable $rowsDapodik, ?callable $filter = null, bool $aktifSaja = true): \Illuminate\Support\Collection
    {
        $dapodik = $this->tandaiDapodik($rowsDapodik);
        $nonAsn = $this->nonAsn($aktifSaja);

        $gabungan = $dapodik->concat($nonAsn);

        return $filter ? $gabungan->filter($filter)->values() : $gabungan->values();
    }

    /**
     * Apakah sebuah ptk_id palsu (Non-ASN)?
     */
    public static function isNonAsn(?string $ptkId): bool
    {
        return is_string($ptkId) && str_starts_with($ptkId, 'nonasn:');
    }

    /**
     * Ambil baris Non-ASN dari ptk_id semu (`nonasn:{id}`), null bila bukan.
     */
    public function dariPtkId(?string $ptkId): ?object
    {
        if (!self::isNonAsn($ptkId) || !$this->nonAsnTersedia()) {
            return null;
        }

        $row = DB::table('gtk_non_asn')->where('id', substr((string) $ptkId, 7))->first();

        return $row ? $this->bentukSepertiGtk($row) : null;
    }

    /**
     * Apakah jenis PTK Non-ASN tergolong Guru (bukan tendik)?
     *
     * Dipakai Guru Aktif (ambil yang true) dan Tendik Aktif (ambil yang false)
     * supaya satu baris tidak muncul di dua daftar.
     */
    public static function jenisAdalahGuru(?string $jenisPtk): bool
    {
        $jenis = strtolower((string) $jenisPtk);

        if ($jenis === '') {
            return false;
        }

        if (str_contains($jenis, 'tenaga kependidikan') || str_contains($jenis, 'tendik')) {
            return false;
        }

        return str_contains($jenis, 'guru') || str_contains($jenis, 'kepala sekolah');
    }

    /**
     * Pencarian seragam untuk baris Non-ASN (meniru filter `q` pada query `gtk`).
     */
    public static function cocokPencarian(object $row, string $q): bool
    {
        $q = strtolower($q);

        foreach (['nama', 'nuptk', 'nip', 'nik', 'jenis_ptk', 'jabatan', 'status_kepegawaian'] as $kolom) {
            if (!empty($row->{$kolom}) && str_contains(strtolower((string) $row->{$kolom}), $q)) {
                return true;
            }
        }

        return false;
    }
}
