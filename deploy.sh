#!/bin/bash
# Jalankan di server setelah git clone / git pull
# Otomatis deteksi user webserver (www / www-data / nginx / apache)
# Standar: Folder 755, File 644, Writable dirs 775/777

echo "🔧 SAE Deploy & Permission Setup"
echo "================================"

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

# 2. Set ownership ke user webserver
echo "👤 Setting ownership to $PHP_USER..."
chown -R $PHP_USER:$PHP_USER . 2>/dev/null || true

# 3. Standar Linux Permission: Direktori 755, File 644
echo "📁 Setting default directory permissions to 755..."
find . -type d -exec chmod 755 {} + 2>/dev/null || true

echo "📄 Setting default file permissions to 644..."
find . -type f -exec chmod 644 {} + 2>/dev/null || true

# 4. Direktori writable Laravel & Git: 775
echo "🔒 Setting writable permissions (775) on storage, cache & git..."
chmod -R 775 storage bootstrap/cache .git 2>/dev/null || true
chown -R $PHP_USER:$PHP_USER storage bootstrap/cache .git 2>/dev/null || true

# Script executable
chmod +x deploy.sh 2>/dev/null || true

# 5. File log jika belum ada
echo "📄 Ensuring log file exists..."
touch storage/logs/laravel.log
chmod 664 storage/logs/laravel.log
chown $PHP_USER:$PHP_USER storage/logs/laravel.log

# 6. Storage symlink
echo "🔗 Creating storage link..."
php artisan storage:link --force 2>/dev/null || true

# 7. Cache Laravel & Optimize
echo "⚡ Optimizing Laravel..."
php artisan optimize:clear 2>/dev/null || true
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true

echo "✅ Deploy & permission setup complete!"