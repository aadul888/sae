<?php

namespace App\Http\Controllers;

use App\Services\UpdateService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UpdateController extends Controller
{
    protected UpdateService $updateService;

    public function __construct(UpdateService $updateService)
    {
        $this->updateService = $updateService;
    }

    /**
     * Halaman update sistem untuk Operator / Admin
     */
    public function index()
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') return redirect()->route('dashboard.' . ($role ?: 'peserta_didik'));

        $status = $this->updateService->checkUpdate();
        return view('dashboard.update', compact('status'));
    }

    /**
     * API Cek update realtime
     */
    public function check(): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $status = $this->updateService->checkUpdate();
        return response()->json([
            'status' => 'success',
            'data' => $status,
        ]);
    }

    /**
     * Proses eksekusi update otomatis (File, Kode, Database)
     */
    public function execute(): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $result = $this->updateService->runUpdate();
        return response()->json([
            'status' => $result['success'] ? 'success' : 'error',
            'data' => $result,
        ]);
    }

    /**
     * Diagnostic endpoint - cek environment server untuk debugging update
     */
    public function diagnose(): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $basePath = base_path();
        $info = [];

        // PHP user
        $info['php_user'] = trim(@shell_exec('whoami 2>&1') ?: 'unknown');
        $info['php_sapi'] = PHP_SAPI;
        $info['base_path'] = $basePath;

        // Git availability
        $info['git_version'] = trim(@shell_exec('git --version 2>&1') ?: 'NOT FOUND');
        $info['git_exists'] = is_dir($basePath . '/.git');

        // Git safe directory check
        $info['git_safe_dir'] = trim(@shell_exec('git config --global --get-all safe.directory 2>&1') ?: 'none');

        // Try git status
        $info['git_status'] = trim(@shell_exec('git -C ' . escapeshellarg($basePath) . ' status --short 2>&1') ?: 'empty');

        // Owner of .git folder
        if (is_dir($basePath . '/.git') && DIRECTORY_SEPARATOR === '/') {
            $info['git_owner'] = trim(@shell_exec('stat -c "%U:%G" ' . escapeshellarg($basePath . '/.git') . ' 2>&1') ?: 'unknown');
            $info['web_root_owner'] = trim(@shell_exec('stat -c "%U:%G" ' . escapeshellarg($basePath) . ' 2>&1') ?: 'unknown');
        }

        // Test git fetch
        $branch = trim(@shell_exec('git -C ' . escapeshellarg($basePath) . ' rev-parse --abbrev-ref HEAD 2>&1') ?: 'main');
        $info['current_branch'] = $branch;
        $fetchResult = @shell_exec('GIT_TERMINAL_PROMPT=0 git -C ' . escapeshellarg($basePath) . ' fetch origin ' . escapeshellarg($branch) . ' 2>&1');
        $info['fetch_result'] = trim($fetchResult ?: 'no output');

        // Test git pull dry run
        $pullDry = @shell_exec('GIT_TERMINAL_PROMPT=0 git -C ' . escapeshellarg($basePath) . ' pull --dry-run --no-rebase origin ' . escapeshellarg($branch) . ' 2>&1');
        $info['pull_dry_run'] = trim($pullDry ?: 'no output');

        // shell_exec available
        $info['shell_exec_enabled'] = function_exists('shell_exec');
        $info['exec_enabled'] = function_exists('exec');

        // disabled functions
        $info['disabled_functions'] = ini_get('disable_functions');

        return response()->json([
            'status' => 'success',
            'data' => $info,
        ]);
    }
}
