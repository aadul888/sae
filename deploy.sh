#!/bin/bash
# Jalankan di server setelah git pull
# sudo bash deploy.sh

echo "🔧 SAE Deploy Script"
echo "===================="

# 1. Permission storage & bootstrap/cache
echo "📁 Setting permissions..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 2. Buat file log jika belum ada
echo "📄 Creating log file..."
touch storage/logs/laravel.log
chmod 664 storage/logs/laravel.log
chown www-data:www-data storage/logs/laravel.log

# 3. Storage symlink
echo "🔗 Creating storage link..."
php artisan storage:link --force 2>/dev/null || true

# 4. Cache Laravel
echo "⚡ Caching config, routes, views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Optimize
php artisan optimize:clear 2>/dev/null || true

echo "✅ Done! Restart Nginx jika perlu: sudo systemctl reload nginx"