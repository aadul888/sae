#!/bin/bash
# Jalankan di server setelah git clone / git pull
# Otomatis deteksi user webserver (www / www-data / nginx / apache)
# Standar: Folder 755, File 644, Writable dirs 775/777

echo "🔧 SAE Deploy & System Synchronization"
echo "======================================"

# 1. Deteksi user webserver runtime
PHP_USER="www"
if id "www" &>/dev/null; then
    PHP_USER="www"
elif id "www-data" &>/dev/null; then
    PHP_USER="www-data"
elif id "nginx" &>/dev/null; then
    PHP_USER="nginx"
elif id "apache" &>/dev/null; then
    PHP_USER="apache"
else
    PHP_USER=$(whoami)
fi

echo "👤 Web User: $PHP_USER"

# 2. Sinkronisasi Autoloader Composer jika binary tersedia
if command -v composer &>/dev/null; then
    echo "📦 Regenerating optimized classloader..."
    composer dump-autoload -o --no-interaction --quiet 2>/dev/null || true
fi

# 3. Database Migration & Sinkronisasi Versi Sistem
if [ -f .env ]; then
    echo "🗄️ Running database migrations..."
    php artisan migrate --force 2>/dev/null || true

    # Sinkronisasi versi aplikasi di database
    php -r '
        require "vendor/autoload.php";
        $app = require_once "bootstrap/app.php";
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();
        try {
            if (Illuminate\Support\Facades\Schema::hasTable("settings")) {
                Illuminate\Support\Facades\DB::table("settings")->where("id", 1)->update([
                    "app_version" => \App\Services\UpdateService::CURRENT_VERSION,
                    "last_commit_hash" => trim(@shell_exec("git rev-parse HEAD") ?: ""),
                    "last_update_at" => now(),
                    "updated_at" => now(),
                ]);
            }
        } catch (\Throwable $e) {}
    ' 2>/dev/null || true
fi

# 4. Storage symlink & Log file
echo "🔗 Creating storage link..."
php artisan storage:link --force 2>/dev/null || true
touch storage/logs/laravel.log 2>/dev/null || true
chmod 664 storage/logs/laravel.log 2>/dev/null || true

# 5. Cache Laravel & Optimize (Laravel 12 Unified Cache)
if [ -f .env ]; then
    echo "⚡ Refreshing Laravel bootstrap & view caches..."
    php artisan optimize:clear 2>/dev/null || true
    php artisan optimize 2>/dev/null || true
else
    echo "⚡ Fresh install mode detected (.env not ready). Flushing cache only..."
    php artisan optimize:clear 2>/dev/null || true
fi

# 6. Set ownership & permissions Linux secara menyeluruh (Termasuk cache hasil optimize)
echo "🔒 Enforcing Linux permissions (Dirs: 755, Files: 644, Writable: 775)..."
chown -R $PHP_USER:$PHP_USER . 2>/dev/null || true
find . -type d -exec chmod 755 {} + 2>/dev/null || true
find . -type f -exec chmod 644 {} + 2>/dev/null || true
chmod -R 775 storage bootstrap/cache .git 2>/dev/null || true
chown -R $PHP_USER:$PHP_USER storage bootstrap/cache .git 2>/dev/null || true
chmod +x deploy.sh 2>/dev/null || true

# 7. Reset OPcache & Terminate Idle Workers (PHP-FPM & LiteSpeed LSPHP)
echo "🔄 Resetting OPcache & reloading web processes..."
if command -v systemctl &>/dev/null; then
    systemctl reload php8.3-fpm 2>/dev/null || systemctl reload php-fpm 2>/dev/null || true
fi
pkill -9 -u $PHP_USER lsphp 2>/dev/null || killall -9 lsphp 2>/dev/null || true
if [ -f /etc/init.d/lsws ]; then
    /etc/init.d/lsws reload 2>/dev/null || true
elif [ -f /usr/local/lsws/bin/lswsctrl ]; then
    /usr/local/lsws/bin/lswsctrl restart 2>/dev/null || true
fi
touch .lsphp_restart.txt 2>/dev/null || true

echo "✅ Deploy & system synchronization complete!"