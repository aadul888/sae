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
     * Test login orang tua dengan tanggal lahir salah ditolak.
     */
    public function test_login_orang_tua_with_wrong_date_rejected()
    {
        $response = $this->post('/login', [
            'login_type'     => 'orang_tua',
            'nisn_anak'      => $this->nisn,
            'tgl_lahir_anak' => '01-01-2015',
        ]);

        $response->assertSessionHas('error');
        $this->assertNull(session('user'));
    }
}
