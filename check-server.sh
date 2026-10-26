#!/bin/bash
# =============================================================================
# SAE — Pemeriksa Kesiapan Server
# -----------------------------------------------------------------------------
# Jalankan di server SEBELUM upgrade:
#     cd /www/wwwroot/<domain-anda> && bash check-server.sh
#
# Skrip ini hanya MEMERIKSA, tidak mengubah apa pun.
# =============================================================================

RED=$'\033[0;31m'; GREEN=$'\033[0;32m'; YELLOW=$'\033[1;33m'; CYAN=$'\033[0;36m'; NC=$'\033[0m'
ok()   { echo "${GREEN}  OK   ${NC} $*"; }
bad()  { echo "${RED}  KURANG${NC} $*"; }
warn() { echo "${YELLOW}  CATAT ${NC} $*"; }

echo "=============================================="
echo " SAE — Pemeriksa Kesiapan Server"
echo "=============================================="

# -----------------------------------------------------------------------------
echo
echo "${CYAN}1. Versi PHP${NC}"
# -----------------------------------------------------------------------------
if command -v php >/dev/null 2>&1; then
    PHP_VER=$(php -r 'echo PHP_VERSION;' 2>/dev/null)
    echo "     php -v: ${PHP_VER}"
    if php -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);' 2>/dev/null; then
        ok "PHP >= 8.3 (syarat Laravel 13)"
    else
        bad "PHP ${PHP_VER} < 8.3 — WAJIB upgrade ke PHP 8.3"
    fi
else
    bad "perintah 'php' tidak ditemukan di PATH"
fi

# Versi PHP yang dipakai web server (bisa berbeda dari CLI)
echo "     Lokasi PHP yang terpasang:"
ls -d /www/server/php/*/ 2>/dev/null | sed 's|/www/server/php/||; s|/||' | while read -r v; do
    echo "       - PHP ${v}"
done

# -----------------------------------------------------------------------------
echo
echo "${CYAN}2. Ekstensi WAJIB${NC}"
echo "     (hanya yang benar-benar dibutuhkan aplikasi ini)"
# -----------------------------------------------------------------------------

# Ekstensi yang WAJIB terpasang eksplisit.
# Catatan: ctype, curl, dom, json, mbstring, openssl, pdo, tokenizer, xml
# SUDAH menyatu di PHP 8.3 standar dan tidak perlu dipasang terpisah.
REQUIRED="pdo_mysql zip gd"

MISSING=""
for ext in ${REQUIRED}; do
    if php -m 2>/dev/null | grep -qi "^${ext}$"; then
        ok "${ext}"
    else
        bad "${ext} — belum ada, WAJIB dipasang"
        MISSING="${MISSING} ${ext}"
    fi
done

# -----------------------------------------------------------------------------
echo
echo "${CYAN}3. Ekstensi yang biasanya SUDAH ADA (tidak perlu dipasang)${NC}"
# -----------------------------------------------------------------------------
for ext in ctype curl dom fileinfo filter hash iconv json libxml mbstring openssl pcre phar session tokenizer xml xmlwriter; do
    if php -m 2>/dev/null | grep -qi "^${ext}$"; then
        ok "${ext}"
    else
        warn "${ext} — tidak terdeteksi, tapi biasanya tersedia lewat polyfill Laravel"
    fi
done

# -----------------------------------------------------------------------------
echo
echo "${CYAN}4. Ekstensi OPSIONAL${NC}"
# -----------------------------------------------------------------------------
if php -m 2>/dev/null | grep -qi "^bcmath$"; then
    ok "bcmath (tidak dipakai aplikasi ini — aman diabaikan)"
else
    warn "bcmath tidak ada — TIDAK MASALAH, aplikasi ini tidak memakainya"
fi

if php -m 2>/dev/null | grep -qi "^intl$"; then
    ok "intl (bonus, bagus untuk format tanggal/locale)"
else
    warn "intl tidak ada — tidak wajib"
fi

# -----------------------------------------------------------------------------
echo
echo "${CYAN}5. Batas Upload (untuk impor Excel 20 MB)${NC}"
# -----------------------------------------------------------------------------
UPLOAD_MAX=$(php -r 'echo ini_get("upload_max_filesize");' 2>/dev/null)
POST_MAX=$(php -r 'echo ini_get("post_max_size");' 2>/dev/null)
MEM_LIMIT=$(php -r 'echo ini_get("memory_limit");' 2>/dev/null)
MAX_EXEC=$(php -r 'echo ini_get("max_execution_time");' 2>/dev/null)
echo "     upload_max_filesize = ${UPLOAD_MAX}"
echo "     post_max_size       = ${POST_MAX}"
echo "     memory_limit        = ${MEM_LIMIT}"
echo "     max_execution_time  = ${MAX_EXEC}"
echo "     Disarankan: upload 25M, post 30M, memory 512M, max_exec 300"

# -----------------------------------------------------------------------------
echo
echo "${CYAN}6. Composer${NC}"
# -----------------------------------------------------------------------------
if command -v composer >/dev/null 2>&1; then
    ok "composer $(composer --version 2>/dev/null | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -n1)"
elif [ -f composer.phar ]; then
    ok "composer.phar lokal tersedia"
elif [ -f /www/server/php/*/bin/composer ] 2>/dev/null; then
    ok "composer tersedia di folder PHP aaPanel"
else
    warn "composer tidak ada di PATH — mungkin tersedia di aaPanel"
    echo "     Cek: ls /www/server/php/*/bin/composer"
    echo "     Atau pasang: curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer"
fi

# -----------------------------------------------------------------------------
echo
echo "${CYAN}7. Git & Status Repo${NC}"
# -----------------------------------------------------------------------------
if command -v git >/dev/null 2>&1; then
    ok "git $(git --version | grep -oE '[0-9]+\.[0-9]+\.[0-9]+')"
    if [ -d .git ]; then
        ok "folder .git ada"
        echo "     Branch : $(git branch --show-current 2>/dev/null)"
        echo "     Commit : $(git log --oneline -1 2>/dev/null)"
        echo "     Remote : $(git remote get-url origin 2>/dev/null)"
        DIRTY=$(git status --porcelain 2>/dev/null | wc -l)
        if [ "${DIRTY}" -gt 0 ]; then
            warn "${DIRTY} file berubah lokal — akan tertimpa/konflik saat pull"
        else
            ok "working tree bersih"
        fi
    else
        bad "bukan repo git — perlu clone atau migrasi manual"
    fi
else
    bad "git tidak ditemukan"
fi

# -----------------------------------------------------------------------------
echo
echo "${CYAN}8. Berkas & Folder Penting${NC}"
# -----------------------------------------------------------------------------
for f in artisan composer.json .env; do
    [ -f "$f" ] && ok "$f" || bad "$f tidak ada (jalankan skrip dari root proyek)"
done

for d in storage bootstrap/cache; do
    if [ -d "$d" ]; then
        if [ -w "$d" ]; then ok "$d (dapat ditulis)"; else bad "$d TIDAK dapat ditulis"; fi
    else
        bad "$d tidak ada"
    fi
done

# -----------------------------------------------------------------------------
echo
echo "${CYAN}9. Koneksi Database${NC}"
# -----------------------------------------------------------------------------
if [ -f .env ]; then
    DB_HOST=$(grep -E '^DB_HOST=' .env | head -n1 | cut -d'=' -f2-)
    DB_NAME=$(grep -E '^DB_DATABASE=' .env | head -n1 | cut -d'=' -f2-)
    DB_USER=$(grep -E '^DB_USERNAME=' .env | head -n1 | cut -d'=' -f2-)
    DB_PASS=$(grep -E '^DB_PASSWORD=' .env | head -n1 | cut -d'=' -f2-)
    echo "     Host=${DB_HOST}  DB=${DB_NAME}  User=${DB_USER}"
    if command -v mysql >/dev/null 2>&1; then
        if MYSQL_PWD="${DB_PASS}" mysql -h"${DB_HOST}" -u"${DB_USER}" -e "SELECT 1" "${DB_NAME}" >/dev/null 2>&1; then
            ok "koneksi database berhasil"
        else
            bad "koneksi database GAGAL — periksa DB_USERNAME/DB_PASSWORD di .env"
        fi
    else
        warn "klien 'mysql' tidak ada — tidak dapat menguji koneksi"
    fi

    ENV_DEBUG=$(grep -E '^APP_DEBUG=' .env | head -n1 | cut -d'=' -f2-)
    if [ "${ENV_DEBUG}" = "true" ]; then
        warn "APP_DEBUG=true — untuk produksi sebaiknya false"
    else
        ok "APP_DEBUG=${ENV_DEBUG}"
    fi
fi

# -----------------------------------------------------------------------------
echo
echo "${CYAN}10. Versi Laravel Terpasang${NC}"
# -----------------------------------------------------------------------------
if [ -f vendor/laravel/framework/composer.json ]; then
    LV=$(php -r '$j=json_decode(file_get_contents("vendor/laravel/framework/composer.json"),true); echo $j["version"] ?? "?";' 2>/dev/null)
    if [ -z "${LV}" ] || [ "${LV}" = "?" ]; then
        # Sebagian instalasi tidak menulis "version" pada composer.json paket.
        LV=$(php artisan --version 2>/dev/null | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -n1)
    fi
    echo "     Laravel terpasang: ${LV:-tidak terbaca}"
else
    warn "vendor/ belum ada — composer install belum dijalankan"
fi

# -----------------------------------------------------------------------------
echo
echo "=============================================="
if [ -n "${MISSING}" ]; then
    echo "${RED}RINGKASAN: ekstensi berikut WAJIB dipasang:${NC}${MISSING}"
    echo
    echo "Cara pasang di aaPanel:"
    echo "  App Store > PHP 8.3 > Setting > Install extension"
    echo "  Cari: pdo_mysql, zip, gd  (lalu klik Install + Reload)"
else
    echo "${GREEN}RINGKASAN: ekstensi wajib sudah lengkap.${NC}"
fi
echo
echo "Catatan: daftar ekstensi di aaPanel HANYA menampilkan ekstensi"
echo "yang belum terpasang. Ekstensi seperti ctype, curl, dom, json,"
echo "mbstring, openssl, pdo, tokenizer, xml sudah menyatu di PHP 8.3"
echo "sehingga TIDAK muncul di daftar tersebut — itu normal."
echo "=============================================="
