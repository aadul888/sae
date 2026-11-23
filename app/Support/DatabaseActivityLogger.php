<?php

namespace App\Support;

use App\Models\AdminAktivitas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Throwable;

/**
 * Logger transaksi basis data otomatis.
 *
 * Mendengarkan event Eloquent created/updated/deleted untuk SEMUA model
 * sehingga setiap perubahan data tercatat di Monitoring tanpa perlu
 * memanggil AdminAktivitas::record() di tiap controller.
 */
final class DatabaseActivityLogger
{
    /** Tabel yang tidak perlu dicatat (noise / infrastruktur). */
    private const IGNORED_TABLES = [
        'admin_aktivitas',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'migrations',
        'personal_access_tokens',
        'password_reset_tokens',
        'notifications',
    ];

    private static ?bool $tableReady = null;

    public static function register(): void
    {
        Event::listen(
            ['eloquent.created: *', 'eloquent.updated: *', 'eloquent.deleted: *'],
            function (string $eventName, array $payload) {
                try {
                    self::handle($eventName, $payload[0] ?? null);
                } catch (Throwable $e) {
                    // Logging tidak boleh menggagalkan transaksi bisnis.
                }
            }
        );
    }

    private static function handle(string $eventName, ?Model $model): void
    {
        if (!$model instanceof Model) {
            return;
        }

        // Hindari rekursi & noise dari proses CLI (migrasi, seeder, artisan),
        // tetapi tetap aktif saat unit test agar perilaku bisa diverifikasi.
        if (app()->runningInConsole() && !app()->runningUnitTests()) {
            return;
        }

        $table = $model->getTable();
        if (in_array($table, self::IGNORED_TABLES, true)) {
            return;
        }

        if (!self::tableReady()) {
            return;
        }

        $action = match (true) {
            str_starts_with($eventName, 'eloquent.created') => 'CREATE',
            str_starts_with($eventName, 'eloquent.updated') => 'UPDATE',
            default => 'DELETE',
        };

        $label = Str::headline($table);
        $modul = MonitoringMenu::groupForRoute(request()->route()?->getName()) ?? $label;

        AdminAktivitas::record(
            '[' . $action . '] ' . $label,
            $modul,
            self::describe($model, $action),
            match ($action) {
                'CREATE' => 'success',
                'UPDATE' => 'info',
                default => 'warning',
            }
        );
    }

    /** Ringkasan payload perubahan agar tabel tetap terbaca. */
    private static function describe(Model $model, string $action): string
    {
        $key = $model->getKeyName() . '#' . ($model->getKey() ?? '-');

        if ($action === 'DELETE') {
            return 'Menghapus data ' . $model->getTable() . ' (' . $key . ')';
        }

        $changed = $action === 'UPDATE' ? array_keys($model->getChanges()) : array_keys($model->getAttributes());
        $changed = array_values(array_diff($changed, [$model->getUpdatedAtColumn(), $model->getCreatedAtColumn()]));

        if ($changed === []) {
            return 'Menyimpan data ' . $model->getTable() . ' (' . $key . ')';
        }

        $shown = array_slice($changed, 0, 8);
        $extra = count($changed) - count($shown);

        return ucfirst(strtolower($action)) . ' ' . $model->getTable() . ' (' . $key . '): '
            . implode(', ', $shown) . ($extra > 0 ? ' +' . $extra . ' kolom' : '');
    }

    private static function tableReady(): bool
    {
        if (self::$tableReady === null) {
            self::$tableReady = Table::exists('admin_aktivitas');
        }
        return self::$tableReady;
    }
}
