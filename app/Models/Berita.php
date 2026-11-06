<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Berita extends Model
{
    protected $table = 'berita';

    protected $fillable = [
        'judul',
        'slug',
        'kategori',
        'ringkasan',
        'isi',
        'gambar',
        'penulis_nama',
        'is_active',
        'views',
        'published_at',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'views'        => 'integer',
        'published_at' => 'datetime',
    ];

    /**
     * Scope berita aktif & terpublikasi
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    /**
     * Scope filter kategori
     */
    public function scopeByCategory(Builder $query, ?string $kategori): Builder
    {
        if ($kategori && $kategori !== 'Semua') {
            $query->where('kategori', $kategori);
        }
        return $query;
    }

    /**
     * Dapatkan URL gambar atau fallback default SVG / gradient
     */
    public function getGambarUrlAttribute(): string
    {
        if (!empty($this->gambar)) {
            if (Str::startsWith($this->gambar, ['http://', 'https://'])) {
                return $this->gambar;
            }
            return asset($this->gambar);
        }

        return '';
    }

    /**
     * Ringkasan otomatis jika tidak diisi manual
     */
    public function getExcerptAttribute(): string
    {
        if (!empty($this->ringkasan)) {
            return $this->ringkasan;
        }

        return Str::limit(trim(strip_tags($this->isi)), 140);
    }

    /**
     * Buat slug unik otomatis dari judul
     */
    public static function createUniqueSlug(string $judul, ?int $exceptId = null): string
    {
        $baseSlug = Str::slug($judul) ?: 'berita-' . time();
        $slug = $baseSlug;
        $counter = 1;

        while (static::where('slug', $slug)->when($exceptId, fn($q) => $q->where('id', '!=', $exceptId))->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * Bersihkan HTML isi artikel agar aman dari XSS
     */
    public function getCleanContentAttribute(): string
    {
        $raw = (string) $this->isi;
        if (strip_tags($raw) === $raw) {
            return nl2br(e($raw));
        }

        $allowedTags = '<p><br><strong><b><em><i><u><s><strike><h1><h2><h3><h4><h5><h6><ul><ol><li><blockquote><a><hr><table><thead><tbody>tr><th><td><span>';
        $cleaned = strip_tags($raw, $allowedTags);

        // Hapus event handler on* dan javascript:
        $cleaned = preg_replace('/<([a-z1-6]+)([^>]*?)(on[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+))([^>]*?)>/i', '<$1$2$4>', $cleaned);
        $cleaned = preg_replace('/href\s*=\s*([\'"])javascript:[^1]*?\1/i', 'href="#"', $cleaned);

        return $cleaned;
    }
}
