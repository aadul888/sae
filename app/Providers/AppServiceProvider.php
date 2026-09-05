<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' || app()->environment('production')) {
            URL::forceScheme('https');
        }

        View::composer('*', function ($view) {
            static $appVersion = null;
            if ($appVersion === null) {
                $appVersion = '1.0.1';
                try {
                    if (file_exists(base_path('.env'))) {
                        $appVersion = DB::table('settings')->where('id', 1)->value('app_version') ?: $appVersion;
                    }
                } catch (\Throwable $e) {
                    // Fallback to default version if DB not ready or table missing
                }
            }
            $view->with('appVersion', $appVersion);
        });
    }
}
