<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Bank soal CBT. Lihat .agents/docs/RENCANA_EKOSISTEM.md §14.7.
 */
class UjianSoal extends Model
{
    protected $table = 'ujian_soal';

    protected $fillable = [
        'pembelajaran_id',
        'tipe',
        'pertanyaan',
        'gambar',
        'video_url',
        'opsi',
        'opsi_gambar',
        'kunci',
        'bobot',
        'tingkat',
        'created_by',
    ];

    protected $casts = [
        'opsi' => 'array',
        'opsi_gambar' => 'array',
        'bobot' => 'integer',
    ];

    public const TIPE = ['pg', 'benar_salah', 'isian', 'uraian'];

    public const TINGKAT = ['mudah', 'sedang', 'sulit'];

    /** URL gambar soal (disk publik) atau null. */
    public function gambarUrl(): ?string
    {
        return $this->gambar ? asset('storage/'.ltrim($this->gambar, '/')) : null;
    }

    /**
     * URL video yang aman disematkan (embed). Mendukung YouTube & Vimeo;
     * URL lain dikembalikan apa adanya (di-render sebagai link, bukan iframe).
     */
    public function videoEmbedUrl(): ?string
    {
        $url = trim((string) $this->video_url);
        if ($url === '') {
            return null;
        }
        if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([\w-]{6,})~i', $url, $m)) {
            return 'https://www.youtube.com/embed/'.rawurlencode($m[1]);
        }
        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~i', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }

        return null;
    }
}
