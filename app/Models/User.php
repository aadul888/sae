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
        'password_updated_at',
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
    ];

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
        if (str_contains($peran, 'tendik') || str_contains($peran, 'tenaga kependidikan') || str_contains($peran, 'tata usaha') || str_contains($peran, 'laboran') || str_contains($peran, 'pustakawan')) {
            return 'tendik';
        }
        if (!empty($this->ptk_id)) {
            // Cek jenis_ptk dari relasi tabel gtk
            $gtk = \Illuminate\Support\Facades\DB::table('gtk')->where('ptk_id', $this->ptk_id)->select('jenis_ptk_id_str')->first();
            if ($gtk && !empty($gtk->jenis_ptk_id_str)) {
                $jPtk = strtolower($gtk->jenis_ptk_id_str);
                if (str_contains($jPtk, 'tenaga kependidikan') || (!str_contains($jPtk, 'guru') && !str_contains($jPtk, 'kepala sekolah'))) {
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
