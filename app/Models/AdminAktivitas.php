<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAktivitas extends Model
{
    protected $table = 'admin_aktivitas';

    protected $fillable = [
        'admin_name',
        'admin_username',
        'modul',
        'aktivitas',
        'keterangan',
        'ip_address',
        'tipe',
    ];

    /**
     * Helper cepat untuk mencatat aktivitas administrator
     */
    public static function record(string $aktivitas, string $modul = 'Sistem', ?string $keterangan = null, string $tipe = 'success'): self
    {
        $sessionUser = session('user');
        $adminName = is_array($sessionUser) ? ($sessionUser['name'] ?? ($sessionUser['nama'] ?? 'Administrator')) : ($sessionUser->name ?? ($sessionUser->nama ?? 'Administrator'));
        $adminUsername = is_array($sessionUser) ? ($sessionUser['username'] ?? null) : ($sessionUser->username ?? null);

        return self::create([
            'admin_name' => $adminName,
            'admin_username' => $adminUsername,
            'modul' => $modul,
            'aktivitas' => $aktivitas,
            'keterangan' => $keterangan,
            'ip_address' => request()->ip(),
            'tipe' => $tipe,
        ]);
    }

    /**
     * Helper mencatat transaksi CRUD eksplisit
     */
    public static function recordCrud(string $action, string $aktivitas, string $modul = 'Sistem', ?string $keterangan = null, string $tipe = 'success'): self
    {
        $prefix = match (strtoupper($action)) {
            'CREATE' => '[CREATE] ',
            'UPDATE' => '[UPDATE] ',
            'DELETE' => '[DELETE] ',
            default => '[READ] ',
        };

        // Buang hanya prefix CRUD yang benar-benar ada (ltrim() salah: charlist, bukan prefix).
        $clean = preg_replace('/^\[(CREATE|UPDATE|DELETE|READ)\]\s*/i', '', $aktivitas);

        return self::record($prefix . $clean, $modul, $keterangan, $tipe);
    }

    /**
     * Deteksi tipe operasi CRUD database
     */
    public function getCrudActionAttribute(): string
    {
        if (str_starts_with($this->aktivitas, '[CREATE]')) return 'CREATE';
        if (str_starts_with($this->aktivitas, '[UPDATE]')) return 'UPDATE';
        if (str_starts_with($this->aktivitas, '[DELETE]')) return 'DELETE';
        if (str_starts_with($this->aktivitas, '[READ]')) return 'READ';

        $act = strtolower($this->aktivitas . ' ' . ($this->keterangan ?? ''));

        if (str_contains($act, 'hapus') || str_contains($act, 'delete') || str_contains($act, 'destroy') || str_contains($act, 'drop') || str_contains($act, 'batal') || str_contains($act, 'reset')) {
            return 'DELETE';
        }

        if (str_contains($act, 'upload') || str_contains($act, 'unggah') || str_contains($act, 'tambah') || str_contains($act, 'buat') || str_contains($act, 'create') || str_contains($act, 'insert') || str_contains($act, 'import') || str_contains($act, 'pengajuan') || str_contains($act, 'input') || str_contains($act, 'simpan')) {
            return 'CREATE';
        }

        if (str_contains($act, 'update') || str_contains($act, 'ubah') || str_contains($act, 'edit') || str_contains($act, 'perbarui') || str_contains($act, 'pembaruan') || str_contains($act, 'verifikasi') || str_contains($act, 'sync') || str_contains($act, 'sinkronisasi') || str_contains($act, 'setujui') || str_contains($act, 'tolak') || str_contains($act, 'konfigurasi')) {
            return 'UPDATE';
        }

        return 'READ';
    }

    /**
     * Konfigurasi badge visual operasi CRUD
     */
    public function getCrudBadgeAttribute(): array
    {
        return match ($this->crud_action) {
            'CREATE' => [
                'label' => 'CREATE',
                'color' => 'var(--success, #10b981)',
                'bg' => 'rgba(16, 185, 129, 0.12)',
                'icon' => 'fa-plus',
            ],
            'UPDATE' => [
                'label' => 'UPDATE',
                'color' => 'var(--warning, #f59e0b)',
                'bg' => 'rgba(245, 158, 11, 0.12)',
                'icon' => 'fa-pen-to-square',
            ],
            'DELETE' => [
                'label' => 'DELETE',
                'color' => 'var(--danger, #ef4444)',
                'bg' => 'rgba(239, 68, 68, 0.12)',
                'icon' => 'fa-trash-can',
            ],
            default => [
                'label' => 'READ',
                'color' => 'var(--accent, #0284c7)',
                'bg' => 'rgba(2, 132, 199, 0.12)',
                'icon' => 'fa-eye',
            ],
        };
    }

    /**
     * Konteks struktural aktivitas: Grup Menu (selaras sidebar), Menu, Fitur, dan Bagian / Target
     */
    public function getKonteksAttribute(): array
    {
        $modul = trim($this->modul ?? '');
        $act = trim($this->aktivitas ?? '');
        $ket = trim($this->keterangan ?? '');
        $actClean = preg_replace('/^\[(CREATE|UPDATE|DELETE|READ)\]\s*/i', '', $act);

        // 1. GRUP MENU SIDEBAR (sumber tunggal: MonitoringMenu)
        $group = \App\Support\MonitoringMenu::meta($modul);
        $bidang = $group['name'];
        $bidangIcon = $group['icon'];
        $bidangColor = $group['color'];
        $bidangBg = $group['bg'];

        // 2. MENU DASHBOARD
        $menu = match ($modul) {
            'Kesiswaan', 'Siswa' => 'Manajemen Data > Peserta Didik',
            'Buku Induk' => 'Kesiswaan > Buku Induk',
            'Mutasi' => 'Kesiswaan > Administrasi Mutasi',
            'Kedisiplinan' => 'Kesiswaan > Kedisiplinan',
            'Dapodik' => 'Utama > Tarik Data Dapodik',
            'Formulir' => 'Layanan Digital > Formulir & Survei',
            'Pengumuman' => 'Layanan Digital > Pengumuman',
            'Presensi' => 'Layanan Digital > RFID & Presensi',
            'Hak Akses' => 'Sistem > Hak Akses RBAC',
            'Backup' => 'Sistem > Cadangan & Arsip',
            'Autentikasi' => 'Sistem > Autentikasi Pengguna',
            'Persuratan' => 'Persuratan > Surat Masuk & Keluar',
            'Kepegawaian' => 'Kepegawaian > Data Pegawai',
            'Sistem' => str_contains(strtolower($act), 'log') ? 'Utama > Monitoring' : 'Sistem > Pembaruan Sistem',
            default => $bidang . ' > ' . ($modul ?: 'Sistem'),
        };

        // 3. FITUR SPESIFIK
        $fitur = $actClean;
        if (str_contains($actClean, ':')) {
            $parts = explode(':', $actClean, 2);
            $fitur = trim($parts[0]);
        } elseif (str_contains($actClean, '(')) {
            $parts = explode('(', $actClean, 2);
            $fitur = trim($parts[0]);
        }

        // 4. BAGIAN / TARGET ENTITAS
        $target = '-';
        if (str_contains($actClean, ':')) {
            $parts = explode(':', $actClean, 2);
            $target = trim($parts[1]);
        } elseif (preg_match('/(NISN:\s*[0-9]+)/i', $act . ' ' . $ket, $m)) {
            $target = $m[1];
        } elseif (preg_match('/(log\s*#[0-9]+)/i', $ket, $m)) {
            $target = $m[1];
        } elseif (preg_match('/via\s+([A-Za-z0-9\s]+)\s*\((IP:[^\)]+)\)/i', $ket, $m)) {
            $target = trim($m[1]) . ' (' . trim($m[2]) . ')';
        } elseif (!empty($ket)) {
            $target = \Illuminate\Support\Str::limit($ket, 50);
        }

        return [
            'bidang' => $bidang,
            'bidang_icon' => $bidangIcon,
            'bidang_color' => $bidangColor,
            'bidang_bg' => $bidangBg,
            'menu' => $menu,
            'fitur' => $fitur,
            'bagian' => $target,
        ];
    }

    /**
     * Scope filter tipe CRUD
     */
    public function scopeFilterCrud($query, ?string $crud)
    {
        if (empty($crud) || $crud === 'all') {
            return $query;
        }

        $crud = strtoupper($crud);

        return match ($crud) {
            'CREATE' => $query->where(function ($q) {
                $q->where('aktivitas', 'like', '[CREATE]%')
                  ->orWhere('aktivitas', 'like', '%upload%')
                  ->orWhere('aktivitas', 'like', '%unggah%')
                  ->orWhere('aktivitas', 'like', '%tambah%')
                  ->orWhere('aktivitas', 'like', '%buat%')
                  ->orWhere('aktivitas', 'like', '%import%')
                  ->orWhere('aktivitas', 'like', '%pengajuan%')
                  ->orWhere('aktivitas', 'like', '%input%')
                  ->orWhere('aktivitas', 'like', '%simpan%');
            }),
            'UPDATE' => $query->where(function ($q) {
                $q->where('aktivitas', 'like', '[UPDATE]%')
                  ->orWhere('aktivitas', 'like', '%update%')
                  ->orWhere('aktivitas', 'like', '%ubah%')
                  ->orWhere('aktivitas', 'like', '%edit%')
                  ->orWhere('aktivitas', 'like', '%perbarui%')
                  ->orWhere('aktivitas', 'like', '%pembaruan%')
                  ->orWhere('aktivitas', 'like', '%verifikasi%')
                  ->orWhere('aktivitas', 'like', '%sync%')
                  ->orWhere('aktivitas', 'like', '%sinkronisasi%')
                  ->orWhere('aktivitas', 'like', '%setujui%')
                  ->orWhere('aktivitas', 'like', '%tolak%')
                  ->orWhere('aktivitas', 'like', '%konfigurasi%');
            }),
            'DELETE' => $query->where(function ($q) {
                $q->where('aktivitas', 'like', '[DELETE]%')
                  ->orWhere('aktivitas', 'like', '%hapus%')
                  ->orWhere('aktivitas', 'like', '%delete%')
                  ->orWhere('aktivitas', 'like', '%destroy%')
                  ->orWhere('aktivitas', 'like', '%drop%')
                  ->orWhere('aktivitas', 'like', '%batal%')
                  ->orWhere('aktivitas', 'like', '%reset%');
            }),
            'READ' => $query->where(function ($q) {
                $q->where('aktivitas', 'like', '[READ]%')
                  ->orWhere('aktivitas', 'like', '%login%')
                  ->orWhere('aktivitas', 'like', '%logout%')
                  ->orWhere('aktivitas', 'like', '%ekspor%')
                  ->orWhere('aktivitas', 'like', '%export%')
                  ->orWhere('aktivitas', 'like', '%cetak%')
                  ->orWhere('aktivitas', 'like', '%unduh%')
                  ->orWhere('aktivitas', 'like', '%cadangan%');
            }),
            default => $query,
        };
    }
}

