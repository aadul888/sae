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
}
