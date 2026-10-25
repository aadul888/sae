<?php

namespace Tests\Feature;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\PesertaDidik\ImportPesertaDidikRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Memverifikasi aturan validasi pada FormRequest.
 */
class FormRequestValidationTest extends TestCase
{
    private function loginRules(): array
    {
        $request = LoginRequest::create('/login', 'POST', ['username' => 'x', 'password' => 'y']);

        return (new LoginRequest())->rules();
    }

    public function test_login_request_uses_username_rule_by_default(): void
    {
        $request = LoginRequest::create('/login', 'POST', ['username' => 'admin', 'password' => 'rahasia']);

        $rules = $request->rules();

        $this->assertArrayHasKey('username', $rules);
        $this->assertArrayHasKey('password', $rules);
        $this->assertArrayNotHasKey('nisn_anak', $rules);
    }

    public function test_login_request_uses_parent_rules_when_parent_login(): void
    {
        $request = LoginRequest::create('/login', 'POST', [
            'login_type' => 'orang_tua',
            'nisn_anak' => '1234567890',
            'tgl_lahir_anak' => '01-01-2010',
        ]);

        $rules = $request->rules();

        $this->assertArrayHasKey('nisn_anak', $rules);
        $this->assertArrayHasKey('tgl_lahir_anak', $rules);
        $this->assertArrayNotHasKey('username', $rules);
    }

    public function test_login_request_rejects_empty_credentials(): void
    {
        $request = LoginRequest::create('/login', 'POST', ['username' => '', 'password' => '']);
        $validator = Validator::make($request->all(), $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('username', $validator->errors()->toArray());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    public function test_login_request_trims_username(): void
    {
        $request = LoginRequest::create('/login', 'POST', ['username' => '  admin  ', 'password' => 'x']);

        // prepareForValidation dijalankan oleh container; panggil manual untuk uji unit.
        $reflection = new \ReflectionMethod($request, 'prepareForValidation');
        $reflection->setAccessible(true);
        $reflection->invoke($request);

        $this->assertSame('admin', $request->input('username'));
    }

    public function test_import_request_rejects_executable_files(): void
    {
        $request = new ImportPesertaDidikRequest();
        $rules = $request->rules();

        // Berkas .php harus ditolak oleh aturan mimes/extensions.
        $file = UploadedFile::fake()->create('shell.php', 10, 'application/x-php');

        $validator = Validator::make(['file_excel' => $file], $rules);

        $this->assertTrue(
            $validator->fails(),
            'Berkas .php seharusnya ditolak oleh validasi impor.'
        );
    }

    public function test_import_request_rejects_zip_files(): void
    {
        $rules = (new ImportPesertaDidikRequest())->rules();
        $file = UploadedFile::fake()->create('arsip.zip', 10, 'application/zip');

        $validator = Validator::make(['file_excel' => $file], $rules);

        $this->assertTrue($validator->fails(), 'Berkas .zip seharusnya ditolak.');
    }

    public function test_import_request_accepts_xlsx(): void
    {
        $rules = (new ImportPesertaDidikRequest())->rules();
        $file = UploadedFile::fake()->create('data.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $validator = Validator::make(['file_excel' => $file], $rules);

        $this->assertFalse(
            $validator->fails(),
            'Berkas .xlsx yang sah seharusnya diterima: ' . json_encode($validator->errors()->toArray())
        );
    }

    public function test_import_request_requires_file(): void
    {
        $rules = (new ImportPesertaDidikRequest())->rules();
        $validator = Validator::make([], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('file_excel', $validator->errors()->toArray());
    }
}
