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
        'no_telepon',
        'no_hp',
        'ptk_id',
        'peserta_didik_id',
        'raw_data',
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
    protected $casts = [];

    protected $appends = [
        'name',
        'role',
    ];

    public function getNameAttribute()
    {
        return $this->nama;
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
        if (str_contains($peran, 'guru') || str_contains($peran, 'ptk') || str_contains($peran, 'tendik') || !empty($this->ptk_id)) {
            return 'guru';
        }
        return 'siswa';
    }
}
