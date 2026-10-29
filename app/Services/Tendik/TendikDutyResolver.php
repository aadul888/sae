<?php

namespace App\Services\Tendik;

use App\Models\RolePermission;
use App\Support\Table;

/**
 * Resolusi bidang tugas & hak akses dashboard Tendik.
 * ponytail: array konstan inline; pindah ke config bila perlu diubah operator.
 */
class TendikDutyResolver
{
    public const DUTY_CODES = [
        'KEPALA_TAS',
        'STAF_PERSURATAN',
        'STAF_KESISWAAN',
        'STAF_KEPEGAWAIAN',
        'STAF_SARPRAS',
        'LABORAN',
        'PUSTAKAWAN',
        'TEKNISI_IT',
        'SATPAM',
        'PENJAGA_SEKOLAH',
    ];

    /** Prioritas penentuan tugas utama bila pengguna punya beberapa tugas. */
    private const PRIMARY_PRIORITY = [
        'KEPALA_TAS', 'GURU_PIKET', 'STAF_KESISWAAN', 'STAF_KEPEGAWAIAN', 'STAF_SARPRAS',
        'LABORAN', 'PUSTAKAWAN', 'TEKNISI_IT', 'SATPAM', 'PENJAGA_SEKOLAH', 'STAF_PERSURATAN',
    ];

    private const BIDANG_MAP = [
        'kepala_tas'   => 'KEPALA_TAS',
        'kepala-tas'   => 'KEPALA_TAS',
        'piket'        => 'GURU_PIKET',
        'guru-piket'   => 'GURU_PIKET',
        'kesiswaan'    => 'STAF_KESISWAAN',
        'kepegawaian'  => 'STAF_KEPEGAWAIAN',
        'sarpras'      => 'STAF_SARPRAS',
        'laboran'      => 'LABORAN',
        'perpustakaan' => 'PUSTAKAWAN',
        'teknisi'      => 'TEKNISI_IT',
        'keamanan'     => 'SATPAM',
        'penjaga'      => 'PENJAGA_SEKOLAH',
        'persuratan'   => 'STAF_PERSURATAN',
    ];

    private const VIEW_SECTION_MAP = [
        'UMUM'             => 'umum',
        'KEPALA_TAS'       => 'kepala-tas',
        'GURU_PIKET'       => 'piket',
        'STAF_KESISWAAN'   => 'kesiswaan',
        'STAF_KEPEGAWAIAN' => 'kepegawaian',
        'STAF_SARPRAS'     => 'sarpras',
        'LABORAN'          => 'laboran',
        'PUSTAKAWAN'       => 'perpustakaan',
        'TEKNISI_IT'       => 'teknisi',
        'SATPAM'           => 'keamanan',
        'PENJAGA_SEKOLAH'  => 'penjaga',
        'STAF_PERSURATAN'  => 'persuratan',
    ];

    private const DUTY_PERMISSION_MAP = [
        'KEPALA_TAS'       => 'menu_kepala_tas',
        'GURU_PIKET'       => 'menu_piket',
        'STAF_KESISWAAN'   => 'menu_kesiswaan',
        'STAF_KEPEGAWAIAN' => 'menu_kepegawaian',
        'STAF_SARPRAS'     => 'menu_sarpras',
        'LABORAN'          => 'menu_laboran',
        'PUSTAKAWAN'       => 'menu_perpustakaan',
        'TEKNISI_IT'       => 'menu_teknisi',
        'SATPAM'           => 'menu_keamanan',
        'PENJAGA_SEKOLAH'  => 'menu_penjaga',
        'STAF_PERSURATAN'  => 'menu_persuratan',
    ];

    /** Kode tugas tambahan yang dihitung sebagai tugas Tendik. */
    public static function primaryDuty(iterable $dutyCodes, bool $isKepalaTas): string
    {
        if ($isKepalaTas) {
            return 'KEPALA_TAS';
        }
        foreach (self::PRIMARY_PRIORITY as $code) {
            if (in_array($code, (array) $dutyCodes, true)) {
                return $code;
            }
        }
        return 'STAF_PERSURATAN';
    }

    /**
     * Tentukan bidang aktif dari query string + otorisasi menu.
     *
     * @return array{0:string,1:string} [currentDuty, viewSection]
     */
    public static function resolveViewSection(mixed $user, ?string $reqBidang): array
    {
        $reqBidang = $reqBidang ?: 'umum';

        if ($reqBidang !== 'umum' && isset(self::BIDANG_MAP[$reqBidang])) {
            $currentDuty = self::BIDANG_MAP[$reqBidang];
            $viewSection = self::VIEW_SECTION_MAP[$currentDuty] ?? 'umum';
        } else {
            $currentDuty = 'UMUM';
            $viewSection = 'umum';
        }

        if ($viewSection !== 'umum') {
            $permKey = self::DUTY_PERMISSION_MAP[$currentDuty] ?? ('menu_' . strtolower($viewSection));
            if (!RolePermission::canAccess($user ?: 'tendik', $permKey)) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses bidang tugas ini.');
            }
        }

        return [$currentDuty, $viewSection];
    }

    /** Label bagian tugas yang ditampilkan di header dashboard. */
    public static function bagianTugas(iterable $activeDuties, ?object $gtk): string
    {
        $duties = $activeDuties instanceof \Illuminate\Support\Collection
            ? $activeDuties->all()
            : (array) $activeDuties;
        if ($duties) {
            return implode(', ', $duties);
        }
        return $gtk?->jabatan_ptk_id_str ?: ($gtk?->jenis_ptk_id_str ?: 'Tenaga Administrasi Sekolah');
    }

    public static function tableExists(string $table): bool
    {
        return Table::exists($table);
    }
}
