<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi login gabungan (GTK/Peserta Didik dan Portal Orang Tua).
 *
 * Satu form request dipakai karena kedua alur berbagi route & controller
 * yang sama; cabangnya ditentukan oleh `login_type` atau keberadaan
 * `nisn_anak`. Aturan disusun kondisional agar perilaku identik dengan
 * validasi inline sebelumnya.
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Apakah permintaan ini untuk Portal Orang Tua?
     */
    private function isParentLogin(): bool
    {
        return $this->input('login_type') === 'orang_tua'
            || $this->filled('nisn_anak');
    }

    public function rules(): array
    {
        if ($this->isParentLogin()) {
            return [
                'nisn_anak'      => ['required', 'string', 'max:255'],
                'tgl_lahir_anak' => ['required', 'string', 'max:255'],
            ];
        }

        return [
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        if ($this->isParentLogin()) {
            return [
                'nisn_anak.required'      => 'NISN atau NIK peserta didik wajib diisi.',
                'tgl_lahir_anak.required' => 'Password/PIN (Tanggal lahir peserta didik) wajib diisi.',
            ];
        }

        return [
            'username.required' => 'Username / NISN / NIP / Email wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ];
    }

    /**
     * Rapikan spasi masukan sebelum divalidasi.
     */
    protected function prepareForValidation(): void
    {
        $merged = [];

        foreach (['username', 'nisn_anak', 'tgl_lahir_anak'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $merged[$field] = trim((string) $this->input($field));
            }
        }

        if ($merged) {
            $this->merge($merged);
        }
    }
}
