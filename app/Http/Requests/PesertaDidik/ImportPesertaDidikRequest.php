<?php

namespace App\Http\Requests\PesertaDidik;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi impor peserta didik dari berkas Excel.
 *
 * Sebelumnya hanya memeriksa `file|max:20480` tanpa membatasi ekstensi,
 * sehingga berkas apa pun (termasuk .php atau .zip) bisa diunggah ke jalur
 * yang sama. Aturan `mimes`/`extensions` ditambahkan agar hanya berkas
 * spreadsheet yang diterima.
 */
class ImportPesertaDidikRequest extends FormRequest
{
    /**
     * Otorisasi ditangani di controller (bergantung hak akses modul),
     * jadi FormRequest ini hanya menangani validasi bentuk data.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file_excel' => [
                'required',
                'file',
                'max:20480', // 20 MB
                'mimes:xlsx,xls,csv,txt',
                'extensions:xlsx,xls,csv,txt',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file_excel.required'   => 'Berkas Excel wajib dipilih.',
            'file_excel.max'        => 'Ukuran berkas maksimal adalah 20 MB.',
            'file_excel.mimes'      => 'Format berkas harus berupa Excel (.xlsx/.xls) atau CSV.',
            'file_excel.extensions' => 'Ekstensi berkas harus .xlsx, .xls, .csv, atau .txt.',
        ];
    }
}
