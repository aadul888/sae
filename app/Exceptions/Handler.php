<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (HttpException $e, $request) {
            if ($e->getStatusCode() === 419 && $request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Sesi halaman telah berakhir. Muat ulang halaman lalu ulangi aksi Anda.',
                ], 419);
            }

            if ($e->getStatusCode() === 403 && $request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Akses ditolak: Anda tidak memiliki izin untuk mengakses fitur ini.',
                ], 403);
            }
        });
    }
}
