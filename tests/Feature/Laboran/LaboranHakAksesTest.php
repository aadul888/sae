<?php

namespace Tests\Feature\Laboran;

use App\Support\DutyPermissionDefaults;
use App\Support\PermissionDictionary;

/**
 * J. Hak akses: permission key modul laboran terdaftar & menjadi default LABORAN.
 */
class LaboranHakAksesTest extends LaboranTestCase
{
    public function test_permission_key_modul_baru_terdaftar_di_dictionary(): void
    {
        $keys = $this->dictionaryKeys();

        foreach ([
            'menu_laboran_kebutuhan',
            'menu_laboran_perawatan',
            'menu_laboran_pemeriksaan',
            'menu_laboran_pengadaan',
            'menu_laboran_kerusakan',
            'menu_laboran_laporan',
        ] as $expected) {
            $this->assertContains($expected, $keys, "Permission key {$expected} belum terdaftar.");
        }
    }

    public function test_default_permission_laboran_mencakup_modul_baru(): void
    {
        $defaults = DutyPermissionDefaults::forDuty('LABORAN');

        foreach ([
            'menu_laboran_kebutuhan',
            'menu_laboran_perawatan',
            'menu_laboran_pemeriksaan',
            'menu_laboran_pengadaan',
            'menu_laboran_kerusakan',
            'menu_laboran_laporan',
        ] as $expected) {
            $this->assertContains($expected, $defaults, "Default LABORAN belum memuat {$expected}.");
        }
    }

    public function test_permission_key_tupoksi_terdaftar(): void
    {
        $keys = $this->dictionaryKeys();

        $expected = [
            'menu_laboran_arsip',        // #7 Arsip/Ordner
            'menu_laboran_jurnal',       // #8 Jurnal Kegiatan
            'menu_laboran_kebersihan',   // #9 Kebersihan
        ];

        foreach ($expected as $key) {
            $this->assertContains($key, $keys, "Permission key {$key} belum terdaftar.");
            $this->assertContains(
                $key,
                DutyPermissionDefaults::forDuty('LABORAN'),
                "Default LABORAN belum memuat {$key}."
            );
        }
    }

    /**
     * @return array<int, string>
     */
    private function dictionaryKeys(): array
    {
        $keys = [];
        foreach (PermissionDictionary::base() as $cluster) {
            foreach (array_keys($cluster) as $key) {
                $keys[] = $key;
            }
        }

        return $keys;
    }
}
