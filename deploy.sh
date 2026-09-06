#!/bin/bash
# Jalankan di server setelah git pull
# PANEL: aaPanel/Baota — PHP-FPM run as user 'www'
# Jika pakai panel lain, ganti 'www' dengan user PHP-FPM Anda

echo "🔧 SAE Deploy Script"
echo "===================="

# 1. Cari user PHP-FPM (www = aaPanel, www-data = Ubuntu default)
PHP_USER="www"
if id "www-data" &>/dev/null; then PHP_USER="www-data"; fi
echo "👤 PHP user: $PHP_USER"

# 2. Permission storage & bootstrap/cache
echo "📁 Setting permissions..."
chmod -R 775 storage bootstrap/cache
chown -R $PHP_USER:$PHP_USER storage bootstrap/cache

# 3. Buat file log jika belum ada
echo "📄 Creating log file..."
touch storage/logs/laravel.log
chmod 664 storage/logs/laravel.log
chown $PHP_USER:$PHP_USER storage/logs/laravel.log

# 4. Storage symlink
echo "🔗 Creating storage link..."
php artisan storage:link --force 2>/dev/null || true

# 5. Cache Laravel
echo "⚡ Caching config, routes, views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Optimize
php artisan optimize:clear 2>/dev/null || true

echo "✅ Done!"

echo "✅ Done! Restart Nginx jika perlu: sudo systemctl reload nginx"