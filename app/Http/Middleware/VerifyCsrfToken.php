<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery as Middleware;

/**
 * Perlindungan CSRF aplikasi.
 *
 * Laravel 13 mengganti nama `VerifyCsrfToken` menjadi `PreventRequestForgery`
 * dan menambahkan verifikasi origin permintaan melalui header `Sec-Fetch-Site`.
 * Kelas ini mengikuti nama baru; `VerifyCsrfToken` hanya tersisa sebagai alias
 * usang sehingga tidak dipakai lagi.
 *
 * Daftarkan pengecualian pada properti $except bila benar-benar diperlukan
 * (mis. endpoint webhook pihak ketiga yang tidak mengirim token CSRF).
 */
class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];
}
