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
        $request->validate([
            'db_host' => 'required',
            'db_port' => 'required|numeric',
            'db_name' => 'required',
            'db_user' => 'required',
        ]);

        $host = $request->input('db_host');
        $port = $request->input('db_port');
        $database = $request->input('db_name');
        $username = $request->input('db_user');
        $password = $request->input('db_pass') ?? '';

        // 1. Tes koneksi MySQL Server & Buat Database jika belum ada
        try {
            $pdo = new PDO("mysql:host={$host};port={$port}", $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Gagal terhubung ke MySQL Server: ' . $e->getMessage());
        }

        // 2. Tulis / Update file .env
        $this->writeEnvFile([
            'DB_HOST' => $host,
            'DB_PORT' => $port,
            'DB_DATABASE' => $database,
            'DB_USERNAME' => $username,
            'DB_PASSWORD' => $password,
            'APP_KEY' => config('app.key') ?: 'base64:' . base64_encode(random_bytes(32)),
        ]);

        // 3. Konfigurasi runtime DB sementara untuk impor SQL
        config([
            'database.connections.mysql.host' => $host,
            'database.connections.mysql.port' => $port,
            'database.connections.mysql.database' => $database,
            'database.connections.mysql.username' => $username,
            'database.connections.mysql.password' => $password,
        ]);
        DB::purge('mysql');

        // 4. Impor database SQL bawaan (database/db_sae.sql)
        try {
            $sqlFile = database_path('db_sae.sql');
            if (File::exists($sqlFile)) {
                $sql = File::get($sqlFile);
                $pdo->exec("USE `{$database}`;");
                $pdo->exec($sql);
            } else {
                // Fallback ke migration & seed jika file sql tidak ada
                Artisan::call('migrate:fresh', [
                    '--force' => true,
                    '--seed' => true,
                ]);
            }
            Artisan::call('config:clear');
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Gagal mengimpor database default: ' . $e->getMessage());
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
