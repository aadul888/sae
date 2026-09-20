<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'pengguna';
    protected $primaryKey = 'pengguna_id';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'pengguna_id',
        'sekolah_id',
        'username',
        'nama',
        'peran_id_str',
        'password',
        'alamat',
        'foto_path',
        'no_telepon',
        'no_hp',
        'ptk_id',
        'peserta_didik_id',
        'raw_data',
        'password_updated_at',
        'rfid_uid',
        'rfid_registered_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password_updated_at' => 'datetime',
        'rfid_registered_at' => 'datetime',
    ];

    protected $appends = [
        'name',
        'role',
        'foto_url',
    ];

    public function getNameAttribute()
    {
        return $this->nama;
    }

    /**
     * Dapatkan URL foto profil (khusus GTK/guru/tendik/admin)
     */
    public function getFotoUrlAttribute(): ?string
    {
        if (empty($this->foto_path)) {
            return null;
        }

        return asset('storage/' . ltrim($this->foto_path, '/'));
    }

    /**
     * Derivasi dinamis role dari peran_id_str / relasi Dapodik
     */
    public function getRoleAttribute(): string
    {
        $peran = strtolower($this->peran_id_str ?? '');
        if (str_contains($peran, 'admin') || str_contains($peran, 'dinas') || str_contains($peran, 'yayasan') || str_contains($peran, 'operator')) {
            return 'admin';
        }
        if (str_contains($peran, 'tendik') || str_contains($peran, 'tenaga kependidikan') || str_contains($peran, 'tata usaha') || str_contains($peran, 'laboran') || str_contains($peran, 'pustakawan')) {
            return 'tendik';
        }
        if (!empty($this->ptk_id) || !empty($this->pengguna_id)) {
            // 1. Cek jenis_ptk dari relasi tabel gtk terlebih dahulu
            if (!empty($this->ptk_id)) {
                $gtk = \Illuminate\Support\Facades\DB::table('gtk')->where('ptk_id', $this->ptk_id)->select('jenis_ptk_id_str')->first();
                if ($gtk && !empty($gtk->jenis_ptk_id_str)) {
                    $jPtk = strtolower($gtk->jenis_ptk_id_str);
                    // Jika terdaftar sebagai Guru atau Kepala Sekolah, tugas utamanya TETAP GURU
                    if (str_contains($jPtk, 'guru') || str_contains($jPtk, 'kepala sekolah')) {
                        return 'guru';
                    }
                    if (str_contains($jPtk, 'tenaga kependidikan') || (!str_contains($jPtk, 'guru') && !str_contains($jPtk, 'kepala sekolah'))) {
                        return 'tendik';
                    }
                }
            }

            // 2. Jika mengampu mata pelajaran pada tabel pembelajaran, tugas utamanya adalah GURU
            if (!empty($this->ptk_id)) {
                $isMengajar = \Illuminate\Support\Facades\DB::table('pembelajaran')->where('ptk_id', $this->ptk_id)->exists();
                if ($isMengajar) {
                    return 'guru';
                }
            }

            // 3. Cek penugasan aktif tendik untuk pengguna yang bukan guru
            if (\Illuminate\Support\Facades\Schema::hasTable('ptk_tugas_tambahan') && \Illuminate\Support\Facades\Schema::hasTable('ref_tugas_tambahan')) {
                $hasTendikDuty = \Illuminate\Support\Facades\DB::table('ptk_tugas_tambahan as ptt')
                    ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                    ->where('ptt.is_active', true)
                    ->where('rtt.is_active', true)
                    ->where(function ($q) {
                        if (!empty($this->pengguna_id)) $q->where('ptt.user_id', $this->pengguna_id);
                        if (!empty($this->ptk_id)) $q->orWhere('ptt.ptk_id', $this->ptk_id);
                    })
                    ->whereIn('rtt.kode', [
                        'KEPALA_TAS', 'STAF_PERSURATAN', 'STAF_KESISWAAN', 'STAF_KEPEGAWAIAN',
                        'STAF_SARPRAS', 'LABORAN', 'PUSTAKAWAN', 'TEKNISI_IT', 'SATPAM', 'PENJAGA_SEKOLAH'
                    ])
                    ->exists();

                if ($hasTendikDuty) {
                    return 'tendik';
                }
            }
        }
        if (str_contains($peran, 'guru') || str_contains($peran, 'ptk') || !empty($this->ptk_id)) {
            return 'guru';
        }
        return 'peserta_didik';
    }
}
