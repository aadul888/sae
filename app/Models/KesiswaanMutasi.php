<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KesiswaanMutasi extends Model
{
    use HasFactory;

    protected $table = 'kesiswaan_mutasi';

    protected $fillable = [
        'peserta_didik_id',
        'nama_siswa',
        'nisn_siswa',
        'nipd_siswa',
        'rombel_terakhir',
        'jenis_mutasi',
        'tanggal_mutasi',
        'alasan',
        'sekolah_tujuan_asal',
        'nomor_surat_penerimaan',
        'nomor_surat_mutasi',
        'nomor_surat_dapodik',
        'file_berkas',
        'file_surat_penerimaan',
        'file_surat_mutasi',
        'surat_masuk_id',
        'surat_keluar_id',
        'catatan',
        'status_proses',
        'dapodik_synced_at',
        'created_by',
    ];

    protected $casts = [
        'tanggal_mutasi' => 'date',
        'dapodik_synced_at' => 'datetime',
    ];

    public function suratMasuk()
    {
        return $this->belongsTo(Persuratan::class, 'surat_masuk_id');
    }

    public function suratKeluar()
    {
        return $this->belongsTo(Persuratan::class, 'surat_keluar_id');
    }

    public function siswa()
    {
        return $this->belongsTo(PesertaDidik::class, 'peserta_didik_id', 'peserta_didik_id');
    }

    /**
     * Pastikan data siswa selalu ada, bahkan saat siswa sudah dipindahkan
     * ke peserta_didik_tidak_aktif oleh sinkronisasi Feeder/Dapodik.
     */
    public function getSiswaAttribute()
    {
        if (array_key_exists('siswa', $this->relations) && $this->relations['siswa']) {
            return $this->relations['siswa'];
        }

        // Cek tabel peserta_didik live terlebih dahulu
        $live = \Illuminate\Support\Facades\DB::table('peserta_didik')
            ->where('peserta_didik_id', $this->peserta_didik_id)
            ->first();
        if ($live) {
            return $live;
        }

        // Fallback: cari di peserta_didik_tidak_aktif
        $pdta = \Illuminate\Support\Facades\DB::table('peserta_didik_tidak_aktif')
            ->where('peserta_didik_id', $this->peserta_didik_id)
            ->first();
        if ($pdta) {
            return (object) [
                'peserta_didik_id' => $pdta->peserta_didik_id,
                'nama'             => $pdta->nama,
                'nisn'             => $pdta->nisn,
                'nipd'             => $pdta->nipd,
                'jenis_kelamin'    => $pdta->jenis_kelamin,
                'nama_rombel'      => $pdta->nama_rombel_terakhir,
                'tempat_lahir'     => $pdta->tempat_lahir,
                'tanggal_lahir'    => $pdta->tanggal_lahir,
                'agama_id_str'     => $pdta->agama_id_str,
                'alamat_jalan'     => $pdta->alamat_jalan,
                'foto_path'        => $pdta->foto_path,
                'foto_url'         => $pdta->foto_path ? asset('storage/' . $pdta->foto_path) : null,
            ];
        }

        // Fallback terakhir: gunakan snapshot yang tersimpan saat pencatatan mutasi
        return (object) [
            'peserta_didik_id' => $this->peserta_didik_id,
            'nama'             => $this->nama_siswa ?: ('Siswa #' . substr($this->peserta_didik_id, 0, 8)),
            'nisn'             => $this->nisn_siswa ?: '-',
            'nipd'             => $this->nipd_siswa ?: '-',
            'jenis_kelamin'    => '-',
            'nama_rombel'      => $this->rombel_terakhir ?: '-',
            'tempat_lahir'     => '-',
            'tanggal_lahir'    => null,
            'agama_id_str'     => '-',
            'alamat_jalan'     => '-',
            'foto_path'        => null,
            'foto_url'         => null,
        ];
    }
}
