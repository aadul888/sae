<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class EnsureInstalled
{
    public function handle(Request $request, Closure $next)
    {
        // Bypass untuk request API
        if ($request->is('api/*')) {
            return $next($request);
        }

        $isInstallRoute = $request->is('install*');

        if (!class_exists(\App\Support\SystemSignature::class) || !\App\Support\SystemSignature::verify()) {
            $dev = class_exists(\App\Support\SystemSignature::class)
                ? \App\Support\SystemSignature::resolve()
                : [];
            return response()->view('errors.integrity', compact('dev'), 503);
        }

        $installed = false;
        if (File::exists(base_path('.env'))) {
            try {
                DB::connection()->getPdo();
                if (DB::getSchemaBuilder()->hasTable('pengguna')) {
                    if (!DB::table('pengguna')->exists()) {
                        DB::table('pengguna')->insert([
                            'pengguna_id' => 'seed-admin-1',
                            'username' => 'admin@sae.id',
                            'nama' => 'Administrator',
                            'peran_id_str' => 'Administrator',
                            'password' => '$2y$12$DZRVMUgFWgc0MUhZY/4maOM15Lrw71j0FO4qi0/PphVu7GiHCnpzi',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    $installed = true;
                }
            } catch (\Throwable $e) {
                // Jika DB connection gagal namun ada bootstrap config cache stale, bersihkan dan coba sekali lagi
                $configCache = base_path('bootstrap/cache/config.php');
                if (File::exists($configCache)) {
                    @unlink($configCache);
                    try {
                        DB::purge('mysql');
                        DB::connection()->getPdo();
                        if (DB::getSchemaBuilder()->hasTable('pengguna')) {
                            if (!DB::table('pengguna')->exists()) {
                                DB::table('pengguna')->insert([
                                    'pengguna_id' => 'seed-admin-1',
                                    'username' => 'admin@sae.id',
                                    'nama' => 'Administrator',
                                    'peran_id_str' => 'Administrator',
                                    'password' => '$2y$12$DZRVMUgFWgc0MUhZY/4maOM15Lrw71j0FO4qi0/PphVu7GiHCnpzi',
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                            $installed = true;
                        }
                    } catch (\Throwable $retryEx) {
                        $installed = false;
                    }
                } else {
                    $installed = false;
                }
            }
        }

        if (!$installed && !$isInstallRoute) {
            return redirect()->route('install.index');
        }

        if ($installed && $isInstallRoute) {
            return redirect()->route('login')->with('info', 'Sistem SAE sudah terinstal. Silakan login.');
        }

        return $next($request);
    }
}
