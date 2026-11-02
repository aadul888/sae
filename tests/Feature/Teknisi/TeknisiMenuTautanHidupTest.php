<?php

namespace Tests\Feature\Teknisi;

use App\Models\RolePermission;
use App\Support\TeknisiModuleRegistry;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regresi: setiap menu grup "Teknisi & IT" yang TAMPIL untuk sebuah akun wajib
 * dapat DIBUKA (HTTP 200). Mencegah tautan mati — kondisi `can` pada
 * TeknisiModuleRegistry::menu() yang tidak sinkron dengan middleware rute.
 */
class TeknisiMenuTautanHidupTest extends TestCase
{
    protected function sessionTugasTambahan(string $kode): ?array
    {
        $row = DB::table('ptk_tugas_tambahan as p')
            ->join('ref_tugas_tambahan as r', 'p.tugas_tambahan_id', '=', 'r.id')
            ->leftJoin('gtk', 'p.ptk_id', '=', 'gtk.ptk_id')
            ->where('p.is_active', true)
            ->where('r.kode', $kode)
            ->whereNotNull('p.user_id')
            ->select('p.user_id', 'p.ptk_id', 'gtk.nama')
            ->first();

        if (!$row) {
            return null;
        }

        return ['user' => [
            'id'           => $row->user_id,
            'pengguna_id'  => $row->user_id,
            'name'         => $row->nama ?: $kode,
            'nama'         => $row->nama ?: $kode,
            'username'     => strtolower($kode),
            'role'         => 'tendik',
            'peran_id_str' => 'Tenaga Administrasi',
            'ptk_id'       => $row->ptk_id,
        ]];
    }

    protected function adminSession(): array
    {
        $admin = DB::table('pengguna')->where('peran_id_str', 'Administrator')->first();

        return ['user' => [
            'id'           => $admin->pengguna_id,
            'pengguna_id'  => $admin->pengguna_id,
            'name'         => $admin->nama,
            'nama'         => $admin->nama,
            'username'     => $admin->username,
            'role'         => 'admin',
            'peran_id_str' => $admin->peran_id_str,
            'ptk_id'       => $admin->ptk_id ?? null,
        ]];
    }

    public static function penyediaAkun(): array
    {
        return [
            ['TEKNISI_IT'],
            ['STAF_SARPRAS'],
            ['LABORAN'],
        ];
    }

    public function test_semua_menu_teknisi_yang_tampil_dapat_dibuka(): void
    {
        $dibuka = 0;
        foreach (self::penyediaAkun() as [$kode]) {
            $session = $this->sessionTugasTambahan($kode);
            if (!$session) {
                continue;
            }

            foreach (TeknisiModuleRegistry::menu() as $items) {
                foreach ($items as $item) {
                    $izin = $item['can'] ?? [];
                    if (!empty($izin) && !collect($izin)->contains(
                        fn ($k) => RolePermission::canAccess($session['user'], $k)
                    )) {
                        continue; // menu tidak tampil untuk akun ini
                    }

                    $this->withSession($session)
                        ->get(route($item['route'], $item['params'] ?? []))
                        ->assertStatus(200, "Menu '{$item['label']}' tampil untuk {$kode} "
                            . "tapi rute {$item['route']} tidak dapat dibuka.");
                    $dibuka++;
                }
            }
        }

        $this->assertGreaterThan(0, $dibuka, 'Tidak ada menu yang diuji.');
    }

    public function test_admin_dapat_membuka_seluruh_menu_teknisi(): void
    {
        foreach (TeknisiModuleRegistry::menu() as $items) {
            foreach ($items as $item) {
                $this->withSession($this->adminSession())
                    ->get(route($item['route'], $item['params'] ?? []))
                    ->assertStatus(200, "Menu '{$item['label']}' gagal dibuka admin.");
            }
        }
    }
}
