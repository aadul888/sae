#!/bin/bash
# =============================================================================
# SAE - Hardening vhost Nginx: eksekusi PHP hanya untuk /index.php
# -----------------------------------------------------------------------------
# Masalah yang diperbaiki:
#   Konfigurasi bawaan aaPanel memuat `include enable-php-XX.conf;` yang berisi
#   `location ~ [^/]\.php(/|$)` → SEMUA berkas .php di bawah DocumentRoot
#   dieksekusi, termasuk yang ada di folder unggahan (public/uploads,
#   public/storage). Itu jalur remote-code-execution bila ada .php yang lolos.
#
# Yang dilakukan skrip ini (per berkas vhost):
#   1. Backup vhost ke <vhost>.sae-bak.<timestamp>
#   2. Ambil blok handler PHP dari file `enable-php-*.conf` yang di-include,
#      lalu ubah targetnya menjadi `location = /index.php` (hanya entry point).
#   3. Hapus semua `location ~ ...php...` lain di vhost.
#   4. Sisipkan blok penjaga: `location ~ \.php$ { deny all; }`, `autoindex off;`,
#      dan larangan akses berkas sensitif (app/config/storage internal).
#   5. `nginx -t` → reload. Bila `nginx -t` GAGAL, vhost dipulihkan dari backup.
#
# Idempotent: boleh dijalankan berkali-kali (blok lama dihapus dulu).
#
# Pemakaian:
#   bash harden-nginx.sh                      # deteksi domain dari .env APP_URL
#   bash harden-nginx.sh sae.smakpal.sch.id   # domain eksplisit
#   bash harden-nginx.sh --dry-run            # lihat hasil tanpa mengubah apa pun
#   bash harden-nginx.sh --socket unix:/tmp/php-cgi-83.sock   # bila handler tak ketemu
#   bash harden-nginx.sh --no-reload          # hanya untuk pengujian
# =============================================================================

set -uo pipefail

RED=$'\033[0;31m'; GREEN=$'\033[0;32m'; YELLOW=$'\033[1;33m'; CYAN=$'\033[0;36m'; NC=$'\033[0m'
ok()   { echo "${GREEN}[OK]${NC} $*"; }
warn() { echo "${YELLOW}[PERHATIAN]${NC} $*"; }
fail() { echo "${RED}[GAGAL]${NC} $*"; }
step() { echo; echo "${CYAN}==> $*${NC}"; }

DRY=0
NO_RELOAD=0
DOMAIN=""
SOCKET=""

while [ $# -gt 0 ]; do
    case "$1" in
        --dry-run) DRY=1 ;;
        --no-reload) NO_RELOAD=1 ;;
        --socket) SOCKET="${2:-}"; shift ;;
        --help|-h) sed -n '1,40p' "$0"; exit 0 ;;
        -*) fail "Opsi tidak dikenal: $1"; exit 2 ;;
        *) DOMAIN="$1" ;;
    esac
    shift
done

# --- PHP wajib ada (server Laravel, pasti ada) -------------------------------
if ! command -v php >/dev/null 2>&1; then
    fail "Perintah 'php' tidak ditemukan."
    exit 1
fi

# --- Deteksi domain ---------------------------------------------------------
if [ -z "${DOMAIN}" ] && [ -f .env ]; then
    APP_URL=$(grep -E '^APP_URL=' .env | head -n1 | cut -d'=' -f2- | tr -d '"' | tr -d "'")
    DOMAIN=$(echo "${APP_URL}" | sed -E 's~^https?://~~; s~/.*$~~; s~:[0-9]+$~~')
fi
if [ -z "${DOMAIN}" ]; then
    fail "Domain tidak diketahui. Jalankan: bash harden-nginx.sh <domain-anda>"
    exit 1
fi
ok "Domain: ${DOMAIN}"

# --- Cari berkas vhost ------------------------------------------------------
CANDIDATES=""
if [ -f "/www/server/panel/vhost/nginx/${DOMAIN}.conf" ]; then
    CANDIDATES="/www/server/panel/vhost/nginx/${DOMAIN}.conf"
else
    for dir in /etc/nginx/conf.d /etc/nginx/sites-enabled /etc/nginx/sites-available /etc/openresty/nginx/conf.d; do
        [ -d "${dir}" ] || continue
        for f in "${dir}"/*.conf; do
            [ -f "$f" ] || continue
            if grep -qE "server_name[^;]*\b${DOMAIN}\b" "$f" 2>/dev/null; then
                CANDIDATES="${CANDIDATES}${f}"$'\n'
            fi
        done
    done
    CANDIDATES=$(printf '%s' "${CANDIDATES}" | sed '/^$/d')
fi

# Override untuk instalasi non-standar / pengujian:
#   SAE_VHOST_DIR=/path/ke/folder bash harden-nginx.sh <domain>
if [ -n "${SAE_VHOST_DIR:-}" ] && [ -d "${SAE_VHOST_DIR}" ]; then
    CANDIDATES=$(grep -rlE "server_name[^;]*\b${DOMAIN}\b" "${SAE_VHOST_DIR}" 2>/dev/null | grep -E '\.conf$')
fi

if [ -z "${CANDIDATES}" ]; then
    fail "Berkas vhost untuk '${DOMAIN}' tidak ditemukan."
    fail "Cari manual: grep -rl 'server_name.*${DOMAIN}' /etc/nginx /www/server/panel/vhost/nginx"
    exit 1
fi

step "Berkas vhost yang diproses"
printf '%s\n' "${CANDIDATES}" | sed 's/^/     - /'

# --- Nginx tersedia? --------------------------------------------------------
NGINX_BIN=""
if command -v nginx >/dev/null 2>&1; then
    NGINX_BIN="nginx"
elif [ -x /www/server/nginx/sbin/nginx ]; then
    NGINX_BIN="/www/server/nginx/sbin/nginx"
fi
if [ -z "${NGINX_BIN}" ]; then
    warn "Binary nginx tidak ditemukan; pemeriksaan 'nginx -t' dilewati."
fi

# =============================================================================
# Transformer (PHP) - dipanggil per berkas vhost.
#   argv[1] = path vhost, argv[2] = 1 bila dry-run, argv[3] = socket paksa (opsi)
# =============================================================================
run_transformer() {
TRANSFORMER_PHP=$(mktemp 2>/dev/null || echo "${TMPDIR:-/tmp}/sae-harden-$$.php")
cat > "${TRANSFORMER_PHP}" <<'PHPEOF'
<?php
$path   = $argv[1];
$dry    = ($argv[2] ?? '0') === '1';
$forced = $argv[3] ?? '';
$domain = $argv[4] ?? '';

$src = file_get_contents($path);
if ($src === false) { fwrite(STDERR, "Tidak bisa membaca {$path}\n"); exit(1); }

$MARK_START = '# >>> SAE php-guard (dikelola harden-nginx.sh - jangan diubah manual)';
$MARK_END   = '# <<< SAE php-guard';

/** Cari blok `... { ... }` dengan pencocokan kurung (mengabaikan $ dalam string). */
function braceBlock(string $s, int $start): ?array
{
    $open = strpos($s, '{', $start);
    if ($open === false) { return null; }
    $depth = 0; $len = strlen($s);
    for ($i = $open; $i < $len; $i++) {
        $c = $s[$i];
        if ($c === '#') { $nl = strpos($s, "\n", $i); $i = ($nl === false) ? $len : $nl; continue; }
        if ($c === '{') { $depth++; }
        elseif ($c === '}') { $depth--; if ($depth === 0) { return [$start, $i]; } }
    }
    return null;
}

/** Buang blok yang cocok dengan regex header, beserta baris sisa (trailing newline). */
function stripBlocks(string $s, string $headerRegex): string
{
    while (preg_match($headerRegex, $s, $m, PREG_OFFSET_CAPTURE)) {
        $blk = braceBlock($s, $m[0][1]);
        if ($blk === null) { break; }
        [$a, $b] = $blk;
        while ($b + 1 < strlen($s) && ($s[$b + 1] === "\n" || $s[$b + 1] === "\r")) { $b++; }
        $s = substr($s, 0, $a) . substr($s, $b + 1);
    }
    return $s;
}

/** Blok penjaga lama dihapus BELAKANGAN (setelah handler diselamatkan agar tidak hilang). */

// ---- 1. Sumber handler PHP -------------------------------------------------
$handlerBlock = null;   // blok location (lengkap dengan tanda kurung)
$sourceNote   = '';

// (a) Blok `location = /index.php` yang sudah ada - pertahankan apa adanya.
if (preg_match('/([ \t]*location\s*=\s*\/index\.php\s*\{)/', $src, $m, PREG_OFFSET_CAPTURE)) {
    $blk = braceBlock($src, $m[1][1]);
    if ($blk !== null) {
        $handlerBlock = substr($src, $blk[0], $blk[1] - $blk[0] + 1);
        $sourceNote = 'memakai handler /index.php yang sudah ada di vhost';
    }
}

// (b) Blok dari `include enable-php-XX.conf;` - ambil lalu retarget ke /index.php.
// Semua include handler PHP dicatat untuk dibersihkan belakangan.
$phpIncludes = [];
if (preg_match_all('/^[ \t]*include\s+([^\s;]+)\s*;[ \t]*$/mi', $src, $mAll)) {
    foreach ($mAll[1] as $kandidat) {
        if (stripos($kandidat, 'php') !== false) { $phpIncludes[] = trim($kandidat, "\"'"); }
    }
}
$inc = $phpIncludes[0] ?? '';
if ($handlerBlock === null && $inc !== '') {
    $incPath = $inc[0] === '/'
        ? $inc
        : rtrim(dirname($path), '/') . '/' . $inc;          // relatif vhost
    $dirs = ['/www/server/nginx/conf', '/etc/nginx', '/etc/nginx/conf.d', '/usr/local/nginx/conf'];
    if (!is_file($incPath)) {
        foreach ($dirs as $d) { if (is_file($d . '/' . $inc)) { $incPath = $d . '/' . $inc; break; } }
    }
    if (is_file($incPath)) {
        $incSrc = (string) file_get_contents($incPath);
        if (preg_match('/[ \t]*location\s+[^\n{]*\\\.php[^\n{]*\{/i', $incSrc, $mm, PREG_OFFSET_CAPTURE)) {
            $blk = braceBlock($incSrc, $mm[0][1]);
            if ($blk !== null) {
                $handlerBlock = substr($incSrc, $blk[0], $blk[1] - $blk[0] + 1);
                // Retarget: hanya /index.php
                $handlerBlock = preg_replace('/location\s+[^\n{]*\\\.php[^\n{]*\{/i', 'location = /index.php {', $handlerBlock, 1);
                $sourceNote = "handler diambil dari {$incPath} lalu diarahkan ke /index.php";
            }
        }
    } else {
        $sourceNote = "PERINGATAN: tidak menemukan berkas include {$inc}";
    }
}

// (c) Socket dipaksa / blok `location ~ \.php$` yang sudah ada di vhost.
if ($handlerBlock === null && preg_match('/[ \t]*location\s+[^\n{]*\\\.php[^\n{]*\{/i', $src, $m, PREG_OFFSET_CAPTURE)) {
    $blk = braceBlock($src, $m[0][1]);
    if ($blk !== null) {
        $handlerBlock = substr($src, $blk[0], $blk[1] - $blk[0] + 1);
        $handlerBlock = preg_replace('/location\s+[^\n{]*\\\.php[^\n{]*\{/i', 'location = /index.php {', $handlerBlock, 1);
        $sourceNote = 'memakai handler .php yang sudah ada di vhost, diarahkan ke /index.php';
    }
}
if ($handlerBlock === null) {
    $sock = $forced !== '' ? $forced : 'unix:/tmp/php-cgi-83.sock';
    $handlerBlock = "    location = /index.php {\n"
        . "        fastcgi_pass {$sock};\n"
        . "        fastcgi_index index.php;\n"
        . "        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;\n"
        . "        include fastcgi_params;\n"
        . "        fastcgi_read_timeout 300;\n"
        . "    }";
    $sourceNote = $forced !== ''
        ? "handler dibuat baru memakai --socket {$forced}"
        : "handler dibuat baru (socket default) - VERIFIKASI fastcgi_pass!";
}

// ---- 2. Bersihkan: blok penjaga lama, include enable-php, dan semua location .php ----
$src = preg_replace('/[ \t]*' . preg_quote($MARK_START, '/') . '.*?' . preg_quote($MARK_END, '/') . '[^\n]*\n/s', '', $src, -1, $nMark);
$removedMarker = $nMark > 0;
$src = preg_replace('/^[ \t]*include\s+[^\s;]*enable-php[^\s;]*\s*;[ \t]*\n/mi', '', $src);
foreach ($phpIncludes as $incHapus) {
    $src = preg_replace('/^[ \t]*include\s+' . preg_quote($incHapus, '/') . '\s*;[ \t]*\n/mi', '', $src);
}
$src = stripBlocks($src, '/[ \t]*location\s*=\s*\/index\.php\s*\{/');
$src = stripBlocks($src, '/[ \t]*location\s+[^\n{]*\\\.php[^\n{]*\{/i');

// ---- 3. Blok penjaga -------------------------------------------------------
// Rapikan indentasi blok handler: tiap baris di-trim lalu diberi indent 8 spasi.
$lines = preg_split('/\r?\n/', trim($handlerBlock));
$handlerBlock = implode("\n", array_map(static fn ($l) => '        ' . trim($l), $lines));

$indent = '    ';
$guard = "{$indent}{$MARK_START}\n"
    . "{$indent}autoindex off;\n"
    . "{$handlerBlock}\n"
    . "{$indent}# Semua .php lain (termasuk di folder unggahan) tidak dieksekusi.\n"
    . "{$indent}location ~ \.php\$ { deny all; return 403; }\n"
    . "{$indent}location ~ ^/(app|bootstrap|config|database|resources|tests|vendor)/ { deny all; return 404; }\n"
    . "{$indent}location ~ ^/storage/(logs|framework|app/private|app/archives)/ { deny all; return 404; }\n"
    . "{$indent}{$MARK_END}\n";

// ---- 4. Sisipkan ke `server { ... }` yang ber-root dan cocok domain --------
$domainRe = $domain !== '' ? '/\b' . preg_quote($domain, '/') . '\b/i' : null;

$slots = [];
$pos = 0;
while (preg_match('/\bserver\s*\{/', $src, $m, PREG_OFFSET_CAPTURE, $pos)) {
    $blk = braceBlock($src, $m[0][1]);
    if ($blk === null) { break; }
    [$a, $b] = $blk;
    $slots[] = [$a, $b, substr($src, $a, $b - $a + 1)];
    $pos = $b + 1;
}

$hasRootFn = static fn (string $body): bool =>
    preg_match('/^[ \t]*root\s+/mi', $body) === 1 && preg_match('/\bupstream\b/', $body) !== 1;
$nameOkFn = static function (string $body) use ($domainRe): bool {
    if ($domainRe === null) { return true; }
    return preg_match('/^[ \t]*server_name\s+([^;]+);/mi', $body, $sn) === 1 && preg_match($domainRe, $sn[1]) === 1;
};

$adaCocok = false;
foreach ($slots as [$a, $b, $body]) {
    if ($hasRootFn($body) && $nameOkFn($body)) { $adaCocok = true; break; }
}
$fallback = !$adaCocok;   // server_name tak ketemu → sisipkan ke semua blok ber-root

$out = ''; $pos = 0; $injected = 0;
foreach ($slots as [$a, $b, $body]) {
    $out .= substr($src, $pos, $a - $pos);
    if ($hasRootFn($body) && ($fallback || $nameOkFn($body))) {
        $out .= substr($body, 0, -1) . "\n" . $guard . "}\n";
        $injected++;
    } else {
        $out .= $body;
    }
    $pos = $b + 1;
}
$out .= substr($src, $pos);

if ($fallback && $injected > 0) {
    fwrite(STDERR, "PERINGATAN: server_name '{$domain}' tidak ditemukan; penjaga disisipkan ke {$injected} blok server ber-root. Periksa hasilnya.\n");
}

if ($injected === 0) {
    fwrite(STDERR, "Tidak menemukan blok 'server { ... }' dengan direktif 'root' di {$path}\n");
    exit(2);
}

if ($dry) {
    echo "----- HASIL (dry-run) {$path} -----\n";
    echo $out;
    exit(0);
}

// Jangan tulis berkas bila tidak ada perubahan nyata.
if ($out === file_get_contents($path)) {
    echo "tidak-berubah\n";
    exit(0);
}

$tmp = $path . '.sae-tmp';
if (file_put_contents($tmp, $out) === false) { fwrite(STDERR, "Gagal menulis {$tmp}\n"); exit(1); }
rename($tmp, $path);

echo "diubah={$injected} marker_lama=" . ($removedMarker ? 'ya' : 'tidak') . " | {$sourceNote}\n";
exit(0);
PHPEOF
php "${TRANSFORMER_PHP}" "$1" "$2" "${3:-}" "${DOMAIN}"
RC=$?
rm -f "${TRANSFORMER_PHP}"
return ${RC}
}

# =============================================================================
# Jalankan per berkas vhost
# =============================================================================
UBAH=0
for VHOST in ${CANDIDATES//$'\n'/ }; do
    step "Memproses: ${VHOST}"

    if [ ! -w "${VHOST}" ]; then
        fail "Tidak dapat menulis ${VHOST} (jalankan sebagai root: sudo bash $0)"
        exit 1
    fi

    if [ "${DRY}" = "1" ]; then
        run_transformer "${VHOST}" 1 "${SOCKET}"
        ok "Dry-run selesai - tidak ada berkas diubah."
        continue
    fi

    BAK="${VHOST}.sae-bak.$(date +%Y%m%d-%H%M%S)"
    cp -p "${VHOST}" "${BAK}" && ok "Backup: ${BAK}"

    HASIL=$(run_transformer "${VHOST}" 0 "${SOCKET}" 2>&1)
    RC=$?
    if [ ${RC} -ne 0 ]; then
        fail "${HASIL}"
        fail "vhost tidak diubah. Bila pesan menyebut handler tidak ditemukan, jalankan:"
        fail "  bash $0 --socket unix:/tmp/php-cgi-XX.sock"
        exit 1
    fi
    ok "${HASIL}"
    UBAH=$((UBAH + 1))

    # ---- Validasi konfigurasi; rollback bila gagal ----
    if [ -n "${NGINX_BIN}" ] && [ "${NO_RELOAD}" = "0" ]; then
        if ${NGINX_BIN} -t 2>&1 | sed 's/^/     /'; then
            ok "nginx -t lolos"
        else
            fail "nginx -t GAGAL - memulihkan dari backup."
            cp -p "${BAK}" "${VHOST}"
            fail "vhost dipulihkan. Kirim pesan galat di atas untuk diperbaiki."
            exit 1
        fi
    fi
done

if [ "${DRY}" = "1" ]; then
    echo
    warn "Mode dry-run. Untuk menerapkan: bash $0 ${DOMAIN}"
    exit 0
fi

if [ "${UBAH}" = "0" ]; then
    ok "Tidak ada perubahan (vhost sudah sesuai)."
fi

# ---- Reload ----------------------------------------------------------------
if [ "${NO_RELOAD}" = "1" ]; then
    warn "--no-reload: nginx TIDAK di-reload (mode pengujian)."
    exit 0
fi

step "Reload nginx"
if command -v systemctl >/dev/null 2>&1 && systemctl is-active --quiet nginx 2>/dev/null; then
    systemctl reload nginx && ok "systemctl reload nginx"
elif [ -n "${NGINX_BIN}" ]; then
    ${NGINX_BIN} -s reload && ok "nginx -s reload"
else
    warn "Reload manual diperlukan (mis. aaPanel > Website > Reload, atau service nginx reload)."
fi

# ---- Verifikasi -----------------------------------------------------------
step "Verifikasi"
APPDIR=$(pwd)
if [ -d "${APPDIR}/public/uploads" ] || mkdir -p "${APPDIR}/public/uploads" 2>/dev/null; then
    PROBE="${APPDIR}/public/uploads/__sae_probe.php"
    echo '<?php echo "RCE-".PHP_VERSION;' > "${PROBE}" 2>/dev/null
    if [ -f "${PROBE}" ]; then
        KODE=$(curl -s -o /dev/null -w '%{http_code}' -H "Host: ${DOMAIN}" "http://127.0.0.1/uploads/__sae_probe.php" 2>/dev/null)
        BODY=$(curl -s -H "Host: ${DOMAIN}" "http://127.0.0.1/uploads/__sae_probe.php" 2>/dev/null | head -c 40)
        rm -f "${PROBE}"
        if [ "${KODE}" = "403" ] || [ "${KODE}" = "404" ] || [ "${KODE}" = "000" ]; then
            ok "probe .php di folder unggahan ditolak (HTTP ${KODE}) - aman"
        elif echo "${BODY}" | grep -q 'RCE-'; then
            fail "probe .php DIEKSEKUSI (HTTP ${KODE}). Hardening belum berlaku - cek urutan location/panel."
        else
            warn "probe .php HTTP ${KODE} (bukan 403/404, tapi tidak dieksekusi). Periksa manual."
        fi
    else
        warn "Tidak bisa menulis berkas probe di public/uploads (izin). Uji manual lewat browser."
    fi
else
    warn "Folder public/uploads tidak ada. Uji manual setelah unggah berkas pertama."
fi

cat <<'SARAN'

Langkah lanjutan:
  1. Uji unggah 1 foto (siswa/GTK) dari aplikasi → pastikan tampil.
  2. Bila kelak mengubah PHP version / setting situs dari panel aaPanel, jalankan
     ulang skrip ini:  bash harden-nginx.sh
     (panel dapat menulis ulang baris `include enable-php-*.conf;`)
SARAN
