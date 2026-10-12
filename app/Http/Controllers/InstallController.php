<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use PDO;
use Exception;
use Throwable;

class InstallController extends Controller
{
    public function index()
    {
        // 1. Hapus bootstrap config cache jika ada agar .env terbaca segar
        $configCache = base_path('bootstrap/cache/config.php');
        if (File::exists($configCache)) {
            @unlink($configCache);
        }

        // 2. Evaluasi status instalasi saat ini
        $status = $this->getInstallationStatus();

        // Jika sudah lengkap terinstal (DB terhubung & akun pengguna ada), redirect ke login
        if ($status['installed']) {
            return redirect()->route('login')->with('info', 'Sistem SAE sudah terinstal. Silakan login.');
        }

        return view('install.index', compact('status'));
    }

    public function finish()
    {
        $configCache = base_path('bootstrap/cache/config.php');
        if (File::exists($configCache)) {
            @unlink($configCache);
        }

        $status = $this->getInstallationStatus();
        if ($status['db_connected'] && $status['has_tables']) {
            return redirect()->route('login')->with('success', 'Instalasi sistem SAE berhasil! Silakan login menggunakan akun default Administrator.');
        }

        return redirect()->route('install.index')->with('warning', 'Database belum siap atau konfigurasi belum lengkap.');
    }

    public function process(Request $request)
    {
        @ini_set('max_execution_time', '300');
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        $isAjax = $request->expectsJson() || $request->ajax() || $request->wantsJson();

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
            } catch (Throwable $directEx) {
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
                } catch (Throwable $rootEx) {
                    $lastError = $rootEx->getMessage();
                }
            }
        }

        if (!$connected || !$pdo) {
            $msg = 'Gagal terhubung ke MySQL Server: ' . $lastError;
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->withInput()->with('error', $msg);
        }

        // 2. Tentukan APP_KEY yang aman (pertahankan jika ada agar sesi tidak hangus)
        $appKey = $this->resolveAppKey();

        // 3. Tulis / Update file .env dengan escaping kutip aman
        $appUrl = $request->getSchemeAndHttpHost();
        $this->writeEnvFile([
            'APP_NAME' => 'SAE',
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => $appUrl,
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $workingHost,
            'DB_PORT' => $port,
            'DB_DATABASE' => $database,
            'DB_USERNAME' => $username,
            'DB_PASSWORD' => $password,
            'APP_KEY' => $appKey,
        ]);

        // 4. Konfigurasi runtime DB sementara untuk migrasi/impor
        config([
            'app.key' => $appKey,
            'database.default' => 'mysql',
            'database.connections.mysql.host' => $workingHost,
            'database.connections.mysql.port' => $port,
            'database.connections.mysql.database' => $database,
            'database.connections.mysql.username' => $username,
            'database.connections.mysql.password' => $password,
        ]);
        DB::purge('mysql');

        // 5. Impor database SQL bawaan & migrasi skema terbaru
        try {
            $pdo->exec("USE `{$database}`;");

            $existingTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            $hasPengguna = in_array('pengguna', $existingTables);

            // Impor db_sae.sql jika tabel pengguna belum ada (mencegah error 'Table already exists')
            if (!$hasPengguna) {
                $sqlFile = database_path('db_sae.sql');
                if (File::exists($sqlFile)) {
                    $sql = File::get($sqlFile);
                    $pdo->exec($sql);
                }
            }

            // Pastikan akun bawaan (Admin) ada jika tabel pengguna kosong
            $this->ensureDefaultAccounts($pdo);

            // Jalankan migrasi tambahan dengan aman
            try {
                Artisan::call('migrate', [
                    '--force' => true,
                ]);
            } catch (Throwable $migEx) {
                // Abaikan jika migrasi minor gagal namun skema inti siap
            }

            // Hubungkan storage symlink secara aman
            try {
                Artisan::call('storage:link', [
                    '--force' => true,
                ]);
            } catch (Throwable $linkEx) {
                // Abaikan jika symlink dilarang di lingkungan hosting / Windows
            }

            // Bersihkan cache optimasi
            try {
                Artisan::call('optimize:clear');
            } catch (Throwable $optEx) {
                @unlink(base_path('bootstrap/cache/config.php'));
                @unlink(base_path('bootstrap/cache/routes-v7.php'));
            }
        } catch (Throwable $e) {
            $errorMsg = 'Gagal memproses skema database: ' . $e->getMessage();
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => $errorMsg], 422);
            }
            return back()->withInput()->with('error', $errorMsg);
        }

        $successMsg = 'Instalasi SAE berhasil! Silakan login menggunakan akun default.';
        if ($isAjax) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'redirect' => route('login'),
            ]);
        }

        return redirect()->route('login')->with('success', $successMsg);
    }

    private function resolveAppKey(): string
    {
        $existing = config('app.key');
        if (!empty($existing)) {
            return $existing;
        }

        $envPath = base_path('.env');
        if (File::exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (preg_match('/^APP_KEY=(.+)$/', trim($line), $m)) {
                    $key = trim($m[1], " \t\n\r\0\x0B\"'");
                    if (!empty($key)) {
                        return $key;
                    }
                }
            }
        }

        return 'base64:' . base64_encode(random_bytes(32));
    }

    private function ensureDefaultAccounts(PDO $pdo): void
    {
        try {
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('pengguna', $tables)) {
                return;
            }

            $adminCount = $pdo->query("SELECT COUNT(*) FROM `pengguna` WHERE `username` = 'admin@sae.id'")->fetchColumn();
            if ($adminCount == 0) {
                $stmt = $pdo->prepare("INSERT INTO `pengguna` (`pengguna_id`, `username`, `nama`, `peran_id_str`, `password`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
                // Password default: Admin543!
                $stmt->execute(['seed-admin-1', 'admin@sae.id', 'Administrator', 'Administrator', '$2y$12$DZRVMUgFWgc0MUhZY/4maOM15Lrw71j0FO4qi0/PphVu7GiHCnpzi']);
            }
        } catch (Throwable $e) {
            // Abaikan jika tabel belum siap
        }
    }

    private function getInstallationStatus(): array
    {
        $envExists = File::exists(base_path('.env'));
        $dbConnected = false;
        $hasTables = false;
        $hasPengguna = false;
        $tableCount = 0;
        $errorMessage = null;

        $envConfig = $this->readEnvDatabaseConfig();

        if ($envExists && !empty($envConfig['database'])) {
            try {
                $host = $envConfig['host'] ?: '127.0.0.1';
                $port = $envConfig['port'] ?: '3306';
                $db = $envConfig['database'];
                $user = $envConfig['username'] ?: 'root';
                $pass = $envConfig['password'] ?? '';

                $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db}", $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5,
                ]);
                $dbConnected = true;

                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                $tableCount = count($tables);
                $hasTables = $tableCount > 0;
                $hasPengguna = in_array('pengguna', $tables);

                if ($hasPengguna) {
                    $userCount = $pdo->query("SELECT COUNT(*) FROM `pengguna`")->fetchColumn();
                    if ($userCount == 0) {
                        // Otomatis seed akun bawaan jika tabel pengguna masih kosong
                        $this->ensureDefaultAccounts($pdo);
                        $userCount = $pdo->query("SELECT COUNT(*) FROM `pengguna`")->fetchColumn();
                    }

                    if ($userCount > 0) {
                        return [
                            'installed' => true,
                            'env_exists' => true,
                            'db_connected' => true,
                            'has_tables' => true,
                            'table_count' => $tableCount,
                            'has_pengguna' => true,
                            'env_config' => $envConfig,
                            'error' => null,
                        ];
                    }
                }
            } catch (Throwable $e) {
                $errorMessage = $e->getMessage();
            }
        }

        return [
            'installed' => false,
            'env_exists' => $envExists,
            'db_connected' => $dbConnected,
            'has_tables' => $hasTables,
            'has_pengguna' => $hasPengguna,
            'table_count' => $tableCount,
            'env_config' => $envConfig,
            'error' => $errorMessage,
        ];
    }

    private function readEnvDatabaseConfig(): array
    {
        $envPath = base_path('.env');
        $result = [
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => '',
            'username' => '',
            'password' => '',
        ];

        if (!File::exists($envPath)) {
            return $result;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            if (preg_match('/^DB_HOST=(.*)$/', $line, $m)) {
                $result['host'] = trim($m[1], " \t\n\r\0\x0B\"'");
            } elseif (preg_match('/^DB_PORT=(.*)$/', $line, $m)) {
                $result['port'] = trim($m[1], " \t\n\r\0\x0B\"'");
            } elseif (preg_match('/^DB_DATABASE=(.*)$/', $line, $m)) {
                $result['database'] = trim($m[1], " \t\n\r\0\x0B\"'");
            } elseif (preg_match('/^DB_USERNAME=(.*)$/', $line, $m)) {
                $result['username'] = trim($m[1], " \t\n\r\0\x0B\"'");
            } elseif (preg_match('/^DB_PASSWORD=(.*)$/', $line, $m)) {
                $result['password'] = trim($m[1], " \t\n\r\0\x0B\"'");
            }
        }

        return $result;
    }

    private function isInstalled(): bool
    {
        return $this->getInstallationStatus()['installed'];
    }

    private function writeEnvFile(array $data)
    {
        $envPath = base_path('.env');
        $examplePath = base_path('.env.example');

        $content = File::exists($envPath) ? File::get($envPath) : (File::exists($examplePath) ? File::get($examplePath) : '');

        foreach ($data as $key => $value) {
            $escapedValue = addcslashes((string)$value, '"\\$');
            $lineValue = '"' . $escapedValue . '"';

            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$lineValue}", $content);
            } else {
                $content .= "\n{$key}={$lineValue}";
            }
        }

        File::put($envPath, $content);
        @chmod($envPath, 0664);

        $cachedConfig = base_path('bootstrap/cache/config.php');
        if (File::exists($cachedConfig)) {
            @unlink($cachedConfig);
        }
    }
}

