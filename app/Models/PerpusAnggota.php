<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerpusAnggota extends Model
{
    protected $table = 'perpus_anggota';

    protected $fillable = [
        'nomor_anggota', 'token_kartu', 'tipe', 'siswa_id', 'ptk_id', 'nisn_nip',
        'nama', 'rombel_atau_unit', 'jenis_kelamin', 'telepon', 'email', 'foto',
        'password', 'is_active', 'poin', 'last_login_at', 'auto_provisioned',
        'tgl_daftar', 'tgl_kadaluarsa', 'catatan',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'is_active' => 'boolean',
        'auto_provisioned' => 'boolean',
        'poin' => 'integer',
        'last_login_at' => 'datetime',
        'tgl_daftar' => 'date',
        'tgl_kadaluarsa' => 'date',
    ];

    /** Label tipe anggota untuk tampilan. */
    public function tipeLabel(): string
    {
        return match ($this->tipe) {
            'gtk'  => 'GTK',
            'umum' => 'Umum',
            default => 'Peserta Didik',
        };
    }

    /** Inisial nama untuk avatar. */
    public function inisial(): string
    {
        $nama = trim((string) $this->nama);
        if ($nama === '') {
            return '?';
        }

        $parts = preg_split('/\s+/', $nama) ?: [];
        $a = mb_substr($parts[0] ?? '', 0, 1);
        $b = mb_substr($parts[1] ?? '', 0, 1);

        return mb_strtoupper($a . $b);
    }

    public function verifyUrl(): string
    {
        return route('perpustakaan.verifikasi', $this->token_kartu);
    }

    public static function generateNomor(string $tipe): string
    {
        $prefix = match ($tipe) {
            'gtk' => 'GTK',
            'umum' => 'UMU',
            default => 'PDS',
        };

        return $prefix . '-' . now()->format('y') . '-' . str_pad((string) (static::where('tipe', $tipe)->count() + 1), 4, '0', STR_PAD_LEFT);
    }
}
