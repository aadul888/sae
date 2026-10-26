#!/bin/bash
# =============================================================================
# SAE — Deploy & Upgrade Script (Laravel 13 / PHP 8.3+)
# -----------------------------------------------------------------------------
# Jalankan dari root proyek di server:
#     cd /www/wwwroot/<domain> && bash deploy.sh
#
# Perubahan penting dibanding versi sebelumnya:
#   - TIDAK lagi menyembunyikan error (`2>/dev/null || true`) pada langkah kritis.
#     Versi lama tetap melaporkan "selesai" meski composer/migrate gagal.
#   - Menjalankan `composer install` bila vendor tidak ada / lock berubah.
#   - Memeriksa versi PHP (wajib >= 8.3 untuk Laravel 13).
#   - Backup database otomatis sebelum migrasi (bila mysqldump tersedia).
#   - Mendeteksi file .env yang belum dikonfigurasi.
# =============================================================================

set -uo pipefail

RED=$'\033[0;31m'; GREEN=$'\033[0;32m'; YELLOW=$'\033[1;33m'; CYAN=$'\033[0;36m'; NC=$'\033[0m'
ok()   { echo "${GREEN}[OK]${NC} $*"; }
warn() { echo "${YELLOW}[PERHATIAN]${NC} $*"; }
fail() { echo "${RED}[GAGAL]${NC} $*"; }
step() { echo; echo "${CYAN}==> $*${NC}"; }

FAILED=0
COMPOSER_CMD=""

echo "=============================================="
echo " SAE — Deploy & Upgrade"
echo "=============================================="

# -----------------------------------------------------------------------------
# 1. Prasyarat: PHP >= 8.3
# -----------------------------------------------------------------------------
step "1/9 Memeriksa PHP"

if ! command -v php >/dev/null 2>&1; then
    fail "Perintah 'php' tidak ditemukan. Pasang PHP 8.3 lewat aaPanel > App Store > PHP 8.3."
    exit 1
fi

PHP_VER=$(php -r 'echo PHP_VERSION;' 2>/dev/null || echo "0.0.0")

if php -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);' 2>/dev/null; then
    ok "PHP ${PHP_VER}"
else
    fail "PHP ${PHP_VER} terlalu lama. Laravel 13 membutuhkan PHP >= 8.3."
    fail "Di aaPanel: App Store > PHP 8.3 > Install, lalu set sebagai default CLI."
    exit 1
fi

MISSING_EXT=""
for ext in bcmath ctype curl dom fileinfo json mbstring openssl pdo pdo_mysql tokenizer xml zip; do
    if ! php -m 2>/dev/null | grep -qi "^${ext}$"; then
        MISSING_EXT="${MISSING_EXT} ${ext}"
    fi
done
if [ -n "${MISSING_EXT}" ]; then
    fail "Ekstensi PHP belum terpasang:${MISSING_EXT}"
    fail "Pasang di aaPanel > App Store > PHP 8.3 > Setting > Install extension."
    FAILED=1
else
    ok "Semua ekstensi wajib tersedia"
fi

# -----------------------------------------------------------------------------
# 2. Prasyarat: Composer
# -----------------------------------------------------------------------------
step "2/9 Memeriksa Composer"

if command -v composer >/dev/null 2>&1; then
    COMPOSER_CMD="composer"
    ok "Composer tersedia"
elif [ -f composer.phar ]; then
    COMPOSER_CMD="php composer.phar"
    ok "Memakai composer.phar lokal"
else
    fail "Composer tidak tersedia. Pasang dulu:"
    fail "  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer"
    FAILED=1
fi

# -----------------------------------------------------------------------------
# 3. Deteksi user web server
# -----------------------------------------------------------------------------
step "3/9 Mendeteksi user web server"

PHP_USER="www"
for candidate in www www-data nginx apache; do
    if id "$candidate" >/dev/null 2>&1; then
        PHP_USER="$candidate"
        break
    fi
done
if ! id "$PHP_USER" >/dev/null 2>&1; then
    PHP_USER=$(whoami)
fi
ok "User web server: ${PHP_USER}"

# -----------------------------------------------------------------------------
# 4. Pemeriksaan .env
# -----------------------------------------------------------------------------
step "4/9 Memeriksa konfigurasi .env"

if [ ! -f .env ]; then
    fail "Berkas .env belum ada."
    fail "Salin dari contoh lalu sesuaikan: cp .env.example .env && php artisan key:generate"
    exit 1
fi

ENV_APP_KEY=$(grep -E '^APP_KEY=' .env | head -n1 | cut -d'=' -f2-)
if [ -z "${ENV_APP_KEY}" ]; then
    warn "APP_KEY kosong. Membuat kunci baru..."
    php artisan key:generate --force && ok "APP_KEY dibuat"
fi

ENV_DB=$(grep -E '^DB_DATABASE=' .env | head -n1 | cut -d'=' -f2-)
ok "Database: ${ENV_DB:-'(tidak terbaca)'}"

ENV_URL=$(grep -E '^APP_URL=' .env | head -n1 | cut -d'=' -f2-)
ok "APP_URL: ${ENV_URL:-'(tidak terbaca)'}"

ENV_DEBUG=$(grep -E '^APP_DEBUG=' .env | head -n1 | cut -d'=' -f2-)
if [ "${ENV_DEBUG}" = "true" ]; then
    warn "APP_DEBUG=true. Untuk produksi sebaiknya false agar detail error tidak tampil ke pengguna."
fi

ENV_CORE=$(grep -E '^SAE_CORE_API_KEY=' .env | head -n1 | cut -d'=' -f2-)
if [ -z "${ENV_CORE}" ]; then
    warn "SAE_CORE_API_KEY kosong — telemetri ke sae-core tidak dikirim (aplikasi tetap normal)."
fi

# -----------------------------------------------------------------------------
# 5. Backup database (sebelum migrasi)
# -----------------------------------------------------------------------------
step "5/9 Backup database"

DB_HOST=$(grep -E '^DB_HOST=' .env | head -n1 | cut -d'=' -f2-)
DB_NAME=$(grep -E '^DB_DATABASE=' .env | head -n1 | cut -d'=' -f2-)
DB_USER=$(grep -E '^DB_USERNAME=' .env | head -n1 | cut -d'=' -f2-)
DB_PASS=$(grep -E '^DB_PASSWORD=' .env | head -n1 | cut -d'=' -f2-)

BACKUP_DIR="storage/backups"
mkdir -p "$BACKUP_DIR"
BACKUP_FILE="${BACKUP_DIR}/db-$(date +%Y%m%d-%H%M%S).sql"

if command -v mysqldump >/dev/null 2>&1 && [ -n "${DB_NAME}" ]; then
    if MYSQL_PWD="${DB_PASS}" mysqldump -h"${DB_HOST:-127.0.0.1}" -u"${DB_USER}" \
        --single-transaction --quick --routines "${DB_NAME}" > "${BACKUP_FILE}" 2>/dev/null; then
        SIZE=$(du -h "${BACKUP_FILE}" | cut -f1)
        ok "Backup tersimpan: ${BACKUP_FILE} (${SIZE})"
    else
        warn "mysqldump gagal. Lanjut tanpa backup otomatis — pastikan Anda punya backup manual."
        rm -f "${BACKUP_FILE}"
    fi
else
    warn "mysqldump tidak tersedia. Buat backup manual lewat aaPanel > Database > Backup."
fi

# -----------------------------------------------------------------------------
# 6. Composer install (kunci upgrade Laravel 13)
# -----------------------------------------------------------------------------
step "6/9 Memasang dependensi Composer"

if [ "${FAILED}" -ne 0 ]; then
    fail "Prasyarat belum terpenuhi — deploy dihentikan sebelum mengubah apa pun."
    exit 1
fi

if [ -n "${COMPOSER_CMD}" ]; then
    # PENTING: tanpa langkah ini, framework tetap versi lama meski kode sudah
    # diperbarui. Versi sebelumnya hanya menjalankan dump-autoload.
    if ${COMPOSER_CMD} install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -n 20; then
        ok "Dependensi terpasang"
    else
        fail "composer install GAGAL. Perbaiki pesan di atas sebelum melanjutkan."
        exit 1
    fi
fi

# -----------------------------------------------------------------------------
# 7. Migrasi database
# -----------------------------------------------------------------------------
step "7/9 Menjalankan migrasi database"

MIGRATE_OUT=$(php artisan migrate --force 2>&1)
MIGRATE_RC=$?
echo "${MIGRATE_OUT}"
if [ ${MIGRATE_RC} -eq 0 ]; then
    ok "Migrasi selesai"
else
    fail "Migrasi GAGAL (exit ${MIGRATE_RC})."
    fail "Pulihkan backup bila perlu:"
    fail "  mysql -u${DB_USER} -p ${DB_NAME} < ${BACKUP_FILE}"
    exit 1
fi

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
' 2>/dev/null || warn "Gagal menyinkronkan versi aplikasi ke tabel settings (tidak fatal)."

# -----------------------------------------------------------------------------
# 8. Storage link & cache
# -----------------------------------------------------------------------------
step "8/9 Menyiapkan storage dan cache"

php artisan storage:link --force 2>/dev/null || warn "storage:link dilewati (mungkin sudah ada)."
touch storage/logs/laravel.log 2>/dev/null || true
chmod 664 storage/logs/laravel.log 2>/dev/null || true

# Bersihkan SEMUA cache dulu, termasuk config hasil deploy sebelumnya.
php artisan optimize:clear 2>/dev/null || true

if php artisan config:cache >/dev/null 2>&1; then
    ok "Config ter-cache"
else
    warn "config:cache gagal (aplikasi tetap jalan tanpa cache config)."
fi

if php artisan route:cache >/dev/null 2>&1; then
    ok "Route ter-cache"
else
    warn "route:cache gagal — biasanya karena ada route dengan Closure. Aplikasi tetap normal."
fi

if php artisan view:cache >/dev/null 2>&1; then
    ok "View ter-cache"
else
    warn "view:cache gagal (aplikasi tetap merender view secara langsung)."
fi

# -----------------------------------------------------------------------------
# 9. Izin berkas & reload PHP
# -----------------------------------------------------------------------------
step "9/9 Mengatur izin berkas dan memuat ulang PHP"

chown -R "${PHP_USER}:${PHP_USER}" . 2>/dev/null || true
find . -type d -exec chmod 755 {} + 2>/dev/null || true
find . -type f -exec chmod 644 {} + 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
chown -R "${PHP_USER}:${PHP_USER}" storage bootstrap/cache 2>/dev/null || true
chmod +x deploy.sh 2>/dev/null || true
ok "Izin berkas diterapkan"

if command -v systemctl >/dev/null 2>&1; then
    for svc in php8.4-fpm php8.3-fpm php-fpm; do
        if systemctl is-active --quiet "$svc" 2>/dev/null; then
            systemctl reload "$svc" 2>/dev/null && ok "Reload ${svc}" && break
        fi
    done
fi

# aaPanel memakai OpenLiteSpeed (lsphp) atau PHP-FPM.
pkill -USR2 -u "${PHP_USER}" lsphp 2>/dev/null || true
if [ -f /usr/local/lsws/bin/lswsctrl ]; then
    /usr/local/lsws/bin/lswsctrl restart >/dev/null 2>&1 && ok "OpenLiteSpeed direstart" || true
fi

# -----------------------------------------------------------------------------
# Ringkasan
# -----------------------------------------------------------------------------
echo
echo "=============================================="
ok "DEPLOY SELESAI"
echo
echo "Versi terpasang:"
php artisan --version 2>/dev/null | sed 's/^/  /'
echo "Commit:"
git log --oneline -1 2>/dev/null | sed 's/^/  /'
echo
echo "Langkah berikutnya:"
echo "  1. Buka aplikasi di browser, pastikan halaman login tampil."
echo "  2. Login sebagai admin dan periksa beberapa menu utama."
echo "  3. Cek log bila ada masalah: tail -f storage/logs/laravel.log"
echo "=============================================="
