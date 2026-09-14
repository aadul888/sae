<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use PDO;
use Exception;

class InstallController extends Controller
{
    public function index()
    {
        // Jika sudah terinstal (.env ada & DB terhubung & users terisi), redirect ke login
        if ($this->isInstalled()) {
            return redirect()->route('login')->with('info', 'Sistem SAE sudah terinstal.');
        }

        return view('install.index');
    }

    public function process(Request $request)
    {
        @ini_set('max_execution_time', '300');
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        $request->validate([
            'db_name' => 'required',
            'db_user' => 'required',
        ]);

        $host = trim($request->input('db_host') ?: '127.0.0.1');
        $port = trim($request->input('db_port') ?: '3306');
        $database = trim($request->input('db_name'));
        $username = trim($request->input('db_user'));
        $password = (string) ($request->input('db_pass') ?? '');

        // 1. Tes koneksi MySQL Server & Buat/Gunakan Database (Kompatibel VPS, Linux Socket, & Shared Hosting)
        $connected = false;
        $lastError = '';
        $pdo = null;
        $workingHost = $host;

        $hostsToTry = array_unique(array_filter([
            $host,
            ($host === '127.0.0.1' ? 'localhost' : ($host === 'localhost' ? '127.0.0.1' : null))
        ]));

        foreach ($hostsToTry as $tryHost) {
            try {
                // Coba koneksi langsung ke DB spesifik (umumnya sudah dibuat terlebih dahulu di cPanel/Hostinger)
                $pdo = new PDO("mysql:host={$tryHost};port={$port};dbname={$database}", $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 10,
                    PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
                ]);
                $workingHost = $tryHost;
                $connected = true;
                break;
            } catch (Exception $directEx) {
                $lastError = $directEx->getMessage();
                // Fallback: Konek ke server root MySQL dan buat database jika izin mencukupi (VPS / Local)
                try {
                    $pdo = new PDO("mysql:host={$tryHost};port={$port}", $username, $password, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_TIMEOUT => 10,
                        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
                    ]);
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                    $pdo->exec("USE `{$database}`;");
                    $workingHost = $tryHost;
                    $connected = true;
                    break;
                } catch (Exception $rootEx) {
                    $lastError = $rootEx->getMessage();
                }
            }
        }

        if (!$connected || !$pdo) {
            return back()->withInput()->with('error', 'Gagal terhubung ke MySQL Server: ' . $lastError);
        }

        // 2. Tulis / Update file .env
        $appUrl = $request->getSchemeAndHttpHost();
        $this->writeEnvFile([
            'APP_NAME' => 'SAE',
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => $appUrl,
            'DB_HOST' => $workingHost,
            'DB_PORT' => $port,
            'DB_DATABASE' => $database,
            'DB_USERNAME' => $username,
            'DB_PASSWORD' => $password,
            'APP_KEY' => config('app.key') ?: 'base64:' . base64_encode(random_bytes(32)),
        ]);

        // 3. Konfigurasi runtime DB sementara untuk impor SQL
        config([
            'database.connections.mysql.host' => $workingHost,
            'database.connections.mysql.port' => $port,
            'database.connections.mysql.database' => $database,
            'database.connections.mysql.username' => $username,
            'database.connections.mysql.password' => $password,
        ]);
        DB::purge('mysql');

        // 4. Impor database SQL bawaan (database/db_sae.sql) dan migrasi skema terbaru
        try {
            $sqlFile = database_path('db_sae.sql');
            if (File::exists($sqlFile)) {
                $sql = File::get($sqlFile);
                $pdo->exec("USE `{$database}`;");
                $pdo->exec($sql);
            }

            // Jalankan migrasi tambahan (seperti tabel kalender_pendidikan, role_permissions, dll)
            Artisan::call('migrate', [
                '--force' => true,
            ]);

            // Hubungkan storage symlink
            Artisan::call('storage:link', [
                '--force' => true,
            ]);

            Artisan::call('optimize:clear');
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Gagal mengimpor skema database: ' . $e->getMessage());
        }

        return redirect()->route('login')->with('success', 'Instalasi SAE berhasil! Silakan login menggunakan akun default.');
    }

    private function isInstalled(): bool
    {
        if (!File::exists(base_path('.env'))) {
            return false;
        }

        try {
            DB::connection()->getPdo();
            return DB::table('pengguna')->exists();
        } catch (Exception $e) {
            return false;
        }
    }

    private function writeEnvFile(array $data)
    {
        $envPath = base_path('.env');
        $examplePath = base_path('.env.example');

        $content = File::exists($envPath) ? File::get($envPath) : (File::exists($examplePath) ? File::get($examplePath) : '');

        foreach ($data as $key => $value) {
            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
            } else {
                $content .= "\n{$key}={$value}";
            }
        }

        File::put($envPath, $content);
    }
}
