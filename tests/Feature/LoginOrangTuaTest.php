<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class LoginOrangTuaTest extends TestCase
{
    protected string $nisn = '0095591334';
    protected string $nik = '3203180702090002';

    /**
     * Test tampilan halaman login memiliki atribut numeric keypad dan calendar picker.
     */
    public function test_login_page_renders_numeric_keypad_and_calendar_picker()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);

        // NISN / NIK input attributes
        $response->assertSee('name="nisn_anak"', false);
        $response->assertSee('inputmode="numeric"', false);
        $response->assertSee('pattern="[0-9]*"', false);

        // Tanggal Lahir input attributes & calendar picker overlay
        $response->assertSee('name="tgl_lahir_anak"', false);
        $response->assertSee('id="inputTglLahirAnak"', false);
        $response->assertSee('id="btnPickerWrapper"', false);
        $response->assertSee('id="nativeDatePicker"', false);
        $response->assertSee('class="calendar-picker-btn"', false);
    }

    /**
     * Test login orang tua dengan format DD-MM-YYYY.
     */
    public function test_login_orang_tua_with_dd_mm_yyyy()
    {
        $response = $this->post('/login', [
            'login_type'     => 'orang_tua',
            'nisn_anak'      => $this->nisn,
            'tgl_lahir_anak' => '07-02-2009',
        ]);

        $response->assertRedirect(route('dashboard.orang-tua'));
        $response->assertSessionHas('user');
        $this->assertEquals('orang_tua', session('user.role'));
    }

    /**
     * Test login orang tua dengan format YYYY-MM-DD (format native picker).
     */
    public function test_login_orang_tua_with_yyyy_mm_dd()
    {
        $response = $this->post('/login', [
            'login_type'     => 'orang_tua',
            'nisn_anak'      => $this->nisn,
            'tgl_lahir_anak' => '2009-02-07',
        ]);

        $response->assertRedirect(route('dashboard.orang-tua'));
        $response->assertSessionHas('user');
    }

    /**
     * Test login orang tua dengan angka murni 8 digit (DDMMYYYY).
     */
    public function test_login_orang_tua_with_raw_ddmmyyyy()
    {
        $response = $this->post('/login', [
            'login_type'     => 'orang_tua',
            'nisn_anak'      => $this->nisn,
            'tgl_lahir_anak' => '07022009',
        ]);

        $response->assertRedirect(route('dashboard.orang-tua'));
        $response->assertSessionHas('user');
    }

    /**
     * Test login orang tua dengan format slash DD/MM/YYYY.
     */
    public function test_login_orang_tua_with_slash_format()
    {
        $response = $this->post('/login', [
            'login_type'     => 'orang_tua',
            'nisn_anak'      => $this->nisn,
            'tgl_lahir_anak' => '07/02/2009',
        ]);

        $response->assertRedirect(route('dashboard.orang-tua'));
        $response->assertSessionHas('user');
    }

    /**
     * Test login orang tua dengan NIK siswa.
     */
    public function test_login_orang_tua_using_nik_as_pin()
    {
        $response = $this->post('/login', [
            'login_type'     => 'orang_tua',
            'nisn_anak'      => $this->nisn,
            'tgl_lahir_anak' => $this->nik,
        ]);

        $response->assertRedirect(route('dashboard.orang-tua'));
        $response->assertSessionHas('user');
    }

    /**
     * Test halaman kehadiran orang tua merender kalender presensi dan legenda warna.
     */
    public function test_orang_tua_kehadiran_page_renders_calendar_view()
    {
        $this->post('/login', [
            'login_type'     => 'orang_tua',
            'nisn_anak'      => $this->nisn,
            'tgl_lahir_anak' => '07-02-2009',
        ]);

        $response = $this->get('/dashboard/orang-tua/kehadiran');
        $response->assertStatus(200);
        $response->assertSee('id="viewKalenderHarian"', false);
        $response->assertSee('class="cal-table"', false);
        $response->assertSee('class="cal-legend-bar"', false);
        $response->assertSee('id="detailPresensiBox"', false);
        $response->assertSee('id="btnViewKalender"', false);
        $response->assertSee('id="btnViewTabel"', false);
        $response->assertSee('id="selectBulanKalender"', false);
        $response->assertSee('id="selectKategoriKehadiran"', false);
        $response->assertSee('id="panelTabelContainer"', false);
        $response->assertSee('id="calHeaderTitle"', false);
        $response->assertSee('id="calFilterAlert"', false);
        $response->assertSee('id="subPanelHarian"', false);
        $response->assertSee('id="subPanelMapel"', false);
        $response->assertSee('id="subPanelIzinKeluar"', false);
        $response->assertSee('id="subPanelSuratIzin"', false);
        $response->assertSee('id="subPanelKedisiplinan"', false);
    }

    /**
     * Test pergantian bulan kalender via AJAX request mengembalikan JSON kalender & tabel partial.
     */
    public function test_orang_tua_kehadiran_ajax_month_switch()
    {
        $this->post('/login', [
            'login_type'     => 'orang_tua',
            'nisn_anak'      => $this->nisn,
            'tgl_lahir_anak' => '07-02-2009',
        ]);

        $response = $this->getJson('/dashboard/orang-tua/kehadiran?bulan=' . now()->subMonth()->format('Y-m') . '&ajax=1');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'selectedMonth',
            'monthLabel',
            'prevMonth',
            'nextMonth',
            'availableMonths',
            'statsHarian',
            'calendarHtml',
            'tableHtml',
        ]);
        $response->assertJson(['status' => 'success']);
    }
}
