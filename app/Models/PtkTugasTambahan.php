<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class PtkTugasTambahan extends Model
{
    protected $table = 'ptk_tugas_tambahan';

    protected $fillable = [
        'user_id',
        'ptk_id',
        'tugas_tambahan_id',
        'nomor_sk',
        'tmt_tugas',
        'tst_tugas',
        'rombel_id',
        'jurusan_id',
        'keterangan',
        'is_active',
    ];

    protected $casts = [
        'tmt_tugas' => 'date',
        'tst_tugas' => 'date',
        'is_active' => 'boolean',
    ];

    public function tugas(): BelongsTo
    {
        return $this->belongsTo(RefTugasTambahan::class, 'tugas_tambahan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Sinkronisasi otomatis Wali Kelas dari tabel rombongan_belajar Dapodik
     */
    public static function syncWaliKelasFromDapodik(): int
    {
        $waliTugas = RefTugasTambahan::where('kode', 'WALI_KELAS')->first();
        if (!$waliTugas) {
            return 0;
        }

        $rombels = DB::table('rombongan_belajar')
            ->where('jenis_rombel', '1') // Kelas Reguler
            ->whereNotNull('ptk_id')
            ->where('ptk_id', '!=', '')
            ->get();

        $syncedCount = 0;
        $activeRombelIds = [];

        foreach ($rombels as $r) {
            $activeRombelIds[] = $r->rombongan_belajar_id;

            // Cari user_id jika akun pengguna sudah ada
            $userId = DB::table('pengguna')->where('ptk_id', $r->ptk_id)->value('pengguna_id');

            $existing = self::where('tugas_tambahan_id', $waliTugas->id)
                ->where('rombel_id', $r->rombongan_belajar_id)
                ->first();

            if ($existing) {
                $existing->update([
                    'ptk_id' => $r->ptk_id,
                    'user_id' => $userId ?: $existing->user_id,
                    'jurusan_id' => $r->jurusan_id,
                    'keterangan' => 'Wali Kelas ' . $r->nama . ' (Dapodik)',
                    'is_active' => true,
                ]);
            } else {
                self::create([
                    'user_id' => $userId,
                    'ptk_id' => $r->ptk_id,
                    'tugas_tambahan_id' => $waliTugas->id,
                    'rombel_id' => $r->rombongan_belajar_id,
                    'jurusan_id' => $r->jurusan_id,
                    'keterangan' => 'Wali Kelas ' . $r->nama . ' (Dapodik)',
                    'is_active' => true,
                ]);
                $syncedCount++;
            }
        }

        // Nonaktifkan wali kelas untuk rombel reguler yang sudah tidak ada di Dapodik
        self::where('tugas_tambahan_id', $waliTugas->id)
            ->whereNotNull('rombel_id')
            ->whereNotIn('rombel_id', $activeRombelIds)
            ->update(['is_active' => false]);

        return $syncedCount;
    }
}
