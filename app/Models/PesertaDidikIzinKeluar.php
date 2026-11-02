<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class PesertaDidikIzinKeluar extends Model
{
    protected $table = 'peserta_didik_izin_keluar';

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal' => 'date',
        'wali_approved_at' => 'datetime',
        'piket_approved_at' => 'datetime',
        'satpam_checkout_at' => 'datetime',
        'satpam_checkin_at' => 'datetime',
    ];

    public function pesertaDidik(): BelongsTo
    {
        return $this->belongsTo(PesertaDidik::class, 'peserta_didik_id', 'peserta_didik_id');
    }

    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(Gtk::class, 'wali_kelas_ptk_id', 'ptk_id');
    }

    public function petugasPiket(): BelongsTo
    {
        return $this->belongsTo(Gtk::class, 'petugas_piket_ptk_id', 'ptk_id');
    }

    /**
     * Generate format nomor tiket baku IZN-YYYYMMDD-XXXX
     */
    public static function generateNomorTiket(): string
    {
        $todayStr = date('Ymd');
        $lastTicket = DB::table('peserta_didik_izin_keluar')
            ->where('nomor_tiket', 'like', "IZN-{$todayStr}-%")
            ->orderBy('id', 'desc')
            ->first();

        $nextSeq = 1;
        if ($lastTicket && preg_match('/IZN-\d+-(\d+)/', $lastTicket->nomor_tiket, $matches)) {
            $nextSeq = (int) $matches[1] + 1;
        }

        return sprintf("IZN-%s-%04d", $todayStr, $nextSeq);
    }

    /**
     * Badge status dan label terformat
     */
    public function getStatusMetaAttribute(): array
    {
        return match ($this->status) {
            'menunggu_wali' => [
                'label' => 'Menunggu Wali Kelas',
                'badge' => 'warning',
                'icon' => 'fa-clock',
                'color' => '#d97706',
            ],
            'menunggu_piket' => [
                'label' => 'Menunggu Guru Piket',
                'badge' => 'info',
                'icon' => 'fa-clipboard-user',
                'color' => '#2563eb',
            ],
            'menunggu_satpam' => [
                'label' => 'Disetujui (Siap Gerbang)',
                'badge' => 'primary',
                'icon' => 'fa-shield-halved',
                'color' => '#4f46e5',
            ],
            'di_luar' => [
                'label' => 'Sedang di Luar',
                'badge' => 'warning',
                'icon' => 'fa-person-walking',
                'color' => '#f59e0b',
            ],
            'kembali' => [
                'label' => 'Sudah Kembali',
                'badge' => 'success',
                'icon' => 'fa-circle-check',
                'color' => '#10b981',
            ],
            'pulang_selesai' => [
                'label' => 'Pulang (Selesai)',
                'badge' => 'success',
                'icon' => 'fa-house-circle-check',
                'color' => '#059669',
            ],
            'ditolak_wali' => [
                'label' => 'Ditolak Wali Kelas',
                'badge' => 'danger',
                'icon' => 'fa-circle-xmark',
                'color' => '#ef4444',
            ],
            'ditolak_piket' => [
                'label' => 'Ditolak Guru Piket',
                'badge' => 'danger',
                'icon' => 'fa-circle-xmark',
                'color' => '#ef4444',
            ],
            'dibatalkan' => [
                'label' => 'Dibatalkan',
                'badge' => 'secondary',
                'icon' => 'fa-ban',
                'color' => '#6b7280',
            ],
            default => [
                'label' => ucfirst(str_replace('_', ' ', $this->status ?? 'menunggu')),
                'badge' => 'secondary',
                'icon' => 'fa-question-circle',
                'color' => '#6b7280',
            ],
        };
    }
}
