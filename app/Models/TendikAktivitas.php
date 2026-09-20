<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TendikAktivitas extends Model
{
    use HasFactory;

    protected $table = 'tendik_aktivitas';

    protected $fillable = [
        'user_id',
        'ptk_id',
        'nama_pegawai',
        'bidang',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'judul_aktivitas',
        'uraian_pekerjaan',
        'output_hasil',
        'status',
        'lampiran_path',
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
    ];

    public const STATUS_LABELS = [
        'selesai' => 'Selesai',
        'proses' => 'Sedang Proses',
        'tertunda' => 'Tertunda / Kendala',
    ];

    public const BIDANG_LABELS = [
        'kepala_tas' => 'Kepala TAS / Tata Usaha',
        'kesiswaan' => 'Kesiswaan',
        'kepegawaian' => 'Kepegawaian',
        'sarpras' => 'Sarana & Prasarana',
        'laboran' => 'Laboratorium',
        'perpustakaan' => 'Perpustakaan',
        'teknisi' => 'Teknisi IT',
        'keamanan' => 'Keamanan & Satpam',
        'penjaga' => 'Penjaga & Fasilitas',
        'persuratan' => 'Persuratan & Arsip',
        'umum' => 'Administrasi Umum',
    ];

    public function getDurasiMenitAttribute(): int
    {
        if (!$this->jam_mulai || !$this->jam_selesai) {
            return 60;
        }
        try {
            $start = \Carbon\Carbon::parse($this->jam_mulai);
            $end = \Carbon\Carbon::parse($this->jam_selesai);
            return max(1, $start->diffInMinutes($end));
        } catch (\Throwable $e) {
            return 60;
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'pengguna_id');
    }

    /**
     * Catat aktivitas sistem tendik secara otomatis (Login, CRUD, dsb).
     */
    public static function recordActivity($userOrId, string $judul, string $bidang = 'umum', ?string $uraian = null, string $status = 'selesai', ?string $output = null): ?self
    {
        try {
            $user = null;
            $userId = null;
            $ptkId = null;
            $nama = 'Tenaga Kependidikan';

            if ($userOrId instanceof User) {
                $user = $userOrId;
                $userId = $user->pengguna_id;
                $ptkId = $user->ptk_id;
                $nama = $user->nama ?: $user->name;
            } elseif (is_array($userOrId)) {
                $userId = $userOrId['pengguna_id'] ?? ($userOrId['id'] ?? null);
                $ptkId = $userOrId['ptk_id'] ?? null;
                $nama = $userOrId['nama'] ?? ($userOrId['name'] ?? 'Tenaga Kependidikan');
            } elseif (is_string($userOrId)) {
                $userId = $userOrId;
                $user = User::where('pengguna_id', $userId)->first();
                if ($user) {
                    $ptkId = $user->ptk_id;
                    $nama = $user->nama ?: $user->name;
                }
            } else {
                $sessionUser = session('user');
                if ($sessionUser) {
                    return self::recordActivity($sessionUser, $judul, $bidang, $uraian, $status, $output);
                }
            }

            if (!$userId && !$ptkId) {
                return null;
            }

            $now = now();
            $jam = $now->format('H:i:s');
            $tanggal = $now->toDateString();

            // Cegah duplikasi log identik dalam interval 5 menit
            $existing = self::where(function ($q) use ($userId, $ptkId) {
                    if ($userId) $q->where('user_id', $userId);
                    if ($ptkId) $q->orWhere('ptk_id', $ptkId);
                })
                ->whereDate('tanggal', $tanggal)
                ->where('judul_aktivitas', $judul)
                ->where('created_at', '>=', $now->copy()->subMinutes(5))
                ->first();

            if ($existing) {
                return $existing;
            }

            return self::create([
                'user_id'          => $userId,
                'ptk_id'           => $ptkId,
                'nama_pegawai'     => $nama,
                'bidang'           => $bidang ?: 'umum',
                'tanggal'          => $tanggal,
                'jam_mulai'        => $jam,
                'jam_selesai'      => $jam,
                'judul_aktivitas'  => $judul,
                'uraian_pekerjaan' => $uraian ?: $judul,
                'output_hasil'     => $output ?: 'Tercatat di Sistem',
                'status'           => $status ?: 'selesai',
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal mencatat aktivitas otomatis: ' . $e->getMessage());
            return null;
        }
    }
}

