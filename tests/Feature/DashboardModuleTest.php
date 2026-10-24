<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class DashboardModuleTest extends TestCase
{
    public function test_guest_is_redirected_from_all_dashboards(): void
    {
        $this->get(route('dashboard.admin'))->assertStatus(302)->assertRedirect(route('login'));
        $this->get(route('dashboard.guru'))->assertStatus(302)->assertRedirect(route('login'));
        $this->get(route('dashboard.tendik'))->assertStatus(302)->assertRedirect(route('login'));
        $this->get(route('dashboard.peserta-didik'))->assertStatus(302)->assertRedirect(route('login'));
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $sessionUser = [
            'id'          => 'admin-dash-test',
            'pengguna_id' => 'admin-dash-test',
            'username'    => 'admin',
            'nama'        => 'Administrator Test',
            'role'        => 'admin',
        ];

        $response = $this->withSession(['user' => $sessionUser])
            ->get(route('dashboard.admin'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.admin');
        $response->assertViewHas('stats');
        $response->assertViewHas('chartTrend');
    }

    public function test_guru_can_access_guru_dashboard(): void
    {
        $sessionUser = [
            'id'          => 'guru-dash-test',
            'pengguna_id' => 'guru-dash-test',
            'username'    => 'guru_test',
            'nama'        => 'Guru Test',
            'role'        => 'guru',
        ];

        $response = $this->withSession(['user' => $sessionUser])
            ->get(route('dashboard.guru'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.guru');
        $response->assertViewHas('stats');
    }

    public function test_tendik_can_access_tendik_dashboard(): void
    {
        $sessionUser = [
            'id'          => 'tendik-dash-test',
            'pengguna_id' => 'tendik-dash-test',
            'username'    => 'tendik_test',
            'nama'        => 'Tendik Test',
            'role'        => 'tendik',
        ];

        $response = $this->withSession(['user' => $sessionUser])
            ->get(route('dashboard.tendik'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.tendik');
        $response->assertViewHas('stats');
    }
}
