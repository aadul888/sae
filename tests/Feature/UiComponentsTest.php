<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class UiComponentsTest extends TestCase
{
    public function test_button_component_renders_primary(): void
    {
        $html = Blade::render('<x-sae.button variant="primary" icon="fas fa-plus">Tambah</x-sae.button>');

        $this->assertStringContainsString('class="sae-btn sae-btn--primary"', $html);
        $this->assertStringContainsString('<button type="button"', $html);
        $this->assertStringContainsString('fas fa-plus', $html);
        $this->assertStringContainsString('Tambah', $html);
    }

    public function test_button_component_renders_anchor_when_href_given(): void
    {
        $html = Blade::render('<x-sae.button href="/dashboard" variant="outline">Beranda</x-sae.button>');

        $this->assertStringContainsString('<a href="/dashboard"', $html);
        $this->assertStringContainsString('sae-btn--outline', $html);
    }

    public function test_badge_component_renders_variant(): void
    {
        $html = Blade::render('<x-sae.badge variant="success">Aktif</x-sae.badge>');

        $this->assertStringContainsString('sae-badge--success', $html);
        $this->assertStringContainsString('Aktif', $html);
    }

    public function test_card_component_renders_title_and_slot(): void
    {
        $html = Blade::render('<x-sae.card title="Ringkasan" icon="fas fa-chart-pie">Isi kartu</x-sae.card>');

        $this->assertStringContainsString('sae-card__title', $html);
        $this->assertStringContainsString('Ringkasan', $html);
        $this->assertStringContainsString('Isi kartu', $html);
    }

    public function test_page_header_component_renders_actions_slot(): void
    {
        $html = Blade::render(
            '<x-sae.page-header title="Data Siswa" subtitle="Daftar peserta didik">'
            . '<x-slot:actions><a href="#">Aksi</a></x-slot:actions>'
            . '</x-sae.page-header>'
        );

        $this->assertStringContainsString('sae-page-header__title', $html);
        $this->assertStringContainsString('Data Siswa', $html);
        $this->assertStringContainsString('sae-page-header__actions', $html);
        $this->assertStringContainsString('Aksi', $html);
    }

    public function test_field_component_renders_label_and_input(): void
    {
        $html = Blade::render('<x-sae.field name="nama" label="Nama Lengkap" required />');

        $this->assertStringContainsString('sae-field__label', $html);
        $this->assertStringContainsString('Nama Lengkap', $html);
        $this->assertStringContainsString('sae-input', $html);
        $this->assertStringContainsString('required', $html);
    }

    public function test_field_component_renders_textarea(): void
    {
        $html = Blade::render('<x-sae.field name="catatan" label="Catatan" type="textarea" />');

        $this->assertStringContainsString('<textarea', $html);
        $this->assertStringContainsString('sae-textarea', $html);
    }

    public function test_alert_component_renders_variant_and_icon(): void
    {
        $html = Blade::render('<x-sae.alert variant="warning">Perhatian</x-sae.alert>');

        $this->assertStringContainsString('sae-alert--warning', $html);
        $this->assertStringContainsString('fa-triangle-exclamation', $html);
        $this->assertStringContainsString('role="alert"', $html);
    }

    public function test_empty_component_renders_title(): void
    {
        $html = Blade::render('<x-sae.empty title="Belum ada jadwal" text="Silakan tambahkan." />');

        $this->assertStringContainsString('sae-empty__title', $html);
        $this->assertStringContainsString('Belum ada jadwal', $html);
        $this->assertStringContainsString('Silakan tambahkan.', $html);
    }

    public function test_stat_component_renders_value_and_label(): void
    {
        $html = Blade::render('<x-sae.stat :value="42" label="Total Siswa" icon="fas fa-users" color="accent" />');

        $this->assertStringContainsString('sae-stat__value', $html);
        $this->assertStringContainsString('42', $html);
        $this->assertStringContainsString('Total Siswa', $html);
        $this->assertStringContainsString('var(--accent)', $html);
    }

    public function test_design_token_stylesheets_are_linked_on_dashboard(): void
    {
        $admin = \Illuminate\Support\Facades\DB::table('pengguna')
            ->where('peran_id_str', 'Administrator')->first();

        $this->assertNotNull($admin, 'Akun administrator tidak ditemukan.');

        $res = $this->withSession(['user' => [
            'id' => $admin->pengguna_id,
            'pengguna_id' => $admin->pengguna_id,
            'name' => $admin->nama,
            'nama' => $admin->nama,
            'username' => $admin->username,
            'role' => 'admin',
            'peran_id_str' => $admin->peran_id_str,
        ]])->get('/dashboard/admin');

        $res->assertStatus(200);
        $res->assertSee('css/tokens.css', false);
        $res->assertSee('css/components.css', false);
    }
}
