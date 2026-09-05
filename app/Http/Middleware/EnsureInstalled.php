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

        $installed = false;
        if (File::exists(base_path('.env'))) {
            try {
                DB::connection()->getPdo();
                $installed = DB::getSchemaBuilder()->hasTable('pengguna') && DB::table('pengguna')->exists();
            } catch (\Exception $e) {
                $installed = false;
            }
        }

        if (!$installed && !$isInstallRoute) {
            return redirect()->route('install.index');
        }

        if ($installed && $isInstallRoute) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
